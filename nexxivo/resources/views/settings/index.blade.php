@extends('layouts.app')

@section('title', 'Configurações - Nexxivo')

@section('content')
<div class="p-6 md:p-10 max-w-4xl space-y-8">
    
    <!-- Cabeçalho -->
    <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Configurações</h1>
        <p class="text-gray-400 mt-2">Gerencie as preferências do sistema.</p>
    </div>

    <!-- Lista de Configurações -->
    <div class="space-y-4">
        
        <!-- Perfil -->
        <a href="{{ route('settings.profile') }}" class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-5 flex items-center justify-between hover:border-purple-500/50 hover:bg-[#1A1A24] transition-all group">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-xl bg-[#2A2A35] group-hover:bg-purple-500/10 flex items-center justify-center text-gray-400 group-hover:text-purple-400 transition-colors shrink-0">
                    <i class="far fa-user text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg">Perfil</h3>
                    <p class="text-sm text-gray-400 mt-0.5">Nome, email e foto de perfil</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-gray-600 group-hover:text-purple-400 transition-colors"></i>
        </a>

        <!-- Segurança -->
        <a href="{{ route('settings.security') }}" class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-5 flex items-center justify-between hover:border-purple-500/50 hover:bg-[#1A1A24] transition-all group">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-xl bg-[#2A2A35] group-hover:bg-purple-500/10 flex items-center justify-center text-gray-400 group-hover:text-purple-400 transition-colors shrink-0">
                    <i class="fas fa-shield-alt text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg">Segurança</h3>
                    <p class="text-sm text-gray-400 mt-0.5">Senha, 2FA e sessões ativas</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-gray-600 group-hover:text-purple-400 transition-colors"></i>
        </a>

        <!-- Notificações -->
        <a href="#" class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-5 flex items-center justify-between hover:border-purple-500/50 hover:bg-[#1A1A24] transition-all group">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-xl bg-[#2A2A35] group-hover:bg-purple-500/10 flex items-center justify-center text-gray-400 group-hover:text-purple-400 transition-colors shrink-0">
                    <i class="far fa-bell text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg">Notificações</h3>
                    <p class="text-sm text-gray-400 mt-0.5">Alertas por email e push</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-gray-600 group-hover:text-purple-400 transition-colors"></i>
        </a>

        <!-- Aparência -->
        <a href="#" class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-5 flex items-center justify-between hover:border-purple-500/50 hover:bg-[#1A1A24] transition-all group">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-xl bg-[#2A2A35] group-hover:bg-purple-500/10 flex items-center justify-center text-gray-400 group-hover:text-purple-400 transition-colors shrink-0">
                    <i class="fas fa-palette text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg">Aparência</h3>
                    <p class="text-sm text-gray-400 mt-0.5">Tema e personalização</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-gray-600 group-hover:text-purple-400 transition-colors"></i>
        </a>

        <!-- Idioma & Região -->
        <a href="#" class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-5 flex items-center justify-between hover:border-purple-500/50 hover:bg-[#1A1A24] transition-all group">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-xl bg-[#2A2A35] group-hover:bg-purple-500/10 flex items-center justify-center text-gray-400 group-hover:text-purple-400 transition-colors shrink-0">
                    <i class="fas fa-globe text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg">Idioma & Região</h3>
                    <p class="text-sm text-gray-400 mt-0.5">Fuso horário e localização</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-gray-600 group-hover:text-purple-400 transition-colors"></i>
        </a>

        <!-- API & Integrações -->
        <a href="#" class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-5 flex items-center justify-between hover:border-purple-500/50 hover:bg-[#1A1A24] transition-all group">
            <div class="flex items-center gap-5">
                <div class="w-12 h-12 rounded-xl bg-[#2A2A35] group-hover:bg-purple-500/10 flex items-center justify-center text-gray-400 group-hover:text-purple-400 transition-colors shrink-0">
                    <i class="fas fa-key text-xl"></i>
                </div>
                <div>
                    <h3 class="font-bold text-white text-lg">API & Integrações</h3>
                    <p class="text-sm text-gray-400 mt-0.5">Chaves de API e webhooks</p>
                </div>
            </div>
            <i class="fas fa-chevron-right text-gray-600 group-hover:text-purple-400 transition-colors"></i>
        </a>

    </div>
</div>
@endsection
