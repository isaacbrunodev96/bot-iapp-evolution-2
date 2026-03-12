<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\Conversation;

return new class extends Migration
{
    public function up(): void
    {
        // Pega todos os tenants existentes
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            // Cria um funil padrao "Pipeline de Vendas"
            $funnel = Funnel::create([
                'tenant_id' => $tenant->id,
                'name' => 'Pipeline de Vendas',
                'is_default' => true
            ]);

            // Cria as etapas tradicionais para ele
            $stageNovo = FunnelStage::create([
                'tenant_id' => $tenant->id,
                'funnel_id' => $funnel->id,
                'name' => 'Novos Leads',
                'color' => '#8B5CF6', // purple-500
                'order' => 1
            ]);

            $stageAtend = FunnelStage::create([
                'tenant_id' => $tenant->id,
                'funnel_id' => $funnel->id,
                'name' => 'Em Atendimento',
                'color' => '#10B981', // emerald-500
                'order' => 2
            ]);

            $stageAguardando = FunnelStage::create([
                'tenant_id' => $tenant->id,
                'funnel_id' => $funnel->id,
                'name' => 'Aguardando Cliente',
                'color' => '#F59E0B', // amber-500
                'order' => 3
            ]);

            $stageFinalizado = FunnelStage::create([
                'tenant_id' => $tenant->id,
                'funnel_id' => $funnel->id,
                'name' => 'Finalizados',
                'color' => '#3B82F6', // blue-500
                'order' => 4
            ]);

            // Migrar conversas antigas (baseadas no status textual) para essas novas IDs //
            Conversation::where('tenant_id', $tenant->id)
                ->where('kanban_status', 'novo')
                ->update(['funnel_stage_id' => $stageNovo->id]);

            Conversation::where('tenant_id', $tenant->id)
                ->where('kanban_status', 'em_atendimento')
                ->update(['funnel_stage_id' => $stageAtend->id]);

            Conversation::where('tenant_id', $tenant->id)
                ->where('kanban_status', 'aguardando')
                ->update(['funnel_stage_id' => $stageAguardando->id]);

            Conversation::where('tenant_id', $tenant->id)
                ->where('kanban_status', 'fechado')
                ->update(['funnel_stage_id' => $stageFinalizado->id]);
        }
    }

    public function down(): void
    {
        // Nao fazemos o down destrutivo das conversas, apenas o artisan rollback truncará os funnels.
    }
};
