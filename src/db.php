<?php
// Cấu hình thông số kết nối cơ sở dữ liệu XAMPP
$host = "db";
$username = "root";
$password = ""; // Mặc định của XAMPP để trống
$dbname = "quanlybanbooks";

try {
    // Tạo kết nối bằng PDO với cấu hình hỗ trợ tiếng Việt (utf8mb4)
    $ket_noi = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Thiết lập chế độ báo lỗi (chỉ giữ lại để phục vụ lập trình, không in ra giao diện)
    $ket_noi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // Nếu xảy ra lỗi kết nối nghiêm trọng, hệ thống sẽ dừng lại và báo lỗi
    die(" Lỗi kết nối cơ sở dữ liệu: " . $e->getMessage());
}
?>