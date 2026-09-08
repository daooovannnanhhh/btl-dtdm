<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();

$user_info = getUserInfo($_SESSION['user_id']);
$user_id = $_SESSION['user_id'];

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update') {
            $cart_id = (int)$_POST['cart_id'];
            $quantity = (int)$_POST['quantity'];
            
            if ($quantity > 0) {
                $update_query = "UPDATE cart SET quantity = $quantity WHERE cart_id = $cart_id AND user_id = $user_id";
                mysqli_query($conn, $update_query);
                $_SESSION['success'] = 'Đã cập nhật giỏ hàng!';
            }
        } elseif ($_POST['action'] == 'remove') {
            $cart_id = (int)$_POST['cart_id'];
            $delete_query = "DELETE FROM cart WHERE cart_id = $cart_id AND user_id = $user_id";
            mysqli_query($conn, $delete_query);
            $_SESSION['success'] = 'Đã xóa sản phẩm khỏi giỏ hàng!';
        }
        header("Location: cart.php");
        exit();
    }
}

// Get cart items
$cart_query = "SELECT c.*, p.product_name, p.price, p.image, p.stock 
               FROM cart c 
               JOIN products p ON c.product_id = p.product_id 
               WHERE c.user_id = $user_id";
$cart_items = mysqli_query($conn, $cart_query);

// Calculate total
$total = 0;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Starbucks</title>
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
                    <a href="cart.php" style="background-color: rgba(255,255,255,0.2);">
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
            <i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn
        </h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($cart_items) > 0): ?>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php while ($item = mysqli_fetch_assoc($cart_items)): 
                        $item_total = $item['price'] * $item['quantity'];
                        $total += $item_total;
                    ?>
                        <div class="cart-item">
                            <img src="../<?php echo $item['image'] ? $item['image'] : 'assets/images/no-image.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                 class="cart-item-image">
                            
                            <div class="cart-item-details">
                                <h3 class="cart-item-title"><?php echo htmlspecialchars($item['product_name']); ?></h3>
                                <p class="cart-item-price"><?php echo formatCurrency($item['price']); ?></p>
                                
                                <?php if ($item['stock'] < $item['quantity']): ?>
                                    <div class="alert alert-danger" style="margin-top: 0.5rem; padding: 0.5rem;">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Chỉ còn <?php echo $item['stock']; ?> sản phẩm!
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                                <form method="POST" action="" style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>" 
                                           class="form-control" style="width: 80px;"
                                           onchange="this.form.submit()">
                                </form>
                                
                                <p style="font-weight: 700; font-size: 1.2rem; color: var(--primary-color);">
                                    <?php echo formatCurrency($item_total); ?>
                                </p>
                                
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" class="btn btn-danger" 
                                            onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

                <!-- Cart Summary -->
                <div>
                    <div class="cart-summary">
                        <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">
                            <i class="fas fa-receipt"></i> Tổng đơn hàng
                        </h3>
                        
                        <div class="summary-row">
                            <span>Tạm tính:</span>
                            <strong><?php echo formatCurrency($total); ?></strong>
                        </div>
                        
                        <div class="summary-row">
                            <span>Phí vận chuyển:</span>
                            <strong style="color: var(--success);">Miễn phí</strong>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Tổng cộng:</span>
                            <strong><?php echo formatCurrency($total); ?></strong>
                        </div>
                        
                        <a href="checkout.php" class="btn btn-primary" style="width: 100%; margin-top: 1.5rem;">
                            <i class="fas fa-credit-card"></i> Thanh toán
                        </a>
                        
                        <a href="menu.php" class="btn btn-outline" 
                           style="width: 100%; margin-top: 1rem; color: var(--primary-color); border: 2px solid var(--primary-color);">
                            <i class="fas fa-arrow-left"></i> Tiếp tục mua hàng
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 4rem 2rem; background: var(--white); border-radius: 15px;">
                <i class="fas fa-shopping-cart" style="font-size: 5rem; color: var(--text-light); margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text-dark); margin-bottom: 1rem;">Giỏ hàng trống</h3>
                <p style="color: var(--text-light); margin-bottom: 2rem;">
                    Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm
                </p>
                <a href="menu.php" class="btn btn-primary">
                    <i class="fas fa-coffee"></i> Xem menu
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

    <script>
    // Auto submit form when quantity changes
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('change', function() {
            if (this.value < 1) this.value = 1;
            if (this.value > this.max) this.value = this.max;
        });
    });
    </script>
</body>
</html>