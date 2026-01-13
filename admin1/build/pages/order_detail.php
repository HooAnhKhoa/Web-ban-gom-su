<?php
if(!isset($_SESSION)) { session_start(); }

// 1. Kết nối Database
require_once '../../../conn.php'; 

// 2. Kiểm tra ID đơn hàng
if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = 'Không tìm thấy đơn hàng!';
    header("Location: orders.php");
    exit();
}

$order_id = (int)$_GET['id'];

// 3. Lấy thông tin chi tiết đơn hàng (Header)
// SỬA LỖI: Thay u.email thành t.email
$sql = "SELECT 
            d.madonhang,
            d.mahoadon,
            d.trangthai,
            d.tongtien,
            d.phivanchuyen,
            d.hinhthucthanhtoan,
            h.ngaydat as ngaydathang,
            t.hotennguoinhan,
            t.sodienthoai as phone_recipient,
            t.diachi,
            t.ghichu as shipping_note,
            t.email as email_recipient,
            u.hotennguoidung,
            u.tendangnhap,
            u.sodienthoai as phone_customer
        FROM tbl_donhang d
        LEFT JOIN tbl_hoadon h ON d.mahoadon = h.mahoadon
        LEFT JOIN tbl_thongtiniaoahang t ON d.mathongtiniaoahang = t.mathongtiniaoahang
        LEFT JOIN tbl_nguoidung u ON t.manguoidung = u.manguoidung
        WHERE d.madonhang = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Lỗi chuẩn bị truy vấn SQL: " . $conn->error);
}
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    $_SESSION['error'] = 'Đơn hàng không tồn tại!';
    header("Location: orders.php");
    exit();
}

$order = $result->fetch_assoc();
$stmt->close();

// 4. Lấy danh sách sản phẩm (Lấy từ tbl_chitiethoadon)
$sql_products = "SELECT 
                    cthd.soluong,
                    cthd.dongia,
                    cthd.thanhtien,
                    sp.masanpham,
                    sp.tensanpham,
                    sp.hinhAnh,
                    sp.mota,
                    l.tenloai
                FROM tbl_chitiethoadon cthd
                LEFT JOIN tbl_sanpham sp ON cthd.masanpham = sp.masanpham
                LEFT JOIN tbl_loai l ON sp.maloai = l.maloai
                WHERE cthd.mahoadon = ?";

$stmt_products = $conn->prepare($sql_products);
$stmt_products->bind_param("i", $order['mahoadon']);
$stmt_products->execute();
$products_result = $stmt_products->get_result();
$products = [];
$total_items = 0;

while($product = $products_result->fetch_assoc()) {
    $products[] = $product;
    $total_items += $product['soluong'];
}
$stmt_products->close();

// 5. Cấu hình trạng thái (Enum)
$order_statuses = [
    'cho_xac_nhan' => ['label' => 'Chờ xác nhận', 'class' => 'bg-yellow-500'],
    'da_xac_nhan'  => ['label' => 'Đã xác nhận',  'class' => 'bg-blue-500'],
    'dang_giao'    => ['label' => 'Đang giao',    'class' => 'bg-orange-500'],
    'da_giao'      => ['label' => 'Đã giao hàng', 'class' => 'bg-green-500'],
    'da_huy'       => ['label' => 'Đã hủy',       'class' => 'bg-red-500']
];

$payment_methods = [
    'cod'     => 'Thanh toán khi nhận hàng (COD)',
    'banking' => 'Chuyển khoản ngân hàng',
    'momo'    => 'Ví điện tử MoMo'
];

$current_status = $order_statuses[$order['trangthai']] ?? ['label' => $order['trangthai'], 'class' => 'bg-gray-500'];
$payment_method = $payment_methods[$order['hinhthucthanhtoan']] ?? $order['hinhthucthanhtoan'];

// Xử lý cập nhật trạng thái ngay tại trang này
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status_detail') {
    $new_status = $_POST['status'];
    if(array_key_exists($new_status, $order_statuses)) {
        $update_sql = "UPDATE tbl_donhang SET trangthai = ? WHERE madonhang = ?";
        $stmt_up = $conn->prepare($update_sql);
        $stmt_up->bind_param("si", $new_status, $order_id);
        if($stmt_up->execute()) {
            $_SESSION['success'] = "Cập nhật trạng thái thành công!";
            header("Location: order_detail.php?id=" . $order_id);
            exit();
        }
    }
}

include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main>
        <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
            <nav>
                <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                    <li class="text-sm leading-normal text-white opacity-50">
                        <a href="orders.php" class="text-white hover:opacity-75">Quản lý đơn hàng</a>
                    </li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">
                        Chi tiết
                    </li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">
                    Chi tiết đơn hàng #<?php echo $order['madonhang']; ?>
                </h6>
            </nav>
        </div>
    </nav>

    <div class="w-full px-6 py-6 mx-auto">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
                <strong>Thành công!</strong> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            
            <div class="bg-white rounded-xl shadow-md p-6 h-full">
                <h4 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-file-invoice text-blue-500 mr-2"></i> Thông tin chung
                </h4>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mã đơn hàng:</span>
                        <span class="font-bold text-blue-600">#<?php echo $order['madonhang']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Mã hóa đơn:</span>
                        <span class="font-bold">#<?php echo $order['mahoadon']; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Ngày đặt:</span>
                        <span class="font-medium">
                            <?php echo $order['ngaydathang'] ? date('d/m/Y H:i', strtotime($order['ngaydathang'])) : 'N/A'; ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Trạng thái:</span>
                        <span class="px-2 py-1 text-xs font-bold text-white rounded <?php echo $current_status['class']; ?>">
                            <?php echo $current_status['label']; ?>
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Thanh toán:</span>
                        <span class="font-medium text-right"><?php echo $payment_method; ?></span>
                    </div>
                </div>
            </div>
<br>
            <div class="bg-white rounded-xl shadow-md p-6 h-full lg:col-span-2">
                <h4 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
                    <i class="fas fa-user-circle text-green-500 mr-2"></i> Thông tin giao nhận
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="text-gray-500 font-bold mb-2 uppercase text-xs">Người đặt hàng (Tài khoản)</p>
                        <p><i class="fas fa-user w-5 text-gray-400"></i> <?php echo htmlspecialchars($order['hotennguoidung']); ?></p>
                        <p><i class="fas fa-envelope w-5 text-gray-400"></i> <?php echo htmlspecialchars($order['tendangnhap']); // Tên đăng nhập là email ?></p>
                        <p><i class="fas fa-phone w-5 text-gray-400"></i> <?php echo htmlspecialchars($order['phone_customer']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-500 font-bold mb-2 uppercase text-xs">Người nhận hàng</p>
                        <p class="font-bold text-gray-800"><?php echo htmlspecialchars($order['hotennguoinhan']); ?></p>
                        <?php if(!empty($order['email_recipient'])): ?>
                            <p><i class="fas fa-envelope w-5 text-gray-400"></i> <?php echo htmlspecialchars($order['email_recipient']); ?></p>
                        <?php endif; ?>
                        <p><i class="fas fa-phone w-5 text-gray-400"></i> <?php echo htmlspecialchars($order['phone_recipient']); ?></p>
                        <p><i class="fas fa-map-marker-alt w-5 text-gray-400"></i> <?php echo htmlspecialchars($order['diachi']); ?></p>
                        <?php if(!empty($order['shipping_note'])): ?>
                            <div class="mt-2 p-2 bg-yellow-50 rounded text-yellow-800 text-xs border border-yellow-200">
                                <strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['shipping_note']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md mb-6">
            <div class="p-6 border-b flex justify-between items-center">
                <h4 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-boxes text-purple-500 mr-2"></i> Chi tiết sản phẩm
                </h4>
                <span class="bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full font-bold">
                    <?php echo $total_items; ?> sản phẩm
                </span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Sản phẩm</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Đơn giá</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase">Số lượng</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Thành tiền</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if(count($products) > 0): ?>
                            <?php foreach($products as $product): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-gray-900"><?php echo htmlspecialchars($product['tensanpham']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($product['tenloai']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-500">
                                    <?php echo number_format($product['dongia']); ?>₫
                                </td>
                                <td class="px-6 py-4 text-center text-sm text-gray-900 font-medium">
                                    x<?php echo $product['soluong']; ?>
                                </td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-blue-600">
                                    <?php echo number_format($product['thanhtien']); ?>₫
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-6 text-gray-500">Không có dữ liệu sản phẩm</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h5 class="text-md font-bold text-gray-800 mb-3">Xử lý đơn hàng</h5>
                    <form method="POST" class="flex gap-3 items-center bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <input type="hidden" name="action" value="update_status_detail">
                        <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Đổi trạng thái:</label>
                        <select name="status" class="flex-1 text-sm border-gray-300 border rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                            <?php foreach($order_statuses as $key => $val): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($order['trangthai'] == $key) ? 'selected' : ''; ?>>
                                    <?php echo $val['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" onclick="return confirm('Xác nhận cập nhật trạng thái?')" 
                                class="btn-edit">
                            Cập nhật
                        </button>
                    </form>
                </div>
            </div>
<br>
            <div class="bg-white rounded-xl shadow-md p-6">
                <h4 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">Tổng thanh toán</h4>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Tiền hàng:</span>
                        <span><?php echo number_format($order['tongtien'] - $order['phivanchuyen']); ?>₫</span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Phí vận chuyển:</span>
                        <span><?php echo number_format($order['phivanchuyen']); ?>₫</span>
                    </div>
                    <div class="border-t pt-3 flex justify-between items-center">
                        <span class="font-bold text-lg text-gray-800">Tổng cộng:</span>
                        <span class="font-bold text-xl text-red-600"><?php echo number_format($order['tongtien']); ?>₫</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php 
    $conn->close();
    include '../includes/footer.php'; 
    ?>
</main> 