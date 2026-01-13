<?php
if(!isset($_SESSION)) { session_start(); }

// 1. Kết nối Database
require_once '../../../conn.php'; 

// Định nghĩa các trạng thái đơn hàng theo ENUM trong database
$order_statuses = [
    'cho_xac_nhan' => ['label' => 'Chờ xác nhận', 'class' => 'btn-toggle'],
    'da_xac_nhan' => ['label' => 'Đã xác nhận', 'class' => 'bg-blue-500'],
    'dang_giao' => ['label' => 'Đang giao hàng', 'class' => 'btn-toggle'],
    'da_giao' => ['label' => 'Đã giao hàng', 'class' => 'bg-blue-500'],
    'da_huy' => ['label' => 'Đã hủy', 'class' => 'btn-delete'],
];

// 2. Xử lý Logic
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'], $_POST['id'])) {
        $id = (int)$_POST['id'];
        $action = $_POST['action'];
        
        // Cập nhật trạng thái đơn hàng
        if ($action == 'update_status' && isset($_POST['status'])) {
            $new_status = $_POST['status']; // Giữ nguyên string từ ENUM
            
            // Kiểm tra nếu trạng thái hợp lệ
            if(array_key_exists($new_status, $order_statuses)) {
                $update_sql = "UPDATE tbl_donhang SET trangthai = ? WHERE madonhang = ?";
                $stmt = $conn->prepare($update_sql);
                
                if($stmt) {
                    $stmt->bind_param("si", $new_status, $id); // "s" cho string, "i" cho int
                    
                    if ($stmt->execute()) {
                        $_SESSION['success'] = 'Cập nhật trạng thái đơn hàng thành công!';
                    } else {
                        $_SESSION['error'] = 'Lỗi cập nhật: ' . $conn->error;
                    }
                    $stmt->close();
                } else {
                    $_SESSION['error'] = 'Lỗi chuẩn bị câu lệnh SQL!';
                }
            } else {
                $_SESSION['error'] = 'Trạng thái không hợp lệ!';
            }
            header("Location: orders.php");
            exit();
        }
        
        // Xóa đơn hàng
        if ($action == 'delete_order') {
            // Bắt đầu transaction để đảm bảo tính toàn vẹn
            $conn->begin_transaction();
            
            try {
                // Kiểm tra xem có hóa đơn liên quan không
                $check_sql = "SELECT mahoadon FROM tbl_donhang WHERE madonhang = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();
                
                if ($check_result) {
                    $invoice_id = $check_result['mahoadon'];
                    
                    // Xóa chi tiết hóa đơn trước
                    $del_details_sql = "DELETE FROM tbl_hoadon WHERE mahoadon = ?";
                    $del_details_stmt = $conn->prepare($del_details_sql);
                    $del_details_stmt->bind_param("i", $invoice_id);
                    $del_details_stmt->execute();
                    $del_details_stmt->close();
                    
                    // Xóa đơn hàng
                    $del_sql = "DELETE FROM tbl_donhang WHERE madonhang = ?";
                    $del_stmt = $conn->prepare($del_sql);
                    $del_stmt->bind_param("i", $id);
                    
                    if ($del_stmt->execute()) {
                        $conn->commit();
                        $_SESSION['success'] = 'Xóa đơn hàng thành công!';
                    } else {
                        $conn->rollback();
                        $_SESSION['error'] = 'Xóa đơn hàng thất bại!';
                    }
                    $del_stmt->close();
                } else {
                    $_SESSION['error'] = 'Không tìm thấy đơn hàng!';
                }
            } catch (Exception $e) {
                $conn->rollback();
                $_SESSION['error'] = 'Lỗi: ' . $e->getMessage();
            }
            header("Location: orders.php");
            exit();
        }
    }
}

// 3. Lấy danh sách đơn hàng với thông tin đầy đủ
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT 
            d.madonhang,
            d.mahoadon,
            d.mathongtiniaoahang,
            d.trangthai,
            d.tongtien,
            d.phivanchuyen,
            d.hinhthucthanhtoan,
            t.hotennguoinhan,
            t.sodienthoai,
            t.diachi,
            t.ghichu,
            u.hotennguoidung,
            u.tendangnhap,
            u.sodienthoai as user_phone,
            COUNT(h.mahoadon) as total_items,
            SUM(h.thanhtien) as total_amount
        FROM tbl_donhang d
        LEFT JOIN tbl_thongtiniaoahang t ON d.mathongtiniaoahang = t.mathongtiniaoahang
        LEFT JOIN tbl_nguoidung u ON t.manguoidung = u.manguoidung
        LEFT JOIN tbl_hoadon h ON d.mahoadon = h.mahoadon
        WHERE 1=1";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (d.madonhang LIKE ? OR d.mahoadon LIKE ? OR t.hotennguoinhan LIKE ? OR u.tendangnhap LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $types .= "ssss";
}

if ($status_filter !== '') {
    $sql .= " AND d.trangthai = ?";
    $params[] = $status_filter;
    $types .= "s";
}

$sql .= " GROUP BY d.madonhang, d.mahoadon, d.mathongtiniaoahang, d.trangthai, d.tongtien, d.phivanchuyen, d.hinhthucthanhtoan, t.hotennguoinhan, t.sodienthoai, t.diachi, t.ghichu, u.hotennguoidung, u.tendangnhap";
$sql .= " ORDER BY d.madonhang DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// 5. Nhúng Giao diện
include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main>
        <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
            <nav>
                <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                    <li class="text-sm leading-normal text-white opacity-50">Quản lý</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Quản lý đơn hàng</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Danh sách đơn hàng</h6>
            </nav>
            <div class="flex items-center mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:flex lg:basis-auto">
                <div class="flex items-center md:ml-auto md:pr-4">
                    <form method="GET" class="flex items-center gap-2">
                        <div class="relative">
                            <span class="absolute z-50 flex items-center h-full px-2.5 text-center pointer-events-none">
                                <i class="fas fa-search text-slate-400"></i>
                            </span>
                            <input type="text" name="search" class="pl-9 text-sm focus:shadow-primary-outline ease w-full leading-5.6 relative -ml-px block min-w-0 flex-auto rounded-lg border border-solid border-gray-300 bg-white py-2 pr-3 text-gray-700 transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none" placeholder="Tìm mã đơn, tên khách..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <select name="status" class="text-sm border rounded-lg p-2 focus:outline-none focus:border-blue-500 cursor-pointer" onchange="this.form.submit()">
                            <option value="">Tất cả trạng thái</option>
                            <?php foreach($order_statuses as $key => $val): ?>
                                <option value="<?php echo $key; ?>" <?php echo ($status_filter == $key) ? 'selected' : ''; ?>>
                                    <?php echo $val['label']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="w-full px-6 py-6 mx-auto">
        <!-- Bảng đơn hàng -->
        <div class="flex flex-wrap -mx-3">
            <div class="flex-none w-full max-w-full px-3">
                <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl flex justify-between items-center">
                        <h6 class="dark:text-white">Danh sách đơn hàng</h6>
                        <div class="text-sm text-gray-600">
                            Tổng: <span class="font-bold"><?php echo $result->num_rows; ?> đơn hàng</span>
                        </div>
                    </div>
                    <div class="flex-auto px-0 pt-0 pb-2">
                        <div class="table-responsive p-0 overflow-x-auto">
                            <table class="items-center w-full mb-0 align-top border-collapse text-slate-500">
                                <thead class="align-bottom">
                                    <tr>
                                        <th class="px-6 py-3 font-bold text-left uppercase text-xxs opacity-70">Mã đơn hàng</th>
                                        <th class="px-6 py-3 font-bold text-left uppercase text-xxs opacity-70">Khách hàng</th>
                                        <th class="px-6 py-3 font-bold text-left uppercase text-xxs opacity-70">Thông tin giao hàng</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Mã hóa đơn</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Số sản phẩm</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Tổng tiền</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Trạng thái</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="p-4 align-middle border-b dark:border-white/40">
                                                <h6 class="mb-0 text-sm font-bold text-blue-500">#<?php echo htmlspecialchars($row['madonhang']); ?></h6>
                                                <small class="text-xs text-gray-500">
                                                    <?php 
                                                    // Lấy ngày đặt hàng từ hóa đơn đầu tiên
                                                    $date_sql = "SELECT ngaydat FROM tbl_hoadon WHERE mahoadon = ? LIMIT 1";
                                                    $date_stmt = $conn->prepare($date_sql);
                                                    $date_stmt->bind_param("i", $row['mahoadon']);
                                                    $date_stmt->execute();
                                                    $date_result = $date_stmt->get_result()->fetch_assoc();
                                                    echo $date_result ? date('d/m/Y H:i', strtotime($date_result['ngaydat'])) : 'N/A';
                                                    ?>
                                                </small>
                                            </td>
                                            <td class="p-4 align-middle border-b dark:border-white/40 text-sm">
                                                <div class="font-medium"><?php echo htmlspecialchars($row['hotennguoidung'] ?? $row['hotennguoinhan']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['tendangnhap'] ?? ''); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['user_phone'] ?? $row['sodienthoai']); ?></div>
                                            </td>
                                            <td class="p-4 align-middle border-b dark:border-white/40 text-sm">
                                                <div class="font-medium"><?php echo htmlspecialchars($row['hotennguoinhan']); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($row['sodienthoai']); ?></div>
                                                <div class="text-xs text-gray-500 truncate max-w-[200px]"><?php echo htmlspecialchars($row['diachi']); ?></div>
                                                <?php if($row['ghichu']): ?>
                                                    <div class="text-xs text-gray-500 italic"><?php echo htmlspecialchars($row['ghichu']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-4 align-middle border-b dark:border-white/40 text-center text-sm">
                                                #<?php echo htmlspecialchars($row['mahoadon']); ?>
                                            </td>
                                            <td class="p-4 align-middle border-b dark:border-white/40 text-center text-sm">
                                                <?php echo $row['total_items']; ?> SP
                                            </td>
                                            <td class="p-4 align-middle border-b dark:border-white/40 text-center text-sm font-bold">
                                                <?php echo number_format($row['tongtien'] ?? $row['total_amount'] ?? 0); ?>₫
                                            </td>
                                            <td class="p-4 align-middle border-b dark:border-white/40">
                                                <div class="flex flex-col items-center justify-center gap-2">
                                                    <!-- Hiển thị trạng thái hiện tại -->
                                                    <?php 
                                                        $stt = $row['trangthai'];
                                                        $stt_info = $order_statuses[$stt] ?? ['label' => 'Không xác định', 'class' => 'bg-gray-400'];
                                                    ?>
                                                    <span class="px-3 py-1 text-xs font-bold text-white rounded-lg <?php echo $stt_info['class']; ?> w-full text-center">
                                                        <?php echo $stt_info['label']; ?>
                                                    </span>
                                                    <br>
                                                    
                                                    <!-- Form chọn trạng thái mới và nút Xác nhận -->
                                                    <form method="POST" class="flex flex-col gap-2 w-full">
                                                        <input type="hidden" name="id" value="<?php echo $row['madonhang']; ?>">
                                                        <input type="hidden" name="action" value="update_status">
                                                        
                                                        <select name="status" 
                                                                class="text-xs border rounded p-2 focus:outline-none focus:border-blue-500 cursor-pointer w-full">
                                                            <option value="">-- Chọn trạng thái --</option>
                                                            <?php foreach($order_statuses as $key => $val): ?>
                                                                <?php if($key != $row['trangthai']): ?>
                                                                    <option value="<?php echo $key; ?>">
                                                                        <?php echo $val['label']; ?>
                                                                    </option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        
                                                        <button type="submit" 
                                                                class="btn-toggle"
                                                                onclick="return confirm('Bạn có chắc muốn cập nhật trạng thái đơn hàng #<?php echo $row['madonhang']; ?>?');">
                                                            <i class="fas fa-check-circle"></i> Xác nhận
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td class="p-4 align-middle border-b dark:border-white/40">
                                                <div class="flex flex-col gap-3 items-center">
                                                    <!-- Nút xem chi tiết -->
                                                    <a href="order_detail.php?id=<?php echo $row['madonhang']; ?>" 
                                                       class="px-4 py-2 text-xs bg-blue-500 text-white rounded hover:bg-blue-600 transition w-full text-center">
                                                        <i class="fas fa-eye"></i> Xem chi tiết
                                                    </a>
                                                    <br>
                                                    <!-- Form xóa đơn hàng -->
                                                    <form method="POST" class="w-full">
                                                        <input type="hidden" name="id" value="<?php echo $row['madonhang']; ?>">
                                                        <input type="hidden" name="action" value="delete_order">
                                                        <button type="submit" 
                                                                class="btn-delete"
                                                                onclick="return confirm('Bạn có chắc muốn xóa đơn hàng #<?php echo $row['madonhang']; ?>? Hành động này không thể hoàn tác.');">
                                                            <i class="fas fa-trash"></i> Xóa đơn hàng
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="p-8 text-center">
                                                <div class="flex flex-col items-center justify-center">
                                                    <i class="fas fa-shopping-cart text-gray-300 text-4xl mb-4"></i>
                                                    <p class="text-gray-500 text-lg">Không có đơn hàng nào</p>
                                                    <?php if(!empty($search) || $status_filter !== ''): ?>
                                                        <a href="orders.php" class="mt-2 text-blue-500 hover:text-blue-700">
                                                            Xóa bộ lọc
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php 
        $stmt->close();
        $conn->close();
        include '../includes/footer.php'; 
        ?>
    </div>
</main>

<style>
.truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.action-buttons form {
    margin-bottom: 5px;
}
</style>