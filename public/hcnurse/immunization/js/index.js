$(function () {
    let selectedResidentId = 0;
    let selectedResidentName = '';

    // Datepickers (no auto-focus, no auto-open)
    $("#immDateGiven, #immNextSchedule").datepicker({
        dateFormat: "mm/dd/yy",
        changeMonth: true,
        changeYear: true
    }).on("focus", function () {
        // optional: prevent auto showing on focus if you want:
        // $(this).datepicker("hide");
    });

    // DataTable
    const immTable = $("#immRecordsTable").DataTable({
        pageLength: 5,
        lengthChange: false,
        searching: false,
        info: false,
        ordering: true
    });

    function renderResidentList(residents) {
        const $box = $("#immResidentList");
        if (!residents.length) {
            $box.html(`<div class="p-3 text-sm text-gray-500">No residents found.</div>`);
            return;
        }

        let html = `<div class="divide-y">`;
        residents.forEach(r => {
            const active = (r.id === selectedResidentId) ? 'bg-blue-100' : 'bg-white';
            html += `
          <div class="immResidentItem ${active} cursor-pointer px-3 py-2 hover:bg-blue-50"
               data-id="${r.id}" data-name="${$("<div>").text(r.name).html()}">
            ${$("<div>").text(r.name).html()}
          </div>
        `;
        });
        html += `</div>`;
        $box.html(html);
    }

    function loadResidents(q = '') {
        $.ajax({
            url: "api/residentSearch.php",
            type: "GET",
            dataType: "json",
            data: { ajax: "residents", q: q, limit: 30 },
            success: function (resp) {
                if (resp.success) renderResidentList(resp.residents || []);
            }
        });
    }

    function setSelectedResident(id, name) {
        selectedResidentId = parseInt(id, 10) || 0;
        selectedResidentName = name || '—';

        $("#immResidentId").val(selectedResidentId);
        $("#immSelectedName").text(selectedResidentName);

        // enable buttons when selected
        $("#immAddBtn").prop("disabled", selectedResidentId <= 0);
        $("#immPrintBtn").prop("disabled", selectedResidentId <= 0);

        // highlight selected in list
        $("#immResidentList .immResidentItem").each(function () {
            const rid = parseInt($(this).data("id"), 10);
            $(this).toggleClass("bg-blue-100", rid === selectedResidentId);
        });

        loadImmunizations();
    }

    function loadImmunizations() {
        immTable.clear().draw();

        if (selectedResidentId <= 0) return;

        $.ajax({
            url: "api/getImmunizationsByResident.php",
            type: "GET",
            dataType: "json",
            data: { ajax: "immunizations", resident_id: selectedResidentId },
            success: function (resp) {
                if (!resp.success) return;

                (resp.rows || []).forEach(row => {
                    immTable.row.add([
                        `${escapeHtml(row.vaccine)}${row.dose ? ' <span class="text-gray-500 text-xs">(' + escapeHtml(row.dose) + ')</span>' : ''}`,
                        escapeHtml(formatSqlDate(row.date_given)),
                        row.next_schedule ? escapeHtml(formatSqlDate(row.next_schedule)) : 'N/A',
                        escapeHtml(row.status)
                    ]);
                });

                immTable.draw();
            }
        });
    }

    // Add immunization
    $("#immAddForm").on("submit", function (e) {
        e.preventDefault();

        if (selectedResidentId <= 0) {
            alert("Please select a resident first.");
            return;
        }

        const $btn = $("#immAddBtn");
        $btn.prop("disabled", true).text("Saving...");

        $.ajax({
            url: "api/addImmunization.php",
            type: "POST",
            dataType: "json",
            data: $(this).serialize(),
            success: function (resp) {
                if (!resp.success) {
                    alert(resp.message || "Failed to add immunization.");
                    return;
                }

                // clear form (keep resident)
                $("#immVaccineName").val("");
                $("#immDose").val("");
                $("#immDateGiven").val("");
                $("#immNextSchedule").val("");
                $("#immRemarks").val("");

                loadImmunizations();
            },
            error: function () {
                alert("Request failed. Please try again.");
            },
            complete: function () {
                $btn.prop("disabled", false).text("Add Immunization");
            }
        });
    });

    // Resident click
    $(document).on("click", ".immResidentItem", function () {
        setSelectedResident($(this).data("id"), $(this).data("name"));
    });

    // Search residents (debounced)
    let t = null;
    $("#immResidentSearch").on("input", function () {
        clearTimeout(t);
        const q = $(this).val();
        t = setTimeout(() => loadResidents(q), 250);
    });

    // Report (simple print)
    $("#immPrintBtn").on("click", function () {
        if (selectedResidentId <= 0) return;

        const tableHtml = $("#immRecordsTable").clone();
        // remove DataTables wrappers if any (best effort)
        tableHtml.removeAttr("id").removeClass("dataTable");

        const w = window.open("", "_blank");
        w.document.write(`
        <html>
          <head>
            <title>Immunization Report</title>
            <style>
              body { font-family: Arial, sans-serif; padding: 20px; }
              h2 { margin: 0 0 10px; }
              table { width: 100%; border-collapse: collapse; margin-top: 10px; }
              th, td { border: 1px solid #333; padding: 8px; text-align: left; }
              th { background: #f2f2f2; }
            </style>
          </head>
          <body>
            <h2>Immunization Records</h2>
            <div><strong>Resident:</strong> ${escapeHtml(selectedResidentName)}</div>
            <div><strong>Date Printed:</strong> ${new Date().toLocaleString()}</div>
            ${tableHtml.prop("outerHTML")}
            <script>window.print();<\/script>
          </body>
        </html>
      `);
        w.document.close();
    });

    // Helpers
    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"'`=\/]/g, function (s) {
            return ({
                "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;",
                "'": "&#39;", "/": "&#x2F;", "`": "&#x60;", "=": "&#x3D;"
            })[s];
        });
    }

    function formatSqlDate(sqlDate) {
        // expects YYYY-MM-DD
        if (!sqlDate) return '';
        const parts = sqlDate.split("-");
        if (parts.length !== 3) return sqlDate;
        return parts[1] + "/" + parts[2] + "/" + parts[0];
    }

    // Initial load
    loadResidents();
});