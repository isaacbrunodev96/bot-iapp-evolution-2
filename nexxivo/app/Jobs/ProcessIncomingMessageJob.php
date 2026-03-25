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
            $response = $this->stripResponseIfEchoesFlowDescription($response, $flow);
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
     * Descrição do fluxo = persona/orientação INTERNA. Prompt da ação = tarefa curta (ex.: responder ao cliente).
     * Se o utilizador colou o mesmo texto nos dois, não duplicar.
     */
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

        $header = "[Orientação interna — define persona, tom e limites. Isto NÃO é mensagem para o cliente: não copie, não cite, não enumere ao cliente.]\n";

        return $header . $desc . "\n\n---\n\n[Tarefa]\n" . $task;
    }

    /**
     * Modelos pequenos (ex. llama3.2:3b) costumam copiar a descrição do fluxo para a resposta.
     * Comparação direta com o texto guardado no painel — não depende só de heurísticas na AIService.
     *
     * @see https://github.com/ollama/ollama/issues/1103 (modelos a repetir contexto)
     * @see https://docs.ollama.com/api/chat (roles system / user na API)
     */
    private function stripResponseIfEchoesFlowDescription(string $response, Flow $flow): string
    {
        $desc = trim((string) ($flow->description ?? ''));
        if ($desc === '' || $response === '') {
            return $response;
        }

        $norm = static function (string $s): string {
            $s = preg_replace('/\s+/u', ' ', trim($s));

            return $s ?? '';
        };

        $d = $norm($desc);
        $r = $norm($response);
        if (mb_strlen($d) < 35) {
            return $response;
        }

        $needles = [];
        $needles[] = mb_substr($d, 0, min(72, mb_strlen($d)));
        if (mb_strlen($d) > 100) {
            $needles[] = mb_substr($d, 40, min(72, mb_strlen($d) - 40));
        }
        if (preg_match('/você\s+é\s+um\s+.{30,120}/ui', $d, $m)) {
            $needles[] = $norm($m[0]);
        }
        if (str_contains(mb_strtolower($d), 'regras de resposta')) {
            $needles[] = 'Regras de resposta';
        }

        foreach (array_unique(array_filter($needles)) as $needle) {
            if (mb_strlen($needle) < 28) {
                continue;
            }
            if (mb_stripos($r, $needle) !== false) {
                Log::warning('ProcessIncomingMessageJob: resposta contém trecho da descrição do fluxo — substituída', [
                    'flow_id' => $flow->id,
                    'needle_len' => mb_strlen($needle),
                ]);

                return 'Olá! Tudo bem? Em que posso ajudar?';
            }
        }

        $lenD = mb_strlen($d);
        $lenR = mb_strlen($r);
        if ($lenD > 120 && $lenR > 150 && $lenR >= (int) ($lenD * 0.55) && $lenD < 4000) {
            similar_text(mb_strtolower($r), mb_strtolower($d), $pct);
            if ($pct > 42.0) {
                Log::warning('ProcessIncomingMessageJob: resposta muito similar à descrição do fluxo', [
                    'flow_id' => $flow->id,
                    'similarity_pct' => $pct,
                ]);

                return 'Olá! Tudo bem? Em que posso ajudar?';
            }
        }

        return $response;
    }
}
