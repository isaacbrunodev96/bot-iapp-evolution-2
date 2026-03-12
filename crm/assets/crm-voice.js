// Voz CRM: integração com ElevenLabs

async function generateVoice(text, voiceId = null, model = null) {
  const res = await fetch('/api/elevenlabs/text-to-speech', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ text, voice_id: voiceId, model })
  });
  const data = await res.json();
  return data.audio || null;
}

// Exemplo de uso:
// generateVoice('Olá, tudo bem?').then(audio => {/* tocar áudio */});
