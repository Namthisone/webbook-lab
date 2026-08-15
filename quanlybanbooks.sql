-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 06, 2026 at 02:34 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `quanlybanbooks`
--

-- --------------------------------------------------------

--
-- Table structure for table `chi_tiet_mua`
--

CREATE TABLE `chi_tiet_mua` (
  `id` int(11) NOT NULL,
  `id_don_hang` int(11) NOT NULL,
  `id_sach` int(11) DEFAULT NULL,
  `so_luong` int(11) NOT NULL,
  `gia_luc_mua` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chi_tiet_mua`
--

INSERT INTO `chi_tiet_mua` (`id`, `id_don_hang`, `id_sach`, `so_luong`, `gia_luc_mua`) VALUES
(1, 1, 1, 1, 150000),
(2, 1, 4, 1, 85000),
(3, 2, 3, 1, 300000),
(4, 6, 3, 1, 300000),
(5, 7, 3, 1, 300000),
(6, 8, 6, 3, 79000),
(7, 9, 11, 1, 100000),
(8, 10, 8, 2, 95000),
(9, 11, 8, 1, 95000),
(10, 12, 10, 1, 130000),
(11, 13, 11, 1, 100000),
(12, 14, 8, 1, 95000);

-- --------------------------------------------------------

--
-- Table structure for table `chi_tiet_thue`
--

CREATE TABLE `chi_tiet_thue` (
  `id` int(11) NOT NULL,
  `id_don_thue` int(11) NOT NULL,
  `id_sach` int(11) DEFAULT NULL,
  `so_luong` int(11) NOT NULL DEFAULT 1,
  `gia_thue_mot_ngay` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `chi_tiet_thue`
--

INSERT INTO `chi_tiet_thue` (`id`, `id_don_thue`, `id_sach`, `so_luong`, `gia_thue_mot_ngay`) VALUES
(1, 1, 5, 1, 3000),
(2, 1, 7, 1, 4000),
(3, 3, 1, 1, 3000),
(4, 4, 8, 1, 3000);

-- --------------------------------------------------------

--
-- Table structure for table `don_hang_mua`
--

CREATE TABLE `don_hang_mua` (
  `id` int(11) NOT NULL,
  `id_nguoi_dung` int(11) DEFAULT NULL,
  `ten_khach_hang` varchar(255) NOT NULL,
  `email_khach_hang` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(20) NOT NULL,
  `dia_chi_giao_hang` text NOT NULL,
  `tong_tien` int(11) NOT NULL,
  `trang_thai` enum('cho_duyet','dang_xu_ly','da_giao','da_huy') DEFAULT 'cho_duyet',
  `ngay_dat_hang` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `don_hang_mua`
--

INSERT INTO `don_hang_mua` (`id`, `id_nguoi_dung`, `ten_khach_hang`, `email_khach_hang`, `so_dien_thoai`, `dia_chi_giao_hang`, `tong_tien`, `trang_thai`, `ngay_dat_hang`) VALUES
(1, 2, 'Nguyễn Văn An', 'an.nguyen@gmail.com', '0912345678', '123 Đường Nguyễn Trãi, Quận 5, TP.HCM', 235000, 'da_giao', '2026-07-05 09:32:00'),
(2, 3, 'Lê Thị Bình', 'binhle@gmail.com', '0987654321', '456 Đường Lê Lợi, Hải Châu, Đà Nẵng', 300000, 'cho_duyet', '2026-07-05 09:32:00'),
(6, 2, 'Nguyễn Văn An', 'an.nguyen@gmail.com', 'áđá', 'áđâs', 300000, 'da_giao', '2026-07-05 10:44:17'),
(7, 2, 'Nguyễn Văn An', 'an.nguyen@gmail.com', 'Áâsđá', 'ádsadsadsad', 300000, 'da_huy', '2026-07-05 10:44:28'),
(8, 1, 'Quản Trị Viên Hệ Thống', 'admin@bookstore.com', '123123123', '123 dfgdfhtdh', 237000, 'cho_duyet', '2026-07-05 18:44:34'),
(9, 4, 'Alex Xơn Đơ', 'Dienne@gmail.com', '45356547657', 'qưeqưéađá', 100000, 'da_giao', '2026-07-06 08:03:02'),
(10, 4, 'Alex Xơn Đơ', 'Dienne@gmail.com', '45356547657', 'sdấđá', 190000, 'da_giao', '2026-07-06 08:28:12'),
(11, 4, 'Alex Xơn Đơ', 'Dienne@gmail.com', '12334235345', '2342 dfgd dfgdg', 95000, 'cho_duyet', '2026-07-06 09:40:57'),
(12, 1, 'Quản Trị Viên Hệ Thống', 'admin@bookstore.com', 'âsáa', 'âsâs', 130000, 'cho_duyet', '2026-07-06 10:13:35'),
(13, 4, 'Alex Xơn Đơ', 'Dienne@gmail.com', 'áđá', 'áđá', 100000, 'da_giao', '2026-07-06 10:52:00'),
(14, 4, 'Alex Xơn Đơ', 'Dienne@gmail.com', 'sdsds', 'sdsd', 95000, 'da_giao', '2026-07-06 11:12:50');

-- --------------------------------------------------------

--
-- Table structure for table `don_hang_thue`
--

CREATE TABLE `don_hang_thue` (
  `id` int(11) NOT NULL,
  `id_nguoi_dung` int(11) DEFAULT NULL,
  `ten_khach_hang` varchar(255) NOT NULL,
  `email_khach_hang` varchar(100) NOT NULL,
  `so_dien_thoai` varchar(20) NOT NULL,
  `ngay_thue` date NOT NULL,
  `han_tra_du_kien` date NOT NULL,
  `ngay_tra_thuc_te` date DEFAULT NULL,
  `tien_coc` int(11) NOT NULL,
  `tong_tien_thue` int(11) NOT NULL,
  `trang_thai` enum('cho_duyet','dang_thue','da_tra','qua_han','da_huy') DEFAULT 'dang_thue',
  `ngay_tao_don` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `don_hang_thue`
--

INSERT INTO `don_hang_thue` (`id`, `id_nguoi_dung`, `ten_khach_hang`, `email_khach_hang`, `so_dien_thoai`, `ngay_thue`, `han_tra_du_kien`, `ngay_tra_thuc_te`, `tien_coc`, `tong_tien_thue`, `trang_thai`, `ngay_tao_don`) VALUES
(1, 2, 'Nguyễn Văn An', 'an.nguyen@gmail.com', '0912345678', '2026-07-01', '2026-07-11', '2026-07-05', 200000, 70000, 'da_tra', '2026-07-05 09:32:00'),
(3, 2, 'Nguyễn Văn An', 'an.nguyen@gmail.com', '45356547657', '2026-07-05', '2026-07-12', NULL, 200000, 21000, 'da_huy', '2026-07-05 10:49:10'),
(4, 4, 'Alex Xơn Đơ', 'Dienne@gmail.com', 'áÁá', '2026-07-06', '2026-07-13', NULL, 200000, 21000, 'dang_thue', '2026-07-06 08:10:12');

-- --------------------------------------------------------

--
-- Table structure for table `khieu_nai`
--

CREATE TABLE `khieu_nai` (
  `id` int(11) NOT NULL,
  `id_nguoi_dung` int(11) NOT NULL,
  `loai_don` enum('mua','thue') NOT NULL COMMENT 'Phân loại khiếu nại đơn mua hay đơn thuê',
  `id_don_hang` int(11) NOT NULL COMMENT 'ID của đơn hàng mua hoặc thuê tương ứng',
  `tieu_de` varchar(255) NOT NULL,
  `noi_dung` text NOT NULL,
  `trang_thai` enum('cho_giai_quyet','dang_xu_ly','da_giai_quyet') DEFAULT 'cho_giai_quyet',
  `phan_hoi_admin` text DEFAULT NULL COMMENT 'Lời nhắn giải quyết từ Admin',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `khieu_nai`
--

INSERT INTO `khieu_nai` (`id`, `id_nguoi_dung`, `loai_don`, `id_don_hang`, `tieu_de`, `noi_dung`, `trang_thai`, `phan_hoi_admin`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 4, 'mua', 11, 'rách', 'qưeqưe', 'dang_xu_ly', 'Xin lõi ní', '2026-07-06 09:41:16', '2026-07-06 09:41:51'),
(2, 4, 'thue', 4, 'rách', '45645645', 'cho_giai_quyet', NULL, '2026-07-06 09:43:53', '2026-07-06 09:43:53'),
(3, 4, 'thue', 4, 'hư', 'ádấd', 'da_giai_quyet', 'Ok ní', '2026-07-06 10:35:02', '2026-07-06 10:46:04');

-- --------------------------------------------------------

--
-- Table structure for table `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `id` int(11) NOT NULL,
  `ten_dang_nhap` varchar(100) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `ho_ten` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `vai_tro` enum('khach_hang','admin') DEFAULT 'khach_hang',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `so_dien_thoai` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`id`, `ten_dang_nhap`, `mat_khau`, `ho_ten`, `email`, `vai_tro`, `ngay_tao`, `so_dien_thoai`) VALUES
(1, 'admin', 'e10adc3949ba59abbe56e057f20f883e', 'Quản Trị Viên Hệ Thống', 'admin@bookstore.com', 'admin', '2026-07-05 09:32:00', ''),
(2, 'nguyenvanan', 'e10adc3949ba59abbe56e057f20f883e', 'Nguyễn Văn An', 'an.nguyen@gmail.com', 'khach_hang', '2026-07-05 09:32:00', ''),
(3, 'lethibinh', 'e10adc3949ba59abbe56e057f20f883e', 'Lê Thị Bình', 'binhle@gmail.com', 'khach_hang', '2026-07-05 09:32:00', ''),
(4, 'Alex', '202cb962ac59075b964b07152d234b70', 'Alex Xơn Đơ', 'Dienne@gmail.com', 'khach_hang', '2026-07-05 10:12:49', ''),
(5, '', '123', 'Trần Nguyễn Phát Đạt', 'phi@gmail.com', 'admin', '2026-07-05 17:06:55', '012326674'),
(6, 'Hihi', '698d51a19d8a121ce581499d7b701668', 'Ahihi', 'ahiih@gmail.com', '', '2026-07-06 07:10:02', '');

-- --------------------------------------------------------

--
-- Table structure for table `sach`
--

CREATE TABLE `sach` (
  `id` int(11) NOT NULL,
  `id_the_loai` int(11) DEFAULT NULL,
  `ten_sach` varchar(255) NOT NULL,
  `tac_gia` varchar(255) NOT NULL,
  `gia_ban` int(11) NOT NULL,
  `gia_thue_theo_ngay` int(11) NOT NULL DEFAULT 0,
  `anh_bia` varchar(255) DEFAULT 'mac_dinh.jpg',
  `mo_ta_chi_tiet` text DEFAULT NULL,
  `noi_bat` tinyint(1) DEFAULT 0,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `so_luong_ton` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sach`
--

INSERT INTO `sach` (`id`, `id_the_loai`, `ten_sach`, `tac_gia`, `gia_ban`, `gia_thue_theo_ngay`, `anh_bia`, `mo_ta_chi_tiet`, `noi_bat`, `ngay_tao`, `so_luong_ton`) VALUES
(1, 1, 'Lập trình PHP từ cơ bản đến nâng cao', 'Nguyễn Văn A', 150000, 3000, '1783340656_z8014207636635_06266d77328605bbd4ac9d8edd3d51ee.jpg', 'Cuốn sách cung cấp kiến thức nền tảng vững chắc về PHP phối hợp với cơ sở dữ liệu MySQL.', 0, '2026-07-05 09:32:00', 0),
(2, 2, 'Biên Niên Sử Phan Thành Vinh', 'Nặc Danh', 0, 5000, '1783340721_z7917411197731_496777b395b3e7285e2d3f56892a144f.jpg', 'Kể về hành trình gian truân của Phan Thành Vinh', 0, '2026-07-05 09:32:00', 1),
(3, 1, 'Nhập môn Trí Tuệ Nhân Tạo (AI)', 'Dr. John Smith', 300000, 8000, '1783340645_z8014207625426_a5c7a88e748a15ae74ce686187d5386b.jpg', 'Sách dịch giúp tiếp cận các thuật toán Machine Learning cơ bản dành cho người mới bắt đầu.', 0, '2026-07-05 09:32:00', 0),
(4, 2, 'Dế Mèn Phiêu Lưu Ký', 'Tô Hoài', 85000, 2000, '1783274420_demen.webp', 'Tác phẩm văn học thiếu nhi kinh điển nổi tiếng nhất của nhà văn Tô Hoài.', 1, '2026-07-05 09:32:00', 0),
(5, 2, 'Mắt Biếc', 'Nguyễn Nhật Ánh', 110000, 3000, '1783332572_mb.jpg', 'Câu chuyện tình yêu đầy lãng mạn nhưng cũng đượm buồn của Ngạn và Hà Lan.', 0, '2026-07-05 09:32:00', 0),
(6, 2, 'Nhà Giả Kim', 'Paulo Coelho', 79000, 2000, '1783332766_download.jpg', 'Cuốn sách bán chạy chỉ sau Kinh Thánh, giúp định hướng và theo đuổi ước mơ cuộc đời.', 0, '2026-07-05 09:32:00', 0),
(7, 3, 'Nghĩ Giàu và Làm Giàu', 'Napoleon Hill', 120000, 4000, '1783340632_z8014207740065_82c062e97d9651d4ed51151e55cdb4c1.jpg', 'Một trong những cuốn sách truyền cảm hứng làm giàu thành công nhất mọi thời đại.', 0, '2026-07-05 09:32:00', 0),
(8, 3, 'Đắc Nhân Tâm', 'Dale Carnegie', 95000, 3000, '1783273868_images.jpg', 'Cuốn sách đưa ra những lời khuyên nghệ thuật ứng xử và thu phục lòng người.', 1, '2026-07-05 09:32:00', 9),
(9, 4, 'Vũ Trụ Trong Vỏ Hạt Dẻ', 'Stephen Hawking', 180000, 5000, '1783340623_z8014207623585_27f33231fd47ac114f770e92d0f09ed6.jpg', 'Khám phá những bí ẩn sâu thẳm của không gian, thời gian và vật lý lượng tử.', 0, '2026-07-05 09:32:00', 0),
(10, 4, 'Hiểu Về Trái Tim', 'Minh Niệm', 130000, 4000, '1783332563_hiểu trái tim.jpg', 'Tác phẩm giúp người đọc nhìn sâu vào tâm lý, chữa lành nỗi đau và tìm lại sự bình yên.', 0, '2026-07-05 09:32:00', 0),
(11, 2, 'Mưa Đỏ', 'Chu Lai', 100000, 5000, '1783272989_muado.jpg', 'Mưa đỏ là tên một cuốn tiểu thuyết chiến tranh nổi tiếng của nhà văn Chu Lai, xuất bản lần đầu năm 2016 bởi Nhà xuất bản Quân đội Nhân dân. Tác phẩm tái hiện một cách chân thực và khốc liệt trận chiến 81 ngày đêm bảo vệ Thành cổ Quảng Trị mùa hè năm 1972.', 1, '2026-07-05 11:28:14', 19),
(12, 2, 'Sparta', 'Đang cập nhật', 30000, 0, '1783275181_spata.jpg', 'Truyện về Sparta thường xoay quanh 300 chiến binh dũng cảm dưới sự chỉ huy của Vua Leonidas. Nổi tiếng nhất là bộ tiểu thuyết đồ họa \"300\" của tác giả Frank Miller, lấy cảm hứng từ trận chiến Thermopylae lịch sử, nơi đội quân Sparta chiến đấu bảo vệ Hy Lạp trước đế quốc Ba Tư.', 1, '2026-07-05 18:13:01', 10),
(13, 5, 'Kỳ Án Ánh Trăng', 'Quỷ Cổ Nữ', 25000, 0, '1783332696_anhtr.jpg', 'Kỳ Án Ánh Trăng là tiểu thuyết trinh thám, linh dị kinh điển của tác giả Quỷ Cổ Nữ. Truyện xoay quanh lời nguyền bí ẩn tại phòng 405 của một ký túc xá y khoa. Cứ đúng nửa đêm ngày 16/6, một nữ sinh lại rơi vào trạng thái mộng du, trèo lên cửa sổ nhảy xuống tự sát', 0, '2026-07-06 10:11:36', 6),
(14, 5, 'Mộ Sau Nhà', 'Huỳnh Lập', 100000, 0, '1783341042_OIP.webp', 'Kể về oan về con nuôi cô út trả thù gia đình gian trá theo một góc nhìn tâm linh kinh dị', 1, '2026-07-06 12:30:42', 20);

-- --------------------------------------------------------

--
-- Table structure for table `the_loai`
--

CREATE TABLE `the_loai` (
  `id` int(11) NOT NULL,
  `ten_the_loai` varchar(255) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `the_loai`
--

INSERT INTO `the_loai` (`id`, `ten_the_loai`, `mo_ta`, `ngay_tao`) VALUES
(1, 'Công nghệ thông tin', 'Sách về lập trình, thiết kế web, cơ sở dữ liệu, AI...', '2026-07-05 09:32:00'),
(2, 'Văn học & Tiểu thuyết', 'Truyện chữ, tiểu thuyết văn học trong nước và thế giới', '2026-07-05 09:32:00'),
(3, 'Kinh doanh & Kỹ năng', 'Sách kỹ năng mềm, tư duy tài chính, quản trị kinh doanh', '2026-07-05 09:32:00'),
(4, 'Khoa học & Đời sống', 'Sách khám phá khoa học vũ trụ, tâm lý học, sức khỏe', '2026-07-05 09:32:00'),
(5, 'Kinh Dị', NULL, '2026-07-05 12:35:36');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `chi_tiet_mua`
--
ALTER TABLE `chi_tiet_mua`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_don_hang` (`id_don_hang`),
  ADD KEY `id_sach` (`id_sach`);

--
-- Indexes for table `chi_tiet_thue`
--
ALTER TABLE `chi_tiet_thue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_don_thue` (`id_don_thue`),
  ADD KEY `id_sach` (`id_sach`);

--
-- Indexes for table `don_hang_mua`
--
ALTER TABLE `don_hang_mua`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_nguoi_dung` (`id_nguoi_dung`);

--
-- Indexes for table `don_hang_thue`
--
ALTER TABLE `don_hang_thue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_nguoi_dung` (`id_nguoi_dung`);

--
-- Indexes for table `khieu_nai`
--
ALTER TABLE `khieu_nai`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `sach`
--
ALTER TABLE `sach`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_the_loai` (`id_the_loai`);

--
-- Indexes for table `the_loai`
--
ALTER TABLE `the_loai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_the_loai` (`ten_the_loai`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `chi_tiet_mua`
--
ALTER TABLE `chi_tiet_mua`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `chi_tiet_thue`
--
ALTER TABLE `chi_tiet_thue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `don_hang_mua`
--
ALTER TABLE `don_hang_mua`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `don_hang_thue`
--
ALTER TABLE `don_hang_thue`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `khieu_nai`
--
ALTER TABLE `khieu_nai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sach`
--
ALTER TABLE `sach`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `the_loai`
--
ALTER TABLE `the_loai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `chi_tiet_mua`
--
ALTER TABLE `chi_tiet_mua`
  ADD CONSTRAINT `chi_tiet_mua_ibfk_1` FOREIGN KEY (`id_don_hang`) REFERENCES `don_hang_mua` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chi_tiet_mua_ibfk_2` FOREIGN KEY (`id_sach`) REFERENCES `sach` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chi_tiet_thue`
--
ALTER TABLE `chi_tiet_thue`
  ADD CONSTRAINT `chi_tiet_thue_ibfk_1` FOREIGN KEY (`id_don_thue`) REFERENCES `don_hang_thue` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `chi_tiet_thue_ibfk_2` FOREIGN KEY (`id_sach`) REFERENCES `sach` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `don_hang_mua`
--
ALTER TABLE `don_hang_mua`
  ADD CONSTRAINT `don_hang_mua_ibfk_1` FOREIGN KEY (`id_nguoi_dung`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `don_hang_thue`
--
ALTER TABLE `don_hang_thue`
  ADD CONSTRAINT `don_hang_thue_ibfk_1` FOREIGN KEY (`id_nguoi_dung`) REFERENCES `nguoi_dung` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sach`
--
ALTER TABLE `sach`
  ADD CONSTRAINT `sach_ibfk_1` FOREIGN KEY (`id_the_loai`) REFERENCES `the_loai` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
