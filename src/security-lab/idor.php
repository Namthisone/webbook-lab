<?php
session_start();
if(!isset($_SESSION['lab_user'])) $_SESSION['lab_user']=1;
$id=(int)($_GET['id']??1); $mode=$_GET['mode']??'vulnerable';
$docs=[1=>['owner'=>1,'title'=>'Đơn hàng của User 1','body'=>'Dữ liệu mẫu A'],2=>['owner'=>2,'title'=>'Đơn hàng của User 2','body'=>'Dữ liệu mẫu B'],3=>['owner'=>3,'title'=>'Đơn hàng của User 3','body'=>'Dữ liệu mẫu C']];
$doc=$docs[$id]??null; $allowed=$doc && ($mode==='vulnerable' || $doc['owner']===$_SESSION['lab_user']);
?><!doctype html><html lang="vi"><body style="font-family:Arial;max-width:900px;margin:30px auto"><h1>IDOR Lab</h1><p>Lab user ID: <?=htmlspecialchars((string)$_SESSION['lab_user'])?></p><form><input type="number" name="id" value="<?=$id?>" min="1"><select name="mode"><option value="vulnerable">vulnerable</option><option value="fixed">fixed</option></select><button>View object</button></form><?php if($allowed): ?><pre><?=htmlspecialchars(print_r($doc,true))?></pre><?php else: ?><p style="color:#b91c1c"><b>403 Forbidden:</b> object không thuộc user hiện tại.</p><?php endif; ?><p>Fixed: kiểm tra authorization/ownership phía server, không dựa vào ID trên URL.</p><a href="index.php">← Lab menu</a></body></html>