<footer class="pt-4">
    <div class="w-full px-6 mx-auto">
        <div class="flex flex-wrap items-center -mx-3 lg:justify-between">
            <div class="w-full max-w-full px-3 mt-0 mb-6 shrink-0 lg:mb-0 lg:w-1/2 lg:flex-none">
                <div class="text-sm leading-normal text-center text-slate-500 lg:text-left">
                    © <script>document.write(new Date().getFullYear())</script>, made with <i class="fa fa-heart"></i> by Team
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Include SweetAlert2 CSS & JS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Kiểm tra Session Success (cần được định nghĩa trước trong PHP)
    <?php if(isset($_SESSION['success'])): ?>
        const successMessage = <?php echo json_encode($_SESSION['success']); ?>;
        Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: successMessage,
            timer: 2000,
            showConfirmButton: false,
            toast: true,
            position: 'top-end',
            timerProgressBar: true,
            background: '#f0f9ff',
            iconColor: '#10b981'
        });
    <?php endif; ?>

    // 2. Kiểm tra Session Error
    <?php if(isset($_SESSION['error'])): ?>
        const errorMessage = <?php echo json_encode($_SESSION['error']); ?>;
        Swal.fire({
            icon: 'error',
            title: 'Có lỗi xảy ra!',
            text: errorMessage,
            confirmButtonColor: '#ef4444',
            background: '#fef2f2',
            iconColor: '#ef4444'
        });
    <?php endif; ?>
});
</script>

<!-- Unset session trong PHP (quan trọng) -->
<?php
// Unset session messages sau khi đã sử dụng
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    unset($_SESSION['error']);
}
?>
</body>
</html>