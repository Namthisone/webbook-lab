<?php
session_start();
require_once 'db.php';

// --- BƯỚC 2: XỬ LÝ TĂNG GIẢM SỐ LƯỢNG QUA URL GỬI LÊN ---
if (isset($_GET['action']) && $_GET['action'] == 'update_quantity') {
    $ma_gio_update = $_GET['id_sach']; // Sử dụng mã giỏ hàng để định danh chính xác sản phẩm mua/thuê
    $thay_doi = intval($_GET['thay_doi']);
    
    if (isset($_SESSION['gio_hang'][$ma_gio_update])) {
        // Tính toán số lượng mới
        $_SESSION['gio_hang'][$ma_gio_update]['so_luong'] += $thay_doi;
        
        // Nếu giảm xuống dưới hoặc bằng 0, tự động xóa sách khỏi đơn thanh toán
        if ($_SESSION['gio_hang'][$ma_gio_update]['so_luong'] <= 0) {
            unset($_SESSION['gio_hang'][$ma_gio_update]);
        }
    }
    
    // Quay trở lại trang thanh toán để cập nhật lại toàn bộ giá tiền
    header("Location: thanh_toan.php");
    exit;
}

if (empty($_SESSION['gio_hang'])) {
    header("Location: index.php");
    exit;
}

$loi = "";
$thanh_cong = false;
$ma_qr_url = ""; 
$thong_tin_ck = [];
$id_don_mua = 0;  // Khởi tạo biến lưu ID đơn mua
$id_don_thue = 0; // Khởi tạo biến lưu ID đơn thuê

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ten_nguoi_nhan = trim($_POST['ten_nguoi_nhan']);
    $so_dien_thoai = trim($_POST['so_dien_thoai']);
    $dia_chi = trim($_POST['dia_chi']);
    $phuong_thuc_tt = $_POST['phuong_thuc_tt']; 
    $ngay_thue_chon = isset($_POST['ngay_thue']) ? $_POST['ngay_thue'] : [];

    if (empty($ten_nguoi_nhan) || empty($so_dien_thoai) || empty($dia_chi)) {
        $loi = "Vui lòng điền đầy đủ thông tin giao hàng bắt buộc!";
    } else {
        $id_nguoi_dung = isset($_SESSION['user']) ? $_SESSION['user']['id'] : null;
        $email_khach_hang = isset($_SESSION['user']['email']) ? $_SESSION['user']['email'] : ($id_nguoi_dung ? 'user'.$id_nguoi_dung.'@gmail.com' : 'khachvonglai@gmail.com');

        $ket_noi->beginTransaction();
        try {
            // --- KIỂM TRA SỐ LƯỢNG TỒN KHO TRƯỚC KHI ĐẶT ---
            foreach ($_SESSION['gio_hang'] as $ma_gio => $sp) {
                $stmt_check_ton = $ket_noi->prepare("SELECT ten_sach, so_luong_ton FROM sach WHERE id = ?");
                $stmt_check_ton->execute([$sp['id']]);
                $sach_kho = $stmt_check_ton->fetch(PDO::FETCH_ASSOC);

                if (!$sach_kho) {
                    throw new Exception("Sách '" . $sp['ten_sach'] . "' không tồn tại trong hệ thống!");
                }
                
                if ($sach_kho['so_luong_ton'] <= 0) {
                    throw new Exception("Sách <strong>'" . $sach_kho['ten_sach'] . "'</strong> đã hết hàng (Số lượng tồn bằng 0). Vui lòng xóa khỏi giỏ hàng!");
                }

                if ($sach_kho['so_luong_ton'] < $sp['so_luong']) {
                    throw new Exception("Sách <strong>'" . $sach_kho['ten_sach'] . "'</strong> không đủ số lượng trong kho (Còn tồn: " . $sach_kho['so_luong_ton'] . ", bạn cần: " . $sp['so_luong'] . "). Vui lòng giảm số lượng!");
                }
            }

            $co_don_mua = false;
            $co_don_thue = false;
            $sach_mua = [];
            $sach_thue = [];
            $tong_tien_cuoi_cung = 0;

            foreach ($_SESSION['gio_hang'] as $ma_gio => $sp) {
                if ($sp['hinh_thuc'] == 'mua') {
                    $sach_mua[] = $sp;
                    $co_don_mua = true;
                    $tong_tien_cuoi_cung += $sp['gia'] * $sp['so_luong'];
                } else {
                    $sp['so_ngay_thue'] = isset($ngay_thue_chon[$ma_gio]) ? intval($ngay_thue_chon[$ma_gio]) : 7;
                    $sach_thue[] = $sp;
                    $co_don_thue = true;
                    $tong_tien_cuoi_cung += ($sp['gia'] * $sp['so_luong'] * $sp['so_ngay_thue']);
                }
            }

            $ma_don_mo_ta = "WebBook" . time();

            // 1. LƯU ĐƠN MUA
            if ($co_don_mua) {
                $tong_tien_mua = 0;
                foreach ($sach_mua as $sm) { 
                    $tong_tien_mua += $sm['gia'] * $sm['so_luong']; 
                }
                
                $sql_mua = "INSERT INTO don_hang_mua (id_nguoi_dung, ten_khach_hang, email_khach_hang, so_dien_thoai, dia_chi_giao_hang, tong_tien, trang_thai, ngay_dat_hang) 
                            VALUES (?, ?, ?, ?, ?, ?, 'cho_duyet', NOW())";
                $stmt_don_mua = $ket_noi->prepare($sql_mua);
                $stmt_don_mua->execute([$id_nguoi_dung, $ten_nguoi_nhan, $email_khach_hang, $so_dien_thoai, $dia_chi, $tong_tien_mua]);
                $id_don_mua = $ket_noi->lastInsertId(); // Ghi nhận ID đơn mua thành công
                
                foreach ($sach_mua as $sm) {
                    $stmt_ct_mua = $ket_noi->prepare("INSERT INTO chi_tiet_mua (id_don_hang, id_sach, so_luong, gia_luc_mua) VALUES (?, ?, ?, ?)");
                    $stmt_ct_mua->execute([$id_don_mua, $sm['id'], $sm['so_luong'], $sm['gia']]);

                    // CẬP NHẬT TRỪ SỐ LƯỢNG TỒN TRONG KHO
                    $stmt_tru_kho = $ket_noi->prepare("UPDATE sach SET so_luong_ton = so_luong_ton - ? WHERE id = ?");
                    $stmt_tru_kho->execute([$sm['so_luong'], $sm['id']]);
                }
            }

            // 2. LƯU ĐƠN THUÊ
            if ($co_don_thue) {
                $tong_tien_thue = 0;
                $max_days = 7; 
                foreach ($sach_thue as $st) { 
                    $tong_tien_thue += ($st['gia'] * $st['so_luong'] * $st['so_ngay_thue']); 
                    if ($st['so_ngay_thue'] > $max_days) {
                        $max_days = $st['so_ngay_thue'];
                    }
                }
                
                $ngay_thue_dt = date('Y-m-d');
                $han_tra_dt = date('Y-m-d', strtotime("+$max_days days"));
                $tien_coc = 200000; 

                $sql_thue = "INSERT INTO don_hang_thue (id_nguoi_dung, ten_khach_hang, email_khach_hang, so_dien_thoai, ngay_thue, han_tra_du_kien, tien_coc, tong_tien_thue, trang_thai, ngay_tao_don) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cho_duyet', NOW())";
                $stmt_don_thue = $ket_noi->prepare($sql_thue);
                $stmt_don_thue->execute([$id_nguoi_dung, $ten_nguoi_nhan, $email_khach_hang, $so_dien_thoai, $ngay_thue_dt, $han_tra_dt, $tien_coc, $tong_tien_thue]);
                $id_don_thue = $ket_noi->lastInsertId(); // Ghi nhận ID đơn thuê thành công
                
                foreach ($sach_thue as $st) {
                    $stmt_ct_thue = $ket_noi->prepare("INSERT INTO chi_tiet_thue (id_don_thue, id_sach, so_luong, gia_thue_mot_ngay) VALUES (?, ?, ?, ?)");
                    $stmt_ct_thue->execute([$id_don_thue, $st['id'], $st['so_luong'], $st['gia']]);

                    // CẬP NHẬT TRỪ SỐ LƯỢNG TỒN TRONG KHO (ĐỐI VỚI ĐƠN THUÊ)
                    $stmt_tru_kho_thue = $ket_noi->prepare("UPDATE sach SET so_luong_ton = so_luong_ton - ? WHERE id = ?");
                    $stmt_tru_kho_thue->execute([$st['so_luong'], $st['id']]);
                }
            }

            $ket_noi->commit();
            $_SESSION['gio_hang'] = []; 
            $thanh_cong = true;

            if ($phuong_thuc_tt === 'qr_code') {
                $NGAN_HANG = "MB"; 
                $STK = "9789517349"; 
                $TEN_CHU_TK = "PHAN QUOC PHI"; 
                
                $ma_qr_url = "https://img.vietqr.io/image/{$NGAN_HANG}-{$STK}-compact2.png?amount={$tong_tien_cuoi_cung}&addInfo=" . urlencode($ma_don_mo_ta) . "&accountName=" . urlencode($TEN_CHU_TK);
                
                $thong_tin_ck = [
                    'ngan_hang' => $NGAN_HANG,
                    'stk' => $STK,
                    'ten' => $TEN_CHU_TK,
                    'tien' => $tong_tien_cuoi_cung,
                    'noi_dung' => $ma_don_mo_ta
                ];
            }

        } catch (Exception $e) {
            $ket_noi->rollBack();
            $loi = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Xác Nhận Đơn Hàng & Thanh Toán - Tạp Hóa Sách</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #f5f6f9; color: #333; padding: 20px; }
        .wrapper { max-width: 1100px; margin: 30px auto; background: white; padding: 35px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        h2 { color: #2c3e50; margin-bottom: 25px; padding-bottom: 10px; border-bottom: 2px solid #eee; }
        .flex-container { display: flex; gap: 30px; flex-wrap: wrap; }
        .form-info { flex: 1; min-width: 350px; background-color: #f8f9fa; padding: 25px; border-radius: 6px; border: 1px solid #e2e8f0; }
        .cart-summary { flex: 1.2; min-width: 400px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #4a5568; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 12px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background-color: #edf2f7; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; color: white; }
        .badge-buy { background-color: #2ecc71; }
        .badge-rent { background-color: #3498db; }
        .final-total { text-align: right; margin-top: 20px; font-size: 20px; font-weight: bold; color: #e74c3c; background: #fff5f5; padding: 15px; border-radius: 4px; border: 1px dashed #feb2b2; }
        .btn-submit { width: 100%; padding: 14px; background-color: #e67e22; border: none; color: white; font-weight: bold; border-radius: 6px; font-size: 16px; cursor: pointer; margin-top: 20px; }
        .btn-submit:hover { background-color: #d35400; }
        .alert { padding: 15px; border-radius: 6px; text-align: center; margin-bottom: 20px; }
        .alert-danger { background-color: #fed7d7; color: #9b2c2c; text-align: left; line-height: 1.5; border: 1px solid #f5c6cb; }
        .alert-success { background-color: #c6f6d5; color: #22543d; text-align: left; padding: 30px; }
        .qr-box { text-align: center; background: #f8f9fa; border: 2px solid #3498db; border-radius: 8px; padding: 20px; margin-top: 20px; }
        .radio-group { background: white; padding: 12px; border: 1px solid #cbd5e0; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; gap: 10px; cursor: pointer; }
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            font-family: 'Segoe UI', sans-serif;
            font-size: 15px;
            font-weight: 600;
            color: #ffffff;
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            border-radius: 30px;
            text-decoration: none;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            transform: translateX(-4px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.4);
        }
    </style>
</head>
<body>

    <div>
        <a href="index.php" class="btn-back">⬅ Quay lại trang chủ</a>
    </div>
    </style>
</head>
<body>

<div class="wrapper">
    <h2>💳 Thông Tin Đặt Hàng & Thanh Toán</h2>

    <?php if ($thanh_cong): ?>
        <div class="alert alert-success">
            <h3 style="margin-bottom: 10px; text-align:center; font-size: 24px;">🎉 ĐẶT HÀNG THÀNH CÔNG!</h3>
            <p style="text-align:center; color:#555; margin-bottom: 15px;">Đơn hàng của bạn đã được ghi nhận và đồng bộ chính xác vào cơ sở dữ liệu hệ thống.</p>
            
            <div style="background: #ffffff; padding: 18px; border-radius: 8px; border: 2px dashed #2ecc71; margin: 20px auto; max-width: 500px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                <?php if ($id_don_mua > 0): ?>
                    <p style="font-size: 16px; margin-bottom: 8px; color: #2c3e50;">
                        📦 Mã đơn hàng mua sách: <strong style="color: #e67e22; font-size: 20px;">#<?php echo $id_don_mua; ?></strong>
                    </p>
                <?php endif; ?>
                
                <?php if ($id_don_thue > 0): ?>
                    <p style="font-size: 16px; color: #2c3e50;">
                        📋 Mã đơn hàng thuê sách: <strong style="color: #2980b9; font-size: 20px;">#<?php echo $id_don_thue; ?></strong>
                    </p>
                <?php endif; ?>
                <p style="font-size: 12px; color: #7f8c8d; margin-top: 12px; border-top: 1px solid #eee; padding-top: 8px;">
                    💡 <em>Vui lòng lưu lại mã số trên để điền vào Form hỗ trợ / Khiếu nại khi cần thiết.</em>
                </p>
            </div>
            
            <?php if (!empty($ma_qr_url)): ?>
                <div class="qr-box">
                    <h4 style="color: #2980b9; margin-bottom: 5px;">MÃ QR THANH TOÁN CHUYỂN KHOẢN</h4>
                    <p style="font-size:13px; color:#7f8c8d; margin-bottom:15px;">Quét mã QR dưới đây để thực hiện giao dịch tự động</p>
                    <img src="<?php echo $ma_qr_url; ?>" alt="QR Code" style="max-width: 250px; border: 1px solid #ddd; padding: 5px; background: #fff;">
                    <div style="margin-top: 15px; text-align: left; display: inline-block; font-size: 14px; line-height: 1.6; color:#4a5568;">
                        🔹 Ngân hàng: <strong><?php echo $thong_tin_ck['ngan_hang']; ?></strong><br>
                        🔹 Số tài khoản: <strong><?php echo $thong_tin_ck['stk']; ?></strong><br>
                        🔹 Tên tài khoản: <strong><?php echo $thong_tin_ck['ten']; ?></strong><br>
                        🔹 Số tiền: <strong style="color:#e74c3c;"><?php echo number_format($thong_tin_ck['tien'], 0, ',', '.'); ?> đ</strong><br>
                        🔹 Nội dung CK: <strong style="color:#2ecc71; background:#eefaf2; padding:2px 5px;"><?php echo $thong_tin_ck['noi_dung']; ?></strong>
                    </div>
                </div>
            <?php else: ?>
                <p style="text-align:center; font-weight:bold; color: #e67e22; margin-top: 15px;">💵 Bạn đã lựa chọn hình thức trả tiền mặt khi nhận hàng (COD).</p>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 25px; display: flex; justify-content: center; gap: 15px;">
                <a href="index.php" style="background: #2c3e50; color: white; padding: 12px 25px; border-radius: 4px; text-decoration: none; font-weight: bold;">Quay lại trang chủ</a>
                <a href="khieu_nai.php" style="background: #e74c3c; color: white; padding: 12px 25px; border-radius: 4px; text-decoration: none; font-weight: bold;"><i class="fa-solid fa-triangle-exclamation"></i> Gửi hỗ trợ / Khiếu nại</a>
            </div>
        </div>
    <?php else: ?>

        <?php if(!empty($loi)): ?>
            <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Lỗi đặt hàng:</strong> <?php echo $loi; ?>
            </div>
        <?php endif; ?>

        <form action="thanh_toan.php" method="POST">
            <div class="flex-container">
                <div class="form-info">
                    <h3 style="color:#2c3e50; margin-bottom:15px; border-bottom:1px solid #ddd; padding-bottom:5px;">📋 Địa chỉ nhận sách</h3>
                    
                    <div class="form-group">
                        <label>Tên người nhận:</label>
                        <input type="text" name="ten_nguoi_nhan" placeholder="Nhập họ tên người nhận" value="<?php echo isset($_SESSION['user']['ho_ten']) ? $_SESSION['user']['ho_ten'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Số điện thoại:</label>
                        <input type="text" name="so_dien_thoai" placeholder="Nhập số điện thoại giao hàng" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Địa chỉ chi tiết:</label>
                        <textarea name="dia_chi" rows="3" placeholder="Số nhà, tên đường, phường/xã, quận/huyện..." required></textarea>
                    </div>

                    <h3 style="color:#2c3e50; margin-top:25px; margin-bottom:15px; border-bottom:1px solid #ddd; padding-bottom:5px;">💳 Phương thức thanh toán</h3>
                    <label class="radio-group">
                        <input type="radio" name="phuong_thuc_tt" value="cod" checked>
                        <div>💵 Trả tiền mặt khi nhận hàng (COD)</div>
                    </label>
                    <label class="radio-group">
                        <input type="radio" name="phuong_thuc_tt" value="qr_code">
                        <div>📱 Chuyển khoản nhanh qua mã QR Ngân hàng</div>
                    </label>
                </div>

                <div class="cart-summary">
                    <h3 style="color:#2c3e50; margin-bottom:15px; padding-bottom:5px;">🛒 Chi tiết sách trong đơn</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Tên Sách</th>
                                <th>Hình Thức</th>
                                <th>Đơn Giá</th>
                                <th style="text-align: center;">S.Lượng</th>
                                <th>Thời Gian</th>
                                <th>Thành Tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['gio_hang'] as $ma_gio => $sp): ?>
                            <tr class="item-row" data-gia="<?php echo $sp['gia']; ?>" data-soluong="<?php echo $sp['so_luong']; ?>" data-hinhthuc="<?php echo $sp['hinh_thuc']; ?>" data-magio="<?php echo $ma_gio; ?>">
                                <td><strong><?php echo $sp['ten_sach']; ?></strong></td>
                                <td><span class="badge <?php echo ($sp['hinh_thuc']=='mua') ? 'badge-buy' : 'badge-rent'; ?>"><?php echo ($sp['hinh_thuc']=='mua') ? 'MUA' : 'THUÊ'; ?></span></td>
                                <td><?php echo number_format($sp['gia'], 0, ',', '.'); ?> đ</td>
                                
                                <td style="text-align: center; min-width: 120px;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 5px;">
                                        <button type="button" class="btn-soluong" onclick="capNhatSoLuong('<?php echo $ma_gio; ?>', -1)" style="width: 28px; height: 28px; background: #e2e8f0; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px;">-</button>
                                        
                                        <input type="text" value="<?php echo $sp['so_luong']; ?>" readonly style="width: 35px; text-align: center; border: 1px solid #cbd5e0; border-radius: 4px; height: 28px; font-weight: bold; background: #fff;">
                                        
                                        <button type="button" class="btn-soluong" onclick="capNhatSoLuong('<?php echo $ma_gio; ?>', 1)" style="width: 28px; height: 28px; background: #e2e8f0; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; font-size: 14px;">+</button>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php if ($sp['hinh_thuc'] == 'thue'): ?>
                                        <select name="ngay_thue[<?php echo $ma_gio; ?>]" class="select-days" onchange="tinhToanTongTienDonHang()">
                                            <option value="3">3 ngày</option>
                                            <option value="7" selected>7 ngày</option>
                                            <option value="14">14 ngày</option>
                                            <option value="30">30 ngày</option>
                                        </select>
                                    <?php else: ?>
                                        <span style="color:#a0aec0;">Mua đứt</span>
                                    <?php endif; ?>
                                </td>
                                <td class="item-total" style="font-weight:bold; color:#2d3748;">0 đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="final-total">
                        Tổng số tiền cần thanh toán: <span id="txt-tong-tien">0 đ</span>
                    </div>

                    <button type="submit" class="btn-submit">🚀 Xác Nhận Đặt Hàng Ngay</button>
                </div>
            </div>

        </form>
    <?php endif; ?>
</div>

<script>
function tinhToanTongTienDonHang() {
    let rows = document.querySelectorAll('.item-row');
    let tongTienDonHang = 0;
    rows.forEach(row => {
        let gia = parseInt(row.getAttribute('data-gia'));
        let soLuong = parseInt(row.getAttribute('data-soluong'));
        let hinhThuc = row.getAttribute('data-hinhthuc');
        let thanhTienItem = gia * soLuong;
        if (hinhThuc === 'thue') {
            let selectDays = row.querySelector('.select-days');
            thanhTienItem = thanhTienItem * parseInt(selectDays.value);
        }
        row.querySelector('.item-total').innerText = thanhTienItem.toLocaleString('vi-VN') + ' đ';
        tongTienDonHang += thanhTienItem;
    });
    document.getElementById('txt-tong-tien').innerText = tongTienDonHang.toLocaleString('vi-VN') + ' đ';
}
document.addEventListener("DOMContentLoaded", function() { tinhToanTongTienDonHang(); });

// --- BƯỚC 3: ĐOẠN JAVASCRIPT ĐIỀU HƯỚNG TĂNG GIẢM SỐ LƯỢNG ---
function capNhatSoLuong(idSach, thayDoi) {
    // Điều hướng trang để xử lý tăng giảm và tải lại giỏ hàng một cách đồng bộ
    window.location.href = 'thanh_toan.php?action=update_quantity&id_sach=' + idSach + '&thay_doi=' + thayDoi;
}
</script>
</body>
</html>