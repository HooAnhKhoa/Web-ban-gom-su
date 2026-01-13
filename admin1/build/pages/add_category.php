<?php
session_start();
require_once '../../../conn.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../sign-in.html");
    exit();
}

// Xử lý Form
$tenloai = '';
$mota = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tenloai = trim($_POST['tenloai'] ?? '');
    $mota = trim($_POST['mota'] ?? '');
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;
    
    // Xử lý upload ảnh
    $anh = ''; // Mặc định rỗng
    if (isset($_FILES['anh']) && $_FILES['anh']['error'] == 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $file_extension = strtolower(pathinfo($_FILES['anh']['name'], PATHINFO_EXTENSION));
        
        // Kiểm tra định dạng file
        if (in_array($file_extension, $allowed_extensions)) {
            // Kiểm tra kích thước file (tối đa 5MB)
            if ($_FILES['anh']['size'] <= 5 * 1024 * 1024) {
                $new_filename = uniqid('cat_', true) . '.' . $file_extension;
                $upload_path = '../../../uploads/categories/' . $new_filename;
                
                // Tạo thư mục nếu chưa có
                $upload_dir = '../../../uploads/categories/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['anh']['tmp_name'], $upload_path)) {
                    $anh = $new_filename;
                } else {
                    $error = "Lỗi khi upload ảnh!";
                }
            } else {
                $error = "Kích thước ảnh quá lớn (tối đa 5MB)!";
            }
        } else {
            $error = "Định dạng ảnh không hợp lệ. Chỉ chấp nhận: JPG, JPEG, PNG, GIF, WEBP!";
        }
    }

    if (empty($error) && empty($tenloai)) {
        $error = "Vui lòng nhập tên loại!";
    }
    
    if (empty($error)) {
        // Kiểm tra xem tên loại đã tồn tại chưa
        $check_sql = "SELECT maloai FROM tbl_loai WHERE tenloai = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $tenloai);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = "Tên loại sản phẩm đã tồn tại!";
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // SQL Insert với 4 cột: tenloai, mota, trangthai, anh
            $stmt = $conn->prepare("INSERT INTO tbl_loai (tenloai, mota, trangthai, anh) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $tenloai, $mota, $trangthai, $anh);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Thêm loại sản phẩm thành công!';
                header('Location: categories.php');
                exit();
            } else {
                $error = "Lỗi: " . $conn->error;
            }
            $stmt->close();
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
                    <li class="text-sm leading-normal text-white opacity-50">Quản lý</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Quản lý loại</li>
                    <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:content-['/']">Thêm mới</li>
                </ol>
                <h6 class="mb-0 font-bold text-white capitalize">Thêm loại sản phẩm mới</h6>
            </nav>
        </div>
    </nav>

    <div class="w-full px-6 py-6 mx-auto">
        <div class="flex flex-wrap -mx-3">
            <div class="flex-none w-full max-w-full px-3">
                <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 shadow-xl dark:bg-slate-850 rounded-2xl">
                    <div class="p-6 pb-0 mb-0 border-b-0 rounded-t-2xl">
                        <h6 class="dark:text-white">Thêm loại sản phẩm mới</h6>
                        <?php if(!empty($error)): ?>
                            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg mt-4" role="alert">
                                <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex-auto p-6">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="flex flex-wrap -mx-3">
                                <!-- Tên loại -->
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">
                                        Tên loại <span class="text-red-500">*</span>
                                    </label>
                                    <input name="tenloai" value="<?php echo htmlspecialchars($tenloai); ?>" required 
                                           class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none" 
                                           type="text" 
                                           placeholder="Ví dụ: Gốm Bát Tràng">
                                    <p class="text-gray-600 text-xs mt-1">Tên loại phải là duy nhất</p>
                                </div>

                                <!-- Hình ảnh -->
                                <div class="w-full md:w-1/2 px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">
                                        Hình ảnh
                                    </label>
                                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
                                        <input name="anh" type="file" accept="image/*" 
                                               class="hidden" 
                                               id="anhInput"
                                               onchange="previewImage(event)">
                                        <label for="anhInput" class="cursor-pointer block">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                                                <span class="text-sm text-gray-600">Click để chọn ảnh</span>
                                                <span class="text-xs text-gray-500 mt-1">(JPG, PNG, GIF, WEBP - tối đa 5MB)</span>
                                            </div>
                                        </label>
                                        <div id="imagePreview" class="mt-4 hidden">
                                            <img id="preview" class="max-h-32 mx-auto rounded-lg shadow">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Mô tả -->
                                <div class="w-full px-3 mb-4">
                                    <label class="block uppercase tracking-wide text-gray-700 text-xs font-bold mb-2">Mô tả</label>
                                    <textarea name="mota" rows="3" 
                                              class="focus:shadow-primary-outline dark:bg-slate-850 dark:text-white text-sm leading-5.6 ease block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all focus:border-blue-500 focus:outline-none"
                                              placeholder="Mô tả về loại sản phẩm..."><?php echo htmlspecialchars($mota); ?></textarea>
                                </div>
                                
                                <!-- Trạng thái -->
                                <div class="w-full px-3 mb-6">
                                    <div class="flex items-center">
                                        <input name="trangthai" type="checkbox" 
                                               id="trangthai" 
                                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
                                               checked>
                                        <label for="trangthai" class="ml-2 text-sm font-medium text-gray-700">
                                            Hiển thị loại sản phẩm
                                        </label>
                                    </div>
                                    <p class="text-gray-600 text-xs mt-1">Nếu không chọn, loại sản phẩm sẽ bị ẩn</p>
                                </div>
                            </div>
                            
                            <!-- Nút hành động -->
                            <div class="flex items-center justify-end mt-8 gap-3">
                                <a href="categories.php" 
                                   class="px-6 py-3 font-bold text-white uppercase bg-gray-500 rounded-lg hover:bg-gray-600 hover:shadow-md text-xs transition duration-200">
                                    <i class="fas fa-times mr-2"></i> Hủy
                                </a>
                                <button type="submit" 
                                        class="px-6 py-3 font-bold text-white uppercase bg-blue-500 rounded-lg hover:bg-blue-600 hover:shadow-md text-xs transition duration-200">
                                    <i class="fas fa-save mr-2"></i> Lưu loại sản phẩm
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php include '../includes/footer.php'; ?>
    </div>
</main>

<script>
// Xem trước ảnh
function previewImage(event) {
    const input = event.target;
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            previewContainer.classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Xóa ảnh preview
function removePreview() {
    const input = document.getElementById('anhInput');
    const preview = document.getElementById('preview');
    const previewContainer = document.getElementById('imagePreview');
    
    input.value = '';
    preview.src = '';
    previewContainer.classList.add('hidden');
}
</script>

<style>
/* Tùy chỉnh style cho form */
.border-dashed {
    border-style: dashed;
}

.hidden {
    display: none;
}

/* Hiệu ứng hover cho file input */
#anhInput + label:hover {
    background-color: #f9fafb;
}

/* Style cho ảnh preview */
#preview {
    max-width: 100%;
    height: auto;
    object-fit: cover;
}

/* Transition cho các nút */
.transition {
    transition: all 0.2s ease-in-out;
}
</style>