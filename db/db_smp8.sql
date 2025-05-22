-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 22, 2025 at 07:57 PM
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
-- Database: `db_smp8`
--

-- --------------------------------------------------------

--
-- Table structure for table `tb_absensi`
--

CREATE TABLE `tb_absensi` (
  `id_absensi` bigint(11) NOT NULL,
  `id_peserta` bigint(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_absensi`
--

INSERT INTO `tb_absensi` (`id_absensi`, `id_peserta`, `tanggal`, `keterangan`) VALUES
(3, 4, '2025-05-22', 'Hadir'),
(4, 5, '2025-05-22', 'Hadir');

-- --------------------------------------------------------

--
-- Table structure for table `tb_admin`
--

CREATE TABLE `tb_admin` (
  `adm_id` bigint(11) NOT NULL,
  `id_user` bigint(11) NOT NULL,
  `adm_nama` varchar(255) NOT NULL,
  `adm_profile` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_admin`
--

INSERT INTO `tb_admin` (`adm_id`, `id_user`, `adm_nama`, `adm_profile`) VALUES
(2, 1, 'Admin Tesa', '6821fe3e0efd8.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tb_ekstrakulikuler`
--

CREATE TABLE `tb_ekstrakulikuler` (
  `id_ekstrakulikuler` bigint(11) NOT NULL,
  `pembina_id` bigint(11) DEFAULT NULL,
  `nama_ekstrakulikuler` varchar(255) NOT NULL,
  `deskripsi_ekstrakulikuler` text NOT NULL,
  `periode` year(4) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_ekstrakulikuler`
--

INSERT INTO `tb_ekstrakulikuler` (`id_ekstrakulikuler`, `pembina_id`, `nama_ekstrakulikuler`, `deskripsi_ekstrakulikuler`, `periode`, `status`) VALUES
(9, 2, 'Ekstrakulikuler 1', 'Bla Bla Bla 1', '2025', 'Masih Berlangsung'),
(11, 4, 'Ekstrakulikuler 2', 'Bla Bla Bla 2', '2025', 'Masih Berlangsung'),
(12, 4, 'Ekstrakulikuler 22', 'Bla Bla Bla 12', '2025', 'Masih Berlangsung'),
(13, 2, 'a', 'a', '2025', 'Selesai'),
(14, 2, 'asdawd', 'asad', '2025', 'Masih Berlangsung'),
(15, 2, 'asdasdqaw', 'asdawd', '2025', 'Masih Berlangsung');

-- --------------------------------------------------------

--
-- Table structure for table `tb_jadwal`
--

CREATE TABLE `tb_jadwal` (
  `id_jadwal` bigint(11) NOT NULL,
  `id_ekstrakulikuler` bigint(11) NOT NULL,
  `hari` varchar(255) NOT NULL,
  `duty_start` time NOT NULL,
  `duty_end` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_jadwal`
--

INSERT INTO `tb_jadwal` (`id_jadwal`, `id_ekstrakulikuler`, `hari`, `duty_start`, `duty_end`) VALUES
(76, 9, 'Kamis', '06:18:00', '06:18:00'),
(77, 9, 'Minggu', '06:14:00', '08:12:00'),
(82, 11, 'Senin', '18:26:00', '06:25:00'),
(83, 12, 'Kamis', '20:15:00', '20:15:00'),
(102, 14, 'Minggu', '09:59:00', '01:55:00'),
(103, 14, 'Jumat', '09:59:00', '09:58:00'),
(104, 15, 'Minggu', '11:36:00', '11:30:00'),
(105, 15, 'Sabtu', '14:55:00', '14:49:00');

-- --------------------------------------------------------

--
-- Table structure for table `tb_kegiatan`
--

CREATE TABLE `tb_kegiatan` (
  `id_kegiatan` bigint(11) NOT NULL,
  `id_ekstrakulikuler` bigint(11) NOT NULL,
  `nama_kegiatan` varchar(255) NOT NULL,
  `kegiatan` varchar(255) NOT NULL,
  `jadwal` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_kegiatan`
--

INSERT INTO `tb_kegiatan` (`id_kegiatan`, `id_ekstrakulikuler`, `nama_kegiatan`, `kegiatan`, `jadwal`) VALUES
(5, 9, 'te', 'asdasd asd asd asd as asd asd asd asdasd', '2025-05-22 23:47:00'),
(6, 9, 'te', 'te', '2025-05-23 23:47:00'),
(7, 9, 'te', 'te', '2025-05-23 23:47:00'),
(8, 9, 'te', 'te', '2025-05-23 23:47:00');

-- --------------------------------------------------------

--
-- Table structure for table `tb_nilai`
--

CREATE TABLE `tb_nilai` (
  `id_nilai` bigint(11) NOT NULL,
  `id_peserta` bigint(11) NOT NULL,
  `nilai_keaktifan` varchar(255) DEFAULT NULL,
  `nilai_keterampilan` varchar(255) DEFAULT NULL,
  `nilai_sikap` varchar(255) DEFAULT NULL,
  `nilai_akhir` varchar(255) DEFAULT NULL,
  `tanggal_input` datetime DEFAULT NULL,
  `tanggal_update` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_nilai`
--

INSERT INTO `tb_nilai` (`id_nilai`, `id_peserta`, `nilai_keaktifan`, `nilai_keterampilan`, `nilai_sikap`, `nilai_akhir`, `tanggal_input`, `tanggal_update`) VALUES
(1, 5, '68', '55', '100', '72.4', '2025-05-22 21:12:24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tb_pembina`
--

CREATE TABLE `tb_pembina` (
  `pembina_id` bigint(11) NOT NULL,
  `id_user` bigint(11) NOT NULL,
  `pembina_nama` varchar(255) NOT NULL,
  `pembina_profile` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_pembina`
--

INSERT INTO `tb_pembina` (`pembina_id`, `id_user`, `pembina_nama`, `pembina_profile`) VALUES
(2, 9, 'Pembina Ekstrakulikuler 1', '682a29bfa0728.jpg'),
(4, 11, 'Pembina Ekstrakulikuler 2', '682a37c511d4f.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `tb_peserta`
--

CREATE TABLE `tb_peserta` (
  `id_peserta` bigint(11) NOT NULL,
  `id_ekstrakulikuler` bigint(11) NOT NULL,
  `id_user` bigint(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_peserta`
--

INSERT INTO `tb_peserta` (`id_peserta`, `id_ekstrakulikuler`, `id_user`) VALUES
(4, 9, 12),
(5, 9, 13),
(6, 11, 13);

-- --------------------------------------------------------

--
-- Table structure for table `tb_siswa`
--

CREATE TABLE `tb_siswa` (
  `siswa_id` bigint(11) NOT NULL,
  `id_user` bigint(11) NOT NULL,
  `siswa_nama` varchar(255) NOT NULL,
  `siswa_profile` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_siswa`
--

INSERT INTO `tb_siswa` (`siswa_id`, `id_user`, `siswa_nama`, `siswa_profile`) VALUES
(2, 12, 'tes siswa1', '682dd2dcb6533.png'),
(3, 13, 'tessiswa2', '682dd2f0c0c5f.png'),
(4, 14, 'adfaerfdasf', '682de06ca134c.png');

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` bigint(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `username`, `password`, `role`) VALUES
(1, 'admin', '123', 'Administrator'),
(9, 'pembina1', '123', 'Pembina'),
(11, 'pembina2', '123', 'Pembina'),
(12, 'tessiswa 1', '123', 'Siswa'),
(13, 'tessiswa2', '123', 'Siswa'),
(14, 'sidjeij3wie', 'ef21341234', 'Siswa');

-- --------------------------------------------------------

--
-- Table structure for table `tb_validasi`
--

CREATE TABLE `tb_validasi` (
  `id_validasi` bigint(11) NOT NULL,
  `id_ekstrakulikuler` bigint(11) DEFAULT NULL,
  `id_user` bigint(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tb_validasi`
--

INSERT INTO `tb_validasi` (`id_validasi`, `id_ekstrakulikuler`, `id_user`) VALUES
(4, 15, 13);

-- --------------------------------------------------------

--
-- Table structure for table `tb_wakilkepalasekolah`
--

CREATE TABLE `tb_wakilkepalasekolah` (
  `wakilkepalasekolah_id` bigint(11) NOT NULL,
  `id_user` bigint(11) NOT NULL,
  `wakilkepalasekolah_nama` varchar(255) NOT NULL,
  `wakilkepalasekolah_profile` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_absensi`
--
ALTER TABLE `tb_absensi`
  ADD PRIMARY KEY (`id_absensi`),
  ADD KEY `id_peserta` (`id_peserta`);

--
-- Indexes for table `tb_admin`
--
ALTER TABLE `tb_admin`
  ADD PRIMARY KEY (`adm_id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_ekstrakulikuler`
--
ALTER TABLE `tb_ekstrakulikuler`
  ADD PRIMARY KEY (`id_ekstrakulikuler`),
  ADD KEY `pembina_id` (`pembina_id`);

--
-- Indexes for table `tb_jadwal`
--
ALTER TABLE `tb_jadwal`
  ADD PRIMARY KEY (`id_jadwal`),
  ADD KEY `id_ekstrakulikuler` (`id_ekstrakulikuler`);

--
-- Indexes for table `tb_kegiatan`
--
ALTER TABLE `tb_kegiatan`
  ADD PRIMARY KEY (`id_kegiatan`),
  ADD KEY `id_ekstrakulikuler` (`id_ekstrakulikuler`);

--
-- Indexes for table `tb_nilai`
--
ALTER TABLE `tb_nilai`
  ADD PRIMARY KEY (`id_nilai`),
  ADD KEY `id_peserta` (`id_peserta`);

--
-- Indexes for table `tb_pembina`
--
ALTER TABLE `tb_pembina`
  ADD PRIMARY KEY (`pembina_id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_peserta`
--
ALTER TABLE `tb_peserta`
  ADD PRIMARY KEY (`id_peserta`),
  ADD KEY `id_ekstrakulikuler` (`id_ekstrakulikuler`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_siswa`
--
ALTER TABLE `tb_siswa`
  ADD PRIMARY KEY (`siswa_id`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`);

--
-- Indexes for table `tb_validasi`
--
ALTER TABLE `tb_validasi`
  ADD PRIMARY KEY (`id_validasi`),
  ADD KEY `id_ekstrakulikuler` (`id_ekstrakulikuler`),
  ADD KEY `id_user` (`id_user`);

--
-- Indexes for table `tb_wakilkepalasekolah`
--
ALTER TABLE `tb_wakilkepalasekolah`
  ADD PRIMARY KEY (`wakilkepalasekolah_id`),
  ADD KEY `id_user` (`id_user`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_absensi`
--
ALTER TABLE `tb_absensi`
  MODIFY `id_absensi` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_admin`
--
ALTER TABLE `tb_admin`
  MODIFY `adm_id` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tb_ekstrakulikuler`
--
ALTER TABLE `tb_ekstrakulikuler`
  MODIFY `id_ekstrakulikuler` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `tb_jadwal`
--
ALTER TABLE `tb_jadwal`
  MODIFY `id_jadwal` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `tb_kegiatan`
--
ALTER TABLE `tb_kegiatan`
  MODIFY `id_kegiatan` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tb_nilai`
--
ALTER TABLE `tb_nilai`
  MODIFY `id_nilai` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tb_pembina`
--
ALTER TABLE `tb_pembina`
  MODIFY `pembina_id` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_peserta`
--
ALTER TABLE `tb_peserta`
  MODIFY `id_peserta` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_siswa`
--
ALTER TABLE `tb_siswa`
  MODIFY `siswa_id` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `tb_validasi`
--
ALTER TABLE `tb_validasi`
  MODIFY `id_validasi` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tb_wakilkepalasekolah`
--
ALTER TABLE `tb_wakilkepalasekolah`
  MODIFY `wakilkepalasekolah_id` bigint(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_absensi`
--
ALTER TABLE `tb_absensi`
  ADD CONSTRAINT `tb_absensi_ibfk_1` FOREIGN KEY (`id_peserta`) REFERENCES `tb_peserta` (`id_peserta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_admin`
--
ALTER TABLE `tb_admin`
  ADD CONSTRAINT `tb_admin_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_ekstrakulikuler`
--
ALTER TABLE `tb_ekstrakulikuler`
  ADD CONSTRAINT `fk_pembina` FOREIGN KEY (`pembina_id`) REFERENCES `tb_pembina` (`pembina_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `tb_jadwal`
--
ALTER TABLE `tb_jadwal`
  ADD CONSTRAINT `tb_jadwal_ibfk_1` FOREIGN KEY (`id_ekstrakulikuler`) REFERENCES `tb_ekstrakulikuler` (`id_ekstrakulikuler`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_kegiatan`
--
ALTER TABLE `tb_kegiatan`
  ADD CONSTRAINT `tb_kegiatan_ibfk_1` FOREIGN KEY (`id_ekstrakulikuler`) REFERENCES `tb_ekstrakulikuler` (`id_ekstrakulikuler`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_nilai`
--
ALTER TABLE `tb_nilai`
  ADD CONSTRAINT `tb_nilai_ibfk_1` FOREIGN KEY (`id_peserta`) REFERENCES `tb_peserta` (`id_peserta`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_pembina`
--
ALTER TABLE `tb_pembina`
  ADD CONSTRAINT `tb_pembina_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_peserta`
--
ALTER TABLE `tb_peserta`
  ADD CONSTRAINT `tb_peserta_ibfk_1` FOREIGN KEY (`id_ekstrakulikuler`) REFERENCES `tb_ekstrakulikuler` (`id_ekstrakulikuler`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_peserta_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_siswa`
--
ALTER TABLE `tb_siswa`
  ADD CONSTRAINT `tb_siswa_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_validasi`
--
ALTER TABLE `tb_validasi`
  ADD CONSTRAINT `tb_validasi_ibfk_1` FOREIGN KEY (`id_ekstrakulikuler`) REFERENCES `tb_ekstrakulikuler` (`id_ekstrakulikuler`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `tb_validasi_ibfk_2` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `tb_wakilkepalasekolah`
--
ALTER TABLE `tb_wakilkepalasekolah`
  ADD CONSTRAINT `tb_wakilkepalasekolah_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
