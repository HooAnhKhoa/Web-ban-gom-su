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
    <!-- Phần head tương tự add_user.php -->
    <title>Sửa thông tin người dùng</title>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png" />
    <link rel="icon" type="image/png" href="../assets/img/favicon.png" />
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
      .action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
      }
      .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
      }
      .status-active {
        background: linear-gradient(195deg, #66BB6A, #43A047);
        color: white;
      }
      .status-inactive {
        background: linear-gradient(195deg, #EF5350, #E53935);
        color: white;
      }
      .status-pending {
        background: linear-gradient(195deg, #FFA726, #FB8C00);
        color: white;
      }
      .btn-edit {
        background: linear-gradient(195deg, #49a3f1, #1A73E8);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
      }
      .btn-edit:hover {
        opacity: 0.9;
        transform: translateY(-1px);
      }
      .btn-delete {
        background: linear-gradient(195deg, #EF5350, #E53935);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
      }
      .btn-delete:hover {
        opacity: 0.9;
        transform: translateY(-1px);
      }
      .btn-toggle {
        background: linear-gradient(195deg, #FFA726, #FB8C00);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
      }
      .btn-toggle:hover {
        opacity: 0.9;
        transform: translateY(-1px);
      }
      .search-box {
        position: relative;
        margin-bottom: 20px;
      }
      .search-box input {
        padding-left: 40px;
      }
      .search-box i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
      }
      .table-responsive {
        overflow-x: auto;
      }
    </style>
</head>
  <body class="m-0 font-sans text-base antialiased font-normal dark:bg-slate-900 leading-default bg-gray-50 text-slate-500">
    <!-- Phần sidebar và navbar giữ nguyên -->
    <div class="absolute w-full bg-blue-500 dark:hidden min-h-75"></div>

    <!-- Sidebar (giữ nguyên từ file gốc) -->
    <aside class="fixed inset-y-0 flex-wrap items-center justify-between block w-full p-0 my-4 overflow-y-auto antialiased transition-transform duration-200 -translate-x-full bg-white border-0 shadow-xl dark:shadow-none dark:bg-slate-850 xl:ml-6 max-w-64 ease-nav-brand z-990 rounded-2xl xl:left-0 xl:translate-x-0" aria-expanded="false">
      <!-- Sidebar content giữ nguyên -->
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
          <!-- Các menu items giữ nguyên -->
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
            <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="../pages/billing.html">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center fill-current stroke-0 text-center xl:p-2.5">
                <i class="relative top-0 text-sm leading-normal text-emerald-500 ni ni-credit-card"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Billing</span>
            </a>
          </li>

          <li class="mt-0.5 w-full">
            <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="../pages/virtual-reality.html">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                <i class="relative top-0 text-sm leading-normal text-cyan-500 ni ni-app"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Virtual Reality</span>
            </a>
          </li>

          <li class="mt-0.5 w-full">
            <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="../pages/rtl.html">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                <i class="relative top-0 text-sm leading-normal text-red-600 ni ni-world-2"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">RTL</span>
            </a>
          </li>

          <li class="w-full mt-4">
            <h6 class="pl-6 ml-2 text-xs font-bold leading-tight uppercase dark:text-white opacity-60">Account pages</h6>
          </li>

          <li class="mt-0.5 w-full">
            <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="../pages/profile.html">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-single-02"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Profile</span>
            </a>
          </li>

          <li class="mt-0.5 w-full">
            <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="../../../login.php">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                <i class="relative top-0 text-sm leading-normal text-orange-500 ni ni-single-copy-04"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Sign In</span>
            </a>
          </li>

          <li class="mt-0.5 w-full">
            <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="../../../register.php">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                <i class="relative top-0 text-sm leading-normal text-cyan-500 ni ni-collection"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Sign Up</span>
            </a>
          </li>
          <!-- Thêm các menu items khác nếu cần -->
        </ul>
      </div>
    </aside>

    <main class="relative h-full max-h-screen transition-all duration-200 ease-in-out xl:ml-68 rounded-xl">
        <!-- Navbar -->
        
        <div class="w-full px-6 py-6 mx-auto">
            <div class="flex flex-wrap -mx-3">
                <div class="w-full max-w-full px-3 mx-auto mt-0 lg:w-8/12">
                    <div class="flex flex-col min-w-0 break-words bg-white border-0 dark:bg-gray-950 dark:shadow-soft-xl rounded-2xl bg-clip-border shadow-soft-xl">
                        <div class="p-6 mb-0 rounded-t-2xl">
                            <h5 class="dark:text-white">Sửa thông tin người dùng</h5>
                            <p class="text-sm text-gray-500">Mã: <?php echo htmlspecialchars($user['manguoidung']); ?></p>
                        </div>
                        <div class="flex-auto p-6">
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                
                                <?php if (isset($_SESSION['error'])): ?>
                                    <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="flex flex-wrap -mx-3">
                                    <div class="w-full max-w-full px-3 lg:w-6/12">
                                        <div class="mb-4">
                                            <label class="inline-block mb-2 ml-1 text-xs font-semibold text-slate-700 dark:text-white/80">Mã người dùng</label>
                                            <input type="text" value="<?php echo htmlspecialchars($user['manguoidung']); ?>" class="focus:shadow-soft-primary-outline dark:bg-gray-800 dark:text-white/80 text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-gray-100 bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all" readonly>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-full px-3 lg:w-6/12">
                                        <div class="mb-4">
                                            <label class="inline-block mb-2 ml-1 text-xs font-semibold text-slate-700 dark:text-white/80">Tên đăng nhập</label>
                                            <input type="text" value="<?php echo htmlspecialchars($user['tendangnhap']); ?>" class="focus:shadow-soft-primary-outline dark:bg-gray-800 dark:text-white/80 text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-gray-100 bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all" readonly>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap -mx-3">
                                    <div class="w-full max-w-full px-3 lg:w-6/12">
                                        <div class="mb-4">
                                            <label for="hoten" class="inline-block mb-2 ml-1 text-xs font-semibold text-slate-700 dark:text-white/80">Họ và tên *</label>
                                            <input type="text" name="hoten" value="<?php echo htmlspecialchars($user['hotennguoidung']); ?>" required class="focus:shadow-soft-primary-outline dark:bg-gray-950 dark:placeholder:text-white/80 dark:text-white/80 text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-fuchsia-300 focus:outline-none">
                                        </div>
                                    </div>
                                    <div class="w-full max-w-full px-3 lg:w-6/12">
                                        <div class="mb-4">
                                            <label for="sodienthoai" class="inline-block mb-2 ml-1 text-xs font-semibold text-slate-700 dark:text-white/80">Số điện thoại</label>
                                            <input type="tel" name="sodienthoai" value="<?php echo htmlspecialchars($user['sodienthoai']); ?>" class="focus:shadow-soft-primary-outline dark:bg-gray-950 dark:placeholder:text-white/80 dark:text-white/80 text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-fuchsia-300 focus:outline-none">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap -mx-3">
                                    <div class="w-full max-w-full px-3 lg:w-6/12">
                                        <div class="mb-4">
                                            <label for="vaitro" class="inline-block mb-2 ml-1 text-xs font-semibold text-slate-700 dark:text-white/80">Vai trò *</label>
                                            <select name="vaitro" required class="focus:shadow-soft-primary-outline dark:bg-gray-950 dark:text-white/80 text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-fuchsia-300 focus:outline-none">
                                                <option value="admin" <?php echo $user['vaitro'] == 'admin' ? 'selected' : ''; ?>>Quản trị viên</option>
                                                <option value="staff" <?php echo $user['vaitro'] == 'staff' ? 'selected' : ''; ?>>Nhân viên</option>
                                                <option value="user" <?php echo $user['vaitro'] == 'user' ? 'selected' : ''; ?>>Người dùng</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="w-full max-w-full px-3 lg:w-6/12">
                                        <div class="mb-4">
                                            <label class="inline-flex items-center cursor-pointer">
                                                <input id="trangthai" name="trangthai" type="checkbox" <?php echo $user['trangthai'] == 1 ? 'checked' : ''; ?> class="sr-only peer">
                                                <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                                                <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Kích hoạt tài khoản</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end mt-6">
                                    <a href="tables.php" class="px-6 py-2 mr-3 text-sm font-bold text-gray-700 uppercase transition-all rounded-lg cursor-pointer hover:bg-gray-100">Hủy</a>
                                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white uppercase transition-all rounded-lg shadow-soft-md bg-gradient-to-tl from-blue-600 to-cyan-400 hover:shadow-soft-xs active:opacity-85">Cập nhật</button>
                                </div>
                            </form>
                            
                            <!-- Đổi mật khẩu riêng -->
                            <hr class="my-6">
                            <h6 class="mb-4 dark:text-white">Đổi mật khẩu</h6>
                            <form method="POST" action="change_password.php" class="mt-4">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="id" value="<?php echo $user['manguoidung']; ?>">
                                
                                <div class="flex flex-wrap -mx-3">
                                    <div class="w-full max-w-full px-3 lg:w-6/12">
                                        <div class="mb-4">
                                            <label for="new_password" class="inline-block mb-2 ml-1 text-xs font-semibold text-slate-700 dark:text-white/80">Mật khẩu mới</label>
                                            <input type="password" name="new_password" class="focus:shadow-soft-primary-outline dark:bg-gray-950 dark:placeholder:text-white/80 dark:text-white/80 text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-fuchsia-300 focus:outline-none">
                                        </div>
                                    </div>
                                    <div class="w-full max-w-full px-3 lg:w-6/12">
                                        <div class="mb-4">
                                            <label for="confirm_password" class="inline-block mb-2 ml-1 text-xs font-semibold text-slate-700 dark:text-white/80">Xác nhận mật khẩu</label>
                                            <input type="password" name="confirm_password" class="focus:shadow-soft-primary-outline dark:bg-gray-950 dark:placeholder:text-white/80 dark:text-white/80 text-sm leading-5.6 ease-soft block w-full appearance-none rounded-lg border border-solid border-gray-300 bg-white bg-clip-padding px-3 py-2 font-normal text-gray-700 outline-none transition-all placeholder:text-gray-500 focus:border-fuchsia-300 focus:outline-none">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" class="px-6 py-2 text-sm font-bold text-white uppercase transition-all rounded-lg shadow-soft-md bg-gradient-to-tl from-green-600 to-lime-400 hover:shadow-soft-xs active:opacity-85">Đổi mật khẩu</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Các script -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>