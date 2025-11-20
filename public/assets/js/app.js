function showDialog(title, message) {
    const modalId = "modal_" + Date.now();
    const safeTitle = title.replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    const safeMessage = message.replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="${modalId}Label">${safeTitle}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${safeMessage}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Ok</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    $("body").append(modalHtml);
    const modalElement = document.getElementById(modalId);
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    $(modalElement).on("hidden.bs.modal", function() {
        $(this).remove();
    });
}

function showDialogReload(title, message) {
    const modalId = "modal_" + Date.now();
    const safeTitle = title.replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    const safeMessage = message.replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="${modalId}Label">${safeTitle}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0">${safeMessage}</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Ok</button>
                    </div>
                </div>
            </div>
        </div>
    `;
    $("body").append(modalHtml);
    const modalElement = document.getElementById(modalId);
    const modal = new bootstrap.Modal(modalElement);
    modal.show();
    $(modalElement).on("hidden.bs.modal", function() {
        $(this).remove();
        location.reload(); // reload page to show new request
    });
}