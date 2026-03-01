// Sub-type options per main type (pwede mong i-edit)
const SUBTYPE_OPTIONS = {
    immunization: [
        { value: "all", label: "All" },
        { value: "child", label: "Child" },
        { value: "adult", label: "Adult" },
        { value: "pregnant", label: "Pregnant" }
    ],
    maternal: [
        { value: "all", label: "All" },
        { value: "mother_only", label: "Mother Only" },
        { value: "child_only", label: "Child Only" },
        { value: "mother_child", label: "Mother & Child" }
    ],
    family_planning: [
        { value: "all", label: "All" },
        { value: "pills", label: "Pills" },
        { value: "injectable", label: "Injectable" },
        { value: "implant", label: "Implant" },
        { value: "iud", label: "IUD" }
    ],
    prenatal: [
        { value: "all", label: "All" },
        { value: "prenatal", label: "Prenatal" },
    ],
    postnatal: [
        { value: "all", label: "All" },
        { value: "postnatal", label: "Postnatal" }
    ],
    child_nutrition: [
        { value: "all", label: "All" },
        { value: "supplementation", label: "Supplementation" },
        { value: "deworming", label: "Deworming" },
        { value: "weighing", label: "Weighing" }
    ],
};

/**
 * Initialize modal dialog (jQuery UI)
 * You can customize the form fields and buttons as needed.
 */
$("#viewConsultationModal").dialog({
    autoOpen: false,
    modal: true,
    width: 600,
    resizable: false
});
$("#editConsultationModal").dialog({
    autoOpen: false,
    modal: true,
    width: 800,
    resizable: true,
    classes: {
        'ui-dialog': 'rounded-lg shadow-lg',
        'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
        'ui-dialog-title': 'font-semibold',
        'ui-dialog-buttonpane': 'text-white rounded-b-lg'
    },
    show: { effect: "fadeIn", duration: 200 },
    hide: { effect: "fadeOut", duration: 200 },
    buttons: {
        "Save Changes": function () {
            $("#editConsultationForm").trigger("submit");
        },
        Cancel: function () {
            $(this).dialog("close");
        }
    },
    open: function () {
        $(this).find('[tabindex="0"]').focus();
        $(this).find(':input').blur();

    }
});

// submit form
$("#editConsultationForm").on("submit", function (e) {
    e.preventDefault();

    const id = $("#edit_id").val(); // hidden input; set this when edit modal opens
    const payload = $(this).serialize() + "&id=" + encodeURIComponent(id);
    console.log("Submitting update:", payload);

    $.ajax({
        url: "api/health_records_api.php?id=" + id + "&action=update&type=" + encodeURIComponent(HEALTH_RECORD_TYPE),
        type: "POST",
        data: payload,
        dataType: "json",
        success: function (res) {
            if (res.status !== "ok") {
                showDialog("Message", res.message || "Update failed");
                return;
            }
            $("#editConsultationModal").dialog("close");
            showDialog("Message", "Update successful");
            loadRecords(); // refresh table
        },
        error: function (xhr) {
            console.log(xhr.responseText);
            alert("Server error");
        }
    });
});

/**
 * UI initialization and event handlers
 * These handlers manage the filter buttons, search input, and dynamically generated view/edit buttons in the records table.
 * @param {*} period 
 */
function setActivePeriod(period) {
    document.querySelectorAll(".periodBtn").forEach(btn => {
        const isActive = btn.dataset.period === period;
        btn.classList.toggle("bg-theme-primary", isActive);
        btn.classList.toggle("text-white", isActive);
        btn.classList.toggle("border-theme-primary", isActive);
        if (!isActive) {
            btn.classList.remove("bg-theme-primary", "text-white", "border-theme-primary");
        }
    });

    const monthPicker = document.getElementById("monthPicker");
    monthPicker.closest("div").classList.toggle("opacity-50", period !== "monthly");
    monthPicker.disabled = period !== "monthly";
}

function fillSubTypes() {
    const sel = document.getElementById("subTypeSelect");
    sel.innerHTML = "";
    const opts = SUBTYPE_OPTIONS[HEALTH_RECORD_TYPE] || [{
        value: "all",
        label: "All"
    }];
    opts.forEach(o => {
        const opt = document.createElement("option");
        opt.value = o.value;
        opt.textContent = o.label;
        sel.appendChild(opt);
    });
    sel.value = INIT_FILTERS.sub || "all";

    // update header label
    const selectedLabel = sel.options[sel.selectedIndex]?.textContent || "All";
    document.getElementById("currentSubTypeLabel").textContent = selectedLabel.replace(" Only", "");
}

function updateUrlAndReload() {
    const period = window.__period || INIT_FILTERS.period || "all";
    const month = document.getElementById("monthPicker").value || INIT_FILTERS.month;
    const sub = document.getElementById("subTypeSelect").value || "all";
    const q = document.getElementById("searchInput").value || "";

    const params = new URLSearchParams();
    params.set("type", HEALTH_RECORD_TYPE);
    params.set("period", period);
    if (period === "monthly") params.set("month", month);
    if (q.trim() !== "") params.set("q", q.trim());
    if (sub !== "all") params.set("sub", sub);

    // change URL (shareable)
    window.history.replaceState({}, "", "?" + params.toString());

    if (typeof loadRecords === 'function') {
        loadRecords({ type: HEALTH_RECORD_TYPE, period, month, sub, q });
    }
}

function openViewModal(id) {

    $.getJSON("api/health_records_api.php", {
        action: "get",
        type: HEALTH_RECORD_TYPE,
        id: id
    }).done(function (res) {

        if (res.status !== "ok") return;

        const record = res.data;
        const meta = record.meta || {};

        $("#view_resident").text(record.resident_name || '');
        $("#view_date").text(record.consultation_date || '');
        $("#view_time").text(meta.time || '-');
        $("#view_type").text(meta.program || '');
        $("#view_sub_type").text(meta.sub_type || '-');
        $("#view_complaint").text(record.complaint || '');
        $("#view_diagnosis").text(record.diagnosis || '-');
        $("#view_treatment").text(record.treatment || '-');
        $("#view_worker").text(meta.health_worker || '-');
        $("#view_status").html(formatStatusBadge(meta.status));
        $("#view_remarks").text(meta.remarks || '-');

        $("#viewConsultationModal").dialog("open");

    }).fail(function (xhr) {
        console.log(xhr.responseText);
    });
}

function openEditModal(id) {
    $.getJSON("api/health_records_api.php", {
        action: "get",
        type: HEALTH_RECORD_TYPE,
        id: id
    }).done(function (res) {
        if (res.status !== "ok") return;

        const record = res.data;

        // fill your form
        $("#edit_id").val(record.id);
        $("#edit_resident_id").val(record.resident_id);
        $("#edit_resident_name").val(record.resident_name);
        $("#consultation_date").val(record.consultation_date);

        $("textarea[name='complaint']").val(record.complaint || "");
        $("textarea[name='diagnosis']").val(record.diagnosis || "");
        $("textarea[name='treatment']").val(record.treatment || "");
        $("textarea[name='remarks']").val(record.meta.remarks || "");

        $("input[name='consultation_time']").val(record.meta.time || "");
        $("input[name='health_worker']").val(record.meta.health_worker || "");
        $("select[name='status']").val(record.meta.status || "Completed");

        $("select[name='consultation_type']").val(record.meta.program || HEALTH_RECORD_TYPE);
        $("#sub_type").val(record.meta.sub_type || "all");

        $("#editConsultationModal").dialog("open");
    }).fail(function (xhr) {
        console.log("Edit load failed:", xhr.status, xhr.responseText);
    });
}

/**
 * Initial filter values are set from the server-rendered INIT_FILTERS object.
 * This ensures that when the page loads, the UI reflects the current filter state. 
 */
window.__period = INIT_FILTERS.period || "all";

document.querySelectorAll(".periodBtn").forEach(btn => {
    btn.addEventListener("click", () => {
        window.__period = btn.dataset.period;
        setActivePeriod(window.__period);
    });
});

document.getElementById("applyPeriodBtn").addEventListener("click", () => {
    updateUrlAndReload();
});

document.getElementById("subTypeSelect").addEventListener("change", () => {
    const sel = document.getElementById("subTypeSelect");
    const selectedLabel = sel.options[sel.selectedIndex]?.textContent || "All";
    document.getElementById("currentSubTypeLabel").textContent = selectedLabel.replace(" Only", "");
    updateUrlAndReload();
});

document.getElementById("searchInput").addEventListener("keydown", (e) => {
    if (e.key === "Enter") updateUrlAndReload();
});

document.getElementById("clearFiltersBtn").addEventListener("click", () => {
    const base = new URL(window.location.href);
    base.search = "";
    base.searchParams.set("type", HEALTH_RECORD_TYPE);
    window.location.href = base.toString();
});

$("#printRecordsBtn").on("click", function () {

    const params = {
        type: HEALTH_RECORD_TYPE,
        period: window.__period || "all",
        month: $("#monthPicker").val() || "",
        search: $("#searchInput").val() || "",
        sub: $("#subTypeSelect").val() || "all"
    };

    $.getJSON("api/health_records_api.php", params, function (res) {

        if (res.status !== "ok") return;

        const records = res.data || [];

        let html = `
      <div style="font-family: Arial; font-size:12px;">
        <h2>Health Records - ${params.type.replace("_", " ").toUpperCase()}</h2>
        <p>
          Period: ${res.filters.period} |
          Date Range: ${res.filters.from} to ${res.filters.to}
        </p>
        <table border="1" width="100%" cellspacing="0" cellpadding="5">
          <thead>
            <tr>
              <th>Date</th>
              <th>Resident</th>
              <th>Sub Type</th>
              <th>Status</th>
              <th>Complaint</th>
            </tr>
          </thead>
          <tbody>
    `;

        records.forEach(r => {
            html += `
        <tr>
          <td>${r.consultation_date}</td>
          <td>${r.resident_name}</td>
          <td>${r.meta.sub_type || '-'}</td>
          <td>${r.meta.status || '-'}</td>
          <td>${r.complaint}</td>
        </tr>
      `;
        });

        html += `
          </tbody>
        </table>
      </div>
    `;

        const original = $("body").html();

        $("body").html(html);
        window.print();
        $("body").html(original);

        // Re-bind scripts after restoring DOM
        location.reload(); // safest way para hindi masira events
    });

});

/**
 * Delegated event handlers for dynamically generated buttons in the records table.
 * These buttons are created when the records are loaded, so we use event delegation.
 */
// VIEW BUTTON
$(document).on("click", ".viewBtn", function () {
    const id = $(this).data("id");
    openViewModal(id);
});

// EDIT BUTTON
$(document).on("click", ".editBtn", function () {
    const id = $(this).data("id");
    openEditModal(id);
});

/**
 * initialize sub-type options based on main type, and set initial filter values
 * This runs on page load to ensure the UI reflects the current filters.
 */
fillSubTypes();
document.getElementById("searchInput").value = INIT_FILTERS.q || "";
document.getElementById("monthPicker").value = INIT_FILTERS.month || "";
setActivePeriod(window.__period);

/**
 * Helper function to format status badges in the view modal.
 * You can customize the badge styles and status values as needed.
 * This function returns HTML strings that are injected into the modal.
 */
function formatStatusBadge(status) {

    if (!status) return '-';

    if (status === "Completed") {
        return '<span class="px-2 py-1 text-xs rounded bg-green-100 text-green-700">Completed</span>';
    }

    if (status === "Ongoing") {
        return '<span class="px-2 py-1 text-xs rounded bg-yellow-100 text-yellow-700">Ongoing</span>';
    }

    if (status === "Dismissed") {
        return '<span class="px-2 py-1 text-xs rounded bg-red-100 text-red-700">Dismissed</span>';
    }

    if (status === "Follow-up") {
        return '<span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-700">Follow-up</span>';
    }

    return status;
}