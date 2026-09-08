<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// Search
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$customers_query = "SELECT u.*, 
    COUNT(DISTINCT o.order_id) as total_orders,
    COALESCE(SUM(CASE WHEN o.status = 'completed' THEN o.total_amount ELSE 0 END), 0) as total_spent
    FROM users u
    LEFT JOIN orders o ON u.user_id = o.user_id
    WHERE u.role = 'customer'";

if (!empty($search)) {
    $customers_query .= " AND (u.full_name LIKE '%$search%' OR u.email LIKE '%$search%' OR u.username LIKE '%$search%')";
}

$customers_query .= " GROUP BY u.user_id ORDER BY u.created_at DESC";
$customers = mysqli_query($conn, $customers_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý khách hàng - Starbucks Admin</title>
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
                <li><a href="customers.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Báo cáo</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-users"></i> Quản lý khách hàng
        </h1>

        <!-- Search -->
        <div style="background: var(--white); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
            <form method="GET" action="" style="display: flex; gap: 1rem;">
                <input type="text" name="search" class="form-control" 
                       placeholder="Tìm theo tên, email hoặc username..." 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       style="flex: 1;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
                <?php if (!empty($search)): ?>
                    <a href="customers.php" class="btn btn-danger">
                        <i class="fas fa-times"></i> Xóa
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Customers Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thông tin khách hàng</th>
                        <th>Username</th>
                        <th>Số đơn hàng</th>
                        <th>Tổng chi tiêu</th>
                        <th>Ngày đăng ký</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($customers) > 0): ?>
                        <?php while ($customer = mysqli_fetch_assoc($customers)): ?>
                            <tr>
                                <td><strong>#<?php echo $customer['user_id']; ?></strong></td>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($customer['full_name']); ?></strong></div>
                                    <div style="font-size: 0.85rem; color: var(--text-light);">
                                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($customer['email']); ?>
                                    </div>
                                    <?php if ($customer['phone']): ?>
                                        <div style="font-size: 0.85rem; color: var(--text-light);">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($customer['phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($customer['username']); ?></td>
                                <td>
                                    <span class="badge <?php echo $customer['total_orders'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $customer['total_orders']; ?> đơn
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: var(--primary-color);">
                                        <?php echo formatCurrency($customer['total_spent']); ?>
                                    </strong>
                                </td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($customer['created_at'])); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-light);">
                                        <?php echo date('H:i', strtotime($customer['created_at'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="customer-detail.php?id=<?php echo $customer['user_id']; ?>" 
                                       class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-light);">
                                <i class="fas fa-users-slash"></i> Không tìm thấy khách hàng nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Summary Statistics -->
        <?php if (mysqli_num_rows($customers) > 0): ?>
            <?php
            mysqli_data_seek($customers, 0);
            $total_customers = mysqli_num_rows($customers);
            $total_revenue = 0;
            $total_orders_count = 0;
            
            while ($c = mysqli_fetch_assoc($customers)) {
                $total_revenue += $c['total_spent'];
                $total_orders_count += $c['total_orders'];
            }
            mysqli_data_seek($customers, 0);
            ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 2rem; margin-top: 3rem;">
                <div class="stat-card">
                    <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <h3><?php echo $total_customers; ?></h3>
                    <p>Tổng khách hàng</p>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #17a2b8, #0c5460);">
                    <i class="fas fa-shopping-bag" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <h3><?php echo $total_orders_count; ?></h3>
                    <p>Tổng đơn hàng</p>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #28a745, #155724);">
                    <i class="fas fa-dollar-sign" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <h3><?php echo formatCurrency($total_revenue); ?></h3>
                    <p>Tổng doanh thu</p>
                </div>
                
                <div class="stat-card" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                    <i class="fas fa-chart-line" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <h3><?php echo $total_customers > 0 ? formatCurrency($total_revenue / $total_customers) : '0 ₫'; ?></h3>
                    <p>Chi tiêu trung bình</p>
                </div>
            </div>
        <?php endif; ?>
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