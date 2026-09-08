<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_info = getUserInfo($_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

// Get user's orders
$orders_query = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC";
$orders = mysqli_query($conn, $orders_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch sử đơn hàng - Starbucks</title>
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
                <li><a href="order-history.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-history"></i> Đơn hàng</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_info['full_name']); ?></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="text-align: center; color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-history"></i> Lịch sử đơn hàng
        </h1>

        <?php if (mysqli_num_rows($orders) > 0): ?>
            <div style="display: grid; gap: 2rem;">
                <?php while ($order = mysqli_fetch_assoc($orders)): 
                    // Get order items
                    $order_id = $order['order_id'];
                    $items_query = "SELECT oi.*, p.product_name, p.image 
                                   FROM order_items oi 
                                   JOIN products p ON oi.product_id = p.product_id 
                                   WHERE oi.order_id = $order_id";
                    $items = mysqli_query($conn, $items_query);
                ?>
                    <div style="background: var(--white); border-radius: 15px; padding: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                        <!-- Order Header -->
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 2px solid var(--light-bg);">
                            <div>
                                <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                                    <i class="fas fa-shopping-bag"></i> Đơn hàng #<?php echo $order['order_id']; ?>
                                </h3>
                                <p style="color: var(--text-light); font-size: 0.9rem;">
                                    <i class="fas fa-calendar"></i> 
                                    <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <?php echo getOrderStatusBadge($order['status']); ?>
                                <div style="margin-top: 0.5rem;">
                                    <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                                </div>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <div style="margin-bottom: 1.5rem;">
                            <?php while ($item = mysqli_fetch_assoc($items)): ?>
                                <div style="display: flex; gap: 1rem; padding: 1rem 0; border-bottom: 1px solid #e0e0e0;">
                                    <img src="../<?php echo $item['image'] ? $item['image'] : 'assets/images/no-image.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         style="width: 80px; height: 80px; object-fit: cover; border-radius: 10px;">
                                    
                                    <div style="flex: 1;">
                                        <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                        <p style="color: var(--text-light); font-size: 0.9rem;">
                                            Số lượng: <strong><?php echo $item['quantity']; ?></strong>
                                        </p>
                                        <?php if ($item['notes']): ?>
                                            <p style="color: var(--text-light); font-size: 0.85rem; font-style: italic; margin-top: 0.3rem;">
                                                <i class="fas fa-sticky-note"></i> <?php echo htmlspecialchars($item['notes']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div style="text-align: right;">
                                        <p style="font-weight: 700; font-size: 1.1rem; color: var(--primary-color);">
                                            <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                                        </p>
                                        <p style="color: var(--text-light); font-size: 0.85rem;">
                                            <?php echo formatCurrency($item['price']); ?> / sp
                                        </p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <!-- Order Details -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                            <div>
                                <h4 style="color: var(--primary-color); margin-bottom: 0.8rem;">
                                    <i class="fas fa-map-marker-alt"></i> Thông tin giao hàng
                                </h4>
                                <p style="margin: 0.3rem 0; color: var(--text-light);">
                                    <i class="fas fa-phone"></i> <strong>SĐT:</strong> <?php echo htmlspecialchars($order['phone']); ?>
                                </p>
                                <p style="margin: 0.3rem 0; color: var(--text-light);">
                                    <i class="fas fa-map-marker-alt"></i> <strong>Địa chỉ:</strong><br>
                                    <?php echo htmlspecialchars($order['delivery_address']); ?>
                                </p>
                                <?php if ($order['notes']): ?>
                                    <p style="margin: 0.8rem 0 0.3rem; color: var(--text-light);">
                                        <i class="fas fa-sticky-note"></i> <strong>Ghi chú:</strong><br>
                                        <?php echo htmlspecialchars($order['notes']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div>
                                <h4 style="color: var(--primary-color); margin-bottom: 0.8rem;">
                                    <i class="fas fa-receipt"></i> Chi tiết thanh toán
                                </h4>
                                <div style="background: var(--light-bg); padding: 1rem; border-radius: 10px;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span>Phương thức:</span>
                                        <strong><?php echo $order['payment_method'] == 'cod' ? 'COD' : 'Online'; ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span>Tạm tính:</span>
                                        <strong><?php echo formatCurrency($order['total_amount']); ?></strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                        <span>Phí vận chuyển:</span>
                                        <strong style="color: var(--success);">Miễn phí</strong>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; padding-top: 0.5rem; border-top: 2px solid var(--white); font-size: 1.2rem;">
                                        <span><strong>Tổng cộng:</strong></span>
                                        <strong style="color: var(--primary-color);">
                                            <?php echo formatCurrency($order['total_amount']); ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <?php if ($order['status'] == 'pending'): ?>
                            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e0e0e0;">
                                <button onclick="if(confirm('Bạn có chắc muốn hủy đơn hàng này?')) { window.location.href='cancel-order.php?id=<?php echo $order['order_id']; ?>'; }" 
                                        class="btn btn-danger">
                                    <i class="fas fa-times-circle"></i> Hủy đơn hàng
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php if ($order['status'] == 'completed'): ?>
                            <div style="text-align: center; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid #e0e0e0;">
                                <a href="menu.php" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Đặt lại đơn hàng này
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem 2rem; background: var(--white); border-radius: 15px;">
                <i class="fas fa-shopping-bag" style="font-size: 5rem; color: var(--text-light); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-dark); margin-bottom: 1rem;">Chưa có đơn hàng nào</h3>
                <p style="color: var(--text-light); margin-bottom: 2rem;">
                    Bạn chưa đặt hàng lần nào. Hãy khám phá menu của chúng tôi!
                </p>
                <a href="menu.php" class="btn btn-primary">
                    <i class="fas fa-coffee"></i> Xem menu
                </a>
            </div>
        <?php endif; ?>
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