<?php
require_once '../includes/app.php';
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
<body class="bg-light" style="display: none;">
  <?php include_once './navbar.php'; ?>
  <div class="d-flex bg-light">
    <?php include_once './sidebar.php'; ?>
    <main class="p-4 w-100">
      <h2 class="h3 mb-4">Profile Settings</h2>
      <div class="mx-auto bg-white shadow-sm rounded p-4" style="max-width: 48rem;">
        
        <?php if (!empty($success)): ?>
          <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php elseif (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
          <!-- Profile Picture Section -->
          <div class="d-flex gap-4 mb-4">
            <div style="flex-shrink: 0;">
              <?php if (!empty($user['profile_picture']) && file_exists(__DIR__ . '/uploads/profiles/' . $user['profile_picture'])): ?>
                 <img src="/uploads/profiles/<?= htmlspecialchars($user['profile_picture']) ?>" 
                   alt="Profile Picture" 
                   width="96" height="96"
                   style="width: 96px; height: 96px; min-width: 96px; min-height: 96px; border-radius: 50%;"
                   class="settings-avatar avatar rounded-circle border-4 border-secondary">
              <?php else: ?>
                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center border-4 border-dark" style="width: 96px; height: 96px;">
                  <span class="fs-2 text-muted">👤</span>
                </div>
              <?php endif; ?>
            </div>
            <div class="flex-grow-1">
              <label class="form-label">Profile Picture</label>
              <input type="file" name="profile_picture" accept="image/jpeg,image/jpg,image/png,image/gif"
                class="form-control">
              <div class="form-text">Max size: 2MB. Formats: JPEG, PNG, GIF</div>
            </div>
          </div>

          <hr>

          <!-- Account Information -->
          <h3 class="h5 mb-3">Account Information</h3>
          
          <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>"
              class="form-control"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>"
              class="form-control"
              required>
          </div>

          <div class="mb-3">
            <label class="form-label">New Password (optional)</label>
            <input type="password" name="password" placeholder="Leave blank to keep current password"
              class="form-control">
            <div class="form-text">Leave blank if you don't want to change your password</div>
          </div>

          <div class="d-flex justify-content-end pt-3">
            <button type="submit"
              class="btn btn-primary">
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
            '<img src="' + e.target.result + '" alt="Profile Preview" width="96" height="96" class="settings-avatar avatar rounded-circle border-4 border-secondary">'
          );
        };
          reader.readAsDataURL(file);
        }
      });
    });
  </script>
</body>
</html>
