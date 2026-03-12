<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::orderBy('created_at', 'desc')->get();
        return view('tenants.index', compact('tenants'));
    }

    // API: retorna lista de tenants em JSON
    public function apiIndex()
    {
        $tenants = Tenant::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $tenants]);
    }

    public function create()
    {
        return view('tenants.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'plan' => 'required|string',
            'status' => 'required|string',
        ]);
        Tenant::create($request->only(['name', 'plan', 'status']));
        return redirect()->route('tenants.index')->with('success', 'Tenant criado com sucesso!');
    }

    public function edit($id)
    {
        $tenant = Tenant::findOrFail($id);
        return view('tenants.edit', compact('tenant'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'plan' => 'required|string',
            'status' => 'required|string',
        ]);
        $tenant = Tenant::findOrFail($id);
        $tenant->update($request->only(['name', 'plan', 'status']));
        return redirect()->route('tenants.index')->with('success', 'Tenant atualizado com sucesso!');
    }

    public function destroy($id)
    {
        $tenant = Tenant::findOrFail($id);
        $tenant->delete();
        return redirect()->route('tenants.index')->with('success', 'Tenant removido com sucesso!');
    }
}
