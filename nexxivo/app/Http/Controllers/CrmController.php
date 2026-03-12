<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;

class CrmController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $instanceNames = \App\Models\BotInstance::where('user_id', auth()->id())
            ->where('tenant_id', $tenantId)
            ->pluck('instance_name');
        $statuses = ['novo', 'em_atendimento', 'aguardando', 'fechado'];

        $conversationsByStatus = [];
        foreach ($statuses as $status) {
            $conversationsByStatus[$status] = Conversation::with('latestMessage')
                ->where('tenant_id', $tenantId)
                ->whereIn('instance_name', $instanceNames)
                ->where('kanban_status', $status)
                ->where('is_archived', false)
                ->orderBy('last_message_at', 'desc')
                ->get();
        }

        return view('crm.index', compact('conversationsByStatus', 'statuses'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'kanban_status' => 'required|string|in:novo,em_atendimento,aguardando,fechado',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $instanceNames = \App\Models\BotInstance::where('user_id', auth()->id())
            ->where('tenant_id', $tenantId)
            ->pluck('instance_name');
        $conversation = Conversation::where('tenant_id', $tenantId)
            ->whereIn('instance_name', $instanceNames)
            ->findOrFail($id);
        $conversation->kanban_status = $request->kanban_status;
        $conversation->save();

        return response()->json([
            'success' => true,
            'message' => 'Status atualizado com sucesso',
        ]);
    }
}
