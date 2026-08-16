<?php require 'common.php';
$mode=lab_mode();
$input=$_GET['host']??'127.0.0.1';
$result='';
if(isset($_GET['run'])){
    if($mode==='vulnerable'){
        // ATTACK container only: deliberately unsafe shell concatenation for lab demonstration.
        $cmd='ping -c 1 '.$input;
        $result=shell_exec($cmd);
    }else{
        if(!preg_match('/^(127\.0\.0\.1|localhost)$/',$input)){
            $result='Blocked: host is not in allowlist.';
        }else{
            $result=shell_exec('ping -c 1 '.escapeshellarg($input));
        }
    }
    audit('OS Command Injection',$mode,'GET',$_SERVER['REQUEST_URI']??'',200,$mode==='vulnerable'?'command-executed':'validated','Compare vulnerable shell concatenation with allowlist/escaping.');
}
?><!doctype html><html lang="vi"><body style="font-family:Arial;max-width:900px;margin:30px auto"><h1>OS Command Injection Lab</h1><p>Deployment mode: <b><?=h($mode)?></b></p><p>Chỉ chạy trong lab cô lập.</p><form><input name="host" value="<?=h($input)?>"><button name="run" value="1">Run</button></form><pre><?=h((string)$result)?></pre><p>Fixed: allowlist + escapeshellarg; tốt nhất tránh shell và dùng API hệ thống khi có thể.</p><a href="index.php">← Lab menu</a></body></html>