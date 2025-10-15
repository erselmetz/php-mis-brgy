<?php
require_once '../includes/app.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Profile Account - MIS Barangay</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>

<body class="bg-gray-100">

  <?php include_once './navbar.php'; ?>

  <div class="flex bg-gray-100">
    <?php include_once './sidebar.php'; ?>
    <main class="p-6 w-screen">
      <h2 class="text-2xl mx-auto font-semibold mb-4">Profile Account</h2>
      <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-xl p-8 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Edit Account Information</h3>

        <?php if (!empty($success)): ?>
          <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4">
            <?= $success ?>
          </div>
        <?php elseif (!empty($error)): ?>
          <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded mb-4">
            <?= $error ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
          <div>
            <label class="block text-gray-600 mb-1 font-medium">Full Name</label>
            <input type="text" name="fullname" value="<?= htmlspecialchars($_SESSION['name']) ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-400">
          </div>

          <div>
            <label class="block text-gray-600 mb-1 font-medium">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($_SESSION['username']) ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-400">
          </div>

          <div>
            <label class="block text-gray-600 mb-1 font-medium">New Password (optional)</label>
            <input type="password" name="password" placeholder="Leave blank to keep current password"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-400">
          </div>

          <div class="flex justify-end pt-4">
            <button type="submit"
              class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>

</body>

</html>