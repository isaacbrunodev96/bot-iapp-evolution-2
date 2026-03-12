// Tenants CRM: integração com backend (admin)

// Tenants CRM: integração com backend (admin, JSON)

async function fetchTenants() {
  const res = await fetch('/api/tenants', { credentials: 'include' });
  const data = await res.json();
  return data.data || [];
}

function renderTenants(tenants) {
  const tenantsDiv = document.getElementById('tenants-board');
  if (!tenantsDiv) return;
  tenantsDiv.innerHTML = '';
  const table = document.createElement('table');
  table.className = 'min-w-full bg-white border border-gray-200 rounded-lg';
  table.innerHTML = `
    <thead>
      <tr>
        <th class="px-4 py-2">ID</th>
        <th class="px-4 py-2">Nome</th>
        <th class="px-4 py-2">Plano</th>
        <th class="px-4 py-2">Status</th>
      </tr>
    </thead>
    <tbody>
      ${tenants.map(t => `
        <tr>
          <td class="px-4 py-2">${t.id}</td>
          <td class="px-4 py-2">${t.name}</td>
          <td class="px-4 py-2">${t.plan}</td>
          <td class="px-4 py-2">${t.status}</td>
        </tr>
      `).join('')}
    </tbody>
  `;
  tenantsDiv.appendChild(table);
}

async function initTenants() {
  const tenants = await fetchTenants();
  renderTenants(tenants);
}

document.addEventListener('DOMContentLoaded', initTenants);
