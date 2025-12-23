<?php
require_once '../../../conn.php';
session_start();

if (!isset($_GET['id'])) { header("Location: tables.php"); exit(); }
$id = $conn->real_escape_string($_GET['id']);

// Lấy thông tin hiện tại
$sql = "SELECT * FROM tbl_nguoidung WHERE manguoidung = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $hoten = $conn->real_escape_string($_POST['hoten']);
    $vaitro = $_POST['vaitro'];
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;

    $update_sql = "UPDATE tbl_nguoidung SET hotennguoidung = ?, vaitro = ?, trangthai = ? WHERE manguoidung = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssis", $hoten, $vaitro, $trangthai, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Cập nhật thành công!";
        header("Location: tables.php");
        exit();
    }
}

if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <div class="w-full px-6 py-6 mx-auto">
        <div class="form-card p-6 bg-white rounded-2xl shadow-xl">
            <h3 class="font-bold">Sửa người dùng: <?php echo htmlspecialchars($user['hotennguoidung']); ?></h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                    <div class="form-group">
                        <label class="form-label font-semibold">Họ và tên</label>
                        <input type="text" name="hoten" id="hoten" value="<?php echo htmlspecialchars($user['hotennguoidung']); ?>" required class="w-full p-2 border rounded-lg">
                    </div>
                    <div class="form-group">
                        <label class="form-label font-semibold">Vai trò</label>
                        <select name="vaitro" id="vaitro" class="w-full p-2 border rounded-lg">
                            <option value="user" <?php echo $user['vaitro'] == 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="staff" <?php echo $user['vaitro'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                            <option value="admin" <?php echo $user['vaitro'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <button type="button" onclick="window.location.href='tables.php'" class="px-6 py-2 bg-gray-500 text-white rounded-lg">Hủy</button>
                    <button type="button" onclick="confirmUpdate()" class="px-6 py-2 bg-blue-500 text-white rounded-lg">Cập nhật</button>
                    <button type="submit" id="submitBtn" style="display:none"></button>
                </div>
            </form>
        </div>
        <?php include '../includes/footer.php'; ?>
    </div>
</main>