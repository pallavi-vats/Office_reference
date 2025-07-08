<?php
session_start();
session_unset();
session_destroy();

if (isset($_COOKIE['remember_username'])) {
    setcookie('remember_username', '', time() - 3600, '/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Logged Out</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
  <meta http-equiv="refresh" content="5;url=login.php">
  <style>
    body {
      background: linear-gradient(135deg, #d9afd9, #97d9e1);
      transition: background 0.10s ease;
    }
    .logout-card {
      animation: fadeIn 0.6s ease-in-out;
      background-color: #ffffff;
      border-radius: 1rem;
      padding: 40px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
      max-width: 450px;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .dark-mode {
      background: #121212 !important;
      color: #e0e0e0;
    }
    .dark-mode .logout-card {
      background-color: #1f1f1f;
      box-shadow: 0 8px 20px rgba(255, 255, 255, 0.05);
    }
  </style>
</head>
<body class="d-flex flex-column justify-content-center align-items-center" style="height: 100vh;">

  <div class="logout-card text-center">
    <img src="https://via.placeholder.com/100x100.png?text=Logo" class="mb-3 rounded-circle" alt="Logo">
    <h2 class="text-success mb-3">Logged out successfully</h2>
    <p class="text-muted">You will be redirected to the login page in <span id="countdown">5</span> seconds.</p>
    <a href="login.php" class="btn btn-primary mt-3">Login Now</a>

    <button onclick="toggleDarkMode()" class="btn btn-sm btn-outline-secondary mt-4">
      <i class="bi bi-moon-stars-fill me-1"></i> Toggle Dark Mode
    </button>
  </div>

  <script>
    let counter = 5;
    const interval = setInterval(() => {
      counter--;
      if (counter >= 0) {
        document.getElementById("countdown").innerText = counter;
      } else {
        clearInterval(interval);
      }
    }, 1000);

    function toggleDarkMode() {
      document.body.classList.toggle("dark-mode");
    }
  </script>
</body>
</html>

