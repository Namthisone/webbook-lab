# WebBook Security Audit Lab — 2 phiên bản Attack / Defense

## Mục tiêu

Đồ án sử dụng chính ứng dụng **Tạp Hóa Sách / WebBook** làm đối tượng kiểm toán. Lab có hai deployment độc lập:

- 🔴 **Attack WebBook**: môi trường cô lập để tái hiện lỗi bảo mật có chủ ý.
- 🟢 **Defense WebBook**: môi trường cô lập để áp dụng secure coding, HTTPS, security headers, ModSecurity/CRS, Fail2ban và kiểm chứng lại.

> Chỉ chạy Attack WebBook trên máy ảo/lab nội bộ. Không expose bản vulnerable ra Internet.

## Port

| Deployment | HTTP | HTTPS | Database host port |
|---|---:|---:|---:|
| Attack | 8081 | 8444 | 3307 (localhost only) |
| Defense | 8080 | 8443 | 3308 (localhost only) |

Nếu Ubuntu VM có IP `192.168.206.140` thì URL lab dự kiến:

```text
Attack:  https://192.168.206.140:8444/
Defense: https://192.168.206.140:8443/
```

## Khởi động Defense

```bash
docker compose -f docker-compose.defense.yml up -d --build
docker compose -f docker-compose.defense.yml ps
```

## Khởi động Attack

```bash
docker compose -f docker-compose.attack.yml up -d --build
docker compose -f docker-compose.attack.yml ps
```

Có thể chạy cả hai cùng lúc vì chúng dùng database, container và port riêng.

## Quy trình kiểm thử chuẩn cho báo cáo

Mỗi lỗ hổng phải có hai bộ bằng chứng:

1. **Before / Attack**: testcase trên Attack WebBook.
2. **After / Defense**: cùng testcase trên Defense WebBook.
3. HTTP request/response hoặc DevTools Network.
4. HTTP status và thời gian phản hồi khi phù hợp.
5. Code vulnerable và code secure tương ứng.
6. ModSecurity audit log khi WAF phát hiện/chặn.
7. Fail2ban log/status đối với brute-force/rate-limit.
8. Screenshot có tên theo mã testcase.

Mẫu thư mục evidence:

```text
evidence/
  A01-idor/
  A02-crypto/
  A03-sqli/
  A03-command-injection/
  A05-security-misconfiguration/
  A07-auth-session/
  A09-logging-monitoring/
  xss/
  csrf/
  waf/
  fail2ban/
```

## Mapping theo Đề 5

| Yêu cầu | WebBook Lab |
|---|---|
| SQLi In-band | Attack/Defense SQLi lab + WebBook search/detail |
| UNION / Error SQLi | SQLi scenarios |
| Blind Boolean | SQLi scenarios |
| Time-based | SQLi scenarios |
| OS Command Injection | Controlled diagnostic lab |
| Stored XSS | Khiếu nại/bình luận lab |
| Reflected XSS | Search/query lab |
| DOM XSS | Front-end lab |
| Session Fixation | Login/session lab |
| Session Hijacking | Controlled session lab |
| IDOR | Đơn mua/đơn thuê/user-owned objects |
| Prepared Statements | Defense code |
| Output Encoding | Defense code |
| CSRF token | State-changing forms |
| HTTPS | Apache SSL |
| Security Headers | Apache |
| ModSecurity + CRS | Defense deployment |
| Fail2ban | Login/Apache logs |
| Audit log | `apache-logs/` + lab audit |

## Nguyên tắc an toàn

Attack WebBook là dữ liệu thử nghiệm. Database Attack và Defense không dùng chung volume. Không dùng tài khoản, dữ liệu hay endpoint thật ngoài lab. Không đưa port vulnerable ra Internet.

## Trạng thái triển khai

Hai compose file đã được tạo để tách môi trường. Bước tiếp theo là tách **security behavior của PHP** theo `WEBBOOK_SECURITY_MODE=attack|defense`, sau đó chuyển từng chức năng WebBook vào ma trận Attack/Defense và tạo testcase Before/After. Không coi việc hai container chạy được là hoàn thành đồ án; phải kiểm chứng từng testcase và thu thập log.
