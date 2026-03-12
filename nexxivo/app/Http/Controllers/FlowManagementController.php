<?php

namespace App\Http\Controllers;

use App\Models\Flow;
use App\Models\AISetting;
use Illuminate\Http\Request;

class FlowManagementController extends Controller
{
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;
        $instanceNames = \App\Models\BotInstance::where('user_id', auth()->id())
            ->where('tenant_id', $tenantId)
            ->pluck('instance_name');
        $flows = Flow::where('tenant_id', $tenantId)
            ->where(function($query) use ($instanceNames) {
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
        $tenantId = auth()->user()->tenant_id;
        $defaultProvider = AISetting::get('default_provider', 'ollama');
        $instances = \App\Models\BotInstance::where('user_id', auth()->id())
            ->where('tenant_id', $tenantId)
            ->orderBy('instance_name')
            ->get();
        return view('flows.create', compact('defaultProvider', 'instances'));
    }

    public function edit($id)
    {
        $tenantId = auth()->user()->tenant_id;
        $instanceNames = \App\Models\BotInstance::where('user_id', auth()->id())
            ->where('tenant_id', $tenantId)
            ->pluck('instance_name');
        $flow = Flow::where('tenant_id', $tenantId)
            ->where(function($query) use ($instanceNames) {
                $query->whereNull('instance_name')
                      ->orWhereIn('instance_name', $instanceNames);
            })
            ->findOrFail($id);
        $defaultProvider = AISetting::get('default_provider', 'ollama');
        $instances = \App\Models\BotInstance::where('user_id', auth()->id())
            ->where('tenant_id', $tenantId)
            ->orderBy('instance_name')
            ->get();
        return view('flows.edit', compact('flow', 'defaultProvider', 'instances'));
    }
}

