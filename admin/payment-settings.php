<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

requireLogin();
requireAdmin();

// Xử lý upload QR
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bank_name = sanitize($_POST['bank_name']);
    $account_number = sanitize($_POST['account_number']);
    $account_holder = sanitize($_POST['account_holder']);
    
    // Xử lý upload ảnh QR
    $qr_image = '';
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['qr_image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $new_filename = 'qr_' . time() . '.' . $ext;
            $upload_path = '../assets/images/' . $new_filename;
            
            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $upload_path)) {
                $qr_image = $new_filename;
            }
        }
    }
    
    // Kiểm tra xem đã có setting chưa
    $check_query = "SELECT id, qr_image FROM payment_settings LIMIT 1";
    $check_result = mysqli_query($conn, $check_query);
    
    if (mysqli_num_rows($check_result) > 0) {
        // Update
        $row = mysqli_fetch_assoc($check_result);
        $setting_id = $row['id'];
        
        if ($qr_image) {
            // Xóa ảnh cũ
            if ($row['qr_image'] && file_exists('../assets/images/' . $row['qr_image'])) {
                unlink('../assets/images/' . $row['qr_image']);
            }
            
            $update_query = "UPDATE payment_settings SET 
                            bank_name = '$bank_name',
                            account_number = '$account_number',
                            account_holder = '$account_holder',
                            qr_image = '$qr_image'
                            WHERE id = $setting_id";
        } else {
            $update_query = "UPDATE payment_settings SET 
                            bank_name = '$bank_name',
                            account_number = '$account_number',
                            account_holder = '$account_holder'
                            WHERE id = $setting_id";
        }
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['success'] = 'Cập nhật thông tin thanh toán thành công!';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra!';
        }
    } else {
        // Insert
        $insert_query = "INSERT INTO payment_settings (bank_name, account_number, account_holder, qr_image) 
                        VALUES ('$bank_name', '$account_number', '$account_holder', '$qr_image')";
        
        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['success'] = 'Thêm thông tin thanh toán thành công!';
        } else {
            $_SESSION['error'] = 'Có lỗi xảy ra!';
        }
    }
    
    header("Location: payment-settings.php");
    exit();
}

// Lấy thông tin hiện tại
$settings_query = "SELECT * FROM payment_settings LIMIT 1";
$settings_result = mysqli_query($conn, $settings_query);
$settings = mysqli_fetch_assoc($settings_result);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt thanh toán - Starbucks Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .qr-preview {
            max-width: 300px;
            margin: 1rem 0;
            border: 3px solid var(--primary-color);
            border-radius: 10px;
            padding: 10px;
        }
        .qr-preview img {
            width: 100%;
            height: auto;
        }
    </style>
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
                <li><a href="products.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Đơn hàng</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Báo cáo</a></li>
                <li><a href="payment-settings.php" style="background-color: rgba(255,255,255,0.2);"><i class="fas fa-cog"></i> Thanh toán</a></li>
                <li><a href="../customer/index.php"><i class="fas fa-store"></i> Xem trang</a></li>
                <li><a href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </div>
    </div>

    <div class="container" style="padding: 3rem 2rem;">
        <h1 style="color: var(--primary-color); margin-bottom: 2rem;">
            <i class="fas fa-cog"></i> Cài đặt thanh toán QR
        </h1>

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

        <div style="max-width: 800px; margin: 0 auto;">
            <div style="background: var(--white); padding: 2rem; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.08);">
                <form method="POST" enctype="multipart/form-data">
                    <h3 style="margin-bottom: 1.5rem; color: var(--primary-color);">
                        <i class="fas fa-university"></i> Thông tin ngân hàng
                    </h3>

                    <div class="form-group">
                        <label for="bank_name">
                            <i class="fas fa-building"></i> Tên ngân hàng <span style="color: red;">*</span>
                        </label>
                        <input type="text" id="bank_name" name="bank_name" class="form-control" 
                               value="<?php echo $settings ? htmlspecialchars($settings['bank_name']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="account_number">
                            <i class="fas fa-credit-card"></i> Số tài khoản <span style="color: red;">*</span>
                        </label>
                        <input type="text" id="account_number" name="account_number" class="form-control" 
                               value="<?php echo $settings ? htmlspecialchars($settings['account_number']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="account_holder">
                            <i class="fas fa-user"></i> Chủ tài khoản <span style="color: red;">*</span>
                        </label>
                        <input type="text" id="account_holder" name="account_holder" class="form-control" 
                               value="<?php echo $settings ? htmlspecialchars($settings['account_holder']) : ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="qr_image">
                            <i class="fas fa-qrcode"></i> Mã QR thanh toán <span style="color: red;">*</span>
                        </label>
                        
                        <?php if ($settings && $settings['qr_image']): ?>
                            <div class="qr-preview">
                                <img src="../assets/images/<?php echo htmlspecialchars($settings['qr_image']); ?>" 
                                     alt="QR Code" id="preview">
                            </div>
                        <?php else: ?>
                            <div class="qr-preview" id="preview-container" style="display: none;">
                                <img src="" alt="QR Code Preview" id="preview">
                            </div>
                        <?php endif; ?>

                        <input type="file" id="qr_image" name="qr_image" class="form-control" 
                               accept="image/*" onchange="previewImage(this)">
                        <small style="color: var(--text-light); display: block; margin-top: 0.5rem;">
                            <i class="fas fa-info-circle"></i> Chấp nhận: JPG, PNG, GIF. Kích thước tối đa: 5MB
                        </small>
                    </div>

                    <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                        <button type="submit" class="btn btn-success" style="flex: 1;">
                            <i class="fas fa-save"></i> Lưu cài đặt
                        </button>
                        <a href="dashboard.php" class="btn btn-outline" 
                           style="flex: 1; color: var(--primary-color); border: 2px solid var(--primary-color); text-align: center;">
                            <i class="fas fa-times"></i> Hủy
                        </a>
                    </div>
                </form>
            </div>

            <!-- Preview QR -->
            <?php if ($settings && $settings['qr_image']): ?>
                <div style="background: #f8f9fa; padding: 2rem; border-radius: 15px; margin-top: 2rem;">
                    <h3 style="margin-bottom: 1rem; color: var(--primary-color);">
                        <i class="fas fa-eye"></i> Xem trước mã QR trên trang thanh toán
                    </h3>
                    <div style="text-align: center; padding: 2rem; background: white; border-radius: 10px;">
                        <div style="max-width: 350px; margin: 0 auto; border: 5px solid var(--primary-color); border-radius: 15px; padding: 1rem;">
                            <img src="../assets/images/<?php echo htmlspecialchars($settings['qr_image']); ?>" 
                                 alt="QR Preview" style="width: 100%; height: auto;">
                        </div>
                        <div style="margin-top: 1.5rem; background: #f8f9fa; padding: 1rem; border-radius: 10px;">
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px dashed #ddd;">
                                <strong>Ngân hàng:</strong>
                                <span><?php echo htmlspecialchars($settings['bank_name']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px dashed #ddd;">
                                <strong>Số TK:</strong>
                                <span><?php echo htmlspecialchars($settings['account_number']); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                                <strong>Chủ TK:</strong>
                                <span><?php echo htmlspecialchars($settings['account_holder']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-content">
            <p><i class="fas fa-coffee"></i> Starbucks Admin Panel</p>
            <p>&copy; 2024 Starbucks. All rights reserved.</p>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('preview');
            const previewContainer = document.getElementById('preview-container');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    if (previewContainer) {
                        previewContainer.style.display = 'block';
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>