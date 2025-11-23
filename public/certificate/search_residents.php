<?php
require_once '../../includes/app.php';

$q = trim($_GET['q'] ?? '');
if ($q === '') {
  echo json_encode([]);
  exit;
}

$stmt = $conn->prepare("SELECT id, first_name, middle_name, last_name, address FROM residents WHERE 
  first_name LIKE CONCAT('%', ?, '%') OR 
  middle_name LIKE CONCAT('%', ?, '%') OR 
  last_name LIKE CONCAT('%', ?, '%') OR 
  address LIKE CONCAT('%', ?, '%') 
  LIMIT 10");
$stmt->bind_param("ssss", $q, $q, $q, $q);
$stmt->execute();
$res = $stmt->get_result();

echo json_encode($res->fetch_all(MYSQLI_ASSOC));
