<?php
require_once '../../includes/app.php';
requireKagawad(); // Only Kagawad can access

$id = intval($_GET['id'] ?? 0);

if ($id === 0) {
    header("Location: /certificate/certificates");
    exit;
}

// Fetch certificate request with resident details
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
    header("Location: /certificate/certificates");
    exit;
}

$cert = $result->fetch_assoc();
$stmt->close();

// Calculate age
$birthDate = new DateTime($cert['birthdate']);
$today = new DateTime();
$age = $birthDate->diff($today)->y;

// Get current date
$currentDate = date('F d, Y');
$currentYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Certificate - <?= htmlspecialchars($cert['certificate_type']) ?></title>
    <style>
        /* A4 Size: 210mm x 297mm (8.27in x 11.69in) */
        /* Usable area with margins: ~7.5in x 10in */
        
        @page {
            size: A4;
            margin: 0.75in;
        }
        
        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            body { 
                margin: 0;
                padding: 0;
                background: white;
                width: 100%;
            }
            .no-print { 
                display: none !important; 
            }
            .certificate {
                page-break-inside: avoid;
                page-break-after: avoid;
                margin: 0;
                padding: 25px;
                max-width: 100%;
                height: auto;
                max-height: 10.5in;
                box-sizing: border-box;
            }
            .header h1 {
                font-size: 16px;
            }
            .certificate-title {
                font-size: 16px;
            }
            .content {
                font-size: 12px;
                line-height: 1.6;
            }
            .signature-section {
                margin-top: 30px;
            }
            .signature-line {
                margin-top: 40px;
            }
        }
        
        body {
            font-family: 'Times New Roman', serif;
            width: 100%;
            max-width: 8.27in;
            margin: 0 auto;
            padding: 20px;
            background: white;
            box-sizing: border-box;
        }
        
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border: 2px solid var(--theme-secondary);
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .print-controls h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: var(--theme-secondary);
        }
        
        .control-group {
            margin-bottom: 10px;
        }
        
        .control-group label {
            display: block;
            font-size: 12px;
            margin-bottom: 5px;
            color: #333;
        }
        
        .control-group input,
        .control-group select {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .print-button {
            width: 100%;
            background: var(--theme-secondary);
            color: white;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .print-button:hover {
            background: var(--theme-secondary);
        }
        
        .certificate {
            border: 3px solid #000;
            padding: 30px;
            max-width: 100%;
            box-sizing: border-box;
            background: white;
            position: relative;
            /* Ensure it fits in A4 */
            min-height: auto;
            max-height: 10in;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin: 3px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1.3;
        }
        
        .header p {
            font-size: 12px;
            margin: 3px 0;
        }
        
        .certificate-title {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 20px 0;
            text-decoration: underline;
        }
        
        .content {
            text-align: justify;
            line-height: 1.8;
            font-size: 13px;
            margin: 20px 0;
        }
        
        .content p {
            margin: 12px 0;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .signature-box {
            text-align: center;
            width: 48%;
            min-width: 200px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 12px;
        }
        
        .footer-info {
            margin-top: 25px;
            text-align: center;
            font-size: 11px;
        }
        
        /* Scale adjustments */
        .scale-90 { transform: scale(0.90); transform-origin: top center; }
        .scale-85 { transform: scale(0.85); transform-origin: top center; }
        .scale-80 { transform: scale(0.80); transform-origin: top center; }
        .scale-75 { transform: scale(0.75); transform-origin: top center; }
        
        @media print {
            .scale-90, .scale-85, .scale-80, .scale-75 {
                transform: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <h3>🖨️ Print Options</h3>
        <div class="control-group">
            <label for="scaleSelect">Scale:</label>
            <select id="scaleSelect" onchange="applyScale()">
                <option value="1.0">100% (Default)</option>
                <option value="0.95">95%</option>
                <option value="0.90">90%</option>
                <option value="0.85">85%</option>
                <option value="0.80">80%</option>
            </select>
        </div>
        <div class="control-group">
            <label for="marginSelect">Margins:</label>
            <select id="marginSelect" onchange="applyMargins()">
                <option value="0.75">Normal (0.75in)</option>
                <option value="0.5">Small (0.5in)</option>
                <option value="0.25">Minimal (0.25in)</option>
                <option value="1.0">Large (1.0in)</option>
            </select>
        </div>
        <button class="print-button" onclick="window.print()">🖨️ Print Certificate</button>
    </div>
    
    <div class="certificate" id="certificateContent">
        <div class="header">
            <h1>Republic of the Philippines</h1>
            <h1>Province of [Province Name]</h1>
            <h1>Municipality of [Municipality Name]</h1>
            <h1>Barangay [Barangay Name]</h1>
            <p>OFFICE OF THE BARANGAY CAPTAIN</p>
        </div>

        <div class="certificate-title">
            <?= htmlspecialchars($cert['certificate_type']) ?>
        </div>

        <div class="content">
            <p style="text-indent: 50px;">
                This is to certify that <strong><?= htmlspecialchars($cert['first_name'] . ' ' . $cert['middle_name'] . ' ' . $cert['last_name']) ?></strong>, 
                <?= $age ?> years old, <?= htmlspecialchars($cert['gender']) ?>, 
                <?= htmlspecialchars($cert['civil_status'] ?? 'Single') ?>, 
                Filipino, and a resident of <?= htmlspecialchars($cert['address']) ?>, 
                Barangay [Barangay Name], [Municipality Name], [Province Name].
            </p>

            <?php if ($cert['certificate_type'] === 'Barangay Clearance'): ?>
                <p style="text-indent: 50px;">
                    This certification is issued upon the request of the above-named person for <strong><?= htmlspecialchars($cert['purpose']) ?></strong> 
                    and whatever legal purpose it may serve.
                </p>
                <p style="text-indent: 50px;">
                    This certification is issued this <strong><?= $currentDate ?></strong> at Barangay [Barangay Name], 
                    [Municipality Name], [Province Name].
                </p>
            <?php elseif ($cert['certificate_type'] === 'Indigency Certificate'): ?>
                <p style="text-indent: 50px;">
                    This is to certify further that the above-named person belongs to an indigent family in this barangay 
                    and is in need of financial assistance for <strong><?= htmlspecialchars($cert['purpose']) ?></strong>.
                </p>
                <p style="text-indent: 50px;">
                    This certification is issued this <strong><?= $currentDate ?></strong> at Barangay [Barangay Name], 
                    [Municipality Name], [Province Name].
                </p>
            <?php elseif ($cert['certificate_type'] === 'Residency Certificate'): ?>
                <p style="text-indent: 50px;">
                    This is to certify that the above-named person is a bonafide resident of this barangay and has been 
                    residing at the above-mentioned address for the purpose of <strong><?= htmlspecialchars($cert['purpose']) ?></strong>.
                </p>
                <p style="text-indent: 50px;">
                    This certification is issued this <strong><?= $currentDate ?></strong> at Barangay [Barangay Name], 
                    [Municipality Name], [Province Name].
                </p>
            <?php endif; ?>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">
                    <strong>Prepared by:</strong><br>
                    <?= htmlspecialchars($cert['issued_by_name'] ?? 'Staff') ?><br>
                    <small>Barangay Staff</small>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <strong>Certified by:</strong><br>
                    [Barangay Captain Name]<br>
                    <small>Barangay Captain</small>
                </div>
            </div>
        </div>

        <div class="footer-info">
            <p>OR No.: _______________ Date Issued: <?= $currentDate ?></p>
        </div>
    </div>

    <script>
        let currentScale = 1.0;
        
        function applyScale() {
            const scale = parseFloat(document.getElementById('scaleSelect').value);
            currentScale = scale;
            const cert = document.getElementById('certificateContent');
            
            // For screen preview, use transform
            if (scale < 1.0) {
                cert.style.transform = `scale(${scale})`;
                cert.style.transformOrigin = 'top center';
            } else {
                cert.style.transform = 'none';
            }
            
            // Update print styles with new scale
            const margin = document.getElementById('marginSelect').value;
            updatePrintStyles(margin, scale);
        }
        
        function applyMargins() {
            const margin = document.getElementById('marginSelect').value;
            updatePrintStyles(margin, currentScale);
        }
        
        function updatePrintStyles(margin, scale) {
            const style = document.createElement('style');
            style.id = 'dynamic-print-styles';
            style.textContent = `
                @page { 
                    size: A4;
                    margin: ${margin}in; 
                }
                @media print {
                    .certificate {
                        padding: ${Math.round(25 * scale)}px !important;
                    }
                    .header h1 {
                        font-size: ${Math.round(16 * scale)}px !important;
                    }
                    .header p {
                        font-size: ${Math.round(12 * scale)}px !important;
                    }
                    .certificate-title {
                        font-size: ${Math.round(16 * scale)}px !important;
                    }
                    .content {
                        font-size: ${Math.round(12 * scale)}px !important;
                        line-height: ${1.6 * scale} !important;
                    }
                    .content p {
                        margin: ${Math.round(12 * scale)}px 0 !important;
                    }
                    .signature-section {
                        margin-top: ${Math.round(30 * scale)}px !important;
                    }
                    .signature-line {
                        margin-top: ${Math.round(40 * scale)}px !important;
                        font-size: ${Math.round(12 * scale)}px !important;
                    }
                    .footer-info {
                        margin-top: ${Math.round(25 * scale)}px !important;
                        font-size: ${Math.round(11 * scale)}px !important;
                    }
                }
            `;
            
            // Remove existing dynamic print styles
            const existing = document.getElementById('dynamic-print-styles');
            if (existing) {
                existing.remove();
            }
            
            document.head.appendChild(style);
        }
        
        // Apply default settings
        window.onload = function() {
            applyScale();
            applyMargins();
        };
    </script>
</body>
</html>

