<?php
session_start();
require_once '../db.php'; // Kết nối cơ sở dữ liệu

$thong_bao = "";

// --- XỬ LÝ 1: XÓA THỂ LOẠI ---
if (isset($_GET['xoa'])) {
    $id_xoa = intval($_GET['xoa']);
    try {
        // Kiểm tra xem có sách nào đang thuộc thể loại này không
        $stmt_check = $ket_noi->prepare("SELECT COUNT(*) FROM sach WHERE id_the_loai = ?");
        $stmt_check->execute([$id_xoa]);
        $co_sach = $stmt_check->fetchColumn();

        if ($co_sach > 0) {
            $thong_bao = "<div class='alert alert-danger'>❌ Không thể xóa! Hiện đang có $co_sach đầu sách thuộc thể loại này.</div>";
        } else {
            $stmt_xoa = $ket_noi->prepare("DELETE FROM the_loai WHERE id = ?");
            $stmt_xoa->execute([$id_xoa]);
            $_SESSION['msg'] = "<div class='alert alert-success'>✅ Đã xóa thể loại thành công!</div>";
            header("Location: admin_quan_ly_the_loai.php");
            exit;
        }
    } catch (Exception $e) {
        $thong_bao = "<div class='alert alert-danger'>❌ Lỗi hệ thống khi xóa: " . $e->getMessage() . "</div>";
    }
}

// --- XỬ LÝ 2: THÊM HOẶC CẬP NHẬT THỂ LOẠI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['luu_the_loai'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $ten_the_loai = trim($_POST['ten_the_loai']);

    if (empty($ten_the_loai)) {
        $thong_bao = "<div class='alert alert-danger'>❌ Tên thể loại không được để trống!</div>";
    } else {
        try {
            if ($id > 0) {
                // CẬP NHẬT
                $sql = "UPDATE the_loai SET ten_the_loai = ? WHERE id = ?";
                $stmt = $ket_noi->prepare($sql);
                $stmt->execute([$ten_the_loai, $id]);
                $thong_bao = "<div class='alert alert-success'>✅ Đã cập nhật tên thể loại thành công!</div>";
            } else {
                // THÊM MỚI
                $sql = "INSERT INTO the_loai (ten_the_loai) VALUES (?)";
                $stmt = $ket_noi->prepare($sql);
                $stmt->execute([$ten_the_loai]);
                $thong_bao = "<div class='alert alert-success'>✅ Đã thêm thể loại mới thành công!</div>";
            }
        } catch (Exception $e) {
            $thong_bao = "<div class='alert alert-danger'>❌ Tên thể loại bị trùng hoặc lỗi DB: " . $e->getMessage() . "</div>";
        }
    }
}

// --- XỬ LÝ 3: LẤY DỮ LIỆU ĐỂ ĐỔ VÀO FORM SỬA ---
$the_loai_sua = null;
if (isset($_GET['sua'])) {
    $id_sua = intval($_GET['sua']);
    $stmt_get = $ket_noi->prepare("SELECT * FROM the_loai WHERE id = ?");
    $stmt_get->execute([$id_sua]);
    $the_loai_sua = $stmt_get->fetch(PDO::FETCH_ASSOC);
}

// --- XỬ LÝ 4: LẤY TOÀN BỘ DANH SÁCH THỂ LOẠI VÀ ĐẾM SỐ SÁCH ĐI KÈM ---
$danh_sach_the_loai = [];
try {
    // Câu SQL thông minh vừa lấy thể loại vừa tính số đầu sách đang có trong kho của thể loại đó
    $sql_all = "SELECT tl.*, COUNT(s.id) AS tong_so_sach 
                FROM the_loai tl 
                LEFT JOIN sach s ON tl.id = s.id_the_loai 
                GROUP BY tl.id 
                ORDER BY tl.id DESC";
    $danh_sach_the_loai = $ket_noi->query($sql_all)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $thong_bao = "<div class='alert alert-danger'>❌ Không thể tải danh sách thể loại: " . $e->getMessage() . "</div>";
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
    <title>Admin - Quản Lý Thể Loại</title>
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
        .table-container { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); flex: 1; min-width: 500px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 14px 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #4a5568; font-weight: bold; font-size: 13px; text-transform: uppercase; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #4a5568; font-size: 13px; }
        input[type="text"] { width: 100%; padding: 11px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 14px; background-color: #fff; }
        input:focus { border-color: #3182ce; outline: none; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.15); }
        
        .btn { padding: 6px 14px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block; text-align: center; }
        .btn-edit { background-color: #3182ce; color: white; margin-right: 5px; }
        .btn-delete { background-color: #e53e3e; color: white; }
        .btn-submit { background-color: #e67e22; color: white; width: 100%; padding: 12px; font-size: 14px; margin-top: 10px; border-radius: 4px; font-weight: bold; border: none; cursor: pointer; transition: background 0.2s;}
        .btn-submit:hover { background-color: #d35400; }
        
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: bold;}
        .alert-success { background-color: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .alert-danger { background-color: #fed7d7; color: #742a2a; border: 1px solid #feb2b2; }
        
        .badge-count { background-color: #ebf8ff; color: #2b6cb0; border: 1px solid #bee3f8; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>📊 ADMIN Tạp Hóa Sách</h3>
    <a href="admin_don_hang_mua.php">📦 Quản lý Đơn Mua</a>
    <a href="admin_don_hang_thue.php">📋 Quản lý Đơn Thuê</a>
    <a href="admin_quan_ly_sach.php">📚 Quản lý Kho Sách</a>
    <a href="admin_quan_ly_the_loai.php" class="active">📁 Quản lý Thể Loại</a>
    <a href="../admin/index.php">🏠 Về Trang Chủ User</a>
</div>

<div class="main-content">
    <h2>📁 Quản Lý Danh Mục & Thể Loại Sách</h2>
    
    <?php echo $thong_bao; ?>

    <div class="grid-layout">
        <div class="form-container">
            <h3 style="color:#2d3748; margin-bottom: 18px; border-bottom: 2px solid #edf2f7; padding-bottom: 8px; font-size: 16px;">
                <?php echo $the_loai_sua ? "🔄 Cập Nhật Thể Loại ID #".$the_loai_sua['id'] : "➕ Thêm Thể Loại Mới"; ?>
            </h3>
            
            <form action="admin_quan_ly_the_loai.php" method="POST">
                <input type="hidden" name="id" value="<?php echo $the_loai_sua['id'] ?? 0; ?>">

                <div class="form-group">
                    <label>Tên danh mục / thể loại:</label>
                    <input type="text" name="ten_the_loai" value="<?php echo htmlspecialchars($the_loai_sua['ten_the_loai'] ?? ''); ?>" placeholder="Ví dụ: Tiểu thuyết, Kỹ năng sống..." required>
                </div>

                <button type="submit" name="luu_the_loai" class="btn btn-submit">
                    <?php echo $the_loai_sua ? "💾 Lưu Thay Đổi" : "🚀 Tạo Thể Loại"; ?>
                </button>
                
                <?php if ($the_loai_sua): ?>
                    <a href="admin_quan_ly_the_loai.php" style="display:block; text-align:center; margin-top:12px; color:#e53e3e; font-size:13px; text-decoration:none; font-weight:bold;">❌ Hủy sửa, quay lại thêm mới</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3 style="color:#2d3748; margin-bottom: 15px; font-size: 16px;">📂 Danh Sách Thể Loại Hiện Tại (<?php echo count($danh_sach_the_loai); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 80px;">Mã ID</th>
                        <th>Tên Thể Loại</th>
                        <th style="width: 150px; text-align: center;">Số Lượng Sách</th>
                        <th style="width: 160px; text-align: right;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($danh_sach_the_loai) == 0): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color:#a0aec0; padding: 20px;">Chưa có thể loại nào. Vui lòng thêm mới!</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($danh_sach_the_loai as $tl): ?>
                            <tr>
                                <td style="font-weight: bold; color: #718096;">#<?php echo $tl['id']; ?></td>
                                <td><span style="font-weight: 600; color:#2d3748;"><?php echo htmlspecialchars($tl['ten_the_loai']); ?></span></td>
                                <td style="text-align: center;">
                                    <span class="badge-count"><?php echo $tl['tong_so_sach']; ?> sách</span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="admin_quan_ly_the_loai.php?sua=<?php echo $tl['id']; ?>" class="btn btn-edit">✏️ Sửa</a>
                                    <a href="admin_quan_ly_the_loai.php?xoa=<?php echo $tl['id']; ?>" class="btn btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa thể loại này? Điều này chỉ thành công nếu không có sách nào thuộc thể loại.');">🗑️ Xóa</a>
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