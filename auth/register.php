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
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = 'Vui lòng điền đầy đủ thông tin bắt buộc!';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp!';
    } elseif (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự!';
    } else {
        // Check if username exists
        $check_username = "SELECT * FROM users WHERE username = '$username'";
        $result = mysqli_query($conn, $check_username);
        
        if (mysqli_num_rows($result) > 0) {
            $error = 'Tên đăng nhập đã tồn tại!';
        } else {
            // Check if email exists
            $check_email = "SELECT * FROM users WHERE email = '$email'";
            $result = mysqli_query($conn, $check_email);
            
            if (mysqli_num_rows($result) > 0) {
                $error = 'Email đã được sử dụng!';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $query = "INSERT INTO users (username, email, password, full_name, phone, address, role) 
                         VALUES ('$username', '$email', '$hashed_password', '$full_name', '$phone', '$address', 'customer')";
                
                if (mysqli_query($conn, $query)) {
                    header("Location: login.php?registered=1");
                    exit();
                } else {
                    $error = 'Đã xảy ra lỗi! Vui lòng thử lại.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Starbucks</title>
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

    <div class="container" style="padding: 3rem 2rem;">
        <div class="form-container" style="max-width: 600px;">
            <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                <i class="fas fa-user-plus"></i> Đăng ký tài khoản
            </h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Tên đăng nhập <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Nhập tên đăng nhập" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email <span style="color: red;">*</span>
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Nhập email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-id-card"></i> Họ và tên <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           placeholder="Nhập họ và tên" required
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Số điện thoại
                    </label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           placeholder="Nhập số điện thoại"
                           value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">
                        <i class="fas fa-map-marker-alt"></i> Địa chỉ
                    </label>
                    <textarea id="address" name="address" class="form-control" 
                              placeholder="Nhập địa chỉ"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Mật khẩu <span style="color: red;">*</span>
                    </label>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Nhập mật khẩu (tối thiểu 6 ký tự)" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Xác nhận mật khẩu <span style="color: red;">*</span>
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                           placeholder="Nhập lại mật khẩu" required>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
                    <i class="fas fa-user-plus"></i> Đăng ký
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <p>Đã có tài khoản? 
                    <a href="login.php" style="color: var(--primary-color); font-weight: 600;">
                        Đăng nhập ngay
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