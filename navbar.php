<?php if (session_status() === PHP_SESSION_NONE) session_start(); ?>
<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="index.php">Daily Glam</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <?php if (isset($_SESSION['username'])): ?>
        <div class="ms-auto d-flex align-items-center">
          <span class="me-2">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
          <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline-success btn-sm ms-auto">Login</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
