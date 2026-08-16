# WebBook Security Lab — Đề 5

Lab sử dụng chính ứng dụng **Tạp Hóa Sách / WebBook** để thực hành Security Audit & Hardening theo Đề 5. Kiến trúc lab có hai deployment độc lập:

- 🔴 **Attack WebBook** — môi trường cô lập để tái hiện lỗ hổng có chủ ý.
- 🟢 **Defense WebBook** — môi trường áp dụng secure coding + hardening + WAF + monitoring.

> Chỉ chạy Attack WebBook trong Ubuntu VM/lab nội bộ. Không expose bản vulnerable ra Internet.

## 1. Hai WebBook

| | 🔴 Attack | 🟢 Defense |
|---|---|---|
| HTTP | `8081` | `8080` |
| HTTPS | Không dùng | `8443` |
| Database | riêng, host `3307` | riêng, host `3308` |
| PHP mode | `WEBBOOK_SECURITY_MODE=attack` | `WEBBOOK_SECURITY_MODE=defense` |
| WAF | Tắt | ModSecurity + OWASP CRS |
| Mục đích | Pentest / Before | Secure Coding / After |

Ví dụ Ubuntu VM `192.168.206.140`:

```text
Attack:  http://192.168.206.140:8081/
Defense: http://192.168.206.140:8080/  →  https://192.168.206.140:8443/
Lab:     http://192.168.206.140:8081/security-lab/
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

Có thể chạy cả hai cùng lúc vì port/database/volume đã được tách.

## 4. Tài liệu thực hành chính

- [`docs/THUC_HANH_WINDOWS_ATTACK_DEFENSE.md`](docs/THUC_HANH_WINDOWS_ATTACK_DEFENSE.md) — hướng dẫn triển khai Windows/VMware, Attack/Defense, payload lab, kiểm chứng WAF/log và bảng báo cáo.
- [`docs/DUAL_WEBBOOK_LAB.md`](docs/DUAL_WEBBOOK_LAB.md) — kiến trúc và quy trình Before/After.
- [`HUONG_DAN_THUC_HANH_DE_5.md`](HUONG_DAN_THUC_HANH_DE_5.md) — hướng dẫn Đề 5 hiện có.
- `src/security-lab/` — các lab kỹ thuật.

## 5. Security Lab hiện có

```text
SQL Injection      → Basic / UNION / Error / Blind / Time
OS Command         → Command Injection
XSS                → Reflected / Stored / DOM
IDOR               → Authorization / object ownership
CSRF               → state-changing request / token
Session            → Fixation / Hijacking simulation
Secure Coding      → Prepared Statement / Encoding / CSRF / Authorization
Audit              → application audit + Apache/ModSecurity logs
```

## 6. Quy trình báo cáo

Mỗi testcase phải thực hiện theo chuỗi:

```text
Attack WebBook
    ↓
Payload / Request
    ↓
HTTP Response + Screenshot
    ↓
Phân tích source code
    ↓
Defense WebBook
    ↓
Chạy lại cùng testcase
    ↓
HTTP status / response mới
    ↓
ModSecurity / Fail2ban / Apache log
    ↓
Đưa vào biên bản Pentest & Audit
```

## 7. Kiểm tra mode

```bash
docker exec webbook_attack_web env | grep WEBBOOK_SECURITY_MODE
docker exec webbook_defense_web env | grep WEBBOOK_SECURITY_MODE
```

Phải lần lượt là `attack` và `defense`.

## 8. Lưu ý về OWASP Top 10

Lab đã triển khai trực tiếp các nhóm phục vụ Đề 5 như A01, A03, A05, A07 và A09; các nhóm còn lại được lập bản đồ trong tài liệu thực hành. **A10 SSRF chưa có endpoint khai thác riêng trong phiên bản hiện tại**, vì vậy không ghi là đã hoàn thành A10 nếu chưa bổ sung module SSRF riêng.

## 9. Dừng lab

```bash
docker compose -f docker-compose.attack.yml down
docker compose -f docker-compose.defense.yml down
```

Xóa luôn database test:

```bash
docker compose -f docker-compose.attack.yml down -v
docker compose -f docker-compose.defense.yml down -v
```
