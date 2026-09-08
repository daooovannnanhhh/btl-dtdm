<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $product_name = sanitize($_POST['product_name']);
            $category_id = (int)$_POST['category_id'];
            $description = sanitize($_POST['description']);
            $price = (float)$_POST['price'];
            $material_cost = (float)$_POST['material_cost']; // THÊM MỚI
            $stock = (int)$_POST['stock'];
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            
            $image = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $image = uploadImage($_FILES['image'], 'products');
                if (!$image) {
                    $_SESSION['error'] = 'Upload ảnh thất bại!';
                }
            }
            
            if ($_POST['action'] == 'add') {
                $query = "INSERT INTO products (category_id, product_name, description, price, material_cost, stock, is_available" . 
                         ($image ? ", image" : "") . ") VALUES ($category_id, '$product_name', '$description', $price, $material_cost, $stock, $is_available" . 
                         ($image ? ", '$image'" : "") . ")";
                
                if (mysqli_query($conn, $query)) {
                    $_SESSION['success'] = 'Thêm sản phẩm thành công!';
                } else {
                    $_SESSION['error'] = 'Thêm sản phẩm thất bại!';
                }
            } else {
                $product_id = (int)$_POST['product_id'];
                $query = "UPDATE products SET 
                         category_id = $category_id,
                         product_name = '$product_name',
                         description = '$description',
                         price = $price,
                         material_cost = $material_cost,
                         stock = $stock,
                         is_available = $is_available" .
                         ($image ? ", image = '$image'" : "") . 
                         " WHERE product_id = $product_id";
                
                if (mysqli_query($conn, $query)) {
                    $_SESSION['success'] = 'Cập nhật sản phẩm thành công!';
                } else {
                    $_SESSION['error'] = 'Cập nhật sản phẩm thất bại!';
                }
            }
            
            header("Location: products.php");
            exit();
        } elseif ($_POST['action'] == 'delete') {
            $product_id = (int)$_POST['product_id'];
            $query = "DELETE FROM products WHERE product_id = $product_id";
            
            if (mysqli_query($conn, $query)) {
                $_SESSION['success'] = 'Xóa sản phẩm thành công!';
            } else {
                $_SESSION['error'] = 'Xóa sản phẩm thất bại!';
            }
            
            header("Location: products.php");
            exit();
        }
    }
}

// Get products with profit calculation
$products_query = "SELECT p.*, c.category_name,
                   (p.price - p.material_cost) as profit_per_unit,
                   ((p.price - p.material_cost) / p.price * 100) as profit_margin
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   ORDER BY p.product_id DESC";
$products = mysqli_query($conn, $products_query);

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY category_name";
$categories = mysqli_query($conn, $categories_query);

// Edit mode
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_query = "SELECT * FROM products WHERE product_id = $edit_id";
    $edit_result = mysqli_query($conn, $edit_query);
    $edit_product = mysqli_fetch_assoc($edit_result);
}

$show_form = isset($_GET['action']) && $_GET['action'] == 'add' || $edit_product;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý sản phẩm - Starbucks Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar-desktop.css">
    <link rel="icon" type="image/png" href="assets/images/logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navbar -->
    <div class="navbar">
        <div class="container">
            <a href="dashboard.php" class="navbar-brand">
                <i class="fas fa-coffee"></i> Star<span>bucks</span> Admin
            </a>
            <ul class="navbar-menu">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="products.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Đơn hàng</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Báo cáo</a></li>
                <li><a href="../customer/index.php"><i class="fas fa-store"></i> Xem trang</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="color: var(--primary-color); margin: 0;">
                <i class="fas fa-box"></i> Quản lý sản phẩm
            </h1>
            <?php if (!$show_form): ?>
                <a href="?action=add" class="btn btn-success">
                    <i class="fas fa-plus-circle"></i> Thêm sản phẩm mới
                </a>
            <?php else: ?>
                <a href="products.php" class="btn btn-danger">
                    <i class="fas fa-times"></i> Hủy
                </a>
            <?php endif; ?>
        </div>

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

        <?php if ($show_form): ?>
            <!-- Add/Edit Form -->
            <div class="form-container" style="max-width: 800px; margin-bottom: 3rem;">
                <h2 style="text-align: center; margin-bottom: 2rem; color: var(--primary-color);">
                    <i class="fas fa-<?php echo $edit_product ? 'edit' : 'plus-circle'; ?>"></i> 
                    <?php echo $edit_product ? 'Chỉnh sửa' : 'Thêm'; ?> sản phẩm
                </h2>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="product_name">
                            <i class="fas fa-tag"></i> Tên sản phẩm <span style="color: red;">*</span>
                        </label>
                        <input type="text" id="product_name" name="product_name" class="form-control" 
                               value="<?php echo $edit_product ? htmlspecialchars($edit_product['product_name']) : ''; ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">
                            <i class="fas fa-list"></i> Danh mục <span style="color: red;">*</span>
                        </label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php 
                            mysqli_data_seek($categories, 0);
                            while ($cat = mysqli_fetch_assoc($categories)): 
                            ?>
                                <option value="<?php echo $cat['category_id']; ?>" 
                                        <?php echo ($edit_product && $edit_product['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-align-left"></i> Mô tả
                        </label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="3"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="price">
                                <i class="fas fa-dollar-sign"></i> Giá bán <span style="color: red;">*</span>
                            </label>
                            <input type="number" id="price" name="price" class="form-control" 
                                   step="1000" min="0" 
                                   value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" 
                                   required onchange="calculateProfit()">
                        </div>
                        
                        <div class="form-group">
                            <label for="material_cost">
                                <i class="fas fa-coins"></i> Chi phí NL <span style="color: red;">*</span>
                            </label>
                            <input type="number" id="material_cost" name="material_cost" class="form-control" 
                                   step="1000" min="0" 
                                   value="<?php echo $edit_product ? $edit_product['material_cost'] : ''; ?>" 
                                   required onchange="calculateProfit()">
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">
                                <i class="fas fa-box"></i> Số lượng <span style="color: red;">*</span>
                            </label>
                            <input type="number" id="stock" name="stock" class="form-control" 
                                   min="0" 
                                   value="<?php echo $edit_product ? $edit_product['stock'] : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    
                    <!-- Profit Preview -->
                    <div id="profit-preview" style="background: var(--light-bg); padding: 1rem; border-radius: 8px; margin-bottom: 1rem; display: none;">
                        <strong>💰 Lợi nhuận dự kiến:</strong>
                        <div style="margin-top: 0.5rem;">
                            <span style="color: var(--success); font-size: 1.1rem; font-weight: 700;" id="profit-amount">0 ₫</span>
                            (<span id="profit-margin">0%</span> / sản phẩm)
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">
                            <i class="fas fa-image"></i> Ảnh sản phẩm
                        </label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        <?php if ($edit_product && $edit_product['image']): ?>
                            <div style="margin-top: 1rem;">
                                <img src="../<?php echo $edit_product['image']; ?>" alt="Current image" 
                                     style="max-width: 200px; border-radius: 10px;">
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; cursor: pointer;">
                            <input type="checkbox" name="is_available" style="margin-right: 0.5rem;" 
                                   <?php echo (!$edit_product || $edit_product['is_available']) ? 'checked' : ''; ?>>
                            <span><i class="fas fa-check-circle"></i> Sản phẩm đang bán</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-save"></i> <?php echo $edit_product ? 'Cập nhật' : 'Thêm'; ?> sản phẩm
                    </button>
                </form>
            </div>
            
            <script>
            function calculateProfit() {
                const price = parseFloat(document.getElementById('price').value) || 0;
                const cost = parseFloat(document.getElementById('material_cost').value) || 0;
                const profit = price - cost;
                const margin = price > 0 ? (profit / price * 100) : 0;
                
                if (price > 0 && cost > 0) {
                    document.getElementById('profit-preview').style.display = 'block';
                    document.getElementById('profit-amount').textContent = profit.toLocaleString('vi-VN') + ' ₫';
                    document.getElementById('profit-margin').textContent = margin.toFixed(1) + '%';
                } else {
                    document.getElementById('profit-preview').style.display = 'none';
                }
            }
            </script>
        <?php endif; ?>

        <!-- Products Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá bán</th>
                        <th>Chi phí NL</th>
                        <th>Lợi nhuận</th>
                        <th>Tồn kho</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($products) > 0): ?>
                        <?php while ($product = mysqli_fetch_assoc($products)): ?>
                            <tr>
                                <td><strong>#<?php echo $product['product_id']; ?></strong></td>
                                <td>
                                    <img src="../<?php echo $product['image'] ? $product['image'] : 'assets/images/no-image.jpg'; ?>" 
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px;">
                                </td>
                                <td><strong><?php echo htmlspecialchars($product['product_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                <td><strong style="color: var(--primary-color);"><?php echo formatCurrency($product['price']); ?></strong></td>
                                <td><strong style="color: var(--danger);"><?php echo formatCurrency($product['material_cost']); ?></strong></td>
                                <td>
                                    <div><strong style="color: var(--success);"><?php echo formatCurrency($product['profit_per_unit']); ?></strong></div>
                                    <div style="font-size: 0.85rem; color: var(--text-light);">
                                        (<?php echo number_format($product['profit_margin'], 1); ?>%)
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?php echo $product['stock'] < 10 ? 'bg-danger' : 'bg-success'; ?>">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($product['is_available']): ?>
                                        <span class="badge bg-success">Đang bán</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Ngừng bán</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <a href="?edit=<?php echo $product['product_id']; ?>" 
                                           class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="" style="display: inline;" 
                                              onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                            <button type="submit" class="btn btn-danger" 
                                                    style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 2rem; color: var(--text-light);">
                                <i class="fas fa-inbox"></i> Chưa có sản phẩm nào
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