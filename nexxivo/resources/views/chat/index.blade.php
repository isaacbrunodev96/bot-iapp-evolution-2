@extends('layouts.app')

@section('title', 'Inbox - Nexxivo')

@section('content')
<div class="p-6 md:p-10 max-w-7xl mx-auto flex flex-col h-full bg-[#0F0F13]">
    
    <!-- Cabeçalho -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-white tracking-tight">Inbox</h1>
        <p class="text-gray-400 mt-2">Gerencie todas as conversas em um só lugar.</p>
    </div>

    <!-- Busca e filtro por instância -->
    <div class="flex flex-col gap-4 mb-6">
        <div class="relative flex-1">
            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                <i class="fas fa-search text-gray-500"></i>
            </div>
            <input type="text" placeholder="Buscar conversas..." class="w-full bg-[#16161D] border border-[#2A2A35] rounded-xl text-white pl-11 pr-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition placeholder-gray-500">
        </div>
        @if($instances->count() > 1)
        <div class="flex flex-col gap-2">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Instância</span>
            <div class="flex items-center gap-2 flex-wrap">
                <a href="{{ route('chat.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border transition whitespace-nowrap {{ ($instanceFilter ?? null) === null ? 'bg-gradient-to-r from-purple-600/25 to-pink-600/25 border-purple-500/50 text-white' : 'bg-[#16161D] border-[#2A2A35] text-gray-400 hover:border-purple-500/50 hover:text-white' }}">
                    Todas
                </a>
                @foreach($instances as $inst)
                <a href="{{ route('chat.index', ['instance' => $inst->instance_name]) }}" title="{{ $inst->instance_name }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium border transition whitespace-nowrap max-w-[min(100%,14rem)] truncate {{ ($instanceFilter ?? null) === $inst->instance_name ? 'bg-gradient-to-r from-purple-600/25 to-pink-600/25 border-purple-500/50 text-white' : 'bg-[#16161D] border-[#2A2A35] text-gray-400 hover:border-purple-500/50 hover:text-white' }}">
                    {{ $inst->instance_name }}
                </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Lista de Conversas -->
    <div class="flex-1 bg-[#16161D] border border-[#2A2A35] rounded-2xl flex flex-col overflow-hidden shadow-xl shadow-black/20">
        
        <div class="overflow-y-auto flex-1 divide-y divide-[#2A2A35]/50">
            @php $lastInstanceSection = null; @endphp
            @forelse($conversations as $conversation)
            @if(($instanceFilter ?? null) === null && $instances->count() > 1 && $lastInstanceSection !== $conversation->instance_name)
            @php $lastInstanceSection = $conversation->instance_name; @endphp
            <div class="px-5 py-2.5 bg-[#14141C] border-b border-[#2A2A35]/60">
                <span class="text-xs font-semibold text-purple-400/90 uppercase tracking-wider">{{ $conversation->instance_name }}</span>
            </div>
            @endif
            @php
                $statusColor = 'bg-gray-500/10 text-gray-400 border-gray-500/20'; // Default Default
                if($conversation->kanban_status === 'novo') $statusColor = 'bg-purple-500/10 text-purple-400 border-purple-500/20';
                if($conversation->kanban_status === 'em_atendimento') $statusColor = 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20';
                if($conversation->kanban_status === 'aguardando') $statusColor = 'bg-yellow-500/10 text-yellow-500 border-yellow-500/20';
                if($conversation->kanban_status === 'fechado' || $conversation->kanban_status === 'finalizado') $statusColor = 'bg-gray-500/10 text-gray-400 border-gray-500/20';
            @endphp
            <a href="{{ route('chat.show', $conversation->id) }}" class="flex items-center justify-between p-5 hover:bg-[#1A1A24] transition-colors group">
                <div class="flex items-center gap-4 truncate">
                    <!-- Avatar -->
                    <div class="w-12 h-12 rounded-full bg-[#2A2A35] flex items-center justify-center shrink-0">
                        <i class="fas fa-user text-gray-400 text-lg"></i>
                    </div>
                    
                    <!-- Textos -->
                    <div class="truncate">
                        <div class="flex items-center gap-2">
                            <h3 class="font-bold text-white text-base group-hover:text-purple-400 transition-colors">{{ $conversation->contact_name ?? $conversation->contact }}</h3>
                        </div>
                        <p class="text-sm text-gray-500 truncate mt-0.5">
                            @if($conversation->latestMessage)
                                {{ \Illuminate\Support\Str::limit($conversation->latestMessage->message, 80) }}
                            @else
                                <span class="italic text-gray-600">Nenhuma mensagem</span>
                            @endif
                        </p>
                    </div>
                </div>

                <!-- Lado Direito -->
                <div class="flex flex-col items-end gap-2 shrink-0 ml-4">
                    <span class="text-xs text-gray-500 font-medium">
                        {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans(null, true, true) : '' }}
                    </span>
                    
                    <div class="flex items-center gap-3 mt-1">
                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $statusColor }}">
                            {{ str_replace('_', ' ', $conversation->kanban_status) }}
                        </span>
                        
                        @if($conversation->kanban_status === 'novo')
                        <div class="w-5 h-5 rounded-full bg-pink-600 flex items-center justify-center text-[10px] text-white font-bold">
                            !
                        </div>
                        @endif
                    </div>

                    <button
                        type="button"
                        title="Apagar conversa"
                        class="shrink-0 p-2 rounded-lg bg-red-500/10 hover:bg-red-500/20 border border-red-500/20 text-red-400 hover:text-red-200 transition inline-flex items-center justify-center"
                        onclick="event.preventDefault(); event.stopPropagation(); deleteConversation({{ $conversation->id }});"
                    >
                        <i class="fas fa-trash-alt text-sm" aria-hidden="true"></i>
                    </button>
                </div>
            </a>
            @empty
            <div class="p-12 text-center flex flex-col items-center justify-center h-full">
                <div class="w-20 h-20 rounded-full bg-[#2A2A35] flex items-center justify-center mb-4 text-gray-500">
                    <i class="fas fa-inbox text-3xl"></i>
                </div>
                <h3 class="text-lg font-bold text-white">Inbox Vazia</h3>
                <p class="text-sm text-gray-500 mt-1">Nenhuma conversa encontrada no momento.</p>
            </div>
            @endforelse
        </div>

        @if($conversations->hasPages())
        <div class="px-6 py-4 border-t border-[#2A2A35] bg-[#16161D]">
            {{ $conversations->links() }}
        </div>
        @endif
    </div>
</div>
<script>
async function deleteConversation(id) {
    if (!confirm('Deseja apagar esta conversa permanentemente?')) return;
    try {
        const response = await axios.delete(`/api/conversations/${id}`);
        if (response.data && response.data.success) {
            location.reload();
        } else {
            alert(response.data?.message || 'Falha ao apagar conversa.');
        }
    } catch (error) {
        console.error('Erro ao apagar conversa:', error);
        alert('Falha na comunicação com o servidor.');
    }
}
</script>
@endsection
