<?php
require_once '../includes/app.php';
requireLogin();
// Fetch sample data (replace with real queries)
// --- Default values (in case table is empty or query fails) ---
$totalPopulation = 0;
$maleCount = 0;
$femaleCount = 0;
$seniorCount = 0;
$pwdCount = 0;
$voter_registered_count = 0;
$voter_unregistered_count = 0;

// --- Check if residents table has data ---
$result = $conn->query("SELECT COUNT(*) AS total FROM residents");
if ($result && $row = $result->fetch_assoc()) {
  $totalPopulation = (int)$row['total'];
}

// --- Run queries only if residents exist ---
if ($totalPopulation > 0) {
  // Male count
  $maleCount = $conn->query("SELECT COUNT(*) AS total FROM residents WHERE gender='Male'")
    ->fetch_assoc()['total'];

  // Female count
  $femaleCount = $conn->query("SELECT COUNT(*) AS total FROM residents WHERE gender='Female'")
    ->fetch_assoc()['total'];

  // Senior citizens (60 years old and above)
  $seniorCount = $conn->query("
        SELECT COUNT(*) AS total 
        FROM residents 
        WHERE TIMESTAMPDIFF(YEAR, birthdate, CURDATE()) >= 60
    ")->fetch_assoc()['total'];

  // Persons with disability
  $pwdCount = $conn->query("
        SELECT COUNT(*) AS total 
        FROM residents 
        WHERE disability_status='Yes'
    ")->fetch_assoc()['total'];

  // Voter registered
  $voter_registered_count = $conn->query("
        SELECT COUNT(*) AS total 
        FROM residents 
        WHERE voter_status='Yes'
    ")->fetch_assoc()['total'];

  // Voter unregistered
  $voter_unregistered_count = $conn->query("
        SELECT COUNT(*) AS total 
        FROM residents 
        WHERE voter_status='No'
    ")->fetch_assoc()['total'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Dashboard - MIS Barangay</title>
  <?php loadAllStyles(); ?>
</head>

<body class="bg-gray-100">

  <?php include './navbar.php'; ?>
  <div class="flex bg-gray-100">
    <?php include './sidebar.php'; ?>
    <main class="p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Dashboard</h2>
      <!-- Population Report -->
      <div class="bg-white p-6 shadow-sm rounded-xl mb-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Population Report</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <!-- Total -->
          <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200">
            <div class="p-3 bg-blue-100 rounded-full text-blue-600">
              <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-500">Total</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $totalPopulation ?></h2>
            </div>
          </div>
          <!-- Male -->
          <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 ">
            <div class="p-3 bg-blue-100 rounded-full text-blue-600">
              <i data-lucide="user-round" class="w-6 h-6"></i>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-500">Male</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $maleCount ?></h2>
            </div>
          </div>
          <!-- Female -->
          <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200">
            <div class="p-3 bg-blue-100 rounded-full text-blue-600">
              <i data-lucide="circle-user-round" class="w-6 h-6"></i>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-500">Female</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $femaleCount ?></h2>
            </div>
          </div>
        </div>
      </div>

      <!-- Senior Citizen / PWD -->
      <div class="bg-white p-6 shadow-sm rounded-xl mb-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Senior Citizen / PWD</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
          <!-- Seniors -->
          <div class="flex items-center p-4 rounded-lg bg-gray-50 border border-gray-200 ">
            <div class="p-3 bg-purple-100 rounded-full text-purple-600 ">
              <i data-lucide="user-round" class="w-6 h-6"></i>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-500">Seniors</p>
              <h2 class="text-2xl font-bold text-gray-800"><?= $seniorCount ?></h2>
            </div>
          </div>
          <!-- PWDs -->
          <div class="flex items-center p-4 rounded-lg bg-gray-50  border border-gray-200 ">
            <div class="p-3 bg-cyan-100 rounded-full text-cyan-600">
              <i data-lucide="wheelchair" class="w-6 h-6"></i>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-500 ">PWDs</p>
              <h2 class="text-2xl font-bold text-gray-800 "><?= $pwdCount ?></h2>
            </div>
          </div>
        </div>
      </div>

      <!-- Voter's Report -->
      <div class="bg-white p-6 shadow-sm rounded-xl mb-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-800  mb-4">Voter's Report</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Registered Voters -->
          <div class="flex items-center p-4 rounded-lg bg-gray-50  border border-gray-200 ">
            <div class="p-3 bg-indigo-100 rounded-full text-indigo-600">
              <i data-lucide="id-card" class="w-6 h-6"></i>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-500 ">Registered Voters</p>
              <h2 class="text-2xl font-bold text-gray-800 "><?= $voter_registered_count ?></h2>
            </div>
          </div>
          <!-- Unregistered Voters -->
          <div class="flex items-center p-4 rounded-lg bg-gray-50  border border-gray-200 ">
            <div class="p-3 bg-red-100 rounded-full text-red-600">
              <i data-lucide="x-circle" class="w-6 h-6"></i>
            </div>
            <div class="ml-4">
              <p class="text-sm text-gray-500 ">Unregistered Voters</p>
              <h2 class="text-2xl font-bold text-gray-800 "><?= $voter_unregistered_count ?></h2>
            </div>
          </div>
        </div>
      </div>
    </main>

</body>

</html>