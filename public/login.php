<?php
require_once '../includes/app.php';

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

<body class="bg-light widget">

  <section class="v-100 d-flex flex-column flex-lg-row">
    <!-- Left: Landing info -->
    <div class="col-lg-8 bg-primary text-white p-5 d-flex flex-column justify-content-center">
      <h1 class="display-5 fw-bold mb-4">MIS Barangay</h1>
      <p class="lead" style="max-width: 32rem; margin-bottom: 2rem;">
        A simple and efficient barangay management information system for handling residents, households,
        certificates, and more.
      </p>
      <img src="assets/images/barangay.jpg" alt="Barangay" class="rounded shadow-lg" style="max-width: 75%; height: auto;">
    </div>

    <!-- Right: Login -->
    <div class="col-lg-4 d-flex align-items-center justify-content-center p-4 bg-white shadow-lg">
      <form method="POST" style="width: 100%; max-width: 28rem;">
        <h2 class="h3 fw-bold mb-4 text-center text-primary">System Login</h2>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
          <?= AlertMessage($error, "Error"); ?>
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" required class="form-control">
        </div>

        <div class="mb-4">
          <label class="form-label">Password</label>
          <input type="password" name="password" required class="form-control">
        </div>

        <button type="submit" class="w-100 btn btn-primary py-2">
          Login
        </button>
        <hr class="my-4">
        <!-- show error message -->
        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2" role="alert">
            <span>❌ Error : <?php echo htmlspecialchars($_GET['error']) ?></span>
          </div>
        <?php endif; ?>
      </form>
    </div>
  </section>
</body>

</html>