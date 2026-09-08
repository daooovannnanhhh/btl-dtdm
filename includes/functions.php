<?php
// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../auth/login.php");
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header("Location: ../customer/index.php");
        exit();
    }
}

// Format currency VND
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' ₫';
}

// Sanitize input
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim($data));
}

// Get cart count
function getCartCount() {
    global $conn;
    if (!isLoggedIn()) return 0;
    
    $user_id = $_SESSION['user_id'];
    $query = "SELECT SUM(quantity) as total FROM cart WHERE user_id = $user_id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'] ?? 0;
}

// Get user info
function getUserInfo($user_id) {
    global $conn;
    $query = "SELECT * FROM users WHERE user_id = " . (int)$user_id;
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result);
}

// Upload image
function uploadImage($file, $folder = 'products') {
    $target_dir = "../assets/images/$folder/";
    
    // Create directory if not exists
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return false;
    }
    
    // Check file size (max 5MB)
    if ($file["size"] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    $allowed_types = array("jpg", "jpeg", "png", "gif", "webp");
    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return "assets/images/$folder/" . $new_filename;
    }
    
    return false;
}

// Get order status badge
function getOrderStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Chờ xác nhận</span>',
        'confirmed' => '<span class="badge bg-info">Đã xác nhận</span>',
        'preparing' => '<span class="badge bg-primary">Đang chuẩn bị</span>',
        'ready' => '<span class="badge bg-success">Sẵn sàng</span>',
        'completed' => '<span class="badge bg-secondary">Hoàn thành</span>',
        'cancelled' => '<span class="badge bg-danger">Đã hủy</span>'
    ];
    return $badges[$status] ?? $status;
}

// Get payment status badge
function getPaymentStatusBadge($status) {
    $badges = [
        'unpaid' => '<span class="badge bg-danger">Chưa thanh toán</span>',
        'paid' => '<span class="badge bg-success">Đã thanh toán</span>'
    ];
    return $badges[$status] ?? $status;
}
?>