<?php
session_start();
require 'conn.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

$response = ['status' => 'error', 'message' => 'Yêu cầu không hợp lệ'];

switch ($action) {
    case 'add':
        // Kiểm tra sản phẩm có tồn tại trong DB không
        $sql = "SELECT * FROM tbl_sanpham WHERE masanpham = $id";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id] += $quantity;
            } else {
                $_SESSION['cart'][$id] = $quantity;
            }
            $response = [
                'status' => 'success', 
                'message' => 'Đã thêm vào giỏ hàng!',
                'total_count' => array_sum($_SESSION['cart'])
            ];
        }
        break;

    case 'update':
        if (isset($_SESSION['cart'][$id])) {
            if ($quantity > 0) {
                $_SESSION['cart'][$id] = $quantity;
            } else {
                unset($_SESSION['cart'][$id]);
            }
            $response = ['status' => 'success', 'message' => 'Đã cập nhật giỏ hàng'];
        }
        break;

    case 'remove':
        if (isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
            $response = ['status' => 'success', 'message' => 'Đã xóa sản phẩm'];
        }
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        $response = ['status' => 'success', 'message' => 'Đã xóa toàn bộ giỏ hàng'];
        break;
}

echo json_encode($response);
?>