<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BotInstance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BotController extends Controller
{
    public function status(Request $request)
    {
        $validated = $request->validate([
            'instance_name' => 'required|string',
            'status' => 'required|string|in:started,stopped,connected,disconnected,auth_failure',
            'phone' => 'nullable|string',
            'name' => 'nullable|string',
        ]);

        // Buscar instância existente
        $instance = BotInstance::firstOrNew(['instance_name' => $validated['instance_name']]);
        
        // Não permitir downgrade de status (connected não pode voltar para started)
        $statusHierarchy = ['stopped' => 0, 'started' => 1, 'connected' => 2];
        $currentStatus = $instance->status ?? 'stopped';
        $currentStatusLevel = isset($statusHierarchy[$currentStatus]) 
            ? $statusHierarchy[$currentStatus] 
            : 0;
        $newStatus = $validated['status'] ?? 'stopped';
        $newStatusLevel = isset($statusHierarchy[$newStatus]) 
            ? $statusHierarchy[$newStatus] 
            : 0;
        
        // Só atualizar se o novo status for maior ou igual
        if ($newStatusLevel >= $currentStatusLevel) {
            $instance->status = $validated['status'];
            
            // Se for conexão bem-sucedida, limpar QR code
            if ($validated['status'] === 'connected') {
                $instance->qrcode = null;
                $instance->qrcode_generated_at = null;
            }
        }
        
        $instance->save();

        Log::info('Status do bot atualizado', [
            'instance' => $validated['instance_name'],
            'status' => $validated['status'],
            'phone' => $validated['phone'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $instance,
        ]);
    }

    public function getQrcode(Request $request, $instanceName)
    {
        $instance = BotInstance::where('instance_name', $instanceName);
        
        // Verifica auth via token para tenant isolamento api
        if ($request->has('tenant_id')) {
            $instance->withoutGlobalScope('tenant')->where('tenant_id', $request->tenant_id);
        }

        $instance = $instance->first();

        if (!$instance) {
            return response()->json([
                'success' => false,
                'message' => 'Instance not found',
                'data' => [
                    'qrcode' => null,
                    'status' => 'disconnected',
                    'generated_at' => null,
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'qrcode' => $instance->qrcode,
                'status' => $instance->status ?? 'disconnected',
                'generated_at' => $instance->qrcode_generated_at,
            ],
        ]);
    }

    public function sendMessage(Request $request, \App\Services\EvolutionApiService $evolution)
    {
        $validated = $request->validate([
            'instance_name' => 'required|string',
            'contact' => 'required|string',
            'message' => 'required|string',
        ]);

        $instance = BotInstance::where('instance_name', $validated['instance_name'])
            ->when(auth()->check(), fn ($q) => $q->where('user_id', auth()->id()))
            ->first();

        if ($instance && config('services.evolution.apikey')) {
            try {
                $evolution->sendText(
                    $validated['instance_name'],
                    $validated['contact'],
                    $validated['message']
                );
                $this->persistOutgoingMessage($validated);
                return response()->json(['success' => true, 'message' => 'Mensagem enviada com sucesso']);
            } catch (\Throwable $e) {
                Log::warning('Evolution sendMessage failed', ['error' => $e->getMessage()]);
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }

        $botUrl = config('services.bot.url', env('BOT_URL', 'http://localhost:3001'));
        try {
            $response = Http::timeout(30)->post("{$botUrl}/send-message", [
                'contact' => $validated['contact'],
                'message' => $validated['message'],
                'instance_name' => $validated['instance_name'],
            ]);
            if ($response->successful()) {
                return response()->json(['success' => true, 'message' => 'Mensagem enviada com sucesso', 'data' => $response->json()]);
            }
            return response()->json(['success' => false, 'message' => 'Erro ao enviar mensagem', 'error' => $response->json()], $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao enviar mensagem. Verifique se o bot está rodando.', 'error' => $e->getMessage()], 500);
        }
    }

    private function persistOutgoingMessage(array $validated): void
    {
        $conversation = \App\Models\Conversation::withoutGlobalScopes()
            ->where('instance_name', $validated['instance_name'])
            ->where('contact', $validated['contact'])
            ->first();
        if ($conversation) {
            $msg = \App\Models\Message::withoutGlobalScopes()->create([
                'conversation_id' => $conversation->id,
                'instance_name' => $validated['instance_name'],
                'message_id' => 'ev_' . uniqid(),
                'from' => $validated['instance_name'] . '@bot',
                'to' => $validated['contact'],
                'message' => $validated['message'],
                'direction' => 'outgoing',
                'timestamp' => now(),
                'tenant_id' => $conversation->tenant_id,
            ]);
            $conversation->update(['last_message_at' => now()]);

            // Auditar disparo
            if (auth()->check()) {
                \App\Models\ActionLog::create([
                     'action_type' => 'message_sent',
                     'entity_type' => 'Conversation',
                     'entity_id' => $conversation->id,
                     'description' => 'Agente enviou uma mensagem',
                     'meta_data' => ['message_id' => $msg->id],
                     'user_id' => auth()->id(),
                     'tenant_id' => auth()->user()->tenant_id,
                ]);
            }
        }
    }

    public function startInstance(Request $request)
    {
        $validated = $request->validate([
            'instance_name' => 'required|string',
        ]);

        $tenantId = $request->tenant_id ?? auth()->user()->tenant_id;
        if (!$tenantId) {
             return response()->json(['success' => false, 'message' => 'Tenant ID ausente'], 400);
        }

        $botUrl = config('services.bot.url', env('BOT_URL', 'http://localhost:3001'));
        
        try {
            $response = Http::timeout(30)->post("{$botUrl}/instances/create", [
                'instance_name' => $validated['instance_name'],
                'tenant_id' => $tenantId
            ]);
            
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao conectar no BotManager', 'error' => $e->getMessage()], 500);
        }
    }

    public function stopInstance(Request $request)
    {
        $validated = $request->validate([
            'instance_name' => 'required|string',
        ]);

        $botUrl = config('services.bot.url', env('BOT_URL', 'http://localhost:3001'));
        
        try {
            $response = Http::timeout(30)->send('DELETE', "{$botUrl}/instances/destroy", [
                 'json' => ['instance_name' => $validated['instance_name']]
            ]);
            
            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erro ao conectar no BotManager', 'error' => $e->getMessage()], 500);
        }
    }
}

