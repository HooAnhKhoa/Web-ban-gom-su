<?php
session_start();
require_once '../../../../conn.php';

// Lấy danh sách loại sản phẩm
$category_result = $conn->query("SELECT maloai, tenloai FROM tbl_loai ORDER BY tenloai");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tensanpham = trim($_POST['tensanpham']);
    $maloai = $_POST['maloai'];
    $gia = $_POST['gia'];
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;
    
    // Xử lý upload ảnh
    $hinhAnh = '';
    if (isset($_FILES['hinhAnh']) && $_FILES['hinhAnh']['error'] == 0) {
        $new_filename = uniqid('product_', true) . '.' . pathinfo($_FILES['hinhAnh']['name'], PATHINFO_EXTENSION);
        if (move_uploaded_file($_FILES['hinhAnh']['tmp_name'], '../../../uploads/products/' . $new_filename)) { $hinhAnh = $new_filename; }
    }

    $insert_sql = "INSERT INTO tbl_sanpham (tensanpham, maloai, gia, hinhAnh, trangthai) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("sidsi", $tensanpham, $maloai, $gia, $hinhAnh, $trangthai);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Thêm sản phẩm thành công!';
        header('Location: ../products.php');
        exit();
    }
}

include '../../includes/header.php'; 
include '../../includes/sidebar.php'; 
?>

<main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
    <div class="w-full px-6 py-6 mx-auto">
        <div class="bg-white p-6 rounded-2xl shadow-xl">
            <h6 class="font-bold mb-4">Thông tin sản phẩm mới</h6>
            <form method="POST" enctype="multipart/form-data">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label class="font-semibold">Tên sản phẩm</label>
                        <input type="text" name="tensanpham" required class="w-full p-2 border rounded-lg">
                    </div>
                    <div class="form-group">
                        <label class="font-semibold">Giá</label>
                        <input type="number" name="gia" required class="w-full p-2 border rounded-lg">
                    </div>
                    <div class="form-group">
                        <label class="font-semibold">Loại sản phẩm</label>
                        <select name="maloai" class="w-full p-2 border rounded-lg">
                            <?php while($cat = $category_result->fetch_assoc()): ?>
                                <option value="<?php echo $cat['maloai']; ?>"><?php echo htmlspecialchars($cat['tenloai']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="font-semibold">Hình ảnh</label>
                        <input type="file" name="hinhAnh" accept="image/*" class="w-full p-2 border rounded-lg">
                    </div>
                </div>
                <div class="mt-6 flex gap-3">
                    <a href="../products.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg">Quay lại</a>
                    <button type="submit" class="px-6 py-2 bg-green-500 text-white rounded-lg">Lưu sản phẩm</button>
                </div>
            </form>
        </div>
        <?php include '../../includes/footer.php'; ?>
    </div>
</main>