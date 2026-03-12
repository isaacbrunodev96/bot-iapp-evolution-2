@extends('layouts.app')

@section('title', 'Voz & IA - Nexxivo')

@section('content')
<div class="p-6 md:p-10 max-w-7xl mx-auto space-y-8">
    
    <!-- Cabeçalho -->
    <div>
        <h1 class="text-3xl font-bold text-white tracking-tight">Voz & IA</h1>
        <p class="text-gray-400 mt-2">Configure inteligência artificial e síntese de voz.</p>
    </div>

    @if(session('success'))
    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg flex items-center gap-3 text-emerald-400">
        <i class="fas fa-check-circle"></i>
        <p>{{ session('success') }}</p>
    </div>
    @endif

    <form method="POST" action="{{ route('ai-settings.store') }}" class="space-y-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Card Esquerdo: Inteligência Artificial -->
            <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-6 shadow-xl shadow-black/20 flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 flex items-center justify-center text-purple-500">
                        <i class="fas fa-brain text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Inteligência Artificial</h2>
                        <p class="text-sm text-gray-500">Gemini / Llama / Ollama</p>
                    </div>
                </div>

                <div class="space-y-5 flex-1">
                    <!-- Provider Padrão -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Modelo Ativo</label>
                        <select name="default_provider" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none appearance-none transition">
                            <option value="gemini" {{ $settings['default_provider'] === 'gemini' ? 'selected' : '' }}>Google Gemini 2.0 Flash</option>
                            <option value="ollama" {{ $settings['default_provider'] === 'ollama' ? 'selected' : '' }}>Ollama (Local Models)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Chave da API Gemini</label>
                            <input type="password" name="gemini_api_key" value="{{ $settings['gemini_api_key'] }}" placeholder="Cole a chave AI Studio aqui..." class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Modelo Gemini</label>
                            <input type="text" name="gemini_model" value="{{ $settings['gemini_model'] }}" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Local Ollama URL</label>
                            <input type="text" name="ollama_url" value="{{ $settings['ollama_url'] }}" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Local Ollama Model</label>
                            <input type="text" name="ollama_model" value="{{ $settings['ollama_model'] }}" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition">
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-[#2A2A35] flex items-center justify-between">
                    <div class="flex items-center gap-2 text-gray-300">
                        <i class="fas fa-magic text-purple-400"></i>
                        <span class="font-medium text-sm">Resposta automática por IA</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-[#0F0F13] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>
            </div>

            <!-- Card Direito: Síntese de Voz -->
            <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl p-6 shadow-xl shadow-black/20 flex flex-col">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-pink-500/10 flex items-center justify-center text-pink-500">
                        <i class="fas fa-volume-up text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">Síntese de Voz</h2>
                        <p class="text-sm text-gray-500">ElevenLabs TTS</p>
                    </div>
                </div>

                <div class="space-y-5 flex-1">
                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Chave da API ElevenLabs</label>
                        <input type="password" name="elevenlabs_api_key" value="{{ $settings['elevenlabs_api_key'] ?? '' }}" placeholder="Cole sua API Key da ElevenLabs..." class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Voice ID Selecionada</label>
                        <input type="text" name="elevenlabs_voice_id" value="{{ $settings['elevenlabs_voice_id'] ?? 'JBFqnCBsd6RMkjVDRZzb' }}" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition">
                        <p class="text-[11px] text-gray-500 mt-2">Dica: Sarah (Feminina - PT-BR) ID recomendado para uso agradável e natural.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Modelo de Idioma TTS</label>
                        <input type="text" name="elevenlabs_model" value="{{ $settings['elevenlabs_model'] ?? 'eleven_multilingual_v2' }}" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-pink-500 focus:ring-1 focus:ring-pink-500 outline-none transition">
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-[#2A2A35] flex items-center justify-between">
                    <div class="flex items-center gap-2 text-gray-300">
                        <i class="fas fa-comment-dots text-pink-400"></i>
                        <span class="font-medium text-sm">Permitir áudio no Roteiro de Fluxo</span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" checked class="sr-only peer">
                        <div class="w-11 h-6 bg-[#0F0F13] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-pink-500"></div>
                    </label>
                </div>
            </div>

        </div>

        <!-- Float Save Button -->
        <div class="fixed bottom-8 right-8 z-30">
            <button type="submit" class="bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-500 hover:to-pink-400 text-white font-medium px-8 py-3 rounded-full shadow-lg shadow-purple-500/30 transition transform hover:-translate-y-1 flex items-center gap-2">
                <i class="fas fa-save"></i>
                Salvar Configurações
            </button>
        </div>
    </form>
</div>
@endsection
