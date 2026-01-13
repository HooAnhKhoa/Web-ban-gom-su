<?php
if(!isset($_SESSION)) { 
    session_start(); 
    ob_start();
}

// 1. Kiểm tra đăng nhập và kết nối Database
require_once '../../../conn.php'; 

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['user_email']) : '';

// 2. Xử lý logic nghiệp vụ (Tìm kiếm, Đổi trạng thái, Xóa)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Xử lý POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = intval($_POST['id']); // Thêm kiểm tra an toàn
    
    if ($_POST['action'] == 'toggle_status') {
        $update_sql = "UPDATE tbl_loai SET trangthai = 1 - trangthai WHERE maloai = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Cập nhật trạng thái thành công!';
        } else {
            $_SESSION['error'] = 'Cập nhật trạng thái thất bại!';
        }
        $stmt->close();
        header("Location: categories.php");
        exit();
    }
    
    if ($_POST['action'] == 'delete_category') {
        // Kiểm tra ràng buộc đơn hàng trước khi xóa
        $check_sql = "SELECT COUNT(*) FROM tbl_sanpham WHERE maloai = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_stmt->bind_result($count);
        $check_stmt->fetch();
        $check_stmt->close();
        
        if ($count > 0) {
            $_SESSION['error'] = 'Không thể xóa loại vì đã có sản phẩm!';
        } else {
            $delete_sql = "DELETE FROM tbl_loai WHERE maloai = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Xóa loại thành công!';
            } else {
                $_SESSION['error'] = 'Xóa loại thất bại!';
            }
            $stmt->close();
        }
        header("Location: categories.php");
        exit();
    }
}

// Lấy danh sách loại
$sql = "SELECT l.*, 
        (SELECT COUNT(*) FROM tbl_sanpham WHERE maloai = l.maloai) as product_count 
        FROM tbl_loai l WHERE 1=1";
if (!empty($search)) {
    $sql .= " AND (l.tenloai LIKE ? OR l.mota LIKE ?)";
    $searchTerm = "%$search%";
}
$sql .= " ORDER BY l.maloai DESC";

$stmt = $conn->prepare($sql);
if (!empty($search)) {
    $stmt->bind_param("ss", $searchTerm, $searchTerm);
}
$stmt->execute();
$result = $stmt->get_result();

// 3. Nhúng Giao diện Header & Sidebar
include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main>
        <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
            <nav>
                <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                    <li class="text-sm leading-normal text-white opacity-50">Pages</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Quản lý loại sản phẩm</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Danh sách loại sản phẩm</h6>
            </nav>
            <div class="flex items-center mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:flex lg:basis-auto">
                <div class="flex items-center md:ml-auto md:pr-4">
                    <form method="GET" class="relative flex flex-wrap items-stretch w-full transition-all rounded-lg ease">
                        <span class="absolute z-50 flex items-center h-full px-2.5 text-center pointer-events-none">
                            <i class="fas fa-search text-slate-400"></i>
                        </span>
                        <input type="text" name="search" class="pl-9 text-sm focus:shadow-primary-outline ease w-1/100 leading-5.6 relative -ml-px block min-w-0 flex-auto rounded-lg border border-solid border-gray-300 bg-white py-2 pr-3 text-gray-700 transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none" placeholder="Tìm loại sản phẩm..." value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="w-full px-6 py-6 mx-auto">
        <!-- HIỂN THỊ THÔNG BÁO Ở ĐÂY -->
        <?php if (isset($success_message)): ?>
        <div class="mb-6">
            <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="mb-6">
            <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex flex-wrap -mx-3">
            <div class="flex-none w-full max-w-full px-3">
                <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl flex justify-between items-center">
                        <h6 class="dark:text-white">Bảng loại sản phẩm</h6>
                        <a href="add_category.php" class="px-4 py-2 text-xs font-bold text-white bg-blue-500 rounded-lg uppercase shadow-md hover:shadow-lg transition-all">
                            <i class="fas fa-plus mr-1"></i> Thêm mới
                        </a>
                    </div>
                    <div class="flex-auto px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="items-center w-full mb-0 align-top border-collapse text-slate-500">
                                <thead class="align-bottom">
                                    <tr>
                                        <th class="px-6 py-3 font-bold text-left uppercase text-xxs opacity-70">Mã loại</th>
                                        <th class="px-6 py-3 font-bold text-left uppercase text-xxs opacity-70">Tên loại</th>
                                        <th class="px-6 py-3 font-bold text-left uppercase text-xxs opacity-70">Mô tả</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Số SP</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if($result->num_rows > 0): ?>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td class="p-2 align-middle border-b dark:border-white/40">
                                                <span class="text-sm font-semibold">#<?php echo $row['maloai']; ?></span>
                                            </td>
                                            <td class="p-2 align-middle border-b dark:border-white/40">
                                                <span class="text-sm font-semibold dark:text-white"><?php echo htmlspecialchars($row['tenloai']); ?></span>
                                            </td>
                                            <td class="p-2 align-middle border-b dark:border-white/40">
                                                <span class="text-sm"><?php echo htmlspecialchars($row['mota'] ?? 'Chưa có mô tả'); ?></span>
                                            </td>
                                            <td class="p-2 align-middle border-b dark:border-white/40 text-center">
                                                <span class="px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    <?php echo $row['product_count']; ?> SP
                                                </span>
                                            </td>                                       
                                            <td class="p-2 align-middle border-b dark:border-white/40">
                                                <div class="action-buttons justify-center">
                                                    <a href="edit_category.php?id=<?php echo $row['maloai']; ?>" class="btn-edit">
                                                        Sửa
                                                    </a>
                                    
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Xác nhận xóa sản phẩm này?');">
                                                        <input type="hidden" name="id" value="<?php echo $row['maloai']; ?>">
                                                        <input type="hidden" name="action" value="delete_category">
                                                        <button type="submit" class="btn-delete">
                                                            Xóa
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="p-4 text-center border-b dark:border-white/40">
                                                <p class="text-slate-500">Không tìm thấy loại sản phẩm nào.</p>
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
        // Đóng statement và connection
        $stmt->close();
        $conn->close();
        ?>
        
        <?php include '../includes/footer.php'; ?>
    </div>
</main>