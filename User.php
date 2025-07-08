<?php
require_once 'Database.php';

class User extends Database {

  public function __construct() {
    parent::__construct(); // Call Database constructor
  }

  // For login
  public function getUserByUsername($username) {
    $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
  }

  // For registration: check if user exists
  public function isUserExists($username, $email) {
    $stmt = $this->conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
  }

  // For registration: insert new user
  public function registerUser($username, $email, $password) {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $this->conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    return $stmt->execute();
  }
}
?>
