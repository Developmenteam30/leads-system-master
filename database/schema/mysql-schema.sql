/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `ani`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ani` (
  `phone` varchar(100) NOT NULL,
  PRIMARY KEY (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auditlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditlog` (
  `logId` int NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ipaddress` varchar(255) NOT NULL,
  `userId` smallint DEFAULT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `notes` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  PRIMARY KEY (`logId`),
  KEY `auditlog_FK` (`agent_id`),
  CONSTRAINT `auditlog_FK` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `idCompany` int unsigned NOT NULL AUTO_INCREMENT,
  `name` text,
  `url` varchar(255) DEFAULT NULL,
  `note` varchar(1000) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zipcode` varchar(10) DEFAULT NULL,
  `country` int unsigned NOT NULL DEFAULT '236',
  `main_name` varchar(255) DEFAULT NULL,
  `main_phone` varchar(30) DEFAULT NULL,
  `main_email` varchar(255) DEFAULT NULL,
  `acct_name` varchar(255) DEFAULT NULL,
  `acct_phone` varchar(30) DEFAULT NULL,
  `acct_email` varchar(255) DEFAULT NULL,
  `tech_name` varchar(255) DEFAULT NULL,
  `tech_phone` varchar(30) DEFAULT NULL,
  `tech_email` varchar(255) DEFAULT NULL,
  `returns_name` varchar(255) DEFAULT NULL,
  `returns_phone` varchar(255) DEFAULT NULL,
  `returns_email` varchar(255) DEFAULT NULL,
  `accountManager` smallint unsigned DEFAULT NULL,
  `status` enum('active','hidden','retired') NOT NULL DEFAULT 'active',
  `isPublisher` tinyint unsigned NOT NULL DEFAULT '0',
  `isAdvertiser` tinyint unsigned NOT NULL DEFAULT '0',
  `isCallCenter` tinyint unsigned DEFAULT '0',
  `accountOpener` smallint unsigned DEFAULT NULL,
  `salesperson` smallint unsigned DEFAULT NULL,
  `paymentTerms` varchar(255) DEFAULT NULL,
  `costPerLead` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `dialer_report_type` varchar(255) DEFAULT NULL,
  `dialer_product_id` bigint unsigned DEFAULT NULL,
  `dialer_payment_type_id` bigint unsigned DEFAULT NULL,
  `dialer_billable_rate` decimal(6,2) DEFAULT NULL,
  `dialer_payable_rate` decimal(6,2) DEFAULT NULL,
  `dialer_bonus_rate` decimal(6,2) DEFAULT NULL,
  `dialer_integer` smallint unsigned DEFAULT NULL,
  PRIMARY KEY (`idCompany`),
  KEY `companies_dialer_products_FK` (`dialer_product_id`),
  KEY `companies_dialer_payment_types_FK` (`dialer_payment_type_id`),
  CONSTRAINT `companies_dialer_payment_types_FK` FOREIGN KEY (`dialer_payment_type_id`) REFERENCES `dialer_payment_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `companies_dialer_products_FK` FOREIGN KEY (`dialer_product_id`) REFERENCES `dialer_products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies_divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies_divisions` (
  `companyId` int unsigned NOT NULL,
  `divisionId` int unsigned NOT NULL,
  PRIMARY KEY (`companyId`,`divisionId`),
  KEY `fk_compdiv_divisionId_idx` (`divisionId`),
  CONSTRAINT `fk_compdiv_companyId` FOREIGN KEY (`companyId`) REFERENCES `companies` (`idCompany`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_compdiv_divisionId` FOREIGN KEY (`divisionId`) REFERENCES `divisions` (`divisionId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies_notes` (
  `noteId` int unsigned NOT NULL AUTO_INCREMENT,
  `companyId` int unsigned NOT NULL,
  `userId` smallint unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `note` text,
  PRIMARY KEY (`noteId`),
  KEY `fk_compnotes_userId_idx` (`userId`),
  KEY `idx_compnotes_compId` (`companyId`),
  CONSTRAINT `fk_compnotes_compId` FOREIGN KEY (`companyId`) REFERENCES `companies` (`idCompany`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_compnotes_userId` FOREIGN KEY (`userId`) REFERENCES `users` (`idUser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies_verticals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies_verticals` (
  `companyId` int unsigned NOT NULL,
  `verticalId` int unsigned NOT NULL,
  PRIMARY KEY (`companyId`,`verticalId`),
  KEY `fk_compvert_verticalId_idx` (`verticalId`),
  CONSTRAINT `fk_compvert_companyId` FOREIGN KEY (`companyId`) REFERENCES `companies` (`idCompany`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_compvert_verticalId` FOREIGN KEY (`verticalId`) REFERENCES `verticals` (`verticalId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuration` (
  `config_key` varchar(255) NOT NULL,
  `config_value` tinytext,
  PRIMARY KEY (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `short_name` varchar(255) NOT NULL,
  `alpha2_code` varchar(2) NOT NULL,
  `alpha3_code` varchar(3) NOT NULL,
  `numeric_code` smallint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `credentials` (
  `credentialId` int unsigned NOT NULL AUTO_INCREMENT,
  `companyId` int unsigned NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `notes` text,
  `employeeName` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`credentialId`),
  KEY `credentials_companies_FK` (`companyId`),
  CONSTRAINT `credentials_companies_FK` FOREIGN KEY (`companyId`) REFERENCES `companies` (`idCompany`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_inbound`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_inbound` (
  `idRecord` bigint NOT NULL AUTO_INCREMENT,
  `idFeedIn` int NOT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `listcode` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `leadstamp` datetime DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `fname` varchar(50) DEFAULT NULL,
  `lname` varchar(50) DEFAULT NULL,
  `addr` varchar(150) DEFAULT NULL,
  `addr2` varchar(150) DEFAULT NULL,
  `city` varchar(75) DEFAULT NULL,
  `state` varchar(25) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `landline` varchar(20) DEFAULT NULL,
  `cellphone` varchar(20) DEFAULT NULL,
  `country` varchar(75) DEFAULT NULL,
  `jobId` int DEFAULT NULL,
  `result` varchar(255) DEFAULT NULL,
  `leadId` varchar(255) DEFAULT NULL,
  `custom1` varchar(255) DEFAULT NULL,
  `custom2` varchar(255) DEFAULT NULL,
  `custom3` varchar(255) DEFAULT NULL,
  `custom4` varchar(255) DEFAULT NULL,
  `custom5` varchar(255) DEFAULT NULL,
  `custom6` varchar(255) DEFAULT NULL,
  `customFields` json DEFAULT NULL,
  `ping` tinyint unsigned DEFAULT '0',
  `rawData` json DEFAULT NULL,
  PRIMARY KEY (`idRecord`),
  KEY `cellphone` (`cellphone`),
  KEY `cellphone_timestamp` (`timestamp`,`cellphone`),
  KEY `cellphone_url_date` (`url`,`timestamp`,`cellphone`),
  KEY `email` (`email`),
  KEY `export` (`idFeedIn`,`timestamp`,`result`),
  KEY `idFeedIn` (`idFeedIn`),
  KEY `ip` (`ip`),
  KEY `jobId` (`jobId`),
  KEY `landline` (`landline`),
  KEY `landline_timestamp` (`timestamp`,`landline`),
  KEY `phones` (`landline`,`cellphone`),
  KEY `result` (`idFeedIn`,`result`),
  KEY `url` (`url`),
  KEY `urlDate` (`url`,`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `data_outbound`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_outbound` (
  `idRecord` bigint unsigned NOT NULL,
  `idFeedIn` int NOT NULL,
  `idFeedOut` int NOT NULL,
  `timestamp` datetime DEFAULT NULL,
  `result` text,
  `idRecordLegacy` bigint unsigned DEFAULT NULL,
  `processed` tinyint NOT NULL DEFAULT '0',
  `isBillable` tinyint unsigned NOT NULL DEFAULT '1',
  `url` varchar(255) DEFAULT NULL,
  `accepted` tinyint unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `recordId` (`idRecord`,`idFeedOut`),
  UNIQUE KEY `idRecordLegacy` (`idRecordLegacy`,`idFeedOut`),
  KEY `accepted_records` (`idFeedOut`,`processed`,`accepted`),
  KEY `feed_processed` (`idFeedOut`,`processed`),
  KEY `idFeedOut` (`idFeedOut`),
  KEY `idFeedOut_processed_result_timestamp` (`idFeedOut`,`processed`,`result`(255),`timestamp`),
  KEY `result` (`idFeedOut`,`result`(255),`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_access_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_access_areas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialer_access_areas_UN` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_access_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_access_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `abbreviation` char(5) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialer_access_roles_UN_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_access_roles_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_access_roles_areas` (
  `area_id` bigint unsigned NOT NULL,
  `role_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`area_id`,`role_id`),
  KEY `dialer_access_level_areas_FK_1` (`role_id`),
  CONSTRAINT `dialer_access_level_areas_FK` FOREIGN KEY (`area_id`) REFERENCES `dialer_access_areas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `dialer_access_level_areas_FK_1` FOREIGN KEY (`role_id`) REFERENCES `dialer_access_roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_companies` (
  `agent_id` bigint unsigned NOT NULL,
  `company_id` int unsigned NOT NULL,
  KEY `dialer_agents_companies_FK` (`company_id`),
  KEY `dialer_agents_companies_FK_1` (`agent_id`),
  CONSTRAINT `dialer_agents_companies_FK` FOREIGN KEY (`company_id`) REFERENCES `companies` (`idCompany`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agents_companies_FK_1` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_effective_dates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_effective_dates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint unsigned NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `agent_type_id` bigint unsigned DEFAULT NULL,
  `payment_type_id` bigint unsigned DEFAULT NULL,
  `product_id` bigint unsigned DEFAULT NULL,
  `payable_rate` decimal(6,2) DEFAULT NULL,
  `billable_rate` decimal(6,2) DEFAULT NULL,
  `bonus_rate` decimal(6,2) DEFAULT NULL,
  `termination_reason_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dialer_agent_effective_dates_FK` (`product_id`),
  KEY `dialer_agent_effective_dates_FK_1` (`agent_id`),
  KEY `dialer_agent_effective_dates_FK_2` (`agent_type_id`),
  KEY `dialer_agent_effective_dates_FK_3` (`payment_type_id`),
  KEY `dialer_agent_effective_dates_FK_4` (`termination_reason_id`),
  CONSTRAINT `dialer_agent_effective_dates_FK` FOREIGN KEY (`product_id`) REFERENCES `dialer_products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_effective_dates_FK_1` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_effective_dates_FK_2` FOREIGN KEY (`agent_type_id`) REFERENCES `dialer_agent_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_effective_dates_FK_3` FOREIGN KEY (`payment_type_id`) REFERENCES `dialer_payment_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_effective_dates_FK_4` FOREIGN KEY (`termination_reason_id`) REFERENCES `dialer_agent_termination_reasons` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_evaluations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reporter_agent_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `writeup_id` bigint unsigned DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `dialer_agent_evaluations_FK` (`agent_id`),
  KEY `dialer_agent_evaluations_writeups_FK` (`writeup_id`),
  KEY `dialer_agent_evaluations_FK_1` (`reporter_agent_id`),
  CONSTRAINT `dialer_agent_evaluations_FK` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_evaluations_FK_1` FOREIGN KEY (`reporter_agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_evaluations_writeups_FK` FOREIGN KEY (`writeup_id`) REFERENCES `dialer_agent_writeups` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_performances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_performances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_date` date NOT NULL,
  `agent_id` bigint unsigned NOT NULL,
  `wait_pct` decimal(5,2) DEFAULT NULL,
  `calls` smallint unsigned DEFAULT NULL,
  `contacts` smallint unsigned DEFAULT NULL,
  `talk_pct` decimal(5,2) DEFAULT NULL,
  `pause_pct` decimal(5,2) DEFAULT NULL,
  `wrapup_pct` decimal(5,2) DEFAULT NULL,
  `talk_time` mediumint unsigned DEFAULT NULL,
  `wait_time` mediumint unsigned DEFAULT NULL,
  `pause_time` mediumint unsigned DEFAULT NULL,
  `wrapup_time` mediumint unsigned DEFAULT NULL,
  `total_time` mediumint unsigned DEFAULT NULL,
  `net_time` mediumint unsigned DEFAULT NULL,
  `billable_time` mediumint unsigned DEFAULT NULL,
  `billable_time_override` mediumint unsigned DEFAULT NULL,
  `voicemail` smallint unsigned DEFAULT NULL,
  `others` smallint unsigned DEFAULT NULL,
  `voicemail_pct` decimal(5,2) DEFAULT NULL,
  `others_pct` decimal(5,2) DEFAULT NULL,
  `transfers` smallint unsigned DEFAULT NULL,
  `under_6_min` smallint unsigned DEFAULT NULL,
  `over_7_min` smallint unsigned DEFAULT NULL,
  `over_20_min` smallint unsigned DEFAULT NULL,
  `over_60_min` smallint unsigned DEFAULT NULL,
  `payable_amount` decimal(7,2) DEFAULT NULL,
  `billable_amount` decimal(7,2) DEFAULT NULL,
  `bonus_amount` decimal(7,2) DEFAULT NULL,
  `billable_transfers_90` smallint unsigned DEFAULT NULL,
  `billable_transfers_120` smallint unsigned DEFAULT NULL,
  `payable_training` tinyint unsigned NOT NULL DEFAULT '0',
  `billable_training` tinyint unsigned NOT NULL DEFAULT '0',
  `payable_rate` decimal(7,2) DEFAULT NULL,
  `billable_rate` decimal(7,2) DEFAULT NULL,
  `bonus_rate` decimal(7,2) DEFAULT NULL,
  `failed_transfers` smallint unsigned DEFAULT NULL,
  `billable_transfers` smallint unsigned DEFAULT NULL,
  `successful_transfers_bill_time` mediumint unsigned DEFAULT NULL,
  `under_5_min` smallint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialer_agent_performance_UN` (`file_date`,`agent_id`),
  KEY `dialer_agent_performance_FK` (`agent_id`),
  CONSTRAINT `dialer_agent_performance_FK` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_termination_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_termination_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialer_agent_termination_reasons_UN` (`reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_writeup_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_writeup_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_writeup_reasons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_writeup_reasons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialer_agent_writeup_reasons_UN` (`reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agent_writeups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agent_writeups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint unsigned NOT NULL,
  `date` date DEFAULT NULL,
  `reason_id` bigint unsigned DEFAULT NULL,
  `notes` text,
  `reporter_agent_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `writeup_level_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dialer_agent_writeups_agents_FK` (`agent_id`),
  KEY `dialer_agent_writeups_reasons_FK` (`reason_id`),
  KEY `dialer_agent_writeups_FK` (`reporter_agent_id`),
  KEY `dialer_agent_writeups_FK_1` (`writeup_level_id`),
  CONSTRAINT `dialer_agent_writeups_agents_FK` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_writeups_FK` FOREIGN KEY (`reporter_agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_writeups_FK_1` FOREIGN KEY (`writeup_level_id`) REFERENCES `dialer_agent_writeup_levels` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agent_writeups_reasons_FK` FOREIGN KEY (`reason_id`) REFERENCES `dialer_agent_writeup_reasons` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_agents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `company_id` int unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `team_id` bigint unsigned DEFAULT NULL,
  `user_id` smallint unsigned DEFAULT NULL,
  `access_role_id` bigint unsigned DEFAULT NULL,
  `username` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dialer_agents_FK` (`company_id`),
  KEY `dialer_agents_team_FK` (`team_id`),
  KEY `dialer_agents_FK_1` (`user_id`),
  KEY `dialer_agents_FK_2` (`access_role_id`),
  CONSTRAINT `dialer_agents_FK` FOREIGN KEY (`company_id`) REFERENCES `companies` (`idCompany`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agents_FK_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`idUser`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agents_FK_2` FOREIGN KEY (`access_role_id`) REFERENCES `dialer_access_roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_agents_team_FK` FOREIGN KEY (`team_id`) REFERENCES `dialer_teams` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_billable_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_billable_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_date` date NOT NULL,
  `company_id` int unsigned NOT NULL,
  `billable_transfers_90` smallint unsigned DEFAULT NULL,
  `billable_transfers_120` smallint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dialer_billable_transfers_FK` (`company_id`),
  CONSTRAINT `dialer_billable_transfers_FK` FOREIGN KEY (`company_id`) REFERENCES `companies` (`idCompany`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_campaign_defaults`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_campaign_defaults` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint unsigned NOT NULL,
  `company_id` int unsigned NOT NULL,
  `billable_rate` decimal(6,2) DEFAULT NULL,
  `payable_rate` decimal(6,2) DEFAULT NULL,
  `bonus_rate` decimal(6,2) DEFAULT NULL,
  `payment_type_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialer_campaign_defaults_UN` (`campaign_id`,`company_id`),
  KEY `dialer_campaign_defaults_FK` (`payment_type_id`),
  KEY `dialer_campaign_defaults_FK_2` (`company_id`),
  CONSTRAINT `dialer_campaign_defaults_FK` FOREIGN KEY (`payment_type_id`) REFERENCES `dialer_payment_types` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_campaign_defaults_FK_1` FOREIGN KEY (`campaign_id`) REFERENCES `dialer_products` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_campaign_defaults_FK_2` FOREIGN KEY (`company_id`) REFERENCES `companies` (`idCompany`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_disposition_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_disposition_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `agent_id` bigint unsigned NOT NULL,
  `file_date` date NOT NULL,
  `status_id` bigint unsigned NOT NULL,
  `total` int unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialer_disposition_logs_UN` (`agent_id`,`file_date`,`status_id`),
  KEY `dialer_disposition_logs_FK_1` (`status_id`),
  KEY `dialer_disposition_logs_file_date_IDX` (`file_date`) USING BTREE,
  CONSTRAINT `dialer_disposition_logs_FK` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_disposition_logs_FK_1` FOREIGN KEY (`status_id`) REFERENCES `dialer_statuses` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_logs` (
  `call_id` int unsigned NOT NULL,
  `lead_id` int unsigned DEFAULT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `number_dialed` varchar(255) DEFAULT NULL,
  `caller_id` varchar(255) DEFAULT NULL,
  `status_name` varchar(255) DEFAULT NULL,
  `time_stamp` datetime DEFAULT NULL,
  `agent_name` varchar(255) DEFAULT NULL,
  `talk_time` int unsigned DEFAULT NULL,
  `bill_time` time DEFAULT NULL,
  `queue_wait_time` decimal(8,2) DEFAULT NULL,
  `cost` decimal(8,4) DEFAULT NULL,
  `call_type` varchar(255) DEFAULT NULL,
  `termination_reason` varchar(255) DEFAULT NULL,
  `outbound_called_count` varchar(255) DEFAULT NULL,
  `campaign_name` varchar(255) DEFAULT NULL,
  `list_name` varchar(255) DEFAULT NULL,
  `queue_name` varchar(255) DEFAULT NULL,
  `recordings` text CHARACTER SET latin1 COLLATE latin1_swedish_ci,
  `gmt_offset_now` varchar(255) DEFAULT NULL,
  `address_1` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `audio_quality` varchar(255) DEFAULT NULL,
  `revenue` varchar(255) DEFAULT NULL,
  `returned` varchar(255) DEFAULT NULL,
  `agent_email` varchar(255) DEFAULT NULL,
  `agent_extension` varchar(255) DEFAULT NULL,
  `agent_first_name` varchar(255) DEFAULT NULL,
  `agent_id` bigint unsigned DEFAULT NULL,
  `agent_last_name` varchar(255) DEFAULT NULL,
  `called_since_last_reset` varchar(255) DEFAULT NULL,
  `wrapup_time` int unsigned DEFAULT NULL,
  `date_and_hour` datetime DEFAULT NULL,
  `day_of_month` tinyint unsigned DEFAULT NULL,
  `day_of_week` tinyint unsigned DEFAULT NULL,
  `end_epoch` timestamp NULL DEFAULT NULL,
  `hour` tinyint unsigned DEFAULT NULL,
  `month` tinyint unsigned DEFAULT NULL,
  `start_epoch` timestamp NULL DEFAULT NULL,
  `time` time DEFAULT NULL,
  `year` int unsigned DEFAULT NULL,
  `alternate_number_to_dial` varchar(255) DEFAULT NULL,
  `campaign_id` varchar(255) DEFAULT NULL,
  `campaign_type` varchar(255) DEFAULT NULL,
  `queue_id` int unsigned DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `originating_agent_id` int unsigned DEFAULT NULL,
  `originating_agent_name` varchar(255) DEFAULT NULL,
  `beneficiary_last_name` varchar(255) DEFAULT NULL,
  `beneficiary_relationship` varchar(255) DEFAULT NULL,
  `carrier_name` varchar(255) DEFAULT NULL,
  `carrier_type` varchar(255) DEFAULT NULL,
  `cell_phone` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `created_by` int unsigned DEFAULT NULL,
  `current_life_ins` varchar(255) DEFAULT NULL,
  `currently_employed` varchar(255) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email_consent` varchar(255) DEFAULT NULL,
  `final_reached_at` datetime DEFAULT NULL,
  `fl_call_counter` tinyint unsigned DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `have_children` varchar(255) DEFAULT NULL,
  `height` varchar(255) DEFAULT NULL,
  `ib_call_campaign` varchar(255) DEFAULT NULL,
  `ib_call_source` varchar(255) DEFAULT NULL,
  `illnesses` varchar(255) DEFAULT NULL,
  `insurance_purpose` varchar(255) DEFAULT NULL,
  `integriant_lead_id` varchar(255) DEFAULT NULL,
  `last_called` datetime DEFAULT NULL,
  `last_reached_at` datetime DEFAULT NULL,
  `last_viewed` datetime DEFAULT NULL,
  `last_modified_by` int unsigned DEFAULT NULL,
  `lead_owner` varchar(255) DEFAULT NULL,
  `leadspedia_campaign_name` varchar(255) DEFAULT NULL,
  `leadspedia_lead_id` varchar(255) DEFAULT NULL,
  `list` varchar(255) DEFAULT NULL,
  `major_illnesses` varchar(255) DEFAULT NULL,
  `marital_status` varchar(255) DEFAULT NULL,
  `medicaid_low_income` varchar(255) DEFAULT NULL,
  `medicare_a_and_b` varchar(255) DEFAULT NULL,
  `nicotine_frequency` varchar(255) DEFAULT NULL,
  `notes` text,
  `original_lead_gen_date` datetime DEFAULT NULL,
  `payment_type` varchar(255) DEFAULT NULL,
  `primary_phone` varchar(255) DEFAULT NULL,
  `product` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `security_phrase` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `last_modify` datetime DEFAULT NULL,
  `tobacco_use` varchar(255) DEFAULT NULL,
  `trackdrive_status` varchar(255) DEFAULT NULL,
  `vendor_code` varchar(255) DEFAULT NULL,
  `vertical` varchar(255) DEFAULT NULL,
  `weight` varchar(255) DEFAULT NULL,
  `work_phone` varchar(255) DEFAULT NULL,
  `zoho_contact_id` bigint unsigned DEFAULT NULL,
  `comment` text,
  `jornaya_lead_id` varchar(255) DEFAULT NULL,
  `last_dnc_check_date` date DEFAULT NULL,
  `send_life_data_to_retreaver` varchar(255) DEFAULT NULL,
  `send_medicare_data_to_retreaver` varchar(255) DEFAULT NULL,
  `sub_product` varchar(255) DEFAULT NULL,
  `tcpa_date` date DEFAULT NULL,
  `trusted_form_cert_id` varchar(255) DEFAULT NULL,
  `mini_tcpa_blocked` varchar(255) DEFAULT NULL,
  `mini_tcpa_block_expires` datetime DEFAULT NULL,
  PRIMARY KEY (`call_id`),
  KEY `dialer_logs_agent_id_IDX` (`agent_id`) USING BTREE,
  KEY `dialer_logs_time_stamp_IDX` (`time_stamp`) USING BTREE,
  KEY `dialer_logs_performance_IDX` (`agent_id`,`status`,`time_stamp`) USING BTREE,
  KEY `dialer_logs_lead_id_IDX` (`lead_id`) USING BTREE,
  KEY `dialer_logs_time_stamp_status_IDX` (`time_stamp`,`status`) USING BTREE,
  CONSTRAINT `dialer_logs_FK` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_payment_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_payment_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_statuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_statuses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
  `status_name` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dialer_statuses_UN` (`status`),
  KEY `dialer_statuses_status_IDX` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_team_leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_team_leads` (
  `team_id` bigint unsigned NOT NULL,
  `agent_id` bigint unsigned NOT NULL,
  KEY `dialer_team_leads_FK` (`team_id`),
  KEY `dialer_team_leads_FK_1` (`agent_id`),
  CONSTRAINT `dialer_team_leads_FK` FOREIGN KEY (`team_id`) REFERENCES `dialer_teams` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `dialer_team_leads_FK_1` FOREIGN KEY (`agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dialer_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dialer_teams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `is_archived` tinyint unsigned NOT NULL DEFAULT '0',
  `manager_agent_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dialer_teams_manager_FK` (`manager_agent_id`),
  CONSTRAINT `dialer_teams_manager_FK` FOREIGN KEY (`manager_agent_id`) REFERENCES `dialer_agents` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `divisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `divisions` (
  `divisionId` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`divisionId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `errorlog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `errorlog` (
  `idError` int NOT NULL AUTO_INCREMENT,
  `origination` varchar(45) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `stamp` datetime DEFAULT NULL,
  PRIMARY KEY (`idError`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedPopulation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedPopulation` (
  `idAssoc` bigint NOT NULL AUTO_INCREMENT,
  `idFeedIn` bigint DEFAULT NULL,
  `idFeedOut` bigint DEFAULT NULL,
  `enabled` enum('0','1') DEFAULT '0',
  `filterTypeUrl` enum('reject','accept') DEFAULT NULL,
  `filterUrl` varchar(5000) DEFAULT NULL,
  `filterTypeEmail` enum('reject','accept') DEFAULT NULL,
  `filterEmail` varchar(5000) DEFAULT NULL,
  `filterTypeListcode` enum('reject','accept') DEFAULT NULL,
  `filterListcode` varchar(1000) DEFAULT NULL,
  `forceUrlList` varchar(5000) DEFAULT NULL,
  `forceUrl` tinyint DEFAULT '0',
  `livedata` tinyint NOT NULL DEFAULT '0',
  `waterfall` tinyint NOT NULL DEFAULT '0',
  `waterfallPriority` smallint unsigned NOT NULL DEFAULT '0',
  `queueType` enum('queue','livedata','waterfall','waterfallLimit','waterfallLimitLive') NOT NULL DEFAULT 'queue',
  `startDate` date DEFAULT NULL,
  `populationType` enum('individual','category') NOT NULL DEFAULT 'individual',
  `feedCategory` varchar(255) DEFAULT NULL,
  `isArchived` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`idAssoc`),
  UNIQUE KEY `feedPopulation_UN` (`idFeedIn`,`idFeedOut`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedinc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedinc` (
  `idFeedIn` int NOT NULL AUTO_INCREMENT,
  `label` varchar(255) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `idCompany` int DEFAULT NULL,
  `required` varchar(1000) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT 'email;ip;url;stamp',
  `allowedFields` varchar(1000) DEFAULT 'listcode;url;ip;stamp;email;fname;lname;addr;addr2;city;state;zip;dob;gender;landline;cellphone',
  `password` varchar(16) DEFAULT NULL,
  `dedupeEmail` enum('0','1') DEFAULT '0',
  `dedupeLandline` enum('0','1') DEFAULT '0',
  `dedupeCellphone` enum('0','1') DEFAULT '0',
  `rejectOldLeads` tinyint DEFAULT '1',
  `rejectOldLeadsMaxAge` varchar(50) DEFAULT '7 Days Ago',
  `dedupeAcross` varchar(32) DEFAULT NULL,
  `filterTypeUrl` enum('reject','accept') DEFAULT NULL,
  `filterUrl` varchar(5000) DEFAULT NULL,
  `filterTypeSiftLogic` enum('reject','accept') DEFAULT NULL,
  `filterSiftLogic` varchar(5000) DEFAULT NULL,
  `notifications` enum('0','1') DEFAULT '1',
  `status` enum('active','hidden','retired') NOT NULL DEFAULT 'active',
  `chokePercent` tinyint unsigned NOT NULL DEFAULT '0',
  `feedCategory` varchar(255) NOT NULL DEFAULT 'email',
  `dailyLimit` mediumint unsigned DEFAULT NULL,
  `custom1Label` varchar(255) DEFAULT NULL,
  `custom2Label` varchar(255) DEFAULT NULL,
  `custom3Label` varchar(255) DEFAULT NULL,
  `custom4Label` varchar(255) DEFAULT NULL,
  `custom5Label` varchar(255) DEFAULT NULL,
  `custom6Label` varchar(255) DEFAULT NULL,
  `costPerLead` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `notifyThresholdCount` int unsigned NOT NULL DEFAULT '0',
  `notifyThresholdDays` varchar(100) DEFAULT NULL,
  `notifyThresholdLastSent` datetime DEFAULT NULL,
  `notifyThresholdTime` time DEFAULT NULL,
  `salesperson` smallint unsigned DEFAULT NULL,
  `paused` tinyint unsigned NOT NULL DEFAULT '0',
  `pauseMessage` varchar(255) DEFAULT NULL,
  `filterTypeDNCScrub` varchar(5000) DEFAULT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'America/New_York',
  `timeskew` varchar(255) DEFAULT NULL,
  `filterState` json DEFAULT NULL,
  `lookbackPeriod` tinyint unsigned NOT NULL DEFAULT '90',
  `pingTimeout` int unsigned NOT NULL DEFAULT '300',
  `requiredPingFields` varchar(1000) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `allowedPingFields` varchar(1000) CHARACTER SET latin1 COLLATE latin1_swedish_ci DEFAULT NULL,
  `minimumBirthAge` tinyint unsigned DEFAULT NULL,
  `maximumBirthAge` tinyint unsigned DEFAULT NULL,
  `filterZip` json DEFAULT NULL,
  PRIMARY KEY (`idFeedIn`),
  KEY `idx_idCompany` (`idCompany`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `feedout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedout` (
  `idFeedOut` int NOT NULL AUTO_INCREMENT,
  `label` varchar(30) DEFAULT NULL,
  `description` varchar(100) DEFAULT NULL,
  `idCompany` int unsigned DEFAULT NULL,
  `feedType` enum('curlPOST','curlGET','JSON','csvString','soapPOST','curlPOST-urlencoded','xmlPOST') DEFAULT NULL,
  `postUrl` varchar(1000) DEFAULT NULL,
  `staticFields` varchar(1000) DEFAULT NULL,
  `varFields` varchar(1000) DEFAULT NULL,
  `fieldMap` text,
  `cron` enum('0','1') DEFAULT '0',
  `cronTiming` int DEFAULT '1',
  `successString` varchar(50) DEFAULT NULL,
  `throttle` int DEFAULT '100',
  `urlassignments` varchar(1000) DEFAULT NULL,
  `dailyLimit` mediumint unsigned DEFAULT NULL,
  `delay` int unsigned DEFAULT NULL,
  `queued` int DEFAULT '0',
  `status` enum('active','hidden','retired') NOT NULL DEFAULT 'active',
  `feedCategory` varchar(255) NOT NULL DEFAULT 'email',
  `delayDump` tinyint unsigned NOT NULL DEFAULT '0',
  `notifyThresholdCount` int unsigned NOT NULL DEFAULT '0',
  `notifyThresholdTime` time DEFAULT NULL,
  `notifyThresholdLastSent` datetime DEFAULT NULL,
  `notifyThresholdDays` varchar(100) DEFAULT NULL,
  `revenuePerLead` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `launchDate` date DEFAULT NULL,
  `costPerLeadOverride` decimal(10,4) unsigned DEFAULT NULL,
  `valueMap` text,
  `salesperson` smallint unsigned DEFAULT NULL,
  `xmlDTD` mediumtext,
  `processingSchedule` mediumtext,
  `staticFieldsJSON` json DEFAULT NULL,
  `varFieldsJSON` json DEFAULT NULL,
  `timezone` varchar(255) NOT NULL DEFAULT 'UTC',
  PRIMARY KEY (`idFeedOut`),
  KEY `feedout_idCompany_IDX` (`idCompany`),
  CONSTRAINT `feedout_companies_FK` FOREIGN KEY (`idCompany`) REFERENCES `companies` (`idCompany`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fields` (
  `fieldId` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `fieldName` varchar(255) NOT NULL,
  `fieldType` enum('system','custom','derived','outbound','outbound-export','inbound-export') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'custom',
  `fieldDescription` varchar(255) DEFAULT NULL,
  `fieldFormat` varchar(255) DEFAULT NULL,
  `fieldDefinition` varchar(255) DEFAULT 'varchar(255)',
  PRIMARY KEY (`fieldId`),
  UNIQUE KEY `fields_UN` (`fieldName`,`fieldType`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forecast_expectations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forecast_expectations` (
  `expectationId` int unsigned NOT NULL AUTO_INCREMENT,
  `userId` smallint unsigned NOT NULL,
  `expectationMonth` date NOT NULL,
  `existingBusinessAmount` int unsigned NOT NULL DEFAULT '0',
  `newBusinessAmount` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`expectationId`),
  UNIQUE KEY `forecast_expectations_UN` (`userId`,`expectationMonth`),
  CONSTRAINT `forecast_expectations_users_FK` FOREIGN KEY (`userId`) REFERENCES `users` (`idUser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `forecast_weights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `forecast_weights` (
  `weightId` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `idFeedOut` int NOT NULL,
  `month` date NOT NULL,
  `weight` decimal(5,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`weightId`),
  UNIQUE KEY `forecast_weights_UN` (`idFeedOut`,`month`),
  CONSTRAINT `forecast_weights_feedout_FK` FOREIGN KEY (`idFeedOut`) REFERENCES `feedout` (`idFeedOut`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `insertion_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `insertion_orders` (
  `orderId` int unsigned NOT NULL AUTO_INCREMENT,
  `orderType` enum('publisher','advertiser') NOT NULL,
  `userId` smallint unsigned DEFAULT NULL,
  `companyId` int unsigned NOT NULL,
  `verticalId` int unsigned NOT NULL,
  `startDate` date DEFAULT NULL,
  `paymentTerms` varchar(100) DEFAULT NULL,
  `callReporting` enum('publisher','advertiser') DEFAULT NULL,
  `costPerLead` varchar(255) DEFAULT NULL,
  `deliveryMethod` varchar(255) DEFAULT NULL,
  `qty` varchar(255) DEFAULT NULL,
  `did` varchar(255) DEFAULT NULL,
  `deliveryDays` varchar(255) DEFAULT NULL,
  `callHours` varchar(255) DEFAULT NULL,
  `notes` text,
  `isArchived` tinyint unsigned NOT NULL DEFAULT '0',
  `endDate` date DEFAULT NULL,
  `costPerLeadUOM` enum('lead','hour','call') DEFAULT NULL,
  `includeBankingInfo` tinyint unsigned NOT NULL DEFAULT '0',
  `includeW9` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`orderId`),
  KEY `insertion_orders_companies_FK` (`companyId`),
  KEY `insertion_orders_users_FK` (`userId`),
  KEY `insertion_orders_verticals_FK` (`verticalId`),
  CONSTRAINT `insertion_orders_companies_FK` FOREIGN KEY (`companyId`) REFERENCES `companies` (`idCompany`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `insertion_orders_users_FK` FOREIGN KEY (`userId`) REFERENCES `users` (`idUser`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `insertion_orders_verticals_FK` FOREIGN KEY (`verticalId`) REFERENCES `verticals` (`verticalId`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `date` char(6) NOT NULL,
  `idCompany` int NOT NULL DEFAULT '0',
  `invoiceNumber` varchar(255) DEFAULT NULL,
  `paymentDate` date DEFAULT NULL,
  `userId` smallint unsigned DEFAULT NULL,
  PRIMARY KEY (`date`,`idCompany`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `jobId` int NOT NULL AUTO_INCREMENT,
  `type` varchar(255) NOT NULL,
  `idUser` int NOT NULL DEFAULT '0',
  `message` varchar(255) DEFAULT NULL,
  `status` varchar(45) NOT NULL DEFAULT 'pending',
  `timestamp` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `destination` int NOT NULL,
  `fields` text NOT NULL,
  `filename` varchar(255) NOT NULL,
  `records` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`jobId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger` (
  `ledgerId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `divisionId` int NOT NULL,
  `companyId` int NOT NULL,
  `verticalId` int DEFAULT NULL,
  `paymentDate` date DEFAULT NULL,
  `paymentMethod` varchar(255) DEFAULT NULL,
  `ledgerMonth` date NOT NULL,
  `invoiceAmount` decimal(8,2) unsigned NOT NULL DEFAULT '0.00',
  `invoiceNum` varchar(255) DEFAULT NULL,
  `paymentAmount` decimal(8,2) unsigned DEFAULT NULL,
  `type` tinyint NOT NULL DEFAULT '0',
  `vendorCompanyId` int DEFAULT NULL,
  `commissionAmount1` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate1` date DEFAULT NULL,
  `commissionRevenue1` enum('new','existing') DEFAULT NULL,
  `userId1` smallint unsigned DEFAULT NULL,
  `commissionAmount2` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate2` date DEFAULT NULL,
  `commissionRevenue2` enum('new','existing') DEFAULT NULL,
  `userId2` smallint unsigned DEFAULT NULL,
  `commissionAmount3` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate3` date DEFAULT NULL,
  `commissionRevenue3` enum('new','existing') DEFAULT NULL,
  `userId3` smallint unsigned DEFAULT NULL,
  `billingCycleStart` date DEFAULT NULL,
  `billingCycleEnd` date DEFAULT NULL,
  `isReimbursed` tinyint unsigned NOT NULL DEFAULT '0',
  `reimbursementPaymentDate` date DEFAULT NULL,
  `dueDate` date DEFAULT NULL,
  `reimbursementAmount` decimal(8,2) unsigned DEFAULT NULL,
  PRIMARY KEY (`ledgerId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_offline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_offline` (
  `ledgerId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `clientCompanyId` int NOT NULL,
  `mailerName` varchar(255) DEFAULT NULL,
  `listName` varchar(255) DEFAULT NULL,
  `clientPoNum` varchar(255) DEFAULT NULL,
  `orderType` char(1) DEFAULT NULL,
  `orderDate` date DEFAULT NULL,
  `mailDate` date DEFAULT NULL,
  `qty` int DEFAULT NULL,
  `invoiceNum` varchar(255) DEFAULT NULL,
  `invoiceAmount` decimal(8,2) unsigned DEFAULT NULL,
  `ledgerMonth` date NOT NULL,
  `paymentDate` date DEFAULT NULL,
  `paymentMethod` varchar(255) DEFAULT NULL,
  `paymentAmount` decimal(8,2) unsigned DEFAULT NULL,
  `vendorCompanyId` int DEFAULT NULL,
  `ourPoNum` varchar(255) DEFAULT NULL,
  `loInvoiceNum` varchar(255) DEFAULT NULL,
  `loInvoiceAmount` decimal(8,2) unsigned DEFAULT NULL,
  `loPaymentDate` date DEFAULT NULL,
  `loPaymentMethod` varchar(255) DEFAULT NULL,
  `loPaymentAmount` decimal(8,2) unsigned DEFAULT NULL,
  `commissionAmount1` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate1` date DEFAULT NULL,
  `commissionRevenue1` enum('new','existing') DEFAULT NULL,
  `userId1` smallint unsigned DEFAULT NULL,
  `commissionAmount2` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate2` date DEFAULT NULL,
  `commissionRevenue2` enum('new','existing') DEFAULT NULL,
  `userId2` smallint unsigned DEFAULT NULL,
  `commissionAmount3` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate3` date DEFAULT NULL,
  `commissionRevenue3` enum('new','existing') DEFAULT NULL,
  `userId3` smallint unsigned DEFAULT NULL,
  `billingCycleStart` date DEFAULT NULL,
  `billingCycleEnd` date DEFAULT NULL,
  `dueDate` date DEFAULT NULL,
  `reimbursementAmount` decimal(8,2) unsigned DEFAULT NULL,
  `reimbursementPaymentDate` date DEFAULT NULL,
  PRIMARY KEY (`ledgerId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_phones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_phones` (
  `ledgerId` bigint unsigned NOT NULL AUTO_INCREMENT,
  `clientCompanyId` int unsigned NOT NULL,
  `verticalId` int unsigned DEFAULT NULL,
  `listName` varchar(255) DEFAULT NULL,
  `orderDate` date DEFAULT NULL,
  `qty` int unsigned DEFAULT NULL,
  `invoiceNum` varchar(255) DEFAULT NULL,
  `invoiceAmount` decimal(8,2) unsigned DEFAULT NULL,
  `ledgerMonth` date NOT NULL,
  `paymentDate` date DEFAULT NULL,
  `paymentMethod` varchar(255) DEFAULT NULL,
  `paymentAmount` decimal(8,2) unsigned DEFAULT NULL,
  `commissionAmount1` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate1` date DEFAULT NULL,
  `commissionRevenue1` enum('new','existing') DEFAULT NULL,
  `userId1` smallint unsigned DEFAULT NULL,
  `commissionAmount2` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate2` date DEFAULT NULL,
  `commissionRevenue2` enum('new','existing') DEFAULT NULL,
  `userId2` smallint unsigned DEFAULT NULL,
  `commissionAmount3` decimal(8,2) unsigned DEFAULT NULL,
  `commissionDate3` date DEFAULT NULL,
  `commissionRevenue3` enum('new','existing') DEFAULT NULL,
  `userId3` smallint unsigned DEFAULT NULL,
  `billingCycleStart` date DEFAULT NULL,
  `billingCycleEnd` date DEFAULT NULL,
  `dueDate` date DEFAULT NULL,
  `reimbursementAmount` decimal(8,2) unsigned DEFAULT NULL,
  `reimbursementPaymentDate` date DEFAULT NULL,
  PRIMARY KEY (`ledgerId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_phones_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_phones_vendors` (
  `ledgerId` bigint unsigned NOT NULL,
  `indexId` int unsigned NOT NULL DEFAULT '0',
  `vendorCompanyId` int DEFAULT NULL,
  `loInvoiceNum` varchar(255) DEFAULT NULL,
  `loInvoiceAmount` decimal(8,2) unsigned DEFAULT NULL,
  `loPaymentDate` date DEFAULT NULL,
  `loPaymentMethod` varchar(255) DEFAULT NULL,
  `loPaymentAmount` decimal(8,2) unsigned DEFAULT NULL,
  PRIMARY KEY (`ledgerId`,`indexId`),
  CONSTRAINT `fk_phones_vendors_ledgerId` FOREIGN KEY (`ledgerId`) REFERENCES `ledger_phones` (`ledgerId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ledger_vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ledger_vendors` (
  `ledgerId` bigint unsigned NOT NULL,
  `indexId` int unsigned NOT NULL DEFAULT '0',
  `vendorCompanyId` int DEFAULT NULL,
  `loInvoiceNum` varchar(255) DEFAULT NULL,
  `loInvoiceAmount` decimal(8,2) unsigned DEFAULT NULL,
  `loPaymentDate` date DEFAULT NULL,
  `loPaymentMethod` varchar(255) DEFAULT NULL,
  `loPaymentAmount` decimal(8,2) unsigned DEFAULT NULL,
  PRIMARY KEY (`ledgerId`,`indexId`),
  CONSTRAINT `fk_ledger_vendors_ledgerId` FOREIGN KEY (`ledgerId`) REFERENCES `ledger` (`ledgerId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listcode`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listcode` (
  `idListcode` smallint unsigned NOT NULL AUTO_INCREMENT,
  `idCompany` int DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idListcode`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `listcode_url`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `listcode_url` (
  `idUrl` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `idListcode` smallint unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  PRIMARY KEY (`idUrl`),
  UNIQUE KEY `listcode_url` (`idListcode`,`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `idFeedIn` int NOT NULL,
  `url` varchar(500) NOT NULL,
  `lastTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `notifyTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`idFeedIn`,`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications_companies` (
  `idCompany` int unsigned NOT NULL,
  `lastNotification` timestamp NOT NULL,
  PRIMARY KEY (`idCompany`),
  CONSTRAINT `notifications_companies_companies_FK` FOREIGN KEY (`idCompany`) REFERENCES `companies` (`idCompany`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opportunities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunities` (
  `opportunityId` int unsigned NOT NULL AUTO_INCREMENT,
  `companyId` int unsigned NOT NULL,
  `divisionId` int unsigned DEFAULT NULL,
  `userId` smallint unsigned NOT NULL,
  `affiliate` varchar(255) DEFAULT NULL,
  `products` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `expense` float(8,2) unsigned NOT NULL DEFAULT '0.00',
  `revenue` float(8,2) unsigned NOT NULL DEFAULT '0.00',
  `startQty` float(8,2) unsigned NOT NULL DEFAULT '0.00',
  `goalQty` float(8,2) unsigned NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`opportunityId`),
  KEY `fk_opp_divisionId_idx` (`divisionId`),
  KEY `fk_opp_userId_idx` (`userId`),
  KEY `idx_opp_companyId` (`companyId`),
  CONSTRAINT `fk_opp_companyId` FOREIGN KEY (`companyId`) REFERENCES `companies` (`idCompany`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opp_divisionId` FOREIGN KEY (`divisionId`) REFERENCES `divisions` (`divisionId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_opp_userId` FOREIGN KEY (`userId`) REFERENCES `users` (`idUser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `opportunities_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `opportunities_notes` (
  `noteId` int unsigned NOT NULL AUTO_INCREMENT,
  `opportunityId` int unsigned NOT NULL,
  `userId` smallint unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `note` text,
  PRIMARY KEY (`noteId`),
  KEY `fk_oppnotes_userId_idx` (`userId`),
  KEY `idx_oppnotes_opId` (`opportunityId`),
  CONSTRAINT `fk_oppnotes_oppId` FOREIGN KEY (`opportunityId`) REFERENCES `opportunities` (`opportunityId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oppnotes_userId` FOREIGN KEY (`userId`) REFERENCES `users` (`idUser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `php_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `php_sessions` (
  `sess_id` varbinary(128) NOT NULL,
  `sess_data` blob NOT NULL,
  `sess_lifetime` mediumint NOT NULL,
  `sess_time` int unsigned NOT NULL,
  PRIMARY KEY (`sess_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospects` (
  `prospectId` int unsigned NOT NULL AUTO_INCREMENT,
  `userId` smallint unsigned NOT NULL,
  `company` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `opportunity` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `divisions` text,
  `percentage` tinyint unsigned NOT NULL DEFAULT '0',
  `isArchived` tinyint unsigned NOT NULL DEFAULT '0',
  `expectedClose` date DEFAULT NULL,
  `verticals` text,
  `isAdvertiser` tinyint unsigned NOT NULL DEFAULT '0',
  `isCallCenter` tinyint unsigned DEFAULT '0',
  `isPublisher` tinyint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`prospectId`),
  KEY `fk_prospect_userId_idx` (`userId`),
  CONSTRAINT `fk_prospect_userId` FOREIGN KEY (`userId`) REFERENCES `users` (`idUser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prospects_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prospects_notes` (
  `noteId` int unsigned NOT NULL AUTO_INCREMENT,
  `prospectId` int unsigned NOT NULL,
  `userId` smallint unsigned NOT NULL,
  `timestamp` datetime NOT NULL,
  `note` text,
  `nextSteps` text,
  `followUpDate` date DEFAULT NULL,
  `actionType` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`noteId`),
  KEY `fk_prospnotes_userId_idx` (`userId`),
  KEY `idx_prospnotes_opId` (`prospectId`),
  CONSTRAINT `fk_prospnotes_prospectId` FOREIGN KEY (`prospectId`) REFERENCES `prospects` (`prospectId`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prospnotes_userId` FOREIGN KEY (`userId`) REFERENCES `users` (`idUser`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `queued_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `queued_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `queued_jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `revenue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `revenue` (
  `date` char(6) NOT NULL,
  `idFeedIn` int NOT NULL,
  `idFeedOut` int NOT NULL,
  `url` varchar(255) NOT NULL,
  `value` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`date`,`idFeedIn`,`idFeedOut`,`url`),
  KEY `idx_revenueReport` (`date`,`idFeedIn`,`idFeedOut`,`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stats_correlated`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stats_correlated` (
  `idFeedIn` int NOT NULL,
  `idFeedOut` int NOT NULL,
  `url` varchar(255) NOT NULL,
  `stamp` date NOT NULL,
  `accepted` int unsigned NOT NULL DEFAULT '0',
  `rejected` int unsigned NOT NULL DEFAULT '0',
  `costPerLead` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `revenuePerLead` decimal(10,4) unsigned NOT NULL DEFAULT '0.0000',
  `billable` int unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `feedUrl` (`idFeedIn`,`idFeedOut`,`url`,`stamp`),
  KEY `stats_correlated_stamp_IDX` (`stamp`),
  KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stats_inbound`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stats_inbound` (
  `idFeedIn` int NOT NULL,
  `url` varchar(255) NOT NULL,
  `stamp` date NOT NULL,
  `accepted` int DEFAULT '0',
  `rejected` int DEFAULT '0',
  UNIQUE KEY `feedUrl` (`idFeedIn`,`url`,`stamp`),
  KEY `idFeedIn` (`idFeedIn`),
  KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stats_outbound`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stats_outbound` (
  `idFeedOut` int NOT NULL,
  `url` varchar(255) NOT NULL,
  `stamp` date NOT NULL,
  `accepted` int unsigned NOT NULL DEFAULT '0',
  `rejected` int unsigned NOT NULL DEFAULT '0',
  `queued` int unsigned NOT NULL DEFAULT '0',
  `billable` int unsigned NOT NULL DEFAULT '0',
  UNIQUE KEY `feedUrl` (`idFeedOut`,`url`,`stamp`),
  KEY `idx_feedUrl` (`idFeedOut`,`url`),
  KEY `url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppression`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppression` (
  `email` varchar(150) NOT NULL,
  `idCompany` int DEFAULT NULL,
  UNIQUE KEY `email_UNIQUE` (`email`,`idCompany`),
  KEY `idCompany` (`idCompany`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `suppression_phones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppression_phones` (
  `phone` varchar(255) NOT NULL,
  `idCompany` mediumint unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`phone`,`idCompany`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `url_mapping`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `url_mapping` (
  `idFeedIn` int NOT NULL,
  `idFeedOut` int NOT NULL,
  `timestamp` datetime DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  UNIQUE KEY `url` (`idFeedIn`,`idFeedOut`,`url`),
  KEY `idx_url` (`url`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `urlcount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `urlcount` (
  `idUrlEntry` bigint NOT NULL AUTO_INCREMENT,
  `idFeedIn` varchar(45) DEFAULT NULL,
  `urlTrim` varchar(250) DEFAULT NULL,
  `urlFull` varchar(500) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `stamp` date DEFAULT NULL,
  PRIMARY KEY (`idUrlEntry`),
  UNIQUE KEY `dateUrl` (`stamp`,`idFeedIn`,`urlFull`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `urlcount_invalid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `urlcount_invalid` (
  `idUrlEntry` bigint NOT NULL AUTO_INCREMENT,
  `idFeedIn` varchar(45) DEFAULT NULL,
  `urlTrim` varchar(250) DEFAULT NULL,
  `urlFull` varchar(500) DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `stamp` date DEFAULT NULL,
  PRIMARY KEY (`idUrlEntry`),
  UNIQUE KEY `dateUrl` (`stamp`,`urlFull`,`idFeedIn`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `urls`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `urls` (
  `idUrl` bigint NOT NULL AUTO_INCREMENT,
  `urlFull` varchar(2000) DEFAULT NULL,
  `urlTrim` varchar(255) DEFAULT NULL,
  `urlGroup` varchar(255) DEFAULT NULL,
  `vertical` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idUrl`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `idUser` smallint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(255) DEFAULT NULL,
  `fullName` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `idCompany` int DEFAULT NULL,
  `level` int NOT NULL DEFAULT '0',
  `email` varchar(255) DEFAULT NULL,
  `accessBits` int unsigned NOT NULL DEFAULT '0',
  `isArchived` tinyint unsigned NOT NULL DEFAULT '0',
  `emailBits` int unsigned DEFAULT '0',
  PRIMARY KEY (`idUser`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `verticals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `verticals` (
  `verticalId` int unsigned NOT NULL AUTO_INCREMENT,
  `divisionId` int NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`verticalId`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` VALUES (1,'2022_06_08_015007_create_sessions_table',1);
INSERT INTO `migrations` VALUES (2,'2022_06_08_020033_create_queued_jobs_table',2);
INSERT INTO `migrations` VALUES (3,'2022_06_08_024316_create_failed_jobs_table',3);
