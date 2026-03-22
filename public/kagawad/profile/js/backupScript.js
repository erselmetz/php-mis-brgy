const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

/**
 * Trigger a database backup — sends CSRF-protected POST,
 * receives file download, then refreshes history.
 */
function triggerBackup() {
  const btn = document.getElementById('backupBtn');
  const desc = document.getElementById('backupDescription').value.trim() || 'Manual Backup';

  btn.disabled = true;
  btn.innerHTML = `
      <svg class="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
      </svg>
      Generating...`;

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/kagawad/profile/backup.php';

  const csrfInput = document.createElement('input');
  csrfInput.type = 'hidden';
  csrfInput.name = 'csrf_token';
  csrfInput.value = csrfToken;

  const descInput = document.createElement('input');
  descInput.type = 'hidden';
  descInput.name = 'description';
  descInput.value = desc;

  form.appendChild(csrfInput);
  form.appendChild(descInput);
  document.body.appendChild(form);
  form.submit();
  document.body.removeChild(form);

  // Re-enable button and refresh history after a short delay
  setTimeout(() => {
    btn.disabled = false;
    btn.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        BACK UP DATA`;
    document.getElementById('backupDescription').value = '';
    loadBackupHistory();
  }, 3000);
}

/**
 * Load backup history from API and render into table.
 */
function loadBackupHistory() {
  const tbody = document.getElementById('backupHistoryBody');
  tbody.innerHTML = `
      <tr>
        <td colspan="4" class="py-6 text-center text-gray-400 text-sm">Loading...</td>
      </tr>`;

  fetch('/kagawad/profile/backup_history.php')
    .then(res => res.json())
    .then(data => {
      if (data.status !== 'ok' || data.data.length === 0) {
        tbody.innerHTML = `
            <tr>
              <td colspan="4" class="py-6 text-center text-gray-400 text-sm">No backups yet.</td>
            </tr>`;
        return;
      }

      tbody.innerHTML = data.data.map(row => `
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="py-3 text-theme-primary font-medium">
              ${escapeHtml(row.date_formatted)}
              <span class="text-gray-400 font-normal ml-1 text-xs">${escapeHtml(row.time_formatted)}</span>
            </td>
            <td class="py-3 text-gray-700">${escapeHtml(row.size_formatted)}</td>
            <td class="py-3 text-gray-700">${escapeHtml(row.description || 'Manual Backup')}</td>
            <td class="py-3 text-gray-500">${escapeHtml(row.performed_by_name)}</td>
          </tr>
        `).join('');
    })
    .catch(() => {
      tbody.innerHTML = `
          <tr>
            <td colspan="4" class="py-4 text-center text-red-400 text-sm">Failed to load backup history.</td>
          </tr>`;
    });
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// Load history on page load
document.addEventListener('DOMContentLoaded', loadBackupHistory);