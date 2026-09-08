<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: order-history.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Verify order belongs to user and is cancellable
$check_query = "SELECT * FROM orders WHERE order_id = $order_id AND user_id = $user_id AND status = 'pending'";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    $_SESSION['error'] = 'Không thể hủy đơn hàng này!';
    header("Location: order-history.php");
    exit();
}

// Update order status
$update_query = "UPDATE orders SET status = 'cancelled' WHERE order_id = $order_id";

if (mysqli_query($conn, $update_query)) {
    // Restore product stock
    $items_query = "SELECT product_id, quantity FROM order_items WHERE order_id = $order_id";
    $items_result = mysqli_query($conn, $items_query);
    
    while ($item = mysqli_fetch_assoc($items_result)) {
        $restore_stock = "UPDATE products SET stock = stock + {$item['quantity']} WHERE product_id = {$item['product_id']}";
        mysqli_query($conn, $restore_stock);
    }
    
    $_SESSION['success'] = 'Đã hủy đơn hàng thành công!';
} else {
    $_SESSION['error'] = 'Hủy đơn hàng thất bại! Vui lòng thử lại.';
}

header("Location: order-history.php");
exit();
?>