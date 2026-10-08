-- ========================================================
-- LearnLMS Complete Database Backup & Sample Data
-- Generated: 2026-10-08 12:14:01
-- Database: railway on localhost:3306
-- Includes: Users, Syllabi, Topics, Materials, Assessments,
--           Questions, Submissions, Grades, Progress, etc.
-- Safe for direct import into any MySQL / MariaDB instance.
-- ========================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = '+00:00';
SET NAMES utf8mb4;

-- --------------------------------------------------------
-- Table structure & data for table `activity_logs` (468 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_user` (`user_id`),
  KEY `idx_activity_category` (`category`),
  KEY `idx_activity_created` (`created_at`)
) ENGINE=MyISAM AUTO_INCREMENT=609 DEFAULT CHARSET=latin1;

INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('1', '1', 'Logged out', 'Authentication', '2026-07-04 01:38:06'),
('2', '1', 'Logged out', 'Authentication', '2026-07-04 01:49:52'),
('3', '1', 'Logged in', 'Authentication', '2026-07-04 01:50:30'),
('4', '1', 'Logged out', 'Authentication', '2026-07-04 02:40:49'),
('5', '1', 'Logged in', 'Authentication', '2026-07-04 03:19:24'),
('6', '3', 'Logged in', 'Authentication', '2026-07-04 03:45:58'),
('7', '3', 'Auto-logged out (maintenance mode enabled)', 'Authentication', '2026-07-04 03:45:59'),
('8', '1', 'Logged in', 'Authentication', '2026-07-04 03:46:25'),
('9', '1', 'Logged out', 'Authentication', '2026-07-04 08:07:09'),
('10', '1', 'Logged in', 'Authentication', '2026-07-04 08:07:28'),
('11', '1', 'Logged out', 'Authentication', '2026-07-04 08:08:02'),
('12', '1', 'Logged in', 'Authentication', '2026-07-04 08:13:55'),
('13', '1', 'Logged out', 'Authentication', '2026-07-04 08:26:35'),
('14', '2', 'Logged in', 'Authentication', '2026-07-04 08:27:11'),
('15', '1', 'Logged in', 'Authentication', '2026-07-04 12:42:12'),
('16', '1', 'Logged out', 'Authentication', '2026-07-04 12:42:30'),
('17', '2', 'Logged out', 'Authentication', '2026-07-05 02:39:22'),
('18', '3', 'Logged in', 'Authentication', '2026-07-05 02:40:22'),
('19', '3', 'Logged out', 'Authentication', '2026-07-05 02:49:26'),
('20', '1', 'Logged in', 'Authentication', '2026-07-05 02:49:38'),
('21', '1', 'Logged out', 'Authentication', '2026-07-05 02:58:41'),
('22', '1', 'Logged in', 'Authentication', '2026-07-05 02:59:34'),
('23', '1', 'Logged out', 'Authentication', '2026-07-05 02:59:53'),
('24', '3', 'Logged in', 'Authentication', '2026-07-05 03:00:07'),
('25', '3', 'Logged out', 'Authentication', '2026-07-05 03:00:56'),
('26', '1', 'Logged in', 'Authentication', '2026-07-05 03:01:25'),
('27', '1', 'Logged out', 'Authentication', '2026-07-05 03:07:14'),
('28', '2', 'Logged in', 'Authentication', '2026-07-05 03:07:44'),
('29', '1', 'Logged in', 'Authentication', '2026-07-05 03:48:08'),
('30', '1', 'Logged out', 'Authentication', '2026-07-05 03:49:41'),
('31', '2', 'Logged in', 'Authentication', '2026-07-05 03:49:54'),
('32', '2', 'Logged out', 'Authentication', '2026-07-05 04:14:42'),
('33', '2', 'Logged in', 'Authentication', '2026-07-05 04:18:33'),
('34', '2', 'Logged out', 'Authentication', '2026-07-05 05:27:00'),
('35', '1', 'Logged in', 'Authentication', '2026-07-05 05:27:17'),
('36', '1', 'Logged out', 'Authentication', '2026-07-05 05:30:02'),
('37', '3', 'Logged in', 'Authentication', '2026-07-05 05:30:20'),
('38', '3', 'Logged out', 'Authentication', '2026-07-05 05:30:35'),
('39', '2', 'Logged in', 'Authentication', '2026-07-05 05:31:05'),
('40', '2', 'Logged out', 'Authentication', '2026-07-05 05:33:13'),
('41', '1', 'Logged in', 'Authentication', '2026-07-05 05:35:02'),
('42', '3', 'Logged in', 'Authentication', '2026-07-05 05:35:48'),
('43', '3', 'Auto-logged out (maintenance mode enabled)', 'Authentication', '2026-07-05 05:35:48'),
('44', '2', 'Logged in', 'Authentication', '2026-07-05 05:36:24'),
('45', '2', 'Auto-logged out (maintenance mode enabled)', 'Authentication', '2026-07-05 05:36:25'),
('46', '2', 'Logged in', 'Authentication', '2026-07-05 05:36:57'),
('47', '1', 'Logged out', 'Authentication', '2026-07-05 06:01:21'),
('48', '3', 'Logged in', 'Authentication', '2026-07-05 06:01:35'),
('49', '3', 'Logged out', 'Authentication', '2026-07-05 06:03:11'),
('50', '2', 'Logged in', 'Authentication', '2026-07-05 06:03:22');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('51', '2', 'Logged out', 'Authentication', '2026-07-05 06:05:12'),
('52', '17', 'Logged in', 'Authentication', '2026-07-05 06:05:26'),
('53', '17', 'Logged out', 'Authentication', '2026-07-05 06:07:02'),
('54', '3', 'Logged in', 'Authentication', '2026-07-05 06:07:20'),
('55', '3', 'Logged in', 'Authentication', '2026-07-07 03:27:43'),
('56', '3', 'Logged out', 'Authentication', '2026-07-07 03:29:18'),
('57', '1', 'Logged in', 'Authentication', '2026-07-07 03:29:33'),
('58', '1', 'Logged out', 'Authentication', '2026-07-07 08:27:28'),
('59', '1', 'Logged in', 'Authentication', '2026-07-07 08:27:43'),
('60', '1', 'Logged out', 'Authentication', '2026-07-07 08:28:22'),
('61', '2', 'Logged in', 'Authentication', '2026-07-07 12:48:50'),
('62', '2', 'Logged out', 'Authentication', '2026-07-07 12:51:35'),
('63', '1', 'Logged in', 'Authentication', '2026-07-13 10:07:06'),
('64', '1', 'Logged out', 'Authentication', '2026-07-13 10:46:47'),
('65', '12', 'Logged in', 'Authentication', '2026-07-13 10:47:42'),
('66', '12', 'Logged out', 'Authentication', '2026-07-13 11:50:53'),
('67', '1', 'Logged in', 'Authentication', '2026-07-13 11:51:07'),
('68', '1', 'Logged in', 'Authentication', '2026-07-18 07:44:18'),
('69', '1', 'Logged in', 'Authentication', '2026-07-29 12:24:04'),
('70', '1', 'Logged out', 'Authentication', '2026-07-29 12:26:14'),
('71', '1', 'Logged in', 'Authentication', '2026-07-30 10:55:10'),
('72', '1', 'Logged out', 'Authentication', '2026-07-30 10:56:45'),
('73', '17', 'Logged in', 'Authentication', '2026-07-30 10:57:11'),
('74', '17', 'Logged out', 'Authentication', '2026-07-30 10:57:49'),
('75', '3', 'Logged in', 'Authentication', '2026-07-30 10:57:56'),
('76', '1', 'Logged in', 'Authentication', '2026-07-30 11:01:07'),
('77', '1', 'Logged in', 'Authentication', '2026-07-30 12:24:13'),
('78', '1', 'Logged in', 'Authentication', '2026-08-01 06:45:47'),
('79', '1', 'Logged in', 'Authentication', '2026-08-05 06:52:53'),
('80', '1', 'Logged in', 'Authentication', '2026-08-05 09:15:02'),
('81', '1', 'Logged in', 'Authentication', '2026-08-05 14:36:15'),
('82', '1', 'Logged out', 'Authentication', '2026-08-05 14:36:42'),
('83', '1', 'Logged in', 'Authentication', '2026-08-05 15:23:12'),
('84', '1', 'Logged out', 'Authentication', '2026-08-05 15:24:59'),
('85', '34', 'Logged in', 'Authentication', '2026-08-05 15:25:20'),
('86', '34', 'Logged out', 'Authentication', '2026-08-05 15:25:56'),
('87', '1', 'Logged in', 'Authentication', '2026-08-05 15:26:13'),
('88', '1', 'Logged out', 'Authentication', '2026-08-05 15:26:48'),
('89', '2', 'Logged in', 'Authentication', '2026-08-05 15:31:16'),
('90', '3', 'Logged in', 'Authentication', '2026-08-05 15:31:17'),
('91', '1', 'Logged out', 'Authentication', '2026-08-05 18:39:22'),
('92', '2', 'Logged in', 'Authentication', '2026-08-05 18:40:09'),
('93', '1', 'Logged in', 'Authentication', '2026-08-06 01:49:06'),
('94', '35', 'Logged in', 'Authentication', '2026-08-06 01:56:43'),
('95', '2', 'Logged out', 'Authentication', '2026-08-06 01:59:32'),
('96', '2', 'Logged in', 'Authentication', '2026-08-06 01:59:54'),
('97', '35', 'Logged out', 'Authentication', '2026-08-06 02:00:13'),
('98', '36', 'Logged in', 'Authentication', '2026-08-06 02:00:50'),
('99', '2', 'Logged out', 'Authentication', '2026-08-06 02:02:00'),
('100', '3', 'Logged in', 'Authentication', '2026-08-06 02:02:10');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('101', '36', 'Logged out', 'Authentication', '2026-08-06 02:03:00'),
('102', '36', 'Logged in', 'Authentication', '2026-08-06 02:05:10'),
('103', '3', 'Logged in', 'Authentication', '2026-08-06 02:08:58'),
('104', '1', 'Logged out', 'Authentication', '2026-08-06 02:10:31'),
('105', '3', 'Logged out', 'Authentication', '2026-08-06 02:11:02'),
('106', '2', 'Logged in', 'Authentication', '2026-08-06 02:11:57'),
('107', '36', 'Logged in', 'Authentication', '2026-08-06 02:12:08'),
('108', '36', 'Logged out', 'Authentication', '2026-08-06 02:13:41'),
('109', '3', 'Logged in', 'Authentication', '2026-08-06 02:13:51'),
('110', '2', 'Logged out', 'Authentication', '2026-08-06 02:14:55'),
('111', '36', 'Logged in', 'Authentication', '2026-08-06 02:15:21'),
('112', '36', 'Logged out', 'Authentication', '2026-08-06 02:15:59'),
('113', '35', 'Logged in', 'Authentication', '2026-08-06 02:16:19'),
('114', '3', 'Logged out', 'Authentication', '2026-08-06 02:19:45'),
('115', '35', 'Logged in', 'Authentication', '2026-08-06 02:20:07'),
('116', '35', 'Logged out', 'Authentication', '2026-08-06 18:06:56'),
('117', '3', 'Logged in', 'Authentication', '2026-08-06 18:07:57'),
('118', '3', 'Logged out', 'Authentication', '2026-08-06 18:08:09'),
('119', '1', 'Logged in', 'Authentication', '2026-08-06 18:08:22'),
('120', '1', 'Logged out', 'Authentication', '2026-08-06 18:17:34'),
('121', '37', 'Logged in', 'Authentication', '2026-08-06 18:17:51'),
('122', '37', 'Logged out', 'Authentication', '2026-08-06 18:22:50'),
('123', '38', 'Logged in', 'Authentication', '2026-08-06 18:23:05'),
('124', '38', 'Logged out', 'Authentication', '2026-08-06 18:23:47'),
('125', '37', 'Logged in', 'Authentication', '2026-08-06 18:24:08'),
('126', '37', 'Logged out', 'Authentication', '2026-08-06 18:35:41'),
('127', '2', 'Logged in', 'Authentication', '2026-08-06 18:35:50'),
('128', '2', 'Logged in', 'Authentication', '2026-08-06 18:47:21'),
('129', '2', 'Logged out', 'Authentication', '2026-08-06 20:19:54'),
('130', '3', 'Logged in', 'Authentication', '2026-08-06 20:20:06'),
('131', '2', 'Logged in', 'Authentication', '2026-08-07 12:37:04'),
('132', '2', 'Logged out', 'Authentication', '2026-08-07 14:12:42'),
('133', '3', 'Logged in', 'Authentication', '2026-08-07 14:13:00'),
('134', '3', 'Logged out', 'Authentication', '2026-08-07 14:14:34'),
('135', '2', 'Logged in', 'Authentication', '2026-08-07 14:14:46'),
('136', '2', 'Logged out', 'Authentication', '2026-08-07 14:19:19'),
('137', '3', 'Logged in', 'Authentication', '2026-08-07 14:19:41'),
('138', '3', 'Logged out', 'Authentication', '2026-08-07 14:21:52'),
('139', '2', 'Logged in', 'Authentication', '2026-08-07 14:22:03'),
('140', '2', 'Logged out', 'Authentication', '2026-08-07 14:24:36'),
('141', '3', 'Logged in', 'Authentication', '2026-08-07 14:25:11'),
('142', '1', 'Logged in', 'Authentication', '2026-08-07 14:31:16'),
('143', '1', 'Logged out', 'Authentication', '2026-08-07 14:31:38'),
('144', '3', 'Logged in', 'Authentication', '2026-08-07 14:31:48'),
('145', '3', 'Logged out', 'Authentication', '2026-08-07 14:32:11'),
('146', '2', 'Logged in', 'Authentication', '2026-08-07 14:32:24'),
('147', '1', 'Logged in', 'Authentication', '2026-08-08 03:23:29'),
('148', '1', 'Logged out', 'Authentication', '2026-08-08 03:30:30'),
('149', '2', 'Logged in', 'Authentication', '2026-08-08 03:30:56'),
('150', '2', 'Logged out', 'Authentication', '2026-08-08 03:42:32');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('151', '3', 'Logged in', 'Authentication', '2026-08-08 03:42:47'),
('152', '3', 'Logged out', 'Authentication', '2026-08-08 03:49:03'),
('153', '1', 'Logged in', 'Authentication', '2026-08-08 03:50:00'),
('154', '1', 'Logged out', 'Authentication', '2026-08-08 05:28:51'),
('155', '1', 'Logged in', 'Authentication', '2026-08-08 05:40:31'),
('156', '1', 'Logged out', 'Authentication', '2026-08-08 05:41:18'),
('157', '2', 'Logged in', 'Authentication', '2026-08-08 06:12:29'),
('158', '2', 'Logged out', 'Authentication', '2026-08-08 06:19:14'),
('159', '1', 'Logged in', 'Authentication', '2026-08-08 06:20:04'),
('160', '1', 'Logged out', 'Authentication', '2026-08-08 06:21:36'),
('161', '3', 'Logged in', 'Authentication', '2026-08-08 06:21:50'),
('162', '3', 'Logged out', 'Authentication', '2026-08-08 07:08:20'),
('163', '1', 'Logged in', 'Authentication', '2026-08-08 08:05:56'),
('164', '1', 'Logged out', 'Authentication', '2026-08-08 08:08:29'),
('165', '2', 'Logged in', 'Authentication', '2026-08-08 08:08:41'),
('166', '2', 'Logged out', 'Authentication', '2026-08-08 08:12:19'),
('167', '2', 'Logged in', 'Authentication', '2026-08-08 08:12:47'),
('168', '2', 'Logged out', 'Authentication', '2026-08-08 08:17:50'),
('169', '2', 'Logged in', 'Authentication', '2026-08-08 08:18:20'),
('170', '3', 'Logged in', 'Authentication', '2026-08-08 08:19:12'),
('171', '36', 'Logged in', 'Authentication', '2026-08-08 08:27:16'),
('172', '36', 'Logged out', 'Authentication', '2026-08-08 08:27:55'),
('173', '3', 'Logged in', 'Authentication', '2026-08-08 08:28:31'),
('174', '2', 'Logged in', 'Authentication', '2026-08-08 08:29:36'),
('175', '2', 'Logged in', 'Authentication', '2026-08-08 08:31:36'),
('176', '1', 'Logged in', 'Authentication', '2026-08-08 08:34:06'),
('177', '1', 'Logged out', 'Authentication', '2026-08-08 11:30:24'),
('178', '2', 'Logged in', 'Authentication', '2026-08-08 11:30:40'),
('319', '2', 'Logged out', 'Authentication', '2026-09-03 08:53:14'),
('320', '3', 'Logged in', 'Authentication', '2026-09-03 08:53:32'),
('321', '3', 'Logged out', 'Authentication', '2026-09-03 08:58:17'),
('322', '1', 'Logged in', 'Authentication', '2026-09-03 08:58:27'),
('323', '1', 'Logged out', 'Authentication', '2026-09-03 09:15:17'),
('324', '2', 'Logged in', 'Authentication', '2026-09-03 16:33:56'),
('325', '2', 'Logged out', 'Authentication', '2026-09-03 17:03:44'),
('326', '3', 'Logged in', 'Authentication', '2026-09-03 17:03:54'),
('327', '3', 'Logged out', 'Authentication', '2026-09-03 17:04:41'),
('328', '2', 'Logged in', 'Authentication', '2026-09-03 17:04:53'),
('329', '2', 'Logged in', 'Authentication', '2026-09-03 17:06:10'),
('330', '2', 'Logged out', 'Authentication', '2026-09-03 17:06:37'),
('331', '3', 'Logged in', 'Authentication', '2026-09-03 17:06:50'),
('332', '3', 'Logged out', 'Authentication', '2026-09-03 17:10:32'),
('333', '1', 'Logged in', 'Authentication', '2026-09-03 17:10:47'),
('334', '2', 'Logged in', 'Authentication', '2026-09-04 05:36:24'),
('335', '2', 'Logged in', 'Authentication', '2026-09-04 05:40:46'),
('336', '2', 'Logged out', 'Authentication', '2026-09-04 05:44:15'),
('337', '3', 'Logged in', 'Authentication', '2026-09-04 05:44:30'),
('338', '3', 'Logged out', 'Authentication', '2026-09-04 05:45:00'),
('339', '1', 'Logged in', 'Authentication', '2026-09-04 05:45:08'),
('340', '1', 'Logged out', 'Authentication', '2026-09-04 06:16:18');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('341', '2', 'Logged in', 'Authentication', '2026-09-04 06:16:25'),
('342', '2', 'Logged out', 'Authentication', '2026-09-04 06:25:13'),
('343', '1', 'Logged in', 'Authentication', '2026-09-04 06:25:22'),
('344', '2', 'Logged out', 'Authentication', '2026-09-04 06:47:13'),
('345', '3', 'Logged in', 'Authentication', '2026-09-04 06:47:32'),
('346', '1', 'Logged out', 'Authentication', '2026-09-04 08:36:24'),
('347', '1', 'Logged in', 'Authentication', '2026-09-04 08:36:30'),
('348', '1', 'Logged in', 'Authentication', '2026-09-04 08:40:11'),
('349', '3', 'Logged out', 'Authentication', '2026-09-04 08:47:24'),
('350', '1', 'Logged in', 'Authentication', '2026-09-04 09:33:31'),
('351', '3', 'Logged in', 'Authentication', '2026-09-05 13:25:34'),
('352', '2', 'Logged in', 'Authentication', '2026-09-06 07:35:04'),
('353', '2', 'Logged out', 'Authentication', '2026-09-06 07:35:33'),
('354', '1', 'Logged in', 'Authentication', '2026-09-06 07:35:40'),
('355', '1', 'Logged out', 'Authentication', '2026-09-06 12:33:40'),
('356', '2', 'Logged in', 'Authentication', '2026-09-06 12:33:46'),
('357', '1', 'Logged in', 'Authentication', '2026-09-07 12:35:29'),
('358', '1', 'Logged out', 'Authentication', '2026-09-08 05:23:30'),
('359', '2', 'Logged in', 'Authentication', '2026-09-08 05:23:36'),
('360', '1', 'Logged in', 'Authentication', '2026-09-09 06:52:10'),
('361', '1', 'Logged out', 'Authentication', '2026-09-09 06:52:36'),
('362', '1', 'Logged in', 'Authentication', '2026-09-09 23:27:59'),
('363', '1', 'Logged out', 'Authentication', '2026-09-09 23:48:42'),
('364', '2', 'Logged in', 'Authentication', '2026-09-09 23:48:50'),
('365', '2', 'Logged out', 'Authentication', '2026-09-09 23:50:56'),
('366', '3', 'Logged in', 'Authentication', '2026-09-09 23:51:01'),
('367', '3', 'Logged out', 'Authentication', '2026-09-09 23:51:34'),
('368', '2', 'Logged in', 'Authentication', '2026-09-09 23:51:40'),
('369', '2', 'Logged out', 'Authentication', '2026-09-10 00:05:50'),
('370', '3', 'Logged in', 'Authentication', '2026-09-10 00:06:02'),
('371', '3', 'Logged out', 'Authentication', '2026-09-10 00:07:21'),
('372', '1', 'Logged in', 'Authentication', '2026-09-10 00:10:22'),
('373', '1', 'Logged in', 'Authentication', '2026-09-10 00:17:49'),
('374', '1', 'Logged in', 'Authentication', '2026-09-10 00:18:26'),
('375', '1', 'Logged out', 'Authentication', '2026-09-10 03:55:36'),
('376', '1', 'Logged in', 'Authentication', '2026-09-11 02:18:49'),
('377', '1', 'Logged in', 'Authentication', '2026-09-11 05:17:29'),
('378', '1', 'Logged out', 'Authentication', '2026-09-11 06:29:23'),
('379', '2', 'Logged in', 'Authentication', '2026-09-11 06:31:19'),
('380', '2', 'Logged out', 'Authentication', '2026-09-11 06:34:13'),
('381', '1', 'Logged in', 'Authentication', '2026-09-11 06:34:19'),
('382', '1', 'Logged out', 'Authentication', '2026-09-11 06:35:44'),
('383', '2', 'Logged in', 'Authentication', '2026-09-11 06:35:51'),
('384', '2', 'Logged out', 'Authentication', '2026-09-11 06:37:19'),
('385', '1', 'Logged in', 'Authentication', '2026-09-11 06:40:01'),
('386', '1', 'Logged out', 'Authentication', '2026-09-11 06:44:29'),
('387', '2', 'Logged in', 'Authentication', '2026-09-11 06:44:34'),
('388', '2', 'Logged out', 'Authentication', '2026-09-11 07:24:54'),
('389', '1', 'Logged in', 'Authentication', '2026-09-11 07:25:00'),
('390', '1', 'Logged out', 'Authentication', '2026-09-11 09:01:52');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('391', '1', 'Logged in', 'Authentication', '2026-09-11 09:02:07'),
('392', '1', 'Logged out', 'Authentication', '2026-09-11 09:04:57'),
('393', '2', 'Logged in', 'Authentication', '2026-09-11 09:05:16'),
('394', '2', 'Logged out', 'Authentication', '2026-09-11 09:07:42'),
('395', '1', 'Logged in', 'Authentication', '2026-09-11 09:07:59'),
('396', '1', 'Logged out', 'Authentication', '2026-09-11 09:14:41'),
('397', '2', 'Logged in', 'Authentication', '2026-09-11 09:16:12'),
('398', '2', 'Logged out', 'Authentication', '2026-09-11 09:19:27'),
('399', '1', 'Logged in', 'Authentication', '2026-09-11 09:20:37'),
('400', '3', 'Logged in', 'Authentication', '2026-09-11 09:20:42'),
('401', '1', 'Logged out', 'Authentication', '2026-09-11 09:22:49'),
('402', '3', 'Logged in', 'Authentication', '2026-09-11 09:23:03'),
('403', '3', 'Logged out', 'Authentication', '2026-09-11 09:25:24'),
('404', '1', 'Logged in', 'Authentication', '2026-09-11 09:25:37'),
('405', '1', 'Logged out', 'Authentication', '2026-09-11 10:43:14'),
('406', '3', 'Logged in', 'Authentication', '2026-09-11 10:43:19'),
('407', '3', 'Logged out', 'Authentication', '2026-09-11 11:11:47'),
('408', '1', 'Logged in', 'Authentication', '2026-09-11 11:11:56'),
('409', '1', 'Logged out', 'Authentication', '2026-09-11 11:13:59'),
('410', '2', 'Logged in', 'Authentication', '2026-09-11 11:14:05'),
('411', '2', 'Logged out', 'Authentication', '2026-09-11 11:14:56'),
('412', '3', 'Logged in', 'Authentication', '2026-09-11 11:15:01'),
('413', '3', 'Logged out', 'Authentication', '2026-09-11 11:16:23'),
('414', '1', 'Logged in', 'Authentication', '2026-09-11 11:17:06'),
('415', '1', 'Logged out', 'Authentication', '2026-09-11 11:25:06'),
('416', '1', 'Logged out', 'Authentication', '2026-09-11 11:46:49'),
('417', '2', 'Logged in', 'Authentication', '2026-09-11 11:48:09'),
('418', '1', 'Logged in', 'Authentication', '2026-09-11 12:01:31'),
('419', '1', 'Logged in', 'Authentication', '2026-09-11 12:18:26'),
('420', '1', 'Logged out', 'Authentication', '2026-09-11 12:26:59'),
('421', '41', 'Logged in', 'Authentication', '2026-09-11 12:27:05'),
('422', '41', 'Logged out', 'Authentication', '2026-09-11 12:28:01'),
('423', '1', 'Logged in', 'Authentication', '2026-09-11 12:28:12'),
('424', '1', 'Logged in', 'Authentication', '2026-09-11 12:29:33'),
('425', '2', 'Logged in', 'Authentication', '2026-09-11 13:29:29'),
('426', '13', 'Logged in', 'Authentication', '2026-09-11 14:02:57'),
('427', '2', 'Logged out', 'Authentication', '2026-09-11 14:09:59'),
('428', '1', 'Logged out', 'Authentication', '2026-09-11 14:17:19'),
('429', '2', 'Logged out', 'Authentication', '2026-09-11 15:01:00'),
('430', '1', 'Logged in', 'Authentication', '2026-09-11 15:01:42'),
('431', '45', 'Logged in', 'Authentication', '2026-09-11 15:28:05'),
('432', '45', 'Logged in', 'Authentication', '2026-09-11 15:40:43'),
('433', '45', 'Logged out', 'Authentication', '2026-09-11 15:47:27'),
('434', '59', 'Logged in', 'Authentication', '2026-09-11 15:47:38'),
('435', '1', 'Logged out', 'Authentication', '2026-09-11 15:48:13'),
('436', '45', 'Logged in', 'Authentication', '2026-09-11 15:48:25'),
('437', '1', 'Logged in', 'Authentication', '2026-09-11 15:56:52'),
('438', '1', 'Logged out', 'Authentication', '2026-09-11 16:16:34'),
('439', '45', 'Logged in', 'Authentication', '2026-09-11 16:16:49'),
('440', '59', 'Logged out', 'Authentication', '2026-09-11 16:59:17');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('441', '1', 'Logged in', 'Authentication', '2026-09-11 16:59:31'),
('442', '45', 'Logged out', 'Authentication', '2026-09-11 17:03:53'),
('443', '59', 'Logged in', 'Authentication', '2026-09-11 17:04:08'),
('444', '1', 'Logged out', 'Authentication', '2026-09-11 17:47:46'),
('445', '59', 'Logged in', 'Authentication', '2026-09-11 17:47:51'),
('446', '1', 'Logged out', 'Authentication', '2026-09-11 17:51:30'),
('447', '59', 'Logged in', 'Authentication', '2026-09-11 17:51:56'),
('448', '1', 'Logged in', 'Authentication', '2026-09-11 17:59:46'),
('449', '1', 'Logged out', 'Authentication', '2026-09-11 18:01:31'),
('450', '45', 'Logged in', 'Authentication', '2026-09-11 18:01:53'),
('451', '45', 'Logged out', 'Authentication', '2026-09-11 18:28:02'),
('452', '1', 'Logged in', 'Authentication', '2026-09-11 18:28:23'),
('453', '1', 'Logged out', 'Authentication', '2026-09-11 18:29:54'),
('454', '45', 'Logged in', 'Authentication', '2026-09-11 18:30:08'),
('455', '45', 'Logged out', 'Authentication', '2026-09-11 18:32:34'),
('456', '59', 'Logged out', 'Authentication', '2026-09-11 18:32:59'),
('457', '1', 'Logged in', 'Authentication', '2026-09-11 18:33:12'),
('458', '45', 'Logged in', 'Authentication', '2026-09-11 18:33:38'),
('459', '1', 'Logged out', 'Authentication', '2026-09-11 18:35:00'),
('460', '59', 'Logged in', 'Authentication', '2026-09-11 18:35:14'),
('461', '45', 'Logged out', 'Authentication', '2026-09-11 18:48:54'),
('462', '1', 'Logged in', 'Authentication', '2026-09-11 18:49:12'),
('463', '1', 'Logged out', 'Authentication', '2026-09-11 18:50:23'),
('464', '41', 'Logged in', 'Authentication', '2026-09-11 18:50:59'),
('465', '59', 'Logged out', 'Authentication', '2026-09-11 18:52:52'),
('466', '1', 'Logged in', 'Authentication', '2026-09-11 18:53:09'),
('467', '41', 'Logged out', 'Authentication', '2026-09-11 18:59:06'),
('468', '59', 'Logged in', 'Authentication', '2026-09-11 18:59:33'),
('469', '59', 'Logged out', 'Authentication', '2026-09-11 19:03:29'),
('470', '1', 'Logged in', 'Authentication', '2026-09-11 19:03:37'),
('471', '1', 'Logged out', 'Authentication', '2026-09-11 19:04:31'),
('472', '1', 'Logged in', 'Authentication', '2026-09-11 19:05:24'),
('473', '1', 'Logged out', 'Authentication', '2026-09-11 19:05:39'),
('474', '1', 'Logged in', 'Authentication', '2026-09-11 19:06:23'),
('475', '1', 'Logged out', 'Authentication', '2026-09-11 19:06:42'),
('476', '39', 'Logged in', 'Authentication', '2026-09-11 19:07:02'),
('477', '39', 'Logged out', 'Authentication', '2026-09-11 19:12:16'),
('478', '59', 'Logged in', 'Authentication', '2026-09-11 19:12:24'),
('479', '59', 'Logged out', 'Authentication', '2026-09-11 19:12:36'),
('480', '39', 'Logged in', 'Authentication', '2026-09-11 19:12:54'),
('481', '39', 'Logged out', 'Authentication', '2026-09-11 19:14:56'),
('482', '59', 'Logged in', 'Authentication', '2026-09-11 19:15:02'),
('483', '59', 'Logged out', 'Authentication', '2026-09-11 19:16:26'),
('484', '39', 'Logged in', 'Authentication', '2026-09-11 19:16:39'),
('485', '39', 'Logged out', 'Authentication', '2026-09-11 19:19:01'),
('486', '59', 'Logged in', 'Authentication', '2026-09-11 19:19:34'),
('487', '59', 'Logged out', 'Authentication', '2026-09-11 19:21:00'),
('488', '39', 'Logged in', 'Authentication', '2026-09-11 19:21:11'),
('489', '39', 'Logged out', 'Authentication', '2026-09-11 19:25:26'),
('490', '1', 'Logged in', 'Authentication', '2026-09-11 19:25:36');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('491', '1', 'Logged out', 'Authentication', '2026-09-11 19:29:57'),
('492', '39', 'Logged in', 'Authentication', '2026-09-11 19:31:52'),
('493', '39', 'Logged out', 'Authentication', '2026-09-11 19:32:38'),
('494', '59', 'Logged out', 'Authentication', '2026-09-11 19:36:47'),
('495', '1', 'Logged in', 'Authentication', '2026-09-11 19:36:55'),
('496', '45', 'Logged out', 'Authentication', '2026-09-11 20:26:54'),
('497', '59', 'Logged in', 'Authentication', '2026-09-11 20:27:10'),
('498', '59', 'Logged out', 'Authentication', '2026-09-11 20:32:07'),
('499', '39', 'Logged in', 'Authentication', '2026-09-11 20:33:02'),
('500', '1', 'Logged out', 'Authentication', '2026-09-11 20:36:37'),
('501', '59', 'Logged in', 'Authentication', '2026-09-11 20:36:47'),
('502', '59', 'Logged out', 'Authentication', '2026-09-11 20:37:53'),
('503', '39', 'Logged in', 'Authentication', '2026-09-11 20:38:13'),
('504', '39', 'Logged out', 'Authentication', '2026-09-11 20:39:29'),
('505', '59', 'Logged in', 'Authentication', '2026-09-11 20:39:39'),
('506', '59', 'Logged out', 'Authentication', '2026-09-11 20:40:00'),
('507', '39', 'Logged in', 'Authentication', '2026-09-11 20:40:13'),
('508', '39', 'Logged out', 'Authentication', '2026-09-11 20:40:47'),
('509', '59', 'Logged in', 'Authentication', '2026-09-11 20:41:00'),
('510', '59', 'Logged out', 'Authentication', '2026-09-11 20:42:04'),
('511', '39', 'Logged in', 'Authentication', '2026-09-11 20:42:17'),
('512', '39', 'Logged out', 'Authentication', '2026-09-11 20:42:45'),
('513', '1', 'Logged out', 'Authentication', '2026-09-11 20:43:34'),
('514', '1', 'Logged in', 'Authentication', '2026-09-11 20:43:42'),
('515', '45', 'Logged in', 'Authentication', '2026-09-11 20:44:01'),
('516', '45', 'Logged out', 'Authentication', '2026-09-11 20:44:46'),
('517', '90', 'Logged in', 'Authentication', '2026-09-11 20:48:35'),
('518', '1', 'Logged out', 'Authentication', '2026-09-11 20:50:08'),
('519', '59', 'Logged in', 'Authentication', '2026-09-11 20:50:17'),
('520', '90', 'Logged out', 'Authentication', '2026-09-11 21:01:10'),
('521', '39', 'Logged out', 'Authentication', '2026-09-11 21:01:47'),
('522', '1', 'Logged in', 'Authentication', '2026-09-11 21:02:07'),
('523', '39', 'Logged in', 'Authentication', '2026-09-11 21:02:55'),
('524', '39', 'Logged out', 'Authentication', '2026-09-11 21:28:57'),
('525', '59', 'Logged in', 'Authentication', '2026-09-11 21:29:31'),
('526', '59', 'Logged out', 'Authentication', '2026-09-11 21:35:57'),
('527', '39', 'Logged in', 'Authentication', '2026-09-11 21:36:23'),
('528', '39', 'Logged out', 'Authentication', '2026-09-11 21:49:40'),
('529', '45', 'Logged in', 'Authentication', '2026-09-11 21:50:17'),
('530', '1', 'Logged in', 'Authentication', '2026-09-11 22:08:21'),
('531', '1', 'Logged out', 'Authentication', '2026-09-11 22:37:57'),
('532', '59', 'Logged in', 'Authentication', '2026-09-11 22:38:05'),
('533', '59', 'Logged out', 'Authentication', '2026-09-11 22:38:45'),
('534', '59', 'Logged out', 'Authentication', '2026-09-11 22:39:48'),
('535', '1', 'Logged in', 'Authentication', '2026-09-11 22:39:53'),
('536', '90', 'Logged in', 'Authentication', '2026-09-11 22:40:38'),
('537', '45', 'Logged out', 'Authentication', '2026-09-11 22:59:52'),
('538', '1', 'Logged in', 'Authentication', '2026-09-11 23:00:24'),
('539', '90', 'Logged out', 'Authentication', '2026-09-12 02:25:27'),
('540', '1', 'Logged in', 'Authentication', '2026-09-12 02:25:42');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('541', '1', 'Logged out', 'Authentication', '2026-09-12 02:30:25'),
('542', '59', 'Logged in', 'Authentication', '2026-09-12 02:30:39'),
('543', '59', 'Logged out', 'Authentication', '2026-09-12 02:32:02'),
('544', '39', 'Logged in', 'Authentication', '2026-09-12 02:32:45'),
('545', '39', 'Logged out', 'Authentication', '2026-09-12 02:33:13'),
('546', '1', 'Logged in', 'Authentication', '2026-09-12 02:33:25'),
('547', '1', 'Logged out', 'Authentication', '2026-09-12 02:35:10'),
('548', '39', 'Logged in', 'Authentication', '2026-09-12 02:35:27'),
('549', '39', 'Logged out', 'Authentication', '2026-09-12 02:35:50'),
('550', '90', 'Logged in', 'Authentication', '2026-09-12 02:36:06'),
('551', '90', 'Logged out', 'Authentication', '2026-09-12 02:46:31'),
('552', '44', 'Logged in', 'Authentication', '2026-09-12 02:46:50'),
('553', '44', 'Logged out', 'Authentication', '2026-09-12 02:49:35'),
('554', '1', 'Logged in', 'Authentication', '2026-09-12 02:50:08'),
('555', '1', 'Logged out', 'Authentication', '2026-09-12 02:53:31'),
('556', '39', 'Logged in', 'Authentication', '2026-09-12 02:53:48'),
('557', '39', 'Logged out', 'Authentication', '2026-09-12 02:54:53'),
('558', '1', 'Logged in', 'Authentication', '2026-09-12 02:55:05'),
('559', '1', 'Logged out', 'Authentication', '2026-09-12 02:55:56'),
('560', '39', 'Logged in', 'Authentication', '2026-09-12 02:56:13'),
('561', '39', 'Logged out', 'Authentication', '2026-09-12 02:56:54'),
('562', '90', 'Logged in', 'Authentication', '2026-09-12 02:57:15'),
('563', '90', 'Logged out', 'Authentication', '2026-09-12 02:57:30'),
('564', '59', 'Logged in', 'Authentication', '2026-09-12 02:57:45'),
('565', '59', 'Logged out', 'Authentication', '2026-09-12 02:58:32'),
('566', '45', 'Logged in', 'Authentication', '2026-09-12 02:58:47'),
('567', '45', 'Logged out', 'Authentication', '2026-09-12 02:59:39'),
('568', '1', 'Logged in', 'Authentication', '2026-09-12 03:00:57'),
('569', '1', 'Logged out', 'Authentication', '2026-09-12 03:01:23'),
('570', '43', 'Logged in', 'Authentication', '2026-09-12 03:02:04'),
('571', '1', 'Logged out', 'Authentication', '2026-09-12 04:02:16'),
('572', '1', 'Logged in', 'Authentication', '2026-09-12 04:17:48'),
('573', '1', 'Logged in', 'Authentication', '2026-09-12 04:43:27'),
('574', '1', 'Logged out', 'Authentication', '2026-09-12 04:43:36'),
('575', '1', 'Logged in', 'Authentication', '2026-09-12 04:44:47'),
('576', '1', 'Logged out', 'Authentication', '2026-09-12 04:46:58'),
('577', '45', 'Logged in', 'Authentication', '2026-09-12 04:47:24'),
('578', '1', 'Logged out', 'Authentication', '2026-09-12 04:48:27'),
('579', '39', 'Logged in', 'Authentication', '2026-09-12 04:49:13'),
('580', '39', 'Logged out', 'Authentication', '2026-09-12 04:49:39'),
('581', '1', 'Logged in', 'Authentication', '2026-09-12 04:49:47'),
('582', '45', 'Logged in', 'Authentication', '2026-09-12 04:50:31'),
('583', '45', 'Logged out', 'Authentication', '2026-09-12 04:53:09'),
('584', '59', 'Logged in', 'Authentication', '2026-09-12 04:53:25'),
('585', '1', 'Logged out', 'Authentication', '2026-09-12 04:53:36'),
('586', '45', 'Logged in', 'Authentication', '2026-09-12 04:53:47'),
('587', '45', 'Logged in', 'Authentication', '2026-09-12 04:57:44'),
('588', '45', 'Logged out', 'Authentication', '2026-09-12 05:00:22'),
('589', '39', 'Logged in', 'Authentication', '2026-09-12 05:00:37'),
('590', '45', 'Logged out', 'Authentication', '2026-09-12 05:01:32');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
('591', '1', 'Logged in', 'Authentication', '2026-09-12 05:01:44'),
('592', '1', 'Logged out', 'Authentication', '2026-09-12 05:05:44'),
('593', '39', 'Logged out', 'Authentication', '2026-09-12 05:06:23'),
('594', '45', 'Logged in', 'Authentication', '2026-09-12 05:07:50'),
('595', '59', 'Logged in', 'Authentication', '2026-09-12 05:10:08'),
('596', '59', 'Logged out', 'Authentication', '2026-09-12 05:52:44'),
('597', '1', 'Logged in', 'Authentication', '2026-09-13 06:27:50'),
('598', '45', 'Logged in', 'Authentication', '2026-09-13 15:58:24'),
('599', '45', 'Logged in', 'Authentication', '2026-10-08 09:01:27'),
('600', '1', 'Logged in', 'Authentication', '2026-10-08 09:11:51'),
('601', '1', 'Logged out', 'Authentication', '2026-10-08 09:19:47'),
('602', '45', 'Logged in', 'Authentication', '2026-10-08 09:19:53'),
('603', '45', 'Logged in', 'Authentication', '2026-10-08 09:25:39'),
('604', '45', 'Logged in', 'Authentication', '2026-10-08 09:33:49'),
('605', '45', 'Logged out', 'Authentication', '2026-10-08 09:36:01'),
('606', '1', 'Logged in', 'Authentication', '2026-10-08 09:36:06'),
('607', '45', 'Logged in', 'Authentication', '2026-10-08 09:48:01'),
('608', '45', 'Logged in', 'Authentication', '2026-10-08 10:04:19');

-- --------------------------------------------------------
-- Table structure & data for table `announcements` (9 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `author_id` int NOT NULL,
  `syllabus_id` int DEFAULT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `content` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_role` enum('all','teacher','student') COLLATE utf8mb4_unicode_ci DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `author_id` (`author_id`),
  KEY `syllabus_id` (`syllabus_id`),
  CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `announcements` (`id`, `author_id`, `syllabus_id`, `title`, `content`, `target_role`, `created_at`) VALUES
('4', '1', NULL, 'Exam', 'Exam will be on April 6-8', 'all', '2026-03-25 09:30:21'),
('7', '1', NULL, '1234', '123435465', 'all', '2026-08-08 03:24:45'),
('8', '1', NULL, 'asd', 'asd', 'teacher', '2026-08-08 03:25:00'),
('9', '1', NULL, 'asd', 'asd', 'teacher', '2026-08-08 03:25:00'),
('10', '1', NULL, 'asd', 'asd', 'teacher', '2026-08-08 03:25:03'),
('12', '1', NULL, 'Exam', 'Exam will be on Sept.30 2026', 'all', '2026-09-12 04:48:04'),
('13', '1', NULL, 'I-Tech College Midterm Examination Schedule (AY 2025-2026)', 'Please be informed that the BSIS 4th Year Midterm Examinations will take place from Sept 28 to Oct 02, 2026. All course syllabi, lecture slides, and project rubrics are accessible on BlendEd LMS.', 'all', '2026-10-08 08:49:11'),
('14', '39', NULL, 'PROMAN413: Sprint 1 Retrospective & Project Backlog Due', 'Kindly ensure that all project teams have finalized their Sprint 1 User Stories and WBS Dictionary by Friday 11:59 PM. Submissions must be uploaded via the assessment module.', 'student', '2026-10-08 08:49:12'),
('15', '45', NULL, 'ADV08: Hands-on Lab Session on Data Cleaning', 'Our next class will be a hands-on laboratory session in Computer Lab 4. Please review the lecture materials on z-score normalization and outlier detection before class.', 'student', '2026-10-08 08:49:12');

-- --------------------------------------------------------
-- Table structure & data for table `assessment_questions` (8 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `assessment_questions`;
CREATE TABLE `assessment_questions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assessment_id` int NOT NULL,
  `question_text` text NOT NULL,
  `question_type` varchar(50) NOT NULL DEFAULT 'multiple_choice',
  `points` decimal(8,2) NOT NULL DEFAULT '1.00',
  `options` longtext,
  `correct_answer` text,
  `explanation` text,
  `sort_order` int NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aq_ass` (`assessment_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `assessment_questions` (`id`, `assessment_id`, `question_text`, `question_type`, `points`, `options`, `correct_answer`, `explanation`, `sort_order`, `created_at`) VALUES
('1', '45', 'What is the primary objective of an Enterprise Resource Planning (ERP) system?', 'multiple_choice', '5.00', '[\"Isolate department data in independent silos\",\"Integrate core business processes into a unified platform\",\"Replace human decision-making completely\",\"Manage local personal spreadsheets\"]', 'B. Integrate core business processes into a unified platform', 'An ERP integrates disparate business processes across finance, supply chain, and operations into a single shared database.', '1', '2026-10-08 08:47:24'),
('2', '45', 'Which architectural component of an Enterprise System ensures data consistency across all departments?', 'multiple_choice', '5.00', '[\"Independent department spreadsheets\",\"Centralized shared database repository\",\"Local USB flash storage\",\"Isolated email threads\"]', 'B. Centralized shared database repository', 'A centralized database repository eliminates data redundancy and guarantees that all functional units access a single source of truth.', '2', '2026-10-08 08:47:25'),
('3', '45', 'Enterprise Systems integrate business processes and core information flows across an entire organization in real-time.', 'true_false', '5.00', '[\"True\",\"False\"]', 'True', 'Enterprise systems operate on real-time transaction processing, updating organization-wide ledgers immediately.', '3', '2026-10-08 08:47:25'),
('4', '45', 'In a 3-tier enterprise architecture, which layer executes business logic, security policies, and transactional workflows?', 'multiple_choice', '5.00', '[\"Presentation Layer\",\"Application \\/ Business Logic Layer\",\"Hardware Power Supply\",\"Physical Cabling Layer\"]', 'B. Application / Business Logic Layer', 'The Application / Business Logic tier contains the ERP software engines that enforce validation and workflows between client UI and database.', '4', '2026-10-08 08:47:25'),
('5', '25', 'What is the primary objective of an Enterprise Resource Planning (ERP) system?', 'multiple_choice', '5.00', '[\"Isolate department data in independent silos\",\"Integrate core business processes into a unified platform\",\"Replace human decision-making completely\",\"Manage local personal spreadsheets\"]', 'B. Integrate core business processes into a unified platform', 'An ERP integrates disparate business processes across finance, supply chain, and operations into a single shared database.', '1', '2026-10-08 08:47:25'),
('6', '25', 'Which architectural component of an Enterprise System ensures data consistency across all departments?', 'multiple_choice', '5.00', '[\"Independent department spreadsheets\",\"Centralized shared database repository\",\"Local USB flash storage\",\"Isolated email threads\"]', 'B. Centralized shared database repository', 'A centralized database repository eliminates data redundancy and guarantees that all functional units access a single source of truth.', '2', '2026-10-08 08:47:25'),
('7', '25', 'Enterprise Systems integrate business processes and core information flows across an entire organization in real-time.', 'true_false', '5.00', '[\"True\",\"False\"]', 'True', 'Enterprise systems operate on real-time transaction processing, updating organization-wide ledgers immediately.', '3', '2026-10-08 08:47:25'),
('8', '25', 'In a 3-tier enterprise architecture, which layer executes business logic, security policies, and transactional workflows?', 'multiple_choice', '5.00', '[\"Presentation Layer\",\"Application \\/ Business Logic Layer\",\"Hardware Power Supply\",\"Physical Cabling Layer\"]', 'B. Application / Business Logic Layer', 'The Application / Business Logic tier contains the ERP software engines that enforce validation and workflows between client UI and database.', '4', '2026-10-08 08:47:25');

-- --------------------------------------------------------
-- Table structure & data for table `assessments` (18 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `assessments`;
CREATE TABLE `assessments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `syllabus_id` int NOT NULL,
  `topic_id` int DEFAULT NULL,
  `teacher_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('quiz','assignment','exam','project','activity') COLLATE utf8mb4_unicode_ci DEFAULT 'quiz',
  `max_score` decimal(5,2) DEFAULT '100.00',
  `due_date` datetime DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT '0',
  `delivery_mode` enum('online','offline','both') COLLATE utf8mb4_unicode_ci DEFAULT 'both',
  `attachment_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT '1',
  `submission_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'quiz_builder',
  `time_limit` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `syllabus_id` (`syllabus_id`),
  KEY `topic_id` (`topic_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `syllabus_topics` (`id`) ON DELETE SET NULL,
  CONSTRAINT `assessments_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `assessments` (`id`, `syllabus_id`, `topic_id`, `teacher_id`, `title`, `description`, `type`, `max_score`, `due_date`, `is_closed`, `delivery_mode`, `attachment_path`, `created_at`, `shuffle_questions`, `submission_type`, `time_limit`) VALUES
('3', '6', NULL, '17', 'Manage', 'Network Management', 'activity', '100.00', '2026-03-28 07:19:00', '0', 'both', NULL, '2026-03-27 23:19:56', '1', 'quiz_builder', NULL),
('10', '19', '35', '2', 'BSIS101 Quiz 1', 'Overview of IS concepts and applications', 'quiz', '100.00', '2026-09-15 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('11', '20', '37', '17', 'BSIS104 Assignment 1', 'Object-oriented programming', 'assignment', '100.00', '2026-09-16 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('12', '21', '39', '35', 'BSIS105 Exam 1', 'IS development methodologies', 'exam', '100.00', '2026-09-17 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('13', '22', '41', '37', 'BSIS106 Project 1', 'Relational databases and SQL', 'project', '100.00', '2026-09-18 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('14', '23', '43', '2', 'BSIS107 Activity 1', 'Web development for IS', 'activity', '100.00', '2026-09-19 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('15', '24', '45', '17', 'BSIS108 Quiz 1', 'Building enterprise applications', 'quiz', '100.00', '2026-09-20 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('16', '25', '47', '35', 'BSIS109 Assignment 1', 'Data and information resource management', 'assignment', '100.00', '2026-09-21 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('17', '26', '49', '37', 'BSIS111 Exam 1', 'Strategic planning for IS', 'exam', '100.00', '2026-09-22 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('18', '27', '51', '2', 'BSIS112 Project 1', 'UI/UX design principles', 'project', '100.00', '2026-09-23 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('19', '28', '53', '17', 'BSIS113 Activity 1', 'Cybersecurity in IS environments', 'activity', '100.00', '2026-09-24 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('20', '29', '55', '35', 'BSIS114 Quiz 1', 'Ethics and professionalism in IS', 'quiz', '100.00', '2026-09-25 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('21', '30', '57', '37', 'BSIS115 Assignment 1', 'Enterprise systems integration', 'assignment', '100.00', '2026-09-26 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('22', '31', '59', '2', 'BSIS116 Exam 1', 'IS research and project proposal', 'exam', '100.00', '2026-09-27 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('23', '32', '61', '17', 'BSIS117 Project 1', 'IS project implementation and defense', 'project', '100.00', '2026-09-28 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('24', '33', '63', '35', 'BSIS118 Activity 1', 'On-the-job training in IS field', 'activity', '100.00', '2026-09-29 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('25', '34', '65', '37', 'ENT123 Quiz 1', 'An Enterprise system is a large scale integrated software.', 'quiz', '20.00', '2026-09-30 23:59:00', '0', 'both', NULL, '2026-08-09 00:00:00', '1', 'quiz_builder', NULL),
('45', '70', '100', '45', 'Week 1: Enterprise Systems Fundamentals Quiz', 'Practical assessment evaluating understanding of ERP architecture, centralized data, and multi-tier systems.', 'quiz', '20.00', NULL, '0', 'online', NULL, '2026-10-08 08:47:24', '1', 'quiz_builder', NULL);

-- --------------------------------------------------------
-- Table structure & data for table `courses` (25 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `courses`;
CREATE TABLE `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `department_id` int NOT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `units` int DEFAULT '3',
  `prerequisite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'None',
  `year_level` int DEFAULT '1',
  `semester` enum('1st','2nd','Summer') COLLATE utf8mb4_unicode_ci DEFAULT '1st',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=74 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `courses` (`id`, `department_id`, `course_code`, `course_name`, `description`, `units`, `prerequisite`, `year_level`, `semester`, `status`, `created_at`) VALUES
('35', '5', 'BSIS101', 'Introduction to Information Systems', 'Overview of IS concepts and applications', '3', 'None', '2', '2nd', 'active', '2026-03-12 02:04:42'),
('36', '5', 'BSIS102', 'Computer Programming 1', 'Fundamentals of programming', '3', 'BSIS 101', '1', '1st', 'active', '2026-03-12 02:04:42'),
('37', '5', 'BSIS103', 'Mathematics in the Modern World', 'Applied mathematics for IS students', '3', 'None', '1', '1st', 'active', '2026-03-12 02:04:42'),
('38', '5', 'BSIS104', 'Computer Programming 2', 'Object-oriented programming', '3', 'BSIS 102', '1', '2nd', 'active', '2026-03-12 02:04:42'),
('39', '5', 'BSIS105', 'Systems Analysis and Design', 'IS development methodologies', '3', 'None', '2', '1st', 'active', '2026-03-12 02:04:42'),
('40', '5', 'BSIS106', 'Database Management Systems', 'Relational databases and SQL', '3', 'None', '2', '1st', 'active', '2026-03-12 02:04:42'),
('41', '5', 'BSIS107', 'Web Systems and Technologies', 'Web development for IS', '3', 'None', '2', '1st', 'active', '2026-03-12 02:04:43'),
('42', '5', 'BSIS108', 'Application Development', 'Building enterprise applications', '3', 'None', '2', '2nd', 'active', '2026-03-12 02:04:43'),
('43', '5', 'BSIS109', 'Information Management', 'Data and information resource management', '3', 'None', '2', '2nd', 'active', '2026-03-12 02:04:43'),
('44', '5', 'BSIS110', 'Network Management', 'Network design and administration', '3', 'None', '3', '1st', 'active', '2026-03-12 02:04:43'),
('45', '5', 'BSIS111', 'IS Strategy Management and Acquisition', 'Strategic planning for IS', '3', 'None', '3', '1st', 'active', '2026-03-12 02:04:43'),
('46', '5', 'BSIS112', 'Human Computer Interaction', 'UI/UX design principles', '3', 'None', '3', '1st', 'active', '2026-03-12 02:04:43'),
('47', '5', 'BSIS113', 'Information Assurance and Security', 'Cybersecurity in IS environments', '3', 'None', '3', '2nd', 'active', '2026-03-12 02:04:43'),
('48', '5', 'BSIS114', 'Social and Professional Issues in IS', 'Ethics and professionalism in IS', '3', 'None', '3', '2nd', 'active', '2026-03-12 02:04:43'),
('49', '5', 'BSIS115', 'Systems Integration and Architecture', 'Enterprise systems integration', '3', 'None', '4', '1st', 'active', '2026-03-12 02:04:43'),
('50', '5', 'BSIS116', 'Capstone Project 1', 'IS research and project proposal', '3', 'None', '4', '1st', 'active', '2026-03-12 02:04:43'),
('51', '5', 'BSIS117', 'Capstone Project 2', 'IS project implementation and defense', '3', 'None', '4', '2nd', 'active', '2026-03-12 02:04:43'),
('52', '5', 'BSIS118', 'Practicum / OJT', 'On-the-job training in IS field', '6', 'None', '4', '2nd', 'active', '2026-03-12 02:04:43'),
('53', '5', 'ENT123', 'ENTERPRISE SYSTEM', 'An Enterprise system is a large scale integrated software.', '3', 'BSIS 101', '3', '2nd', 'active', '2026-03-28 06:00:33'),
('54', '5', 'ISSMA413', 'IS Strategy,Management And Acquisition', '', '3', 'None', '4', '1st', 'active', '2026-08-24 08:19:15'),
('59', '5', 'ADET413', 'Application Development and Emerging Technologies', '', '3', 'None', '4', '1st', 'active', '2026-08-24 16:14:55'),
('60', '5', 'HCI413', 'HUMAN COMPUTER INTERACTION', '', '3', 'None', '4', '1st', 'active', '2026-08-24 16:16:19'),
('65', '5', 'PROMAN413', 'IS Project Management2', '', '3', 'None', '4', '1st', 'active', '2026-08-24 16:17:29'),
('66', '5', 'CAP413', 'Capstone2', '', '2', 'None', '4', '1st', 'active', '2026-08-24 16:18:03'),
('70', '5', 'ADV08', 'Data Mining', '', '3', 'None', '4', '1st', 'active', '2026-08-24 16:18:38');

-- --------------------------------------------------------
-- Table structure & data for table `departments` (1 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `departments` (`id`, `name`, `code`, `description`, `created_at`) VALUES
('5', 'Bachelor of Science and Information System', 'BSIS', '', '2026-03-12 01:57:14');

-- --------------------------------------------------------
-- Table structure & data for table `enrollments` (233 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `enrollments`;
CREATE TABLE `enrollments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `syllabus_id` int NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('enrolled','dropped','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'enrolled',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`student_id`,`syllabus_id`),
  KEY `syllabus_id` (`syllabus_id`),
  CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=325 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `enrollments` (`id`, `student_id`, `syllabus_id`, `enrolled_at`, `status`) VALUES
('15', '3', '6', '2026-03-27 23:18:33', 'enrolled'),
('20', '3', '11', '2026-08-06 18:06:10', 'enrolled'),
('22', '38', '14', '2026-08-06 18:22:29', 'enrolled'),
('26', '3', '19', '2026-08-09 00:00:00', 'enrolled'),
('27', '11', '19', '2026-08-09 00:00:00', 'enrolled'),
('28', '12', '20', '2026-08-09 00:00:00', 'enrolled'),
('29', '13', '20', '2026-08-09 00:00:00', 'enrolled'),
('30', '15', '21', '2026-08-09 00:00:00', 'enrolled'),
('31', '16', '21', '2026-08-09 00:00:00', 'enrolled'),
('32', '31', '22', '2026-08-09 00:00:00', 'enrolled'),
('33', '32', '22', '2026-08-09 00:00:00', 'enrolled'),
('34', '33', '23', '2026-08-09 00:00:00', 'enrolled'),
('35', '36', '23', '2026-08-09 00:00:00', 'enrolled'),
('36', '38', '24', '2026-08-09 00:00:00', 'enrolled'),
('37', '3', '24', '2026-08-09 00:00:00', 'enrolled'),
('38', '11', '25', '2026-08-09 00:00:00', 'enrolled'),
('39', '12', '25', '2026-08-09 00:00:00', 'enrolled'),
('40', '13', '26', '2026-08-09 00:00:00', 'enrolled'),
('41', '15', '26', '2026-08-09 00:00:00', 'enrolled'),
('42', '16', '27', '2026-08-09 00:00:00', 'enrolled'),
('43', '31', '27', '2026-08-09 00:00:00', 'enrolled'),
('44', '32', '28', '2026-08-09 00:00:00', 'enrolled'),
('45', '33', '28', '2026-08-09 00:00:00', 'enrolled'),
('46', '36', '29', '2026-08-09 00:00:00', 'enrolled'),
('47', '38', '29', '2026-08-09 00:00:00', 'enrolled'),
('48', '3', '30', '2026-08-09 00:00:00', 'enrolled'),
('49', '11', '30', '2026-08-09 00:00:00', 'enrolled'),
('50', '12', '31', '2026-08-09 00:00:00', 'enrolled'),
('51', '13', '31', '2026-08-09 00:00:00', 'enrolled'),
('52', '15', '32', '2026-08-09 00:00:00', 'enrolled'),
('53', '16', '32', '2026-08-09 00:00:00', 'enrolled'),
('54', '31', '33', '2026-08-09 00:00:00', 'enrolled'),
('55', '32', '33', '2026-08-09 00:00:00', 'enrolled'),
('56', '33', '34', '2026-08-09 00:00:00', 'enrolled'),
('57', '36', '34', '2026-08-09 00:00:00', 'enrolled'),
('92', '3', '34', '2026-10-08 08:44:52', 'enrolled'),
('93', '3', '70', '2026-10-08 08:44:52', 'enrolled'),
('97', '3', '13', '2026-10-08 08:48:56', 'enrolled'),
('98', '3', '14', '2026-10-08 08:48:56', 'enrolled'),
('100', '3', '20', '2026-10-08 08:48:56', 'enrolled'),
('101', '3', '21', '2026-10-08 08:48:56', 'enrolled'),
('102', '3', '22', '2026-10-08 08:48:56', 'enrolled'),
('103', '3', '23', '2026-10-08 08:48:57', 'enrolled'),
('105', '3', '25', '2026-10-08 08:48:57', 'enrolled'),
('106', '3', '26', '2026-10-08 08:48:57', 'enrolled'),
('107', '3', '27', '2026-10-08 08:48:57', 'enrolled'),
('108', '3', '28', '2026-10-08 08:48:57', 'enrolled'),
('109', '3', '29', '2026-10-08 08:48:57', 'enrolled'),
('111', '3', '31', '2026-10-08 08:48:57', 'enrolled'),
('112', '3', '32', '2026-10-08 08:48:57', 'enrolled');
INSERT INTO `enrollments` (`id`, `student_id`, `syllabus_id`, `enrolled_at`, `status`) VALUES
('113', '3', '33', '2026-10-08 08:48:57', 'enrolled'),
('115', '11', '6', '2026-10-08 08:48:57', 'enrolled'),
('116', '11', '13', '2026-10-08 08:48:57', 'enrolled'),
('117', '11', '14', '2026-10-08 08:48:58', 'enrolled'),
('119', '11', '20', '2026-10-08 08:48:58', 'enrolled'),
('120', '11', '21', '2026-10-08 08:48:58', 'enrolled'),
('121', '11', '22', '2026-10-08 08:48:58', 'enrolled'),
('122', '11', '23', '2026-10-08 08:48:58', 'enrolled'),
('123', '11', '24', '2026-10-08 08:48:58', 'enrolled'),
('125', '11', '26', '2026-10-08 08:48:58', 'enrolled'),
('126', '11', '27', '2026-10-08 08:48:58', 'enrolled'),
('127', '11', '28', '2026-10-08 08:48:58', 'enrolled'),
('128', '11', '29', '2026-10-08 08:48:59', 'enrolled'),
('130', '11', '31', '2026-10-08 08:48:59', 'enrolled'),
('131', '11', '32', '2026-10-08 08:48:59', 'enrolled'),
('132', '11', '33', '2026-10-08 08:48:59', 'enrolled'),
('133', '11', '70', '2026-10-08 08:48:59', 'enrolled'),
('134', '12', '6', '2026-10-08 08:48:59', 'enrolled'),
('135', '12', '13', '2026-10-08 08:48:59', 'enrolled'),
('136', '12', '14', '2026-10-08 08:48:59', 'enrolled'),
('137', '12', '19', '2026-10-08 08:48:59', 'enrolled'),
('139', '12', '21', '2026-10-08 08:48:59', 'enrolled'),
('140', '12', '22', '2026-10-08 08:48:59', 'enrolled'),
('141', '12', '23', '2026-10-08 08:48:59', 'enrolled'),
('142', '12', '24', '2026-10-08 08:49:00', 'enrolled'),
('144', '12', '26', '2026-10-08 08:49:00', 'enrolled'),
('145', '12', '27', '2026-10-08 08:49:00', 'enrolled'),
('146', '12', '28', '2026-10-08 08:49:00', 'enrolled'),
('147', '12', '29', '2026-10-08 08:49:00', 'enrolled'),
('148', '12', '30', '2026-10-08 08:49:00', 'enrolled'),
('150', '12', '32', '2026-10-08 08:49:00', 'enrolled'),
('151', '12', '33', '2026-10-08 08:49:00', 'enrolled'),
('152', '12', '70', '2026-10-08 08:49:00', 'enrolled'),
('153', '13', '6', '2026-10-08 08:49:00', 'enrolled'),
('154', '13', '13', '2026-10-08 08:49:00', 'enrolled'),
('155', '13', '14', '2026-10-08 08:49:01', 'enrolled'),
('156', '13', '19', '2026-10-08 08:49:01', 'enrolled'),
('158', '13', '21', '2026-10-08 08:49:01', 'enrolled'),
('159', '13', '22', '2026-10-08 08:49:01', 'enrolled'),
('160', '13', '23', '2026-10-08 08:49:01', 'enrolled'),
('161', '13', '24', '2026-10-08 08:49:01', 'enrolled'),
('162', '13', '25', '2026-10-08 08:49:01', 'enrolled'),
('164', '13', '27', '2026-10-08 08:49:01', 'enrolled'),
('165', '13', '28', '2026-10-08 08:49:01', 'enrolled'),
('166', '13', '29', '2026-10-08 08:49:01', 'enrolled'),
('167', '13', '30', '2026-10-08 08:49:01', 'enrolled'),
('169', '13', '32', '2026-10-08 08:49:02', 'enrolled'),
('170', '13', '33', '2026-10-08 08:49:02', 'enrolled'),
('171', '13', '70', '2026-10-08 08:49:02', 'enrolled'),
('172', '15', '6', '2026-10-08 08:49:02', 'enrolled');
INSERT INTO `enrollments` (`id`, `student_id`, `syllabus_id`, `enrolled_at`, `status`) VALUES
('173', '15', '13', '2026-10-08 08:49:02', 'enrolled'),
('174', '15', '14', '2026-10-08 08:49:02', 'enrolled'),
('175', '15', '19', '2026-10-08 08:49:02', 'enrolled'),
('176', '15', '20', '2026-10-08 08:49:02', 'enrolled'),
('178', '15', '22', '2026-10-08 08:49:02', 'enrolled'),
('179', '15', '23', '2026-10-08 08:49:02', 'enrolled'),
('180', '15', '24', '2026-10-08 08:49:02', 'enrolled'),
('181', '15', '25', '2026-10-08 08:49:03', 'enrolled'),
('183', '15', '27', '2026-10-08 08:49:03', 'enrolled'),
('184', '15', '28', '2026-10-08 08:49:03', 'enrolled'),
('185', '15', '29', '2026-10-08 08:49:03', 'enrolled'),
('186', '15', '30', '2026-10-08 08:49:03', 'enrolled'),
('187', '15', '31', '2026-10-08 08:49:03', 'enrolled'),
('189', '15', '33', '2026-10-08 08:49:03', 'enrolled'),
('190', '15', '70', '2026-10-08 08:49:03', 'enrolled'),
('191', '16', '6', '2026-10-08 08:49:03', 'enrolled'),
('192', '16', '13', '2026-10-08 08:49:03', 'enrolled'),
('193', '16', '14', '2026-10-08 08:49:03', 'enrolled'),
('194', '16', '19', '2026-10-08 08:49:03', 'enrolled'),
('195', '16', '20', '2026-10-08 08:49:03', 'enrolled'),
('197', '16', '22', '2026-10-08 08:49:04', 'enrolled'),
('198', '16', '23', '2026-10-08 08:49:04', 'enrolled'),
('199', '16', '24', '2026-10-08 08:49:04', 'enrolled'),
('200', '16', '25', '2026-10-08 08:49:04', 'enrolled'),
('201', '16', '26', '2026-10-08 08:49:04', 'enrolled'),
('203', '16', '28', '2026-10-08 08:49:04', 'enrolled'),
('204', '16', '29', '2026-10-08 08:49:04', 'enrolled'),
('205', '16', '30', '2026-10-08 08:49:04', 'enrolled'),
('206', '16', '31', '2026-10-08 08:49:04', 'enrolled'),
('208', '16', '33', '2026-10-08 08:49:04', 'enrolled'),
('209', '16', '70', '2026-10-08 08:49:04', 'enrolled'),
('210', '31', '6', '2026-10-08 08:49:04', 'enrolled'),
('211', '31', '13', '2026-10-08 08:49:05', 'enrolled'),
('212', '31', '14', '2026-10-08 08:49:05', 'enrolled'),
('213', '31', '19', '2026-10-08 08:49:05', 'enrolled'),
('214', '31', '20', '2026-10-08 08:49:05', 'enrolled'),
('215', '31', '21', '2026-10-08 08:49:05', 'enrolled'),
('217', '31', '23', '2026-10-08 08:49:05', 'enrolled'),
('218', '31', '24', '2026-10-08 08:49:05', 'enrolled'),
('219', '31', '25', '2026-10-08 08:49:05', 'enrolled'),
('220', '31', '26', '2026-10-08 08:49:05', 'enrolled'),
('222', '31', '28', '2026-10-08 08:49:05', 'enrolled'),
('223', '31', '29', '2026-10-08 08:49:05', 'enrolled'),
('224', '31', '30', '2026-10-08 08:49:05', 'enrolled'),
('225', '31', '31', '2026-10-08 08:49:06', 'enrolled'),
('226', '31', '32', '2026-10-08 08:49:06', 'enrolled'),
('228', '31', '70', '2026-10-08 08:49:06', 'enrolled'),
('229', '32', '6', '2026-10-08 08:49:06', 'enrolled'),
('230', '32', '13', '2026-10-08 08:49:06', 'enrolled'),
('231', '32', '14', '2026-10-08 08:49:06', 'enrolled');
INSERT INTO `enrollments` (`id`, `student_id`, `syllabus_id`, `enrolled_at`, `status`) VALUES
('232', '32', '19', '2026-10-08 08:49:06', 'enrolled'),
('233', '32', '20', '2026-10-08 08:49:06', 'enrolled'),
('234', '32', '21', '2026-10-08 08:49:06', 'enrolled'),
('236', '32', '23', '2026-10-08 08:49:06', 'enrolled'),
('237', '32', '24', '2026-10-08 08:49:06', 'enrolled'),
('238', '32', '25', '2026-10-08 08:49:06', 'enrolled'),
('239', '32', '26', '2026-10-08 08:49:07', 'enrolled'),
('240', '32', '27', '2026-10-08 08:49:07', 'enrolled'),
('242', '32', '29', '2026-10-08 08:49:07', 'enrolled'),
('243', '32', '30', '2026-10-08 08:49:07', 'enrolled'),
('244', '32', '31', '2026-10-08 08:49:07', 'enrolled'),
('245', '32', '32', '2026-10-08 08:49:07', 'enrolled'),
('247', '32', '70', '2026-10-08 08:49:07', 'enrolled'),
('248', '33', '6', '2026-10-08 08:49:07', 'enrolled'),
('249', '33', '13', '2026-10-08 08:49:07', 'enrolled'),
('250', '33', '14', '2026-10-08 08:49:07', 'enrolled'),
('251', '33', '19', '2026-10-08 08:49:07', 'enrolled'),
('252', '33', '20', '2026-10-08 08:49:07', 'enrolled'),
('253', '33', '21', '2026-10-08 08:49:07', 'enrolled'),
('254', '33', '22', '2026-10-08 08:49:08', 'enrolled'),
('256', '33', '24', '2026-10-08 08:49:08', 'enrolled'),
('257', '33', '25', '2026-10-08 08:49:08', 'enrolled'),
('258', '33', '26', '2026-10-08 08:49:08', 'enrolled'),
('259', '33', '27', '2026-10-08 08:49:08', 'enrolled'),
('261', '33', '29', '2026-10-08 08:49:08', 'enrolled'),
('262', '33', '30', '2026-10-08 08:49:08', 'enrolled'),
('263', '33', '31', '2026-10-08 08:49:08', 'enrolled'),
('264', '33', '32', '2026-10-08 08:49:08', 'enrolled'),
('265', '33', '33', '2026-10-08 08:49:08', 'enrolled'),
('266', '33', '70', '2026-10-08 08:49:08', 'enrolled'),
('267', '36', '6', '2026-10-08 08:49:08', 'enrolled'),
('268', '36', '13', '2026-10-08 08:49:08', 'enrolled'),
('269', '36', '14', '2026-10-08 08:49:09', 'enrolled'),
('270', '36', '19', '2026-10-08 08:49:09', 'enrolled'),
('271', '36', '20', '2026-10-08 08:49:09', 'enrolled'),
('272', '36', '21', '2026-10-08 08:49:09', 'enrolled'),
('273', '36', '22', '2026-10-08 08:49:09', 'enrolled'),
('275', '36', '24', '2026-10-08 08:49:09', 'enrolled'),
('276', '36', '25', '2026-10-08 08:49:09', 'enrolled'),
('277', '36', '26', '2026-10-08 08:49:09', 'enrolled'),
('278', '36', '27', '2026-10-08 08:49:09', 'enrolled'),
('279', '36', '28', '2026-10-08 08:49:09', 'enrolled'),
('281', '36', '30', '2026-10-08 08:49:09', 'enrolled'),
('282', '36', '31', '2026-10-08 08:49:09', 'enrolled'),
('283', '36', '32', '2026-10-08 08:49:10', 'enrolled'),
('284', '36', '33', '2026-10-08 08:49:10', 'enrolled'),
('285', '36', '70', '2026-10-08 08:49:10', 'enrolled'),
('286', '38', '6', '2026-10-08 08:49:10', 'enrolled'),
('287', '38', '13', '2026-10-08 08:49:10', 'enrolled'),
('289', '38', '19', '2026-10-08 08:49:10', 'enrolled');
INSERT INTO `enrollments` (`id`, `student_id`, `syllabus_id`, `enrolled_at`, `status`) VALUES
('290', '38', '20', '2026-10-08 08:49:10', 'enrolled'),
('291', '38', '21', '2026-10-08 08:49:10', 'enrolled'),
('292', '38', '22', '2026-10-08 08:49:10', 'enrolled'),
('293', '38', '23', '2026-10-08 08:49:10', 'enrolled'),
('295', '38', '25', '2026-10-08 08:49:10', 'enrolled'),
('296', '38', '26', '2026-10-08 08:49:10', 'enrolled'),
('297', '38', '27', '2026-10-08 08:49:10', 'enrolled'),
('298', '38', '28', '2026-10-08 08:49:11', 'enrolled'),
('300', '38', '30', '2026-10-08 08:49:11', 'enrolled'),
('301', '38', '31', '2026-10-08 08:49:11', 'enrolled'),
('302', '38', '32', '2026-10-08 08:49:11', 'enrolled'),
('303', '38', '33', '2026-10-08 08:49:11', 'enrolled'),
('304', '38', '70', '2026-10-08 08:49:11', 'enrolled'),
('305', '91', '70', '2026-10-08 09:32:17', 'enrolled'),
('306', '92', '70', '2026-10-08 09:32:17', 'enrolled'),
('307', '93', '70', '2026-10-08 09:32:17', 'enrolled'),
('308', '94', '70', '2026-10-08 09:32:17', 'enrolled'),
('309', '95', '70', '2026-10-08 09:32:17', 'enrolled'),
('310', '91', '34', '2026-10-08 09:32:17', 'enrolled'),
('311', '92', '34', '2026-10-08 09:32:17', 'enrolled'),
('312', '93', '34', '2026-10-08 09:32:17', 'enrolled'),
('313', '94', '34', '2026-10-08 09:32:18', 'enrolled'),
('314', '95', '34', '2026-10-08 09:32:18', 'enrolled'),
('315', '91', '22', '2026-10-08 09:32:18', 'enrolled'),
('316', '92', '22', '2026-10-08 09:32:18', 'enrolled'),
('317', '93', '22', '2026-10-08 09:32:18', 'enrolled'),
('318', '94', '22', '2026-10-08 09:32:18', 'enrolled'),
('319', '95', '22', '2026-10-08 09:32:18', 'enrolled'),
('320', '91', '14', '2026-10-08 09:32:18', 'enrolled'),
('321', '92', '14', '2026-10-08 09:32:19', 'enrolled'),
('322', '93', '14', '2026-10-08 09:32:19', 'enrolled'),
('323', '94', '14', '2026-10-08 09:32:19', 'enrolled'),
('324', '95', '14', '2026-10-08 09:32:19', 'enrolled');

-- --------------------------------------------------------
-- Table structure & data for table `learning_materials` (3 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `learning_materials`;
CREATE TABLE `learning_materials` (
  `id` int NOT NULL AUTO_INCREMENT,
  `syllabus_topic_id` int DEFAULT NULL,
  `syllabus_id` int DEFAULT NULL,
  `teacher_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'module',
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_mode` enum('online','offline','both') COLLATE utf8mb4_unicode_ci DEFAULT 'both',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `estimated_read_time` int NOT NULL DEFAULT '5',
  `view_count` int DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `syllabus_topic_id` (`syllabus_topic_id`),
  KEY `syllabus_id` (`syllabus_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `learning_materials_ibfk_1` FOREIGN KEY (`syllabus_topic_id`) REFERENCES `syllabus_topics` (`id`) ON DELETE SET NULL,
  CONSTRAINT `learning_materials_ibfk_2` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `learning_materials_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `learning_materials` (`id`, `syllabus_topic_id`, `syllabus_id`, `teacher_id`, `title`, `description`, `type`, `file_path`, `external_url`, `delivery_mode`, `created_at`, `content`, `estimated_read_time`, `view_count`) VALUES
('5', '8', '6', '17', 'test', 'dwewfgetgrht', 'document', 'mat_6a4792806c992.pdf', '', 'both', '2026-07-03 10:44:16', NULL, '5', '0'),
('32', '100', '70', '45', 'Introduction to Enterprise Systems & ERP Foundations', 'Foundational reading module covering enterprise architecture, 3-tier models, and business process integration.', 'module', NULL, NULL, 'both', '2026-10-08 08:47:24', '<h3>1. Overview of Enterprise Systems</h3>\n<p>An Enterprise System (ES), commonly realized as an Enterprise Resource Planning (ERP) system, is a comprehensive software platform designed to integrate all facets of an organization’s business processes—including finance, human resources, manufacturing, supply chain management, and customer relations—into a single unified computing environment.</p>\n\n<div style=\"background:#f8fafc;border-left:4px solid #2563eb;padding:16px 20px;margin:20px 0;border-radius:0 8px 8px 0\">\n    <h4 style=\"margin-top:0;color:#1e40af\"><i class=\"fas fa-lightbulb\"></i> Core Principle: The Single Source of Truth</h4>\n    <p style=\"margin-bottom:0\">By utilizing a unified, centralized database repository, an Enterprise System eliminates redundant data silos and ensures that transaction updates in one department immediately reflect across the entire organization in real-time.</p>\n</div>\n\n<h3>2. Foundational Architecture (3-Tier Framework)</h3>\n<p>Modern enterprise platforms typically follow a multi-tier modular architecture:</p>\n<ul>\n    <li><strong>Presentation Layer (Client Tier):</strong> The web or desktop interface accessed by end-users across departments to input transactions, view dashboards, and monitor KPIs.</li>\n    <li><strong>Application Layer (Business Logic Tier):</strong> The functional logic and processing engine enforcing organizational rules, workflow approvals, authorization matrices, and transactional validation.</li>\n    <li><strong>Database Layer (Data Tier):</strong> High-performance relational database management system guaranteeing ACID compliance and referential integrity across all corporate records.</li>\n</ul>\n\n<h3>3. Comparison: Traditional Silos vs. Enterprise Systems</h3>\n<table style=\"width:100%;border-collapse:collapse;margin:16px 0;font-size:14px\">\n    <thead>\n        <tr style=\"background:#f1f5f9;text-align:left\">\n            <th style=\"padding:10px;border:1px solid #cbd5e1\">Attribute</th>\n            <th style=\"padding:10px;border:1px solid #cbd5e1\">Traditional Functional Silos</th>\n            <th style=\"padding:10px;border:1px solid #cbd5e1\">Enterprise System (ERP)</th>\n        </tr>\n    </thead>\n    <tbody>\n        <tr>\n            <td style=\"padding:10px;border:1px solid #cbd5e1;font-weight:bold\">Data Storage</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Disconnected, local spreadsheets & isolated department databases.</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Centralized repository with relational consistency across all units.</td>\n        </tr>\n        <tr>\n            <td style=\"padding:10px;border:1px solid #cbd5e1;font-weight:bold\">Process Flow</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Manual data re-entry, delayed paper handoffs, high human error rates.</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Automated event triggers, seamless inter-departmental handoffs.</td>\n        </tr>\n        <tr>\n            <td style=\"padding:10px;border:1px solid #cbd5e1;font-weight:bold\">Reporting Speed</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Weekly or monthly reconciliation required to balance metrics.</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Instantaneous real-time institutional dashboards and analytics.</td>\n        </tr>\n    </tbody>\n</table>\n\n<h3>4. Key Takeaways for Assessment</h3>\n<p>As you prepare for the practical assessment, remember that successful enterprise adoption depends on strategic business process reengineering (BPR), clean data governance, and comprehensive user change management.</p>', '5', '0'),
('33', '65', '34', '37', 'Introduction to Enterprise Systems & ERP Foundations', 'Foundational reading module covering enterprise architecture, 3-tier models, and business process integration.', 'module', NULL, NULL, 'both', '2026-10-08 08:47:25', '<h3>1. Overview of Enterprise Systems</h3>\n<p>An Enterprise System (ES), commonly realized as an Enterprise Resource Planning (ERP) system, is a comprehensive software platform designed to integrate all facets of an organization’s business processes—including finance, human resources, manufacturing, supply chain management, and customer relations—into a single unified computing environment.</p>\n\n<div style=\"background:#f8fafc;border-left:4px solid #2563eb;padding:16px 20px;margin:20px 0;border-radius:0 8px 8px 0\">\n    <h4 style=\"margin-top:0;color:#1e40af\"><i class=\"fas fa-lightbulb\"></i> Core Principle: The Single Source of Truth</h4>\n    <p style=\"margin-bottom:0\">By utilizing a unified, centralized database repository, an Enterprise System eliminates redundant data silos and ensures that transaction updates in one department immediately reflect across the entire organization in real-time.</p>\n</div>\n\n<h3>2. Foundational Architecture (3-Tier Framework)</h3>\n<p>Modern enterprise platforms typically follow a multi-tier modular architecture:</p>\n<ul>\n    <li><strong>Presentation Layer (Client Tier):</strong> The web or desktop interface accessed by end-users across departments to input transactions, view dashboards, and monitor KPIs.</li>\n    <li><strong>Application Layer (Business Logic Tier):</strong> The functional logic and processing engine enforcing organizational rules, workflow approvals, authorization matrices, and transactional validation.</li>\n    <li><strong>Database Layer (Data Tier):</strong> High-performance relational database management system guaranteeing ACID compliance and referential integrity across all corporate records.</li>\n</ul>\n\n<h3>3. Comparison: Traditional Silos vs. Enterprise Systems</h3>\n<table style=\"width:100%;border-collapse:collapse;margin:16px 0;font-size:14px\">\n    <thead>\n        <tr style=\"background:#f1f5f9;text-align:left\">\n            <th style=\"padding:10px;border:1px solid #cbd5e1\">Attribute</th>\n            <th style=\"padding:10px;border:1px solid #cbd5e1\">Traditional Functional Silos</th>\n            <th style=\"padding:10px;border:1px solid #cbd5e1\">Enterprise System (ERP)</th>\n        </tr>\n    </thead>\n    <tbody>\n        <tr>\n            <td style=\"padding:10px;border:1px solid #cbd5e1;font-weight:bold\">Data Storage</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Disconnected, local spreadsheets & isolated department databases.</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Centralized repository with relational consistency across all units.</td>\n        </tr>\n        <tr>\n            <td style=\"padding:10px;border:1px solid #cbd5e1;font-weight:bold\">Process Flow</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Manual data re-entry, delayed paper handoffs, high human error rates.</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Automated event triggers, seamless inter-departmental handoffs.</td>\n        </tr>\n        <tr>\n            <td style=\"padding:10px;border:1px solid #cbd5e1;font-weight:bold\">Reporting Speed</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Weekly or monthly reconciliation required to balance metrics.</td>\n            <td style=\"padding:10px;border:1px solid #cbd5e1\">Instantaneous real-time institutional dashboards and analytics.</td>\n        </tr>\n    </tbody>\n</table>\n\n<h3>4. Key Takeaways for Assessment</h3>\n<p>As you prepare for the practical assessment, remember that successful enterprise adoption depends on strategic business process reengineering (BPR), clean data governance, and comprehensive user change management.</p>', '5', '0');

-- --------------------------------------------------------
-- Table structure & data for table `query_logs` (3 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `query_logs`;
CREATE TABLE `query_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `query_text` text NOT NULL,
  `query_type` varchar(20) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `affected_rows` int DEFAULT NULL,
  `error_message` text,
  `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

INSERT INTO `query_logs` (`id`, `admin_id`, `admin_name`, `query_text`, `query_type`, `success`, `affected_rows`, `error_message`, `executed_at`) VALUES
('1', '1', 'System Administrator', 'SHOW TABLES;', 'SHOW', '1', '18', NULL, '2026-07-04 00:54:06'),
('2', '1', 'System Administrator', 'SELECT * FROM users LIMIT 50;', 'SELECT', '1', '13', NULL, '2026-07-04 00:54:15'),
('3', '1', 'System Administrator', 'DESCRIBE users;', 'DESCRIBE', '1', '10', NULL, '2026-07-04 00:54:18');

-- --------------------------------------------------------
-- Table structure & data for table `submission_answers` (24 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `submission_answers`;
CREATE TABLE `submission_answers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `submission_id` int NOT NULL,
  `question_id` int NOT NULL,
  `student_answer` text,
  `is_correct` tinyint(1) DEFAULT NULL,
  `points_awarded` decimal(8,2) NOT NULL DEFAULT '0.00',
  `feedback` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sa_sub` (`submission_id`),
  KEY `idx_sa_q` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO `submission_answers` (`id`, `submission_id`, `question_id`, `student_answer`, `is_correct`, `points_awarded`, `feedback`, `created_at`) VALUES
('1', '37', '1', 'B. Integrate core business processes into a unified platform', '1', '5.00', NULL, '2026-10-08 09:32:19'),
('2', '37', '2', 'B. Centralized shared database repository', '1', '5.00', NULL, '2026-10-08 09:32:20'),
('3', '37', '3', 'True', '1', '5.00', NULL, '2026-10-08 09:32:20'),
('4', '37', '4', 'B. Application / Business Logic Layer', '1', '5.00', NULL, '2026-10-08 09:32:20'),
('5', '38', '1', 'B. Integrate core business processes into a unified platform', '1', '5.00', NULL, '2026-10-08 09:32:20'),
('6', '38', '2', 'B. Centralized shared database repository', '1', '5.00', NULL, '2026-10-08 09:32:20'),
('7', '38', '3', 'True', '1', '5.00', NULL, '2026-10-08 09:32:20'),
('8', '38', '4', 'B. Application / Business Logic Layer', '1', '5.00', NULL, '2026-10-08 09:32:20'),
('9', '39', '1', 'B. Integrate core business processes into a unified platform', '1', '5.00', NULL, '2026-10-08 09:32:20'),
('10', '39', '2', 'B. Centralized shared database repository', '1', '5.00', NULL, '2026-10-08 09:32:21'),
('11', '39', '3', 'True', '1', '5.00', NULL, '2026-10-08 09:32:21'),
('12', '39', '4', 'A. Presentation Layer', '0', '0.00', NULL, '2026-10-08 09:32:21'),
('13', '40', '1', 'B. Integrate core business processes into a unified platform', '1', '5.00', NULL, '2026-10-08 09:32:21'),
('14', '40', '2', 'A. Independent department spreadsheets', '0', '0.00', NULL, '2026-10-08 09:32:21'),
('15', '40', '3', 'True', '1', '5.00', NULL, '2026-10-08 09:32:21'),
('16', '40', '4', 'B. Application / Business Logic Layer', '1', '5.00', NULL, '2026-10-08 09:32:21'),
('17', '41', '1', 'B. Integrate core business processes into a unified platform', '1', '5.00', NULL, '2026-10-08 09:32:21'),
('18', '41', '2', 'B. Centralized shared database repository', '1', '5.00', NULL, '2026-10-08 09:32:22'),
('19', '41', '3', 'True', '1', '5.00', NULL, '2026-10-08 09:32:22'),
('20', '41', '4', 'B. Application / Business Logic Layer', '1', '5.00', NULL, '2026-10-08 09:32:22'),
('21', '42', '1', 'A. Isolate department data in independent silos', '0', '0.00', NULL, '2026-10-08 09:32:22'),
('22', '42', '2', 'B. Centralized shared database repository', '1', '5.00', NULL, '2026-10-08 09:32:22'),
('23', '42', '3', 'True', '1', '5.00', NULL, '2026-10-08 09:32:22'),
('24', '42', '4', 'B. Application / Business Logic Layer', '1', '5.00', NULL, '2026-10-08 09:32:22');

-- --------------------------------------------------------
-- Table structure & data for table `submissions` (7 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `submissions`;
CREATE TABLE `submissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assessment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `text_answer` text COLLATE utf8mb4_unicode_ci,
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `graded_at` timestamp NULL DEFAULT NULL,
  `status` enum('submitted','graded','late') COLLATE utf8mb4_unicode_ci DEFAULT 'submitted',
  `is_auto_graded` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `assessment_id` (`assessment_id`),
  KEY `student_id` (`student_id`),
  CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `submissions` (`id`, `assessment_id`, `student_id`, `file_path`, `text_answer`, `score`, `feedback`, `submitted_at`, `graded_at`, `status`, `is_auto_graded`) VALUES
('9', '3', '3', NULL, '', '50.00', '', '2026-03-27 23:20:29', '2026-07-30 10:57:20', 'graded', '0'),
('37', '45', '3', NULL, NULL, '20.00', 'Outstanding mastery! Demonstrated comprehensive understanding of enterprise ERP architecture.', '2026-10-08 07:32:19', '2026-10-08 08:32:19', 'graded', '1'),
('38', '45', '91', NULL, NULL, '20.00', 'Perfect score! Flawless grasp of institutional systems and 3-tier architecture.', '2026-10-08 07:32:20', '2026-10-08 08:32:20', 'graded', '1'),
('39', '45', '92', NULL, NULL, '15.00', 'Good performance overall. Review 3-tier architectural components; the application layer executes business logic.', '2026-10-08 07:32:20', '2026-10-08 08:32:20', 'graded', '1'),
('40', '45', '93', NULL, NULL, '15.00', 'Well done on foundational concepts. Note that ERP systems eliminate independent spreadsheets in favor of a centralized repository.', '2026-10-08 07:32:21', '2026-10-08 08:32:21', 'graded', '1'),
('41', '45', '94', NULL, NULL, '20.00', 'Excellent work! Very accurate breakdown of ERP modules and core business processes.', '2026-10-08 07:32:21', '2026-10-08 08:32:21', 'graded', '1'),
('42', '45', '95', NULL, NULL, '15.00', 'Good effort! Remember that ERP systems connect and unify departments rather than keeping them isolated in silos.', '2026-10-08 07:32:22', '2026-10-08 08:32:22', 'graded', '1');

-- --------------------------------------------------------
-- Table structure & data for table `syllabi` (22 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `syllabi`;
CREATE TABLE `syllabi` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` enum('1st','2nd','Summer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `section_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_description` text COLLATE utf8mb4_unicode_ci,
  `course_outcomes` text COLLATE utf8mb4_unicode_ci,
  `grading_system` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','published','archived') COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `syllabus_file` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `external_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `course_id` (`course_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `syllabi_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `syllabi_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=71 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `syllabi` (`id`, `course_id`, `teacher_id`, `academic_year`, `semester`, `section_name`, `course_description`, `course_outcomes`, `grading_system`, `status`, `image_path`, `syllabus_file`, `external_url`, `created_at`, `updated_at`) VALUES
('6', '44', '17', '2025-2026', '1st', NULL, 'Network', '1.Identify', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-03-22 11:16:19', '2026-07-05 06:06:18'),
('11', '37', '35', '2025-2026', '1st', NULL, 'sdf', 'qwe', '', 'published', NULL, NULL, NULL, '2026-08-06 18:05:24', '2026-08-06 18:06:43'),
('12', '37', '35', '2025-2026', '1st', NULL, 'sdf', 'qwe', '', 'draft', NULL, NULL, NULL, '2026-08-06 18:05:24', '2026-08-06 18:05:24'),
('13', '37', '35', '2025-2026', '1st', NULL, 'sdf', 'qwe', 'wqeqwe', 'published', NULL, NULL, NULL, '2026-08-06 18:05:27', '2026-08-06 18:06:40'),
('14', '36', '37', '2025-2026', '1st', NULL, '123', '123', '', 'published', 'syl_6a74d0c1d93ff.png', NULL, '', '2026-08-06 18:18:12', '2026-08-08 08:34:27'),
('19', '35', '2', '2025-2026', '2nd', NULL, 'Overview of IS concepts and applications', '1. Explain key concepts of Introduction to Information Systems. 2. Apply Introduction to Information Systems principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('20', '38', '17', '2025-2026', '2nd', NULL, 'Object-oriented programming', '1. Explain key concepts of Computer Programming 2. 2. Apply Computer Programming 2 principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('21', '39', '35', '2025-2026', '1st', NULL, 'IS development methodologies', '1. Explain key concepts of Systems Analysis and Design. 2. Apply Systems Analysis and Design principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('22', '40', '37', '2025-2026', '1st', NULL, 'Relational databases and SQL', '1. Explain key concepts of Database Management Systems. 2. Apply Database Management Systems principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('23', '41', '2', '2025-2026', '1st', NULL, 'Web development for IS', '1. Explain key concepts of Web Systems and Technologies. 2. Apply Web Systems and Technologies principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('24', '42', '17', '2025-2026', '2nd', NULL, 'Building enterprise applications', '1. Explain key concepts of Application Development. 2. Apply Application Development principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('25', '43', '35', '2025-2026', '2nd', NULL, 'Data and information resource management', '1. Explain key concepts of Information Management. 2. Apply Information Management principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('26', '45', '37', '2025-2026', '1st', NULL, 'Strategic planning for IS', '1. Explain key concepts of IS Strategy Management and Acquisition. 2. Apply IS Strategy Management and Acquisition principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('27', '46', '2', '2025-2026', '1st', NULL, 'UI/UX design principles', '1. Explain key concepts of Human Computer Interaction. 2. Apply Human Computer Interaction principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('28', '47', '17', '2025-2026', '2nd', NULL, 'Cybersecurity in IS environments', '1. Explain key concepts of Information Assurance and Security. 2. Apply Information Assurance and Security principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('29', '48', '35', '2025-2026', '2nd', NULL, 'Ethics and professionalism in IS', '1. Explain key concepts of Social and Professional Issues in IS. 2. Apply Social and Professional Issues in IS principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('30', '49', '37', '2025-2026', '1st', NULL, 'Enterprise systems integration', '1. Explain key concepts of Systems Integration and Architecture. 2. Apply Systems Integration and Architecture principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('31', '50', '2', '2025-2026', '1st', NULL, 'IS research and project proposal', '1. Explain key concepts of Capstone Project 1. 2. Apply Capstone Project 1 principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('32', '51', '17', '2025-2026', '2nd', NULL, 'IS project implementation and defense', '1. Explain key concepts of Capstone Project 2. 2. Apply Capstone Project 2 principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('33', '52', '35', '2025-2026', '2nd', NULL, 'On-the-job training in IS field', '1. Explain key concepts of Practicum / OJT. 2. Apply Practicum / OJT principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('34', '53', '37', '2025-2026', '2nd', NULL, 'An Enterprise system is a large scale integrated software.', '1. Explain key concepts of Enterprise System. 2. Apply Enterprise System principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
('70', '53', '45', '2025-2026', '2nd', NULL, 'An Enterprise system is a large scale integrated software.', '1. Explain key concepts of Enterprise System.\n2. Apply Enterprise System principles to real-world scenarios.', NULL, 'published', NULL, NULL, NULL, '2026-10-08 08:44:51', '2026-10-08 08:44:51');

-- --------------------------------------------------------
-- Table structure & data for table `syllabus_assignments` (0 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `syllabus_assignments`;
CREATE TABLE `syllabus_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `course_id` int DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` enum('1st','2nd','Summer') COLLATE utf8mb4_unicode_ci DEFAULT '1st',
  `section_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','accepted','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `syllabus_id` int DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `syllabus_id` (`syllabus_id`),
  CONSTRAINT `syllabus_assignments_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `syllabus_templates` (`id`) ON DELETE CASCADE,
  CONSTRAINT `syllabus_assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `syllabus_assignments_ibfk_3` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure & data for table `syllabus_template_topics` (0 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `syllabus_template_topics`;
CREATE TABLE `syllabus_template_topics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `template_id` int NOT NULL,
  `week_number` int NOT NULL,
  `topic_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `learning_outcomes` text COLLATE utf8mb4_unicode_ci,
  `teaching_activities` text COLLATE utf8mb4_unicode_ci,
  `assessment_task` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_mode` enum('face-to-face','online','blended','asynchronous','synchronous') COLLATE utf8mb4_unicode_ci DEFAULT 'blended',
  PRIMARY KEY (`id`),
  KEY `template_id` (`template_id`),
  CONSTRAINT `syllabus_template_topics_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `syllabus_templates` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure & data for table `syllabus_templates` (0 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `syllabus_templates`;
CREATE TABLE `syllabus_templates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credits` int DEFAULT '3',
  `instructor_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_outcomes` text COLLATE utf8mb4_unicode_ci,
  `grading_system` text COLLATE utf8mb4_unicode_ci,
  `resources` text COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `syllabus_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure & data for table `syllabus_topics` (35 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `syllabus_topics`;
CREATE TABLE `syllabus_topics` (
  `id` int NOT NULL AUTO_INCREMENT,
  `syllabus_id` int NOT NULL,
  `week_number` int NOT NULL,
  `topic_title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `topic_description` text COLLATE utf8mb4_unicode_ci,
  `learning_outcomes` text COLLATE utf8mb4_unicode_ci,
  `delivery_mode` enum('face-to-face','online','blended','asynchronous','synchronous') COLLATE utf8mb4_unicode_ci DEFAULT 'blended',
  `online_platform` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resources` text COLLATE utf8mb4_unicode_ci,
  `assessment_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_completed` tinyint(1) NOT NULL DEFAULT '0',
  `completion_notes` text COLLATE utf8mb4_unicode_ci,
  `deletion_requested` tinyint(1) NOT NULL DEFAULT '0',
  `deletion_reason` text COLLATE utf8mb4_unicode_ci,
  `ilo_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blooms_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activity_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `syllabus_id` (`syllabus_id`),
  CONSTRAINT `syllabus_topics_ibfk_1` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `syllabus_topics` (`id`, `syllabus_id`, `week_number`, `topic_title`, `topic_description`, `learning_outcomes`, `delivery_mode`, `online_platform`, `resources`, `assessment_type`, `sort_order`, `created_at`, `is_completed`, `completion_notes`, `deletion_requested`, `deletion_reason`, `ilo_code`, `blooms_level`, `activity_title`) VALUES
('8', '6', '1', 'test', 'test', 'test', 'blended', 'test', 'test', 'test', '0', '2026-04-09 15:46:03', '0', NULL, '0', NULL, NULL, NULL, NULL),
('35', '19', '1', 'Introduction to Introduction to Information Systems', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('36', '19', '2', 'Core Concepts of Introduction to Information Systems', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('37', '20', '1', 'Introduction to Computer Programming 2', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('38', '20', '2', 'Core Concepts of Computer Programming 2', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('39', '21', '1', 'Introduction to Systems Analysis and Design', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('40', '21', '2', 'Core Concepts of Systems Analysis and Design', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('41', '22', '1', 'Introduction to Database Management Systems', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('42', '22', '2', 'Core Concepts of Database Management Systems', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('43', '23', '1', 'Introduction to Web Systems and Technologies', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('44', '23', '2', 'Core Concepts of Web Systems and Technologies', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('45', '24', '1', 'Introduction to Application Development', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('46', '24', '2', 'Core Concepts of Application Development', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('47', '25', '1', 'Introduction to Information Management', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('48', '25', '2', 'Core Concepts of Information Management', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('49', '26', '1', 'Introduction to IS Strategy Management and Acquisition', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('50', '26', '2', 'Core Concepts of IS Strategy Management and Acquisition', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('51', '27', '1', 'Introduction to Human Computer Interaction', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('52', '27', '2', 'Core Concepts of Human Computer Interaction', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('53', '28', '1', 'Introduction to Information Assurance and Security', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('54', '28', '2', 'Core Concepts of Information Assurance and Security', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('55', '29', '1', 'Introduction to Social and Professional Issues in IS', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('56', '29', '2', 'Core Concepts of Social and Professional Issues in IS', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('57', '30', '1', 'Introduction to Systems Integration and Architecture', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('58', '30', '2', 'Core Concepts of Systems Integration and Architecture', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('59', '31', '1', 'Introduction to Capstone Project 1', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('60', '31', '2', 'Core Concepts of Capstone Project 1', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('61', '32', '1', 'Introduction to Capstone Project 2', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('62', '32', '2', 'Core Concepts of Capstone Project 2', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('63', '33', '1', 'Introduction to Practicum / OJT', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('64', '33', '2', 'Core Concepts of Practicum / OJT', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('65', '34', '1', 'Introduction to Enterprise System', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('66', '34', '2', 'Core Concepts of Enterprise System', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-08-09 00:00:00', '0', NULL, '0', NULL, NULL, NULL, NULL),
('100', '70', '1', 'Introduction to Enterprise System', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', '0', '2026-10-08 08:44:52', '0', NULL, '0', NULL, NULL, NULL, NULL),
('101', '70', '2', 'Core Concepts of Enterprise System', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', '1', '2026-10-08 08:44:52', '0', NULL, '0', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------
-- Table structure & data for table `system_settings` (1 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` int NOT NULL DEFAULT '1',
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT '0',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

INSERT INTO `system_settings` (`id`, `maintenance_mode`, `updated_at`) VALUES
('1', '0', '2026-07-07 03:29:46');

-- --------------------------------------------------------
-- Table structure & data for table `topic_done_status` (5 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `topic_done_status`;
CREATE TABLE `topic_done_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `syllabus_id` int NOT NULL,
  `status` tinyint DEFAULT '0',
  `done_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_syllabus` (`teacher_id`,`syllabus_id`)
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `topic_done_status` (`id`, `teacher_id`, `syllabus_id`, `status`, `done_at`) VALUES
('1', '2', '9', '0', '2026-04-10 18:54:12'),
('11', '2', '8', '0', '2026-04-10 18:09:05'),
('19', '2', '7', '0', '2026-04-10 18:09:07'),
('20', '2', '5', '0', '2026-04-10 18:09:09'),
('21', '2', '4', '0', '2026-04-10 18:09:11');

-- --------------------------------------------------------
-- Table structure & data for table `topic_progress` (7 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `topic_progress`;
CREATE TABLE `topic_progress` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `syllabus_topic_id` int NOT NULL,
  `status` enum('not_started','in_progress','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'not_started',
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `read_percentage` decimal(5,2) NOT NULL DEFAULT '0.00',
  `last_read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_progress` (`student_id`,`syllabus_topic_id`),
  UNIQUE KEY `unique_student_topic` (`student_id`,`syllabus_topic_id`),
  KEY `syllabus_topic_id` (`syllabus_topic_id`),
  CONSTRAINT `topic_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `topic_progress_ibfk_2` FOREIGN KEY (`syllabus_topic_id`) REFERENCES `syllabus_topics` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `topic_progress` (`id`, `student_id`, `syllabus_topic_id`, `status`, `completed_at`, `notes`, `read_percentage`, `last_read_at`) VALUES
('21', '3', '8', 'completed', '2026-07-02 16:38:16', NULL, '0.00', NULL),
('72', '3', '100', 'completed', '2026-10-08 09:32:19', NULL, '100.00', '2026-10-08 09:32:19'),
('73', '91', '100', 'completed', '2026-10-08 09:32:19', NULL, '100.00', '2026-10-08 09:32:19'),
('74', '92', '100', 'completed', '2026-10-08 09:32:19', NULL, '95.00', '2026-10-08 09:32:19'),
('75', '93', '100', 'in_progress', NULL, NULL, '85.00', '2026-10-08 09:32:19'),
('76', '94', '100', 'completed', '2026-10-08 09:32:19', NULL, '90.00', '2026-10-08 09:32:19'),
('77', '95', '100', 'completed', '2026-10-08 09:32:19', NULL, '100.00', '2026-10-08 09:32:19');

-- --------------------------------------------------------
-- Table structure & data for table `topic_week_done` (0 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `topic_week_done`;
CREATE TABLE `topic_week_done` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int NOT NULL,
  `topic_id` int NOT NULL,
  `status` tinyint DEFAULT '0',
  `done_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_teacher_topic` (`teacher_id`,`topic_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure & data for table `users` (27 rows)
-- --------------------------------------------------------

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','teacher','student') COLLATE utf8mb4_unicode_ci NOT NULL,
  `profile_pic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `profile_pic`, `status`, `created_at`, `updated_at`) VALUES
('1', 'admin', '$2y$10$DTdHhUtuWmxnJNKILABxeuHoCM4xNur3cbw41o6ZcrX5XulV/BbTq', 'System Administrator', 'jeff.lim111@gmail.com\r\n', 'admin', NULL, 'active', '2026-03-12 01:29:03', '2026-03-27 17:08:41'),
('2', '001', '$2y$10$wDYS9pN2OQdsEWHb6YUKHeChJyb6vqj7xeQnA6vc/wMGfOe7/R0rW', 'Jeff Lim', 'Jeff.Lim@gmail.com', 'teacher', NULL, 'active', '2026-03-12 01:44:13', '2026-03-27 15:18:21'),
('3', 'cristian', '$2y$10$yI.gVxmAwGPz4nwJaenRN.Mp48jF.iH4ybgFrk5RGDlcnGe0OMWy2', 'Cristian Felix Demateo', 'cristiandemateo333@gmail.com', 'student', NULL, 'active', '2026-03-12 01:45:54', '2026-10-08 08:44:51'),
('11', 'Margo', '$2y$10$s5FSqFiO/yc2hL69xj4nB.J4Yu2gFYXPqZGEuaJA.ZKCBp0gCaZIi', 'Margx M. Nomat', 'M.Nomat@gmail.com', 'student', NULL, 'active', '2026-03-14 16:52:27', '2026-03-14 16:52:27'),
('12', 'Shane', '$2y$10$4pE9zoKZekJyUfA7qb1w/eeghpBVRQqk7gBYCxyVQTwHvZA7iaTnq', 'Manang Shane', 'Shane.Bellanio@gmail.com', 'student', NULL, 'active', '2026-03-14 16:53:51', '2026-03-14 16:53:51'),
('13', 'Joy', '$2y$10$Nl.jScE4Z0PAJMUKZdX5Y.0AxE5IVI8Q6mPT9O8dBSmSL9n4FFo2G', 'Joy Deniola', 'Joy.Deniola@gmail.com', 'student', NULL, 'active', '2026-03-14 16:54:22', '2026-03-14 16:54:22'),
('15', 'Chie', '$2y$10$af6d.AuGPAh64B8eP4KVGOrssmX9dhHbHejvWx5oOBSvvxnnKujjW', 'Archie Jalea', 'Chie.Jalea@gmail.com', 'student', NULL, 'active', '2026-03-17 09:02:55', '2026-03-17 09:02:55'),
('16', 'Niel', '$2y$10$mj6nWXhizwfGoDebjfb/xu4rC/1BnugW9LQphb2U4/Iv33VPZxkRi', 'Niel John Marcial', 'Niel.Marcial@gmail.com', 'student', NULL, 'active', '2026-03-17 09:03:42', '2026-03-17 09:03:42'),
('17', 'Dale', '$2y$10$KAoNTXCTAk4CJjQWwgftMuwxPebdGdzq.pAFztb4iyItsd7thYys.', 'Dale Alojado', 'Dale.Alojado@gmail.com', 'teacher', NULL, 'active', '2026-03-22 11:12:57', '2026-03-22 11:12:57'),
('29', 'admin2', '$2y$10$WfoqA.t4AmT4wODqhnH9xuyhARJNRn/ZZBIfiP6jcJAx9oCE7Eq2q', 'Rafael Claveria', 'Rafael.Claveria@gmail.com', 'admin', NULL, 'active', '2026-03-27 15:08:42', '2026-03-27 15:11:56'),
('31', 'Ann', '$2y$10$qYmaQFWNHPoDaxnAEFZx8eI/EB7JmjiyMUazt6/4VEAbVGa5b1qnG', 'Thresia Ann Clamor', 'thresia123@gmail.com', 'student', NULL, 'active', '2026-03-28 05:47:17', '2026-03-28 06:09:05'),
('32', 'Janjan', '$2y$10$6AGBOzMjxfWSV6bWSUzFv.wgYwcBsjy90k7s6BrPDFpjrKXVBIPSu', 'John Michael Escobar', 'janjan@gmail.com', 'student', NULL, 'active', '2026-03-28 05:48:23', '2026-04-08 04:52:04'),
('33', 'winnie', '$2y$10$1/5Fv1c9PhNA4OBtR0FKyuo5K9KUwYekAScGUmaLj76Y3maoMgv2i', 'Andree Alvior', 'andree.alvior@gmail.com', 'student', NULL, 'active', '2026-03-28 06:25:07', '2026-08-06 01:49:50'),
('35', 'q', '$2y$10$fiE0jhZWcc/ePKYIGD3lBuMJ40dg8OJsjs3jjimpRMJAe3AHsoPl2', 'q', 'q@gmail.com', 'teacher', NULL, 'active', '2026-08-06 01:56:19', '2026-08-06 01:56:19'),
('36', 's', '$2y$10$9vm9zFq5USXXdcjpwHq/8OyKimBhN3dSCJzaSHTfR/reQzug1O8tK', 's', 's@gmail.com', 'student', NULL, 'active', '2026-08-06 02:00:06', '2026-08-06 02:00:06'),
('37', 'Kenneth', '$2y$10$myfmT4mwK6G9nzvcYmBjWeZMr2jI1.Y5oKm2MVFHqwTXZtK0eOnva', 'Kenneth Demateo', 'kenneth@gmail.com', 'teacher', NULL, 'active', '2026-08-06 18:10:25', '2026-08-06 18:16:11'),
('38', 'ban', '$2y$10$DCixNZwqCOr//PiS84BTeun5aAgw1zuwPm27m9BWBZstOs4Ue.gSS', 'Ban Demateo', 'ban@gmail.com', 'student', NULL, 'active', '2026-08-06 18:17:20', '2026-08-06 18:17:20'),
('39', 'Albert Buenafe', '$2y$10$xJtQhYeckJsABE.K7gue3OiTNBhIxLZda6UFFrGxAZ2zkp/sD7PZy', 'Albert Buenafe', 'albertbuenafe@gmail.com', 'teacher', NULL, 'active', '2026-10-08 08:44:51', '2026-10-08 08:44:51'),
('40', 'Eazylle Conception', '$2y$10$HLnjO6eXIq.H/urasgfao.VGlm3eRfcYal4.XxqpBsnCRPOO5etUK', 'Eazylle Conception', 'eazylleconception@gmail.com', 'teacher', NULL, 'active', '2026-10-08 08:44:51', '2026-10-08 08:44:51'),
('43', 'Famie', '$2y$10$A3zO0NdSuv4uMFmgn8tPE.OvRYeRWM1jMwuBOPENbfnbRKk1QzAKW', 'Famie Rose Bilbao', 'famierose@gmail.com', 'teacher', NULL, 'active', '2026-10-08 08:44:51', '2026-10-08 08:44:51'),
('44', 'Redgie', '$2y$10$WxzrlaFrph1K9YdGw/OByuVrR9NW5sNakr82shyAtpyZrev0mSzu6', 'Redgie Pomario', 'redgiepomario@gmail.com', 'teacher', NULL, 'active', '2026-10-08 08:44:51', '2026-10-08 08:44:51'),
('45', 'Jeffred', '$2y$10$JuJWhrEYay1Pss4UiJyoyOXA9PEQG7fWZHKcdWvErpxrd9R82jCRi', 'Jeffred Lim', 'jeffredlim@gmail.com', 'teacher', NULL, 'active', '2026-10-08 08:44:51', '2026-10-08 08:44:51'),
('91', 'maria.santos', '$2y$10$5P.vaxbwRZkBdmRgCC1FQeTHEzFtTO45ZK8CFbGoDdxgsWgrUc/eq', 'Maria Angelica Santos', 'maria.santos@student.learnlms.edu', 'student', NULL, 'active', '2026-10-08 09:32:16', '2026-10-08 09:32:16'),
('92', 'joshua.garcia', '$2y$10$5P.vaxbwRZkBdmRgCC1FQeTHEzFtTO45ZK8CFbGoDdxgsWgrUc/eq', 'Joshua Miguel Garcia', 'joshua.garcia@student.learnlms.edu', 'student', NULL, 'active', '2026-10-08 09:32:16', '2026-10-08 09:32:16'),
('93', 'bea.reyes', '$2y$10$5P.vaxbwRZkBdmRgCC1FQeTHEzFtTO45ZK8CFbGoDdxgsWgrUc/eq', 'Bea Louise Reyes', 'bea.reyes@student.learnlms.edu', 'student', NULL, 'active', '2026-10-08 09:32:16', '2026-10-08 09:32:16'),
('94', 'john.cruz', '$2y$10$5P.vaxbwRZkBdmRgCC1FQeTHEzFtTO45ZK8CFbGoDdxgsWgrUc/eq', 'John Kenneth Cruz', 'john.cruz@student.learnlms.edu', 'student', NULL, 'active', '2026-10-08 09:32:16', '2026-10-08 09:32:16'),
('95', 'alyssa.ramos', '$2y$10$5P.vaxbwRZkBdmRgCC1FQeTHEzFtTO45ZK8CFbGoDdxgsWgrUc/eq', 'Alyssa Mae Ramos', 'alyssa.ramos@student.learnlms.edu', 'student', NULL, 'active', '2026-10-08 09:32:17', '2026-10-08 09:32:17');

COMMIT;
SET FOREIGN_KEY_CHECKS=1;
