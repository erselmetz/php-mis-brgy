/**
   * Load live file report stats and render rows.
   */
function loadFileReports() {
    const tbody = document.getElementById('fileReportsBody');
    tbody.innerHTML = `
      <tr>
        <td colspan="4" class="py-6 text-center text-gray-400 text-sm">Loading...</td>
      </tr>`;

    fetch('/secretary/profile/file_reports.php')
        .then(res => res.json())
        .then(data => {
            if (data.status !== 'ok' || !data.data.length) {
                tbody.innerHTML = `
            <tr>
              <td colspan="4" class="py-6 text-center text-gray-400 text-sm">No data available.</td>
            </tr>`;
                return;
            }

            tbody.innerHTML = data.data.map(r => `
          <tr class="hover:bg-gray-50 transition-colors">
            <td class="py-3 font-medium text-gray-800">${escapeHtml(r.label)}</td>
            <td class="py-3 text-gray-600">
              <span class="bg-gray-100 text-gray-700 text-xs font-semibold px-2 py-0.5 rounded-full">
                ${r.count.toLocaleString()} records
              </span>
            </td>
            <td class="py-3 text-gray-500 text-xs">${escapeHtml(r.last_updated)}</td>
            <td class="py-3 text-right">
              <button
                onclick="printReport('${escapeHtml(r.print_url)}')"
                class="bg-theme-primary hover-theme-darker text-white text-xs px-3 py-1.5 rounded flex items-center gap-1 ml-auto">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Report
              </button>
            </td>
          </tr>
        `).join('');
        })
        .catch(() => {
            tbody.innerHTML = `
          <tr>
            <td colspan="4" class="py-4 text-center text-red-400 text-sm">Failed to load report stats.</td>
          </tr>`;
        });
}

/**
 * Open the print page in a new tab.
 */
function printReport(url) {
    window.open(url, '_blank', 'width=1000,height=700');
}

// Load on page ready (alongside loadBackupHistory)
document.addEventListener('DOMContentLoaded', () => {
    loadFileReports();
    // loadBackupHistory() already called from the backup script
});