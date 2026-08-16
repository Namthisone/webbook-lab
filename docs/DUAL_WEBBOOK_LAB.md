# WEBBOOK SECURITY LAB — HƯỚNG DẪN THỰC HÀNH ĐỒ ÁN ĐỀ 5

> **Mục đích:** tài liệu thao tác từng bước để triển khai, kiểm thử, vá lỗi và thu thập bằng chứng cho đồ án **Advanced Web Security Audit & Hardening** trên chính ứng dụng WebBook/Tạp Hóa Sách.
>
> Lab được thiết kế thành **2 WebBook chạy song song**:
>
> - 🔴 **ATTACK**: cố ý giữ các hành vi vulnerable để thực hành Pentest/Before.
> - 🟢 **DEFENSE**: áp dụng secure coding + HTTPS + headers + ModSecurity/OWASP CRS + logging/Fail2ban để thực hành After.
>
> **Chỉ dùng Attack WebBook trong máy ảo/lab nội bộ. Không đưa bản vulnerable lên Internet hoặc mạng sản xuất.**

---

## 0. Kết quả cuối cùng cần đạt

Sau khi hoàn thành tài liệu này, sinh viên phải có:

```text
Windows Host
    │
    │ VMware NAT/VMnet8
    ▼
Ubuntu Server/VM
    │
    ├── 🔴 Attack WebBook
    │      ├── HTTP  :8081
    │      ├── HTTPS :8444
    │      └── MariaDB :3307 (localhost only)
    │
    └── 🟢 Defense WebBook
           ├── HTTP  :8080
           ├── HTTPS :8443
           └── MariaDB :3308 (localhost only)
```

Báo cáo phải chứng minh được chuỗi:

```text
ATTACK / BEFORE
      ↓
HTTP Request / Payload
      ↓
Lỗ hổng khai thác được
      ↓
Screenshot + HTTP status + log
      ↓
Phân tích source code
      ↓
SECURE CODING / HARDENING
      ↓
DEFENSE / AFTER
      ↓
Gửi lại cùng testcase
      ↓
Blocked / Sanitized / Unauthorized
      ↓
HTTP status + ModSecurity/Fail2ban log
```

---

# PHẦN A — KIỂM TRA MÁY VÀ MÃ NGUỒN

## 1. Kiểm tra Ubuntu

```bash
cat /etc/os-release
php -v
git --version
podman --version || true
docker --version || true
```

Nếu hệ thống dùng Podman giả lập Docker CLI, có thể xuất hiện:

```text
Emulate Docker CLI using podman.
```

Điều đó có nghĩa lệnh `docker` đang được chuyển sang Podman. Khi đó vẫn dùng được `docker compose` nếu compose provider đã được cài đặt.

Kiểm tra:

```bash
sudo docker ps
```

---

## 2. Vào project

```bash
cd ~/webbook-lab
pwd
ls -la
```

Kiểm tra hai compose file:

```bash
ls -l docker-compose.attack.yml docker-compose.defense.yml
```

Phải có cả hai file.

---

## 3. Đồng bộ GitHub an toàn

Nếu `git pull` báo:

```text
error: Your local changes to the following files would be overwritten by merge:
    Dockerfile
```

**Không xóa Dockerfile ngay.** Kiểm tra trước:

```bash
git status
git diff -- Dockerfile
```

Nếu thay đổi local chỉ là thay đổi thử nghiệm và không cần giữ:

```bash
git restore Dockerfile
git pull
```

Nếu muốn giữ thay đổi local:

```bash
git stash push -m "local Dockerfile changes"
git pull
```

Sau khi pull xong có thể xem lại:

```bash
git stash list
git stash show -p stash@{0}
```

**Không commit thư mục log hoặc file build tạm vào repository nếu chúng chỉ phục vụ lab local.**

---

# PHẦN B — KIẾN TRÚC 2 WEBBOOK

## 4. ATTACK WebBook

File:

```text
docker-compose.attack.yml
```

Các thông số:

| Thành phần | ATTACK |
|---|---|
| Container web | `webbook_attack_web` |
| Container DB | `webbook_attack_db` |
| HTTP | `8081` |
| HTTPS | `8444` |
| DB host port | `127.0.0.1:3307` |
| Security mode | `attack` |
| Instance | `ATTACK` |

Khởi động:

```bash
sudo docker compose -f docker-compose.attack.yml up -d --build
```

Kiểm tra:

```bash
sudo docker compose -f docker-compose.attack.yml ps
sudo docker ps
```

---

## 5. DEFENSE WebBook

File:

```text
docker-compose.defense.yml
```

Các thông số:

| Thành phần | DEFENSE |
|---|---|
| Container web | `webbook_defense_web` |
| Container DB | `webbook_defense_db` |
| HTTP | `8080` |
| HTTPS | `8443` |
| DB host port | `127.0.0.1:3308` |
| Security mode | `defense` |
| Instance | `DEFENSE` |

Khởi động:

```bash
sudo docker compose -f docker-compose.defense.yml up -d --build
```

Kiểm tra:

```bash
sudo docker compose -f docker-compose.defense.yml ps
sudo docker ps
```

Khi cả hai hoạt động phải có khoảng **4 container**:

```text
webbook_attack_web
webbook_attack_db
webbook_defense_web
webbook_defense_db
```

---

# PHẦN C — KIỂM TRA WEB TRÊN UBUNTU

## 6. Kiểm tra HTTP

ATTACK:

```bash
curl -I http://127.0.0.1:8081/
```

DEFENSE:

```bash
curl -I http://127.0.0.1:8080/
```

Nếu HTTP chuyển hướng HTTPS, có thể nhận:

```text
HTTP/1.1 301 Moved Permanently
```

Đây không tự động có nghĩa là ứng dụng hỏng.

---

## 7. Kiểm tra HTTPS

ATTACK:

```bash
curl -k -I https://127.0.0.1:8444/
```

DEFENSE:

```bash
curl -k -I https://127.0.0.1:8443/
```

`-k` chỉ dùng cho chứng chỉ self-signed trong lab.

---

## 8. Lỗi redirect `localhost:8443`

Nếu:

```bash
curl -I http://127.0.0.1:8081/
```

trả:

```text
Location: https://localhost:8443/
```

thì Apache đang redirect sai host/port.

Kiểm tra:

```bash
grep -RniE "8443|localhost|Redirect permanent|RewriteRule" --exclude-dir=.git .
```

Đặc biệt kiểm tra:

```text
apache-vhost.conf
```

và trong container:

```bash
sudo docker exec webbook_attack_web sh -c 'grep -RniE "8443|localhost|RewriteRule|Redirect" /etc/apache2 2>/dev/null'
```

Sau khi sửa cấu hình, rebuild:

```bash
sudo docker compose -f docker-compose.attack.yml up -d --build
```

và kiểm tra lại.

---

# PHẦN D — KẾT NỐI WINDOWS → UBUNTU

## 9. Kiểm tra IP Ubuntu

```bash
ip addr
```

Ví dụ Ubuntu của lab:

```text
ens33
inet 192.168.206.140/24
```

IP thực tế có thể khác; luôn dùng IP hiện tại của máy.

---

## 10. VMware NAT / VMnet8

Nếu Windows không truy cập được Ubuntu:

### Trên VMware

```text
VM → Settings → Network Adapter
```

Chọn:

```text
☑ Connected
☑ Connect at power on
● NAT
```

Trong trường hợp dùng VMnet8/NAT, không cần chuyển sang Bridged chỉ để làm lab này.

### Kiểm tra Virtual Network Editor

Mở:

```text
Edit → Virtual Network Editor
```

Chọn:

```text
VMnet8
Type: NAT
```

Nếu có nút **Change Settings**, mở quyền Administrator trước khi chỉnh.

Trên Windows chạy:

```powershell
ncpa.cpl
```

Phải thấy:

```text
VMware Network Adapter VMnet8
```

Nếu adapter bị Disabled → chuột phải → Enable.

---

## 11. Kiểm tra từ Windows

Giả sử Ubuntu là `192.168.206.140`:

```powershell
ping 192.168.206.140
```

Kiểm tra Attack:

```powershell
Test-NetConnection 192.168.206.140 -Port 8081
```

Kiểm tra Defense:

```powershell
Test-NetConnection 192.168.206.140 -Port 8080
```

Kỳ vọng:

```text
TcpTestSucceeded : True
```

---

# PHẦN E — MỞ HAI WEBBOOK

## 12. ATTACK

Trên Windows:

```text
http://IP_UBUNTU:8081/
```

hoặc HTTPS:

```text
https://IP_UBUNTU:8444/
```

Ví dụ:

```text
https://192.168.206.140:8444/
```

Trình duyệt có thể cảnh báo certificate self-signed. Chỉ chấp nhận trong lab nội bộ.

## 13. DEFENSE

```text
http://IP_UBUNTU:8080/
```

hoặc:

```text
https://IP_UBUNTU:8443/
```

Ví dụ:

```text
https://192.168.206.140:8443/
```

---

# PHẦN F — QUY TRÌNH PENTEST BEFORE/AFTER

## 14. Nguyên tắc ghi bằng chứng

Mỗi testcase tạo một thư mục:

```text
evidence/
├── A03-sqli/
├── A03-command-injection/
├── A03-xss-reflected/
├── A03-xss-stored/
├── A03-xss-dom/
├── A01-idor/
├── A07-session-fixation/
├── A07-session-hijacking/
├── A01-csrf/
├── A05-security-headers/
├── A06-https/
├── A09-modsecurity/
└── A09-fail2ban/
```

Mỗi testcase nên lưu:

```text
01-request.txt
02-response.txt
03-before.png
04-source-before.txt
05-defense-source.txt
06-after.png
07-waf-log.txt
08-result.txt
```

Không đưa mật khẩu thật, cookie thật hoặc dữ liệu cá nhân vào báo cáo.

---

# PHẦN G — SQL INJECTION

## 15. Basic SQL Injection

Mục tiêu: chứng minh input được nối trực tiếp vào SQL ở ATTACK.

Dùng testcase đơn giản trong **môi trường Attack WebBook**. Ví dụ chuỗi kiểm thử:

```text
' OR '1'='1
```

Nếu endpoint nhận query parameter, URL-encode payload khi cần.

Quan sát:

- số lượng kết quả thay đổi;
- dữ liệu không thuộc truy vấn bình thường xuất hiện;
- HTTP status;
- response body;
- source code tạo câu SQL.

Sau đó thực hiện cùng testcase trên DEFENSE.

Kỳ vọng Defense:

```text
Prepared Statement
+ input validation
→ payload không được biến thành SQL
```

---

## 16. UNION SQLi

Mục tiêu: minh họa In-band UNION SQLi trong lab.

Một payload minh họa an toàn cho lab:

```text
' UNION SELECT NULL,NULL-- -
```

Số lượng cột phải phù hợp với query của testcase. Không dùng payload này trên hệ thống ngoài lab.

Ghi lại:

```text
Attack → response có dấu hiệu UNION
Defense → prepared statement → không thực thi payload như SQL
```

---

## 17. Error-based SQLi

Gửi input bất thường có ký tự quote:

```text
'
```

Quan sát xem ATTACK có lộ:

- SQL syntax error;
- tên bảng;
- tên cột;
- stack trace;
- đường dẫn source.

Defense phải trả lỗi chung, không lộ chi tiết SQL.

---

## 18. Blind Boolean SQLi

So sánh hai request có điều kiện đúng/sai:

```text
' AND 1=1-- -
```

và:

```text
' AND 1=2-- -
```

So sánh:

- response body;
- số record;
- status;
- thời gian phản hồi.

Mục đích là chứng minh khác biệt TRUE/FALSE trong Attack.

---

## 19. Time-based SQLi

Chỉ thực hiện trong endpoint SQLi lab có kiểm soát và với delay nhỏ.

Mục tiêu báo cáo là chứng minh **thời gian phản hồi thay đổi theo điều kiện**, không phải gây tải hệ thống.

Ghi:

```text
normal request:  ~X ms
controlled delay: ~Y ms
```

Sau khi bật Defense/WAF, request phải bị chặn hoặc không còn tác động tới câu SQL.

---

# PHẦN H — OS COMMAND INJECTION

## 20. Attack

Chỉ thực hiện trong endpoint lab được thiết kế riêng cho command injection.

Ví dụ kiểm thử delimiter đơn giản:

```text
127.0.0.1; whoami
```

hoặc:

```text
127.0.0.1 && whoami
```

Mục tiêu chứng minh input đi vào shell ngoài ý muốn.

**Không thử trên máy thật hoặc endpoint quản trị thật.**

## 21. Defense

Ưu tiên:

```text
Không gọi shell nếu không cần
```

Nếu bắt buộc:

- allowlist input;
- dùng API trực tiếp thay cho shell;
- không ghép chuỗi command;
- chạy process bằng quyền thấp;
- giới hạn environment.

---

# PHẦN I — XSS

## 22. Reflected XSS

Payload lab cơ bản:

```html
<script>alert('XSS-LAB')</script>
```

Thực hiện trên endpoint phản hồi input ngay trong response.

Attack:

```text
input → HTML response → JavaScript thực thi
```

Defense:

```text
input
  ↓
validation
  ↓
context-aware output encoding
  ↓
HTML hiển thị dạng text
```

---

## 23. Stored XSS

Đưa payload vào trường được lưu database, ví dụ nội dung khiếu nại/bình luận nếu lab cung cấp.

Payload lab:

```html
<script>alert('STORED-XSS-LAB')</script>
```

Sau khi lưu, mở lại trang.

Attack thành công khi payload được render thành HTML/JavaScript.

Defense cần:

- encode khi output;
- validation phù hợp;
- CSP là lớp phòng thủ bổ sung, không thay thế output encoding.

---

## 24. DOM-based XSS

Kiểm tra JavaScript phía client sử dụng dữ liệu từ:

```text
location.search
location.hash
location.href
```

và đưa trực tiếp vào:

```javascript
innerHTML
```

Thực hành bằng endpoint DOM-XSS của lab.

Defense ưu tiên:

```javascript
textContent
```

thay vì:

```javascript
innerHTML
```

khi chỉ cần hiển thị text.

---

# PHẦN J — IDOR / ACCESS CONTROL

## 25. IDOR

Ví dụ nếu lab có endpoint:

```text
/xem_don_mua.php?id=1
```

Đăng nhập bằng User A và xác định object của A.

Sau đó thay ID sang object của User B trong **Attack WebBook**.

Mục tiêu chứng minh server chỉ dựa vào ID client gửi lên.

Defense phải kiểm tra ownership ở server:

```text
current_user_id == object.owner_id
```

Nếu không:

```text
HTTP 403 Forbidden
```

Không được chỉ ẩn ID trên giao diện.

---

# PHẦN K — AUTHENTICATION / SESSION

## 26. Session Fixation

Kiểm tra xem session ID có được thay mới sau login hay không.

Quy trình:

```text
Before login → session ID A
Login
After login → session ID ?
```

Defense phải regenerate session ID sau khi xác thực thành công:

```php
session_regenerate_id(true);
```

---

## 27. Session Hijacking — lab nội bộ

Chỉ sử dụng session cookie của **tài khoản test do lab tạo**.

Kiểm tra cookie:

```text
HttpOnly
Secure
SameSite
```

Không đưa cookie thật vào GitHub hoặc báo cáo công khai.

Defense:

```text
HttpOnly
Secure
SameSite=Lax/Strict
session_regenerate_id()
logout/invalidate session
```

---

# PHẦN L — CSRF

## 28. Kiểm tra CSRF

Tìm các request thay đổi trạng thái:

```text
POST
PUT
DELETE
```

Attack testcase: gửi request thay đổi trạng thái mà không có token hợp lệ.

Defense phải yêu cầu token:

```text
CSRF token hợp lệ → cho phép
CSRF token thiếu/sai → từ chối
```

Ví dụ PHP tạo token:

```php
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

Kiểm tra token bằng so sánh timing-safe:

```php
hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')
```

---

# PHẦN M — SECURE CODING

## 29. Prepared Statements

### Không an toàn

```php
$sql = "SELECT * FROM books WHERE id = " . $_GET['id'];
$result = $pdo->query($sql);
```

### Defense

```php
$stmt = $pdo->prepare('SELECT * FROM books WHERE id = :id');
$stmt->execute(['id' => $id]);
```

Ngoài prepared statement vẫn phải validate kiểu dữ liệu:

```php
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
```

---

## 30. Output Encoding

Không đưa input người dùng trực tiếp vào HTML.

Dùng:

```php
htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
```

Lưu ý: encoding phải **đúng context**. HTML, JavaScript, URL và CSS có quy tắc encoding khác nhau.

---

# PHẦN N — HTTPS + SECURITY HEADERS

## 31. HTTPS

Kiểm tra Apache:

```bash
sudo docker exec webbook_defense_web apache2ctl -M | grep ssl
```

Kiểm tra SSL:

```bash
curl -k -I https://127.0.0.1:8443/
```

---

## 32. Security Headers

Kiểm tra:

```bash
curl -k -I https://127.0.0.1:8443/
```

Báo cáo nên kiểm tra ít nhất:

```text
Content-Security-Policy
X-Frame-Options
X-Content-Type-Options
Strict-Transport-Security
Referrer-Policy
Permissions-Policy
```

Kỳ vọng tối thiểu theo đề:

```text
CSP
X-Frame-Options
X-Content-Type-Options
HSTS
```

HSTS chỉ nên bật cho HTTPS deployment mà bạn kiểm soát.

---

# PHẦN O — MODSECURITY + OWASP CRS

## 33. Kiểm tra ModSecurity

```bash
sudo docker exec webbook_defense_web apache2ctl -M | grep security
```

Kiểm tra cấu hình:

```bash
sudo docker exec webbook_defense_web sh -c 'grep -n "SecRuleEngine" /etc/modsecurity/modsecurity.conf'
```

Defense phải có:

```text
SecRuleEngine On
```

---

## 34. Kiểm tra CRS

```bash
sudo docker exec webbook_defense_web sh -c 'ls -la /etc/modsecurity/crs'
```

Kiểm tra rule:

```bash
sudo docker exec webbook_defense_web sh -c 'ls /etc/modsecurity/crs/rules | head'
```

---

## 35. Test WAF

Dùng một request kiểm thử rõ ràng trong lab, ví dụ request có chuỗi SQLi/XSS ở query parameter.

Ví dụ:

```bash
curl -k -i 'https://127.0.0.1:8443/security-lab/sqli.php?id=%27%20OR%20%271%27%3D%271'
```

Kết quả tùy CRS/config có thể là request bị từ chối, thường thuộc nhóm HTTP 4xx.

**Không kết luận WAF hoạt động chỉ vì thấy 403. Phải kiểm tra audit log.**

---

## 36. Xem ModSecurity audit log

Trên host nếu compose mount log:

```bash
sudo ls -la apache-logs/defense/
```

Theo dõi:

```bash
sudo tail -f apache-logs/defense/modsec_audit.log
```

Hoặc trong container:

```bash
sudo docker exec webbook_defense_web sh -c 'tail -n 100 /var/log/apache2/modsec_audit.log'
```

Báo cáo cần chụp:

```text
Request
Rule ID / message
Action
HTTP status
Timestamp
```

---

# PHẦN P — FAIL2BAN / BRUTE-FORCE

## 37. Mục tiêu

Fail2ban dùng log để phát hiện nhiều request/login thất bại và thực hiện ban theo cấu hình.

Kiểm tra trên Ubuntu:

```bash
sudo systemctl status fail2ban
```

Nếu lab chạy Fail2ban trong container hoặc cơ chế tương đương, kiểm tra theo deployment thực tế và ghi rõ trong báo cáo.

---

## 38. Test brute-force có kiểm soát

Chỉ dùng **tài khoản test** và số lượng request nhỏ.

Không chạy stress test hoặc flood.

Ví dụ quy trình:

```text
5–10 login failures
        ↓
log Apache/PHP
        ↓
Fail2ban filter
        ↓
ban action
```

Kiểm tra:

```bash
sudo fail2ban-client status
```

và jail cụ thể:

```bash
sudo fail2ban-client status <jail-name>
```

Nếu Fail2ban được chạy theo container, xem log container tương ứng.

---

# PHẦN Q — AUDIT LOG

## 39. Log cần thu thập

```bash
sudo docker logs webbook_attack_web --tail 100
sudo docker logs webbook_defense_web --tail 100
```

Apache:

```bash
sudo ls -la apache-logs/
```

ModSecurity:

```bash
sudo tail -n 100 apache-logs/defense/modsec_audit.log
```

Mỗi testcase nên ghi:

```text
Test ID:
Ngày/giờ:
URL:
Method:
Payload:
Attack status:
Attack result:
Defense status:
Defense result:
WAF rule/log:
Fail2ban result:
Screenshot:
Kết luận:
```

---

# PHẦN R — MA TRẬN ĐỒ ÁN ĐỀ 5

| Yêu cầu đề | Test/Module | Bằng chứng |
|---|---|---|
| SQLi Basic | SQLi lab | request + response |
| SQLi UNION | SQLi lab | before/after |
| SQLi Error | SQLi lab | error vs generic error |
| Blind Boolean | SQLi lab | TRUE/FALSE response |
| Time-based | SQLi lab | timing |
| OS Command Injection | command lab | before/after |
| Reflected XSS | XSS lab | browser screenshot |
| Stored XSS | complaint/comment lab | stored payload |
| DOM XSS | JS lab | DevTools/browser |
| Session Fixation | auth/session lab | session ID before/after |
| Session Hijacking | controlled test account | cookie/session evidence |
| IDOR | object endpoints | 403 after defense |
| Prepared Statements | PHP source | code comparison |
| Input Validation | PHP source | validation code |
| Output Encoding | PHP source | encoded output |
| CSRF | state-changing form | token + rejection |
| HTTPS | Apache SSL | curl headers |
| Security Headers | Apache | response headers |
| ModSecurity | Defense | audit log |
| OWASP CRS | Defense | CRS rule evidence |
| Fail2ban | auth/log | jail status/log |
| Verification | all tests | Before/After table |

---

# PHẦN S — CHECKLIST BÁO CÁO

## 40. Sơ đồ kiến trúc

Vẽ tối thiểu:

```text
Windows Host
     │
 VMware VMnet8/NAT
     │
     ▼
Ubuntu VM
     │
     ├── Apache + PHP
     │       │
     │       ├── Attack WebBook
     │       └── Defense WebBook
     │
     ├── ModSecurity + OWASP CRS
     │
     ├── Fail2ban
     │
     └── MariaDB
```

Thể hiện rõ firewall/WAF nằm ở đâu và luồng request đi qua đâu.

---

## 41. Bảng Before / After

Trong báo cáo tạo bảng:

| Test | Attack/Before | Defense/After | Evidence |
|---|---|---|---|
| SQLi | khai thác được | không còn SQLi / bị WAF | ảnh + log |
| XSS | script thực thi | output encoded | ảnh |
| IDOR | xem object khác | 403 | HTTP |
| CSRF | request thành công | token required | request |
| Command Injection | command được thực thi | input bị giới hạn | log |
| WAF | request đi qua | blocked | ModSecurity |
| Brute force | nhiều login fail | bị ban | Fail2ban |

---

# PHẦN T — VIDEO DEMO

## 42. Kịch bản quay video

### Video 1 — SQL Injection Before

```text
1. Mở Attack WebBook
2. Vào SQLi Lab
3. Chọn testcase
4. Gửi payload
5. Cho thấy response
6. Mở source code
7. Chỉ ra điểm nối SQL
```

### Video 2 — SQL Injection After

```text
1. Mở Defense WebBook
2. Gửi cùng testcase
3. Payload không còn được xử lý như SQL
4. Chụp HTTP status
5. Mở code Prepared Statement
6. Mở ModSecurity log nếu WAF phát hiện
```

### Video 3 — XSS

```text
Attack → payload → JavaScript chạy
Defense → cùng payload → được encode/chặn
```

### Video 4 — WAF

```text
Request
   ↓
ModSecurity
   ↓
OWASP CRS
   ↓
BLOCK
   ↓
HTTP 4xx
   ↓
Audit log
```

### Video 5 — Fail2ban

```text
Repeated failed login
        ↓
Apache/PHP log
        ↓
Fail2ban
        ↓
Ban
        ↓
Request bị từ chối
```

---

# PHẦN U — XỬ LÝ LỖI THƯỜNG GẶP

## 43. `docker-compose.attack.yml: no such file`

```bash
ls -l docker-compose.attack.yml
```

Nếu file có mà compose không tìm thấy, kiểm tra:

```bash
pwd
```

Phải là:

```text
/home/<user>/webbook-lab
```

---

## 44. CRS đã tồn tại

Nếu build báo:

```text
fatal: destination path '/etc/modsecurity/crs' already exists and is not an empty directory.
```

Dockerfile phải xóa CRS trước khi clone hoặc dùng cách cài CRS không clone chồng lên thư mục đã có.

Kiểm tra Dockerfile:

```bash
grep -n "modsecurity/crs" Dockerfile
```

Nếu có:

```text
rm -rf /etc/modsecurity/crs
```

trước `git clone`, rebuild:

```bash
sudo docker compose -f docker-compose.defense.yml build --no-cache web_defense
```

---

## 45. `git pull` báo local changes

Dùng quy trình ở mục 3. Không dùng `git reset --hard` nếu chưa chắc chắn vì có thể làm mất thay đổi local.

---

## 46. Port không mở từ Windows

Trên Ubuntu:

```bash
sudo docker ps
sudo ss -lntp | grep -E ':8080|:8081|:8443|:8444'
```

Trên Windows:

```powershell
Test-NetConnection <IP-UBUNTU> -Port 8081
Test-NetConnection <IP-UBUNTU> -Port 8080
```

Nếu Ubuntu nghe được nhưng Windows không kết nối:

```bash
sudo ufw status
```

Nếu firewall đang bật, mở đúng port lab theo mạng nội bộ, hoặc tắt firewall **chỉ trong môi trường lab nếu giảng viên yêu cầu**. Ưu tiên rule giới hạn nguồn thay vì mở toàn bộ.

---

## 47. Browser chuyển sang `https://localhost:8443`

Đây là lỗi redirect host/port. Tìm:

```bash
grep -Rni "localhost:8443" --exclude-dir=.git .
```

và:

```bash
sudo docker exec webbook_attack_web sh -c 'grep -Rni "localhost:8443" /etc/apache2 2>/dev/null'
```

Sửa vhost rồi rebuild container.

---

# PHẦN V — DỪNG / RESET LAB

## 48. Dừng WebBook

```bash
sudo docker compose -f docker-compose.attack.yml down
sudo docker compose -f docker-compose.defense.yml down
```

## 49. Xóa cả database test

```bash
sudo docker compose -f docker-compose.attack.yml down -v
sudo docker compose -f docker-compose.defense.yml down -v
```

> `-v` sẽ xóa database volume của lab. Chỉ dùng khi muốn reset dữ liệu thử nghiệm.

---

# PHẦN W — TIÊU CHÍ HOÀN THÀNH

Không đánh dấu đồ án hoàn thành chỉ vì Docker chạy được.

Checklist cuối:

```text
[ ] Ubuntu + PHP + Apache + MariaDB chạy
[ ] Attack WebBook chạy
[ ] Defense WebBook chạy
[ ] Hai database tách riêng
[ ] Attack/Before có testcase khai thác
[ ] Defense/After có testcase kiểm chứng
[ ] SQLi Basic
[ ] SQLi UNION
[ ] SQLi Error
[ ] Blind Boolean
[ ] Time-based
[ ] OS Command Injection
[ ] Reflected XSS
[ ] Stored XSS
[ ] DOM XSS
[ ] Session Fixation
[ ] Session Hijacking (controlled)
[ ] IDOR
[ ] CSRF
[ ] Prepared Statements
[ ] Input Validation
[ ] Context-aware Output Encoding
[ ] HTTPS
[ ] Security Headers
[ ] HttpOnly/Secure/SameSite cookies
[ ] ModSecurity ON
[ ] OWASP CRS loaded
[ ] WAF blocking verified
[ ] ModSecurity audit log captured
[ ] Fail2ban/equivalent configured
[ ] Brute-force blocking verified
[ ] Apache/security logs captured
[ ] Before/After table hoàn chỉnh
[ ] Sơ đồ kiến trúc
[ ] Screenshot
[ ] Video Attack
[ ] Video Defense/WAF
[ ] Video Fail2ban
```

---

# KẾT LUẬN

Mô hình 2 WebBook cho phép trình bày đồ án theo đúng logic của một Security Audit:

```text
             BEFORE                         AFTER

      🔴 ATTACK WEBBOOK              🟢 DEFENSE WEBBOOK
             │                              │
             ▼                              ▼
       Vulnerability                  Secure Coding
             │                              │
             ▼                              ▼
       Exploitation                    Validation
             │                              │
             ▼                              ▼
        Evidence                     Output Encoding
                                            │
                                            ▼
                                      CSRF Protection
                                            │
                                            ▼
                                      HTTPS + Headers
                                            │
                                            ▼
                                   ModSecurity + CRS
                                            │
                                            ▼
                                      Fail2ban
                                            │
                                            ▼
                                     Audit Logs
```

**Nguyên tắc báo cáo:** cùng một testcase → chạy trên Attack → lưu bằng chứng → áp dụng biện pháp phòng thủ → chạy lại trên Defense → lưu HTTP status/log → kết luận hiệu quả phòng thủ.

---

## Lệnh khởi động nhanh

```bash
cd ~/webbook-lab

sudo docker compose -f docker-compose.attack.yml up -d --build
sudo docker compose -f docker-compose.defense.yml up -d --build

sudo docker ps

curl -I http://127.0.0.1:8081/
curl -I http://127.0.0.1:8080/
curl -k -I https://127.0.0.1:8444/
curl -k -I https://127.0.0.1:8443/
```

## Repository

`Namthisone/webbook-lab`

Tài liệu này là **runbook thực hành**; trạng thái hoàn thành phải được xác nhận bằng testcase và evidence thực tế, không chỉ bằng việc container ở trạng thái `Up`.
