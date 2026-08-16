<?php
$host=getenv('DB_HOST') ?: 'db';
$username=getenv('DB_USER') ?: 'webbook_app';
$password=getenv('DB_PASSWORD') ?: 'webbook_lab_password';
$dbname=getenv('DB_NAME') ?: 'quanlybanbooks';
try{$ket_noi=new PDO("mysql:host={$host};dbname={$dbname};charset=utf8mb4",$username,$password,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);}catch(PDOException $e){http_response_code(503);error_log('[DB-CONNECTION-ERROR] '.$e->getMessage());die('Database service unavailable.');}
?>