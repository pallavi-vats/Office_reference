<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

require_once '../Product.php';
require_once '../Database.php';

$product = new Product();
$db = new Database();
$conn = $db->getConnection();

//  Unread email count
$emailCountResult = mysqli_query($conn, "SELECT COUNT(*) AS total FROM emails WHERE status = 'unread'");
$emailCountData = mysqli_fetch_assoc($emailCountResult);
$unreadEmails = $emailCountData['total'] ?? 0;

// Delete product
if (isset($_GET['delete'])) {
  if ($product->delete($_GET['delete'])) {
    $_SESSION['alert'] = "Product deleted successfully.";
  } else {
    $_SESSION['alert'] = "Error deleting product.";
  }
  header("Location: list.php");
  exit;
}

$limit = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;
$total = $product->count();
$totalPages = ceil($total / $limit);
$products = $product->getAll($limit, $offset);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product List - Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #9997c9;
      background-image: url("https://www.transparenttextures.com/patterns/clean-gray-paper.png");
      font-family: 'Segoe UI', sans-serif;
    }

    nav.navbar {
      background: linear-gradient(to right, #6a11cb, #2575fc);
    }

    .navbar-brand {
      color: white !important;
      font-weight: bold;
      font-size: 1.5rem;
    }

    .navbar-nav .nav-link {
      color: white !important;
      font-weight: 500;
    }

    .navbar-nav .nav-link.active {
      background: rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      padding: 5px 12px;
    }

    .navbar .form-check-label {
      color: white;
    }

    .table-hover tbody tr:hover {
      background-color: rgba(255, 255, 255, 0.15);
      transition: 0.3s ease;
      box-shadow: 0 0 5px #444;
    }

    .btn-sm {
      transition: all 0.2s ease-in-out;
    }

    .btn-sm:hover {
      transform: scale(1.03);
    }

    .product-img {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #ccc;
    }

    .form-control:focus {
      border-color: #6a11cb;
      box-shadow: 0 0 0 0.2rem rgba(106, 17, 203, 0.25);
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="#">Daily_Products</a>
    <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarContent">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link active" href="list.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="form.php">Add Product</a></li>
        <li class="nav-item"><a class="nav-link" href="export_excel.php">Export Excel</a></li>
      </ul>
      <form action="import_excel.php" method="post" enctype="multipart/form-data" class="d-flex gap-2">
        <input type="file" name="excelFile" accept=".xlsx" required class="form-control form-control-sm">
        <button type="submit" class="btn btn-light btn-sm">Import Excel</button>
      </form>
      <div class="ms-3 text-white">
        👤 <?= htmlspecialchars($_SESSION['username']) ?>
        <a href="logout.php" class="btn btn-outline-light btn-sm ms-2">Logout</a>
      </div>
    </div>
  </div>
</nav>

<!-- 📬 Email Inbox Button -->
<div class="container mt-4 mb-3">
  <a href="admin_email.php" class="btn btn-primary">
    📬 Email Inbox <span class="badge bg-warning text-dark"><?= $unreadEmails ?> Unread</span>
  </a>
</div>

<div class="container py-4">
  <?php if (isset($_SESSION['alert'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
      <?= htmlspecialchars($_SESSION['alert']); unset($_SESSION['alert']); ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="mb-3">
    <input type="text" class="form-control" id="searchInput" placeholder="🔍 Search by title...">
  </div>

  <div class="table-responsive bg-white rounded shadow-sm p-3">
    <table class="table table-bordered table-hover" id="productTable">
      <thead class="table-light">
        <tr>
          <th>S.No</th><th>ID</th><th>Title</th><th>Price</th><th>Weight</th><th>Image</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php $sno = $offset + 1; ?>
      <?php if (empty($products)): ?>
        <tr><td colspan="7" class="text-center text-danger">No products found.</td></tr>
      <?php endif; ?>
      <?php foreach ($products as $row): ?>
        <tr>
          <td><?= $sno++ ?></td>
          <td><?= $row['id'] ?></td>
          <td><?= htmlspecialchars($row['title']) ?></td>
          <td>₹<?= number_format($row['price'], 2) ?></td>
          <td><?= htmlspecialchars($row['weight']) ?> g</td>
          <td>
            <?php
              $imagePath = '../' . ltrim($row['image'] ?? '', '/');
              if (!empty($row['image']) && file_exists($imagePath)): ?>
              <img src="<?= htmlspecialchars($imagePath) ?>" class="product-img" alt="Product Image">
            <?php else: ?>
              <span class="text-muted">No Image</span>
            <?php endif; ?>
          </td>
          <td>
            <a href="detail.php?id=<?= $row['id'] ?>" class="btn btn-info btn-sm">View</a>
            <a href="form.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
            <a href="?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav>
    <ul class="pagination justify-content-center mt-4">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
          <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  const searchInput = document.getElementById('searchInput');
  const tableRows = document.querySelectorAll('#productTable tbody tr');
  searchInput.addEventListener('input', function () {
    const searchTerm = this.value.toLowerCase();
    tableRows.forEach(row => {
      const title = row.cells[2].textContent.toLowerCase();
      row.style.display = title.includes(searchTerm) ? '' : 'none';
    });
  });
</script>
</body>
</html>
