<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// Get statistics with profit calculation
// ✅ FIXED: Chỉ tính doanh thu khi status = 'completed' AND payment_status = 'paid'
$stats_query = "SELECT 
    (SELECT COUNT(*) FROM products WHERE is_available = 1) as total_products,
    (SELECT COUNT(*) FROM orders WHERE status != 'cancelled') as total_orders,
    (SELECT COUNT(*) FROM users WHERE role = 'customer') as total_customers,
    (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'completed' AND payment_status = 'paid') as total_revenue";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Calculate material costs and profit
// ✅ FIXED: Chỉ tính lợi nhuận từ đơn đã hoàn thành VÀ đã thanh toán
$profit_query = "SELECT 
    COALESCE(SUM(o.total_amount), 0) as revenue,
    COALESCE(SUM(oi.quantity * p.material_cost), 0) as material_costs,
    COALESCE(SUM(o.total_amount) - SUM(oi.quantity * p.material_cost), 0) as profit
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.status = 'completed' AND o.payment_status = 'paid'";
$profit_result = mysqli_query($conn, $profit_query);
$profit_stats = mysqli_fetch_assoc($profit_result);

// Get pending orders
$pending_orders_query = "SELECT COUNT(*) as pending FROM orders WHERE status = 'pending'";
$pending_result = mysqli_query($conn, $pending_orders_query);
$pending = mysqli_fetch_assoc($pending_result);

// Get recent orders
$recent_orders_query = "SELECT o.*, u.full_name, u.email 
                        FROM orders o 
                        JOIN users u ON o.user_id = u.user_id 
                        ORDER BY o.order_date DESC 
                        LIMIT 10";
$recent_orders = mysqli_query($conn, $recent_orders_query);

// Get low stock products
$low_stock_query = "SELECT * FROM products WHERE stock < 10 AND is_available = 1 ORDER BY stock ASC LIMIT 5";
$low_stock = mysqli_query($conn, $low_stock_query);

// Profit margin
$profit_margin = $profit_stats['revenue'] > 0 ? ($profit_stats['profit'] / $profit_stats['revenue'] * 100) : 0;

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Starbucks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar-desktop.css">
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
                <li><a href="dashboard.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Đơn hàng</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Báo cáo</a></li>
                <li><a href="../customer/index.php"><i class="fas fa-store"></i> Xem trang</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h1>

        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-box" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo $stats['total_products']; ?></h3>
                <p>Sản phẩm đang bán</p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8, #0c5460);">
                <i class="fas fa-shopping-bag" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo $stats['total_orders']; ?></h3>
                <p>Tổng đơn hàng đã hoàn thành</p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                <i class="fas fa-clock" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo $pending['pending']; ?></h3>
                <p>Đơn hàng chờ xử lý</p>
            </div>

            <div class="stat-card" style="background: linear-gradient(135deg, #20c997, #17a674);">
                <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo $stats['total_customers']; ?></h3>
                <p>Khách hàng</p>
            </div>
        </div>

        <!-- Profit Details Card -->
        <div style="background: linear-gradient(135deg, #6f42c1, #4e2a84); color: white; padding: 2rem; border-radius: 15px; margin-bottom: 3rem; box-shadow: 0 10px 30px rgba(111, 66, 193, 0.3);">
            <h2 style="color: white; margin-bottom: 1.5rem; text-align: center;">
                <i class="fas fa-chart-pie"></i> Phân tích lợi nhuận
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 10px;">
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">💰 Tổng doanh thu</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?php echo formatCurrency($profit_stats['revenue']); ?></div>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 10px;">
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">📦 Chi phí nguyên liệu</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?php echo formatCurrency($profit_stats['material_costs']); ?></div>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 10px;">
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">📈 Lợi nhuận ròng</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?php echo formatCurrency($profit_stats['profit']); ?></div>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 1.5rem; border-radius: 10px;">
                    <div style="font-size: 0.9rem; opacity: 0.9; margin-bottom: 0.5rem;">📊 Biên lợi nhuận</div>
                    <div style="font-size: 1.5rem; font-weight: 700;"><?php echo number_format($profit_margin, 1); ?>%</div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 3rem;">
            <!-- Recent Orders -->
            <div>
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i class="fas fa-shopping-bag"></i> Đơn hàng gần đây
                </h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã ĐH</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th>Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                                <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
                                        <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                        <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                                        <td>
                                            <?php echo getOrderStatusBadge($order['status']); ?>
                                            <div style="font-size: 0.8rem; margin-top: 0.3rem;">
                                                <?php echo getPaymentStatusBadge($order['payment_status']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                        <td>
                                            <a href="order-detail.php?id=<?php echo $order['order_id']; ?>" 
                                               class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                                <i class="fas fa-eye"></i> Xem
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-light);">
                                        <i class="fas fa-inbox"></i> Chưa có đơn hàng nào
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                        <div style="text-align: center; margin-top: 1.5rem;">
                            <a href="orders.php" class="btn btn-outline" 
                               style="color: var(--primary-color); border-color: var(--primary-color);">
                                Xem tất cả đơn hàng <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Low Stock & Quick Actions -->
            <div>
                <!-- Low Stock Alert -->
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-triangle"></i> Sản phẩm sắp hết
                </h2>
                <div style="background: var(--white); padding: 1.5rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 2rem;">
                    <?php if (mysqli_num_rows($low_stock) > 0): ?>
                        <?php while ($product = mysqli_fetch_assoc($low_stock)): ?>
                            <div style="padding: 1rem; border-bottom: 1px solid #e0e0e0; display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    <div style="color: var(--danger); font-size: 0.9rem; margin-top: 0.3rem;">
                                        <i class="fas fa-box"></i> Còn: <?php echo $product['stock']; ?>
                                    </div>
                                </div>
                                <a href="products.php?edit=<?php echo $product['product_id']; ?>" 
                                   class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: var(--success); padding: 1rem;">
                            <i class="fas fa-check-circle"></i> Tất cả sản phẩm đều đủ hàng
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Thêm vào phần dashboard stats -->
                <div class="stat-card" style="background: linear-gradient(135deg, #6f42c1, #4e2a84);">
                    <a href="payment-settings.php" style="color: white; text-decoration: none;">
                        <i class="fas fa-qrcode" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                        <h3>Cài đặt QR</h3>
                        <p>Quản lý mã QR thanh toán</p>
                    </a>
                </div>
                <!-- Quick Actions -->
                <br>
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i class="fas fa-bolt"></i> Thao tác nhanh
                </h2>
                <div style="background: var(--white); padding: 1.5rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                    <a href="products.php?action=add" class="btn btn-success" style="width: 100%; margin-bottom: 1rem;">
                        <i class="fas fa-plus-circle"></i> Thêm sản phẩm mới
                    </a>
                    <a href="orders.php?status=pending" class="btn btn-warning" style="width: 100%; margin-bottom: 1rem; color: var(--text-dark);">
                        <i class="fas fa-clock"></i> Xem đơn chờ xử lý
                    </a>
                    <a href="reports.php" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-chart-bar"></i> Xem báo cáo chi tiết
                    </a>
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