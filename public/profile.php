<?php
require_once '../includes/app.php';
requireLogin();

$user_id = $_SESSION['user_id']; // Assuming your login sets this
$success = '';
$error = '';

// ✅ Fetch current user info
$stmt = $conn->prepare("SELECT name, username FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($name) || empty($username)) {
        $error = "Name and username are required.";
    } else {
        if (!empty($password)) {
            // Update with new password (hashed)
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $name, $username, $hashed, $user_id);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $username, $user_id);
        }

        if ($stmt->execute()) {
            $_SESSION['name'] = $name;
            $_SESSION['username'] = $username;
            $success = "✅ Profile updated successfully!";
        } else {
            $error = "❌ Failed to update profile. Try again.";
        }
    }

    // Refresh user info
    $stmt = $conn->prepare("SELECT name, username FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Profile Account - MIS Barangay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php loadAllAssets();?>
</head>

<body class="bg-gray-100" style="display: none;">

  <?php include_once './navbar.php'; ?>

  <div class="flex bg-gray-100">
    <?php include_once './sidebar.php'; ?>

    <main class="p-6 w-screen">
      <h2 class="text-2xl mx-auto font-semibold mb-4">Profile Account</h2>
      <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-xl p-8 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Edit Account Information</h3>

        <!-- ✅ Dynamic messages -->
        <?php if (!empty($success)): ?>
          <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4">
            <?= htmlspecialchars($success) ?>
          </div>
        <?php elseif (!empty($error)): ?>
          <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded mb-4">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5" id="profileForm">
          <div>
            <label class="block text-gray-600 mb-1 font-medium">Full Name</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($user['name']) ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-400"
              required>
          </div>

          <div>
            <label class="block text-gray-600 mb-1 font-medium">Username</label>
            <input type="text" name="username" id="username" value="<?= htmlspecialchars($user['username']) ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring focus:ring-blue-200 focus:border-blue-400"
              required>
          </div>

          <div>
            <label class="block text-gray-600 mb-1 font-medium">New Password (optional)</label>
            <input type="password" name="password" id="password"
              placeholder="Leave blank to keep current password"
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

  <script>
    $(function () {
      $("body").show();
      // ✅ Dialogs for feedback
      <?php if (!empty($success)): ?>
        $("<div><?= addslashes($success) ?></div>").dialog({
          title: "Success",
          modal: true,
          buttons: {
            Ok: function () {
              $(this).dialog("close");
            }
          }
        });
      <?php elseif (!empty($error)): ?>
        $("<div><?= addslashes($error) ?></div>").dialog({
          title: "Error",
          modal: true,
          buttons: {
            Ok: function () {
              $(this).dialog("close");
            }
          }
        });
      <?php endif; ?>
    });
  </script>
</body>

</html>
