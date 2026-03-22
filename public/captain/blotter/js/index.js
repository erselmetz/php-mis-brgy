/**
 * Blotter Case Register — JS Controller
 * Replaces: public/secretary/blotter/js/index.js
 */

/* ─── Helpers ─── */
function dlgCfg(opts) {
    return Object.assign({
        autoOpen: false, modal: true, resizable: false, closeOnEscape: true,
        classes: { 'ui-dialog': '', 'ui-dialog-titlebar': '', 'ui-dialog-buttonpane': '' }
    }, opts);
}
function showAlert(title, msg, type) {
    const id = 'a_' + Date.now();
    const col = type === 'success' ? 'var(--ok-fg)' : type === 'danger' ? 'var(--danger-fg)' : 'var(--accent)';
    $('body').append(`<div id="${id}" title="${esc(title)}" style="display:none;">
        <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">${esc(msg)}</div>
    </div>`);
    $(`#${id}`).dialog(dlgCfg({ width: 400, buttons: { 'OK': function () { $(this).dialog('close').remove(); } } })).dialog('open');
}
function esc(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
function fmtDate(s) {
    if (!s) return '—';
    return new Date(s + 'T00:00:00').toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}
function fmtDateTime(s) {
    if (!s) return '—';
    const d = new Date(s);
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
        + ' ' + d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
}

const STATUS_CONF = {
    pending: { label: 'Pending', cls: 'bs-pending' },
    under_investigation: { label: 'Under Investigation', cls: 'bs-invest' },
    resolved: { label: 'Resolved', cls: 'bs-resolved' },
    dismissed: { label: 'Dismissed', cls: 'bs-dismissed' },
};
const SF_CLASS = {
    pending: 'sf-pending', under_investigation: 'sf-invest',
    resolved: 'sf-resolved', dismissed: 'sf-dismissed',
};

$(function () {

    /* ═══════════════════════════════════════
       DATATABLE + SEARCH + FILTER
    ═══════════════════════════════════════ */
    const table = $('#blotterTable').DataTable({
        pageLength: 25, order: [[3, 'desc']],
        dom: 'tip',
        language: {
            info: 'Showing _START_–_END_ of _TOTAL_ cases',
            paginate: { previous: '‹', next: '›' }
        }
    });

    $('#bltTableSearch').on('input', function () { table.search($(this).val()).draw(); });

    $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
        if (settings.nTable.id !== 'blotterTable') return true;
        const activeStatus = $('#statusFilters .sf-pill.active').data('status');
        if (!activeStatus) return true;
        const rowStatus = $($('#blotterTable tbody tr')[dataIndex]).data('status');
        return rowStatus === activeStatus;
    });

    $('#statusFilters').on('click', '.sf-pill', function () {
        $('#statusFilters .sf-pill').removeClass('active');
        $(this).addClass('active');
        table.draw();
    });

    /* ═══════════════════════════════════════
       ADD NEW CASE MODAL
    ═══════════════════════════════════════ */
    $('#addBlotterModal').dialog(dlgCfg({ width: 720 }));
    $('#openBlotterModalBtn').on('click', function () {
        // Reset form before opening
        $('#addBlotterForm')[0].reset();
        $('#addBlotterModal').dialog('open');
    });

    // Listen to the button click (type="button") — NOT form submit
    // This completely avoids any native form POST
    $('#submitAddBlotter').on('click', function () {
        const $btn = $(this);
        const $form = $('#addBlotterForm');
        const origTxt = $btn.html();

        // Client-side validation
        const complainant = $form.find('[name="complainant_name"]').val().trim();
        const respondent = $form.find('[name="respondent_name"]').val().trim();
        const date = $form.find('[name="incident_date"]').val().trim();
        const location = $form.find('[name="incident_location"]').val().trim();
        const description = $form.find('[name="incident_description"]').val().trim();

        if (!complainant || !respondent || !date || !location || !description) {
            showAlert('Validation', 'Please fill in all required fields.', 'danger');
            return;
        }

        $btn.prop('disabled', true).html('Saving…');

        $.ajax({
            url: 'add_blotter.php',
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    $('#addBlotterModal').dialog('close');
                    window.location.assign(window.location.href);
                } else {
                    $btn.prop('disabled', false).html(origTxt);
                    showAlert('Error', res.message || 'Failed to save case.', 'danger');
                }
            },
            error: function () {
                $btn.prop('disabled', false).html(origTxt);
                showAlert('Error', 'Failed to connect to server.', 'danger');
            }
        });
    });

    /* ═══════════════════════════════════════
       VIEW / EDIT CASE MODAL
    ═══════════════════════════════════════ */
    $('#viewBlotterModal').dialog(dlgCfg({
        width: 780,
        buttons: {},   // no default buttons — footer has its own
        open: function () { }
    }));
    // Close button
    $(document).on('click', '#vcCloseBtn', function () {
        $('#viewBlotterModal').dialog('close');
    });

    $(document).on('click', '.view-blotter-btn', function () {
        loadBlotterData($(this).data('id'));
    });

    function loadBlotterData(id) {
        $.getJSON(`get_blotter.php?id=${id}`, function (data) {
            if (data.error) { showAlert('Error', data.error, 'danger'); return; }
            populateViewModal(data);
            $('#viewBlotterModal').dialog('open');
        }).fail(() => showAlert('Error', 'Failed to load case data.', 'danger'));
    }

    function populateViewModal(d) {
        $('#vc-id').val(d.id);

        // Header
        $('#vc-case-no').text(d.case_number);
        $('#vc-parties').text((d.complainant_name || '—') + ' vs. ' + (d.respondent_name || '—'));
        $('#vc-date').text(d.incident_date ? fmtDate(d.incident_date) : '—');
        $('#vc-filed').text(d.created_by_name || '—');

        const sc = STATUS_CONF[d.status] || { label: d.status, cls: 'bs-dismissed' };
        $('#vc-status-badge').html(`<span class="bs ${sc.cls}">${sc.label}</span>`);
        $('#vc-status-select').val(d.status);
        $('#vc-resolved-date').val(d.resolved_date || '');

        // Complainant display
        $('#vc-comp-name-display').text(d.complainant_name || '—');
        $('#vc-comp-contact-display').text(d.complainant_contact || '—');
        $('#vc-comp-addr-display').text(d.complainant_address || '—');

        // Respondent display
        $('#vc-resp-name-display').text(d.respondent_name || '—');
        $('#vc-resp-contact-display').text(d.respondent_contact || '—');
        $('#vc-resp-addr-display').text(d.respondent_address || '—');

        // Incident display
        const rawDate = d.incident_date || '';
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        let formattedDate = '—';
        if (rawDate) {
            const parts = rawDate.split('-');
            if (parts.length === 3) {
                formattedDate = months[parseInt(parts[1]) - 1] + ' ' + parseInt(parts[2]) + ', ' + parts[0];
            }
        }
        $('#vc-inc-date-display').text(formattedDate);

        const rawTime = d.incident_time || '';
        let formattedTime = '—';
        if (rawTime) {
            const [h, m] = rawTime.split(':');
            const hr = parseInt(h);
            const ap = hr >= 12 ? 'PM' : 'AM';
            formattedTime = (hr % 12 || 12) + ':' + m + ' ' + ap;
        }
        $('#vc-inc-time-display').text(formattedTime);
        $('#vc-inc-loc-display').text(d.incident_location || '—');
        $('#vc-inc-desc-display').text(d.incident_description || '—');

        // Resolution block — show only if there's content
        if (d.resolution && d.resolution.trim() !== '') {
            $('#vc-resolution-display').text(d.resolution);
            $('#vc-res-date-display').text(
                d.resolved_date ? 'Resolved: ' + fmtDate(d.resolved_date) : 'Resolved date not set'
            );
            $('#vc-resolution-block').removeClass('empty');
        } else {
            $('#vc-resolution-block').addClass('empty');
        }

        // Footer meta
        const createdAt = d.created_at ? new Date(d.created_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
        const updatedAt = d.updated_at ? new Date(d.updated_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '—';
        $('#vc-footer-meta').text(
            d.case_number + '  ·  Created ' + createdAt + '  ·  Updated ' + updatedAt
        );

        // Archive button data
        $('#vcArchiveBtn').data('id', d.id).data('case', d.case_number)
            .attr('data-id', d.id).attr('data-case', d.case_number);

        // Hidden fields for full save (blotterForm serialize)
        $('#vc-comp-name').val(d.complainant_name || '');
        $('#vc-comp-addr').val(d.complainant_address || '');
        $('#vc-comp-contact').val(d.complainant_contact || '');
        $('#vc-resp-name').val(d.respondent_name || '');
        $('#vc-resp-addr').val(d.respondent_address || '');
        $('#vc-resp-contact').val(d.respondent_contact || '');
        $('#vc-inc-date').val(d.incident_date || '');
        $('#vc-inc-time').val(d.incident_time || '');
        $('#vc-inc-loc').val(d.incident_location || '');
        $('#vc-inc-desc').val(d.incident_description || '');
        $('#vc-status').val(d.status || 'pending');
        $('#vc-resolution').val(d.resolution || '');
        $('#vc-res-date').val(d.resolved_date || '');
    }

    // Quick status save
    $('#vcSaveStatus').on('click', function () {
        const id = $('#vc-id').val();
        if (!id || id === '0' || id === '') {
            showAlert('Error', 'No case selected. Please reopen the case and try again.', 'danger');
            return;
        }

        const status = $('#vc-status-select').val();
        const resDate = $('#vc-resolved-date').val();

        $.post('update_blotter.php', {
            id, status,
            resolved_date: status === 'resolved' ? resDate : '',
            csrf_token: $('[name="csrf_token"]').val(),
            complainant_name: $('#vc-comp-name').val(),
            respondent_name: $('#vc-resp-name').val(),
            incident_date: $('#vc-inc-date').val(),
            incident_location: $('#vc-inc-loc').val(),
            incident_description: $('#vc-inc-desc').val(),
            incident_time: $('#vc-inc-time').val(),
            complainant_address: $('#vc-comp-addr').val(),
            complainant_contact: $('#vc-comp-contact').val(),
            respondent_address: $('#vc-resp-addr').val(),
            respondent_contact: $('#vc-resp-contact').val(),
            resolution: $('#vc-resolution').val(),
        }, function (res) {
            if (res.success) {
                const sc = STATUS_CONF[status] || { label: status, cls: 'bs-dismissed' };
                $('#vc-status-badge').html(`<span class="bs ${sc.cls}">${sc.label}</span>`);
                $('#vc-status').val(status);
                showAlert('Saved', 'Status updated.', 'success');
                window.location.assign(window.location.href);
            } else {
                showAlert('Error', res.message || 'Update failed.', 'danger');
            }
        }, 'json');
    });

    // Full save (all fields)
    function saveBlotter() {
        const id = $('#vc-id').val();
        if (!id || id === '0' || id === '') {
            showAlert('Error', 'No case selected. Please reopen the case and try again.', 'danger');
            return;
        }

        $.post('update_blotter.php', $('#blotterForm').serialize(), function (res) {
            if (res.success) {
                $('#viewBlotterModal').dialog('close');
                showAlert('Saved', 'Case record updated.', 'success');
                window.location.assign(window.location.href);
            } else {
                showAlert('Error', res.message || 'Update failed.', 'danger');
            }
        }, 'json').fail(() => showAlert('Error', 'Failed to connect to server.', 'danger'));
    }

    /* ═══════════════════════════════════════
       ARCHIVE CASE
    ═══════════════════════════════════════ */
    $(document).on('click', '.archive-case-btn', function () {
        archiveCase($(this).data('id'), $(this).data('case'));
    });

    function archiveCase(id, caseNo) {
        if (!id || id === '0' || id === '') {
            showAlert('Error', 'Invalid case ID.', 'danger');
            return;
        }
        const dlgId = 'arc_' + Date.now();
        $('body').append(`<div id="${dlgId}" title="Archive Case" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);">
                Archive case <strong style="font-family:var(--f-mono);">${esc(caseNo)}</strong>?<br>
                <span style="font-size:11px;color:var(--ink-faint);">This can be reversed from the Archived Cases panel.</span>
            </div>
        </div>`);
        $(`#${dlgId}`).dialog(dlgCfg({
            width: 420,
            buttons: {
                'Archive': function () {
                    $(this).dialog('close').remove();
                    $.post('archive_api.php', { action: 'archive', blotter_id: id }, function (res) {
                        if (res.success) {
                            $('#viewBlotterModal').dialog('close');
                            showAlert('Archived', res.message, 'success');
                            window.location.assign(window.location.href);
                        } else {
                            showAlert('Error', res.message, 'danger');
                        }
                    }, 'json');
                },
                'Cancel': function () { $(this).dialog('close').remove(); }
            }
        })).dialog('open');
    }

    /* ═══════════════════════════════════════
       ARCHIVED CASES DIALOG
    ═══════════════════════════════════════ */
    $('#archivedCasesDialog').dialog(dlgCfg({
        width: 860,
        open: function () { loadArchivedCases(); }
    }));
    $('#btnArchivedCases').on('click', () => $('#archivedCasesDialog').dialog('open'));

    let arcTimer;
    $('#arcSearch').on('input', function () {
        clearTimeout(arcTimer);
        arcTimer = setTimeout(() => loadArchivedCases($(this).val()), 300);
    });

    function loadArchivedCases(search = '') {
        const $body = $('#arcBody');
        $body.html('<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--ink-faint);">Loading…</td></tr>');
        $.getJSON('archive_api.php', { search, limit: 100 }, function (res) {
            if (!res.success) {
                $body.html('<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--danger-fg);">Error loading archive.</td></tr>'); return;
            }
            const cases = res.blotters || [];
            const total = res.total || cases.length;

            $('#arc-total').text(total);
            $('#arc-latest').text(cases[0]?.archived_date || '—');

            if (!cases.length) {
                $body.html('<tr><td colspan="6" style="padding:24px;text-align:center;color:var(--ink-faint);font-style:italic;">No archived cases found.</td></tr>');
                $('#arcFooter').text('0 RECORDS'); return;
            }

            $body.empty();
            cases.forEach((c, i) => {
                $body.append(`<tr>
                    <td style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);text-align:right;">${i + 1}</td>
                    <td style="font-family:var(--f-mono);font-weight:700;color:var(--accent);">${esc(c.case_number)}</td>
                    <td>
                        <div style="font-weight:500;">${esc(c.parties)}</div>
                        <div style="font-size:10.5px;color:var(--ink-faint);">${esc(c.incident || '')}</div>
                    </td>
                    <td>—</td>
                    <td style="font-family:var(--f-mono);font-size:11px;color:var(--ink-muted);">${esc(c.archived_date)}</td>
                    <td style="text-align:center;">
                </tr>`);
            });
            $('#arcFooter').text(total + ' ARCHIVED CASE' + (total !== 1 ? 'S' : ''));
        });
    }

    $(document).on('click', '.restore-case-btn', function () {
        const id = $(this).data('id');
        const caseNo = $(this).data('case');
        const dlgId = 'rst_' + Date.now();
        $('body').append(`<div id="${dlgId}" title="Restore Case" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);">
                Restore case <strong style="font-family:var(--f-mono);">${esc(caseNo)}</strong> to active register?
            </div>
        </div>`);
        $(`#${dlgId}`).dialog(dlgCfg({
            width: 420,
            buttons: {
                'Restore': function () {
                    $(this).dialog('close').remove();
                    $.post('archive_api.php', { action: 'restore', blotter_id: id }, function (res) {
                        showAlert(res.success ? 'Restored' : 'Error', res.message, res.success ? 'success' : 'danger');
                        if (res.success) loadArchivedCases($('#arcSearch').val());
                    }, 'json');
                },
                'Cancel': function () { $(this).dialog('close').remove(); }
            }
        })).dialog('open');
    });

    /* ═══════════════════════════════════════
       CASE HISTORY DIALOG
    ═══════════════════════════════════════ */
    $('#caseHistoryDialog').dialog(dlgCfg({
        width: 860,
        open: function () { loadCaseHistory(); }
    }));
    $('#btnCaseHistory').on('click', () => $('#caseHistoryDialog').dialog('open'));

    let histTimer;
    $('#histSearch').on('input', function () {
        clearTimeout(histTimer);
        histTimer = setTimeout(() => loadCaseHistory($(this).val()), 300);
    });

    function loadCaseHistory(search = '') {
        const $body = $('#histBody');
        $body.html('<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--ink-faint);">Loading…</td></tr>');
        const url = search ? `history_api.php?case_number=${encodeURIComponent(search)}` : 'history_api.php';
        $.getJSON(url, function (res) {
            if (!res.success) {
                $body.html('<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--danger-fg);">Error loading history.</td></tr>'); return;
            }
            if (!res.history.length) {
                $body.html('<tr><td colspan="5" style="padding:24px;text-align:center;color:var(--ink-faint);font-style:italic;">No history found.</td></tr>');
                $('#histFooter').text('0 RECORDS'); return;
            }

            $body.empty();
            res.history.forEach(h => {
                const actionMap = {
                    status_changed: ['ap-status', 'Status Changed'],
                    archived: ['ap-archived', 'Archived'],
                    restored: ['ap-restored', 'Restored'],
                    created: ['ap-other', 'Created'],
                    updated: ['ap-other', 'Updated'],
                };
                const [apCls, apLbl] = actionMap[h.action_type] || ['ap-other', h.action_type];

                let flow = '—';
                if (h.old_status && h.new_status && h.old_status !== h.new_status) {
                    const oSF = SF_CLASS[h.old_status] || 'sf-dismissed';
                    const nSF = SF_CLASS[h.new_status] || 'sf-dismissed';
                    flow = `<div class="status-flow">
                        <span class="sf-badge ${oSF}">${esc(h.old_status_display || h.old_status)}</span>
                        <span class="sf-arrow">→</span>
                        <span class="sf-badge ${nSF}">${esc(h.new_status_display || h.new_status)}</span>
                    </div>`;
                } else if (h.new_status) {
                    const nSF = SF_CLASS[h.new_status] || 'sf-dismissed';
                    flow = `<span class="sf-badge ${nSF}">${esc(h.new_status_display || h.new_status)}</span>`;
                }

                $body.append(`<tr>
                    <td style="font-family:var(--f-mono);font-weight:700;color:var(--accent);">${esc(h.case_number)}</td>
                    <td><span class="action-pill ${apCls}">${apLbl}</span></td>
                    <td>${flow}</td>
                    <td style="font-size:12px;color:var(--ink-muted);">${esc(h.user_name || '—')}</td>
                    <td style="font-family:var(--f-mono);font-size:11px;color:var(--ink-faint);white-space:nowrap;">${fmtDateTime(h.created_at)}</td>
                </tr>`);
            });
            $('#histFooter').text(res.total + ' RECORD' + (res.total !== 1 ? 'S' : ''));
        });
    }

});