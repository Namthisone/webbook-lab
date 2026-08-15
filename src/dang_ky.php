<?php
require_once 'db.php';
session_start();

if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$loi = "";
$thong_bao = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten_dang_nhap = trim($_POST['ten_dang_nhap']);
    $mat_khau = $_POST['mat_khau'];
    $ho_ten = trim($_POST['ho_ten']);
    $email = trim($_POST['email']);

    if (empty($ten_dang_nhap) || empty($mat_khau) || empty($ho_ten) || empty($email)) {
        $loi = "Vui lòng điền đầy đủ thông tin bắt buộc!";
    } else {
        $stmt = $ket_noi->prepare("SELECT id FROM nguoi_dung WHERE ten_dang_nhap = ? OR email = ?");
        $stmt->execute([$ten_dang_nhap, $email]);
        
        if ($stmt->rowCount() > 0) {
            $loi = "Tên đăng nhập hoặc Email này đã tồn tại trên hệ thống!";
        } else {
            $mat_khau_ma_hoa = md5($mat_khau);
            
            $stmt_insert = $ket_noi->prepare("INSERT INTO nguoi_dung (ten_dang_nhap, mat_khau, ho_ten, email, vai_tro) VALUES (?, ?, ?, ?, 'user')");
            $ket_qua = $stmt_insert->execute([$ten_dang_nhap, $mat_khau_ma_hoa, $ho_ten, $email]);
            
            if ($ket_qua) {
                $thong_bao = "🎉 Đăng ký thành công! <a href='dang_nhap.php' style='color:#34d399; font-weight:bold; text-decoration:underline;'>Đăng nhập ngay</a>";
            } else {
                $loi = "Có lỗi xảy ra trong quá trình khởi tạo tài khoản!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - Tạp Hóa Sách</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc; 
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        /* Khung kính mờ Glassmorphism thời thượng */
        .reg-wrapper {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 45px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 460px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .reg-wrapper h2 {
            text-align: center;
            color: #fff;
            font-size: 26px;
            font-weight: 700;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }
        
        .subtitle {
            text-align: center;
            color: #94a3b8;
            font-size: 13px;
            margin-bottom: 35px;
        }

        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; color: #cbd5e1; font-size: 13px; }
        
        input[type="text"], input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            background-color: rgba(15, 23, 42, 0.6);
            color: #fff;
        }
        
        /* Hiệu ứng phát sáng Neon xanh lục khi focus */
        input:focus { 
            border-color: #10b981; 
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.25); 
        }
        
        /* Nút bấm Gradient Xanh lục bảo ngọc tinh tế */
        .btn-register {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.3);
            transition: all 0.3s ease;
            margin-top: 12px;
            letter-spacing: 0.5px;
        }
        
        .btn-register:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(5, 150, 105, 0.5); 
            filter: brightness(1.1);
        }

        /* Khối thông báo lỗi & Thành công mượt */
        .alert { padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; font-weight: 600; text-align: center; }
        .alert-danger { background-color: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }
        .alert-success { background-color: rgba(16, 185, 129, 0.15); color: #a7f3d0; border: 1px solid rgba(16, 185, 129, 0.3); }
        
        .footer-links { text-align: center; margin-top: 28px; font-size: 14px; color: #94a3b8; }
        .footer-links a { color: #38bdf8; text-decoration: none; font-weight: bold; margin-left: 3px; transition: color 0.2s; }
        .footer-links a:hover { color: #7dd3fc; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="reg-wrapper">
        <h2>📝 ĐĂNG KÝ MỚI</h2>
        <div class="subtitle">Tạo tài khoản mua và thuê sách miễn phí</div>
        
        <?php if(!empty($loi)): ?>
            <div class="alert alert-danger">❌ <?php echo $loi; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($thong_bao)): ?>
            <div class="alert alert-success"><?php echo $thong_bao; ?></div>
        <?php endif; ?>

        <form action="dang_ky.php" method="POST">
            <div class="form-group">
                <label>Họ và tên của bạn:</label>
                <input type="text" name="ho_ten" placeholder="Nhập đầy đủ họ tên..." required>
            </div>

            <div class="form-group">
                <label>Địa chỉ Email:</label>
                <input type="email" name="email" placeholder="Ví dụ: nguyenvana@gmail.com..." required>
            </div>

            <div class="form-group">
                <label>Tên tài khoản đăng nhập:</label>
                <input type="text" name="ten_dang_nhap" placeholder="Tên viết liền không dấu..." required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Mật khẩu bảo mật:</label>
                <input type="password" name="mat_khau" placeholder="Nhập mật khẩu an toàn..." required>
            </div>

            <button type="submit" class="btn-register">TẠO TÀI KHOẢN MỚI</button>
        </form>

        <div class="footer-links">
            Đã có tài khoản? <a href="dang_nhap.php">Đăng nhập tại đây</a>
        </div>
    </div>

</body>
</html>