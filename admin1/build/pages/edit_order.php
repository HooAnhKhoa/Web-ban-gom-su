<?php
session_start();
require_once '../../../conn.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../sign-in.html");
    exit();
}

// Kiểm tra ID
if (!isset($_GET['id'])) {
    header("Location: orders.php");
    exit();
}
$id = $_GET['id'];

// Lấy thông tin đơn hàng hiện tại
$stmt = $conn->prepare("SELECT * FROM tbl_donhang WHERE madonhang = ?");
$stmt->bind_param("s", $id); // Giả sử madonhang là string (varchar)
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Đơn hàng không tồn tại");
}

// Định nghĩa trạng thái
$order_statuses = [
    0 => 'Chờ xử lý',
    1 => 'Đang giao',
    2 => 'Hoàn thành',
    3 => 'Đã hủy'
];

// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trangthai = $_POST['trangthai'];
    // Có thể thêm logic cập nhật thông tin giao hàng nếu cần
    
    $sql = "UPDATE tbl_donhang SET trangthai=? WHERE madonhang=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $trangthai, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Cập nhật đơn hàng thành công!';
        header('Location: orders.php');
        exit();
    } else {
        $error = "Lỗi: " . $conn->error;
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
                    <li class="text-sm leading-normal text-white opacity-50">Pages</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Quản lý đơn hàng</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Thêm mới</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Sửa đơn hàng</h6>
            </nav>
        </div>
    </nav>

    <div class="w-full px-6 py-6 mx-auto">
        <div class="flex flex-wrap -mx-3">
            <div class="flex-none w-full max-w-full px-3">
                <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl">
                        <h6 class="dark:text-white">Cập nhật đơn hàng #<?php echo htmlspecialchars($order['madonhang']); ?></h6>
                    </div>
                    <div class="flex-auto p-6">
                        <form method="POST">
                            <div class="flex flex-wrap -mx-3">
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Mã đơn hàng</label>
                                    <input value="<?php echo htmlspecialchars($order['madonhang']); ?>" disabled class="bg-gray-200 block w-full appearance-none rounded-lg border border-solid border-gray-300 px-3 py-2 font-normal text-gray-700 outline-none" type="text">
                                </div>
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Mã hóa đơn</label>
                                    <input value="<?php echo htmlspecialchars($order['mahoadon']); ?>" disabled class="bg-gray-200 block w-full appearance-none rounded-lg border border-solid border-gray-300 px-3 py-2 font-normal text-gray-700 outline-none" type="text">
                                </div>
                                
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Thông tin giao hàng (Mã)</label>
                                    <input value="<?php echo htmlspecialchars($order['mathongtingiaohang']); ?>" disabled class="bg-gray-200 block w-full appearance-none rounded-lg border border-solid border-gray-300 px-3 py-2 font-normal text-gray-700 outline-none" type="text">
                                </div>

                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Trạng thái đơn hàng</label>
                                    <select name="trangthai" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none">
                                        <?php foreach($order_statuses as $key => $label): ?>
                                            <option value="<?php echo $key; ?>" <?php echo ($key == $order['trangthai']) ? 'selected' : ''; ?>>
                                                <?php echo $label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end mt-4 gap-3">
                                <a href="orders.php" class="px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all bg-gray-500 border-0 rounded-lg cursor-pointer hover:scale-102 active:opacity-85 hover:shadow-xs text-xs leading-pro">Hủy</a>
                                <button type="submit" class="px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all bg-blue-500 border-0 rounded-lg cursor-pointer hover:scale-102 active:opacity-85 hover:shadow-xs text-xs leading-pro">Cập nhật đơn hàng</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../includes/footer.php'; ?>
    </div>
</main>