document.addEventListener('DOMContentLoaded', function () {
  const api = window.__ADMIN_API || '/Mpemba/api/admin_data.php';

  function renderCharts(data) {
    if (typeof Chart !== 'undefined') {
      if (data.revenue && document.getElementById('revenueChart')) {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
          type: 'line',
          data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
              label: 'Revenue',
              data: data.revenue,
              backgroundColor: 'rgba(108,92,231,0.08)',
              borderColor: '#6c5ce7',
              tension: 0.3,
              fill: true
            }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } }
          }
        });
      }

      if (data.revenue && document.getElementById('reportRevenue')) {
        const ctx2 = document.getElementById('reportRevenue').getContext('2d');
        new Chart(ctx2, {
          type: 'bar',
          data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{ label: 'Revenue', data: data.revenue, backgroundColor: '#8b5cf6' }]
          },
          options: { indexAxis: 'x' }
        });
      }
    }
  }

  fetch(api)
    .then(r => r.json())
    .then(renderCharts)
    .catch(() => {
      // Ignore chart rendering issues for pages without chart library support
    });

  const panel = document.createElement('div');
  panel.className = 'admin-notification-panel';
  Object.assign(panel.style, {
    position: 'fixed',
    top: '88px',
    right: '24px',
    width: '340px',
    maxWidth: 'calc(100vw - 32px)',
    background: '#ffffff',
    border: '1px solid rgba(148,163,184,0.16)',
    borderRadius: '1rem',
    boxShadow: '0 24px 60px rgba(15,23,42,0.18)',
    padding: '1rem',
    zIndex: '9999',
    opacity: '0',
    pointerEvents: 'none',
    transition: 'opacity 180ms ease-in-out, transform 180ms ease-in-out',
    transform: 'translateY(-6px)'
  });
  document.body.appendChild(panel);
  panel.addEventListener('click', function (event) {
    if (event.target.closest('.admin-popup-close')) {
      hidePanel();
    }
  });

  let activeTrigger = null;

  function hidePanel() {
    panel.style.opacity = '0';
    panel.style.pointerEvents = 'none';
    panel.style.transform = 'translateY(-6px)';
    activeTrigger = null;
  }

  function showPanel(content) {
    panel.innerHTML = content;
    panel.style.opacity = '1';
    panel.style.pointerEvents = 'auto';
    panel.style.transform = 'translateY(0)';
  }

  function buildPanel(title, items) {
    const lines = [`<div class="flex items-start justify-between mb-4">`, `<div>`, `<p style="font-size:14px;font-weight:700;color:#0f172a;margin:0;">${title}</p>`, `</div>`, `<button type="button" class="admin-popup-close" aria-label="Close notification panel" style="background:transparent;border:none;color:#64748b;font-weight:700;cursor:pointer;">×</button>`, `</div>`, `<div style="display:grid;gap:14px;">`];
    items.forEach(item => {
      lines.push(`
        <div style="display:flex;gap:12px;padding:16px;border-radius:18px;background:#f8fafc;align-items:flex-start;">
          <span style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:14px;background:#eef2ff;color:#4338ca;font-size:18px;">${item.icon}</span>
          <div style="flex:1;min-width:0;">
            <p style="margin:0;font-size:13px;font-weight:700;color:#0f172a;">${item.title}</p>
            <p style="margin:4px 0 0;font-size:12px;color:#475569;line-height:1.5;">${item.subtitle}</p>
          </div>
        </div>
      `);
    });
    lines.push('</div>');
    return lines.join('\n');
  }

  function getPanelContent(name) {
    if (name === 'notifications') {
      return buildPanel('Notifications', [
        { icon: '🛍️', title: 'Low stock alert', subtitle: '12 products are below your threshold. Review inventory.' },
        { icon: '📦', title: 'New orders received', subtitle: '5 orders have been placed in the last hour.' },
        { icon: '✉️', title: 'Digest ready', subtitle: 'Weekly summary will be delivered to your inbox.' }
      ]);
    }
    if (name === 'help_outline') {
      return buildPanel('Admin Help', [
        { icon: '💡', title: 'Need support?', subtitle: 'Email support@mpemba.com for urgent assistance.' },
        { icon: '⚙️', title: 'Manage settings', subtitle: 'Toggle notifications and save preferences in the Settings page.' }
      ]);
    }
    return '';
  }

  document.addEventListener('click', function (event) {
    const trigger = event.target.closest('button');
    if (!trigger || !trigger.classList.contains('admin-notification-trigger')) {
      if (activeTrigger && !panel.contains(event.target)) {
        hidePanel();
      }
      return;
    }

    const icon = trigger.querySelector('.material-symbols-outlined');
    if (!icon) {
      return;
    }

    const name = icon.textContent.trim();
    const content = getPanelContent(name);
    if (!content) {
      return;
    }

    event.preventDefault();
    if (activeTrigger === trigger) {
      hidePanel();
      return;
    }

    activeTrigger = trigger;
    showPanel(content);
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape') {
      hidePanel();
    }
  });

  function filterAdminTableRows(query, table) {
    const normalized = (query || '').trim().toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      const rowText = row.textContent.trim().toLowerCase();
      row.style.display = !normalized || rowText.includes(normalized) ? '' : 'none';
    });
  }

  document.querySelectorAll('input[type="text"]').forEach(function (input) {
    if (!input.placeholder.toLowerCase().includes('search')) {
      return;
    }

    const table = input.closest('main')?.querySelector('table') || document.querySelector('table');
    if (!table) {
      return;
    }

    input.addEventListener('input', function () {
      filterAdminTableRows(this.value, table);
    });
  });

  document.querySelectorAll('button').forEach(function (button) {
    const icon = button.querySelector('.material-symbols-outlined');
    if (!icon) {
      return;
    }
    const name = icon.textContent.trim();
    if (name === 'notifications' || name === 'help_outline') {
      button.classList.add('admin-notification-trigger');
      button.setAttribute('type', 'button');
    }
  });
});
