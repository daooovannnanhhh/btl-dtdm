<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$user_id = $_SESSION['user_id'];

// Get order info
$order_query = "SELECT * FROM orders WHERE order_id = $order_id AND user_id = $user_id";
$order_result = mysqli_query($conn, $order_query);

if (mysqli_num_rows($order_result) == 0) {
    header("Location: order-history.php");
    exit();
}

$order = mysqli_fetch_assoc($order_result);

// Lấy thông tin QR từ database
$payment_settings_query = "SELECT * FROM payment_settings LIMIT 1";
$payment_settings_result = mysqli_query($conn, $payment_settings_query);
$payment_settings = mysqli_fetch_assoc($payment_settings_result);

$user_info = getUserInfo($user_id);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán QR - Starbucks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .qr-container {
            text-align: center;
            padding: 2rem;
        }
        .qr-code {
            max-width: 350px;
            margin: 0 auto;
            border: 5px solid var(--primary-color);
            border-radius: 15px;
            padding: 1rem;
            background: white;
        }
        .qr-code img {
            width: 100%;
            height: auto;
        }
        .payment-info {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }
        .payment-info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px dashed #ddd;
        }
        .payment-info-item:last-child {
            border-bottom: none;
        }
    </style>
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
                <li><a href="cart.php"><i class="fas fa-shopping-cart"></i> Giỏ hàng</a></li>
                <li><a href="order-history.php"><i class="fas fa-history"></i> Đơn hàng</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_info['full_name']); ?></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem; max-width: 700px;">
        <div style="background: var(--white); padding: 3rem; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
            <h1 style="text-align: center; color: var(--primary-color); margin-bottom: 1rem;">
                <i class="fas fa-qrcode"></i> Quét mã để thanh toán
            </h1>
            
            <p style="text-align: center; color: var(--text-light); margin-bottom: 2rem;">
                Mã đơn hàng: <strong style="color: var(--primary-color);">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></strong>
            </p>

            <!-- QR Code -->
            <div class="qr-container">
                <div class="qr-code">
                    <?php if ($payment_settings && $payment_settings['qr_image']): ?>
                        <img src="../assets/images/<?php echo htmlspecialchars($payment_settings['qr_image']); ?>" alt="QR Payment">
                    <?php else: ?>
                        <div style="padding: 2rem; color: var(--danger);">
                            <i class="fas fa-exclamation-triangle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                            <p><strong>Admin chưa cấu hình mã QR!</strong></p>
                            <p style="font-size: 0.9rem;">Vui lòng liên hệ hotline: 1900-xxxx</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Payment Info -->
            <div class="payment-info">
                <h4 style="margin-bottom: 1rem; color: var(--primary-color); text-align: center;">
                    <i class="fas fa-university"></i> Thông tin chuyển khoản
                </h4>
                <div class="payment-info-item">
                    <span><strong>Ngân hàng:</strong></span>
                    <span><?php echo $payment_settings ? htmlspecialchars($payment_settings['bank_name']) : 'Chưa cấu hình'; ?></span>
                </div>
                <div class="payment-info-item">
                    <span><strong>Số tài khoản:</strong></span>
                    <span><?php echo $payment_settings ? htmlspecialchars($payment_settings['account_number']) : 'Chưa cấu hình'; ?></span>
                </div>
                <div class="payment-info-item">
                    <span><strong>Chủ tài khoản:</strong></span>
                    <span><?php echo $payment_settings ? htmlspecialchars($payment_settings['account_holder']) : 'Chưa cấu hình'; ?></span>
                </div>
                <div class="payment-info-item" style="background: #fff3cd; margin: 0.5rem -1.5rem -1.5rem -1.5rem; padding: 1rem 1.5rem; border-radius: 0 0 10px 10px;">
                    <span><strong>Số tiền:</strong></span>
                    <span style="color: var(--danger); font-weight: bold; font-size: 1.3rem;">
                        <?php echo formatCurrency($order['total_amount']); ?>
                    </span>
                </div>
            </div>

            <!-- Nội dung chuyển khoản -->
            <div style="background: #e7f3ff; padding: 1.5rem; border-radius: 10px; margin: 1.5rem 0; border-left: 4px solid #0066cc;">
                <div style="text-align: center;">
                    <strong style="font-size: 1.1rem; color: #0066cc;">
                        <i class="fas fa-sticky-note"></i> Nội dung chuyển khoản:
                    </strong>
                    <div style="margin-top: 0.5rem; font-size: 1.3rem; font-weight: bold; color: var(--primary-color); letter-spacing: 1px;">
                        SB <?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div style="background: #fff3cd; padding: 1.5rem; border-radius: 10px; margin: 1.5rem 0; border-left: 4px solid #ffc107;">
                <h5 style="margin-bottom: 1rem; color: #856404;">
                    <i class="fas fa-info-circle"></i> Hướng dẫn:
                </h5>
                <ul style="margin: 0; padding-left: 1.5rem; color: #856404;">
                    <li style="margin-bottom: 0.5rem;">Mở app ngân hàng và quét mã QR</li>
                    <li style="margin-bottom: 0.5rem;">Chuyển <strong>ĐÚNG số tiền</strong>: <strong><?php echo formatCurrency($order['total_amount']); ?></strong></li>
                    <li style="margin-bottom: 0.5rem;">Nhập <strong>ĐÚNG nội dung</strong>: <strong>SB <?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></strong></li>
                    <li style="margin-bottom: 0.5rem;">Đơn hàng sẽ được xử lý sau khi Admin xác nhận</li>
                </ul>
            </div>

            <!-- Back Button -->
            <div style="margin-top: 2rem;">
                <a href="order-detail.php?id=<?php echo $order_id; ?>" 
                   class="btn btn-primary" 
                   style="width: 100%; text-align: center; padding: 1rem; font-size: 1.1rem;">
                    <i class="fas fa-arrow-left"></i> Quay lại chi tiết đơn hàng
                </a>
            </div>

            <p style="text-align: center; color: var(--text-light); margin-top: 1.5rem; font-size: 0.9rem;">
                <i class="fas fa-shield-alt"></i> Thanh toán an toàn & bảo mật
            </p>
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