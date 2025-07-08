<?php
session_start();
require_once __DIR__ . '/../Product.php';

$productObj = new Product();

$isSingle = isset($_GET['id']) && is_numeric($_GET['id']);

if ($isSingle) {
  $id = intval($_GET['id']);
  $product = $productObj->getById($id);
} else {
  $result = $productObj->getAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $isSingle ? htmlspecialchars($product['title']) . " | Product Details" : "All Products" ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    .star-rating i {
      color: #ffc107;
    }
    .product-img {
      height: 250px;
      object-fit: contain;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
  </style>
</head>
<body class="bg-light">

<div class="container py-5">
  <a href="list.php" class="btn btn-secondary mb-4">&larr; Back to Product List</a>

  <?php if ($isSingle && $product): ?>
    <?php
      $imagePath = "../" . $product['image'];
      if (!file_exists($imagePath) || empty($product['image'])) {
        $imagePath = "https://via.placeholder.com/300x300.png?text=No+Image";
      }
    ?>
    <div class="card shadow-lg p-4">
      <div class="row g-4">
        <div class="col-md-5 text-center">
          <img src="<?= $imagePath ?>" alt="Product Image" class="img-fluid product-img">
        </div>
        <div class="col-md-7">
          <h2 class="mb-3"><?= htmlspecialchars($product['title']) ?></h2>
          <p><strong>Description:</strong> <?= htmlspecialchars($product['description']) ?></p>
          <p><strong>Price:</strong> ₹<?= number_format($product['price'], 2) ?></p>
          <p><strong>Weight:</strong>
            <?php
              $w = $product['weight'];
              echo ($w >= 1000)
                ? floor($w / 1000) . ' kg ' . ($w % 1000 ? $w % 1000 . ' g' : '')
                : "$w g";
            ?>
          </p>
          <p><strong>Status:</strong> <?= $product['status'] ? '<span class="text-success">Active</span>' : '<span class="text-danger">Inactive</span>' ?></p>

          <div class="star-rating mb-2" title="Rated 4.0 out of 5">
            <strong>Rating:</strong>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star"></i>
            <span class="ms-2">(4.0)</span>
          </div>

          <div class="mt-2">
            <strong>Share:</strong>
            <a href="#" class="btn btn-outline-primary btn-sm"><i class="bi bi-facebook"></i></a>
            <a href="#" class="btn btn-outline-info btn-sm"><i class="bi bi-twitter-x"></i></a>
            <a href="#" class="btn btn-outline-success btn-sm"><i class="bi bi-whatsapp"></i></a>
            <button class="btn btn-outline-secondary btn-sm" onclick="copyLink()">Copy Link</button>
            <div id="copyFeedback" class="text-success mt-2" style="display: none;">✔ Link copied!</div>
          </div>
        </div>
      </div>
    </div>
  <?php elseif (!$isSingle): ?>
    <h2 class="mb-4">All Products</h2>
    <div class="row row-cols-1 row-cols-md-3 g-4">
      <?php foreach ($result as $product): ?>
        <?php
          $imagePath = "../" . $product['image'];
          if (!file_exists($imagePath) || empty($product['image'])) {
            $imagePath = "https://via.placeholder.com/300x300.png?text=No+Image";
          }
        ?>
        <div class="col">
          <div class="card h-100 shadow-sm">
            <img src="<?= $imagePath ?>" class="card-img-top product-img" alt="Product Image">
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($product['title']) ?></h5>
              <p class="card-text"><?= htmlspecialchars($product['description']) ?></p>
              <p><strong>Price:</strong> ₹<?= number_format($product['price'], 2) ?></p>
              <a href="detail.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary btn-sm">View Details</a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="alert alert-danger">Product not found.</div>
  <?php endif; ?>
</div>

<script>
function copyLink() {
  const dummy = document.createElement("input");
  dummy.value = window.location.href;
  document.body.appendChild(dummy);
  dummy.select();
  document.execCommand("copy");
  document.body.removeChild(dummy);
  document.getElementById("copyFeedback").style.display = "block";
  setTimeout(() => document.getElementById("copyFeedback").style.display = "none", 2000);
}
</script>

</body>
</html>
