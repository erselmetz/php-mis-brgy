<?php
require_once __DIR__ . '/../../../includes/app.php';
requireSecretary();

$id = intval($_GET['id'] ?? 0);
if ($id === 0) {
    header("Location: ../certificate/");
    exit;
}

// Fetch certificate + resident details
$stmt = $conn->prepare("
    SELECT cr.*, r.*, u.name as issued_by_name
    FROM certificate_request cr
    INNER JOIN residents r ON cr.resident_id = r.id
    LEFT JOIN users u ON cr.issued_by = u.id
    WHERE cr.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: ../certificate/");
    exit;
}

// Fetch active barangay captain
$captainStmt = $conn->prepare("
    SELECT u.name
    FROM users u
    WHERE u.role = 'captain' AND u.status = 'active'
    ORDER BY u.id DESC
    LIMIT 1
");
$captainStmt->execute();
$captain = $captainStmt->get_result()->fetch_assoc();
$barangay_captain_name = strtoupper($captain['name'] ?? 'BARANGAY CAPTAIN');
$captainStmt->close();

$cert = $result->fetch_assoc();
$stmt->close();

// Computed values
$birthDate   = new DateTime($cert['birthdate']);
$age         = $birthDate->diff(new DateTime())->y;
$currentDate = date('F d, Y');
$currentYear = date('Y');
$fullName    = strtoupper(trim(
    $cert['first_name'] . ' ' .
    ($cert['middle_name'] ? $cert['middle_name'] . ' ' : '') .
    $cert['last_name']
));

// ──────────────────────────────────────────────────────────────
// CERTIFICATE BODY TEMPLATES
// To add a new certificate type:
//   1. Add a new case to the match below
//   2. Return an array with 'paragraphs' (array of strings)
//   3. Each paragraph will be indented and rendered automatically
// ──────────────────────────────────────────────────────────────
function getCertificateBody(array $cert, string $currentDate): array
{
    $purpose = htmlspecialchars($cert['purpose']);
    $date    = "<strong>{$currentDate}</strong>";

    return match ($cert['certificate_type']) {

        'Barangay Clearance' => [
            'paragraphs' => [
                "This certification is issued upon the request of the above-named person for
                 <strong>{$purpose}</strong> and whatever legal purpose it may serve.",

                "This certification is issued this {$date} at Barangay Bongbongan, Morong, Rizal.",
            ],
        ],

        'Indigency Certificate' => [
            'paragraphs' => [
                "This is to certify further that the above-named person belongs to an indigent
                 family in this barangay and is in need of financial assistance for
                 <strong>{$purpose}</strong>.",

                "This certification is issued this {$date} at Barangay Bongbongan, Morong, Rizal.",
            ],
        ],

        'Residency Certificate' => [
            'paragraphs' => [
                "This is to certify that the above-named person is a bonafide resident of this
                 barangay and has been residing at the above-mentioned address for the purpose of
                 <strong>{$purpose}</strong>.",

                "This certification is issued this {$date} at Barangay Bongbongan, Morong, Rizal.",
            ],
        ],

        // ── ADD NEW CERTIFICATE TYPES BELOW ──────────────────────────────────
        // Example:
        // 'Business Clearance' => [
        //     'paragraphs' => [
        //         "This is to certify that ...",
        //         "This certification is issued this {$date} ...",
        //     ],
        // ],
        // ─────────────────────────────────────────────────────────────────────

        default => [
            'paragraphs' => [
                "This certification is issued upon the request of the above-named person for
                 <strong>{$purpose}</strong> and whatever legal purpose it may serve.",

                "This certification is issued this {$date} at Barangay Bongbongan, Morong, Rizal.",
            ],
        ],
    };
}

$certBody = getCertificateBody($cert, $currentDate);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($cert['certificate_type']) ?> — <?= $fullName ?></title>
    <?= loadAllAssets(); ?>
    <style>
        /* ── Page Setup ────────────────────────────────────── */
        @page {
            size: A4;
            margin: 0.75in;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Times New Roman', Times, serif;
            background: #e5e7eb;
            margin: 0;
            padding: 20px;
            color: #1a1a1a;
        }

        /* ── Print Controls (hidden on print) ──────────────── */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 2px solid var(--theme-primary, #2d6a4f);
            border-radius: 10px;
            padding: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
            z-index: 1000;
            min-width: 200px;
        }

        .print-controls h3 {
            margin: 0 0 12px 0;
            font-size: 14px;
            font-family: sans-serif;
            color: var(--theme-primary, #2d6a4f);
            font-weight: 700;
        }

        .control-group {
            margin-bottom: 10px;
            font-family: sans-serif;
        }

        .control-group label {
            display: block;
            font-size: 11px;
            margin-bottom: 4px;
            color: #555;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .control-group select {
            width: 100%;
            padding: 5px 8px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 12px;
            background: #f9fafb;
        }

        .print-button {
            width: 100%;
            background: var(--theme-primary, #2d6a4f);
            color: white;
            padding: 10px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
            margin-top: 10px;
            font-family: sans-serif;
            letter-spacing: 0.3px;
            transition: background 0.2s;
        }

        .print-button:hover {
            filter: brightness(1.1);
        }

        /* ── Certificate Paper ──────────────────────────────── */
        .certificate {
            background: white;
            max-width: 8.27in;
            margin: 0 auto;
            padding: 40px 50px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            position: relative;

            /* Double border effect */
            border: 4px double #1a4a2e;
            outline: 1px solid #2d6a4f;
            outline-offset: -10px;
        }

        /* ── Header ─────────────────────────────────────────── */
        .cert-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            margin-bottom: 6px;
            padding-bottom: 14px;
            border-bottom: 2px solid #1a4a2e;
        }

        .cert-header .logo {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
            object-fit: contain;
        }

        .cert-header .logo-placeholder {
            width: 80px;
            height: 80px;
            flex-shrink: 0;
            border: 2px dashed #9ca3af;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            color: #9ca3af;
            text-align: center;
        }

        .cert-header .header-text {
            text-align: center;
        }

        .cert-header .header-text .republic {
            font-size: 11px;
            font-style: italic;
            color: #444;
            margin: 0 0 2px 0;
            letter-spacing: 0.3px;
        }

        .cert-header .header-text .province,
        .cert-header .header-text .municipality {
            font-size: 13px;
            font-weight: 600;
            margin: 1px 0;
            color: #222;
        }

        .cert-header .header-text .barangay-name {
            font-size: 22px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #1a4a2e;
            margin: 4px 0 2px 0;
        }

        .cert-header .header-text .office-label {
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #555;
            font-weight: 600;
        }

        /* ── Certificate Type Title ─────────────────────────── */
        .cert-type-title {
            text-align: center;
            margin: 22px 0 18px 0;
        }

        .cert-type-title h2 {
            display: inline-block;
            font-size: 17px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #1a4a2e;
            border-bottom: 2px solid #1a4a2e;
            padding-bottom: 4px;
            margin: 0;
        }

        /* ── Divider ─────────────────────────────────────────── */
        .cert-divider {
            border: none;
            border-top: 1px solid #ccc;
            margin: 16px 0;
        }

        /* ── Body Text ──────────────────────────────────────── */
        .cert-body {
            font-size: 13.5px;
            line-height: 1.9;
            text-align: justify;
            color: #1a1a1a;
        }

        .cert-body p {
            margin: 14px 0;
            text-indent: 60px;
        }

        /* ── Signatures ─────────────────────────────────────── */
        .cert-signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 50px;
            gap: 20px;
        }

        .sig-block {
            text-align: center;
            width: 45%;
        }

        .sig-line-space {
            height: 48px; /* space for actual signature */
        }

        .sig-line {
            border-top: 1.5px solid #1a4a2e;
            padding-top: 6px;
        }

        .sig-name {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .sig-title {
            font-size: 11px;
            color: #555;
            margin: 2px 0 0 0;
            font-style: italic;
        }

        /* ── Footer ─────────────────────────────────────────── */
        .cert-footer {
            margin-top: 30px;
            padding-top: 12px;
            border-top: 1px solid #d1d5db;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 11px;
            color: #555;
        }

        .cert-footer .or-block {
            font-size: 11px;
        }

        .cert-footer .cert-no {
            font-size: 11px;
            text-align: right;
        }

        /* ── Print Overrides ────────────────────────────────── */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .no-print {
                display: none !important;
            }

            .certificate {
                box-shadow: none;
                max-width: 100%;
                padding: 30px 40px;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <!-- ── Print Controls ──────────────────────────────────── -->
    <div class="print-controls no-print">
        <h3>🖨️ Print Options</h3>
        <div class="control-group">
            <label for="scaleSelect">Scale</label>
            <select id="scaleSelect" onchange="applyScale()">
                <option value="1.0">100% (Default)</option>
                <option value="0.95">95%</option>
                <option value="0.90">90%</option>
                <option value="0.85">85%</option>
                <option value="0.80">80%</option>
            </select>
        </div>
        <div class="control-group">
            <label for="marginSelect">Margins</label>
            <select id="marginSelect" onchange="applyMargins()">
                <option value="0.75">Normal (0.75in)</option>
                <option value="0.5">Small (0.5in)</option>
                <option value="0.25">Minimal (0.25in)</option>
                <option value="1.0">Large (1.0in)</option>
            </select>
        </div>
        <button class="print-button" onclick="update_document_status()">🖨️ Print Certificate</button>
    </div>

    <!-- ── Certificate Paper ────────────────────────────────── -->
    <div class="certificate" id="certificateContent">

        <!-- HEADER -->
        <div class="cert-header">
            <?php
            // Try favicon.ico first, then favicon.png, then show placeholder
            $logoPath  = __DIR__ . '/../../../favicon.ico';
            $logoPath2 = __DIR__ . '/../../../favicon.png';
            $logoSrc   = null;

            if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoSrc  = 'data:image/x-icon;base64,' . $logoData;
            } elseif (file_exists($logoPath2)) {
                $logoData = base64_encode(file_get_contents($logoPath2));
                $logoSrc  = 'data:image/png;base64,' . $logoData;
            }
            ?>

            <?php if ($logoSrc): ?>
                <img src="<?= $logoSrc ?>" alt="Barangay Logo" class="logo">
            <?php else: ?>
                <div class="logo-placeholder">BRGY<br>LOGO</div>
            <?php endif; ?>

            <div class="header-text">
                <p class="republic">Republic of the Philippines</p>
                <p class="province">Province of Rizal</p>
                <p class="municipality">Municipality of Morong</p>
                <p class="barangay-name">Barangay Bongbongan</p>
                <p class="office-label">Office of the Barangay Captain</p>
            </div>

            <?php if ($logoSrc): ?>
                <img src="<?= $logoSrc ?>" alt="Barangay Logo" class="logo">
            <?php else: ?>
                <div class="logo-placeholder">BRGY<br>LOGO</div>
            <?php endif; ?>
        </div>

        <!-- CERTIFICATE TYPE -->
        <div class="cert-type-title">
            <h2><?= htmlspecialchars($cert['certificate_type']) ?></h2>
        </div>

        <hr class="cert-divider">

        <!-- BODY -->
        <div class="cert-body">
            <!-- Opening paragraph (common to all types) -->
            <p>
                This is to certify that
                <strong><?= $fullName ?></strong>,
                <?= $age ?> years old,
                <?= htmlspecialchars($cert['gender']) ?>,
                <?= htmlspecialchars($cert['civil_status'] ?? 'Single') ?>,
                Filipino, and a resident of
                <?= htmlspecialchars($cert['address']) ?>,
                Barangay Bongbongan, Morong, Rizal.
            </p>

            <!-- Type-specific paragraphs -->
            <?php foreach ($certBody['paragraphs'] as $para): ?>
                <p><?= $para ?></p>
            <?php endforeach; ?>
        </div>

        <hr class="cert-divider">

        <!-- SIGNATURES -->
        <div class="cert-signatures">
            <div class="sig-block">
                <div class="sig-line-space"></div>
                <div class="sig-line">
                    <p class="sig-name"><?= htmlspecialchars(strtoupper($cert['issued_by_name'] ?? 'Staff')) ?></p>
                    <p class="sig-title">Barangay Secretary</p>
                    <p class="sig-title">Prepared by</p>
                </div>
            </div>

            <div class="sig-block">
                <div class="sig-line-space"></div>
                <div class="sig-line">
                    <p class="sig-name"><?= htmlspecialchars($barangay_captain_name) ?></p>
                    <p class="sig-title">Barangay Captain</p>
                    <p class="sig-title">Certified by</p>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="cert-footer">
            <div class="or-block">
                OR No.: _________________________ &nbsp;&nbsp; Amount Paid: _____________
            </div>
            <div class="cert-no">
                CTC No.: _________________________ <br>
                Date Issued: <?= $currentDate ?>
            </div>
        </div>

    </div><!-- /.certificate -->

    <script src="./js/print.js"></script>
</body>
</html>