-- MySQL dump 10.13  Distrib 9.5.0, for Win64 (x86_64)
--
-- Host: localhost    Database: blinkit_clone
-- ------------------------------------------------------
-- Server version	9.5.0

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
SET @MYSQLDUMP_TEMP_LOG_BIN = @@SESSION.SQL_LOG_BIN;
SET @@SESSION.SQL_LOG_BIN= 0;

--
-- GTID state at the beginning of the backup 
--

SET @@GLOBAL.GTID_PURGED=/*!80000 '+'*/ '11f5765b-cde8-11f0-b9c8-fc3497a20ea7:1-48';

--
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `label` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Home',
  `line1` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `landmark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pincode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_addresses_user` (`user_id`),
  CONSTRAINT `fk_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admin_users`
--

DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `admin_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_users`
--

LOCK TABLES `admin_users` WRITE;
/*!40000 ALTER TABLE `admin_users` DISABLE KEYS */;
INSERT INTO `admin_users` VALUES (2,'Super Admin','admin@blinkhub.com','$2y$12$Ol193GuBvlahR.dWjJvX.u3EguoboghehUttppqBkzIQE1yHsZ6OK','2025-12-11 17:48:17'),(5,'Leon','leon@123.com','$2y$12$tbxS6Ryj16anRA8FLw1nOe.Ap6/Ld8pyhO8BWNKG2rcgW/v1kFJhO','2025-12-11 18:29:19');
/*!40000 ALTER TABLE `admin_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Groceries','groceries',1),(2,'Snacks','snacks',2),(3,'Beverages','beverages',3),(4,'Dairy & Bakery','dairy-bakery',4),(5,'Personal Care','personal-care',5),(6,'Household','household',6),(7,'Fresh Fruits','fresh-fruits',7),(8,'Ice Creams','ice-creams',8);
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_id` int unsigned NOT NULL,
  `qty` int unsigned NOT NULL DEFAULT '1',
  `unit_price` int unsigned NOT NULL,
  `line_total` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `status` enum('pending','paid','cancelled','delivered') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `subtotal` int unsigned NOT NULL,
  `delivery_fee` int unsigned NOT NULL DEFAULT '0',
  `total` int unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `category_id` int unsigned NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` int unsigned NOT NULL,
  `mrp` int unsigned NOT NULL,
  `tag` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eta_minutes` int unsigned DEFAULT '15',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `tags` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (9,1,'Maggi 2-Minute Noodles 280g','Multipack instant noodles (4x70g)',72,80,'Instant',14,1,'2025-12-01 11:47:57','noodles, maggi, instant, snack'),(10,1,'Aashirvaad Atta 5kg','Whole wheat flour 5kg pack',260,295,'Staple',20,1,'2025-12-01 11:47:57','atta, aashirvaad, flour, wheat'),(11,1,'Fortune Sunlite Oil 1L','Refined sunflower oil 1L pouch',155,175,'Everyday Use',18,1,'2025-12-01 11:47:57','oil, sunlite, cooking oil, fortune'),(12,1,'Tata Salt 1kg','Iodised salt 1kg packet',22,28,'Bestseller',12,1,'2025-12-01 11:47:57',NULL),(13,1,'India Gate Basmati Rice 1kg','Classic basmati rice for daily use',135,150,'Premium',19,1,'2025-12-01 11:47:57','rice, basmati, grain, india gate'),(14,2,'Lay\'s Magic Masala 90g','Masala flavoured potato chips',35,40,'Trending',12,1,'2025-12-01 11:47:57','chips, lays, snack, masala'),(15,2,'Kurkure Masala Munch 90g','Crunchy masala extruded snack',25,30,'Chatpata',11,1,'2025-12-01 11:47:57','chips, kurkure, snack, chatpata'),(16,2,'Too Yumm Multigrain Chips 85g','Baked multigrain chips snack',45,55,'Baked',13,1,'2025-12-01 11:47:57',NULL),(17,2,'Haldiram\'s Aloo Bhujia 200g','Spicy aloo bhujia namkeen',55,65,'Classic',15,1,'2025-12-01 11:47:57',NULL),(18,2,'Cadbury Dairy Milk Silk 150g','Silky smooth chocolate bar',155,170,'Indulgence',13,1,'2025-12-01 11:47:57',NULL),(19,3,'Coca-Cola 1.25L','Chilled Coke 1.25L bottle',75,85,'Party Pack',15,1,'2025-12-01 11:47:57',NULL),(20,3,'Sprite 750ml','Lemon-lime flavoured soft drink',45,55,'Chiller',12,1,'2025-12-01 11:47:57',NULL),(21,3,'Thums Up 750ml','Strong cola soft drink',50,60,'Bold Taste',13,1,'2025-12-01 11:47:57',NULL),(22,3,'Real Mixed Fruit Juice 1L','Thick mixed fruit juice carton',115,130,'Breakfast Fav',16,1,'2025-12-01 11:47:57',NULL),(23,3,'Bru Instant Coffee 100g','Instant coffee-chicory mix',125,140,'Morning Fix',17,1,'2025-12-01 11:47:57',NULL),(24,4,'Amul Toned Milk 1L','Fresh Amul toned milk 1L pack',62,70,'Bestseller',10,1,'2025-12-01 11:47:57',NULL),(25,4,'Mother Dairy Curd 400g','Fresh set dahi 400g tub',38,45,'Everyday Use',11,1,'2025-12-01 11:47:57',NULL),(26,4,'Brown Bread 400g','Soft whole wheat brown bread',45,50,'Fresh',11,1,'2025-12-01 11:47:57',NULL),(27,4,'Amul Butter 100g','Salted table butter pack',54,60,'Iconic',9,1,'2025-12-01 11:47:57',NULL),(28,4,'Britannia Cheese Slices 200g','Processed cheese slices (10 pcs)',135,150,'Cheesy',13,1,'2025-12-01 11:47:57',NULL),(29,5,'Colgate Strong Teeth 100g','Fluoride toothpaste for cavity protection',55,65,'Value Pack',14,1,'2025-12-01 11:47:57',NULL),(30,5,'Dove Cream Beauty Bathing Bar 100g','Moisturising bathing bar',42,50,'Soft Skin',16,1,'2025-12-01 11:47:57',NULL),(31,5,'Lifebuoy Handwash 190ml','Total 10 germ protection handwash',68,79,'Hygiene',15,1,'2025-12-01 11:47:57',NULL),(32,5,'Pantene Shampoo 180ml','Advanced hairfall solution shampoo',125,145,'Hair Care',20,1,'2025-12-01 11:47:57',NULL),(33,5,'Nivea Body Lotion 200ml','Smooth milk body lotion for dry skin',210,235,'Winter Care',22,1,'2025-12-01 11:47:57',NULL),(34,6,'Surf Excel Easy Wash 1kg','Detergent powder 1kg pack',185,210,'Laundry',18,1,'2025-12-01 11:47:57',NULL),(35,6,'Vim Dishwash Bar 300g','Lemon dishwash bar',35,40,'Kitchen Essential',14,1,'2025-12-01 11:47:57',NULL),(36,6,'Lizol Floor Cleaner 500ml','Citrus disinfectant surface cleaner',110,125,'Disinfectant',19,1,'2025-12-01 11:47:57',NULL),(37,6,'Harpic Toilet Cleaner 500ml','Power plus toilet cleaner',98,110,'Bathroom',19,1,'2025-12-01 11:47:57','oil, sunlite, cooking oil, fortune'),(38,6,'Good Knight Advanced Refill 45ml','Liquid mosquito repellent refill',75,85,'Mosquito Care',21,1,'2025-12-01 11:47:57',NULL),(39,7,'Banana Yelakki (6 pcs)','Fresh Yelakki bananas pack of 6',55,65,'Fresh Pick',9,1,'2025-12-01 11:47:57',NULL),(40,7,'Apple Fuji 4 pcs (approx. 800g)','Crisp, juicy imported apples',185,210,'Imported',15,1,'2025-12-01 11:47:57',NULL),(41,7,'Seedless Green Grapes 500g','Table grapes, juicy and sweet',95,115,'Seasonal',14,1,'2025-12-01 11:47:57',NULL),(42,7,'Pomegranate 2 pcs (approx. 500g)','Fresh pomegranates, medium size',110,130,'Antioxidant',16,1,'2025-12-01 11:47:57',NULL),(43,7,'Papaya (Medium, 1 pc)','Ripe papaya, ready to cut',60,75,'Immunity',18,1,'2025-12-01 11:47:57',NULL),(44,8,'Amul Vanilla Ice Cream 1L','Family pack vanilla ice cream',190,210,'Family Pack',20,1,'2025-12-01 11:47:57',NULL),(45,8,'Amul Choco Bar 60ml','Chocolate coated vanilla bar',35,40,'On The Go',12,1,'2025-12-01 11:47:57',NULL),(46,8,'Cornetto Choco Disc 110ml','Crunchy cone with chocolate topping',60,70,'Treat',15,1,'2025-12-01 11:47:57',NULL),(47,8,'Kwality Walls Butterscotch 700ml','Butterscotch flavoured brick',195,220,'Party Fav',18,1,'2025-12-01 11:47:57',NULL),(48,8,'Amul Dark Chocolate Ice Cream 750ml','Rich dark chocolate ice cream tub',240,270,'Premium',20,1,'2025-12-01 11:47:57','');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `role` enum('user','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'test','admin@test1235.com','$2y$12$M53m4BwULrI6OJDfOlKdsexYQHdtgoxfPPqVLgPY8qE/d0oFwgmY2','2025-12-11 15:52:46','user');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
SET @@SESSION.SQL_LOG_BIN = @MYSQLDUMP_TEMP_LOG_BIN;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-12  0:08:10
