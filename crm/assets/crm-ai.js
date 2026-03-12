// IA CRM: integração com backend (Ollama/Gemini)

async function generateAIResponse(message, flowId = null) {
  const res = await fetch('/api/ai/generate', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message, flow_id: flowId })
  });
  const data = await res.json();
  return data.response || '';
}

// Exemplo de uso:
// generateAIResponse('Olá, preciso de ajuda').then(resp => console.log(resp));
