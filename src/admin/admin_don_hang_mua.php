<?php
session_start();
// DÒNG MỚI ĐÃ SỬA:
require_once '../db.php'; // Đảm bảo đường dẫn tới file kết nối database của bạn chính xác

// 1. XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG (Nếu có yêu cầu đổi trạng thái)
$thong_bao = "";
if (isset($_POST['cap_nhat_trang_thai'])) {
    $id_don = intval($_POST['id_don_hang']);
    $trang_thai_moi = $_POST['trang_thai'];
    
    try {
        $stmt = $ket_noi->prepare("UPDATE don_hang_mua SET trang_thai = ? WHERE id = ?");
        $stmt->execute([$trang_thai_moi, $id_don]);
        $thong_bao = "<div class='alert alert-success'>✅ Đã cập nhật trạng thái đơn hàng #" . $id_don . " thành công!</div>";
    } catch (Exception $e) {
        $thong_bao = "<div class='alert alert-danger'>❌ Lỗi cập nhật: " . $e->getMessage() . "</div>";
    }
}

// 2. LẤY DANH SÁCH ĐƠN HÀNG MUA
$sql = "SELECT * FROM don_hang_mua ORDER BY ngay_dat_hang DESC";
$stmt_don = $ket_noi->query($sql);
$danh_sach_don = $stmt_don->fetchAll(PDO::FETCH_ASSOC);

// 3. XỬ LÝ LẤY CHI TIẾT ĐƠN HÀNG KHI BAM "XEM CHI TIẾT" (Dùng Ajax hoặc hiển thị trực tiếp)
$chi_tiet_don_id = isset($_GET['xem_chi_tiet']) ? intval($_GET['xem_chi_tiet']) : 0;
$sach_trong_don = [];
$thong_tin_don_hien_tai = null;

if ($chi_tiet_don_id > 0) {
    // Lấy thông tin đơn hàng hiện tại
    $stmt_check = $ket_noi->prepare("SELECT * FROM don_hang_mua WHERE id = ?");
    $stmt_check->execute([$chi_tiet_don_id]);
    $thong_tin_don_hien_tai = $stmt_check->fetch(PDO::FETCH_ASSOC);

    if ($thong_tin_don_hien_tai) {
        // Lấy danh sách sách trong đơn hàng (Khớp cột id_don_hang và gia_luc_mua)
        // Lưu ý: Tôi giả định bảng sách của bạn tên là 'sach' và có trường 'ten_sach'. Nếu tên khác hãy sửa lại chuỗi SQL này.
        $sql_ct = "SELECT ctm.*, s.ten_sach 
                   FROM chi_tiet_mua ctm 
                   LEFT JOIN sach s ON ctm.id_sach = s.id 
                   WHERE ctm.id_don_hang = ?";
        $stmt_ct = $ket_noi->prepare($sql_ct);
        $stmt_ct->execute([$chi_tiet_don_id]);
        $sach_trong_don = $stmt_ct->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin - Quản Lý Đơn Hàng Mua</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; min-height: 100vh; }
        
        /* Giao diện thanh bên sơ bộ */
        .sidebar { width: 250px; background-color: #2c3e50; color: white; padding: 20px; }
        .sidebar h3 { text-align: center; margin-bottom: 30px; color: #e67e22; border-bottom: 1px solid #34495e; padding-bottom: 10px; }
        .sidebar a { display: block; color: #bdc3c7; padding: 12px; text-decoration: none; border-radius: 4px; margin-bottom: 8px; font-weight: bold;}
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }

        /* Vùng nội dung chính */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        h2 { color: #333; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;}
        
        .table-container { background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); padding: 20px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background-color: #f8f9fa; color: #4a5568; font-weight: bold; }
        
        /* Badge trạng thái */
        .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; text-align: center; display: inline-block; }
        .badge-cho_duyet { background-color: #f1c40f; color: #7f6000; }
        .badge-da_giao { background-color: #2ecc71; color: white; }
        .badge-da_huy { background-color: #e74c3c; color: white; }
        
        .btn { padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: bold; }
        .btn-info { background-color: #3498db; color: white; }
        .btn-info:hover { background-color: #2980b9; }
        
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-weight: bold;}
        .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* Vùng chi tiết đơn hàng (Modal giả lập bên dưới danh sách) */
        .detail-box { background: #fff; border: 2px solid #3498db; border-radius: 8px; padding: 25px; margin-top: 20px; }
        .detail-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        
        .form-update { display: flex; gap: 10px; align-items: center; margin-top: 15px; background: #f8f9fa; padding: 15px; border-radius: 6px;}
        select { padding: 8px; border-radius: 4px; border: 1px solid #cbd5e0; font-size: 14px;}
        .btn-submit { background-color: #e67e22; color: white; padding: 8px 15px; border-radius: 4px; border: none; cursor: pointer; font-weight: bold;}
        .btn-submit:hover { background-color: #d35400; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>📚 ADMIN Tạp Hóa Sách</h3>
    <a href="#" class="active">📦 Quản lý Đơn Mua</a>
    <a href="admin_don_hang_thue.php">📋 Quản lý Đơn Thuê</a>
    <a href="index.php">🏠 Về Trang Chủ User</a>
</div>

<div class="main-content">
    <h2>📦 Danh Sách Quản Lý Đơn Hàng Mua 
        <span style="font-size: 14px; color: #7f8c8d; font-weight: normal;">Tổng cộng: <?php echo count($danh_sach_don); ?> đơn hàng</span>
    </h2>

    <?php echo $thong_bao; ?>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Mã Đơn</th>
                    <th>Tên Khách Hàng</th>
                    <th>Số Điện Thoại</th>
                    <th>Tổng Tiền</th>
                    <th>Ngày Đặt</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($danh_sach_don) == 0): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #7f8c8d;">Chưa có đơn hàng mua nào trong hệ thống.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danh_sach_don as $don): ?>
                        <tr>
                            <td><strong>#<?php echo $don['id']; ?></strong></td>
                            <td><?php echo htmlspecialchars($don['ten_khach_hang']); ?></td>
                            <td><?php echo htmlspecialchars($don['so_dien_thoai']); ?></td>
                            <td style="color: #e74c3c; font-weight: bold;"><?php echo number_format($don['tong_tien'], 0, ',', '.'); ?> đ</td>
                            <td><?php echo date('d/m/Y H:i', strtotime($don['ngay_dat_hang'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $don['trang_thai']; ?>">
                                    <?php 
                                        if($don['trang_thai'] == 'cho_duyet') echo 'Chờ Giao';
                                        elseif($don['trang_thai'] == 'da_giao') echo 'Đã giao hàng';
                                        elseif($don['trang_thai'] == 'da_huy') echo 'Đã hủy';
                                        else echo $don['trang_thai'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <a href="admin_don_hang_mua.php?xem_chi_tiet=<?php echo $don['id']; ?>#chi-tiet" class="btn btn-info">🔍 Xem Chi Tiết</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($chi_tiet_don_id > 0 && $thong_tin_don_hien_tai): ?>
        <div class="detail-box" id="chi-tiet">
            <div class="detail-header">
                <h3 style="color: #2980b9;">🔍 Chi Tiết Đơn Hàng Mua #<?php echo $thong_tin_don_hien_tai['id']; ?></h3>
                <a href="admin_don_hang_mua.php" style="color: #e74c3c; text-decoration: none; font-weight: bold;">❌ Đóng xem chi tiết</a>
            </div>
            
            <div style="display: flex; gap: 40px; margin-bottom: 20px; font-size: 14px; line-height: 1.8; background: #fdfefe; padding: 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <div>
                    👤 <strong>Khách hàng:</strong> <?php echo htmlspecialchars($thong_tin_don_hien_tai['ten_khach_hang']); ?><br>
                    📞 <strong>Số điện thoại:</strong> <?php echo htmlspecialchars($thong_tin_don_hien_tai['so_dien_thoai']); ?><br>
                    📧 <strong>Email:</strong> <?php echo htmlspecialchars($thong_tin_don_hien_tai['email_khach_hang']); ?>
                </div>
                <div>
                    📍 <strong>Địa chỉ giao hàng:</strong> <?php echo htmlspecialchars($thong_tin_don_hien_tai['dia_chi_giao_hang']); ?><br>
                    📅 <strong>Ngày đặt hàng:</strong> <?php echo date('d/m/Y H:i:s', strtotime($thong_tin_don_hien_tai['ngay_dat_hang'])); ?><br>
                    💰 <strong>Tổng giá trị:</strong> <span style="color: #e74c3c; font-weight: bold;"><?php echo number_format($thong_tin_don_hien_tai['tong_tien'], 0, ',', '.'); ?> đ</span>
                </div>
            </div>

            <h4 style="margin-bottom: 10px; color:#4a5568;">📚 Danh sách sách đã mua:</h4>
            <table style="margin-bottom: 20px;">
                <thead>
                    <tr style="background-color: #edf2f7;">
                        <th>ID Sách</th>
                        <th>Tên Đầu Sách</th>
                        <th>Giá Lúc Mua</th>
                        <th>Số Lượng mua</th>
                        <th>Thành Tiền</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sach_trong_don as $sach): ?>
                        <tr>
                            <td>#<?php echo $sach['id_sach']; ?></td>
                            <td><strong><?php echo htmlspecialchars($sach['ten_sach'] ?? 'Sách đã bị xóa hoặc ẩn'); ?></strong></td>
                            <td><?php echo number_format($sach['gia_luc_mua'], 0, ',', '.'); ?> đ</td>
                            <td>x <?php echo $sach['so_luong']; ?></td>
                            <td style="font-weight: bold; color: #2c3e50;"><?php echo number_format($sach['gia_luc_mua'] * $sach['so_luong'], 0, ',', '.'); ?> đ</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form action="admin_don_hang_mua.php" method="POST" class="form-update">
                <input type="hidden" name="id_don_hang" value="<?php echo $thong_tin_don_hien_tai['id']; ?>">
                <label style="font-weight: bold; color:#2c3e50;">⚙️ Thay đổi trạng thái đơn:</label>
                <select name="trang_thai">
                    <option value="cho_duyet" <?php if($thong_tin_don_hien_tai['trang_thai'] == 'cho_duyet') echo 'selected'; ?>>🟡 Chờ duyệt đơn</option>
                    <option value="da_giao" <?php if($thong_tin_don_hien_tai['trang_thai'] == 'da_giao') echo 'selected'; ?>>🟢 Đã giao hàng thành công</option>
                    <option value="da_huy" <?php if($thong_tin_don_hien_tai['trang_thai'] == 'da_huy') echo 'selected'; ?>>🔴 Hủy đơn hàng</option>
                </select>
                <button type="submit" name="cap_nhat_trang_thai" class="btn-submit">💾 Cập Nhật</button>
            </form>
        </div>
    <?php endif; ?>
</div>

</body>
</html>