<?php
// Bật session nếu hệ thống của bạn sử dụng đăng nhập để lấy tên User hiển thị trên Header
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

// Lấy ID sách từ URL
$id_sach = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Truy vấn thông tin chi tiết cuốn sách kết hợp lấy luôn tên thể loại của nó
$stmt = $ket_noi->prepare("
    SELECT s.*, tl.ten_the_loai 
    FROM sach s 
    LEFT JOIN the_loai tl ON s.id_the_loai = tl.id 
    WHERE s.id = ?
");
$stmt->execute([$id_sach]);
$sach = $stmt->fetch(PDO::FETCH_ASSOC);

// Nếu không tìm thấy sách, quay về trang chủ
if (!$sach) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sach['ten_sach']); ?> - Chi Tiết Sách</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            background-color: #f4f6f9; 
            color: #333; 
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* --- HEADER CHUẨN ĐỒNG BỘ --- */
        .main-header {
            background: linear-gradient(135deg, #1f2c39 0%, #111a24 100%);
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 3px solid #e67e22;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand-logo {
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #5dade2;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brand-text h1 {
            color: #ffffff;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }

        .brand-text span {
            color: #a0aec0;
            font-size: 12px;
            font-style: italic;
        }

        .header-user {
            background-color: rgba(255, 255, 255, 0.05);
            padding: 8px 18px;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #e2e8f0;
            font-size: 14px;
        }

        .header-user span {
            font-weight: bold;
            color: #fff;
        }

        .btn-logout {
            color: #2ecc71;
            text-decoration: none;
            margin-left: 10px;
            font-weight: bold;
        }

        /* --- ĐIỀU HƯỚNG & KHUNG NỘI DUNG --- */
        .page-content {
            flex: 1;
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            padding: 20px 15px 40px 15px;
        }

        .btn-back { 
            display: inline-flex; 
            align-items: center; 
            gap: 5px;
            margin-bottom: 20px; 
            color: #2980b9; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 14px;
            transition: color 0.2s;
        }
        .btn-back:hover { color: #1a5276; }

        .container-box { 
            background: white; 
            padding: 35px; 
            border-radius: 12px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.06); 
        }
        
        /* --- CHI TIẾT SÁCH --- */
        .book-detail { display: flex; gap: 45px; flex-wrap: wrap; }
        
        .book-left { 
            flex: 1; 
            min-width: 280px; 
            max-width: 320px;
        }
        
        .book-cover-wrapper {
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
            border: 1px solid #e2e8f0;
            background-color: #fff;
            aspect-ratio: 3/4;
        }

        .book-cover-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        
        .book-right { flex: 1.5; min-width: 320px; }
        
        .book-right h2 { color: #2c3e50; margin-bottom: 8px; font-size: 28px; font-weight: 700; }
        .author { font-size: 15px; color: #7f8c8d; margin-bottom: 25px; }
        
        .info-row { margin-bottom: 14px; font-size: 15px; display: flex; gap: 8px; }
        .info-label { font-weight: 600; color: #4a5568; min-width: 80px; }
        .badge-genre { background-color: #edf2f7; padding: 3px 10px; border-radius: 15px; color: #4a5568; font-size: 13px; font-weight: 500; }
        
        /* Box Giá Tiền Mới */
        .price-box { 
            background-color: #f7fafc; 
            padding: 18px; 
            border-radius: 8px; 
            margin: 25px 0; 
            border-left: 4px solid #e67e22; 
        }
        .price-buy { font-size: 22px; color: #e53e3e; font-weight: 700; margin-bottom: 6px; }
        .price-rent { font-size: 16px; color: #2b6cb0; font-weight: 600; }
        
        /* Nhóm Nút Bấm Hành Động */
        .btn-action-group { display: flex; gap: 15px; margin-top: 30px; }
        .btn { 
            flex: 1; 
            text-align: center; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 6px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            text-decoration: none; 
            color: white; 
            transition: all 0.2s ease; 
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .btn-buy { background-color: #2ecc71; }
        .btn-buy:hover { background-color: #27ae60; transform: translateY(-1px); }
        .btn-rent { background-color: #3498db; }
        .btn-rent:hover { background-color: #2980b9; transform: translateY(-1px); }
        
        /* Phần tóm tắt */
        .description { 
            margin-top: 35px; 
            line-height: 1.7; 
            color: #4a5568; 
            border-top: 1px solid #edf2f7; 
            padding-top: 25px; 
        }
        .description h3 { color: #2c3e50; margin-bottom: 12px; font-size: 18px; font-weight: 600; }
        .description p { font-size: 15px; text-align: justify; white-space: pre-line; }

        /* --- FOOTER CHUẨN ĐỒNG BỘ --- */
        .main-footer {
            background-color: #22313f;
            color: #bdc3c7;
            padding: 40px 40px 20px 40px;
            border-top: 4px solid #e67e22;
            font-size: 14px;
            margin-top: auto;
        }

        .footer-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 40px;
        }

        .footer-col h3 {
            color: #ffffff;
            font-size: 16px;
            margin-bottom: 20px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .footer-col p {
            line-height: 1.6;
            color: #a0aec0;
        }

        .footer-links { list-style: none; }
        .footer-links li {
            margin-bottom: 12px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
        }

        .footer-links a {
            color: #e67e22;
            text-decoration: none;
            font-weight: 500;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 12px;
            color: #718096;
        }
    </style>
</head>
<body>

    <header class="main-header">
        <div class="header-brand">
            <div class="brand-text">
                <h1>Tạp Hóa Sách</h1>
                <span>" Mỗi trang sách là một chân trời mới ! "</span>
            </div>
        </div>
        <div class="header-user">
            👋 Xin chào, <span><?php echo isset($_SESSION['user']['ho_ten']) ? htmlspecialchars($_SESSION['user']['ho_ten']) : 'Alex Xơn Đơ'; ?></span> | <a href="dang_xuat.php" class="btn-logout">[Đăng xuất]</a>
        </div>
    </header>

    <main class="page-content">
        <a href="index.php" class="btn-back">⬅️ Quay lại trang danh sách</a>
        
        <div class="container-box">
            <div class="book-detail">
                <div class="book-left">
                    <div class="book-cover-wrapper">
                        <img src="uploads/<?php echo htmlspecialchars($sach['anh_bia']); ?>" alt="Bìa sách">
                    </div>
                </div>
                
                <div class="book-right">
                    <h2><?php echo htmlspecialchars($sach['ten_sach']); ?></h2>
                    <p class="author">Tác giả: <strong><?php echo htmlspecialchars($sach['tac_gia']); ?></strong></p>
                    
                    <div class="info-row">
                        <span class="info-label">Thể loại:</span> 
                        <span class="badge-genre"><?php echo htmlspecialchars($sach['ten_the_loai'] ? $sach['ten_the_loai'] : 'Chưa phân loại'); ?></span>
                    </div>
                    
                    <div class="price-box">
                        <div class="price-buy">Giá bán: <?php echo number_format($sach['gia_ban'], 0, ',', '.'); ?> đ</div>
                        <div class="price-rent">Giá thuê: <?php echo number_format($sach['gia_thue_theo_ngay'], 0, ',', '.'); ?> đ / ngày</div>
                    </div>

                    <div class="btn-action-group">
                        <a href="gio_hang.php?hanh_dong=them&id=<?php echo $sach['id']; ?>&hinh_thuc=mua" class="btn btn-buy">🛒 Mua Sách Mua Đứt</a>
                        <a href="gio_hang.php?hanh_dong=them&id=<?php echo $sach['id']; ?>&hinh_thuc=thue" class="btn btn-rent">📖 Thuê Sách Đọc</a>
                    </div>
                </div>
            </div>

            <div class="description">
                <h3>📋 Tóm tắt nội dung / Mô tả sản phẩm</h3>
                <p><?php echo nl2br(htmlspecialchars($sach['mo_ta_chi_tiet'])); ?></p>
            </div>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-grid">
            <div class="footer-col">
                <h3>📚 TẠP HÓA SÁCH</h3>
                <p>Hệ thống cung cấp tri thức, mua bán và cho thuê các đầu sách đa dạng thể loại với chi phí tiết kiệm nhất cho mọi độc giả.</p>
            </div>

            <div class="footer-col">
                <h3>📍 THÔNG TIN LIÊN HỆ</h3>
                <ul class="footer-links">
                    <li>
                        <span>🏠 <strong>Địa chỉ:</strong></span> 
                        <span style="color: #a0aec0;">123, đường Nguyễn Hương, tổ 60, khóm Bến Bắc, thành phố Cao Lãnh, tỉnh Đồng Tháp.</span>
                    </li>
                    <li>
                        <span>📞 <strong>Số điện thoại:</strong></span> 
                        <a href="tel:0789512345">0789512345</a>
                    </li>
                    <li>
                        <span>✉️ <strong>Email chủ shop:</strong></span> 
                        <a href="mailto:admin@gmail.com" style="color: #e67e22;">admin@gmail.com</a>
                    </li>
                </ul>
            </div>

            <div class="footer-col">
                <h3>🕒 THỜI GIAN MỞ CỬA</h3>
                <p>Thứ 2 - Chủ Nhật: 07:00 - 22:00</p>
                <p style="font-size: 13px; color: #718096; margin-top: 12px;">Hệ thống website hỗ trợ đặt hàng trực tuyến 24/7.</p>
            </div>
        </div>

        <div class="footer-bottom">
            © 2026 Tạp Hóa Sách. All rights reserved. Thiết kế hệ thống quản lý kho và thuê sách trực tuyến.
        </div>
    </footer>

</body>
</html>