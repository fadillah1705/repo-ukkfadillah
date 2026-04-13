-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Feb 24, 2026 at 11:59 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fzone_team`
--

-- --------------------------------------------------------

--
-- Table structure for table `barang`
--

CREATE TABLE `barang` (
  `id_barang` int NOT NULL,
  `nama` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `stok` int DEFAULT NULL,
  `harga` int DEFAULT NULL,
  `stok_baik` int DEFAULT '0',
  `stok_rusak` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barang`
--

INSERT INTO `barang` (`id_barang`, `nama`, `stok`, `harga`, `stok_baik`, `stok_rusak`) VALUES
(26, 'lampu belajar', 111, 100000, 111, 0),
(27, 'laptop', 130, 580000, 130, 0),
(28, 'jam dinding', 102, 35000, 102, 0),
(29, 'meja', 200, 43000, 200, 0),
(30, 'baju', 200, 200000, 200, 0),
(31, 'handphone', 144, 800000, 144, 0),
(32, 'jam tangan', 12, 20000, 12, 0),
(33, 'sarung ', 34, 130000, 34, 0),
(34, 'televisi', 45, 460000, 45, 0),
(35, 'sapu lidi', 12, 20000, 12, 0),
(36, 'sofa', 122, 100000, 122, 0),
(37, 'gelas', 28, 26000, 28, 0),
(38, 'piring', 87, 15000, 87, 0);

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id_transaksi` int NOT NULL,
  `id_user` int NOT NULL,
  `id_barang` int NOT NULL,
  `jumlah` int NOT NULL DEFAULT '0',
  `tanggal_transaksi` date DEFAULT (curdate()),
  `status` enum('keluar','masuk') COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id_transaksi`, `id_user`, `id_barang`, `jumlah`, `tanggal_transaksi`, `status`) VALUES
(31, 5, 26, 123, '2026-02-03', 'masuk'),
(32, 5, 27, 230, '2026-02-03', 'masuk'),
(33, 5, 28, 114, '2026-02-03', 'masuk'),
(34, 5, 28, 12, '2026-02-03', 'keluar'),
(35, 5, 26, 12, '2026-02-03', 'keluar'),
(36, 5, 29, 200, '2026-02-03', 'masuk'),
(37, 5, 30, 200, '2026-02-03', 'masuk'),
(38, 5, 31, 144, '2026-02-03', 'masuk'),
(39, 5, 32, 12, '2026-02-03', 'masuk'),
(40, 5, 33, 34, '2026-02-03', 'masuk'),
(41, 5, 34, 65, '2026-02-04', 'masuk'),
(42, 5, 34, 20, '2026-02-04', 'keluar'),
(43, 5, 27, 100, '2026-02-04', 'keluar'),
(44, 7, 35, 12, '2026-02-23', 'masuk'),
(45, 5, 36, 122, '2026-02-23', 'masuk'),
(46, 5, 37, 28, '2026-02-23', 'masuk'),
(47, 5, 38, 87, '2026-02-23', 'masuk');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `username` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nama_lengkap` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gmail` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('admin','petugas') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `username`, `nama_lengkap`, `gmail`, `role`, `password`) VALUES
(5, 'admin', 'Admin 1', 'admin@gmail.com', 'admin', '0192023a7bbd73250516f069df18b500'),
(6, 'admin2', 'Admin 2', 'admin2@gmail.com', 'admin', '1844156d4166d94387f1a4ad031ca5fa'),
(7, 'petugas', 'Petugas 1', 'petugas@gmail.com', 'petugas', 'b53fe7751b37e40ff34d012c7774d65f'),
(8, 'petugas2', 'Petugas 2', 'petugas2@gmail.com', 'petugas', '4a3ad0dbfcbc623c35079e660e38b1c2');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `barang`
--
ALTER TABLE `barang`
  ADD PRIMARY KEY (`id_barang`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id_transaksi`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_barang` (`id_barang`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `barang`
--
ALTER TABLE `barang`
  MODIFY `id_barang` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id_transaksi` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `fk_transaksi_barang` FOREIGN KEY (`id_barang`) REFERENCES `barang` (`id_barang`),
  ADD CONSTRAINT `fk_transaksi_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id_user`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
