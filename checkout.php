<?php
session_start();
require 'conn.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout');
    exit();
}

$user_id = $_SESSION['user_id'];

// Kiểm tra giỏ hàng
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

// Lấy thông tin người dùng
$user_sql = "SELECT * FROM tbl_nguoidung WHERE manguoidung = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Lấy thông tin địa chỉ giao hàng nếu có
$address_sql = "SELECT * FROM tbl_thongtiniaoahang WHERE manguoidung = ? LIMIT 1";
$address_stmt = $conn->prepare($address_sql);
$address_stmt->bind_param("i", $user_id);
$address_stmt->execute();
$default_address = $address_stmt->get_result()->fetch_assoc();

// Tính toán giỏ hàng
$cart = $_SESSION['cart'];
$cartDetails = [];
$subtotal = 0;
$shippingFee = 30000;
$discount = 0;
$total = 0;

if (!empty($cart)) {
    $productIds = array_keys($cart);
    $placeholders = str_repeat('?,', count($productIds) - 1) . '?';
    $sql = "SELECT * FROM tbl_sanpham WHERE masanpham IN ($placeholders) AND trangthai = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while($product = $result->fetch_assoc()) {
        $productId = $product['masanpham'];
        $quantity = $cart[$productId]['quantity'] ?? $cart[$productId] ?? 1;
        $price = $product['gia'];
        $itemTotal = $price * $quantity;
        
        $cartDetails[$productId] = [
            'id' => $productId,
            'name' => $product['tensanpham'],
            'price' => $price,
            'image' => $product['hinhAnh'],
            'quantity' => $quantity,
            'max_quantity' => $product['soluong'] ?? 100,
            'item_total' => $itemTotal
        ];
        
        $subtotal += $itemTotal;
    }
    
    // Miễn phí vận chuyển nếu tổng đơn > 1,000,000đ
    if ($subtotal >= 1000000) {
        $shippingFee = 0;
    }
    
    $total = $subtotal + $shippingFee - $discount;
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['fullName'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $district = $_POST['district'];
    $notes = $_POST['notes'] ?? '';
    $paymentMethod = $_POST['paymentMethod'];
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // 1. Lưu thông tin giao hàng
        $shipping_sql = "INSERT INTO tbl_thongtiniaoahang 
                        (manguoidung, hotennguoinhan, sodienthoai, diachi, ghichu, email, tinhthanh, quanhuyen) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $shipping_stmt = $conn->prepare($shipping_sql);
        $shipping_stmt->bind_param("isssssss", $user_id, $fullName, $phone, $address, $notes, $email, $city, $district);
        $shipping_stmt->execute();
        $shipping_id = $conn->insert_id;
        
        // 2. Tạo hóa đơn CHÍNH (1 dòng duy nhất cho đơn hàng)
        // Đầu tiên tìm mã hóa đơn tiếp theo
        $get_next_invoice_sql = "SELECT COALESCE(MAX(mahoadon), 0) + 1 as next_id FROM tbl_hoadon";
        $next_invoice_result = $conn->query($get_next_invoice_sql);
        $next_invoice = $next_invoice_result->fetch_assoc()['next_id'];
        
        // Tạo hóa đơn chính (lưu tổng tiền)
        $invoice_sql = "INSERT INTO tbl_hoadon 
                       (mahoadon, manguoidung, soluong, thanhtien, ngaydat) 
                       VALUES (?, ?, ?, ?, NOW())";
        $invoice_stmt = $conn->prepare($invoice_sql);
        
        // Tính tổng số lượng sản phẩm
        $totalQuantity = array_sum(array_column($cartDetails, 'quantity'));
        
        $invoice_stmt->bind_param("iiid", $next_invoice, $user_id, $totalQuantity, $total);
        $invoice_stmt->execute();
        
        // 3. Tạo đơn hàng (liên kết hóa đơn và địa chỉ giao)
        $order_sql = "INSERT INTO tbl_donhang 
                     (mahoadon, mathongtiniaoahang, trangthai, hinhthucthanhtoan, tongtien, phivanchuyen) 
                     VALUES (?, ?, 'cho_xac_nhan', ?, ?, ?)";
        $order_stmt = $conn->prepare($order_sql);
        $order_stmt->bind_param("iisdd", $next_invoice, $shipping_id, $paymentMethod, $subtotal, $shippingFee);
        $order_stmt->execute();
        $order_id = $conn->insert_id;
        
        // 4. Tạo bảng chi tiết hóa đơn tạm thời (vì không có trong database)
        // Tạo bảng nếu chưa tồn tại
        $create_detail_table_sql = "CREATE TABLE IF NOT EXISTS tbl_chitiethoadon (
            id INT PRIMARY KEY AUTO_INCREMENT,
            mahoadon INT NOT NULL,
            masanpham INT NOT NULL,
            tensanpham VARCHAR(255),
            soluong INT NOT NULL,
            dongia DECIMAL(10,2) NOT NULL,
            thanhtien DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (mahoadon) REFERENCES tbl_hoadon(mahoadon),
            FOREIGN KEY (masanpham) REFERENCES tbl_sanpham(masanpham)
        )";
        $conn->query($create_detail_table_sql);
        
        // 5. Lưu chi tiết sản phẩm vào bảng mới
        foreach ($cartDetails as $item) {
            // Lưu chi tiết hóa đơn
            $detail_sql = "INSERT INTO tbl_chitiethoadon 
                          (mahoadon, masanpham, tensanpham, soluong, dongia, thanhtien) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $detail_stmt = $conn->prepare($detail_sql);
            $detail_stmt->bind_param("iisidd", $next_invoice, $item['id'], $item['name'], $item['quantity'], $item['price'], $item['item_total']);
            $detail_stmt->execute();
            
            // Cập nhật tồn kho
            $update_stock_sql = "UPDATE tbl_sanpham 
                                SET soluong = soluong - ? 
                                WHERE masanpham = ? AND soluong >= ?";
            $update_stock_stmt = $conn->prepare($update_stock_sql);
            $update_stock_stmt->bind_param("iii", $item['quantity'], $item['id'], $item['quantity']);
            $update_stock_stmt->execute();
            
            if ($update_stock_stmt->affected_rows === 0) {
                throw new Exception("Sản phẩm '" . $item['name'] . "' không đủ hàng trong kho");
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Xóa giỏ hàng session
        unset($_SESSION['cart']);
        
        // Trả về JSON success
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Đặt hàng thành công!',
            'order_id' => $order_id,
            'invoice_id' => $next_invoice,
            'order_code' => 'DH' . str_pad($order_id, 6, '0', STR_PAD_LEFT)
        ]);
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        $conn->rollback();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/checkout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <section class="checkout-section">
        <div class="container">
            <div class="checkout-header">
                <h1 class="page-title">Thanh Toán</h1>
                <div class="breadcrumb">
                    <a href="index.php">Trang chủ</a> / 
                    <a href="cart.php">Giỏ hàng</a> / 
                    <span>Thanh toán</span>
                </div>
            </div>

            <?php if (empty($cartDetails)): ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h2>Giỏ hàng của bạn đang trống</h2>
                    <p>Hãy thêm sản phẩm vào giỏ hàng để bắt đầu mua sắm</p>
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                    </a>
                </div>
            <?php else: ?>
                <div class="checkout-layout">
                    <div class="checkout-form">
                        <form id="checkoutForm" method="POST">
                            <!-- Shipping Information -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-truck"></i>
                                    Thông tin giao hàng
                                </h3>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="fullName">Họ và tên *</label>
                                        <input type="text" id="fullName" name="fullName" required 
                                               value="<?php echo isset($default_address['hotennguoinhan']) ? htmlspecialchars($default_address['hotennguoinhan']) : htmlspecialchars($user['hotennguoidung'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" id="email" name="email" required
                                               value="<?php echo htmlspecialchars($user['tendangnhap'] ?? ''); ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="phone">Số điện thoại *</label>
                                        <input type="tel" id="phone" name="phone" required
                                               value="<?php echo isset($default_address['sodienthoai']) ? htmlspecialchars($default_address['sodienthoai']) : htmlspecialchars($user['sodienthoai'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="address">Địa chỉ *</label>
                                    <input type="text" id="address" name="address" required
                                           value="<?php echo isset($default_address['diachi']) ? htmlspecialchars($default_address['diachi']) : ''; ?>">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="city">Tỉnh/Thành phố *</label>
                                        <select id="city" name="city" required>
                                            <option value="">Chọn tỉnh/thành phố</option>
                                            <option value="Hà Nội" <?php echo (isset($default_address['tinhthanh']) && $default_address['tinhthanh'] == 'Hà Nội') ? 'selected' : ''; ?>>Hà Nội</option>
                                            <option value="TP. Hồ Chí Minh" <?php echo (isset($default_address['tinhthanh']) && $default_address['tinhthanh'] == 'TP. Hồ Chí Minh') ? 'selected' : ''; ?>>TP. Hồ Chí Minh</option>
                                            <option value="Đà Nẵng" <?php echo (isset($default_address['tinhthanh']) && $default_address['tinhthanh'] == 'Đà Nẵng') ? 'selected' : ''; ?>>Đà Nẵng</option>
                                            <option value="Hải Phòng">Hải Phòng</option>
                                            <option value="Cần Thơ">Cần Thơ</option>
                                            <option value="Khác">Khác</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="district">Quận/Huyện *</label>
                                        <input type="text" id="district" name="district" required
                                               value="<?php echo isset($default_address['quanhuyen']) ? htmlspecialchars($default_address['quanhuyen']) : (isset($default_address['ghichu']) ? htmlspecialchars($default_address['ghichu']) : ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="notes">Ghi chú đơn hàng (tùy chọn)</label>
                                    <textarea id="notes" name="notes" rows="3" placeholder="Ghi chú về đơn hàng, ví dụ: thời gian giao hàng..."><?php echo isset($default_address['ghichu']) ? htmlspecialchars($default_address['ghichu']) : ''; ?></textarea>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-credit-card"></i>
                                    Phương thức thanh toán
                                </h3>
                                
                                <div class="payment-methods">
                                    <div class="payment-method">
                                        <input type="radio" id="cod" name="paymentMethod" value="cod" checked>
                                        <label for="cod">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>Thanh toán khi nhận hàng (COD)</span>
                                        </label>
                                    </div>
                                    
                                    <div class="payment-method">
                                        <input type="radio" id="banking" name="paymentMethod" value="banking">
                                        <label for="banking">
                                            <i class="fas fa-university"></i>
                                            <span>Chuyển khoản ngân hàng</span>
                                        </label>
                                    </div>
                                    
                                    <div class="payment-method">
                                        <input type="radio" id="momo" name="paymentMethod" value="momo">
                                        <label for="momo">
                                            <i class="fas fa-mobile-alt"></i>
                                            <span>Ví điện tử MoMo</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="payment-info" id="bankingInfo" style="display: none;">
                                    <div class="bank-info">
                                        <h4>Thông tin chuyển khoản</h4>
                                        <div class="bank-details">
                                            <div class="bank-detail">
                                                <span>Ngân hàng:</span>
                                                <strong>Vietcombank</strong>
                                            </div>
                                            <div class="bank-detail">
                                                <span>Số tài khoản:</span>
                                                <strong>0123456789</strong>
                                            </div>
                                            <div class="bank-detail">
                                                <span>Chủ tài khoản:</span>
                                                <strong>CÔNG TY TNHH GỐM SỨ TINH HOA</strong>
                                            </div>
                                            <div class="bank-detail">
                                                <span>Nội dung chuyển khoản:</span>
                                                <strong id="transferContent">Mã đơn hàng + Số điện thoại</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <a href="cart.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitOrder">
                                    <i class="fas fa-lock"></i> Hoàn tất đơn hàng
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="order-summary">
                        <div class="summary-card">
                            <h3 class="summary-title">Đơn hàng của bạn</h3>
                            
                            <div class="order-items">
                                <?php foreach($cartDetails as $item): ?>
                                <div class="order-item">
                                    <div class="item-image">
                                        <img src="uploads/products/<?php echo $item['image']; ?>" 
                                            alt="<?php echo $item['name']; ?>"
                                            onerror="this.src='images/default-product.jpg'"
                                            style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px; margin-right: 15px;">
                                    </div>
                                    <div class="item-info">
                                        <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                        <div class="item-meta">
                                            <span><?php echo number_format($item['price']); ?>₫ × <?php echo $item['quantity']; ?></span>
                                        </div>
                                    </div>
                                    <div class="item-total">
                                        <?php echo number_format($item['item_total']); ?>₫
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="summary-details">
                                <div class="summary-row">
                                    <span>Tạm tính:</span>
                                    <span id="orderSubtotal"><?php echo number_format($subtotal); ?>₫</span>
                                </div>
                                
                                <div class="summary-row">
                                    <span>Phí vận chuyển:</span>
                                    <span id="orderShipping">
                                        <?php if($shippingFee == 0): ?>
                                            <span class="free-shipping">Miễn phí</span>
                                        <?php else: ?>
                                            <?php echo number_format($shippingFee); ?>₫
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="summary-row">
                                    <span>Giảm giá:</span>
                                    <span id="orderDiscount">-<?php echo number_format($discount); ?>₫</span>
                                </div>
                                
                                <div class="summary-divider"></div>
                                
                                <div class="summary-row total">
                                    <span>Tổng cộng:</span>
                                    <span id="orderTotal"><?php echo number_format($total); ?>₫</span>
                                </div>
                            </div>
                            
                            <div class="security-badges">
                                <div class="security-badge">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Bảo mật SSL</span>
                                </div>
                                <div class="security-badge">
                                    <i class="fas fa-lock"></i>
                                    <span>Thanh toán an toàn</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Order Success Modal -->
    <div class="modal" id="orderSuccessModal" style="display: none;">
        <div class="modal-content success-modal">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Đặt hàng thành công!</h2>
            <p class="success-message">Cảm ơn bạn đã đặt hàng. Mã đơn hàng của bạn là <strong id="orderNumber">#DH<?php echo str_pad(time() % 1000000, 6, '0', STR_PAD_LEFT); ?></strong></p>
            <p class="success-details">Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất để xác nhận đơn hàng.</p>
            <div class="success-actions">
                <a href="index.php" class="btn btn-primary">Tiếp tục mua sắm</a>
                <a href="profile.php?tab=orders" class="btn btn-secondary">Theo dõi đơn hàng</a>
            </div>
        </div>
    </div>

    // Thay thế các alert bằng SweetAlert2
<script>
$(document).ready(function() {
    // Xử lý hiển thị thông tin chuyển khoản
    $('input[name="paymentMethod"]').change(function() {
        if ($(this).val() === 'banking') {
            $('#bankingInfo').show();
        } else {
            $('#bankingInfo').hide();
        }
    });
    
    // Xử lý submit form
    $('#checkoutForm').submit(function(e) {
        e.preventDefault();
        
        // Validate số điện thoại
        var phone = $('#phone').val();
        var phoneRegex = /^(0[0-9]{9,10})$/;
        if (!phoneRegex.test(phone)) {
            Swal.fire({
                icon: 'warning',
                title: 'Số điện thoại không hợp lệ',
                text: 'Vui lòng nhập số điện thoại hợp lệ (10-11 số, bắt đầu bằng 0)',
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Đã hiểu'
            });
            return false;
        }
        
        // Disable nút submit
        $('#submitOrder').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Đang xử lý...');
        
        $.ajax({
            url: 'checkout.php',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        title: 'Đặt hàng thành công!',
                        html: `Cảm ơn bạn đã đặt hàng.<br>Mã đơn hàng: <strong>${response.order_code}</strong>`,
                        icon: 'success',
                        confirmButtonText: 'Xem đơn hàng',
                        showCancelButton: true,
                        cancelButtonText: 'Tiếp tục mua sắm',
                        confirmButtonColor: '#10b981',
                        cancelButtonColor: '#64748b',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'profile.php?tab=orders';
                        } else {
                            window.location.href = 'index.php';
                        }
                    });
                    
                    // Xóa giỏ hàng hiển thị (nếu cần)
                    updateCartCount(0);
                    
                } else {
                    $('#submitOrder').prop('disabled', false).html('<i class="fas fa-lock"></i> Hoàn tất đơn hàng');
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Đặt hàng thất bại',
                        text: response.message,
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: 'Thử lại'
                    });
                }
            },
            error: function() {
                $('#submitOrder').prop('disabled', false).html('<i class="fas fa-lock"></i> Hoàn tất đơn hàng');
                
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi kết nối',
                    text: 'Có lỗi xảy ra khi xử lý đơn hàng. Vui lòng thử lại sau.',
                    confirmButtonColor: '#ef4444',
                    confirmButtonText: 'Đã hiểu'
                });
            }
        });
    });
    
    // Cập nhật số lượng giỏ hàng
    function updateCartCount(count) {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            cartCountElement.style.display = count > 0 ? 'flex' : 'none';
        }
    }
});
</script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>