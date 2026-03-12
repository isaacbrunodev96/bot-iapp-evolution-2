<?php

namespace App\Http\Controllers;

use App\Models\BotInstance;
use App\Models\Conversation;
use App\Models\Flow;
use App\Models\Message;
use App\Models\TenantQueue;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $userId = auth()->id();

        // 1. Mensagens Recebidas (Total e Hoje)
        $conversationsCount = Conversation::where('tenant_id', $tenantId)->count();
        $messagesToday = Message::whereHas('conversation', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->whereDate('created_at', Carbon::today())->count();
        
        $messagesYesterday = Message::whereHas('conversation', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->whereDate('created_at', Carbon::yesterday())->count();

        $percentIncrease = 0;
        if ($messagesYesterday > 0) {
            $percentIncrease = (($messagesToday - $messagesYesterday) / $messagesYesterday) * 100;
        } elseif ($messagesToday > 0) {
            $percentIncrease = 100;
        }

        // 2. Automações Ativas
        $flowsCount = Flow::where('tenant_id', $tenantId)->count();

        // 3. Canais Conectados
        $instances = BotInstance::where('user_id', $userId)->get();
        $instancesCount = $instances->count();
        $connectedCount = $instances->where('status', 'connected')->count();

        // 4. Últimas Atividades
        $recentConvs = Conversation::where('tenant_id', $tenantId)
            ->with('latestMessage')
            ->orderBy('last_message_at', 'desc')
            ->limit(5)
            ->get();

        // 5. Filas de Atendimento (Bootstrapping se não houver)
        $queues = TenantQueue::where('tenant_id', $tenantId)->get();
        if ($queues->isEmpty()) {
            $this->bootstrapQueues($tenantId);
            $queues = TenantQueue::where('tenant_id', $tenantId)->get();
        }

        // Métricas das Filas
        $queueMetrics = $queues->map(function($queue) {
            return [
                'id' => $queue->id,
                'name' => $queue->name,
                'color' => $queue->color,
                'active' => $queue->is_active,
                'waiting' => Conversation::where('tenant_queue_id', $queue->id)->whereNull('user_id')->count()
            ];
        });

        return view('dashboard', compact(
            'instancesCount', 
            'connectedCount',
            'conversationsCount', 
            'messagesToday',
            'percentIncrease',
            'flowsCount', 
            'recentConvs',
            'queueMetrics'
        ));
    }

    private function bootstrapQueues($tenantId)
    {
        TenantQueue::create([
            'tenant_id' => $tenantId,
            'name' => 'Suporte Técnico',
            'description' => 'Fila para problemas técnicos',
            'color' => '#10B981',
            'is_active' => true
        ]);

        TenantQueue::create([
            'tenant_id' => $tenantId,
            'name' => 'Vendas',
            'description' => 'Fila para novos orçamentos',
            'color' => '#EC4899',
            'is_active' => true
        ]);
        
        TenantQueue::create([
            'tenant_id' => $tenantId,
            'name' => 'Financeiro',
            'description' => 'Fila para boletos e pagamentos',
            'color' => '#F59E0B',
            'is_active' => true
        ]);
    }
}
