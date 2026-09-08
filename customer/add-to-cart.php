<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    $size = isset($_POST['size']) ? sanitize($_POST['size']) : 'medium';
    $user_id = $_SESSION['user_id'];
    
    // Validate product exists and is available
    $product_query = "SELECT * FROM products WHERE product_id = $product_id AND is_available = 1";
    $product_result = mysqli_query($conn, $product_query);
    
    if (mysqli_num_rows($product_result) == 1) {
        $product = mysqli_fetch_assoc($product_result);
        
        // Check stock
        if ($product['stock'] < $quantity) {
            $_SESSION['error'] = 'Số lượng sản phẩm không đủ!';
            header("Location: menu.php");
            exit();
        }
        
        // Check if product already in cart
        $check_cart = "SELECT * FROM cart WHERE user_id = $user_id AND product_id = $product_id AND size = '$size'";
        $cart_result = mysqli_query($conn, $check_cart);
        
        if (mysqli_num_rows($cart_result) > 0) {
            // Update quantity
            $cart_item = mysqli_fetch_assoc($cart_result);
            $new_quantity = $cart_item['quantity'] + $quantity;
            
            // Check if new quantity exceeds stock
            if ($new_quantity > $product['stock']) {
                $_SESSION['error'] = 'Số lượng trong giỏ hàng vượt quá tồn kho!';
                header("Location: menu.php");
                exit();
            }
            
            $update_query = "UPDATE cart SET quantity = $new_quantity WHERE cart_id = {$cart_item['cart_id']}";
            mysqli_query($conn, $update_query);
        } else {
            // Add new item to cart
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity, size) 
                            VALUES ($user_id, $product_id, $quantity, '$size')";
            mysqli_query($conn, $insert_query);
        }
        
        $_SESSION['success'] = 'Đã thêm sản phẩm vào giỏ hàng!';
    } else {
        $_SESSION['error'] = 'Sản phẩm không tồn tại hoặc không còn bán!';
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'menu.php'));
exit();
?>