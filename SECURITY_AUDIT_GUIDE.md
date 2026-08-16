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

Apache logs ---> Fail2ban ---> block abusive source
```

## 2. Khởi động

```bash
docker compose down -v
docker compose up -d --build
```

Mở `https://localhost:8443/security-lab/`. Chứng chỉ tự ký nên trình duyệt sẽ cảnh báo lần đầu.

## 3. Bộ kiểm thử

| ID | Scenario | Vulnerable | Hardened | Evidence |
|---|---|---|---|---|
| SQL-01 | Basic SQLi | `sqli.php?type=basic&mode=vulnerable` | `mode=fixed` | response + audit + WAF log |
| SQL-02 | UNION-style | `type=union` | fixed | response + status |
| SQL-03 | Error-based | `type=error` | fixed | error/status |
| SQL-04 | Blind Boolean | `type=blind` | fixed | TRUE/FALSE |
| SQL-05 | Time-based | `type=time` | fixed | elapsed time |
| XSS-01 | Reflected | `xss.php` | fixed | browser + response |
| XSS-02 | Stored | `xss.php` POST | fixed | stored output |
| XSS-03 | DOM | `xss-dom.php` | fixed | DOM sink |
| CMD-01 | OS Command | `command.php` | fixed | command output/status |
| IDOR-01 | Object authorization | `idor.php` | fixed | 403/allowed |
| CSRF-01 | State change | `csrf.php` | fixed | token rejection |
| SES-01 | Session fixation | `session.php` | fixed | session ID change |

> Payloads được thử nghiệm chỉ trong localhost/VM. Ghi đúng payload bạn dùng trong biên bản của nhóm, không thử trên hệ thống bên ngoài phạm vi được phép.

## 4. WAF verification

ModSecurity chạy ở `SecRuleEngine On`. Khi gửi một request rõ ràng có dấu hiệu SQLi/XSS, kiểm tra:

```bash
docker compose exec web sh -c 'grep -Ei "ModSecurity|Access denied|SQL Injection|XSS" /var/log/apache2/*'
```

HTTP status thường cần ghi lại là `403` khi CRS chặn request. Không coi một payload cụ thể là bằng chứng duy nhất; dùng log thực tế làm bằng chứng.

## 5. Fail2ban verification

Đăng nhập sai nhiều lần vào `/dang_nhap.php`, sau đó kiểm tra:

```bash
docker compose logs fail2ban
```

và log Apache/PHP:

```bash
grep -R "WEBBOOK-LOGIN-FAIL" apache-logs/
```

Ghi thời điểm, IP lab, số lần thất bại và trạng thái jail vào báo cáo.

## 6. Security headers

```bash
curl -k -I https://localhost:8443/
```

Cần kiểm tra tối thiểu:

- `Content-Security-Policy`
- `X-Frame-Options: DENY`
- `X-Content-Type-Options: nosniff`
- `Strict-Transport-Security`
- `Referrer-Policy`
- `Permissions-Policy`

Cookie PHP cần kiểm tra `Secure`, `HttpOnly`, `SameSite` bằng DevTools → Application/Storage → Cookies.

## 7. Audit evidence

Mở:

`https://localhost:8443/security-lab/audit.php`

Trang này lưu scenario, vulnerable/fixed mode, HTTP method/path, status code, kết quả và ghi chú. Chụp màn hình bảng này cho phần Verification của báo cáo.

## 8. Cấu trúc báo cáo

1. Giới thiệu và phạm vi kiểm toán
2. Môi trường Ubuntu/Docker/Apache/PHP/MariaDB
3. Sơ đồ kiến trúc bảo mật
4. Threat model và OWASP Top 10 liên quan
5. Pentest trước hardening
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
17. Verification sau hardening
18. So sánh Before/After
19. Kết luận
20. Phụ lục request/response, log và ảnh/video

## 9. Bảng Before/After mẫu

| Test | Before | After | HTTP | WAF/Log |
|---|---|---|---|---|
| SQLi | khai thác được trong vulnerable lab | prepared statement / WAF block | 403 hoặc safe response | CRS alert |
| XSS | script được phản ánh/lưu trong vulnerable lab | encoded | safe response | CRS nếu rule match |
| Command | shell nhận input | allowlist + escaping | blocked/safe | Apache log |
| IDOR | xem object khác | authorization check | 403 | audit |
| CSRF | state thay đổi | thiếu token bị từ chối | 403 | audit |
| Session | ID giữ nguyên | regenerate sau login | 200 | audit |
