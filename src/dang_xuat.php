<?php
session_start();
// Hủy bỏ toàn bộ session gán cho tài khoản
session_destroy();
// Quay trở về trang chủ
header("Location: dang_nhap.php");
exit;
?>