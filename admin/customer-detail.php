<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: customers.php");
    exit();
}

$customer_id = (int)$_GET['id'];

// Get customer info
$customer_query = "SELECT * FROM users WHERE user_id = $customer_id AND role = 'customer'";
$customer_result = mysqli_query($conn, $customer_query);

if (mysqli_num_rows($customer_result) == 0) {
    header("Location: customers.php");
    exit();
}

$customer = mysqli_fetch_assoc($customer_result);

// Get customer statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_spent,
    COALESCE(AVG(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as avg_order,
    MAX(order_date) as last_order_date
    FROM orders WHERE user_id = $customer_id";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $stats_query));

// Get customer orders
$orders_query = "SELECT * FROM orders WHERE user_id = $customer_id ORDER BY order_date DESC";
$orders = mysqli_query($conn, $orders_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết khách hàng - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-coffee"></i> Star<span>bucks</span> Admin
            </a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Đơn hàng</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Báo cáo</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <div style="margin-bottom: 2rem;">
            <a href="customers.php" class="btn btn-outline" style="border: 2px solid var(--primary-color); color: var(--primary-color);">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- Customer Profile -->
            <div>
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); text-align: center;">
                    <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--dark-color)); margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    
                    <h2 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($customer['full_name']); ?>
                    </h2>
                    
                    <p style="color: var(--text-light); margin-bottom: 0.3rem;">
                        <i class="fas fa-at"></i> <?php echo htmlspecialchars($customer['username']); ?>
                    </p>
                    
                    <p style="color: var(--text-light); margin-bottom: 0.3rem;">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?>
                    </p>
                    
                    <?php if ($customer['phone']): ?>
                        <p style="color: var(--text-light); margin-bottom: 0.3rem;">
                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?>
                        </p>
                    <?php endif; ?>
                    
                    <p style="color: var(--text-light); margin-top: 1.5rem;">
                        <i class="fas fa-calendar"></i> Tham gia: <?php echo date('d/m/Y', strtotime($customer['created_at'])); ?>
                    </p>
                    
                    <?php if ($customer['address']): ?>
                        <div style="margin-top: 1.5rem; padding: 1rem; background: var(--light-bg); border-radius: 10px; text-align: left;">
                            <p style="font-weight: 600; margin-bottom: 0.5rem;">
                                <i class="fas fa-map-marker-alt"></i> Địa chỉ:
                            </p>
                            <p style="color: var(--text-light); font-size: 0.9rem;">
                                <?php echo nl2br(htmlspecialchars($customer['address'])); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Statistics -->
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-top: 2rem;">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem; text-align: center;">
                        <i class="fas fa-chart-bar"></i> Thống kê
                    </h3>
                    
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-color);">
                            <?php echo $stats['total_orders']; ?>
                        </div>
                        <div style="color: var(--text-light);">Tổng đơn hàng</div>
                    </div>
                    
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">
                            <?php echo formatCurrency($stats['total_spent']); ?>
                        </div>
                        <div style="color: var(--text-light);">Tổng chi tiêu</div>
                    </div>
                    
                    <div style="text-align: center; margin-bottom: 1.5rem;">
                        <div style="font-size: 1.3rem; font-weight: 700; color: var(--info);">
                            <?php echo formatCurrency($stats['avg_order']); ?>
                        </div>
                        <div style="color: var(--text-light);">Giá trị đơn TB</div>
                    </div>
                    
                    <?php if ($stats['last_order_date']): ?>
                        <div style="text-align: center; padding-top: 1rem; border-top: 1px solid #e0e0e0;">
                            <div style="color: var(--text-light); font-size: 0.9rem;">Đơn hàng gần nhất</div>
                            <div style="font-weight: 600; color: var(--text-dark); margin-top: 0.3rem;">
                                <?php echo date('d/m/Y', strtotime($stats['last_order_date'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Order History -->
            <div>
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                    <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-history"></i> Lịch sử đơn hàng
                    </h2>
                    
                    <?php if (mysqli_num_rows($orders) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Mã ĐH</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Hành động</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                                        <tr>
                                            <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                            <td>
                                                <div><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></div>
                                                <div style="font-size: 0.85rem; color: var(--text-light);">
                                                    <?php echo date('H:i', strtotime($order['order_date'])); ?>
                                                </div>
                                            </td>
                                            <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                                            <td><?php echo getOrderStatusBadge($order['status']); ?></td>
                                            <td>
                                                <a href="order-detail.php?id=<?php echo $order['order_id']; ?>" 
                                                   class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                                    <i class="fas fa-eye"></i> Xem
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 3rem; color: var(--text-light);">
                            <i class="fas fa-shopping-bag" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p>Khách hàng chưa có đơn hàng nào</p>
                        </div>
                    <?php endif; ?>
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