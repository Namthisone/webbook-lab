<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user']['id'])) {
    header('Location: dang_nhap.php');
    exit;
}

$id_nguoi_dung_hien_tai = (int)$_SESSION['user']['id'];
$stmt = $ket_noi->prepare(
    'SELECT id, trang_thai, ngay_dat_hang, tong_tien
     FROM don_hang_mua
     WHERE id_nguoi_dung = ?
     ORDER BY ngay_dat_hang DESC'
);
$stmt->execute([$id_nguoi_dung_hien_tai]);
$danh_sach = $stmt->fetchAll();
?>
<!doctype html>
<html lang="vi"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Đơn hàng mua của tôi - Tạp Hóa Sách</title>
<style>
body{font-family:Arial;background:#f8fafc;color:#1e293b;margin:0;padding:30px}main{max-width:1000px;margin:auto;background:#fff;padding:25px;border-radius:12px;box-shadow:0 4px 15px #0001}table{width:100%;border-collapse:collapse}th,td{padding:12px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#0f172a;color:#fff}a{color:#2563eb;text-decoration:none}.empty{text-align:center;padding:30px;color:#64748b}
</style></head><body><main>
<h1>📦 Đơn hàng mua của tôi</h1>
<p><a href="index.php">← Trang chủ</a></p>
<?php if (!$danh_sach): ?><div class="empty">Bạn chưa có đơn mua nào.</div>
<?php else: ?><table><thead><tr><th>Mã đơn</th><th>Ngày đặt</th><th>Trạng thái</th><th>Tổng tiền</th></tr></thead><tbody>
<?php foreach ($danh_sach as $don): ?><tr>
<td>#<?= (int)$don['id'] ?></td>
<td><?= htmlspecialchars((string)$don['ngay_dat_hang'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= htmlspecialchars((string)$don['trang_thai'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?= number_format((float)$don['tong_tien'],0,',','.') ?> đ</td>
</tr><?php endforeach; ?></tbody></table><?php endif; ?>
</main></body></html>
