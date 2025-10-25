<?php

if ($action === 'add_resident') {
    // --- Collect and sanitize inputs ---
    $household_id = !empty($_POST['household_id']) ? intval($_POST['household_id']) : null;
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name'] ?? '');
    $last_name = trim($_POST['last_name']);
    $suffix = trim($_POST['suffix'] ?? '');
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];
    $birthplace = trim($_POST['birthplace'] ?? '');
    $civil_status = $_POST['civil_status'] ?? 'Single';
    $religion = trim($_POST['religion'] ?? '');
    $occupation = trim($_POST['occupation'] ?? '');
    $citizenship = trim($_POST['citizenship'] ?? 'Filipino');
    $contact_no = trim($_POST['contact_no'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $voter_status = $_POST['voter_status'] ?? 'No';
    $disability_status = $_POST['disability_status'] ?? 'No';
    $remarks = trim($_POST['remarks'] ?? '');

    // --- Auto-compute age ---
    $birthDateObj = new DateTime($birthdate);
    $today = new DateTime();
    $age = $birthDateObj->diff($today)->y;

    // --- Step 1: Check for duplicate resident ---
    $check = $conn->prepare("
        SELECT id FROM residents 
        WHERE first_name = ? AND last_name = ? AND birthdate = ?
    ");
    $check->bind_param("sss", $first_name, $last_name, $birthdate);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $error = "⚠️ Resident already exists in the database!";
    } else {
        // --- Step 2: Insert new record ---
        $sql = $conn->prepare("
            INSERT INTO residents 
            (household_id, first_name, middle_name, last_name, suffix, gender, birthdate, birthplace, civil_status, religion, occupation, citizenship, contact_no, address, voter_status, disability_status, remarks)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $sql->bind_param(
            "issssssssssssssss",
            $household_id,
            $first_name,
            $middle_name,
            $last_name,
            $suffix,
            $gender,
            $birthdate,
            $birthplace,
            $civil_status,
            $religion,
            $occupation,
            $citizenship,
            $contact_no,
            $address,
            $voter_status,
            $disability_status,
            $remarks
        );

        if ($sql->execute()) {
            // success — return to resident list or send success flag
            header("Location: /residents?success=1");
            exit();
        } else {
            $error = "❌ Error adding resident: " . $conn->error;
        }

        $sql->close();
    }

    $check->close();
}
?>

<!-- ✅ Optional: show error message if form was redisplayed -->
<?php if (isset($error)): ?>
    <div class="p-3 mb-2 bg-red-100 border border-red-300 text-red-700 rounded-lg">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>