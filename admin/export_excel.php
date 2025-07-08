<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Database class
require_once __DIR__ . '/../Database.php';


// Product Exporter class
class ProductExporter {
    private $conn;

    public function __construct($dbConn) {
        $this->conn = $dbConn;
    }

    public function exportToExcel() {
        $sql = "SELECT title, description, price, weight, status FROM products";
        $result = $this->conn->query($sql);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Title', 'Description', 'Price', 'Weight', 'Status'], NULL, 'A1');

        $rowNum = 2;
        while ($row = $result->fetch_assoc()) {
            $sheet->fromArray(array_values($row), NULL, "A$rowNum");
            $rowNum++;
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="products.xlsx"');
        $writer->save('php://output');
        exit;
    }
}

// Main Execution
$db = new Database();
$exporter = new ProductExporter($db->conn);
$exporter->exportToExcel();
