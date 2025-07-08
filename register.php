<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'Database.php';
require_once 'User.php';

$userObj = new User(); // User inherits Database

$registerError = '';
$registerSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = strtolower(trim($_POST['username'] ?? ''));
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm_password = $_POST['confirm_password'] ?? '';

  if (empty($username) || empty($password) || empty($email)) {
    $registerError = "All fields are required.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $registerError = "Invalid email format.";
  } elseif ($password !== $confirm_password) {
    $registerError = "Passwords do not match.";
  } elseif ($userObj->isUserExists($username, $email)) {
    $registerError = "Username or email already taken.";
  } else {
    if ($userObj->registerUser($username, $email, $password)) {
      $registerSuccess = "Registration successful! <a href='login.php'>Login here</a>.";
    } else {
      $registerError = "Error while registering. Please try again.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body {
      background: linear-gradient(135deg, #74ebd5, #ACB6E5);
      min-height: 100vh;
    }
    .card {
      border-radius: 1rem;
      background-color: #fff;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
  </style>
</head>
<body>
  <div class="container d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-4 shadow" style="max-width: 400px; width: 100%;">
      <h3 class="mb-4 text-center">Register</h3>

      <?php if ($registerError): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($registerError) ?></div>
      <?php endif; ?>
      <?php if ($registerSuccess): ?>
        <div class="alert alert-success"><?= $registerSuccess ?></div>
      <?php endif; ?>

      <form method="POST" action="" novalidate>
        <div class="mb-3">
          <label for="username" class="form-label">Username</label>
          <input type="text" name="username" id="username" class="form-control" required minlength="3" />
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" name="email" id="email" class="form-control" required />
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" name="password" id="password" class="form-control" required minlength="6" />
        </div>
        <div class="mb-3">
          <label for="confirm_password" class="form-label">Confirm Password</label>
          <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6" />
        </div>
        <button type="submit" class="btn btn-primary w-100">Register</button>
      </form>

      <p class="mt-3 text-center">Already have an account? <a href="login.php">Login here</a></p>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
