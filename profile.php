<?php 
if(!isset($_SESSION)) { session_start(); }
require 'conn.php'; // Đảm bảo đường dẫn đúng

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// 2. Xử lý POST Requests
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // A. Cập nhật thông tin cá nhân
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $phone = trim($_POST['phone']);
        
        $stmt = $conn->prepare("UPDATE tbl_nguoidung SET hotennguoidung = ?, sodienthoai = ? WHERE manguoidung = ?");
        $stmt->bind_param("ssi", $fullname, $phone, $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $fullname; // Cập nhật session
            $_SESSION['user_phone'] = $phone;
            $msg = "Cập nhật thông tin thành công!";
            $msg_type = "success";
        } else {
            $msg = "Lỗi cập nhật: " . $conn->error;
            $msg_type = "error";
        }
        $stmt->close();
    }

    // B. Đổi mật khẩu
    if (isset($_POST['change_password'])) {
        $current_pass = $_POST['currentPassword'];
        $new_pass = $_POST['newPassword'];
        $confirm_pass = $_POST['confirmNewPassword'];

        if ($new_pass !== $confirm_pass) {
            $msg = "Mật khẩu xác nhận không khớp!";
            $msg_type = "error";
        } else {
            // Lấy mật khẩu cũ từ DB
            $stmt = $conn->prepare("SELECT matkhau FROM tbl_nguoidung WHERE manguoidung = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $user_data = $res->fetch_assoc();
            $stmt->close();

            if (password_verify($current_pass, $user_data['matkhau'])) {
                // Mật khẩu cũ đúng -> Cập nhật mới
                $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt_up = $conn->prepare("UPDATE tbl_nguoidung SET matkhau = ? WHERE manguoidung = ?");
                $stmt_up->bind_param("si", $hashed_pass, $user_id);
                if ($stmt_up->execute()) {
                    $msg = "Đổi mật khẩu thành công!";
                    $msg_type = "success";
                }
                $stmt_up->close();
            } else {
                $msg = "Mật khẩu hiện tại không đúng!";
                $msg_type = "error";
            }
        }
    }
}

// 3. Lấy dữ liệu người dùng mới nhất
$stmt = $conn->prepare("SELECT * FROM tbl_nguoidung WHERE manguoidung = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 4. Lấy lịch sử đơn hàng
$order_sql = "SELECT d.*, h.mahoadon, h.ngaydat 
              FROM tbl_donhang d 
              LEFT JOIN tbl_hoadon h ON d.mahoadon = h.mahoadon 
              WHERE h.manguoidung = ? 
              ORDER BY d.madonhang DESC";
$stmt_orders = $conn->prepare($order_sql);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$orders = $stmt_orders->get_result();

// 5. Lấy danh sách địa chỉ
$addr_sql = "SELECT * FROM tbl_thongtiniaoahang WHERE manguoidung = ?";
$stmt_addr = $conn->prepare($addr_sql);
$stmt_addr->bind_param("i", $user_id);
$stmt_addr->execute();
$addresses = $stmt_addr->get_result();

// Định nghĩa trạng thái (để hiển thị đẹp hơn)
$status_map = [
    'cho_xac_nhan' => ['text' => 'Chờ xác nhận', 'class' => 'badge-warning'],
    'da_xac_nhan' => ['text' => 'Đã xác nhận', 'class' => 'badge-info'],
    'dang_giao' => ['text' => 'Đang giao', 'class' => 'badge-primary'],
    'da_giao' => ['text' => 'Hoàn thành', 'class' => 'badge-success'],
    'da_huy' => ['text' => 'Đã hủy', 'class' => 'badge-danger']
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        /* CSS Bổ sung cho Badge và Tab */
        .badge { padding: 5px 10px; border-radius: 15px; font-size: 0.8em; color: white; font-weight: bold;}
        .badge-warning { background-color: #f6c23e; }
        .badge-info { background-color: #36b9cc; }
        .badge-primary { background-color: #4e73df; }
        .badge-success { background-color: #1cc88a; }
        .badge-danger { background-color: #e74a3b; }
        .badge-secondary { background-color: #858796; }

        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.5s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Ẩn các nút hành động ban đầu */
        .form-actions-edit { display: none; margin-top: 20px; }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="profile-section">
        <div class="container">
            <div class="profile-header">
                <h1 class="page-title">Hồ Sơ Của Tôi</h1>
                <div class="breadcrumb">
                    <a href="index.php">Trang chủ</a> / <span>Hồ sơ</span>
                </div>
            </div>

            <!-- Hidden inputs để lưu thông báo từ PHP -->
            <input type="hidden" id="msg" value="<?php echo htmlspecialchars($msg); ?>">
            <input type="hidden" id="msg_type" value="<?php echo $msg_type; ?>">

            <div class="profile-layout">
                <aside class="profile-sidebar">
                    <div class="user-info">
                        <div class="user-avatar">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['hotennguoidung']); ?>&background=random" alt="Avatar">
                        </div>
                        <div class="user-details">
                            <h3 class="user-name"><?php echo htmlspecialchars($user['hotennguoidung']); ?></h3>
                            <p class="user-email"><?php echo htmlspecialchars($user['tendangnhap']); ?></p>
                        </div>
                    </div>

                    <nav class="profile-nav" style="background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
                        <a href="#personal" class="nav-item active" onclick="openTab(event, 'personal-tab')">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </a>
                        <a href="#orders" class="nav-item" onclick="openTab(event, 'orders-tab')">
                            <i class="fas fa-shopping-bag"></i> Đơn hàng của tôi
                            <span class="order-count"><?php echo $orders->num_rows; ?></span>
                        </a>
                        <a href="#addresses" class="nav-item" onclick="openTab(event, 'addresses-tab')">
                            <i class="fas fa-map-marker-alt"></i> Sổ địa chỉ
                        </a>
                        <a href="#change-password" class="nav-item" onclick="openTab(event, 'change-password-tab')">
                            <i class="fas fa-lock"></i> Đổi mật khẩu
                        </a>
                        <a href="logout.php" class="nav-item" style="color: red;">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </nav>
                </aside>

                <main class="profile-content">
                    
                    <div class="tab-content active" id="personal-tab">
                        <div class="tab-header">
                            <h2>Thông Tin Cá Nhân</h2>
                            <button class="btn btn-primary" type="button" onclick="enableEdit()">
                                <i class="fas fa-edit"></i> Chỉnh sửa
                            </button>
                        </div>

                        <form class="profile-form" method="POST">
                            <div class="form-group">
                                <label>Họ Tên</label>
                                <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($user['hotennguoidung']); ?>" readonly required>
                            </div>

                            <div class="form-group">
                                <label>Email (Tên đăng nhập)</label>
                                <input type="email" value="<?php echo htmlspecialchars($user['tendangnhap']); ?>" readonly disabled class="bg-gray-100">
                            </div>

                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($user['sodienthoai']); ?>" readonly pattern="[0-9]{10,11}">
                            </div>

                            <div class="form-actions form-actions-edit" id="personalActions">
                                <button type="button" class="btn btn-secondary" onclick="cancelEdit()">Hủy</button>
                                <button type="submit" name="update_profile" class="btn btn-primary">Lưu thay đổi</button>
                            </div>
                        </form>
                    </div>

                    <div class="tab-content" id="orders-tab">
                        <div class="tab-header">
                            <h2>Lịch Sử Đơn Hàng</h2>
                        </div>
                        <div class="orders-list">
                            <?php if ($orders->num_rows > 0): ?>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #eee; text-align: left;">
                                            <th style="padding: 10px;">Mã đơn</th>
                                            <th style="padding: 10px;">Ngày đặt</th>
                                            <th style="padding: 10px;">Tổng tiền</th>
                                            <th style="padding: 10px;">Trạng thái</th>
                                            <th style="padding: 10px;">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($order = $orders->fetch_assoc()): ?>
                                            <?php 
                                                $stt = $order['trangthai'];
                                                $stt_display = $status_map[$stt] ?? ['text' => $stt, 'class' => 'badge-secondary'];
                                            ?>
                                            <tr style="border-bottom: 1px solid #eee;">
                                                <td style="padding: 15px 10px; font-weight: bold;">#<?php echo $order['madonhang']; ?></td>
                                                <td style="padding: 15px 10px;"><?php echo date('d/m/Y', strtotime($order['ngaydat'])); ?></td>
                                                <td style="padding: 15px 10px; color: #d32f2f; font-weight: bold;"><?php echo number_format($order['tongtien']); ?>₫</td>
                                                <td style="padding: 15px 10px;">
                                                    <span class="badge <?php echo $stt_display['class']; ?>">
                                                        <?php echo $stt_display['text']; ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 15px 10px;">
                                                    <a href="order_detail.php?id=<?php echo $order['madonhang']; ?>" class="btn-sm btn-secondary">Xem</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <p style="text-align: center; color: #666; margin-top: 20px;">Bạn chưa có đơn hàng nào.</p>
                                <a href="products.php" class="btn btn-primary" style="display: block; width: fit-content; margin: 20px auto;">Mua sắm ngay</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-content" id="addresses-tab">
                        <div class="tab-header">
                            <h2>Sổ Địa Chỉ</h2>
                        </div>
                        <div class="addresses-list">
                            <?php if ($addresses->num_rows > 0): ?>
                                <?php while($addr = $addresses->fetch_assoc()): ?>
                                    <div class="address-card" style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px;">
                                        <p><strong>Người nhận:</strong> <?php echo htmlspecialchars($addr['hotennguoinhan']); ?></p>
                                        <p><strong>SĐT:</strong> <?php echo htmlspecialchars($addr['sodienthoai']); ?></p>
                                        <p><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($addr['diachi']); ?></p>
                                        <?php if($addr['tinhthanh']): ?>
                                            <p><?php echo htmlspecialchars($addr['quanhuyen'] . ', ' . $addr['tinhthanh']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>Chưa có địa chỉ nào được lưu. Địa chỉ sẽ được lưu tự động khi bạn đặt hàng.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-content" id="change-password-tab">
                        <div class="tab-header">
                            <h2>Đổi Mật Khẩu</h2>
                        </div>
                        <form class="profile-form" method="POST" id="changePasswordForm">
                            <div class="form-group">
                                <label>Mật khẩu hiện tại</label>
                                <input type="password" name="currentPassword" id="currentPassword" required>
                            </div>
                            <div class="form-group">
                                <label>Mật khẩu mới</label>
                                <input type="password" name="newPassword" id="newPassword" required minlength="6">
                            </div>
                            <div class="form-group">
                                <label>Xác nhận mật khẩu mới</label>
                                <input type="password" name="confirmNewPassword" id="confirmNewPassword" required minlength="6">
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="change_password" class="btn btn-primary">Cập nhật mật khẩu</button>
                            </div>
                        </form>
                    </div>

                </main>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Hàm hiển thị thông báo từ PHP
        function showNotification(message, type) {
            const config = {
                title: type === 'success' ? 'Thành công!' : 'Có lỗi xảy ra!',
                text: message,
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
                background: '#f8fafc'
            };
            
            if (type === 'success') {
                config.icon = 'success';
                config.iconColor = '#10b981';
            } else if (type === 'error') {
                config.icon = 'error';
                config.iconColor = '#ef4444';
            }
            
            Swal.fire(config);
        }

        // Kiểm tra và hiển thị thông báo khi trang tải xong
        document.addEventListener('DOMContentLoaded', function() {
            const msg = document.getElementById('msg')?.value;
            const msgType = document.getElementById('msg_type')?.value;
            
            if (msg && msgType) {
                showNotification(msg, msgType);
            }
            
            // Mặc định mở tab từ URL hash
            var hash = window.location.hash.substr(1);
            if(hash && document.getElementById(hash + "-tab")) {
                openTab(null, hash + "-tab");
                // Active nav item tương ứng
                var navs = document.getElementsByClassName("nav-item");
                for(var i=0; i<navs.length; i++) {
                    if(navs[i].getAttribute("href") == "#" + hash) {
                        navs[i].classList.add("active");
                    } else {
                        navs[i].classList.remove("active");
                    }
                }
            }
            
            // Xác nhận khi đổi mật khẩu
            document.getElementById('changePasswordForm')?.addEventListener('submit', function(e) {
                const currentPass = document.getElementById('currentPassword').value;
                const newPass = document.getElementById('newPassword').value;
                const confirmPass = document.getElementById('confirmNewPassword').value;
                
                if (newPass !== confirmPass) {
                    e.preventDefault();
                    showNotification('Mật khẩu xác nhận không khớp!', 'error');
                    return false;
                }
                
                if (newPass.length < 6) {
                    e.preventDefault();
                    showNotification('Mật khẩu mới phải có ít nhất 6 ký tự!', 'error');
                    return false;
                }
            });
        });

        // Hàm chuyển Tab
        function openTab(evt, tabName) {
            var i, tabcontent, navitem;
            
            // Ẩn tất cả nội dung tab
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }

            // Xóa class active khỏi tất cả nav item
            navitem = document.getElementsByClassName("nav-item");
            for (i = 0; i < navitem.length; i++) {
                navitem[i].classList.remove("active");
            }

            // Hiển thị tab hiện tại và thêm class active
            document.getElementById(tabName).style.display = "block";
            document.getElementById(tabName).classList.add("active");
            
            if(evt) evt.currentTarget.classList.add("active");
            
            // Cập nhật URL hash
            window.location.hash = tabName.replace('-tab', '');
        }

        // Xử lý bật/tắt chế độ sửa thông tin
        function enableEdit() {
            document.getElementById('fullname').removeAttribute('readonly');
            document.getElementById('phone').removeAttribute('readonly');
            document.getElementById('fullname').focus();
            document.getElementById('personalActions').style.display = 'flex';
        }

        function cancelEdit() {
            document.getElementById('fullname').setAttribute('readonly', true);
            document.getElementById('phone').setAttribute('readonly', true);
            document.getElementById('personalActions').style.display = 'none';
            
            // Reset giá trị về ban đầu
            const originalName = "<?php echo htmlspecialchars($user['hotennguoidung']); ?>";
            const originalPhone = "<?php echo htmlspecialchars($user['sodienthoai']); ?>";
            
            document.getElementById('fullname').value = originalName;
            document.getElementById('phone').value = originalPhone;
            
            // Thông báo hủy
            showNotification('Đã hủy chỉnh sửa', 'info');
        }
    </script>
</body>
</html>