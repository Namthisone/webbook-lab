<?php
require_once 'db.php';

// Lấy ID thể loại từ URL
$id_the_loai = isset($_GET['id']) ? intval($_GET['id']) : 0;

// 1. Lấy thông tin của thể loại hiện tại đang xem
$stmt_tl_hien_tai = $ket_noi->prepare("SELECT * FROM the_loai WHERE id = ?");
$stmt_tl_hien_tai->execute([$id_the_loai]);
$the_loai_hien_tai = $stmt_tl_hien_tai->fetch(PDO::FETCH_ASSOC);

// Nếu không tìm thấy thể loại, quay về trang chủ
if (!$the_loai_hien_tai) {
    header("Location: index.php");
    exit;
}

// 2. Lấy lại toàn bộ danh sách thể loại để hiển thị lại trên Menu điều hướng
$query_all_tl = $ket_noi->query("SELECT * FROM the_loai");
$danh_sach_the_loai = $query_all_tl->fetchAll(PDO::FETCH_ASSOC);

// 3. Lấy danh sách sách thuộc thể loại này
$stmt_sach = $ket_noi->prepare("SELECT * FROM sach WHERE id_the_loai = ? ORDER BY id DESC");
$stmt_sach->execute([$id_the_loai]);
$danh_sach_sach = $stmt_sach->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $the_loai_hien_tai['ten_the_loai']; ?> - WebBook</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; }
        body { background-color: #f5f6f9; color: #333; padding-bottom: 50px; }
        header { background-color: #2c3e50; color: white; padding: 20px; text-align: center; }
        nav { background-color: #34495e; padding: 10px; text-align: center; }
        nav a { color: white; text-decoration: none; margin: 0 15px; font-weight: bold; }
        nav a:hover, nav a.active { color: #2ecc71; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        h2 { margin: 30px 0 15px 0; color: #2c3e50; border-left: 5px solid #2ecc71; padding-left: 10px; }
        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 25px; }
        .book-card { background: white; border-radius: 8px; padding: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); text-align: center; display: flex; flex-direction: column; justify-content: space-between; transition: 0.3s; }
        .book-card:hover { transform: translateY(-5px); }
        .book-img { width: 100%; height: 200px; background-color: #e0e0e0; display: flex; align-items: center; justify-content: center; border-radius: 4px; margin-bottom: 15px; color: #7f8c8d; font-weight: bold; }
        .book-title { font-size: 16px; font-weight: bold; margin-bottom: 5px; height: 44px; overflow: hidden; }
        .book-author { font-size: 14px; color: #7f8c8d; margin-bottom: 10px; }
        .book-price { font-size: 15px; color: #e74c3c; font-weight: bold; margin-bottom: 5px; }
        .book-rent { font-size: 13px; color: #2980b9; font-weight: bold; margin-bottom: 15px; }
        .btn-group { display: flex; gap: 5px; }
        .btn { flex: 1; padding: 8px 5px; border: none; border-radius: 4px; font-weight: bold; font-size: 12px; cursor: pointer; text-decoration: none; text-align: center; color: white; }
        .btn-buy { background-color: #2ecc71; }
        .btn-rent { background-color: #3498db; }
    </style>
</head>
<body>

    <header>
        <h1>📚 Danh Mục: <?php echo $the_loai_hien_tai['ten_the_loai']; ?> </h1>
        <p><?php echo $the_loai_hien_tai['mo_ta']; ?></p>
    </header>

    <nav>
        <a href="index.php">Tất Cả Sách</a>
        <?php foreach($danh_sach_the_loai as $tl): ?>
            <a href="the_loai.php?id=<?php echo $tl['id']; ?>" class="<?php echo $tl['id'] == $id_the_loai ? 'active' : ''; ?>">
                <?php echo $tl['ten_the_loai']; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="container">
        <h2>Kết quả lọc (<?php echo count($danh_sach_sach); ?> cuốn)</h2>
        
        <?php if(count($danh_sach_sach) == 0): ?>
            <p style="text-align: center; margin-top: 40px; color: #7f8c8d;">Hiện tại chưa có sách nào thuộc thể loại này.</p>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach($danh_sach_sach as $sach): ?>
                    <div class="book-card">
                        <div>
                            <div class="book-cover">
                    <img src="uploads/<?php echo $sach['anh_bia']; ?>" alt="Bìa sách" style="width: 100%; height: 100%; object-fit: cover;">
                </div>
                            <div class="book-title">
                                <a href="chi_tiet_sach.php?id=<?php echo $sach['id']; ?>" style="text-decoration: none; color: #2c3e50;">
                                    <?php echo $sach['ten_sach']; ?>
                                </a>
                            </div>
                            <div class="book-author">TG: <?php echo $sach['tac_gia']; ?></div>
                        </div>
                        <div>
                            <div class="book-price">Mua: <?php echo number_format($sach['gia_ban'], 0, ',', '.'); ?> đ</div>
                            <div class="book-rent">Thuê: <?php echo number_format($sach['gia_thue_theo_ngay'], 0, ',', '.'); ?> đ/ngày</div>
                            <div class="btn-group">
                                <a href="#" class="btn btn-buy">Mua đứt</a>
                                <a href="#" class="btn btn-rent">Thuê sách</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>