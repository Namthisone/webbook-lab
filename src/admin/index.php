<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['vai_tro'] ?? '') !== 'admin') {
    header('Location: ../dang_nhap.php');
    exit;
}

$admin_name = (string)($_SESSION['user']['ho_ten'] ?? $_SESSION['user']['ten_dang_nhap'] ?? 'Admin');
$count_sach = (int)$ket_noi->query('SELECT COUNT(*) FROM sach')->fetchColumn();
$count_the_loai = (int)$ket_noi->query('SELECT COUNT(*) FROM the_loai')->fetchColumn();
$count_don_mua = (int)$ket_noi->query("SELECT COUNT(*) FROM don_hang_mua WHERE trang_thai='cho_duyet'")->fetchColumn();
$count_don_thue = (int)$ket_noi->query("SELECT COUNT(*) FROM don_hang_thue WHERE trang_thai='dang_thue'")->fetchColumn();
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin Dashboard</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial;background:#f4f6f9;color:#1e293b;display:flex;min-height:100vh}.sidebar{width:250px;background:#1e293b;color:#fff;padding:20px 0}.sidebar h2{padding:0 20px 20px}.sidebar a{display:block;padding:12px 20px;color:#cbd5e1;text-decoration:none}.sidebar a:hover{background:#334155;color:#fff}.main{flex:1;padding:30px}.top{display:flex;justify-content:space-between;align-items:center;background:#fff;padding:18px 22px;border-radius:10px;margin-bottom:25px}.logout{color:#dc2626;text-decoration:none;font-weight:bold}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px}.card{background:#fff;padding:25px;border-radius:12px;box-shadow:0 3px 12px #0000000d}.num{font-size:32px;font-weight:bold;margin-top:10px;color:#2563eb}.small{color:#64748b}
</style></head><body>
<aside class="sidebar"><h2>📚 ADMIN</h2><a href="index.php">📊 Dashboard</a><a href="admin_quan_ly_the_loai.php">📁 Thể loại</a><a href="admin_quan_ly_sach.php">📚 Sách</a><a href="admin_don_hang_mua.php">🛒 Đơn mua</a><a href="admin_don_hang_thue.php">📋 Đơn thuê</a><a href="admin_quan_ly_nguoi_dung.php">👤 Người dùng</a><a href="admin_khieu_nai.php">⚠️ Khiếu nại</a><a href="../index.php">🌐 Trang chủ</a></aside>
<main class="main"><div class="top"><strong>Xin chào, <?= htmlspecialchars($admin_name,ENT_QUOTES,'UTF-8') ?></strong><a class="logout" href="../dang_xuat.php">Đăng xuất</a></div><h1>📊 Tổng quan hệ thống</h1><div class="grid">
<div class="card"><div class="small">Tổng số sách</div><div class="num"><?= $count_sach ?></div></div>
<div class="card"><div class="small">Tổng số thể loại</div><div class="num"><?= $count_the_loai ?></div></div>
<div class="card"><div class="small">Đơn mua chờ duyệt</div><div class="num"><?= $count_don_mua ?></div></div>
<div class="card"><div class="small">Đơn thuê đang thuê</div><div class="num"><?= $count_don_thue ?></div></div>
</div></main></body></html>
