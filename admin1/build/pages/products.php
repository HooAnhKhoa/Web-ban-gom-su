<?php
if(!isset($_SESSION)) 
{ 
    session_start(); 
}

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$userName = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['user_email']) : '';
$userRole = $isLoggedIn ? ($_SESSION['user_role'] ?? 'user') : '';

require '../../../conn.php';

// Lấy danh sách user từ database
$sql = "SELECT masanpham, tensanpham, maloai, xuatxu, chatlieu, mota, gia, kichthuoc FROM tbl_sanpham";
$result = $conn->query($sql);

// Xử lý khi có POST request (cập nhật trạng thái)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action']) && isset($_POST['id'])) {
        $id = $_POST['id'];
        
        if ($_POST['action'] == 'toggle_status') {
            // Lấy trạng thái hiện tại
            $check_sql = "SELECT trangthai FROM tbl_nguoidung WHERE manguoidung = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("s", $id);
            $stmt->execute();
            $stmt->bind_result($current_status);
            $stmt->fetch();
            $stmt->close();
            
            // Đảo trạng thái
            $new_status = ($current_status == 1) ? 0 : 1;
            
            // Cập nhật trạng thái
            $update_sql = "UPDATE tbl_nguoidung SET trangthai = ? WHERE manguoidung = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ss", $new_status, $id);
            
            if ($stmt->execute()) {
                echo "<script>alert('Cập nhật trạng thái thành công!');</script>";
                echo "<script>window.location.href = 'tables.php';</script>";
            }
            $stmt->close();
        }
        
        if ($_POST['action'] == 'delete_user') {
            $delete_sql = "DELETE FROM tbl_nguoidung WHERE manguoidung = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("s", $id);
            
            if ($stmt->execute()) {
                echo "<script>alert('Xóa người dùng thành công!');</script>";
                echo "<script>window.location.href = 'tables.php';</script>";
            }
            $stmt->close();
        }
    }
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
    <title>Quản lý sản phẩm - Argon Dashboard</title>
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
            <a class="py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors dark:text-white dark:opacity-80" href="../pages/tables.php">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                <!-- <i class="relative top-0 text-sm leading-normal text-blue-500 ni ni-tv-2"></i> -->
                <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-single-02"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Quản lý người dùng</span>
            </a>
          </li>
          
          <li class="mt-0.5 w-full">
            <a class="py-2.7 bg-blue-500/13 dark:text-white dark:opacity-80 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap rounded-lg px-4 font-semibold text-slate-700 transition-colors" href="products.php">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                <i class="relative top-0 text-sm leading-normal text-orange-500 ni ni-calendar-grid-58"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Quản lý sản phẩm</span>
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

          <!-- <li class="mt-0.5 w-full">
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
          </li> -->

          <li class="mt-0.5 w-full">
            <a class=" dark:text-white dark:opacity-80 py-2.7 text-sm ease-nav-brand my-0 mx-2 flex items-center whitespace-nowrap px-4 transition-colors" href="../pages/profile.html">
              <div class="mr-2 flex h-8 w-8 items-center justify-center rounded-lg bg-center stroke-0 text-center xl:p-2.5">
                <i class="relative top-0 text-sm leading-normal text-slate-700 ni ni-single-02"></i>
              </div>
              <span class="ml-1 duration-300 opacity-100 pointer-events-none ease">Profile</span>
            </a>
          </li>

          <!-- <li class="mt-0.5 w-full">
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
          </li> -->
          <!-- Thêm các menu items khác nếu cần -->
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
                <a class="text-white opacity-50" href="javascript:;">Pages</a>
              </li>
              <li class="text-sm pl-2 capitalize leading-normal text-white before:float-left before:pr-2 before:text-white before:content-['/']" aria-current="page">Quản lý người dùng</li>
            </ol>
            <h6 class="mb-0 font-bold text-white capitalize">Danh sách sản phẩm</h6>
          </nav>

          <div class="flex items-center mt-2 grow sm:mt-0 sm:mr-6 md:mr-0 lg:flex lg:basis-auto">
            <div class="flex items-center md:ml-auto md:pr-4">
              <form method="GET" action="" class="relative flex flex-wrap items-stretch w-full transition-all rounded-lg ease">
                <span class="text-sm ease leading-5.6 absolute z-50 -ml-px flex h-full items-center whitespace-nowrap rounded-lg rounded-tr-none rounded-br-none border border-r-0 border-transparent bg-transparent py-2 px-2.5 text-center font-normal text-slate-500 transition-all">
                  <i class="fas fa-search"></i>
                </span>
                <input type="text" name="search" class="pl-9 text-sm focus:shadow-primary-outline ease w-1/100 leading-5.6 relative -ml-px block min-w-0 flex-auto rounded-lg border border-solid border-gray-300 dark:bg-slate-850 dark:text-white bg-white bg-clip-padding py-2 pr-3 text-gray-700 transition-all placeholder:text-gray-500 focus:border-blue-500 focus:outline-none focus:transition-shadow" placeholder="Tìm kiếm người dùng..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" />
              </form>
            </div>
          </div>
          <li class="flex items-center">
                <a href="../../login.php" class="block px-0 py-2 text-sm font-semibold text-white transition-all ease-nav-brand">
                  <i class="fa fa-user sm:mr-1"></i>
                    <span class="hidden sm:inline">
                      <?php if ($isLoggedIn): ?>
                        Xin chào, <?php echo htmlspecialchars($userName); ?>
                      <?php else: ?>
                        Đăng nhập
                      <?php endif; ?>
                    </span>
                </a>

                <?php if ($isLoggedIn): ?>
                <li class="flex items-center ml-4">
                  <a href="../../logout.php" class="block px-0 py-2 text-sm font-semibold text-white transition-all ease-nav-brand hover:text-red-300">
                    <i class="fa fa-sign-out sm:mr-1"></i>
                      <span class="hidden sm:inline">Đăng xuất</span>
                  </a>
                </li>
                <?php endif; ?>
              </li>
        </div>
      </nav>

      <div class="w-full px-6 py-6 mx-auto">
        <!-- Bảng danh sách người dùng -->
        <div class="flex flex-wrap -mx-3">
          <div class="flex-none w-full max-w-full px-3">
            <div class="relative flex flex-col min-w-0 mb-6 break-words bg-white border-0 border-transparent border-solid shadow-xl dark:bg-slate-850 dark:shadow-dark-xl rounded-2xl bg-clip-border">
              <div class="p-6 pb-0 mb-0 border-b-0 border-b-solid rounded-t-2xl border-b-transparent">
                <div class="flex justify-between items-center">
                  <h6 class="dark:text-white">Danh sách sản phẩm</h6>
                  <a href="product/add_product.php" class="px-4 py-2 text-sm font-bold leading-normal text-center text-white capitalize align-middle transition-all bg-blue-500 border-0 rounded-lg cursor-pointer hover:shadow-xs hover:-translate-y-px active:opacity-85 ease-in tracking-tight-rem shadow-md bg-150 bg-x-25">
                    <i class="fas fa-plus mr-2"></i>Thêm sản phẩm
                  </a>
                </div>
              </div>
              <div class="flex-auto px-0 pt-0 pb-2">
                <div class="table-responsive">
                  <table class="items-center w-full mb-0 align-top border-collapse dark:border-white/40 text-slate-500">
                    <thead class="align-bottom">
                      <tr>
                        <th class="px-6 py-3 font-bold text-left uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">ID</th>
                        <th class="px-6 py-3 font-bold text-left uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">Ten</th>
                        <th class="px-6 py-3 font-bold text-left uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">Ma loai</th>
                        <th class="px-6 py-3 font-bold text-left uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">Mo ta</th>
                        <th class="px-6 py-3 font-bold text-center uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">Xuat xu</th>
                        <th class="px-6 py-3 font-bold text-center uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">Chat lieu</th>
                        <th class="px-6 py-3 font-bold text-center uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">Gia</th>
                        <th class="px-6 py-3 font-bold text-center uppercase align-middle bg-transparent border-b border-collapse shadow-none dark:border-white/40 dark:text-white text-xxs border-b-solid tracking-none whitespace-nowrap text-slate-400 opacity-70">Thao tác</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                          <tr>
                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                              <p class="mb-0 text-xs font-semibold leading-tight dark:text-white dark:opacity-80"><?php echo htmlspecialchars($row['masanpham']); ?></p>
                            </td>
                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                              <div class="flex px-2 py-1">
                                <div class="flex flex-col justify-center">
                                  <h6 class="mb-0 text-sm leading-normal dark:text-white"><?php echo htmlspecialchars($row['tensanpham']); ?></h6>
                                </div>
                              </div>
                            </td>
                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                              <p class="mb-0 text-xs font-semibold leading-tight dark:text-white dark:opacity-80"><?php echo htmlspecialchars($row['maloai']); ?></p>
                            </td>
                            
                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                              <div class="flex px-2 py-1">
                                <div class="flex flex-col justify-center">
                                  <h6 class="mb-0 text-sm leading-normal dark:text-white"><?php echo htmlspecialchars($row['mota']); ?></h6>
                                </div>
                              </div>
                            </td>

                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                              <div class="flex px-2 py-1">
                                <div class="flex flex-col justify-center">
                                  <h6 class="mb-0 text-sm leading-normal dark:text-white"><?php echo htmlspecialchars($row['xuatxu']); ?></h6>
                                </div>
                              </div>
                            </td>
                            
                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                              <div class="flex px-2 py-1">
                                <div class="flex flex-col justify-center">
                                  <h6 class="mb-0 text-sm leading-normal dark:text-white"><?php echo htmlspecialchars($row['chatlieu']); ?></h6>
                                </div>
                              </div>
                            </td>

                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                              <p class="mb-0 text-xs font-semibold leading-tight dark:text-white dark:opacity-80"><?php echo htmlspecialchars($row['gia']); ?></p>
                            </td>

                            <td class="p-2 align-middle bg-transparent border-b dark:border-white/40 whitespace-nowrap shadow-transparent">
                              <div class="action-buttons justify-center">
                                <button onclick="editProduct('<?php echo $row['masanpham']; ?>')" class="btn-edit">
                                  <i class="fas fa-edit mr-1"></i>Sửa
                                </button>
                                <form method="POST" style="display: inline;">
                                  <input type="hidden" name="id" value="<?php echo $row['masanpham']; ?>">
                                  <input type="hidden" name="action" value="toggle_status">
                                  <button type="submit" class="btn-toggle" onclick="return confirm('Bạn có chắc muốn thay đổi trạng thái?')">
                                    <i class="fas fa-power-off mr-1"></i>Đổi TT
                                  </button>
                                </form>
                                <form method="POST" style="display: inline;">
                                  <input type="hidden" name="id" value="<?php echo $row['masanpham']; ?>">
                                  <input type="hidden" name="action" value="delete_user">
                                  <button type="submit" class="btn-delete" onclick="return confirmDelete()">
                                    <i class="fas fa-trash mr-1"></i>Xóa
                                  </button>
                                </form>
                              </div>
                            </td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr>
                          <td colspan="8" class="p-4 text-center">
                            <p class="text-slate-500 dark:text-white">Không có người dùng nào.</p>
                          </td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
                
                <!-- Phân trang -->
                <div class="px-6 py-3 border-t border-slate-200 dark:border-white/40">
                  <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-500 dark:text-white">
                      <?php 
                      $total_users = $result ? $result->num_rows : 0;
                      echo "Hiển thị " . $total_users . " sản phẩm";
                      ?>
                    </p>
                    <!-- Có thể thêm phân trang ở đây nếu cần -->
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <footer class="pt-4">
          <div class="w-full px-6 mx-auto">
            <div class="flex flex-wrap items-center -mx-3 lg:justify-between">
              <div class="w-full max-w-full px-3 mt-0 mb-6 shrink-0 lg:mb-0 lg:w-1/2 lg:flex-none">
                <div class="text-sm leading-normal text-center text-slate-500 lg:text-left">
                  © <?php echo date('Y'); ?>, Hệ thống quản lý sản phẩm
                </div>
              </div>
            </div>
          </div>
        </footer>
      </div>
    </main>

    <!-- Các script giữ nguyên -->
    <script src="../assets/js/plugins/perfect-scrollbar.min.js" async></script>
    <script src="../assets/js/argon-dashboard-tailwind.js?v=1.0.1" async></script>
    
    <script>
      function confirmDelete() {
        return confirm('Bạn có chắc chắn muốn xóa người dùng này?');
      }
      
      function editProduct(userId) {
        window.location.href = 'product/edit_product.php?id=' + userId;
      }
      
      // Tự động submit form search khi nhập
      document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          this.form.submit();
        }
      });
      
      // SweetAlert cho các thông báo
      <?php if (isset($_GET['success'])): ?>
        Swal.fire({
          icon: 'success',
          title: 'Thành công!',
          text: '<?php echo $_GET['success']; ?>',
          timer: 2000,
          showConfirmButton: false
        });
      <?php endif; ?>
      
      <?php if (isset($_GET['error'])): ?>
        Swal.fire({
          icon: 'error',
          title: 'Lỗi!',
          text: '<?php echo $_GET['error']; ?>',
          timer: 3000,
          showConfirmButton: true
        });
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