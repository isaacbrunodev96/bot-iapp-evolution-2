<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingMessageJob;
use App\Models\BotInstance;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class EvolutionWebhookController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $raw = $request->getContent();
        Log::info('Evolution webhook POST', ['path' => $request->path(), 'body_length' => strlen($raw)]);
        $payload = $request->all();
        if (($payload === [] || (count($payload) <= 1 && isset($payload['event']))) && $raw !== '' && $raw !== '{}') {
            $decoded = json_decode($raw, true);
            $payload = is_array($decoded) ? $decoded : [];
        }
        if ($payload === [] || $payload === ['event' => null]) {
            Log::warning('Evolution webhook payload vazio', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'body_length' => strlen($raw),
                'body_preview' => substr($raw, 0, 800),
            ]);
            return response()->noContent(200);
        }
        // Garantir que lemos o body bruto se o JSON for aninhado em uma chave
        if (isset($payload['data']) && is_string($payload['data'])) {
            $decoded = json_decode($payload['data'], true);
            if (is_array($decoded)) {
                $payload['data'] = $decoded;
            }
        }
        $event = $payload['event'] ?? $payload['data']['event'] ?? null;
        if (empty($event) && $request->route('evolutionEvent')) {
            $event = str_replace(['-', '/'], ['.', '_'], $request->route('evolutionEvent'));
        }
        $instanceName = $this->resolveInstanceName($payload);

        if (! $instanceName) {
            Log::warning('Evolution webhook sem instanceName', [
                'payload_keys' => array_keys($payload),
                'data_keys' => array_keys($payload['data'] ?? []),
                'event' => $event,
            ]);
            return response()->noContent(200);
        }

        $instance = BotInstance::where('instance_name', $instanceName)->first();
        if (! $instance) {
            Log::warning('Evolution webhook instância não encontrada no banco', [
                'instanceName' => $instanceName,
                'event' => $event,
            ]);
            return response()->noContent(200);
        }

        $eventNormalized = $this->normalizeEvent($event);
        $data = $payload['data'] ?? $payload;
        if ($eventNormalized !== 'MESSAGES_UPSERT' && $this->looksLikeMessagePayload($payload, $data)) {
            $eventNormalized = 'MESSAGES_UPSERT';
            Log::info('Evolution payload tratado como mensagem (estrutura detectada)', ['instance' => $instanceName]);
        }
        Log::info('Evolution webhook recebido', [
            'event' => $event,
            'instance' => $instanceName,
            'data_keys' => array_keys($data),
        ]);

        switch ($eventNormalized) {
            case 'QRCODE_UPDATED':
                $this->handleQrcodeUpdated($instance, $payload);
                break;
            case 'CONNECTION_UPDATE':
                $this->tryExtractQrFromPayload($instance, $payload);
                $this->handleConnectionUpdate($instance, $payload);
                break;
            case 'MESSAGES_UPSERT':
                try {
                    $this->handleMessagesUpsert($instanceName, $payload);
                } catch (\Throwable $e) {
                    Log::error('Evolution MESSAGES_UPSERT erro ao processar', [
                        'instance' => $instanceName,
                        'message' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
                break;
            default:
                $this->tryExtractQrFromPayload($instance, $payload);
                break;
        }

        return response()->noContent(200);
    }

    /**
     * Varre o payload recursivamente por qrcode/base64/qr e grava se achar.
     */
    private function tryExtractQrFromPayload(BotInstance $instance, array $payload): void
    {
        $qrcode = $this->findQrInArray($payload);
        if ($qrcode && is_string($qrcode) && strlen($qrcode) > 100) {
            if (preg_match('#^data:image/[^;]+;base64,(.+)$#', $qrcode, $m)) {
                $qrcode = $m[1];
            }
            $instance->update([
                'qrcode' => $qrcode,
                'qrcode_generated_at' => now(),
                'status' => 'disconnected',
            ]);
            Log::info('Evolution QR extraído do payload (evento alternativo)', ['instance' => $instance->instance_name]);
        }
    }

    private function findQrInArray(array $arr): ?string
    {
        $keys = ['qrcode', 'base64', 'qr', 'base64Image', 'image', 'qrcodeBase64'];
        foreach ($keys as $key) {
            if (isset($arr[$key])) {
                $v = $arr[$key];
                if (is_string($v) && strlen($v) > 100) {
                    return $v;
                }
                if (is_array($v)) {
                    $found = $this->findQrInArray($v);
                    if ($found !== null) {
                        return $found;
                    }
                }
            }
        }
        foreach ($arr as $v) {
            if (is_array($v)) {
                $found = $this->findQrInArray($v);
                if ($found !== null) {
                    return $found;
                }
            }
        }
        return null;
    }

    private function normalizeEvent(?string $event): ?string
    {
        if ($event === null) {
            return null;
        }
        $e = strtolower(str_replace('.', '_', $event));
        return match ($e) {
            'qrcode_updated', 'qr' => 'QRCODE_UPDATED',
            'connection_update' => 'CONNECTION_UPDATE',
            'messages_upsert' => 'MESSAGES_UPSERT',
            default => $event,
        };
    }

    /** Detecta se o payload tem estrutura de mensagem (remoteJid + conteúdo) mesmo sem event correto. */
    private function looksLikeMessagePayload(array $payload, array $data): bool
    {
        $key = $data['key'] ?? [];
        $remoteJid = $key['remoteJid'] ?? $data['remoteJid'] ?? $data['keyRemoteJid'] ?? null;
        if (! is_string($remoteJid) || $remoteJid === '') {
            return false;
        }
        $hasContent = isset($data['message']) || isset($data['content'])
            || (is_array($key) && (isset($key['id']) || isset($key['remoteJid'])));
        return $hasContent;
    }

    private function resolveInstanceName(array $payload): ?string
    {
        $data = $payload['data'] ?? [];
        $v = $payload['instanceName'] ?? $payload['instance'] ?? $data['instanceName'] ?? $data['instance'] ?? $data['numberId'] ?? $payload['numberId'] ?? null;
        if (is_string($v) && $v !== '') {
            return $v;
        }
        if (is_array($v)) {
            $name = $v['name'] ?? $v['instanceName'] ?? $v['instance'] ?? $v['value'] ?? null;
            return is_string($name) ? $name : null;
        }
        return null;
    }

    private function handleQrcodeUpdated(BotInstance $instance, array $payload): void
    {
        $data = $payload['data'] ?? $payload;
        $qrcodeObj = $data['qrcode'] ?? null;
        $qrcode = (is_array($qrcodeObj) ? ($qrcodeObj['base64'] ?? $qrcodeObj['image'] ?? null) : $qrcodeObj)
            ?? $payload['qrcode']
            ?? $payload['base64']
            ?? $data['qrcode']
            ?? $data['base64']
            ?? $data['qr']
            ?? $data['qrcodeBase64']
            ?? $data['image']
            ?? (is_string($data) ? $data : null);
        if (is_array($qrcode)) {
            $qrcode = $qrcode['base64'] ?? $qrcode['image'] ?? $qrcode['data'] ?? $qrcode['qr'] ?? null;
        }
        if ($qrcode && is_string($qrcode)) {
            if (preg_match('#^data:image/[^;]+;base64,(.+)$#', $qrcode, $m)) {
                $qrcode = $m[1];
            }
            $instance->update([
                'qrcode' => $qrcode,
                'qrcode_generated_at' => now(),
                'status' => 'disconnected',
            ]);
            Log::info('Evolution QR Code salvo', ['instance' => $instance->instance_name]);
        } else {
            Log::warning('Evolution QRCODE_UPDATED sem base64', [
                'instance' => $instance->instance_name,
                'payload_keys' => array_keys($payload),
                'data_keys' => is_array($data) ? array_keys($data) : 'not_array',
                'data_sample' => is_array($data) ? array_map(fn ($v) => is_string($v) ? substr($v, 0, 50) . '...' : gettype($v), $data) : null,
            ]);
        }
    }

    private function handleConnectionUpdate(BotInstance $instance, array $payload): void
    {
        $data = $payload['data'] ?? $payload;
        $state = $data['state'] ?? $data['status'] ?? $data['connectionStatus'] ?? null;
        $status = match (strtolower((string) $state)) {
            'open', 'connected' => 'connected',
            'close', 'disconnected' => 'disconnected',
            default => $instance->status,
        };

        $updates = [
            'status' => $status,
            'qrcode' => $status === 'connected' ? null : $instance->qrcode,
        ];

        $qrcodeObj = $data['qrcode'] ?? null;
        $qrcode = (is_array($qrcodeObj) ? ($qrcodeObj['base64'] ?? $qrcodeObj['image'] ?? null) : $qrcodeObj)
            ?? $payload['qrcode'] ?? $payload['base64'] ?? $data['qrcode'] ?? $data['base64'] ?? $data['qr'] ?? $data['qrcodeBase64'] ?? null;
        if (is_array($qrcode)) {
            $qrcode = $qrcode['base64'] ?? $qrcode['image'] ?? $qrcode['qr'] ?? null;
        }
        if ($qrcode && is_string($qrcode)) {
            if (preg_match('#^data:image/[^;]+;base64,(.+)$#', $qrcode, $m)) {
                $qrcode = $m[1];
            }
            if (strlen($qrcode) > 100) {
                $updates['qrcode'] = $qrcode;
                $updates['qrcode_generated_at'] = now();
            }
        }

        $instance->update($updates);
    }

    private function handleMessagesUpsert(string $instanceName, array $payload): void
    {
        $data = $payload['data'] ?? $payload;
        $messages = $data['messages'] ?? null;
        $items = $messages && is_array($messages) ? $messages : [$data];

        foreach ($items as $dataItem) {
            if (! is_array($dataItem)) {
                continue;
            }
            $this->processOneMessage($instanceName, $dataItem);
        }
    }

    private function processOneMessage(string $instanceName, array $data): void
    {
        $key = $data['key'] ?? [];
        $isOutgoing = $this->normalizeBool($key['fromMe'] ?? $data['fromMe'] ?? false);

        $remoteJid = (string) ($key['remoteJid'] ?? $data['remoteJid'] ?? $data['keyRemoteJid'] ?? '');
        if ($remoteJid === '') {
            Log::warning('Evolution MESSAGES_UPSERT sem remoteJid', ['instance' => $instanceName, 'data_keys' => array_keys($data)]);
            return;
        }

        $messageId = $key['id'] ?? $data['id'] ?? $data['messageId'] ?? uniqid('ev_', true);
        $pushName = $data['pushName'] ?? $data['notifyName'] ?? '';
        $rawMessage = $data['message'] ?? $data['content'] ?? [];
        $unwrapped = $this->unwrapBaileysMessage(is_array($rawMessage) ? $rawMessage : []);
        $messageTimestamp = (int) ($data['messageTimestamp'] ?? $key['messageTimestamp'] ?? time());

        $text = $this->extractTextFromPayload($unwrapped, $data);
        if (($text === '' || $text === null) && $this->payloadHasReaction($unwrapped)) {
            $emoji = is_array($unwrapped['reactionMessage'] ?? null)
                ? (string) ($unwrapped['reactionMessage']['text'] ?? '')
                : '';
            $text = trim($emoji) !== '' ? '[Reação] '.$emoji : '[Reação]';
        }
        if (($text === '' || $text === null) && $this->payloadHasMedia($unwrapped)) {
            $text = '[Mídia]';
        }
        Log::info('Evolution processOneMessage', [
            'instance' => $instanceName,
            'remoteJid' => $remoteJid,
            'message_id' => $messageId,
            'has_text' => $text !== '' && $text !== null,
            'text_preview' => is_string($text) && $text !== '' ? substr($text, 0, 50) : '(vazio)',
        ]);
        if ($text === '' || $text === null) {
            if ($this->isOnlyProtocolNoise($unwrapped)) {
                Log::debug('Evolution MESSAGES_UPSERT ignorado (protocolo/sincronização)', [
                    'instance' => $instanceName,
                    'message_keys' => array_keys($unwrapped),
                ]);

                return;
            }
            Log::warning('Evolution MESSAGES_UPSERT ignorado (sem texto)', [
                'instance' => $instanceName,
                'message_keys' => array_keys($unwrapped),
                'data_keys' => array_keys($data),
            ]);
            return;
        }

        $contact = preg_replace('/@.*$/', '', $remoteJid);
        $contact = preg_replace('/[^0-9]/', '', $contact);
        if (strlen($contact) >= 10 && !str_starts_with($contact, '55')) {
            $contact = '55' . $contact;
        }

        $instanceModel = BotInstance::with('user:id,tenant_id')
            ->where('instance_name', $instanceName)
            ->first();
        $tenantId = $instanceModel?->effectiveTenantId();
        $userId = $instanceModel?->user_id;

        // Webhook não tem auth: BelongsToTenant não preenche tenant_id. Inbox usa escopo por tenant; instância pode ter tenant só no user.
        $conversation = Conversation::withoutGlobalScopes()->firstOrCreate(
            [
                'instance_name' => $instanceName,
                'contact' => $contact,
            ],
            [
                'contact_name' => $pushName,
                'last_message_at' => now(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
            ]
        );
        $conversation->update([
            'contact_name' => $pushName ?: $conversation->contact_name,
            'last_message_at' => now(),
            'tenant_id' => $tenantId ?? $conversation->tenant_id,
            'user_id' => $userId ?? $conversation->user_id,
        ]);

        if ($tenantId && $instanceModel && $instanceModel->tenant_id === null) {
            $instanceModel->update(['tenant_id' => $tenantId]);
        }

        $message = Message::withoutGlobalScopes()->firstOrCreate(
            [
                'conversation_id' => $conversation->id,
                'message_id' => $messageId,
            ],
            [
                'instance_name' => $instanceName,
                'from' => $remoteJid,
                'to' => null,
                'message' => $text,
                'direction' => $isOutgoing ? 'outgoing' : 'incoming',
                'raw_data' => $data,
                'timestamp' => \Carbon\Carbon::createFromTimestamp($messageTimestamp),
                'tenant_id' => $tenantId,
            ]
        );

        if ($message->wasRecentlyCreated && $isOutgoing) {
            Log::info('Evolution mensagem enviada gravada (fromMe)', [
                'instance' => $instanceName,
                'contact' => $contact,
                'text_preview' => strlen($text) > 80 ? substr($text, 0, 80) . '...' : $text,
            ]);
            return;
        }

        if ($message->wasRecentlyCreated && ! $isOutgoing) {
            Log::info('Evolution mensagem recebida e salva', [
                'instance' => $instanceName,
                'contact' => $contact,
                'text_preview' => strlen($text) > 80 ? substr($text, 0, 80) . '...' : $text,
            ]);
        }

        // Disparar job de forma idempotente (Evolution pode reenviar o mesmo messages.upsert).
        if (! $isOutgoing) {
            $dedupeKey = "evo:incoming:{$instanceName}:{$messageId}";
            $ttlSeconds = 180;
            $first = Redis::set($dedupeKey, '1', 'EX', $ttlSeconds, 'NX');

            if ($first) {
                if (config('services.evolution.dispatch_incoming_sync')) {
                    ProcessIncomingMessageJob::dispatchSync($instanceName, $contact, $remoteJid, $text, $message->id);
                } else {
                    ProcessIncomingMessageJob::dispatch($instanceName, $contact, $remoteJid, $text, $message->id);
                }

                Log::info('Evolution incoming job disparado', [
                    'instance' => $instanceName,
                    'contact' => $contact,
                    'message_id' => $messageId,
                    'dedupe_ttl' => $ttlSeconds,
                ]);
            } else {
                Log::debug('Evolution incoming duplicado (dedupe), job não disparado', [
                    'instance' => $instanceName,
                    'message_id' => $messageId,
                    'conversation_id' => $conversation->id,
                ]);
            }
        }
    }

    private function normalizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'yes'], true);
        }
        return (bool) $value;
    }

    /** Tipos Baileys comuns sem campo conversation/text. */
    private function payloadHasMedia(array $messageContent): bool
    {
        $keys = ['imageMessage', 'videoMessage', 'audioMessage', 'stickerMessage', 'documentMessage', 'ptvMessage'];
        foreach ($keys as $k) {
            if (! empty($messageContent[$k])) {
                return true;
            }
        }

        return false;
    }

    private function payloadHasReaction(array $messageContent): bool
    {
        return ! empty($messageContent['reactionMessage']);
    }

    /**
     * Desembrulha ephemeralMessage / viewOnce / etc. para ler texto ou mídia interior.
     */
    private function unwrapBaileysMessage(array $messageContent, int $depth = 0): array
    {
        if ($depth > 10) {
            return $messageContent;
        }

        $wrapperKeys = [
            'ephemeralMessage',
            'viewOnceMessage',
            'viewOnceMessageV2',
            'documentWithCaptionMessage',
            'buttonsResponseMessage',
            'templateMessage',
            'interactiveMessage',
        ];

        foreach ($wrapperKeys as $wk) {
            if (empty($messageContent[$wk]) || ! is_array($messageContent[$wk])) {
                continue;
            }
            $inner = $messageContent[$wk];
            if (! empty($inner['message']) && is_array($inner['message'])) {
                return $this->unwrapBaileysMessage($inner['message'], $depth + 1);
            }

            return $this->unwrapBaileysMessage($inner, $depth + 1);
        }

        return $messageContent;
    }

    /** Apenas chaves de protocolo Baileys (sem conteúdo para o Inbox). */
    private function isOnlyProtocolNoise(array $m): bool
    {
        if ($m === []) {
            return true;
        }
        $noise = ['senderKeyDistributionMessage', 'messageContextInfo', 'deviceSentMessage'];
        $keys = array_keys($m);

        return count(array_diff($keys, $noise)) === 0;
    }

    /**
     * Extrai texto da mensagem suportando vários formatos (Baileys, Evolution v2, evoapicloud).
     */
    private function extractTextFromPayload($messageContent, array $data): ?string
    {
        if (is_string($messageContent) && trim($messageContent) !== '') {
            return trim($messageContent);
        }
        if (! is_array($messageContent)) {
            $text = $data['text'] ?? $data['body'] ?? null;
            return is_string($text) ? trim($text) : null;
        }
        $text = $messageContent['conversation'] ?? $messageContent['text'] ?? $messageContent['body']
            ?? $messageContent['extendedTextMessage']['text'] ?? null;
        if (is_string($text) && trim($text) !== '') {
            return trim($text);
        }
        $text = $this->extractText($messageContent);
        if (is_string($text) && trim($text) !== '') {
            return trim($text);
        }
        // Legendas em mídia
        foreach (['imageMessage', 'videoMessage', 'documentMessage'] as $mk) {
            if (! empty($messageContent[$mk]['caption']) && is_string($messageContent[$mk]['caption'])) {
                $c = trim($messageContent[$mk]['caption']);
                if ($c !== '') {
                    return $c;
                }
            }
        }
        // Fallback: Evolution às vezes envia texto em data['text'] ou data['body']
        $text = $data['text'] ?? $data['body'] ?? null;
        return is_string($text) ? trim($text) : null;
    }

    private function extractText(array $messageContent): ?string
    {
        if (isset($messageContent['conversation'])) {
            return trim((string) $messageContent['conversation']);
        }
        if (isset($messageContent['extendedTextMessage']['text'])) {
            return trim((string) $messageContent['extendedTextMessage']['text']);
        }
        if (isset($messageContent['text'])) {
            return trim((string) $messageContent['text']);
        }
        return null;
    }
}
