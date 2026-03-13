@extends('layouts.app')

@section('title', 'CRM Kanban - Nexxivo')

@section('content')
<div class="p-6 md:p-10 flex flex-col h-full space-y-6 bg-[#0B0B0F] w-full min-w-0">
    
    <!-- Cabeçalho Superior -->
    <div class="flex flex-col md:flex-row items-center justify-between gap-6 shrink-0">
        <div class="flex flex-col gap-1">
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-white tracking-tight">CRM Kanban</h1>
                
                <!-- Funnel Selector -->
                @if(count($funnels) > 1)
                <div class="relative group">
                    <button class="bg-[#16161D] border border-[#2A2A35] hover:border-fuchsia-500/50 text-gray-400 px-4 py-1.5 rounded-xl text-xs font-bold flex items-center gap-2 transition-all">
                        {{ $currentFunnel->name }} 
                        <i class="fas fa-chevron-down text-[8px] text-gray-600 group-hover:text-fuchsia-400"></i>
                    </button>
                    <!-- Dropdown Content -->
                    <div class="absolute left-0 top-full mt-2 w-56 bg-[#16161D] border border-[#2A2A35] rounded-2xl shadow-2xl shadow-black/80 hidden group-hover:block transition-all z-50 overflow-hidden backdrop-blur-md">
                        @foreach($funnels as $f)
                        <a href="?funnel_id={{ $f->id }}" class="flex items-center justify-between px-4 py-3 text-[11px] font-black uppercase tracking-widest {{ $f->id === $currentFunnel->id ? 'text-fuchsia-400 bg-fuchsia-500/5' : 'text-gray-500 hover:bg-white/5 hover:text-white' }} transition">
                            {{ $f->name }}
                            @if($f->id === $currentFunnel->id)
                                <i class="fas fa-check text-[10px]"></i>
                            @endif
                        </a>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="px-3 py-1 bg-[#16161D] border border-[#2A2A35] rounded-lg">
                    <span class="text-[10px] font-black text-fuchsia-500 uppercase tracking-[0.2em]">{{ $currentFunnel->name }}</span>
                </div>
                @endif
            </div>
            <p class="text-gray-600 text-[11px] font-bold uppercase tracking-wider">Gerencie as etapas de negociação do seu pipeline.</p>
        </div>
        
        <div class="flex items-center gap-4">
            <!-- Barra de Ferramentas: Filtros e Busca -->
            <div class="flex items-center bg-[#16161D] border border-[#2A2A35] rounded-xl p-1 shadow-inner">
                <button class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-400 hover:text-white hover:bg-white/5 rounded-lg transition">
                    <i class="fas fa-filter text-xs text-fuchsia-500/70"></i> Filtrar
                </button>
                <div class="w-px h-4 bg-[#2A2A35] mx-1"></div>
                <div class="flex items-center px-3 gap-2 group focus-within:ring-1 focus-within:ring-fuchsia-500/50 rounded-lg transition-all">
                    <i class="fas fa-search text-xs text-gray-600 group-focus-within:text-fuchsia-500 transition"></i>
                    <input type="text" id="kanban-search" placeholder="Buscar por nome ou telefone..." class="bg-transparent border-none text-sm text-white focus:ring-0 w-32 md:w-64 placeholder-gray-600">
                </div>
            </div>

            <!-- Botão Notificação -->
            <button class="relative w-10 h-10 flex items-center justify-center bg-[#16161D] border border-[#2A2A35] text-gray-400 hover:text-white rounded-xl transition hover:border-fuchsia-500/30 group">
                <i class="far fa-bell"></i>
                <span class="absolute top-2.5 right-2.5 w-2 h-2 bg-fuchsia-500 rounded-full border-2 border-[#16161D] group-hover:scale-110 transition"></span>
            </button>

            <!-- Botão Nova Coluna -->
            <button onclick="document.getElementById('newStageModal').classList.remove('hidden')" class="bg-gradient-to-r from-fuchsia-600 to-pink-600 hover:from-fuchsia-500 hover:to-pink-500 text-white px-6 py-2.5 rounded-xl font-bold shadow-lg shadow-fuchsia-600/20 transition-all transform hover:-translate-y-0.5 active:scale-95 flex items-center justify-center gap-2.5">
                <i class="fas fa-plus text-xs mb-[1px]"></i>
                <span class="leading-none">Nova Coluna</span>
            </button>
        </div>
    </div>

    <!-- Kanban Board -->
    <div class="flex-1 overflow-x-auto overflow-y-hidden custom-scrollbar pb-6 min-h-0">
        <div id="kanban-board" class="flex gap-8 h-full items-start px-2" style="width: max-content;">
            
            @foreach($stages as $stage)
            <!-- Coluna -->
            <div class="kanban-column w-[340px] flex flex-col h-full shrink-0 group/col" data-id="{{ $stage->id }}">
                
                <!-- Header da Coluna -->
                <div class="flex items-center justify-between mb-5 px-1 column-drag-handle cursor-grab active:cursor-grabbing">
                    <div class="flex items-center gap-3">
                        <div class="w-2.5 h-2.5 rounded-full shadow-[0_0_8px_currentColor]" style="color: {{ $stage->color }}; background-color: {{ $stage->color }}"></div>
                        <h2 class="text-sm font-black text-gray-200 uppercase tracking-[0.15em] flex items-center gap-2">
                            {{ $stage->name }}
                            <span class="bg-white/5 border border-white/5 text-gray-500 text-[10px] px-2 py-0.5 rounded-full font-bold">
                                {{ $stage->conversations->count() }}
                            </span>
                        </h2>
                    </div>
                    
                    <div class="relative opacity-0 group-hover/col:opacity-100 transition-opacity">
                        <button onclick="toggleDropdown('dropdown-{{ $stage->id }}')" class="w-8 h-8 flex items-center justify-center text-gray-600 hover:text-white hover:bg-white/5 rounded-lg transition">
                            <i class="fas fa-ellipsis-v text-xs"></i>
                        </button>
                        <div id="dropdown-{{ $stage->id }}" class="hidden absolute right-0 top-full mt-1 w-40 bg-[#1A1A24] border border-[#2A2A35] rounded-xl shadow-2xl z-50 overflow-hidden">
                            <form action="{{ route('crm.stages.destroy', $stage->id) }}" method="POST" onsubmit="return confirm('Excluir esta coluna?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full text-left px-4 py-3 text-xs font-bold text-red-500 hover:bg-red-500/10 transition">
                                    <i class="fas fa-trash-alt mr-2"></i> Excluir Etapa
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Cards Container -->
                <div class="flex-1 space-y-4 overflow-y-auto custom-scrollbar pr-2 kanban-cards-container min-h-[100px]" data-stage-id="{{ $stage->id }}">
                    
                    @foreach($stage->conversations as $conversation)
                    <!-- Ticket Card -->
                    <div class="kanban-card bg-[#16161D] border border-[#2A2A35] hover:border-fuchsia-500/30 rounded-[22px] p-5 cursor-grab active:cursor-grabbing transition-all duration-300 group/card shadow-lg shadow-black/5 hover:shadow-fuchsia-500/5 relative overflow-hidden" data-id="{{ $conversation->id }}">
                        
                        <!-- Glow de hover sutil -->
                        <div class="absolute inset-0 bg-gradient-to-br from-fuchsia-500/[0.02] to-transparent opacity-0 group-hover/card:opacity-100 transition-opacity pointer-events-none"></div>

                        <div class="flex items-start gap-4 mb-5">
                            <!-- Avatar com iniciais -->
                            <div class="w-12 h-12 rounded-2xl bg-[#0B0B0F] border border-[#2A2A35] flex items-center justify-center text-gray-400 shrink-0 shadow-inner group-hover/card:border-fuchsia-500/20 transition-colors">
                                <i class="far fa-user text-xl"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2 mb-0.5">
                                    <h3 class="font-bold text-gray-100 text-[15px] truncate group-hover/card:text-white transition-colors">
                                        {{ $conversation->contact_name ?? $conversation->contact }}
                                    </h3>
                                    <!-- Bolinha de Status Online (Mock) -->
                                    <div class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.5)] shrink-0"></div>
                                </div>
                                <p class="text-xs text-gray-500 font-medium truncate mb-2">{{ $conversation->contact }}</p>
                                
                                <!-- Tags / Labels -->
                                @if($conversation->label)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[9px] font-black tracking-widest uppercase bg-fuchsia-500/10 text-fuchsia-500 border border-fuchsia-500/20">
                                    {{ $conversation->label }}
                                </span>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Footer do Card -->
                        <div class="flex items-center justify-between pt-3 border-t border-[#2A2A35]/50">
                            <div class="flex items-center gap-1.5 text-gray-600 group-hover/card:text-gray-400 transition-colors">
                                <i class="far fa-clock text-[10px]"></i>
                                <span class="text-[10px] font-bold uppercase tracking-tighter">
                                    {{ $conversation->last_message_at ? $conversation->last_message_at->diffForHumans(null, true, true) : 'Ativo' }}
                                </span>
                            </div>
                            
                            <div class="flex items-center gap-2">
                                <a href="{{ route('chat.show', $conversation->id) }}" class="flex items-center gap-2 px-4 py-1.5 rounded-xl bg-[#2A2A35]/30 hover:bg-fuchsia-600 text-gray-300 hover:text-white text-[11px] font-black uppercase tracking-wider transition-all border border-transparent hover:border-fuchsia-400/30">
                                    <i class="far fa-comment-dots text-xs"></i> Chat
                                </a>
                                <button class="w-8 h-8 flex items-center justify-center bg-[#2A2A35]/30 hover:bg-white/5 rounded-xl text-gray-500 hover:text-white transition border border-transparent hover:border-[#2A2A35]">
                                    <i class="fas fa-phone-alt text-[10px]"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <!-- Adicionar Lead Button -->
                    <button onclick="openAddLeadModal('{{ $stage->id }}')" class="w-full py-4 rounded-[22px] border-2 border-dashed border-[#1A1A24] text-gray-600 hover:text-fuchsia-500 hover:bg-fuchsia-500/5 hover:border-fuchsia-500/20 transition-all flex items-center justify-center gap-2.5 font-bold text-xs uppercase tracking-widest">
                        <i class="fas fa-plus-circle mb-[1px]"></i>
                        <span class="leading-none">Adicionar lead</span>
                    </button>
                    
                </div>
            </div>
            @endforeach

            <!-- Nova Coluna Placeholder -->
            <div class="w-[340px] shrink-0 h-full">
                <button onclick="document.getElementById('newStageModal').classList.remove('hidden')" class="w-full h-36 border-2 border-dashed border-[#1A1A24] hover:border-fuchsia-500/30 bg-[#16161D]/30 hover:bg-[#1A1A24]/50 rounded-[25px] flex flex-col items-center justify-center transition-all group shrink-0 py-6">
                    <div class="w-12 h-12 rounded-full bg-[#1A1A24] group-hover:bg-fuchsia-600 flex items-center justify-center text-gray-600 group-hover:text-white transition-all mb-3 shadow-inner">
                        <i class="fas fa-plus text-base"></i>
                    </div>
                    <span class="text-[11px] font-black text-gray-500 uppercase tracking-widest group-hover:text-fuchsia-400 leading-none">Nova Coluna</span>
                </button>
            </div>

        </div>
    </div>
</div>

<!-- Modals -->

<!-- Modal: Novo Lead -->
<div id="addLeadModal" class="fixed inset-0 bg-black/90 backdrop-blur-md hidden z-50 flex items-center justify-center p-4">
    <div class="bg-[#16161D] border border-[#2A2A35] rounded-3xl p-10 max-w-md w-full shadow-2xl shadow-black relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-emerald-600 to-teal-600"></div>
        
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-black text-white uppercase tracking-tighter">Adicionar Novo Lead</h2>
            <button onclick="document.getElementById('addLeadModal').classList.add('hidden')" class="text-gray-500 hover:text-white transition w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/5">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <form action="{{ route('crm.leads.store') }}" method="POST" class="space-y-6">
            @csrf
            <input type="hidden" name="funnel_stage_id" id="modal_stage_id">
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3">Nome do Contato</label>
                <input type="text" name="contact_name" required placeholder="Ex: João da Silva" class="w-full bg-[#0B0B0F] border border-[#2A2A35] rounded-2xl text-white px-5 py-4 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/50 outline-none transition-all placeholder-gray-700">
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3">WhatsApp / Telefone</label>
                <input type="text" name="contact" required placeholder="Ex: 5511999999999" class="w-full bg-[#0B0B0F] border border-[#2A2A35] rounded-2xl text-white px-5 py-4 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/50 outline-none transition-all placeholder-gray-700">
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white py-5 rounded-2xl font-black uppercase tracking-[0.2em] shadow-xl shadow-emerald-600/20 transition-all hover:-translate-y-1 active:scale-95">
                Salvar Lead
            </button>
        </form>
    </div>
</div>

<!-- Modal: Nova Coluna -->
<div id="newStageModal" class="fixed inset-0 bg-black/90 backdrop-blur-md hidden z-50 flex items-center justify-center p-4">
    <div class="bg-[#16161D] border border-[#2A2A35] rounded-3xl p-10 max-w-md w-full shadow-2xl shadow-black relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-fuchsia-600 to-pink-600"></div>
        
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-2xl font-black text-white uppercase tracking-tighter">Criar Nova Etapa</h2>
            <button onclick="document.getElementById('newStageModal').classList.add('hidden')" class="text-gray-500 hover:text-white transition w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/5">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <form action="{{ route('crm.stages.store', $currentFunnel->id) }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3">Nome da Etapa</label>
                <input type="text" name="name" required placeholder="Ex: Negociação Estratégica" class="w-full bg-[#0B0B0F] border border-[#2A2A35] rounded-2xl text-white px-5 py-4 focus:border-fuchsia-500 focus:ring-1 focus:ring-fuchsia-500/50 outline-none transition-all placeholder-gray-700">
            </div>
            
            <div>
                <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest mb-3">Cor de Destaque</label>
                <div class="flex items-center gap-6 p-4 bg-[#0B0B0F] rounded-2xl border border-[#2A2A35]">
                    <div class="relative group cursor-pointer">
                        <input type="color" name="color" id="stageColorPicker" value="#A855F7" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">
                        <div id="colorPreview" class="w-16 h-16 rounded-2xl border-2 border-white/20 shadow-2xl transition-all scale-100 group-hover:scale-105 active:scale-95" style="background-color: #A855F7"></div>
                        <div class="absolute -bottom-1 -right-1 bg-white text-black w-6 h-6 rounded-lg flex items-center justify-center shadow-lg border-2 border-[#0B0B0F]">
                            <i class="fas fa-eye-dropper text-[10px]"></i>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span id="colorHex" class="text-sm font-black text-white uppercase tracking-tighter">#A855F7</span>
                        <span class="text-[10px] text-gray-500 font-bold uppercase tracking-widest">Clique para alterar</span>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-gradient-to-r from-fuchsia-600 to-pink-600 hover:from-fuchsia-500 hover:to-pink-500 text-white py-5 rounded-2xl font-black uppercase tracking-[0.2em] shadow-xl shadow-fuchsia-600/20 transition-all hover:-translate-y-1 active:scale-95">
                Salvar Nova Etapa
            </button>
        </form>
    </div>
</div>

<style>
/* Custom Layout Adjustments */
body { background-color: #0B0B0F !important; }

.custom-scrollbar::-webkit-scrollbar { height: 12px; width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.2); border-radius: 20px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 20px; border: 3px solid #0B0B0F; transition: background 0.3s; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #d946ef; } /* fuchsia-500 */

/* SortableJS Styles */
.sortable-ghost-card { opacity: 0.2; transform: scale(0.95); grayscale: 1; }
.sortable-drag-card { cursor: grabbing !important; transform: rotate(1.5deg) scale(1.02); box-shadow: 0 30px 60px -12px rgba(0,0,0,0.8); z-index: 100 !important; }

.sortable-ghost-col { opacity: 0.1; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Column Sorting
    const board = document.getElementById('kanban-board');
    if (board) {
        new Sortable(board, {
            animation: 300,
            handle: '.column-drag-handle',
            ghostClass: 'sortable-ghost-col',
            onEnd: function () {
                const columns = [...board.querySelectorAll('.kanban-column')];
                const newOrder = columns.map((col, index) => ({ id: col.dataset.id, order: index + 1 }));
                axios.post('{{ route('crm.stages.reorder') }}', { stages: newOrder });
            }
        });
    }

    // 2. Card Sorting
    document.querySelectorAll('.kanban-cards-container').forEach(container => {
        new Sortable(container, {
            group: 'shared',
            animation: 200,
            ghostClass: 'sortable-ghost-card',
            dragClass: 'sortable-drag-card',
            onEnd: function (evt) {
                if (evt.from !== evt.to) {
                    const id = evt.item.dataset.id;
                    const stageId = evt.to.dataset.stageId;
                    axios.post(`/crm/conversations/${id}/move`, { funnel_stage_id: stageId })
                        .then(() => updateCounters());
                }
            }
        });
    });

    // 3. Busca em Tempo Real
    const searchInput = document.getElementById('kanban-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            document.querySelectorAll('.kanban-card').forEach(card => {
                const name = card.querySelector('h3').innerText.toLowerCase();
                const phone = card.querySelector('p').innerText.toLowerCase();
                if (name.includes(term) || phone.includes(term)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            updateCounters();
        });
    }

    // 4. Color Picker Preview
    const colorPicker = document.getElementById('stageColorPicker');
    const colorPreview = document.getElementById('colorPreview');
    const colorHex = document.getElementById('colorHex');
    
    if (colorPicker) {
        colorPicker.addEventListener('input', function(e) {
            const color = e.target.value;
            colorPreview.style.backgroundColor = color;
            colorHex.innerText = color.toUpperCase();
        });
    }

    window.openAddLeadModal = function(stageId) {
        document.getElementById('modal_stage_id').value = stageId;
        document.getElementById('addLeadModal').classList.remove('hidden');
    }

    window.toggleDropdown = function(id) {
        document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.id !== id && el.classList.add('hidden'));
        document.getElementById(id).classList.toggle('hidden');
    }

    document.addEventListener('click', e => {
        if (!e.target.closest('.relative')) document.querySelectorAll('[id^="dropdown-"]').forEach(el => el.classList.add('hidden'));
    });

    function updateCounters() {
        document.querySelectorAll('.kanban-column').forEach(col => {
            const count = col.querySelectorAll('.kanban-card').length;
            const badge = col.querySelector('h2 span');
            if (badge) badge.innerText = count;
        });
    }

    // 5. Scroll Horizontal Inteligente (Mouse Wheel + Grab to Scroll)
    const scrollContainer = document.querySelector('.overflow-x-auto.custom-scrollbar');
    if (scrollContainer) {
        // Wheel Scroll
        window.addEventListener('wheel', (evt) => {
            const isOverCards = evt.target.closest('.kanban-cards-container');
            
            // Se o mouse estiver sobre a área de cards, permite o scroll vertical nativo das colunas
            if (isOverCards) return;

            if (evt.target.closest('#kanban-board') || evt.target.closest('.overflow-x-auto')) {
                if (evt.deltaX !== 0) return;
                scrollContainer.scrollLeft += evt.deltaY * 2; // Maior sensibilidade
                evt.preventDefault();
            }
        }, { passive: false });

        // Grab to Scroll
        let isDown = false;
        let startX;
        let scrollLeft;

        scrollContainer.addEventListener('mousedown', (e) => {
            if (e.target.closest('.kanban-card') || e.target.closest('button')) return; // Não atrapalhar cards
            isDown = true;
            scrollContainer.classList.add('cursor-grabbing');
            startX = e.pageX - scrollContainer.offsetLeft;
            scrollLeft = scrollContainer.scrollLeft;
        });
        scrollContainer.addEventListener('mouseleave', () => {
            isDown = false;
            scrollContainer.classList.remove('cursor-grabbing');
        });
        scrollContainer.addEventListener('mouseup', () => {
            isDown = false;
            scrollContainer.classList.remove('cursor-grabbing');
        });
        scrollContainer.addEventListener('mousemove', (e) => {
            if(!isDown) return;
            e.preventDefault();
            const x = e.pageX - scrollContainer.offsetLeft;
            const walk = (x - startX) * 2; // Velocidade do arraste
            scrollContainer.scrollLeft = scrollLeft - walk;
        });
    }
});
</script>
@endsection
