<?php
session_start();
require_once 'db.php';

// 1. Lấy danh sách thể loại để làm menu phân loại
$query_the_loai = $ket_noi->query("SELECT * FROM the_loai");
$danh_sach_the_loai = $query_the_loai->fetchAll(PDO::FETCH_ASSOC);

// --- CẬP NHẬT CÂU LỆNH SQL TRUY VẤN DANH SÁCH SÁCH LỌC THEO TỪ KHÓA ---
$tu_khoa = isset($_GET['tu_khoa']) ? trim($_GET['tu_khoa']) : '';

// Lấy danh sách Sách Nổi Bật (Nếu có tìm kiếm thì lọc theo từ khóa)
$sql_noibat = "SELECT * FROM sach WHERE noi_bat = 1";
if (!empty($tu_khoa)) {
    $sql_noibat .= " AND (ten_sach LIKE :tu_khoa OR tac_gia LIKE :tu_khoa)";
}
$sql_noibat .= " ORDER BY id DESC LIMIT 4"; 
$stmt_nb = $ket_noi->prepare($sql_noibat);
if (!empty($tu_khoa)) {
    $stmt_nb->bindValue(':tu_khoa', '%' . $tu_khoa . '%');
}
$stmt_nb->execute();
$sach_noi_bat = $stmt_nb->fetchAll(PDO::FETCH_ASSOC);

// Lấy Danh Mục Toàn Bộ Sách (Nếu có tìm kiếm thì lọc theo từ khóa)
$sql_tatca = "SELECT * FROM sach WHERE 1=1";
if (!empty($tu_khoa)) {
    $sql_tatca .= " AND (ten_sach LIKE :tu_khoa OR tac_gia LIKE :tu_khoa)";
}
$sql_tatca .= " ORDER BY id DESC";
$stmt_tc = $ket_noi->prepare($sql_tatca);
if (!empty($tu_khoa)) {
    $stmt_tc->bindValue(':tu_khoa', '%' . $tu_khoa . '%');
}
$stmt_tc->execute();
$tat_ca_sach = $stmt_tc->fetchAll(PDO::FETCH_ASSOC); 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tạp Hóa Sách - Mỗi trang sách là một chân trời mới !</title>
    <style>
        /* Toàn bộ hệ thống font và reset */
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,400&display=swap');
        
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #f8fafc; color: #1e293b; padding-bottom: 60px; line-height: 1.5; }

        /* HEADER SIÊU GỌN GÀNG */
        header { 
            background: radial-gradient(circle at top right, rgba(16, 185, 129, 0.12), transparent 45%), 
                        linear-gradient(135deg, #1e293b 0%, #0f172a 100%); 
            color: white; 
            padding: 30px 60px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 40px;
            position: relative; 
            overflow: hidden; 
        }

        .header-brand-block {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .logo-title-wrapper {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .shop-logo {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }
        header h1 { 
            font-family: 'Playfair Display', serif;
            font-size: 2.4rem; 
            font-weight: 600; 
            letter-spacing: -0.5px;
            background: linear-gradient(to right, #ffffff, #f1f5f9, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        header p { 
            font-family: 'Playfair Display', serif;
            font-style: italic;
            color: #94a3b8; 
            font-size: 1rem; 
            letter-spacing: 0.5px;
            padding-left: 4px;
        }

        /* KHỐI TÀI KHOẢN TRÊN HEADER */
        .header-action-block {
            display: flex;
            align-items: center;
        }
        .user-status {
            font-size: 13px;
            color: #94a3b8;
            background: rgba(255, 255, 255, 0.05);
            padding: 8px 18px;
            border-radius: 30px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .user-status a { color: #34d399; text-decoration: none; font-weight: 600; margin-left: 5px; transition: color 0.2s; }
        .user-status a:hover { color: #10b981; text-decoration: underline; }
        
        /* Định dạng riêng cho cụm nút Đơn Mua / Đơn Thuê / Giỏ hàng */
        .order-actions-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-donhang {
            color: #475569;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 16px;
            background: #ffffff;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        .btn-donhang:hover {
            color: #0f172a;
            background: #f1f5f9;
            border-color: #94a3b8;
        }

        /* NAV */
        nav { 
            background-color: white; 
            padding: 0 60px; 
            box-shadow: 0 4px 10px rgba(15, 23, 42, 0.03); 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .nav-left-group {
            display: flex;
            align-items: center;
            gap: 24px; 
        }

        .nav-link { 
            color: #64748b; 
            text-decoration: none; 
            padding: 18px 0; 
            font-weight: 600; 
            font-size: 15px; 
            letter-spacing: 0.3px;
            transition: all 0.25s ease; 
            border-bottom: 3px solid transparent; 
            display: inline-flex; 
            align-items: center;
            gap: 6px;
            cursor: pointer;
            background: none;
            border: none;
        }
        .nav-link:hover, .dropdown:hover .nav-link { 
            color: #10b981; 
            border-bottom-color: #10b981; 
        }

        .nav-link .arrow-icon {
            font-size: 10px;
            color: #94a3b8;
            transition: transform 0.25s ease;
        }
        .dropdown:hover .arrow-icon {
            transform: rotate(180deg);
            color: #10b981;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            left: 0;
            top: 100%;
            background-color: white;
            min-width: 240px;
            max-height: 380px; 
            overflow-y: auto;  
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            border-radius: 0 0 8px 8px;
            padding: 6px 0;
            z-index: 200;
            animation: fadeInMenu 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .dropdown-content a {
            color: #334155;
            padding: 11px 20px;
            text-decoration: none;
            display: block;
            font-size: 14px;
            font-weight: 500;
            text-align: left;
            transition: all 0.2s ease;
        }
        .dropdown-content a:hover {
            background-color: #f0fdf4;
            color: #10b981;
            padding-left: 24px; 
        }
        .dropdown:hover .dropdown-content {
            display: block;
        }

        @keyframes fadeInMenu {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* KHỐI TÌM KIẾM BÊN PHẢI */
        .nav-search-block {
            min-width: 340px;
            padding: 10px 0;
        }
        .nav-search-input {
            flex: 1; 
            padding: 8px 16px; 
            border: 1px solid #cbd5e1; 
            border-radius: 20px; 
            font-size: 13px; 
            outline: none; 
            background: #f8fafc; 
            color: #1e293b;
            transition: all 0.2s;
        }
        .nav-search-input:focus {
            border-color: #10b981;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .nav-search-btn {
            padding: 8px 18px; 
            background-color: #e67e22; 
            color: white; 
            border: none; 
            border-radius: 20px; 
            font-weight: bold; 
            font-size: 13px; 
            cursor: pointer; 
            transition: 0.2s; 
            white-space: nowrap;
        }
        .nav-search-btn:hover {
            background-color: #d35400;
        }
        
        /* Container & Tiêu đề vùng nội dung */
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        
        h2 { margin: 40px 0 20px 0; color: #0f172a; font-size: 1.5rem; font-weight: 700; display: flex; justify-content: space-between; align-items: center; position: relative; padding-bottom: 10px; }
        h2::after { content: ''; position: absolute; bottom: 0; left: 0; width: 60px; height: 4px; background: #10b981; border-radius: 2px; }
        
        /* Nút xem giỏ hàng */
        .view-cart-btn { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); color: white; padding: 10px 20px; border-radius: 8px; font-size: 14px; text-decoration: none; font-weight: 600; box-shadow: 0 4px 10px rgba(234, 88, 12, 0.3); transition: all 0.3s ease; display: flex; align-items: center; gap: 8px; }
        .view-cart-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(234, 88, 12, 0.4); filter: brightness(1.1); }
        
        /* Grid hiển thị sách thông minh */
        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 30px; }
        
        /* Thẻ sách đổ bóng mịn */
        .book-card { background: white; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01); text-align: center; display: flex; flex-direction: column; justify-content: space-between; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); position: relative; overflow: hidden; }
        .book-card:hover { transform: translateY(-8px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); border-color: #cbd5e1; }
        
        .book-cover { width: 100%; height: 260px; border-radius: 8px; margin-bottom: 15px; overflow: hidden; background-color: #f1f5f9; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 8px rgba(0,0,0,0.05); transition: transform 0.3s ease; }
        .book-card:hover .book-cover { transform: scale(1.03); }
        
        .book-title { font-size: 16px; font-weight: 600; margin-bottom: 6px; line-height: 1.4; height: 44px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
        .book-title a { text-decoration: none; color: #1e293b; transition: color 0.2s; }
        .book-title a:hover { color: #10b981; }
        .book-author { font-size: 13px; color: #64748b; margin-bottom: 15px; font-weight: 500; }
        
        /* KHỐI THÔNG TIN GIÁ SÁCH */
        .price-box { border-top: 1px dashed #e2e8f0; padding-top: 15px; margin-bottom: 15px; text-align: left; }
        .book-price { font-size: 15px; color: #ef4444; font-weight: 700; margin-bottom: 4px; display: flex; justify-content: space-between; }
        .book-price span { font-weight: 400; color: #64748b; font-size: 13px; }
        .book-rent { font-size: 14px; color: #0284c7; font-weight: 700; margin-bottom: 4px; display: flex; justify-content: space-between; }
        .book-rent span { font-weight: 400; color: #64748b; font-size: 13px; }

        /* KHÔI PHỤC ĐẦY ĐỦ CSS CHO NÚT MUA VÀ THUÊ SÁCH */
        .btn-group { display: flex; gap: 8px; }
        .btn { flex: 1; padding: 10px 5px; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; text-decoration: none; transition: all 0.2s ease; text-align: center; }
        
        .btn-buy { background-color: #10b981; color: white; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2); }
        .btn-buy:hover { background-color: #059669; transform: scale(1.02); }
        
        .btn-rent { background-color: #0ea5e9; color: white; box-shadow: 0 2px 4px rgba(14, 165, 233, 0.2); }
        .btn-rent:hover { background-color: #0284c7; transform: scale(1.02); }

        /* Tối ưu hóa hiển thị Responsive trên thiết bị di động nhỏ */
        @media (max-width: 900px) {
            header { padding: 30px 20px; flex-direction: column; align-items: center; text-align: center; gap: 20px; }
            .logo-title-wrapper { flex-direction: column; gap: 10px; }
            header h1 { font-size: 2rem; }
            header p { font-size: 0.95rem; padding-left: 0; }
            .user-status { width: 100%; text-align: center; }
            
            nav { flex-direction: column; padding: 15px 20px; gap: 15px; align-items: stretch; }
            .nav-left-group { justify-content: center; gap: 20px; }
            .nav-link { padding: 10px 0; }
            .nav-search-block { min-width: 100%; width: 100%; padding: 0; }
            
            h2 { font-size: 1.2rem; flex-direction: column; align-items: flex-start; gap: 15px; }
            .order-actions-group { width: 100%; flex-direction: column; gap: 8px; }
            .view-cart-btn { width: 100%; justify-content: center; }
            .btn-donhang { width: 100%; text-align: center; }
            .book-grid { grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 15px; }
            .book-cover { height: 200px; }
            .btn-group { flex-direction: column; }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-brand-block">
            <div class="logo-title-wrapper">
                <img src="uploads/logo.png" alt="Logo" class="shop-logo" onerror="this.style.display='none'">
                <h1> Tạp Hóa Sách</h1>
            </div>
            <p>" Mỗi trang sách là một chân trời mới ! "</p>
        </div>

        <div class="header-action-block">
            <div class="user-status">
                <?php if(isset($_SESSION['user'])): ?>
                    👋 Xin chào, <strong><?php echo $_SESSION['user']['ho_ten']; ?></strong> 
                    | <a href="dang_xuat.php">[Đăng xuất]</a>
                <?php else: ?>
                    Chào khách! <a href="dang_nhap.php">Đăng nhập</a> hoặc <a href="dang_ky.php">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <nav>
        <div class="nav-left-group">
            <div class="dropdown">
                <button class="nav-link">
                    <span class="arrow-icon">▼</span>Các Thể Loại Sách
                </button>
                <div class="dropdown-content">
                    <?php foreach($danh_sach_the_loai as $tl): ?>
                        <a href="the_loai.php?id=<?php echo $tl['id']; ?>">
                            <?php echo $tl['ten_the_loai']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="khieu_nai.php" class="nav-link">Khiếu nại</a>
        </div>
        
        <div class="nav-search-block">
            <form action="index.php" method="GET" style="display: flex; gap: 6px; width: 100%;">
                <input type="text" name="tu_khoa" value="<?php echo htmlspecialchars($_GET['tu_khoa'] ?? ''); ?>" 
                       placeholder="Tìm tên sách hoặc tác giả..." class="nav-search-input">
                <button type="submit" class="nav-search-btn">
                    Tìm
                </button>
                <?php if(!empty($_GET['tu_khoa'])): ?>
                    <a href="index.php" style="padding: 8px 14px; background-color: #64748b; color: white; text-decoration: none; border-radius: 20px; font-size: 12px; display: flex; align-items: center; font-weight: 600;">Xóa</a>
                <?php endif; ?>
            </form>
        </div>
    </nav>

    <div class="container">
        
        <h2>
            ⭐ Sách Nổi Bật Bán Chạy 
            <div class="order-actions-group">
                <?php if(isset($_SESSION['user'])): ?>
                    <a href="xem_don_mua.php" class="btn-donhang">📦 Đơn Mua</a>
                    <a href="xem_don_thue.php" class="btn-donhang">📋 Đơn Thuê</a>
                <?php endif; ?>
                <a href="gio_hang.php" class="view-cart-btn">🛒 Xem Giỏ Hàng</a>
            </div>
        </h2>
        
        <div class="book-grid">
            <?php if (empty($sach_noi_bat)): ?>
                <p style="grid-column: 1/-1; text-align: center; color: #64748b; padding: 20px;">Không tìm thấy sách nổi bật phù hợp với từ khóa.</p>
            <?php else: ?>
                <?php foreach($sach_noi_bat as $sach): ?>
                    <div class="book-card">
                        <div>
                            <div class="book-cover">
                                <img src="uploads/<?php echo $sach['anh_bia']; ?>" alt="Bìa sách" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div class="book-title">
                                <a href="chi_tiet_sach.php?id=<?php echo $sach['id']; ?>">
                                    <?php echo $sach['ten_sach']; ?>
                                </a>
                            </div>
                            <div class="book-author">TG: <?php echo $sach['tac_gia']; ?></div>
                        </div>
                        <div>
                            <div class="price-box">
                                <div class="book-price"><span>Mua Sách:</span> <?php echo number_format($sach['gia_ban'], 0, ',', '.'); ?> đ</div>
                                <div class="book-rent"><span>Thuê:</span> <?php echo number_format($sach['gia_thue_theo_ngay'], 0, ',', '.'); ?> đ/ngày</div>
                                <div style="font-size: 13px; color: #64748b; font-weight: 700; display: flex; justify-content: space-between; margin-top: 4px;">
                                    <span>Còn trong kho:</span> 
                                    <span style="font-weight: 700; color: <?php echo $sach['so_luong_ton'] > 0 ? '#10b981' : '#ef4444'; ?>;">
                                        <?php echo number_format($sach['so_luong_ton'], 0, ',', '.'); ?> quyển
                                    </span>
                                </div>
                            </div>
                            <div class="btn-group">
                                <a href="gio_hang.php?hanh_dong=them&id=<?php echo $sach['id']; ?>&hinh_thuc=mua" class="btn btn-buy">Mua Sách</a>
                                <a href="gio_hang.php?hanh_dong=them&id=<?php echo $sach['id']; ?>&hinh_thuc=thue" class="btn btn-rent">Thuê sách</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <h2>📖 Danh Mục Toàn Bộ Sách</h2>
        <div class="book-grid">
            <?php if (empty($tat_ca_sach)): ?>
                <p style="grid-column: 1/-1; text-align: center; color: #64748b; padding: 40px; font-weight: 500;">Kho sách chưa có tài liệu tương ứng với từ khóa tìm kiếm của bạn!</p>
            <?php else: ?>
                <?php foreach($tat_ca_sach as $sach): ?>
                    <div class="book-card">
                        <div>
                            <div class="book-cover">
                                <img src="uploads/<?php echo $sach['anh_bia']; ?>" alt="Bìa sách" style="width: 100%; height: 100%; object-fit: cover;">
                            </div>
                            <div class="book-title">
                                <a href="chi_tiet_sach.php?id=<?php echo $sach['id']; ?>">
                                    <?php echo $sach['ten_sach']; ?>
                                </a>
                            </div>
                            <div class="book-author">TG: <?php echo $sach['tac_gia']; ?></div>
                        </div>
                        <div>
                            <div class="price-box">
                                <div class="book-price"><span>Mua Sách:</span> <?php echo number_format($sach['gia_ban'], 0, ',', '.'); ?> đ</div>
                                <div class="book-rent"><span>Thuê:</span> <?php echo number_format($sach['gia_thue_theo_ngay'], 0, ',', '.'); ?> đ/ngày</div>
                                <div style="font-size: 13px; color: #64748b; font-weight: 700; display: flex; justify-content: space-between; margin-top: 4px;">
                                    <span>Còn trong kho:</span> 
                                    <span style="font-weight: 700; color: <?php echo $sach['so_luong_ton'] > 0 ? '#10b981' : '#ef4444'; ?>;">
                                        <?php echo number_format($sach['so_luong_ton'], 0, ',', '.'); ?> quyển
                                    </span>
                                </div>
                            </div>
                            <div class="btn-group">
                                <a href="gio_hang.php?hanh_dong=them&id=<?php echo $sach['id']; ?>&hinh_thuc=mua" class="btn btn-buy">Mua Sách</a>
                                <a href="gio_hang.php?hanh_dong=them&id=<?php echo $sach['id']; ?>&hinh_thuc=thue" class="btn btn-rent">Thuê sách</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <footer style="background-color: #2c3e50; color: #bdc3c7; padding: 40px 20px; margin-top: 5px; font-family: 'Segoe UI', sans-serif; border-top: 4px solid #e67e22;">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; flex-wrap: wrap; gap: 30px; justify-content: space-between;">
            
            <div style="flex: 1; min-width: 280px;">
                <h3 style="color: #ffffff; margin-bottom: 15px; font-size: 18px;">📚 TẠP HÓA SÁCH</h3>
                <p style="font-size: 14px; line-height: 1.6;">Hệ thống cung cấp tri thức, mua bán và cho thuê các đầu sách đa dạng thể loại với chi phí tiết kiệm nhất cho mọi độc giả.</p>
            </div>

            <div style="flex: 1; min-width: 300px;">
                <h3 style="color: #ffffff; margin-bottom: 15px; font-size: 18px;">📍 THÔNG TIN LIÊN HỆ</h3>
                <ul style="list-style: none; padding: 0; font-size: 14px; line-height: 1.8;">
                    <li style="margin-bottom: 8px;">
                        <strong>🏠 Địa chỉ:</strong> 123, đường Nguyễn Hương, tổ 60, khóm Bến Bắc, thành phố Cao Lãnh, tỉnh Đồng Tháp.
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>📞 Số điện thoại:</strong> <a href="tel:0789512345" style="color: #e67e22; text-decoration: none; font-weight: bold;">0789512345</a>
                    </li>
                    <li style="margin-bottom: 8px;">
                        <strong>✉️ Email chủ shop:</strong> <a href="mailto:admin@gmail.com" style="color: #e67e22; text-decoration: none;">admin@gmail.com</a>
                    </li>
                </ul>
            </div>

            <div style="flex: 1; min-width: 200px;">
                <h3 style="color: #ffffff; margin-bottom: 15px; font-size: 18px;">🕒 THỜI GIAN MỞ CỬA</h3>
                <p style="font-size: 14px; line-height: 1.6;">Thứ 2 - Chủ Nhật: 07:00 - 22:00</p>
                <p style="font-size: 13px; color: #7f8c8d; margin-top: 10px;">Hệ thống website hỗ trợ đặt hàng trực tuyến 24/7.</p>
            </div>

        </div>

        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #34495e; font-size: 13px; color: #7f8c8d;">
            © 2026 Tạp Hóa Sách. All rights reserved. Thiết kế hệ thống quản lý kho và thuê sách trực tuyến.
        </div>
    </footer>

</body>
</html> 