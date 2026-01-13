<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Header -->
    <?php 
    session_start();
    require 'conn.php';
    include 'header.php'; 
    
    // Kiểm tra đăng nhập
    $isLoggedIn = isset($_SESSION['user_id']);
    
    // Lấy thông tin giỏ hàng từ session
    $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
    
    // Tính toán tổng tiền
    $subtotal = 0;
    $shippingFee = 30000; // Phí vận chuyển mặc định
    $discount = 0;
    $totalItems = 0;
    
    // Lấy thông tin chi tiết sản phẩm từ database
    $cartDetails = [];
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
                'max_quantity' => $product['soluong'], // Số lượng tồn kho
                'item_total' => $itemTotal
            ];
            
            $subtotal += $itemTotal;
            $totalItems += $quantity;
        }
        
        // Miễn phí vận chuyển nếu tổng đơn > 1,000,000đ
        if ($subtotal >= 1000000) {
            $shippingFee = 0;
        }
        
        $total = $subtotal + $shippingFee - $discount;
    }
    ?>
    
    <!-- Cart Section -->
    <section class="cart-section">
        <div class="container">
            <div class="cart-header">
                <h1 class="page-title">Giỏ Hàng</h1>
                <div class="breadcrumb">
                    <a href="index.php">Trang chủ</a> / <span>Giỏ hàng</span>
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
                <div class="cart-layout">
                    <div class="cart-items">
                        <div class="cart-table-header">
                            <div class="col-product">Sản phẩm</div>
                            <div class="col-price">Giá</div>
                            <div class="col-quantity">Số lượng</div>
                            <div class="col-total">Tổng</div>
                            <div class="col-remove"></div>
                        </div>

                        <div class="cart-items-list">
                            <?php foreach($cartDetails as $item): ?>
                            <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                                <div class="col-product">
                                    <div class="product-info">
                                        <img src="uploads/products/<?php echo $item['image']; ?>" 
                                            alt="<?php echo $item['name']; ?>"
                                            onerror="this.src='images/default-product.jpg'"
                                            style="width: 80px; height: 80px; object-fit: cover; border-radius: 5px; margin-right: 15px;">
                                        <div class="product-details">
                                            <h4><?php echo $item['name']; ?></h4>
                                            <div class="product-meta">
                                                <span>Mã SP: <?php echo $item['id']; ?></span>
                                            </div>
                                            <?php if($item['quantity'] > $item['max_quantity']): ?>
                                                <div class="stock-warning">
                                                    <i class="fas fa-exclamation-triangle"></i>
                                                    Chỉ còn <?php echo $item['max_quantity']; ?> sản phẩm
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-price">
                                    <span class="price"><?php echo number_format($item['price']); ?>₫</span>
                                </div>
                                
                                <div class="col-quantity">
                                    <div class="quantity-control">
                                        <button class="qty-btn minus" onclick="updateQuantity(<?php echo $item['id']; ?>, -1)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" 
                                               class="qty-input" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" 
                                               max="<?php echo $item['max_quantity']; ?>"
                                               data-id="<?php echo $item['id']; ?>"
                                               onchange="updateQuantityInput(<?php echo $item['id']; ?>, this.value)">
                                        <button class="qty-btn plus" onclick="updateQuantity(<?php echo $item['id']; ?>, 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-total">
                                    <span class="item-total"><?php echo number_format($item['item_total']); ?>₫</span>
                                </div>
                                
                                <div class="col-remove">
                                    <button class="remove-btn" onclick="removeItem(<?php echo $item['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="cart-actions">
                            <a href="products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                            </a>
                            <button class="btn btn-secondary" onclick="clearCart()">
                                <i class="fas fa-trash"></i> Xóa giỏ hàng
                            </button>
                        </div>
                    </div>

                    <div class="cart-summary">
                        <div class="summary-card">
                            <h3 class="summary-title">Tóm tắt đơn hàng</h3>
                            
                            <div class="summary-row">
                                <span>Tạm tính:</span>
                                <span id="subtotal"><?php echo number_format($subtotal); ?>₫</span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Phí vận chuyển:</span>
                                <span id="shipping">
                                    <?php if($shippingFee == 0): ?>
                                        <span class="free-shipping">Miễn phí</span>
                                    <?php else: ?>
                                        <?php echo number_format($shippingFee); ?>₫
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="summary-row">
                                <span>Giảm giá:</span>
                                <span id="discount">-<?php echo number_format($discount); ?>₫</span>
                            </div>
                            
                            <div class="summary-divider"></div>
                            
                            <div class="summary-row total">
                                <span>Tổng cộng:</span>
                                <span id="total"><?php echo number_format($total); ?>₫</span>
                            </div>

                            <div class="coupon-section">
                                <input type="text" placeholder="Mã giảm giá" id="couponCode">
                                <button class="btn btn-primary" onclick="applyCoupon()">Áp dụng</button>
                            </div>

                            <?php if($isLoggedIn): ?>
                                <a href="checkout.php" class="btn btn-primary btn-full" id="checkoutBtn">
                                    <i class="fas fa-lock"></i> Tiến hành thanh toán
                                </a>
                            <?php else: ?>
                                <button class="btn btn-primary btn-full" onclick="alert('Vui lòng đăng nhập để thanh toán')">
                                    <i class="fas fa-lock"></i> Tiến hành thanh toán
                                </button>
                                <p class="login-required">Vui lòng <a href="login.php">đăng nhập</a> để thanh toán</p>
                            <?php endif; ?>

                            <div class="security-info">
                                <div class="security-item">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Thanh toán an toàn</span>
                                </div>
                                <div class="security-item">
                                    <i class="fas fa-truck"></i>
                                    <span>Miễn phí vận chuyển đơn từ 1.000.000₫</span>
                                </div>
                                <div class="security-item">
                                    <i class="fas fa-undo"></i>
                                    <span>Đổi trả trong 7 ngày</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
    function updateQuantity(productId, change) {
        let input = document.querySelector(`.qty-input[data-id="${productId}"]`);
        let newQty = parseInt(input.value) + change;
        let maxQty = parseInt(input.max);
        
        if (newQty < 1) newQty = 1;
        if (newQty > maxQty) {
            alert(`Chỉ còn ${maxQty} sản phẩm trong kho`);
            newQty = maxQty;
        }
        
        input.value = newQty;
        updateCart(productId, newQty);
    }
    
    function updateQuantityInput(productId, quantity) {
        let input = document.querySelector(`.qty-input[data-id="${productId}"]`);
        let maxQty = parseInt(input.max);
        
        if (quantity < 1) quantity = 1;
        if (quantity > maxQty) {
            alert(`Chỉ còn ${maxQty} sản phẩm trong kho`);
            quantity = maxQty;
            input.value = maxQty;
        }
        
        updateCart(productId, quantity);
    }
    
    function updateCart(productId, quantity) {
        $.ajax({
            url: 'api_cart.php',
            type: 'POST',
            data: {
                action: 'update',
                id: productId,
                quantity: quantity
            },
            success: function(response) {
                let result = JSON.parse(response);
                if (result.status === 'success') {
                    location.reload(); // Reload để cập nhật tổng tiền
                } else {
                    alert('Có lỗi xảy ra: ' + result.message);
                }
            }
        });
    }
    
    function removeItem(productId) {
        if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            $.ajax({
                url: 'api_cart.php',
                type: 'POST',
                data: {
                    action: 'remove',
                    id: productId
                },
                success: function(response) {
                    let result = JSON.parse(response);
                    if (result.status === 'success') {
                        location.reload();
                    }
                }
            });
        }
    }
    
    function clearCart() {
        if (confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) {
            $.ajax({
                url: 'api_cart.php',
                type: 'POST',
                data: {
                    action: 'clear'
                },
                success: function(response) {
                    let result = JSON.parse(response);
                    if (result.status === 'success') {
                        location.reload();
                    }
                }
            });
        }
    }
    
    function applyCoupon() {
        let couponCode = document.getElementById('couponCode').value;
        if (!couponCode.trim()) {
            alert('Vui lòng nhập mã giảm giá');
            return;
        }
        
        alert('Tính năng mã giảm giá đang được phát triển');
        // Thực hiện AJAX call để validate và áp dụng coupon
    }
    </script>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>