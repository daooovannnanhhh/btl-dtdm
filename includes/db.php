<?php
// Database configuration
// Ưu tiên đọc từ biến môi trường (Azure App Service > Configuration > Application settings).
// Nếu không có (chạy local bằng XAMPP/Laragon) thì dùng giá trị mặc định.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'starbucks_management');
define('DB_PORT', getenv('DB_PORT') ?: 3306);
define('DB_SSL', getenv('DB_SSL') ?: false); // true nếu Azure MySQL bắt buộc SSL

// Create connection
if (DB_SSL) {
    $conn = mysqli_init();
    mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
    mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, NULL, MYSQLI_CLIENT_SSL);
} else {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
}

// Check connection
if (!$conn || $conn->connect_error) {
    die("Connection failed: " . ($conn->connect_error ?? 'Unknown error'));
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>