<?php  
session_start();
require 'conn.php';

// Lấy danh mục
$sql_loai = "SELECT * FROM tbl_loai LIMIT 4";
$res_loai = $conn->query($sql_loai);

// Lấy 4 sản phẩm mới nhất (hoặc nổi bật)
$sql_sp_hot = "SELECT * FROM tbl_sanpham WHERE trangthai = 1 ORDER BY masanpham DESC LIMIT 4";
$res_sp_hot = $conn->query($sql_sp_hot);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Gốm Sứ Tinh Hoa - Trang chủ</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Nét Đẹp Tinh Hoa Gốm Việt</h1>
                <p>Khám phá bộ sưu tập gốm sứ tinh xảo.</p>
                <div class="hero-buttons">
                    <a href="products.php" class="btn btn-primary">Mua sắm ngay</a>
                </div>
            </div>
        </div>
    </section>

    <section class="featured-categories">
        <div class="container">
            <h2 class="section-title">Danh Mục Nổi Bật</h2>
            
            <div class="categories-grid">
                <?php if($res_loai->num_rows > 0): ?>
                    <?php while($cat = $res_loai->fetch_assoc()): ?>
                    <div class="category-card">
                        <div class="category-img">
                            <?php 
                                // Xác định đường dẫn ảnh
                                $cat_img = 'uploads/categories/' . $cat['anh'];
                                
                                // Kiểm tra nếu không có ảnh hoặc file không tồn tại thì dùng ảnh mặc định
                                if(empty($cat['anh']) || !file_exists($cat_img)) {
                                    // Ảnh placeholder online
                                    $img_src = "https://images.unsplash.com/photo-1586444248902-2f64eddc13df?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&h=300&q=80";
                                } else {
                                    $img_src = $cat_img;
                                }
                            ?>
                            <img src="<?php echo $img_src; ?>" 
                                 alt="<?php echo htmlspecialchars($cat['tenloai']); ?>"
                                 class="category-image"
                                 onerror="this.src='https://images.unsplash.com/photo-1586444248902-2f64eddc13df?ixlib=rb-1.2.1&auto=format&fit=crop&w=300&h=300&q=80'">
                            <div class="category-overlay">
                                <a href="products.php?maloai=<?php echo $cat['maloai']; ?>" class="category-link">
                                    <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                        <div class="category-info">
                            <h3 class="category-name">
                                <a href="products.php?maloai=<?php echo $cat['maloai']; ?>">
                                    <?php echo htmlspecialchars($cat['tenloai']); ?>
                                </a>
                            </h3>
                            <?php if(!empty($cat['mota'])): ?>
                                <p class="category-desc">
                                    <?php echo mb_substr(htmlspecialchars($cat['mota']), 0, 100); ?>...
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-categories">
                        <i class="fas fa-box-open fa-3x"></i>
                        <p>Chưa có danh mục nào</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="featured-products">
        <div class="container">
            <h2 class="section-title">Sản Phẩm Mới Nhất</h2>
            <div class="products-grid">
                <?php while($row = $res_sp_hot->fetch_assoc()): ?>
                <div class="product-card">
                    <div class="product-img">
                        <img src="uploads/products/<?php echo $row['hinhAnh']; ?>" alt="<?php echo $row['tensanpham']; ?>">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="product-detail.php?id=<?php echo $row['masanpham']; ?>">
                                <?php echo $row['tensanpham']; ?>
                            </a>
                        </h3>
                        <div class="product-price">
                            <?php echo number_format($row['gia'], 0, ',', '.'); ?>₫
                        </div>
                        <div class="product-actions">
                            <button class="add-to-cart" onclick="addToCart(<?php echo $row['masanpham']; ?>)">Thêm vào giỏ</button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php include 'includes/footer.php'; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function addToCart(id) {
            $.post('api_cart.php', { action: 'add', id: id, quantity: 1 }, function(data) {
                const res = JSON.parse(data);
                if(res.status === 'success') {
                    alert(res.message);
                    // Cập nhật số lượng trên icon giỏ hàng nếu có
                }
            });
        }
    </script>
</body>
</html>