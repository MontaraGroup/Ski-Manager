-- MySQL dump 10.13  Distrib 8.4.9, for Linux (x86_64)
--
-- Host: localhost    Database: skimanager_v2
-- ------------------------------------------------------
-- Server version	8.4.9

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

--
-- Table structure for table `achievement_defs`
--

DROP TABLE IF EXISTS `achievement_defs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievement_defs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `achievement_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(100) NOT NULL,
  `target` int unsigned DEFAULT '1',
  `reward` int unsigned DEFAULT '0',
  `sort_order` int unsigned DEFAULT '0',
  `unlocks` varchar(255) DEFAULT NULL,
  `unlock_label` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `achievement_key` (`achievement_key`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `achievements`
--

DROP TABLE IF EXISTS `achievements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `achievements` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `achievement_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `progress` int unsigned NOT NULL DEFAULT '0',
  `target` int unsigned NOT NULL DEFAULT '1',
  `reward_amount` int unsigned NOT NULL DEFAULT '0',
  `completed` tinyint NOT NULL DEFAULT '0',
  `claimed` tinyint NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `game_day` int unsigned NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `message` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `icon` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'fa-solid fa-circle-info',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_game_day` (`user_id`,`game_day`)
) ENGINE=InnoDB AUTO_INCREMENT=253 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_groups_users`
--

DROP TABLE IF EXISTS `auth_groups_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_groups_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `auth_groups_users_user_id_foreign` (`user_id`),
  CONSTRAINT `auth_groups_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_identities`
--

DROP TABLE IF EXISTS `auth_identities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_identities` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `secret` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `secret2` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `extra` text COLLATE utf8mb4_general_ci,
  `force_reset` tinyint(1) NOT NULL DEFAULT '0',
  `last_used_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_secret` (`type`,`secret`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `auth_identities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_logins`
--

DROP TABLE IF EXISTS `auth_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_logins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_type` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `date` datetime NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_type_identifier` (`id_type`,`identifier`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_permissions_users`
--

DROP TABLE IF EXISTS `auth_permissions_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_permissions_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `permission` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `auth_permissions_users_user_id_foreign` (`user_id`),
  CONSTRAINT `auth_permissions_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_remember_tokens`
--

DROP TABLE IF EXISTS `auth_remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_remember_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `selector` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `hashedValidator` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int unsigned NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `selector` (`selector`),
  KEY `auth_remember_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `auth_remember_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `auth_token_logins`
--

DROP TABLE IF EXISTS `auth_token_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auth_token_logins` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `id_type` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `identifier` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int unsigned DEFAULT NULL,
  `date` datetime NOT NULL,
  `success` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `id_type_identifier` (`id_type`,`identifier`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bonus_rewards`
--

DROP TABLE IF EXISTS `bonus_rewards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bonus_rewards` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `streak_day` int unsigned NOT NULL,
  `reward` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `streak_day` (`streak_day`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `building_levels`
--

DROP TABLE IF EXISTS `building_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `building_levels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type_key` varchar(50) NOT NULL,
  `level` int unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `capacity` int unsigned DEFAULT '0',
  `revenue` int unsigned DEFAULT '0',
  `upkeep` int unsigned DEFAULT '0',
  `cost` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_key` (`type_key`,`level`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `building_types`
--

DROP TABLE IF EXISTS `building_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `building_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `singular` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `color` varchar(50) NOT NULL,
  `description` text,
  `route` varchar(100) NOT NULL,
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_key` (`type_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `buildings`
--

DROP TABLE IF EXISTS `buildings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `buildings` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `building_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `level` int unsigned NOT NULL DEFAULT '1',
  `capacity` int unsigned NOT NULL DEFAULT '0',
  `revenue_per_day` int unsigned NOT NULL DEFAULT '0',
  `upkeep_per_day` int unsigned NOT NULL DEFAULT '0',
  `price_rate` int unsigned DEFAULT '100',
  `satisfaction` int unsigned DEFAULT '80',
  `specialty` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `condition_pct` int unsigned NOT NULL DEFAULT '100',
  `status` enum('open','closed','upgrading','broken') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `campaign_types`
--

DROP TABLE IF EXISTS `campaign_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `campaign_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `daily_cost` int unsigned DEFAULT '0',
  `visitor_boost` int unsigned DEFAULT '0',
  `reputation_boost` int unsigned DEFAULT '0',
  `duration_days` int unsigned DEFAULT '7',
  `total_price` int unsigned DEFAULT '0',
  `description` text,
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_key` (`type_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cannon_types`
--

DROP TABLE IF EXISTS `cannon_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cannon_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `level` int unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `output_per_day` int unsigned DEFAULT '0',
  `energy_cost` int unsigned DEFAULT '0',
  `water_usage` int unsigned DEFAULT '0',
  `cost` int unsigned DEFAULT '0',
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `level` (`level`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `daily_bonus`
--

DROP TABLE IF EXISTS `daily_bonus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_bonus` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `last_claim_day` int unsigned NOT NULL DEFAULT '0',
  `streak` int unsigned NOT NULL DEFAULT '0',
  `total_claimed` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dashboard_widgets`
--

DROP TABLE IF EXISTS `dashboard_widgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_widgets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `widget_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `visible` tinyint NOT NULL DEFAULT '1',
  `size` enum('small','medium','large') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'large',
  `sort_order` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=97 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `difficulty_config`
--

DROP TABLE IF EXISTS `difficulty_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `difficulty_config` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `difficulty` enum('easy','standard','hard') NOT NULL,
  `config_key` varchar(50) NOT NULL,
  `config_value` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `difficulty` (`difficulty`,`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `energy_management`
--

DROP TABLE IF EXISTS `energy_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `energy_management` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `source_type` enum('grid','solar','wind','generator') NOT NULL DEFAULT 'grid',
  `name` varchar(100) NOT NULL,
  `capacity_kwh` int unsigned NOT NULL DEFAULT '0',
  `output_kwh` int unsigned NOT NULL DEFAULT '0',
  `cost_per_day` int unsigned NOT NULL DEFAULT '0',
  `condition_pct` decimal(5,2) NOT NULL DEFAULT '100.00',
  `status` enum('active','off','broken','under_construction') NOT NULL DEFAULT 'under_construction',
  `build_days_left` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `environmental`
--

DROP TABLE IF EXISTS `environmental`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `environmental` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `eco_score` int unsigned NOT NULL DEFAULT '50',
  `carbon_output` int unsigned NOT NULL DEFAULT '0',
  `renewable_pct` int unsigned NOT NULL DEFAULT '0',
  `waste_management` int unsigned NOT NULL DEFAULT '0',
  `wildlife_impact` int unsigned NOT NULL DEFAULT '50',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `equipment`
--

DROP TABLE IF EXISTS `equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `equipment_type` enum('groomer','snowmaker') COLLATE utf8mb4_general_ci NOT NULL,
  `model_key` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `brand` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `capacity` int unsigned NOT NULL DEFAULT '0',
  `fuel_cost` int unsigned NOT NULL DEFAULT '0',
  `condition_pct` int unsigned NOT NULL DEFAULT '100',
  `assigned_to` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','off','broken','maintenance') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'off',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `output_per_day` int unsigned DEFAULT '0',
  `energy_kwh` int unsigned DEFAULT '0',
  `water_liters` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `equipment_catalog`
--

DROP TABLE IF EXISTS `equipment_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_catalog` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `model_key` varchar(50) NOT NULL,
  `equipment_type` enum('groomer','snowmaker') NOT NULL,
  `brand` varchar(100) NOT NULL,
  `name` varchar(150) NOT NULL,
  `description` text,
  `capacity` int unsigned DEFAULT '0',
  `fuel_cost` int unsigned DEFAULT '0',
  `cost` int unsigned DEFAULT '0',
  `output_per_day` int unsigned DEFAULT '0',
  `energy_kwh` int unsigned DEFAULT '0',
  `water_liters` int unsigned DEFAULT '0',
  `icon` varchar(100) DEFAULT 'fa-solid fa-truck-monster',
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `model_key` (`model_key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `financial_transactions`
--

DROP TABLE IF EXISTS `financial_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_transactions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `game_day` int unsigned NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `type` enum('income','expense') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'income',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_game_day` (`user_id`,`game_day`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `genepis`
--

DROP TABLE IF EXISTS `genepis`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genepis` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `balance` int unsigned NOT NULL DEFAULT '100',
  `total_earned` int unsigned NOT NULL DEFAULT '100',
  `total_spent` int unsigned NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `genepis_log`
--

DROP TABLE IF EXISTS `genepis_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `genepis_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `amount` int NOT NULL DEFAULT '0',
  `type` enum('earned','spent') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'earned',
  `reason` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `insurance`
--

DROP TABLE IF EXISTS `insurance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `policy_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `premium_per_day` int unsigned NOT NULL,
  `coverage_amount` int unsigned NOT NULL,
  `active` tinyint NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `insurance_types`
--

DROP TABLE IF EXISTS `insurance_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insurance_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `premium` int unsigned DEFAULT '0',
  `coverage` int unsigned DEFAULT '0',
  `icon` varchar(100) NOT NULL,
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_key` (`type_key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lift_tickets`
--

DROP TABLE IF EXISTS `lift_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lift_tickets` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `ticket_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `price` int unsigned NOT NULL DEFAULT '0',
  `active` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `loans`
--

DROP TABLE IF EXISTS `loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `loans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `loan_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `principal` int unsigned NOT NULL,
  `interest_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `remaining` int unsigned NOT NULL,
  `daily_payment` int unsigned NOT NULL,
  `days_total` int unsigned NOT NULL,
  `days_remaining` int unsigned NOT NULL,
  `status` enum('active','paid','defaulted') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_lockouts`
--

DROP TABLE IF EXISTS `login_lockouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_lockouts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `locked_until` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `maintenance_task_types`
--

DROP TABLE IF EXISTS `maintenance_task_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_task_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `task_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `description` text,
  `cost_per_unit` int unsigned DEFAULT '0',
  `target` varchar(50) NOT NULL,
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `task_key` (`task_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `map_segments`
--

DROP TABLE IF EXISTS `map_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `map_segments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL DEFAULT '1',
  `resort_map` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Vail',
  `type` enum('lift','slope') COLLATE utf8mb4_general_ci NOT NULL,
  `difficulty` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `points` json NOT NULL,
  `midstations` json DEFAULT NULL,
  `length_meters` int unsigned NOT NULL DEFAULT '0',
  `sector` int unsigned NOT NULL DEFAULT '0',
  `active` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_user_map` (`user_id`,`resort_map`)
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `marketing_campaigns`
--

DROP TABLE IF EXISTS `marketing_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `marketing_campaigns` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `campaign_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `daily_cost` int unsigned NOT NULL DEFAULT '0',
  `visitor_boost` int unsigned NOT NULL DEFAULT '0',
  `reputation_boost` int unsigned NOT NULL DEFAULT '0',
  `days_remaining` int unsigned NOT NULL DEFAULT '0',
  `status` enum('active','paused','expired') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `group` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `namespace` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `time` int NOT NULL,
  `batch` int unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `night_skiing`
--

DROP TABLE IF EXISTS `night_skiing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `night_skiing` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `light_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `light_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `level` int unsigned NOT NULL DEFAULT '1',
  `coverage` int unsigned NOT NULL DEFAULT '0',
  `energy_cost` int unsigned NOT NULL DEFAULT '0',
  `status` enum('active','off','broken') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'off',
  `condition_pct` int unsigned NOT NULL DEFAULT '100',
  `assigned_slope` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `icon` varchar(80) NOT NULL DEFAULT 'fa-solid fa-bell',
  `link` varchar(200) DEFAULT NULL,
  `is_read` tinyint NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_read` (`user_id`,`is_read`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `park_feature_types`
--

DROP TABLE IF EXISTS `park_feature_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `park_feature_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `type_key` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `size` enum('small','medium','large') NOT NULL,
  `cost` int unsigned DEFAULT '0',
  `upkeep` int unsigned DEFAULT '0',
  `build_days` int unsigned DEFAULT '1',
  `capacity` int unsigned DEFAULT '0',
  `popularity_base` int unsigned DEFAULT '0',
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_key` (`type_key`,`size`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `parking`
--

DROP TABLE IF EXISTS `parking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parking` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `parking_type` enum('surface_lot','garage','shuttle_stop','village_gondola') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'surface_lot',
  `capacity` int unsigned NOT NULL DEFAULT '0',
  `occupied` int unsigned NOT NULL DEFAULT '0',
  `fee_per_day` decimal(10,2) NOT NULL DEFAULT '0.00',
  `daily_revenue` decimal(10,2) NOT NULL DEFAULT '0.00',
  `condition_pct` decimal(5,2) NOT NULL DEFAULT '100.00',
  `status` enum('open','closed','under_construction','full') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'under_construction',
  `build_days_left` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `player_finances`
--

DROP TABLE IF EXISTS `player_finances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_finances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `cash` bigint NOT NULL DEFAULT '500000',
  `resort_open` tinyint NOT NULL DEFAULT '1',
  `units` varchar(10) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'metric',
  `total_income` bigint unsigned NOT NULL DEFAULT '0',
  `total_expenses` bigint unsigned NOT NULL DEFAULT '0',
  `updated_at` datetime DEFAULT NULL,
  `daily_visitors` int unsigned DEFAULT '0',
  `difficulty` enum('easy','standard','hard') COLLATE utf8mb4_general_ci DEFAULT 'standard',
  `allow_tours` tinyint(1) DEFAULT '1',
  `resort_map` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Vail',
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `player_items`
--

DROP TABLE IF EXISTS `player_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `player_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `segment_id` int unsigned NOT NULL,
  `item_type` enum('lift','slope') COLLATE utf8mb4_general_ci NOT NULL,
  `subtype` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `level` int unsigned NOT NULL DEFAULT '1',
  `length_meters` int unsigned NOT NULL DEFAULT '0',
  `condition_pct` int unsigned NOT NULL DEFAULT '100',
  `capacity` int unsigned NOT NULL DEFAULT '0',
  `difficulty` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `snow_quality` enum('powder','groomed','packed','icy','bare') COLLATE utf8mb4_general_ci DEFAULT 'packed',
  `status` enum('open','closed','building','broken') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'open',
  `sector` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `regulation_types`
--

DROP TABLE IF EXISTS `regulation_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regulation_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `reg_key` varchar(50) NOT NULL,
  `name` varchar(150) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `cost` int unsigned DEFAULT '0',
  `penalty` int unsigned DEFAULT '0',
  `benefit` text,
  `tier` int unsigned DEFAULT '1',
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `reg_key` (`reg_key`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `regulations`
--

DROP TABLE IF EXISTS `regulations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regulations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `regulation_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `compliance_cost` int unsigned NOT NULL DEFAULT '0',
  `penalty_risk` int unsigned NOT NULL DEFAULT '0',
  `compliant` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resort_likes`
--

DROP TABLE IF EXISTS `resort_likes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resort_likes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `resort_user_id` int unsigned NOT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id_resort_user_id` (`user_id`,`resort_user_id`),
  KEY `resort_user_id` (`resort_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resort_reports`
--

DROP TABLE IF EXISTS `resort_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resort_reports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `game_day` int unsigned NOT NULL,
  `status` enum('pending','ready') NOT NULL DEFAULT 'pending',
  `report_data` json DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resort_sectors`
--

DROP TABLE IF EXISTS `resort_sectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resort_sectors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `resort_map` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `min_lon` decimal(10,6) DEFAULT NULL,
  `max_lon` decimal(10,6) DEFAULT NULL,
  `min_lat` decimal(10,6) DEFAULT NULL,
  `max_lat` decimal(10,6) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#3b82f6',
  `boundary_points` json DEFAULT NULL,
  `visible` tinyint NOT NULL DEFAULT '0',
  `released` tinyint NOT NULL DEFAULT '0',
  `sort_order` int unsigned DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `resource_types`
--

DROP TABLE IF EXISTS `resource_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `resource_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `resource_key` varchar(50) NOT NULL,
  `category` enum('energy','water') NOT NULL,
  `label` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `cost` int unsigned DEFAULT '0',
  `build_days` int unsigned DEFAULT '1',
  `capacity` int unsigned DEFAULT '0',
  `output` int unsigned DEFAULT '0',
  `upkeep` int unsigned DEFAULT '0',
  `decay_rate` decimal(4,2) DEFAULT '0.10',
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `resource_key` (`resource_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `scenic_lifts`
--

DROP TABLE IF EXISTS `scenic_lifts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scenic_lifts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `item_id` int unsigned NOT NULL,
  `revenue_per_day` int unsigned NOT NULL DEFAULT '1500',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_item_id` (`user_id`,`item_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `seasons`
--

DROP TABLE IF EXISTS `seasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `seasons` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `season_number` int unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `resort_map` varchar(50) NOT NULL,
  `start_date` date NOT NULL,
  `duration_days` int unsigned NOT NULL DEFAULT '135',
  `winter_days` int unsigned NOT NULL DEFAULT '100',
  `active` tinyint NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `segment_connections`
--

DROP TABLE IF EXISTS `segment_connections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `segment_connections` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `from_segment_id` int unsigned NOT NULL,
  `from_end` enum('start','end') NOT NULL DEFAULT 'end',
  `to_segment_id` int unsigned NOT NULL,
  `to_end` enum('start','end') NOT NULL DEFAULT 'start',
  `resort_map` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_connection` (`from_segment_id`,`to_segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `class` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `value` text COLLATE utf8mb4_general_ci,
  `type` varchar(31) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'string',
  `context` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `snow_cannons`
--

DROP TABLE IF EXISTS `snow_cannons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `snow_cannons` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `cannon_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `level` int unsigned NOT NULL DEFAULT '1',
  `output_per_day` int unsigned NOT NULL DEFAULT '3',
  `energy_cost` int unsigned NOT NULL DEFAULT '500',
  `water_usage` int unsigned NOT NULL DEFAULT '100',
  `status` enum('active','off','broken') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'off',
  `condition_pct` int unsigned NOT NULL DEFAULT '100',
  `assigned_slope` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `special_events`
--

DROP TABLE IF EXISTS `special_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `special_events` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `event_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `effect_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `effect_value` int NOT NULL DEFAULT '0',
  `game_day` int unsigned NOT NULL,
  `duration_days` int unsigned NOT NULL DEFAULT '1',
  `active` tinyint NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `level` int unsigned NOT NULL DEFAULT '1',
  `salary` int unsigned NOT NULL DEFAULT '0',
  `morale` int unsigned NOT NULL DEFAULT '100',
  `experience` int unsigned NOT NULL DEFAULT '0',
  `assigned_to` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('active','resting','training','fired') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `staff_roles`
--

DROP TABLE IF EXISTS `staff_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_roles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `role_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `color` varchar(50) NOT NULL,
  `salary` int unsigned NOT NULL,
  `description` text,
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_key` (`role_key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `summer_activity_types`
--

DROP TABLE IF EXISTS `summer_activity_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `summer_activity_types` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `activity_key` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `icon` varchar(100) NOT NULL,
  `cost` int unsigned DEFAULT '0',
  `revenue` int unsigned DEFAULT '0',
  `upkeep` int unsigned DEFAULT '0',
  `capacity` int unsigned DEFAULT '0',
  `description` text,
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `activity_key` (`activity_key`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `terrain_parks`
--

DROP TABLE IF EXISTS `terrain_parks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `terrain_parks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `park_type` enum('halfpipe','jump_line','rail_garden','slopestyle') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'rail_garden',
  `size` enum('small','medium','large') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'small',
  `condition_pct` decimal(5,2) NOT NULL DEFAULT '100.00',
  `popularity` int unsigned NOT NULL DEFAULT '0',
  `daily_visitors` int unsigned NOT NULL DEFAULT '0',
  `status` enum('open','closed','under_construction','maintenance') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'under_construction',
  `build_days_left` int unsigned NOT NULL DEFAULT '0',
  `slope_id` int unsigned DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ticket_sales`
--

DROP TABLE IF EXISTS `ticket_sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_sales` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `game_day` int unsigned NOT NULL,
  `ticket_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int unsigned NOT NULL DEFAULT '0',
  `revenue` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id_game_day` (`user_id`,`game_day`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tournaments`
--

DROP TABLE IF EXISTS `tournaments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tournaments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `start_day` int unsigned NOT NULL,
  `end_day` int unsigned NOT NULL,
  `prize_pool` int unsigned NOT NULL,
  `metric` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('upcoming','active','ended') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'upcoming',
  `host_id` int unsigned DEFAULT '0',
  `visitors_boost` int unsigned DEFAULT '0',
  `reputation_boost` int unsigned DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tutorial_progress`
--

DROP TABLE IF EXISTS `tutorial_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tutorial_progress` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `current_step` int unsigned NOT NULL DEFAULT '0',
  `completed` tinyint NOT NULL DEFAULT '0',
  `skipped` tinyint NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `update_items`
--

DROP TABLE IF EXISTS `update_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `update_items` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `update_id` int unsigned NOT NULL,
  `category` varchar(100) DEFAULT 'New Features',
  `type` enum('new','improved','fixed','removed') DEFAULT 'new',
  `text` text NOT NULL,
  `sort_order` int unsigned DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `update_id` (`update_id`),
  CONSTRAINT `update_items_ibfk_1` FOREIGN KEY (`update_id`) REFERENCES `updates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `updates`
--

DROP TABLE IF EXISTS `updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `updates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `type` enum('major','minor','patch','hotfix') DEFAULT 'minor',
  `released_at` date NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status_message` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `last_active` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `vip_guests`
--

DROP TABLE IF EXISTS `vip_guests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vip_guests` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `vip_type` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `requirements` text,
  `reward_type` varchar(50) NOT NULL DEFAULT 'cash',
  `reward_amount` int unsigned NOT NULL DEFAULT '0',
  `reputation_bonus` int NOT NULL DEFAULT '0',
  `status` enum('arriving','visiting','satisfied','disappointed','departed') NOT NULL DEFAULT 'arriving',
  `days_remaining` int unsigned NOT NULL DEFAULT '1',
  `game_day_arrived` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `water_management`
--

DROP TABLE IF EXISTS `water_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `water_management` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int unsigned NOT NULL,
  `source_type` enum('reservoir','well','river_pump','recycling_plant') NOT NULL DEFAULT 'reservoir',
  `name` varchar(100) NOT NULL,
  `capacity_liters` int unsigned NOT NULL DEFAULT '0',
  `output_liters` int unsigned NOT NULL DEFAULT '0',
  `cost_per_day` int unsigned NOT NULL DEFAULT '0',
  `condition_pct` decimal(5,2) NOT NULL DEFAULT '100.00',
  `status` enum('active','off','broken','under_construction') NOT NULL DEFAULT 'under_construction',
  `build_days_left` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `weather`
--

DROP TABLE IF EXISTS `weather`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `weather` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `game_day` int unsigned NOT NULL DEFAULT '1',
  `temp` int NOT NULL DEFAULT '0',
  `condition_name` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `wind` int unsigned NOT NULL DEFAULT '0',
  `snowfall` int unsigned NOT NULL DEFAULT '0',
  `visibility` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `humidity` int unsigned NOT NULL DEFAULT '50',
  `snow_base` int unsigned NOT NULL DEFAULT '0',
  `forecast` json DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `game_day` (`game_day`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-06  2:54:02
