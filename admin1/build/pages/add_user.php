<?php
require_once '../../../conn.php';
session_start();

// Logic xử lý thêm người dùng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) { die("Token không hợp lệ!"); }
    
    $hoten = $conn->real_escape_string($_POST['hoten']);
    $tendangnhap = $conn->real_escape_string($_POST['tendangnhap']);
    $matkhau = password_hash($_POST['matkhau'], PASSWORD_DEFAULT);
    $sodienthoai = $conn->real_escape_string($_POST['sodienthoai']);
    $vaitro = $_POST['vaitro'];
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;
    
    // Tạo mã người dùng tự động
    $prefix = strtoupper(substr($vaitro, 0, 1));
    $manguoidung = $prefix . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    $insert_sql = "INSERT INTO tbl_nguoidung (manguoidung, hotennguoidung, tendangnhap, matkhau, sodienthoai, vaitro, trangthai, ngaytao) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssssssi", $manguoidung, $hoten, $tendangnhap, $matkhau, $sodienthoai, $vaitro, $trangthai);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Thêm người dùng thành công!";
        header("Location: tables.php");
        exit();
    }
}

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
        <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main>
        <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
            <nav>
                <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                    <li class="text-sm leading-normal text-white opacity-50">Pages</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Quản lý người dùng</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Thêm mới</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Thêm người dùng mới</h6>
            </nav>
        </div>
    </nav>
    <div class="w-full px-6 py-6 mx-auto">
        <div class="flex flex-wrap -mx-3">
            <div class="w-full max-w-full px-3 mx-auto mt-0 lg:w-8/12">
                <div class="form-section p-6 bg-white rounded-2xl shadow-xl">
                    <h3 class="mb-4 font-bold">Thêm người dùng mới</h3>
                    <form method="POST" id="userForm">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label class="form-label font-semibold">Họ và tên</label>
                                <input type="text" name="hoten" id="hoten" required class="w-full p-2 border rounded-lg">
                            </div>
                            <div class="form-group">
                                <label class="form-label font-semibold">Tên đăng nhập</label>
                                <input type="text" name="tendangnhap" id="tendangnhap" required class="w-full p-2 border rounded-lg">
                            </div>
                            <div class="form-group">
                                <label class="form-label font-semibold">Mật khẩu</label>
                                <input type="password" name="matkhau" id="matkhau" required class="w-full p-2 border rounded-lg">
                            </div>
                            <div class="form-group">
                                <label class="form-label font-semibold">Vai trò</label>
                                <select name="vaitro" id="vaitro" class="w-full p-2 border rounded-lg">
                                    <option value="user">Người dùng</option>
                                    <option value="staff">Nhân viên</option>
                                    <option value="admin">Quản trị viên</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-6 flex gap-3">
                            <a href="tables.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg">Hủy</a>
                            <button type="button" onclick="validateAndSubmit()" class="px-6 py-2 bg-green-500 text-white rounded-lg">Lưu lại</button>
                            <button type="submit" id="submitBtn" style="display:none"></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php include '../includes/footer.php'; ?>
    </div>
</main>