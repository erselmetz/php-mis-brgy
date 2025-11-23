<?php
require_once '../../includes/app.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch current user info
$stmt = $conn->prepare("SELECT name, username, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    // Handle profile picture upload
    $profile_picture = $user['profile_picture']; // Keep existing if not changed
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($file['type'], $allowedTypes)) {
            $error = "Invalid file type. Only JPEG, PNG, and GIF are allowed.";
        } elseif ($file['size'] > $maxSize) {
            $error = "File size exceeds 2MB limit.";
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Delete old profile picture if exists
                if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_picture'])) {
                    unlink(__DIR__ . '/uploads/profiles/' . $user['profile_picture']);
                }
                $profile_picture = $filename;
            } else {
                $error = "Failed to upload profile picture.";
            }
        }
    }
    
    if (empty($error)) {
        if (empty($name) || empty($username)) {
            $error = "Name and username are required.";
        } else {
            if (!empty($password)) {
                // Update with new password (hashed)
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, password = ?, profile_picture = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $name, $username, $hashed, $profile_picture, $user_id);
            } else {
                // Update without changing password
                $stmt = $conn->prepare("UPDATE users SET name = ?, username = ?, profile_picture = ? WHERE id = ?");
                $stmt->bind_param("sssi", $name, $username, $profile_picture, $user_id);
            }
            
            if ($stmt->execute()) {
                $_SESSION['name'] = $name;
                $_SESSION['username'] = $username;
                if (!empty($profile_picture)) {
                    $_SESSION['profile_picture'] = $profile_picture;
                }
                $success = "✅ Profile updated successfully!";
                
                // Refresh user info
                $stmt = $conn->prepare("SELECT name, username, profile_picture FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error = "❌ Failed to update profile. Try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Profile Account - MIS Barangay</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php loadAllAssets(); ?>
</head>
<body class="bg-gray-100" style="display: none;">
  <?php include_once '../layout/navbar.php'; ?>
  <div class="flex bg-gray-100">
    <?php include_once '../layout/sidebar.php'; ?>
    <main class="p-6 w-screen">
      <h2 class="text-2xl font-semibold mb-4">Profile Settings</h2>
      <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-xl p-8 border border-gray-200">
        
        <?php if (!empty($success)): ?>
          <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded mb-4">
            <?= htmlspecialchars($success) ?>
          </div>
        <?php elseif (!empty($error)): ?>
          <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded mb-4">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
          <!-- Profile Picture Section -->
          <div class="flex items-center space-x-6">
            <div class="flex-shrink-0">
              <?php if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_picture'])): ?>
                <img src="/uploads/profiles/<?= htmlspecialchars($user['profile_picture']) ?>" 
                     alt="Profile Picture" 
                     class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">
              <?php else: ?>
                <div class="w-24 h-24 rounded-full bg-gray-200 flex items-center justify-center border-4 border-gray-300">
                  <span class="text-3xl text-gray-400">👤</span>
                </div>
              <?php endif; ?>
            </div>
            <div class="flex-grow">
              <label class="block text-gray-700 font-medium mb-2">Profile Picture</label>
              <input type="file" name="profile_picture" accept="image/jpeg,image/jpg,image/png,image/gif"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-300 focus:border-blue-400">
              <p class="text-xs text-gray-500 mt-1">Max size: 2MB. Formats: JPEG, PNG, GIF</p>
            </div>
          </div>

          <hr class="border-gray-200">

          <!-- Account Information -->
          <h3 class="text-lg font-semibold mb-4 text-gray-800">Account Information</h3>
          
          <div>
            <label class="block text-gray-700 mb-1 font-medium">Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
              required>
          </div>

          <div>
            <label class="block text-gray-700 mb-1 font-medium">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-400"
              required>
          </div>

          <div>
            <label class="block text-gray-700 mb-1 font-medium">New Password (optional)</label>
            <input type="password" name="password" placeholder="Leave blank to keep current password"
              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-200 focus:border-blue-400">
            <p class="text-xs text-gray-500 mt-1">Leave blank if you don't want to change your password</p>
          </div>

          <div class="flex justify-end pt-4">
            <button type="submit"
              class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-medium">
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
      
      // Preview profile picture before upload
      $('input[name="profile_picture"]').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = function(e) {
            $('.flex-shrink-0 img, .flex-shrink-0 div').replaceWith(
              '<img src="' + e.target.result + '" alt="Profile Preview" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">'
            );
          };
          reader.readAsDataURL(file);
        }
      });
    });
  </script>
</body>
</html>
