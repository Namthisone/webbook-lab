<?php
session_start();
require_once 'db.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['gio_hang'])) {
    $_SESSION['gio_hang'] = [];
}

// XỬ LÝ CÁC HÀNH ĐỘNG TRÊN GIỎ HÀNG (THÊM / XÓA / SỬA SỐ LƯỢNG)
$hanh_dong = isset($_GET['hanh_dong']) ? $_GET['hanh_dong'] : '';

if ($hanh_dong == 'them') {
    $id_sach = intval($_GET['id']);
    $hinh_thuc = ($_GET['hinh_thuc'] == 'thue') ? 'thue' : 'mua';

    // Lấy thông tin sách từ DB để đảm bảo sách tồn tại
    $stmt = $ket_noi->prepare("SELECT * FROM sach WHERE id = ?");
    $stmt->execute([$id_sach]);
    $sach = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sach) {
        // Tạo một mã định danh duy nhất trong giỏ hàng (Ví dụ: "5_mua" hoặc "5_thue")
        $ma_gio_hang = $id_sach . '_' . $hinh_thuc;

        if (isset($_SESSION['gio_hang'][$ma_gio_hang])) {
            $_SESSION['gio_hang'][$ma_gio_hang]['so_luong'] += 1;
        } else {
            $_SESSION['gio_hang'][$ma_gio_hang] = [
                'id' => $sach['id'],
                'ten_sach' => $sach['ten_sach'],
                'gia' => ($hinh_thuc == 'mua') ? $sach['gia_ban'] : $sach['gia_thue_theo_ngay'],
                'hinh_thuc' => $hinh_thuc,
                'so_luong' => 1
            ];
        }
    }
    // Sau khi thêm xong, quay về trang giỏ hàng để xem
    header("Location: gio_hang.php");
    exit;
}

// Xóa 1 sản phẩm cụ thể ra khỏi giỏ
if ($hanh_dong == 'xoa') {
    $ma_xoa = $_GET['ma'];
    if (isset($_SESSION['gio_hang'][$ma_xoa])) {
        unset($_SESSION['gio_hang'][$ma_xoa]);
    }
    header("Location: gio_hang.php");
    exit;
}

// Xóa sạch toàn bộ giỏ hàng
if ($hanh_dong == 'xoa_het') {
    $_SESSION['gio_hang'] = [];
    header("Location: gio_hang.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ Hàng Mua & Thuê Sách - WebBook</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #f5f6f9; color: #333; padding: 20px; }
        .container { max-width: 1000px; margin: 30px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        h2 { color: #2c3e50; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; color: #2c3e50; }
        
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: white; }
        .badge-buy { background-color: #2ecc71; }
        .badge-rent { background-color: #3498db; }
        
        .btn-delete { color: #e74c3c; text-decoration: none; font-weight: bold; }
        .btn-delete:hover { text-decoration: underline; }
        
        .total-section { text-align: right; margin-top: 20px; font-size: 18px; font-weight: bold; color: #2c3e50; }
        .action-buttons { display: flex; justify-content: space-between; margin-top: 30px; }
        
        .btn { padding: 12px 20px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; font-size: 15px; }
        .btn-secondary { background-color: #7f8c8d; color: white; }
        .btn-danger { background-color: #e74c3c; color: white; }
        .btn-success { background-color: #2ecc71; color: white; }
    </style>
</head>
<body>

<div class="container">
    <h2>🛒 Giỏ Hàng Của Bạn</h2>
    <a href="index.php" style="text-decoration: none; color: #3498db; font-weight: bold; display: inline-block; margin-bottom: 15px;">⬅️ Tiếp tục chọn sách</a>

    <?php if (empty($_SESSION['gio_hang'])): ?>
        <p style="text-align: center; color: #7f8c8d; padding: 40px 0; font-size: 16px;">Giỏ hàng của bạn đang trống trơn!</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Tên Sách</th>
                    <th>Hình Thức</th>
                    <th>Giá (Đơn vị)</th>
                    <th>Số Lượng</th>
                    <th>Thành Tiền</th>
                    <th>Thao Tác</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $tong_tien_tinh_tam = 0;
                foreach ($_SESSION['gio_hang'] as $ma_gio => $sp): 
                    $thanh_tien = $sp['gia'] * $sp['so_luong'];
                    $tong_tien_tinh_tam += $thanh_tien;
                ?>
                <tr>
                    <td><strong><?php echo $sp['ten_sach']; ?></strong></td>
                    <td>
                        <?php if($sp['hinh_thuc'] == 'mua'): ?>
                            <span class="badge badge-buy">MUA Ngay</span>
                        <?php else: ?>
                            <span class="badge badge-rent">THUÊ Ngay</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo number_format($sp['gia'], 0, ',', '.'); ?> đ<?php echo ($sp['hinh_thuc'] == 'thue') ? '/ngày' : ''; ?></td>
                    <td><?php echo $sp['so_luong']; ?></td>
                    <td style="font-weight: bold; color: #2c3e50;"><?php echo number_format($thanh_tien, 0, ',', '.'); ?> đ</td>
                    <td>
                        <a href="gio_hang.php?hanh_dong=xoa&ma=<?php echo $ma_gio; ?>" class="btn-delete">Xóa</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="total-section">
            Tạm tính tổng tiền: <span style="color: #e74c3c; font-size: 22px;"><?php echo number_format($tong_tien_tinh_tam, 0, ',', '.'); ?> đ</span>
            <p style="font-size: 13px; color: #7f8c8d; font-weight: normal; margin-top: 5px;">*(Lưu ý: Tiền thuê thực tế sẽ nhân theo số ngày thuê khi bạn chọn ở bước thanh toán tiếp theo)</p>
        </div>

        <div class="action-buttons">
            <a href="gio_hang.php?hanh_dong=xoa_het" class="btn btn-danger">🗑️ Xóa sạch giỏ hàng</a>
            <a href="thanh_toan.php" class="btn btn-success">💳 Tiến hành đặt hàng & Thuê sách</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>