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
                        
                        // Redirect to central navigator after successful login
                        header("Location: /navigator.php");
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MIS Barangay - Login</title>
  <link rel="icon" type="image/x-icon" href="/assets/images/logo.ico">
  <?php loadAllAssets(); ?>
  <style>
    .login-bg {
      background: linear-gradient(135deg, var(--theme-color-6) 0%, var(--theme-secondary) 100%);
    }
    .login-form-container {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
    }
    .logo-container {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .logo-img {
      width: 60px;
      height: 60px;
      object-fit: contain;
    }
  </style>
</head>

<body class="bg-gray-100 widget">

  <section class="min-h-screen flex flex-col lg:flex-row">
    <!-- Left: Landing info with Barangay Hall Image -->
    <div class="lg:w-2/3 login-bg text-white p-8 lg:p-12 flex flex-col justify-center relative overflow-hidden">
      <div class="relative z-10">
        <!-- Logo and Title -->
        <div class="flex items-center gap-4 mb-6">
          <img src="/assets/images/logo.ico" alt="Barangay Logo" class="w-16 h-16 object-contain bg-white rounded-lg p-2 shadow-lg">
          <div>
            <h1 class="text-4xl lg:text-5xl font-bold">MIS Barangay</h1>
            <p class="text-theme-accent text-sm lg:text-base">Barangay Bombongan</p>
          </div>
        </div>
        
        <p class="text-lg lg:text-xl max-w-2xl mb-8 text-theme-accent leading-relaxed">
          A comprehensive and efficient barangay management information system for handling residents, households,
          certificates, and more. Streamline your barangay operations with ease.
        </p>
        
        <!-- Barangay Hall Image -->
        <div class="mt-8">
          <img src="/assets/images/brgy-hall.jpg" alt="Barangay Hall" class="rounded-xl shadow-2xl w-full max-w-2xl object-cover" style="max-height: 400px;">
        </div>
      </div>
      
      <!-- Decorative background elements -->
      <div class="absolute top-0 right-0 w-64 h-64 bg-theme-primary rounded-full opacity-20 blur-3xl"></div>
      <div class="absolute bottom-0 left-0 w-48 h-48 bg-theme-primary rounded-full opacity-20 blur-3xl"></div>
    </div>

    <!-- Right: Login Form -->
    <div class="lg:w-1/3 flex items-center justify-center p-8 login-form-container shadow-2xl">
      <form method="POST" class="w-full max-w-sm">
        <!-- Logo in Login Form -->
        <div class="text-center mb-6">
          <img src="/assets/images/logo.ico" alt="Logo" class="w-20 h-20 mx-auto mb-4 object-contain bg-white rounded-lg p-2 shadow-md">
          <h2 class="text-3xl font-bold text-theme-accent">System Login</h2>
          <p class="text-gray-600 text-sm mt-2">Enter your credentials to access</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4 text-center border border-red-300">
            <span class="font-medium"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
          </div>
          <?= AlertMessage($error, "Error"); ?>
        <?php endif; ?>

        <div class="mb-5">
          <label class="block text-gray-700 font-medium mb-2">Username</label>
          <input type="text" name="username" required 
                 class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary focus:border-theme-primary transition outline-none">
        </div>

        <div class="mb-6">
          <label class="block text-gray-700 font-medium mb-2">Password</label>
          <input type="password" name="password" required 
                 class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-theme-primary focus:border-theme-primary transition outline-none">
        </div>

        <button type="submit" 
                class="w-full bg-theme-primary text-white py-3 rounded-lg hover-theme-darker transition font-semibold shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
          Login
        </button>
        
        <hr class="my-6 border-gray-300">
        
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