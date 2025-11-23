<?php
/**
 * Login Page
 * 
 * Handles user authentication and session management.
 * Uses prepared statements to prevent SQL injection attacks.
 * Validates user credentials and sets session variables upon successful login.
 */

require_once '../../includes/app.php';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // Basic validation - ensure both fields are provided
    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        /**
         * Use prepared statement to prevent SQL injection attacks
         * This is critical for security - never use string concatenation for SQL queries
         */
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        if ($stmt === false) {
            // Log database error for debugging
            error_log('Login query preparation failed: ' . $conn->error);
            $error = "Database error. Please try again later.";
        } else {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                /**
                 * Verify password using PHP's password_verify function
                 * This securely compares the provided password with the hashed password in the database
                 */
                if (password_verify($password, $user['password'])) {
                    // Check if account is active
                    if ($user['status'] !== 'active') {
                        $error = "Account is " . htmlspecialchars($user['status'], ENT_QUOTES, 'UTF-8') . ". Please contact admin.";
                    } else {
                        // Set session variables for authenticated user
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['role'] = $user['role'];
                        
                        // Redirect to dashboard after successful login
                        header("Location: /dashboard");
                        exit;
                    }
                } else {
                    // Invalid password - don't reveal which field was wrong (security best practice)
                    $error = "Invalid username or password.";
                }
            } else {
                // User not found - same message as invalid password (security best practice)
                $error = "Invalid username or password.";
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
  <title>MIS Barangay - Login</title>
  <?php loadAllAssets(); ?>
</head>

<body class="bg-gray-100 widget">

  <section class="min-h-screen flex flex-col lg:flex-row">
    <!-- Left: Landing info -->
    <div class="lg:w-2/3 bg-blue-700 text-white p-12 flex flex-col justify-center">
      <h1 class="text-5xl font-bold mb-6">MIS Barangay</h1>
      <p class="text-lg max-w-2xl mb-10">
        A simple and efficient barangay management information system for handling residents, households,
        certificates, and more.
      </p>
      <img src="assets/images/barangay.jpg" alt="Barangay" class="rounded-lg shadow-lg w-3/4">
    </div>

    <!-- Right: Login -->
    <div class="lg:w-1/3 flex items-center justify-center p-8 bg-white shadow-lg">
      <form method="POST" class="w-full max-w-sm">
        <h2 class="text-3xl font-bold mb-6 text-center text-blue-700">System Login</h2>

        <?php if (!empty($error)): ?>
          <div class="bg-red-100 text-red-700 p-2 rounded mb-4 text-center"><?= $error ?></div>
          <?= AlertMessage($error, "Error"); ?>
        <?php endif; ?>

        <div class="mb-4">
          <label class="block text-gray-700 mb-2">Username</label>
          <input type="text" name="username" required class="w-full p-2 border rounded">
        </div>

        <div class="mb-6">
          <label class="block text-gray-700 mb-2">Password</label>
          <input type="password" name="password" required class="w-full p-2 border rounded">
        </div>

        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition">
          Login
        </button>
        <hr class="my-6">
        <!-- show error message -->
        <?php if (isset($_GET['error'])): ?>
          <div class="mb-4 p-3 rounded-lg border border-red-300 bg-red-50 text-red-700 flex items-center space-x-2">
            <span>❌ Error : <?= htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8') ?></span>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </section>
</body>

</html>