# WebBook Security Lab – Đề 5

## Mục tiêu
Các trang trong `src/security-lab/` là phòng thí nghiệm cục bộ để kiểm thử trước/sau hardening. Không triển khai các trang vulnerable lên Internet.

## Truy cập
Sau khi chạy Docker Compose:
- `http://localhost:8080/security-lab/`
- HTTPS: `https://localhost:8443/security-lab/`

## Các module
| Module | Vulnerable | Fixed | Nội dung báo cáo |
|---|---|---|---|
| `sqli.php` | Có | Có | Request/response, prepared statement, ModSecurity log |
| `xss.php` | Có | Có | Reflected + Stored, output encoding, CSP |
| `command.php` | Có | Có | Command injection, allowlist/escaping |
| `idor.php` | Có | Có | Authorization/ownership, HTTP 403 |
| `csrf.php` | Có | Có | State-changing request + CSRF token |
| `session.php` | Có | Có | Session fixation, regenerate ID, cookie hardening |
| `secure.php` | - | Có | Secure coding reference |

## Quy trình thực hành
1. Chạy bản `vulnerable` trong localhost/VM.
2. Ghi lại URL, method, tham số, status code và response.
3. Chụp màn hình kết quả khai thác trong lab.
4. Bật hardening/ModSecurity.
5. Lặp lại cùng kịch bản.
6. Ghi status code và log Apache/ModSecurity/Fail2ban.
7. Đối chiếu trước/sau trong báo cáo.

## Lưu ý
Payload chỉ được thử trên ứng dụng lab của chính bạn. Các trang này cố ý có lỗi để phục vụ môn học; không dùng code vulnerable cho hệ thống thật.