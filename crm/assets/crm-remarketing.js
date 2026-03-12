// Remarketing CRM: integração com backend

async function sendRemarketing(message, target = 'all', contacts = [], sendAsAudio = false, voiceId = null) {
  const res = await fetch('/api/remarketing/send', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message, target, contacts, send_as_audio: sendAsAudio, voice_id: voiceId })
  });
  const data = await res.json();
  return data.success;
}

// Exemplo de uso:
// sendRemarketing('Promoção especial!', 'all').then(success => {/* feedback */});
