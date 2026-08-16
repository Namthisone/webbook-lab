<?php require 'common.php';
$mode=lab_mode();
if(!isset($_SESSION['lab_balance'])) $_SESSION['lab_balance']=1000000;
if(!isset($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $amount=(int)($_POST['amount']??0);
    if($mode==='fixed' && !hash_equals($_SESSION['csrf_token'],$_POST['csrf_token']??'')){
        http_response_code(403);
        $message='403: CSRF token không hợp lệ.';
        audit('CSRF',$mode,'POST','/security-lab/csrf.php',403,'blocked','Server-side CSRF token validation failed.');
    }elseif($amount>0 && $amount<=1000000){
        $_SESSION['lab_balance']-=$amount;
        $message='Transfer accepted. Balance: '.$_SESSION['lab_balance'];
        audit('CSRF',$mode,'POST','/security-lab/csrf.php',200,'state-changed','Compare state-changing request with and without the token.');
    }
}
?><!doctype html><html lang="vi"><body style="font-family:Arial;max-width:900px;margin:30px auto"><h1>CSRF Lab</h1><p>Deployment mode: <b><?=h($mode)?></b></p><p>Balance: <?=h((string)$_SESSION['lab_balance'])?></p><form method="post"><input type="number" name="amount" value="1000"><input type="hidden" name="csrf_token" value="<?=h($_SESSION['csrf_token'])?>"><button>Change state</button></form><p><?=h($message)?></p><p>Attack mode intentionally accepts the state-changing request without enforcing the token. Defense mode requires a server-side token check.</p><a href="index.php">← Lab menu</a></body></html>