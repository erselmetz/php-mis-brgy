<!doctype html>
<html>

<head>
  <meta charset="utf-8">
  <title>Initial Setup</title>

  <!-- use your existing assets (jquery + jquery ui + tailwind if meron) -->
  <link rel="stylesheet" href="/assets/vendor/jquery-ui/jquery-ui.css">
  <script src="/assets/vendor/jquery/jquery.js"></script>
  <script src="/assets/vendor/jquery-ui/jquery-ui.js"></script>
</head>

<body class="p-6">
  <div id="setupDialog" title="System Setup Required" style="display:none;">
    <?php if (!empty($setup_error)): ?>
      <div style="margin:10px 0;padding:10px;background:#fff3cd;border:1px solid #ffeeba;border-radius:6px;">
        <b>Detected Error:</b><br>
        <code><?= htmlspecialchars($setup_error) ?></code>
      </div>
    <?php endif; ?> 
    <div style="line-height:1.6">
      <p><b>Hindi pa ready ang database / MySQL.</b></p>

      <p><b>Step 1:</b> Install <b>Laragon</b></p>
      <p><b>Step 2:</b> Open Laragon → click <b>Start All</b> (Apache + MySQL)</p>

      <p><b>Step 3:</b> Create database name:</p>
      <div style="padding:8px;background:#f3f4f6;border-radius:6px;">
        <code>php_mis_brgy</code>
      </div>

      <p style="margin-top:10px;">
        After creating DB, click <b>Retry Connection</b>.
      </p>

      <hr style="margin:12px 0">

      <p style="font-size:13px;color:#666">
        Tip: You can create DB using HeidiSQL/phpMyAdmin or MySQL command:
        <br><code>CREATE DATABASE php_mis_brgy;</code>
      </p>
    </div>
  </div>

  <script>
    $(function () {
      $("#setupDialog").dialog({
        modal: true,
        width: 520,
        resizable: false,
        closeOnEscape: false,
        open: function () {
          $(".ui-dialog-titlebar-close").hide();
        },
        buttons: {
          "Retry Connection": function () {
            location.reload();
          },
          "Close": function () {
            $(this).dialog("close");
          }
        }
      });
    });
  </script>

</body>

</html>