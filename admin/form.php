<?php
session_start();
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

require_once '../Database.php';

class Product {
  private $conn;
  public function __construct($conn) { $this->conn = $conn; }

  public function getById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
  }

  public function save($data, $file, $id = 0) {
    $imagePath = $data['existing_image'] ?? '';
    if (!empty($file['image']['name'])) {
      $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
      if (in_array($file['image']['type'], $allowedTypes)) {
        $name = time() . '_' . basename($file['image']['name']);
        $targetPath = '../uploads/' . $name;
        $imagePath = 'uploads/' . $name;
        move_uploaded_file($file['image']['tmp_name'], $targetPath);
      }
    }

    if ($id) {
      $stmt = $this->conn->prepare("UPDATE products SET title=?, description=?, price=?, weight=?, status=?, image=? WHERE id=?");
      $stmt->bind_param("ssddisi", $data['title'], $data['description'], $data['price'], $data['weight'], $data['status'], $imagePath, $id);
      $_SESSION['alert'] = "Product updated successfully.";
    } else {
      $stmt = $this->conn->prepare("INSERT INTO products (title, description, price, weight, status, image) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("ssddis", $data['title'], $data['description'], $data['price'], $data['weight'], $data['status'], $imagePath);
      $_SESSION['alert'] = "Product added successfully.";
    }
    $stmt->execute();
  }
}

$db = new Database();
$productObj = new Product($db->conn);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = ['title' => '', 'description' => '', 'price' => '', 'weight' => '', 'status' => 0, 'image' => ''];
if ($id) $product = $productObj->getById($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $data = [
    'title' => htmlspecialchars($_POST['title']),
    'description' => htmlspecialchars($_POST['description']),
    'price' => floatval($_POST['price']),
    'weight' => floatval($_POST['weight']),
    'status' => isset($_POST['status']) ? 1 : 0,
    'existing_image' => $product['image'] ?? ''
  ];
  $productObj->save($data, $_FILES, $id);
  header("Location: list.php");
  exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $id ? 'Edit' : 'Add' ?> Product</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background-color: #9997c9;
      background-image: url("https://www.transparenttextures.com/patterns/clean-gray-paper.png");
    }
    .form-container {
      background-color: #fff;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .form-control:focus {
      border-color: #6c63ff;
      box-shadow: 0 0 0 0.2rem rgba(108, 99, 255, 0.25);
    }
    .btn-primary {
      background-color: #6c63ff;
      border: none;
    }
    .btn-primary:hover {
      background-color: #574fd6;
    }
    .navbar {
      background: linear-gradient(to right, #6c63ff, #3f87ff);
    }
    .navbar-brand, .nav-link, .navbar span {
      color: #fff !important;
      font-weight: 500;
    }
    .btn-outline-danger {
      border-color: #fff;
      color: #fff;
    }
    .btn-outline-danger:hover {
      background-color: #fff;
      color: #dc3545;
    }
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="list.php">Daily_Products</a>
    <div class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        <li class="nav-item"><a class="nav-link" href="list.php">Dashboard</a></li>
        <li class="nav-item"><a class="nav-link" href="export_excel.php">Export Excel</a></li>
      </ul>
      <div class="d-flex">
        <span class="me-2">👤 <?= htmlspecialchars($_SESSION['username']) ?></span>
        <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
      </div>
    </div>
  </div>
</nav>

<div class="container py-5">
  <div class="form-container">
    <h3 class="mb-4"><?= $id ? 'Edit' : 'Add' ?> Product</h3>
    <form method="POST" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">Title</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($product['title']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Description</label>
        <input type="text" name="description" class="form-control" value="<?= htmlspecialchars($product['description']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Price</label>
        <input type="number" step="0.01" name="price" class="form-control" value="<?= $product['price'] ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Weight (grams)</label>
        <input type="number" step="0.1" name="weight" class="form-control" value="<?= $product['weight'] ?>" required>
      </div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" name="status" <?= $product['status'] ? 'checked' : '' ?>>
        <label class="form-check-label">Active</label>
      </div>
      <div class="mb-3">
        <label class="form-label">Image</label>
        <input type="file" name="image" class="form-control">
        <?php if ($product['image']): ?>
          <img src="../<?= $product['image'] ?>" width="100" class="mt-2 border rounded">
        <?php endif; ?>
      </div>
      <button type="submit" class="btn btn-primary">Save</button>
      <a href="list.php" class="btn btn-secondary">Cancel</a>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
