@extends('layouts.app')

@section('title', 'Perfil - Nexxivo')

@section('content')
<div class="p-6 md:p-10 max-w-4xl space-y-8">
    
    <!-- Cabeçalho com Botão Voltar -->
    <div>
        <a href="{{ route('settings.index') }}" class="text-sm text-gray-400 hover:text-white transition flex items-center gap-2 mb-4">
            <i class="fas fa-arrow-left"></i> Voltar para Configurações
        </a>
        <h1 class="text-3xl font-bold text-white tracking-tight">Meu Perfil</h1>
        <p class="text-gray-400 mt-2">Atualize suas informações pessoais e email de acesso.</p>
    </div>

    <!-- Alertas -->
    @if(session('success'))
    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-xl text-emerald-400 flex items-center gap-3">
        <i class="fas fa-check-circle"></i>
        {{ session('success') }}
    </div>
    @endif
    
    @if($errors->any())
    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-500">
        <p class="font-semibold mb-1 flex items-center gap-2"><i class="fas fa-exclamation-circle text-red-400"></i> Verifique os erros:</p>
        <ul class="list-disc list-inside text-sm ml-6">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Formulário -->
    <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl shadow-xl shadow-black/20 overflow-hidden">
        <form action="{{ route('settings.profile.update') }}" method="POST" class="p-6 md:p-8 space-y-6">
            @csrf
            
            <!-- Avatar (Visual) -->
            <div class="flex items-center gap-6 pb-6 border-b border-[#2A2A35]">
                <div class="w-20 h-20 rounded-full bg-gradient-to-tr from-purple-600 to-pink-500 flex items-center justify-center text-white font-bold text-3xl shadow-lg shadow-purple-500/20">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <button type="button" class="bg-[#1A1A24] text-white hover:bg-[#2A2A35] border border-[#2A2A35] px-4 py-2 rounded-xl text-sm font-medium transition flex items-center gap-2">
                        <i class="fas fa-camera"></i> Alterar Foto
                    </button>
                    <p class="text-xs text-gray-500 mt-2">Formatos JPG, GIF ou PNG. Tam. Max. 1MB</p>
                </div>
            </div>

            <!-- Dados Básicos -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Nome Completo</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                </div>
                
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Email de Acesso</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end">
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-500 hover:to-pink-400 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-purple-500/20 transition flex items-center gap-2">
                    <i class="fas fa-save"></i> Salvar Alterações
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
