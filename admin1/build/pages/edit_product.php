<?php
require_once '../../../conn.php';
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || ($_SESSION['user_role'] ?? 'user') !== 'admin') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) { 
    header("Location: ../products.php"); 
    exit(); 
}

$id = $conn->real_escape_string($_GET['id']);

// Lấy thông tin sản phẩm
$product_sql = "SELECT * FROM tbl_sanpham WHERE masanpham = ?";
$product_stmt = $conn->prepare($product_sql);
$product_stmt->bind_param("i", $id);
$product_stmt->execute();
$product_result = $product_stmt->get_result();

if ($product_result->num_rows === 0) {
    $_SESSION['error'] = "Sản phẩm không tồn tại!";
    header("Location: ../products.php");
    exit();
}

$product = $product_result->fetch_assoc();
$product_stmt->close();

// Lấy danh sách loại sản phẩm
$category_sql = "SELECT maloai, tenloai FROM tbl_loai ORDER BY tenloai";
$category_result = $conn->query($category_sql);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tensanpham = $conn->real_escape_string($_POST['tensanpham']);
    $maloai = intval($_POST['maloai']);
    $xuatxu = $conn->real_escape_string($_POST['xuatxu']);
    $chatlieu = $conn->real_escape_string($_POST['chatlieu']);
    $mota = $conn->real_escape_string($_POST['mota']);
    $gia = floatval($_POST['gia']);
    $kichthuoc = $conn->real_escape_string($_POST['kichthuoc']);
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;
    
    // Xử lý upload hình ảnh
    $hinhAnh = $product['hinhAnh']; // Giữ ảnh cũ nếu không upload ảnh mới
    
    if (isset($_FILES['hinhAnh']) && $_FILES['hinhAnh']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['hinhAnh']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            // Xóa ảnh cũ nếu có
            if (!empty($product['hinhAnh']) && file_exists('../../../uploads/products/' . $product['hinhAnh'])) {
                unlink('../../../uploads/products/' . $product['hinhAnh']);
            }
            
            $extension = pathinfo($_FILES['hinhAnh']['name'], PATHINFO_EXTENSION);
            $new_filename = uniqid('product_', true) . '.' . $extension;
            $upload_path = '../../../uploads/products/' . $new_filename;
            
            if (move_uploaded_file($_FILES['hinhAnh']['tmp_name'], $upload_path)) {
                $hinhAnh = $new_filename;
            }
        }
    }
    
    // Xóa ảnh nếu người dùng chọn xóa
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
        if (!empty($product['hinhAnh']) && file_exists('../../../uploads/products/' . $product['hinhAnh'])) {
            unlink('../../../uploads/products/' . $product['hinhAnh']);
        }
        $hinhAnh = '';
    }

    $update_sql = "UPDATE tbl_sanpham SET 
                    tensanpham = ?, 
                    maloai = ?, 
                    xuatxu = ?, 
                    chatlieu = ?, 
                    mota = ?, 
                    gia = ?, 
                    hinhAnh = ?, 
                    kichthuoc = ?, 
                    trangthai = ? 
                   WHERE masanpham = ?";
    
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssssdssii", 
        $tensanpham, 
        $maloai, 
        $xuatxu, 
        $chatlieu, 
        $mota, 
        $gia, 
        $hinhAnh, 
        $kichthuoc, 
        $trangthai, 
        $id
    );
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Cập nhật sản phẩm thành công!";
        header("Location: ../products.php");
        exit();
    } else {
        $_SESSION['error'] = "Lỗi khi cập nhật sản phẩm: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa sản phẩm - Admin</title>
    <link rel="stylesheet" href="../assets/css/argon-dashboard-tailwind.css?v=1.0.1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #4a5568;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.375rem;
            transition: border-color 0.15s ease-in-out;
        }
        .form-control:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(195deg, #4299e1, #3182ce);
            color: white;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: linear-gradient(195deg, #718096, #4a5568);
            color: white;
        }
        .btn-secondary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .btn-danger {
            background: linear-gradient(195deg, #e53e3e, #c53030);
            color: white;
        }
        .btn-danger:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .image-preview {
            width: 200px;
            height: 200px;
            border: 2px solid #e2e8f0;
            border-radius: 0.375rem;
            overflow: hidden;
            margin-top: 0.5rem;
            position: relative;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .delete-image-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(229, 62, 62, 0.9);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        .checkbox-label input {
            margin-right: 0.5rem;
        }
        .required::after {
            content: ' *';
            color: #e53e3e;
        }
        .grid-cols-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        @media (max-width: 768px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="m-0 font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-gray-50 text-slate-500">
    <div class="absolute w-full bg-blue-500 dark:hidden min-h-75"></div>

    <!-- Include sidebar -->
    <?php include '../includes/sidebar.php'; ?>

    <main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
        <!-- Navbar -->
        <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main navbar-scroll="false">
            <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
                <nav>
                    <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                        <li class="text-sm leading-normal">
                            <a class="text-white opacity-50" href="javascript:;">Pages</a>
                        </li>
                        <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']">
                            <a href="../products.php" class="text-white">Quản lý sản phẩm</a>
                        </li>
                        <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">
                            Sửa sản phẩm
                        </li>
                    </ol>
                    <h6 class="mb-0 font-bold text-white capitalize">Sửa sản phẩm: <?php echo htmlspecialchars($product['tensanpham']); ?></h6>
                </nav>
            </div>
        </nav>

        <div class="w-full px-6 py-6 mx-auto">
            <div class="form-container">
                <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 border-transparent border-solid shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
                    <div class="p-6 pb-0 mb-0 border-b-0 border-b-solid rounded-t-2xl border-b-transparent">
                        <h6 class="dark:text-white">Thông tin sản phẩm</h6>
                    </div>
                    <div class="flex-auto p-6">
                        <form method="POST" action="" enctype="multipart/form-data" id="editForm">
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="mb-4 p-3 bg-red-100 border border-red-200 text-red-700 rounded">
                                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                                    <?php unset($_SESSION['error']); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="grid-cols-2">
                                <!-- Cột trái -->
                                <div>
                                    <div class="form-group">
                                        <label class="form-label required">Tên sản phẩm</label>
                                        <input type="text" name="tensanpham" class="form-control" required 
                                               value="<?php echo htmlspecialchars($product['tensanpham']); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label required">Loại sản phẩm</label>
                                        <select name="maloai" class="form-control" required>
                                            <option value="">-- Chọn loại sản phẩm --</option>
                                            <?php if ($category_result && $category_result->num_rows > 0): ?>
                                                <?php while($category = $category_result->fetch_assoc()): ?>
                                                    <option value="<?php echo $category['maloai']; ?>" 
                                                        <?php echo $product['maloai'] == $category['maloai'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($category['tenloai']); ?>
                                                    </option>
                                                <?php endwhile; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label required">Giá</label>
                                        <input type="number" name="gia" class="form-control" required min="0" step="1000" 
                                               value="<?php echo $product['gia']; ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Xuất xứ</label>
                                        <input type="text" name="xuatxu" class="form-control" 
                                               value="<?php echo htmlspecialchars($product['xuatxu']); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Chất liệu</label>
                                        <input type="text" name="chatlieu" class="form-control" 
                                               value="<?php echo htmlspecialchars($product['chatlieu']); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Kích thước</label>
                                        <input type="text" name="kichthuoc" class="form-control" placeholder="VD: 30x40cm"
                                               value="<?php echo htmlspecialchars($product['kichthuoc']); ?>">
                                    </div>
                                </div>
                                
                                <!-- Cột phải -->
                                <div>
                                    <div class="form-group">
                                        <label class="form-label">Hình ảnh</label>
                                        <input type="file" name="hinhAnh" class="form-control" accept="image/*" onchange="previewImage(this)">
                                        
                                        <?php if (!empty($product['hinhAnh'])): ?>
                                            <div class="image-preview" id="imagePreview">
                                                <img src="../../../uploads/products/<?php echo htmlspecialchars($product['hinhAnh']); ?>" 
                                                     alt="Preview" id="previewImage">
                                                <button type="button" class="delete-image-btn" onclick="deleteImage()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <input type="hidden" name="delete_image" id="deleteImage" value="0">
                                            </div>
                                        <?php else: ?>
                                            <div class="image-preview" id="imagePreview">
                                                <span>Chưa có hình ảnh</span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="form-label">Mô tả</label>
                                        <textarea name="mota" class="form-control" rows="6"><?php echo htmlspecialchars($product['mota']); ?></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label class="checkbox-label">
                                            <input type="checkbox" name="trangthai" value="1" 
                                                <?php echo $product['trangthai'] == 1 ? 'checked' : ''; ?>>
                                            Hiển thị sản phẩm
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mt-6">
                                <a href="../products.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                                </a>
                                <button type="button" onclick="confirmUpdate()" class="btn btn-primary ml-2">
                                    <i class="fas fa-save mr-2"></i>Lưu thay đổi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const deleteInput = document.getElementById('deleteImage');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (preview.querySelector('img')) {
                        preview.querySelector('img').src = e.target.result;
                    } else {
                        preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                    }
                    
                    // Hiển thị nút xóa
                    if (!preview.querySelector('.delete-image-btn')) {
                        const deleteBtn = document.createElement('button');
                        deleteBtn.type = 'button';
                        deleteBtn.className = 'delete-image-btn';
                        deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                        deleteBtn.onclick = deleteImage;
                        preview.appendChild(deleteBtn);
                    }
                    
                    // Reset delete flag
                    if (deleteInput) {
                        deleteInput.value = '0';
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function deleteImage() {
            const preview = document.getElementById('imagePreview');
            const deleteInput = document.getElementById('deleteImage');
            
            Swal.fire({
                title: 'Xác nhận xóa',
                text: 'Bạn có chắc chắn muốn xóa hình ảnh này?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    preview.innerHTML = '<span>Chưa có hình ảnh</span>';
                    document.querySelector('input[name="hinhAnh"]').value = '';
                    
                    if (deleteInput) {
                        deleteInput.value = '1';
                    }
                    
                    Swal.fire(
                        'Đã xóa!',
                        'Hình ảnh đã được xóa.',
                        'success'
                    );
                }
            });
        }
        
        function confirmUpdate() {
            Swal.fire({
                title: 'Xác nhận cập nhật',
                text: 'Bạn có chắc chắn muốn lưu các thay đổi?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Lưu',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('editForm').submit();
                }
            });
        }
        
        // Hiển thị thông báo thành công nếu có
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: '<?php echo $_SESSION['success']; ?>',
                timer: 2000,
                showConfirmButton: false
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
    </script>
</body>
</html>

<?php
// Đóng kết nối database
if (isset($conn)) {
    $conn->close();
}
?>