<?php

namespace App\Http\Controllers;

use App\Models\AISetting;
use App\Models\BotInstance;
use App\Models\Flow;
use App\Models\Tenant;
use Illuminate\Http\Request;

class FlowManagementController extends Controller
{
    public function index()
    {
        $tenantId = $this->effectiveTenantIdForListing();
        $instanceNames = BotInstance::forPanelUser()->pluck('instance_name');
        $flows = Flow::where('tenant_id', $tenantId)
            ->where(function ($query) use ($instanceNames) {
                $query->whereNull('instance_name')
                    ->orWhereIn('instance_name', $instanceNames);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('flows.index', compact('flows'));
    }

    public function create()
    {
        $defaultProvider = AISetting::get('default_provider', 'ollama');
        $instances = BotInstance::forPanelUser()->orderBy('instance_name')->get();

        return view('flows.create', compact('defaultProvider', 'instances'));
    }

    public function edit($id)
    {
        $tenantId = $this->effectiveTenantIdForListing();
        $instanceNames = BotInstance::forPanelUser()->pluck('instance_name');
        $flow = Flow::where('tenant_id', $tenantId)
            ->where(function ($query) use ($instanceNames) {
                $query->whereNull('instance_name')
                    ->orWhereIn('instance_name', $instanceNames);
            })
            ->findOrFail($id);
        $defaultProvider = AISetting::get('default_provider', 'ollama');
        $instances = BotInstance::forPanelUser()->orderBy('instance_name')->get();

        return view('flows.edit', compact('flow', 'defaultProvider', 'instances'));
    }

    /**
     * Mesma lógica do resto do painel: tenant do user ou único tenant quando existir só um.
     */
    private function effectiveTenantIdForListing(): ?int
    {
        $user = auth()->user();
        if ($user->tenant_id !== null) {
            return (int) $user->tenant_id;
        }
        if (Tenant::query()->count() === 1) {
            $v = Tenant::query()->value('id');

            return $v !== null ? (int) $v : null;
        }

        return null;
    }
}

