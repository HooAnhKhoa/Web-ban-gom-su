<?php
// header.php
if(!isset($_SESSION)) { 
    session_start(); 
}

// Kết nối database
require_once 'conn.php';

// Kiểm tra trạng thái đăng nhập
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['user_email']) : '';
$userRole = $isLoggedIn ? ($_SESSION['user_role'] ?? 'user') : '';

// Lấy danh mục cho menu dropdown
$sql_categories = "SELECT maloai, tenloai FROM tbl_loai WHERE trangthai = 1 ORDER BY tenloai";
$result_categories = $conn->query($sql_categories);
$categories = [];
while($row = $result_categories->fetch_assoc()) {
    $categories[] = $row;
}

// Tính số lượng sản phẩm trong giỏ hàng
$cart_count = 0;
if($isLoggedIn && isset($_SESSION['manguoidung'])) {
    $user_id = $_SESSION['manguoidung'];
    $sql_cart = "SELECT SUM(soluong) as total FROM tbl_giohang WHERE manguoidung = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $user_id);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
    if($cart_data = $result_cart->fetch_assoc()) {
        $cart_count = $cart_data['total'] ?? 0;
    }
    $stmt_cart->close();
}
?>

<header>
        <div class="header-top">
            <div class="container">
                <div class="header-contact">
                    <span><i class="fas fa-phone"></i> 0123 456 789</span>
                    <span><i class="fas fa-envelope"></i> info@gomsutinhhoa.vn</span>
                </div>
                <div class="header-actions">
                    <?php if ($isLoggedIn): ?>
                        <span>Xin chào, <?php echo htmlspecialchars($userName); ?></span>
                        <a href="logout.php">Đăng xuất</a>
                    <?php else: ?>
                        <a href="login.php">Đăng nhập</a>
                        <a href="register.php">Đăng ký</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="header-main">
            <div class="container">
                <div class="logo">
                    <div class="logo-placeholder">
                        <i class="fas fa-vase"></i>
                    </div>
                    <div class="logo-text">Gốm Sứ <span>Tinh Hoa</span></div>
                </div>
                <div class="search-bar">
                    <input type="text" placeholder="Tìm kiếm sản phẩm...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="header-icons">
                    <a href="profile.php"><i class="fas fa-user"></i></a>
                    <a href="cart.php" title="Giỏ hàng">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($cart_count > 0): ?>
                            <span class="cart-count"><?php echo $cart_count; ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                <button class="mobile-menu-btn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
        <nav>
            <div class="container">
                <ul class="nav-menu">
                    <li><a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">Trang chủ</a></li>
                    
                    <li>
                        <a href="products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>">
                            Sản phẩm <i class="fas fa-chevron-down"></i>
                        </a>
                        <?php if(count($categories) > 0): ?>
                            <ul class="dropdown-menu">
                                <li><a href="products.php">Tất cả sản phẩm</a></li>
                                <?php foreach($categories as $category): ?>
                                    <li>
                                        <a href="products.php?maloai=<?php echo $category['maloai']; ?>">
                                            <?php echo htmlspecialchars($category['tenloai']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                </ul>
            </div>
        </nav>
    </header>