<?php
/**
 * Login Page
 * Replaces: public/login/index.php
 */
include_once '../navigator.php';
require_once '../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        if ($stmt === false) {
            error_log('Login query preparation failed: ' . $conn->error);
            $error = "Database error. Please try again later.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    if ($user['status'] !== 'active') {
                        $error = "Account is " . htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8') . ". Please contact admin.";
                    } else {
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['role'] = $user['role'];
                        header("Location: /navigator.php");
                        exit;
                    }
                } else {
                    $error = "Invalid username or password.";
                }
            } else {
                $error = "Invalid username or password.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MIS Barangay — Login</title>
    <link rel="icon" type="image/x-icon" href="/assets/images/logo.ico">
    <?php loadAllAssets(); ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Source+Serif+4:ital,wght@0,300;0,400;0,600;0,700;1,400&family=Source+Sans+3:wght@300;400;500;600;700&family=Source+Code+Pro:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --accent:    var(--theme-primary, #2d5a27);
        --accent-dk: color-mix(in srgb, var(--accent) 70%, black);
        --accent-lt: color-mix(in srgb, var(--accent) 8%, white);
        --paper:     #fdfcf9;
        --ink:       #1a1a1a;
        --muted:     #5a5a5a;
        --faint:     #a0a0a0;
        --rule:      #d8d4cc;
        --danger:    #7a1f1a;
        --f-serif:   'Source Serif 4', Georgia, serif;
        --f-sans:    'Source Sans 3', 'Segoe UI', sans-serif;
        --f-mono:    'Source Code Pro', 'Courier New', monospace;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: var(--f-sans); background: #edeae4; min-height: 100vh; display: flex; }

    /* ── LEFT PANEL ── */
    .lp {
        flex: 1;
        background: var(--accent);
        background-image:
            radial-gradient(ellipse at 20% 80%, color-mix(in srgb, var(--accent) 60%, black) 0%, transparent 60%),
            radial-gradient(ellipse at 80% 20%, color-mix(in srgb, var(--accent) 80%, white) 0%, transparent 50%);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 52px 56px;
        position: relative;
        overflow: hidden;
        min-height: 100vh;
    }
    /* grain overlay */
    .lp::after {
        content: '';
        position: absolute; inset: 0;
        background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.04'/%3E%3C/svg%3E");
        pointer-events: none;
    }
    .lp-top { position: relative; z-index: 1; }
    .lp-seal {
        width: 56px; height: 56px;
        background: rgba(255,255,255,.12);
        border: 1.5px solid rgba(255,255,255,.3);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 26px;
        margin-bottom: 28px;
        backdrop-filter: blur(4px);
    }
    .lp-republic {
        font-size: 9px; font-weight: 700; letter-spacing: 2px;
        text-transform: uppercase; color: rgba(255,255,255,.5);
        margin-bottom: 6px;
    }
    .lp-brgy {
        font-family: var(--f-serif);
        font-size: clamp(28px, 4vw, 42px);
        font-weight: 700;
        color: #fff;
        line-height: 1.1;
        letter-spacing: -.5px;
        margin-bottom: 10px;
    }
    .lp-office {
        font-size: 11px; font-weight: 600; letter-spacing: 1.2px;
        text-transform: uppercase;
        color: rgba(255,255,255,.5);
    }
    .lp-desc {
        margin-top: 32px;
        font-size: 14px;
        color: rgba(255,255,255,.6);
        line-height: 1.7;
        max-width: 420px;
    }

    /* feature pills */
    .lp-pills {
        display: flex; flex-wrap: wrap; gap: 8px;
        margin-top: 28px;
        position: relative; z-index: 1;
    }
    .lp-pill {
        padding: 5px 13px;
        background: rgba(255,255,255,.1);
        border: 1px solid rgba(255,255,255,.2);
        border-radius: 999px;
        font-size: 11px; font-weight: 600; color: rgba(255,255,255,.75);
        backdrop-filter: blur(4px);
    }

    .lp-bottom {
        position: relative; z-index: 1;
        border-top: 1px solid rgba(255,255,255,.12);
        padding-top: 20px;
        display: flex; align-items: center; gap: 14px;
    }
    .lp-photo {
        width: 100%;
        max-width: 480px;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,.15);
        box-shadow: 0 20px 60px rgba(0,0,0,.3);
        margin-top: 36px;
        position: relative; z-index: 1;
    }
    .lp-photo img {
        width: 100%;
        display: block;
        object-fit: cover;
        max-height: 280px;
    }
    .lp-footer-txt {
        font-family: var(--f-mono);
        font-size: 9px; letter-spacing: 1px;
        text-transform: uppercase;
        color: rgba(255,255,255,.35);
    }

    /* ── RIGHT PANEL (form) ── */
    .rp {
        width: 420px;
        flex-shrink: 0;
        background: var(--paper);
        display: flex;
        flex-direction: column;
        justify-content: center;
        padding: 52px 48px;
        box-shadow: -4px 0 40px rgba(0,0,0,.12);
        position: relative;
    }
    .rp-eyebrow {
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.8px;
        text-transform: uppercase; color: var(--accent);
        display: flex; align-items: center; gap: 8px;
        margin-bottom: 10px;
    }
    .rp-eyebrow::before {
        content: '';
        width: 18px; height: 2px;
        background: var(--accent);
        display: inline-block;
        flex-shrink: 0;
    }
    .rp-title {
        font-family: var(--f-serif);
        font-size: 28px; font-weight: 700;
        color: var(--ink); letter-spacing: -.4px;
        margin-bottom: 6px;
    }
    .rp-sub {
        font-size: 13px; color: var(--faint);
        font-style: italic;
        margin-bottom: 36px;
    }

    /* error banner */
    .err-banner {
        background: #fdeeed;
        border: 1px solid color-mix(in srgb, var(--danger) 25%, transparent);
        border-left: 3px solid var(--danger);
        border-radius: 2px;
        padding: 11px 14px;
        font-size: 12.5px;
        color: var(--danger);
        margin-bottom: 20px;
        display: flex; align-items: center; gap: 8px;
    }
    .err-banner::before { content: '⚠'; flex-shrink: 0; }

    /* form fields */
    .fg { margin-bottom: 18px; }
    .fg-label {
        display: block;
        font-size: 8.5px; font-weight: 700; letter-spacing: 1.2px;
        text-transform: uppercase; color: var(--muted);
        margin-bottom: 7px;
    }
    .fg-input {
        width: 100%; padding: 11px 14px;
        border: 1.5px solid var(--rule); border-radius: 2px;
        font-family: var(--f-sans); font-size: 13.5px; color: var(--ink);
        background: #fff; outline: none;
        transition: border-color .15s, box-shadow .15s;
    }
    .fg-input:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 10%, transparent);
    }
    .fg-input::placeholder { color: #c0bbb4; font-style: italic; }

    /* submit */
    .btn-login {
        width: 100%; padding: 12px;
        background: var(--accent); border: 1.5px solid var(--accent);
        border-radius: 2px; color: #fff;
        font-family: var(--f-sans); font-size: 12px; font-weight: 700;
        letter-spacing: .6px; text-transform: uppercase;
        cursor: pointer; transition: filter .13s;
        margin-top: 8px;
    }
    .btn-login:hover { filter: brightness(1.1); }

    .rp-footer {
        position: absolute; bottom: 28px; left: 48px; right: 48px;
        text-align: center;
        font-family: var(--f-mono);
        font-size: 8.5px; letter-spacing: .8px;
        text-transform: uppercase; color: var(--rule);
    }

    @media (max-width: 900px) {
        body { flex-direction: column; }
        .lp { min-height: auto; padding: 36px 28px; }
        .lp-desc, .lp-photo { display: none; }
        .rp { width: 100%; padding: 40px 28px; box-shadow: none; }
    }
    </style>
</head>
<body style="display:none;">

    <!-- ── LEFT PANEL ── -->
    <div class="lp">
        <div class="lp-top">
            <div class="lp-seal">🏛️</div>
            <div class="lp-republic">Republic of the Philippines</div>
            <div class="lp-brgy">Barangay<br>Bombongan</div>
            <div class="lp-office">Management Information System</div>
            <p class="lp-desc">
                A unified digital platform for barangay governance — managing residents,
                health records, certificates, blotter, and community scheduling in one place.
            </p>
            <div class="lp-pills">
                <span class="lp-pill">Residents</span>
                <span class="lp-pill">Health Center</span>
                <span class="lp-pill">Certificates</span>
                <span class="lp-pill">Blotter</span>
                <span class="lp-pill">Scheduling</span>
                <span class="lp-pill">Inventory</span>
            </div>

            <div class="lp-photo">
                <img src="/assets/images/brgy-hall.jpg" alt="Barangay Hall"
                     onerror="this.parentElement.style.display='none'">
            </div>
        </div>

        <div class="lp-bottom">
            <span class="lp-footer-txt">© <?= date('Y') ?> Barangay Bombongan · Morong, Rizal</span>
        </div>
    </div>

    <!-- ── RIGHT PANEL ── -->
    <div class="rp">
        <div class="rp-eyebrow">Secure Access</div>
        <div class="rp-title">System Login</div>
        <div class="rp-sub">Enter your credentials to continue</div>

        <?php if (!empty($error)): ?>
        <div class="err-banner"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="fg">
                <label class="fg-label">Username</label>
                <input type="text" name="username" class="fg-input"
                       placeholder="Enter your username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES) ?>"
                       required autofocus>
            </div>
            <div class="fg">
                <label class="fg-label">Password</label>
                <input type="password" name="password" class="fg-input"
                       placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn-login">Sign In →</button>
        </form>

        <?php if (isset($_GET['error'])): ?>
        <div class="err-banner" style="margin-top:16px;margin-bottom:0;">
            <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <div class="rp-footer">MIS Barangay Bombongan · <?= date('Y') ?></div>
    </div>

    <script>$(document).ready(function(){ $('body').show(); });</script>
</body>
</html>