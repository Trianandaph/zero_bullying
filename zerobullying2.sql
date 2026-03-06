/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: zerobullying2
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0+deb12u2

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
-- Table structure for table `access_requests`
--

DROP TABLE IF EXISTS `access_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `guru_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `guru_id` (`guru_id`),
  CONSTRAINT `access_requests_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `access_requests_ibfk_2` FOREIGN KEY (`guru_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_requests`
--

LOCK TABLES `access_requests` WRITE;
/*!40000 ALTER TABLE `access_requests` DISABLE KEYS */;
INSERT INTO `access_requests` VALUES
(1,2,3,'approved',0,'2026-02-09 03:44:01'),
(2,1,3,'approved',0,'2026-02-11 07:52:03'),
(4,2,5,'rejected',1,'2026-03-03 03:06:25'),
(5,6,3,'rejected',1,'2026-03-04 04:51:38'),
(6,16,3,'pending',0,'2026-03-06 00:23:53');
/*!40000 ALTER TABLE `access_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comments`
--

LOCK TABLES `comments` WRITE;
/*!40000 ALTER TABLE `comments` DISABLE KEYS */;
INSERT INTO `comments` VALUES
(1,1,2,'betul2',NULL,'2026-02-09 02:32:29'),
(2,1,2,'halah',NULL,'2026-02-09 03:04:07'),
(3,1,4,'hrdhrxhd ',NULL,'2026-02-09 03:06:37'),
(4,2,4,'asli ini',NULL,'2026-02-09 03:10:19'),
(5,2,3,'ngawur',NULL,'2026-02-09 03:10:41'),
(9,6,3,'woy',NULL,'2026-03-03 03:26:58'),
(10,6,2,'p',NULL,'2026-03-04 04:51:20'),
(11,12,2,'kelas kingg',NULL,'2026-03-05 21:04:09'),
(12,16,2,'wooo',NULL,'2026-03-06 00:10:51'),
(13,15,2,'😹',NULL,'2026-03-06 00:11:00');
/*!40000 ALTER TABLE `comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `interactions`
--

DROP TABLE IF EXISTS `interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('agree','disagree') NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_interaction` (`post_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `interactions_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `interactions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interactions`
--

LOCK TABLES `interactions` WRITE;
/*!40000 ALTER TABLE `interactions` DISABLE KEYS */;
INSERT INTO `interactions` VALUES
(9,1,1,'agree'),
(24,1,4,'disagree'),
(25,2,4,'agree'),
(35,1,2,'agree'),
(37,2,2,'agree'),
(51,6,7,'agree'),
(52,2,7,'agree'),
(54,12,2,'agree');
/*!40000 ALTER TABLE `interactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `messages`
--

DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `messages`
--

LOCK TABLES `messages` WRITE;
/*!40000 ALTER TABLE `messages` DISABLE KEYS */;
INSERT INTO `messages` VALUES
(1,2,3,'tes',1,'2026-02-11 07:09:34'),
(2,3,2,'ya',1,'2026-02-11 07:17:22'),
(3,3,2,'p',1,'2026-02-11 07:18:34'),
(4,2,3,'aa',1,'2026-02-11 07:18:47'),
(5,3,2,'asa]',1,'2026-02-11 07:47:58'),
(6,2,3,'aa',1,'2026-02-11 07:48:13'),
(7,2,1,'p',0,'2026-02-26 00:53:16'),
(8,2,1,'info min',0,'2026-02-26 04:07:37'),
(9,2,3,'dableyu',1,'2026-02-26 04:09:01'),
(10,2,3,'dabelyu',1,'2026-02-26 04:09:06'),
(11,6,1,'hai',0,'2026-02-26 06:19:25'),
(12,7,3,'p',1,'2026-02-26 06:37:23'),
(13,2,3,'p',1,'2026-02-26 09:29:04'),
(14,2,3,'a',1,'2026-02-26 09:29:59'),
(15,3,2,'ok',1,'2026-02-26 09:34:10'),
(16,3,2,'a',1,'2026-02-26 09:34:20'),
(17,2,3,'tes',1,'2026-02-26 09:34:42'),
(18,3,2,'q',1,'2026-02-26 09:40:02'),
(19,3,2,'a',1,'2026-02-26 09:43:04'),
(20,3,2,'ss',1,'2026-02-26 09:43:06'),
(21,2,3,'apa pak',1,'2026-02-26 09:43:23'),
(22,2,3,'lapor',1,'2026-02-26 09:43:26'),
(23,3,2,'ya ada apa muridku',1,'2026-02-26 09:50:01'),
(24,3,2,'woy',1,'2026-02-26 09:56:25'),
(25,2,3,'a',1,'2026-02-26 09:58:50'),
(26,3,2,'tes',1,'2026-02-26 10:01:42'),
(27,3,2,'p',1,'2026-02-26 23:49:07'),
(28,3,2,'tess',1,'2026-02-26 23:49:27');
/*!40000 ALTER TABLE `messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES
(1,2,'Syahrull mode malam',NULL,'2026-02-09 02:32:06'),
(2,4,'bram gay bgt jir',NULL,'2026-02-09 03:09:57'),
(6,7,'linggay',NULL,'2026-02-26 09:19:31'),
(12,2,'a','1772647824_6a9c488d8874b34c23a2a336e848f28e.jpg','2026-03-04 18:10:24'),
(14,8,'*****',NULL,'2026-03-05 23:44:02'),
(15,8,'***',NULL,'2026-03-05 23:59:21'),
(16,8,'***',NULL,'2026-03-05 23:59:28');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('user','admin','guru') NOT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `jurusan` varchar(50) DEFAULT NULL,
  `gender` enum('L','P') DEFAULT NULL,
  `no_telp` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Administrator System','admin',NULL,'$2y$10$OAjaUcJtyGvZU.R.yZgBeedaN9fbPlzgk7szqXEp17T5MLtpwFcoO','admin',NULL,NULL,NULL,NULL),
(2,'Nanda','nandalemon32',NULL,'$2y$10$w3.UXhvgK/1xZRQAkiexTuRmzlRfBqmqQZaQ0Hjjn4yQBvFFqz4IW','user','11','SIJA','L','081234567'),
(3,'WE','pakwe123',NULL,'$2y$10$OiOLYJDT5aURZMJPdOVf9uirvIA7nUPcJ3475/djMx7F9Vesn0eZO','guru',NULL,NULL,NULL,'1345678'),
(4,'PEJE','peje123',NULL,'$2y$10$wC/TDq5SD.57RFIPB8BosOtTm5YWqCpYrTICADCv7KTSsXwYAsXju','user','11','SIJA','L','098197267167'),
(5,'amba','amba52',NULL,'$2y$10$pHZ.6D6sN84iwpHQe.CIPOz2c0Np.stnMbLa3i20PFdD.pNjZbKCC','guru',NULL,NULL,NULL,'085900238941'),
(7,'Reyhan','reyhan123','ryzen2f4f@gmail.com','$2y$10$6GIOn1CIZQGW.XmDni.dTetCd9HQ4zreaomu5PZlBtGb8Z0zPUPYS','user','11','SIJA','L','085900238941'),
(8,'reyhan321','reyhan321','ryzz12969@gmail.com','$2y$10$1ImLq49.IWGn883Koqx54OQoyDQ6HnIcjRXhCZIFln7/kEKLqPuZe','user','10','TP','L','081347186622');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-06  9:18:23
