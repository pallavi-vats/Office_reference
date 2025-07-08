<?php
require_once __DIR__ . '/Database.php';

class Product extends Database {

  public function __construct() {
    parent::__construct(); // Connect to DB from parent
  }

  public function delete($id) {
    $id = intval($id);
    $imgQuery = $this->conn->query("SELECT image FROM products WHERE id = $id");
    if ($imgQuery && $imgQuery->num_rows > 0) {
      $imgRow = $imgQuery->fetch_assoc();
      if (!empty($imgRow['image']) && file_exists("../" . $imgRow['image'])) {
        unlink("../" . $imgRow['image']); // Delete image file
      }
    }
    return $this->conn->query("DELETE FROM products WHERE id = $id");
  }

  public function count() {
    $result = $this->conn->query("SELECT COUNT(*) AS total FROM products");
    $row = $result->fetch_assoc();
    return $row['total'];
  }

  public function getAll($limit = 1000, $offset = 0) {
    $stmt = $this->conn->prepare("SELECT * FROM products LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
  }

  public function getById($id) {
    $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }
}
