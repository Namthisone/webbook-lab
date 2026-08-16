# HƯỚNG DẪN THỰC HÀNH CHI TIẾT – ĐỀ 5

## KIỂM TRA & TỐI ƯU HÓA AN TOÀN ỨNG DỤNG WEB NÂNG CAO

> **Mục đích:** tài liệu này hướng dẫn chạy WebBook Security Lab, thực hành các nhóm lỗ hổng trong phạm vi localhost/VM, áp dụng Secure Coding + HTTPS + Security Headers + ModSecurity/OWASP CRS + Fail2ban và thu thập bằng chứng Before/After cho báo cáo.
>
> **Cảnh báo:** toàn bộ module vulnerable chỉ dùng trong máy ảo/lab được kiểm soát. Không đưa ứng dụng vulnerable lên Internet và không thử payload trên hệ thống không được phép.

---

## 1. ĐỐI CHIẾU YÊU CẦU ĐỀ 5

| Mã | Yêu cầu | Module/bằng chứng cần có |
|---|---|---|
| INF-01 | Ubuntu/Windows + Docker/Native | Docker Compose |
| INF-02 | Apache + PHP 8.x + MySQL/MariaDB | `Dockerfile`, `docker-compose.yml` |
| INF-03 | Ứng dụng mục tiêu có lỗi | `/security-lab/` |
| VUL-01 | SQLi In-band/UNION | `sqli.php` |
| VUL-02 | SQLi Error-based | `sqli.php` |
| VUL-03 | SQLi Blind Boolean | `sqli.php` |
| VUL-04 | SQLi Time-based | `sqli.php` |
| VUL-05 | OS Command Injection | `command.php` |
| VUL-06 | Reflected XSS | `xss.php` |
| VUL-07 | Stored XSS | `xss.php` |
| VUL-08 | DOM XSS | `dom-xss.php` |
| VUL-09 | Filter bypass | testcase XSS trong lab; ghi rõ giới hạn |
| VUL-10 | Session Fixation | `session.php` |
| VUL-11 | Session Hijacking | `session-hijack.php` – simulation an toàn |
| VUL-12 | IDOR | `idor.php` |
| DEF-01 | Prepared Statements | Secure Coding + fixed branches |
| DEF-02 | Input Validation | fixed branches |
| DEF-03 | Context-aware Output Encoding | XSS fixed/DOM fixed |
| DEF-04 | Anti-CSRF | `csrf.php` |
| HARD-01 | HTTPS/TLS | Apache SSL |
| HARD-02 | Security Headers | Apache headers |
| HARD-03 | HttpOnly/Secure/SameSite | PHP/session configuration |
| HARD-04 | ModSecurity | Apache `security2_module` |
| HARD-05 | OWASP CRS | CRS rules |
| HARD-06 | Fail2ban | jail/filter + logs |
| VER-01 | Test lại sau hardening | Before/After matrix |
| VER-02 | HTTP status | curl/browser capture |
| VER-03 | ModSecurity alert | audit log |
| VER-04 | Fail2ban block | fail2ban status/log |
| REP-01 | Sơ đồ kiến trúc | mục 11 |
| REP-02 | Pentest/Audit Log | Audit Dashboard + screenshots |
| REP-03 | Request/Response | mục 9 |
| REP-04 | Cấu hình | mục 12 |
| REP-05 | Video | mục 13 |

> **Quy tắc:** không đánh dấu một testcase là “đã hoàn thành” chỉ vì có file PHP. Phải chạy testcase và lưu bằng chứng.

---

# 2. KIẾN TRÚC LAB

```text
                         HOST / WINDOWS
                              │
                              │ Browser
                              ▼
                    ┌───────────────────┐
                    │ HTTPS :8443       │
                    │ Apache + TLS      │
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │ ModSecurity + CRS │
                    │ WAF               │
                    └─────────┬─────────┘
                              │
                    ┌─────────▼─────────┐
                    │ PHP 8.x           │
                    │ WebBook            │
                    │ Security Lab      │
                    └─────────┬─────────┘
                              │ PDO
                    ┌─────────▼─────────┐
                    │ MariaDB           │
                    └───────────────────┘

       Apache/PHP logs ─────► Fail2ban ─────► temporary ban
                              │
                              ▼
                       Audit / Evidence
```

---

# 3. CHUẨN BỊ MÔI TRƯỜNG

## 3.1. Ubuntu

Cập nhật hệ thống:

```bash
sudo apt update
sudo apt install -y git curl ca-certificates
```

Kiểm tra Docker:

```bash
docker --version
sudo docker compose version
```

Nếu Docker chưa có, cài Docker Engine/Docker Compose theo tài liệu chính thức của Docker.

## 3.2. Clone project

```bash
git clone https://github.com/Namthisone/webbook-lab.git
cd webbook-lab
```

Kiểm tra:

```bash
ls -la
find . -maxdepth 2 -type f | sort
```

---

# 4. KHỞI ĐỘNG ỨNG DỤNG

## 4.1. Build lần đầu

```bash
sudo docker compose down
sudo docker compose up -d --build
```

Kiểm tra:

```bash
sudo docker compose ps
```

Xem log:

```bash
sudo docker compose logs --tail=100 web
sudo docker compose logs --tail=100 db
```

## 4.2. Nếu muốn reset hoàn toàn database lab

> Lệnh này xóa volume database của Compose. Chỉ dùng khi không cần dữ liệu cũ.

```bash
sudo docker compose down -v
sudo docker compose up -d --build
```

---

# 5. TRUY CẬP WEB

HTTP:

```text
http://localhost:8080/
```

HTTPS:

```text
https://localhost:8443/
```

Security Lab:

```text
https://localhost:8443/security-lab/
```

Audit:

```text
https://localhost:8443/security-lab/audit.php
```

Nếu dùng máy thật truy cập VM, thay `localhost` bằng IP của Ubuntu VM:

```bash
ip addr
```

Sau đó dùng:

```text
https://IP_UBUNTU:8443/security-lab/
```

Chứng chỉ tự ký có thể làm trình duyệt cảnh báo. Đây là hành vi bình thường trong lab.

---

# 6. KIỂM TRA HẠ TẦNG TRƯỚC KHI PENTEST

## 6.1. Apache

```bash
sudo docker compose exec web apachectl -t
sudo docker compose exec web apachectl -M | grep security
```

Mong muốn có `security2_module`.

## 6.2. PHP

```bash
sudo docker compose exec web php -v
```

## 6.3. MariaDB

```bash
sudo docker compose exec db mariadb -u root -p -e 'SHOW DATABASES;'
```

Dùng credential đúng trong `.env`/Compose của project; không ghi mật khẩu thật vào báo cáo công khai.

## 6.4. ModSecurity/CRS

```bash
sudo docker compose exec web grep -R "SecRuleEngine" /etc/modsecurity.d 2>/dev/null | head
sudo docker compose exec web find /etc/modsecurity.d -maxdepth 3 -type f | grep -i crs | head -20
```

## 6.5. Logs

```bash
sudo docker compose exec web ls -lh /var/log/apache2/
sudo docker compose logs --tail=100 fail2ban
```

---

# 7. CẤU TRÚC SECURITY LAB

Mở:

```text
https://localhost:8443/security-lab/
```

Các module:

```text
1. SQL Injection
2. XSS
3. OS Command Injection
4. IDOR
5. CSRF
6. Session
7. Secure Coding
8. Verification/Audit
```

Mỗi testcase phải chạy theo quy trình:

```text
Vulnerable
   ↓
Ghi payload/test input
   ↓
Ghi HTTP request
   ↓
Ghi HTTP response
   ↓
Chụp màn hình
   ↓
Hardening
   ↓
Chạy lại cùng testcase
   ↓
Ghi status/result
   ↓
Kiểm tra WAF/log
```

---

# 8. THỰC HÀNH TỪNG NHÓM LỖ HỔNG

## 8.1. SQL Injection

Mở:

```text
https://localhost:8443/security-lab/sqli.php
```

Chọn lần lượt:

```text
Basic
UNION
Error
Blind
Time
```

### SQLi-01: Basic

Mục tiêu: chứng minh dữ liệu đầu vào được đưa vào câu SQL theo cách không an toàn ở chế độ vulnerable.

Ghi:

```text
Test ID: SQLi-01
Mode: vulnerable
Method: GET
Parameter: id
Expected: truy vấn bị ảnh hưởng bởi input
```

Không cần dùng dữ liệu ngoài lab. Chỉ sử dụng input thử nghiệm trong database fixture của project.

### SQLi-02: UNION

Mục tiêu: chứng minh tác động của việc ghép chuỗi SQL và so sánh với prepared statement.

Báo cáo cần có:

```text
Input/test case
Request
Response
Database result
Fixed response
```

### SQLi-03: Error-based

Mục tiêu: tạo điều kiện để ứng dụng vulnerable phản hồi khác biệt khi câu SQL không hợp lệ; ghi lại error nếu lab cho phép.

Ở bản fixed, không để lỗi SQL chi tiết lộ cho người dùng.

### SQLi-04: Blind Boolean

Mục tiêu: quan sát sự khác biệt giữa điều kiện đúng và sai thông qua response.

Ghi:

```text
Condition TRUE → response
Condition FALSE → response
Fixed → response không cho phép điều khiển SQL
```

### SQLi-05: Time-based

Mục tiêu: chứng minh timing difference trong lab khi input làm DB xử lý chậm.

Ghi:

```text
Normal request: ___ ms
Test request: ___ ms
Fixed request: ___ ms
```

> Không chạy timing payload trên hệ thống ngoài lab.

### Sau khi hardening

Kiểm tra code fixed phải dùng:

```php
$stmt = $pdo->prepare('SELECT ... WHERE id = ?');
$stmt->execute([$id]);
```

Không ghép trực tiếp input vào SQL.

---

## 8.2. Reflected XSS

Mở:

```text
https://localhost:8443/security-lab/xss.php
```

Thực hiện trong chế độ vulnerable bằng input HTML/JavaScript vô hại trong localhost lab để chứng minh output được đưa vào HTML mà không encode.

Ghi:

```text
XSS-01
Input
Request URL
Response
Browser result
```

Chuyển `fixed` và chạy lại.

Kết quả mong muốn:

```text
Input được encode
→ không thực thi như HTML/JS
```

---

## 8.3. Stored XSS

Trong `xss.php` nhập một comment thử nghiệm.

So sánh:

```text
Vulnerable → nội dung lưu và render không an toàn
Fixed → nội dung được encode khi xuất
```

Chụp:

1. Form input.
2. Request POST.
3. Stored result.
4. Fixed result.

---

## 8.4. DOM-based XSS

Mở:

```text
https://localhost:8443/security-lab/dom-xss.php
```

Vulnerable dùng DOM sink không an toàn.

Fixed dùng text-only output.

Điểm cần ghi trong báo cáo:

```text
Source: URL/query input
Sink vulnerable: innerHTML
Sink fixed: textContent
```

---

## 8.5. OS Command Injection

Mở:

```text
https://localhost:8443/security-lab/command.php
```

Mục tiêu là chứng minh sự khác biệt giữa việc đưa input trực tiếp vào command và allowlist/escaping ở bản fixed.

Chỉ sử dụng các lệnh harmless trong container lab.

Ghi:

```text
CMD-01
Input
Command/result
HTTP status
Fixed result
```

Không sử dụng payload nhằm phá hoại, persistence hoặc truy cập hệ thống ngoài lab.

---

## 8.6. IDOR

Mở:

```text
https://localhost:8443/security-lab/idor.php
```

Lab có các user/object fixture.

Thực hành:

```text
User A → object của A → 200
User A → object của B → vulnerable có thể đọc
User A → object của B → fixed phải từ chối
Admin → object → được phép theo RBAC
```

Kết quả fixed mong muốn:

```text
HTTP 403 hoặc response từ chối tương đương
```

---

## 8.7. CSRF

Mở:

```text
https://localhost:8443/security-lab/csrf.php
```

So sánh:

```text
Vulnerable:
POST state-changing request không kiểm tra token

Fixed:
POST
  ↓
CSRF token
  ↓
hash_equals()
  ↓
valid → tiếp tục
invalid → 403
```

Chụp request có token và request thiếu/sai token.

---

## 8.8. Session Fixation

Mở:

```text
https://localhost:8443/security-lab/session.php
```

Mục tiêu:

```text
Vulnerable → session ID không được rotate đúng lúc
Fixed → session_regenerate_id(true)
```

Ghi session ID **chỉ của lab**, không ghi cookie thật của người dùng vào báo cáo.

---

## 8.9. Session Hijacking – Simulation

Mở:

```text
https://localhost:8443/security-lab/session-hijack.php
```

Đây là simulation an toàn để chứng minh nguyên lý:

```text
Authentication
     ↓
Vulnerable: giữ session state
Fixed: regenerate session ID
```

Dùng hai profile trình duyệt chỉ dành cho localhost nếu cần minh họa. Không sao chép cookie của tài khoản thật.

---

# 9. GHI REQUEST/RESPONSE CHO BÁO CÁO

Có thể dùng `curl` để lấy header/status.

Ví dụ:

```bash
curl -k -i 'https://localhost:8443/security-lab/'
```

Ghi:

```text
REQUEST
GET /security-lab/ HTTP/1.1
Host: localhost:8443

RESPONSE
HTTP/1.1 200 OK
Content-Type: text/html; charset=UTF-8
...
```

Kiểm tra header:

```bash
curl -k -I https://localhost:8443/
```

Các header cần kiểm tra:

```text
Content-Security-Policy
X-Frame-Options
X-Content-Type-Options
Strict-Transport-Security
Referrer-Policy
Set-Cookie
```

---

# 10. HARDENING

## 10.1. Prepared Statements

Mọi truy vấn nhận input phải ưu tiên:

```php
$stmt = $pdo->prepare('SELECT ... WHERE id = ?');
$stmt->execute([$id]);
```

Không dùng:

```php
$sql = "SELECT ... WHERE id = '$id'";
```

## 10.2. Input Validation

Ví dụ ID số:

```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    http_response_code(400);
    exit('Invalid input');
}
```

Validation phải phù hợp kiểu dữ liệu và business rule.

## 10.3. Output Encoding

HTML context:

```php
htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
```

JavaScript context không được tùy tiện nối chuỗi; ưu tiên `json_encode()` với flags phù hợp hoặc đưa dữ liệu vào DOM bằng `textContent`.

URL/attribute/CSS phải dùng encoding phù hợp với đúng context.

## 10.4. CSRF

Mọi state-changing request phải có token:

```text
POST
PUT
DELETE
```

GET không nên dùng cho thao tác thay đổi trạng thái.

## 10.5. Session

Khuyến nghị:

```text
HttpOnly
Secure
SameSite=Lax/Strict phù hợp
session_regenerate_id(true)
logout destroys session
```

---

# 11. HTTPS + SECURITY HEADERS

Kiểm tra:

```bash
curl -k -I https://localhost:8443/
```

Bảng bằng chứng:

| Header | Kỳ vọng |
|---|---|
| CSP | Có |
| X-Frame-Options | DENY/SAMEORIGIN |
| X-Content-Type-Options | nosniff |
| HSTS | Có khi chạy HTTPS |
| Referrer-Policy | Có |
| Set-Cookie | HttpOnly/Secure/SameSite |

> HSTS chỉ nên bật cho HTTPS; trong lab self-signed cần giải thích rõ môi trường thử nghiệm.

---

# 12. MODSECURITY + OWASP CRS

Kiểm tra module:

```bash
sudo docker compose exec web apachectl -M | grep security
```

Kiểm tra engine:

```bash
sudo docker compose exec web grep -R "SecRuleEngine" /etc/modsecurity.d 2>/dev/null
```

Kiểm tra audit log:

```bash
sudo docker compose exec web tail -n 50 /var/log/apache2/modsec_audit.log
```

### Before

Dùng script/cấu hình của project để đưa WAF vào chế độ phù hợp cho giai đoạn quan sát.

Ghi:

```text
Request
Status
Detection/log
```

### After

Bật blocking:

```text
SecRuleEngine On
```

Chạy lại testcase.

Kết quả mong muốn với testcase bị CRS chặn:

```text
HTTP 403
ModSecurity audit log có event
```

Kiểm tra:

```bash
sudo docker compose exec web tail -f /var/log/apache2/modsec_audit.log
```

> Không phải mọi lỗi ứng dụng đều phải do WAF chặn. IDOR/CSRF/RBAC phải được bảo vệ ở application layer; WAF là một lớp defense-in-depth.

---

# 13. FAIL2BAN

Kiểm tra container:

```bash
sudo docker compose ps fail2ban
```

Nếu có shell trong container:

```bash
sudo docker compose exec fail2ban fail2ban-client status
```

Kiểm tra jail cụ thể theo tên cấu hình trong project:

```bash
sudo docker compose exec fail2ban fail2ban-client status <JAIL_NAME>
```

Kiểm tra log:

```bash
sudo docker compose logs --tail=100 fail2ban
```

Thực hành brute-force **chỉ với tài khoản lab** và số lần giới hạn theo cấu hình.

Bằng chứng cần chụp:

```text
failed login count
jail status
banned IP
fail2ban log
```

Không dùng IP của người khác và không thử trên Internet.

---

# 14. BEFORE / AFTER – QUY TRÌNH CHUẨN

Mỗi testcase tạo một dòng:

| ID | Vulnerability | Before | Hardening | After | WAF/App result |
|---|---|---|---|---|---|
| SQLi-01 | Basic SQLi | Ảnh + status | Prepared Statement | Ảnh + status | Safe/Blocked |
| SQLi-02 | UNION | Ảnh + status | Prepared Statement | Ảnh + status | Safe/Blocked |
| SQLi-03 | Error | Error evidence | Generic DB error | Safe | Safe/Blocked |
| SQLi-04 | Blind | TRUE/FALSE | Prepared Statement | Safe | Safe/Blocked |
| SQLi-05 | Time | Timing | Prepared Statement | Normal timing | Safe/Blocked |
| XSS-01 | Reflected | Executed/rendered | Encoding | Text | Safe |
| XSS-02 | Stored | Stored/rendered | Encoding | Text | Safe |
| XSS-03 | DOM | DOM sink | textContent | Text | Safe |
| CMD-01 | Command | Command effect | Allowlist | Rejected | Safe/Blocked |
| IDOR-01 | IDOR | Object exposed | Ownership check | 403 | App block |
| CSRF-01 | CSRF | State change | Token | 403 | App block |
| SESS-01 | Fixation | Same session | Regenerate | New ID | Mitigated |
| SESS-02 | Hijacking | Simulation | Regenerate/flags | Mitigated | App block |

> Điền số liệu thực tế sau khi chạy, không điền giả.

---

# 15. AUDIT DASHBOARD

Mở:

```text
https://localhost:8443/security-lab/audit.php
```

Mỗi testcase nên có record:

```text
Scenario
Mode
HTTP method
Path
Status code
Result
Notes
Timestamp
```

Nếu bản code hiện tại chưa ghi được payload/full request-response, hãy lưu chúng bằng file evidence hoặc Burp/ZAP/DevTools và ghi Test ID trùng với Audit Dashboard.

Cấu trúc evidence đề nghị:

```text
evidence/
├── 01-infrastructure/
├── 02-sqli/
│   ├── before/
│   └── after/
├── 03-xss/
│   ├── before/
│   └── after/
├── 04-command/
├── 05-idor/
├── 06-csrf/
├── 07-session/
├── 08-waf/
├── 09-fail2ban/
└── 10-headers-https/
```

Không commit cookie thật, password thật, private key ngoài lab hoặc dữ liệu cá nhân vào Git.

---

# 16. VIDEO DEMO – KỊCH BẢN

## Video 1: SQL Injection trước/sau

1. Mở Security Lab.
2. Chọn SQLi.
3. Chọn vulnerable.
4. Thực hiện testcase an toàn trong database lab.
5. Quay Request/Response.
6. Mở source fixed/giải thích Prepared Statement.
7. Chuyển fixed.
8. Chạy lại cùng testcase.
9. Quay response.
10. Mở ModSecurity log.

## Video 2: XSS

1. Reflected vulnerable.
2. Stored vulnerable.
3. DOM vulnerable.
4. Chuyển fixed.
5. Chứng minh encoding/textContent.
6. Kiểm tra CSP.

## Video 3: WAF

1. Kiểm tra ModSecurity ON.
2. Chạy testcase có dấu hiệu injection trong lab.
3. Nhận HTTP 403 nếu CRS chặn.
4. Mở `modsec_audit.log`.
5. Chỉ ra event/rule.

## Video 4: Fail2ban

1. Mở tài khoản lab.
2. Thực hiện số lần đăng nhập sai giới hạn.
3. Kiểm tra jail.
4. Hiển thị banned IP.
5. Hiển thị log.

---

# 17. CÁC LỆNH KIỂM TRA NHANH

```bash
# trạng thái toàn hệ thống
sudo docker compose ps

# log web
sudo docker compose logs --tail=100 web

# log DB
sudo docker compose logs --tail=100 db

# log fail2ban
sudo docker compose logs --tail=100 fail2ban

# PHP
sudo docker compose exec web php -v

# Apache syntax
sudo docker compose exec web apachectl -t

# Apache modules
sudo docker compose exec web apachectl -M

# HTTPS headers
curl -k -I https://localhost:8443/

# Security Lab
curl -k -I https://localhost:8443/security-lab/

# ModSecurity log
sudo docker compose exec web tail -n 50 /var/log/apache2/modsec_audit.log
```

---

# 18. XỬ LÝ LỖI THƯỜNG GẶP

## Port đã được sử dụng

```bash
sudo ss -lntp | grep -E ':8080|:8443'
```

Nếu port bị service khác dùng, dừng service đó hoặc đổi port Compose.

## Container không chạy

```bash
sudo docker compose ps
sudo docker compose logs --tail=200 web
```

## Apache không khởi động

```bash
sudo docker compose exec web apachectl -t
```

## Database lỗi

```bash
sudo docker compose logs --tail=200 db
```

Nếu database lab bị hỏng và không cần dữ liệu:

```bash
sudo docker compose down -v
sudo docker compose up -d --build
```

## HTTPS không truy cập được

```bash
sudo docker compose ps
sudo ss -lntp | grep 8443
curl -k -I https://localhost:8443/
```

## WAF không chặn

Không kết luận WAF hỏng ngay. Kiểm tra lần lượt:

```bash
sudo docker compose exec web apachectl -M | grep security
sudo docker compose exec web grep -R "SecRuleEngine" /etc/modsecurity.d 2>/dev/null
sudo docker compose exec web tail -n 100 /var/log/apache2/modsec_audit.log
```

---

# 19. CHECKLIST TRƯỚC KHI NỘP

## Hạ tầng

- [ ] Ubuntu/Windows đã cài Docker
- [ ] Web container chạy
- [ ] Database chạy
- [ ] HTTPS chạy
- [ ] Apache chạy
- [ ] ModSecurity module chạy
- [ ] CRS được load
- [ ] Fail2ban chạy

## Vulnerability Assessment

- [ ] SQLi Basic
- [ ] SQLi UNION
- [ ] SQLi Error-based
- [ ] SQLi Blind Boolean
- [ ] SQLi Time-based
- [ ] Command Injection
- [ ] Reflected XSS
- [ ] Stored XSS
- [ ] DOM XSS
- [ ] XSS filter/bypass testcase phù hợp
- [ ] Session Fixation
- [ ] Session Hijacking simulation
- [ ] IDOR
- [ ] CSRF

## Secure Coding

- [ ] Prepared Statements
- [ ] Input Validation
- [ ] Output Encoding
- [ ] CSRF Token
- [ ] Authorization/ownership
- [ ] Session regeneration
- [ ] Secure cookie flags

## Hardening

- [ ] HTTPS
- [ ] CSP
- [ ] X-Frame-Options
- [ ] X-Content-Type-Options
- [ ] HSTS
- [ ] HttpOnly
- [ ] Secure
- [ ] SameSite
- [ ] ModSecurity
- [ ] OWASP CRS
- [ ] Fail2ban

## Verification

- [ ] Có testcase BEFORE
- [ ] Có testcase AFTER
- [ ] Có HTTP status
- [ ] Có Request
- [ ] Có Response
- [ ] Có screenshot
- [ ] Có ModSecurity log
- [ ] Có Fail2ban log
- [ ] Có Audit Dashboard
- [ ] Có bảng Before/After

## Báo cáo

- [ ] Sơ đồ kiến trúc
- [ ] Mô tả môi trường
- [ ] Mô tả từng lỗ hổng
- [ ] Payload/test input trong phạm vi lab
- [ ] Request/Response
- [ ] Nguyên nhân
- [ ] Secure Coding
- [ ] WAF
- [ ] Fail2ban
- [ ] Before/After
- [ ] Screenshot
- [ ] Kết luận

## Video

- [ ] Video khai thác SQLi
- [ ] Video XSS
- [ ] Video Command Injection/IDOR/CSRF/Session phù hợp
- [ ] Video WAF block
- [ ] Video Fail2ban block

---

# 20. CẤU TRÚC BÁO CÁO ĐỀ XUẤT

```text
CHƯƠNG 1. GIỚI THIỆU
1.1 Mục tiêu
1.2 Phạm vi
1.3 Môi trường

CHƯƠNG 2. KIẾN TRÚC HỆ THỐNG
2.1 Sơ đồ mạng
2.2 Apache/PHP/MariaDB
2.3 WAF
2.4 Firewall/Fail2ban

CHƯƠNG 3. VULNERABILITY ASSESSMENT
3.1 SQL Injection
3.2 Command Injection
3.3 XSS
3.4 Authentication/Session
3.5 IDOR
3.6 CSRF

CHƯƠNG 4. SECURE CODING
4.1 Prepared Statements
4.2 Validation
4.3 Output Encoding
4.4 CSRF
4.5 Session Security

CHƯƠNG 5. WEB SERVER HARDENING
5.1 HTTPS
5.2 Security Headers
5.3 Cookie Security
5.4 ModSecurity
5.5 OWASP CRS
5.6 Fail2ban

CHƯƠNG 6. VERIFICATION
6.1 Before
6.2 After
6.3 HTTP Response
6.4 ModSecurity Logs
6.5 Fail2ban Logs
6.6 Audit Dashboard

CHƯƠNG 7. KẾT LUẬN
```

---

# 21. NGUYÊN TẮC LẤY BẰNG CHỨNG

Mỗi ảnh nên đặt tên theo Test ID:

```text
SQLi-01-before.png
SQLi-01-after.png
SQLi-01-waf-log.png
XSS-01-before.png
XSS-01-after.png
IDOR-01-before.png
IDOR-01-after.png
CSRF-01-before.png
CSRF-01-after.png
WAF-01-403.png
FAIL2BAN-01-ban.png
```

Mỗi testcase trong báo cáo phải trả lời 5 câu hỏi:

1. Lỗ hổng là gì?
2. Vì sao code vulnerable?
3. Testcase/input trong lab là gì?
4. Sau khi sửa, vì sao không còn khai thác được?
5. Có bằng chứng HTTP/log nào chứng minh?

---

# 22. KẾT LUẬN

Repository này được thiết kế để phục vụ **thực hành kiểm toán và hardening trong môi trường lab**, không phải triển khai ứng dụng vulnerable ra Internet.

Để đạt yêu cầu đồ án, sinh viên phải hoàn thành cả **code + chạy thực tế + bằng chứng + báo cáo + video**. Việc có một file PHP hoặc một cấu hình WAF trong GitHub không tự động chứng minh testcase đã thành công.

**Trình tự làm việc khuyến nghị:**

```text
1. Clone repo
2. Build Docker
3. Kiểm tra hạ tầng
4. Chạy vulnerable tests
5. Lưu evidence
6. Hardening
7. Bật WAF blocking
8. Kiểm tra Fail2ban
9. Chạy lại tests
10. Lưu HTTP/log evidence
11. Điền Audit Dashboard
12. Hoàn thiện báo cáo
13. Quay video
14. Kiểm tra checklist lần cuối
```

**Không đánh dấu hoàn thành testcase nếu chưa có bằng chứng chạy thực tế.**
