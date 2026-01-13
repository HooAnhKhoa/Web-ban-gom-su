<?php
// login.php
session_start();
require_once 'conn.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = "Vui lòng điền đầy đủ thông tin";
    } else {
        try {
            $stmt = $conn->prepare("SELECT manguoidung, hotennguoidung, tendangnhap, matkhau, trangthai, vaitro FROM tbl_nguoidung WHERE tendangnhap = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Kiểm tra trạng thái tài khoản
                if ($user['trangthai'] != 1) {
                    $error = "Tài khoản đã bị khóa. Vui lòng liên hệ quản trị viên.";
                } else {
                    // Kiểm tra mật khẩu
                    if (password_verify($password, $user['matkhau'])) {
                        // Lưu thông tin user vào session
                        $_SESSION['user_id'] = $user['manguoidung'];
                        $_SESSION['user_name'] = $user['hotennguoidung'];
                        $_SESSION['user_email'] = $user['tendangnhap'];
                        $_SESSION['user_role'] = $user['vaitro'];
                        $_SESSION['logged_in'] = true;
                        
                        // Chuyển hướng dựa trên vai trò
                        if ($user['vaitro'] === 'admin') {
                            header("Location: admin1/build");
                        } else {
                            header("Location: index.php");
                        }
                        exit();
                    } else {
                        $error = "Email hoặc mật khẩu không đúng";
                    }
                }
            } else {
                $error = "Email hoặc mật khẩu không đúng";
            }
            
            $stmt->close();
            
        } catch(Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Lỗi hệ thống. Vui lòng thử lại sau.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - Gốm Sứ Tinh Hoa</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background-color: #fee;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-success {
            background-color: #e8f5e8;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>

    <!-- Login Section -->
    <section class="auth-section">
        <div class="container">
            <div class="auth-container">
                <div class="auth-form">
                    <h2>Đăng Nhập</h2>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered']) && $_GET['registered'] == 'success'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Đăng ký thành công! Vui lòng đăng nhập.
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['logout']) && $_GET['logout'] == 'success'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            Đăng xuất thành công!
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required autofocus>
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
                        
                        <div class="form-options">
                            <label class="checkbox">
                                <input type="checkbox" name="remember" id="remember" 
                                       <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                Ghi nhớ đăng nhập
                            </label>
                            <a href="forgot-password.php" class="forgot-password">Quên mật khẩu?</a>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full">
                            <i class="fas fa-sign-in-alt"></i> Đăng Nhập
                        </button>
                    </form>
                    
                    <div class="auth-switch">
                        Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Hiển thị/ẩn mật khẩu
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const passwordInput = this.previousElementSibling;
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                } else {
                    passwordInput.type = 'password';
                    icon.className = 'fas fa-eye';
                }
            });
        });

        // Clear error khi user bắt đầu nhập
        document.getElementById('email').addEventListener('input', clearError);
        document.getElementById('password').addEventListener('input', clearError);
        
        function clearError() {
            const errorAlert = document.querySelector('.alert-error');
            if (errorAlert) {
                errorAlert.style.display = 'none';
            }
        }
    </script>
        <?php include 'includes/footer.php'; ?>

</body>
</html>