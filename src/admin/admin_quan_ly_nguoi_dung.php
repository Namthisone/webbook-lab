<?php
session_start();
require_once '../db.php';

if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['vai_tro'] ?? '') !== 'admin') {
    header('Location: ../dang_nhap.php');
    exit;
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
}

$thong_bao = '';
$csrf = $_SESSION['admin_csrf'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        $thong_bao = 'CSRF token không hợp lệ.';
    } else {
        $action = $_POST['action'] ?? '';
        try {
            if ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0 || $id === (int)$_SESSION['user']['id']) {
                    throw new RuntimeException('Không thể xóa tài khoản hiện tại hoặc ID không hợp lệ.');
                }
                $s = $ket_noi->prepare('DELETE FROM nguoi_dung WHERE id=?');
                $s->execute([$id]);
                $thong_bao = 'Đã xóa người dùng.';
            } elseif ($action === 'save') {
                $id = (int)($_POST['id'] ?? 0);
                $ho_ten = trim($_POST['ho_ten'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['so_dien_thoai'] ?? '');
                $role = ($_POST['vai_tro'] ?? 'khach_hang') === 'admin' ? 'admin' : 'khach_hang';
                $password = $_POST['mat_khau'] ?? '';

                if ($ho_ten === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('Họ tên và email hợp lệ là bắt buộc.');
                }

                if ($id > 0) {
                    if ($password !== '') {
                        if (strlen($password) < 8) throw new RuntimeException('Mật khẩu mới phải có ít nhất 8 ký tự.');
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $s = $ket_noi->prepare('UPDATE nguoi_dung SET ho_ten=?, email=?, so_dien_thoai=?, vai_tro=?, mat_khau=? WHERE id=?');
                        $s->execute([$ho_ten,$email,$phone,$role,$hash,$id]);
                    } else {
                        $s = $ket_noi->prepare('UPDATE nguoi_dung SET ho_ten=?, email=?, so_dien_thoai=?, vai_tro=? WHERE id=?');
                        $s->execute([$ho_ten,$email,$phone,$role,$id]);
                    }
                    $thong_bao = 'Đã cập nhật tài khoản.';
                } else {
                    if (strlen($password) < 8) throw new RuntimeException('Mật khẩu mới phải có ít nhất 8 ký tự.');
                    $s = $ket_noi->prepare('INSERT INTO nguoi_dung(ten_dang_nhap,mat_khau,ho_ten,email,so_dien_thoai,vai_tro) VALUES(?,?,?,?,?,?)');
                    $username = trim($_POST['ten_dang_nhap'] ?? '');
                    if (!preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $username)) throw new RuntimeException('Tên đăng nhập không hợp lệ.');
                    $s->execute([$username,password_hash($password,PASSWORD_DEFAULT),$ho_ten,$email,$phone,$role]);
                    $thong_bao = 'Đã tạo tài khoản.';
                }
            }
        } catch (Throwable $e) {
            $thong_bao = 'Lỗi: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}

$user_sua = null;
if (isset($_GET['sua'])) {
    $s = $ket_noi->prepare('SELECT id,ten_dang_nhap,ho_ten,email,so_dien_thoai,vai_tro FROM nguoi_dung WHERE id=?');
    $s->execute([(int)$_GET['sua']]);
    $user_sua = $s->fetch();
}
$danh_sach_user = $ket_noi->query('SELECT id,ten_dang_nhap,ho_ten,email,so_dien_thoai,vai_tro FROM nguoi_dung ORDER BY id DESC')->fetchAll();
?>
<!doctype html>
<html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin - Người dùng</title>
<style>
*{box-sizing:border-box}body{font-family:Arial;background:#f4f6f9;margin:0;color:#1e293b}.wrap{max-width:1200px;margin:30px auto;padding:0 15px}.box{background:#fff;padding:22px;border-radius:12px;box-shadow:0 3px 12px #0001;margin-bottom:20px}input,select{width:100%;padding:10px;margin:6px 0 14px;border:1px solid #cbd5e1;border-radius:6px}button,.btn{border:0;border-radius:6px;padding:9px 14px;background:#2563eb;color:#fff;text-decoration:none;cursor:pointer}.danger{background:#dc2626}.back{color:#2563eb;text-decoration:none}table{width:100%;border-collapse:collapse}th,td{padding:11px;border-bottom:1px solid #e2e8f0;text-align:left}th{background:#0f172a;color:#fff}.alert{padding:12px;background:#dcfce7;border-radius:6px;margin-bottom:15px}.role{font-weight:bold}
</style></head><body><div class="wrap"><p><a class="back" href="index.php">← Dashboard</a></p>
<div class="box"><h2>👥 Quản lý người dùng</h2><?php if($thong_bao): ?><div class="alert"><?= $thong_bao ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($user_sua['id']??0) ?>">
<?php if(!$user_sua): ?><label>Tên đăng nhập</label><input name="ten_dang_nhap" required pattern="[A-Za-z0-9_.-]{3,80}"><?php else: ?><p><b>Tài khoản:</b> <?= htmlspecialchars($user_sua['ten_dang_nhap'],ENT_QUOTES,'UTF-8') ?></p><?php endif; ?>
<label>Họ tên</label><input name="ho_ten" value="<?= htmlspecialchars($user_sua['ho_ten']??'',ENT_QUOTES,'UTF-8') ?>" required>
<label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($user_sua['email']??'',ENT_QUOTES,'UTF-8') ?>" required>
<label>Số điện thoại</label><input name="so_dien_thoai" value="<?= htmlspecialchars($user_sua['so_dien_thoai']??'',ENT_QUOTES,'UTF-8') ?>">
<label>Vai trò</label><select name="vai_tro"><option value="khach_hang" <?= (($user_sua['vai_tro']??'khach_hang')==='khach_hang')?'selected':'' ?>>Khách hàng</option><option value="admin" <?= (($user_sua['vai_tro']??'')==='admin')?'selected':'' ?>>Admin</option></select>
<label>Mật khẩu <?= $user_sua?'(để trống nếu giữ nguyên)':'' ?></label><input type="password" name="mat_khau" minlength="8" autocomplete="new-password"><button>Lưu</button> <?php if($user_sua): ?><a class="btn" href="admin_quan_ly_nguoi_dung.php">Hủy</a><?php endif; ?></form></div>
<div class="box"><h2>Danh sách tài khoản</h2><table><thead><tr><th>ID</th><th>Tài khoản</th><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Thao tác</th></tr></thead><tbody><?php foreach($danh_sach_user as $u): ?><tr><td><?= (int)$u['id'] ?></td><td><?= htmlspecialchars($u['ten_dang_nhap'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars($u['ho_ten'],ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars($u['email'],ENT_QUOTES,'UTF-8') ?></td><td class="role"><?= htmlspecialchars($u['vai_tro'],ENT_QUOTES,'UTF-8') ?></td><td><a class="btn" href="?sua=<?= (int)$u['id'] ?>">Sửa</a> <?php if((int)$u['id']!==(int)$_SESSION['user']['id']): ?><form method="post" style="display:inline"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf,ENT_QUOTES,'UTF-8') ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><button class="danger" onclick="return confirm('Xóa tài khoản này?')">Xóa</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div></div></body></html>
