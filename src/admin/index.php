<?php
session_start();
// Nhúng file db.php từ thư mục cha bằng cách dùng "../"
require_once '../db.php';

// KHU VỰC BẢO MẬT: Chặn người dùng nếu không phải là Admin
if (!isset($_SESSION['user']) || $_SESSION['user']['vai_tro'] !== 'admin') {
    // Trục xuất về trang đăng nhập ở thư mục gốc ngoài cùng
    header("Location: ../dang_nhap.php");
    exit;
}

// Lấy thông tin admin đang đăng nhập
$admin_name = $_SESSION['user']['ho_ten'];

// TRUY VẤN THỐNG KÊ SỐ LIỆU TRÊN DASHBOARD
// 1. Đếm tổng số sách
$count_sach = $ket_noi->query("SELECT COUNT(*) FROM sach")->fetchColumn();

// 2. Đếm tổng số thể loại
$count_the_loai = $ket_noi->query("SELECT COUNT(*) FROM the_loai")->fetchColumn();

// 3. Đếm tổng số đơn mua sách đang chờ duyệt
$count_don_mua = $ket_noi->query("SELECT COUNT(*) FROM don_hang_mua WHERE trang_thai = 'cho_duyet'")->fetchColumn();

// 4. Đếm tổng số đơn thuê đang cho mượn sách
$count_don_thue = $ket_noi->query("SELECT COUNT(*) FROM don_hang_thue WHERE trang_thai = 'dang_thue'")->fetchColumn();

// Mảng danh sách các câu châm ngôn tuyển chọn về sách
$cham_ngon = [
    ["cau_noi" => "Mỗi cuốn sách là một giấc mơ cầm trong tay.", "tac_gia" => "Jorge Luis Borges", "bg" => "#e0f2fe", "color" => "#0369a1"],
    ["cau_noi" => "Đừng đọc để tin, mà đọc để suy ngẫm, mà đọc để tung hô.", "tac_gia" => "Francis Bacon", "bg" => "#fffbeb", "color" => "#b45309"],
    ["cau_noi" => "Một cuốn sách hay có thể thay đổi số phận của bạn.", "tac_gia" => "Khuyết danh", "bg" => "#fef3c7", "color" => "#92400e"],
    ["cau_noi" => "Sách là người bạn chung thủy nhất.", "tac_gia" => "Mark Twain", "bg" => "#ecfeff", "color" => "#0e7490"],
    ["cau_noi" => "Cách duy nhất để chứa đựng nhiều cuộc đời là đọc sách.", "tac_gia" => "M.Gorky", "bg" => "#f0fdf4", "color" => "#15803d"],
    ["cau_noi" => "Việc đọc hết tất cả các cuốn sách hay cũng như trò chuyện với những bộ óc tuyệt vời nhất.", "tac_gia" => "Rene Descartes", "bg" => "#fdf2f8", "color" => "#be185d"]
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảng Điều Khiển Quản Trị - WebBook Admin</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { display: flex; background-color: #f4f6f9; min-height: 100vh; }
        
        /* 1. Giao diện Thanh bên (Sidebar) độc lập - GIỮ NGUYÊN BẢN */
        .sidebar { width: 260px; background-color: #2c3e50; color: #ecf0f1; display: flex; flex-direction: column; shrink: 0; }
        .sidebar-brand { padding: 25px 20px; text-align: center; font-size: 20px; font-weight: bold; background-color: #1a252f; border-bottom: 1px solid #34495e; }
        .sidebar-menu { list-style: none; padding: 15px 0; flex: 1; }
        .sidebar-menu li a { display: block; padding: 12px 20px; color: #bdc3c7; text-decoration: none; font-weight: 500; transition: 0.2s; }
        .sidebar-menu li a:hover, .sidebar-menu li a.active { background-color: #34495e; color: #fff; border-left: 4px solid #3498db; }
        
        /* 2. Giao diện Khu vực nội dung bên phải (Main Content) */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-x: hidden; }
        
        /* Thanh trên cùng (Topbar) */
        .topbar { background-color: #fff; height: 70px; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .topbar .welcome { font-size: 16px; color: #333; }
        .topbar .btn-logout { background-color: #e74c3c; color: white; padding: 8px 15px; border-radius: 4px; text-decoration: none; font-weight: bold; font-size: 14px; }
        .topbar .btn-logout:hover { background-color: #c0392b; }
        
        /* Nội dung chính bên dưới Dashboard */
        .dashboard-body { padding: 30px; }
        .page-title { color: #2c3e50; margin-bottom: 25px; font-weight: 700; }
        
        /* Cải tiến hộp thống kê to hơn và hỗ trợ Grid linh hoạt */
        .cards-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); 
            gap: 25px; 
            margin-bottom: 30px;
        }
        
        .card { 
            background-color: white; 
            border-radius: 12px; 
            padding: 24px; 
            box-shadow: 0 4px 6px rgba(0,0,0,0.02); 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between; 
            gap: 15px;
            transition: 0.3s; 
            position: relative; 
            overflow: hidden; 
        }
        .card:hover { transform: translateY(-4px); box-shadow: 0 10px 15px rgba(0,0,0,0.06); }
        
        .card-label { font-size: 13px; color: #7f8c8d; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Layout bên trong Card chứa biểu đồ và số liệu */
        .card-body-chart {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        /* Biểu đồ tròn dạng Donut bằng CSS Pure */
        .chart-circle {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        /* Đổ phân vùng màu sắc động mô phỏng biểu đồ */
        .chart-books { background: conic-gradient(#3498db 0% 75%, #eaedf1 75% 100%); }
        .chart-categories { background: conic-gradient(#9b59b6 0% 50%, #eaedf1 50% 100%); }
        .chart-orders { background: conic-gradient(#e67e22 0% 60%, #eaedf1 60% 100%); }
        .chart-rentals { background: conic-gradient(#eaedf1 0% 100%); } /* Mặc định xám nếu dữ liệu = 0 */

        .chart-circle::before {
            content: "";
            position: absolute;
            width: 68px;
            height: 68px;
            background: white;
            border-radius: 50%;
            z-index: 1;
        }
        .chart-number { position: relative; z-index: 2; }

        /* Chú thích giả lập bên cạnh biểu đồ */
        .chart-legends { list-style: none; font-size: 12px; color: #7f8c8d; display: flex; flex-direction: column; gap: 4px; }
        .chart-legends li { display: flex; align-items: center; gap: 6px; }
        .bullet { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }

        /* Màu sắc trang trí viền trên các hộp thống kê */
        .card-books { border-top: 4px solid #3498db; }
        .card-categories { border-top: 4px solid #9b59b6; }
        .card-orders { border-top: 4px solid #e67e22; }
        .card-rentals { border-top: 4px solid #2ecc71; }

        /* Khu vực châm ngôn nghệ thuật dưới góc */
        .quotes-section-title { font-size: 18px; font-weight: 700; color: #2c3e50; margin: 35px 0 15px 0; padding-top: 15px; border-top: 1px dashed #cbd5e1; }
        .quotes-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .quote-card { padding: 22px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01); display: flex; flex-direction: column; justify-content: space-between; gap: 12px; border-left: 5px solid rgba(0,0,0,0.12); transition: 0.2s; }
        .quote-card:hover { transform: translateY(-2px); }
        .quote-text { font-size: 15px; font-weight: 500; line-height: 1.5; font-style: italic; }
        .quote-author { font-size: 12px; font-weight: 600; text-align: right; opacity: 0.8; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand"> ADMIN Tạp Hóa Sách</div>
        <ul class="sidebar-menu">
            <li><a href="index.php" class="active">📊 Tổng quan Dashboard</a></li>
            <li><a href="admin_quan_ly_the_loai.php">📁 Quản lý thể loại</a></li>
            <li><a href="admin_quan_ly_sach.php">📚 Quản lý sách</a></li>
            <li><a href="admin_don_hang_mua.php">🛒 Đơn mua sách</a></li>
            <li><a href="admin_don_hang_thue.php">🔑 Đơn thuê sách</a></li>
            <li><a href="admin_quan_ly_nguoi_dung.php">👤 Quản lý người dùng</a></li>
            <li><a href="admin_khieu_nai.php">⚠️ Quản lý khiếu nại</a></li>
            <li><a href="../index.php" target="_blank">🌐 Xem trang chủ </a></li>
        </ul>
    </div>

    <div class="main-content">
        
        <div class="topbar">
            <div class="welcome">Xin chào chủ shop: <strong><?php echo $admin_name; ?></strong></div>
            <a href="../dang_xuat.php" class="btn-logout">Đăng xuất hệ thống ↩️</a>
        </div>

        <div class="dashboard-body">
            <h2 class="page-title">📊 Hệ thống thống kê tổng quan</h2>
            
            <div class="cards-grid">
                <div class="card card-books">
                    <span class="card-label">Tổng số đầu sách</span>
                    <div class="card-body-chart">
                        <div class="chart-circle chart-books">
                            <span class="chart-number"><?php echo $count_sach; ?></span>
                        </div>
                        <ul class="chart-legends">
                            <li><span class="bullet" style="background:#3498db;"></span> Sách mở rộng</li>
                            <li><span class="bullet" style="background:#5dade2;"></span> Đang lưu kho</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card card-categories">
                    <span class="card-label">Tổng số danh mục</span>
                    <div class="card-body-chart">
                        <div class="chart-circle chart-categories">
                            <span class="chart-number"><?php echo $count_the_loai; ?></span>
                        </div>
                        <ul class="chart-legends">
                            <li><span class="bullet" style="background:#9b59b6;"></span> Thể loại gốc</li>
                            <li><span class="bullet" style="background:#bb8fce;"></span> Nhóm phụ</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card card-orders">
                    <span class="card-label">Đơn mua chờ duyệt</span>
                    <div class="card-body-chart">
                        <div class="chart-circle chart-orders">
                            <span class="chart-number"><?php echo $count_don_mua; ?></span>
                        </div>
                        <ul class="chart-legends">
                            <li><span class="bullet" style="background:#e67e22;"></span> Mới phát sinh</li>
                            <li><span class="bullet" style="background:#f0b27a;"></span> Đang xử lý</li>
                        </ul>
                    </div>
                </div>
                
                <div class="card card-rentals">
                    <span class="card-label">Sách đang cho thuê</span>
                    <div class="card-body-chart">
                        <div class="chart-circle chart-rentals" style="<?php echo ($count_don_thue > 0) ? 'background: conic-gradient(#2ecc71 0% 70%, #eaedf1 70% 100%)' : ''; ?>">
                            <span class="chart-number"><?php echo $count_don_thue; ?></span>
                        </div>
                        <ul class="chart-legends">
                            <li><span class="bullet" style="background:<?php echo ($count_don_thue > 0) ? '#2ecc71' : '#cbd5e1'; ?>;"></span> Đang thuê ngoài</li>
                        </ul>
                    </div>
                </div>
            </div>

            <h3 class="quotes-section-title">✨ Góc cảm hứng & Châm ngôn sách tuyển chọn</h3>
            <div class="quotes-grid">
                <?php foreach($cham_ngon as $qn): ?>
                    <div class="quote-card" style="background-color: <?php echo $qn['bg']; ?>; color: <?php echo $qn['color']; ?>;">
                        <div class="quote-text">“<?php echo $qn['cau_noi']; ?>”</div>
                        <div class="quote-author">— <?php echo $qn['tac_gia']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

        </div>
    </div>

</body>
</html>