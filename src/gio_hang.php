<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['gio_hang']) || !is_array($_SESSION['gio_hang'])) {
    $_SESSION['gio_hang'] = [];
}

if (!isset($_SESSION['cart_csrf'])) {
    $_SESSION['cart_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['cart_csrf'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['cart_csrf'], $token)) {
        http_response_code(403);
        exit('Yêu cầu không hợp lệ.');
    }

    $hanh_dong = $_POST['hanh_dong'] ?? '';

    if ($hanh_dong === 'them') {
        $id_sach = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $hinh_thuc = ($_POST['hinh_thuc'] ?? '') === 'thue' ? 'thue' : 'mua';

        if (!$id_sach) {
            http_response_code(400);
            exit('ID sách không hợp lệ.');
        }

        $stmt = $ket_noi->prepare('SELECT id, ten_sach, gia_ban, gia_thue_theo_ngay FROM sach WHERE id = ? LIMIT 1');
        $stmt->execute([$id_sach]);
        $sach = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($sach) {
            $ma_gio_hang = $id_sach . '_' . $hinh_thuc;
            if (isset($_SESSION['gio_hang'][$ma_gio_hang])) {
                $_SESSION['gio_hang'][$ma_gio_hang]['so_luong'] = min(99, (int)$_SESSION['gio_hang'][$ma_gio_hang]['so_luong'] + 1);
            } else {
                $_SESSION['gio_hang'][$ma_gio_hang] = [
                    'id' => (int)$sach['id'],
                    'ten_sach' => $sach['ten_sach'],
                    'gia' => (float)($hinh_thuc === 'mua' ? $sach['gia_ban'] : $sach['gia_thue_theo_ngay']),
                    'hinh_thuc' => $hinh_thuc,
                    'so_luong' => 1
                ];
            }
        }
    } elseif ($hanh_dong === 'xoa') {
        $ma_xoa = (string)($_POST['ma'] ?? '');
        if ($ma_xoa !== '' && isset($_SESSION['gio_hang'][$ma_xoa])) {
            unset($_SESSION['gio_hang'][$ma_xoa]);
        }
    } elseif ($hanh_dong === 'xoa_het') {
        $_SESSION['gio_hang'] = [];
    }

    header('Location: gio_hang.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giỏ Hàng Mua & Thuê Sách - WebBook</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Segoe UI',Arial,sans-serif}body{background:#f5f6f9;color:#333;padding:20px}.container{max-width:1000px;margin:30px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.05)}h2{color:#2c3e50;margin-bottom:20px;border-bottom:2px solid #eee;padding-bottom:10px}table{width:100%;border-collapse:collapse;margin-bottom:20px}th,td{padding:12px;text-align:left;border-bottom:1px solid #ddd}th{background:#f8f9fa;color:#2c3e50}.badge{padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;color:#fff}.badge-buy{background:#2ecc71}.badge-rent{background:#3498db}.btn{padding:12px 20px;border:0;border-radius:4px;font-weight:bold;cursor:pointer;text-decoration:none;text-align:center;font-size:15px}.btn-danger{background:#e74c3c;color:#fff}.btn-success{background:#2ecc71;color:#fff}.action-buttons{display:flex;justify-content:space-between;gap:15px;margin-top:30px}.total-section{text-align:right;margin-top:20px;font-size:18px;font-weight:bold;color:#2c3e50}
</style>
</head>
<body>
<div class="container">
<h2>🛒 Giỏ Hàng Của Bạn</h2>
<a href="index.php" style="text-decoration:none;color:#3498db;font-weight:bold;display:inline-block;margin-bottom:15px">⬅️ Tiếp tục chọn sách</a>
<?php if (empty($_SESSION['gio_hang'])): ?>
<p style="text-align:center;color:#7f8c8d;padding:40px 0;font-size:16px">Giỏ hàng của bạn đang trống trơn!</p>
<?php else: ?>
<table><thead><tr><th>Tên Sách</th><th>Hình Thức</th><th>Giá</th><th>Số Lượng</th><th>Thành Tiền</th><th>Thao Tác</th></tr></thead><tbody>
<?php $tong_tien_tinh_tam = 0; foreach ($_SESSION['gio_hang'] as $ma_gio => $sp): $gia=(float)($sp['gia']??0); $so_luong=max(1,min(99,(int)($sp['so_luong']??1))); $thanh_tien=$gia*$so_luong; $tong_tien_tinh_tam+=$thanh_tien; ?>
<tr>
<td><strong><?= htmlspecialchars((string)$sp['ten_sach'], ENT_QUOTES, 'UTF-8') ?></strong></td>
<td><?= ($sp['hinh_thuc']??'mua')==='mua' ? '<span class="badge badge-buy">MUA</span>' : '<span class="badge badge-rent">THUÊ</span>' ?></td>
<td><?= number_format($gia,0,',','.') ?> đ<?= ($sp['hinh_thuc']??'')==='thue' ? '/ngày' : '' ?></td>
<td><?= $so_luong ?></td>
<td style="font-weight:bold;color:#2c3e50"><?= number_format($thanh_tien,0,',','.') ?> đ</td>
<td><form method="post" style="display:inline"><input type="hidden" name="cart_csrf" value="<?= htmlspecialchars($_SESSION['cart_csrf'],ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="hanh_dong" value="xoa"><input type="hidden" name="ma" value="<?= htmlspecialchars((string)$ma_gio,ENT_QUOTES,'UTF-8') ?>"><button type="submit" style="border:0;background:none;color:#e74c3c;font-weight:bold;cursor:pointer">Xóa</button></form></td>
</tr>
<?php endforeach; ?></tbody></table>
<div class="total-section">Tạm tính tổng tiền: <span style="color:#e74c3c;font-size:22px"><?= number_format($tong_tien_tinh_tam,0,',','.') ?> đ</span></div>
<div class="action-buttons">
<form method="post"><input type="hidden" name="cart_csrf" value="<?= htmlspecialchars($_SESSION['cart_csrf'],ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="hanh_dong" value="xoa_het"><button class="btn btn-danger" type="submit">🗑️ Xóa sạch giỏ hàng</button></form>
<a href="thanh_toan.php" class="btn btn-success">💳 Tiến hành đặt hàng & Thuê sách</a>
</div>
<?php endif; ?>
</div>
</body>
</html>
