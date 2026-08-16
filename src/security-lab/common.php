<?php
$instanceMode = strtolower(getenv('WEBBOOK_SECURITY_MODE') ?: 'attack');
$instanceMode = in_array($instanceMode, ['attack','defense'], true) ? $instanceMode : 'attack';

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params(['httponly'=>true,'secure'=>$https,'samesite'=>'Lax']);
if(session_status()!==PHP_SESSION_ACTIVE) session_start();
require_once __DIR__.'/../db.php';

// The deployment decides the security state. A URL parameter cannot turn a
// defense container back into vulnerable mode.
function lab_mode(): string { global $instanceMode; return $instanceMode==='defense'?'fixed':'vulnerable'; }
function lab_instance(): string { global $instanceMode; return $instanceMode==='defense'?'DEFENSE':'ATTACK'; }
function h($v): string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function audit(string $scenario,string $mode,string $method,string $path,int $status,string $result,string $notes=''):void{global $ket_noi;try{$s=$ket_noi->prepare('INSERT INTO security_lab_audit(scenario,mode,method,path,status_code,result,notes) VALUES(?,?,?,?,?,?,?)');$s->execute([$scenario,$mode,$method,$path,$status,$result,$notes]);}catch(Throwable $e){error_log('[LAB-AUDIT-ERROR] '.$e->getMessage());}}
function ensure_lab_schema():void{global $ket_noi;
$ket_noi->exec("CREATE TABLE IF NOT EXISTS security_lab_users(id INT PRIMARY KEY,username VARCHAR(80) UNIQUE NOT NULL,password_hash VARCHAR(255) NOT NULL,role ENUM('user','admin') NOT NULL DEFAULT 'user') ENGINE=InnoDB");
$ket_noi->exec("CREATE TABLE IF NOT EXISTS security_lab_documents(id INT PRIMARY KEY,owner_id INT NOT NULL,title VARCHAR(200) NOT NULL,secret_text TEXT NOT NULL) ENGINE=InnoDB");
$ket_noi->exec("CREATE TABLE IF NOT EXISTS security_lab_comments(id INT AUTO_INCREMENT PRIMARY KEY,user_id INT NOT NULL,body TEXT NOT NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
$ket_noi->exec("CREATE TABLE IF NOT EXISTS security_lab_audit(id BIGINT AUTO_INCREMENT PRIMARY KEY,scenario VARCHAR(80) NOT NULL,mode VARCHAR(20) NOT NULL,method VARCHAR(10) NOT NULL,path VARCHAR(255) NOT NULL,status_code INT NOT NULL,result VARCHAR(40) NOT NULL,notes VARCHAR(500),created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
if((int)$ket_noi->query('SELECT COUNT(*) FROM security_lab_users')->fetchColumn()===0){$p=password_hash('LabPassword123!',PASSWORD_DEFAULT);$s=$ket_noi->prepare('INSERT INTO security_lab_users(id,username,password_hash,role) VALUES(?,?,?,?)');foreach([[101,'lab_user_a','user'],[102,'lab_user_b','user'],[103,'lab_admin','admin']] as $u)$s->execute([$u[0],$u[1],$p,$u[2]]);}
if((int)$ket_noi->query('SELECT COUNT(*) FROM security_lab_documents')->fetchColumn()===0){$d=$ket_noi->prepare('INSERT INTO security_lab_documents(id,owner_id,title,secret_text) VALUES(?,?,?,?)');foreach([[201,101,'Tài liệu User A','SECRET-A'],[202,102,'Tài liệu User B','SECRET-B'],[203,103,'Tài liệu Admin','SECRET-ADMIN']] as $x)$d->execute($x);}}
ensure_lab_schema();
?>
