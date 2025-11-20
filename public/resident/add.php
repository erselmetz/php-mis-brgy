<?php

if ($action === 'add_resident') {
    // --- Validate and sanitize inputs ---
    
    // Required fields validation
    if (empty($_POST['first_name']) || empty($_POST['last_name']) || empty($_POST['gender']) || empty($_POST['birthdate'])) {
        $error = "⚠️ Please fill in all required fields (First Name, Last Name, Gender, and Birthdate).";
    } else {
        // Collect and sanitize inputs
        $household_id = !empty($_POST['household_id']) ? intval($_POST['household_id']) : null;
        $first_name = trim($_POST['first_name']);
        $middle_name = trim($_POST['middle_name'] ?? '');
        $last_name = trim($_POST['last_name']);
        $suffix = trim($_POST['suffix'] ?? '');
        $gender = $_POST['gender'];
        $birthdate = trim($_POST['birthdate']);
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

        // Validate birthdate format (YYYY-MM-DD)
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthdate)) {
            $error = "⚠️ Invalid birthdate format. Please use YYYY-MM-DD format.";
        } else {
            // Validate birthdate is a valid date
            try {
                $birthDateObj = DateTime::createFromFormat('Y-m-d', $birthdate);
                if (!$birthDateObj || $birthDateObj->format('Y-m-d') !== $birthdate) {
                    throw new Exception('Invalid date');
                }
                
                // Check if birthdate is not in the future
                $today = new DateTime();
                if ($birthDateObj > $today) {
                    $error = "⚠️ Birthdate cannot be in the future.";
                }
            } catch (Exception $e) {
                $error = "⚠️ Invalid birthdate. Please enter a valid date.";
            }
        }

        // Validate enum fields
        if (!isset($error)) {
            $validGenders = ['Male', 'Female'];
            if (!in_array($gender, $validGenders)) {
                $error = "⚠️ Invalid gender. Must be Male or Female.";
            }

            $validCivilStatus = ['Single', 'Married', 'Widowed', 'Separated'];
            if (!in_array($civil_status, $validCivilStatus)) {
                $error = "⚠️ Invalid civil status.";
            }

            $validStatuses = ['Yes', 'No'];
            if (!in_array($voter_status, $validStatuses)) {
                $error = "⚠️ Invalid voter status. Must be Yes or No.";
            }

            if (!in_array($disability_status, $validStatuses)) {
                $error = "⚠️ Invalid disability status. Must be Yes or No.";
            }
        }

        // Validate contact number format if provided (Philippine format: 09XXXXXXXXX)
        if (!empty($contact_no) && !preg_match('/^09\d{9}$/', $contact_no)) {
            $error = "⚠️ Invalid contact number format. Please use 09XXXXXXXXX format.";
        }

        // If no validation errors, proceed with database operations
        if (!isset($error)) {
            $check = null;
            $sql = null;

            try {
                // --- Step 1: Check for duplicate resident ---
                $check = $conn->prepare("
                    SELECT id FROM residents 
                    WHERE first_name = ? AND last_name = ? AND birthdate = ?
                ");
                
                if ($check === false) {
                    throw new Exception("Database error: " . $conn->error);
                }

                $check->bind_param("sss", $first_name, $last_name, $birthdate);
                $check->execute();
                $res = $check->get_result();

                if ($res->num_rows > 0) {
                    $error = "⚠️ Resident already exists in the database!";
                    $check->close();
                } else {
                    $check->close();

                    // --- Step 2: Insert new record ---
                    $sql = $conn->prepare("
                        INSERT INTO residents 
                        (household_id, first_name, middle_name, last_name, suffix, gender, birthdate, birthplace, civil_status, religion, occupation, citizenship, contact_no, address, voter_status, disability_status, remarks)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");

                    if ($sql === false) {
                        throw new Exception("Database error: " . $conn->error);
                    }

                    // bind_param: 1 int (household_id) + 16 strings = 17 parameters total
                    // Manually construct type string to ensure accuracy: i + 16*s
                    $types = "i"; // household_id is int
                    $types .= str_repeat("s", 16); // remaining 16 are strings
                    
                    $sql->bind_param(
                        $types,
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
                        // Success — return to resident list or send success flag
                        $sql->close();
                        header("Location: /resident/residents?success=1");
                        exit();
                    } else {
                        $error = "❌ Error adding resident: " . $sql->error;
                        $sql->close();
                    }
                }
            } catch (Exception $e) {
                if ($check !== null) {
                    $check->close();
                }
                if ($sql !== null) {
                    $sql->close();
                }
                error_log("Resident Add Error: " . $e->getMessage());
                $error = "❌ An error occurred while adding the resident. Please try again.";
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