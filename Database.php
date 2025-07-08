<?php
class Database {
  protected $host = "localhost";
  protected $username = "root";
  protected $password = "";
  protected $dbname = "product_db";
  public $conn;

  public function __construct() {
    $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);
    if ($this->conn->connect_error) {
      die("Connection failed: " . $this->conn->connect_error);
    }
  }

  public function getConnection() {
    return $this->conn;
  }
}
