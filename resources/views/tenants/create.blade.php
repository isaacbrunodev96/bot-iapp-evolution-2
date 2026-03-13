@extends('layouts.app')

@section('title', 'Nova Empresa')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-3xl font-bold mb-6">Nova Empresa</h1>
    <form action="{{ route('tenants.store') }}" method="POST" class="bg-white p-6 rounded-lg border border-gray-200">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Nome</label>
            <input type="text" name="name" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Plano</label>
            <input type="text" name="plan" class="w-full px-4 py-2 border border-gray-300 rounded-lg" required value="basic">
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Status</label>
            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                <option value="active">Ativo</option>
                <option value="inactive">Inativo</option>
            </select>
        </div>
        <button type="submit" class="btn-primary text-white px-6 py-2 rounded-lg font-semibold">Salvar</button>
        <a href="{{ route('tenants.index') }}" class="ml-4 text-gray-600 hover:underline">Cancelar</a>
    </form>
</div>
@endsection