<?php
$mode=$_GET['mode']??'vulnerable'; $input=$_GET['host']??'127.0.0.1'; $result='';
if(isset($_GET['run'])){
 if($mode==='vulnerable'){$cmd='ping -c 1 '.$input; $result=shell_exec($cmd);}
 else { if(!preg_match('/^(127\.0\.0\.1|localhost)$/',$input)){$result='Blocked: host is not in allowlist.';} else {$result=shell_exec('ping -c 1 '.escapeshellarg($input));} }
}
?><!doctype html><html lang="vi"><body style="font-family:Arial;max-width:900px;margin:30px auto"><h1>OS Command Injection Lab</h1><p>Chỉ chạy trong lab cục bộ.</p><form><input name="host" value="<?=htmlspecialchars($input)?>"><select name="mode"><option value="vulnerable">vulnerable</option><option value="fixed">fixed</option></select><button name="run" value="1">Run</button></form><pre><?=htmlspecialchars((string)$result)?></pre><p>Fixed: allowlist + escapeshellarg; tốt nhất tránh shell và dùng API hệ thống khi có thể.</p><a href="index.php">← Lab menu</a></body></html>