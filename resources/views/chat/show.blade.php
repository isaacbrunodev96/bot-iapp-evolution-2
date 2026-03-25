@extends('layouts.app')

@section('title', 'Conversa')

@section('content')
@php
    $contactLabel = $conversation->contact_name ?? $conversation->contact;
    $trim = trim((string) $contactLabel);
    $parts = preg_split('/\s+/u', $trim, -1, PREG_SPLIT_NO_EMPTY);
    if (count($parts) >= 2) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1) . mb_substr($parts[count($parts) - 1], 0, 1));
    } else {
        $initials = mb_strtoupper(mb_substr($trim !== '' ? $trim : '?', 0, min(2, max(1, mb_strlen($trim)))));
    }
@endphp
<div class="h-[calc(100dvh-1rem)] sm:h-[calc(100dvh-2rem)] max-h-[calc(100vh-1rem)] flex flex-col min-h-0 px-3 sm:px-5 py-3 sm:py-4 max-w-4xl mx-auto w-full">
    <div class="flex flex-col flex-1 min-h-0 rounded-2xl border border-[#2A2A35] bg-[#16161D] shadow-2xl shadow-black/40 overflow-hidden ring-1 ring-white/[0.03]">
        {{-- Cabeçalho: identidade do contacto + ação clara --}}
        <header class="shrink-0 px-4 sm:px-6 py-4 border-b border-[#2A2A35] bg-gradient-to-r from-[#16161D] via-[#1a1a24] to-[#16161D]">
            <div class="flex items-center gap-4">
                <a
                    href="{{ route('chat.index') }}"
                    class="shrink-0 w-10 h-10 rounded-xl border border-[#2A2A35] bg-[#0F0F13] text-gray-400 hover:text-white hover:border-purple-500/40 hover:bg-[#1F1F2A] flex items-center justify-center transition-all"
                    title="Voltar ao Inbox"
                    aria-label="Voltar ao Inbox"
                >
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-gradient-to-br from-purple-600 to-pink-500 flex items-center justify-center text-white font-bold text-sm sm:text-base shadow-lg shadow-purple-900/30 ring-2 ring-white/10 shrink-0">
                    {{ $initials }}
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="text-lg sm:text-xl font-bold text-white tracking-tight truncate">
                        {{ $contactLabel }}
                    </h1>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 text-xs font-mono text-gray-400 bg-[#0F0F13] px-2.5 py-1 rounded-lg border border-[#2A2A35]">
                            <i class="fab fa-whatsapp text-emerald-500 text-[11px]"></i>
                            {{ $conversation->contact }}
                        </span>
                        @if($conversation->instance_name)
                            <span class="text-[10px] uppercase tracking-wider text-gray-500 font-semibold px-2 py-0.5 rounded-md bg-white/5 border border-white/5">
                                {{ $conversation->instance_name }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </header>

        {{-- Área de mensagens --}}
        <div
            id="messages-container"
            class="flex-1 overflow-y-auto min-h-0 px-3 sm:px-6 py-5 space-y-4 bg-[#0F0F13] bg-[radial-gradient(ellipse_120%_80%_at_50%_-20%,rgba(147,51,234,0.08),transparent_50%)]"
        >
            @foreach($conversation->messages as $message)
            <div class="flex {{ $message->direction === 'outgoing' ? 'justify-end' : 'justify-start' }}">
                <div class="max-w-[85%] sm:max-w-[75%] lg:max-w-md px-4 py-3 rounded-2xl shadow-lg {{ $message->direction === 'outgoing' ? 'rounded-br-md bg-gradient-to-br from-purple-600 to-pink-600 text-white shadow-purple-900/25' : 'rounded-bl-md bg-[#1F1F28] text-gray-100 border border-[#2A2A35] shadow-black/20' }}">
                    <p class="text-[15px] leading-relaxed whitespace-pre-wrap break-words">{{ $message->message }}</p>
                    <p class="text-[11px] mt-2 {{ $message->direction === 'outgoing' ? 'text-white/65' : 'text-gray-500' }} tabular-nums">
                        {{ $message->created_at->format('H:i') }}
                    </p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Compositor --}}
        <div class="shrink-0 p-3 sm:p-4 border-t border-[#2A2A35] bg-[#16161D]">
            <form id="message-form" class="flex gap-2 sm:gap-3 items-center">
                <label class="sr-only" for="message-input">Mensagem</label>
                <div class="flex-1 flex items-center min-h-12 rounded-xl border border-[#2A2A35] bg-[#0F0F13] focus-within:border-purple-500/50 focus-within:ring-1 focus-within:ring-purple-500/20 transition-all">
                    <input
                        type="text"
                        id="message-input"
                        name="message"
                        placeholder="Escreva uma mensagem…"
                        autocomplete="off"
                        class="w-full min-h-12 h-12 box-border bg-transparent text-gray-100 placeholder:text-gray-500 px-4 text-[15px] leading-normal rounded-xl border-0 focus:outline-none focus:ring-0"
                        required
                    >
                </div>
                <button
                    type="submit"
                    id="send-button"
                    class="shrink-0 h-12 min-h-12 min-w-12 sm:min-w-0 px-4 sm:px-6 rounded-xl bg-neon-gradient text-white font-semibold text-sm shadow-lg shadow-purple-900/30 hover:opacity-95 active:scale-[0.98] focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 focus:ring-offset-[#16161D] disabled:opacity-45 disabled:cursor-not-allowed disabled:active:scale-100 transition-all inline-flex items-center justify-center gap-2"
                >
                    <span class="hidden sm:inline">Enviar</span>
                    <i class="fas fa-paper-plane text-[15px] leading-none shrink-0" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
const conversationId = {{ $conversation->id }};
const instanceName = {!! json_encode($conversation->instance_name) !!};
const contact = {!! json_encode($conversation->contact) !!};

const bubbleOutgoing = 'max-w-[85%] sm:max-w-[75%] lg:max-w-md px-4 py-3 rounded-2xl rounded-br-md shadow-lg bg-gradient-to-br from-purple-600 to-pink-600 text-white shadow-purple-900/25';
const bubbleIncoming = 'max-w-[85%] sm:max-w-[75%] lg:max-w-md px-4 py-3 rounded-2xl rounded-bl-md shadow-lg bg-[#1F1F28] text-gray-100 border border-[#2A2A35] shadow-black/20';
const timeOutgoing = 'text-[11px] mt-2 text-white/65 tabular-nums';
const timeIncoming = 'text-[11px] mt-2 text-gray-500 tabular-nums';

document.getElementById('message-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const input = document.getElementById('message-input');
    const message = input.value.trim();

    if (!message) return;

    const messagesContainer = document.getElementById('messages-container');
    const messageId = 'temp-' + Date.now();
    const messageDiv = document.createElement('div');
    messageDiv.id = messageId;
    messageDiv.className = 'flex justify-end';
    messageDiv.innerHTML = `
        <div class="${bubbleOutgoing}">
            <p class="text-[15px] leading-relaxed whitespace-pre-wrap break-words"></p>
            <p class="${timeOutgoing}">Enviando…</p>
        </div>
    `;
    messageDiv.querySelector('p').textContent = message;
    messagesContainer.appendChild(messageDiv);
    messagesContainer.scrollTop = messagesContainer.scrollHeight;

    input.value = '';

    const submitButton = document.getElementById('send-button');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;

    try {
        const response = await axios.post('/api/bot/send-message', {
            instance_name: instanceName,
            contact: contact,
            message: message
        });

        if (response.data.success) {
            const messageElement = document.getElementById(messageId);
            if (messageElement) {
                const wrap = messageElement.querySelector('div');
                wrap.className = bubbleOutgoing;
                wrap.innerHTML = `
                    <p class="text-[15px] leading-relaxed whitespace-pre-wrap break-words"></p>
                    <p class="${timeOutgoing}">Agora</p>
                `;
                wrap.querySelector('p').textContent = message;
            }
        } else {
            throw new Error(response.data.message || 'Erro ao enviar mensagem');
        }
    } catch (error) {
        console.error('Erro ao enviar mensagem:', error);

        const messageElement = document.getElementById(messageId);
        if (messageElement) {
            messageElement.remove();
        }

        input.value = message;

        let errorMessage = 'Erro ao enviar mensagem. Tente novamente.';
        if (error.response?.data?.error) {
            errorMessage = error.response.data.error;
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.message) {
            errorMessage = error.message;
        }
        alert(errorMessage);
    } finally {
        submitButton.disabled = false;
        submitButton.innerHTML = originalButtonText;
    }
});

const messagesContainer = document.getElementById('messages-container');

let lastMessageId = {{ $conversation->messages->isEmpty() ? 0 : $conversation->messages->max('id') }};

function scrollToBottom() {
    messagesContainer.scrollTop = messagesContainer.scrollHeight;
}
scrollToBottom();

function renderMessage(msg) {
    const isOutgoing = msg.direction === 'outgoing';
    const timeStr = msg.created_at ? new Date(msg.created_at).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' }) : '';
    const div = document.createElement('div');
    div.className = 'flex ' + (isOutgoing ? 'justify-end' : 'justify-start');
    div.dataset.messageId = msg.id;
    const bubbleClass = isOutgoing ? bubbleOutgoing : bubbleIncoming;
    const timeClass = isOutgoing ? timeOutgoing : timeIncoming;
    div.innerHTML = `
        <div class="${bubbleClass}">
            <p class="text-[15px] leading-relaxed whitespace-pre-wrap break-words"></p>
            <p class="${timeClass}">${timeStr}</p>
        </div>
    `;
    div.querySelector('p').textContent = msg.message || '';
    return div;
}

setInterval(async () => {
    if (document.visibilityState !== 'visible') return;
    try {
        const response = await axios.get(`/api/messages?conversation_id=${conversationId}&after_id=${lastMessageId}`);
        const messages = response.data.data || [];
        for (const msg of messages) {
            messagesContainer.appendChild(renderMessage(msg));
            lastMessageId = Math.max(lastMessageId, msg.id);
        }
        if (messages.length) scrollToBottom();
    } catch (err) {
        //
    }
}, 3000);
</script>
@endsection
