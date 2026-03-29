<?php
require_once __DIR__ . '/../../includes/app.php';
requireHCNurse();

$user_id = $_SESSION['user_id'];
$success = '';
$error   = '';

/* ── Fetch current user ── */
$stmt = $conn->prepare("SELECT name, username, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

/* ── Handle POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please refresh and try again.";
    } else {
        $name     = sanitizeString($_POST['name']     ?? '', false);
        $username = sanitizeString($_POST['username'] ?? '', false);
        $password = sanitizeString($_POST['password'] ?? '');

        /* ── Profile picture upload ── */
        $profile_picture = $user['profile_picture'];

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $validation = validateUploadedFile(
                $_FILES['profile_picture'],
                ['image/jpeg','image/png','image/gif'],
                2 * 1024 * 1024,
                ['jpg','jpeg','png','gif']
            );

            if (!$validation['valid']) {
                $error = $validation['error'];
            } else {
                $filename = 'profile_' . $user_id . '_' . $validation['safe_filename'];
                $filepath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
                    if (!empty($user['profile_picture'])) {
                        $old = $uploadDir . basename($user['profile_picture']);
                        if (file_exists($old) && is_file($old)) unlink($old);
                    }
                    $profile_picture = $filename;
                } else {
                    $error = "Failed to upload profile picture. Please try again.";
                }
            }
        }

        if (empty($error)) {
            if (empty($name) || empty($username)) {
                $error = "Name and username are required.";
            } else {
                if (!empty($password)) {
                    $hashed = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name=?, username=?, password=?, profile_picture=? WHERE id=?");
                    $stmt->bind_param("ssssi", $name, $username, $hashed, $profile_picture, $user_id);
                } else {
                    $stmt = $conn->prepare("UPDATE users SET name=?, username=?, profile_picture=? WHERE id=?");
                    $stmt->bind_param("sssi", $name, $username, $profile_picture, $user_id);
                }

                if ($stmt->execute()) {
                    $_SESSION['name']     = $name;
                    $_SESSION['username'] = $username;
                    if (!empty($profile_picture)) $_SESSION['profile_picture'] = $profile_picture;
                    $success = "Profile updated successfully.";
                    /* refresh user row */
                    $stmt2 = $conn->prepare("SELECT name, username, profile_picture FROM users WHERE id=?");
                    $stmt2->bind_param("i", $user_id);
                    $stmt2->execute();
                    $user = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

/* initials fallback */
$nameParts = explode(' ', trim($user['name'] ?? 'U'));
$initials  = strtoupper(substr($nameParts[0]??'U',0,1).substr($nameParts[count($nameParts)-1]??'',0,1));
$hasAvatar = !empty($user['profile_picture'])
          && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_picture']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --paper:      #fdfcf9;
        --paper-lt:   #f9f7f3;
        --paper-dk:   #f0ede6;
        --ink:        #1a1a1a;
        --ink-muted:  #5a5a5a;
        --ink-faint:  #a0a0a0;
        --rule:       #d8d4cc;
        --rule-dk:    #b8b4ac;
        --bg:         #edeae4;
        --accent:     var(--theme-primary, #2d5a27);
        --accent-lt:  color-mix(in srgb, var(--accent) 8%, white);
        --ok-bg:      #edfaf3; --ok-fg:     #1a5c35;
        --danger-bg:  #fdeeed; --danger-fg: #7a1f1a;
        --f-serif: 'Source Serif 4', Georgia, serif;
        --f-sans:  'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:  'Source Code Pro', 'Courier New', monospace;
        --shadow:  0 1px 2px rgba(0,0,0,.07), 0 3px 14px rgba(0,0,0,.05);
    }
    *, *::before, *::after { box-sizing:border-box; margin:0; padding:0; }
    body, input, button, select, textarea { font-family:var(--f-sans); }
    .set-page { background:var(--bg); min-height:100%; padding-bottom:56px; }

    /* ── Doc header ── */
    .doc-header { background:var(--paper); border-bottom:1px solid var(--rule); padding:20px 28px 18px; }
    .doc-eyebrow {
        font-size:8.5px; font-weight:700; letter-spacing:1.8px;
        text-transform:uppercase; color:var(--ink-faint);
        display:flex; align-items:center; gap:8px; margin-bottom:6px;
    }
    .doc-eyebrow::before { content:''; width:18px; height:2px; background:var(--accent); display:inline-block; }
    .doc-title {
        font-family:var(--f-serif); font-size:22px; font-weight:700;
        color:var(--ink); letter-spacing:-.3px; margin-bottom:3px;
    }
    .doc-sub { font-size:12px; color:var(--ink-faint); font-style:italic; }
    .doc-accent-bar { height:3px; background:linear-gradient(to right, var(--accent), transparent); }

    /* ── Layout ── */
    .set-body {
        max-width:860px; margin:28px auto; padding:0 28px;
        display:grid; grid-template-columns:240px 1fr; gap:24px; align-items:start;
    }
    @media (max-width:800px) { .set-body { grid-template-columns:1fr; } }

    /* ── Identity card (left) ── */
    .identity-card {
        background:var(--paper); border:1px solid var(--rule);
        border-radius:2px; box-shadow:var(--shadow); overflow:hidden;
        text-align:center;
    }
    .identity-card-top {
        padding:28px 20px 20px;
        border-bottom:1px solid var(--rule);
        background:linear-gradient(to bottom, var(--accent-lt), var(--paper));
    }
    /* Avatar */
    .avatar-wrap {
        position:relative; width:88px; height:88px;
        margin:0 auto 14px; cursor:pointer;
    }
    .avatar-img {
        width:88px; height:88px; border-radius:3px;
        border:2px solid var(--rule-dk);
        object-fit:cover; display:block;
    }
    .avatar-init {
        width:88px; height:88px; border-radius:3px;
        border:2px solid var(--rule-dk);
        background:var(--paper-dk);
        display:flex; align-items:center; justify-content:center;
        font-family:var(--f-mono); font-size:26px; font-weight:700;
        color:var(--ink-muted); letter-spacing:.5px;
    }
    .avatar-overlay {
        position:absolute; inset:0; border-radius:3px;
        background:rgba(0,0,0,.45);
        display:flex; align-items:center; justify-content:center;
        opacity:0; transition:opacity .15s;
    }
    .avatar-wrap:hover .avatar-overlay { opacity:1; }
    .avatar-overlay-txt {
        font-size:9px; font-weight:700; letter-spacing:.8px;
        text-transform:uppercase; color:#fff;
    }
    .identity-name {
        font-family:var(--f-serif); font-size:16px; font-weight:600;
        color:var(--ink); margin-bottom:4px;
    }
    .identity-role {
        font-size:9px; font-weight:700; letter-spacing:1.2px;
        text-transform:uppercase; color:var(--ink-faint);
    }
    .identity-role::before { content:'— '; color:var(--accent); }
    .identity-card-meta { padding:14px 18px; }
    .identity-meta-row {
        display:flex; justify-content:space-between;
        padding:7px 0; border-bottom:1px solid #f0ede8; font-size:12px;
    }
    .identity-meta-row:last-child { border-bottom:none; }
    .identity-meta-lbl { color:var(--ink-faint); }
    .identity-meta-val { font-family:var(--f-mono); font-size:11.5px; color:var(--ink-muted); font-weight:600; }

    /* ── Form card (right) ── */
    .form-card {
        background:var(--paper); border:1px solid var(--rule);
        border-radius:2px; box-shadow:var(--shadow); overflow:hidden;
    }
    .form-section { padding:18px 22px 0; border-top:1px solid var(--rule); }
    .form-section:first-child { border-top:none; padding-top:22px; }
    .form-section-lbl {
        font-size:8px; font-weight:700; letter-spacing:1.6px;
        text-transform:uppercase; color:var(--ink-faint);
        margin-bottom:14px; display:flex; align-items:center; gap:8px;
    }
    .form-section-lbl::after { content:''; flex:1; height:1px; background:var(--rule); }
    .form-section-body { padding-bottom:18px; }

    .fg { margin-bottom:14px; }
    .fg-label {
        display:block; font-size:8.5px; font-weight:700;
        letter-spacing:1.2px; text-transform:uppercase; color:var(--ink-muted); margin-bottom:6px;
    }
    .fg-input {
        width:100%; padding:9px 12px;
        border:1.5px solid var(--rule-dk); border-radius:2px;
        font-family:var(--f-sans); font-size:13px; color:var(--ink);
        background:#fff; outline:none; transition:border-color .15s, box-shadow .15s;
    }
    .fg-input:focus {
        border-color:var(--accent);
        box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent);
    }
    .fg-input::placeholder { color:var(--ink-faint); font-style:italic; font-size:12px; }
    .fg-hint { font-size:10.5px; color:var(--ink-faint); margin-top:5px; font-style:italic; }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

    /* ── File input ── */
    .file-input-wrap {
        border:1.5px dashed var(--rule-dk); border-radius:2px;
        padding:14px 16px; background:var(--paper-lt);
        display:flex; align-items:center; gap:14px;
        cursor:pointer; transition:border-color .14s;
    }
    .file-input-wrap:hover { border-color:var(--accent); }
    .file-input-wrap input[type="file"] { display:none; }
    .file-btn {
        padding:6px 14px; border-radius:2px;
        background:#fff; border:1.5px solid var(--rule-dk);
        font-size:11px; font-weight:700; letter-spacing:.4px;
        text-transform:uppercase; color:var(--ink-muted);
        cursor:pointer; transition:all .13s; flex-shrink:0;
    }
    .file-btn:hover { border-color:var(--accent); color:var(--accent); }
    .file-name { font-size:12px; color:var(--ink-faint); font-style:italic; }
    .file-hint { font-size:10px; color:var(--ink-faint); margin-top:6px; }

    /* ── Alert banners ── */
    .alert-banner {
        display:flex; align-items:center; gap:10px;
        padding:11px 16px; border-radius:2px; margin-bottom:16px;
        font-size:12.5px; border:1px solid;
    }
    .alert-ok      { background:var(--ok-bg);     color:var(--ok-fg);     border-color:color-mix(in srgb,var(--ok-fg) 25%,transparent); }
    .alert-danger  { background:var(--danger-bg);  color:var(--danger-fg); border-color:color-mix(in srgb,var(--danger-fg) 25%,transparent); }

    /* ── Submit button ── */
    .btn-save {
        display:inline-flex; align-items:center; gap:6px;
        padding:10px 24px; border-radius:2px;
        background:var(--accent); border:1.5px solid var(--accent); color:#fff;
        font-family:var(--f-sans); font-size:12px; font-weight:700;
        letter-spacing:.5px; text-transform:uppercase;
        cursor:pointer; transition:filter .13s;
    }
    .btn-save:hover { filter:brightness(1.1); }
    </style>
</head>
<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto set-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-eyebrow">Barangay Bombongan — Health Center</div>
                <div class="doc-title">Account Settings</div>
                <div class="doc-sub">Manage your profile, credentials, and display preferences</div>
            </div>
            <div class="doc-accent-bar"></div>

            <!-- ── Body ── -->
            <div class="set-body">

                <!-- ── Left: Identity Card ── -->
                <div class="identity-card">
                    <div class="identity-card-top">
                        <!-- Avatar (click to upload) -->
                        <label for="avatarFileInput">
                            <div class="avatar-wrap">
                                <?php if ($hasAvatar): ?>
                                    <img class="avatar-img"
                                         src="/uploads/profiles/<?= htmlspecialchars($user['profile_picture']) ?>"
                                         alt="Profile" id="avatarPreview">
                                <?php else: ?>
                                    <div class="avatar-init" id="avatarInitials"><?= htmlspecialchars($initials) ?></div>
                                    <img class="avatar-img" id="avatarPreview" style="display:none;" alt="Preview">
                                <?php endif; ?>
                                <div class="avatar-overlay">
                                    <span class="avatar-overlay-txt">Change Photo</span>
                                </div>
                            </div>
                        </label>
                        <div class="identity-name"><?= htmlspecialchars($user['name'] ?? '—') ?></div>
                        <div class="identity-role">Health Center Nurse</div>
                    </div>
                    <div class="identity-card-meta">
                        <div class="identity-meta-row">
                            <span class="identity-meta-lbl">Username</span>
                            <span class="identity-meta-val">@<?= htmlspecialchars($user['username'] ?? '—') ?></span>
                        </div>
                        <div class="identity-meta-row">
                            <span class="identity-meta-lbl">Role</span>
                            <span class="identity-meta-val">hcnurse</span>
                        </div>
                        <div class="identity-meta-row">
                            <span class="identity-meta-lbl">User ID</span>
                            <span class="identity-meta-val">#<?= str_pad($user_id, 4, '0', STR_PAD_LEFT) ?></span>
                        </div>
                    </div>
                </div>

                <!-- ── Right: Form Card ── -->
                <div class="form-card">
                    <form method="POST" enctype="multipart/form-data" id="profileForm">
                        <?= csrfTokenField() ?>

                        <!-- Hidden file input (triggered by avatar click) -->
                        <input type="file" id="avatarFileInput" name="profile_picture"
                               accept="image/jpeg,image/jpg,image/png,image/gif" style="display:none;">

                        <!-- Alerts -->
                        <?php if ($success): ?>
                        <div style="padding:14px 22px 0;">
                            <div class="alert-banner alert-ok">✓ <?= htmlspecialchars($success) ?></div>
                        </div>
                        <?php elseif ($error): ?>
                        <div style="padding:14px 22px 0;">
                            <div class="alert-banner alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
                        </div>
                        <?php endif; ?>

                        <!-- Profile Picture Upload (visible upload row) -->
                        <div class="form-section">
                            <div class="form-section-lbl">Profile Picture</div>
                            <div class="form-section-body">
                                <label for="avatarFileInput" class="file-input-wrap">
                                    <button type="button" class="file-btn" onclick="document.getElementById('avatarFileInput').click()">
                                        Browse…
                                    </button>
                                    <span class="file-name" id="fileNameDisplay">No file chosen</span>
                                </label>
                                <div class="file-hint">Max 2 MB · JPEG, PNG, or GIF</div>
                            </div>
                        </div>

                        <!-- Account Info -->
                        <div class="form-section">
                            <div class="form-section-lbl">Account Information</div>
                            <div class="form-section-body">
                                <div class="form-grid-2">
                                    <div class="fg">
                                        <label class="fg-label">Full Name <span style="color:var(--danger-fg);">*</span></label>
                                        <input type="text" name="name" class="fg-input"
                                               value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                                               required autocomplete="off">
                                    </div>
                                    <div class="fg">
                                        <label class="fg-label">Username <span style="color:var(--danger-fg);">*</span></label>
                                        <input type="text" name="username" class="fg-input"
                                               value="<?= htmlspecialchars($user['username'] ?? '') ?>"
                                               required autocomplete="off">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-section">
                            <div class="form-section-lbl">Change Password</div>
                            <div class="form-section-body">
                                <div class="form-grid-2">
                                    <div class="fg">
                                        <label class="fg-label">New Password</label>
                                        <input type="password" name="password" class="fg-input"
                                               placeholder="Leave blank to keep current" autocomplete="new-password">
                                    </div>
                                    <div class="fg">
                                        <label class="fg-label">Confirm Password</label>
                                        <input type="password" id="confirmPassword" class="fg-input"
                                               placeholder="Re-enter new password" autocomplete="new-password">
                                        <div class="fg-hint">Only fill these if you want to change your password.</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div style="padding:14px 22px 22px;border-top:1px solid var(--rule);background:var(--paper-lt);display:flex;justify-content:flex-end;">
                            <button type="submit" class="btn-save">✓ Save Changes</button>
                        </div>

                    </form>
                </div><!-- /form-card -->

            </div><!-- /set-body -->
        </main>
    </div>

    <script>
    $(function () {
        $('body').show();

        /* ── Avatar preview on file select ── */
        $('#avatarFileInput').on('change', function () {
            const file = this.files[0];
            if (!file) return;
            $('#fileNameDisplay').text(file.name);
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#avatarInitials').hide();
                $('#avatarPreview').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(file);
        });

        /* ── Password match validation ── */
        $('form').on('submit', function (e) {
            const pw  = $('input[name="password"]').val();
            const cpw = $('#confirmPassword').val();
            if (pw && pw !== cpw) {
                e.preventDefault();
                const id = 'al_' + Date.now();
                $('body').append(`<div id="${id}" title="Validation" style="display:none;">
                    <div style="padding:18px 20px;font-size:13px;color:var(--ink);border-left:3px solid var(--danger-fg);background:var(--paper);">
                        Passwords do not match. Please re-enter.
                    </div>
                </div>`);
                $(`#${id}`).dialog({
                    autoOpen:true, modal:true, width:400, resizable:false,
                    buttons:{ 'OK': function(){ $(this).dialog('close').remove(); } }
                });
            }
        });
    });
    </script>
</body>
</html>