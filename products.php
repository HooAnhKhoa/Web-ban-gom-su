<?php
require 'conn.php'; // Kết nối CSDL
session_start();

// 1. Lấy mã loại từ URL để lọc (nếu có)
$maloai_filter = isset($_GET['maloai']) ? (int)$_GET['maloai'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$price_range = isset($_GET['price']) ? $_GET['price'] : '';

// 2. Lấy danh sách danh mục cho Sidebar
$sql_loai = "SELECT l.*, 
             (SELECT COUNT(*) FROM tbl_sanpham WHERE maloai = l.maloai AND trangthai = 1) as product_count 
             FROM tbl_loai l 
             WHERE EXISTS (SELECT 1 FROM tbl_sanpham WHERE maloai = l.maloai AND trangthai = 1)
             ORDER BY l.tenloai";
$res_loai = $conn->query($sql_loai);

// 3. Xây dựng câu truy vấn sản phẩm với phân trang
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Điều kiện cơ bản
$where_conditions = ["sp.trangthai = 1"];
$params = [];
$types = "";

// Lọc theo danh mục
if ($maloai_filter > 0) {
    $where_conditions[] = "sp.maloai = ?";
    $params[] = $maloai_filter;
    $types .= "i";
}

// Tìm kiếm theo tên
if (!empty($search)) {
    $where_conditions[] = "(sp.tensanpham LIKE ? OR sp.mota LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}

// Lọc theo khoảng giá
if (!empty($price_range)) {
    switch ($price_range) {
        case 'under-1m':
            $where_conditions[] = "sp.gia < 1000000";
            break;
        case '1m-3m':
            $where_conditions[] = "sp.gia BETWEEN 1000000 AND 3000000";
            break;
        case '3m-5m':
            $where_conditions[] = "sp.gia BETWEEN 3000000 AND 5000000";
            break;
        case 'over-5m':
            $where_conditions[] = "sp.gia > 5000000";
            break;
    }
}

// Xây dựng WHERE clause
$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Đếm tổng số sản phẩm
$count_sql = "SELECT COUNT(*) as total FROM tbl_sanpham sp $where_clause";
$count_stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_products = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_products / $items_per_page);
$count_stmt->close();

// Xử lý sắp xếp
$order_by = "ORDER BY sp.masanpham DESC";
switch ($sort) {
    case 'price-low': $order_by = "ORDER BY sp.gia ASC"; break;
    case 'price-high': $order_by = "ORDER BY sp.gia DESC"; break;
    case 'name-asc': $order_by = "ORDER BY sp.tensanpham ASC"; break;
    case 'name-desc': $order_by = "ORDER BY sp.tensanpham DESC"; break;
    default: $order_by = "ORDER BY sp.masanpham DESC"; break;
}

// Truy vấn sản phẩm với phân trang
$sql_sp = "SELECT sp.*, l.tenloai 
           FROM tbl_sanpham sp 
           LEFT JOIN tbl_loai l ON sp.maloai = l.maloai 
           $where_clause 
           $order_by 
           LIMIT ? OFFSET ?";

$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sql_sp);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res_sp = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $maloai_filter > 0 ? $current_category['tenloai'] . ' - ' : ''; ?>Sản phẩm - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/products.css">
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
        
        .products-section {
            padding: 60px 0;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 80vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .products-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .page-title {
            font-size: 2.5rem;
            color: var(--dark-color);
            margin-bottom: 15px;
            font-weight: 700;
            position: relative;
            display: inline-block;
        }
        
        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        .breadcrumb {
            font-size: 0.95rem;
            color: var(--secondary-color);
            margin-top: 25px;
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
        
        .products-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .products-layout {
                grid-template-columns: 1fr;
            }
        }
        
        .filters-sidebar {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .filter-group {
            margin-bottom: 30px;
        }
        
        .filter-group:last-child {
            margin-bottom: 0;
        }
        
        .filter-title {
            font-size: 1.2rem;
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
        }
        
        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .filter-list li {
            margin-bottom: 10px;
        }
        
        .filter-list a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 15px;
            background: var(--light-color);
            border-radius: 8px;
            color: var(--dark-color);
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid transparent;
        }
        
        .filter-list a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }
        
        .filter-list a.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            font-weight: 500;
        }
        
        .category-count {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
        }
        
        .price-filter {
            margin-top: 10px;
        }
        
        .price-option {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .price-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .products-main {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        
        .products-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .products-count {
            font-size: 1rem;
            color: var(--dark-color);
        }
        
        .products-count span {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .view-options select {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            background: white;
            color: var(--dark-color);
            font-size: 0.95rem;
            cursor: pointer;
            transition: border-color 0.3s;
            min-width: 200px;
        }
        
        .view-options select:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
        }
        
        @media (max-width: 576px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
            }
        }
        
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .product-image {
            position: relative;
            height: 220px;
            overflow: hidden;
        }
        
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .product-card:hover .product-image img {
            transform: scale(1.05);
        }
        
        .product-actions {
            position: absolute;
            top: 15px;
            right: 15px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .product-card:hover .product-actions {
            opacity: 1;
        }
        
        .product-actions button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: none;
            background: white;
            color: var(--dark-color);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .product-actions button:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-name {
            font-size: 1.1rem;
            color: var(--dark-color);
            margin-bottom: 10px;
            line-height: 1.4;
            height: 50px;
            overflow: hidden;
        }
        
        .product-name a {
            color: inherit;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .product-name a:hover {
            color: var(--primary-color);
        }
        
        .product-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-top: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 40px;
            gap: 10px;
        }
        
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: white;
            color: var(--dark-color);
            text-decoration: none;
            border: 2px solid var(--border-color);
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .page-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-link.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .page-link.disabled:hover {
            background: white;
            color: var(--dark-color);
            border-color: var(--border-color);
        }
        
        .search-box {
            margin-bottom: 30px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 15px 20px 15px 50px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
            background: white;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondary-color);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--border-color);
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: var(--dark-color);
        }
        
        .category-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            background: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            z-index: 1;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="products-section">
        <div class="container">
            <div class="products-header">
                <h1 class="page-title">
                    <?php 
                    if ($maloai_filter > 0) {
                        // Lấy tên danh mục hiện tại
                        $cat_sql = "SELECT tenloai FROM tbl_loai WHERE maloai = ?";
                        $cat_stmt = $conn->prepare($cat_sql);
                        $cat_stmt->bind_param("i", $maloai_filter);
                        $cat_stmt->execute();
                        $cat_result = $cat_stmt->get_result();
                        $current_category = $cat_result->fetch_assoc();
                        echo htmlspecialchars($current_category['tenloai']);
                    } else {
                        echo 'Tất Cả Sản Phẩm';
                    }
                    ?>
                </h1>
                <div class="breadcrumb">
                    <a href="index.php">Trang chủ</a> / <span>Sản phẩm</span>
                    <?php if ($maloai_filter > 0): ?>
                    / <span><?php echo htmlspecialchars($current_category['tenloai']); ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="products-layout">
                <aside class="filters-sidebar">
                    <!-- Tìm kiếm -->
                    <div class="filter-group search-box">
                        <form method="GET" action="products.php">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" 
                                   name="search" 
                                   placeholder="Tìm kiếm sản phẩm..." 
                                   value="<?php echo htmlspecialchars($search); ?>"
                                   onkeypress="if(event.keyCode==13) this.form.submit()">
                            <?php if ($maloai_filter > 0): ?>
                                <input type="hidden" name="maloai" value="<?php echo $maloai_filter; ?>">
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Danh mục -->
                    <div class="filter-group">
                        <h3 class="filter-title">Danh mục</h3>
                        <ul class="filter-list">
                            <li>
                                <a href="products.php" class="<?php echo $maloai_filter == 0 ? 'active' : ''; ?>">
                                    Tất cả sản phẩm
                                    <span class="category-count"><?php echo $total_products; ?></span>
                                </a>
                            </li>
                            <?php while($row_loai = $res_loai->fetch_assoc()): ?>
                            <li>
                                <a href="products.php?maloai=<?php echo $row_loai['maloai']; ?>" 
                                   class="<?php echo $maloai_filter == $row_loai['maloai'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($row_loai['tenloai']); ?>
                                    <span class="category-count"><?php echo $row_loai['product_count']; ?></span>
                                </a>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>

                    <!-- Lọc giá -->
                    <div class="filter-group">
                        <h3 class="filter-title">Khoảng giá</h3>
                        <div class="price-filter">
                            <?php
                            $price_options = [
                                'all' => 'Tất cả giá',
                                'under-1m' => 'Dưới 1 triệu',
                                '1m-3m' => '1 - 3 triệu',
                                '3m-5m' => '3 - 5 triệu',
                                'over-5m' => 'Trên 5 triệu'
                            ];
                            ?>
                            <form method="GET" id="priceFilterForm">
                                <?php if ($maloai_filter > 0): ?>
                                    <input type="hidden" name="maloai" value="<?php echo $maloai_filter; ?>">
                                <?php endif; ?>
                                <?php if (!empty($search)): ?>
                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <?php endif; ?>
                                <?php foreach ($price_options as $value => $label): ?>
                                <div class="price-option">
                                    <input type="radio" 
                                           id="price-<?php echo $value; ?>" 
                                           name="price" 
                                           value="<?php echo $value; ?>"
                                           <?php echo $price_range == $value || ($value == 'all' && empty($price_range)) ? 'checked' : ''; ?>
                                           onchange="document.getElementById('priceFilterForm').submit()">
                                    <label for="price-<?php echo $value; ?>"><?php echo $label; ?></label>
                                </div>
                                <?php endforeach; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Reset bộ lọc -->
                    <?php if ($maloai_filter > 0 || !empty($search) || !empty($price_range)): ?>
                    <div class="filter-group">
                        <a href="products.php" class="btn-reset">
                            <i class="fas fa-redo-alt"></i> Xóa bộ lọc
                        </a>
                    </div>
                    <?php endif; ?>
                </aside>

                <main class="products-main">
                    <div class="products-toolbar">
                        <div class="products-count">
                            Hiển thị <span><?php echo $res_sp->num_rows; ?></span> sản phẩm
                            <?php if ($total_products > 0): ?>
                                / Tổng cộng <span><?php echo $total_products; ?></span> sản phẩm
                            <?php endif; ?>
                        </div>
                        <div class="view-options">
                            <select class="sort-select" onchange="location = this.value + '<?php 
                                echo $maloai_filter > 0 ? '&maloai=' . $maloai_filter : ''; 
                                echo !empty($search) ? '&search=' . urlencode($search) : '';
                                echo !empty($price_range) ? '&price=' . $price_range : '';
                            ?>'">
                                <option value="products.php?sort=newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Mới nhất</option>
                                <option value="products.php?sort=price-low" <?php echo $sort == 'price-low' ? 'selected' : ''; ?>>Giá thấp đến cao</option>
                                <option value="products.php?sort=price-high" <?php echo $sort == 'price-high' ? 'selected' : ''; ?>>Giá cao đến thấp</option>
                                <option value="products.php?sort=name-asc" <?php echo $sort == 'name-asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                                <option value="products.php?sort=name-desc" <?php echo $sort == 'name-desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                            </select>
                        </div>
                    </div>

                    <div class="products-grid" id="productsView">
                        <?php if ($res_sp->num_rows > 0): ?>
                            <?php while($product = $res_sp->fetch_assoc()): ?>
                                <div class="product-card">
                                    <?php if (!empty($product['tenloai'])): ?>
                                        <div class="category-badge"><?php echo htmlspecialchars($product['tenloai']); ?></div>
                                    <?php endif; ?>
                                    <div class="product-image">
                                        <img src="uploads/products/<?php echo htmlspecialchars($product['hinhAnh']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['tensanpham']); ?>"
                                             onerror="this.src='images/default-product.jpg'">
                                        <div class="product-actions">
                                            <button title="Xem nhanh" class="quick-view" onclick="openQuickView(<?php echo $product['masanpham']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button title="Thêm vào giỏ" class="add-to-cart" onclick="addToCart(<?php echo $product['masanpham']; ?>)">
                                                <i class="fas fa-shopping-cart"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="product-info">
                                        <h3 class="product-name">
                                            <a href="product-detail.php?id=<?php echo $product['masanpham']; ?>">
                                                <?php echo htmlspecialchars($product['tensanpham']); ?>
                                            </a>
                                        </h3>
                                        <?php if (!empty($product['xuatxu'])): ?>
                                            <div class="product-origin">
                                                <small><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($product['xuatxu']); ?></small>
                                            </div>
                                        <?php endif; ?>
                                        <div class="product-price">
                                            <?php echo number_format($product['gia'], 0, ',', '.'); ?> đ
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-state" style="grid-column: 1 / -1;">
                                <i class="fas fa-box-open"></i>
                                <h3>Không tìm thấy sản phẩm nào</h3>
                                <p>Vui lòng thử lại với bộ lọc khác hoặc quay lại trang chủ</p>
                                <a href="products.php" class="page-link" style="display: inline-flex; width: auto; padding: 10px 20px; margin-top: 20px;">
                                    <i class="fas fa-home mr-2"></i> Xem tất cả sản phẩm
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Phân trang -->
                    <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <!-- Nút đầu trang -->
                        <?php if ($page > 1): ?>
                            <a href="products.php?page=1<?php 
                                echo $maloai_filter > 0 ? '&maloai=' . $maloai_filter : ''; 
                                echo !empty($sort) ? '&sort=' . $sort : '';
                                echo !empty($search) ? '&search=' . urlencode($search) : '';
                                echo !empty($price_range) ? '&price=' . $price_range : '';
                            ?>" class="page-link" title="Trang đầu">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Nút trang trước -->
                        <?php if ($page > 1): ?>
                            <a href="products.php?page=<?php echo $page - 1; ?><?php 
                                echo $maloai_filter > 0 ? '&maloai=' . $maloai_filter : ''; 
                                echo !empty($sort) ? '&sort=' . $sort : '';
                                echo !empty($search) ? '&search=' . urlencode($search) : '';
                                echo !empty($price_range) ? '&price=' . $price_range : '';
                            ?>" class="page-link" title="Trang trước">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Các trang -->
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="products.php?page=<?php echo $i; ?><?php 
                                echo $maloai_filter > 0 ? '&maloai=' . $maloai_filter : ''; 
                                echo !empty($sort) ? '&sort=' . $sort : '';
                                echo !empty($search) ? '&search=' . urlencode($search) : '';
                                echo !empty($price_range) ? '&price=' . $price_range : '';
                            ?>" class="page-link <?php echo $i == $page ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <!-- Nút trang sau -->
                        <?php if ($page < $total_pages): ?>
                            <a href="products.php?page=<?php echo $page + 1; ?><?php 
                                echo $maloai_filter > 0 ? '&maloai=' . $maloai_filter : ''; 
                                echo !empty($sort) ? '&sort=' . $sort : '';
                                echo !empty($search) ? '&search=' . urlencode($search) : '';
                                echo !empty($price_range) ? '&price=' . $price_range : '';
                            ?>" class="page-link" title="Trang sau">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Nút cuối trang -->
                        <?php if ($page < $total_pages): ?>
                            <a href="products.php?page=<?php echo $total_pages; ?><?php 
                                echo $maloai_filter > 0 ? '&maloai=' . $maloai_filter : ''; 
                                echo !empty($sort) ? '&sort=' . $sort : '';
                                echo !empty($search) ? '&search=' . urlencode($search) : '';
                                echo !empty($price_range) ? '&price=' . $price_range : '';
                            ?>" class="page-link" title="Trang cuối">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </main>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Thêm vào giỏ hàng
    function addToCart(productId) {
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId + '&quantity=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công!',
                    text: 'Đã thêm sản phẩm vào giỏ hàng',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
                // Cập nhật số lượng giỏ hàng
                if (data.cart_count !== undefined) {
                    updateCartCount(data.cart_count);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi!',
                    text: data.message || 'Không thể thêm sản phẩm vào giỏ hàng'
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

    // Xem nhanh sản phẩm
    function openQuickView(productId) {
        fetch('get_product_detail.php?id=' + productId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: data.product.tensanpham,
                    html: `
                        <div style="text-align: center;">
                            <img src="uploads/products/${data.product.hinhAnh}" 
                                 alt="${data.product.tensanpham}" 
                                 style="width: 100%; max-height: 300px; object-fit: cover; border-radius: 10px; margin-bottom: 20px;">
                            <div style="text-align: left;">
                                <p><strong>Giá:</strong> ${formatPrice(data.product.gia)} đ</p>
                                ${data.product.xuatxu ? `<p><strong>Xuất xứ:</strong> ${data.product.xuatxu}</p>` : ''}
                                ${data.product.chatlieu ? `<p><strong>Chất liệu:</strong> ${data.product.chatlieu}</p>` : ''}
                                ${data.product.kichthuoc ? `<p><strong>Kích thước:</strong> ${data.product.kichthuoc}</p>` : ''}
                                ${data.product.mota ? `<p><strong>Mô tả:</strong> ${data.product.mota.substring(0, 200)}...</p>` : ''}
                            </div>
                        </div>
                    `,
                    showCancelButton: true,
                    confirmButtonText: 'Thêm vào giỏ',
                    cancelButtonText: 'Xem chi tiết',
                    confirmButtonColor: '#2563eb',
                    showCloseButton: true,
                    width: 600
                }).then((result) => {
                    if (result.isConfirmed) {
                        addToCart(productId);
                    } else if (result.dismiss === Swal.DismissReason.cancel) {
                        window.location.href = 'product-detail.php?id=' + productId;
                    }
                });
            }
        });
    }

    // Format giá tiền
    function formatPrice(price) {
        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    // Cập nhật số lượng giỏ hàng
    function updateCartCount(count) {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
        }
    }

    // Lazy loading cho ảnh
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.product-image img');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src || img.src;
                    imageObserver.unobserve(img);
                }
            });
        });

        images.forEach(img => {
            if (img.dataset.src) {
                imageObserver.observe(img);
            }
        });

        // Hiệu ứng hover cho card
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-10px)';
            });
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    });
    </script>
</body>
</html>

<?php
// Đóng kết nối
$stmt->close();
$conn->close();
?>