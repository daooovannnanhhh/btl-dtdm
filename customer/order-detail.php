<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_info = getUserInfo($_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

// Get order ID
if (!isset($_GET['id'])) {
    header("Location: order-history.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Get order details - ensure it belongs to current user
$order_query = "SELECT * FROM orders WHERE order_id = $order_id AND user_id = $user_id";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: order-history.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT oi.*, p.product_name, p.image 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.product_id 
                WHERE oi.order_id = $order_id";
$items = mysqli_query($conn, $items_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_id; ?> - Starbucks</title>
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
                <li>
                    <a href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Giỏ hàng
                        <?php 
                        $cart_count = getCartCount();
                        if ($cart_count > 0): 
                        ?>
                            <span class="cart-badge"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li><a href="order-history.php"><i class="fas fa-history"></i> Đơn hàng</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_info['full_name']); ?></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <div style="margin-bottom: 2rem;">
            <a href="order-history.php" class="btn btn-outline" style="border: 2px solid var(--primary-color); color: var(--primary-color);">
                <i class="fas fa-arrow-left"></i> Quay lại
            </a>
        </div>

        <div style="background: var(--white); border-radius: 15px; padding: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
            <!-- Order Header -->
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 2rem; padding-bottom: 2rem; border-bottom: 2px solid var(--light-bg);">
                <div>
                    <h1 style="color: var(--primary-color); margin-bottom: 1rem;">
                        <i class="fas fa-receipt"></i> Đơn hàng #<?php echo $order['order_id']; ?>
                    </h1>
                    <p style="color: var(--text-light); margin: 0.3rem 0;">
                        <i class="fas fa-calendar"></i> Ngày đặt: <strong><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></strong>
                    </p>
                    <p style="color: var(--text-light); margin: 0.3rem 0;">
                        <i class="fas fa-sync"></i> Cập nhật: <strong><?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?></strong>
                    </p>
                </div>
                <div style="text-align: right;">
                    <?php echo getOrderStatusBadge($order['status']); ?>
                    <div style="margin-top: 0.8rem;">
                        <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                    </div>
                    <div style="margin-top: 0.8rem; color: var(--text-light); font-size: 0.9rem;">
                        <i class="fas fa-credit-card"></i> <?php echo $order['payment_method'] == 'cod' ? 'Thanh toán COD' : 'Thanh toán Online'; ?>
                    </div>
                </div>
            </div>

            <!-- Order Items -->
            <div style="margin-bottom: 3rem;">
                <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i class="fas fa-shopping-bag"></i> Sản phẩm đã đặt
                </h3>
                <div>
                    <?php while ($item = mysqli_fetch_assoc($items)): ?>
                        <div style="display: flex; gap: 1.5rem; padding: 1.5rem; border: 2px solid var(--light-bg); border-radius: 10px; margin-bottom: 1rem;">
                            <img src="../<?php echo $item['image'] ? $item['image'] : 'assets/images/no-image.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                 style="width: 100px; height: 100px; object-fit: cover; border-radius: 10px;">
                            
                            <div style="flex: 1;">
                                <h4 style="margin-bottom: 0.5rem; color: var(--dark-color);">
                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                </h4>
                                <p style="color: var(--text-light); margin-bottom: 0.3rem;">
                                    <i class="fas fa-times"></i> Số lượng: <strong><?php echo $item['quantity']; ?></strong>
                                </p>
                                <p style="color: var(--primary-color); font-weight: 700; font-size: 1.1rem;">
                                    <?php echo formatCurrency($item['price']); ?> × <?php echo $item['quantity']; ?>
                                </p>
                                <?php if ($item['notes']): ?>
                                    <p style="color: var(--text-light); font-size: 0.9rem; margin-top: 0.5rem; font-style: italic;">
                                        <i class="fas fa-sticky-note"></i> Ghi chú: <?php echo htmlspecialchars($item['notes']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            
                            <div style="text-align: right;">
                                <p style="font-size: 1.3rem; font-weight: 700; color: var(--primary-color);">
                                    <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Delivery Information -->
                <div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-truck"></i> Thông tin giao hàng
                    </h3>
                    <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 10px;">
                        <p style="margin: 0.5rem 0;"><i class="fas fa-phone"></i> <strong>SĐT:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <p style="margin: 0.5rem 0;"><i class="fas fa-map-marker-alt"></i> <strong>Địa chỉ:</strong></p>
                        <p style="margin: 0.5rem 0 0.5rem 1.5rem; color: var(--text-dark);">
                            <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                        </p>
                        <?php if ($order['notes']): ?>
                            <p style="margin: 0.8rem 0 0.3rem;"><i class="fas fa-sticky-note"></i> <strong>Ghi chú:</strong></p>
                            <p style="margin: 0.3rem 0 0 1.5rem; color: var(--text-dark); font-style: italic;">
                                <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div>
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-receipt"></i> Thanh toán
                    </h3>
                    <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 10px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem; padding-bottom: 0.8rem; border-bottom: 1px solid #ddd;">
                            <span>Tạm tính:</span>
                            <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.8rem; padding-bottom: 0.8rem; border-bottom: 1px solid #ddd;">
                            <span>Phí vận chuyển:</span>
                            <strong style="color: var(--success);">Miễn phí</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1.3rem; padding-top: 0.5rem;">
                            <span><strong>Tổng cộng:</strong></span>
                            <strong style="color: var(--primary-color);"><?php echo formatCurrency($order['total_amount']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 2px solid var(--light-bg);">
                <?php if ($order['status'] == 'pending'): ?>
                    <button onclick="if(confirm('Bạn có chắc muốn hủy đơn hàng này?')) { window.location.href='cancel-order.php?id=<?php echo $order_id; ?>'; }" 
                            class="btn btn-danger" style="margin-right: 1rem;">
                        <i class="fas fa-times-circle"></i> Hủy đơn hàng
                    </button>
                <?php endif; ?>
                
                <?php if ($order['status'] == 'completed'): ?>
                    <a href="menu.php" class="btn btn-success">
                        <i class="fas fa-redo"></i> Đặt lại đơn hàng này
                    </a>
                <?php endif; ?>
                
                <a href="order-history.php" class="btn btn-outline" style="border: 2px solid var(--primary-color); color: var(--primary-color);">
                    <i class="fas fa-history"></i> Xem tất cả đơn hàng
                </a>
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
</body>
</html>