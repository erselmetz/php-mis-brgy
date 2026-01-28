<?php
require_once __DIR__ . '/../../../includes/app.php';
requireKagawad();

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
  /**
   * CSRF Protection
   * Validate CSRF token to prevent Cross-Site Request Forgery attacks
   */
  if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
    $error = "Invalid security token. Please refresh the page and try again.";
  } else {
    $name = sanitizeString($_POST['name'] ?? '', false);
    $username = sanitizeString($_POST['username'] ?? '', false);
    $password = sanitizeString($_POST['password'] ?? '');

    // Handle profile picture upload
    $profile_picture = $user['profile_picture']; // Keep existing if not changed

    /**
     * Handle profile picture upload with security validation
     * Uses comprehensive file validation to prevent malicious uploads
     */
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
      $uploadDir = __DIR__ . '/../../uploads/profiles/';

      // Create upload directory if it doesn't exist
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }

      // Validate uploaded file using secure validation function
      $validation = validateUploadedFile(
        $_FILES['profile_picture'],
        ['image/jpeg', 'image/png', 'image/gif'], // Allowed MIME types
        2 * 1024 * 1024, // Max size: 2MB
        ['jpg', 'jpeg', 'png', 'gif'] // Allowed extensions
      );

      if (!$validation['valid']) {
        $error = $validation['error'];
      } else {
        // Use safe filename from validation
        $filename = 'profile_' . $user_id . '_' . $validation['safe_filename'];
        $filepath = $uploadDir . $filename;

        // Move uploaded file to destination
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filepath)) {
          // Delete old profile picture if exists (prevent path traversal)
          if (!empty($user['profile_picture'])) {
            $oldFilePath = $uploadDir . basename($user['profile_picture']); // basename prevents path traversal
            if (file_exists($oldFilePath) && is_file($oldFilePath)) {
              unlink($oldFilePath);
            }
          }
          $profile_picture = $filename;
        } else {
          error_log('Profile picture upload failed for user ID: ' . $user_id);
          $error = "Failed to upload profile picture. Please try again.";
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

<body class="bg-gray-100 h-screen overflow-hidden" style="display: none;">
  <?php include_once '../layout/navbar.php'; ?>
  <div class="flex h-full bg-gray-100">
    <?php include_once '../layout/sidebar.php'; ?>
    <main class="pb-24 overflow-y-auto flex-1 p-6 w-screen">
      <!-- =========================
      BACKUP & REPORTS SECTION
      ========================= -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">

        <!-- FILE REPORTS -->
        <div class="lg:col-span-2 bg-white border rounded-xl p-6 shadow-sm">
          <h3 class="text-sm font-semibold mb-4">FILE REPORTS</h3>

          <table class="w-full text-sm border-collapse">
            <thead>
              <tr class="border-b text-gray-500">
                <th class="text-left py-2">FILE REPORTS</th>
                <th class="text-left py-2">SIZE</th>
                <th class="text-left py-2">LAST UPDATE</th>
                <th class="text-right py-2">ACTION</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <tr>
                <td class="py-3">Residence List</td>
                <td>56 MB</td>
                <td>2 mins ago</td>
                <td class="text-right">
                  <button class="bg-theme-primary hover-theme-darker text-white text-xs px-3 py-1 rounded">
                    Print Report
                  </button>
                </td>
              </tr>
              <tr>
                <td class="py-3">Official and Staff</td>
                <td>4 MB</td>
                <td>8/24/25</td>
                <td class="text-right">
                  <button class="bg-theme-primary hover-theme-darker text-white text-xs px-3 py-1 rounded">
                    Print Report
                  </button>
                </td>
              </tr>
              <tr>
                <td class="py-3">Blotter</td>
                <td>104 MB</td>
                <td>8/24/25</td>
                <td class="text-right">
                  <button class="bg-theme-primary hover-theme-darker text-white text-xs px-3 py-1 rounded">
                    Print Report
                  </button>
                </td>
              </tr>
              <tr>
                <td class="py-3">Inventory</td>
                <td>2.4 GB</td>
                <td>1 hour ago</td>
                <td class="text-right">
                  <button class="bg-theme-primary hover-theme-darker text-white text-xs px-3 py-1 rounded">
                    Print Report
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="flex justify-center mt-6">
            <button class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-full text-sm">
              REFRESH
            </button>
          </div>
        </div>

        <!-- ARCHIVES -->
        <div class="bg-white border rounded-xl p-6 shadow-sm flex flex-col items-center justify-center">
          <h3 class="text-lg font-semibold mb-6">Archives</h3>

          <!-- ICON -->
          <div class="w-24 h-24 bg-gray-800 rounded-lg flex items-center justify-center mb-6">
            <div class="w-10 h-14 bg-gray-900 border border-theme-primary relative">
              <div class="absolute top-2 left-2 right-2 space-y-1">
                <div class="h-1 bg-theme-primary"></div>
                <div class="h-1 bg-theme-primary"></div>
                <div class="h-1 bg-theme-primary"></div>
              </div>
            </div>
          </div>

          <button class="bg-theme-primary hover-theme-darker text-white px-6 py-2 rounded-full text-sm">
            BACK UP DATA
          </button>
        </div>

      </div>

      <!-- BACKUP HISTORY -->
      <div class="bg-white border rounded-xl p-6 shadow-sm mb-10">
        <h3 class="text-sm font-semibold mb-4">BACKUP HISTORY</h3>

        <table class="w-full text-sm border-collapse">
          <thead>
            <tr class="border-b text-gray-500">
              <th class="text-left py-2">DATE</th>
              <th class="text-left py-2">SIZE</th>
              <th class="text-left py-2">DESCRIPTION</th>
            </tr>
          </thead>
          <tbody class="divide-y">
            <tr>
              <td class="py-3 text-theme-primary">07-18-2025</td>
              <td>56 MB</td>
              <td>Maintenance</td>
            </tr>
            <tr>
              <td class="py-3 text-theme-primary">06-05-2025</td>
              <td>4 MB</td>
              <td>Maintenance</td>
            </tr>
            <tr>
              <td class="py-3 text-theme-primary">10-01-2024</td>
              <td>104 MB</td>
              <td>Maintenance</td>
            </tr>
            <tr>
              <td class="py-3 text-theme-primary">01-20-2024</td>
              <td>2.4 GB</td>
              <td>Maintenance</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- profile settings -->
      <div class="max-w-3xl mx-auto bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-6">
        <h2 class="text-2xl font-semibold mb-4">Profile Settings</h2>
        <?php if (!empty($success)): ?>
          <div class="bg-green-100 border border-green-300 text-green-700 p-3 rounded">
            <?= htmlspecialchars($success) ?>
          </div>
        <?php elseif (!empty($error)): ?>
          <div class="bg-red-100 border border-red-300 text-red-700 p-3 rounded">
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-6">
          <?= csrfTokenField() ?>

          <!-- Profile Picture Section -->
          <div class="flex items-center space-x-6">
            <div class="flex-shrink-0">
              <?php if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/../../uploads/profiles/' . $user['profile_picture'])): ?>
                <img src="../../uploads/profiles/<?= htmlspecialchars($user['profile_picture']) ?>"
                  alt="Profile Picture"
                  class="w-24 h-24 rounded-xl object-cover border-4 border-gray-200">
              <?php else: ?>
                <div class="w-24 h-24 rounded-xl bg-gray-200 flex items-center justify-center border-4 border-gray-300">
                  <span class="text-3xl text-gray-400">👤</span>
                </div>
              <?php endif; ?>
            </div>
            <div class="flex-grow">
              <label class="block text-gray-700 font-medium mb-2">Profile Picture</label>
              <input type="file" name="profile_picture" accept="image/jpeg,image/jpg,image/png,image/gif"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-theme-primary focus:border-theme-primary">
              <p class="text-xs text-gray-500 mt-1">Max size: 2MB. Formats: JPEG, PNG, GIF</p>
            </div>
          </div>

          <hr class="border-gray-200">

          <!-- Account Information -->
          <h3 class="text-lg font-semibold mb-4 text-gray-800">Account Information</h3>

          <div class="space-y-4">
            <div>
              <label class="block text-gray-700 mb-1 font-medium">Full Name</label>
              <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-theme-primary focus:border-theme-primary"
                required>
            </div>

            <div>
              <label class="block text-gray-700 mb-1 font-medium">Username</label>
              <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-theme-primary focus:border-theme-primary"
                required>
            </div>

            <div>
              <label class="block text-gray-700 mb-1 font-medium">New Password (optional)</label>
              <input type="password" name="password" placeholder="Leave blank to keep current password"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-theme-primary focus:border-theme-primary">
              <p class="text-xs text-gray-500 mt-1">Leave blank if you don't want to change your password</p>
            </div>
          </div>

          <div class="flex justify-end pt-4">
            <button type="submit"
              class="bg-theme-primary text-white px-6 py-2 rounded-xl hover-theme-darker transition font-medium">
              Save Changes
            </button>
          </div>
        </form>
      </div>
    </main>
  </div>

  <script>
    $(function() {
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