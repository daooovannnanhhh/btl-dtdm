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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
    
    if (empty($email)) {
        $error = 'Vui lòng nhập email!';
    } else {
        // Check if email exists
        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32)); // 64 characters
            $token_expiry = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token hết hạn sau 1 giờ
            
            // Save token to database
            $update_query = "UPDATE users SET 
                           reset_token = '$reset_token',
                           reset_token_expiry = '$token_expiry'
                           WHERE user_id = {$user['user_id']}";
            
            if (mysqli_query($conn, $update_query)) {
                // In production, send email with reset link
                // For now, we'll just show the reset link
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
                $reset_link = $protocol . $_SERVER['HTTP_HOST'] . "/auth/reset-password.php?token=$reset_token";
                
                $success = "Link đặt lại mật khẩu đã được tạo! (Trong môi trường thực tế, link này sẽ được gửi qua email)<br><br>
                           <strong>Link của bạn:</strong><br>
                           <a href='$reset_link' style='color: var(--primary-color); word-break: break-all;'>$reset_link</a><br><br>
                           <small>Link này có hiệu lực trong 1 giờ.</small>";
                
                // TODO: Send email in production
                /*
                $to = $email;
                $subject = "Đặt lại mật khẩu - Starbucks";
                $message = "Click vào link sau để đặt lại mật khẩu: $reset_link";
                $headers = "From: noreply@starbucks.com";
                mail($to, $subject, $message, $headers);
                */
            } else {
                $error = 'Có lỗi xảy ra! Vui lòng thử lại.';
            }
        } else {
            // Don't reveal if email exists or not (security)
            $success = "Nếu email tồn tại trong hệ thống, bạn sẽ nhận được link đặt lại mật khẩu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - Starbucks</title>
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
            <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                <i class="fas fa-key"></i> Quên mật khẩu
            </h2>
            
            <p style="text-align: center; color: var(--text-light); margin-bottom: 2rem;">
                Nhập email của bạn để nhận link đặt lại mật khẩu
            </p>
            
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
            
            <?php if (!$success): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" id="email" name="email" class="form-control" 
                               placeholder="Nhập email đã đăng ký" required
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                        <i class="fas fa-paper-plane"></i> Gửi link đặt lại mật khẩu
                    </button>
                </form>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e0e0e0;">
                <p>Đã nhớ mật khẩu? 
                    <a href="login.php" style="color: var(--primary-color); font-weight: 600;">
                        Đăng nhập ngay
                    </a>
                </p>
                <p style="margin-top: 0.5rem;">Chưa có tài khoản? 
                    <a href="register.php" style="color: var(--primary-color); font-weight: 600;">
                        Đăng ký
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