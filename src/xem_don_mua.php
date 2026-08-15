<?php
// 1. Khởi động session để lấy ID người dùng đã đăng nhập
session_start();

// Kiểm tra nếu người dùng đã đăng nhập từ index.php (Sử dụng $_SESSION['user']['id'])
if (isset($_SESSION['user']['id'])) {
    $id_nguoi_dung_hien_tai = $_SESSION['user']['id'];
} else {
    // Nếu chưa đăng nhập, mặc định giả lập id = 2 như dữ liệu mẫu (hoặc bạn có thể dùng header("Location: dang_nhap.php");)
    $id_nguoi_dung_hien_tai = 2; 
}

// 2. Kết nối Cơ sở dữ liệu (Dùng cấu hình giống db.php nếu bạn dùng PDO hoặc mysqli)
$host = "db";
$username = "root";
$password = "";
$dbname = "quanlybanbooks";

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// 3. Truy vấn lấy thông tin đơn hàng mua
$sql = "SELECT id, trang_thai, ngay_dat_hang, tong_tien FROM don_hang_mua WHERE id_nguoi_dung = ? ORDER BY ngay_dat_hang DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_nguoi_dung_hien_tai);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn Hàng Mua Của Tôi - Tạp Hóa Sách</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;1,400&display=swap');
        
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: #f8fafc; color: #1e293b; padding-bottom: 60px; line-height: 1.5; }

        /* Header đồng bộ với trang index */
        header { 
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); 
            color: white; 
            padding: 25px 60px; 
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header-brand h1 { 
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem; 
            font-weight: 600; 
            background: linear-gradient(to right, #ffffff, #34d399);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .btn-back {
            color: #34d399;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-back:hover { color: #10b981; text-decoration: underline; }

        /* Khung nội dung */
        .container { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
        }
        .page-title::after { content: ''; position: absolute; bottom: 0; left: 0; width: 50px; height: 4px; background: #3b82f6; border-radius: 2px; }

        /* Card bảng dữ liệu đổ bóng siêu mịn */
        .card-table {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.01);
            overflow: hidden;
        }

        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 14px; }
        th { background-color: #0f172a; color: #f8fafc; font-weight: 600; padding: 16px 20px; letter-spacing: 0.5px; }
        td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; color: #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #f8fafc; }

        .order-id { font-weight: 700; color: #1e293b; }
        .order-price { font-weight: 600; color: #0f172a; }

        /* Badge trạng thái tinh tế */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        .badge-cho_duyet { background-color: #fef3c7; color: #d97706; }
        .badge-dang_xu_ly { background-color: #e0f2fe; color: #0369a1; }
        .badge-da_giao { background-color: #dcfce7; color: #15803d; }
        .badge-da_huy { background-color: #fee2e2; color: #b91c1c; }

        .empty-state { text-align: center; padding: 5px 0; color: #64748b; font-weight: 500; }

        @media (max-width: 600px) {
            header { padding: 20px; flex-direction: column; gap: 10px; text-align: center; }
            th, td { padding: 12px; font-size: 13px; }
        }
    </style>
</head>
<body>

    <header>
        <div class="header-brand">
            <h1>Tạp Hóa Sách</h1>
        </div>
        <div>
            <a href="index.php" class="btn-back">⬅ Quay lại trang chủ</a>
        </div>
    </header>

    <div class="container">
        <h2 class="page-title">📦 Danh Sách Đơn Hàng Mua Của Bạn</h2>
        
        <div class="card-table">
            <table>
                <thead>
                    <tr>
                        <th>Mã đơn hàng</th>
                        <th>Trạng thái đơn</th>
                        <th>Tổng tiền mua</th>
                        <th>Ngày đặt hàng</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            // Xử lý chuyển đổi tên trạng thái hiển thị hiển thị
                            $trang_thai_vn = "";
                            switch ($row['trang_thai']) {
                                case 'cho_duyet': $trang_thai_vn = "Chờ duyệt"; break;
                                case 'dang_xu_ly': $trang_thai_vn = "Đang xử lý"; break;
                                case 'da_giao': $trang_thai_vn = "Đã giao"; break;
                                case 'da_huy': $trang_thai_vn = "Đã hủy"; break;
                                default: $trang_thai_vn = $row['trang_thai'];
                            }

                            $ngay_dat = date("d-m-Y H:i:s", strtotime($row['ngay_dat_hang']));
                            $tong_tien_format = number_format($row['tong_tien'], 0, ',', '.') . ' đ';

                            echo "<tr>";
                            echo "<td class='order-id'>#" . $row['id'] . "</td>";
                            echo "<td><span class='badge badge-" . $row['trang_thai'] . "'>" . $trang_thai_vn . "</span></td>";
                            echo "<td class='order-price'>" . $tong_tien_format . "</td>";
                            echo "<td>" . $ngay_dat . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='empty-state'>Bạn chưa thực hiện đơn hàng mua nào.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>
<?php
$stmt->close();
$conn->close();
?>