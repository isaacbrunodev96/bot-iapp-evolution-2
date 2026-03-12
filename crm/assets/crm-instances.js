// Instâncias CRM: integração com backend

async function fetchInstances() {
  const res = await fetch('/instances', { credentials: 'include' });
  const parser = new DOMParser();
  const doc = parser.parseFromString(await res.text(), 'text/html');
  return doc.querySelectorAll('.instance-card');
}

function renderInstances(instances) {
  const instancesDiv = document.getElementById('instances-board');
  if (!instancesDiv) return;
  instancesDiv.innerHTML = '';
  instances.forEach(instance => {
    instancesDiv.appendChild(instance.cloneNode(true));
  });
}

async function initInstances() {
  const instances = await fetchInstances();
  renderInstances(instances);
}

document.addEventListener('DOMContentLoaded', initInstances);
