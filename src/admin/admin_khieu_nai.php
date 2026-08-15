<?php
session_start();
require_once '../db.php'; // Kết nối cơ sở dữ liệu

$thong_bao = "";

// 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI & PHẢN HỒI KHIẾU NẠI
if (isset($_POST['cap_nhat_khieu_nai'])) {
    $id_kn = intval($_POST['id_khieu_nai']);
    $trang_thai_moi = $_POST['trang_thai'];
    $phan_hoi = trim($_POST['phan_hoi_admin']);

    try {
        $stmt = $ket_noi->prepare("UPDATE khieu_nai SET trang_thai = ?, phan_hoi_admin = ? WHERE id = ?");
        $stmt->execute([$trang_thai_moi, $phan_hoi, $id_kn]);
        $thong_bao = "<div class='alert alert-success'>✅ Đã cập nhật phản hồi cho khiếu nại #$id_kn thành công!</div>";
    } catch (Exception $e) {
        $thong_bao = "<div class='alert alert-danger'>❌ Lỗi: " . $e->getMessage() . "</div>";
    }
}

// 2. LẤY DANH SÁCH TẤT CẢ KHIẾU NẠI
$sql = "SELECT kn.*, nd.ho_ten, nd.so_dien_thoai 
        FROM khieu_nai kn
        LEFT JOIN nguoi_dung nd ON kn.id_nguoi_dung = nd.id
        ORDER BY kn.ngay_tao DESC";
$stmt_kn = $ket_noi->query($sql);
$danh_sach_kn = $stmt_kn->fetchAll(PDO::FETCH_ASSOC);

// 3. XEM CHI TIẾT MỘT KHIẾU NẠI
$xem_id = isset($_GET['xem']) ? intval($_GET['xem']) : 0;
$kn_hien_tai = null;
if ($xem_id > 0) {
    $stmt_detail = $ket_noi->prepare("SELECT kn.*, nd.ho_ten, nd.so_dien_thoai, nd.email 
                                      FROM khieu_nai kn 
                                      LEFT JOIN nguoi_dung nd ON kn.id_nguoi_dung = nd.id 
                                      WHERE kn.id = ?");
    $stmt_detail->execute([$xem_id]);
    $kn_hien_tai = $stmt_detail->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin - Quản Lý Khiếu Nại</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; min-height: 100vh; }
        
        .sidebar { width: 250px; background-color: #2c3e50; color: white; padding: 20px; }
        .sidebar h3 { text-align: center; margin-bottom: 30px; color: #e67e22; border-bottom: 1px solid #34495e; padding-bottom: 10px; }
        .sidebar a { display: block; color: #bdc3c7; padding: 12px; text-decoration: none; border-radius: 4px; margin-bottom: 8px; font-weight: bold;}
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }

        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        h2 { color: #333; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;}
        
        .table-container { background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background-color: #f8f9fa; color: #4a5568; font-weight: bold; }
        
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-cho_giai_quyet { background-color: #f1c40f; color: #7f6000; }
        .badge-dang_xu_ly { background-color: #3498db; color: white; }
        .badge-da_giai_quyet { background-color: #2ecc71; color: white; }
        
        .btn { padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: bold; }
        .btn-info { background-color: #2980b9; color: white; }
        
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;}
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        .detail-box { background: #fff; border: 2px solid #e67e22; border-radius: 8px; padding: 25px; margin-top: 20px; }
        .detail-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        
        .form-update { display: flex; flex-direction: column; gap: 15px; margin-top: 15px; background: #f8f9fa; padding: 20px; border-radius: 6px;}
        select, textarea { padding: 10px; border-radius: 4px; border: 1px solid #cbd5e0; font-size: 14px; width: 100%;}
        .btn-submit { background-color: #e67e22; color: white; padding: 10px 20px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold; align-self: flex-start;}
        .btn-submit:hover { background-color: #d35400; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>📚 ADMIN Tạp Hóa Sách</h3>
    <a href="admin_don_hang_mua.php">📦 Quản lý Đơn Mua</a>
    <a href="admin_don_hang_thue.php">📋 Quản lý Đơn Thuê</a>
    <a href="admin_khieu_nai.php" class="active">⚠️ Quản lý Khiếu Nại</a>
    <a href="../admin/index.php">🏠 Về Dashboard</a>
</div>

<div class="main-content">
    <h2>⚠️ Danh Sách Yêu Cầu Khiếu Nại & Hỗ Trợ 
        <span style="font-size: 14px; color: #7f8c8d; font-weight: normal;">Tổng số: <?php echo count($danh_sach_kn); ?> yêu cầu</span>
    </h2>

    <?php echo $thong_bao; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mã KN</th>
                    <th>Khách Hàng</th>
                    <th>SĐT</th>
                    <th>Loại Đơn</th>
                    <th>Mã Đơn</th>
                    <th>Tiêu Đề</th>
                    <th>Ngày Gửi</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($danh_sach_kn) == 0): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: #7f8c8d;">Hiện tại chưa có khiếu nại nào từ khách hàng.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danh_sach_kn as $kn): ?>
                        <tr>
                            <td><strong>#<?php echo $kn['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($kn['ho_ten']); ?></td>
                            <td><?php echo htmlspecialchars($kn['so_dien_thoai']); ?></td>
                            <td><strong><?php echo ($kn['loai_don'] == 'mua') ? '📦 Mua Sách' : '📋 Thuê Sách'; ?></strong></td>
                            <td>#<?php echo $kn['id_don_hang']; ?></td>
                            <td style="max-width: 180px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($kn['tieu_de']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($kn['ngay_tao'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $kn['trang_thai']; ?>">
                                    <?php 
                                        if($kn['trang_thai'] == 'cho_giai_quyet') echo '⏳ Chờ duyệt';
                                        elseif($kn['trang_thai'] == 'dang_xu_ly') echo '🔵 Đang xử lý';
                                        elseif($kn['trang_thai'] == 'da_giai_quyet') echo '🟢 Đã giải quyết';
                                    ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin_khieu_nai.php?xem=<?php echo $kn['id']; ?>#chi-tiet-kn" class="btn btn-info">🔍 Xử Lý</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($xem_id > 0 && $kn_hien_tai): ?>
        <div class="detail-box" id="chi-tiet-kn">
            <div class="detail-header">
                <h3 style="color: #e67e22;">🔍 Chi Tiết Khiếu Nại #<?php echo $kn_hien_tai['id']; ?></h3>
                <a href="admin_khieu_nai.php" style="color: #e74c3c; text-decoration: none; font-weight: bold;">❌ Đóng</a>
            </div>
            
            <div style="background: #fdfefe; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0; margin-bottom: 20px; font-size: 14px; line-height: 1.8;">
                👤 <strong>Khách hàng:</strong> <?php echo htmlspecialchars($kn_hien_tai['ho_ten']); ?> | 📞 <?php echo htmlspecialchars($kn_hien_tai['so_dien_thoai']); ?> | 📧 <?php echo htmlspecialchars($kn_hien_tai['email']); ?><br>
                📦 <strong>Đơn hàng liên quan:</strong> Đơn <?php echo ($kn_hien_tai['loai_don'] == 'mua') ? 'Mua' : 'Thuê'; ?> <strong>#<?php echo $kn_hien_tai['id_don_hang']; ?></strong><br>
                📌 <strong>Tiêu đề sự cố:</strong> <span style="color:#d35400; font-weight:bold;"><?php echo htmlspecialchars($kn_hien_tai['tieu_de']); ?></span><br>
                💬 <strong>Nội dung khách viết:</strong> 
                <p style="background: #fff; padding: 10px; border: 1px dashed #cbd5e0; margin-top: 5px; border-radius: 4px; color: #2c3e50;">
                    <?php echo nl2br(htmlspecialchars($kn_hien_tai['noi_dung'])); ?>
                </p>
            </div>

            <form action="admin_khieu_nai.php" method="POST" class="form-update">
                <input type="hidden" name="id_khieu_nai" value="<?php echo $kn_hien_tai['id']; ?>">
                
                <div>
                    <label style="font-weight: bold; color:#2c3e50; display:block; margin-bottom:8px;">⚙️ Đổi trạng thái xử lý:</label>
                    <select name="trang_thai" style="width: 300px;">
                        <option value="cho_giai_quyet" <?php if($kn_hien_tai['trang_thai'] == 'cho_giai_quyet') echo 'selected'; ?>>⏳ Chờ giải quyết</option>
                        <option value="dang_xu_ly" <?php if($kn_hien_tai['trang_thai'] == 'dang_xu_ly') echo 'selected'; ?>>🔵 Đang trong tiến trình xử lý</option>
                        <option value="da_giai_quyet" <?php if($kn_hien_tai['trang_thai'] == 'da_giai_quyet') echo 'selected'; ?>>🟢 Đã giải quyết xong</option>
                    </select>
                </div>

                <div>
                    <label style="font-weight: bold; color:#2c3e50; display:block; margin-bottom:8px;">🛡️ Nội dung phản hồi / Phương án đền bù gửi tới khách:</label>
                    <textarea name="phan_hoi_admin" rows="4" placeholder="Nhập câu trả lời hoặc hướng giải quyết tại đây (Ví dụ: Đã hoàn lại tiền cọc, ship lại sách mới...)" required><?php echo htmlspecialchars($kn_hien_tai['phan_hoi_admin'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="cap_nhat_khieu_nai" class="btn-submit">💾 Lưu & Gửi Phản Hồi</button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>