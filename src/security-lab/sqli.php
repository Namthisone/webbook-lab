<?php require 'common.php';
$mode=lab_mode();$type=$_GET['type']??'basic';$id=$_GET['id']??'1';$out='';$elapsed=0;$status=200;
$start=microtime(true);
try{
 if($mode==='fixed'){
  if(!ctype_digit((string)$id)){throw new RuntimeException('Invalid numeric ID.');}
  $st=$ket_noi->prepare('SELECT id,ten_sach,gia_ban FROM sach WHERE id=?');$st->execute([(int)$id]);$out=print_r($st->fetchAll(PDO::FETCH_ASSOC),true);$result='blocked-or-safe';
 }else{
  if($type==='basic'||$type==='union'||$type==='error'||$type==='blind'||$type==='time'){
   if($type==='union'){$q="SELECT id,ten_sach,gia_ban FROM sach WHERE id = '$id'";}
   elseif($type==='blind'){$q="SELECT id FROM sach WHERE id = '$id'";}
   elseif($type==='time'){$q="SELECT id FROM sach WHERE id = '$id'";}
   else{$q="SELECT id,ten_sach,gia_ban FROM sach WHERE id = '$id'";}
   $st=$ket_noi->query($q);$rows=$st->fetchAll(PDO::FETCH_ASSOC);$elapsed=microtime(true)-$start;$out=print_r($rows,true);$result=($type==='blind'?(count($rows)?'TRUE':'FALSE'):($type==='time'?sprintf('Elapsed %.3fs',$elapsed):'query-executed'));
  }else{$out='Unknown test type';$status=400;$result='invalid-test';}
 }
}catch(Throwable $e){$status=500;$out=$mode==='fixed'?'Invalid input / request rejected':$e->getMessage();$result='error';}
$elapsed=microtime(true)-$start;audit('SQL Injection '.$type,$mode,'GET',$_SERVER['REQUEST_URI']??'', $status,$result,'Use the same input in vulnerable and fixed modes; record HTTP status and ModSecurity log.');
?><!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SQL Injection Lab</title><style>body{font-family:Arial;max-width:1000px;margin:25px auto}.row{display:flex;gap:8px;flex-wrap:wrap}input,select,button{padding:9px}pre{background:#111;color:#0f0;padding:15px;white-space:pre-wrap;overflow:auto}.bad{color:#b91c1c}.good{color:#15803d}.box{background:#f3f4f6;padding:12px;margin:12px 0}</style></head><body><h1>SQL Injection Lab — 5 scenarios</h1><div class="box"><b>Basic:</b> normal lookup · <b>Union:</b> UNION-style test · <b>Error:</b> syntax/error behavior · <b>Blind:</b> TRUE/FALSE inference · <b>Time:</b> timing inference. Chỉ dùng localhost/VM.</div><form class="row"><input name="id" value="<?=h($id)?>" size="35"><select name="type"><?php foreach(['basic','union','error','blind','time'] as $t):?><option value="<?=$t?>" <?=$type===$t?'selected':''?>><?=$t?></option><?php endforeach;?></select><select name="mode"><option value="vulnerable" <?=$mode==='vulnerable'?'selected':''?>>vulnerable</option><option value="fixed" <?=$mode==='fixed'?'selected':''?>>fixed</option></select><button>Test</button></form><p>HTTP status: <b><?=$status?></b> · elapsed: <b><?=number_format($elapsed,3)?>s</b></p><pre><?=h($out)?></pre><p><b>Fixed:</b> PDO prepared statement + strict numeric validation. WAF should be verified separately.</p><p><a href="index.php">← Lab menu</a> · <a href="audit.php">Audit log</a></p></body></html>