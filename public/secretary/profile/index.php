<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch current user
$stmt = $conn->prepare("SELECT name, username, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid security token. Please refresh the page.";
    } else {
        $name = sanitizeString($_POST['name'] ?? '', false);
        $username = sanitizeString($_POST['username'] ?? '', false);
        $password = sanitizeString($_POST['password'] ?? '');
        $profile_picture = $user['profile_picture'];

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/profiles/';
            if (!is_dir($uploadDir))
                mkdir($uploadDir, 0755, true);

            $validation = validateUploadedFile(
                $_FILES['profile_picture'],
                ['image/jpeg', 'image/png', 'image/gif'],
                2097152,
                ['jpg', 'jpeg', 'png', 'gif']
            );
            if (!$validation['valid']) {
                $error = $validation['error'];
            } else {
                $filename = 'profile_' . $user_id . '_' . $validation['safe_filename'];
                $filepath = $uploadDir . $filename;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
                    if (!empty($user['profile_picture'])) {
                        $old = $uploadDir . basename($user['profile_picture']);
                        if (file_exists($old) && is_file($old))
                            unlink($old);
                    }
                    $profile_picture = $filename;
                } else {
                    $error = "Failed to upload profile picture.";
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
                    $_SESSION['name'] = $name;
                    $_SESSION['username'] = $username;
                    if (!empty($profile_picture))
                        $_SESSION['profile_picture'] = $profile_picture;
                    $success = "Profile updated successfully.";
                    // Refresh
                    $stmt2 = $conn->prepare("SELECT name, username, profile_picture FROM users WHERE id = ?");
                    $stmt2->bind_param("i", $user_id);
                    $stmt2->execute();
                    $user = $stmt2->get_result()->fetch_assoc();
                    $stmt2->close();
                } else {
                    $error = "Failed to update profile.";
                }
                $stmt->close();
            }
        }
    }
}

// Profile picture path helper
$picSrc = '';
if (!empty($user['profile_picture'])) {
    $picPath = __DIR__ . '/../../uploads/profiles/' . $user['profile_picture'];
    if (file_exists($picPath))
        $picSrc = '/uploads/profiles/' . htmlspecialchars($user['profile_picture']);
}

// User initials fallback
$nameParts = explode(' ', trim($user['name'] ?? 'U'));
$initials = strtoupper(substr($nameParts[0], 0, 1) . (count($nameParts) > 1 ? substr(end($nameParts), 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Settings — MIS Barangay</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(getCSRFToken()) ?>">
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap"
        rel="stylesheet">
    <style>
        /* ═══════════════════════════════════════
       TOKENS
    ═══════════════════════════════════════ */
        :root {
            --paper: #fdfcf9;
            --paper-lt: #f9f7f3;
            --paper-dk: #f0ede6;
            --ink: #1a1a1a;
            --ink-muted: #5a5a5a;
            --ink-faint: #a0a0a0;
            --rule: #d8d4cc;
            --rule-dk: #b8b4ac;
            --bg: #edeae4;
            --accent: var(--theme-primary, #2d5a27);
            --accent-lt: color-mix(in srgb, var(--accent) 8%, white);
            --accent-dk: color-mix(in srgb, var(--accent) 65%, black);
            --ok-bg: #edfaf3;
            --ok-fg: #1a5c35;
            --warn-bg: #fef9ec;
            --warn-fg: #7a5700;
            --info-bg: #edf3fa;
            --info-fg: #1a3a5c;
            --danger-bg: #fdeeed;
            --danger-fg: #7a1f1a;
            --neu-bg: #f3f1ec;
            --neu-fg: #5a5a5a;
            --f-serif: 'Source Serif 4', Georgia, serif;
            --f-sans: 'Source Sans 3', 'Segoe UI', sans-serif;
            --f-mono: 'Source Code Pro', 'Courier New', monospace;
            --shadow: 0 1px 2px rgba(0, 0, 0, .07), 0 3px 12px rgba(0, 0, 0, .04);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body,
        input,
        button,
        select,
        textarea {
            font-family: var(--f-sans);
        }

        /* ═══════════════════════════════════════
       PAGE LAYOUT
    ═══════════════════════════════════════ */
        .set-page {
            background: var(--bg);
            min-height: 100%;
            padding-bottom: 56px;
        }

        /* ── Document Header ── */
        .doc-header {
            background: var(--paper);
            border-bottom: 1px solid var(--rule);
        }

        .doc-header-inner {
            padding: 20px 28px 20px;
        }

        .doc-eyebrow {
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.8px;
            text-transform: uppercase;
            color: var(--ink-faint);
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .doc-eyebrow::before {
            content: '';
            display: inline-block;
            width: 18px;
            height: 2px;
            background: var(--accent);
        }

        .doc-title {
            font-family: var(--f-serif);
            font-size: 22px;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -.2px;
            margin-bottom: 3px;
        }

        .doc-sub {
            font-size: 12px;
            color: var(--ink-faint);
            font-style: italic;
        }

        /* ── Two column grid ── */
        .set-grid {
            display: grid;
            grid-template-columns: 340px 1fr;
            gap: 22px;
            padding: 22px 28px;
            align-items: start;
        }

        @media (max-width: 900px) {
            .set-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Card (shared) ── */
        .set-card {
            background: var(--paper);
            border: 1px solid var(--rule);
            border-top: 3px solid var(--accent);
            border-radius: 2px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .set-card+.set-card {
            margin-top: 22px;
        }

        .card-head {
            padding: 14px 20px;
            border-bottom: 1px solid var(--rule);
            background: var(--paper-lt);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-title {
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.6px;
            text-transform: uppercase;
            color: var(--ink-muted);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-title::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 14px;
            background: var(--accent);
            border-radius: 1px;
        }

        .card-body {
            padding: 20px;
        }

        /* ═══════════════════════════════════════
       LEFT COLUMN — Profile Identity
    ═══════════════════════════════════════ */

        /* Avatar */
        .avatar-zone {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 24px 20px 20px;
            border-bottom: 1px solid var(--rule);
        }

        .avatar-ring {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            border: 3px solid var(--accent);
            position: relative;
            margin-bottom: 14px;
            cursor: pointer;
            overflow: hidden;
            box-shadow: 0 0 0 4px var(--accent-lt);
            transition: box-shadow .2s;
        }

        .avatar-ring:hover {
            box-shadow: 0 0 0 6px var(--accent-lt);
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .avatar-initials {
            width: 100%;
            height: 100%;
            background: var(--accent);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--f-serif);
            font-size: 28px;
            font-weight: 600;
            letter-spacing: -.5px;
        }

        .avatar-overlay {
            position: absolute;
            inset: 0;
            background: rgba(0, 0, 0, .42);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity .2s;
            font-size: 10px;
            color: #fff;
            font-weight: 700;
            letter-spacing: .5px;
            text-transform: uppercase;
        }

        .avatar-ring:hover .avatar-overlay {
            opacity: 1;
        }

        .avatar-name {
            font-family: var(--f-serif);
            font-size: 17px;
            font-weight: 600;
            color: var(--ink);
            text-align: center;
            margin-bottom: 4px;
        }

        .avatar-role {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--ink-faint);
            background: var(--neu-bg);
            padding: 3px 10px;
            border-radius: 2px;
            border: 1px solid var(--rule);
        }

        .avatar-meta {
            margin-top: 10px;
            font-family: var(--f-mono);
            font-size: 10px;
            color: var(--ink-faint);
            letter-spacing: .3px;
            text-align: center;
        }

        /* Account form */
        .fg {
            margin-bottom: 14px;
        }

        .fg-label {
            display: block;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--ink-muted);
            margin-bottom: 5px;
        }

        .req {
            color: var(--danger-fg);
        }

        .fg-input,
        .fg-select {
            width: 100%;
            padding: 9px 12px;
            border: 1.5px solid var(--rule-dk);
            border-radius: 2px;
            font-family: var(--f-sans);
            font-size: 13px;
            color: var(--ink);
            background: #fff;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }

        .fg-input:focus,
        .fg-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
        }

        .fg-input::placeholder {
            color: var(--ink-faint);
            font-style: italic;
            font-size: 12px;
        }

        .fg-hint {
            font-size: 10px;
            color: var(--ink-faint);
            margin-top: 4px;
            font-style: italic;
        }

        .fg-file-btn {
            display: block;
            width: 100%;
            padding: 8px 12px;
            border: 1.5px dashed var(--rule-dk);
            border-radius: 2px;
            background: var(--paper-lt);
            text-align: center;
            font-size: 11.5px;
            color: var(--ink-muted);
            cursor: pointer;
            transition: border-color .15s, color .15s;
        }

        .fg-file-btn:hover {
            border-color: var(--accent);
            color: var(--accent);
        }

        .fg-file-name {
            font-size: 10.5px;
            color: var(--ok-fg);
            margin-top: 5px;
            font-family: var(--f-mono);
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 2px;
            font-family: var(--f-sans);
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
            cursor: pointer;
            border: 1.5px solid;
            transition: all .14s;
            white-space: nowrap;
        }

        .btn-primary {
            background: var(--accent);
            border-color: var(--accent);
            color: #fff;
        }

        .btn-primary:hover {
            filter: brightness(1.1);
        }

        .btn-ghost {
            background: #fff;
            border-color: var(--rule-dk);
            color: var(--ink-muted);
        }

        .btn-ghost:hover {
            border-color: var(--accent);
            color: var(--accent);
            background: var(--accent-lt);
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 10px;
        }

        /* Alert banners */
        .alert {
            margin: 0 20px 16px;
            padding: 11px 14px;
            border-radius: 2px;
            font-size: 12.5px;
            font-weight: 500;
            border-left: 3px solid;
        }

        .alert-ok {
            background: var(--ok-bg);
            color: var(--ok-fg);
            border-color: var(--ok-fg);
        }

        .alert-danger {
            background: var(--danger-bg);
            color: var(--danger-fg);
            border-color: var(--danger-fg);
        }

        /* ═══════════════════════════════════════
       RIGHT COLUMN — Reports & Backup
    ═══════════════════════════════════════ */

        /* File reports table */
        .rep-table {
            width: 100%;
            border-collapse: collapse;
        }

        .rep-table thead th {
            padding: 9px 14px;
            background: var(--paper-lt);
            text-align: left;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--ink-muted);
            border-bottom: 1px solid var(--rule-dk);
            white-space: nowrap;
        }

        .rep-table tbody tr {
            border-bottom: 1px solid #f0ede8;
            transition: background .1s;
        }

        .rep-table tbody tr:last-child {
            border-bottom: none;
        }

        .rep-table tbody tr:hover {
            background: var(--paper-lt);
        }

        .rep-table td {
            padding: 11px 14px;
            font-size: 12.5px;
            color: var(--ink);
            vertical-align: middle;
        }

        .rep-label {
            font-weight: 600;
        }

        .rep-count {
            font-family: var(--f-mono);
            font-size: 14px;
            font-weight: 600;
            color: var(--ink);
        }

        .rep-time {
            font-size: 11px;
            color: var(--ink-faint);
            font-style: italic;
        }

        /* Backup archive visual */
        .backup-panel {
            display: grid;
            grid-template-columns: 1fr 200px;
            gap: 20px;
            align-items: start;
        }

        .backup-icon-block {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
            border: 1.5px dashed var(--rule-dk);
            border-radius: 2px;
            background: var(--paper-lt);
            gap: 14px;
        }

        .backup-icon {
            width: 56px;
            height: 56px;
            background: var(--accent);
            color: #fff;
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .backup-icon-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1.4px;
            text-transform: uppercase;
            color: var(--ink-faint);
            text-align: center;
        }

        /* Backup history table */
        .bk-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12.5px;
        }

        .bk-table thead th {
            padding: 9px 14px;
            background: var(--paper-lt);
            text-align: left;
            font-size: 8.5px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--ink-muted);
            border-bottom: 1px solid var(--rule-dk);
            white-space: nowrap;
        }

        .bk-table tbody tr {
            border-bottom: 1px solid #f0ede8;
            transition: background .1s;
        }

        .bk-table tbody tr:hover {
            background: var(--paper-lt);
        }

        .bk-table td {
            padding: 10px 14px;
            vertical-align: middle;
        }

        .bk-date {
            font-family: var(--f-mono);
            font-size: 11.5px;
            color: var(--accent);
            font-weight: 600;
        }

        .bk-time {
            font-family: var(--f-mono);
            font-size: 10.5px;
            color: var(--ink-faint);
            margin-top: 1px;
        }

        .bk-size {
            font-family: var(--f-mono);
            font-size: 11.5px;
            color: var(--ink-muted);
        }

        .bk-by {
            font-size: 12px;
            color: var(--ink-muted);
        }

        .bk-desc {
            font-size: 11px;
            color: var(--ink-faint);
            font-style: italic;
        }

        /* Spinner */
        .spin {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, .3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="bg-gray-100 h-screen overflow-hidden" style="display:none;">
    <?php include_once '../layout/navbar.php'; ?>
    <div class="flex h-full" style="background:var(--bg);">
        <?php include_once '../layout/sidebar.php'; ?>

        <main class="flex-1 h-screen overflow-y-auto set-page">

            <!-- ── Document Header ── -->
            <div class="doc-header">
                <div class="doc-header-inner">
                    <div class="doc-eyebrow">Barangay Bombongan — Administration</div>
                    <div class="doc-title">Account &amp; System Settings</div>
                    <div class="doc-sub">Profile management, data reports, and database backup</div>
                </div>
            </div>

            <!-- ── Two-column grid ── -->
            <div class="set-grid">

                <!-- ════════════════════════════
                     LEFT: Profile Card
                ════════════════════════════ -->
                <div>
                    <div class="set-card">
                        <!-- Avatar zone -->
                        <div class="avatar-zone">
                            <label for="picInput" class="avatar-ring" title="Click to change photo">
                                <?php if ($picSrc): ?>
                                    <img src="<?= $picSrc ?>" alt="Profile" class="avatar-img" id="avatarPreview">
                                <?php else: ?>
                                    <div class="avatar-initials" id="avatarInitials"><?= $initials ?></div>
                                <?php endif; ?>
                                <div class="avatar-overlay">Change Photo</div>
                            </label>
                            <div class="avatar-name"><?= htmlspecialchars($user['name']) ?></div>
                            <div class="avatar-role"><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?></div>
                            <div class="avatar-meta">@<?= htmlspecialchars($user['username']) ?> · ID
                                <?= str_pad($user_id, 4, '0', STR_PAD_LEFT) ?></div>
                        </div>

                        <!-- Account form -->
                        <form method="POST" enctype="multipart/form-data">
                            <?= csrfTokenField() ?>
                            <input type="file" id="picInput" name="profile_picture"
                                accept="image/jpeg,image/jpg,image/png,image/gif" class="hidden"
                                onchange="previewPic(this)">

                            <div class="card-body">
                                <?php if ($success): ?>
                                    <div class="alert alert-ok">✓ <?= htmlspecialchars($success) ?></div>
                                <?php elseif ($error): ?>
                                    <div class="alert alert-danger">⚠ <?= htmlspecialchars($error) ?></div>
                                <?php endif; ?>

                                <!-- Photo upload strip -->
                                <div class="fg">
                                    <label class="fg-label">Profile Photo</label>
                                    <label for="picInput" class="fg-file-btn" id="fileLabel">
                                        ↑ Upload new photo (JPEG, PNG, GIF · max 2 MB)
                                    </label>
                                    <div class="fg-file-name hidden" id="fileName"></div>
                                </div>

                                <div class="fg">
                                    <label class="fg-label">Full Name <span class="req">*</span></label>
                                    <input type="text" name="name" class="fg-input"
                                        value="<?= htmlspecialchars($user['name']) ?>" required autocomplete="off">
                                </div>

                                <div class="fg">
                                    <label class="fg-label">Username <span class="req">*</span></label>
                                    <input type="text" name="username" class="fg-input"
                                        value="<?= htmlspecialchars($user['username']) ?>" required autocomplete="off">
                                </div>

                                <div class="fg">
                                    <label class="fg-label">New Password</label>
                                    <input type="password" name="password" class="fg-input"
                                        placeholder="Leave blank to keep current password">
                                    <div class="fg-hint">Only fill this in if you want to change your password.</div>
                                </div>

                                <button type="submit" class="btn btn-primary"
                                    style="width:100%;justify-content:center;margin-top:4px;">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ════════════════════════════
                     RIGHT: Reports + Backup
                ════════════════════════════ -->
                <div>

                    <!-- File Reports -->
                    <div class="set-card">
                        <div class="card-head">
                            <span class="card-title">File Reports</span>
                            <button class="btn btn-ghost btn-sm" onclick="loadFileReports()">↺ Refresh</button>
                        </div>
                        <table class="rep-table">
                            <thead>
                                <tr>
                                    <th>Report</th>
                                    <th style="text-align:center;">Records</th>
                                    <th>Last Updated</th>
                                    <th style="text-align:right;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="fileReportsBody">
                                <tr>
                                    <td colspan="4"
                                        style="padding:20px;text-align:center;color:var(--ink-faint);font-style:italic;">
                                        Loading reports…</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Database Backup -->
                    <div class="set-card" style="margin-top:22px;">
                        <div class="card-head">
                            <span class="card-title">Database Backup</span>
                        </div>
                        <div class="card-body">
                            <div class="backup-panel">
                                <!-- Trigger -->
                                <div>
                                    <div class="fg">
                                        <label class="fg-label">Backup Description</label>
                                        <input type="text" id="backupDescription" class="fg-input"
                                            placeholder="Optional description for this backup…">
                                    </div>
                                    <button class="btn btn-primary" id="backupBtn" onclick="triggerBackup()"
                                        style="width:100%;justify-content:center;">
                                        ↓ Generate &amp; Download Backup
                                    </button>
                                    <div
                                        style="margin-top:10px;font-size:10.5px;color:var(--ink-faint);line-height:1.6;">
                                        Creates a full SQL dump of the database and downloads it to your computer. A
                                        record is logged in backup history.
                                    </div>
                                </div>
                                <!-- Archive icon -->
                                <div class="backup-icon-block">
                                    <div class="backup-icon">🗄</div>
                                    <div class="backup-icon-label">SQL Archive</div>
                                    <div style="font-family:var(--f-mono);font-size:9px;color:var(--ink-faint);text-align:center;line-height:1.8;"
                                        id="lastBackupMeta">—</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Backup History -->
                    <div class="set-card" style="margin-top:22px;">
                        <div class="card-head">
                            <span class="card-title">Backup History</span>
                            <button class="btn btn-ghost btn-sm" onclick="loadBackupHistory()">↺ Refresh</button>
                        </div>
                        <div style="overflow-x:auto;">
                            <table class="bk-table">
                                <thead>
                                    <tr>
                                        <th>Date &amp; Time</th>
                                        <th>Size</th>
                                        <th>Description</th>
                                        <th>Performed By</th>
                                    </tr>
                                </thead>
                                <tbody id="backupHistoryBody">
                                    <tr>
                                        <td colspan="4"
                                            style="padding:20px;text-align:center;color:var(--ink-faint);font-style:italic;">
                                            Loading backup history…</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div><!-- /right -->
            </div><!-- /grid -->

        </main>
    </div>

    <script>
        /* ── Profile photo preview ── */
        function previewPic(input) {
            const file = input.files[0];
            if (!file) return;
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileName').classList.remove('hidden');

            const reader = new FileReader();
            reader.onload = function (e) {
                const ring = document.querySelector('.avatar-ring');
                const initDiv = document.getElementById('avatarInitials');
                if (initDiv) initDiv.remove();
                let img = document.getElementById('avatarPreview');
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'avatarPreview';
                    img.className = 'avatar-img';
                    img.alt = 'Profile';
                    ring.insertBefore(img, ring.querySelector('.avatar-overlay'));
                }
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }

        /* ── File Reports ── */
        function loadFileReports() {
            const tbody = document.getElementById('fileReportsBody');
            tbody.innerHTML = '<tr><td colspan="4" style="padding:16px;text-align:center;color:var(--ink-faint);">Loading…</td></tr>';

            // cache: 'no-store' forces a fresh network request every time
            fetch('/secretary/profile/file_reports.php?t=' + Date.now(), { cache: 'no-store' })
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'ok' || !data.data.length) {
                        tbody.innerHTML = '<tr><td colspan="4" style="padding:16px;text-align:center;color:var(--ink-faint);font-style:italic;">No data available.</td></tr>';
                        return;
                    }
                    tbody.innerHTML = data.data.map(r => `
                    <tr>
                        <td><div class="rep-label">${escHtml(r.label)}</div></td>
                        <td style="text-align:center;"><span class="rep-count">${Number(r.count).toLocaleString()}</span></td>
                        <td><div class="rep-time">${escHtml(r.last_updated)}</div></td>
                        <td style="text-align:right;">
                            <button class="btn btn-ghost btn-sm" onclick="printReport('${escHtml(r.print_url)}')">
                                ↗ Print
                            </button>
                        </td>
                    </tr>
                `).join('');
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="4" style="padding:16px;text-align:center;color:var(--danger-fg);">Failed to load reports.</td></tr>';
                });
        }

        function printReport(url) { window.open(url, '_blank', 'width=1000,height=700'); }

        /* ── Backup ── */
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        function triggerBackup() {
            const btn = document.getElementById('backupBtn');
            const desc = document.getElementById('backupDescription').value.trim() || 'Manual Backup';

            btn.disabled = true;
            btn.innerHTML = '<span class="spin"></span> Generating…';

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/secretary/profile/backup.php';

            [['csrf_token', csrfToken], ['description', desc]].forEach(([n, v]) => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = n; inp.value = v;
                form.appendChild(inp);
            });
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            setTimeout(() => {
                btn.disabled = false;
                btn.innerHTML = '↓ Generate &amp; Download Backup';
                document.getElementById('backupDescription').value = '';
                loadBackupHistory();
            }, 3000);
        }

        function loadBackupHistory() {
            const tbody = document.getElementById('backupHistoryBody');
            tbody.innerHTML = '<tr><td colspan="4" style="padding:16px;text-align:center;color:var(--ink-faint);">Loading…</td></tr>';

            // cache: 'no-store' forces a fresh network request every time
            fetch('/secretary/profile/backup_history.php?t=' + Date.now(), { cache: 'no-store' })
                .then(r => r.json())
                .then(data => {
                    if (data.status !== 'ok' || !data.data.length) {
                        tbody.innerHTML = '<tr><td colspan="4" style="padding:16px;text-align:center;color:var(--ink-faint);font-style:italic;">No backups recorded yet.</td></tr>';
                        document.getElementById('lastBackupMeta').textContent = 'No backup yet';
                        return;
                    }
                    // Update archive meta
                    const latest = data.data[0];
                    document.getElementById('lastBackupMeta').textContent =
                        `Last: ${latest.date_formatted}\n${latest.time_formatted}\n${latest.size_formatted}`;

                    tbody.innerHTML = data.data.map(r => `
                    <tr>
                        <td>
                            <div class="bk-date">${escHtml(r.date_formatted)}</div>
                            <div class="bk-time">${escHtml(r.time_formatted)}</div>
                        </td>
                        <td><span class="bk-size">${escHtml(r.size_formatted)}</span></td>
                        <td><span class="bk-desc">${escHtml(r.description || 'Manual Backup')}</span></td>
                        <td><span class="bk-by">${escHtml(r.performed_by_name)}</span></td>
                    </tr>
                `).join('');
                })
                .catch(() => {
                    tbody.innerHTML = '<tr><td colspan="4" style="padding:16px;text-align:center;color:var(--danger-fg);">Failed to load backup history.</td></tr>';
                });
        }

        function escHtml(s) {
            const d = document.createElement('div');
            d.textContent = s || '';
            return d.innerHTML;
        }

        /* ── Boot ── */
        $(function () {
            $('body').show();
            loadFileReports();
            loadBackupHistory();
        });
    </script>
</body>

</html>