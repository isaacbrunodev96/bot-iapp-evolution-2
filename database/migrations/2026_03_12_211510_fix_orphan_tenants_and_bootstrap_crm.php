<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Tenant;
use App\Models\User;
use App\Models\BotInstance;
use App\Models\Conversation;
use App\Models\Flow;
use App\Models\Funnel;
use App\Models\FunnelStage;
use App\Models\AISetting;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Criar o Tenant Master se não houver nenhum
        $tenant = Tenant::first();
        if (!$tenant) {
            $tenant = Tenant::create([
                'name' => 'Nexxivo Master',
                'plan' => 'enterprise',
                'status' => 'active'
            ]);
        }

        // 2. Vincular usuários sem tenant a este tenant
        User::whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);

        // 3. Vincular outras entidades órfãs
        BotInstance::whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
        Conversation::whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
        Flow::whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
        AISetting::whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);

        // 4. Se o CRM (funis) estiver vazio para este tenant, povoar agora
        if (Funnel::where('tenant_id', $tenant->id)->count() === 0) {
            $funnel = Funnel::create([
                'tenant_id' => $tenant->id,
                'name' => 'Pipeline de Vendas',
                'is_default' => true
            ]);

            $stageNovo = FunnelStage::create([
                'tenant_id' => $tenant->id,
                'funnel_id' => $funnel->id,
                'name' => 'Novos Leads',
                'color' => '#8B5CF6',
                'order' => 1
            ]);

            $stageAtend = FunnelStage::create([
                'tenant_id' => $tenant->id,
                'funnel_id' => $funnel->id,
                'name' => 'Em Atendimento',
                'color' => '#10B981',
                'order' => 2
            ]);

            $stageAguardando = FunnelStage::create([
                'tenant_id' => $tenant->id,
                'funnel_id' => $funnel->id,
                'name' => 'Aguardando Cliente',
                'color' => '#F59E0B',
                'order' => 3
            ]);

            $stageFinalizado = FunnelStage::create([
                'tenant_id' => $tenant->id,
                'funnel_id' => $funnel->id,
                'name' => 'Finalizados',
                'color' => '#3B82F6',
                'order' => 4
            ]);

            // Vincular conversas órfãs aos estágios iniciais baseados no status antigo
            Conversation::where('tenant_id', $tenant->id)
                ->whereNull('funnel_stage_id')
                ->each(function($conv) use ($stageNovo, $stageAtend, $stageAguardando, $stageFinalizado) {
                    $stageId = $stageNovo->id;
                    if ($conv->kanban_status == 'em_atendimento') $stageId = $stageAtend->id;
                    if ($conv->kanban_status == 'aguardando') $stageId = $stageAguardando->id;
                    if ($conv->kanban_status == 'fechado' || $conv->kanban_status == 'resolvido') $stageId = $stageFinalizado->id;
                    
                    $conv->update(['funnel_stage_id' => $stageId]);
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
