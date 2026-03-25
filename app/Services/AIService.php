<?php
/**
 * Serviço genérico de IA: Ollama (local), Google Gemini. Multi-provedor.
 * Provedor padrão vem do banco (ai_settings.default_provider).
 * NÃO contém persona: prompt vem do Fluxo (banco).
 * Contexto central (system + memória) é montado antes de rotear; Ollama e Gemini recebem o mesmo.
 * Áudio (voz): fluxo pode usar send_audio/response_type=audio → ElevenLabsService + Evolution sendAudio.
 */

namespace App\Services;

use App\Models\AISetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private $ollamaUrl;

    private $geminiApiKey;

    private $defaultModel;

    private $tenantId;

    private bool $ollamaFromEnv;

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId;
        $this->ollamaFromEnv = (bool) config('services.ai.ollama_from_env', false);
        $configUrl = config('services.ai.ollama_url', 'http://localhost:11434');
        // Buscar URL Ollama: painel (ai_settings) ou só .env se OLLAMA_FROM_ENV=true
        $raw = $this->ollamaFromEnv
            ? $configUrl
            : AISetting::get('ollama_url', $configUrl, $this->tenantId);
        $this->ollamaUrl = str_replace('http://localhost', 'http://127.0.0.1', $raw);
        $this->geminiApiKey = AISetting::get('gemini_api_key', config('services.ai.gemini_key', env('GEMINI_API_KEY', '')), $this->tenantId);
        $this->defaultModel = AISetting::get('default_provider', config('services.ai.default_model', env('AI_DEFAULT_MODEL', 'ollama')), $this->tenantId);
        if ((bool) config('services.ai.ollama_force_provider', false)) {
            $this->defaultModel = 'ollama';
        }
    }

    /**
     * Gera resposta. Contexto central (agnóstico a provedor) é construído antes de rotear para Ollama ou Gemini.
     */
    public function generateResponse(string $prompt, string $userMessage, string $provider = null, string $model = null, array $conversationHistory = []): string
    {
        $provider = $provider ?? $this->defaultModel;
        $context = $this->buildCentralContext($prompt, $userMessage, $conversationHistory);

        try {
            if ($provider === 'ollama') {
                return $this->generateWithOllama($context, $model);
            }
            if ($provider === 'gemini') {
                return $this->generateWithGemini($context, $model);
            }
            throw new \Exception("Provedor de IA não suportado: {$provider}");
        } catch (\Exception $e) {
            Log::error('Erro ao gerar resposta com IA', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Gera resposta usando Ollama (streaming para evitar timeout com modelo frio)
     */
    private function generateWithOllama(array $context, ?string $model = null): string
    {
        $defaultModel = $this->ollamaFromEnv
            ? (string) config('services.ai.ollama_model', 'llama2')
            : AISetting::get('ollama_model', config('services.ai.ollama_model', 'llama2'), $this->tenantId);
        // Com OLLAMA_FROM_ENV, o fluxo pode ainda ter model=llama2 gravado no JSON — ignorar e usar só o .env
        if ($this->ollamaFromEnv) {
            $model = trim($defaultModel);
        } else {
            $model = trim((string) ($model ?? $defaultModel));
            if ($model === '') {
                $model = $defaultModel;
            }
        }

        $messages = $context['messages'] ?? [];
        if (empty($messages)) {
            $messages = [
                ['role' => 'system', 'content' => $context['system']],
                ['role' => 'user', 'content' => trim($context['prompt'])],
            ];
        }
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => true,
            'options' => ['temperature' => 0.28, 'top_p' => 0.65],
        ];
        Log::info('Payload enviado ao Ollama:', ['messages' => $messages]);
        $url = rtrim($this->ollamaUrl, '/') . '/api/chat';
        $payloadJson = json_encode($payload);
        $tmpFile = tempnam(sys_get_temp_dir(), 'ollama_');
        file_put_contents($tmpFile, $payloadJson);
        $cmd = sprintf(
            "curl -s -N -X POST %s -H 'Content-Type: application/json' -d @%s --max-time 180 2>&1",
            escapeshellarg($url),
            escapeshellarg($tmpFile)
        );
        $handle = popen($cmd, 'r');
        register_shutdown_function(function () use ($tmpFile) { @unlink($tmpFile); });
        if (!$handle) {
            throw new \Exception('Não foi possível invocar Ollama (curl).');
        }
        $buffer = '';
        $responseText = '';
        while (!feof($handle)) {
            $buffer .= fread($handle, 8192);
            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);
                if ($line === '') continue;
                $data = json_decode($line, true);
                if (is_array($data) && isset($data['error'])) {
                    pclose($handle);
                    throw new \Exception('Ollama: ' . ($data['error'] ?? 'erro desconhecido'));
                }
                if (is_array($data) && isset($data['message']['content'])) {
                    $responseText .= $data['message']['content'];
                }
                if (is_array($data) && !empty($data['done'])) {
                    break 2;
                }
            }
        }
        pclose($handle);
        $responseText = trim($responseText);
        if ($responseText === '') {
            Log::info('Ollama streaming retornou vazio, tentando sem streaming (fallback)');
            $responseText = $this->generateWithOllamaNoStream($url, $model, $messages);
        }
        if ($responseText === '') {
            throw new \Exception('Resposta vazia recebida do Ollama.');
        }
        return $this->sanitizeResponseForChat($responseText, (string) ($context['raw_user_message'] ?? ''));
    }

    /**
     * Fallback: chama Ollama sem streaming (útil quando streaming devolve vazio)
     */
    private function generateWithOllamaNoStream(string $url, string $model, array $messages): string
    {
        $payload = [
            'model' => $model,
            'messages' => $messages,
            'stream' => false,
            'options' => ['temperature' => 0.28, 'top_p' => 0.65],
        ];
        $response = Http::timeout(180)->post($url, $payload);
        if (! $response->successful()) {
            throw new \Exception('Ollama (fallback): ' . ($response->body() ?: 'erro HTTP ' . $response->status()));
        }
        $data = $response->json();
        $text = $data['message']['content'] ?? '';
        return trim((string) $text);
    }

    /**
     * Gera resposta usando Google Gemini (formato oficial da API)
     */
    private function generateWithGemini(array $context, ?string $model = null): string
    {
        $apiKey = AISetting::get('gemini_api_key', '', $this->tenantId) ?: $this->geminiApiKey;
        if (empty($apiKey)) {
            throw new \Exception("Chave da API do Gemini não configurada para este Tenant. Configure em Configurações IA.");
        }

        $model = $model ?? AISetting::get('gemini_model', config('services.ai.gemini_model', env('GEMINI_MODEL', 'gemini-2.0-flash')), $this->tenantId);
        $model = trim((string) $model);
        if ($model === '' || $model === 'gemini-pro' || $model === 'gemini-1.5-flash') {
            $model = 'gemini-2.0-flash';
        }

        // Mesmo contexto que Ollama: systemInstruction + contents (user prompt). Temperatura baixa para PT-BR estável.
        $payload = [
            'systemInstruction' => ['parts' => [['text' => $context['system']]]],
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $context['prompt']]]],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'topP' => 0.9,
            ],
        ];

        $response = Http::timeout(60)
            ->withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => $apiKey,
            ])
            ->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent",
                $payload
            );

        $body = $response->body();
        if (!$response->successful()) {
            $errMsg = $body;
            $decoded = json_decode($body, true);
            if (!empty($decoded['error']['message'])) {
                $errMsg = $decoded['error']['message'];
            }
            Log::warning('Gemini API erro', ['status' => $response->status(), 'body' => substr($body, 0, 500)]);
            throw new \Exception("Gemini: " . $errMsg);
        }

        $data = $response->json();
        if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $blockReason = $data['candidates'][0]['finishReason'] ?? ($data['candidates'][0]['safetyRatings'] ?? 'desconhecido');
            Log::warning('Gemini resposta sem texto', ['data_keys' => array_keys($data)]);
            throw new \Exception("Resposta inválida do Gemini (finishReason: " . json_encode($blockReason) . ")");
        }

        $responseText = trim($data['candidates'][0]['content']['parts'][0]['text']);
        if ($responseText === '') {
            throw new \Exception("Resposta vazia recebida do Gemini");
        }

        return $this->sanitizeResponseForChat($responseText, (string) ($context['raw_user_message'] ?? ''));
    }

    /**
     * Modelos pequenos (ex.: llama3.2) às vezes devolvem o roteiro comercial inteiro (Persona/Objetivos…).
     */
    private function extractReplyIfModelDumpedInstructions(string $text): ?string
    {
        $t = trim($text);
        if (mb_strlen($t) < 180) {
            return null;
        }
        $lower = mb_strtolower($t);
        $nonEmptyLines = array_values(array_filter(preg_split('/\r?\n/', $t), fn ($l) => trim($l) !== ''));
        $lineCount = count($nonEmptyLines);

        $hasPersona = str_contains($lower, 'persona') || str_contains($lower, 'personagem');
        $hasObjetivos = str_contains($lower, 'objetivo');
        $hasRegras = str_contains($lower, 'regra');
        $hasFormato = str_contains($lower, 'formato');
        $colons = substr_count($t, ':');
        // Texto colado num parágrafo só (poucas quebras de linha) — comum no llama3.2
        $denseScript = mb_strlen($t) > 550
            && $colons >= 5
            && (
                (str_contains($lower, 'assistente comercial') && $hasObjetivos)
                || ($hasPersona && $hasObjetivos && $hasRegras)
                || ($hasObjetivos && $hasFormato && $colons >= 7)
            );
        $looksLikeScript = ($hasPersona && $hasObjetivos && $hasRegras)
            || ($hasObjetivos && $hasFormato && $hasRegras && $lineCount >= 6)
            || ($lineCount >= 8 && $hasObjetivos && $hasRegras && $colons >= 8)
            || $denseScript;

        if (! $looksLikeScript) {
            return null;
        }

        // Bloco único: tentar isolar frase de exemplo (tom desejado)
        if ($lineCount <= 4 && preg_match('/Olá!\s*Tudo\s*bem\?[^\n]{0,200}/ui', $t, $m)) {
            return trim($m[0]);
        }
        if ($lineCount <= 4 && preg_match('/["\x{201C}](Olá[^"\x{201D}]{8,250})["\x{201D}]/u', $t, $m)) {
            return trim($m[1]);
        }

        $paragraphs = preg_split('/\n\s*\n/', $t);
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

        for ($i = count($paragraphs) - 1; $i >= 0; $i--) {
            $p = $paragraphs[$i];
            $len = mb_strlen($p);
            if ($len < 15 || $len > 2200) {
                continue;
            }
            $pl = mb_strtolower($p);
            if (preg_match('/^\*{0,2}\s*(persona|objetivos|regras|formato|lógica)\b/ui', $p)) {
                continue;
            }
            if (str_contains($pl, 'objetivo') && str_contains($pl, 'regra') && $len > 350) {
                continue;
            }
            if (substr_count($p, ':') >= 6 && $len > 400) {
                continue;
            }
            if (preg_match('/^(\d+\.)\s+.+\n(\d+\.)\s+/u', $p)) {
                continue;
            }

            return $p;
        }

        Log::warning('AIService: resposta parecia roteiro completo; usando saudação curta', [
            'chars' => mb_strlen($t),
            'lines' => $lineCount,
        ]);

        return 'Olá! Tudo bem? Em que posso ajudar?';
    }

    /**
     * Remove da resposta trechos que parecem vazamento de prompt (regras, exemplos).
     */
    private function stripLeakedPromptFromResponse(string $text): string
    {
        $extracted = $this->extractReplyIfModelDumpedInstructions($text);
        if ($extracted !== null) {
            return $extracted;
        }

        $lower = mb_strtolower($text);
        $markers = [
            'regras de ouro', 'as regras de ouro', 'golden rules', 'aqui está um exemplo',
            'exemplo de como você pode responder', 'exemplo de como', 'como você pode responder',
            'nunca ignore', 'nunca repita', 'nunca mostre', '[crítico]', 'sua resposta deve conter apenas',
            'aqui está o roteiro', 'roteiro para você', 'aguarde resposta', 'estado 1', 'estado 2', 'pare aqui',
            '**persona**', 'persona:', 'objetivos:', 'formato:', 'tom desejado',
            'você é um assistente', 'atendente comercial profissional', 'call to action',
        ];
        $hasLeak = false;
        foreach ($markers as $m) {
            if (strpos($lower, $m) !== false) {
                $hasLeak = true;
                break;
            }
        }
        if (!$hasLeak) {
            return $text;
        }
        if (stripos($text, 'Laura:') !== false) {
            $parts = preg_split('/Laura\s*:\s*/ui', $text, -1, PREG_SPLIT_NO_EMPTY);
            $last = trim(end($parts));
            if ($last !== '' && strlen($last) < 2000) {
                return $last;
            }
        }
        $lines = preg_split('/\r?\n/', $text);
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') continue;
            if (preg_match('/^\d+\.\s/u', $t)) continue;
            if (preg_match('/^(regras|as regras|exemplo|aqui está|golden)/ui', $t)) continue;
            if (strlen($t) > 20 && strlen($t) < 1500) {
                $tl = mb_strtolower($t);
                if (str_contains($tl, 'persona') && str_contains($tl, 'objetivo')) {
                    continue;
                }

                return $t;
            }
        }

        return $text;
    }

    /**
     * Remove da resposta: instruções de cena (**smiles**), placeholders {img002}, linhas internas (ESTADO X:, PARE AQUI).
     */
    private function sanitizeResponseForChat(string $text, string $userMessage = ''): string
    {
        $text = trim($text);
        if ($text === '') return $text;
        // Remover vazamento de prompt: blocos com regras, exemplos ou instruções
        $text = $this->stripLeakedPromptFromResponse($text);
        $text = preg_replace('/\bsmile\b\s*/', '', $text);
        $text = preg_replace('/\*\*[^*]+\*\*/u', '', $text);
        $text = preg_replace('/\*[^*]+\*/u', '', $text);
        $text = preg_replace('/\{img[a-zA-Z0-9]*\}/u', '', $text);
        $lines = explode("\n", $text);
        $out = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') continue;
            if (preg_match('/^ESTADO\s+\d+/ui', $t)) continue;
            if (preg_match('/^PARE\s+AQUI/ui', $t) || preg_match('/^Gatilho\s*:/ui', $t)) continue;
            if (preg_match('/^NUNCA\s+(ignore|repite|mostre|cite)/ui', $t)) continue;
            if (preg_match('/^(Send|Ask)\s*:\s*\{/ui', $t)) continue;
            if (preg_match('/^Waiting for the response/ui', $t)) continue;
            if (preg_match('/roteiro\s+para\s+você|aqui está o roteiro|aguarde\s+(resposta|a resposta)/ui', $t)) continue;
            $out[] = $line;
        }
        $text = implode("\n", $out);
        $text = trim($text);
        $text = $this->replaceEnglishWithPortuguese($text);

        return trim($this->finalizeHumanWhatsAppReply($text, $userMessage));
    }

    /**
     * Última defesa: se ainda parecer roteiro, extrai saudação curta ou resposta fixa humana.
     */
    private function finalizeHumanWhatsAppReply(string $text, string $userMessage): string
    {
        $text = trim($text);
        if ($text === '') {
            return $text;
        }
        if (mb_strlen($text) < 320) {
            return $text;
        }
        $l = mb_strtolower($text);
        $scriptLike = (bool) preg_match(
            '/persona|objetivos?\b|regras?\b|formato\b|lógica|tom\s+desejado|assistente\s+comercial|call\s+to\s+action|seu\s+objetivo\s+é/ui',
            $l
        );
        if (! $scriptLike) {
            return $text;
        }
        if (preg_match('/Olá!\s*Tudo\s*bem\?[^\n]{0,180}/ui', $text, $m)) {
            return trim($m[0]);
        }
        if (preg_match('/^Oi[!,.]?\s+.+/ui', $text, $m) && mb_strlen($m[0]) < 280) {
            return trim($m[0]);
        }
        $u = mb_strtolower(trim($userMessage));
        if (preg_match('/^(oi|ol[aá]|opa|hey|bom dia|boa tarde|boa noite)\b/u', $u)) {
            return 'Oi! Tudo bem? Em que posso ajudar?';
        }

        return 'Obrigado pelo contato! Em que posso ajudar?';
    }

    private function replaceEnglishWithPortuguese(string $text): string
    {
        $r = [
            'Hello! smile I\'m Laura' => 'Oi! Sou a Laura',
            'Hello! I\'m Laura, from Viu One.' => 'Oi! Sou a Laura da Viu One.',
            'Hello there!' => 'Olá!',
            'Hello! I\'m Laura from Viu One' => 'Oi! Sou a Laura da Viu One',
            'and I\'m here to help you with your landing page today!' => 'e estou aqui para te ajudar com sua landing page hoje!',
            'So, what can I do for you?' => 'O que posso fazer por você?',
            'Do you have any questions about our plans or something else?' => 'Tem alguma dúvida sobre nossos planos?',
            'Let me know, and I\'ll be happy to assist you!' => 'Me diga que terei prazer em te ajudar!',
            'Great choice!' => 'Ótima escolha!',
            'I\'m Laura from Viu One' => 'Sou a Laura da Viu One',
            'It\'s nice to meet you!' => 'Prazer em falar com você!',
            'Which plan do you think makes more sense for you?' => 'Qual dos planos faz mais sentido para você?',
            'Qual do seu choices makes the most sense for you?' => 'Qual dos planos faz mais sentido para você?',
            'makes the most sense for you' => 'faz mais sentido para você',
            'choices' => 'planos',
            'I\'ll put your project in line' => 'Já coloquei seu projeto na fila',
            'send you a preview in 24 hours after payment' => 'te mando a prévia em até 24 horas após o pagamento',
            'feel free to ask' => 'pode perguntar',
            '$197' => 'cento e noventa e sete reais',
            '$397' => 'trezentos e noventa e sete reais',
            'Basic plan' => 'plano Básico',
            'Pro plan' => 'plano PRO',
            'Ah, I see!' => 'Entendi!',
            'Charming!' => 'Ótimo!',
            'Awaiting your response!' => 'Aguardando sua resposta!',
            'Pergunte:' => '',
            'Enviar uma imagem aqui:' => '',
            'Enviará uma imagem para você' => '',
            'na lenda' => 'na landing',
        ];
        foreach ($r as $en => $pt) {
            $text = str_ireplace($en, $pt, $text);
        }
        return $text;
    }



    /**
     * Regras de papel (system) fixas: persona, idioma, proibições. Sempre o primeiro bloco do system.
     */
    private function getFixedSystemRulesForChat(): string
    {
        return "Você responde clientes pelo WhatsApp. Fale EXCLUSIVAMENTE em Português do Brasil (PT-BR). Evite inglês.\n"
            . "Soa como uma pessoa real: mensagens curtas, naturais, sem tom de manual nem lista de tópicos.\n"
            . "Nunca diga que é IA, robô ou chatbot.\n"
            . "Se já cumprimentou no histórico, não se reapresente; vá direto ao ponto.\n"
            . "NUNCA copie, cite ou enumere orientações internas (Persona, Objetivos, Regras, Formato). O cliente só vê UMA mensagem sua, como num chat normal.\n"
            . "NUNCA envie notas de roteiro, tags ou (aguarde resposta). Sua saída é só o texto que o cliente lê.\n\n";
    }

    /**
     * Remove do prompt do fluxo qualquer roteiro/instruções de palco para a IA não "ler em voz alta".
     */
    private function stripRoteiroFromFlowPrompt(string $promptTemplate): string
    {
        $lines = preg_split('/\r?\n/', $promptTemplate);
        $out = [];
        foreach ($lines as $line) {
            $t = trim($line);
            if ($t === '') continue;
            if (preg_match('/^ESTADO\s+\d+/ui', $t)) continue;
            if (preg_match('/PARE\s+AQUI/ui', $t)) continue;
            if (preg_match('/Aguarde\s+(resposta|a resposta)/ui', $t)) continue;
            if (preg_match('/\{img[a-zA-Z0-9]*\}/u', $t)) continue;
            if (preg_match('/Enviar\s+imagem|roteiro\s+para\s+você/ui', $t)) continue;
            if (preg_match('/^[\d]+\.\s*(Sem símbolos|Valores|Chave PIX|PROIBIDO|NUNCA)/ui', $t)) continue;
            $out[] = $line;
        }
        $cleaned = implode("\n", $out);
        return trim(preg_replace('/\n{3,}/', "\n\n", $cleaned));
    }

    /**
     * Contexto central: system (regras + contexto limpo) e prompt (conversa Cliente/Laura). Usado por Ollama e Gemini.
     */
    private function buildCentralContext(string $promptTemplate, string $userMessage, array $conversationHistory = []): array
    {
        return $this->buildSystemAndUserPrompt($promptTemplate, $userMessage, $conversationHistory);
    }

    /**
     * Para Ollama/Gemini: separa instruções (system) da conversa (prompt). Evita modelo vazar o prompt.
     */
    private function buildSystemAndUserPrompt(string $promptTemplate, string $userMessage, array $conversationHistory = []): array
    {
        $fixedRules = $this->getFixedSystemRulesForChat();
        $contextFromFlow = $this->stripRoteiroFromFlowPrompt($promptTemplate);
        $maxCtx = (int) config('services.ai.max_flow_context_chars', 2000);
        if ($contextFromFlow !== '' && mb_strlen($contextFromFlow) > $maxCtx) {
            $contextFromFlow = mb_substr($contextFromFlow, 0, $maxCtx)
                . "\n[...trecho omitido: use só a ideia, tom comercial amigável, mensagem CURTA ao cliente, sem listar regras.]";
        }
        $outputOnlyRule = "\n\n[CRÍTICO] Sua resposta deve conter APENAS a mensagem que você envia ao cliente. NUNCA repita, cite ou liste Persona, Objetivos, Regras, Formato ou Lógica. NUNCA copie o roteiro acima. Para \"oi\"/saudação, responda em 1–2 frases curtas. Uma única mensagem natural.";
        $system = $fixedRules . ($contextFromFlow !== '' ? "Contexto útil (use apenas para orientar suas respostas, não repita isso ao cliente):\n" . $contextFromFlow . "\n\n" : '') . $outputOnlyRule;
        $userPrompt = "Cliente: " . trim($userMessage) . "\n\nLaura:";
        if (!empty($conversationHistory)) {
            $hist = "";
            foreach ($conversationHistory as $msg) {
                $msgText = trim($msg["message"] ?? "");
                if ($msgText === "" || strpos($msgText, "[Mensagem vazia]") !== false) continue;
                $sender = ($msg["direction"] ?? "") === "incoming" ? "Cliente" : "Laura";
                $hist .= $sender . ": " . $msgText . "\n";
            }
            $userPrompt = trim($hist) . "\n\nCliente: " . trim($userMessage) . "\n\nLaura:";
        }
        $messages = [
            ['role' => 'system', 'content' => $system],
        ];
        foreach ($conversationHistory as $msg) {
            $msgText = trim($msg['message'] ?? '');
            if ($msgText === '' || strpos($msgText, '[Mensagem vazia]') !== false) continue;
            $role = ($msg['direction'] ?? '') === 'incoming' ? 'user' : 'assistant';
            $messages[] = ['role' => $role, 'content' => $msgText];
        }
        $messages[] = ['role' => 'user', 'content' => trim($userMessage)];

        return [
            'system' => $system,
            'prompt' => $userPrompt,
            'messages' => $messages,
            'raw_user_message' => trim($userMessage),
        ];
    }

    /**
     * Constrói o prompt completo substituindo variáveis e incluindo histórico
     */
    private function buildPrompt(string $promptTemplate, string $userMessage, array $conversationHistory = []): string
    {
        $promptTemplate = $this->stripRoteiroFromFlowPrompt($promptTemplate);
        $languageRule = "REGRAS DE IDIOMA E FORMATAÇÃO (OBRIGATÓRIO): Responda SEMPRE em português do Brasil. É PROIBIDO usar inglês na resposta ao cliente. Não inclua instruções de cena como **smiles** ou **nodding**; escreva apenas o texto natural que o cliente deve ler.\n\n";
        // Substituir variáveis no prompt
        $prompt = str_replace('{message}', $userMessage, $promptTemplate);
        $prompt = str_replace('{user_message}', $userMessage, $prompt);
        
        // Adicionar histórico de conversa se disponível
        if (!empty($conversationHistory)) {
            Log::info('Construindo prompt com histórico', [
                'history_count' => count($conversationHistory),
                'last_messages' => array_slice($conversationHistory, -3),
            ]);
            
            $historyText = "\n\n--- Histórico da Conversa (IMPORTANTE: Use este contexto para responder) ---\n";
            foreach ($conversationHistory as $msg) {
                // FILTRAR mensagens vazias do histórico
                $msgText = trim($msg['message'] ?? '');
                if (empty($msgText) || 
                    $msgText === '[Mensagem vazia]' || 
                    $msgText === '[Erro ao processar áudio]' ||
                    $msgText === '[Áudio não disponível]' ||
                    $msgText === '[Áudio não transcrito]') {
                    continue; // Pular mensagens vazias
                }
                
                $sender = $msg['direction'] === 'incoming' ? 'Cliente' : 'Atendente';
                // Limpar prefixos [Áudio] do histórico também
                $cleanMessage = preg_replace('/^(\[Áudio\]|\[Audio\]|audio:|áudio:|Audio:|Áudio:)\s*/i', '', $msgText);
                $cleanMessage = preg_replace('/^(audio|áudio)\s*:?\s*/i', '', $cleanMessage);
                
                // Validar que ainda há conteúdo após limpeza
                if (empty(trim($cleanMessage))) {
                    continue; // Pular se ficou vazio após limpeza
                }
                
                $historyText .= "{$sender}: {$cleanMessage}\n";
            }
            $historyText .= "--- Fim do Histórico ---\n\n";
            $historyText .= "IMPORTANTE: Baseie sua resposta no histórico acima. NÃO repita perguntas que já foram respondidas. Continue a conversa de forma natural.\n\n";
            
            // Inserir histórico antes da mensagem atual
            $prompt = str_replace('{message}', $historyText . "Cliente: {$userMessage}", $promptTemplate);
            $prompt = str_replace('{user_message}', $historyText . "Cliente: {$userMessage}", $promptTemplate);
            
            // Se não tinha variáveis, adicionar histórico no início
            if ($prompt === $promptTemplate) {
                $prompt = $historyText . $prompt . "\n\nCliente: {$userMessage}";
            }
        } else {
            Log::warning('Prompt sendo construído SEM histórico de conversa', [
                'user_message_preview' => substr($userMessage, 0, 50),
            ]);
        }

        $finalReminder = "\n\n[LEMBRETE OBRIGATÓRIO: Responda SOMENTE em português do Brasil. É PROIBIDO escrever em inglês.]"; return $languageRule . $prompt . $finalReminder;
    }
}

