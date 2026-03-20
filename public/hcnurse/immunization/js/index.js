/**
 * Immunization — JS Controller
 * Replaces: public/hcnurse/immunization/js/index.js
 */
$(function () {

    let selectedId   = 0;
    let selectedName = '—';

    /* ── Datepickers ── */
    $('#immDateGiven, #immNextSchedule').datepicker({
        dateFormat: 'mm/dd/yy',
        changeMonth: true,
        changeYear: true,
    });

    /* ── DataTable ── */
    const dt = $('#immRecordsTable').DataTable({
        pageLength: 50,
        lengthChange: false,
        searching: false,
        info: false,
        ordering: true,
        order: [[1, 'desc']],
        columns: [
            {
                title: 'Vaccine',
                render: function (data, type, row) {
                    let html = `<div class="imm-vaccine">${esc(row.vaccine)}</div>`;
                    if (row.dose) html += `<div class="imm-dose">${esc(row.dose)}</div>`;
                    return html;
                }
            },
            {
                title: 'Date Given',
                render: (d, t, row) => `<span class="imm-date">${esc(fmtDate(row.date_given))}</span>`
            },
            {
                title: 'Next Schedule',
                render: (d, t, row) => row.next_schedule
                    ? `<span class="imm-next">${esc(fmtDate(row.next_schedule))}</span>`
                    : '<span style="color:var(--ink-faint);font-size:11px;">—</span>'
            },
            {
                title: 'Status',
                render: (d, t, row) => {
                    const s   = row.status || 'Unknown';
                    const cls = s === 'Up to Date' ? 'iss-ok' : s === 'Due/Overdue' ? 'iss-due' : 'iss-unk';
                    return `<span class="imm-status ${cls}">${esc(s)}</span>`;
                }
            }
        ],
        columnDefs: [{ orderable: false, targets: [3] }]
    });

    /* ── Helpers ── */
    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }
    function fmtDate(s) {
        if (!s) return '—';
        const parts = String(s).split('-');
        if (parts.length !== 3) return s;
        return parts[1] + '/' + parts[2] + '/' + parts[0];
    }
    function showAlert(title, msg, type) {
        const col = type === 'success' ? 'var(--ok-fg)' : type === 'danger' ? 'var(--danger-fg)' : 'var(--warn-fg)';
        const id  = 'al_' + Date.now();
        $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
        </div>`);
        $(`#${id}`).dialog({
            autoOpen: true, modal: true, width: 400, resizable: false,
            buttons: { 'OK': function () { $(this).dialog('close').remove(); } }
        });
    }

    /* ════════════════════════
       RESIDENT LIST
    ════════════════════════ */
    function renderResList(residents) {
        const $list = $('#immResList');
        if (!residents.length) {
            $list.html('<div class="res-empty">No residents found.</div>');
            return;
        }
        let html = '';
        residents.forEach(r => {
            const active = r.id === selectedId ? ' selected' : '';
            html += `<div class="res-item${active}" data-id="${r.id}" data-name="${esc(r.name)}">
                <div class="res-item-name">${esc(r.name)}</div>
            </div>`;
        });
        $list.html(html);
    }

    function loadResidents(q = '') {
        $.ajax({
            url: 'api/residentSearch.php',
            data: { ajax: 'residents', q, limit: 60 },
            dataType: 'json',
            success: res => { if (res.success) renderResList(res.residents || []); }
        });
    }

    /* Debounced search */
    let searchTimer;
    $('#immResSearch').on('input', function () {
        clearTimeout(searchTimer);
        const q = $(this).val();
        searchTimer = setTimeout(() => loadResidents(q), 250);
    });

    /* ════════════════════════
       SELECT RESIDENT
    ════════════════════════ */
    function selectResident(id, name) {
        selectedId   = parseInt(id, 10) || 0;
        selectedName = name || '—';

        $('#immResidentId').val(selectedId);
        $('#immSelectedName').text(selectedName);

        /* highlight in list */
        $('.res-item').removeClass('selected');
        $(`.res-item[data-id="${selectedId}"]`).addClass('selected');

        /* enable controls */
        const on = selectedId > 0;
        $('#immSaveBtn, #btnPrint, #btnPrintReport').prop('disabled', !on);
        $('#immVaccineName, #immDose, #immDateGiven, #immNextSchedule, #immRemarks').prop('disabled', !on);

        /* show panels */
        if (on) {
            $('#immNoSelection').hide();
            $('#immTableWrap, #immAddWrap').show();
            loadRecords();
        } else {
            $('#immNoSelection').show();
            $('#immTableWrap, #immAddWrap').hide();
        }
    }

    /* Click on resident */
    $(document).on('click', '.res-item', function () {
        selectResident($(this).data('id'), $(this).data('name'));
    });

    /* ════════════════════════
       LOAD RECORDS
    ════════════════════════ */
    function loadRecords() {
        if (!selectedId) return;

        dt.clear().draw();

        $.ajax({
            url: 'api/getImmunizationsByResident.php',
            data: { ajax: 'immunizations', resident_id: selectedId },
            dataType: 'json',
            success: function (res) {
                if (!res.success) return;
                (res.rows || []).forEach(row => {
                    dt.row.add(row);
                });
                dt.draw();
            }
        });
    }

    /* ════════════════════════
       ADD IMMUNIZATION
    ════════════════════════ */
    $('#immAddForm').on('submit', function (e) {
        e.preventDefault();

        if (!selectedId) {
            showAlert('Error', 'No resident selected.', 'danger'); return;
        }
        if (!$.trim($('#immVaccineName').val())) {
            showAlert('Validation', 'Vaccine name is required.', 'danger'); return;
        }
        if (!$.trim($('#immDateGiven').val())) {
            showAlert('Validation', 'Date Given is required.', 'danger'); return;
        }

        const $btn = $('#immSaveBtn');
        $btn.prop('disabled', true).text('Saving…');

        $.ajax({
            url: 'api/addImmunization.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (!res.success) {
                    showAlert('Error', res.message || 'Failed to add immunization.', 'danger');
                    return;
                }
                /* reset form fields (keep resident) */
                $('#immVaccineName').val('');
                $('#immDose').val('');
                $('#immDateGiven').val('');
                $('#immNextSchedule').val('');
                $('#immRemarks').val('');
                loadRecords();
            },
            error: () => showAlert('Error', 'Request failed. Please try again.', 'danger'),
            complete: () => $btn.prop('disabled', false).text('+ Add')
        });
    });

    /* ════════════════════════
       PRINT
    ════════════════════════ */
    function printReport() {
        if (!selectedId) return;

        /* grab table rows */
        const tableHtml = $('<table border="1" cellpadding="6" cellspacing="0" style="width:100%;border-collapse:collapse;font-size:12px;"></table>');
        tableHtml.append('<thead style="background:#f3f3f3;"><tr><th>Vaccine</th><th>Dose</th><th>Date Given</th><th>Next Schedule</th><th>Status</th></tr></thead>');
        const $tbody = $('<tbody></tbody>');

        dt.rows().data().each(function (row) {
            $tbody.append(`<tr>
                <td>${esc(row.vaccine || '')}</td>
                <td>${esc(row.dose || '—')}</td>
                <td>${esc(fmtDate(row.date_given))}</td>
                <td>${row.next_schedule ? esc(fmtDate(row.next_schedule)) : '—'}</td>
                <td>${esc(row.status || '—')}</td>
            </tr>`);
        });
        tableHtml.append($tbody);

        const w = window.open('', '_blank');
        w.document.write(`
            <html><head><title>Immunization Report</title>
            <style>body{font-family:Arial,sans-serif;padding:20px;}h2{margin:0 0 6px;}p{margin:0 0 10px;font-size:12px;color:#555;}</style>
            </head><body>
            <h2>Immunization Records</h2>
            <p><strong>Resident:</strong> ${esc(selectedName)}</p>
            <p><strong>Date Printed:</strong> ${new Date().toLocaleString('en-PH')}</p>
            ${tableHtml.prop('outerHTML')}
            <script>window.print();<\/script>
            </body></html>
        `);
        w.document.close();
    }

    $('#btnPrint, #btnPrintReport').on('click', printReport);

    /* ════════════════════════
       BOOT
    ════════════════════════ */
    /* Disable form until resident chosen */
    $('#immVaccineName, #immDose, #immDateGiven, #immNextSchedule, #immRemarks').prop('disabled', true);

    loadResidents();
    $('body').show();
});