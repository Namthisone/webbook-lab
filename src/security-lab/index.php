<?php
session_start();
?><!doctype html>
<html lang="vi">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>WebBook Security Lab</title>
<style>body{font-family:Arial;background:#0f172a;color:#e2e8f0;max-width:1100px;margin:40px auto;padding:20px}a{color:#38bdf8}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}.card{background:#1e293b;padding:20px;border-radius:12px;border:1px solid #334155}.tag{color:#fbbf24}.ok{color:#4ade80}.warn{color:#fb7185}</style></head>
<body>
<h1>🔐 WebBook – Advanced Web Security Lab</h1>
<p class="warn"><b>Chỉ dùng trong localhost/VM lab.</b> Các trang "Vulnerable" cố ý chứa lỗi để phục vụ bài kiểm thử và so sánh trước/sau hardening.</p>
<div class="grid">
<div class="card"><h2>1. SQL Injection</h2><p>In-band/UNION-style, error-based và blind-style thử nghiệm.</p><a href="sqli.php">Mở SQLi Lab →</a></div>
<div class="card"><h2>2. XSS</h2><p>Reflected + Stored + DOM-based, có khu vực vulnerable/fixed.</p><a href="xss.php">Mở XSS Lab →</a></div>
<div class="card"><h2>3. OS Command Injection</h2><p>Input đi vào shell ở bản vulnerable; bản fixed dùng allowlist.</p><a href="command.php">Mở Command Lab →</a></div>
<div class="card"><h2>4. IDOR</h2><p>Truy cập object theo ID không kiểm tra ownership.</p><a href="idor.php">Mở IDOR Lab →</a></div>
<div class="card"><h2>5. CSRF</h2><p>State-changing request không token vs có token.</p><a href="csrf.php">Mở CSRF Lab →</a></div>
<div class="card"><h2>6. Session</h2><p>Session fixation/hijacking demo và phiên bản regenerate ID.</p><a href="session.php">Mở Session Lab →</a></div>
<div class="card"><h2>7. Secure Coding</h2><p>Prepared statements, validation, encoding, CSRF và RBAC.</p><a href="secure.php">Mở Secure Coding →</a></div>
</div>
<p><a href="../index.php">← Quay về WebBook</a></p>
</body></html>