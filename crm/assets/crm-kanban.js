// Kanban CRM: integração com backend

async function fetchConversations() {
  const res = await fetch('/api/conversations', { credentials: 'include' });
  const data = await res.json();
  return data.data || [];
}

function renderKanban(conversations) {
  const kanban = document.getElementById('kanban-board');
  if (!kanban) return;
  kanban.innerHTML = '';
  const statuses = ['novo', 'em_atendimento', 'aguardando', 'fechado'];
  statuses.forEach(status => {
    const col = document.createElement('div');
    col.className = 'kanban-col';
    col.innerHTML = `<h2>${status}</h2>`;
    const leads = conversations.filter(c => c.kanban_status === status);
    leads.forEach(lead => {
      const card = document.createElement('div');
      card.className = 'kanban-card';
      card.innerHTML = `<strong>${lead.contact}</strong><br><span>${lead.latestMessage?.message || ''}</span>`;
      col.appendChild(card);
    });
    kanban.appendChild(col);
  });
}

async function initKanban() {
  const conversations = await fetchConversations();
  renderKanban(conversations);
}

document.addEventListener('DOMContentLoaded', initKanban);
