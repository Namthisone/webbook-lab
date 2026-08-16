# WEBBOOK LAB — HƯỚNG DẪN THỰC HÀNH ĐỀ 5

> Phạm vi: chỉ dùng trên máy Ubuntu/Windows/VMware do sinh viên kiểm soát. Không dùng các payload trong tài liệu này trên hệ thống Internet hoặc hệ thống không được phép.

## 1. Mục tiêu

Lab được tách thành hai deployment độc lập:

| Bản | Cổng | Mục đích | WAF/hardening |
|---|---:|---|---|
| ATTACK | `8081` | WebBook cố ý dễ bị khai thác để chứng minh lỗ hổng | Tắt ModSecurity/CRS, không ép HTTPS |
| DEFENSE | `8080` → `8443` | Bản đã hardening để kiểm chứng phòng thủ | HTTPS, security headers, ModSecurity/CRS |

Attack và Defense dùng database/volume riêng, vì vậy thao tác phá dữ liệu trong Attack không làm bẩn database Defense.

## 2. Sơ đồ triển khai

```text
Windows Browser
      |
      | http://UBUNTU_IP:8081
      v
+----------------------+       +----------------------+
| WebBook ATTACK       |       | WebBook DEFENSE      |
| Apache :80           |       | Apache :80/:443      |
| no WAF               |       | ModSecurity + CRS    |
| vulnerable mode      |       | fixed mode           |
+----------+-----------+       +----------+-----------+
           |                              |
           v                              v
     MariaDB Attack                 MariaDB Defense
       :3307 host                     :3308 host

Ubuntu VM: 192.168.x.x
```

## 3. Kiểm tra mạng VMware

Trên Ubuntu:

```bash
ip addr show ens33
```

Ghi lại IPv4, ví dụ `192.168.206.140`.

Trên Windows CMD:

```cmd
ping 192.168.206.140
curl -I http://192.168.206.140:8081/
```

Nếu Ubuntu trả `HTTP/1.1 200 OK` nhưng Windows không kết nối được, không sửa Docker trước; kiểm tra VMware Network Adapter/Bridged hoặc NAT và Windows firewall.

## 4. Chạy ATTACK

Trong Ubuntu:

```bash
cd ~/webbook-lab
sudo docker compose -f docker-compose.attack.yml config
sudo docker compose -f docker-compose.attack.yml up -d --build
sudo docker ps
```

Phải thấy `webbook_attack_web` với `0.0.0.0:8081->80/tcp`.

Kiểm tra:

```bash
curl -I http://127.0.0.1:8081/
curl -I http://$(hostname -I | awk '{print $1}'):8081/
```

Mở trên Windows:

```text
http://UBUNTU_IP:8081/
http://UBUNTU_IP:8081/security-lab/
```

ATTACK không dùng `Dockerfile` chính. `Dockerfile.attack` chỉ cài PHP extensions, rewrite và copy vhost Attack; không cài ModSecurity/CRS. `apache-vhost.attack.conf` cũng ghi rõ đây là lab không WAF/hardening. 

## 5. Chạy DEFENSE

```bash
sudo docker compose -f docker-compose.defense.yml config
sudo docker compose -f docker-compose.defense.yml up -d --build
sudo docker ps
```

Mở:

```text
http://UBUNTU_IP:8080/
```

HTTP sẽ chuyển sang:

```text
https://UBUNTU_IP:8443/
```

Chứng chỉ tự ký sẽ khiến trình duyệt cảnh báo; đây là bình thường trong lab. Chọn Advanced/Proceed để tiếp tục.

Kiểm tra WAF:

```bash
sudo docker exec webbook_defense_web apache2ctl -M | grep security2
sudo docker exec webbook_defense_web grep -R "SecRuleEngine" /etc/modsecurity/modsecurity.conf
```

## 6. Kiểm tra đúng mode

Trong Attack:

```bash
sudo docker exec webbook_attack_web env | grep WEBBOOK_SECURITY_MODE
```

Kết quả phải là:

```text
WEBBOOK_SECURITY_MODE=attack
```

Defense:

```bash
sudo docker exec webbook_defense_web env | grep WEBBOOK_SECURITY_MODE
```

Kết quả:

```text
WEBBOOK_SECURITY_MODE=defense
```

Các module security-lab lấy mode từ deployment; không dùng query string để biến container Defense thành vulnerable.

## 7. SQL Injection — A03 Injection

Trang:

```text
http://UBUNTU_IP:8081/security-lab/sqli.php
```

Các scenario có sẵn: `basic`, `union`, `error`, `blind`, `time`.

### 7.1 Basic

ID bình thường:

```text
1
```

Mục đích: tạo baseline.

### 7.2 Union-style test

Trong ô ID, dùng payload lab đơn giản:

```text
1' UNION SELECT 1,'LAB',999 -- -
```

Quan sát output. Ghi lại URL, request, response và kết quả.

### 7.3 Error-based

Dùng input làm phát sinh lỗi cú pháp trong câu SQL, ví dụ:

```text
1'
```

Ghi lại status/output trước hardening.

### 7.4 Boolean blind

So sánh hai request TRUE/FALSE, ví dụ:

```text
1' AND '1'='1' -- -
1' AND '1'='2' -- -
```

Chỉ quan sát sự khác nhau về số dòng/response; không dump dữ liệu thật.

### 7.5 Time-based

Module có scenario timing để so sánh thời gian phản hồi. Trong báo cáo, ưu tiên dùng chức năng lab và đo elapsed time thay vì tạo tải lớn.

### Defense

Mở cùng module trên:

```text
https://UBUNTU_IP:8443/security-lab/sqli.php
```

Defense dùng PDO prepared statement và validation. Kết quả cần ghi: payload không còn được ghép vào SQL, response an toàn hoặc bị từ chối.

## 8. OS Command Injection — A03 Injection

Trang:

```text
http://UBUNTU_IP:8081/security-lab/command.php
```

Baseline:

```text
127.0.0.1
```

Payload lab kiểm soát:

```text
127.0.0.1; id
```

hoặc:

```text
127.0.0.1 && whoami
```

Mục tiêu chỉ chứng minh command thứ hai được thực thi trong ATTACK. Không dùng `rm`, reverse shell, download, persistence hoặc phá dữ liệu.

Defense dùng allowlist + escaping và không cho input tùy ý đi vào shell.

## 9. XSS — A03 Injection

Trang:

```text
http://UBUNTU_IP:8081/security-lab/xss.php
```

### Reflected XSS

```html
<script>alert('XSS-LAB')</script>
```

Nếu trình duyệt hiện hộp thoại trong Attack, ghi screenshot.

### Stored XSS

Nhập cùng payload vào comment. Reload trang để chứng minh payload được lưu và render.

### DOM XSS

Trang:

```text
http://UBUNTU_IP:8081/security-lab/dom-xss.php?q=<script>alert('DOM-LAB')</script>
```

Attack dùng `innerHTML`; Defense dùng `textContent`.

Defense cần kiểm chứng payload được hiển thị như text, không thực thi.

## 10. IDOR — A01 Broken Access Control

Trang:

```text
http://UBUNTU_IP:8081/security-lab/idor.php
```

ATTACK:

```text
User A (101), Object 201
```

sau đó đổi object thành:

```text
202
```

Nếu User A đọc được tài liệu của User B, đó là IDOR.

Defense:

```text
User A (101), Object 202
```

phải trả `403 Forbidden`.

Đây là kiểm tra authorization phía server, không phải chỉ ẩn ID trên giao diện.

## 11. CSRF — A01 Broken Access Control / Secure Design

Trang:

```text
http://UBUNTU_IP:8081/security-lab/csrf.php
```

Trong Attack, POST thay đổi state được chấp nhận mà không bắt buộc token hợp lệ.

Trong Defense, thử gửi POST thiếu token bằng DevTools/curl trong lab. Kỳ vọng:

```text
403
```

Payload kiểm thử tối thiểu chỉ thay đổi số dư demo, không liên quan tài khoản thật.

## 12. Session Fixation — A07 Identification and Authentication Failures

Trang:

```text
http://UBUNTU_IP:8081/security-lab/session.php
```

Ghi Session ID trước login, bấm Login demo, ghi Session ID sau login.

Attack: ID cố ý được giữ lại.

Defense: `session_regenerate_id(true)` sau authentication, đồng thời cookie có HttpOnly/Secure/SameSite phù hợp.

## 13. Session Hijacking — A07

Trang:

```text
http://UBUNTU_IP:8081/security-lab/session-hijack.php
```

Đây là mô phỏng lab: không đánh cắp cookie của máy khác. Dùng giá trị session hiện tại, thay bằng một giá trị khác và so sánh status.

Attack: mô phỏng có thể được chấp nhận.

Defense: session identifier không hợp lệ phải bị từ chối (`403`).

## 14. Audit Log

Trang:

```text
http://UBUNTU_IP:8081/security-lab/audit.php
```

Mỗi module ghi scenario, mode, method, path, HTTP status và kết quả vào bảng audit. Đây là nguồn để chụp màn hình và lập bảng Pentest/Audit Log.

Apache:

```bash
sudo docker exec webbook_attack_web tail -n 50 /var/log/apache2/access.log
sudo docker exec webbook_attack_web tail -n 50 /var/log/apache2/error.log
```

Defense + ModSecurity:

```bash
sudo docker exec webbook_defense_web tail -n 50 /var/log/apache2/access.log
sudo docker exec webbook_defense_web tail -n 50 /var/log/apache2/modsec_audit.log
```

## 15. Kiểm chứng WAF

Thực hiện cùng request trên Attack và Defense.

Attack:

```text
HTTP 200/ứng dụng xử lý payload
```

Defense:

```text
HTTP 403 hoặc response bị WAF chặn
```

Không kết luận WAF chỉ dựa vào HTTP status; phải chụp cả ModSecurity audit log và ghi thời điểm test.

## 16. Security headers

Defense:

```bash
curl -k -I https://UBUNTU_IP:8443/
```

Kiểm tra các header theo yêu cầu đồ án:

```text
Content-Security-Policy
X-Frame-Options
X-Content-Type-Options
Strict-Transport-Security
```

Cookie cần kiểm tra:

```text
HttpOnly
Secure (khi HTTPS)
SameSite
```

## 17. Fail2ban

Nếu triển khai Fail2ban container/service:

```bash
sudo docker ps -a | grep fail2ban
sudo docker logs webbook_fail2ban --tail 100
```

Nếu service chạy native:

```bash
sudo fail2ban-client status
sudo fail2ban-client status webbook-login
```

Báo cáo cần có: log trước khi block, số lần thử, thời điểm ban, IP lab và bằng chứng sau khi ban.

## 18. Mapping với Đề 5

| Yêu cầu đề | Thành phần lab |
|---|---|
| SQLi Union/Error/Blind/Time | `sqli.php` |
| OS Command Injection | `command.php` |
| Reflected/Stored XSS | `xss.php` |
| DOM XSS | `dom-xss.php` |
| Session Fixation | `session.php` |
| Session Hijacking simulation | `session-hijack.php` |
| IDOR | `idor.php` |
| CSRF | `csrf.php` |
| Prepared Statements | `sqli.php`, `secure.php` |
| Output Encoding | `xss.php`, `dom-xss.php`, `secure.php` |
| HTTPS | Defense Apache :443 mapped to 8443 |
| Security Headers | `apache-vhost.conf`, `apache-ssl.conf` |
| ModSecurity + CRS | `Dockerfile` |
| Audit Log | `audit.php` + Apache logs |
| Fail2ban | `fail2ban/` |

## 19. Mapping OWASP Top 10

Lab hiện triển khai trực tiếp các nhóm quan trọng của Đề 5:

- A01 Broken Access Control: IDOR, CSRF/authorization checks.
- A02 Cryptographic Failures: HTTPS, Secure cookie và TLS configuration; đây là phần hardening, không phải một trang khai thác dữ liệu mã hóa thật.
- A03 Injection: SQLi, OS Command Injection, XSS/DOM XSS.
- A04 Insecure Design: so sánh vulnerable/fixed design và server-side authorization.
- A05 Security Misconfiguration: HTTP headers, HTTPS, WAF configuration.
- A06 Vulnerable and Outdated Components: kiểm kê PHP/Apache/MariaDB/container image và ghi phiên bản vào báo cáo.
- A07 Identification and Authentication Failures: session fixation/hijacking simulation và session regeneration.
- A08 Software and Data Integrity Failures: kiểm tra nguồn image, checksum/build reproducibility trong quy trình triển khai; không tải code không tin cậy trong lab.
- A09 Security Logging and Monitoring Failures: audit table, Apache log, ModSecurity log và Fail2ban.
- A10 SSRF: chưa phải module khai thác riêng trong phiên bản hiện tại; không được ghi là 'đã hoàn thành' nếu chưa bổ sung endpoint SSRF riêng.

> Quan trọng: Đề 5 không bắt buộc phải biến cả WebBook thành một bản sao DVWA. Các module security-lab là khu vực thực hành có kiểm soát. Với A10, nếu giảng viên yêu cầu demo trực tiếp, cần bổ sung một endpoint SSRF lab riêng trước khi ghi hoàn thành trong báo cáo.

## 20. Bảng bằng chứng báo cáo

Mỗi testcase ghi:

```text
ID testcase:
Mục tiêu:
URL:
Mode: ATTACK / DEFENSE
Payload/input:
HTTP method:
Request:
Response status:
Kết quả trước vá:
Biện pháp phòng thủ:
Kết quả sau vá:
Apache log:
ModSecurity log:
Fail2ban log:
Ảnh/video số:
```

## 21. Quy trình quay video

1. Mở ATTACK.
2. Chọn module.
3. Dùng payload lab được mô tả ở trên.
4. Chụp kết quả khai thác thành công.
5. Mở DEFENSE.
6. Gửi đúng request/payload đó.
7. Chụp `403`/response an toàn.
8. Mở ModSecurity audit log.
9. Nếu là brute-force lab, mở Fail2ban và chụp trạng thái ban.
10. Kết luận: cùng một testcase, trước và sau hardening.

## 22. Dọn lab

```bash
sudo docker compose -f docker-compose.attack.yml down
sudo docker compose -f docker-compose.defense.yml down
```

Nếu cần xóa cả database lab để làm lại từ đầu:

```bash
sudo docker compose -f docker-compose.attack.yml down -v
sudo docker compose -f docker-compose.defense.yml down -v
```

Lệnh `down -v` sẽ xóa dữ liệu database của lab; chỉ dùng khi muốn reset.
