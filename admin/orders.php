<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $order_id = (int)$_POST['order_id'];
        $new_status = sanitize($_POST['status']);
        
        $allowed_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
        if (in_array($new_status, $allowed_statuses)) {
            $update_query = "UPDATE orders SET status = '$new_status' WHERE order_id = $order_id";
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['success'] = 'Cập nhật trạng thái đơn hàng thành công!';
            } else {
                $_SESSION['error'] = 'Cập nhật thất bại!';
            }
        }
        
        header("Location: orders.php");
        exit();
    }
}

// Filter
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$orders_query = "SELECT o.*, u.full_name, u.email, u.phone as user_phone 
                 FROM orders o 
                 JOIN users u ON o.user_id = u.user_id 
                 WHERE 1=1";

if ($status_filter != 'all') {
    $orders_query .= " AND o.status = '$status_filter'";
}

if (!empty($search)) {
    $orders_query .= " AND (o.order_id LIKE '%$search%' OR u.full_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$orders_query .= " ORDER BY o.order_date DESC";
$orders = mysqli_query($conn, $orders_query);

// Get order counts by status
$counts_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$counts_result = mysqli_query($conn, $counts_query);
$status_counts = [];
while ($count = mysqli_fetch_assoc($counts_result)) {
    $status_counts[$count['status']] = $count['count'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đơn hàng - Starbucks Admin</title>
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
                <li><a href="orders.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-shopping-bag"></i> Đơn hàng</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Báo cáo</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Thoát</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-shopping-bag"></i> Quản lý đơn hàng
        </h1>

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

        <!-- Status Filter Tabs -->
        <div style="background: var(--white); padding: 1.5rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-bottom: 1.5rem;">
                <a href="?status=all" class="btn <?php echo $status_filter == 'all' ? 'btn-primary' : 'btn-outline'; ?>" 
                   style="<?php echo $status_filter != 'all' ? 'border: 2px solid var(--primary-color); color: var(--primary-color);' : ''; ?>">
                    <i class="fas fa-list"></i> Tất cả 
                    (<?php echo mysqli_num_rows($orders); ?>)
                </a>
                <a href="?status=pending" class="btn <?php echo $status_filter == 'pending' ? 'btn-warning' : 'btn-outline'; ?>" 
                   style="<?php echo $status_filter != 'pending' ? 'border: 2px solid var(--warning); color: var(--text-dark);' : 'color: var(--text-dark);'; ?>">
                    <i class="fas fa-clock"></i> Chờ xác nhận 
                    (<?php echo $status_counts['pending'] ?? 0; ?>)
                </a>
                <a href="?status=confirmed" class="btn <?php echo $status_filter == 'confirmed' ? 'btn-primary' : 'btn-outline'; ?>" 
                   style="<?php echo $status_filter != 'confirmed' ? 'border: 2px solid var(--info); color: var(--info);' : ''; ?>">
                    <i class="fas fa-check"></i> Đã xác nhận 
                    (<?php echo $status_counts['confirmed'] ?? 0; ?>)
                </a>
                <a href="?status=completed" class="btn <?php echo $status_filter == 'completed' ? 'btn-success' : 'btn-outline'; ?>" 
                   style="<?php echo $status_filter != 'completed' ? 'border: 2px solid var(--success); color: var(--success);' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Hoàn thành 
                    (<?php echo $status_counts['completed'] ?? 0; ?>)
                </a>
                <a href="?status=cancelled" class="btn <?php echo $status_filter == 'cancelled' ? 'btn-danger' : 'btn-outline'; ?>" 
                   style="<?php echo $status_filter != 'cancelled' ? 'border: 2px solid var(--danger); color: var(--danger);' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Đã hủy 
                    (<?php echo $status_counts['cancelled'] ?? 0; ?>)
                </a>
            </div>

            <!-- Search -->
            <form method="GET" action="" style="display: flex; gap: 1rem;">
                <?php if ($status_filter != 'all'): ?>
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                <?php endif; ?>
                <input type="text" name="search" class="form-control" 
                       placeholder="Tìm theo mã đơn hàng, tên hoặc email khách hàng..." 
                       value="<?php echo htmlspecialchars($search); ?>" 
                       style="flex: 1;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
                <?php if (!empty($search)): ?>
                    <a href="orders.php?status=<?php echo $status_filter; ?>" class="btn btn-danger">
                        <i class="fas fa-times"></i> Xóa
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Khách hàng</th>
                        <th>Tổng tiền</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($orders) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                <td>
                                    <div><strong><?php echo htmlspecialchars($order['full_name']); ?></strong></div>
                                    <div style="font-size: 0.85rem; color: var(--text-light);">
                                        <?php echo htmlspecialchars($order['email']); ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--text-light);">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['phone']); ?>
                                    </div>
                                </td>
                                <td><strong><?php echo formatCurrency($order['total_amount']); ?></strong></td>
                                <td>
                                    <div><?php echo getPaymentStatusBadge($order['payment_status']); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-light); margin-top: 0.3rem;">
                                        <?php echo $order['payment_method'] == 'cod' ? 'COD' : 'Online'; ?>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" action="" style="max-width: 150px;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="status" class="form-control" style="padding: 0.4rem; font-size: 0.85rem;" 
                                                onchange="if(confirm('Xác nhận thay đổi trạng thái?')) this.form.submit();"
                                                <?php echo ($order['status'] == 'cancelled' || $order['status'] == 'completed') ? 'disabled' : ''; ?>>
                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                            <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Hoàn thành</option>
                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                        </select>
                                        <?php if ($order['status'] == 'cancelled' || $order['status'] == 'completed'): ?>
                                            <small style="color: var(--text-light); font-size: 0.75rem; display: block; margin-top: 0.3rem;">
                                                Không thể thay đổi
                                            </small>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td>
                                    <div><?php echo date('d/m/Y', strtotime($order['order_date'])); ?></div>
                                    <div style="font-size: 0.85rem; color: var(--text-light);">
                                        <?php echo date('H:i', strtotime($order['order_date'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="order-detail.php?id=<?php echo $order['order_id']; ?>" 
                                       class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                        <i class="fas fa-eye"></i> Chi tiết
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-light);">
                                <i class="fas fa-inbox"></i> Không có đơn hàng nào
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
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