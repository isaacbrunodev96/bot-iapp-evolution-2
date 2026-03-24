@extends('layouts.app')

@section('title', 'Canais - Nexxivo')

@section('content')
<div class="p-6 md:p-10 max-w-7xl mx-auto space-y-8">
    
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white tracking-tight">Canais</h1>
            <p class="text-gray-400 mt-2">Gerencie seus canais de comunicação.</p>
        </div>
        <button type="button" onclick="document.getElementById('form-new').classList.toggle('hidden')" class="bg-gradient-to-r from-purple-600 to-pink-500 hover:from-purple-500 hover:to-pink-400 text-white px-6 py-2.5 rounded-full font-medium shadow-lg shadow-purple-500/20 transition transform hover:-translate-y-0.5 flex items-center gap-2">
            <i class="fas fa-plus"></i>
            Adicionar Canal
        </button>
    </div>

    @if(session('success'))
    <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 rounded-lg text-emerald-400 flex items-center gap-3">
        <i class="fas fa-check-circle"></i>
        {{ session('success') }}
    </div>
    @endif
    @if($errors->any())
    <div class="p-4 bg-red-500/10 border border-red-500/20 rounded-lg text-red-500">
        <p class="font-semibold mb-1 flex items-center gap-2"><i class="fas fa-exclamation-circle text-red-400"></i> Erro ao criar canal:</p>
        <ul class="list-disc list-inside text-sm ml-6">
            @foreach($errors->all() as $e)
            <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Form Novo Canal (Oculto por Padrão) -->
    <div id="form-new" class="{{ $errors->any() ? '' : 'hidden' }} bg-[#16161D] border border-[#2A2A35] rounded-2xl p-6 shadow-xl shadow-black/20">
        <form id="form-create-instance" action="{{ route('instances.store') }}" method="POST" class="flex flex-col md:flex-row items-end gap-4">
            @csrf
            <div class="flex-1 w-full">
                <label for="instance-name" class="block text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Nome do WhatsApp (Instância)</label>
                <input id="instance-name" type="text" name="name" value="{{ old('name') }}" required maxlength="80" autocomplete="off" class="w-full bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-white px-4 py-3 focus:border-purple-500 focus:ring-1 focus:ring-purple-500 outline-none transition" placeholder="ex: Atendimento Vendas">
            </div>
            <button type="submit" id="btn-create-instance" class="bg-purple-600 hover:bg-purple-500 text-white px-8 py-3 rounded-xl font-medium transition w-full md:w-auto">
                Criar Conexão
            </button>
        </form>
    </div>

    <!-- Polling Script p/ QRCode -->
    @php
        $instancesAguardandoQr = $instances->filter(fn ($i) => $i->status !== 'connected' && empty($i->qrcode));
        $refreshQrUrls = $instancesAguardandoQr->map(fn ($i) => route('instances.refresh-qr', $i))->values()->all();
        $pollingList = $instancesAguardandoQr->map(fn ($i) => [
            'id' => $i->id,
            'rawUrl' => route('instances.evolution-connect-raw', $i),
            'saveUrl' => route('instances.save-qr', $i),
        ])->values()->all();
    @endphp
    @if($instancesAguardandoQr->isNotEmpty())
    <script>
    (function() {
        var refreshUrls = @json($refreshQrUrls);
        var pollingList = @json($pollingList);
        var csrf = @json(csrf_token());
        function findInObj(obj, out) {
            if (!obj || typeof obj !== 'object') return;
            var keys = Object.keys(obj);
            for (var i = 0; i < keys.length; i++) {
                var k = keys[i], v = obj[k];
                if (typeof v === 'string') {
                    if ((k === 'pairingCode' || k === 'pairing_code') && v.length >= 4 && v.length <= 32) out.pairingCode = v;
                    if (v.length > 100 && v.indexOf('@') === -1 && /^[A-Za-z0-9+\/=]+$/.test(v)) out.base64 = v;
                    if (v.indexOf('data:image') === 0 && v.indexOf('base64,') !== -1) out.base64 = v.replace(/^data:image\/[^;]+;base64,/, '');
                } else if (typeof v === 'object' && v !== null) findInObj(v, out);
            }
        }
        function poll() {
            if (document.visibilityState !== 'visible') return;
            refreshUrls.forEach(function(url) {
                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(data) { if (data.qrcode || data.pairing_code) location.reload(); }).catch(function() {});
            });
            pollingList.forEach(function(item) {
                fetch(item.rawUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var out = {}; findInObj(data, out);
                        if (out.base64 || out.pairingCode) {
                            var body = new FormData();
                            if (csrf) body.append('_token', csrf);
                            if (out.base64) body.append('qrcode', out.base64);
                            if (out.pairingCode) body.append('pairing_code', out.pairingCode);
                            fetch(item.saveUrl, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf } })
                                .then(function() { location.reload(); });
                        }
                    }).catch(function() {});
            });
        }
        setInterval(poll, 2000); poll();
    })();
    </script>
    @endif

    <!-- Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- Instâncias Reais WhatsApp -->
        @foreach($instances as $instance)
        <div class="bg-[#16161D] border border-purple-500/30 rounded-2xl flex flex-col shadow-[0_0_15px_rgba(168,85,247,0.15)] overflow-hidden relative transition-transform hover:-translate-y-1">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-500 shrink-0">
                            <i class="fab fa-whatsapp text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-lg">{{ $instance->instance_name }}</h3>
                            <p class="text-[11px] text-gray-400">WhatsApp Business API</p>
                        </div>
                    </div>
                    
                    <div>
                        @if($instance->status === 'connected')
                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>ON</span>
                        @elseif($instance->status === 'started')
                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-yellow-500/10 text-yellow-500 border border-yellow-500/20 flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-yellow-500"></div>...</span>
                        @else
                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider bg-red-500/10 text-red-500 border border-red-500/20 flex items-center gap-1.5"><div class="w-1.5 h-1.5 rounded-full bg-red-500"></div>OFF</span>
                        @endif
                    </div>
                </div>

                @if($instance->status === 'connected')
                <div class="mt-4 p-4 bg-emerald-500/5 border border-emerald-500/20 rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 border border-emerald-500/20">
                        <i class="fas fa-check-circle text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">WhatsApp conectado</p>
                        <p class="text-xs text-gray-400">Esta instância está ativa e pode enviar e receber mensagens.</p>
                    </div>
                </div>
                @elseif($instance->qrcode)
                <div class="mt-4 flex flex-col items-center">
                    <div class="bg-white p-2 rounded-xl">
                        <img src="{{ str_starts_with($instance->qrcode, 'data:image') ? $instance->qrcode : 'data:image/png;base64,'.$instance->qrcode }}" alt="QR Code" class="w-40 h-40">
                    </div>
                    <p class="text-xs text-gray-400 mt-3 text-center">Escaneie o QRCode acima com o WhatsApp</p>
                </div>
                @elseif($instance->pairing_code)
                <div class="mt-4 p-4 bg-[#0F0F13] border border-[#2A2A35] rounded-xl text-center">
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-widest mb-1">Código de pareamento</p>
                    <p class="text-3xl font-mono font-bold text-white tracking-[0.2em]">{{ $instance->pairing_code }}</p>
                </div>
                @else
                <div class="mt-4 p-4 bg-[#0F0F13] border border-[#2A2A35] rounded-xl flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-[#1A1A24] flex items-center justify-center text-purple-500 border border-purple-500/20">
                        <i class="fas fa-plug text-sm"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Aguardando conexão</p>
                        <p class="text-xs text-gray-500">Gere o QR em Canais ou confira se a Evolution API está rodando no servidor.</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Footer Action -->
            <div class="mt-auto bg-[#0F0F13] border-t border-[#2A2A35] p-3 flex items-center justify-between">
                <form action="{{ route('instances.destroy', $instance) }}" method="POST" onsubmit="return confirm('ATENÇÃO: Desconectar este número fará o bot de WhatsApp parar. Tem certeza?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-gray-500 hover:text-red-500 hover:bg-red-500/10 px-3 py-1.5 rounded-lg text-xs font-semibold uppercase tracking-wider transition focus:outline-none flex items-center gap-2">
                        <i class="fas fa-unlink"></i> Apagar
                    </button>
                </form>
                <button class="bg-[#1A1A24] text-gray-300 hover:text-white border border-[#2A2A35] px-3 py-1.5 rounded-lg text-xs font-semibold uppercase tracking-wider flex items-center gap-2 transition">
                    <i class="fas fa-cog"></i> Configurar
                </button>
            </div>
        </div>
        @endforeach

        @if($instances->isEmpty())
        <!-- Placeholder: WhatsApp (Adicionar Novo) -->
        <div class="bg-[#16161D] border border-dashed border-[#2A2A35] hover:border-emerald-500/50 rounded-2xl flex flex-col items-center justify-center p-8 text-center cursor-pointer transition-colors group" onclick="document.getElementById('form-new').classList.remove('hidden'); document.getElementById('instance-name').focus();">
            <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center text-emerald-500 mb-4 group-hover:scale-110 transition-transform">
                <i class="fab fa-whatsapp text-3xl"></i>
            </div>
            <h3 class="font-bold text-white text-lg mb-1">Conectar WhatsApp</h3>
            <p class="text-xs text-gray-400">Clique para criar uma nova instância da Evolution API.</p>
        </div>
        @endif

        <!-- Placeholder: Telegram -->
        <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl flex flex-col shadow-lg shadow-black/10 overflow-hidden opacity-75">
            <div class="p-6">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-[#2AABEE]/10 flex items-center justify-center text-[#2AABEE] shrink-0">
                            <i class="fab fa-telegram-plane text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-lg">Telegram</h3>
                            <p class="text-sm text-gray-400">Integre chatbots no Telegram</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-400 border border-gray-500/20">Em breve</span>
                </div>
            </div>
            <div class="mt-auto bg-[#0F0F13] border-t border-[#2A2A35] p-4 flex items-center justify-center">
                <button class="text-gray-500 cursor-not-allowed text-sm font-medium w-full py-1">Conectar</button>
            </div>
        </div>

        <!-- Placeholder: Instagram -->
        <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl flex flex-col shadow-lg shadow-black/10 overflow-hidden opacity-75">
            <div class="p-6">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-yellow-400 via-pink-500 to-purple-500 bg-opacity-10 flex items-center justify-center text-pink-500 shrink-0">
                            <i class="fab fa-instagram text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-lg">Instagram</h3>
                            <p class="text-sm text-gray-400">Conecte mensagens do Direct</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-400 border border-gray-500/20">Em breve</span>
                </div>
            </div>
            <div class="mt-auto bg-[#0F0F13] border-t border-[#2A2A35] p-4 flex items-center justify-center">
                <button class="text-gray-500 cursor-not-allowed text-sm font-medium w-full py-1">Conectar</button>
            </div>
        </div>

        <!-- Placeholder: Messenger -->
        <div class="bg-[#16161D] border border-[#2A2A35] rounded-2xl flex flex-col shadow-lg shadow-black/10 overflow-hidden opacity-75">
            <div class="p-6">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-[#0084FF]/10 flex items-center justify-center text-[#0084FF] shrink-0">
                            <i class="fab fa-facebook-messenger text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-white text-lg">Messenger</h3>
                            <p class="text-sm text-gray-400">Integre o Facebook Messenger</p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-500/10 text-gray-400 border border-gray-500/20">Em breve</span>
                </div>
            </div>
            <div class="mt-auto bg-[#0F0F13] border-t border-[#2A2A35] p-4 flex items-center justify-center">
                <button class="text-gray-500 cursor-not-allowed text-sm font-medium w-full py-1">Conectar</button>
            </div>
        </div>

    </div>
</div>
@endsection
