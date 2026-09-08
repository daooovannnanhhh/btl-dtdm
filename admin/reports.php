<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// Date filter
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// Revenue, Cost & Profit by date
// ✅ FIXED: Chỉ tính doanh thu khi status = 'completed' AND payment_status = 'paid'
$revenue_query = "SELECT 
    DATE(o.order_date) as date,
    COUNT(DISTINCT o.order_id) as orders_count,
    SUM(o.total_amount) as revenue,
    SUM(oi.quantity * p.material_cost) as material_cost,
    SUM(o.total_amount) - SUM(oi.quantity * p.material_cost) as profit,
    ((SUM(o.total_amount) - SUM(oi.quantity * p.material_cost)) / SUM(o.total_amount) * 100) as profit_margin
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.status = 'completed' AND o.payment_status = 'paid'
    AND DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'
    GROUP BY DATE(o.order_date)
    ORDER BY date DESC";
$revenue_data = mysqli_query($conn, $revenue_query);

// Total statistics for period with profit
// ✅ FIXED: Chỉ tính từ đơn đã hoàn thành VÀ đã thanh toán
$total_stats_query = "SELECT 
    COUNT(DISTINCT o.order_id) as total_orders,
    COALESCE(SUM(o.total_amount), 0) as total_revenue,
    COALESCE(SUM(oi.quantity * p.material_cost), 0) as total_material_cost,
    COALESCE(SUM(o.total_amount) - SUM(oi.quantity * p.material_cost), 0) as total_profit,
    COALESCE(AVG(o.total_amount), 0) as avg_order_value
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.product_id = p.product_id
    WHERE o.status = 'completed' AND o.payment_status = 'paid'
    AND DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'";
$total_stats = mysqli_fetch_assoc(mysqli_query($conn, $total_stats_query));

$profit_margin = $total_stats['total_revenue'] > 0 ? 
    ($total_stats['total_profit'] / $total_stats['total_revenue'] * 100) : 0;

// Best selling products with profit
// ✅ FIXED: Chỉ tính từ đơn đã hoàn thành VÀ đã thanh toán
$best_products_query = "SELECT 
    p.product_name,
    p.price,
    p.material_cost,
    (p.price - p.material_cost) as profit_per_unit,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.price * oi.quantity) as total_revenue,
    SUM(oi.quantity * p.material_cost) as total_material_cost,
    SUM(oi.price * oi.quantity) - SUM(oi.quantity * p.material_cost) as total_profit
    FROM order_items oi
    JOIN products p ON oi.product_id = p.product_id
    JOIN orders o ON oi.order_id = o.order_id
    WHERE o.status = 'completed' AND o.payment_status = 'paid'
    AND DATE(o.order_date) BETWEEN '$from_date' AND '$to_date'
    GROUP BY oi.product_id
    ORDER BY total_profit DESC
    LIMIT 10";
$best_products = mysqli_query($conn, $best_products_query);

// Order status distribution
$status_query = "SELECT 
    status,
    COUNT(*) as count
    FROM orders 
    WHERE DATE(order_date) BETWEEN '$from_date' AND '$to_date'
    GROUP BY status";
$status_data = mysqli_query($conn, $status_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo cáo thống kê - Starbucks Admin</title>
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
                <li><a href="reports.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-chart-bar"></i> Báo cáo</a></li>
                <li><a href="../customer/index.php"><i class="fas fa-store"></i> Xem trang</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-chart-bar"></i> Báo cáo & Thống kê
        </h1>

        <!-- ⚠️ THÔNG BÁO LOGIC MỚI -->
        <div style="background: linear-gradient(135deg, #17a2b8, #0c5460); color: white; padding: 1.5rem; border-radius: 12px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(23, 162, 184, 0.3);">
            <p style="margin: 0; display: flex; align-items: center; gap: 0.8rem;">
                <i class="fas fa-info-circle" style="font-size: 1.5rem;"></i>
                <span><strong>Lưu ý:</strong> Báo cáo chỉ tính các đơn hàng đã <strong>Hoàn thành</strong> và <strong>Đã thanh toán</strong>.</span>
            </p>
        </div>

        <!-- Date Filter -->
        <div style="background: var(--white); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
            <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        <i class="fas fa-calendar"></i> Từ ngày:
                    </label>
                    <input type="date" name="from_date" class="form-control" 
                           value="<?php echo $from_date; ?>" required>
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">
                        <i class="fas fa-calendar"></i> Đến ngày:
                    </label>
                    <input type="date" name="to_date" class="form-control" 
                           value="<?php echo $to_date; ?>" required>
                </div>
                <div style="align-self: flex-end;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                </div>
                <div style="align-self: flex-end;">
                    <a href="reports.php" class="btn btn-danger">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Export Buttons -->
        <div style="background: var(--white); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
            <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">
                <i class="fas fa-file-export"></i> Xuất báo cáo Excel
            </h3>
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <a href="export-excel.php?type=revenue&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Xuất báo cáo doanh thu & lợi nhuận
                </a>
                <a href="export-excel.php?type=best_selling&from_date=<?php echo $from_date; ?>&to_date=<?php echo $to_date; ?>" 
                   class="btn btn-success">
                    <i class="fas fa-file-excel"></i> Xuất sản phẩm lợi nhuận cao
                </a>
            </div>
            <p style="margin-top: 1rem; color: var(--text-light); font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> Báo cáo xuất ra sẽ chỉ bao gồm các đơn hàng đã hoàn thành và đã thanh toán.
            </p>
        </div>

        <!-- Summary Statistics -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-shopping-bag" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo $total_stats['total_orders']; ?></h3>
                <p>Đơn hàng hoàn thành</p>
                <small style="font-size: 0.75rem; opacity: 0.9; margin-top: 0.3rem; display: block;">
                    (Đã thanh toán)
                </small>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #155724);">
                <i class="fas fa-dollar-sign" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo formatCurrency($total_stats['total_revenue']); ?></h3>
                <p>Tổng doanh thu</p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                <i class="fas fa-coins" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo formatCurrency($total_stats['total_material_cost']); ?></h3>
                <p>Chi phí nguyên liệu</p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #6f42c1, #4e2a84);">
                <i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo formatCurrency($total_stats['total_profit']); ?></h3>
                <p>Lợi nhuận ròng</p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8, #0c5460);">
                <i class="fas fa-percentage" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo number_format($profit_margin, 1); ?>%</h3>
                <p>Biên lợi nhuận</p>
            </div>
            
            <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                <i class="fas fa-chart-pie" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                <h3><?php echo formatCurrency($total_stats['avg_order_value']); ?></h3>
                <p>Giá trị đơn hàng TB</p>
            </div>
        </div>

        <!-- Profit Breakdown Chart -->
        <div style="background: linear-gradient(135deg, #6f42c1, #4e2a84); color: white; padding: 2.5rem; border-radius: 15px; margin: 3rem 0; box-shadow: 0 10px 30px rgba(111, 66, 193, 0.3);">
            <h2 style="color: white; margin-bottom: 2rem; text-align: center;">
                <i class="fas fa-chart-pie"></i> Phân tích chi tiết lợi nhuận
            </h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 2rem; margin-bottom: 2rem;">
                <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 12px;">
                    <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 0.8rem;">💰 Tổng doanh thu</div>
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo formatCurrency($total_stats['total_revenue']); ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.8;">100%</div>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 12px;">
                    <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 0.8rem;">📦 Chi phí nguyên liệu</div>
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo formatCurrency($total_stats['total_material_cost']); ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.8;">
                        <?php echo $total_stats['total_revenue'] > 0 ? number_format(($total_stats['total_material_cost'] / $total_stats['total_revenue'] * 100), 1) : 0; ?>%
                    </div>
                </div>
                <div style="text-align: center; background: rgba(255,255,255,0.1); padding: 2rem; border-radius: 12px;">
                    <div style="font-size: 1rem; opacity: 0.9; margin-bottom: 0.8rem;">📈 Lợi nhuận ròng</div>
                    <div style="font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem;"><?php echo formatCurrency($total_stats['total_profit']); ?></div>
                    <div style="font-size: 0.9rem; opacity: 0.8;"><?php echo number_format($profit_margin, 1); ?>%</div>
                </div>
            </div>
            
            <!-- Visual Progress Bar -->
            <div style="background: rgba(255,255,255,0.2); height: 40px; border-radius: 20px; overflow: hidden; position: relative;">
                <?php 
                $cost_percent = $total_stats['total_revenue'] > 0 ? ($total_stats['total_material_cost'] / $total_stats['total_revenue'] * 100) : 0;
                $profit_percent = $total_stats['total_revenue'] > 0 ? ($total_stats['total_profit'] / $total_stats['total_revenue'] * 100) : 0;
                ?>
                <div style="position: absolute; left: 0; top: 0; height: 100%; width: <?php echo $cost_percent; ?>%; background: #dc3545; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600;">
                    <?php if ($cost_percent > 15): ?>Chi phí: <?php echo number_format($cost_percent, 1); ?>%<?php endif; ?>
                </div>
                <div style="position: absolute; left: <?php echo $cost_percent; ?>%; top: 0; height: 100%; width: <?php echo $profit_percent; ?>%; background: #28a745; display: flex; align-items: center; justify-content: center; font-size: 0.85rem; font-weight: 600;">
                    <?php if ($profit_percent > 15): ?>Lợi nhuận: <?php echo number_format($profit_percent, 1); ?>%<?php endif; ?>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-top: 3rem;">
            <!-- Revenue by Date with Profit -->
            <div>
                <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                    <i class="fas fa-chart-line"></i> Doanh thu & Lợi nhuận theo ngày
                </h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Ngày</th>
                                <th>Số đơn</th>
                                <th>Doanh thu</th>
                                <th>Chi phí</th>
                                <th>Lợi nhuận</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($revenue_data) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($revenue_data)): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($row['date'])); ?></td>
                                        <td><span class="badge bg-info"><?php echo $row['orders_count']; ?></span></td>
                                        <td><strong><?php echo formatCurrency($row['revenue']); ?></strong></td>
                                        <td><strong style="color: var(--danger);"><?php echo formatCurrency($row['material_cost']); ?></strong></td>
                                        <td>
                                            <div><strong style="color: var(--success);"><?php echo formatCurrency($row['profit']); ?></strong></div>
                                            <div style="font-size: 0.85rem; color: var(--text-light);">
                                                (<?php echo number_format($row['profit_margin'], 1); ?>%)
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: var(--text-light);">
                                        <i class="fas fa-info-circle"></i> Không có dữ liệu đơn hàng đã hoàn thành và thanh toán trong khoảng thời gian này
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Best Selling Products WITH PROFIT -->
        <div style="margin-top: 3rem;">
            <h2 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                <i class="fas fa-star"></i> Sản phẩm lợi nhuận cao nhất (Top 10)
            </h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Hạng</th>
                            <th>Tên sản phẩm</th>
                            <th>SL bán</th>
                            <th>Doanh thu</th>
                            <th>Chi phí NL</th>
                            <th>Lợi nhuận</th>
                            <th>LN/SP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($best_products) > 0): ?>
                            <?php 
                            $rank = 1;
                            while ($product = mysqli_fetch_assoc($best_products)): 
                            ?>
                                <tr>
                                    <td>
                                        <?php if ($rank <= 3): ?>
                                            <span style="font-size: 1.5rem;">
                                                <?php 
                                                $medals = ['🥇', '🥈', '🥉'];
                                                echo $medals[$rank - 1];
                                                ?>
                                            </span>
                                        <?php else: ?>
                                            <strong><?php echo $rank; ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                    <td><span class="badge bg-info"><?php echo $product['total_quantity']; ?></span></td>
                                    <td><strong><?php echo formatCurrency($product['total_revenue']); ?></strong></td>
                                    <td><strong style="color: var(--danger);"><?php echo formatCurrency($product['total_material_cost']); ?></strong></td>
                                    <td><strong style="color: var(--success);"><?php echo formatCurrency($product['total_profit']); ?></strong></td>
                                    <td><?php echo formatCurrency($product['profit_per_unit']); ?></td>
                                </tr>
                            <?php 
                            $rank++;
                            endwhile; 
                            ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-light);">
                                    <i class="fas fa-info-circle"></i> Không có dữ liệu sản phẩm trong khoảng thời gian này
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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