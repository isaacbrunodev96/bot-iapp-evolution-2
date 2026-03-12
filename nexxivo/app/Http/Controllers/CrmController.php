<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Funnel;
use App\Models\FunnelStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Pega todos os funis do tenant para o dropdown
        $funnels = Funnel::where('tenant_id', $tenantId)->get();
        if ($funnels->isEmpty()) {
            return redirect()->route('dashboard')->with('error', 'Nenhum funil configurado.');
        }

        // Pega o funil selecionado ou o primeiro
        $currentFunnel = $request->has('funnel_id') 
            ? $funnels->where('id', $request->funnel_id)->first() 
            : $funnels->first();

        // Carrega os estágios com as conversas (tickets)
        $stages = FunnelStage::where('funnel_id', $currentFunnel->id)
            ->orderBy('order')
            ->with(['conversations' => function ($query) use ($tenantId) {
                $query->with('latestMessage')
                      ->where('tenant_id', $tenantId)
                      ->where('is_archived', false)
                      ->orderBy('last_message_at', 'desc');
            }])->get();

        return view('crm.index', compact('funnels', 'currentFunnel', 'stages'));
    }

    public function storeLead(Request $request)
    {
        $request->validate([
            'contact_name' => 'required|string|max:255',
            'contact' => 'required|string|max:20',
            'funnel_stage_id' => 'required|exists:funnel_stages,id',
        ]);

        $tenantId = auth()->user()->tenant_id;

        Conversation::create([
            'tenant_id' => $tenantId,
            'contact_name' => $request->contact_name,
            'contact' => $request->contact,
            'funnel_stage_id' => $request->funnel_stage_id,
            'status' => 'novo',
            'last_message_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Lead adicionado com sucesso!');
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'funnel_stage_id' => 'required|exists:funnel_stages,id',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $conversation = Conversation::where('tenant_id', $tenantId)->findOrFail($id);
        
        $conversation->funnel_stage_id = $request->funnel_stage_id;
        $conversation->save();

        return response()->json([
            'success' => true,
            'message' => 'Status movido com sucesso',
        ]);
    }

    // --- APIs for Kanban Customization --- //

    public function storeStage(Request $request, $funnelId)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:50',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $funnel = Funnel::where('tenant_id', $tenantId)->findOrFail($funnelId);
        
        $maxOrder = FunnelStage::where('funnel_id', $funnel->id)->max('order');

        $stage = FunnelStage::create([
            'tenant_id' => $tenantId,
            'funnel_id' => $funnel->id,
            'name' => $request->name,
            'color' => $request->color ?? '#8B5CF6',
            'order' => $maxOrder + 1,
        ]);

        return redirect()->back()->with('success', 'Nova coluna criada com sucesso!');
    }

    public function deleteStage($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $stage = FunnelStage::where('tenant_id', $tenantId)->findOrFail($id);

        if ($stage->conversations()->count() > 0) {
            return redirect()->back()->withErrors(['stage' => 'Não é possível excluir uma coluna que ainda possui cards/leads. Mova-os primeiro.']);
        }

        $stage->delete();
        return redirect()->back()->with('success', 'Coluna excluída com sucesso!');
    }

    public function reorderStages(Request $request)
    {
        $request->validate([
            'stages' => 'required|array',
            'stages.*.id' => 'required|exists:funnel_stages,id',
            'stages.*.order' => 'required|integer',
        ]);

        $tenantId = auth()->user()->tenant_id;
        
        DB::transaction(function () use ($request, $tenantId) {
            foreach ($request->stages as $stageData) {
                FunnelStage::where('tenant_id', $tenantId)
                    ->where('id', $stageData['id'])
                    ->update(['order' => $stageData['order']]);
            }
        });

        return response()->json(['success' => true]);
    }
}
