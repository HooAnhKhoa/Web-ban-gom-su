<?php
// Đường dẫn thư mục upload (sửa lại cho đúng cấu trúc của bạn)
$folder = '../../../uploads/products/';

if (!file_exists($folder)) {
    echo "Thư mục không tồn tại. Hãy tạo thư mục trước!";
} elseif (is_writable($folder)) {
    echo "Thành công: Thư mục '$folder' ĐÃ có quyền ghi (Upload OK).";
} else {
    echo "Lỗi: Thư mục '$folder' KHÔNG có quyền ghi. Hãy cấp quyền lại.";
}
?>