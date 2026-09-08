<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: ../customer/index.php");
    exit();
}

$error = '';
$success = '';
$valid_token = false;
$user_id = null;

// Check if token exists in URL
if (!isset($_GET['token'])) {
    header("Location: forgot-password.php");
    exit();
}

$token = sanitize($_GET['token']);

// Verify token
$query = "SELECT * FROM users WHERE reset_token = '$token' AND reset_token_expiry > NOW()";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 1) {
    $valid_token = true;
    $user = mysqli_fetch_assoc($result);
    $user_id = $user['user_id'];
} else {
    $error = 'Link đặt lại mật khẩu không hợp lệ hoặc đã hết hạn!';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $valid_token) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password) || empty($confirm_password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($new_password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } else {
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $update_query = "UPDATE users SET 
                        password = '$hashed_password',
                        reset_token = NULL,
                        reset_token_expiry = NULL
                        WHERE user_id = $user_id";
        
        if (mysqli_query($conn, $update_query)) {
            $success = true;
        } else {
            $error = 'Có lỗi xảy ra! Vui lòng thử lại.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - Starbucks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="navbar">
        <div class="container">
            <a href="../index.php" class="navbar-brand">
                <i class="fas fa-coffee"></i> Star<span>bucks</span>
            </a>
        </div>
    </div>

    <div class="container" style="min-height: 80vh; display: flex; align-items: center; justify-content: center;">
        <div class="form-container">
            <?php if ($success): ?>
                <div style="text-align: center;">
                    <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--success); margin: 0 auto 2rem; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 3rem;">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 style="color: var(--success); margin-bottom: 1rem;">
                        Đặt lại mật khẩu thành công!
                    </h2>
                    <p style="color: var(--text-light); margin-bottom: 2rem;">
                        Mật khẩu của bạn đã được cập nhật. Bạn có thể đăng nhập với mật khẩu mới.
                    </p>
                    <a href="login.php" class="btn btn-primary" style="display: inline-block;">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
                    </a>
                </div>
            <?php elseif ($valid_token): ?>
                <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                    <i class="fas fa-lock"></i> Đặt lại mật khẩu
                </h2>
                
                <p style="text-align: center; color: var(--text-light); margin-bottom: 2rem;">
                    Nhập mật khẩu mới cho tài khoản của bạn
                </p>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="new_password">
                            <i class="fas fa-lock"></i> Mật khẩu mới
                        </label>
                        <input type="password" id="new_password" name="new_password" class="form-control" 
                               placeholder="Nhập mật khẩu mới (tối thiểu 6 ký tự)" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">
                            <i class="fas fa-lock"></i> Xác nhận mật khẩu mới
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Nhập lại mật khẩu mới" required>
                    </div>
                    
                    <div style="background: var(--light-bg); padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                        <p style="margin: 0; font-size: 0.9rem; color: var(--text-light);">
                            <i class="fas fa-info-circle"></i> <strong>Lưu ý:</strong>
                        </p>
                        <ul style="margin: 0.5rem 0 0 1.5rem; font-size: 0.9rem; color: var(--text-light);">
                            <li>Mật khẩu phải có ít nhất 6 ký tự</li>
                            <li>Nên sử dụng kết hợp chữ hoa, chữ thường và số</li>
                            <li>Không sử dụng mật khẩu dễ đoán</li>
                        </ul>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        <i class="fas fa-save"></i> Đặt lại mật khẩu
                    </button>
                </form>
            <?php else: ?>
                <div style="text-align: center;">
                    <div style="width: 100px; height: 100px; border-radius: 50%; background: var(--danger); margin: 0 auto 2rem; display: flex; align-items: center; justify-content: center; color: var(--white); font-size: 3rem;">
                        <i class="fas fa-times"></i>
                    </div>
                    <h2 style="color: var(--danger); margin-bottom: 1rem;">
                        Link không hợp lệ!
                    </h2>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                    <p style="color: var(--text-light); margin-bottom: 2rem;">
                        Link đặt lại mật khẩu có thể đã hết hạn hoặc đã được sử dụng.
                    </p>
                    <a href="forgot-password.php" class="btn btn-primary" style="display: inline-block;">
                        <i class="fas fa-redo"></i> Yêu cầu link mới
                    </a>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e0e0e0;">
                <p>
                    <a href="login.php" style="color: var(--primary-color); font-weight: 600;">
                        <i class="fas fa-arrow-left"></i> Quay lại đăng nhập
                    </a>
                </p>
            </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <p>&copy; 2024 Starbucks Coffee Company. All rights reserved.</p>
        </div>
    </div>
</body>
</html>