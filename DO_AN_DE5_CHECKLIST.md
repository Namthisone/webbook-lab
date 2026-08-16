# ĐỀ 5 — CHECKLIST HOÀN THIỆN ĐỒ ÁN

## 1. Hạ tầng
- [x] Docker Compose
- [x] Apache + PHP 8.x
- [x] MariaDB
- [x] HTTPS/TLS
- [x] Security Headers
- [x] ModSecurity
- [x] OWASP CRS
- [x] Fail2ban configuration

## 2. Vulnerability Assessment
- [x] SQL Injection lab: Basic / UNION / Error / Blind / Time
- [x] Reflected XSS
- [x] Stored XSS
- [x] DOM XSS
- [x] OS Command Injection
- [x] IDOR
- [x] CSRF
- [x] Session Fixation
- [x] Session Hijacking simulation

## 3. Secure Coding
- [x] PDO Prepared Statements
- [x] Numeric/input validation
- [x] Context-aware HTML output encoding
- [x] CSRF token validation
- [x] Authorization/ownership check
- [x] Session regeneration
- [x] Secure/HttpOnly/SameSite cookie settings

## 4. Verification — cần chạy trên máy lab
- [ ] Run every vulnerable testcase
- [ ] Capture HTTP request
- [ ] Capture HTTP response/status
- [ ] Enable WAF blocking
- [ ] Repeat every testcase
- [ ] Capture ModSecurity audit entry
- [ ] Trigger and verify Fail2ban for repeated login failures
- [ ] Record Before/After results
- [ ] Confirm HTTPS and all required headers with browser/curl

## 5. Báo cáo
- [ ] Sơ đồ kiến trúc mạng/WAF/Firewall/application
- [ ] Mô tả từng lỗ hổng
- [ ] Payload/test input used only in local lab
- [ ] Request/Response before patch
- [ ] Secure coding patch
- [ ] Request/Response after patch
- [ ] ModSecurity log evidence
- [ ] Fail2ban log evidence
- [ ] Performance/response-time comparison
- [ ] Screenshots
- [ ] Video: exploitation before hardening
- [ ] Video: WAF/Fail2ban blocking after hardening

> Chỉ đánh dấu Verification và Báo cáo là hoàn thành sau khi chạy thực tế trên Ubuntu VM/Windows Docker host và lưu bằng chứng. Các module vulnerable chỉ dùng trong localhost/VM kiểm soát được.