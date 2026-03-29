<?php
require_once __DIR__ . '/../../includes/app.php';
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
$birthDate = new DateTime($cert['birthdate']);
$age = $birthDate->diff(new DateTime())->y;
$currentDate = date('F d, Y');
$currentYear = date('Y');
$fullName = strtoupper(trim(
    $cert['first_name'] . ' ' .
    ($cert['middle_name'] ? $cert['middle_name'] . ' ' : '') .
    $cert['last_name']
));

function getCertificateBody(array $cert, string $currentDate): array
{
    $purpose = htmlspecialchars($cert['purpose']);
    $date = "<strong>{$currentDate}</strong>";

    return match ($cert['certificate_type']) {
        'Barangay Clearance' => [
            'paragraphs' => [
                "This certification is issued upon the request of the above-named person for
                 <strong>{$purpose}</strong> and whatever legal purpose it may serve.",
                "This certification is issued this {$date} at Barangay Bombongan, Morong, Rizal.",
            ],
        ],
        'Indigency Certificate' => [
            'paragraphs' => [
                "This is to certify further that the above-named person belongs to an indigent
                 family in this barangay and is in need of financial assistance for
                 <strong>{$purpose}</strong>.",
                "This certification is issued this {$date} at Barangay Bombongan, Morong, Rizal.",
            ],
        ],
        'Residency Certificate' => [
            'paragraphs' => [
                "This is to certify that the above-named person is a bonafide resident of this
                 barangay and has been residing at the above-mentioned address for the purpose of
                 <strong>{$purpose}</strong>.",
                "This certification is issued this {$date} at Barangay Bombongan, Morong, Rizal.",
            ],
        ],
        default => [
            'paragraphs' => [
                "This certification is issued upon the request of the above-named person for
                 <strong>{$purpose}</strong> and whatever legal purpose it may serve.",
                "This certification is issued this {$date} at Barangay Bombongan, Morong, Rizal.",
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
    /* ── Reset ── */
    * { box-sizing: border-box; margin: 0; padding: 0; }

    /* ── Screen: body background ── */
    body {
        font-family: 'Times New Roman', Times, serif;
        background: #e5e7eb;
        padding: 20px;
        color: #1a1a1a;
    }

    /* ── Print controls (hidden on print) ── */
    .print-controls {
        position: fixed;
        top: 20px; right: 20px;
        background: white;
        border: 2px solid #2d6a4f;
        border-radius: 10px;
        padding: 16px;
        box-shadow: 0 4px 15px rgba(0,0,0,.15);
        z-index: 1000;
        min-width: 200px;
    }
    .print-controls h3 {
        margin: 0 0 12px;
        font-size: 14px;
        font-family: sans-serif;
        color: #2d6a4f;
        font-weight: 700;
    }
    .control-group { margin-bottom: 10px; font-family: sans-serif; }
    .control-group label {
        display: block; font-size: 11px; margin-bottom: 4px;
        color: #555; font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
    }
    .control-group select {
        width: 100%; padding: 5px 8px;
        border: 1px solid #d1d5db; border-radius: 6px;
        font-size: 12px; background: #f9fafb;
    }
    .print-button {
        width: 100%; background: #2d6a4f; color: white;
        padding: 10px; border: none; border-radius: 7px;
        cursor: pointer; font-size: 13px; font-weight: bold;
        margin-top: 10px; font-family: sans-serif;
        transition: background .2s;
    }
    .print-button:hover { background: #235a40; }

    /* ═══════════════════════════════════════════
       CERTIFICATE PAPER
       Uses flex column so we can pin header top,
       body center, signatures+footer bottom.
    ═══════════════════════════════════════════ */
    .certificate {
        background: white;
        max-width: 8.27in;          /* A4 width default */
        margin: 0 auto;
        padding: 40px 50px;
        box-shadow: 0 4px 20px rgba(0,0,0,.2);

        /* Double border */
        border: 4px double #1a4a2e;
        outline: 1px solid #2d6a4f;
        outline-offset: -10px;

        /* FULL PAGE LAYOUT */
        display: flex;
        flex-direction: column;

        /* Screen preview height — updated by applyPageStyle() JS */
        min-height: 11.03in;    /* A4 default: 297mm ≈ 11.03in */

        /* Watermark sits inside, so position:relative is needed */
        position: relative;
    }

    /* ── Watermark ── */
    .cert-watermark {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 600px;
        height: 600px;
        pointer-events: none;
        z-index: 0;

        /* Faded — just barely visible behind content */
        opacity: 0.05;
        filter: grayscale(30%);
    }

    /* Keep all content above the watermark */
    .cert-header,
    .cert-type-title,
    .cert-divider,
    .cert-body-section,
    .cert-bottom {
        position: relative;
        z-index: 1;
    }

    /* ── Header — stays at top ── */
    .cert-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        padding-bottom: 14px;
        border-bottom: 2px solid #1a4a2e;
        flex-shrink: 0;             /* never compress */
    }
    .cert-header .logo {
        width: 80px; height: 80px;
        flex-shrink: 0; object-fit: contain;
    }
    .cert-header .logo-placeholder {
        width: 80px; height: 80px; flex-shrink: 0;
        border: 2px dashed #9ca3af; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 10px; color: #9ca3af; text-align: center;
    }
    .cert-header .header-text { text-align: center; }
    .cert-header .header-text .republic  { font-size: 11px; font-style: italic; color: #444; margin: 0 0 2px; }
    .cert-header .header-text .province,
    .cert-header .header-text .municipality { font-size: 13px; font-weight: 600; margin: 1px 0; color: #222; }
    .cert-header .header-text .barangay-name {
        font-size: 22px; font-weight: 900; text-transform: uppercase;
        letter-spacing: 2px; color: #1a4a2e; margin: 4px 0 2px;
    }
    .cert-header .header-text .office-label {
        font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase;
        color: #555; font-weight: 600;
    }

    /* ── Certificate type title ── */
    .cert-type-title {
        text-align: center;
        margin: 18px 0 0;
        flex-shrink: 0;
    }
    .cert-type-title h2 {
        display: inline-block;
        font-size: 17px; font-weight: 900;
        text-transform: uppercase; letter-spacing: 3px; color: #1a4a2e;
        border-bottom: 2px solid #1a4a2e; padding-bottom: 4px; margin: 0;
    }

    /* ── Divider ── */
    .cert-divider {
        border: none; border-top: 1px solid #ccc;
        margin: 14px 0;
        flex-shrink: 0;
    }

    /* ══════════════════════════════════════════
       BODY SECTION — grows to fill available
       space and centers content vertically
    ══════════════════════════════════════════ */
    .cert-body-section {
        flex: 1;                        /* take all remaining vertical space */
        display: flex;
        flex-direction: column;
        justify-content: center;        /* vertically center the text */
        padding: 10px 0;
    }

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

    /* ══════════════════════════════════════════
       BOTTOM SECTION — signatures + footer
       pinned to the bottom of the page
    ══════════════════════════════════════════ */
    .cert-bottom {
        flex-shrink: 0;                 /* never compress */
        margin-top: auto;               /* push to bottom if body is short */
    }

    /* ── Signatures ── */
    .cert-signatures {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        margin-bottom: 20px;
    }
    .sig-block { text-align: center; width: 45%; }
    .sig-line-space { height: 48px; }
    .sig-line { border-top: 1.5px solid #1a4a2e; padding-top: 6px; }
    .sig-name {
        font-size: 13px; font-weight: 700;
        text-transform: uppercase; letter-spacing: .5px; margin: 0;
    }
    .sig-title { font-size: 11px; color: #555; margin: 2px 0 0; font-style: italic; }

    /* ── Footer (OR No., CTC No.) ── */
    .cert-footer {
        padding-top: 12px;
        border-top: 1px solid #d1d5db;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        font-size: 11px;
        color: #555;
    }

    /* ── Print overrides ── */
    @media print {
        body { background: white; padding: 0; }
        .no-print { display: none !important; }
        .certificate {
            box-shadow: none;
            max-width: 100%;
            padding: 0px 0px;
            margin: 0;
            /* Fill the full printable area */
            min-height: 100vh;
            page-break-inside: avoid;
        }
    }

    /* Style tag injected by applyPageStyle() */
    </style>
</head>
<body>

    <!-- ── Print Controls ── -->
    <div class="print-controls no-print">
        <h3>🖨️ Print Options</h3>
        <div class="control-group">
            <label for="paperSelect">Paper Size</label>
            <select id="paperSelect" onchange="applyPageStyle()">
                <option value="A4">A4 (210 × 297mm)</option>
                <option value="letter">Letter (8.5 × 11in)</option>
                <option value="legal">Legal (8.5 × 14in)</option>
                <option value="A5">A5 (148 × 210mm)</option>
            </select>
        </div>
        <div class="control-group">
            <label for="scaleSelect">Scale</label>
            <select id="scaleSelect" onchange="applyScale()">
                <option value="1.0">100%</option>
                <option value="0.95">95%</option>
                <option value="0.90">90%</option>
                <option value="0.85">85%</option>
                <option value="0.80">80%</option>
            </select>
        </div>
        <div class="control-group">
            <label for="marginSelect">Margins</label>
            <select id="marginSelect" onchange="applyPageStyle()">
                <option value="0.75">Normal (0.75in)</option>
                <option value="0.5">Small (0.5in)</option>
                <option value="0.25">Minimal (0.25in)</option>
                <option value="1.0">Large (1.0in)</option>
            </select>
        </div>
        <button class="print-button" onclick="update_document_status()">🖨️ Print Certificate</button>
    </div>

    <!-- ══════════════════════════════════
         CERTIFICATE PAPER
    ══════════════════════════════════ -->
    <?php
    // Detect logo once — used for both header and watermark
    $logoPath  = __DIR__ . '/../../favicon.ico';
    $logoPath2 = __DIR__ . '/../../favicon.png';
    $logoSrc   = null;
    if (file_exists($logoPath))       { $logoSrc = 'data:image/x-icon;base64,' . base64_encode(file_get_contents($logoPath)); }
    elseif (file_exists($logoPath2))  { $logoSrc = 'data:image/png;base64,'    . base64_encode(file_get_contents($logoPath2)); }
    ?>
    <div class="certificate" id="certificateContent">

        <!-- WATERMARK — centered behind all content -->
        <?php if ($logoSrc): ?>
            <img src="<?= $logoSrc ?>" alt="" class="cert-watermark" aria-hidden="true">
        <?php else: ?>
            <!-- No logo file found — watermark omitted -->
        <?php endif; ?>

        <!-- HEADER — top -->
        <div class="cert-header">
            <?php /* $logoSrc already set above */ ?>
            <?php if ($logoSrc): ?>
                <img src="<?= $logoSrc ?>" alt="Barangay Logo" class="logo">
            <?php else: ?>
                <div class="logo-placeholder">BRGY<br>LOGO</div>
            <?php endif; ?>

            <div class="header-text">
                <p class="republic">Republic of the Philippines</p>
                <p class="province">Province of Rizal</p>
                <p class="municipality">Municipality of Morong</p>
                <p class="barangay-name">Barangay Bombongan</p>
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

        <!-- BODY — vertically centered in flex:1 container -->
        <div class="cert-body-section">
            <div class="cert-body">
                <!-- Opening paragraph -->
                <p>
                    This is to certify that
                    <strong><?= $fullName ?></strong>,
                    <?= $age ?> years old,
                    <?= htmlspecialchars($cert['gender']) ?>,
                    <?= htmlspecialchars($cert['civil_status'] ?? 'Single') ?>,
                    Filipino, and a resident of
                    <?= htmlspecialchars($cert['address']) ?>,
                    Barangay Bombongan, Morong, Rizal.
                </p>

                <!-- Type-specific paragraphs -->
                <?php foreach ($certBody['paragraphs'] as $para): ?>
                    <p><?= $para ?></p>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- BOTTOM — signatures + footer pinned to bottom -->
        <div class="cert-bottom">
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
                <div>OR No.: _________________________ &nbsp;&nbsp; Amount Paid: _____________</div>
                <div style="text-align:right;">
                    CTC No.: _________________________ <br>
                    Date Issued: <?= $currentDate ?>
                </div>
            </div>
        </div>

    </div><!-- /.certificate -->

    <style id="pageStyle"></style>

    <script>
    /* ── Paper dimensions ── */
    const PAPER = {
        'A4':     { w: '8.27in',  h: '11.03in', wPx: 793  },
        'letter': { w: '8.5in',   h: '11in',    wPx: 816  },
        'legal':  { w: '8.5in',   h: '14in',    wPx: 816  },
        'A5':     { w: '5.83in',  h: '8.27in',  wPx: 559  },
    };

    function applyPageStyle() {
        const paper  = document.getElementById('paperSelect').value;
        const margin = parseFloat(document.getElementById('marginSelect').value);
        const p      = PAPER[paper];

        // 1. @page rule for actual print
        document.getElementById('pageStyle').textContent =
            `@page { size: ${paper}; margin: ${margin}in; }`;

        // 2. Screen preview: set certificate width and height to match paper minus margins
        const cert = document.getElementById('certificateContent');
        cert.style.maxWidth  = p.w;
        // min-height = paper height minus top+bottom margins
        // Using calc so it scales with the paper unit
        cert.style.minHeight = `calc(${p.h} - ${margin * 2}in)`;
    }

    function applyScale() {
        const scale = parseFloat(document.getElementById('scaleSelect').value);
        const cert  = document.getElementById('certificateContent');
        cert.style.transform       = `scale(${scale})`;
        cert.style.transformOrigin = 'top center';
    }

    // Init on load
    window.addEventListener('DOMContentLoaded', applyPageStyle);
    </script>

    <script src="./js/print.js"></script>
</body>
</html>