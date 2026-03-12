@extends('layouts.app')

@section('title', 'Segurança - Nexxivo')

@section('content')
<div class="p-6 md:p-10 max-w-4xl space-y-8">
    
    <!-- Cabeçalho com Botão Voltar -->
    <div>
        <a href="{{ route('settings.index') }}" class="text-sm text-gray-400 hover:text-white transition flex items-center gap-2 mb-4">
            <i class="fas fa-arrow-left"></i> Voltar para Configurações
        </a>
        <h1 class="text-3xl font-bold text-white tracking-tight flex items-center gap-3">
            <i class="fas fa-shield-alt text-purple-500"></i> Segurança
        </h1>
        <p class="text-gray-400 mt-2">Gerencie sua senha e métodos de segurança da conta.</p>
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
        <p class="font-semibold mb-1 flex items-center gap-2"><i class="fas fa-exclamation-circle text-red-400"></i> Erro ao alterar senha:</p>
        <ul class="list-disc list-inside text-sm ml-6">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Formulário -->
    <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl shadow-xl shadow-black/20 overflow-hidden">
        <form action="{{ route('settings.security.update') }}" method="POST" class="p-6 md:p-8 space-y-6">
            @csrf
            
            <h3 class="text-lg font-bold text-white mb-4">Alterar Senha</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Senha Atual</label>
                    <input type="password" name="current_password" required class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2 border-t border-[#2A2A35]/50">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Nova Senha</label>
                        <input type="password" name="password" required minlength="8" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                        <p class="text-xs text-gray-500 mt-2">Mínimo de 8 caracteres.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Confirmar Nova Senha</label>
                        <input type="password" name="password_confirmation" required minlength="8" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                    </div>
                </div>
            </div>

            <div class="pt-6 mt-6 border-t border-[#2A2A35] flex items-center justify-end">
                <button type="submit" class="bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-500 hover:to-pink-400 text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-purple-500/20 transition flex items-center gap-2">
                    <i class="fas fa-key"></i> Atualizar Senha
                </button>
            </div>
        </form>
    </div>

    <!-- Active Sessions Info (Visual) -->
    <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl shadow-xl shadow-black/20 overflow-hidden p-6 md:p-8">
        <h3 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-desktop text-gray-400"></i> Sessões Ativas
        </h3>
        <p class="text-sm text-gray-400 mb-6">Dispositivos que estão conectados à sua conta no momento.</p>

        <div class="flex items-center justify-between p-4 bg-[#0F0F13] border border-[#2A2A35] rounded-xl">
            <div class="flex items-center gap-4">
                <div class="text-emerald-500 bg-emerald-500/10 w-10 h-10 rounded-full flex items-center justify-center">
                    <i class="fas fa-laptop"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-white">Este dispositivo</p>
                    <p class="text-xs text-gray-400">Windows / Chrome (Visto agora)</p>
                </div>
            </div>
            <span class="text-xs font-bold text-emerald-500">Atual</span>
        </div>
    </div>
</div>
@endsection
