<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<footer class="main-footer">
    <div class="footer-top">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col footer-about">
                    <h3 class="footer-title">Gốm Sứ Tinh Hoa</h3>
                    <p class="footer-description">
                        Chuyên cung cấp các sản phẩm gốm sứ cao cấp, tinh xảo, 
                        mang đậm nét văn hóa truyền thống Việt Nam.
                    </p>
                    <div class="footer-social">
                        <a href="#" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-link" title="Youtube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="#" class="social-link" title="Zalo">
                            <i class="fab fa-facebook-messenger"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-col footer-links">
                    <h3 class="footer-title">Liên kết nhanh</h3>
                    <ul class="footer-menu">
                        <li><a href="index.php"><i class="fas fa-chevron-right"></i> Trang chủ</a></li>
                        <li><a href="products.php"><i class="fas fa-chevron-right"></i> Sản phẩm</a></li>
                        <li><a href="about.php"><i class="fas fa-chevron-right"></i> Giới thiệu</a></li>
                        <li><a href="contact.php"><i class="fas fa-chevron-right"></i> Liên hệ</a></li>
                        <li><a href="news.php"><i class="fas fa-chevron-right"></i> Tin tức</a></li>
                    </ul>
                </div>
                
                <div class="footer-col footer-categories">
                    <h3 class="footer-title">Danh mục</h3>
                    <ul class="footer-menu">
                        <?php
                        // Lấy danh sách danh mục cho footer
                        $cat_sql = "SELECT * FROM tbl_loai WHERE EXISTS (SELECT 1 FROM tbl_sanpham WHERE maloai = tbl_loai.maloai) LIMIT 5";
                        $cat_result = $conn->query($cat_sql);
                        
                        while($category = $cat_result->fetch_assoc()):
                        ?>
                        <li>
                            <a href="products.php?maloai=<?php echo $category['maloai']; ?>">
                                <i class="fas fa-chevron-right"></i> <?php echo htmlspecialchars($category['tenloai']); ?>
                            </a>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
                
                <div class="footer-col footer-contact">
                    <h3 class="footer-title">Thông tin liên hệ</h3>
                    <ul class="contact-info">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>123 Đường ABC, Quận XYZ, TP. Hà Nội</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>0987 654 321</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>info@gomsutinhhoa.vn</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span>8:00 - 22:00 (T2 - CN)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> Gốm Sứ Tinh Hoa. Tất cả các quyền được bảo lưu.</p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Back to top button -->
<button id="backToTop" class="back-to-top">
    <i class="fas fa-chevron-up"></i>
</button>

<!-- CSS cho footer -->
<style>
    .main-footer {
        background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
        color: #cbd5e1;
        margin-top: auto;
    }
    
    .footer-top {
        padding: 60px 0 40px;
    }
    
    .container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }
    
    .footer-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 40px;
    }
    
    @media (max-width: 768px) {
        .footer-grid {
            grid-template-columns: 1fr;
            gap: 30px;
        }
    }
    
    .footer-col {
        padding: 0 15px;
    }
    
    .footer-logo img {
        margin-bottom: 20px;
    }
    
    .footer-title {
        color: white;
        font-size: 1.3rem;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3b82f6;
        font-weight: 600;
        position: relative;
    }
    
    .footer-title::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 60px;
        height: 2px;
        background: #3b82f6;
    }
    
    .footer-description {
        line-height: 1.7;
        margin-bottom: 25px;
        font-size: 0.95rem;
    }
    
    .footer-social {
        display: flex;
        gap: 15px;
    }
    
    .social-link {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: rgba(255,255,255,0.1);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        transition: all 0.3s;
    }
    
    .social-link:hover {
        background: #3b82f6;
        transform: translateY(-3px);
    }
    
    .footer-menu {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .footer-menu li {
        margin-bottom: 12px;
    }
    
    .footer-menu a {
        color: #cbd5e1;
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: all 0.3s;
        font-size: 0.95rem;
    }
    
    .footer-menu a:hover {
        color: #3b82f6;
        transform: translateX(5px);
    }
    
    .footer-menu a i {
        margin-right: 10px;
        font-size: 0.8rem;
    }
    
    .contact-info {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .contact-info li {
        display: flex;
        align-items: flex-start;
        margin-bottom: 20px;
        line-height: 1.6;
    }
    
    .contact-info i {
        color: #3b82f6;
        margin-right: 15px;
        margin-top: 3px;
        font-size: 1.1rem;
        min-width: 20px;
    }
    
    .footer-bottom {
        background: rgba(0,0,0,0.2);
        padding: 25px 0;
        border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .footer-bottom-content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    @media (max-width: 768px) {
        .footer-bottom-content {
            flex-direction: column;
            gap: 20px;
            text-align: center;
        }
    }
    
    .copyright p {
        margin: 0;
        font-size: 0.9rem;
    }
    
    .payment-methods {
        display: flex;
        gap: 15px;
        align-items: center;
    }
    
    .payment-methods img {
        filter: brightness(0) invert(1);
        opacity: 0.8;
        transition: opacity 0.3s;
    }
    
    .payment-methods img:hover {
        opacity: 1;
    }
    
    /* Back to top button */
    .back-to-top {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #3b82f6;
        color: white;
        border: none;
        cursor: pointer;
        display: none;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        transition: all 0.3s;
        z-index: 999;
    }
    
    .back-to-top:hover {
        background: #2563eb;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
    }
    
    .back-to-top.show {
        display: flex;
    }
    
    /* Chat widget */
    .chat-widget {
        position: fixed;
        bottom: 30px;
        right: 100px;
        z-index: 998;
    }
    
    .chat-icons {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 15px;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s;
    }
    
    .chat-widget.active .chat-icons {
        opacity: 1;
        transform: translateY(0);
    }
    
    .chat-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-decoration: none;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        transition: all 0.3s;
        font-size: 1.2rem;
    }
    
    .chat-icon.phone {
        background: #10b981;
    }
    
    .chat-icon.zalo {
        background: #0068ff;
    }
    
    .chat-icon.messenger {
        background: #1877f2;
    }
    
    .chat-icon:hover {
        transform: scale(1.1);
    }
    
    .chat-toggle {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: #ef4444;
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 4px 20px rgba(239, 68, 68, 0.3);
        transition: all 0.3s;
    }
    
    .chat-toggle:hover {
        background: #dc2626;
        transform: scale(1.05);
    }
    
    /* Responsive chat widget */
    @media (max-width: 768px) {
        .chat-widget {
            right: 20px;
            bottom: 90px;
        }
        
        .back-to-top {
            right: 20px;
            bottom: 20px;
        }
    }
</style>

<script>
// Back to top button
const backToTop = document.getElementById('backToTop');

window.addEventListener('scroll', function() {
    if (window.pageYOffset > 300) {
        backToTop.classList.add('show');
    } else {
        backToTop.classList.remove('show');
    }
});

backToTop.addEventListener('click', function() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Chat widget toggle
let chatWidgetActive = false;

function toggleChatWidget() {
    const chatWidget = document.querySelector('.chat-widget');
    chatWidgetActive = !chatWidgetActive;
    
    if (chatWidgetActive) {
        chatWidget.classList.add('active');
    } else {
        chatWidget.classList.remove('active');
    }
}

// Đóng chat widget khi click ra ngoài
document.addEventListener('click', function(event) {
    const chatWidget = document.querySelector('.chat-widget');
    if (chatWidgetActive && !chatWidget.contains(event.target)) {
        chatWidgetActive = false;
        chatWidget.classList.remove('active');
    }
});

// Smooth scroll cho các link trong footer
document.querySelectorAll('footer a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Lazy loading cho hình ảnh footer
const footerImages = document.querySelectorAll('footer img');
const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            if (img.dataset.src) {
                img.src = img.dataset.src;
            }
            imageObserver.unobserve(img);
        }
    });
});

footerImages.forEach(img => {
    if (img.dataset.src) {
        imageObserver.observe(img);
    }
});
</script>

<!-- Include SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Kiểm tra và hiển thị thông báo từ session
if (isset($_SESSION['success'])) {
    echo '<script>
    Swal.fire({
        icon: "success",
        title: "Thành công!",
        text: "' . addslashes($_SESSION['success']) . '",
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000
    });
    </script>';
    unset($_SESSION['success']);
}

if (isset($_SESSION['error'])) {
    echo '<script>
    Swal.fire({
        icon: "error",
        title: "Lỗi!",
        text: "' . addslashes($_SESSION['error']) . '",
        confirmButtonColor: "#ef4444"
    });
    </script>';
    unset($_SESSION['error']);
}

if (isset($_SESSION['warning'])) {
    echo '<script>
    Swal.fire({
        icon: "warning",
        title: "Cảnh báo!",
        text: "' . addslashes($_SESSION['warning']) . '",
        confirmButtonColor: "#f59e0b"
    });
    </script>';
    unset($_SESSION['warning']);
}
?>

</body>
</html>