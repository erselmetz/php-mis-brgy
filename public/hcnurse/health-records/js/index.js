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
        { value: "postnatal", label: "Postnatal" }
    ],
    postnatal: [
        { value: "all", label: "All" },
        { value: "prenatal", label: "Prenatal" },
        { value: "postnatal", label: "Postnatal" }
    ],
    child_nutrition: [
        { value: "all", label: "All" },
        { value: "supplementation", label: "Supplementation" },
        { value: "deworming", label: "Deworming" },
        { value: "weighing", label: "Weighing" }
    ],
};

// dialog init
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
        url: "api/health_records_api.php?id="+id+"&action=update&type=" + encodeURIComponent(HEALTH_RECORD_TYPE),
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

// ---------- UI init ----------
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
        type: HEALTH_RECORD_TYPE,
        id: id
    }, function (res) {

        const record = res.data.find(r => r.id == id);
        if (!record) return;

        alert(
            "Resident: " + record.resident_name +
            "\nDate: " + record.consultation_date +
            "\nComplaint: " + record.complaint +
            "\nStatus: " + (record.meta.status || '')
        );
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

// ---------- events ----------
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

document.getElementById("printRecordsBtn").addEventListener("click", () => {
    window.open(`/hcnurse/health-records/print.php${window.location.search}`, "_blank");
});

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

// init
fillSubTypes();
document.getElementById("searchInput").value = INIT_FILTERS.q || "";
document.getElementById("monthPicker").value = INIT_FILTERS.month || "";
setActivePeriod(window.__period);