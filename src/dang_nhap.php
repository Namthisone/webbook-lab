<?php
require_once 'db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/*
 * WebBook dual-mode login lab.
 * ATTACK: intentionally vulnerable SQL concatenation, isolated to :8081.
 * DEFENSE: prepared statements + password verification, used on :8080/:8443.
 */
$attackMode = getenv('WEBBOOK_SECURITY_MODE') === 'attack';

if (empty($_SESSION['login_csrf'])) {
    $_SESSION['login_csrf'] = bin2hex(random_bytes(32));
}

if (isset($_SESSION['user'])) {
    header('Location: ' . (($_SESSION['user']['vai_tro'] ?? '') === 'admin' ? 'admin/index.php' : 'index.php'));
    exit;
}

$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $ten   = trim($_POST['ten_dang_nhap'] ?? '');
    $mat   = $_POST['mat_khau'] ?? '';

    if (!$attackMode && !hash_equals($_SESSION['login_csrf'], $token)) {
        $loi = 'Phiên biểu mẫu không hợp lệ. Vui lòng thử lại.';
        http_response_code(403);
    } elseif ($ten === '' || $mat === '') {
        $loi = 'Vui lòng nhập đầy đủ tài khoản và mật khẩu!';
    } elseif (!$attackMode && !preg_match('/^[A-Za-z0-9_.-]{3,80}$/', $ten)) {
        $loi = 'Tên đăng nhập không hợp lệ.';
    } else {
        try {
            if ($attackMode) {
                /*
                 * INTENTIONALLY VULNERABLE — ATTACK LAB ONLY.
                 * Both username and password are concatenated into SQL.
                 * The seed database contains legacy MD5 hashes so the lab can
                 * demonstrate the classic authentication-bypass pattern.
                 * Never expose this container outside the isolated lab.
                 */
                $tenSql = $ket_noi->quote($ten);
                $matSql = $ket_noi->quote($mat);
                $sql = "SELECT * FROM nguoi_dung
                        WHERE ten_dang_nhap='$ten'
                        AND (mat_khau=MD5('$mat') OR mat_khau='$mat')
                        LIMIT 1";

                // Deliberately use query() rather than prepare() in ATTACK mode.
                $stmt = $ket_noi->query($sql);
            } else {
                /* DEFENSE: parameterized query; user input never becomes SQL. */
                $stmt = $ket_noi->prepare(
                    'SELECT * FROM nguoi_dung WHERE ten_dang_nhap=? LIMIT 1'
                );
                $stmt->execute([$ten]);
            }

            $user = $stmt->fetch();
            $ok = false;

            if ($user) {
                $stored = (string)($user['mat_khau'] ?? '');

                if ($attackMode) {
                    /*
                     * In ATTACK mode the SQL query itself determines whether a
                     * row is authenticated. This is intentionally vulnerable.
                     */
                    $ok = true;
                } else {
                    $ok = password_verify($mat, $stored);

                    /* One-time migration for old MD5 records in the lab seed. */
                    if (!$ok && preg_match('/^[a-f0-9]{32}$/i', $stored) && hash_equals(strtolower($stored), md5($mat))) {
                        $ok = true;
                        $new = password_hash($mat, PASSWORD_DEFAULT);
                        $u = $ket_noi->prepare('UPDATE nguoi_dung SET mat_khau=? WHERE id=?');
                        $u->execute([$new, $user['id']]);
                    }
                }
            }

            if ($ok) {
                session_regenerate_id(true);
                $_SESSION['user'] = $user;
                $_SESSION['login_csrf'] = bin2hex(random_bytes(32));

                header('Location: ' . (($user['vai_tro'] ?? '') === 'admin' ? 'admin/index.php' : 'index.php'));
                exit;
            }

            error_log(
                '[WEBBOOK-LOGIN-FAIL] MODE=' . ($attackMode ? 'ATTACK' : 'DEFENSE') .
                ' IP=' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') .
                ' user=' . preg_replace('/[^A-Za-z0-9_.-]/', '', $ten)
            );
            $loi = 'Tài khoản hoặc mật khẩu không chính xác!';
        } catch (Throwable $e) {
            error_log('[WEBBOOK-LOGIN-ERROR] MODE=' . ($attackMode ? 'ATTACK' : 'DEFENSE') . ' ' . $e->getMessage());
            $loi = $attackMode
                ? 'ATTACK LAB: câu SQL đã lỗi. Kiểm tra payload và log Apache.'
                : 'Không thể xử lý đăng nhập lúc này.';
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $attackMode ? 'Đăng nhập - ATTACK LAB' : 'Đăng nhập an toàn' ?></title>
<style>
body{font-family:Arial;background:#0f172a;color:#fff;display:grid;place-items:center;min-height:100vh;margin:0}
.box{width:min(460px,92vw);background:#1e293b;padding:30px;border-radius:16px;box-sizing:border-box}
input,button{width:100%;padding:12px;margin:8px 0;box-sizing:border-box}
button{background:#ea580c;color:#fff;border:0;border-radius:8px;cursor:pointer}
a{color:#38bdf8}.err{background:#7f1d1d;padding:10px;border-radius:8px}.lab{background:#7c2d12;padding:12px;border-radius:8px;margin-bottom:12px}
.ok{background:#14532d;padding:12px;border-radius:8px;margin-bottom:12px}.small{font-size:13px;color:#cbd5e1;line-height:1.5}
code{background:#0f172a;padding:2px 5px;border-radius:4px}
</style>
</head>
<body>
<div class="box">
<h1>🔐 Đăng nhập</h1>
<?php if ($attackMode): ?>
<div class="lab">
<b>⚠️ ATTACK LAB</b><br>
Trang này cố ý chứa SQL Injection để kiểm thử <b>chỉ trong VM/lab nội bộ</b>.
</div>
<div class="ok">
<b>🧪 Kịch bản:</b> SQL được ghép trực tiếp từ dữ liệu nhập.<br>
<b>Tài khoản lab mẫu:</b> <code>admin</code> / <code>123456</code><br>
Có thể dùng payload SQLi ở ô tên đăng nhập để chứng minh authentication bypass.
</div>
<?php else: ?>
<div class="ok"><b>🛡️ DEFENSE LAB</b><br>Prepared Statement + password verification + CSRF.</div>
<?php endif; ?>
<?php if ($loi): ?><div class="err"><?= htmlspecialchars($loi, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<form method="post" autocomplete="off">
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['login_csrf'], ENT_QUOTES, 'UTF-8') ?>">
<input name="ten_dang_nhap" placeholder="Tên đăng nhập" autocomplete="username" required>
<input type="password" name="mat_khau" placeholder="Mật khẩu" autocomplete="current-password" required>
<button>Đăng nhập</button>
</form>
<p class="small">ATTACK chỉ dùng trên <code>:8081</code>. DEFENSE dùng <code>:8080</code>/<code>:8443</code>.</p>
<p><a href="dang_ky.php">Đăng ký</a> · <a href="index.php">Trang chủ</a></p>
</div>
</body>
</html>
