<?php
session_start();
if(!isset($_SESSION['lab_balance'])) $_SESSION['lab_balance']=1000000;
if(!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$mode=$_GET['mode']??'vulnerable'; $message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $amount=(int)($_POST['amount']??0);
 if($mode==='fixed' && !hash_equals($_SESSION['csrf_token'],$_POST['csrf_token']??'')){$message='403: CSRF token không hợp lệ.';}
 elseif($amount>0 && $amount<=1000000){$_SESSION['lab_balance']-=$amount;$message='Transfer accepted. Balance: '.$_SESSION['lab_balance'];}
}
?><!doctype html><html lang="vi"><body style="font-family:Arial;max-width:900px;margin:30px auto"><h1>CSRF Lab</h1><p>Balance: <?=htmlspecialchars((string)$_SESSION['lab_balance'])?></p><form method="post"><input type="number" name="amount" value="1000"><input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>"><select name="mode" onchange="this.form.action='csrf.php?mode='+this.value"><option value="vulnerable" <?=$mode==='vulnerable'?'selected':''?>>vulnerable</option><option value="fixed" <?=$mode==='fixed'?'selected':''?>>fixed</option></select><button>Change state</button></form><p><?=$message?></p><p>Fixed: mọi state-changing request phải có token; kiểm tra server-side.</p><a href="index.php">← Lab menu</a></body></html>