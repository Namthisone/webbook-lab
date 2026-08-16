# Đề 5 — Security Audit & Hardening Lab

## 1. Kiến trúc
```text
Browser / Security Testing VM
          |
       HTTPS :8443
          |
   Apache + ModSecurity
          |
    OWASP CRS + PHP 8.2
          |
      PDO / MySQL
          |
      MariaDB 10.11

Apache/PHP logs ---> Fail2ban ---> host firewall block
```

## 2. Khởi động sạch
```bash
docker compose down -v
docker compose up -d --build
```
Mở `https://localhost:8443/security-lab/`. Chứng chỉ tự ký nên trình duyệt cảnh báo lần đầu.

## 3. Before/After đúng yêu cầu đề

**Before — application vulnerable + WAF DetectionOnly:**
```bash
./tools/waf-mode.sh off
```
Sau đó thực hiện từng scenario với `mode=vulnerable` và lưu screenshot Request/Response.

**After — secure coding + WAF blocking:**
```bash
./tools/waf-mode.sh on
```
Thực hiện lại cùng scenario với `mode=fixed`, đồng thời thử request có dấu hiệu tấn công để chứng minh CRS block. Kiểm tra status và log thực tế.

Kiểm tra trạng thái:
```bash
./tools/waf-mode.sh status
```

## 4. Bộ kiểm thử
| ID | Scenario | Vulnerable | Hardened | Evidence |
|---|---|---|---|---|
| SQL-01 | Basic SQLi | `sqli.php?type=basic&mode=vulnerable` | `mode=fixed` | response + audit + WAF |
| SQL-02 | UNION-style | `type=union` | fixed | response + status |
| SQL-03 | Error-based | `type=error` | fixed | error/status |
| SQL-04 | Blind Boolean | `type=blind` | fixed | TRUE/FALSE |
| SQL-05 | Time-based | `type=time` | fixed | elapsed time |
| XSS-01 | Reflected | `xss.php` | fixed | browser + response |
| XSS-02 | Stored | `xss.php` POST | fixed | stored output |
| XSS-03 | DOM | `xss-dom.php` | fixed | DOM sink |
| CMD-01 | OS Command | `command.php` | fixed | output/status |
| IDOR-01 | Object authorization | `idor.php` | fixed | 403/allowed |
| CSRF-01 | State change | `csrf.php` | fixed | token rejection |
| SES-01 | Session fixation | `session.php` | fixed | session ID change |

> Chỉ thử payloads trên localhost/VM được phép. Trong báo cáo ghi chính xác request bạn đã thực hiện và kết quả thực tế.

## 5. ModSecurity / OWASP CRS

ModSecurity mặc định được build với `SecRuleEngine On` và audit log tại `/var/log/apache2/modsec_audit.log`.

```bash
docker compose exec web sh -c 'grep -E "^SecRuleEngine|^SecAudit" /etc/modsecurity/modsecurity.conf'
docker compose exec web sh -c 'tail -n 80 /var/log/apache2/modsec_audit.log'
```

Khi CRS chặn request, ghi lại **HTTP 403 + rule ID/message trong audit log**. Không kết luận dựa trên một payload cố định; dùng log của chính môi trường lab.

## 6. Fail2ban / brute-force

Ứng dụng ghi `[WEBBOOK-LOGIN-FAIL]` vào Apache/PHP error log. Jail `webbook-login` dùng `maxretry=5`, `findtime=300`, `bantime=600`.

```bash
docker compose logs fail2ban
grep -R "WEBBOOK-LOGIN-FAIL" apache-logs/
```

Trong video: nhập sai mật khẩu nhiều lần → log tăng → Fail2ban phát hiện → jail/block. Chỉ dùng IP của máy lab.

## 7. Security headers / HTTPS
```bash
curl -k -I https://localhost:8443/
```
Kiểm tra: `Content-Security-Policy`, `X-Frame-Options`, `X-Content-Type-Options`, `Strict-Transport-Security`, `Referrer-Policy`, `Permissions-Policy`.

Cookie PHP: kiểm tra `Secure`, `HttpOnly`, `SameSite` trong DevTools.

## 8. Audit evidence

Mở `https://localhost:8443/security-lab/audit.php`.

Bảng lưu: scenario, vulnerable/fixed mode, method, path, status code, result, notes. Đây là nguồn để chụp ảnh phần Verification. Script tổng hợp:

```bash
bash tools/verify-security.sh
```

## 9. Sơ đồ báo cáo

```text
Internet/Client (lab)
        |
     HTTPS/TLS
        |
 [Apache Web Server]
        |
 [ModSecurity + CRS]
        |
 [PHP Secure Coding]
   /         |        \
 SQLi      XSS       Auth/CSRF/IDOR
        |
     [PDO]
        |
    [MariaDB]
        |
 Apache/PHP Logs --> Fail2ban
```

## 10. Cấu trúc báo cáo
1. Giới thiệu/phạm vi
2. Môi trường Ubuntu/Docker/Apache/PHP/MariaDB
3. Sơ đồ kiến trúc
4. Threat model
5. Pentest Before
6. SQL Injection 5 kịch bản
7. XSS 3 kịch bản
8. OS Command Injection
9. IDOR
10. CSRF
11. Session Fixation/Session Security
12. Secure Coding
13. HTTPS/TLS
14. Security Headers
15. ModSecurity + OWASP CRS
16. Fail2ban + log monitoring
17. Verification After
18. Before/After comparison
19. Kết luận
20. Phụ lục Request/Response, log, screenshot, video

## 11. Bảng Before/After
| Test | Before | After | HTTP | Evidence |
|---|---|---|---|---|
| SQLi | vulnerable app có thể bị tác động | prepared statement / WAF | 403 hoặc safe | audit + CRS |
| XSS | raw output | encoded output | safe | browser + audit |
| Command | shell nhận input | allowlist/escaping | blocked/safe | log |
| IDOR | object khác có thể đọc | authorization | 403 | audit |
| CSRF | state đổi không token | token bắt buộc | 403 | audit |
| Session | ID giữ nguyên | regenerate | 200 | session evidence |
