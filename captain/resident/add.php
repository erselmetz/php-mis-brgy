<?php
/**
 * Add Resident Handler
 * 
 * Processes form submission to add new resident records.
 * Validates input, checks for duplicates, and inserts new record.
 * Uses prepared statements for all database operations.
 */

if ($action === 'add_resident') {
    /**
     * Collect and sanitize form inputs
     * All inputs are sanitized to prevent XSS and SQL injection
     */
    $household_id = !empty($_POST['household_id']) ? sanitizeInt($_POST['household_id'], 1) : null;
    $first_name = sanitizeString($_POST['first_name'] ?? '', false);
    $middle_name = sanitizeString($_POST['middle_name'] ?? '');
    $last_name = sanitizeString($_POST['last_name'] ?? '', false);
    $suffix = sanitizeString($_POST['suffix'] ?? '');
    $gender = sanitizeString($_POST['gender'] ?? '');
    $birthdate = $_POST['birthdate'] ?? '';
    $birthplace = sanitizeString($_POST['birthplace'] ?? '');
    $civil_status = sanitizeString($_POST['civil_status'] ?? 'Single');
    $religion = sanitizeString($_POST['religion'] ?? '');
    $occupation = sanitizeString($_POST['occupation'] ?? '');
    $citizenship = sanitizeString($_POST['citizenship'] ?? 'Filipino');
    $contact_no = sanitizeString($_POST['contact_no'] ?? '');
    $address = sanitizeString($_POST['address'] ?? '');
    $voter_status = sanitizeString($_POST['voter_status'] ?? 'No');
    $disability_status = sanitizeString($_POST['disability_status'] ?? 'No');
    $remarks = sanitizeString($_POST['remarks'] ?? '');

    // Validate required fields
    if (empty($first_name) || empty($last_name) || empty($birthdate)) {
        $error = "⚠️ First name, last name, and birthdate are required!";
    } elseif (!validateDateFormat($birthdate)) {
        $error = "⚠️ Invalid birthdate format. Use YYYY-MM-DD format.";
    } elseif (!empty($contact_no) && !validatePhilippinePhone($contact_no)) {
        $error = "⚠️ Invalid contact number format. Use 09XXXXXXXXX format.";
    } else {
        /**
         * Check for duplicate resident
         * Prevents adding the same person twice (same name and birthdate)
         */
        $check = $conn->prepare("
            SELECT id FROM residents 
            WHERE first_name = ? AND last_name = ? AND birthdate = ?
        ");
        
        if ($check === false) {
            error_log('Resident Add Error - Duplicate check query preparation failed: ' . $conn->error);
            $error = "❌ Database error. Please try again.";
        } else {
            $check->bind_param("sss", $first_name, $last_name, $birthdate);
            $check->execute();
            $res = $check->get_result();

            if ($res->num_rows > 0) {
                $error = "⚠️ Resident already exists in the database!";
                $check->close();
            } else {
                $check->close();
                /**
                 * Insert new resident record using prepared statement
                 * This prevents SQL injection attacks
                 */
                $sql = $conn->prepare("
                    INSERT INTO residents 
                    (household_id, first_name, middle_name, last_name, suffix, gender, birthdate, birthplace, civil_status, religion, occupation, citizenship, contact_no, address, voter_status, disability_status, remarks)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                if ($sql === false) {
                    error_log('Resident Add Error - Insert query preparation failed: ' . $conn->error);
                    $error = "❌ Database error. Please try again.";
                } else {
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
                        // Success — redirect to resident list
                        header("Location: /secretary/resident/?success=1");
                        exit();
                    } else {
                        error_log('Resident Add Error: ' . $sql->error);
                        $error = "❌ Error adding resident. Please try again.";
                    }

                    $sql->close();
                }
            }
        }
    }
}
?>

<!-- ✅ Optional: show error message if form was redisplayed -->
<?php if (isset($error)): ?>
    <div class="p-3 mb-2 bg-red-100 border border-red-300 text-red-700 rounded-lg">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>