# WebBook Security Lab — Đề 5

Lab sử dụng chính ứng dụng **Tạp Hóa Sách / WebBook** để thực hành Security Audit & Hardening theo Đề 5. Kiến trúc lab có hai deployment độc lập:

- 🔴 **Attack WebBook** — môi trường cô lập để tái hiện lỗ hổng có chủ ý.
- 🟢 **Defense WebBook** — môi trường áp dụng secure coding + hardening + WAF + monitoring.

> Chỉ chạy Attack WebBook trong Ubuntu VM/lab nội bộ. Không expose bản vulnerable ra Internet.

## 1. Hai WebBook

| | 🔴 Attack | 🟢 Defense |
|---|---|---|
| HTTP | `8081` | `8080` |
| HTTPS | `8444` | `8443` |
| Database | riêng | riêng |
| PHP mode | `WEBBOOK_SECURITY_MODE=attack` | `WEBBOOK_SECURITY_MODE=defense` |
| Mục đích | Pentest / Before | Secure Coding / After |

Với Ubuntu VM `192.168.206.140`:

```text
Attack:  https://192.168.206.140:8444/
Defense: https://192.168.206.140:8443/
```

## 2. Khởi động Defense

```bash
docker compose -f docker-compose.defense.yml up -d --build
docker compose -f docker-compose.defense.yml ps
```

## 3. Khởi động Attack

```bash
docker compose -f docker-compose.attack.yml up -d --build
docker compose -f docker-compose.attack.yml ps
```

Có thể chạy cả hai cùng lúc.

## 4. Tài liệu thực hành

Xem:

- [`docs/DUAL_WEBBOOK_LAB.md`](docs/DUAL_WEBBOOK_LAB.md) — kiến trúc, port, quy trình Before/After và mapping Đề 5.
- `src/security-lab/` — các lab kỹ thuật hiện có.

## 5. Quy trình báo cáo

Mỗi testcase phải thực hiện theo chuỗi:

```text
Attack WebBook
    ↓
Request / Response
    ↓
Screenshot + HTTP status
    ↓
Phân tích code
    ↓
Defense WebBook
    ↓
Chạy lại cùng testcase
    ↓
HTTP status / response mới
    ↓
ModSecurity / Fail2ban log
    ↓
Đưa vào biên bản Pentest & Audit
```

Các nhóm chính: SQL Injection (Union/Error/Blind/Time), OS Command Injection, Stored/Reflected/DOM XSS, IDOR, Session Fixation, Session Hijacking, CSRF, Authentication/Access Control, Security Misconfiguration, Logging/Monitoring, HTTPS, Security Headers, ModSecurity + OWASP CRS và Fail2ban.

## 6. Lưu ý về trạng thái mã nguồn

Hai compose file đã được thêm để **tách hạ tầng Attack và Defense**. Việc tách container/database là bước đầu; security behavior của PHP sẽ được chuyển sang `WEBBOOK_SECURITY_MODE=attack|defense` theo từng chức năng. Vì vậy không coi việc hai container khởi động được là đã hoàn thành toàn bộ Đề 5. Phải kiểm thử và thu thập bằng chứng Before/After cho từng yêu cầu.

## 7. Dừng lab

```bash
docker compose -f docker-compose.attack.yml down
docker compose -f docker-compose.defense.yml down
```

Xóa luôn database test:

```bash
docker compose -f docker-compose.attack.yml down -v
docker compose -f docker-compose.defense.yml down -v
```
