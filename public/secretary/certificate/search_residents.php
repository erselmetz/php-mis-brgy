<?php
/**
 * Resident Search API
 * 
 * Provides AJAX search functionality for residents.
 * Searches by first name, middle name, last name, and address.
 * Uses prepared statements to prevent SQL injection.
 * Limits results to 10 for performance.
 */

require_once __DIR__ . '/../../../includes/app.php';

// Get and sanitize search query
$q = trim($_GET['q'] ?? '');

// Return empty array if query is too short or empty
if (empty($q) || strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

/**
 * Search residents using LIKE pattern matching
 * Searches across first name, middle name, last name, and address
 * Uses prepared statements to prevent SQL injection
 * Limits to 10 results for performance
 */
$searchPattern = '%' . $q . '%';
$stmt = $conn->prepare("
    SELECT id, first_name, middle_name, last_name, address 
    FROM residents 
    WHERE 
        first_name LIKE ? OR 
        middle_name LIKE ? OR 
        last_name LIKE ? OR 
        address LIKE ? 
    LIMIT 10
");

if ($stmt === false) {
    error_log('Resident Search Error - Query preparation failed: ' . $conn->error);
    echo json_encode([]);
    exit;
}

$stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
$stmt->execute();
$res = $stmt->get_result();

// Return results as JSON
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
$stmt->close();
