<?php
// File: edit_user.php
require '../../../conn.php';
session_start();

if (!isset($_GET['id'])) {
    header("Location: tables.php");
    exit();
}

$id = $conn->real_escape_string($_GET['id']);

// Lấy thông tin người dùng
$sql = "SELECT * FROM tbl_nguoidung WHERE manguoidung = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: tables.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Xác thực CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token không hợp lệ!");
    }
    
    $hoten = $conn->real_escape_string($_POST['hoten']);
    $sodienthoai = $conn->real_escape_string($_POST['sodienthoai']);
    $vaitro = $conn->real_escape_string($_POST['vaitro']);
    $trangthai = isset($_POST['trangthai']) ? 1 : 0;
    
    // Cập nhật thông tin
    $update_sql = "UPDATE tbl_nguoidung SET 
                    hotennguoidung = ?, 
                    sodienthoai = ?, 
                    vaitro = ?, 
                    trangthai = ? 
                   WHERE manguoidung = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("sssis", $hoten, $sodienthoai, $vaitro, $trangthai, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Cập nhật thông tin thành công!";
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
    <title>Sửa thông tin người dùng - Argon Dashboard</title>
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
        .form-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }
        
        .form-subtitle {
            color: #718096;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            font-weight: 500;
            margin-bottom: 8px;
            color: #4a5568;
        }
        
        .form-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #4299e1;
        }
        
        .form-input[readonly] {
            background: #f7fafc;
            color: #718096;
        }
        
        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #4299e1;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3182ce;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }
        
        .btn-secondary:hover {
            background: #cbd5e0;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .switch {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .switch input[type="checkbox"] {
            width: 40px;
            height: 20px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
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
        
        .dark .form-card {
            background: #2d3748;
        }
        
        .dark .form-title {
            color: #e2e8f0;
        }
        
        .dark .form-subtitle {
            color: #a0aec0;
        }
        
        .dark .form-label {
            color: #cbd5e0;
        }
        
        .dark .form-input {
            background: #4a5568;
            border-color: #718096;
            color: #e2e8f0;
        }
        
        .dark .form-input:focus {
            border-color: #4299e1;
        }
        
        .dark .form-input[readonly] {
            background: #4a5568;
            color: #a0aec0;
        }
    </style>
</head>
<body class="m-0 font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-gray-50 text-slate-500">
    <div class="absolute w-full bg-blue-500 dark:hidden min-h-75"></div>

    <!-- Sidebar đơn giản -->
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
            </ul>
        </div>
    </aside>

    <main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
        <!-- Navbar đơn giản -->
        <nav class="relative flex flex-wrap items-center justify-between px-0 py-2 mx-6 transition-all ease-in shadow-none duration-250 rounded-2xl lg:flex-nowrap lg:justify-start" navbar-main navbar-scroll="false">
            <div class="flex items-center justify-between w-full px-4 py-1 mx-auto flex-wrap-inherit">
                <nav>
                    <ol class="flex flex-wrap pt-1 mr-12 bg-transparent rounded-lg sm:mr-16">
                        <li class="text-sm leading-normal">
                            <a class="text-white opacity-50" href="tables.php">Danh sách người dùng</a>
                        </li>
                        <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">Sửa thông tin</li>
                    </ol>
                    <h6 class="mb-0 font-bold text-white capitalize">Sửa thông tin người dùng</h6>
                </nav>
            </div>
        </nav>

        <div class="w-full px-6 py-6 mx-auto">
            <div class="flex flex-wrap -mx-3">
                <div class="w-full max-w-full px-3 mx-auto mt-0 lg:w-8/12">
                    
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-card">
                        <h3 class="form-title">Sửa thông tin người dùng</h3>
                        <p class="form-subtitle">Mã: <?php echo htmlspecialchars($user['manguoidung']); ?></p>
                        
                        <form method="POST" action="" id="editForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Mã người dùng -->
                                <div class="form-group">
                                    <label class="form-label">Mã người dùng</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['manguoidung']); ?>" class="form-input" readonly>
                                </div>
                                
                                <!-- Tên đăng nhập -->
                                <div class="form-group">
                                    <label class="form-label">Tên đăng nhập</label>
                                    <input type="text" value="<?php echo htmlspecialchars($user['tendangnhap']); ?>" class="form-input" readonly>
                                </div>
                                
                                <!-- Họ tên -->
                                <div class="form-group">
                                    <label for="hoten" class="form-label">Họ và tên</label>
                                    <input type="text" name="hoten" id="hoten" required 
                                           value="<?php echo htmlspecialchars($user['hotennguoidung']); ?>"
                                           class="form-input">
                                </div>
                                
                                <!-- Số điện thoại -->
                                <div class="form-group">
                                    <label for="sodienthoai" class="form-label">Số điện thoại</label>
                                    <input type="tel" name="sodienthoai" id="sodienthoai"
                                           value="<?php echo htmlspecialchars($user['sodienthoai']); ?>"
                                           class="form-input">
                                </div>
                                
                                <!-- Vai trò -->
                                <div class="form-group">
                                    <label for="vaitro" class="form-label">Vai trò</label>
                                    <select name="vaitro" id="vaitro" required class="form-input">
                                        <option value="admin" <?php echo $user['vaitro'] == 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                        <option value="staff" <?php echo $user['vaitro'] == 'staff' ? 'selected' : ''; ?>>Nhân viên</option>
                                        <option value="user" <?php echo $user['vaitro'] == 'user' ? 'selected' : ''; ?>>Người dùng</option>
                                    </select>
                                </div>
                                
                                <!-- Trạng thái -->
                                <div class="form-group">
                                    <label class="form-label">Trạng thái tài khoản</label>
                                    <div class="switch">
                                        <input type="checkbox" name="trangthai" id="trangthai" 
                                               <?php echo $user['trangthai'] == 1 ? 'checked' : ''; ?>>
                                        <span id="statusText"><?php echo $user['trangthai'] == 1 ? 'Hoạt động' : 'Không hoạt động'; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="btn-group">
                                <button type="button" onclick="window.location.href='tables.php'" class="btn btn-secondary">
                                    <i class="fas fa-times mr-2"></i>Hủy
                                </button>
                                <button type="button" onclick="confirmUpdate()" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i>Cập nhật
                                </button>
                                <button type="submit" id="submitBtn" style="display: none;">Submit</button>
                            </div>
                        </form>
                        
                        <!-- Phần đổi mật khẩu đơn giản -->
                        <hr class="my-8">
                        <h4 class="form-title mb-4">Đổi mật khẩu</h4>
                        <form method="POST" action="change_password.php">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="id" value="<?php echo $user['manguoidung']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="form-group">
                                    <label for="new_password" class="form-label">Mật khẩu mới</label>
                                    <input type="password" name="new_password" class="form-input">
                                </div>
                                
                                <div class="form-group">
                                    <label for="confirm_password" class="form-label">Xác nhận mật khẩu</label>
                                    <input type="password" name="confirm_password" class="form-input">
                                </div>
                            </div>
                            
                            <div class="flex justify-end mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key mr-2"></i>Đổi mật khẩu
                                </button>
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
        // Cập nhật trạng thái khi thay đổi checkbox
        document.getElementById('trangthai').addEventListener('change', function() {
            document.getElementById('statusText').textContent = this.checked ? 'Hoạt động' : 'Không hoạt động';
        });
        
        // Xác nhận cập nhật
        function confirmUpdate() {
            const hoten = document.getElementById('hoten').value.trim();
            const vaitro = document.getElementById('vaitro').value;
            const statusText = document.getElementById('trangthai').checked ? 'Hoạt động' : 'Không hoạt động';
            
            if (!hoten) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Vui lòng nhập họ tên',
                    confirmButtonText: 'OK'
                });
                return;
            }
            
            Swal.fire({
                title: 'Xác nhận cập nhật',
                html: `
                    <div style="text-align: left;">
                        <p><strong>Họ tên:</strong> ${hoten}</p>
                        <p><strong>Vai trò:</strong> ${vaitro}</p>
                        <p><strong>Trạng thái:</strong> ${statusText}</p>
                        <hr style="margin: 10px 0;">
                        <p style="color: #666; font-size: 14px;">
                            Bạn có chắc chắn muốn cập nhật thông tin?
                        </p>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Cập nhật',
                cancelButtonText: 'Hủy'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form
                    document.getElementById('submitBtn').click();
                }
            });
        }
        
        // Hiển thị thông báo nếu có
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Thành công!',
                text: '<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>',
                showConfirmButton: false,
                timer: 2000,
                willClose: () => {
                    window.location.href = 'tables.php';
                }
            });
        <?php endif; ?>
    </script>
</body>
</html>