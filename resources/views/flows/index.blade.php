@extends('layouts.app')

@section('title', 'Automações - Nexxivo')

@section('content')
<div class="p-6 md:p-10 max-w-7xl mx-auto space-y-8">
    
    <!-- Cabeçalho -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Automações</h1>
            <p class="text-gray-400 mt-2">Crie e gerencie os fluxos de automação do seu bot.</p>
        </div>
        <a href="{{ route('flows.create') }}" class="bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-500 hover:to-pink-400 text-white px-6 py-2.5 rounded-full font-medium shadow-lg shadow-purple-500/20 transition transform hover:-translate-y-0.5 flex items-center gap-2">
            <i class="fas fa-plus"></i>
            Novo Fluxo
        </a>
    </div>

    <!-- Lista de Fluxos -->
    <div class="space-y-4">
        @forelse($flows as $flow)
        <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl flex flex-col hover:border-purple-500/30 transition-colors shadow-lg shadow-black/10 overflow-hidden">
            
            <div class="p-6 md:flex md:items-start md:justify-between gap-6">
                
                <div class="flex-1 space-y-4">
                    <!-- Title Row -->
                    <div class="flex items-center gap-3 flex-wrap">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-400 shrink-0">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <h3 class="text-lg font-bold text-white">{{ $flow->name }}</h3>
                        
                        @if($flow->is_active)
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>Ativo</span>
                        @else
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-gray-500/10 text-gray-400 border border-gray-500/20">Inativo</span>
                        @endif

                        @if($flow->priority > 0)
                            <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-500/10 text-blue-400 border border-blue-500/20 ml-auto md:ml-0">
                                Prio: {{ $flow->priority }}
                            </span>
                        @endif
                    </div>
                    
                    @if($flow->description)
                        <p class="text-sm text-gray-400">{{ $flow->description }}</p>
                    @endif
                    
                    <!-- Details Sections -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2">
                        <!-- Gatilhos -->
                        <div class="bg-[#0F0F13] border border-[#2A2A35] rounded-xl p-4">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2 flex items-center gap-2"><i class="fas fa-bolt text-yellow-500"></i> Gatilhos</h4>
                            <ul class="text-sm text-gray-300 space-y-1.5 ml-1">
                                @foreach($flow->triggers as $trigger)
                                <li class="flex items-center gap-2">
                                    <span class="w-1 h-1 rounded-full bg-gray-500 shrink-0"></span>
                                    @if($trigger['type'] === 'catch_all')
                                        Qualquer Mensagem
                                    @elseif(isset($trigger['value']))
                                        {{ ucfirst(str_replace('_', ' ', $trigger['type'])) }}: <span class="text-white">"{{ $trigger['value'] }}"</span>
                                    @else
                                        {{ ucfirst(str_replace('_', ' ', $trigger['type'])) }}
                                    @endif
                                </li>
                                @endforeach
                            </ul>
                        </div>

                        <!-- Ações -->
                        <div class="bg-[#0F0F13] border border-[#2A2A35] rounded-xl p-4">
                            <h4 class="text-xs font-semibold text-gray-400 uppercase mb-2 flex items-center gap-2"><i class="fas fa-play text-emerald-500"></i> Ações Principais</h4>
                            <ul class="text-sm text-gray-300 space-y-1.5 ml-1 truncate">
                                @foreach(array_slice($flow->actions, 0, 3) as $action)
                                <li class="flex items-center gap-2 truncate">
                                    <span class="w-1 h-1 rounded-full bg-gray-500 shrink-0"></span>
                                    @if($action['type'] === 'send_message')
                                        Enviar texto
                                    @elseif($action['type'] === 'wait')
                                        Aguardar {{ isset($action['duration']) ? $action['duration']/1000 : 0 }}s
                                    @elseif($action['type'] === 'ai_response')
                                        Resposta IA ({{ ucfirst($action['provider'] ?? 'Local') }})
                                    @elseif($action['type'] === 'conditional')
                                        Condição Lógica
                                    @else
                                        {{ ucfirst($action['type']) }}
                                    @endif
                                </li>
                                @endforeach
                                @if(count($flow->actions) > 3)
                                <li class="text-xs text-gray-500 mt-1 italic">+ {{ count($flow->actions) - 3 }} outras ações...</li>
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Actions menu -->
                <div class="mt-6 md:mt-0 md:ml-6 flex flex-row md:flex-col gap-2 shrink-0 border-t md:border-t-0 md:border-l border-[#2A2A35] pt-4 md:pt-0 md:pl-6 justify-end">
                    <a href="{{ route('flows.edit', $flow->id) }}" class="bg-[#0F0F13] hover:bg-[#1A1A24] border border-[#2A2A35] text-white px-4 py-2 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <button onclick="toggleFlow({{ $flow->id }}, {{ $flow->is_active ? 'false' : 'true' }})" class="{{ $flow->is_active ? 'bg-orange-500/10 text-orange-500 border-orange-500/20 hover:bg-orange-500/20' : 'bg-emerald-500/10 text-emerald-500 border-emerald-500/20 hover:bg-emerald-500/20' }} border px-4 py-2 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2">
                        <i class="fas fa-power-off"></i> {{ $flow->is_active ? 'Desativar' : 'Ativar' }}
                    </button>
                    <button onclick="deleteFlow({{ $flow->id }})" class="bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-500 px-4 py-2 rounded-lg text-sm font-medium transition flex items-center justify-center gap-2 mt-auto">
                        <i class="fas fa-trash-alt"></i> Excluir
                    </button>
                </div>
                
            </div>
            
        </div>
        @empty
        <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-12 text-center flex flex-col items-center justify-center">
            <div class="w-16 h-16 rounded-full bg-[#2A2A35] flex items-center justify-center text-gray-500 mb-4">
                <i class="fas fa-project-diagram text-2xl"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Sem Automacões</h3>
            <p class="text-gray-400 mb-6">Você ainda não criou nenhum fluxo de resposta para o seu robô.</p>
            <a href="{{ route('flows.create') }}" class="bg-purple-600 hover:bg-purple-500 text-white px-6 py-2.5 rounded-full font-medium transition">
                Criar Primeiro Fluxo
            </a>
        </div>
        @endforelse
    </div>
</div>

<script>
async function toggleFlow(id, isActive) {
    try {
        const response = await axios.put(`/api/flows/${id}`, { is_active: isActive });
        if (response.data.success) location.reload();
    } catch (error) {
        console.error('Erro:', error);
        alert('Falha na comunicação com o servidor.');
    }
}

async function deleteFlow(id) {
    if (!confirm('Deseja excluir este fluxo permanentemente?')) return;
    try {
        const response = await axios.delete(`/api/flows/${id}`);
        if (response.data.success) location.reload();
    } catch (error) {
        console.error('Erro:', error);
        alert('Falha na comunicação com o servidor.');
    }
}
</script>
@endsection
