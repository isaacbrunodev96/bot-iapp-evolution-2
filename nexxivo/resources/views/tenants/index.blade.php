@extends('layouts.app')

@section('title', 'Empresas (Tenants)')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-3xl font-bold">Empresas (Tenants)</h1>
        <a href="{{ route('tenants.create') }}" class="btn-primary text-white px-6 py-2 rounded-lg font-semibold">
            <i class="fas fa-plus mr-2"></i>Nova Empresa
        </a>
    </div>
    @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-800">
            {{ session('success') }}
        </div>
    @endif
    <table class="min-w-full bg-white border border-gray-200 rounded-lg">
        <thead>
            <tr>
                <th class="px-4 py-2">ID</th>
                <th class="px-4 py-2">Nome</th>
                <th class="px-4 py-2">Plano</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tenants as $tenant)
            <tr>
                <td class="px-4 py-2">{{ $tenant->id }}</td>
                <td class="px-4 py-2">{{ $tenant->name }}</td>
                <td class="px-4 py-2">{{ $tenant->plan }}</td>
                <td class="px-4 py-2">{{ $tenant->status }}</td>
                <td class="px-4 py-2">
                    <a href="{{ route('tenants.edit', $tenant->id) }}" class="text-blue-600 hover:underline mr-2">Editar</a>
                    <form action="{{ route('tenants.destroy', $tenant->id) }}" method="POST" class="inline" onsubmit="return confirm('Remover esta empresa?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:underline">Remover</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection