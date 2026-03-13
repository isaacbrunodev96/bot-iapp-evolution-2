@extends('layouts.app')

@section('title', 'Dashboard - Nexxivo')

@section('content')
<div class="p-6 md:p-10 max-w-7xl mx-auto space-y-8">
    
    <!-- Cabeçalho -->
    <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Dashboard</h1>
        <p class="text-gray-400 mt-2">Bem-vindo ao painel de controle.</p>
    </div>

    <!-- Estatísticas Principais -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        
        <!-- Card 1: Mensagens -->
        <div class="bg-gradient-to-br from-purple-900/60 to-purple-900/10 border border-purple-500/20 rounded-2xl p-6 relative overflow-hidden shadow-lg shadow-purple-900/10 transition-transform hover:scale-[1.02]">
            <div class="absolute right-0 top-0 mt-6 mr-6 text-purple-400/20">
                <i class="fas fa-comment-alt text-6xl"></i>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-semibold text-purple-300 uppercase tracking-wider mb-2">Mensagens (Total)</h3>
                <div class="flex items-end gap-3">
                    <span class="text-4xl font-bold text-white">{{ number_format($conversationsCount) }}</span>
                    <span class="text-sm font-medium {{ $percentIncrease >= 0 ? 'text-emerald-400' : 'text-red-400' }} mb-1">
                        {{ $percentIncrease >= 0 ? '+' : '' }}{{ round($percentIncrease) }}% hoje
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 2: Automações -->
        <div class="bg-gradient-to-br from-[#1A1F35] to-[#121629] border border-blue-500/20 rounded-2xl p-6 relative overflow-hidden shadow-lg shadow-blue-900/10 transition-transform hover:scale-[1.02]">
            <div class="absolute right-0 top-0 mt-6 mr-6 text-blue-400/10">
                <i class="fas fa-bolt text-6xl"></i>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-semibold text-blue-300 uppercase tracking-wider mb-2">Automações Ativas</h3>
                <div class="flex items-end gap-3">
                    <span class="text-4xl font-bold text-white">{{ $flowsCount }}</span>
                    <span class="text-sm font-medium text-gray-400 mb-1">Fluxos rodando</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Canais -->
        <div class="bg-gradient-to-br from-[#122A23] to-[#0A1A14] border border-emerald-500/20 rounded-2xl p-6 relative overflow-hidden shadow-lg shadow-emerald-900/10 transition-transform hover:scale-[1.02]">
            <div class="absolute right-0 top-0 mt-6 mr-6 text-emerald-400/10">
                <i class="fas fa-broadcast-tower text-6xl"></i>
            </div>
            <div class="relative z-10">
                <h3 class="text-xs font-semibold text-emerald-300 uppercase tracking-wider mb-2">Canais Conectados</h3>
                <div class="flex items-end gap-3">
                    <span class="text-4xl font-bold text-white">{{ $connectedCount }}</span>
                    <span class="text-sm font-medium text-gray-400 mb-1">/ {{ $instancesCount }} Ativos</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Layout Dividido Inferior -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Fila de Atendimento -->
        <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-6 shadow-xl shadow-black/20">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fas fa-users text-fuchsia-500"></i> Fila de Atendimento
                </h2>
                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest bg-white/5 px-2 py-1 rounded">Tempo Real</span>
            </div>

            <div class="space-y-4">
                @foreach($queueMetrics as $queue)
                <div class="flex items-center justify-between p-4 rounded-xl bg-[#0F0F13] border border-[#2A2A35] hover:border-fuchsia-500/30 transition-all group">
                    <div class="flex items-center gap-4">
                        <div class="w-1.5 h-8 rounded-full" style="background-color: {{ $queue['color'] }}"></div>
                        <div>
                            <span class="text-sm font-black text-white uppercase tracking-tighter">{{ $queue['name'] }}</span>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="w-1.5 h-1.5 rounded-full {{ $queue['active'] ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                <span class="text-[9px] text-gray-500 font-bold uppercase">{{ $queue['active'] ? 'Ativa' : 'Inativa' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="block text-xl font-black text-white leading-none">{{ $queue['waiting'] }}</span>
                        <span class="text-[9px] text-gray-500 font-bold uppercase tracking-widest">Aguardando</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Últimas Atividades -->
        <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-6 shadow-xl shadow-black/20">
            <h2 class="text-lg font-bold text-white mb-6">Últimas Atividades</h2>

            <div class="space-y-6">
                @forelse($recentConvs as $conv)
                <div class="flex items-start gap-4 p-3 rounded-xl hover:bg-white/[0.02] transition-colors">
                    <div class="w-10 h-10 rounded-full bg-[#2A2A35] flex items-center justify-center shrink-0 text-gray-400 shadow-inner">
                        <i class="far fa-user"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-200 font-bold truncate">{{ $conv->contact_name ?? $conv->contact }}</p>
                            <span class="text-[10px] text-gray-600 font-medium">{{ $conv->last_message_at ? $conv->last_message_at->diffForHumans() : 'Recente' }}</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-0.5 truncate italic">
                            {{ $conv->latestMessage ? Str::limit($conv->latestMessage->content, 40) : 'Nova conversa cadastrada' }}
                        </p>
                    </div>
                    <a href="{{ route('chat.show', $conv->id) }}" class="w-8 h-8 rounded-lg bg-[#2A2A35] hover:bg-fuchsia-600 flex items-center justify-center text-gray-500 hover:text-white transition-all">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                </div>
                @empty
                <div class="text-center py-12">
                    <div class="w-16 h-16 bg-[#0F0F13] rounded-full flex items-center justify-center mx-auto mb-4 border border-[#2A2A35]/50">
                        <i class="fas fa-history text-2xl text-gray-700"></i>
                    </div>
                    <p class="text-sm text-gray-500 font-medium">Nenhuma atividade recente encontrada.</p>
                </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
</div>
@endsection
