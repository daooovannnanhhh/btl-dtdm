<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Redirect if logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: customer/index.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starbucks - Coffee Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
                <li><a href="auth/login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a></li>
                <li><a href="auth/register.php"><i class="fas fa-user-plus"></i> Đăng ký</a></li>
            </ul>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero" style="min-height: 80vh; display: flex; align-items: center;">
        <div class="container">
            <h1 style="font-size: 4rem;">Chào mừng đến với Starbucks</h1>
            <p style="font-size: 1.5rem; margin-bottom: 3rem;">
                Hệ thống quản lý bán hàng chuyên nghiệp<br>
                Đặt hàng online - Nhanh chóng - Tiện lợi
            </p>
            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="auth/login.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 3rem;">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </a>
                <a href="auth/register.php" class="btn btn-outline" style="font-size: 1.2rem; padding: 1rem 3rem;">
                    <i class="fas fa-user-plus"></i> Đăng ký ngay
                </a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="section" style="background: var(--white);">
        <div class="container">
            <h2 class="section-title">Tính năng nổi bật</h2>
            <div class="cards-grid">
                <div class="card" style="text-align: center;">
                    <div class="card-body" style="padding: 3rem 2rem;">
                        <i class="fas fa-shopping-cart" style="font-size: 4rem; color: var(--primary-color); margin-bottom: 1.5rem;"></i>
                        <h3 class="card-title">Đặt hàng online</h3>
                        <p class="card-description">
                            Dễ dàng đặt hàng trực tuyến với vài thao tác đơn giản. 
                            Chọn món yêu thích và thanh toán nhanh chóng.
                        </p>
                    </div>
                </div>
                
                <div class="card" style="text-align: center;">
                    <div class="card-body" style="padding: 3rem 2rem;">
                        <i class="fas fa-clock" style="font-size: 4rem; color: var(--secondary-color); margin-bottom: 1.5rem;"></i>
                        <h3 class="card-title">Theo dõi đơn hàng</h3>
                        <p class="card-description">
                            Cập nhật trạng thái đơn hàng theo thời gian thực. 
                            Biết chính xác khi nào đơn hàng sẵn sàng.
                        </p>
                    </div>
                </div>
                
                <div class="card" style="text-align: center;">
                    <div class="card-body" style="padding: 3rem 2rem;">
                        <i class="fas fa-coffee" style="font-size: 4rem; color: var(--dark-color); margin-bottom: 1.5rem;"></i>
                        <h3 class="card-title">Menu đa dạng</h3>
                        <p class="card-description">
                            Hàng trăm món đồ uống và thức ăn phong phú. 
                            Từ cà phê đến trà, bánh ngọt và nhiều hơn nữa.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Preview -->
    <div class="section">
        <div class="container">
            <h2 class="section-title">Danh mục sản phẩm</h2>
            <div class="cards-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <div class="card" style="background: linear-gradient(135deg, var(--primary-color), var(--dark-color)); color: var(--white); text-align: center;">
                    <div class="card-body" style="padding: 3rem 1.5rem;">
                        <i class="fas fa-mug-hot" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Coffee</h3>
                    </div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #8B4513, #654321); color: var(--white); text-align: center;">
                    <div class="card-body" style="padding: 3rem 1.5rem;">
                        <i class="fas fa-leaf" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Tea</h3>
                    </div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #FF6B6B, #EE5A6F); color: var(--white); text-align: center;">
                    <div class="card-body" style="padding: 3rem 1.5rem;">
                        <i class="fas fa-blender" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Frappuccino</h3>
                    </div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #4ECDC4, #44A08D); color: var(--white); text-align: center;">
                    <div class="card-body" style="padding: 3rem 1.5rem;">
                        <i class="fas fa-glass-water" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Refreshers</h3>
                    </div>
                </div>
                <div class="card" style="background: linear-gradient(135deg, #F7B731, #FFA502); color: var(--white); text-align: center;">
                    <div class="card-body" style="padding: 3rem 1.5rem;">
                        <i class="fas fa-bread-slice" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h3>Bakery</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    <div class="section" style="background: linear-gradient(135deg, var(--primary-color), var(--dark-color)); color: var(--white); text-align: center;">
        <div class="container">
            <h2 style="font-size: 3rem; margin-bottom: 1.5rem; color: var(--white);">
                Sẵn sàng thưởng thức?
            </h2>
            <p style="font-size: 1.3rem; margin-bottom: 2rem; opacity: 0.9;">
                Đăng ký ngay để bắt đầu đặt hàng và nhận những ưu đãi đặc biệt!
            </p>
            <a href="auth/register.php" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 3rem; background: var(--secondary-color); color: var(--dark-color);">
                <i class="fas fa-user-plus"></i> Đăng ký miễn phí
            </a>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <h3 style="margin-bottom: 1rem;">
                <i class="fas fa-coffee"></i> Starbucks Coffee Company
            </h3>
            <p>Địa chỉ: 123 Đường ABC, Quận 1, TP. Hồ Chí Minh</p>
            <p>Email: contact@starbucks.com | Hotline: 1900 1234</p>
            <div style="margin: 1.5rem 0;">
                <a href="#" style="color: var(--white); font-size: 1.5rem; margin: 0 0.5rem;">
                    <i class="fab fa-facebook"></i>
                </a>
                <a href="#" style="color: var(--white); font-size: 1.5rem; margin: 0 0.5rem;">
                    <i class="fab fa-instagram"></i>
                </a>
                <a href="#" style="color: var(--white); font-size: 1.5rem; margin: 0 0.5rem;">
                    <i class="fab fa-twitter"></i>
                </a>
            </div>
            <p>&copy; 2024 Starbucks. All rights reserved.</p>
        </div>
    </div>
</body>
</html>