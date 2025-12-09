<?php
// File: add_user.php
require '../../../conn.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Xác thực CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token không hợp lệ!");
    }
    
    $hoten = $conn->real_escape_string($_POST['hoten']);
    $tendangnhap = $conn->real_escape_string($_POST['tendangnhap']);
    $matkhau = password_hash($_POST['matkhau'], PASSWORD_DEFAULT);
    $sodienthoai = $conn->real_escape_string($_POST['sodienthoai']);
    $vaitro = $conn->real_escape_string($_POST['vaitro']);
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;
    
    // Kiểm tra username đã tồn tại chưa
    $check_sql = "SELECT COUNT(*) as count FROM tbl_nguoidung WHERE tendangnhap = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("s", $tendangnhap);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    
    if ($count > 0) {
        $_SESSION['error'] = "Tên đăng nhập đã tồn tại!";
        header("Location: add_user.php");
        exit();
    }
    
    // Tạo mã người dùng tự động
    $prefix = strtoupper(substr($vaitro, 0, 1));
    $sql = "SELECT MAX(CAST(SUBSTRING(manguoidung, 2) AS UNSIGNED)) as max_id 
            FROM tbl_nguoidung 
            WHERE manguoidung LIKE '{$prefix}%'";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $next_id = ($row['max_id'] ?? 0) + 1;
    $manguoidung = $prefix . str_pad($next_id, 4, '0', STR_PAD_LEFT);
    
    // Thêm người dùng
    $insert_sql = "INSERT INTO tbl_nguoidung (manguoidung, hotennguoidung, tendangnhap, matkhau, sodienthoai, vaitro, trangthai, ngaytao) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssssssi", $manguoidung, $hoten, $tendangnhap, $matkhau, $sodienthoai, $vaitro, $trangthai);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Thêm người dùng thành công!";
        header("Location: tables.php");
        exit();
    } else {
        $_SESSION['error'] = "Có lỗi xảy ra: " . $conn->error;
    }
    $stmt->close();
}

// Tạo CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="../assets/img/favicon.png" />
    <title>Thêm người dùng mới - Argon Dashboard</title>
    <!-- Fonts and icons -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Nucleo Icons -->
    <link href="../assets/css/nucleo-icons.css" rel="stylesheet" />
    <link href="../assets/css/nucleo-svg.css" rel="stylesheet" />
    <!-- Main Styling -->
    <link href="../assets/css/argon-dashboard-tailwind.css?v=1.0.1" rel="stylesheet" />
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #edf2f7;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 6px;
            color: #4a5568;
        }
        
        .form-label.required:after {
            content: " *";
            color: #e53e3e;
        }
        
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }
        
        .form-hint {
            font-size: 12px;
            color: #718096;
            margin-top: 4px;
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background: linear-gradient(195deg, #4299e1, #3182ce);
            color: white;
        }
        
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(66, 153, 225, 0.2);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .btn-success {
            background: linear-gradient(195deg, #48bb78, #38a169);
            color: white;
        }
        
        .btn-success:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(72, 187, 120, 0.2);
        }
        
        .btn-danger {
            background: linear-gradient(195deg, #f56565, #e53e3e);
            color: white;
        }
        
        .btn-danger:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(245, 101, 101, 0.2);
        }
        
        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e0;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background: linear-gradient(195deg, #48bb78, #38a169);
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .switch-label {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 25px;
        }
        
        .btn-group {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #fed7d7;
            color: #9b2c2c;
            border-left: 4px solid #e53e3e;
        }
        
        .alert-success {
            background: #c6f6d5;
            color: #276749;
            border-left: 4px solid #38a169;
        }
        
        .preview-box {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .preview-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .preview-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #edf2f7;
        }
        
        .preview-label {
            font-weight: 500;
            color: #4a5568;
        }
        
        .preview-value {
            color: #2d3748;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: linear-gradient(195deg, #9f7aea, #805ad5);
            color: white;
        }
        
        .badge-staff {
            background: linear-gradient(195deg, #4299e1, #3182ce);
            color: white;
        }
        
        .badge-user {
            background: linear-gradient(195deg, #48bb78, #38a169);
            color: white;
        }
        
        .badge-active {
            background: linear-gradient(195deg, #48bb78, #38a169);
            color: white;
        }
        
        .badge-inactive {
            background: linear-gradient(195deg, #f56565, #e53e3e);
            color: white;
        }
        
        /* Dark mode styles */
        .dark .form-section {
            background: #2d3748;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .dark .form-title {
            color: #e2e8f0;
            border-bottom-color: #4a5568;
        }
        
        .dark .form-label {
            color: #cbd5e0;
        }
        
        .dark .form-control {
            background: #4a5568;
            border-color: #718096;
            color: #e2e8f0;
        }
        
        .dark .form-control:focus {
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.2);
        }
        
        .dark .form-hint {
            color: #a0aec0;
        }
        
        .dark .preview-box {
            background: #4a5568;
            border-color: #718096;
        }
        
        .dark .preview-title {
            color: #e2e8f0;
        }
        
        .dark .preview-label {
            color: #cbd5e0;
        }
        
        .dark .preview-value {
            color: #e2e8f0;
        }
        
        .dark .btn-group {
            border-top-color: #4a5568;
        }
    </style>
</head>
<body class="m-0 font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-gray-50 text-slate-500">
    <div class="absolute w-full bg-blue-500 dark:hidden min-h-75"></div>

    <!-- Sidebar (đơn giản hóa) -->
    <aside class="fixed inset-y-0 flex-wrap items-center justify-between block w-full p-0 my-4 overflow-y-auto antialiased transition-transform duration-200 -translate-x-full bg-white border-0 shadow-xl dark:shadow-none dark:bg-slate-850 xl:ml-6 max-w-64 ease-nav-brand z-990 rounded-2xl xl:left-0 xl:translate-x-0" aria-expanded="false">
        <div class="h-19">
            <i class="absolute top-0 right-0 p-4 opacity-50 cursor-pointer fas fa-times dark:text-white text-slate-400 xl:hidden" sidenav-close></i>
            <a class="block px-8 py-6 m-0 text-sm whitespace-nowrap dark:text-white text-slate-700" href="javascript:;">
                <img src="../assets/img/logo-ct-dark.png" class="inline h-full max-w-full transition-all duration-200 dark:hidden ease-nav-brand max-h-8" alt="main_logo" />
                <img src="../assets/img/logo-ct.png" class="hidden h-full max-w-full transition-all duration-200 dark:inline ease-nav-brand max-h-8" alt="main_logo" />
                <span class="ml-1 font-semibold transition-all duration-200 ease-nav-brand">Admin Dashboard</span>
            </a>
        </div>

        <hr class="h-px mt-0 bg-transparent bg-gradient-to-r from-transparent via-black/40 to-transparent dark:bg-gradient-to-r dark:from-transparent dark:via-white dark:to-transparent" />

        <div class="items-center block w-auto max-h-screen overflow-auto h-sidenav grow basis-full">
            <ul class="flex flex-col pl-0 mb-0">
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80" href="../pages/dashboard.html">
                        <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                            <i class="relative top-0 text-sm leading-normal text-blue-500 ni ni-tv-2"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Dashboard</span>
                    </a>
                </li>
                
                <li class="mt-0.5 w-full">
                    <a class="py-2.7 bg-blue-500/13 dark:text-white dark:opacity-80 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 font-semibold text-slate-700 transition-colors" href="tables.php">
                        <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                            <i class="relative top-0 text-sm leading-normal text-orange-500 ni ni-calendar-grid-58"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Quản lý người dùng</span>
                    </a>
                </li>
                
                <li class="mt-0.5 w-full">
                    <a class="dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="add_user.php">
                        <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                            <i class="relative top-0 text-sm leading-normal text-green-500 fas fa-user-plus"></i>
                        </div>
                        <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Thêm người dùng</span>
                    </a>
                </li>
            </ul>
        </div>
    </aside>

    <main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
        <!-- Navbar -->
        <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main navbar-scroll="false">
            <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
                <nav>
                    <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                        <li class="text-sm leading-normal">
                            <a class="text-white opacity-50" href="tables.php">Quản lý người dùng</a>
                        </li>
                        <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">Thêm mới</li>
                    </ol>
                    <h6 class="mb-0 font-bold text-white capitalize">Thêm người dùng mới</h6>
                </nav>
            </div>
        </nav>

        <div class="w-full px-6 py-6 mx-auto">
            <div class="flex flex-wrap -mx-3">
                <div class="w-full max-w-full px-3 mx-auto mt-0 lg:w-8/12">
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-section">
                        <h3 class="form-title">Thông tin người dùng</h3>
                        
                        <form method="POST" action="" id="userForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Họ tên -->
                                <div class="form-group">
                                    <label for="hoten" class="form-label required">Họ và tên</label>
                                    <input type="text" name="hoten" id="hoten" required 
                                           class="form-control" 
                                           placeholder="Nguyễn Văn A">
                                    <div class="form-hint">Nhập đầy đủ họ và tên</div>
                                </div>
                                
                                <!-- Tên đăng nhập -->
                                <div class="form-group">
                                    <label for="tendangnhap" class="form-label required">Tên đăng nhập</label>
                                    <input type="text" name="tendangnhap" id="tendangnhap" required 
                                           class="form-control" 
                                           placeholder="nguyenvana">
                                    <div class="form-hint">Tối thiểu 3 ký tự</div>
                                </div>
                                
                                <!-- Mật khẩu -->
                                <div class="form-group">
                                    <label for="matkhau" class="form-label required">Mật khẩu</label>
                                    <input type="password" name="matkhau" id="matkhau" required 
                                           class="form-control" 
                                           placeholder="********">
                                    <div class="form-hint">Tối thiểu 6 ký tự</div>
                                </div>
                                
                                <!-- Số điện thoại -->
                                <div class="form-group">
                                    <label for="sodienthoai" class="form-label">Số điện thoại</label>
                                    <input type="tel" name="sodienthoai" id="sodienthoai" 
                                           class="form-control" 
                                           placeholder="0987654321">
                                    <div class="form-hint">10-11 chữ số</div>
                                </div>
                                
                                <!-- Vai trò -->
                                <div class="form-group">
                                    <label for="vaitro" class="form-label required">Vai trò</label>
                                    <select name="vaitro" id="vaitro" required class="form-control">
                                        <option value="">-- Chọn vai trò --</option>
                                        <option value="admin">Quản trị viên</option>
                                        <option value="staff">Nhân viên</option>
                                        <option value="user">Người dùng</option>
                                    </select>
                                    <div class="form-hint">Chọn quyền hạn cho người dùng</div>
                                </div>
                                
                                <!-- Trạng thái -->
                                <div class="form-group">
                                    <label class="switch-label">
                                        <span class="form-label">Trạng thái tài khoản</span>
                                        <label class="switch">
                                            <input type="checkbox" name="trangthai" id="trangthai" checked>
                                            <span class="slider"></span>
                                        </label>
                                        <span id="statusText">Hoạt động</span>
                                    </label>
                                    <div class="form-hint">Bật/tắt trạng thái hoạt động của tài khoản</div>
                                </div>
                            </div>
                            
                            <!-- Preview thông tin -->
                            <div class="preview-box" id="previewBox">
                                <h4 class="preview-title">Xem trước thông tin</h4>
                                <div class="preview-item">
                                    <span class="preview-label">Họ tên:</span>
                                    <span class="preview-value" id="previewName">Chưa nhập</span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Tên đăng nhập:</span>
                                    <span class="preview-value" id="previewUsername">Chưa nhập</span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Vai trò:</span>
                                    <span class="preview-value">
                                        <span class="badge" id="previewRole">Chưa chọn</span>
                                    </span>
                                </div>
                                <div class="preview-item">
                                    <span class="preview-label">Trạng thái:</span>
                                    <span class="preview-value">
                                        <span class="badge badge-active" id="previewStatus">Hoạt động</span>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Nhóm nút -->
                            <div class="btn-group">
                                <button type="button" onclick="window.location.href='tables.php'" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left mr-2"></i>Quay lại
                                </button>
                                <button type="button" onclick="clearForm()" class="btn btn-danger">
                                    <i class="fas fa-redo mr-2"></i>Nhập lại
                                </button>
                                <button type="button" onclick="validateAndSubmit()" class="btn btn-success">
                                    <i class="fas fa-check-circle mr-2"></i>Xác nhận thêm
                                </button>
                                <button type="submit" id="submitBtn" style="display: none;">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Cập nhật preview khi thay đổi
        function updatePreview() {
            const name = document.getElementById('hoten').value;
            const username = document.getElementById('tendangnhap').value;
            const role = document.getElementById('vaitro').value;
            const status = document.getElementById('trangthai').checked;
            
            // Cập nhật preview
            document.getElementById('previewName').textContent = name || 'Chưa nhập';
            document.getElementById('previewUsername').textContent = username || 'Chưa nhập';
            
            // Cập nhật vai trò
            let roleText = 'Chưa chọn';
            let roleClass = '';
            switch(role) {
                case 'admin':
                    roleText = 'Quản trị viên';
                    roleClass = 'badge-admin';
                    break;
                case 'staff':
                    roleText = 'Nhân viên';
                    roleClass = 'badge-staff';
                    break;
                case 'user':
                    roleText = 'Người dùng';
                    roleClass = 'badge-user';
                    break;
            }
            document.getElementById('previewRole').textContent = roleText;
            document.getElementById('previewRole').className = `badge ${roleClass}`;
            
            // Cập nhật trạng thái
            const statusText = status ? 'Hoạt động' : 'Không hoạt động';
            const statusClass = status ? 'badge-active' : 'badge-inactive';
            document.getElementById('previewStatus').textContent = statusText;
            document.getElementById('previewStatus').className = `badge ${statusClass}`;
            document.getElementById('statusText').textContent = statusText;
        }
        
        // Xóa form
        function clearForm() {
            Swal.fire({
                title: 'Xác nhận nhập lại',
                text: 'Bạn có chắc muốn xóa tất cả thông tin đã nhập?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Xóa tất cả',
                cancelButtonText: 'Hủy bỏ'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('userForm').reset();
                    updatePreview();
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã xóa!',
                        text: 'Tất cả thông tin đã được xóa',
                        timer: 1500,
                        showConfirmButton: false
                    });
                }
            });
        }
        
        // Kiểm tra và xác nhận submit
        function validateAndSubmit() {
            const hoten = document.getElementById('hoten').value.trim();
            const tendangnhap = document.getElementById('tendangnhap').value.trim();
            const matkhau = document.getElementById('matkhau').value.trim();
            const vaitro = document.getElementById('vaitro').value;
            
            // Kiểm tra dữ liệu
            let errors = [];
            
            if (!hoten) errors.push('Họ tên không được để trống');
            if (!tendangnhap) errors.push('Tên đăng nhập không được để trống');
            if (tendangnhap.length < 3) errors.push('Tên đăng nhập phải có ít nhất 3 ký tự');
            if (!matkhau) errors.push('Mật khẩu không được để trống');
            if (matkhau.length < 6) errors.push('Mật khẩu phải có ít nhất 6 ký tự');
            if (!vaitro) errors.push('Vai trò không được để trống');
            
            if (errors.length > 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi nhập liệu',
                    html: '<ul style="text-align: left; margin: 10px 0;">' + 
                          errors.map(error => `<li>• ${error}</li>`).join('') + 
                          '</ul>',
                    confirmButtonText: 'Đã hiểu'
                });
                return;
            }
            
            // Hiển thị xác nhận
            const roleText = document.getElementById('vaitro').options[document.getElementById('vaitro').selectedIndex].text;
            const statusText = document.getElementById('trangthai').checked ? 'Hoạt động' : 'Không hoạt động';
            
            Swal.fire({
                title: 'Xác nhận thêm người dùng',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Họ tên:</strong> ${hoten}</p>
                        <p><strong>Tên đăng nhập:</strong> ${tendangnhap}</p>
                        <p><strong>Vai trò:</strong> ${roleText}</p>
                        <p><strong>Trạng thái:</strong> ${statusText}</p>
                        <hr style="margin: 15px 0;">
                        <p style="color: #666; font-size: 14px;">
                            <i class="fas fa-info-circle"></i> Bạn có chắc chắn muốn thêm người dùng này?
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Xác nhận thêm',
                cancelButtonText: 'Hủy bỏ',
                width: 500
            }).then((result) => {
                if (result.isConfirmed) {
                    // Hiển thị loading
                    Swal.fire({
                        title: 'Đang thêm người dùng...',
                        text: 'Vui lòng chờ trong giây lát',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit form
                    setTimeout(() => {
                        document.getElementById('submitBtn').click();
                    }, 100);
                }
            });
        }
        
        // Cập nhật preview khi tải trang
        document.addEventListener('DOMContentLoaded', function() {
            updatePreview();
            
            // Thêm event listeners cho các input
            document.getElementById('hoten').addEventListener('input', updatePreview);
            document.getElementById('tendangnhap').addEventListener('input', updatePreview);
            document.getElementById('vaitro').addEventListener('change', updatePreview);
            document.getElementById('trangthai').addEventListener('change', updatePreview);
        });
        
        // Hiển thị thông báo nếu có
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: '<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>',
                showConfirmButton: true,
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'tables.php';
            });
        <?php endif; ?>
    </script>
</body>
</html>