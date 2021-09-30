-- MySQL dump 10.19  Distrib 10.3.29-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: websco
-- ------------------------------------------------------
-- Server version	10.3.29-MariaDB-0+deb10u1

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
-- Current Database: `websco`
--

/*!40000 DROP DATABASE IF EXISTS `websco`*/;

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `websco` /*!40100 DEFAULT CHARACTER SET utf8 */;

USE `websco`;

--
-- Table structure for table `w_access`
--

DROP TABLE IF EXISTS `w_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_access` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dn` varchar(1024) NOT NULL DEFAULT '',
  `oid` int(10) unsigned NOT NULL DEFAULT 0,
  `allow_bits` binary(32) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_config`
--

DROP TABLE IF EXISTS `w_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_config` (
  `uid` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL DEFAULT '',
  `value` varchar(8192) NOT NULL DEFAULT '',
  PRIMARY KEY (`name`,`uid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_logs`
--

DROP TABLE IF EXISTS `w_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `operation` varchar(1024) NOT NULL,
  `params` varchar(4096) NOT NULL,
  `flags` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_runbooks`
--

DROP TABLE IF EXISTS `w_runbooks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_runbooks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `folder_id` int(10) unsigned NOT NULL,
  `guid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(1024) NOT NULL,
  `flags` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`,`guid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_runbooks_activities`
--

DROP TABLE IF EXISTS `w_runbooks_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_runbooks_activities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `guid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `flags` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`,`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_runbooks_folders`
--

DROP TABLE IF EXISTS `w_runbooks_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_runbooks_folders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` varchar(36) NOT NULL,
  `guid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `flags` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`,`guid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_runbooks_jobs`
--

DROP TABLE IF EXISTS `w_runbooks_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_runbooks_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `pid` int(10) unsigned NOT NULL,
  `guid` varchar(36) NOT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `flags` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`,`guid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_runbooks_jobs_params`
--

DROP TABLE IF EXISTS `w_runbooks_jobs_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_runbooks_jobs_params` (
  `pid` int(10) unsigned NOT NULL,
  `guid` varchar(36) NOT NULL,
  `value` varchar(4096) NOT NULL,
  PRIMARY KEY (`pid`,`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_runbooks_params`
--

DROP TABLE IF EXISTS `w_runbooks_params`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_runbooks_params` (
  `pid` varchar(36) NOT NULL,
  `guid` varchar(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `flags` int(10) unsigned NOT NULL,
  PRIMARY KEY (`guid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `w_users`
--

DROP TABLE IF EXISTS `w_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `w_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `passwd` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `mail` varchar(1024) CHARACTER SET latin1 NOT NULL,
  `sid` varchar(16) DEFAULT NULL,
  `flags` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-09-22 11:51:18
