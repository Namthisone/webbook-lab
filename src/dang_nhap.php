<?php
require_once 'db.php';
if(session_status()!==PHP_SESSION_ACTIVE)session_start();

/*
 * ATTACK/DEFENSE lab switch.
 * ATTACK is intentionally vulnerable and must only be used on the isolated
 * docker-compose.attack.yml instance. DEFENSE keeps the hardened flow.
 */
$attackMode = getenv('WEBBOOK_SECURITY_MODE') === 'attack';

if(empty($_SESSION['login_csrf']))$_SESSION['login_csrf']=bin2hex(random_bytes(32));
if(isset($_SESSION['user'])){header('Location: '.($_SESSION['user']['vai_tro']==='admin'?'admin/index.php':'index.php'));exit;}
$loi='';

if($_SERVER['REQUEST_METHOD']==='POST'){
 $token=$_POST['csrf_token']??'';
 $ten=trim($_POST['ten_dang_nhap']??'');
 $mat=$_POST['mat_khau']??'';

 if(!$attackMode && !hash_equals($_SESSION['login_csrf'],$token)){
  $loi='Phiên biểu mẫu không hợp lệ. Vui lòng thử lại.';
  http_response_code(403);
 }
 elseif($ten===''||$mat==='')$loi='Vui lòng nhập đầy đủ tài khoản và mật khẩu!';
 elseif(!$attackMode && !preg_match('/^[A-Za-z0-9_.-]{3,80}$/',$ten))$loi='Tên đăng nhập không hợp lệ.';
 else{
  if($attackMode){
   // INTENTIONALLY VULNERABLE FOR THE ISOLATED ATTACK LAB ONLY.
   // User input is concatenated into SQL so the SQLi testcase can be
   // demonstrated against :8081. The defense container never enters here.
   $sql="SELECT * FROM nguoi_dung WHERE ten_dang_nhap='$ten' LIMIT 1";
   $stmt=$ket_noi->query($sql);
  }else{
   // DEFENSE: prepared statement prevents SQL injection.
   $stmt=$ket_noi->prepare('SELECT * FROM nguoi_dung WHERE ten_dang_nhap=? LIMIT 1');
   $stmt->execute([$ten]);
  }

  $user=$stmt->fetch();
  $ok=false;
  if($user){
   $stored=$user['mat_khau'];
   $ok=password_verify($mat,$stored);
   if(!$ok&&hash_equals(strtolower($stored),md5($mat))){
    $ok=true;
    $new=password_hash($mat,PASSWORD_DEFAULT);
    $u=$ket_noi->prepare('UPDATE nguoi_dung SET mat_khau=? WHERE id=?');
    $u->execute([$new,$user['id']]);
   }
  }

  if($ok){
   session_regenerate_id(true);
   $_SESSION['user']=$user;
   $_SESSION['login_csrf']=bin2hex(random_bytes(32));
   header('Location: '.($user['vai_tro']==='admin'?'admin/index.php':'index.php'));
   exit;
  }

  error_log('[WEBBOOK-LOGIN-FAIL] MODE='.($attackMode?'ATTACK':'DEFENSE').' IP='.($_SERVER['REMOTE_ADDR']??'unknown').' user='.preg_replace('/[^A-Za-z0-9_.-]/','',$ten));
  $loi='Tài khoản hoặc mật khẩu không chính xác!';
 }
}
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= $attackMode?'Đăng nhập - ATTACK LAB':'Đăng nhập an toàn' ?></title><style>body{font-family:Arial;background:#0f172a;color:#fff;display:grid;place-items:center;min-height:100vh}.box{width:min(420px,92vw);background:#1e293b;padding:30px;border-radius:16px}input,button{width:100%;padding:12px;margin:8px 0;box-sizing:border-box}button{background:#ea580c;color:#fff;border:0;border-radius:8px}a{color:#38bdf8}.err{background:#7f1d1d;padding:10px;border-radius:8px}.lab{background:#7c2d12;padding:10px;border-radius:8px;margin-bottom:12px}</style></head><body><div class="box"><h1>🔐 Đăng nhập</h1><?php if($attackMode):?><div class="lab"><b>⚠️ ATTACK LAB</b><br>Chức năng này cố ý chứa SQL Injection để kiểm thử trong môi trường lab.</div><?php endif;?><?php if($loi):?><div class="err"><?=htmlspecialchars($loi,ENT_QUOTES,'UTF-8')?></div><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['login_csrf'],ENT_QUOTES,'UTF-8')?>"><input name="ten_dang_nhap" placeholder="Tên đăng nhập" autocomplete="username" required><input type="password" name="mat_khau" placeholder="Mật khẩu" autocomplete="current-password" required><button>Đăng nhập</button></form><p><a href="dang_ky.php">Đăng ký</a> · <a href="index.php">Trang chủ</a></p></div></body></html>
