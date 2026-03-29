/**
 * Resident Register — JS Controller
 * Replaces: public/secretary/resident/js/index.js
 *
 * All backend calls are unchanged. Only UI/class names updated
 * to match the government-official redesign.
 */

/* ──────────────────────────────────────────────
   DIALOG CONFIG HELPER
────────────────────────────────────────────── */
function dialogCfg(opts) {
    return Object.assign({
        autoOpen: false,
        modal: true,
        resizable: false,
        closeOnEscape: true,
        classes: {
            'ui-dialog':            '',
            'ui-dialog-titlebar':   '',
            'ui-dialog-buttonpane': ''
        }
    }, opts);
}

function showAlert(title, msg, type) {
    const id  = 'dlg_' + Date.now();
    const col = type === 'success' ? 'var(--ok-fg)' : type === 'danger' ? 'var(--danger-fg)' : 'var(--accent)';
    $('body').append(`<div id="${id}" title="${escHtml(title)}" style="display:none;">
        <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid ${col};background:var(--paper);">
            ${escHtml(msg)}
        </div>
    </div>`);
    $(`#${id}`).dialog(dialogCfg({
        width: 400,
        buttons: {
            'OK': function () { $(this).dialog('close').remove(); }
        }
    })).dialog('open');
    return id;
}

function escHtml(s) {
    const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML;
}

/* ──────────────────────────────────────────────
   DATATABLES INIT
────────────────────────────────────────────── */
$(function () {

    const table = $('#residentsTable').DataTable({
        pageLength: 25,
        order: [[0, 'asc']],
        dom: 'tip',                    // no built-in search / length
        language: {
            info: 'Showing _START_–_END_ of _TOTAL_ residents',
            infoEmpty: 'No residents found',
            paginate: { previous: '‹', next: '›' }
        }
    });

    // Wire our own search input to DataTables
    $('#resTableSearch').on('input', function () {
        table.search($(this).val()).draw();
    });

    /* ══════════════════════════════════════════
       VIEW MODAL
    ══════════════════════════════════════════ */
    $('#viewResidentModal').dialog(dialogCfg({
        width: 680,
        buttons: { 'Close': function () { $(this).dialog('close'); } }
    }));

    $(document).on('click', '.view-resident-btn', function () {
        loadResidentForView($(this).data('id'));
        $('#viewResidentModal').dialog('open');
    });

    function loadResidentForView(id) {
        $.getJSON(`get_resident.php?id=${id}`, function (data) {
            if (data.error) { showAlert('Error', data.error, 'danger'); return; }

            const nameParts = [data.first_name, data.middle_name, data.last_name, data.suffix].filter(Boolean);
            const fullName  = nameParts.join(' ');
            const age       = data.birthdate ? calcAge(data.birthdate) : null;

            $('#view-id-num').text(String(data.id).padStart(4, '0'));
            $('#view-full-name').text(fullName || '—');

            // Tags
            const tags = [];
            if (age)                            tags.push(`<span class="view-tag accent">${age} yrs old</span>`);
            if (data.gender)                    tags.push(`<span class="view-tag">${escHtml(data.gender)}</span>`);
            if (data.civil_status)              tags.push(`<span class="view-tag">${escHtml(data.civil_status)}</span>`);
            if (data.voter_status === 'Yes')    tags.push(`<span class="view-tag accent">Registered Voter</span>`);
            if (data.disability_status === 'Yes') tags.push(`<span class="view-tag">PWD</span>`);
            $('#view-tags').html(tags.join(''));

            $('#view-birthdate').text(data.birthdate ? fmtDate(data.birthdate) : '—');
            $('#view-age').text(age ? age + ' years old' : '—');
            $('#view-birthplace').text(data.birthplace || '—');
            $('#view-civil-status').text(data.civil_status || '—');
            $('#view-religion').text(data.religion || '—');
            $('#view-citizenship').text(data.citizenship || '—');
            $('#view-contact').text(data.contact_no || '—');
            $('#view-address').text(data.address || '—');
            $('#view-occupation').text(data.occupation || '—');
            $('#view-voter-status').text(data.voter_status || '—');
            $('#view-disability-status').text(data.disability_status || '—');
            $('#view-household-id').text(data.household_display || '—');
            $('#view-remarks').text(data.remarks || '—');
        });
    }

    /* ══════════════════════════════════════════
       EDIT MODAL
    ══════════════════════════════════════════ */
    $('#editResidentModal').dialog(dialogCfg({
        width: 700,
        open: function () { loadHouseholdsForDropdown('edit'); },
        buttons: {
            'Save Changes': function () { saveResidentEdits(); },
            'Cancel':       function () { $(this).dialog('close'); }
        }
    }));

    $(document).on('click', '.edit-resident-btn', function () {
        loadResidentForEdit($(this).data('id'));
        $('#editResidentModal').dialog('open');
    });

    function loadResidentForEdit(id) {
        loadHouseholdsForDropdown('edit');
        $.getJSON(`get_resident.php?id=${id}`, function (data) {
            if (data.error) { showAlert('Error', data.error, 'danger'); return; }
            $('#edit-resident-id').val(data.id || '');
            $('#edit-household-id').val(data.household_id || '');
            $('#edit-household-search').val(data.household_display || '');
            $('#edit-first-name').val(data.first_name || '');
            $('#edit-middle-name').val(data.middle_name || '');
            $('#edit-last-name').val(data.last_name || '');
            $('#edit-suffix').val(data.suffix || '');
            $('#edit-gender').val(data.gender || 'Male');
            $('#edit-birthdate').val(data.birthdate || '');
            $('#edit-birthplace').val(data.birthplace || '');
            $('#edit-civil-status').val(data.civil_status || 'Single');
            $('#edit-religion').val(data.religion || '');
            $('#edit-occupation').val(data.occupation || '');
            $('#edit-citizenship').val(data.citizenship || 'Filipino');
            $('#edit-contact-no').val(data.contact_no || '');
            $('#edit-address').val(data.address || '');
            $('#edit-voter-status').val(data.voter_status || 'No');
            $('#edit-disability-status').val(data.disability_status || 'No');
            $('#edit-remarks').val(data.remarks || '');
        });
    }

    function saveResidentEdits() {
        const formData = new FormData(document.getElementById('editResidentForm'));
        const data = Object.fromEntries(formData.entries());
        $.ajax({
            url: 'update_resident.php', type: 'POST', data,
            dataType: 'json',
            success: function (res) {
                showAlert(res.success ? 'Saved' : 'Error', res.message, res.success ? 'success' : 'danger');
                if (res.success) { $('#editResidentModal').dialog('close'); location.reload(); }
            },
            error: () => showAlert('Error', 'Failed to connect to server.', 'danger')
        });
    }

    /* ══════════════════════════════════════════
       ADD MODAL
    ══════════════════════════════════════════ */
    $('#addResidentModal').dialog(dialogCfg({
        width: 700,
        open: function () { loadHouseholdsForDropdown('add'); }
    }));
    $('#openResidentModalBtn').on('click', () => $('#addResidentModal').dialog('open'));

    /* ══════════════════════════════════════════
       ARCHIVE REGISTRY DIALOG (Residents + Households)
    ══════════════════════════════════════════ */
    $('#archivedResidentsDialog').dialog(dialogCfg({
        width: 780,
        open: function () {
            loadArchivedResidents();
            loadArchivedHouseholds();
        }
    }));
    $('#archiveResidentsBtn').on('click', () => $('#archivedResidentsDialog').dialog('open'));

    // Tab switching
    $(document).on('click', '.arc-tab', function () {
        const tab = $(this).data('tab');
        $('.arc-tab').removeClass('active');
        $(this).addClass('active');
        $('.arc-pane').removeClass('active');
        $(`#arc-pane-${tab}`).addClass('active');
    });

    // ── Archive a resident ──
    $(document).on('click', '.archive-resident-btn', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');
        const dlgId = 'cdlg_' + Date.now();
        $('body').append(`<div id="${dlgId}" title="Confirm Archive" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);">
                Archive <strong>${escHtml(name)}</strong> from the register?<br>
                <span style="font-size:11px;color:var(--ink-faint);">This can be reversed from the Archive Registry.</span>
            </div>
        </div>`);
        $(`#${dlgId}`).dialog(dialogCfg({
            width: 420,
            buttons: {
                'Archive': function () {
                    $(this).dialog('close').remove();
                    $.post('archive_api.php', { action: 'archive', resident_id: id }, function (res) {
                        showAlert(res.success ? 'Archived' : 'Error', res.message, res.success ? 'success' : 'danger');
                        if (res.success) location.reload();
                    }, 'json');
                },
                'Cancel': function () { $(this).dialog('close').remove(); }
            }
        })).dialog('open');
    });

    // ── Restore resident ──
    $(document).on('click', '.restore-res-btn', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');
        const dlgId = 'rdlg_' + Date.now();
        $('body').append(`<div id="${dlgId}" title="Restore Resident" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);">
                Restore <strong>${escHtml(name)}</strong> to the active register?
            </div>
        </div>`);
        $(`#${dlgId}`).dialog(dialogCfg({
            width: 420,
            buttons: {
                'Restore': function () {
                    $(this).dialog('close').remove();
                    $.post('archive_api.php', { action: 'restore', resident_id: id }, function (res) {
                        showAlert(res.success ? 'Restored' : 'Error', res.message, res.success ? 'success' : 'danger');
                        if (res.success) { loadArchivedResidents(); location.reload(); }
                    }, 'json');
                },
                'Cancel': function () { $(this).dialog('close').remove(); }
            }
        })).dialog('open');
    });

    // ── Restore household ──
    $(document).on('click', '.restore-hh-btn', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');
        const dlgId = 'rhhd_' + Date.now();
        $('body').append(`<div id="${dlgId}" title="Restore Household" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);">
                Restore household <strong>${escHtml(name)}</strong> to the active register?
            </div>
        </div>`);
        $(`#${dlgId}`).dialog(dialogCfg({
            width: 420,
            buttons: {
                'Restore': function () {
                    $(this).dialog('close').remove();
                    $.post('household_api.php', { action: 'restore', id }, function (res) {
                        showAlert(res.success ? 'Restored' : 'Error', res.message, res.success ? 'success' : 'danger');
                        if (res.success) loadArchivedHouseholds();
                    }, 'json');
                },
                'Cancel': function () { $(this).dialog('close').remove(); }
            }
        })).dialog('open');
    });

    // ── Search residents archive ──
    let arcResTimer;
    $('#arcResSearch').on('input', function () {
        clearTimeout(arcResTimer);
        arcResTimer = setTimeout(() => loadArchivedResidents($(this).val()), 300);
    });

    // ── Search households archive ──
    let arcHhTimer;
    $('#arcHhSearch').on('input', function () {
        clearTimeout(arcHhTimer);
        arcHhTimer = setTimeout(() => loadArchivedHouseholds($(this).val()), 300);
    });

    // ── Load archived residents ──
    function loadArchivedResidents(search = '') {
        $.getJSON('archive_api.php', { search, limit: 100, offset: 0 }, function (res) {
            const $body = $('#arcResBody');
            if (!res.success) {
                $body.html('<tr><td colspan="4" class="archive-empty">Error loading archive.</td></tr>');
                return;
            }

            // Stats
            const total = res.total || 0;
            const now   = new Date();
            const thisMonth = res.residents.filter(r => {
                const d = new Date(r.archived_date);
                return d.getFullYear() === now.getFullYear() && d.getMonth() === now.getMonth();
            }).length;
            const latest = res.residents[0]
                ? new Date(res.residents[0].archived_date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
                : '—';

            $('#arc-res-total').text(total);
            $('#arc-res-month').text(thisMonth);
            $('#arc-res-latest').text(latest);
            $('#arc-res-count').text(total);

            if (!res.residents.length) {
                $body.html('<tr><td colspan="4" class="archive-empty">No archived residents found.</td></tr>');
                $('#arcResFooter').text('0 RECORDS');
                return;
            }

            $body.empty();
            res.residents.forEach((r, i) => {
                $body.append(`<tr>
                    <td style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);text-align:right;">${String(r.id).padStart(4,'0')}</td>
                    <td style="font-weight:500;">${escHtml(r.full_name)}</td>
                    <td style="font-family:var(--f-mono);font-size:11px;color:var(--ink-muted);">${r.archived_date}</td>
                    <td style="text-align:center;">
                        <button class="act-btn restore-res-btn"
                            style="color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 30%,transparent);background:#fff;"
                            data-id="${r.id}" data-name="${escHtml(r.full_name)}">
                            Restore
                        </button>
                    </td>
                </tr>`);
            });
            $('#arcResFooter').text(total + ' ARCHIVED RESIDENT' + (total !== 1 ? 'S' : ''));
        });
    }

    // ── Load archived households ──
    function loadArchivedHouseholds(search = '') {
        // household_api.php returns archived households (archived_at IS NOT NULL)
        $.getJSON('household_api.php', { archived: 1, search, limit: 100 }, function (res) {
            const $body = $('#arcHhBody');

            if (!res.success) {
                $body.html('<tr><td colspan="6" class="archive-empty">Error loading archive.</td></tr>');
                return;
            }

            const households = res.households || [];
            const total      = res.total || households.length;

            // Stats
            const totalMembers = households.reduce((s, h) => s + (parseInt(h.total_members) || 0), 0);
            const latest = households[0]
                ? new Date(households[0].archived_at).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' })
                : '—';

            $('#arc-hh-total').text(total);
            $('#arc-hh-members').text(totalMembers);
            $('#arc-hh-latest').text(latest);
            $('#arc-hh-count').text(total);

            if (!households.length) {
                $body.html('<tr><td colspan="6" class="archive-empty">No archived households found.</td></tr>');
                $('#arcHhFooter').text('0 RECORDS');
                return;
            }

            $body.empty();
            households.forEach((h, i) => {
                $body.append(`<tr>
                    <td style="font-family:var(--f-mono);font-size:10px;color:var(--ink-faint);text-align:right;">${i + 1}</td>
                    <td style="font-family:var(--f-mono);font-weight:700;color:var(--accent);">${escHtml(h.household_no)}</td>
                    <td style="font-weight:500;">${escHtml(h.head_name || '—')}</td>
                    <td style="font-size:12px;color:var(--ink-muted);">${escHtml(h.address || '—')}</td>
                    <td style="text-align:center;font-family:var(--f-mono);font-size:12px;">${h.total_members || 0}</td>
                    <td style="text-align:center;">
                        <button class="act-btn restore-hh-btn"
                            style="color:var(--ok-fg);border-color:color-mix(in srgb,var(--ok-fg) 30%,transparent);background:#fff;"
                            data-id="${h.id}" data-name="${escHtml(h.household_no)}">
                            Restore
                        </button>
                    </td>
                </tr>`);
            });
            $('#arcHhFooter').text(total + ' ARCHIVED HOUSEHOLD' + (total !== 1 ? 'S' : ''));
        });
    }


    /* ══════════════════════════════════════════
       HOUSEHOLD MANAGEMENT
    ══════════════════════════════════════════ */
    $('#householdManagementModal').dialog(dialogCfg({
        width: 720,
        open: function () { loadHouseholds(); }
    }));
    $('#manageHouseholdsBtn').on('click', () => $('#householdManagementModal').dialog('open'));

    let hhSearchTimer;
    $('#householdSearchInput').on('input', function () {
        clearTimeout(hhSearchTimer);
        hhSearchTimer = setTimeout(() => loadHouseholds($(this).val()), 300);
    });

    function loadHouseholds(search = '') {
        $.getJSON('household_api.php', { search, limit: 50 }, function (res) {
            const $list = $('#householdList');
            if (!res.success || !res.households.length) {
                $list.html('<div style="padding:32px;text-align:center;color:var(--ink-faint);font-style:italic;font-size:12px;">No households found.</div>');
                return;
            }
            $list.empty();
            res.households.forEach(hh => {
                $list.append(`
                    <div class="hh-item">
                        <div>
                            <div class="hh-no">${escHtml(hh.household_no)}</div>
                            <div class="hh-addr">${escHtml(hh.address)}</div>
                            <div class="hh-head">Head: ${escHtml(hh.head_name || '—')} · ${hh.total_members} member${hh.total_members !== 1 ? 's' : ''}</div>
                        </div>
                        <div class="hh-actions">
                            <button class="act-btn act-edit edit-hh-btn btn-sm"
                                data-hh='${JSON.stringify(hh).replace(/'/g,"&#39;")}'>Edit</button>
                            <button class="act-btn act-archive del-hh-btn btn-sm"
                                data-id="${hh.id}" data-name="${escHtml(hh.household_no)}">Archive</button>
                        </div>
                    </div>
                `);
            });
        });
    }

    // Create household button
    $('#createHouseholdBtn').on('click', function () {
        resetHouseholdForm();
        loadResidentsForHeadDropdown();
        $('#householdFormModal').dialog('option', 'title', 'New Household Record');
        $('#householdFormModal').dialog('open');
    });

    // Edit household
    $(document).on('click', '.edit-hh-btn', function () {
        const hh = JSON.parse($(this).attr('data-hh').replace(/&#39;/g, "'"));
        $('#householdFormId').val(hh.id);
        $('#householdFormNo').val(hh.household_no);
        $('#householdFormAddress').val(hh.address);
        $('#householdFormHeadId').val(hh.head_id || '');
        $('#householdFormHeadSearch').val(hh.head_name || '');
        $('#householdFormModal').dialog('option', 'title', 'Edit Household — ' + hh.household_no);
        $('#householdFormModal').dialog('open');
        loadResidentsForHeadDropdown();
    });

    // Archive household
    $(document).on('click', '.del-hh-btn', function () {
        const id   = $(this).data('id');
        const name = $(this).data('name');
        const dlgId = 'delhh_' + Date.now();
        $('body').append(`<div id="${dlgId}" title="Archive Household" style="display:none;">
            <div style="padding:18px 20px;font-size:13px;color:var(--ink);">
                Archive household <strong>${escHtml(name)}</strong>?<br>
                <span style="font-size:11px;color:var(--ink-faint);">Households with active residents cannot be archived.</span>
            </div>
        </div>`);
        $(`#${dlgId}`).dialog(dialogCfg({
            width: 420,
            buttons: {
                'Archive': function () {
                    $(this).dialog('close').remove();
                    $.post('household_api.php', { action: 'archive', id }, function (res) {
                        showAlert(res.success ? 'Archived' : 'Error', res.message, res.success ? 'success' : 'danger');
                        if (res.success) loadHouseholds($('#householdSearchInput').val());
                    }, 'json');
                },
                'Cancel': function () { $(this).dialog('close').remove(); }
            }
        })).dialog('open');
    });

    // Household form modal
    $('#householdFormModal').dialog(dialogCfg({
        width: 500,
        buttons: {
            'Save Household': function () { saveHousehold(); },
            'Cancel':         function () { $(this).dialog('close'); }
        }
    }));

    function resetHouseholdForm() {
        $('#householdForm')[0].reset();
        $('#householdFormId').val('');
        $('#householdFormHeadId').val('');
        $('#householdFormHeadSearch').val('');
        $('#householdFormHeadContainer').show();
    }

    function saveHousehold() {
        const data = Object.fromEntries(new FormData(document.getElementById('householdForm')).entries());
        const isEdit = !!data.id;
        data.action = isEdit ? 'update' : 'create';
        $.post('household_api.php', data, function (res) {
            showAlert(res.success ? 'Saved' : 'Error', res.message, res.success ? 'success' : 'danger');
            if (res.success) { $('#householdFormModal').dialog('close'); loadHouseholds(); }
        }, 'json').fail(() => showAlert('Error', 'Failed to save household.', 'danger'));
    }

    /* ══════════════════════════════════════════
       HOUSEHOLD INLINE SEARCH DROPDOWNS
    ══════════════════════════════════════════ */
    let allHouseholds = [];

    function loadHouseholdsForDropdown(prefix) {
        $.getJSON('household_api.php', { limit: 1000 }, function (res) {
            if (!res.success) return;
            allHouseholds = res.households;
            populateHHDropdown(prefix);
        });
    }

    function populateHHDropdown(prefix) {
        const $dd  = $(`#${prefix}-household-dropdown`);
        const $sel = $(`#${prefix}-household-id`);
        $dd.empty();
        $sel.empty().append('<option value="">— None —</option>');
        allHouseholds.forEach(hh => {
            const label = `${hh.household_no} — ${hh.head_name} (${hh.address})`;
            $sel.append(`<option value="${hh.id}">${escHtml(label)}</option>`);
            $dd.append(`<div class="hh-opt" data-id="${hh.id}" data-text="${escHtml(label)}">
                <div class="opt-main">${escHtml(hh.household_no)}</div>
                <div class="opt-sub">${escHtml(hh.head_name)} · ${escHtml(hh.address)}</div>
            </div>`);
        });
    }

    // Search filter for HH dropdowns
    $(document).on('input', '#edit-household-search, #add-household-search', function () {
        const prefix = this.id.startsWith('edit') ? 'edit' : 'add';
        const q = $(this).val().toLowerCase();
        const $dd = $(`#${prefix}-household-dropdown`);
        if (!q) { $(`#${prefix}-household-id`).val(''); $dd.removeClass('open'); return; }
        $dd.find('.hh-opt').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
        $dd.addClass('open');
    });

    $(document).on('click', '#edit-household-dropdown .hh-opt, #add-household-dropdown .hh-opt', function () {
        const isEdit = $(this).closest('#edit-household-dropdown').length;
        const prefix = isEdit ? 'edit' : 'add';
        const id   = $(this).data('id');
        const text = $(this).data('text');
        $(`#${prefix}-household-id`).val(id);
        $(`#${prefix}-household-search`).val(text);
        $(`#${prefix}-household-dropdown`).removeClass('open');
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('#edit-household-search, #edit-household-dropdown, #add-household-search, #add-household-dropdown').length) {
            $('.hh-dropdown').removeClass('open');
        }
    });

    /* ══════════════════════════════════════════
       RESIDENT SEARCH FOR HH HEAD
    ══════════════════════════════════════════ */
    function loadResidentsForHeadDropdown() {
        $.getJSON('get_residents_for_head.php', { limit: 1000 }, function (res) {
            if (!res.success) return;
            const $dd = $('#householdFormHeadDropdown');
            $dd.empty();
            res.residents.forEach(r => {
                $dd.append(`<div class="hh-opt" data-id="${r.id}" data-name="${escHtml(r.full_name)}">
                    <div class="opt-main">${escHtml(r.full_name)}</div>
                    <div class="opt-sub">${escHtml(r.address || 'No address')} · Age ${r.age || '?'}</div>
                </div>`);
            });
        });
    }

    $(document).on('input', '#householdFormHeadSearch', function () {
        const q = $(this).val().toLowerCase();
        const $dd = $('#householdFormHeadDropdown');
        if (!q) { $('#householdFormHeadId').val(''); $dd.removeClass('open'); return; }
        $dd.find('.hh-opt').each(function () {
            $(this).toggle($(this).text().toLowerCase().includes(q));
        });
        $dd.addClass('open');
    });

    $(document).on('click', '#householdFormHeadDropdown .hh-opt', function () {
        $('#householdFormHeadId').val($(this).data('id'));
        $('#householdFormHeadSearch').val($(this).data('name'));
        $('#householdFormHeadDropdown').removeClass('open');
    });

    /* ══════════════════════════════════════════
       UTILITIES
    ══════════════════════════════════════════ */
    function calcAge(dob) {
        if (!dob) return null;
        const d = new Date(dob), n = new Date();
        let age = n.getFullYear() - d.getFullYear();
        if (n.getMonth() < d.getMonth() || (n.getMonth() === d.getMonth() && n.getDate() < d.getDate())) age--;
        return age;
    }

    function fmtDate(s) {
        if (!s) return '—';
        const d = new Date(s + 'T00:00:00');
        return d.toLocaleDateString('en-PH', { month: 'long', day: 'numeric', year: 'numeric' });
    }

});