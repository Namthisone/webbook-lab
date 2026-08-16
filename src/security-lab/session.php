<?php
session_start();
if(isset($_GET['login'])){$_SESSION['user']='student'; if(($_GET['mode']??'vulnerable')==='fixed') session_regenerate_id(true);}
$mode=$_GET['mode']??'vulnerable'; $sid=session_id();
?><!doctype html><html lang="vi"><body style="font-family:Arial;max-width:900px;margin:30px auto"><h1>Session Security Lab</h1><p>Mode: <b><?=htmlspecialchars($mode)?></b></p><p>User: <?=htmlspecialchars($_SESSION['user']??'chưa đăng nhập')?></p><p>Session ID hiện tại: <code><?=htmlspecialchars($sid)?></code></p><p><a href="?login=1&mode=vulnerable">Login vulnerable</a> | <a href="?login=1&mode=fixed">Login fixed</a></p><p>Fixed: regenerate session ID sau authentication, cookie Secure/HttpOnly/SameSite, timeout và logout đúng cách.</p><a href="index.php">← Lab menu</a></body></html>