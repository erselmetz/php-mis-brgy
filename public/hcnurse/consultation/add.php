<?php
// hcnurse/consultation/add.php
// Uses $conn from db.php (already loaded via app.php)

$resident_id = (int)($_POST['resident_id'] ?? 0);
$complaint = trim($_POST['complaint'] ?? '');
$diagnosis = trim($_POST['diagnosis'] ?? '');
$treatment = trim($_POST['treatment'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$consultation_date = $_POST['consultation_date'] ?? '';

if ($resident_id <= 0 || $complaint === '' || $consultation_date === '') {
  $_SESSION['flash_error'] = "Please fill required fields.";
  header("Location: consultation.php");
  exit;
}

// Insert into consultations (matches your schema exactly)
$sql = "INSERT INTO consultations (resident_id, complaint, diagnosis, treatment, notes, consultation_date)
        VALUES (?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
  "isssss",
  $resident_id,
  $complaint,
  $diagnosis,
  $treatment,
  $notes,
  $consultation_date
);

$stmt->execute();

$_SESSION['flash_success'] = "Consultation added successfully!";
header("Location: consultation.php");
exit;
