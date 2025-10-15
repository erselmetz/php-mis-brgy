<?php
require_once '../includes/app.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Certificate - MIS Barangay</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">

  <?php include './navbar.php'; ?>

  <div class="flex bg-gray-100">
    <?php include './sidebar.php'; ?>
    <main class="p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Certificate</h2>

    </main>
  </div>

</body>

</html>