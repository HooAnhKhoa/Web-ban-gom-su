<?php
if(!isset($_SESSION)) { session_start(); }
require_once 'conn.php';

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// 2. Kiểm tra ID đơn hàng hợp lệ
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: profile.php");
    exit();
}

$order_id = (int)$_GET['id'];
$user_id = $_SESSION['manguoidung'];

// 3. Lấy thông tin đơn hàng (Chỉ lấy nếu đơn hàng thuộc về người dùng đang đăng nhập)
// Kết nối 3 bảng: tbl_donhang (Trạng thái, Tiền), tbl_hoadon (Ngày đặt), tbl_thongtiniaoahang (Địa chỉ)
$sql_order = "SELECT 
                d.madonhang, d.mahoadon, d.trangthai, d.tongtien, d.phivanchuyen, d.hinhthucthanhtoan,
                h.ngaydat,
                t.hotennguoinhan, t.sodienthoai, t.diachi, t.ghichu, t.email
              FROM tbl_donhang d
              JOIN tbl_hoadon h ON d.mahoadon = h.mahoadon
              JOIN tbl_thongtiniaoahang t ON d.mathongtiniaoahang = t.mathongtiniaoahang
              WHERE d.madonhang = ? AND h.manguoidung = ?";

$stmt = $conn->prepare($sql_order);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result_order = $stmt->get_result();

if ($result_order->num_rows === 0) {
    // Không tìm thấy đơn hàng hoặc đơn hàng không thuộc về người dùng này
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='profile.php';</script>";
    exit();
}

$order = $result_order->fetch_assoc();
$stmt->close();

// 4. Lấy danh sách sản phẩm
$sql_items = "SELECT 
                ct.soluong, ct.dongia, ct.thanhtien,
                sp.masanpham, sp.tensanpham, sp.hinhAnh
              FROM tbl_chitiethoadon ct
              JOIN tbl_sanpham sp ON ct.masanpham = sp.masanpham
              WHERE ct.mahoadon = ?";

$stmt_items = $conn->prepare($sql_items);
$stmt_items->bind_param("i", $order['mahoadon']);
$stmt_items->execute();
$items = $stmt_items->get_result();

// Định nghĩa trạng thái hiển thị
$status_map = [
    'cho_xac_nhan' => ['text' => 'Chờ xác nhận', 'class' => 'bg-yellow-100 text-yellow-800'],
    'da_xac_nhan' => ['text' => 'Đã xác nhận', 'class' => 'bg-blue-100 text-blue-800'],
    'dang_giao' => ['text' => 'Đang giao hàng', 'class' => 'bg-orange-100 text-orange-800'],
    'da_giao' => ['text' => 'Giao thành công', 'class' => 'bg-green-100 text-green-800'],
    'da_huy' => ['text' => 'Đã hủy', 'class' => 'bg-red-100 text-red-800']
];

$payment_map = [
    'cod' => 'Thanh toán khi nhận hàng (COD)',
    'banking' => 'Chuyển khoản ngân hàng',
    'momo' => 'Ví MoMo'
];

$stt_key = $order['trangthai'];
$status_display = $status_map[$stt_key] ?? ['text' => $stt_key, 'class' => 'bg-gray-100 text-gray-800'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết đơn hàng #<?php echo $order_id; ?> - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* CSS Riêng cho trang chi tiết đơn hàng */
        .order-detail-section { padding: 40px 0; background: #f9f9f9; }
        .order-card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 30px; margin-bottom: 20px; }
        .order-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
        .order-title h1 { font-size: 24px; margin: 0; color: #333; }
        .order-meta { color: #666; font-size: 14px; margin-top: 5px; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .bg-yellow-100 { background-color: #fef3c7; color: #92400e; }
        .bg-blue-100 { background-color: #dbeafe; color: #1e40af; }
        .bg-orange-100 { background-color: #ffedd5; color: #9a3412; }
        .bg-green-100 { background-color: #d1fae5; color: #065f46; }
        .bg-red-100 { background-color: #fee2e2; color: #b91c1c; }
        .bg-gray-100 { background-color: #f3f4f6; color: #1f2937; }

        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 30px; }
        .info-box h3 { font-size: 16px; font-weight: 700; margin-bottom: 15px; color: #333; text-transform: uppercase; border-left: 3px solid #bfa47e; padding-left: 10px; }
        .info-row { display: flex; margin-bottom: 8px; font-size: 14px; }
        .info-label { width: 120px; color: #666; flex-shrink: 0; }
        .info-value { color: #333; font-weight: 500; }

        .product-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .product-table th { text-align: left; padding: 12px; border-bottom: 2px solid #eee; font-weight: 600; color: #555; }
        .product-table td { padding: 15px 12px; border-bottom: 1px solid #f5f5f5; vertical-align: middle; }
        .product-item { display: flex; align-items: center; gap: 15px; }
        .product-img { width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee; }
        .product-name { font-weight: 500; color: #333; }
        
        .order-summary { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-left: auto; max-width: 400px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #555; }
        .summary-row.total { border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px; font-size: 18px; font-weight: 700; color: #b91c1c; }
        
        .btn-back { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: #fff; border: 1px solid #ddd; color: #555; border-radius: 5px; text-decoration: none; font-weight: 500; transition: all 0.3s; }
        .btn-back:hover { background: #f5f5f5; color: #333; }

        @media (max-width: 768px) {
            .order-header { flex-direction: column; align-items: flex-start; }
            .status-badge { margin-top: 10px; }
            .product-table th { display: none; }
            .product-table td { display: block; width: 100%; padding: 5px 0; border: none; }
            .product-table tr { border-bottom: 1px solid #eee; display: block; padding: 15px 0; }
            .product-item { margin-bottom: 10px; }
            .col-price, .col-qty, .col-total { display: flex; justify-content: space-between; }
            .col-price::before { content: 'Đơn giá:'; color: #666; }
            .col-qty::before { content: 'Số lượng:'; color: #666; }
            .col-total::before { content: 'Thành tiền:'; color: #666; font-weight: 600; }
            .order-summary { max-width: 100%; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="order-detail-section">
        <div class="container">
            <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <div class="breadcrumb" style="margin: 0;">
                    <a href="index.php">Trang chủ</a> / 
                    <a href="profile.php">Hồ sơ</a> / 
                    <span>Chi tiết đơn hàng</span>
                </div>
                <a href="profile.php#orders" class="btn-back">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách
                </a>
            </div>

            <div class="order-card">
                <div class="order-header">
                    <div class="order-title">
                        <h1>Đơn hàng #<?php echo $order['madonhang']; ?></h1>
                        <div class="order-meta">
                            <span>Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['ngaydat'])); ?></span>
                            <span style="margin: 0 10px;">|</span>
                            <span>Mã hóa đơn: #<?php echo $order['mahoadon']; ?></span>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $status_display['class']; ?>">
                        <?php echo $status_display['text']; ?>
                    </span>
                </div>

                <div class="info-grid">
                    <div class="info-box">
                        <h3><i class="fas fa-map-marker-alt"></i> Địa chỉ nhận hàng</h3>
                        <div class="info-row">
                            <span class="info-label">Người nhận:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['hotennguoinhan']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Điện thoại:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['sodienthoai']); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Địa chỉ:</span>
                            <span class="info-value"><?php echo htmlspecialchars($order['diachi']); ?></span>
                        </div>
                        <?php if(!empty($order['ghichu'])): ?>
                        <div class="info-row">
                            <span class="info-label">Ghi chú:</span>
                            <span class="info-value" style="font-style: italic;"><?php echo htmlspecialchars($order['ghichu']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="info-box">
                        <h3><i class="fas fa-credit-card"></i> Thanh toán & Vận chuyển</h3>
                        <div class="info-row">
                            <span class="info-label">Hình thức:</span>
                            <span class="info-value"><?php echo $payment_map[$order['hinhthucthanhtoan']] ?? $order['hinhthucthanhtoan']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Trạng thái:</span>
                            <span class="info-value">
                                <?php echo ($order['trangthai'] == 'da_giao') ? '<span style="color:green">Đã thanh toán</span>' : 'Chưa thanh toán/COD'; ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Phí vận chuyển:</span>
                            <span class="info-value"><?php echo number_format($order['phivanchuyen']); ?>₫</span>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 30px;">
                    <h3 style="font-size: 18px; margin-bottom: 15px; color: #333;">Sản phẩm</h3>
                    <table class="product-table">
                        <thead>
                            <tr>
                                <th style="width: 50%;">Sản phẩm</th>
                                <th style="width: 15%; text-align: center;">Đơn giá</th>
                                <th style="width: 15%; text-align: center;">Số lượng</th>
                                <th style="width: 20%; text-align: right;">Tạm tính</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $items_total = 0;
                            while($item = $items->fetch_assoc()): 
                                $items_total += $item['thanhtien'];
                            ?>
                            <tr>
                                <td>
                                    <div class="product-item">
                                        <img src="uploads/products/<?php echo htmlspecialchars($item['hinhAnh']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['tensanpham']); ?>" 
                                             class="product-img"
                                             onerror="this.src='images/default-product.jpg'">
                                        <div>
                                            <div class="product-name"><?php echo htmlspecialchars($item['tensanpham']); ?></div>
                                            <div style="font-size: 12px; color: #888;">Mã SP: <?php echo $item['masanpham']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-price" style="text-align: center;">
                                    <?php echo number_format($item['dongia']); ?>₫
                                </td>
                                <td class="col-qty" style="text-align: center;">
                                    x<?php echo $item['soluong']; ?>
                                </td>
                                <td class="col-total" style="text-align: right; font-weight: bold;">
                                    <?php echo number_format($item['thanhtien']); ?>₫
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="order-summary">
                    <div class="summary-row">
                        <span>Tổng tiền hàng:</span>
                        <span><?php echo number_format($items_total); ?>₫</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span><?php echo number_format($order['phivanchuyen']); ?>₫</span>
                    </div>
                    <div class="summary-row total">
                        <span>Thành tiền:</span>
                        <span><?php echo number_format($order['tongtien']); ?>₫</span>
                    </div>
                </div>

                <?php if($order['trangthai'] == 'cho_xac_nhan'): ?>
                <div style="margin-top: 30px; text-align: right;">
                    <form method="POST" action="cancel_order.php" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này không?');">
                        <input type="hidden" name="order_id" value="<?php echo $order['madonhang']; ?>">
                        <button type="submit" class="btn btn-secondary" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca;">
                            <i class="fas fa-times"></i> Hủy đơn hàng
                        </button>
                    </form>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
</body>
</html>