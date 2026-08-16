<?php
require_once '../db.php';
$mode=$_GET['mode']??'vulnerable'; $id=$_GET['id']??'1'; $out='';
if($mode==='vulnerable'){
  // LAB ONLY: intentional SQL injection. Never use this pattern in production.
  try{$q="SELECT ma_sach, ten_sach, gia_ban FROM sach WHERE ma_sach = '$id'"; $rows=$ket_noi->query($q)->fetchAll(PDO::FETCH_ASSOC); $out=htmlspecialchars(print_r($rows,true));}
  catch(Throwable $e){$out=htmlspecialchars($e->getMessage());}
}else{
  try{$st=$ket_noi->prepare('SELECT ma_sach, ten_sach, gia_ban FROM sach WHERE ma_sach = ?'); $st->execute([(int)$id]); $out=htmlspecialchars(print_r($st->fetchAll(PDO::FETCH_ASSOC),true));}
  catch(Throwable $e){$out='Invalid input';}
}
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><title>SQLi Lab</title><style>body{font-family:Arial;max-width:950px;margin:30px auto}pre{background:#111;color:#0f0;padding:15px;white-space:pre-wrap}.bad{color:#b91c1c}.good{color:#15803d}</style></head><body>
<h1>SQL Injection Lab</h1><p>Mode: <b class="<?= $mode==='vulnerable'?'bad':'good'?>"><?=htmlspecialchars($mode)?></b></p>
<form><input name="id" value="<?=htmlspecialchars($id)?>" placeholder="Book ID"><select name="mode"><option value="vulnerable">vulnerable</option><option value="fixed">fixed</option></select><button>Test</button></form>
<h3>Result</h3><pre><?=$out?></pre><p><b>Ghi báo cáo:</b> URL/request, input thử nghiệm trong lab, response, lỗi/không lỗi và log WAF.</p><p><a href="index.php">← Lab menu</a></p></body></html>