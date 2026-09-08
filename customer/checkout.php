<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_info = getUserInfo($_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

// Get cart items
$cart_query = "SELECT c.*, p.product_name, p.price, p.stock 
               FROM cart c 
               JOIN products p ON c.product_id = p.product_id 
               WHERE c.user_id = $user_id";
$cart_items = mysqli_query($conn, $cart_query);

// Check if cart is empty
if (mysqli_num_rows($cart_items) == 0) {
    header("Location: cart.php");
    exit();
}

// Calculate total
$total = 0;
$has_stock_issue = false;
mysqli_data_seek($cart_items, 0);
while ($item = mysqli_fetch_assoc($cart_items)) {
    $total += $item['price'] * $item['quantity'];
    if ($item['stock'] < $item['quantity']) {
        $has_stock_issue = true;
    }
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $delivery_address = sanitize($_POST['delivery_address']);
    $phone = sanitize($_POST['phone']);
    $payment_method = sanitize($_POST['payment_method']);
    $notes = sanitize($_POST['notes']);
    
    if (empty($delivery_address) || empty($phone)) {
        $_SESSION['error'] = 'Vui lòng điền đầy đủ thông tin giao hàng!';
    } elseif ($has_stock_issue) {
        $_SESSION['error'] = 'Một số sản phẩm không đủ số lượng!';
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Thanh toán online và COD đều tạo với trạng thái 'unpaid'
            // Admin sẽ xác nhận sau
            $payment_status = 'unpaid';
            
            $insert_order = "INSERT INTO orders (user_id, total_amount, payment_method, payment_status, delivery_address, phone, notes) 
                            VALUES ($user_id, $total, '$payment_method', '$payment_status', '$delivery_address', '$phone', '$notes')";
            
            if (mysqli_query($conn, $insert_order)) {
                $order_id = mysqli_insert_id($conn);
                
                // Get cart items again
                mysqli_data_seek($cart_items, 0);
                
                // Insert order items and update stock
                while ($item = mysqli_fetch_assoc($cart_items)) {
                    $product_id = $item['product_id'];
                    $quantity = $item['quantity'];
                    $price = $item['price'];
                    $size = $item['size'];
                    
                    // Insert order item
                    $insert_item = "INSERT INTO order_items (order_id, product_id, quantity, price, size) 
                                   VALUES ($order_id, $product_id, $quantity, $price, '$size')";
                    mysqli_query($conn, $insert_item);
                    
                    // Update stock
                    $update_stock = "UPDATE products SET stock = stock - $quantity WHERE product_id = $product_id";
                    mysqli_query($conn, $update_stock);
                }
                
                // Clear cart
                $clear_cart = "DELETE FROM cart WHERE user_id = $user_id";
                mysqli_query($conn, $clear_cart);
                
                // Commit transaction
                mysqli_commit($conn);
                
                // Nếu thanh toán online, chuyển sang trang QR
                if ($payment_method == 'online') {
                    $_SESSION['pending_payment_order'] = $order_id;
                    header("Location: payment-qr.php?order_id=$order_id");
                    exit();
                } else {
                    $_SESSION['success'] = 'Đặt hàng thành công!';
                    header("Location: order-detail.php?id=$order_id");
                    exit();
                }
            }
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $_SESSION['error'] = 'Đã xảy ra lỗi! Vui lòng thử lại.';
        }
    }
}

mysqli_data_seek($cart_items, 0);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Starbucks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="container">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-coffee"></i> Star<span>bucks</span>
            </a>
            <ul class="navbar-menu">
                <li><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><a href="menu.php"><i class="fas fa-book-open"></i> Menu</a></li>
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Giỏ hàng</a></li>
                <li><a href="order-history.php"><i class="fas fa-history"></i> Đơn hàng</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_info['full_name']); ?></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="text-align: center; color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-credit-card"></i> Thanh toán
        </h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <?php if ($has_stock_issue): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> 
                Một số sản phẩm không đủ số lượng! Vui lòng quay lại giỏ hàng và cập nhật.
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 2rem;">
            <!-- Checkout Form -->
            <div>
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">
                        <i class="fas fa-shipping-fast"></i> Thông tin giao hàng
                    </h3>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Số điện thoại <span style="color: red;">*</span>
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_info['phone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="delivery_address">
                                <i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng <span style="color: red;">*</span>
                            </label>
                            <textarea id="delivery_address" name="delivery_address" class="form-control" 
                                      rows="3" required><?php echo htmlspecialchars($user_info['address']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">
                                <i class="fas fa-sticky-note"></i> Ghi chú đơn hàng
                            </label>
                            <textarea id="notes" name="notes" class="form-control" 
                                      rows="3" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-credit-card"></i> Phương thức thanh toán <span style="color: red;">*</span>
                            </label>
                            <div style="display: flex; flex-direction: column; gap: 1rem; margin-top: 0.5rem;">
                                <label style="cursor: pointer; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 10px; transition: all 0.3s;">
                                    <input type="radio" name="payment_method" value="cod" checked style="margin-right: 0.5rem;">
                                    <span style="font-weight: 500;">
                                        <i class="fas fa-money-bill-wave" style="color: var(--success);"></i> 
                                        Thanh toán khi nhận hàng (COD)
                                    </span>
                                </label>
                                <label style="cursor: pointer; padding: 1rem; border: 2px solid #e0e0e0; border-radius: 10px; transition: all 0.3s;">
                                    <input type="radio" name="payment_method" value="online" style="margin-right: 0.5rem;">
                                    <span style="font-weight: 500;">
                                        <i class="fas fa-qrcode" style="color: var(--primary-color);"></i> 
                                        Chuyển khoản ngân hàng (Quét mã QR)
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;"
                                <?php echo $has_stock_issue ? 'disabled' : ''; ?>>
                            <i class="fas fa-check-circle"></i> Đặt hàng
                        </button>
                        
                        <a href="cart.php" class="btn btn-outline" 
                           style="width: 100%; margin-top: 1rem; color: var(--primary-color); border: 2px solid var(--primary-color); display: inline-block; text-align: center;">
                            <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                        </a>
                    </form>
                </div>
            </div>

            <!-- Order Summary -->
            <div>
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">
                        <i class="fas fa-receipt"></i> Đơn hàng của bạn
                    </h3>
                    
                    <div style="max-height: 400px; overflow-y: auto; margin-bottom: 1.5rem;">
                        <?php while ($item = mysqli_fetch_assoc($cart_items)): 
                            $item_total = $item['price'] * $item['quantity'];
                        ?>
                            <div style="padding: 1rem 0; border-bottom: 1px solid #e0e0e0;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 0.3rem;">
                                    <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                    <strong><?php echo formatCurrency($item_total); ?></strong>
                                </div>
                                <div style="color: var(--text-light); font-size: 0.9rem;">
                                    Size: <?php echo strtoupper($item['size']); ?> × <?php echo $item['quantity']; ?>
                                </div>
                                <?php if ($item['stock'] < $item['quantity']): ?>
                                    <div style="color: var(--danger); font-size: 0.85rem; margin-top: 0.3rem;">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Chỉ còn <?php echo $item['stock']; ?> sản phẩm
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <strong><?php echo formatCurrency($total); ?></strong>
                    </div>
                    
                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <strong style="color: var(--success);">Miễn phí</strong>
                    </div>
                    
                    <div class="summary-row total">
                        <span>Tổng cộng:</span>
                        <strong><?php echo formatCurrency($total); ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <p><i class="fas fa-coffee"></i> Starbucks Coffee Company</p>
            <p>&copy; 2024 Starbucks. All rights reserved.</p>
        </div>
    </div>

    <script>
        // Highlight selected payment method
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('label').forEach(label => {
                    label.style.borderColor = '#e0e0e0';
                    label.style.backgroundColor = 'transparent';
                });
                this.parentElement.style.borderColor = 'var(--primary-color)';
                this.parentElement.style.backgroundColor = 'rgba(0, 112, 74, 0.05)';
            });
        });
        
        // Set initial state
        document.querySelector('input[name="payment_method"]:checked').parentElement.style.borderColor = 'var(--primary-color)';
        document.querySelector('input[name="payment_method"]:checked').parentElement.style.backgroundColor = 'rgba(0, 112, 74, 0.05)';
    </script>
</body>
</html>