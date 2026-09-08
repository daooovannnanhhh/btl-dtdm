/**
 * Form Validation JavaScript
 * Bắt lỗi nhập liệu phía client
 */

// Hàm hiển thị lỗi
function showError(input, message) {
    const formGroup = input.parentElement;
    
    // Xóa lỗi cũ nếu có
    const oldError = formGroup.querySelector('.error-message');
    if (oldError) {
        oldError.remove();
    }
    
    // Thêm class lỗi cho input
    input.classList.add('error');
    input.style.borderColor = '#dc3545';
    
    // Tạo thông báo lỗi
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '0.85rem';
    errorDiv.style.marginTop = '0.3rem';
    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
    
    formGroup.appendChild(errorDiv);
}

// Hàm xóa lỗi
function clearError(input) {
    const formGroup = input.parentElement;
    input.classList.remove('error');
    input.style.borderColor = '#e0e0e0';
    
    const errorDiv = formGroup.querySelector('.error-message');
    if (errorDiv) {
        errorDiv.remove();
    }
}

// Validate email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

// Validate số điện thoại (VN)
function validatePhone(phone) {
    const re = /^(0|\+84)[0-9]{9}$/;
    return re.test(phone);
}

// Validate giá (phải là số dương)
function validatePrice(price) {
    return !isNaN(price) && parseFloat(price) > 0;
}

// Validate số lượng (phải là số nguyên dương)
function validateQuantity(quantity) {
    return !isNaN(quantity) && Number.isInteger(parseFloat(quantity)) && parseInt(quantity) > 0;
}

// Validate username (chỉ chữ, số và dấu gạch dưới, 3-20 ký tự)
function validateUsername(username) {
    const re = /^[a-zA-Z0-9_]{3,20}$/;
    return re.test(username);
}

// Validate password (tối thiểu 6 ký tự)
function validatePassword(password) {
    return password.length >= 6;
}

// ==================== VALIDATION CHO FORM ĐĂNG KÝ ====================
function validateRegisterForm(form) {
    let isValid = true;
    
    // Username
    const username = form.querySelector('#username');
    if (username) {
        clearError(username);
        if (username.value.trim() === '') {
            showError(username, 'Vui lòng nhập tên đăng nhập');
            isValid = false;
        } else if (!validateUsername(username.value)) {
            showError(username, 'Username chỉ chứa chữ, số và dấu gạch dưới (3-20 ký tự)');
            isValid = false;
        }
    }
    
    // Email
    const email = form.querySelector('#email');
    if (email) {
        clearError(email);
        if (email.value.trim() === '') {
            showError(email, 'Vui lòng nhập email');
            isValid = false;
        } else if (!validateEmail(email.value)) {
            showError(email, 'Email không hợp lệ');
            isValid = false;
        }
    }
    
    // Full name
    const fullName = form.querySelector('#full_name');
    if (fullName) {
        clearError(fullName);
        if (fullName.value.trim() === '') {
            showError(fullName, 'Vui lòng nhập họ tên');
            isValid = false;
        } else if (fullName.value.trim().length < 3) {
            showError(fullName, 'Họ tên phải có ít nhất 3 ký tự');
            isValid = false;
        }
    }
    
    // Phone
    const phone = form.querySelector('#phone');
    if (phone && phone.value.trim() !== '') {
        clearError(phone);
        if (!validatePhone(phone.value)) {
            showError(phone, 'Số điện thoại không hợp lệ (VD: 0912345678)');
            isValid = false;
        }
    }
    
    // Password
    const password = form.querySelector('#password');
    if (password) {
        clearError(password);
        if (password.value === '') {
            showError(password, 'Vui lòng nhập mật khẩu');
            isValid = false;
        } else if (!validatePassword(password.value)) {
            showError(password, 'Mật khẩu phải có ít nhất 6 ký tự');
            isValid = false;
        }
    }
    
    // Confirm Password
    const confirmPassword = form.querySelector('#confirm_password');
    if (confirmPassword && password) {
        clearError(confirmPassword);
        if (confirmPassword.value === '') {
            showError(confirmPassword, 'Vui lòng xác nhận mật khẩu');
            isValid = false;
        } else if (confirmPassword.value !== password.value) {
            showError(confirmPassword, 'Mật khẩu xác nhận không khớp');
            isValid = false;
        }
    }
    
    return isValid;
}

// ==================== VALIDATION CHO FORM SẢN PHẨM ====================
function validateProductForm(form) {
    let isValid = true;
    
    // Product name
    const productName = form.querySelector('#product_name');
    if (productName) {
        clearError(productName);
        if (productName.value.trim() === '') {
            showError(productName, 'Vui lòng nhập tên sản phẩm');
            isValid = false;
        } else if (productName.value.trim().length < 3) {
            showError(productName, 'Tên sản phẩm phải có ít nhất 3 ký tự');
            isValid = false;
        }
    }
    
    // Category
    const category = form.querySelector('#category_id');
    if (category) {
        clearError(category);
        if (category.value === '' || category.value === '0') {
            showError(category, 'Vui lòng chọn danh mục');
            isValid = false;
        }
    }
    
    // Price
    const price = form.querySelector('#price');
    if (price) {
        clearError(price);
        if (price.value === '') {
            showError(price, 'Vui lòng nhập giá sản phẩm');
            isValid = false;
        } else if (!validatePrice(price.value)) {
            showError(price, 'Giá phải là số dương');
            isValid = false;
        } else if (parseFloat(price.value) < 1000) {
            showError(price, 'Giá phải lớn hơn 1,000 VNĐ');
            isValid = false;
        }
    }
    
    // Stock
    const stock = form.querySelector('#stock');
    if (stock) {
        clearError(stock);
        if (stock.value === '') {
            showError(stock, 'Vui lòng nhập số lượng');
            isValid = false;
        } else if (!validateQuantity(stock.value)) {
            showError(stock, 'Số lượng phải là số nguyên dương');
            isValid = false;
        }
    }
    
    // Image (nếu là thêm mới)
    const image = form.querySelector('#image');
    const isEdit = form.querySelector('input[name="action"]')?.value === 'edit';
    
    if (image && !isEdit) {
        clearError(image);
        if (image.files.length > 0) {
            const file = image.files[0];
            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!validTypes.includes(file.type)) {
                showError(image, 'Chỉ chấp nhận file ảnh (JPG, PNG, GIF, WEBP)');
                isValid = false;
            } else if (file.size > maxSize) {
                showError(image, 'Kích thước file không được vượt quá 5MB');
                isValid = false;
            }
        }
    }
    
    return isValid;
}

// ==================== VALIDATION CHO FORM CHECKOUT ====================
function validateCheckoutForm(form) {
    let isValid = true;
    
    // Phone
    const phone = form.querySelector('#phone');
    if (phone) {
        clearError(phone);
        if (phone.value.trim() === '') {
            showError(phone, 'Vui lòng nhập số điện thoại');
            isValid = false;
        } else if (!validatePhone(phone.value)) {
            showError(phone, 'Số điện thoại không hợp lệ');
            isValid = false;
        }
    }
    
    // Address
    const address = form.querySelector('#delivery_address');
    if (address) {
        clearError(address);
        if (address.value.trim() === '') {
            showError(address, 'Vui lòng nhập địa chỉ giao hàng');
            isValid = false;
        } else if (address.value.trim().length < 10) {
            showError(address, 'Địa chỉ phải có ít nhất 10 ký tự');
            isValid = false;
        }
    }
    
    return isValid;
}

// ==================== VALIDATION REALTIME ====================
// Tự động validate khi người dùng nhập
document.addEventListener('DOMContentLoaded', function() {
    // Email validation
    const emailInputs = document.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() !== '' && !validateEmail(this.value)) {
                showError(this, 'Email không hợp lệ');
            } else {
                clearError(this);
            }
        });
    });
    
    // Phone validation
    const phoneInputs = document.querySelectorAll('input[type="tel"], input[name="phone"]');
    phoneInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() !== '' && !validatePhone(this.value)) {
                showError(this, 'Số điện thoại không hợp lệ');
            } else {
                clearError(this);
            }
        });
    });
    
    // Number validation (price, stock)
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(input => {
        input.addEventListener('input', function() {
            if (this.value < 0) {
                this.value = 0;
            }
        });
    });
});

// Export functions để sử dụng trong các form
window.validateRegisterForm = validateRegisterForm;
window.validateProductForm = validateProductForm;
window.validateCheckoutForm = validateCheckoutForm;