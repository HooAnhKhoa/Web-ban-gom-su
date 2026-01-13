<?php
session_start();
// Kết nối database (đi ra 3 cấp thư mục để về root)
require_once '../../../conn.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../sign-in.html");
    exit();
}

// Lấy danh sách loại sản phẩm cho thẻ select
$category_result = $conn->query("SELECT maloai, tenloai FROM tbl_loai ORDER BY tenloai");

// Xử lý khi submit form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tensanpham = $_POST['tensanpham'];
    $maloai = $_POST['maloai'];
    $gia = $_POST['gia']; 
    $soluong = $_POST['soluong'];
    $mota = $_POST['mota'];
    $xuatxu = $_POST['xuatxu'];
    $chatlieu = $_POST['chatlieu'];
    $kichthuoc = $_POST['kichthuoc'];
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;
    
    // Xử lý upload ảnh
    $hinhAnh = '';
    if (isset($_FILES['hinhAnh']) && $_FILES['hinhAnh']['error'] == 0) {
        // Tạo tên file duy nhất để tránh trùng
        $ext = pathinfo($_FILES['hinhAnh']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('prod_', true) . '.' . $ext;
        $upload_path = '../../../uploads/products/' . $new_filename;
        
        if (move_uploaded_file($_FILES['hinhAnh']['tmp_name'], $upload_path)) {
            $hinhAnh = $new_filename;
        }
    }

    $sql = "INSERT INTO tbl_sanpham (tensanpham, maloai, gia, mota, xuatxu, chatlieu, kichthuoc, hinhAnh, trangthai, soluong) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sissssssii", $tensanpham, $maloai, $gia, $mota, $xuatxu, $chatlieu, $kichthuoc, $hinhAnh, $trangthai, $soluong);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Thêm sản phẩm thành công!';
        header('Location: products.php');
        exit();
    } else {
        $error = "Lỗi: " . $conn->error;
    }
}

// Include Header & Sidebar (Lùi 1 cấp ra folder build)
include '../includes/header.php'; 
include '../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main>
        <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
            <nav>
                <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                    <li class="text-sm leading-normal text-white opacity-50">Pages</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Quản lý sản phẩm</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Thêm mới</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Thêm sản phẩm mới</h6>
            </nav>
        </div>
    </nav>

    <div class="w-full px-6 py-6 mx-auto">
        <div class="flex flex-wrap -mx-3">
            <div class="flex-none w-full max-w-full px-3">
                <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl">
                        <h6 class="dark:text-white">Nhập thông tin sản phẩm</h6>
                        <?php if(isset($error)) echo "<p class='text-red-500 text-sm'>$error</p>"; ?>
                    </div>
                    <div class="flex-auto p-6">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="flex flex-wrap -mx-3">
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Tên sản phẩm</label>
                                    <input name="tensanpham" required class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none" type="text">
                                </div>
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Loại sản phẩm</label>
                                    <select name="maloai" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none">
                                        <?php while($cat = $category_result->fetch_assoc()): ?>
                                            <option value="<?php echo $cat['maloai']; ?>"><?php echo htmlspecialchars($cat['tenloai']); ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Giá (VNĐ)</label>
                                    <input name="gia" required type="number" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Số lượng</label>
                                    <input name="soluong" required type="number" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Hình ảnh</label>
                                    <input name="hinhAnh" type="file" accept="image/*" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="w-full md:w-1/3 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Xuất xứ</label>
                                    <input name="xuatxu" type="text" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="w-full md:w-1/3 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Chất liệu</label>
                                    <input name="chatlieu" type="text" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none">
                                </div>
                                <div class="w-full md:w-1/3 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Kích thước</label>
                                    <input name="kichthuoc" type="text" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none">
                                </div>

                                <div class="w-full px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Mô tả</label>
                                    <textarea name="mota" rows="4" class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none"></textarea>
                                </div>
                                
                                <div class="w-full px-3 mb-4">
                                    <div class="flex items-center min-h-6 pl-0">
                                        <input name="trangthai" checked id="trangthai" type="checkbox" class="w-5 h-5 ease rounded-1.4 duration-250 text-center transition-all after:text-xxs after:font-awesome after:content-['\f00c'] after:text-white checked:bg-blue-500 checked:after:block checked:border-blue-500 relative float-left mt-1 cursor-pointer appearance-none border border-solid border-slate-200 bg-white bg-contain bg-center bg-no-repeat align-top focus:outline-none">
                                        <label for="trangthai" class="w-full ml-2 font-normal cursor-pointer text-slate-700 text-sm">Hiển thị sản phẩm này</label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-end mt-4 gap-3">
                                <a href="products.php" class="px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all bg-gray-500 border-0 rounded-lg cursor-pointer hover:scale-102 active:opacity-85 hover:shadow-xs text-xs leading-pro">Hủy</a>
                                <button type="submit" class="px-6 py-3 font-bold text-center text-white uppercase align-middle transition-all bg-blue-500 border-0 rounded-lg cursor-pointer hover:scale-102 active:opacity-85 hover:shadow-xs text-xs leading-pro">Lưu sản phẩm</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../includes/footer.php'; ?>
    </div>
</main>