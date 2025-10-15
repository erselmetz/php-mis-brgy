<?php
require_once '../../includes/app.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View - Resident - MIS Barangay</title>
    <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100">
    <?php include_once '../navbar.php'; ?>
    <div class="flex bg-gray-100">
        <?php include_once '../sidebar.php'; ?>
        <main class="p-6 w-screen">
            <h1>View Resident</h1>
        </main>
    </div>
</body>

</html>