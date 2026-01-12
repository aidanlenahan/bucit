-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jan 12, 2026 at 03:31 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bucit`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `part_name` varchar(255) NOT NULL,
  `part_number` varchar(100) DEFAULT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `part_name`, `part_number`, `quantity`, `notes`, `created_at`, `updated_at`) VALUES
(2, 'Screen', '2', 8, '', '2026-01-08 13:26:32', '2026-01-08 13:32:18');

-- --------------------------------------------------------

--
-- Table structure for table `technicians`
--

DROP TABLE IF EXISTS `technicians`;
CREATE TABLE IF NOT EXISTS `technicians` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(150) NOT NULL,
  `display_name` varchar(255) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `must_change_password` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `technicians`
--

INSERT INTO `technicians` (`id`, `username`, `display_name`, `password_hash`, `active`, `must_change_password`, `created_at`, `updated_at`) VALUES
(1, 'jmilonas', 'Jeremy Milonas', '$2y$10$JEKI7H0g1Hq9Kd6q2qWzsu8rwOszFx5ye7QaypJhWyHRzQ6QPQh0a', 1, 0, '2025-11-24 13:59:36', '2025-12-05 13:26:00'),
(2, 'aaguirre', 'Alex Aguirre', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:36', '2025-12-03 15:19:51'),
(3, 'jbonavico', 'James Bonavico', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:36', '2025-12-03 15:19:51'),
(4, 'nbracken', 'Nathan Bracken', '$2y$10$OX6O3xe2GeV4HYuVCwK2eeChZyVTH07n6ZcFzWjeWMozPJoU19Grm', 1, 0, '2025-11-24 13:59:36', '2025-12-05 13:15:53'),
(5, 'bcarventelopez', 'Brady Carvente-lopez', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:36', '2025-12-03 15:19:51'),
(6, 'pchen', 'Phil Chen', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:36', '2025-12-03 15:19:51'),
(7, 'kfarry', 'Keegan Farry', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:36', '2025-12-03 15:19:51'),
(8, 'jgardner', 'John Gardner', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:36', '2025-12-03 15:19:51'),
(9, 'cgriffiths', 'Charles Griffiths', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:36', '2025-12-03 15:19:51'),
(10, 'hhernandezpaez', 'Humberto Hernandez-paez', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(11, 'clanga', 'Charles Langa', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(12, 'alenahan', 'Aidan Lenahan', '$2y$10$ZyuXMXf2Sd4QHdNp.FNFReJkxGiVQ7uFeQb/.bHFwELhUJxDoDUha', 1, 0, '2025-11-24 13:59:37', '2025-12-03 15:21:13'),
(13, 'dlozada', 'Daniel Lozada', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(14, 'rmarcinczyk', 'Russell Marcinczyk', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(15, 'vniesz', 'Vincent Niesz', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(16, 'rromanski', 'Ryan Romanski', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(17, 'jschneider', 'John Schneider', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(18, 'tsquarewell', 'Tristen Squarewell', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(19, 'ktenahuagarcia', 'Kevin Tenahua-garcia', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(20, 'jhuss', 'Jason Huss', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-24 13:59:37', '2025-12-03 15:19:51'),
(21, 'awood', 'Alex Wood', '$2y$10$/OIm5uWJxbcCF3DpriAXF.6/B.3XqTetaN0kptAHnJrElLmcUZIyW', 1, 0, '2025-11-25 14:59:35', '2025-12-05 13:08:03'),
(22, 'recov', 'recov', '$2y$10$MgDCJiHj6NDUxYP2mr4fFeoA/k0gIbY2rJbMF17adICfmPZR12suq', 1, 1, '2025-11-25 15:21:32', '2025-12-03 15:19:51');

-- --------------------------------------------------------

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for the ticket',
  `first_name` varchar(50) NOT NULL COMMENT 'Student''s first name',
  `last_name` varchar(50) NOT NULL COMMENT 'Student''s last name',
  `school_id` varchar(20) NOT NULL COMMENT 'Student''s school ID',
  `class_of` varchar(4) DEFAULT NULL COMMENT 'Graduation year',
  `school_email` varchar(255) DEFAULT NULL,
  `additional_info` varchar(120) DEFAULT NULL,
  `date_reported` date NOT NULL COMMENT 'Date issue was reported',
  `problem_category` varchar(50) NOT NULL COMMENT 'Main problem selected (screen, battery, etc.)',
  `problem_detail` varchar(100) DEFAULT NULL,
  `custom_detail` text COMMENT 'If “Something else” was specified',
  `priority` tinyint(1) DEFAULT '3',
  `status` enum('Open','In Progress','Closed','Delayed') NOT NULL DEFAULT 'Open' COMMENT 'Current status of the ticket',
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the ticket was submitted',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Auto-updates when modified',
  `restarted` tinyint(1) DEFAULT '0',
  `tech` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tickets`
--

INSERT INTO `tickets` (`id`, `first_name`, `last_name`, `school_id`, `class_of`, `school_email`, `additional_info`, `date_reported`, `problem_category`, `problem_detail`, `custom_detail`, `priority`, `status`, `notes`, `created_at`, `updated_at`, `restarted`, `tech`) VALUES
(17, 'Aidan', '11111111', '111111', '2069', NULL, NULL, '2025-12-12', 'other', 'something_else', '11111111', 3, 'Open', NULL, '2025-12-12 14:18:04', '2025-12-12 14:18:04', 0, NULL),
(7, 'Gov', 'Ment', '263477', '0000', NULL, NULL, '1992-03-19', 'keyboard', 'something_else', 'Whenever I type a key the world blows up', 5, 'Closed', '', '2025-11-19 15:06:17', '2025-11-19 15:06:17', 0, 'awood'),
(8, 'Gov', 'Ment', '263477', '0000', NULL, NULL, '1992-03-19', 'other', 'something_else', 'Whenever I type a key the world blows up', 5, 'Closed', '', '2025-11-19 15:09:44', '2025-11-19 15:09:44', 0, 'nbracken'),
(16, 'first', 'Last', '222222', '2026', NULL, NULL, '2025-12-12', 'screen', 'flickering_display', NULL, 3, 'In Progress', NULL, '2025-12-12 14:11:06', '2025-12-12 14:11:06', 0, 'alenahan'),
(10, 'TestFirst', 'TestLast', '123456', '2026', NULL, NULL, '2025-11-21', 'screen', '', 'none', 5, 'Closed', '', '2025-11-21 12:56:56', '2025-11-21 12:56:56', 0, 'alenahan'),
(11, 'TestFirst', 'TestLast', '123456', '2026', NULL, NULL, '2025-11-21', 'screen', '', 'none', 5, 'Closed', '', '2025-11-21 12:56:57', '2025-11-21 12:56:57', 0, 'kfarry'),
(13, 'Student', 'A Student', '000000', '0000', NULL, NULL, '2025-11-21', 'battery', 'something_else', 'hello', 5, 'Closed', '', '2025-11-21 13:09:51', '2025-11-21 13:09:51', 0, 'nbracken'),
(18, 'Alexander', 'Wood', '676767', '2026', NULL, NULL, '2025-12-15', 'screen', 'cracked', '', 1, 'In Progress', '', '2025-12-15 15:21:31', '2025-12-15 15:21:31', 0, 'alenahan'),
(15, 'aha', 'asz', '000000', '0000', NULL, NULL, '2025-12-01', 'login', 'forgot_password', '', 5, 'Closed', '', '2025-12-01 13:30:25', '2025-12-01 13:30:25', 0, 'jschneider'),
(19, 'Alex', 'Wood', '333333', '2222', 'a@d.s', '', '2026-01-09', 'keyboard', 'key(s)_stuck', NULL, 3, 'In Progress', NULL, '2026-01-09 14:19:43', '2026-01-09 14:19:43', 0, 'alenahan');

-- --------------------------------------------------------

--
-- Table structure for table `ticket_parts`
--

DROP TABLE IF EXISTS `ticket_parts`;
CREATE TABLE IF NOT EXISTS `ticket_parts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `part_id` int NOT NULL,
  `quantity_used` int NOT NULL DEFAULT '1',
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `added_by` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_parts_ticket` (`ticket_id`),
  KEY `idx_ticket_parts_part` (`part_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ticket_points`
--

DROP TABLE IF EXISTS `ticket_points`;
CREATE TABLE IF NOT EXISTS `ticket_points` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_id` int NOT NULL,
  `tech` varchar(150) NOT NULL,
  `problem_category` varchar(50) DEFAULT NULL,
  `base_points` int NOT NULL DEFAULT '0',
  `awarded_points` int NOT NULL DEFAULT '0',
  `manual_override` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text,
  `updated_by` varchar(150) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_id` (`ticket_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ticket_points`
--

INSERT INTO `ticket_points` (`id`, `ticket_id`, `tech`, `problem_category`, `base_points`, `awarded_points`, `manual_override`, `notes`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 8, 'alenahan', 'other', 0, 0, 0, NULL, 'backfill', '2025-12-05 13:08:42', '2025-12-05 13:08:42'),
(2, 7, 'awood', 'keyboard', 7, 7, 0, NULL, 'backfill', '2025-12-05 13:15:09', '2025-12-05 13:15:09'),
(3, 10, 'alenahan', 'screen', 10, 10, 0, NULL, 'backfill', '2025-12-05 13:15:09', '2025-12-05 13:15:09'),
(4, 13, 'nbracken', 'other', 0, 0, 0, NULL, 'backfill', '2025-12-05 13:17:05', '2025-12-05 13:17:05'),
(5, 11, 'kfarry', 'screen', 10, 10, 0, NULL, 'backfill', '2025-12-05 13:18:43', '2025-12-05 13:18:43'),
(6, 15, 'jschneider', 'login', 3, 3, 0, NULL, 'backfill', '2025-12-05 13:19:16', '2025-12-05 13:19:16');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
