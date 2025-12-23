<?php
if(!isset($_SESSION)) { session_start(); }

// 1. Kiểm tra đăng nhập và kết nối Database
require_once '../../../conn.php'; 

// Logic xử lý người dùng (CRUD)
$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['user_email']) : '';

// Xử lý khi có POST request (cập nhật trạng thái/xóa)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'], $_POST['id'])) {
    $id = $_POST['id'];
    if ($_POST['action'] == 'toggle_status') {
        $update_sql = "UPDATE tbl_nguoidung SET trangthai = 1 - trangthai WHERE manguoidung = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        header("Location: tables.php?success=Cập nhật trạng thái thành công");
        exit();
    }
    if ($_POST['action'] == 'delete_user') {
        $delete_sql = "DELETE FROM tbl_nguoidung WHERE manguoidung = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("s", $id);
        $stmt->execute();
        header("Location: tables.php?success=Xóa người dùng thành công");
        exit();
    }
}

// Lấy danh sách user
$sql = "SELECT manguoidung, hotennguoidung, tendangnhap, trangthai, ngaytao, sodienthoai, vaitro FROM tbl_nguoidung ORDER BY ngaytao DESC";
$result = $conn->query($sql);

// 2. Nhúng Giao diện
include '../includes/header.php';  // Đi lên 1 cấp vào build rồi vào includes
include '../includes/sidebar.php'; // Đi lên 1 cấp vào build rồi vào includes
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main>
        <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
            <nav>
                <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                    <li class="text-sm leading-normal text-white opacity-50">Pages</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Quản lý người dùng</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Danh sách người dùng</h6>
            </nav>
            <div class="flex items-center mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:flex lg:basis-auto">
                <div class="flex items-center md:ml-auto md:pr-4">
                    <form method="GET" class="relative flex flex-wrap items-stretch w-full transition-all rounded-lg ease">
                        <span class="absolute z-50 flex items-center h-full px-2.5 text-center pointer-events-none">
                            <i class="fas fa-search text-slate-400"></i>
                        </span>
                        <input type="text" name="search" class="pl-9 text-sm focus:shadow-primary-outline ease w-1/100 leading-5.6 relative -ml-px block min-w-0 flex-auto rounded-lg border border-solid border-gray-300 bg-white py-2 pr-3 text-gray-700 transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none" placeholder="Tìm kiếm...">
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="w-full px-6 py-6 mx-auto">
        <div class="flex flex-wrap -mx-3">
            <div class="flex-none w-full max-w-full px-3">
                <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl flex justify-between">
                        <h6 class="dark:text-white">Bảng người dùng</h6>
                        <a href="add_user.php" class="px-4 py-2 text-xs font-bold text-white bg-blue-500 rounded-lg uppercase">Thêm mới</a>
                    </div>
                    <div class="flex-auto px-0 pt-0 pb-2">
                        <div class="table-responsive p-0">
                            <table class="items-center w-full mb-0 align-top border-collapse text-slate-500">
                                <thead class="align-bottom">
                                    <tr>
                                        <th class="px-6 py-3 font-bold text-left uppercase text-xxs opacity-70">ID</th>
                                        <th class="px-6 py-3 font-bold text-left uppercase text-xxs opacity-70">Người dùng</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Vai trò</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Trạng thái</th>
                                        <th class="px-6 py-3 font-bold text-center uppercase text-xxs opacity-70">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="p-4 align-middle text-sm">#<?php echo $row['manguoidung']; ?></td>
                                        <td class="p-2">
                                            <div class="flex px-2 py-1">
                                                <div class="flex flex-col justify-center">
                                                    <h6 class="mb-0 text-sm leading-normal"><?php echo htmlspecialchars($row['hotennguoidung']); ?></h6>
                                                    <p class="mb-0 text-xs text-slate-400">@<?php echo htmlspecialchars($row['tendangnhap']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="p-2 text-center align-middle text-sm uppercase font-bold text-blue-500"><?php echo $row['vaitro']; ?></td>
                                        <td class="p-2 text-center">
                                            <span class="status-badge <?php echo ($row['trangthai'] == 1 ? 'status-active' : 'status-inactive'); ?>">
                                                <?php echo ($row['trangthai'] == 1 ? 'Hoạt động' : 'Khóa'); ?>
                                            </span>
                                        </td>
                                        <td class="p-2 align-middle">
                                            <div class="action-buttons justify-center">
                                                <button onclick="window.location.href='edit_user.php?id=<?php echo $row['manguoidung']; ?>'" class="btn-edit">Sửa</button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="id" value="<?php echo $row['manguoidung']; ?>">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <button type="submit" class="btn-toggle">Đổi TT</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include '../includes/footer.php'; // Nhúng footer ?>
    </div>
</main>