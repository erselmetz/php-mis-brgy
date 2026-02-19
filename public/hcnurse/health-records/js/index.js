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

// init
fillSubTypes();
document.getElementById("searchInput").value = INIT_FILTERS.q || "";
document.getElementById("monthPicker").value = INIT_FILTERS.month || "";
setActivePeriod(window.__period);