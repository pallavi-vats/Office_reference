<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'User.php';

$userObj = new User();
$loginError = '';
$rememberUsername = $_COOKIE['remember_username'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $password = $_POST['password'] ?? '';
  $remember = isset($_POST['remember']);
  $role = $_POST['role'] ?? '';

  $user = $userObj->getUserByUsername($username);

  if ($user && password_verify($password, $user['password'])) {
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_id'] = $user['id'];

    if ($remember) {
      setcookie("remember_username", $username, time() + (7 * 24 * 60 * 60), "/");
    } else {
      setcookie("remember_username", "", time() - 3600, "/");
    }

    if ($role === 'Admin') {
      $_SESSION['admin'] = true;
      header("Location: admin/list.php");
    } else {
      header("Location: index.php");
    }
    exit;
  } else {
    $loginError = "Invalid username or password.";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to right, #6a11cb, #2575fc);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
    }

    .login-card {
      background: #fff;
      border-radius: 12px;
      padding: 30px;
      width: 400px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      animation: fadeIn 0.4s ease-in-out;
    }

    .login-card h3 {
      color: #6a11cb;
      font-weight: 600;
    }

    .btn-primary {
      background-color: #6a11cb;
      border: none;
      transition: 0.3s;
    }

    .btn-primary:hover {
      background-color: #4e0fa3;
    }

    .form-control:focus, .form-select:focus {
      border-color: #6a11cb;
      box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
    }

    a {
      color: #6a11cb;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>

  <div class="login-card">
    <h3 class="text-center mb-4">Login</h3>

    <?php if ($loginError): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($loginError) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="mb-3">
        <label for="username" class="form-label">Username</label>
        <input type="text" name="username" id="username" class="form-control" required value="<?= htmlspecialchars($rememberUsername) ?>">
      </div>

      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control" required>
      </div>

      <div class="mb-3">
        <label for="role" class="form-label">Role</label>
        <select name="role" id="role" class="form-select" required>
          <option value="" disabled selected>Select Role</option>
          <option value="User">User</option>
          <option value="Admin">Admin</option>
        </select>
      </div>

      <div class="form-check mb-3">
        <input type="checkbox" name="remember" id="remember" class="form-check-input" <?= $rememberUsername ? 'checked' : '' ?>>
        <label for="remember" class="form-check-label">Remember Me</label>
      </div>

      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <div class="text-center mt-3">
      <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
  </div>

</body>
</html>
