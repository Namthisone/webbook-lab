<?php
require_once 'db.php';
session_start();


if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['vai_tro'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

$loi = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten_dang_nhap = trim($_POST['ten_dang_nhap']);
    $mat_khau = $_POST['mat_khau'];

    if (empty($ten_dang_nhap) || empty($mat_khau)) {
        $loi = "Vui lòng nhập đầy đủ tài khoản và mật khẩu!";
    } else {
        $mat_khau_ma_hoa = md5($mat_khau);

        // Kiểm tra tài khoản trong database
        $stmt = $ket_noi->prepare("SELECT * FROM nguoi_dung WHERE ten_dang_nhap = ? AND mat_khau = ?");
        $stmt->execute([$ten_dang_nhap, $mat_khau_ma_hoa]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $_SESSION['user'] = $user;
            if ($user['vai_tro'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit;
        } else {
            error_log("[WEBBOOK-LOGIN-FAIL] IP=" . $_SERVER['REMOTE_ADDR'] . " user=" . $ten_dang_nhap);
            $loi = "Tài khoản hoặc mật khẩu không chính xác!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - Tạp Hóa Sách</title>
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
        .login-wrapper {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 45px 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 420px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .login-wrapper h2 {
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

        .form-group { margin-bottom: 22px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #cbd5e1; font-size: 14px; }
        
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 13px 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            background-color: rgba(15, 23, 42, 0.6);
            color: #fff;
        }
        
        /* Hiệu ứng phát sáng Neon khi focus */
        input:focus { 
            border-color: #38bdf8; 
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.25); 
        }
        
        /* Nút bấm Gradient Cam - Đỏ hoàng hôn rực rỡ */
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(234, 88, 12, 0.3);
            transition: all 0.3s ease;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }
        
        .btn-login:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 20px rgba(234, 88, 12, 0.5); 
            filter: brightness(1.1);
        }

        /* Thông báo lỗi đỏ Neon mượt */
        .alert {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 13px;
            font-weight: 600;
            background-color: rgba(239, 68, 68, 0.15);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.3);
            text-align: center;
        }
        
        .footer-links { text-align: center; margin-top: 28px; font-size: 14px; color: #94a3b8; }
        .footer-links a { color: #38bdf8; text-decoration: none; font-weight: bold; margin-left: 3px; transition: color 0.2s; }
        .footer-links a:hover { color: #7dd3fc; text-decoration: underline; }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <h2>🔑 ĐĂNG NHẬP</h2>
        <div class="subtitle">Tạp Hóa Sách Xin Chào !</div>
        
        <?php if(!empty($loi)): ?>
            <div class="alert">❌ <?php echo $loi; ?></div>
        <?php endif; ?>

        <form action="dang_nhap.php" method="POST">
            <div class="form-group">
                <label>Tên đăng nhập / Tài khoản:</label>
                <input type="text" name="ten_dang_nhap" placeholder="Nhập tài khoản đăng nhập..." required autocomplete="off">
            </div>

            <div class="form-group">
                <label>Mật khẩu bảo mật:</label>
                <input type="password" name="mat_khau" placeholder="Nhập mật khẩu..." required>
            </div>

            <button type="submit" class="btn-login">ĐĂNG NHẬP HỆ THỐNG</button>
        </form>

        <div class="footer-links">
            Chưa có tài khoản? <a href="dang_ky.php">Đăng ký thành viên</a>
        </div>
    </div>

</body>
</html>