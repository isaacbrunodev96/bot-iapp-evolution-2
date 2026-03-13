<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Nexxivo - Painel de Controle')</title>
    @if(file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
        <script>
            tailwind.config = {
                darkMode: 'class',
                theme: {
                    extend: {
                        colors: {
                            nexxivo: {
                                bg: '#0F0F13',
                                surface: '#16161D',
                                border: '#2A2A35',
                                primary: '#9333EA', // purple-600
                                secondary: '#EC4899', // pink-500
                                hover: '#1F1F2A'
                            }
                        }
                    }
                }
            }
        </script>
    @endif
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0F0F13; /* nexxivo bg */
            color: #E2E8F0; /* slate-200 */
        }

        /* Gradient for active menu */
        .bg-neon-gradient {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
        }

        /* Neon Text */
        .text-neon-gradient {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0F0F13;
        }
        ::-webkit-scrollbar-thumb {
            background: #2A2A35;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #3F3F4E;
        }
        
        .glass-panel {
            background: rgba(22, 22, 29, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid #2A2A35;
        }
    </style>
</head>
<body class="bg-nexxivo-bg text-slate-200 antialiased h-screen flex overflow-hidden" x-data="{ sidebarCollapsed: false }">

    <!-- Sidebar -->
    <aside 
        :class="sidebarCollapsed ? 'w-20' : 'w-64'"
        class="bg-nexxivo-bg border-r border-white/[0.01] flex flex-col justify-between hidden md:flex h-full z-20 transition-all duration-300 ease-in-out shrink-0 relative">
        
        <!-- Logo Area -->
        <div class="h-20 flex items-center transition-all duration-300" :class="sidebarCollapsed ? 'justify-center px-0' : 'px-6'">
            <a href="/chat" class="flex items-center gap-3 overflow-hidden">
                <div class="w-8 h-8 rounded-lg bg-neon-gradient flex items-center justify-center text-white font-bold text-lg shrink-0">
                    <i class="fas fa-bolt"></i>
                </div>
                <span class="text-xl font-bold tracking-tight text-white transition-opacity duration-300" x-show="!sidebarCollapsed">Nexxivo</span>
            </a>
        </div>

        <!-- Toggle Button (Edge) -->
        <button @click="sidebarCollapsed = !sidebarCollapsed" 
            class="absolute -right-3 top-7 w-6 h-6 bg-[#0B0B0F] border border-[#2A2A35] rounded-full flex items-center justify-center text-gray-500 hover:text-white hover:border-fuchsia-500/50 shadow-2xl z-50 transition-all group">
            <i class="fas fa-chevron-left text-[8px] transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''"></i>
        </button>

        <!-- Menu Navigation -->
        <div class="flex-1 overflow-y-auto py-2 space-y-1 custom-scrollbar" :class="sidebarCollapsed ? 'px-2' : 'px-4'">
            <div class="text-[10px] font-bold text-gray-700 uppercase tracking-widest mb-4 mt-4 px-3 overflow-hidden whitespace-nowrap" x-show="!sidebarCollapsed">Menu Principal</div>
            
            <a href="/" class="flex items-center gap-3 py-2.5 rounded-lg transition-all group {{ request()->is('dashboard') || request()->is('/') ? 'bg-neon-gradient text-white shadow-lg shadow-purple-500/20 font-medium' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5' }}" :class="sidebarCollapsed ? 'px-0 justify-center' : 'px-3'">
                <i class="fas fa-th-large w-5 text-center shrink-0 group-hover:scale-110 transition-transform"></i>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium tracking-wide whitespace-nowrap overflow-hidden transition-opacity duration-300">Dashboard</span>
            </a>

            <a href="{{ route('instances.index') }}" class="flex items-center gap-3 py-2.5 rounded-lg transition-all group {{ request()->is('instances*') ? 'bg-neon-gradient text-white shadow-lg shadow-purple-500/20 font-medium' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5' }}" :class="sidebarCollapsed ? 'px-0 justify-center' : 'px-3'">
                <i class="fas fa-comment-dots w-5 text-center shrink-0 group-hover:scale-110 transition-transform"></i>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium tracking-wide whitespace-nowrap overflow-hidden transition-opacity duration-300">Canais</span>
            </a>

            <a href="/chat" class="flex items-center gap-3 py-2.5 rounded-lg transition-all group {{ request()->is('chat*') ? 'bg-neon-gradient text-white shadow-lg shadow-purple-500/20 font-medium' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5' }}" :class="sidebarCollapsed ? 'px-0 justify-center' : 'px-3'">
                <i class="fas fa-inbox w-5 text-center shrink-0 group-hover:scale-110 transition-transform"></i>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium tracking-wide whitespace-nowrap overflow-hidden transition-opacity duration-300">Inbox</span>
            </a>

            <a href="{{ route('crm.index') }}" class="flex items-center gap-3 py-2.5 rounded-lg transition-all group {{ request()->is('crm*') ? 'bg-neon-gradient text-white shadow-lg shadow-purple-500/20 font-medium' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5' }}" :class="sidebarCollapsed ? 'px-0 justify-center' : 'px-3'">
                <i class="fas fa-columns w-5 text-center shrink-0 group-hover:scale-110 transition-transform"></i>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium tracking-wide whitespace-nowrap overflow-hidden transition-opacity duration-300">CRM Kanban</span>
            </a>

            <a href="/flows" class="flex items-center gap-3 py-2.5 rounded-lg transition-all group {{ request()->is('flows*') ? 'bg-neon-gradient text-white shadow-lg shadow-purple-500/20 font-medium' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5' }}" :class="sidebarCollapsed ? 'px-0 justify-center' : 'px-3'">
                <i class="fas fa-robot w-5 text-center shrink-0 group-hover:scale-110 transition-transform"></i>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium tracking-wide whitespace-nowrap overflow-hidden transition-opacity duration-300">Automação</span>
            </a>

            @if(Auth::check() && (Auth::user()->is_admin ?? true))
            <a href="/ai-settings" class="flex items-center gap-3 py-2.5 rounded-lg transition-all group {{ request()->is('ai-settings*') ? 'bg-neon-gradient text-white shadow-lg shadow-purple-500/20 font-medium' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5' }}" :class="sidebarCollapsed ? 'px-0 justify-center' : 'px-3'">
                <i class="fas fa-microphone-alt w-5 text-center shrink-0 group-hover:scale-110 transition-transform"></i>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium tracking-wide whitespace-nowrap overflow-hidden transition-opacity duration-300">Voz & IA</span>
            </a>
            @endif

            <a href="/settings" class="flex items-center gap-3 py-2.5 rounded-lg transition-all group {{ request()->is('settings*') ? 'bg-neon-gradient text-white shadow-lg shadow-purple-500/20 font-medium' : 'text-gray-400 hover:text-gray-100 hover:bg-white/5' }}" :class="sidebarCollapsed ? 'px-0 justify-center' : 'px-3'">
                <i class="fas fa-cog w-5 text-center shrink-0 group-hover:scale-110 transition-transform"></i>
                <span x-show="!sidebarCollapsed" class="text-sm font-medium tracking-wide whitespace-nowrap overflow-hidden transition-opacity duration-300">Configurações</span>
            </a>
        </div>

        <!-- User Profile Slot -->
        @auth
        <div class="p-4 flex items-center justify-between" :class="sidebarCollapsed ? 'flex-col gap-4 mb-4 py-4' : 'h-24'">
            <div class="flex items-center gap-3 truncate" :class="sidebarCollapsed ? 'justify-center w-full' : ''">
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-pink-600 to-purple-600 flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-lg shadow-pink-500/20 border border-white/5">
                    {{ substr(Auth::user()->name, 0, 2) }}
                </div>
                <div class="truncate" x-show="!sidebarCollapsed">
                    <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-gray-500 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>
            
            <form method="POST" action="{{ route('logout') }}" class="shrink-0" :class="sidebarCollapsed ? 'mt-auto' : 'ml-2'">
                @csrf
                <button type="submit" class="text-gray-500 hover:text-pink-500 transition-all p-2 rounded-lg hover:bg-white/5" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                </button>
            </form>
        </div>
        @endauth
    </aside>

    <!-- Main Content wrapper -->
    <div 
        class="flex-1 flex flex-col h-screen transition-all duration-300 ease-in-out min-w-0 overflow-hidden">
        <!-- Optional Topbar for Mobile -->
        <header class="h-16 border-b border-nexxivo-border bg-nexxivo-bg flex items-center justify-between px-4 md:hidden shrink-0 z-10">
            <a href="/chat" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-neon-gradient flex items-center justify-center text-white font-bold text-sm">
                    <i class="fas fa-bolt"></i>
                </div>
                <span class="text-lg font-bold text-white">Nexxivo</span>
            </a>
            <button class="text-gray-300 focus:outline-none">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <!-- Dynamic Content -->
        <main class="flex-1 overflow-y-auto bg-nexxivo-bg min-w-0 relative">
            <div class="h-full min-w-0">
                @yield('content')
            </div>
        </main>
    </div>

</body>
</html>
