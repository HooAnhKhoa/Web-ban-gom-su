<?php
require 'conn.php'; // Kết nối CSDL
session_start();

// Kiểm tra ID sản phẩm
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: products.php");
    exit();
}

$product_id = (int)$_GET['id'];

// Lấy thông tin sản phẩm
$sql = "SELECT sp.*, l.tenloai 
        FROM tbl_sanpham sp 
        LEFT JOIN tbl_loai l ON sp.maloai = l.maloai 
        WHERE sp.masanpham = ? AND sp.trangthai = 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: products.php");
    exit();
}

$product = $result->fetch_assoc();
$stmt->close();

// Lấy sản phẩm liên quan (cùng loại)
$related_sql = "SELECT * FROM tbl_sanpham 
                WHERE maloai = ? AND masanpham != ? AND trangthai = 1 
                ORDER BY RAND() LIMIT 4";
$related_stmt = $conn->prepare($related_sql);
$related_stmt->bind_param("ii", $product['maloai'], $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();

// Tăng lượt xem sản phẩm
$view_sql = "UPDATE tbl_sanpham SET luotxem = luotxem + 1 WHERE masanpham = ?";
$view_stmt = $conn->prepare($view_sql);
$view_stmt->bind_param("i", $product_id);
$view_stmt->execute();
$view_stmt->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['tensanpham']); ?> - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/product-detail.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-hover: #1d4ed8;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --border-color: #e2e8f0;
        }
        
        .product-detail-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 80vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .breadcrumb {
            margin-bottom: 30px;
            font-size: 0.95rem;
            color: var(--secondary-color);
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .breadcrumb a:hover {
            color: var(--primary-hover);
            text-decoration: underline;
        }
        
        .product-detail-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .product-main {
            padding: 40px;
        }
        
        @media (max-width: 768px) {
            .product-main {
                padding: 20px;
            }
        }
        
        .product-gallery {
            position: relative;
            margin-bottom: 30px;
        }
        
        .main-image {
            width: 100%;
            height: 500px;
            border-radius: 15px;
            overflow: hidden;
            background: var(--light-color);
            position: relative;
        }
        
        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s;
        }
        
        .image-zoom {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            z-index: 10;
        }
        
        .image-zoom:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            overflow-x: auto;
            padding: 10px 0;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
            flex-shrink: 0;
        }
        
        .thumbnail.active {
            border-color: var(--primary-color);
        }
        
        .thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .product-info {
            padding-left: 40px;
        }
        
        @media (max-width: 992px) {
            .product-info {
                padding-left: 0;
                margin-top: 30px;
            }
        }
        
        .product-category {
            display: inline-block;
            background: var(--light-color);
            color: var(--primary-color);
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-bottom: 15px;
            font-weight: 500;
        }
        
        .product-title {
            font-size: 2.2rem;
            color: var(--dark-color);
            margin-bottom: 20px;
            line-height: 1.3;
            font-weight: 700;
        }
        
        .product-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #fbbf24;
        }
        
        .rating-count {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-left: 10px;
        }
        
        .view-count {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .view-count i {
            margin-right: 5px;
        }
        
        .product-price {
            margin-bottom: 25px;
        }
        
        .current-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-right: 15px;
        }
        
        .product-description {
            margin-bottom: 30px;
            line-height: 1.8;
            color: var(--dark-color);
        }
        
        .product-specs {
            margin-bottom: 30px;
        }
        
        .specs-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        
        .specs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        .spec-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: var(--light-color);
            border-radius: 10px;
        }
        
        .spec-icon {
            margin-right: 10px;
            color: var(--primary-color);
            width: 20px;
            text-align: center;
        }
        
        .spec-label {
            font-weight: 500;
            color: var(--dark-color);
            min-width: 100px;
        }
        
        .spec-value {
            color: var(--secondary-color);
        }
        
        .product-actions {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            overflow: hidden;
            height: 50px;
        }
        
        .quantity-btn {
            width: 50px;
            height: 100%;
            border: none;
            background: var(--light-color);
            color: var(--dark-color);
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .quantity-btn:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .quantity-input {
            width: 60px;
            height: 100%;
            border: none;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .quantity-input:focus {
            outline: none;
        }
        
        .add-to-cart-btn, .buy-now-btn {
            height: 50px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 0 30px;
            flex: 1;
            min-width: 200px;
        }
        
        .add-to-cart-btn {
            background: var(--primary-color);
            color: white;
        }
        
        .add-to-cart-btn:hover {
            background: var(--primary-hover);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2);
        }
        
        .buy-now-btn {
            background: var(--success-color);
            color: white;
        }
        
        .buy-now-btn:hover {
            background: #0ea271;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
        }
        
        .wishlist-btn {
            width: 50px;
            height: 50px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: white;
            color: var(--dark-color);
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wishlist-btn:hover, .wishlist-btn.active {
            border-color: var(--danger-color);
            color: var(--danger-color);
            background: #fef2f2;
        }
        
        .related-products {
            margin-top: 60px;
            padding: 40px;
            border-top: 2px solid var(--border-color);
        }
        
        @media (max-width: 768px) {
            .related-products {
                padding: 20px;
            }
        }
        
        .section-title {
            font-size: 1.8rem;
            color: var(--dark-color);
            margin-bottom: 30px;
            text-align: center;
            font-weight: 700;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }
        
        .related-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .related-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .related-image {
            height: 180px;
            overflow: hidden;
        }
        
        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .related-card:hover .related-image img {
            transform: scale(1.05);
        }
        
        .related-info {
            padding: 20px;
        }
        
        .related-name {
            font-size: 1.1rem;
            color: var(--dark-color);
            margin-bottom: 10px;
            line-height: 1.4;
        }
        
        .related-name a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .related-name a:hover {
            color: var(--primary-color);
        }
        
        .related-price {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-image {
            max-width: 90%;
            max-height: 90%;
            object-fit: contain;
        }
        
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
        }
        
        .stock-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding: 10px 15px;
            background: #f0f9ff;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
        }
        
        .stock-info.in-stock {
            background: #f0fdf4;
            border-left-color: var(--success-color);
        }
        
        .stock-info.low-stock {
            background: #fef3c7;
            border-left-color: var(--warning-color);
        }
        
        .stock-info.out-of-stock {
            background: #fef2f2;
            border-left-color: var(--danger-color);
        }
        
        .stock-label {
            font-weight: 600;
            color: var(--dark-color);
        }
        
        .stock-value {
            color: var(--secondary-color);
        }
        
        .social-share {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--border-color);
        }
        
        .share-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        .share-buttons {
            display: flex;
            gap: 10px;
        }
        
        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: transform 0.3s;
        }
        
        .share-btn:hover {
            transform: scale(1.1);
        }
        
        .share-facebook { background: #1877f2; }
        .share-twitter { background: #1da1f2; }
        .share-pinterest { background: #e60023; }
        .share-zalo { background: #0068ff; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="product-detail-section">
        <div class="container">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="index.php">Trang chủ</a> / 
                <a href="products.php">Sản phẩm</a> / 
                <?php if (!empty($product['tenloai'])): ?>
                    <a href="products.php?maloai=<?php echo $product['maloai']; ?>"><?php echo htmlspecialchars($product['tenloai']); ?></a> / 
                <?php endif; ?>
                <span><?php echo htmlspecialchars($product['tensanpham']); ?></span>
            </div>

            <div class="product-detail-container">
                <!-- Thông tin chính sản phẩm -->
                <div class="product-main">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Hình ảnh sản phẩm -->
                            <div class="product-gallery">
                                <div class="main-image" id="mainImage">
                                    <img src="uploads/products/<?php echo htmlspecialchars($product['hinhAnh']); ?>" 
                                         alt="<?php echo htmlspecialchars($product['tensanpham']); ?>"
                                         id="currentImage"
                                         onerror="this.src='images/default-product.jpg'">
                                    <button class="image-zoom" onclick="openImageZoom()">
                                        <i class="fas fa-search-plus"></i>
                                    </button>
                                </div>
                                
                                <!-- Thumbnails (nếu có nhiều ảnh) -->
                                <div class="thumbnails">
                                    <div class="thumbnail active" onclick="changeImage('uploads/products/<?php echo htmlspecialchars($product['hinhAnh']); ?>')">
                                        <img src="uploads/products/<?php echo htmlspecialchars($product['hinhAnh']); ?>" 
                                             alt="Ảnh chính"
                                             onerror="this.src='images/default-product.jpg'">
                                    </div>
                                    <!-- Có thể thêm các ảnh phụ ở đây nếu có -->
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <!-- Thông tin sản phẩm -->
                            <div class="product-info">
                                <?php if (!empty($product['tenloai'])): ?>
                                    <div class="product-category">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['tenloai']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <h1 class="product-title"><?php echo htmlspecialchars($product['tensanpham']); ?></h1>
                                
                                <div class="product-meta">
                                    <div class="rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star-half-alt"></i>
                                        <span class="rating-count">(4.5/5)</span>
                                    </div>
                                    <div class="view-count">
                                        <i class="fas fa-eye"></i> <?php echo ($product['luotxem'] ?? 0) + 1; ?> lượt xem
                                    </div>
                                </div>
                                
                                <!-- Giá sản phẩm -->
                                <div class="product-price">
                                    <span class="current-price"><?php echo number_format($product['gia'], 0, ',', '.'); ?> đ</span>
                                </div>
                                
                                <!-- Thông tin tồn kho -->
                                <div class="stock-info in-stock">
                                    <i class="fas fa-check-circle"></i>
                                    <span class="stock-label">Tình trạng:</span>
                                    <span class="stock-value">Còn hàng - Giao hàng trong 24h</span>
                                </div>
                                
                                <!-- Mô tả ngắn -->
                                <div class="product-description">
                                    <?php echo nl2br(htmlspecialchars($product['mota'] ?? 'Sản phẩm chất lượng cao, thiết kế tinh xảo')); ?>
                                </div>
                                
                                <!-- Thông số kỹ thuật -->
                                <div class="product-specs">
                                    <h3 class="specs-title">Thông số sản phẩm</h3>
                                    <div class="specs-grid">
                                        <?php if (!empty($product['xuatxu'])): ?>
                                        <div class="spec-item">
                                            <div class="spec-icon"><i class="fas fa-map-marker-alt"></i></div>
                                            <div class="spec-label">Xuất xứ:</div>
                                            <div class="spec-value"><?php echo htmlspecialchars($product['xuatxu']); ?></div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($product['chatlieu'])): ?>
                                        <div class="spec-item">
                                            <div class="spec-icon"><i class="fas fa-cube"></i></div>
                                            <div class="spec-label">Chất liệu:</div>
                                            <div class="spec-value"><?php echo htmlspecialchars($product['chatlieu']); ?></div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($product['kichthuoc'])): ?>
                                        <div class="spec-item">
                                            <div class="spec-icon"><i class="fas fa-ruler-combined"></i></div>
                                            <div class="spec-label">Kích thước:</div>
                                            <div class="spec-value"><?php echo htmlspecialchars($product['kichthuoc']); ?></div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="spec-item">
                                            <div class="spec-icon"><i class="fas fa-weight-hanging"></i></div>
                                            <div class="spec-label">Trọng lượng:</div>
                                            <div class="spec-value">~1.5 kg</div>
                                        </div>
                                        
                                        <div class="spec-item">
                                            <div class="spec-icon"><i class="fas fa-palette"></i></div>
                                            <div class="spec-label">Màu sắc:</div>
                                            <div class="spec-value">Trắng ngà, Xanh ngọc</div>
                                        </div>
                                        
                                        <div class="spec-item">
                                            <div class="spec-icon"><i class="fas fa-shield-alt"></i></div>
                                            <div class="spec-label">Bảo hành:</div>
                                            <div class="spec-value">12 tháng</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Hành động mua hàng -->
                                <div class="product-actions">
                                    <div class="quantity-selector">
                                        <button class="quantity-btn" onclick="decreaseQuantity()">-</button>
                                        <input type="number" class="quantity-input" id="quantity" value="1" min="1" max="99" readonly>
                                        <button class="quantity-btn" onclick="increaseQuantity()">+</button>
                                    </div>
                                    
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product_id; ?>)">
                                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                                    </button>
                                    
                                    <button class="buy-now-btn" onclick="buyNow(<?php echo $product_id; ?>)">
                                        <i class="fas fa-bolt"></i> Mua ngay
                                    </button>
                                    
                                    <button class="wishlist-btn" id="wishlistBtn" onclick="toggleWishlist(<?php echo $product_id; ?>)">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                                
                                <!-- Chia sẻ mạng xã hội -->
                                <div class="social-share">
                                    <span class="share-label">Chia sẻ:</span>
                                    <div class="share-buttons">
                                        <a href="#" class="share-btn share-facebook" onclick="shareOnFacebook()">
                                            <i class="fab fa-facebook-f"></i>
                                        </a>
                                        <a href="#" class="share-btn share-twitter" onclick="shareOnTwitter()">
                                            <i class="fab fa-twitter"></i>
                                        </a>
                                        <a href="#" class="share-btn share-pinterest" onclick="shareOnPinterest()">
                                            <i class="fab fa-pinterest-p"></i>
                                        </a>
                                        <a href="#" class="share-btn share-zalo" onclick="shareOnZalo()">
                                            <i class="fab fa-facebook-messenger"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sản phẩm liên quan -->
                <?php if ($related_result->num_rows > 0): ?>
                <div class="related-products">
                    <h2 class="section-title">Sản Phẩm Liên Quan</h2>
                    <div class="related-grid">
                        <?php while($related = $related_result->fetch_assoc()): ?>
                        <div class="related-card">
                            <div class="related-image">
                                <a href="product-detail.php?id=<?php echo $related['masanpham']; ?>">
                                    <img src="uploads/products/<?php echo htmlspecialchars($related['hinhAnh']); ?>" 
                                         alt="<?php echo htmlspecialchars($related['tensanpham']); ?>"
                                         onerror="this.src='images/default-product.jpg'">
                                </a>
                            </div>
                            <div class="related-info">
                                <h3 class="related-name">
                                    <a href="product-detail.php?id=<?php echo $related['masanpham']; ?>">
                                        <?php echo htmlspecialchars($related['tensanpham']); ?>
                                    </a>
                                </h3>
                                <div class="related-price">
                                    <?php echo number_format($related['gia'], 0, ',', '.'); ?> đ
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Modal zoom ảnh -->
    <div class="modal-overlay" id="imageModal" onclick="closeImageZoom()">
        <button class="modal-close" onclick="closeImageZoom()">&times;</button>
        <img class="modal-image" id="modalImage" src="" alt="">
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Xử lý số lượng
    let quantity = 1;
    
    function increaseQuantity() {
        if (quantity < 99) {
            quantity++;
            document.getElementById('quantity').value = quantity;
        }
    }
    
    function decreaseQuantity() {
        if (quantity > 1) {
            quantity--;
            document.getElementById('quantity').value = quantity;
        }
    }
    
    // Thêm vào giỏ hàng
    function addToCart(productId) {
        const qty = document.getElementById('quantity').value;
        
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=' + qty
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Đã thêm ' + qty + ' sản phẩm vào giỏ hàng',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true,
                    background: '#f0f9ff',
                    iconColor: '#2563eb'
                });
                
                // Cập nhật số lượng giỏ hàng
                if (data.cart_count !== undefined) {
                    updateCartCount(data.cart_count);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data.message || 'Không thể thêm sản phẩm vào giỏ hàng',
                    confirmButtonColor: '#ef4444'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi!',
                text: 'Đã xảy ra lỗi khi kết nối với máy chủ'
            });
        });
    }
    
    // Mua ngay
    function buyNow(productId) {
        const qty = document.getElementById('quantity').value;
        
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=' + qty
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Chuyển đến trang giỏ hàng
                window.location.href = 'cart.php';
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data.message || 'Không thể thêm sản phẩm vào giỏ hàng'
                });
            }
        });
    }
    
    // Yêu thích sản phẩm
    function toggleWishlist(productId) {
        const wishlistBtn = document.getElementById('wishlistBtn');
        const isActive = wishlistBtn.classList.contains('active');
        
        if (!isActive) {
            // Thêm vào danh sách yêu thích
            fetch('wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    wishlistBtn.classList.add('active');
                    wishlistBtn.innerHTML = '<i class="fas fa-heart"></i>';
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã thêm vào yêu thích!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        } else {
            // Xóa khỏi danh sách yêu thích
            fetch('wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=remove&product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    wishlistBtn.classList.remove('active');
                    wishlistBtn.innerHTML = '<i class="far fa-heart"></i>';
                    Swal.fire({
                        icon: 'info',
                        title: 'Đã xóa khỏi yêu thích!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            });
        }
    }
    
    // Zoom ảnh
    function openImageZoom() {
        const currentImage = document.getElementById('currentImage').src;
        document.getElementById('modalImage').src = currentImage;
        document.getElementById('imageModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeImageZoom() {
        document.getElementById('imageModal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }
    
    // Thay đổi ảnh chính
    function changeImage(src) {
        document.getElementById('currentImage').src = src;
        // Cập nhật thumbnail active
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        event.target.closest('.thumbnail').classList.add('active');
    }
    
    // Chia sẻ mạng xã hội
    function shareOnFacebook() {
        const url = encodeURIComponent(window.location.href);
        const title = encodeURIComponent(document.title);
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${title}`, '_blank');
    }
    
    function shareOnTwitter() {
        const url = encodeURIComponent(window.location.href);
        const text = encodeURIComponent(document.title);
        window.open(`https://twitter.com/intent/tweet?url=${url}&text=${text}`, '_blank');
    }
    
    function shareOnPinterest() {
        const url = encodeURIComponent(window.location.href);
        const media = encodeURIComponent(document.getElementById('currentImage').src);
        const description = encodeURIComponent(document.title);
        window.open(`https://pinterest.com/pin/create/button/?url=${url}&media=${media}&description=${description}`, '_blank');
    }
    
    function shareOnZalo() {
        const url = encodeURIComponent(window.location.href);
        window.open(`https://zalo.me/share?url=${url}`, '_blank');
    }
    
    // Cập nhật số lượng giỏ hàng
    function updateCartCount(count) {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            cartCountElement.style.display = count > 0 ? 'flex' : 'none';
        }
    }
    
    // Kiểm tra sản phẩm yêu thích
    document.addEventListener('DOMContentLoaded', function() {
        // Kiểm tra nếu sản phẩm đã được yêu thích
        fetch('wishlist.php?action=check&product_id=<?php echo $product_id; ?>')
        .then(response => response.json())
        .then(data => {
            if (data.is_wishlisted) {
                const wishlistBtn = document.getElementById('wishlistBtn');
                wishlistBtn.classList.add('active');
                wishlistBtn.innerHTML = '<i class="fas fa-heart"></i>';
            }
        });
        
        // Đóng modal bằng phím ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeImageZoom();
            }
        });
    });
    </script>
</body>
</html>

<?php
// Đóng kết nối
$related_stmt->close();
$conn->close();
?>