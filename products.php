<?php
require 'conn.php'; // Kết nối CSDL

// 1. Lấy mã loại từ URL để lọc (nếu có)
$maloai_filter = isset($_GET['maloai']) ? (int)$_GET['maloai'] : 0;

// 2. Lấy danh sách danh mục cho Sidebar
$sql_loai = "SELECT * FROM tbl_loai";
$res_loai = $conn->query($sql_loai);

// 3. Xây dựng câu truy vấn sản phẩm
$sql_sp = "SELECT * FROM tbl_sanpham WHERE trangthai = 1";
if ($maloai_filter > 0) {
    $sql_sp .= " AND maloai = $maloai_filter";
}

// Xử lý sắp xếp (giả sử bạn truyền tham số sort qua URL)
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
switch ($sort) {
    case 'price-low': $sql_sp .= " ORDER BY gia ASC"; break;
    case 'price-high': $sql_sp .= " ORDER BY gia DESC"; break;
    case 'name': $sql_sp .= " ORDER BY tensanpham ASC"; break;
    default: $sql_sp .= " ORDER BY masanpham DESC"; break;
}

$res_sp = $conn->query($sql_sp);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="products-section">
        <div class="container">
            <div class="products-header">
                <h1 class="page-title">Sản Phẩm Gốm Sứ</h1>
                <div class="breadcrumb">
                    <a href="index.php">Trang chủ</a> / <span>Sản phẩm</span>
                </div>
            </div>

            <div class="products-layout">
                <aside class="filters-sidebar">
                    <div class="filter-group">
                        <h3 class="filter-title">Danh mục</h3>
                        <ul class="filter-list">
                            <li>
                                <a href="products.php" class="<?php echo $maloai_filter == 0 ? 'active' : ''; ?>">
                                    Tất cả sản phẩm
                                </a>
                            </li>
                            <?php while($row_loai = $res_loai->fetch_assoc()): ?>
                            <li>
                                <a href="products.php?maloai=<?php echo $row_loai['maloai']; ?>" 
                                   class="<?php echo $maloai_filter == $row_loai['maloai'] ? 'active' : ''; ?>">
                                    <?php echo $row_loai['tenloai']; ?>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                    </aside>

                <main class="products-main">
                    <div class="products-toolbar">
                        <div class="products-count">
                            Hiển thị <span><?php echo $res_sp->num_rows; ?></span> sản phẩm
                        </div>
                        <div class="view-options">
                            <select class="sort-select" onchange="location = this.value;">
                                <option value="products.php?sort=newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="products.php?sort=price-low" <?php echo $sort == 'price-low' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                                <option value="products.php?sort=price-high" <?php echo $sort == 'price-high' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                            </select>
                        </div>
                    </div>

                    <div class="products-grid" id="productsView">
                        <?php if ($res_sp->num_rows > 0): ?>
                            <?php while($product = $res_sp->fetch_assoc()): ?>
                                <div class="product-card">
                                    <div class="product-image">
                                        <img src="uploads/products/<?php echo $product['hinhAnh']; ?>" alt="<?php echo $product['tensanpham']; ?>">
                                        <div class="product-actions">
                                            <button title="Xem nhanh" class="quick-view" onclick="openQuickView(<?php echo $product['masanpham']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button title="Thêm vào giỏ" class="add-to-cart">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name">
                                            <a href="product-detail.php?id=<?php echo $product['masanpham']; ?>">
                                                <?php echo $product['tensanpham']; ?>
                                            </a>
                                        </h3>
                                        <div class="product-price">
                                            <?php echo number_format($product['gia'], 0, ',', '.'); ?> đ
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Không tìm thấy sản phẩm nào.</p>
                        <?php endif; ?>
                    </div>
                </main>
            </div>
        </div>
    </section>

    <script src="js/main.js"></script>
    <script>
        function openQuickView(id) {
            // Logic gọi AJAX lấy chi tiết sản phẩm và hiện modal
        }
    </script>
</body>
</html>