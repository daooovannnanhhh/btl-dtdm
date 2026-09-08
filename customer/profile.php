<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_info = getUserInfo($_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $address = sanitize($_POST['address']);
        
        // Check if email exists for other users
        $check_email = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id";
        $result = mysqli_query($conn, $check_email);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Email đã được sử dụng bởi tài khoản khác!';
        } else {
            $update_query = "UPDATE users SET 
                           full_name = '$full_name',
                           email = '$email',
                           phone = '$phone',
                           address = '$address'
                           WHERE user_id = $user_id";
            
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['full_name'] = $full_name;
                $success = 'Cập nhật thông tin thành công!';
                $user_info = getUserInfo($user_id); // Refresh user info
            } else {
                $error = 'Cập nhật thất bại! Vui lòng thử lại.';
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] == 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $user_info['password'])) {
            $error = 'Mật khẩu hiện tại không đúng!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Mật khẩu mới không khớp!';
        } elseif (strlen($new_password) < 6) {
            $error = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE users SET password = '$hashed_password' WHERE user_id = $user_id";
            
            if (mysqli_query($conn, $update_query)) {
                $success = 'Đổi mật khẩu thành công!';
            } else {
                $error = 'Đổi mật khẩu thất bại! Vui lòng thử lại.';
            }
        }
    }
}

// Get order statistics
$stats_query = "SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END), 0) as total_spent
    FROM orders WHERE user_id = $user_id";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - Starbucks</title>
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
                <li><a href="profile.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_info['full_name']); ?></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="text-align: center; color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-user-circle"></i> Thông tin cá nhân
        </h1>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 2rem;">
            <!-- Sidebar -->
            <div>
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); text-align: center;">
                    <div style="width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-color), var(--dark-color)); margin: 0 auto 1.5rem; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 3rem;">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 style="color: var(--primary-color); margin-bottom: 0.5rem;">
                        <?php echo htmlspecialchars($user_info['full_name']); ?>
                    </h3>
                    <p style="color: var(--text-light); margin-bottom: 0.3rem;">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_info['email']); ?>
                    </p>
                    <p style="color: var(--text-light); margin-bottom: 1.5rem;">
                        <i class="fas fa-calendar"></i> Tham gia: <?php echo date('d/m/Y', strtotime($user_info['created_at'])); ?>
                    </p>
                    
                    <div style="background: var(--light-bg); padding: 1.5rem; border-radius: 10px; margin-top: 1.5rem;">
                        <h4 style="color: var(--primary-color); margin-bottom: 1rem;">Thống kê</h4>
                        <div style="display: flex; justify-content: space-around; text-align: center;">
                            <div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-color);">
                                    <?php echo $stats['total_orders']; ?>
                                </div>
                                <div style="font-size: 1rem; color: var(--text-light);">Đơn hàng</div>
                            </div>
                            <div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);">
                                    <?php echo formatCurrency($stats['total_spent']); ?>
                                </div>
                                <div style="font-size: 1rem; color: var(--text-light);">Đã chi tiêu</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div>
                <!-- Update Profile Form -->
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08); margin-bottom: 2rem;">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-edit"></i> Chỉnh sửa thông tin
                    </h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="username">
                                <i class="fas fa-user"></i> Tên đăng nhập
                            </label>
                            <input type="text" id="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_info['username']); ?>" 
                                   disabled style="background-color: #f5f5f5; cursor: not-allowed;">
                            <small style="color: var(--text-light);">Tên đăng nhập không thể thay đổi</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">
                                <i class="fas fa-id-card"></i> Họ và tên <span style="color: red;">*</span>
                            </label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_info['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email <span style="color: red;">*</span>
                            </label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_info['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">
                                <i class="fas fa-phone"></i> Số điện thoại
                            </label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user_info['phone']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">
                                <i class="fas fa-map-marker-alt"></i> Địa chỉ
                            </label>
                            <textarea id="address" name="address" class="form-control" 
                                      rows="3"><?php echo htmlspecialchars($user_info['address']); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-save"></i> Cập nhật thông tin
                        </button>
                    </form>
                </div>

                <!-- Change Password Form -->
                <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                    <h3 style="color: var(--primary-color); margin-bottom: 1.5rem;">
                        <i class="fas fa-key"></i> Đổi mật khẩu
                    </h3>
                    
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">
                                <i class="fas fa-lock"></i> Mật khẩu hiện tại <span style="color: red;">*</span>
                            </label>
                            <input type="password" id="current_password" name="current_password" 
                                   class="form-control" placeholder="Nhập mật khẩu hiện tại" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">
                                <i class="fas fa-lock"></i> Mật khẩu mới <span style="color: red;">*</span>
                            </label>
                            <input type="password" id="new_password" name="new_password" 
                                   class="form-control" placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Xác nhận mật khẩu mới <span style="color: red;">*</span>
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" 
                                   class="form-control" placeholder="Nhập lại mật khẩu mới" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </button>
                    </form>
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
</body>
</html>