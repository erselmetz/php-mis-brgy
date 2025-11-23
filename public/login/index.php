<?php
require_once '../../includes/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';

  if (empty($username) || empty($password)) {
    $error = "Username and password are required.";
  } else {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();

      if (password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
          $error = "Account is " . htmlspecialchars($user['status']) . ". Please contact admin.";
        } else {
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['username'] = $user['username'];
          $_SESSION['name'] = $user['name'];
          $_SESSION['role'] = $user['role'];
          header("Location: /dashboard");
          exit;
        }
      } else {
        $error = "Invalid password.";
      }
    } else {
      $error = "No account found.";
    }
    $stmt->close();
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

            <span>❌ Error : <?php echo $_GET['error'] ?></span>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </section>
</body>

</html>