<?php
require_once '../../includes/app.php';
require_once '../api/v1/BaseModel.php';
require_once '../api/v1/residents/ResidentModel.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') {
  echo json_encode([]);
  exit;
}

// Use ResidentModel directly
$model = new ResidentModel();
$searchTerm = '%' . $q . '%';
$conditions = [
    'first_name LIKE ? OR middle_name LIKE ? OR last_name LIKE ? OR address LIKE ?'
];
$params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
$residents = $model->findWhere($conditions, $params, 'last_name, first_name', 10);

// Format response to match expected format
$formatted = array_map(function($resident) {
    return [
        'id' => $resident['id'],
        'first_name' => $resident['first_name'] ?? '',
        'middle_name' => $resident['middle_name'] ?? '',
        'last_name' => $resident['last_name'] ?? '',
        'address' => $resident['address'] ?? ''
    ];
}, $residents);

echo json_encode($formatted);
