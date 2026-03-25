<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Flow;
use App\Models\FlowExecution;
use App\Models\Message;
use App\Services\AIService;
use App\Services\EvolutionApiService;
use App\Services\ElevenLabsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Tempo máximo (segundos). Ollama pode levar 2-3 min. */
    public int $timeout = 300;
    /** Tentativas antes de failed_jobs */
    public int $tries = 3;

    public function __construct(
        public string $instanceName,
        public string $contact,
        public string $remoteJid,
        public string $messageText,
        public int $messageId
    ) {}

    public function handle(EvolutionApiService $evolution): void
    {
        Log::info('ProcessIncomingMessageJob iniciado', [
            'instance' => $this->instanceName,
            'contact' => $this->contact,
            'text_preview' => substr($this->messageText, 0, 80),
        ]);

        $instance = \App\Models\BotInstance::where('instance_name', $this->instanceName)
            ->with('user:id,tenant_id')
            ->first();
        if (! $instance) {
            Log::error('BotInstance não encontrada para instanciar a IA', ['instance' => $this->instanceName]);

            return;
        }
        // Mesmo critério que flows/sync: instância pode ter tenant só no user ou único tenant na base.
        $tenantId = $instance->effectiveTenantId();

        $aiService = new AIService($tenantId);
        $elevenLabs = new ElevenLabsService($tenantId);

        $flows = Flow::query()
            ->where('is_active', true)
            ->where('tenant_id', $tenantId)
            ->where(function ($q) {
                $q->where('instance_name', $this->instanceName)->orWhereNull('instance_name');
            })
            ->orderBy('priority', 'desc')
            ->get();

        if ($flows->isEmpty()) {
            Log::warning('ProcessIncomingMessageJob: nenhum fluxo ativo para este tenant/instância', [
                'tenant_id' => $tenantId,
                'instance' => $this->instanceName,
            ]);
        }

        $executed = false;
        $flowThatFailed = null;
        foreach ($flows as $flow) {
            if (! $this->matchFlow($flow)) {
                continue;
            }

            try {
                $this->executeFlow($flow, $evolution, $aiService, $elevenLabs);
                FlowExecution::create([
                    'flow_id' => $flow->id,
                    'contact' => $this->contact,
                    'trigger_message' => $this->messageText,
                ]);
                $executed = true;
            } catch (\Throwable $e) {
                $flowThatFailed = $flow;
                Log::error('ProcessIncomingMessageJob flow error', [
                    'flow_id' => $flow->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
            break;
        }

        // Se o fluxo deu erro (ex: timeout Ollama, falha ao enviar), enviar mensagem de erro ao usuário
        if (! $executed && $flowThatFailed) {
            $fallback = $this->getFlowErrorMessage($flowThatFailed);
            if ($fallback !== '') {
                try {
                    $evolution->sendText($this->instanceName, $this->contact, $fallback);
                } catch (\Throwable $e) {
                    Log::error('ProcessIncomingMessageJob falha ao enviar mensagem de erro', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        if (! $executed && ! $flowThatFailed) {
            Log::info('Nenhum fluxo correspondeu à mensagem', [
                'message_preview' => strlen($this->messageText) > 60 ? substr($this->messageText, 0, 60) . '...' : $this->messageText,
            ]);
            $noFlowReply = trim((string) config('services.evolution.no_flow_reply'));
            if ($noFlowReply !== '') {
                try {
                    $evolution->sendText($this->instanceName, $this->contact, $noFlowReply);
                } catch (\Throwable $e) {
                    Log::error('ProcessIncomingMessageJob: falha ao enviar mensagem de nenhum fluxo', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    private function matchFlow(Flow $flow): bool
    {
        $triggers = $flow->triggers ?? [];
        if (count($triggers) === 0) {
            return false;
        }

        $text = mb_strtolower($this->messageText);

        foreach ($triggers as $trigger) {
            $type = $trigger['type'] ?? '';
            $value = $trigger['value'] ?? '';

            if ($type === 'catch_all') {
                return true;
            }
            if ($type === 'exact' && $text === mb_strtolower($value)) {
                return true;
            }
            if ($type === 'contains' && str_contains($text, mb_strtolower($value))) {
                return true;
            }
            if ($type === 'starts_with' && str_starts_with($text, mb_strtolower($value))) {
                return true;
            }
        }

        return false;
    }

    private function getFlowErrorMessage(Flow $flow): string
    {
        $actions = $flow->actions ?? [];
        foreach ($actions as $action) {
            if (($action['type'] ?? '') === 'ai_response') {
                $msg = trim((string) ($action['error_message'] ?? ''));
                return $msg !== '' ? $msg : 'Desculpe, não consegui processar. Tente de novo.';
            }
        }
        return 'Desculpe, não consegui processar. Tente de novo.';
    }

    private function executeFlow(Flow $flow, EvolutionApiService $evolution, AIService $aiService, ElevenLabsService $elevenLabs): void
    {
        $actions = $flow->actions ?? [];
        $message = Message::withoutGlobalScopes()->find($this->messageId);
        $conversationId = $message?->conversation_id;

        foreach ($actions as $action) {
            $type = $action['type'] ?? '';

            if ($type === 'send_message') {
                $content = trim((string) ($action['content'] ?? ''));
                if ($content !== '') {
                    $evolution->sendText($this->instanceName, $this->contact, $content);
                }
                continue;
            }

            if ($type === 'wait') {
                $duration = (int) ($action['duration'] ?? 1000);
                usleep($duration * 1000);
                continue;
            }

            if ($type === 'ai_response') {
                $this->executeAiResponse($action, $evolution, $aiService, $elevenLabs, $conversationId, $flow);
                continue;
            }

            if ($type === 'conditional') {
                $this->executeConditional($action, $evolution, $aiService, $elevenLabs, $conversationId, $flow);
            }
        }
    }

    private function executeAiResponse(
        array $action,
        EvolutionApiService $evolution,
        AIService $aiService,
        ElevenLabsService $elevenLabs,
        ?int $conversationId,
        Flow $flow,
    ): void {
        $prompt = $this->composeAiPromptFromFlowAndAction($flow, $action);
        $provider = $action['provider'] ?? null;
        $model = $action['model'] ?? null;
        $useContext = isset($action['use_context']) ? ! empty($action['use_context']) : true;

        $history = [];
        if ($useContext && $conversationId) {
            $messages = Message::withoutGlobalScopes()
                ->where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse()
                ->values();
            foreach ($messages as $msg) {
                $history[] = [
                    'message' => $msg->message,
                    'direction' => $msg->direction,
                    'timestamp' => $msg->timestamp?->toIso8601String(),
                ];
            }
        }

        try {
            $response = $aiService->generateResponse($prompt, $this->messageText, $provider, $model, $history);
            $response = trim($response);
            $response = $this->stripResponseIfEchoesFlowDescription($response, $flow, $action);
            if ($response === '') {
                Log::warning('ProcessIncomingMessageJob: resposta da IA vazia após sanitização — usando fallback', [
                    'flow_id' => $flow->id,
                    'instance' => $this->instanceName,
                ]);
                $response = trim((string) ($action['error_message'] ?? ''));
                if ($response === '') {
                    $response = 'Desculpe, não consegui formular uma resposta agora. Pode repetir em uma frase?';
                }
            }
            if ($response !== '') {
                $sendAudio = ! empty($action['send_audio']) || ($action['response_type'] ?? '') === 'audio';
                if ($sendAudio) {
                    $audioBase64 = $elevenLabs->textToSpeech($response);
                    if ($audioBase64 !== '') {
                        $evolution->sendAudio($this->instanceName, $this->contact, $audioBase64);
                    } else {
                        $evolution->sendText($this->instanceName, $this->contact, $response);
                    }
                } else {
                    $evolution->sendText($this->instanceName, $this->contact, $response);
                }
                if ($conversationId) {
                    $convTenant = Conversation::withoutGlobalScopes()->whereKey($conversationId)->value('tenant_id');
                    Message::withoutGlobalScopes()->create([
                        'conversation_id' => $conversationId,
                        'instance_name' => $this->instanceName,
                        'message_id' => 'out_' . uniqid('', true),
                        'from' => $this->instanceName,
                        'to' => $this->remoteJid,
                        'message' => $response,
                        'direction' => 'outgoing',
                        'timestamp' => now(),
                        'tenant_id' => $convTenant,
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('AI response failed in flow', [
                'error' => $e->getMessage(),
                'instance' => $this->instanceName,
                'contact' => $this->contact,
                'provider' => $action['provider'] ?? null,
            ]);
            $fallback = $action['error_message'] ?? 'Desculpe, não consegui processar. Tente de novo.';
            if ($fallback !== '') {
                $evolution->sendText($this->instanceName, $this->contact, $fallback);
            }
        }
    }

    private function executeConditional(array $action, EvolutionApiService $evolution, AIService $aiService, ElevenLabsService $elevenLabs, ?int $conversationId, Flow $flow): void
    {
        $conditions = $action['conditions'] ?? [];
        $text = mb_strtolower($this->messageText);

        foreach ($conditions as $condition) {
            if (! empty($condition['default'])) {
                continue;
            }
            $type = $condition['type'] ?? '';
            $value = $condition['value'] ?? '';
            $matches = false;
            if ($type === 'contains' && str_contains($text, mb_strtolower($value))) {
                $matches = true;
            }
            if ($type === 'exact' && $text === mb_strtolower($value)) {
                $matches = true;
            }
            if ($type === 'starts_with' && str_starts_with($text, mb_strtolower($value))) {
                $matches = true;
            }
            if ($matches && ! empty($condition['actions'])) {
                foreach ($condition['actions'] as $sub) {
                    if (($sub['type'] ?? '') === 'send_message' && trim((string) ($sub['content'] ?? '')) !== '') {
                        $evolution->sendText($this->instanceName, $this->contact, trim($sub['content']));
                    }
                    if (($sub['type'] ?? '') === 'ai_response') {
                        $this->executeAiResponse($sub, $evolution, $aiService, $elevenLabs, $conversationId, $flow);
                    }
                }
                return;
            }
        }

        $default = collect($conditions)->firstWhere('default', true);
        if ($default && ! empty($default['actions'])) {
            foreach ($default['actions'] as $sub) {
                if (($sub['type'] ?? '') === 'send_message' && trim((string) ($sub['content'] ?? '')) !== '') {
                    $evolution->sendText($this->instanceName, $this->contact, trim($sub['content']));
                }
                if (($sub['type'] ?? '') === 'ai_response') {
                    $this->executeAiResponse($sub, $evolution, $aiService, $elevenLabs, $conversationId, $flow);
                }
            }
        }
    }

    /**
     * Junta descrição do fluxo (como a IA deve agir) e prompt da ação (o que fazer nesta mensagem).
     * O AIService separa pelo marcador <<<TAREFA_AUTOMACAO>>> — política vs tarefa, para não tratar tudo como texto a recitar.
     */
    public const AI_TASK_DELIMITER = "\n\n<<<TAREFA_AUTOMACAO>>>\n";

    private function composeAiPromptFromFlowAndAction(Flow $flow, array $action): string
    {
        $desc = trim((string) ($flow->description ?? ''));
        $rawAction = trim((string) ($action['prompt'] ?? ''));

        if ($desc !== '' && $rawAction !== '' && $rawAction === $desc) {
            $rawAction = '';
        }

        $task = $rawAction !== ''
            ? str_replace(['{message}', '{user_message}'], $this->messageText, $rawAction)
            : 'Responda à última mensagem do cliente no WhatsApp: seja natural, breve e humano; não liste regras nem explique o seu papel.';

        if ($desc === '') {
            return $task;
        }

        // Mantém a "behavior" do flow limpa (somente o texto do painel).
        return $desc . self::AI_TASK_DELIMITER . $task;
    }

    /**
     * Modelos pequenos (ex. llama3.2:3b) costumam copiar a descrição do fluxo para a resposta.
     * Comparação direta com o texto guardado no painel — não depende só de heurísticas na AIService.
     *
     * @see https://github.com/ollama/ollama/issues/1103 (modelos a repetir contexto)
     * @see https://docs.ollama.com/api/chat (roles system / user na API)
     */
    private function stripResponseIfEchoesFlowDescription(string $response, Flow $flow, array $action = []): string
    {
        if ($response === '') {
            return $response;
        }

        $norm = static function (string $s): string {
            $s = preg_replace('/\s+/u', ' ', trim($s));

            return $s ?? '';
        };

        $r = $norm($response);
        $lenR = mb_strlen($r);
        $rl = mb_strtolower($r);
        // O modelo pode omitir palavras ("Seu objetivo" → "objetivo") — combinação típica de guião comercial
        $hasRegrasOuExemplo = str_contains($rl, 'regras de resposta')
            || str_contains($rl, 'exemplo de tom')
            || str_contains($rl, 'formato da resposta');
        if (mb_strlen($r) > 180
            && str_contains($rl, 'assistente comercial')
            && $hasRegrasOuExemplo
            && str_contains($rl, 'objetivo')) {
            Log::warning('ProcessIncomingMessageJob: resposta com padrão de guião comercial (eco da descrição) — substituída', [
                'flow_id' => $flow->id,
            ]);

            return 'Olá! Tudo bem? Em que posso ajudar?';
        }

        $sources = $this->getEchoSources($flow, $action, $norm);
        foreach ($sources as $source) {
            $text = $source['text'];
            $label = $source['label'];
            $simThreshold = (float) $source['sim_threshold'];
            $lenSource = mb_strlen($text);
            if ($lenSource < 35) {
                continue;
            }

            $needles = $this->buildEchoNeedles($text, $norm);
            $minRespForNeedle = max(120, (int) min(260, floor($lenSource * 0.35)));
            if ($lenR >= $minRespForNeedle) {
                foreach (array_unique(array_filter($needles)) as $needle) {
                    if (mb_strlen($needle) < 14) {
                        continue;
                    }
                    if (mb_stripos($r, $needle) !== false) {
                        Log::warning('ProcessIncomingMessageJob: resposta contém trecho de instrução interna — substituída', [
                            'flow_id' => $flow->id,
                            'source' => $label,
                            'needle_len' => mb_strlen($needle),
                        ]);

                        return 'Olá! Tudo bem? Em que posso ajudar?';
                    }
                }
            }

            if ($lenSource > 120 && $lenR > 150 && $lenR >= (int) ($lenSource * 0.55) && $lenSource < 4000) {
                similar_text(mb_strtolower($r), mb_strtolower($text), $pct);
                if ($pct > $simThreshold) {
                    Log::warning('ProcessIncomingMessageJob: resposta muito similar à instrução interna', [
                        'flow_id' => $flow->id,
                        'source' => $label,
                        'similarity_pct' => $pct,
                    ]);

                    return 'Olá! Tudo bem? Em que posso ajudar?';
                }
            }
        }

        return $response;
    }

    private function getEchoSources(Flow $flow, array $action, callable $norm): array
    {
        $sources = [];

        $desc = $norm((string) ($flow->description ?? ''));
        if (mb_strlen($desc) >= 35) {
            $sources[] = [
                'label' => 'flow_description',
                'text' => $desc,
                'sim_threshold' => 42.0,
            ];
        }

        $rawAction = trim((string) ($action['prompt'] ?? ''));
        if ($desc !== '' && $rawAction !== '' && $norm($rawAction) === $desc) {
            $rawAction = '';
        }
        $task = $rawAction !== ''
            ? str_replace(['{message}', '{user_message}'], $this->messageText, $rawAction)
            : '';
        $task = $norm($task);
        if (mb_strlen($task) >= 110) {
            $sources[] = [
                'label' => 'action_task',
                'text' => $task,
                'sim_threshold' => 64.0,
            ];
        }

        return $sources;
    }

    private function buildEchoNeedles(string $sourceText, callable $norm): array
    {
        $needles = [];
        $needles[] = mb_substr($sourceText, 0, min(72, mb_strlen($sourceText)));
        if (mb_strlen($sourceText) > 100) {
            $needles[] = mb_substr($sourceText, 40, min(72, mb_strlen($sourceText) - 40));
        }
        if (preg_match('/você\s+é\s+um\s+.{30,120}/ui', $sourceText, $m)) {
            $needles[] = $norm($m[0]);
        }
        $sl = mb_strtolower($sourceText);
        if (str_contains($sl, 'regras de resposta')) {
            $needles[] = 'Regras de resposta';
            $needles[] = 'Regras de resposta: - responda';
        }
        if (str_contains($sl, 'formato da resposta')) {
            $needles[] = 'Formato da resposta';
        }

        return $needles;
    }
}
