<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

// Get order with customer info
$order_query = "SELECT o.*, u.full_name, u.email, u.username, u.phone as user_phone
                FROM orders o
                JOIN users u ON o.user_id = u.user_id
                WHERE o.order_id = $order_id";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: orders.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT oi.*, p.product_name, p.image, p.price as current_price
                FROM order_items oi
                JOIN products p ON oi.product_id = p.product_id
                WHERE oi.order_id = $order_id";
$items = mysqli_query($conn, $items_query);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = sanitize($_POST['status']);
    $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
    
    // KIỂM TRA: Không cho phép thay đổi nếu đã hủy hoặc hoàn thành
    if ($order['status'] == 'cancelled' || $order['status'] == 'completed') {
        $_SESSION['error'] = 'Không thể thay đổi trạng thái đơn hàng đã ' . 
                            ($order['status'] == 'cancelled' ? 'hủy' : 'hoàn thành') . '!';
        header("Location: order-detail.php?id=$order_id");
        exit();
    }
    
    if (in_array($new_status, $allowed)) {
        $update = "UPDATE orders SET status = '$new_status' WHERE order_id = $order_id";
        if (mysqli_query($conn, $update)) {
            $_SESSION['success'] = 'Cập nhật trạng thái thành công!';
            header("Location: order-detail.php?id=$order_id");
            exit();
        }
    }
}

// Handle payment status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_payment'])) {
    $new_payment_status = sanitize($_POST['payment_status']);
    $allowed_payment = ['pending', 'paid', 'failed'];
    
    // KIỂM TRA: Không cho phép thay đổi nếu đơn đã hủy
    if ($order['status'] == 'cancelled') {
        $_SESSION['error'] = 'Không thể thay đổi trạng thái thanh toán của đơn hàng đã hủy!';
        header("Location: order-detail.php?id=$order_id");
        exit();
    }
    
    if (in_array($new_payment_status, $allowed_payment)) {
        $update = "UPDATE orders SET payment_status = '$new_payment_status' WHERE order_id = $order_id";
        if (mysqli_query($conn, $update)) {
            $_SESSION['success'] = 'Cập nhật trạng thái thanh toán thành công!';
            header("Location: order-detail.php?id=$order_id");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_id; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-coffee"></i> <span>Starbucks</span>
            </a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Đơn hàng</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Báo cáo</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Thoát</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <div style="margin-bottom: 2rem;">
            <a href="orders.php" class="btn btn-outline" style="border: 2px solid var(--primary-color); color: var(--primary-color);">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
            <!-- Main Order Info -->
            <div>
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 2rem;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 2px solid var(--light-bg);">
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
                        </div>
                    </div>

                    <!-- Customer Info -->
                    <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-user"></i> Thông tin khách hàng
                        </h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p style="margin: 0.5rem 0;"><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['full_name']); ?></p>
                                <p style="margin: 0.5rem 0;"><strong>Username:</strong> <?php echo htmlspecialchars($order['username']); ?></p>
                            </div>
                            <div>
                                <p style="margin: 0.5rem 0;"><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                <p style="margin: 0.5rem 0;"><strong>SĐT:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-shopping-bag"></i> Sản phẩm
                    </h3>
                    <?php while ($item = mysqli_fetch_assoc($items)): ?>
                        <div style="display: flex; gap: 1rem; padding: 1rem; border: 2px solid var(--light-bg); border-radius: 10px; margin-bottom: 1rem;">
                            <img src="../<?php echo $item['image'] ? $item['image'] : 'assets/images/no-image.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                            
                            <div style="flex: 1;">
                                <h4 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <p style="color: var(--text-light); font-size: 0.9rem;">
                                    Size: <strong><?php echo ucfirst($item['size']); ?></strong> × <?php echo $item['quantity']; ?>
                                </p>
                                <p style="color: var(--primary-color); font-weight: 700;">
                                    <?php echo formatCurrency($item['price']); ?> × <?php echo $item['quantity']; ?>
                                </p>
                            </div>
                            
                            <div style="text-align: right;">
                                <p style="font-size: 1.2rem; font-weight: 700; color: var(--primary-color);">
                                    <?php echo formatCurrency($item['price'] * $item['quantity']); ?>
                                </p>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <!-- Delivery Info -->
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid var(--light-bg);">
                        <h3 style="color: var(--primary-color); margin-bottom: 1rem;">
                            <i class="fas fa-truck"></i> Thông tin giao hàng
                        </h3>
                        <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 10px;">
                            <p style="margin: 0.5rem 0;"><strong>Địa chỉ:</strong></p>
                            <p style="margin: 0.5rem 0 1rem 1rem;"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                            <?php if ($order['notes']): ?>
                                <p style="margin: 0.5rem 0;"><strong>Ghi chú:</strong></p>
                                <p style="margin: 0.5rem 0 0 1rem; font-style: italic;"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                <!-- Update Status -->
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 2rem;">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-edit"></i> Cập nhật trạng thái
                    </h3>
                    
                    <?php if ($order['status'] == 'cancelled' || $order['status'] == 'completed'): ?>
                        <!-- Hiển thị thông báo không thể thay đổi -->
                        <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <i class="fas fa-lock" style="font-size: 3rem; color: var(--text-light); margin-bottom: 1rem;"></i>
                            <h4 style="color: var(--text-dark); margin-bottom: 0.5rem;">Không thể thay đổi trạng thái</h4>
                            <p style="color: var(--text-light); margin-bottom: 1rem;">
                                Đơn hàng đã ở trạng thái 
                                <?php echo $order['status'] == 'cancelled' ? '"Đã hủy"' : '"Hoàn thành"'; ?>
                            </p>
                            <?php echo getOrderStatusBadge($order['status']); ?>
                            <p style="color: var(--text-light); font-size: 0.9rem; margin-top: 1rem;">
                                <?php if ($order['status'] == 'cancelled'): ?>
                                    Đơn hàng đã bị hủy và không thể khôi phục.
                                <?php else: ?>
                                    Đơn hàng đã hoàn thành và đã được ghi nhận vào doanh thu.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Form thay đổi trạng thái -->
                        <form method="POST" action="">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Trạng thái đơn hàng:</label>
                                <select name="status" class="form-control" required>
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                    <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                    <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                </select>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-save"></i> Cập nhật trạng thái
                            </button>
                        </form>
                        
                        <div style="margin-top: 1rem; padding: 1rem; background: var(--light-bg); border-radius: 8px;">
                            <p style="margin: 0; font-size: 0.85rem; color: var(--text-light);">
                                <i class="fas fa-info-circle"></i> <strong>Lưu ý:</strong>
                                Sau khi chuyển sang trạng thái "Hoàn thành" hoặc "Đã hủy", bạn sẽ không thể thay đổi lại.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Update Payment Status -->
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 2rem;">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-credit-card"></i> Trạng thái thanh toán
                    </h3>
                    
                    <?php if ($order['status'] == 'cancelled'): ?>
                        <!-- Hiển thị thông báo không thể thay đổi -->
                        <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 10px; text-align: center;">
                            <i class="fas fa-lock" style="font-size: 2.5rem; color: var(--text-light); margin-bottom: 1rem;"></i>
                            <p style="color: var(--text-light); margin-bottom: 1rem;">
                                Không thể thay đổi trạng thái thanh toán của đơn hàng đã hủy
                            </p>
                            <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                        </div>
                    <?php else: ?>
                        <!-- Form thay đổi trạng thái thanh toán -->
                        <form method="POST" action="">
                            <div class="form-group">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Trạng thái:</label>
                                <select name="payment_status" class="form-control" required>
                                    <option value="pending" <?php echo $order['payment_status'] == 'pending' ? 'selected' : ''; ?>>Chưa thanh toán</option>
                                    <option value="paid" <?php echo $order['payment_status'] == 'paid' ? 'selected' : ''; ?>>Đã thanh toán</option>
                                    <option value="failed" <?php echo $order['payment_status'] == 'failed' ? 'selected' : ''; ?>>Thanh toán thất bại</option>
                                </select>
                            </div>
                            <button type="submit" name="update_payment" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-save"></i> Cập nhật thanh toán
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Order Summary -->
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-receipt"></i> Tổng đơn hàng
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
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem; padding-bottom: 1rem; border-bottom: 2px solid #ddd;">
                            <span>Phương thức:</span>
                            <strong><?php echo $order['payment_method'] == 'cod' ? 'COD' : 'Online'; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1.3rem;">
                            <span><strong>Tổng cộng:</strong></span>
                            <strong style="color: var(--primary-color);"><?php echo formatCurrency($order['total_amount']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <p><i class="fas fa-coffee"></i> Starbucks Admin Panel</p>
            <p>&copy; 2024 Starbucks. All rights reserved.</p>
        </div>
    </div>
</body>
</html>