$(window).on('load', function () {
    $("body").show();

    $("#consultation_date").datepicker({ dateFormat: "yy-mm-dd" });

    const table = $('#consultationsTable').DataTable({
        pageLength: 20,
        lengthChange: false,
        info: false,
        dom: 'rt<"flex items-center justify-between mt-4"p>', // hide default search
    });

    $('#consultSearchInput').on('keyup', function () {
        table.search(this.value).draw();
    });

    $("#addConsultationModal").dialog({
        autoOpen: false,
        modal: true,
        width: 650,
        resizable: false
    });

    $("#viewConsultationModal").dialog({
        autoOpen: false,
        modal: true,
        width: 780,
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
            Close: function () {
                $(this).dialog("close");
            }
        }
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

    $("#edit_consultation_date").datepicker({ dateFormat: "mm/dd/yy" });


    $("#openConsultationModalBtn").on("click", function () {
        $("#addConsultationModal").dialog("open");
    });

    $("#closeConsultationModalBtn").on("click", function () {
        $("#addConsultationModal").dialog("close");
    });

    /**
     * Resident autocomplete for ADD modal
     */
    $("#add_resident_name").autocomplete({
        appendTo: "#addConsultationModal",
        minLength: 1,
        source: function (request, response) {
            $.getJSON("api/resident.php", { term: request.term }, response);
        },
        select: function (event, ui) {
            $("#add_resident_id").val(ui.item.id);
            $("#add_resident_name").val(ui.item.label);
            return false;
        }
    });

    // clear hidden if user changes text
    $("#add_resident_name").on("input", function () {
        $("#add_resident_id").val("");
    });


    /**
     * datepicker (same ecosystem)
     */
    $("#consultation_date").datepicker({
        dateFormat: "mm/dd/yy"
    });

    $("#addConsultationModal").dialog({
        autoOpen: false,
        modal: true,
        resizable: true,
        classes: {
            'ui-dialog': 'rounded-lg shadow-lg',
            'ui-dialog-titlebar': 'bg-theme-primary text-white rounded-t-lg',
            'ui-dialog-title': 'font-semibold',
            'ui-dialog-buttonpane': 'bg-theme-primary text-white rounded-b-lg',
        },
        show: {
            effect: "fadeIn",
            duration: 200
        },
        hide: {
            effect: "fadeOut",
            duration: 200
        },
        buttons: {
            "Add Consultation": function () {
                $("#addConsultationForm").trigger("submit");
            },
            "Cancel": function () {
                $(this).dialog("close");
            }
        },
        open: function () {
            // Reset form when opened
            $("#addConsultationForm")[0].reset();
            $(".ui-dialog-buttonpane button:first").addClass("bg-theme-primary hover-theme-darker text-white px-4 py-2 rounded");
        }
    });

    $("#openConsultationModalBtn").on("click", function () {
        $("#addConsultationModal").dialog("open");
    });

    // add consultation form submit handler
    // Convert mm/dd/yyyy -> yyyy-mm-dd before submit
    $("#addConsultationForm").on("submit", function (e) {
        e.preventDefault();

        const $form = $(this);
        const formData = $form.serialize(); // includes resident_id, date, complaint, etc.

        /**
        * select resident validation before opening add consultation modal
        */
        if (!$("#add_resident_id").val()) {
            showDialog("Message", "Please select a resident from the dropdown list.");
            return;
        }

        $.ajax({
            url: "api/add.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (res) {
                if (!res.success) {
                    showDialog("Message", res.message || "Failed to add consultation.");
                    return;
                }
                $("#addConsultationModal").dialog("close");
                showDialogReload("Message", "Consultation added successfully.");
                location.reload(); // simple refresh; later we can append row dynamically
            },
            error: function () {
                showDialog("Message", "Server error while adding consultation.");
            }
        });
    });

    /**
     * View consultation details in a modal
     */
    $(document).on("click", ".viewConsultBtn", function () {
        const id = $(this).data("id");

        $.getJSON("api/get.php", { id }, function (res) {
            if (!res.success) return alert(res.message || "Not found");

            const d = res.data;
            $("#v_fullname").text(d.fullname || "—");
            $("#v_date").text(d.consultation_date || "—");
            $("#v_time").text(d.time || "—");
            $("#v_status").text(d.status || "—");
            $("#v_worker").text(d.health_worker || "—");
            $("#v_complaint").text(d.complaint || "—");
            $("#v_diagnosis").text(d.diagnosis || "—");
            $("#v_treatment").text(d.treatment || "—");
            $("#v_remarks").text(d.remarks || "—");

            $("#viewConsultationModal").dialog("open");
        });
    });

    /**
     * edit consultation (similar to view, but with form fields and save button)
     */
    $("#editConsultationForm").on("submit", function (e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: "api/edit.php",
            type: "POST",
            data: formData,
            dataType: "json",
            success: function (res) {
                if (!res.success) {
                    showDialog("Message", res.message || "Failed to update consultation.");
                    return;
                }
                $("#editConsultationModal").dialog("close");
                showDialogReload("Message", "Consultation Updated ✅");
                location.reload();
            },
            error: function () {
                showDialog("Message", "Server error while updating consultation.");
            }
        });
    });

    /**
     * view consult button
     */
    $(document).on("click", ".viewConsultBtn", function () {
        const id = $(this).data("id");

        $.ajax({
            url: "api/view.php",
            type: "GET",
            dataType: "json",
            data: { id: id },
            success: function (res) {
                if (!res.success) {
                    alert(res.message || "Not found.");
                    return;
                }

                const d = res.data;

                $("#v_fullname").text(d.fullname || "—");
                $("#v_date").text(d.consultation_date || "—");
                $("#v_time").text(d.time || "—");
                $("#v_status").text(d.status || "—");
                $("#v_worker").text(d.health_worker || "—");

                $("#v_complaint").text(d.complaint || "—");
                $("#v_diagnosis").text(d.diagnosis || "—");
                $("#v_treatment").text(d.treatment || "—");
                $("#v_remarks").text(d.remarks || "—");

                $("#viewConsultationModal").dialog("open");
            },
            error: function () {
                showDialog("Message", "Server error while loading consultation.");
            }
        });
    });

    /**
     * Edit Consult Button
     */
    $(document).on("click", ".editConsultBtn", function () {
        const id = $(this).data("id");

        $.getJSON("api/view.php", { id }, function (res) {
            if (!res.success) return alert(res.message || "Not found");

            const d = res.data;

            $("#edit_id").val(d.id);
            $("#edit_resident_id").val(d.resident_id);
            $("#edit_resident_name").val(d.fullname || "");

            // show mm/dd/yyyy in UI if api returns yyyy-mm-dd
            if (d.consultation_date && d.consultation_date.includes("-")) {
                const parts = d.consultation_date.split("-");
                $("#edit_consultation_date").val(`${parts[1]}/${parts[2]}/${parts[0]}`);
            } else {
                $("#edit_consultation_date").val(d.consultation_date || "");
            }

            $("#edit_consultation_time").val(d.time || "");
            $("#edit_complaint").val(d.complaint || "");
            $("#edit_diagnosis").val(d.diagnosis || "");
            $("#edit_treatment").val(d.treatment || "");
            $("#edit_health_worker").val(d.health_worker || "");
            $("#edit_status").val(d.status || "Completed");
            $("#edit_remarks").val(d.remarks || "");

            $("#editConsultationModal").dialog("open");
        });
    });

    /**
     * Generate report button (opens generate dialog with options, then generates PDF in new tab) per resident
     */
    $("#generateDialogResident").dialog({
        autoOpen: false,
        modal: true,
        width: 520,
        resizable: false,
        buttons: {
            "Generate & Print": function () {
                const doc = $("#gen_doc").val();
                const period = $("#gen_period").val();
                const month = $("#gen_month").val();
                const residentId = $("#gen_resident_id").val();
                const purpose = $("#gen_purpose").val();

                const params = new URLSearchParams();
                params.set("doc", doc);
                params.set("period", period);
                if (period === "monthly") params.set("month", month);
                if (doc !== "report") params.set("resident_id", residentId);
                if (doc === "certificate") params.set("purpose", purpose);

                // open print page
                window.open("api/generate.php?" + params.toString(), "_blank");
                $(this).dialog("close");
            },
            Cancel: function () {
                $(this).dialog("close");
            }
        }
    });

    $("#gen_period").on("change", function () {
        const isMonthly = $(this).val() === "monthly";
        $("#gen_month_wrap").toggleClass("hidden", !isMonthly);
    });

    $("#gen_doc").on("change", function () {
        const isCert = $(this).val() === "certificate";
        $("#gen_purpose_wrap").toggleClass("hidden", !isCert);
    });

    $(document).on("click", ".generateBtnResident", function () {
        const rid = $(this).data("resident-id");
        $("#gen_resident_id").val(rid);
        $("#gen_doc").val("summary").trigger("change");
        $("select[name='doc'] option[value='report']").hide();
        $("#gen_period").val("monthly").trigger("change");
        $("#generateDialog").dialog("open");
    });

    /**
     * Generate report button (opens generate dialog with options, then generates PDF in new tab)
     */
    $("#generateDialog").dialog({
        autoOpen: false,
        modal: true,
        width: 520,
        resizable: false,
        buttons: {
            "Generate & Print": function () {
                const doc = $("#gen_doc").val();
                const period = $("#gen_period").val();
                const month = $("#gen_month").val();
                const residentId = $("#gen_resident_id").val();
                const purpose = $("#gen_purpose").val();

                const params = new URLSearchParams();
                params.set("doc", doc);
                params.set("period", period);
                if (period === "monthly") params.set("month", month);
                if (doc !== "report") params.set("resident_id", residentId);
                if (doc === "certificate") params.set("purpose", purpose);

                // open print page
                window.open("api/generate.php?" + params.toString(), "_blank");
                $(this).dialog("close");
            },
            Cancel: function () {
                $(this).dialog("close");
                $("select[name='doc'] option[value='report']").show();
                $("#gen_doc").val("report").trigger("change").attr("disabled", false);
            }
        }
    });

    $("#gen_period").on("change", function () {
        const isMonthly = $(this).val() === "monthly";
        $("#gen_month_wrap").toggleClass("hidden", !isMonthly);
    });

    $("#gen_doc").on("change", function () {
        const isCert = $(this).val() === "certificate";
        $("#gen_purpose_wrap").toggleClass("hidden", !isCert);
    });

    $(document).on("click", "#generateConsultReportBtn", function () {
        $("#gen_doc").val("summary").trigger("change");
        $("#gen_period").val("monthly").trigger("change");
        $("#generateDialog").dialog("open");

        $("#gen_doc").val("report").trigger("change").attr("disabled", true);
    });
});