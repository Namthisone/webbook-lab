<?php
session_start();
require_once '../db.php'; // Kết nối cơ sở dữ liệu

$thong_bao = "";

// --- XỬ LÝ 1: XÓA NGƯỜI DÙNG ---
if (isset($_GET['xoa'])) {
    $id_xoa = intval($_GET['xoa']);
    
    // Ngăn chặn admin tự xóa chính mình bằng cách check ID trong Session
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $id_xoa) {
        $thong_bao = "<div class='alert alert-danger'>❌ Bạn không thể tự xóa tài khoản của chính mình!</div>";
    } else {
        try {
            $stmt_xoa = $ket_noi->prepare("DELETE FROM nguoi_dung WHERE id = ?");
            $stmt_xoa->execute([$id_xoa]);
            $_SESSION['msg'] = "<div class='alert alert-success'>✅ Đã xóa người dùng thành công!</div>";
            header("Location: admin_quan_ly_nguoi_dung.php");
            exit;
        } catch (Exception $e) {
            $thong_bao = "<div class='alert alert-danger'>❌ Không thể xóa (Người dùng này có thể đã có lịch sử đơn hàng): " . $e->getMessage() . "</div>";
        }
    }
}

// --- XỬ LÝ 2: THÊM HOẶC CẬP NHẬT TÀI KHOẢN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['luu_nguoi_dung'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $ho_ten = trim($_POST['ho_ten']);
    $email = trim($_POST['email']);
    $so_dien_thoai = trim($_POST['so_dien_thoai']);
    $vai_tro = trim($_POST['vai_tro'] ?? 'khach_hang');
    $mat_khau_moi = trim($_POST['mat_khau']);

    if (empty($ho_ten) || empty($email)) {
        $thong_bao = "<div class='alert alert-danger'>❌ Họ tên và Email không được để trống!</div>";
    } else {
        try {
            if ($id > 0) {
                // CẬP NHẬT TÀI KHOẢN
                if (!empty($mat_khau_moi)) {
                    // Nếu admin có nhập mật khẩu mới -> đổi luôn mật khẩu (nên mã hóa md5 hoặc password_hash tùy dự án của bạn)
                    $sql = "UPDATE nguoi_dung SET ho_ten = ?, email = ?, so_dien_thoai = ?, vai_tro = ?, mat_khau = ? WHERE id = ?";
                    $stmt = $ket_noi->prepare($sql);
                    $stmt->execute([$ho_ten, $email, $so_dien_thoai, $vai_tro, $mat_khau_moi, $id]);
                } else {
                    // Không nhập mật khẩu mới -> giữ nguyên mật khẩu cũ
                    $sql = "UPDATE nguoi_dung SET ho_ten = ?, email = ?, so_dien_thoai = ?, vai_tro = ? WHERE id = ?";
                    $stmt = $ket_noi->prepare($sql);
                    $stmt->execute([$ho_ten, $email, $so_dien_thoai, $vai_tro, $id]);
                }
                $thong_bao = "<div class='alert alert-success'>✅ Cập nhật thông tin tài khoản thành công!</div>";
            } else {
                // THÊM TÀI KHOẢN MỚI
                if (empty($mat_khau_moi)) { $mat_khau_moi = '123456'; } // Mật khẩu mặc định nếu trống
                $sql = "INSERT INTO nguoi_dung (ho_ten, email, so_dien_thoai, vai_tro, mat_khau) VALUES (?, ?, ?, ?, ?)";
                $stmt = $ket_noi->prepare($sql);
                $stmt->execute([$ho_ten, $email, $so_dien_thoai, $vai_tro, $mat_khau_moi]);
                $thong_bao = "<div class='alert alert-success'>✅ Đã tạo tài khoản thành viên mới thành công!</div>";
            }
        } catch (Exception $e) {
            $thong_bao = "<div class='alert alert-danger'>❌ Lỗi: Email bị trùng hoặc không hợp lệ: " . $e->getMessage() . "</div>";
        }
    }
}

// --- XỬ LÝ 3: LẤY DỮ LIỆU ĐỂ ĐỔ VÀO FORM SỬA ---
$user_sua = null;
if (isset($_GET['sua'])) {
    $id_sua = intval($_GET['sua']);
    $stmt_get = $ket_noi->prepare("SELECT * FROM nguoi_dung WHERE id = ?");
    $stmt_get->execute([$id_sua]);
    $user_sua = $stmt_get->fetch(PDO::FETCH_ASSOC);
}

// --- XỬ LÝ 4: LẤY TOÀN BỘ DANH SÁCH THÀNH VIÊN ---
$danh_sach_user = [];
try {
    $sql_all = "SELECT * FROM nguoi_dung ORDER BY id DESC";
    $danh_sach_user = $ket_noi->query($sql_all)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $thong_bao = "<div class='alert alert-danger'>❌ Không thể tải danh sách tài khoản: " . $e->getMessage() . "</div>";
}

if (isset($_SESSION['msg'])) {
    $thong_bao = $_SESSION['msg'];
    unset($_SESSION['msg']);
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Admin - Quản Lý Thành Viên</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f4f6f9; display: flex; min-height: 100vh; }
        
        /* Sidebar Admin */
        .sidebar { width: 260px; background-color: #2c3e50; color: white; padding: 20px 0; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h3 { text-align: left; padding: 0 20px 20px 20px; margin-bottom: 20px; color: #e67e22; border-bottom: 1px solid #34495e; font-size: 16px; letter-spacing: 1px;}
        .sidebar a { display: block; color: #bdc3c7; padding: 14px 20px; text-decoration: none; font-size: 14px; font-weight: bold; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; border-left: 4px solid #e67e22; padding-left: 16px; }

        /* Vùng nội dung chính */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        h2 { color: #333; margin-bottom: 20px; font-size: 24px; display: flex; align-items: center; gap: 10px; }
        
        .grid-layout { display: flex; gap: 25px; flex-wrap: wrap; align-items: flex-start; }
        .form-container { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); width: 360px; flex-shrink: 0; }
        .table-container { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); flex: 1; min-width: 550px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #4a5568; font-weight: bold; font-size: 13px; text-transform: uppercase; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #4a5568; font-size: 13px; }
        input[type="text"], input[type="password"], select { width: 100%; padding: 11px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 14px; background-color: #fff; }
        input:focus, select:focus { border-color: #3182ce; outline: none; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15); }
        
        .btn { padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block; text-align: center; }
        .btn-edit { background-color: #3182ce; color: white; margin-right: 5px; }
        .btn-delete { background-color: #e53e3e; color: white; }
        .btn-submit { background-color: #3182ce; color: white; width: 100%; padding: 12px; font-size: 14px; margin-top: 10px; border-radius: 4px; font-weight: bold; border: none; cursor: pointer; transition: background 0.2s;}
        .btn-submit:hover { background-color: #2b6cb0; }
        
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: bold;}
        .alert-success { background-color: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .alert-danger { background-color: #fed7d7; color: #742a2a; border: 1px solid #feb2b2; }
        
        /* Badge phân quyền */
        .badge-role { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; }
        .role-admin { background-color: #fed7d7; color: #9b1c1c; border: 1px solid #fbb6b6; }
        .role-user { background-color: #e2e8f0; color: #4a5568; }
        .text-muted { color: #718096; font-size: 12px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>📊 ADMIN Tạp Hóa Sách</h3>
    <a href="admin_don_hang_mua.php">📦 Quản lý Đơn Mua</a>
    <a href="admin_don_hang_thue.php">📋 Quản lý Đơn Thuê</a>
    <a href="admin_quan_ly_sach.php">📚 Quản lý Kho Sách</a>
    <a href="admin_quan_ly_the_loai.php">📁 Quản lý Thể Loại</a>
    <a href="admin_quan_ly_nguoi_dung.php" class="active">👥 Quản lý Người Dùng</a>
    <a href="../admin/index.php">🏠 Về Trang Chủ User</a>
</div>

<div class="main-content">
    <h2>👥 Quản Lý Tài Khoản Thành Viên & Ban Quản Trị</h2>
    
    <?php echo $thong_bao; ?>

    <div class="grid-layout">
        <div class="form-container">
            <h3 style="color:#2d3748; margin-bottom: 18px; border-bottom: 2px solid #edf2f7; padding-bottom: 8px; font-size: 16px;">
                <?php echo $user_sua ? "🔄 Sửa Tài Khoản ID #".$user_sua['id'] : "➕ Tạo Tài Khoản Mới"; ?>
            </h3>
            
            <form action="admin_quan_ly_nguoi_dung.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $user_sua['id'] ?? 0; ?>">

                <div class="form-group">
                    <label>Họ và tên:</label>
                    <input type="text" name="ho_ten" value="<?php echo htmlspecialchars($user_sua['ho_ten'] ?? ''); ?>" placeholder="Nhập đầy đủ họ tên..." required>
                </div>

                <div class="form-group">
                    <label>Email tài khoản:</label>
                    <input type="text" name="email" value="<?php echo htmlspecialchars($user_sua['email'] ?? ''); ?>" placeholder="Nhập địa chỉ email..." required>
                </div>

                <div class="form-group">
                    <label>Số điện thoại:</label>
                    <input type="text" name="so_dien_thoai" value="<?php echo htmlspecialchars($user_sua['so_dien_thoai'] ?? ''); ?>" placeholder="Nhập số điện thoại...">
                </div>

                <div class="form-group">
                    <label>Phân quyền cấp bậc:</label>
                    <select name="vai_tro" required>
                        <option value="khach_hang" <?php if(isset($user_sua['vai_tro']) && $user_sua['vai_tro'] == 'khach_hang') echo 'selected'; ?>>🧑 Khách Hàng (Thành viên)</option>
                        <option value="admin" <?php if(isset($user_sua['vai_tro']) && $user_sua['vai_tro'] == 'admin') echo 'selected'; ?>>🛡️ Ban Quản Trị (Admin)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Mật khẩu:</label>
                    <input type="password" name="mat_khau" placeholder="<?php echo $user_sua ? 'Để trống nếu giữ nguyên mật khẩu cũ' : 'Nhập mật khẩu...'; ?>">
                    <?php if($user_sua): ?>
                        <span class="text-muted">Mật khẩu hiện tại đang được ẩn mật.</span>
                    <?php endif; ?>
                </div>

                <button type="submit" name="luu_nguoi_dung" class="btn btn-submit">
                    <?php echo $user_sua ? "💾 Lưu Thay Đổi" : "🚀 Cấp Tài Khoản"; ?>
                </button>
                
                <?php if ($user_sua): ?>
                    <a href="admin_quan_ly_nguoi_dung.php" style="display:block; text-align:center; margin-top:12px; color:#e53e3e; font-size:13px; text-decoration:none; font-weight:bold;">❌ Hủy sửa, quay lại thêm mới</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3 style="color:#2d3748; margin-bottom: 15px; font-size: 16px;">📂 Danh Sách Tài Khoản (<?php echo count($danh_sach_user); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 60px;">Mã</th>
                        <th>Thông Tin Thành Viên</th>
                        <th>Phân Quyền</th>
                        <th style="width: 120px; text-align: right;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($danh_sach_user) == 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color:#a0aec0; padding: 20px;">Hệ thống chưa có người dùng nào đăng ký.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($danh_sach_user as $user): ?>
                            <tr>
                                <td style="font-weight: bold; color: #718096;">#<?php echo $user['id']; ?></td>
                                <td>
                                    <div style="font-weight: 600; color:#2d3748;"><?php echo htmlspecialchars($user['ho_ten']); ?></div>
                                    <div class="text-muted">📧 Email: <strong><?php echo htmlspecialchars($user['email']); ?></strong></div>
                                    <div class="text-muted">📞 SĐT: <?php echo htmlspecialchars($user['so_dien_thoai'] ?: 'Chưa cập nhật'); ?></div>
                                </td>
                                <td>
                                    <?php if($user['vai_tro'] == 'admin'): ?>
                                        <span class="badge-role role-admin">🛡️ Quản trị viên</span>
                                    <?php else: ?>
                                        <span class="badge-role role-user">🧑 Khách hàng</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="admin_quan_ly_nguoi_dung.php?sua=<?php echo $user['id']; ?>" class="btn btn-edit">✏️</a>
                                    <a href="admin_quan_ly_nguoi_dung.php?xoa=<?php echo $user['id']; ?>" class="btn btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa thành viên này? Việc này sẽ xóa vĩnh viễn quyền truy cập của họ.');">🗑️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>