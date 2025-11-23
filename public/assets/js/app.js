function showDialog(title, message) {
    $("#dialog-message").attr("title", title);
    $("#dialog-text").html(message);

    $("#dialog-message").dialog({
        modal: true,
        width: 400,
        buttons: {
            Ok: function () {
                $(this).dialog("close");
            }
        }
    });
}
function showDialogReload(title, message) {
    $("#dialog-message").attr("title", title);
    $("#dialog-text").html(message);

    $("#dialog-message").dialog({
        modal: true,
        width: 400,
        buttons: {
            Ok: function () {
                $(this).dialog("close");
                location.reload(); // reload page to show new request
            }
        }
    });
}