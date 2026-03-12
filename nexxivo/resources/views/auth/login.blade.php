<!DOCTYPE html>
<html lang="pt-BR" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexxivo - Entrar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0B0B0F;
        }
        .bg-neon-gradient {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
        }
        .text-neon-gradient {
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .glow-fuchsia {
            box-shadow: 0 0 20px rgba(236, 72, 153, 0.2);
        }
        .input-dark {
            background-color: #16161D;
            border: 1px solid #2A2A35;
            color: #E2E8F0;
            transition: all 0.3s ease;
        }
        .input-dark:focus {
            border-color: #ec4899;
            box-shadow: 0 0 0 2px rgba(236, 72, 153, 0.1);
            outline: none;
        }
    </style>
</head>
<body class="bg-[#0B0B0F] min-h-screen flex items-center justify-center p-0 md:p-4 overflow-x-hidden">

    <div class="w-full max-w-6xl min-h-[600px] flex flex-col md:flex-row bg-[#0B0B0F] md:rounded-3xl overflow-hidden md:border md:border-white/[0.05] shadow-2xl">
        
        <!-- Lado Esquerdo: Info/Branding -->
        <div class="w-full md:w-1/2 p-8 md:p-16 flex flex-col justify-between relative overflow-hidden bg-[#0B0B0F]">
            <!-- Background Glow Decorativo (Aurora) -->
            <div class="absolute -top-40 -left-40 w-[600px] h-[600px] bg-purple-600/20 rounded-full blur-[120px] animate-pulse"></div>
            <div class="absolute top-1/2 -right-20 w-80 h-80 bg-fuchsia-600/10 rounded-full blur-[100px]"></div>
            
            <div class="relative z-10">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-10">
                    <div class="w-10 h-10 rounded-xl bg-neon-gradient flex items-center justify-center text-white shadow-lg shadow-fuchsia-500/20">
                        <i class="fas fa-bolt text-xl"></i>
                    </div>
                    <span class="text-2xl font-extrabold text-white tracking-tight">Nexxivo</span>
                </div>

                <h2 class="text-gray-400 text-lg md:text-xl font-medium leading-relaxed mb-12 max-w-sm">
                    Plataforma inteligente de atendimento multicanal com <span class="text-white font-bold">IA integrada</span>.
                </h2>

                <!-- Features List -->
                <div class="space-y-8">
                    <div class="flex items-start gap-4 group">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 group-hover:text-fuchsia-400 group-hover:border-fuchsia-400/30 transition-all">
                            <i class="fas fa-comments text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm mb-1 tracking-tight">Multicanal Unificado</h4>
                            <p class="text-gray-500 text-xs leading-relaxed">WhatsApp, Instagram, Telegram e mais em um só lugar.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 group">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 group-hover:text-fuchsia-400 group-hover:border-fuchsia-400/30 transition-all">
                            <i class="fas fa-robot text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm mb-1 tracking-tight">IA Conversacional</h4>
                            <p class="text-gray-500 text-xs leading-relaxed">Respostas automáticas com Llama, Gemini e modelos avançados.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4 group">
                        <div class="w-10 h-10 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-gray-400 group-hover:text-fuchsia-400 group-hover:border-fuchsia-400/30 transition-all">
                            <i class="fas fa-magic text-lg"></i>
                        </div>
                        <div>
                            <h4 class="text-white font-bold text-sm mb-1 tracking-tight">Automação Inteligente</h4>
                            <p class="text-gray-500 text-xs leading-relaxed">Fluxos automatizados para escalar seu atendimento.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Proof / Footer Esquerdo -->
            <div class="mt-16 md:mt-24 relative z-10">
                <div class="flex items-center gap-2 mb-2">
                    <div class="flex -space-x-3">
                        <div class="w-8 h-8 rounded-full border-2 border-[#0B0B0F] bg-purple-600 flex items-center justify-center text-[10px] font-bold text-white">A</div>
                        <div class="w-8 h-8 rounded-full border-2 border-[#0B0B0F] bg-pink-600 flex items-center justify-center text-[10px] font-bold text-white">B</div>
                        <div class="w-8 h-8 rounded-full border-2 border-[#0B0B0F] bg-blue-600 flex items-center justify-center text-[10px] font-bold text-white">C</div>
                        <div class="w-8 h-8 rounded-full border-2 border-[#0B0B0F] bg-emerald-600 flex items-center justify-center text-[10px] font-bold text-white">D</div>
                    </div>
                    <span class="text-xs text-gray-400 ml-2"><span class="text-white font-bold">+2.500</span> empresas já usam</span>
                </div>
            </div>
        </div>

        <!-- Lado Direito: Formulário -->
        <div class="w-full md:w-1/2 p-8 md:p-20 bg-[#0B0B0F] flex flex-col justify-center border-t border-white/[0.05] md:border-t-0 md:border-l">
            
            <div class="max-w-md mx-auto w-full">
                <div class="mb-10 text-center md:text-left">
                    <h1 class="text-3xl font-black text-white mb-2 tracking-tight">Bem-vindo de volta</h1>
                    <p class="text-gray-500 text-sm">Entre na sua conta para continuar</p>
                </div>

                @if($errors->any())
                <div class="mb-6 animate-pulse">
                    @foreach($errors->all() as $error)
                        <div class="flex items-center gap-3 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-400 text-sm mb-2">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>{{ $error }}</span>
                        </div>
                    @endforeach
                </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-400 mb-2 uppercase tracking-wide">E-mail</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="{{ old('email') }}" 
                            required 
                            autofocus
                            class="w-full px-5 py-4 input-dark rounded-xl text-sm placeholder:text-gray-700"
                            placeholder="seu@email.com"
                        >
                    </div>

                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-sm font-bold text-gray-400 uppercase tracking-wide">Senha</label>
                            <a href="#" class="text-[11px] font-bold text-fuchsia-400 hover:text-fuchsia-300 transition-colors">Esqueceu a senha?</a>
                        </div>
                        <div class="relative group">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                required
                                class="w-full px-5 py-4 input-dark rounded-xl text-sm placeholder:text-gray-700 pr-12"
                                placeholder="••••••••"
                            >
                            <button type="button" id="togglePassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-600 hover:text-gray-400">
                                <i class="far fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            id="remember" 
                            name="remember" 
                            class="w-4 h-4 rounded border-[#2A2A35] bg-[#16161D] text-fuchsia-600 focus:ring-offset-[#0B0B0F] focus:ring-fuchsia-500"
                        >
                        <label for="remember" class="text-xs text-gray-500 font-medium cursor-pointer">Manter conectado por 30 dias</label>
                    </div>

                    <button 
                        type="submit" 
                        class="w-full py-4 bg-neon-gradient text-white rounded-xl font-black text-sm uppercase tracking-widest shadow-lg shadow-fuchsia-500/20 hover:opacity-90 transition-all hover:scale-[1.01]"
                    >
                        Entrar
                    </button>
                    
                    <div class="relative flex items-center justify-center py-4">
                        <div class="w-full border-t border-white/[0.05]"></div>
                        <span class="absolute px-4 bg-[#0B0B0F] text-[10px] text-gray-600 font-bold uppercase tracking-widest">ou continue com</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <button type="button" class="flex items-center justify-center gap-3 py-3 px-4 bg-[#16161D] border border-white/[0.05] rounded-xl text-gray-300 text-xs font-bold hover:bg-[#1C1C24] transition-colors">
                            <i class="fab fa-google text-red-500"></i>
                            Google
                        </button>
                        <button type="button" class="flex items-center justify-center gap-3 py-3 px-4 bg-[#16161D] border border-white/[0.05] rounded-xl text-gray-300 text-xs font-bold hover:bg-[#1C1C24] transition-colors">
                            <i class="fab fa-github text-white"></i>
                            GitHub
                        </button>
                    </div>
                </form>

                <div class="mt-10 text-center">
                    <p class="text-sm text-gray-500">
                        Não tem uma conta? 
                        <a href="#" class="text-fuchsia-400 font-bold hover:underline">Criar conta</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>

