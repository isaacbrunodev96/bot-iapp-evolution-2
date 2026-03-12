// Flows CRM: integração com backend

async function fetchFlows() {
  const res = await fetch('/api/flows', { credentials: 'include' });
  const data = await res.json();
  return data.data || [];
}

function renderFlows(flows) {
  const flowsDiv = document.getElementById('flows-board');
  if (!flowsDiv) return;
  flowsDiv.innerHTML = '';
  flows.forEach(flow => {
    const card = document.createElement('div');
    card.className = 'flow-card';
    card.innerHTML = `<strong>${flow.name}</strong><br><span>${flow.description || ''}</span>`;
    flowsDiv.appendChild(card);
  });
}

async function initFlows() {
  const flows = await fetchFlows();
  renderFlows(flows);
}

document.addEventListener('DOMContentLoaded', initFlows);
