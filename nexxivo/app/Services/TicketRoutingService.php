<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use App\Models\TenantQueue;
use Illuminate\Support\Facades\Log;

class TicketRoutingService
{
    /**
     * Tenta rotear uma conversa nova/pendente para um Atendente.
     * Round-robin básico baseado na quantidade de tickets em aberto.
     */
    public function routeConversation(Conversation $conversation): void
    {
        // Se já tiver atendente ou não estiver pendente, não roteia
        if ($conversation->user_id || $conversation->status !== 'pending') {
            return;
        }

        // 1. Tentar rotear pela fila atual da conversa (se configurada previamente por um fluxo de Bot)
        if ($conversation->tenant_queue_id) {
            $user = $this->findAvailableAgentForQueue($conversation->tenant_queue_id, $conversation->tenant_id);
            if ($user) {
                $this->assignConversation($conversation, $user);
                return;
            }
        }

        // 2. Tentar achar alguma Fila default ativa do Tenant para colocar
        $defaultQueue = TenantQueue::where('tenant_id', $conversation->tenant_id)
            ->where('is_active', true)
            ->first();

        if ($defaultQueue) {
            $conversation->update(['tenant_queue_id' => $defaultQueue->id]);
            $user = $this->findAvailableAgentForQueue($defaultQueue->id, $conversation->tenant_id);
            if ($user) {
                $this->assignConversation($conversation, $user);
                return;
            }
        }

        // 3. Fallback: Assumir primeiro usuário do Tenant (Admin ou dono)
        $fallbackUser = User::where('tenant_id', $conversation->tenant_id)->first();
        if ($fallbackUser) {
             $this->assignConversation($conversation, $fallbackUser);
        }
    }

    private function findAvailableAgentForQueue(int $queueId, int $tenantId): ?User
    {
        // Regra Round-Robin: Pegar o Usuário associado a esta fila
        // Que possui a menor quantidade de conversas ativas (status 'open' ou 'pending')
        $user = User::where('tenant_id', $tenantId)
            ->whereHas('queues', function ($query) use ($queueId) {
                $query->where('tenant_queue_id', $queueId);
            })
            ->withCount(['conversations' => function ($query) {
                $query->whereIn('status', ['open', 'pending']);
            }])
            ->orderBy('conversations_count', 'asc')
            ->first();

        return $user;
    }

    private function assignConversation(Conversation $conversation, User $user): void
    {
        $conversation->update([
            'user_id' => $user->id,
            'status' => 'open', // Passa de pendente para Aberto
        ]);

        Log::info("Ticket Round-Robin Distribuído", [
            'conversation_id' => $conversation->id,
            'assigned_user_id' => $user->id
        ]);
    }
}
