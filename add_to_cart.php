<?php
session_start();
require 'conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thêm vào giỏ hàng']);
    exit();
}

if (!isset($_POST['product_id']) || !is_numeric($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không hợp lệ']);
    exit();
}

$product_id = (int)$_POST['product_id'];
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

// Kiểm tra sản phẩm có tồn tại và còn hàng không
$check_sql = "SELECT * FROM tbl_sanpham WHERE masanpham = ? AND trangthai = 1";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $product_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc đã ngừng bán']);
    exit();
}

$product = $result->fetch_assoc();
$check_stmt->close();

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Thêm sản phẩm vào giỏ
if (isset($_SESSION['cart'][$product_id])) {
    $_SESSION['cart'][$product_id]['quantity'] += $quantity;
} else {
    $_SESSION['cart'][$product_id] = [
        'id' => $product_id,
        'name' => $product['tensanpham'],
        'price' => $product['gia'],
        'image' => $product['hinhAnh'],
        'quantity' => $quantity
    ];
}

// Tính tổng số lượng sản phẩm trong giỏ
$total_items = array_sum(array_column($_SESSION['cart'], 'quantity'));

echo json_encode([
    'success' => true,
    'message' => 'Đã thêm sản phẩm vào giỏ hàng',
    'cart_count' => $total_items
]);
?>