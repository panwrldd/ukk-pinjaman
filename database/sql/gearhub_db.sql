-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: 127.0.0.1    Database: gearhub_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `gearhub_db`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `gearhub_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;

USE `gearhub_db`;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `module` varchar(50) NOT NULL DEFAULT 'system',
  `action` varchar(100) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_module_action_index` (`module`,`action`),
  KEY `activity_logs_user_id_index` (`user_id`),
  KEY `activity_logs_created_at_index` (`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
INSERT INTO `activity_logs` VALUES (1,6,'loans','loan_created','loans',1,'Pengajuan peminjaman LN-20261116-0001 dibuat dengan 2 jenis alat.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:53','2026-11-16 07:49:53'),(2,2,'db_trigger','loan_status_changed','loans',1,'Status transaksi peminjaman LN-20261106-0001 berubah dari \"pending\" menjadi \"approved\"','{\"status\": \"pending\"}','{\"status\": \"approved\"}',NULL,NULL,'2026-11-16 07:49:53','2026-11-16 07:49:53'),(3,2,'loans','loan_approved','loans',1,'Pengajuan peminjaman LN-20261106-0001 disetujui dan stok direservasi.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:53','2026-11-16 07:49:53'),(4,2,'db_trigger','loan_status_changed','loans',1,'Status transaksi peminjaman LN-20261106-0001 berubah dari \"approved\" menjadi \"borrowed\"','{\"status\": \"approved\"}','{\"status\": \"borrowed\"}',NULL,NULL,'2026-11-16 07:49:53','2026-11-16 07:49:53'),(5,2,'loans','loan_handed_over','loans',1,'Peralatan untuk peminjaman LN-20261106-0001 telah diserahkan kepada peminjam.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:53','2026-11-16 07:49:53'),(6,6,'returns','return_submitted','returns',1,'Pengajuan pengembalian RTN-20261116-0001 untuk pinjaman LN-20261106-0001 dikirim.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:53','2026-11-16 07:49:53'),(7,2,'db_trigger','loan_status_changed','loans',1,'Status transaksi peminjaman LN-20261106-0001 berubah dari \"borrowed\" menjadi \"returned\"','{\"status\": \"borrowed\"}','{\"status\": \"returned\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(8,2,'returns','return_verified','returns',1,'Pengembalian RTN-20261109-0001 diverifikasi. Keterlambatan: 0 hari, Denda: Rp0.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(9,5,'loans','loan_created','loans',2,'Pengajuan peminjaman LN-20261116-0001 dibuat dengan 2 jenis alat.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(10,3,'db_trigger','loan_status_changed','loans',2,'Status transaksi peminjaman LN-20261107-0002 berubah dari \"pending\" menjadi \"approved\"','{\"status\": \"pending\"}','{\"status\": \"approved\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(11,3,'loans','loan_approved','loans',2,'Pengajuan peminjaman LN-20261107-0002 disetujui dan stok direservasi.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(12,3,'db_trigger','loan_status_changed','loans',2,'Status transaksi peminjaman LN-20261107-0002 berubah dari \"approved\" menjadi \"borrowed\"','{\"status\": \"approved\"}','{\"status\": \"borrowed\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(13,3,'loans','loan_handed_over','loans',2,'Peralatan untuk peminjaman LN-20261107-0002 telah diserahkan kepada peminjam.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(14,5,'returns','return_submitted','returns',2,'Pengajuan pengembalian RTN-20261116-0001 untuk pinjaman LN-20261107-0002 dikirim.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(15,3,'db_trigger','loan_status_changed','loans',2,'Status transaksi peminjaman LN-20261107-0002 berubah dari \"borrowed\" menjadi \"returned\"','{\"status\": \"borrowed\"}','{\"status\": \"returned\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(16,3,'returns','return_verified','returns',2,'Pengembalian RTN-20261113-0002 diverifikasi. Keterlambatan: 3 hari, Denda: Rp15.000.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(17,7,'loans','loan_created','loans',3,'Pengajuan peminjaman LN-20261116-0001 dibuat dengan 1 jenis alat.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(18,2,'db_trigger','loan_status_changed','loans',3,'Status transaksi peminjaman LN-20261111-0003 berubah dari \"pending\" menjadi \"approved\"','{\"status\": \"pending\"}','{\"status\": \"approved\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(19,2,'loans','loan_approved','loans',3,'Pengajuan peminjaman LN-20261111-0003 disetujui dan stok direservasi.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(20,2,'db_trigger','loan_status_changed','loans',3,'Status transaksi peminjaman LN-20261111-0003 berubah dari \"approved\" menjadi \"borrowed\"','{\"status\": \"approved\"}','{\"status\": \"borrowed\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(21,2,'loans','loan_handed_over','loans',3,'Peralatan untuk peminjaman LN-20261111-0003 telah diserahkan kepada peminjam.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(22,2,'db_trigger','loan_status_changed','loans',3,'Status transaksi peminjaman LN-20261111-0003 berubah dari \"borrowed\" menjadi \"overdue\"','{\"status\": \"borrowed\"}','{\"status\": \"overdue\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(23,7,'returns','return_submitted','returns',3,'Pengajuan pengembalian RTN-20261116-0001 untuk pinjaman LN-20261111-0003 dikirim.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(24,4,'loans','loan_created','loans',4,'Pengajuan peminjaman LN-20261116-0001 dibuat dengan 2 jenis alat.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(25,3,'db_trigger','loan_status_changed','loans',4,'Status transaksi peminjaman LN-20261115-0004 berubah dari \"pending\" menjadi \"approved\"','{\"status\": \"pending\"}','{\"status\": \"approved\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(26,3,'loans','loan_approved','loans',4,'Pengajuan peminjaman LN-20261115-0004 disetujui dan stok direservasi.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(27,3,'db_trigger','loan_status_changed','loans',4,'Status transaksi peminjaman LN-20261115-0004 berubah dari \"approved\" menjadi \"borrowed\"','{\"status\": \"approved\"}','{\"status\": \"borrowed\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(28,3,'loans','loan_handed_over','loans',4,'Peralatan untuk peminjaman LN-20261115-0004 telah diserahkan kepada peminjam.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(29,5,'loans','loan_created','loans',5,'Pengajuan peminjaman LN-20261116-0001 dibuat dengan 1 jenis alat.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(30,3,'db_trigger','loan_status_changed','loans',5,'Status transaksi peminjaman LN-20261116-0001 berubah dari \"pending\" menjadi \"approved\"','{\"status\": \"pending\"}','{\"status\": \"approved\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(31,3,'loans','loan_approved','loans',5,'Pengajuan peminjaman LN-20261116-0001 disetujui dan stok direservasi.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(32,4,'loans','loan_created','loans',6,'Pengajuan peminjaman LN-20261116-0002 dibuat dengan 1 jenis alat.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(33,6,'loans','loan_created','loans',7,'Pengajuan peminjaman LN-20261116-0003 dibuat dengan 1 jenis alat.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54'),(34,3,'db_trigger','loan_status_changed','loans',7,'Status transaksi peminjaman LN-20261116-0003 berubah dari \"pending\" menjadi \"rejected\"','{\"status\": \"pending\"}','{\"status\": \"rejected\"}',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(35,3,'loans','loan_rejected','loans',7,'Pengajuan peminjaman LN-20261116-0003 ditolak. Alasan: Sesuai SOP Laboratorium, inventaris sekolah hanya diperuntukkan bagi kegiatan pembelajaran dan resmi sekolah.',NULL,NULL,'127.0.0.1','Symfony','2026-11-16 07:49:54','2026-11-16 07:49:54');
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `categories_code_unique` (`code`),
  UNIQUE KEY `categories_slug_unique` (`slug`),
  KEY `categories_name_index` (`name`),
  KEY `categories_is_active_index` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'KAT-KAM','Kamera & Videografi','kamera-videografi','Kamera mirrorless, DSLR, camcorder, lensa, dan aksesoris pengambilan gambar.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(2,'KAT-AUD','Audio & Podcast','audio-podcast','Mikrofon wireless, audio recorder, soundcard, dan headphone monitoring studio.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(3,'KAT-PRJ','Proyektor & Display','proyektor-display','LCD proyektor ruang praktik, layar portabel, capture card, dan pen tablet.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(4,'KAT-JAR','Laboratorium Jaringan & IoT','laboratorium-jaringan-iot','Router Mikrotik, switch manageable, crimping kit, LAN tester, dan perangkat praktik jaringan.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(5,'KAT-STD','Tata Cahaya & Penyangga Studio','tata-cahaya-penyangga-studio','LED video light, softbox, tripod fluida, gimbal stabilizer, dan perlengkapan studio.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `equipments`
--

DROP TABLE IF EXISTS `equipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint(20) unsigned NOT NULL,
  `code` varchar(30) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `condition` enum('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `qty_total` int(10) unsigned NOT NULL DEFAULT 1,
  `qty_available` int(10) unsigned NOT NULL DEFAULT 1,
  `qty_damaged` int(10) unsigned NOT NULL DEFAULT 0,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('tersedia','tidak_tersedia','maintenance') NOT NULL DEFAULT 'tersedia',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipments_code_unique` (`code`),
  KEY `equipments_name_index` (`name`),
  KEY `equipments_category_id_status_index` (`category_id`,`status`),
  KEY `equipments_condition_index` (`condition`),
  KEY `equipments_status_index` (`status`),
  CONSTRAINT `equipments_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `equipments`
--

LOCK TABLES `equipments` WRITE;
/*!40000 ALTER TABLE `equipments` DISABLE KEYS */;
INSERT INTO `equipments` VALUES (1,1,'EQP-KAM-001','Kamera Mirrorless Canon EOS M50 Mark II Kit 15-45mm','Kamera mirrorless 24.1MP 4K lengkap dengan lensa kit 15-45mm, baterai LP-E12, charger, dan SD Card 64GB.',NULL,'baik',4,3,0,'Lemari Dry Cabinet A-01 (Studio MM)','tersedia','Wajib dikembalikan beserta tutup lensa dan tas kamera.','2026-11-16 07:49:52','2026-11-16 07:49:54'),(2,1,'EQP-KAM-002','Camcorder Sony HXR-NX100 Full HD Professional','Camcorder profesional sensor 1.0-type Exmor R CMOS untuk peliputan kegiatan sekolah dan praktik broadcasting.',NULL,'baik',2,2,0,'Lemari Dry Cabinet A-02 (Studio MM)','tersedia','Termasuk baterai NP-F970 dan adaptor daya.','2026-11-16 07:49:52','2026-11-16 07:49:52'),(3,1,'EQP-KAM-003','Kamera DSLR Nikon D7500 Body + Lensa 18-140mm VR','Kamera DSLR untuk praktik fotografi jurnalistik dan dokumentasi acara.',NULL,'rusak_ringan',3,3,0,'Lemari Dry Cabinet A-03 (Studio MM)','tersedia','Karet eyecup sedikit longgar namun fungsi optik 100% normal.','2026-11-16 07:49:52','2026-11-16 07:49:52'),(4,2,'EQP-AUD-001','Microphone Wireless Rode Wireless GO II Dual Channel','Sistem mikrofon nirkabel 2 transmitter + 1 receiver dengan kabel TRS dan USB-C.',NULL,'baik',5,4,0,'Laci Audio B-01 (Ruang Podcast)','tersedia','Pastikan furry windshield tidak hilang.','2026-11-16 07:49:52','2026-11-16 07:49:54'),(5,2,'EQP-AUD-002','Portable Field Recorder Zoom H6 All Black','Perekam audio 6-track portabel dengan kapsul mikrofon X/Y untuk produksi film pendek.',NULL,'baik',3,3,0,'Laci Audio B-02 (Ruang Podcast)','tersedia','Dilengkapi koper pelindung keras.','2026-11-16 07:49:52','2026-11-16 07:49:52'),(6,3,'EQP-PRJ-001','Proyektor Epson EB-X500 XGA 3600 Lumens','Proyektor presentasi kelas dan sidang UKK lengkap dengan kabel HDMI 5m dan remote.',NULL,'baik',6,5,0,'Rak Inventaris C-01 (Lab RPL 1)','tersedia','Tunggu kipas berhenti sebelum mencabut kabel daya.','2026-11-16 07:49:52','2026-11-16 07:49:54'),(7,3,'EQP-PRJ-002','Pen Tablet Wacom Intuos Pro Medium PTH-660','Drawing tablet grafis dengan Wacom Pro Pen 2 untuk praktik desain UI/UX dan ilustrasi digital.',NULL,'baik',8,6,0,'Rak Inventaris C-02 (Lab DKV)','tersedia','Termasuk Pro Pen 2, dudukan pena, dan kabel USB-C.','2026-11-16 07:49:52','2026-11-16 07:49:54'),(8,3,'EQP-PRJ-003','HDMI Video Capture Card Elgato Cam Link 4K','Konverter HDMI ke USB 3.0 untuk kebutuhan live streaming kegiatan sekolah.',NULL,'baik',3,3,0,'Rak Inventaris C-03 (Lab RPL 1)','tersedia',NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(9,4,'EQP-JAR-001','Routerboard MikroTik hAP ac2 (RBD52G-5HacD2HnD)','Router dual-band gigabit 5 port untuk praktikum konfigurasi jaringan dan simulasi server.',NULL,'baik',10,10,0,'Lemari Jaringan D-01 (Lab TJKT)','tersedia','Reset konfigurasi setelah selesai praktikum.','2026-11-16 07:49:52','2026-11-16 07:49:54'),(10,4,'EQP-JAR-002','Toolkit Jaringan Lengkap (Crimping + LAN Tester + Punch Down)','Tas toolkit instalasi kabel UTP Cat6 lengkap dengan pengupas kabel dan LAN tester baterai 9V.',NULL,'baik',12,12,0,'Lemari Jaringan D-02 (Lab TJKT)','tersedia',NULL,'2026-11-16 07:49:52','2026-11-16 07:49:54'),(11,5,'EQP-STD-001','LED Studio Video Light Godox SL60W + Light Stand + Softbox','Lampu pencahayaan studio 60W 5600K lengkap dengan tripod lampu dan octabox 80cm.',NULL,'baik',4,3,1,'Gudang Studio E-01 (Studio MM)','tersedia','Pasang pelindung bohlam COB setelah dipakai.','2026-11-16 07:49:52','2026-11-16 07:49:54'),(12,5,'EQP-STD-002','Tripod Video Fluid Head Takara Rover 66','Tripod aluminium beban maks 6kg dengan kepala fluid head untuk pergerakan panning halus.',NULL,'baik',6,6,0,'Gudang Studio E-02 (Studio MM)','tersedia','Quick-release plate wajib terpasang kembali.','2026-11-16 07:49:52','2026-11-16 07:49:54');
/*!40000 ALTER TABLE `equipments` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_equipments_stock_guard_insert
BEFORE INSERT ON `equipments`
FOR EACH ROW
BEGIN
    IF NEW.`qty_available` > NEW.`qty_total` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Integritas stok gagal: stok tersedia tidak boleh melebihi total stok.';
    END IF;

    IF (NEW.`qty_available` + NEW.`qty_damaged`) > NEW.`qty_total` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Integritas stok gagal: stok tersedia ditambah stok rusak melebihi total stok.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_equipments_stock_guard_update
BEFORE UPDATE ON `equipments`
FOR EACH ROW
BEGIN
    IF NEW.`qty_available` > NEW.`qty_total` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Integritas stok gagal: stok tersedia tidak boleh melebihi total stok.';
    END IF;

    IF (NEW.`qty_available` + NEW.`qty_damaged`) > NEW.`qty_total` THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Integritas stok gagal: stok tersedia ditambah stok rusak melebihi total stok.';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` varchar(255) NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` smallint(5) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loan_details`
--

DROP TABLE IF EXISTS `loan_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loan_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `loan_id` bigint(20) unsigned NOT NULL,
  `equipment_id` bigint(20) unsigned NOT NULL,
  `qty` int(10) unsigned NOT NULL,
  `condition_before` enum('baik','rusak_ringan') NOT NULL DEFAULT 'baik',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loan_details_loan_id_equipment_id_unique` (`loan_id`,`equipment_id`),
  KEY `loan_details_equipment_id_foreign` (`equipment_id`),
  CONSTRAINT `loan_details_equipment_id_foreign` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `loan_details_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loan_details`
--

LOCK TABLES `loan_details` WRITE;
/*!40000 ALTER TABLE `loan_details` DISABLE KEYS */;
INSERT INTO `loan_details` VALUES (1,1,9,2,'baik','2026-11-16 07:49:53','2026-11-16 07:49:53'),(2,1,10,2,'baik','2026-11-16 07:49:53','2026-11-16 07:49:53'),(3,2,11,1,'baik','2026-11-16 07:49:54','2026-11-16 07:49:54'),(4,2,12,1,'baik','2026-11-16 07:49:54','2026-11-16 07:49:54'),(5,3,6,1,'baik','2026-11-16 07:49:54','2026-11-16 07:49:54'),(6,4,1,1,'baik','2026-11-16 07:49:54','2026-11-16 07:49:54'),(7,4,4,1,'baik','2026-11-16 07:49:54','2026-11-16 07:49:54'),(8,5,7,2,'baik','2026-11-16 07:49:54','2026-11-16 07:49:54'),(9,6,12,1,'baik','2026-11-16 07:49:54','2026-11-16 07:49:54'),(10,7,1,2,'baik','2026-11-16 07:49:54','2026-11-16 07:49:54');
/*!40000 ALTER TABLE `loan_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loans` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `loan_date` date NOT NULL,
  `due_date` date NOT NULL,
  `purpose` text NOT NULL,
  `status` enum('pending','approved','rejected','borrowed','overdue','returned','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `handed_over_by` bigint(20) unsigned DEFAULT NULL,
  `handed_over_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `loans_code_unique` (`code`),
  KEY `loans_approved_by_foreign` (`approved_by`),
  KEY `loans_handed_over_by_foreign` (`handed_over_by`),
  KEY `loans_status_index` (`status`),
  KEY `loans_user_id_status_index` (`user_id`,`status`),
  KEY `loans_due_date_status_index` (`due_date`,`status`),
  KEY `loans_loan_date_index` (`loan_date`),
  CONSTRAINT `loans_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `loans_handed_over_by_foreign` FOREIGN KEY (`handed_over_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `loans_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `loans`
--

LOCK TABLES `loans` WRITE;
/*!40000 ALTER TABLE `loans` DISABLE KEYS */;
INSERT INTO `loans` VALUES (1,'LN-20261106-0001',6,'2026-11-06','2026-11-09','Praktikum Konfigurasi Routing OSPF Kelas XI TJKT 1','returned',2,'2026-11-16 14:49:53',2,'2026-11-16 14:49:53',NULL,'Disetujui untuk jam praktik ke-1 s/d ke-6.','2026-11-16 07:49:53','2026-11-16 07:49:54'),(2,'LN-20261107-0002',5,'2026-11-07','2026-11-10','Produksi Video Profil Ekstrakurikuler & Dokumentasi Karya DKV','returned',3,'2026-11-16 14:49:54',3,'2026-11-16 14:49:54',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(3,'LN-20261111-0003',7,'2026-11-11','2026-11-14','Presentasi Sidang Proposal Proyek UKK Kelas XII RPL 2','overdue',2,'2026-11-16 14:49:54',2,'2026-11-16 14:49:54',NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(4,'LN-20261115-0004',4,'2026-11-15','2026-11-18','Pengambilan Video Demo Aplikasi Proyek UKK RPL','borrowed',3,'2026-11-16 14:49:54',3,'2026-11-16 14:49:54',NULL,'Silakan diambil di Studio Multimedia.','2026-11-16 07:49:54','2026-11-16 07:49:54'),(5,'LN-20261116-0001',5,'2026-11-16','2026-11-19','Praktikum Desain Ilustrasi Poster Digital Kelas XII DKV 2','approved',3,'2026-11-16 14:49:54',NULL,NULL,NULL,'Unit telah disiapkan di loket peminjaman.','2026-11-16 07:49:54','2026-11-16 07:49:54'),(6,'LN-20261116-0002',4,'2026-11-17','2026-11-20','Kebutuhan Tripod Tambahan untuk Dokumentasi Presentasi UKK','pending',NULL,NULL,NULL,NULL,NULL,NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(7,'LN-20261116-0003',6,'2026-11-16','2026-11-18','Dipinjam untuk acara pribadi di luar kegiatan sekolah','rejected',3,'2026-11-16 14:49:54',NULL,NULL,'Sesuai SOP Laboratorium, inventaris sekolah hanya diperuntukkan bagi kegiatan pembelajaran dan resmi sekolah.',NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54');
/*!40000 ALTER TABLE `loans` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_loans_status_audit
AFTER UPDATE ON `loans`
FOR EACH ROW
BEGIN
    IF OLD.`status` <> NEW.`status` THEN
        INSERT INTO `activity_logs` (
            `user_id`,
            `module`,
            `action`,
            `reference_type`,
            `reference_id`,
            `description`,
            `old_values`,
            `new_values`,
            `created_at`,
            `updated_at`
        ) VALUES (
            COALESCE(NEW.`handed_over_by`, NEW.`approved_by`, NEW.`user_id`),
            'db_trigger',
            'loan_status_changed',
            'loans',
            NEW.`id`,
            CONCAT('Status transaksi peminjaman ', NEW.`code`, ' berubah dari "', OLD.`status`, '" menjadi "', NEW.`status`, '"'),
            JSON_OBJECT('status', OLD.`status`),
            JSON_OBJECT('status', NEW.`status`),
            NOW(),
            NOW()
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2025_01_01_000001_create_categories_table',1),(5,'2025_01_01_000002_create_equipments_table',1),(6,'2025_01_01_000003_create_loans_table',1),(7,'2025_01_01_000004_create_loan_details_table',1),(8,'2025_01_01_000005_create_returns_table',1),(9,'2025_01_01_000006_create_return_details_table',1),(10,'2025_01_01_000007_create_activity_logs_table',1),(11,'2025_01_01_000008_create_settings_table',1),(12,'2025_01_01_000009_create_database_objects',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_details`
--

DROP TABLE IF EXISTS `return_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `return_details` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `return_id` bigint(20) unsigned NOT NULL,
  `loan_detail_id` bigint(20) unsigned NOT NULL,
  `equipment_id` bigint(20) unsigned NOT NULL,
  `qty_returned` int(10) unsigned NOT NULL,
  `qty_damaged` int(10) unsigned NOT NULL DEFAULT 0,
  `condition_after` enum('baik','rusak_ringan','rusak_berat') NOT NULL DEFAULT 'baik',
  `damage_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_details_loan_detail_id_unique` (`loan_detail_id`),
  KEY `return_details_equipment_id_foreign` (`equipment_id`),
  KEY `return_details_return_id_equipment_id_index` (`return_id`,`equipment_id`),
  KEY `return_details_condition_after_index` (`condition_after`),
  CONSTRAINT `return_details_equipment_id_foreign` FOREIGN KEY (`equipment_id`) REFERENCES `equipments` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `return_details_loan_detail_id_foreign` FOREIGN KEY (`loan_detail_id`) REFERENCES `loan_details` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `return_details_return_id_foreign` FOREIGN KEY (`return_id`) REFERENCES `returns` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `return_details`
--

LOCK TABLES `return_details` WRITE;
/*!40000 ALTER TABLE `return_details` DISABLE KEYS */;
INSERT INTO `return_details` VALUES (1,1,1,9,2,0,'baik',NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(2,1,2,10,2,0,'baik',NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54'),(3,2,3,11,1,1,'rusak_berat','Dudukan bowens mount patah dan kipas pendingin mati, masuk antrian servis.','2026-11-16 07:49:54','2026-11-16 07:49:54'),(4,2,4,12,1,0,'baik',NULL,'2026-11-16 07:49:54','2026-11-16 07:49:54');
/*!40000 ALTER TABLE `return_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `returns`
--

DROP TABLE IF EXISTS `returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `returns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `loan_id` bigint(20) unsigned NOT NULL,
  `returned_by` bigint(20) unsigned NOT NULL,
  `verified_by` bigint(20) unsigned DEFAULT NULL,
  `return_date` date NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `late_days` int(10) unsigned NOT NULL DEFAULT 0,
  `fine_per_day_snapshot` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fine_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `fine_paid` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('pending_verification','verified') NOT NULL DEFAULT 'pending_verification',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `returns_code_unique` (`code`),
  UNIQUE KEY `returns_loan_id_unique` (`loan_id`),
  KEY `returns_returned_by_foreign` (`returned_by`),
  KEY `returns_verified_by_foreign` (`verified_by`),
  KEY `returns_status_index` (`status`),
  KEY `returns_return_date_index` (`return_date`),
  KEY `returns_fine_paid_index` (`fine_paid`),
  CONSTRAINT `returns_loan_id_foreign` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `returns_returned_by_foreign` FOREIGN KEY (`returned_by`) REFERENCES `users` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `returns_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `returns`
--

LOCK TABLES `returns` WRITE;
/*!40000 ALTER TABLE `returns` DISABLE KEYS */;
INSERT INTO `returns` VALUES (1,'RTN-20261109-0001',1,6,2,'2026-11-09','2026-11-16 14:49:54',0,5000.00,0.00,1,'verified','Alat lengkap dan berfungsi baik.','2026-11-16 07:49:53','2026-11-16 07:49:54'),(2,'RTN-20261113-0002',2,5,3,'2026-11-13','2026-11-16 14:49:54',3,5000.00,15000.00,1,'verified','Denda keterlambatan 3 hari (Rp15.000) telah dibayar tunai. 1 unit Godox masuk maintenance.','2026-11-16 07:49:54','2026-11-16 07:49:54'),(3,'RTN-20261116-0001',3,7,NULL,'2026-11-16',NULL,2,0.00,10000.00,0,'pending_verification','Proyektor beserta kabel HDMI sudah diletakkan di meja petugas Lab RPL 1.','2026-11-16 07:49:54','2026-11-16 07:49:54');
/*!40000 ALTER TABLE `returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `value` text DEFAULT NULL,
  `type` varchar(30) NOT NULL DEFAULT 'string',
  `description` text DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`),
  KEY `settings_updated_by_foreign` (`updated_by`),
  CONSTRAINT `settings_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'fine_rate_per_day','5000','integer','Tarif denda keterlambatan pengembalian alat per hari (Rupiah).',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(2,'max_loan_days','7','integer','Durasi maksimal peminjaman peralatan dalam satu pengajuan (hari).',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(3,'max_active_loans','3','integer','Batas maksimal transaksi peminjaman aktif per peminjam.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(4,'institution_name','SMK Negeri 1 Rekayasa & Multimedia','string','Nama institusi yang dicetak pada kop laporan resmi.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(5,'institution_unit','Unit Pelaksana Teknis Laboratorium RPL & Multimedia','string','Nama unit/laboratorium pengelola inventaris.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(6,'institution_address','Jl. Pendidikan Teknologi No. 45, Gedung Laboratorium Terpadu Lt. 2','string','Alamat lengkap institusi pada kop cetak laporan.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(7,'institution_phone','(021) 5589-2410','string','Nomor telepon resmi laboratorium.',1,'2026-11-16 07:49:52','2026-11-16 07:49:52');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER trg_settings_value_audit
AFTER UPDATE ON `settings`
FOR EACH ROW
BEGIN
    IF COALESCE(OLD.`value`, '') <> COALESCE(NEW.`value`, '') THEN
        INSERT INTO `activity_logs` (
            `user_id`,
            `module`,
            `action`,
            `reference_type`,
            `reference_id`,
            `description`,
            `old_values`,
            `new_values`,
            `created_at`,
            `updated_at`
        ) VALUES (
            NEW.`updated_by`,
            'db_trigger',
            'setting_updated',
            'settings',
            NEW.`id`,
            CONCAT('Konfigurasi "', NEW.`key`, '" diperbarui dari "', COALESCE(OLD.`value`, ''), '" menjadi "', COALESCE(NEW.`value`, ''), '"'),
            JSON_OBJECT('key', OLD.`key`, 'value', OLD.`value`),
            JSON_OBJECT('key', NEW.`key`, 'value', NEW.`value`),
            NOW(),
            NOW()
        );
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `identity_number` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','petugas','peminjam') NOT NULL DEFAULT 'peminjam',
  `phone` varchar(25) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_username_unique` (`username`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_identity_number_unique` (`identity_number`),
  KEY `users_role_is_active_index` (`role`,`is_active`),
  KEY `users_name_index` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Hendra Wijaya, S.Kom.','admin','admin@gearhub.sch.id','198804122014021001','$2y$12$yA.SbfnNqeztNfN/3KVYee1j8A2xt.9uh4Cdr1GdFbrU.cIH8PJZu','admin','081288001101','Kepala Laboratorium Terpadu RPL & Multimedia',NULL,1,NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(2,'Dimas Saputra, A.Md.T.','petugas','petugas@gearhub.sch.id','199308192019031004','$2y$12$yA.SbfnNqeztNfN/3KVYee1j8A2xt.9uh4Cdr1GdFbrU.cIH8PJZu','petugas','081288001102','Teknisi Laboratorium RPL & Jaringan',NULL,1,NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(3,'Larasati Kusuma, S.Ds.','petugas.mm','petugas.mm@gearhub.sch.id','199603112022212008','$2y$12$yA.SbfnNqeztNfN/3KVYee1j8A2xt.9uh4Cdr1GdFbrU.cIH8PJZu','petugas','081288001103','Teknisi Studio Multimedia & Fotografi',NULL,1,NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(4,'Budi Santoso','peminjam','budi.santoso@siswa.gearhub.sch.id','24251001','$2y$12$yA.SbfnNqeztNfN/3KVYee1j8A2xt.9uh4Cdr1GdFbrU.cIH8PJZu','peminjam','085712340001','Kelas XII RPL 1',NULL,1,NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(5,'Siti Aisyah Putri','siti.aisyah','siti.aisyah@siswa.gearhub.sch.id','24251002','$2y$12$yA.SbfnNqeztNfN/3KVYee1j8A2xt.9uh4Cdr1GdFbrU.cIH8PJZu','peminjam','085712340002','Kelas XII DKV 2',NULL,1,NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(6,'Rizky Pratama','rizky.pratama','rizky.pratama@siswa.gearhub.sch.id','24251003','$2y$12$yA.SbfnNqeztNfN/3KVYee1j8A2xt.9uh4Cdr1GdFbrU.cIH8PJZu','peminjam','085712340003','Kelas XI TJKT 1',NULL,1,NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(7,'Nadia Maharani','nadia.maharani','nadia.maharani@siswa.gearhub.sch.id','24251004','$2y$12$yA.SbfnNqeztNfN/3KVYee1j8A2xt.9uh4Cdr1GdFbrU.cIH8PJZu','peminjam','085712340004','Kelas XII RPL 2',NULL,1,NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52'),(8,'Fajar Nugroho','fajar.nonaktif','fajar.nugroho@siswa.gearhub.sch.id','23241099','$2y$12$yA.SbfnNqeztNfN/3KVYee1j8A2xt.9uh4Cdr1GdFbrU.cIH8PJZu','peminjam','085712340099','Alumni / Akun Nonaktif',NULL,0,NULL,'2026-11-16 07:49:52','2026-11-16 07:49:52');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'gearhub_db'
--

--
-- Dumping routines for database 'gearhub_db'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_calculate_fine` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_calculate_fine`(p_due_date DATE, p_return_date DATE) RETURNS decimal(12,2)
    READS SQL DATA
BEGIN
    DECLARE v_late_days INT DEFAULT 0;
    DECLARE v_rate DECIMAL(12,2) DEFAULT 5000.00;
    DECLARE v_setting_val VARCHAR(100) DEFAULT NULL;

    SET v_late_days = fn_count_late_days(p_due_date, p_return_date);

    IF v_late_days <= 0 THEN
        RETURN 0.00;
    END IF;

    SELECT `value` INTO v_setting_val
    FROM `settings`
    WHERE `key` = 'fine_rate_per_day'
    LIMIT 1;

    IF v_setting_val IS NOT NULL AND v_setting_val REGEXP '^[0-9]+(\\.[0-9]+)?$' THEN
        SET v_rate = CAST(v_setting_val AS DECIMAL(12,2));
    END IF;

    RETURN v_late_days * v_rate;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP FUNCTION IF EXISTS `fn_count_late_days` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_count_late_days`(p_due_date DATE, p_return_date DATE) RETURNS int(11)
    DETERMINISTIC
BEGIN
    DECLARE v_diff INT DEFAULT 0;

    IF p_due_date IS NULL OR p_return_date IS NULL THEN
        RETURN 0;
    END IF;

    SET v_diff = DATEDIFF(p_return_date, p_due_date);

    RETURN IF(v_diff > 0, v_diff, 0);
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_approve_loan` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_approve_loan`(
    IN p_loan_id BIGINT UNSIGNED,
    IN p_staff_id BIGINT UNSIGNED
)
BEGIN
    DECLARE v_done TINYINT(1) DEFAULT 0;
    DECLARE v_loan_status VARCHAR(30) DEFAULT NULL;
    DECLARE v_eq_id BIGINT UNSIGNED;
    DECLARE v_req_qty INT UNSIGNED;
    DECLARE v_eq_name VARCHAR(150);
    DECLARE v_qty_avail INT UNSIGNED;
    DECLARE v_qty_dmg INT UNSIGNED;
    DECLARE v_eq_status VARCHAR(30);
    DECLARE v_new_avail INT;
    DECLARE v_err_msg VARCHAR(128);

    DECLARE cur_items CURSOR FOR
        SELECT `equipment_id`, `qty`
        FROM `loan_details`
        WHERE `loan_id` = p_loan_id
        ORDER BY `equipment_id` ASC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    SELECT `status` INTO v_loan_status
    FROM `loans`
    WHERE `id` = p_loan_id
    FOR UPDATE;

    IF v_loan_status IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data pengajuan peminjaman tidak ditemukan.';
    END IF;

    IF v_loan_status <> 'pending' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Hanya pengajuan berstatus pending yang dapat disetujui.';
    END IF;

    SET v_done = 0;
    OPEN cur_items;

    read_loop: LOOP
        FETCH cur_items INTO v_eq_id, v_req_qty;
        IF v_done = 1 THEN
            LEAVE read_loop;
        END IF;

        SELECT `name`, `qty_available`, `qty_damaged`, `status`
        INTO v_eq_name, v_qty_avail, v_qty_dmg, v_eq_status
        FROM `equipments`
        WHERE `id` = v_eq_id
        FOR UPDATE;

        IF v_eq_status <> 'tersedia' THEN
            SET v_err_msg = LEFT(CONCAT('Alat tidak tersedia untuk dipinjam: ', v_eq_name), 128);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err_msg;
        END IF;

        IF v_qty_avail < v_req_qty THEN
            SET v_err_msg = LEFT(CONCAT('Stok tidak mencukupi untuk alat: ', v_eq_name), 128);
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = v_err_msg;
        END IF;

        SET v_new_avail = v_qty_avail - v_req_qty;

        UPDATE `equipments`
        SET `qty_available` = v_new_avail,
            `status` = CASE
                WHEN v_new_avail > 0 THEN 'tersedia'
                WHEN v_qty_dmg > 0 THEN 'maintenance'
                ELSE 'tidak_tersedia'
            END,
            `updated_at` = NOW()
        WHERE `id` = v_eq_id;

        SET v_done = 0;
    END LOOP;

    CLOSE cur_items;

    UPDATE `loans`
    SET `status` = 'approved',
        `approved_by` = p_staff_id,
        `approved_at` = NOW(),
        `updated_at` = NOW()
    WHERE `id` = p_loan_id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `sp_process_return` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_process_return`(
    IN p_return_id BIGINT UNSIGNED,
    IN p_staff_id BIGINT UNSIGNED,
    IN p_fine_paid TINYINT(1),
    IN p_notes TEXT
)
BEGIN
    DECLARE v_done TINYINT(1) DEFAULT 0;
    DECLARE v_loan_id BIGINT UNSIGNED DEFAULT NULL;
    DECLARE v_return_date DATE DEFAULT NULL;
    DECLARE v_ret_status VARCHAR(30) DEFAULT NULL;
    DECLARE v_due_date DATE DEFAULT NULL;
    DECLARE v_loan_status VARCHAR(30) DEFAULT NULL;
    DECLARE v_detail_count INT DEFAULT 0;
    DECLARE v_late_days INT DEFAULT 0;
    DECLARE v_rate_snapshot DECIMAL(12,2) DEFAULT 5000.00;
    DECLARE v_setting_val VARCHAR(100) DEFAULT NULL;
    DECLARE v_fine_amount DECIMAL(12,2) DEFAULT 0.00;

    DECLARE v_eq_id BIGINT UNSIGNED;
    DECLARE v_qty_returned INT UNSIGNED;
    DECLARE v_qty_damaged INT UNSIGNED;
    DECLARE v_cond_after VARCHAR(30);
    DECLARE v_good_qty INT;
    DECLARE v_qty_total INT UNSIGNED;
    DECLARE v_qty_avail INT UNSIGNED;
    DECLARE v_cur_damaged INT UNSIGNED;
    DECLARE v_cur_cond VARCHAR(30);
    DECLARE v_new_avail INT;
    DECLARE v_new_damaged INT;
    DECLARE v_new_cond VARCHAR(30);

    DECLARE cur_details CURSOR FOR
        SELECT `equipment_id`, `qty_returned`, `qty_damaged`, `condition_after`
        FROM `return_details`
        WHERE `return_id` = p_return_id
        ORDER BY `equipment_id` ASC;

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = 1;

    SELECT `loan_id`, `return_date`, `status`
    INTO v_loan_id, v_return_date, v_ret_status
    FROM `returns`
    WHERE `id` = p_return_id
    FOR UPDATE;

    IF v_loan_id IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Data pengembalian tidak ditemukan.';
    END IF;

    IF v_ret_status <> 'pending_verification' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Pengembalian ini sudah diverifikasi sebelumnya.';
    END IF;

    SELECT `due_date`, `status`
    INTO v_due_date, v_loan_status
    FROM `loans`
    WHERE `id` = v_loan_id
    FOR UPDATE;

    IF v_loan_status NOT IN ('borrowed', 'overdue') THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Status peminjaman tidak valid untuk diverifikasi pengembaliannya.';
    END IF;

    SELECT COUNT(*) INTO v_detail_count
    FROM `return_details`
    WHERE `return_id` = p_return_id;

    IF v_detail_count <= 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Detail pemeriksaan kondisi alat belum diisi.';
    END IF;

    SET v_done = 0;
    SELECT `value` INTO v_setting_val
    FROM `settings`
    WHERE `key` = 'fine_rate_per_day'
    LIMIT 1;

    IF v_setting_val IS NOT NULL AND v_setting_val REGEXP '^[0-9]+(\\.[0-9]+)?$' THEN
        SET v_rate_snapshot = CAST(v_setting_val AS DECIMAL(12,2));
    END IF;

    SET v_late_days = fn_count_late_days(v_due_date, v_return_date);
    SET v_fine_amount = fn_calculate_fine(v_due_date, v_return_date);

    SET v_done = 0;
    OPEN cur_details;

    detail_loop: LOOP
        FETCH cur_details INTO v_eq_id, v_qty_returned, v_qty_damaged, v_cond_after;
        IF v_done = 1 THEN
            LEAVE detail_loop;
        END IF;

        IF v_qty_damaged > v_qty_returned THEN
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Jumlah unit rusak tidak boleh melebihi jumlah yang dikembalikan.';
        END IF;

        SET v_good_qty = v_qty_returned - v_qty_damaged;

        SELECT `qty_total`, `qty_available`, `qty_damaged`, `condition`
        INTO v_qty_total, v_qty_avail, v_cur_damaged, v_cur_cond
        FROM `equipments`
        WHERE `id` = v_eq_id
        FOR UPDATE;

        SET v_new_avail = v_qty_avail + v_good_qty;
        SET v_new_damaged = v_cur_damaged + v_qty_damaged;

        IF v_new_avail = 0 AND v_new_damaged > 0 THEN
            SET v_new_cond = 'rusak_berat';
        ELSEIF v_cond_after = 'rusak_ringan' AND v_good_qty > 0 THEN
            SET v_new_cond = 'rusak_ringan';
        ELSE
            SET v_new_cond = v_cur_cond;
        END IF;

        UPDATE `equipments`
        SET `qty_available` = v_new_avail,
            `qty_damaged` = v_new_damaged,
            `condition` = v_new_cond,
            `status` = CASE
                WHEN v_new_avail > 0 THEN 'tersedia'
                WHEN v_new_damaged > 0 THEN 'maintenance'
                ELSE 'tidak_tersedia'
            END,
            `updated_at` = NOW()
        WHERE `id` = v_eq_id;

        SET v_done = 0;
    END LOOP;

    CLOSE cur_details;

    UPDATE `returns`
    SET `status` = 'verified',
        `verified_by` = p_staff_id,
        `verified_at` = NOW(),
        `late_days` = v_late_days,
        `fine_per_day_snapshot` = v_rate_snapshot,
        `fine_amount` = v_fine_amount,
        `fine_paid` = IF(v_fine_amount <= 0, 1, IFNULL(p_fine_paid, 0)),
        `notes` = COALESCE(p_notes, `notes`),
        `updated_at` = NOW()
    WHERE `id` = p_return_id;

    UPDATE `loans`
    SET `status` = 'returned',
        `updated_at` = NOW()
    WHERE `id` = v_loan_id;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-16 14:50:19
