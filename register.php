<?php
require 'conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Kiểm tra và làm sạch dữ liệu
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $trangthai = 1;
    $vaitro = 'user';
    
    $errors = [];
    
    // Kiểm tra dữ liệu không rỗng
    if (empty($name)) $errors[] = "Vui lòng nhập họ tên";
    if (empty($email)) $errors[] = "Vui lòng nhập email";
    if (empty($password)) $errors[] = "Vui lòng nhập mật khẩu";
    if (empty($confirmPassword)) $errors[] = "Vui lòng xác nhận mật khẩu";
    
    // Kiểm tra email hợp lệ
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    
    // Kiểm tra mật khẩu khớp
    if ($password !== $confirmPassword) {
        $errors[] = "Mật khẩu xác nhận không khớp";
    }
    
    // Kiểm tra độ dài mật khẩu
    if (strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
    }
    
    // Kiểm tra email đã tồn tại chưa
    if (empty($errors)) {
        $check_email = $conn->prepare("SELECT manguoidung FROM tbl_nguoidung WHERE tendangnhap = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();
        
        if ($check_email->num_rows > 0) {
            $errors[] = "Email này đã được đăng ký";
        }
        $check_email->close();
    }
    
    // Nếu có lỗi, hiển thị và dừng
    if (!empty($errors)) {
        $error_message = implode("<br>", $errors);
        echo "<script>alert('$error_message');</script>";
    } else {
        // Hash mật khẩu
        $pass = password_hash($password, PASSWORD_DEFAULT);
        
        // Sử dụng prepared statement để tránh SQL injection
        $query = "INSERT INTO tbl_nguoidung (hotennguoidung, tendangnhap, matkhau, trangthai, vaitro) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        
        if ($stmt) {
            $stmt->bind_param("sssss", $name, $email, $pass, $trangthai, $vaitro);
            
            if ($stmt->execute()) {
                echo "<script>
                    alert('Đăng ký thành công! Bạn có thể đăng nhập ngay.');
                    window.location.href = 'login.php';
                </script>";
            } else {
                echo "<script>alert('Lỗi hệ thống: " . $stmt->error . "');</script>";
            }
            
            $stmt->close();
        } else {
            echo "<script>alert('Lỗi hệ thống: " . $conn->error . "');</script>";
        }
    }
    
    // ĐÃ XÓA: $conn->close(); để header.php có thể sử dụng kết nối
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-form">
                    <h2>Tạo Tài Khoản</h2>
                    <form id="registerForm" method="POST" action="">
                        <div class="form-group">
                            <label for="firstName">Họ Tên</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required>
                            <div class="form-error" id="emailError"></div>
                        </div>

                        <div class="form-group">
                            <label for="password">Mật khẩu</label>
                            <div class="password-input">
                                <input type="password" id="password" name="password" required>
                                <button type="button" class="toggle-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirmPassword">Xác nhận mật khẩu</label>
                            <div class="password-input">
                                <input type="password" id="confirmPassword" name="confirmPassword" required>
                                <button type="button" class="toggle-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="form-error" id="confirmPasswordError"></div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full" id="registerBtn">
                            <i class="fas fa-user-plus"></i> Đăng Ký
                        </button>
                    </form>

                    <div class="auth-switch">
                        Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'includes/footer.php'; ?>

    <script src="js/main.js"></script>
    <script src="js/register.js"></script>
</body>
</html>