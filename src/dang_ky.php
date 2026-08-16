<?php
require_once 'db.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start();
if(empty($_SESSION['register_csrf']))$_SESSION['register_csrf']=bin2hex(random_bytes(32));
$loi='';$thong_bao='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $token=$_POST['csrf_token']??'';$ten=trim($_POST['ten_dang_nhap']??'');$mat=$_POST['mat_khau']??'';$ho=trim($_POST['ho_ten']??'');$email=trim($_POST['email']??'');
 if(!hash_equals($_SESSION['register_csrf'],$token)){$loi='CSRF token không hợp lệ.';http_response_code(403);}
 elseif(!preg_match('/^[A-Za-z0-9_.-]{3,80}$/',$ten))$loi='Tên đăng nhập 3-80 ký tự, chỉ chữ/số/_/./-.';
 elseif(strlen($mat)<8)$loi='Mật khẩu phải có ít nhất 8 ký tự.';
 elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))$loi='Email không hợp lệ.';
 elseif($ho==='')$loi='Vui lòng nhập họ tên.';
 else{$s=$ket_noi->prepare('SELECT id FROM nguoi_dung WHERE ten_dang_nhap=? OR email=? LIMIT 1');$s->execute([$ten,$email]);if($s->fetch())$loi='Tên đăng nhập hoặc Email đã tồn tại.';else{$hash=password_hash($mat,PASSWORD_DEFAULT);$i=$ket_noi->prepare("INSERT INTO nguoi_dung(ten_dang_nhap,mat_khau,ho_ten,email,vai_tro) VALUES(?,?,?,?, 'khach_hang')");if($i->execute([$ten,$hash,$ho,$email])){$thong_bao='Đăng ký thành công.';$_SESSION['register_csrf']=bin2hex(random_bytes(32));}else{$loi='Không thể tạo tài khoản.';}}}
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Đăng ký an toàn</title><style>body{font-family:Arial;background:#0f172a;color:#fff;display:grid;place-items:center;min-height:100vh}.box{width:min(460px,92vw);background:#1e293b;padding:30px;border-radius:16px}input,button{width:100%;padding:12px;margin:8px 0;box-sizing:border-box}button{background:#059669;color:#fff;border:0;border-radius:8px}a{color:#38bdf8}.err{background:#7f1d1d;padding:10px;border-radius:8px}.ok{background:#065f46;padding:10px;border-radius:8px}</style></head><body><div class="box"><h1>📝 Đăng ký</h1><?php if($loi):?><div class="err"><?=htmlspecialchars($loi,ENT_QUOTES,'UTF-8')?></div><?php endif;?><?php if($thong_bao):?><div class="ok"><?=htmlspecialchars($thong_bao,ENT_QUOTES,'UTF-8')?> <a href="dang_nhap.php">Đăng nhập</a></div><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['register_csrf'],ENT_QUOTES,'UTF-8')?>"><input name="ho_ten" placeholder="Họ tên" required><input type="email" name="email" placeholder="Email" required><input name="ten_dang_nhap" placeholder="Tên đăng nhập" required><input type="password" name="mat_khau" placeholder="Mật khẩu tối thiểu 8 ký tự" required><button>Tạo tài khoản</button></form><p><a href="dang_nhap.php">Đăng nhập</a></p></div></body></html>