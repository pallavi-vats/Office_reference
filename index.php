<?php
// Start session if needed
session_start();

// Database Class

require_once 'Database.php';
// Product Class
class Product {
    private $conn;

    public function __construct($connection) {
        $this->conn = $connection;
    }

    public function getActiveProducts() {
        return $this->conn->query("SELECT * FROM products WHERE status = 1");
    }
}

// Init classes
$db = new Database();
$productObj = new Product($db->conn);
$result = $productObj->getActiveProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Daily Glam | Home</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    .product-card img {
      height: 250px;
      object-fit: contain;
    }
  </style>
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container py-5">
  <h2 class="mb-4 text-center">Our Products</h2>
  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
    <?php while ($product = $result->fetch_assoc()): ?>
      <div class="col">
        <div class="card h-100 shadow-sm product-card">
          <img src="<?= htmlspecialchars($product['image']) ?: 'https://via.placeholder.com/300x300.png?text=No+Image' ?>" class="card-img-top" alt="Product Image">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?= htmlspecialchars($product['title']) ?></h5>
            <p class="card-text"><?= substr(htmlspecialchars($product['description']), 0, 80) ?>...</p>
            <h6 class="text-primary">₹<?= number_format($product['price'], 2) ?></h6>
            <a href="view.php?id=<?= $product['id'] ?>" class="btn btn-outline-dark mt-auto">View Details</a>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<footer class="bg-dark text-white text-center py-3 mt-5">
  &copy; <?= date("Y") ?> Daily Glam. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
