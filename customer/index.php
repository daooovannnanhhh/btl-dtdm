<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_info = getUserInfo($_SESSION['user_id']);

// Get featured products
$query = "SELECT p.*, c.category_name FROM products p 
          JOIN categories c ON p.category_id = c.category_id 
          WHERE p.is_available = 1 
          ORDER BY p.created_at DESC LIMIT 6";
$products = mysqli_query($conn, $query);

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories = mysqli_query($conn, $categories_query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ - Starbucks</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar-desktop.css">
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
                <?php if (isAdmin()): ?>
                    <!-- NÚT ADMIN - Chỉ hiện khi admin xem trang -->
                    <li>
                        <a href="../admin/dashboard.php" style="
                            background: linear-gradient(135deg, #ffc107, #ff9800);
                            color: #1e3932;
                            font-weight: 700;
                            border: 2px solid #ffc107;
                            box-shadow: 0 4px 8px rgba(255, 193, 7, 0.4);
                        ">
                            <i class="fas fa-shield-alt"></i> Chế độ Admin
                        </a>
                    </li>
                <?php endif; ?>
                
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
                <li><a href="profile.php"><i class="fas fa-user"></i> <?php echo htmlspecialchars($user_info['full_name']); ?></a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero">
        <div class="container">
            <h1>Chào mừng đến với Starbucks</h1>
            <p>Thưởng thức những ly cà phê tuyệt vời nhất</p>
            <a href="menu.php" class="btn btn-primary">
                <i class="fas fa-coffee"></i> Xem Menu
            </a>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="section">
        <div class="container">
            <h2 class="section-title">Danh mục sản phẩm</h2>
            <div class="cards-grid">
                <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                    <a href="menu.php?category=<?php echo $category['category_id']; ?>" 
                       style="text-decoration: none; color: inherit;">
                        <div class="card">
                            <div class="card-body" style="text-align: center; padding: 3rem 1.5rem;">
                                <i class="fas fa-coffee" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                                <h3 class="card-title"><?php echo htmlspecialchars($category['category_name']); ?></h3>
                                <p class="card-description"><?php echo htmlspecialchars($category['description']); ?></p>
                            </div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Featured Products -->
    <div class="section" style="background: var(--white);">
        <div class="container">
            <h2 class="section-title">Sản phẩm nổi bật</h2>
            <div class="cards-grid">
                <?php while ($product = mysqli_fetch_assoc($products)): ?>
                    <div class="card">
                        <img src="../<?php echo $product['image'] ? $product['image'] : 'assets/images/no-image.jpg'; ?>" 
                             alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                             class="card-image">
                        <div class="card-body">
                            <div class="badge bg-info" style="margin-bottom: 0.5rem;">
                                <?php echo htmlspecialchars($product['category_name']); ?>
                            </div>
                            <h3 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                            <p class="card-description"><?php echo htmlspecialchars($product['description']); ?></p>
                            <div class="card-price"><?php echo formatCurrency($product['price']); ?></div>
                            <div class="card-footer">
                                <form method="POST" action="add-to-cart.php" style="width: 100%;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                                        <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <div style="text-align: center; margin-top: 2rem;">
                <a href="menu.php" class="btn btn-outline" style="color: var(--primary-color); border-color: var(--primary-color);">
                    Xem tất cả sản phẩm <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Features Section -->
    <div class="section">
        <div class="container">
            <div class="cards-grid">
                <div class="card" style="text-align: center;">
                    <div class="card-body" style="padding: 2rem;">
                        <i class="fas fa-shipping-fast" style="font-size: 3rem; color: var(--primary-color); margin-bottom: 1rem;"></i>
                        <h3 class="card-title">Giao hàng nhanh</h3>
                        <p class="card-description">Đặt hàng online và nhận trong vòng 30 phút</p>
                    </div>
                </div>
                <div class="card" style="text-align: center;">
                    <div class="card-body" style="padding: 2rem;">
                        <i class="fas fa-star" style="font-size: 3rem; color: var(--secondary-color); margin-bottom: 1rem;"></i>
                        <h3 class="card-title">Chất lượng đảm bảo</h3>
                        <p class="card-description">100% cà phê nguyên chất từ Starbucks</p>
                    </div>
                </div>
                <div class="card" style="text-align: center;">
                    <div class="card-body" style="padding: 2rem;">
                        <i class="fas fa-gift" style="font-size: 3rem; color: var(--danger); margin-bottom: 1rem;"></i>
                        <h3 class="card-title">Ưu đãi hấp dẫn</h3>
                        <p class="card-description">Nhiều chương trình khuyến mãi đặc biệt</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <p><i class="fas fa-coffee"></i> Starbucks Coffee Company</p>
            <p>Địa chỉ: 123 Đường ABC, Quận 1, TP.HCM | Hotline: 1900 1234</p>
            <p>&copy; 2024 Starbucks. All rights reserved.</p>
        </div>
    </div>
</body>
</html>