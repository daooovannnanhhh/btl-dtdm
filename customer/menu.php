<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_info = getUserInfo($_SESSION['user_id']);

// Get filter
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Build query
$query = "SELECT p.*, c.category_name FROM products p 
          JOIN categories c ON p.category_id = c.category_id 
          WHERE p.is_available = 1";

if ($category_filter > 0) {
    $query .= " AND p.category_id = $category_filter";
}

if (!empty($search)) {
    $query .= " AND (p.product_name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

$query .= " ORDER BY p.product_name";
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
    <title>Menu - Starbucks</title>
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
                <li><a href="menu.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-book-open"></i> Menu</a></li>
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

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="text-align: center; color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-book-open"></i> Menu Starbucks
        </h1>

        <!-- Filter Section -->
        <div style="background: var(--white); padding: 2rem; border-radius: 15px; margin-bottom: 2rem; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
            <form method="GET" action="" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Tìm kiếm sản phẩm..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div style="flex: 1; min-width: 200px;">
                    <select name="category" class="form-control">
                        <option value="0">Tất cả danh mục</option>
                        <?php 
                        mysqli_data_seek($categories, 0);
                        while ($cat = mysqli_fetch_assoc($categories)): 
                        ?>
                            <option value="<?php echo $cat['category_id']; ?>" 
                                    <?php echo $category_filter == $cat['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
                <?php if ($category_filter > 0 || !empty($search)): ?>
                    <a href="menu.php" class="btn btn-danger">
                        <i class="fas fa-times"></i> Xóa lọc
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Products Grid -->
        <?php if (mysqli_num_rows($products) > 0): ?>
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
                            
                            <?php if ($product['stock'] > 0): ?>
                                <div style="color: var(--success); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-check-circle"></i> Còn hàng: <?php echo $product['stock']; ?>
                                </div>
                            <?php else: ?>
                                <div style="color: var(--danger); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-times-circle"></i> Hết hàng
                                </div>
                            <?php endif; ?>
                            
                            <div class="card-price"><?php echo formatCurrency($product['price']); ?></div>
                            
                            <?php if ($product['stock'] > 0): ?>
                                <div class="card-footer">
                                    <form method="POST" action="add-to-cart.php" style="width: 100%;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                        <button type="submit" class="btn btn-primary" style="width: 100%;">
                                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-danger" style="width: 100%; cursor: not-allowed;" disabled>
                                    <i class="fas fa-times"></i> Hết hàng
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem 2rem; background: var(--white); border-radius: 15px;">
                <i class="fas fa-search" style="font-size: 4rem; color: var(--text-light); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-light);">Không tìm thấy sản phẩm nào</h3>
                <p style="color: var(--text-light); margin-top: 1rem;">Vui lòng thử lại với từ khóa khác</p>
                <a href="menu.php" class="btn btn-primary" style="margin-top: 1rem;">
                    <i class="fas fa-arrow-left"></i> Quay lại menu
                </a>
            </div>
        <?php endif; ?>
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