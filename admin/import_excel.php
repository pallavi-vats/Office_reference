<?php
session_start();
require 'vendor/autoload.php';
require_once '../Database.php';// external file

use PhpOffice\PhpSpreadsheet\IOFactory;

// Product Importer class
class ProductImporter {
    private $conn;

    public function __construct($dbConn) {
        $this->conn = $dbConn;
    }

    public function importFromExcel($filePath) {
        try {
            $spreadsheet = IOFactory::load($filePath);
            $data = $spreadsheet->getActiveSheet()->toArray();

            for ($i = 1; $i < count($data); $i++) {
                list($title, $description, $price, $weight, $status) = $data[$i];

                // Check if product already exists
                $check = $this->conn->prepare("SELECT id FROM products WHERE title = ? AND description = ?");
                $check->bind_param("ss", $title, $description);
                $check->execute();
                $check->store_result();

                if ($check->num_rows == 0) {
                    $stmt = $this->conn->prepare("INSERT INTO products (title, description, price, weight, status) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssddi", $title, $description, $price, $weight, $status);
                    $stmt->execute();
                    $stmt->close();
                }
                $check->close();
            }

            $_SESSION['import_success'] = "Import successful.";
        } catch (Exception $e) {
            $_SESSION['import_error'] = "Import failed: " . $e->getMessage();
        }

        header("Location: list.php");
        exit();
    }
}

// Main Execution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excelFile']['tmp_name'])) {
    $db = new Database(); // From external Database.php
    $importer = new ProductImporter($db->conn);
    $filePath = $_FILES['excelFile']['tmp_name'];
    $importer->importFromExcel($filePath);
}
?>

<!-- HTML Upload Form -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Import Products from Excel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <h2 class="mb-4">Import Products from Excel</h2>

  <?php if (isset($_SESSION['import_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['import_success'] ?></div>
    <?php unset($_SESSION['import_success']); ?>
  <?php elseif (isset($_SESSION['import_error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['import_error'] ?></div>
    <?php unset($_SESSION['import_error']); ?>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data" class="border p-4 rounded bg-white shadow-sm">
    <div class="mb-3">
      <label class="form-label">Select Excel File (.xlsx or .xls)</label>
      <input type="file" name="excelFile" accept=".xlsx,.xls" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Import Excel</button>
    <a href="list.php" class="btn btn-secondary">Back</a>
  </form>
</div>
</body>
</html>
