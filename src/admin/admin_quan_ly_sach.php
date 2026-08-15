<?php
session_start();
require_once '../db.php'; // Kết nối cơ sở dữ liệu của bạn

$thong_bao = "";

// --- XỬ LÝ 1: XÓA SÁCH ---
if (isset($_GET['xoa'])) {
    $id_xoa = intval($_GET['xoa']);
    try {
        $stmt_xoa = $ket_noi->prepare("DELETE FROM sach WHERE id = ?");
        $stmt_xoa->execute([$id_xoa]);
        $_SESSION['msg'] = "<div class='alert alert-success'>✅ Đã xóa sách thành công khỏi hệ thống!</div>";
        header("Location: admin_quan_ly_sach.php");
        exit;
    } catch (Exception $e) {
        $thong_bao = "<div class='alert alert-danger'>❌ Không thể xóa sách này (Sách đã có trong đơn hàng của khách): " . $e->getMessage() . "</div>";
    }
}

// --- XỬ LÝ 2: THÊM HOẶC CẬP NHẬT SÁCH ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['luu_sach'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $ten_sach = trim($_POST['ten_sach']);
    $tac_gia = trim($_POST['tac_gia'] ?? ''); // Thêm xử lý nhận Tác giả
    $id_the_loai = intval($_POST['id_the_loai']);
    $gia_ban = floatval($_POST['gia_ban']);
    $so_luong_ton = intval($_POST['so_luong_ton']);
    $mo_ta_chi_tiet = trim($_POST['mo_ta_chi_tiet'] ?? ''); // Đổi thành mo_ta_chi_tiet theo đúng DB
    $noi_bat = isset($_POST['noi_bat']) ? 1 : 0;
    
    // Giữ lại ảnh cũ mặc định
    $anh_bia = $_POST['anh_bia_cu'] ?? '';
    
    // Nếu có chọn tải file ảnh mới lên
    if (isset($_FILES['anh_bia']) && $_FILES['anh_bia']['error'] == 0) {
        $filename = time() . '_' . $_FILES['anh_bia']['name'];
        if (move_uploaded_file($_FILES['anh_bia']['tmp_name'], '../uploads/' . $filename)) {
            $anh_bia = $filename; 
        }
    }

    if (empty($ten_sach) || $id_the_loai <= 0) {
        $thong_bao = "<div class='alert alert-danger'>❌ Vui lòng nhập đầy đủ Tên sách và chọn Thể loại!</div>";
    } else {
        try {
            if ($id > 0) {
                // 🔄 CẬP NHẬT SÁCH (Đã đồng bộ chính xác cột mo_ta_chi_tiet và tac_gia)
                $sql = "UPDATE sach SET ten_sach = ?, tac_gia = ?, id_the_loai = ?, gia_ban = ?, so_luong_ton = ?, anh_bia = ?, mo_ta_chi_tiet = ?, noi_bat = ? WHERE id = ?";
                $stmt = $ket_noi->prepare($sql);
                $stmt->execute([$ten_sach, $tac_gia, $id_the_loai, $gia_ban, $so_luong_ton, $anh_bia, $mo_ta_chi_tiet, $noi_bat, $id]);
                $thong_bao = "<div class='alert alert-success'>✅ Cập nhật thông tin sách thành công!</div>";
            } else {
                // ➕ THÊM SÁCH MỚI (Đã đồng bộ chính xác cột mo_ta_chi_tiet và tac_gia)
                $sql = "INSERT INTO sach (ten_sach, tac_gia, id_the_loai, gia_ban, so_luong_ton, anh_bia, mo_ta_chi_tiet, noi_bat) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $ket_noi->prepare($sql);
                $stmt->execute([$ten_sach, $tac_gia, $id_the_loai, $gia_ban, $so_luong_ton, $anh_bia, $mo_ta_chi_tiet, $noi_bat]);
                $thong_bao = "<div class='alert alert-success'>✅ Đã đăng bán sách mới thành công!</div>";
            }
            $sach_sua = null;
        } catch (Exception $e) {
            $thong_bao = "<div class='alert alert-danger'>❌ Lỗi cơ sở dữ liệu: " . $e->getMessage() . "</div>";
        }
    }
}

// --- XỬ LÝ 3: LẤY DỮ LIỆU ĐỂ ĐỔ VÀO FORM SỬA ---
if (isset($_GET['sua'])) {
    $id_sua = intval($_GET['sua']);
    $stmt_get = $ket_noi->prepare("SELECT * FROM sach WHERE id = ?");
    $stmt_get->execute([$id_sua]);
    $sach_sua = $stmt_get->fetch(PDO::FETCH_ASSOC);
}

// --- XỬ LÝ 4: LẤY DANH SÁCH THỂ LOẠI ---
$danh_sach_the_loai = [];
try {
    $danh_sach_the_loai = $ket_noi->query("SELECT * FROM the_loai ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {}

// --- XỬ LÝ 5: LẤY TOÀN BỘ DANH SÁCH SÁCH HIỂN THỊ TRÊN BẢNG ---
$danh_sach_sach = [];
try {
    $sql_all = "SELECT sach.*, the_loai.ten_the_loai 
                FROM sach 
                LEFT JOIN the_loai ON sach.id_the_loai = the_loai.id 
                ORDER BY sach.id DESC";
    $danh_sach_sach = $ket_noi->query($sql_all)->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $thong_bao = "<div class='alert alert-danger'>❌ Lỗi cơ sở dữ liệu: " . $e->getMessage() . "</div>";
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
    <title>Admin - Quản Lý Kho Sách</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f4f6f9; display: flex; min-height: 100vh; }
        
        /* Sidebar Admin */
        .sidebar { width: 260px; background-color: #2c3e50; color: white; padding: 20px 0; display: flex; flex-direction: column; flex-shrink: 0; }
        .sidebar h3 { text-align: left; padding: 0 20px 20px 20px; margin-bottom: 20px; color: #e67e22; border-bottom: 1px solid #34495e; font-size: 16px; letter-spacing: 1px;}
        .sidebar a { display: block; color: #bdc3c7; padding: 14px 20px; text-decoration: none; font-size: 14px; font-weight: bold; transition: all 0.2s; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; border-left: 4px solid #e67e22; padding-left: 16px; }

        /* Khối nội dung chính */
        .main-content { flex: 1; padding: 30px; overflow-y: auto; }
        h2 { color: #333; margin-bottom: 20px; font-size: 24px; }
        
        .grid-layout { display: flex; gap: 25px; flex-wrap: wrap; align-items: flex-start; }
        .form-container { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); width: 360px; flex-shrink: 0; }
        .table-container { background: white; padding: 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); flex: 1; min-width: 600px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; vertical-align: middle; }
        th { background-color: #f8f9fa; color: #4a5568; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; font-weight: bold; color: #4a5568; font-size: 13px; }
        input[type="text"], input[type="number"], select, textarea { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 4px; font-size: 14px; }
        textarea { resize: vertical; min-height: 80px; font-family: inherit; }
        
        .checkbox-group { display: flex; align-items: center; gap: 8px; margin: 15px 0; }
        .checkbox-group input { width: 18px; height: 18px; cursor: pointer; }
        
        .btn { padding: 6px 12px; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block; }
        .btn-edit { background-color: #3182ce; color: white; margin-right: 5px; }
        .btn-delete { background-color: #e53e3e; color: white; }
        .btn-submit { background-color: #2ecc71; color: white; width: 100%; padding: 12px; font-size: 14px; margin-top: 10px; border-radius: 4px; font-weight: bold; border: none; cursor: pointer;}
        .btn-submit:hover { background-color: #27ae60; }
        
        .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: bold;}
        .alert-success { background-color: #c6f6d5; color: #22543d; border: 1px solid #9ae6b4; }
        .alert-danger { background-color: #fed7d7; color: #742a2a; border: 1px solid #feb2b2; }
        
        .badge-stock { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .in-stock { background-color: #c6f6d5; color: #22543d; }
        .out-stock { background-color: #fed7d7; color: #742a2a; }
        .badge-star { background-color: #feebc8; color: #c05621; padding: 3px 6px; border-radius: 4px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 4px;}
        .img-preview { width: 45px; height: 60px; object-fit: cover; border-radius: 4px; background: #edf2f7; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<div class="sidebar">
    <h3>📊 ADMIN Tạp Hóa Sách</h3>
    <a href="admin_don_hang_mua.php">📦 Quản lý Đơn Mua</a>
    <a href="admin_don_hang_thue.php">📋 Quản lý Đơn Thuê</a>
    <a href="admin_quan_ly_sach.php" class="active">📚 Quản lý Kho Sách</a>
    <a href="admin_quan_ly_the_loai.php">📁 Quản lý Thể Loại</a>
    <a href="admin_quan_ly_nguoi_dung.php">👥 Quản lý Người Dùng</a>
    <a href="index.php">📊 Về Trang Tổng Quan Admin</a>
</div>

<div class="main-content">
    <h2>📚 Hệ Thống Quản Lý Đầu Sách & Kho Hàng</h2>
    
    <?php echo $thong_bao; ?>

    <div class="grid-layout">
        <div class="form-container">
            <h3 style="color:#2d3748; margin-bottom: 15px;">
                <?php echo isset($sach_sua) ? "🔄 Cập Nhật Sách ID #".$sach_sua['id'] : "➕ Thêm Sách Mới"; ?>
            </h3>
            
            <form action="admin_quan_ly_sach.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $sach_sua['id'] ?? 0; ?>">
                <input type="hidden" name="anh_bia_cu" value="<?php echo $sach_sua['anh_bia'] ?? ''; ?>">

                <div class="form-group">
                    <label>Tên sách:</label>
                    <input type="text" name="ten_sach" value="<?php echo htmlspecialchars($sach_sua['ten_sach'] ?? ''); ?>" placeholder="Nhập tên đầu sách..." required>
                </div>

                <div class="form-group">
                    <label>Tác giả:</label>
                    <input type="text" name="tac_gia" value="<?php echo htmlspecialchars($sach_sua['tac_gia'] ?? ''); ?>" placeholder="Nhập tên tác giả...">
                </div>

                <div class="form-group">
                    <label>Thể loại sách:</label>
                    <select name="id_the_loai" required>
                        <option value="">-- Chọn thể loại sách --</option>
                        <?php foreach($danh_sach_the_loai as $tl): ?>
                            <option value="<?php echo $tl['id']; ?>" <?php if(isset($sach_sua['id_the_loai']) && $sach_sua['id_the_loai'] == $tl['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($tl['ten_the_loai']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Giá niêm yết (đ):</label>
                    <input type="number" name="gia_ban" value="<?php echo $sach_sua['gia_ban'] ?? 50000; ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label>Số lượng trong kho:</label>
                    <input type="number" name="so_luong_ton" value="<?php echo $sach_sua['so_luong_ton'] ?? 10; ?>" min="0" required>
                </div>

                <div class="form-group">
                    <label>Mô tả chi tiết sách:</label>
                    <textarea name="mo_ta_chi_tiet" placeholder="Nhập tóm tắt nội dung, năm xuất bản..."><?php echo htmlspecialchars($sach_sua['mo_ta_chi_tiet'] ?? ''); ?></textarea>
                </div>

                <div class="checkbox-group">
                    <input type="checkbox" id="noi_bat" name="noi_bat" value="1" <?php if(isset($sach_sua['noi_bat']) && $sach_sua['noi_bat'] == 1) echo 'checked'; ?>>
                    <label for="noi_bat" style="display:inline; margin:0; cursor:pointer; color:#e67e22;">⭐ Cài đặt làm Sách Nổi Bật</label>
                </div>

                <div class="form-group">
                    <label>Ảnh bìa sách:</label>
                    <input type="file" name="anh_bia" accept="image/*">
                    <?php if(!empty($sach_sua['anh_bia'])): ?>
                        <div style="margin-top: 8px;">
                            <p style="font-size: 11px; color:#718096; margin-bottom: 4px;">Ảnh hiện tại:</p>
                            <img src="../uploads/<?php echo $sach_sua['anh_bia']; ?>" style="width:60px; height:80px; object-fit:cover; border-radius:4px; border:1px solid #cbd5e0;">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="luu_sach" class="btn btn-submit">
                    <?php echo isset($sach_sua) ? "💾 Lưu Thay Đổi" : "🚀 Đăng Bán Sách"; ?>
                </button>
                
                <?php if (isset($sach_sua)): ?>
                    <a href="admin_quan_ly_sach.php" style="display:block; text-align:center; margin-top:10px; color:#e53e3e; font-size:13px; text-decoration:none; font-weight:bold;">❌ Hủy sửa, quay lại thêm mới</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <h3 style="color:#2d3748; margin-bottom: 15px;">📁 Danh Mục Kho Sách Hiện Có (<?php echo count($danh_sach_sach); ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Ảnh bìa</th>
                        <th>Tên Sách</th>
                        <th>Thể Loại</th>
                        <th>Giá Gốc</th>
                        <th>Số Lượng</th>
                        <th style="text-align: right;">Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($danh_sach_sach) == 0): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color:#a0aec0; padding: 20px;">Kho hàng trống. Vui lòng thêm sách mới.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($danh_sach_sach as $sach): ?>
                            <tr>
                                <td>
                                    <?php if(!empty($sach['anh_bia'])): ?>
                                        <img src="../uploads/<?php echo $sach['anh_bia']; ?>" class="img-preview" alt="Bìa">
                                    <?php else: ?>
                                        <div class="img-preview" style="display:flex; align-items:center; justify-content:center; font-size:10px; color:#a0aec0;">Mất ảnh</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($sach['ten_sach']); ?></strong>
                                    <?php if(!empty($sach['tac_gia'])): ?>
                                        <br><span style="font-size: 12px; color: #4a5568;">✍️ Tác giả: <?php echo htmlspecialchars($sach['tac_gia']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if($sach['noi_bat'] == 1): ?>
                                        <br><span class="badge-star">⭐ Nổi bật</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: #718096;">📁 <?php echo htmlspecialchars($sach['ten_the_loai'] ?? 'Chưa phân loại'); ?></td>
                                <td style="font-weight: bold; color: #e53e3e;">
                                    <?php echo number_format($sach['gia_ban'], 0, ',', '.'); ?> đ
                                </td>
                                <td>
                                    <?php if ($sach['so_luong_ton'] > 0): ?>
                                        <span class="badge-stock in-stock">Sẵn có (<?php echo $sach['so_luong_ton']; ?>)</span>
                                    <?php else: ?>
                                        <span class="badge-stock out-stock">Hết hàng (0)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="admin_quan_ly_sach.php?sua=<?php echo $sach['id']; ?>" class="btn btn-edit">✏️ Sửa</a>
                                    <a href="admin_quan_ly_sach.php?xoa=<?php echo $sach['id']; ?>" class="btn btn-delete" onclick="return confirm('Bạn có chắc chắn muốn xóa đầu sách này vĩnh viễn không?');">🗑️ Xóa</a>
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