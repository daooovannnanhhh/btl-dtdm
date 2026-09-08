<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/excel-export.php';

requireLogin();
requireAdmin();

// Get parameters
$type = isset($_GET['type']) ? $_GET['type'] : '';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Call appropriate export function based on type
switch ($type) {
    case 'revenue':
        exportRevenueReport($from_date, $to_date, $conn);
        break;
    case 'best_selling':
        exportBestSellingProducts($from_date, $to_date, $conn);
        break;
    case 'top_customers':
        exportTopCustomers($from_date, $to_date, $conn);
        break;
    default:
        die('Loại báo cáo không hợp lệ!');
}
?>
