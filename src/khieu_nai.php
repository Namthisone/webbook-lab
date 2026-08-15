<?php
require_once 'db.php';
session_start();

// Kiểm tra đăng nhập công khai
if (!isset($_SESSION['user'])) {
    header("Location: dang_nhap.php");
    exit;
}

$id_nguoi_dung = $_SESSION['user']['id'];
$thong_bao = "";

// 1. Nhận thông tin tự động điền từ tiến độ đơn hàng gửi sang (nếu có)
$loai_don_mac_dinh = isset($_GET['loai']) ? trim($_GET['loai']) : 'mua';
$id_don_mac_dinh = isset($_GET['id']) ? intval($_GET['id']) : '';

// 2. Xử lý khi người dùng nhấn Gửi khiếu nại
if (isset($_POST['gui_khieu_nai'])) {
    $loai_don = $_POST['loai_don'];
    $id_don_hang = intval($_POST['id_don_hang']);
    $tieu_de = trim($_POST['tieu_de']);
    $noi_dung = trim($_POST['noi_dung']);

    if (empty($tieu_de) || empty($noi_dung) || $id_don_hang <= 0) {
        $thong_bao = "<div class='alert alert-danger'>❌ Vui lòng điền đầy đủ các thông tin bắt buộc!</div>";
    } else {
        // Kiểm tra xem đơn hàng đó có thật sự là của người dùng này không để tránh hack form
        $table_check = ($loai_don == 'mua') ? 'don_hang_mua' : 'don_hang_thue';
        $stmt_check = $ket_noi->prepare("SELECT id FROM $table_check WHERE id = ? AND id_nguoi_dung = ?");
        $stmt_check->execute([$id_don_hang, $id_nguoi_dung]);
        
        if ($stmt_check->rowCount() == 0) {
            $thong_bao = "<div class='alert alert-danger'>❌ Không tìm thấy đơn hàng #$id_don_hang hợp lệ của bạn trên hệ thống!</div>";
        } else {
            // Hợp lệ -> Tiến hành lưu vào database
            try {
                $stmt_insert = $ket_noi->prepare("INSERT INTO khieu_nai (id_nguoi_dung, loai_don, id_don_hang, tieu_de, noi_dung) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert->execute([$id_nguoi_dung, $loai_don, $id_don_hang, $tieu_de, $noi_dung]);
                $thong_bao = "<div class='alert alert-success'>✅ Gửi khiếu nại thành công! Ban quản trị sẽ sớm liên hệ giải quyết.</div>";
                // Xóa dữ liệu cũ sau khi gửi thành công
                $id_don_mac_dinh = '';
            } catch (Exception $e) {
                $thong_bao = "<div class='alert alert-danger'>❌ Lỗi hệ thống: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// 3. Lấy lịch sử các khiếu nại đã gửi của User này
$stmt_list = $ket_noi->prepare("SELECT * FROM khieu_nai WHERE id_nguoi_dung = ? ORDER BY ngay_tao DESC");
$stmt_list->execute([$id_nguoi_dung]);
$danh_sach_khieu_nai = $stmt_list->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trung Tâm Khiếu Nại & Hỗ Trợ Đơn Hàng</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #f8fafc; color: #1e293b; padding: 40px 15px; }
        .container { max-width: 900px; margin: 0 auto; display: grid; grid-template-columns: 1fr; gap: 30px; }
        
        @media(min-width: 768px) {
            .container { grid-template-columns: 350px 1fr; }
        }

        .header-box { grid-column: 1 / -1; display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px 25px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .btn-back { color: #6366f1; text-decoration: none; font-weight: 600; font-size: 14px; }
        
        /* Form gửi khiếu nại */
        .card-form { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); height: fit-content;}
        .card-title { font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;}
        
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        
        .btn-submit { background: #6366f1; color: white; width: 100%; padding: 12px; border: none; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 10px; transition: background 0.2s; }
        .btn-submit:hover { background: #4f46e5; }

        /* Danh sách lịch sử khiếu nại */
        .card-history { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .ticket-item { border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 15px; background: #fff; }
        .ticket-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
        .ticket-title { font-weight: 600; color: #1e293b; font-size: 15px; }
        
        /* Badge trạng thái */
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-cho_giai_quyet { background: #fef3c7; color: #d97706; }
        .badge-dang_xu_ly { background: #e0f2fe; color: #0369a1; }
        .badge-da_giai_quyet { background: #d1fae5; color: #047857; }

        .ticket-meta { font-size: 12px; color: #64748b; margin-bottom: 8px; }
        .ticket-body { font-size: 13.5px; color: #334155; line-height: 1.5; background: #f8fafc; padding: 10px; border-radius: 6px; }
        
        .admin-reply { margin-top: 10px; padding: 10px; background: #f5f3ff; border-left: 3px solid #8b5cf6; border-radius: 0 6px 6px 0; font-size: 13px; }
        
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 15px; font-size: 13px; font-weight: 500; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-danger { background: #fee2e2; color: #991b1b; }
        .no-data { text-align: center; color: #94a3b8; padding: 40px 0; font-size: 14px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header-box">
        <div>
            <h2 style="font-size: 20px; font-weight: 700;">🤝 Trung Tâm Hỗ Trợ & Khiếu Nại</h2>
            <p style="font-size: 13px; color: #64748b; margin-top: 2px;">Gửi phản hồi nếu đơn hàng của bạn gặp sự cố</p>
        </div>
        <a href="index.php" class="btn-back">⬅️ Quay lại trang chủ</a>
    </div>

    <div class="card-form">
        <div class="card-title">✍️ Tạo yêu cầu khiếu nại</div>
        
        <?php echo $thong_bao; ?>

        <form action="khieu_nai.php" method="POST">
            <div class="form-group">
                <label>Phân loại đơn hàng</label>
                <select name="loai_don" class="form-control">
                    <option value="mua" <?php if($loai_don_mac_dinh == 'mua') echo 'selected'; ?>>📦 Đơn đặt mua sách</option>
                    <option value="thue" <?php if($loai_don_mac_dinh == 'thue') echo 'selected'; ?>>📋 Đơn thuê mượn sách</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Mã số đơn hàng (Mã ID số)</label>
                <input type="number" name="id_don_hang" class="form-control" placeholder="Ví dụ: 12" value="<?php echo htmlspecialchars($id_don_mac_dinh); ?>" required>
            </div>

            <div class="form-group">
                <label>Tiêu đề khiếu nại</label>
                <input type="text" name="tieu_de" class="form-control" placeholder="Sách bị rách, sai số lượng..." required>
            </div>

            <div class="form-group">
                <label>Mô tả chi tiết sự cố</label>
                <textarea name="noi_dung" class="form-control" rows="5" placeholder="Vui lòng mô tả rõ tình trạng đơn hàng để admin xử lý nhanh nhất..." required></textarea>
            </div>

            <button type="submit" name="gui_khieu_nai" class="btn-submit">🚀 Gửi Yêu Cầu Hỗ Trợ</button>
        </form>
    </div>

    <div class="card-history">
        <div class="card-title">🕒 Lịch sử hỗ trợ của bạn</div>
        
        <?php if (empty($danh_sach_khieu_nai)): ?>
            <div class="no-data">Bạn chưa có khiếu nại nào được ghi nhận.</div>
        <?php else: ?>
            <?php foreach ($danh_sach_khieu_nai as $kn): ?>
                <div class="ticket-item">
                    <div class="ticket-header">
                        <div class="ticket-title"><?php echo htmlspecialchars($kn['tieu_de']); ?></div>
                        <div>
                            <?php 
                            if ($kn['trang_thai'] == 'dang_xu_ly') {
                                echo '<span class="badge badge-dang_xu_ly">🔵 Đang xử lý</span>';
                            } elseif ($kn['trang_thai'] == 'da_giai_quyet') {
                                echo '<span class="badge badge-da_giai_quyet">🟢 Đã giải quyết</span>';
                            } else {
                                echo '<span class="badge badge-cho_giai_quyet">⏳ Chờ duyệt</span>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="ticket-meta">
                        Phân loại: <strong>Đơn <?php echo ($kn['loai_don'] == 'mua') ? 'Mua' : 'Thuê'; ?> #<?php echo $kn['id_don_hang']; ?></strong> 
                        | Gửi lúc: <?php echo date('d/m/Y H:i', strtotime($kn['ngay_tao'])); ?>
                    </div>
                    
                    <div class="ticket-body">
                        <?php echo nl2br(htmlspecialchars($kn['noi_dung'])); ?>
                    </div>

                    <?php if (!empty($kn['phan_hoi_admin'])): ?>
                        <div class="admin-reply">
                            🛡️ <strong>Ban Quản Trị phản hồi:</strong><br>
                            <span style="color:#5b21b6;"><?php echo nl2br(htmlspecialchars($kn['phan_hoi_admin'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</body>
</html>