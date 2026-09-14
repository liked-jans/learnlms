 -- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql305.infinityfree.com
-- Generation Time: Aug 08, 2026 at 07:40 AM
-- Server version: 11.4.12-MariaDB
-- PHP Version: 7.2.22
 
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_42325974_learnlms`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `category` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
(1, 1, 'Logged out', 'Authentication', '2026-07-04 01:38:06'),
(2, 1, 'Logged out', 'Authentication', '2026-07-04 01:49:52'),
(3, 1, 'Logged in', 'Authentication', '2026-07-04 01:50:30'),
(4, 1, 'Logged out', 'Authentication', '2026-07-04 02:40:49'),
(5, 1, 'Logged in', 'Authentication', '2026-07-04 03:19:24'),
(6, 3, 'Logged in', 'Authentication', '2026-07-04 03:45:58'),
(7, 3, 'Auto-logged out (maintenance mode enabled)', 'Authentication', '2026-07-04 03:45:59'),
(8, 1, 'Logged in', 'Authentication', '2026-07-04 03:46:25'),
(9, 1, 'Logged out', 'Authentication', '2026-07-04 08:07:09'),
(10, 1, 'Logged in', 'Authentication', '2026-07-04 08:07:28'),
(11, 1, 'Logged out', 'Authentication', '2026-07-04 08:08:02'),
(12, 1, 'Logged in', 'Authentication', '2026-07-04 08:13:55'),
(13, 1, 'Logged out', 'Authentication', '2026-07-04 08:26:35'),
(14, 2, 'Logged in', 'Authentication', '2026-07-04 08:27:11'),
(15, 1, 'Logged in', 'Authentication', '2026-07-04 12:42:12'),
(16, 1, 'Logged out', 'Authentication', '2026-07-04 12:42:30'),
(17, 2, 'Logged out', 'Authentication', '2026-07-05 02:39:22'),
(18, 3, 'Logged in', 'Authentication', '2026-07-05 02:40:22'),
(19, 3, 'Logged out', 'Authentication', '2026-07-05 02:49:26'),
(20, 1, 'Logged in', 'Authentication', '2026-07-05 02:49:38'),
(21, 1, 'Logged out', 'Authentication', '2026-07-05 02:58:41'),
(22, 1, 'Logged in', 'Authentication', '2026-07-05 02:59:34'),
(23, 1, 'Logged out', 'Authentication', '2026-07-05 02:59:53'),
(24, 3, 'Logged in', 'Authentication', '2026-07-05 03:00:07'),
(25, 3, 'Logged out', 'Authentication', '2026-07-05 03:00:56'),
(26, 1, 'Logged in', 'Authentication', '2026-07-05 03:01:25'),
(27, 1, 'Logged out', 'Authentication', '2026-07-05 03:07:14'),
(28, 2, 'Logged in', 'Authentication', '2026-07-05 03:07:44'),
(29, 1, 'Logged in', 'Authentication', '2026-07-05 03:48:08'),
(30, 1, 'Logged out', 'Authentication', '2026-07-05 03:49:41'),
(31, 2, 'Logged in', 'Authentication', '2026-07-05 03:49:54'),
(32, 2, 'Logged out', 'Authentication', '2026-07-05 04:14:42'),
(33, 2, 'Logged in', 'Authentication', '2026-07-05 04:18:33'),
(34, 2, 'Logged out', 'Authentication', '2026-07-05 05:27:00'),
(35, 1, 'Logged in', 'Authentication', '2026-07-05 05:27:17'),
(36, 1, 'Logged out', 'Authentication', '2026-07-05 05:30:02'),
(37, 3, 'Logged in', 'Authentication', '2026-07-05 05:30:20'),
(38, 3, 'Logged out', 'Authentication', '2026-07-05 05:30:35'),
(39, 2, 'Logged in', 'Authentication', '2026-07-05 05:31:05'),
(40, 2, 'Logged out', 'Authentication', '2026-07-05 05:33:13'),
(41, 1, 'Logged in', 'Authentication', '2026-07-05 05:35:02'),
(42, 3, 'Logged in', 'Authentication', '2026-07-05 05:35:48'),
(43, 3, 'Auto-logged out (maintenance mode enabled)', 'Authentication', '2026-07-05 05:35:48'),
(44, 2, 'Logged in', 'Authentication', '2026-07-05 05:36:24'),
(45, 2, 'Auto-logged out (maintenance mode enabled)', 'Authentication', '2026-07-05 05:36:25'),
(46, 2, 'Logged in', 'Authentication', '2026-07-05 05:36:57'),
(47, 1, 'Logged out', 'Authentication', '2026-07-05 06:01:21'),
(48, 3, 'Logged in', 'Authentication', '2026-07-05 06:01:35'),
(49, 3, 'Logged out', 'Authentication', '2026-07-05 06:03:11'),
(50, 2, 'Logged in', 'Authentication', '2026-07-05 06:03:22'),
(51, 2, 'Logged out', 'Authentication', '2026-07-05 06:05:12'),
(52, 17, 'Logged in', 'Authentication', '2026-07-05 06:05:26'),
(53, 17, 'Logged out', 'Authentication', '2026-07-05 06:07:02'),
(54, 3, 'Logged in', 'Authentication', '2026-07-05 06:07:20'),
(55, 3, 'Logged in', 'Authentication', '2026-07-07 03:27:43'),
(56, 3, 'Logged out', 'Authentication', '2026-07-07 03:29:18'),
(57, 1, 'Logged in', 'Authentication', '2026-07-07 03:29:33'),
(58, 1, 'Logged out', 'Authentication', '2026-07-07 08:27:28'),
(59, 1, 'Logged in', 'Authentication', '2026-07-07 08:27:43'),
(60, 1, 'Logged out', 'Authentication', '2026-07-07 08:28:22'),
(61, 2, 'Logged in', 'Authentication', '2026-07-07 12:48:50'),
(62, 2, 'Logged out', 'Authentication', '2026-07-07 12:51:35'),
(63, 1, 'Logged in', 'Authentication', '2026-07-13 10:07:06'),
(64, 1, 'Logged out', 'Authentication', '2026-07-13 10:46:47'),
(65, 12, 'Logged in', 'Authentication', '2026-07-13 10:47:42'),
(66, 12, 'Logged out', 'Authentication', '2026-07-13 11:50:53'),
(67, 1, 'Logged in', 'Authentication', '2026-07-13 11:51:07'),
(68, 1, 'Logged in', 'Authentication', '2026-07-18 07:44:18'),
(69, 1, 'Logged in', 'Authentication', '2026-07-29 12:24:04'),
(70, 1, 'Logged out', 'Authentication', '2026-07-29 12:26:14'),
(71, 1, 'Logged in', 'Authentication', '2026-07-30 10:55:10'),
(72, 1, 'Logged out', 'Authentication', '2026-07-30 10:56:45'),
(73, 17, 'Logged in', 'Authentication', '2026-07-30 10:57:11'),
(74, 17, 'Logged out', 'Authentication', '2026-07-30 10:57:49'),
(75, 3, 'Logged in', 'Authentication', '2026-07-30 10:57:56'),
(76, 1, 'Logged in', 'Authentication', '2026-07-30 11:01:07'),
(77, 1, 'Logged in', 'Authentication', '2026-07-30 12:24:13'),
(78, 1, 'Logged in', 'Authentication', '2026-08-01 06:45:47'),
(79, 1, 'Logged in', 'Authentication', '2026-08-05 06:52:53'),
(80, 1, 'Logged in', 'Authentication', '2026-08-05 09:15:02'),
(81, 1, 'Logged in', 'Authentication', '2026-08-05 14:36:15'),
(82, 1, 'Logged out', 'Authentication', '2026-08-05 14:36:42'),
(83, 1, 'Logged in', 'Authentication', '2026-08-05 15:23:12'),
(84, 1, 'Logged out', 'Authentication', '2026-08-05 15:24:59'),
(85, 34, 'Logged in', 'Authentication', '2026-08-05 15:25:20'),
(86, 34, 'Logged out', 'Authentication', '2026-08-05 15:25:56'),
(87, 1, 'Logged in', 'Authentication', '2026-08-05 15:26:13'),
(88, 1, 'Logged out', 'Authentication', '2026-08-05 15:26:48'),
(89, 2, 'Logged in', 'Authentication', '2026-08-05 15:31:16'),
(90, 3, 'Logged in', 'Authentication', '2026-08-05 15:31:17'),
(91, 1, 'Logged out', 'Authentication', '2026-08-05 18:39:22'),
(92, 2, 'Logged in', 'Authentication', '2026-08-05 18:40:09'),
(93, 1, 'Logged in', 'Authentication', '2026-08-06 01:49:06'),
(94, 35, 'Logged in', 'Authentication', '2026-08-06 01:56:43'),
(95, 2, 'Logged out', 'Authentication', '2026-08-06 01:59:32'),
(96, 2, 'Logged in', 'Authentication', '2026-08-06 01:59:54'),
(97, 35, 'Logged out', 'Authentication', '2026-08-06 02:00:13'),
(98, 36, 'Logged in', 'Authentication', '2026-08-06 02:00:50'),
(99, 2, 'Logged out', 'Authentication', '2026-08-06 02:02:00'),
(100, 3, 'Logged in', 'Authentication', '2026-08-06 02:02:10'),
(101, 36, 'Logged out', 'Authentication', '2026-08-06 02:03:00'),
(102, 36, 'Logged in', 'Authentication', '2026-08-06 02:05:10'),
(103, 3, 'Logged in', 'Authentication', '2026-08-06 02:08:58'),
(104, 1, 'Logged out', 'Authentication', '2026-08-06 02:10:31'),
(105, 3, 'Logged out', 'Authentication', '2026-08-06 02:11:02'),
(106, 2, 'Logged in', 'Authentication', '2026-08-06 02:11:57'),
(107, 36, 'Logged in', 'Authentication', '2026-08-06 02:12:08'),
(108, 36, 'Logged out', 'Authentication', '2026-08-06 02:13:41'),
(109, 3, 'Logged in', 'Authentication', '2026-08-06 02:13:51'),
(110, 2, 'Logged out', 'Authentication', '2026-08-06 02:14:55'),
(111, 36, 'Logged in', 'Authentication', '2026-08-06 02:15:21'),
(112, 36, 'Logged out', 'Authentication', '2026-08-06 02:15:59'),
(113, 35, 'Logged in', 'Authentication', '2026-08-06 02:16:19'),
(114, 3, 'Logged out', 'Authentication', '2026-08-06 02:19:45'),
(115, 35, 'Logged in', 'Authentication', '2026-08-06 02:20:07'),
(116, 35, 'Logged out', 'Authentication', '2026-08-06 18:06:56'),
(117, 3, 'Logged in', 'Authentication', '2026-08-06 18:07:57'),
(118, 3, 'Logged out', 'Authentication', '2026-08-06 18:08:09'),
(119, 1, 'Logged in', 'Authentication', '2026-08-06 18:08:22'),
(120, 1, 'Logged out', 'Authentication', '2026-08-06 18:17:34'),
(121, 37, 'Logged in', 'Authentication', '2026-08-06 18:17:51'),
(122, 37, 'Logged out', 'Authentication', '2026-08-06 18:22:50'),
(123, 38, 'Logged in', 'Authentication', '2026-08-06 18:23:05'),
(124, 38, 'Logged out', 'Authentication', '2026-08-06 18:23:47'),
(125, 37, 'Logged in', 'Authentication', '2026-08-06 18:24:08'),
(126, 37, 'Logged out', 'Authentication', '2026-08-06 18:35:41'),
(127, 2, 'Logged in', 'Authentication', '2026-08-06 18:35:50'),
(128, 2, 'Logged in', 'Authentication', '2026-08-06 18:47:21'),
(129, 2, 'Logged out', 'Authentication', '2026-08-06 20:19:54'),
(130, 3, 'Logged in', 'Authentication', '2026-08-06 20:20:06'),
(131, 2, 'Logged in', 'Authentication', '2026-08-07 12:37:04'),
(132, 2, 'Logged out', 'Authentication', '2026-08-07 14:12:42'),
(133, 3, 'Logged in', 'Authentication', '2026-08-07 14:13:00'),
(134, 3, 'Logged out', 'Authentication', '2026-08-07 14:14:34'),
(135, 2, 'Logged in', 'Authentication', '2026-08-07 14:14:46'),
(136, 2, 'Logged out', 'Authentication', '2026-08-07 14:19:19'),
(137, 3, 'Logged in', 'Authentication', '2026-08-07 14:19:41'),
(138, 3, 'Logged out', 'Authentication', '2026-08-07 14:21:52'),
(139, 2, 'Logged in', 'Authentication', '2026-08-07 14:22:03'),
(140, 2, 'Logged out', 'Authentication', '2026-08-07 14:24:36'),
(141, 3, 'Logged in', 'Authentication', '2026-08-07 14:25:11'),
(142, 1, 'Logged in', 'Authentication', '2026-08-07 14:31:16'),
(143, 1, 'Logged out', 'Authentication', '2026-08-07 14:31:38'),
(144, 3, 'Logged in', 'Authentication', '2026-08-07 14:31:48'),
(145, 3, 'Logged out', 'Authentication', '2026-08-07 14:32:11'),
(146, 2, 'Logged in', 'Authentication', '2026-08-07 14:32:24'),
(147, 1, 'Logged in', 'Authentication', '2026-08-08 03:23:29'),
(148, 1, 'Logged out', 'Authentication', '2026-08-08 03:30:30'),
(149, 2, 'Logged in', 'Authentication', '2026-08-08 03:30:56'),
(150, 2, 'Logged out', 'Authentication', '2026-08-08 03:42:32'),
(151, 3, 'Logged in', 'Authentication', '2026-08-08 03:42:47'),
(152, 3, 'Logged out', 'Authentication', '2026-08-08 03:49:03'),
(153, 1, 'Logged in', 'Authentication', '2026-08-08 03:50:00'),
(154, 1, 'Logged out', 'Authentication', '2026-08-08 05:28:51'),
(155, 1, 'Logged in', 'Authentication', '2026-08-08 05:40:31'),
(156, 1, 'Logged out', 'Authentication', '2026-08-08 05:41:18'),
(157, 2, 'Logged in', 'Authentication', '2026-08-08 06:12:29'),
(158, 2, 'Logged out', 'Authentication', '2026-08-08 06:19:14'),
(159, 1, 'Logged in', 'Authentication', '2026-08-08 06:20:04'),
(160, 1, 'Logged out', 'Authentication', '2026-08-08 06:21:36'),
(161, 3, 'Logged in', 'Authentication', '2026-08-08 06:21:50'),
(162, 3, 'Logged out', 'Authentication', '2026-08-08 07:08:20'),
(163, 1, 'Logged in', 'Authentication', '2026-08-08 08:05:56'),
(164, 1, 'Logged out', 'Authentication', '2026-08-08 08:08:29'),
(165, 2, 'Logged in', 'Authentication', '2026-08-08 08:08:41'),
(166, 2, 'Logged out', 'Authentication', '2026-08-08 08:12:19'),
(167, 2, 'Logged in', 'Authentication', '2026-08-08 08:12:47'),
(168, 2, 'Logged out', 'Authentication', '2026-08-08 08:17:50'),
(169, 2, 'Logged in', 'Authentication', '2026-08-08 08:18:20'),
(170, 3, 'Logged in', 'Authentication', '2026-08-08 08:19:12'),
(171, 36, 'Logged in', 'Authentication', '2026-08-08 08:27:16'),
(172, 36, 'Logged out', 'Authentication', '2026-08-08 08:27:55'),
(173, 3, 'Logged in', 'Authentication', '2026-08-08 08:28:31'),
(174, 2, 'Logged in', 'Authentication', '2026-08-08 08:29:36'),
(175, 2, 'Logged in', 'Authentication', '2026-08-08 08:31:36'),
(176, 1, 'Logged in', 'Authentication', '2026-08-08 08:34:06'),
(177, 1, 'Logged out', 'Authentication', '2026-08-08 11:30:24'),
(178, 2, 'Logged in', 'Authentication', '2026-08-08 11:30:40');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `author_id` int(11) NOT NULL,
  `syllabus_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_role` enum('all','teacher','student') DEFAULT 'all',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `author_id`, `syllabus_id`, `title`, `content`, `target_role`, `created_at`) VALUES
(4, 1, NULL, 'Exam', 'Exam will be on April 6-8', 'all', '2026-03-25 09:30:21'),
(7, 1, NULL, '1234', '123435465', 'all', '2026-08-08 03:24:45'),
(8, 1, NULL, 'asd', 'asd', 'teacher', '2026-08-08 03:25:00'),
(9, 1, NULL, 'asd', 'asd', 'teacher', '2026-08-08 03:25:00'),
(10, 1, NULL, 'asd', 'asd', 'teacher', '2026-08-08 03:25:03');

-- --------------------------------------------------------

--
-- Table structure for table `assessments`
--

CREATE TABLE `assessments` (
  `id` int(11) NOT NULL,
  `syllabus_id` int(11) NOT NULL,
  `topic_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('quiz','assignment','exam','project','activity') DEFAULT 'quiz',
  `max_score` decimal(5,2) DEFAULT 100.00,
  `due_date` datetime DEFAULT NULL,
  `is_closed` tinyint(1) NOT NULL DEFAULT 0,
  `delivery_mode` enum('online','offline','both') DEFAULT 'both',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `syllabus_id`, `topic_id`, `teacher_id`, `title`, `description`, `type`, `max_score`, `due_date`, `is_closed`, `delivery_mode`, `created_at`) VALUES
(3, 6, NULL, 17, 'Manage', 'Network Management', 'activity', '100.00', '2026-03-28 07:19:00', 0, 'both', '2026-03-27 23:19:56'),
(10, 19, 35, 2, 'BSIS101 Quiz 1', 'Overview of IS concepts and applications', 'quiz', '100.00', '2026-09-15 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(11, 20, 37, 17, 'BSIS104 Assignment 1', 'Object-oriented programming', 'assignment', '100.00', '2026-09-16 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(12, 21, 39, 35, 'BSIS105 Exam 1', 'IS development methodologies', 'exam', '100.00', '2026-09-17 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(13, 22, 41, 37, 'BSIS106 Project 1', 'Relational databases and SQL', 'project', '100.00', '2026-09-18 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(14, 23, 43, 2, 'BSIS107 Activity 1', 'Web development for IS', 'activity', '100.00', '2026-09-19 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(15, 24, 45, 17, 'BSIS108 Quiz 1', 'Building enterprise applications', 'quiz', '100.00', '2026-09-20 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(16, 25, 47, 35, 'BSIS109 Assignment 1', 'Data and information resource management', 'assignment', '100.00', '2026-09-21 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(17, 26, 49, 37, 'BSIS111 Exam 1', 'Strategic planning for IS', 'exam', '100.00', '2026-09-22 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(18, 27, 51, 2, 'BSIS112 Project 1', 'UI/UX design principles', 'project', '100.00', '2026-09-23 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(19, 28, 53, 17, 'BSIS113 Activity 1', 'Cybersecurity in IS environments', 'activity', '100.00', '2026-09-24 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(20, 29, 55, 35, 'BSIS114 Quiz 1', 'Ethics and professionalism in IS', 'quiz', '100.00', '2026-09-25 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(21, 30, 57, 37, 'BSIS115 Assignment 1', 'Enterprise systems integration', 'assignment', '100.00', '2026-09-26 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(22, 31, 59, 2, 'BSIS116 Exam 1', 'IS research and project proposal', 'exam', '100.00', '2026-09-27 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(23, 32, 61, 17, 'BSIS117 Project 1', 'IS project implementation and defense', 'project', '100.00', '2026-09-28 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(24, 33, 63, 35, 'BSIS118 Activity 1', 'On-the-job training in IS field', 'activity', '100.00', '2026-09-29 23:59:00', 0, 'both', '2026-08-09 00:00:00'),
(25, 34, 65, 37, 'ENT123 Quiz 1', 'An Enterprise system is a large scale integrated software.', 'quiz', '100.00', '2026-09-30 23:59:00', 0, 'both', '2026-08-09 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `units` int(11) DEFAULT 3,
  `year_level` int(11) DEFAULT 1,
  `semester` enum('1st','2nd','Summer') DEFAULT '1st',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`id`, `department_id`, `course_code`, `course_name`, `description`, `units`, `year_level`, `semester`, `status`, `created_at`) VALUES
(35, 5, 'BSIS101', 'Introduction to Information Systems', 'Overview of IS concepts and applications', 3, 2, '2nd', 'active', '2026-03-12 02:04:42'),
(36, 5, 'BSIS102', 'Computer Programming 1', 'Fundamentals of programming', 3, 1, '1st', 'active', '2026-03-12 02:04:42'),
(37, 5, 'BSIS103', 'Mathematics in the Modern World', 'Applied mathematics for IS students', 3, 1, '1st', 'active', '2026-03-12 02:04:42'),
(38, 5, 'BSIS104', 'Computer Programming 2', 'Object-oriented programming', 3, 1, '2nd', 'active', '2026-03-12 02:04:42'),
(39, 5, 'BSIS105', 'Systems Analysis and Design', 'IS development methodologies', 3, 2, '1st', 'active', '2026-03-12 02:04:42'),
(40, 5, 'BSIS106', 'Database Management Systems', 'Relational databases and SQL', 3, 2, '1st', 'active', '2026-03-12 02:04:42'),
(41, 5, 'BSIS107', 'Web Systems and Technologies', 'Web development for IS', 3, 2, '1st', 'active', '2026-03-12 02:04:43'),
(42, 5, 'BSIS108', 'Application Development', 'Building enterprise applications', 3, 2, '2nd', 'active', '2026-03-12 02:04:43'),
(43, 5, 'BSIS109', 'Information Management', 'Data and information resource management', 3, 2, '2nd', 'active', '2026-03-12 02:04:43'),
(44, 5, 'BSIS110', 'Network Management', 'Network design and administration', 3, 3, '1st', 'active', '2026-03-12 02:04:43'),
(45, 5, 'BSIS111', 'IS Strategy Management and Acquisition', 'Strategic planning for IS', 3, 3, '1st', 'active', '2026-03-12 02:04:43'),
(46, 5, 'BSIS112', 'Human Computer Interaction', 'UI/UX design principles', 3, 3, '1st', 'active', '2026-03-12 02:04:43'),
(47, 5, 'BSIS113', 'Information Assurance and Security', 'Cybersecurity in IS environments', 3, 3, '2nd', 'active', '2026-03-12 02:04:43'),
(48, 5, 'BSIS114', 'Social and Professional Issues in IS', 'Ethics and professionalism in IS', 3, 3, '2nd', 'active', '2026-03-12 02:04:43'),
(49, 5, 'BSIS115', 'Systems Integration and Architecture', 'Enterprise systems integration', 3, 4, '1st', 'active', '2026-03-12 02:04:43'),
(50, 5, 'BSIS116', 'Capstone Project 1', 'IS research and project proposal', 3, 4, '1st', 'active', '2026-03-12 02:04:43'),
(51, 5, 'BSIS117', 'Capstone Project 2', 'IS project implementation and defense', 3, 4, '2nd', 'active', '2026-03-12 02:04:43'),
(52, 5, 'BSIS118', 'Practicum / OJT', 'On-the-job training in IS field', 6, 4, '2nd', 'active', '2026-03-12 02:04:43'),
(53, 5, 'ENT123', 'ENTERPRISE SYSTEM', 'An Enterprise system is a large scale integrated software.', 3, 3, '2nd', 'active', '2026-03-28 06:00:33');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`, `code`, `description`, `created_at`) VALUES
(5, 'Bachelor of Science and Information System', 'BSIS', '', '2026-03-12 01:57:14');

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `syllabus_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('enrolled','dropped','completed') DEFAULT 'enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`id`, `student_id`, `syllabus_id`, `enrolled_at`, `status`) VALUES
(15, 3, 6, '2026-03-27 23:18:33', 'enrolled'),
(20, 3, 11, '2026-08-06 18:06:10', 'enrolled'),
(22, 38, 14, '2026-08-06 18:22:29', 'enrolled'),
(26, 3, 19, '2026-08-09 00:00:00', 'enrolled'),
(27, 11, 19, '2026-08-09 00:00:00', 'enrolled'),
(28, 12, 20, '2026-08-09 00:00:00', 'enrolled'),
(29, 13, 20, '2026-08-09 00:00:00', 'enrolled'),
(30, 15, 21, '2026-08-09 00:00:00', 'enrolled'),
(31, 16, 21, '2026-08-09 00:00:00', 'enrolled'),
(32, 31, 22, '2026-08-09 00:00:00', 'enrolled'),
(33, 32, 22, '2026-08-09 00:00:00', 'enrolled'),
(34, 33, 23, '2026-08-09 00:00:00', 'enrolled'),
(35, 36, 23, '2026-08-09 00:00:00', 'enrolled'),
(36, 38, 24, '2026-08-09 00:00:00', 'enrolled'),
(37, 3, 24, '2026-08-09 00:00:00', 'enrolled'),
(38, 11, 25, '2026-08-09 00:00:00', 'enrolled'),
(39, 12, 25, '2026-08-09 00:00:00', 'enrolled'),
(40, 13, 26, '2026-08-09 00:00:00', 'enrolled'),
(41, 15, 26, '2026-08-09 00:00:00', 'enrolled'),
(42, 16, 27, '2026-08-09 00:00:00', 'enrolled'),
(43, 31, 27, '2026-08-09 00:00:00', 'enrolled'),
(44, 32, 28, '2026-08-09 00:00:00', 'enrolled'),
(45, 33, 28, '2026-08-09 00:00:00', 'enrolled'),
(46, 36, 29, '2026-08-09 00:00:00', 'enrolled'),
(47, 38, 29, '2026-08-09 00:00:00', 'enrolled'),
(48, 3, 30, '2026-08-09 00:00:00', 'enrolled'),
(49, 11, 30, '2026-08-09 00:00:00', 'enrolled'),
(50, 12, 31, '2026-08-09 00:00:00', 'enrolled'),
(51, 13, 31, '2026-08-09 00:00:00', 'enrolled'),
(52, 15, 32, '2026-08-09 00:00:00', 'enrolled'),
(53, 16, 32, '2026-08-09 00:00:00', 'enrolled'),
(54, 31, 33, '2026-08-09 00:00:00', 'enrolled'),
(55, 32, 33, '2026-08-09 00:00:00', 'enrolled'),
(56, 33, 34, '2026-08-09 00:00:00', 'enrolled'),
(57, 36, 34, '2026-08-09 00:00:00', 'enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `learning_materials`
--

CREATE TABLE `learning_materials` (
  `id` int(11) NOT NULL,
  `syllabus_topic_id` int(11) DEFAULT NULL,
  `syllabus_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('document','video','link','presentation','quiz','activity') DEFAULT 'document',
  `file_path` varchar(255) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `delivery_mode` enum('online','offline','both') DEFAULT 'both',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `learning_materials`
--

INSERT INTO `learning_materials` (`id`, `syllabus_topic_id`, `syllabus_id`, `teacher_id`, `title`, `description`, `type`, `file_path`, `external_url`, `delivery_mode`, `created_at`) VALUES
(5, 8, 6, 17, 'test', 'dwewfgetgrht', 'document', 'mat_6a4792806c992.pdf', '', 'both', '2026-07-03 10:44:16');

-- --------------------------------------------------------

--
-- Table structure for table `query_logs`
--

CREATE TABLE `query_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `admin_name` varchar(100) NOT NULL,
  `query_text` text NOT NULL,
  `query_type` varchar(20) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `affected_rows` int(11) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `executed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `query_logs`
--

INSERT INTO `query_logs` (`id`, `admin_id`, `admin_name`, `query_text`, `query_type`, `success`, `affected_rows`, `error_message`, `executed_at`) VALUES
(1, 1, 'System Administrator', 'SHOW TABLES;', 'SHOW', 1, 18, NULL, '2026-07-04 00:54:06'),
(2, 1, 'System Administrator', 'SELECT * FROM users LIMIT 50;', 'SELECT', 1, 13, NULL, '2026-07-04 00:54:15'),
(3, 1, 'System Administrator', 'DESCRIBE users;', 'DESCRIBE', 1, 10, NULL, '2026-07-04 00:54:18');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `text_answer` text DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `graded_at` timestamp NULL DEFAULT NULL,
  `status` enum('submitted','graded','late') DEFAULT 'submitted'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `submissions`
--

INSERT INTO `submissions` (`id`, `assessment_id`, `student_id`, `file_path`, `text_answer`, `score`, `feedback`, `submitted_at`, `graded_at`, `status`) VALUES
(9, 3, 3, NULL, '', '50.00', '', '2026-03-27 23:20:29', '2026-07-30 10:57:20', 'graded');

-- --------------------------------------------------------

--
-- Table structure for table `syllabi`
--

CREATE TABLE `syllabi` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` enum('1st','2nd','Summer') NOT NULL,
  `section_name` varchar(100) DEFAULT NULL,
  `course_description` text DEFAULT NULL,
  `course_outcomes` text DEFAULT NULL,
  `grading_system` text DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `image_path` varchar(255) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `syllabi`
--

INSERT INTO `syllabi` (`id`, `course_id`, `teacher_id`, `academic_year`, `semester`, `course_description`, `course_outcomes`, `grading_system`, `status`, `image_path`, `external_url`, `created_at`, `updated_at`) VALUES
(6, 44, 17, '2025-2026', '1st', 'Network', '1.Identify', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-03-22 11:16:19', '2026-07-05 06:06:18'),
(11, 37, 35, '2025-2026', '1st', 'sdf', 'qwe', '', 'published', NULL, NULL, '2026-08-06 18:05:24', '2026-08-06 18:06:43'),
(12, 37, 35, '2025-2026', '1st', 'sdf', 'qwe', '', 'draft', NULL, NULL, '2026-08-06 18:05:24', '2026-08-06 18:05:24'),
(13, 37, 35, '2025-2026', '1st', 'sdf', 'qwe', 'wqeqwe', 'published', NULL, NULL, '2026-08-06 18:05:27', '2026-08-06 18:06:40'),
(14, 36, 37, '2025-2026', '1st', '123', '123', '', 'published', 'syl_6a74d0c1d93ff.png', '', '2026-08-06 18:18:12', '2026-08-08 08:34:27'),
(19, 35, 2, '2025-2026', '2nd', 'Overview of IS concepts and applications', '1. Explain key concepts of Introduction to Information Systems. 2. Apply Introduction to Information Systems principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(20, 38, 17, '2025-2026', '2nd', 'Object-oriented programming', '1. Explain key concepts of Computer Programming 2. 2. Apply Computer Programming 2 principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(21, 39, 35, '2025-2026', '1st', 'IS development methodologies', '1. Explain key concepts of Systems Analysis and Design. 2. Apply Systems Analysis and Design principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(22, 40, 37, '2025-2026', '1st', 'Relational databases and SQL', '1. Explain key concepts of Database Management Systems. 2. Apply Database Management Systems principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(23, 41, 2, '2025-2026', '1st', 'Web development for IS', '1. Explain key concepts of Web Systems and Technologies. 2. Apply Web Systems and Technologies principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(24, 42, 17, '2025-2026', '2nd', 'Building enterprise applications', '1. Explain key concepts of Application Development. 2. Apply Application Development principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(25, 43, 35, '2025-2026', '2nd', 'Data and information resource management', '1. Explain key concepts of Information Management. 2. Apply Information Management principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(26, 45, 37, '2025-2026', '1st', 'Strategic planning for IS', '1. Explain key concepts of IS Strategy Management and Acquisition. 2. Apply IS Strategy Management and Acquisition principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(27, 46, 2, '2025-2026', '1st', 'UI/UX design principles', '1. Explain key concepts of Human Computer Interaction. 2. Apply Human Computer Interaction principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(28, 47, 17, '2025-2026', '2nd', 'Cybersecurity in IS environments', '1. Explain key concepts of Information Assurance and Security. 2. Apply Information Assurance and Security principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(29, 48, 35, '2025-2026', '2nd', 'Ethics and professionalism in IS', '1. Explain key concepts of Social and Professional Issues in IS. 2. Apply Social and Professional Issues in IS principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(30, 49, 37, '2025-2026', '1st', 'Enterprise systems integration', '1. Explain key concepts of Systems Integration and Architecture. 2. Apply Systems Integration and Architecture principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(31, 50, 2, '2025-2026', '1st', 'IS research and project proposal', '1. Explain key concepts of Capstone Project 1. 2. Apply Capstone Project 1 principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(32, 51, 17, '2025-2026', '2nd', 'IS project implementation and defense', '1. Explain key concepts of Capstone Project 2. 2. Apply Capstone Project 2 principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(33, 52, 35, '2025-2026', '2nd', 'On-the-job training in IS field', '1. Explain key concepts of Practicum / OJT. 2. Apply Practicum / OJT principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00'),
(34, 53, 37, '2025-2026', '2nd', 'An Enterprise system is a large scale integrated software.', '1. Explain key concepts of Enterprise System. 2. Apply Enterprise System principles to real-world scenarios.', 'quiz20% \\r\\nactivity30%\\r\\nexams50%', 'published', NULL, '', '2026-08-09 00:00:00', '2026-08-09 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `syllabus_assignments`
--

CREATE TABLE `syllabus_assignments` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` enum('1st','2nd','Summer') DEFAULT '1st',
  `section_name` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','accepted','rejected') DEFAULT 'pending',
  `syllabus_id` int(11) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `syllabus_templates`
--

CREATE TABLE `syllabus_templates` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(200) NOT NULL,
  `program` varchar(100) DEFAULT NULL,
  `credits` int(11) DEFAULT 3,
  `instructor_name` varchar(100) DEFAULT NULL,
  `course_outcomes` text DEFAULT NULL,
  `grading_system` text DEFAULT NULL,
  `resources` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `syllabus_template_topics`
--

CREATE TABLE `syllabus_template_topics` (
  `id` int(11) NOT NULL,
  `template_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `topic_title` varchar(200) NOT NULL,
  `learning_outcomes` text DEFAULT NULL,
  `teaching_activities` text DEFAULT NULL,
  `assessment_task` varchar(200) DEFAULT NULL,
  `delivery_mode` enum('face-to-face','online','blended','asynchronous','synchronous') DEFAULT 'blended'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `syllabus_topics`
--

CREATE TABLE `syllabus_topics` (
  `id` int(11) NOT NULL,
  `syllabus_id` int(11) NOT NULL,
  `week_number` int(11) NOT NULL,
  `topic_title` varchar(200) NOT NULL,
  `topic_description` text DEFAULT NULL,
  `learning_outcomes` text DEFAULT NULL,
  `delivery_mode` enum('face-to-face','online','blended','asynchronous','synchronous') DEFAULT 'blended',
  `online_platform` varchar(100) DEFAULT NULL,
  `resources` text DEFAULT NULL,
  `assessment_type` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `syllabus_topics`
--

INSERT INTO `syllabus_topics` (`id`, `syllabus_id`, `week_number`, `topic_title`, `topic_description`, `learning_outcomes`, `delivery_mode`, `online_platform`, `resources`, `assessment_type`, `sort_order`, `created_at`) VALUES
(8, 6, 1, 'test', 'test', 'test', 'blended', 'test', 'test', 'test', 0, '2026-04-09 15:46:03'),
(35, 19, 1, 'Introduction to Introduction to Information Systems', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(36, 19, 2, 'Core Concepts of Introduction to Information Systems', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(37, 20, 1, 'Introduction to Computer Programming 2', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(38, 20, 2, 'Core Concepts of Computer Programming 2', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(39, 21, 1, 'Introduction to Systems Analysis and Design', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(40, 21, 2, 'Core Concepts of Systems Analysis and Design', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(41, 22, 1, 'Introduction to Database Management Systems', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(42, 22, 2, 'Core Concepts of Database Management Systems', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(43, 23, 1, 'Introduction to Web Systems and Technologies', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(44, 23, 2, 'Core Concepts of Web Systems and Technologies', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(45, 24, 1, 'Introduction to Application Development', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(46, 24, 2, 'Core Concepts of Application Development', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(47, 25, 1, 'Introduction to Information Management', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(48, 25, 2, 'Core Concepts of Information Management', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(49, 26, 1, 'Introduction to IS Strategy Management and Acquisition', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(50, 26, 2, 'Core Concepts of IS Strategy Management and Acquisition', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(51, 27, 1, 'Introduction to Human Computer Interaction', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(52, 27, 2, 'Core Concepts of Human Computer Interaction', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(53, 28, 1, 'Introduction to Information Assurance and Security', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(54, 28, 2, 'Core Concepts of Information Assurance and Security', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(55, 29, 1, 'Introduction to Social and Professional Issues in IS', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(56, 29, 2, 'Core Concepts of Social and Professional Issues in IS', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(57, 30, 1, 'Introduction to Systems Integration and Architecture', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(58, 30, 2, 'Core Concepts of Systems Integration and Architecture', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(59, 31, 1, 'Introduction to Capstone Project 1', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(60, 31, 2, 'Core Concepts of Capstone Project 1', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(61, 32, 1, 'Introduction to Capstone Project 2', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(62, 32, 2, 'Core Concepts of Capstone Project 2', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(63, 33, 1, 'Introduction to Practicum / OJT', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(64, 33, 2, 'Core Concepts of Practicum / OJT', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00'),
(65, 34, 1, 'Introduction to Enterprise System', 'Overview of key concepts and scope of the course.', 'Identify the core concepts of the subject.', 'blended', 'Google Classroom', 'Course syllabus, lecture slides', 'Quiz', 0, '2026-08-09 00:00:00'),
(66, 34, 2, 'Core Concepts of Enterprise System', 'Deeper look into the main topics of the course.', 'Explain and apply the core concepts learned.', 'blended', 'Google Classroom', 'Reading materials, activity sheets', 'Activity', 1, '2026-08-09 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL DEFAULT 1,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `maintenance_mode`, `updated_at`) VALUES
(1, 0, '2026-07-07 03:29:46');

-- --------------------------------------------------------

--
-- Table structure for table `topic_done_status`
--

CREATE TABLE `topic_done_status` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `syllabus_id` int(11) NOT NULL,
  `status` tinyint(4) DEFAULT 0,
  `done_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `topic_done_status`
--

INSERT INTO `topic_done_status` (`id`, `teacher_id`, `syllabus_id`, `status`, `done_at`) VALUES
(1, 2, 9, 0, '2026-04-10 18:54:12'),
(11, 2, 8, 0, '2026-04-10 18:09:05'),
(19, 2, 7, 0, '2026-04-10 18:09:07'),
(20, 2, 5, 0, '2026-04-10 18:09:09'),
(21, 2, 4, 0, '2026-04-10 18:09:11');

-- --------------------------------------------------------

--
-- Table structure for table `topic_progress`
--

CREATE TABLE `topic_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `syllabus_topic_id` int(11) NOT NULL,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `topic_progress`
--

INSERT INTO `topic_progress` (`id`, `student_id`, `syllabus_topic_id`, `status`, `completed_at`, `notes`) VALUES
(21, 3, 8, 'completed', '2026-07-02 16:38:16', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `topic_week_done`
--

CREATE TABLE `topic_week_done` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `topic_id` int(11) NOT NULL,
  `status` tinyint(4) DEFAULT 0,
  `done_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `profile_pic`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$DTdHhUtuWmxnJNKILABxeuHoCM4xNur3cbw41o6ZcrX5XulV/BbTq', 'System Administrator', 'jeff.lim111@gmail.com\r\n', 'admin', NULL, 'active', '2026-03-12 01:29:03', '2026-03-27 17:08:41'),
(2, '001', '$2y$10$wDYS9pN2OQdsEWHb6YUKHeChJyb6vqj7xeQnA6vc/wMGfOe7/R0rW', 'Jeff Lim', 'Jeff.Lim@gmail.com', 'teacher', NULL, 'active', '2026-03-12 01:44:13', '2026-03-27 15:18:21'),
(3, 'cristian', '$2y$10$yI.gVxmAwGPz4nwJaenRN.Mp48jF.iH4ybgFrk5RGDlcnGe0OMWy2', 'cristian felix demateo', 'cristiandemateo333@gmail.com', 'student', NULL, 'active', '2026-03-12 01:45:54', '2026-08-06 02:22:06'),
(11, 'Margo', '$2y$10$s5FSqFiO/yc2hL69xj4nB.J4Yu2gFYXPqZGEuaJA.ZKCBp0gCaZIi', 'Margx M. Nomat', 'M.Nomat@gmail.com', 'student', NULL, 'active', '2026-03-14 16:52:27', '2026-03-14 16:52:27'),
(12, 'Shane', '$2y$10$4pE9zoKZekJyUfA7qb1w/eeghpBVRQqk7gBYCxyVQTwHvZA7iaTnq', 'Manang Shane', 'Shane.Bellanio@gmail.com', 'student', NULL, 'active', '2026-03-14 16:53:51', '2026-03-14 16:53:51'),
(13, 'Joy', '$2y$10$Nl.jScE4Z0PAJMUKZdX5Y.0AxE5IVI8Q6mPT9O8dBSmSL9n4FFo2G', 'Joy Deniola', 'Joy.Deniola@gmail.com', 'student', NULL, 'active', '2026-03-14 16:54:22', '2026-03-14 16:54:22'),
(15, 'Chie', '$2y$10$af6d.AuGPAh64B8eP4KVGOrssmX9dhHbHejvWx5oOBSvvxnnKujjW', 'Archie Jalea', 'Chie.Jalea@gmail.com', 'student', NULL, 'active', '2026-03-17 09:02:55', '2026-03-17 09:02:55'),
(16, 'Niel', '$2y$10$mj6nWXhizwfGoDebjfb/xu4rC/1BnugW9LQphb2U4/Iv33VPZxkRi', 'Niel John Marcial', 'Niel.Marcial@gmail.com', 'student', NULL, 'active', '2026-03-17 09:03:42', '2026-03-17 09:03:42'),
(17, 'Dale', '$2y$10$KAoNTXCTAk4CJjQWwgftMuwxPebdGdzq.pAFztb4iyItsd7thYys.', 'Dale Alojado', 'Dale.Alojado@gmail.com', 'teacher', NULL, 'active', '2026-03-22 11:12:57', '2026-03-22 11:12:57'),
(29, 'admin2', '$2y$10$WfoqA.t4AmT4wODqhnH9xuyhARJNRn/ZZBIfiP6jcJAx9oCE7Eq2q', 'Rafael Claveria', 'Rafael.Claveria@gmail.com', 'admin', NULL, 'active', '2026-03-27 15:08:42', '2026-03-27 15:11:56'),
(31, 'Ann', '$2y$10$qYmaQFWNHPoDaxnAEFZx8eI/EB7JmjiyMUazt6/4VEAbVGa5b1qnG', 'Thresia Ann Clamor', 'thresia123@gmail.com', 'student', NULL, 'active', '2026-03-28 05:47:17', '2026-03-28 06:09:05'),
(32, 'Janjan', '$2y$10$6AGBOzMjxfWSV6bWSUzFv.wgYwcBsjy90k7s6BrPDFpjrKXVBIPSu', 'John Michael Escobar', 'janjan@gmail.com', 'student', NULL, 'active', '2026-03-28 05:48:23', '2026-04-08 04:52:04'),
(33, 'winnie', '$2y$10$1/5Fv1c9PhNA4OBtR0FKyuo5K9KUwYekAScGUmaLj76Y3maoMgv2i', 'Andree Alvior', 'andree.alvior@gmail.com', 'student', NULL, 'active', '2026-03-28 06:25:07', '2026-08-06 01:49:50'),
(35, 'q', '$2y$10$fiE0jhZWcc/ePKYIGD3lBuMJ40dg8OJsjs3jjimpRMJAe3AHsoPl2', 'q', 'q@gmail.com', 'teacher', NULL, 'active', '2026-08-06 01:56:19', '2026-08-06 01:56:19'),
(36, 's', '$2y$10$9vm9zFq5USXXdcjpwHq/8OyKimBhN3dSCJzaSHTfR/reQzug1O8tK', 's', 's@gmail.com', 'student', NULL, 'active', '2026-08-06 02:00:06', '2026-08-06 02:00:06'),
(37, 'Kenneth', '$2y$10$myfmT4mwK6G9nzvcYmBjWeZMr2jI1.Y5oKm2MVFHqwTXZtK0eOnva', 'Kenneth Demateo', 'kenneth@gmail.com', 'teacher', NULL, 'active', '2026-08-06 18:10:25', '2026-08-06 18:16:11'),
(38, 'ban', '$2y$10$DCixNZwqCOr//PiS84BTeun5aAgw1zuwPm27m9BWBZstOs4Ue.gSS', 'Ban Demateo', 'ban@gmail.com', 'student', NULL, 'active', '2026-08-06 18:17:20', '2026-08-06 18:17:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_user` (`user_id`),
  ADD KEY `idx_activity_category` (`category`),
  ADD KEY `idx_activity_created` (`created_at`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `syllabus_id` (`syllabus_id`);

--
-- Indexes for table `assessments`
--
ALTER TABLE `assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `syllabus_id` (`syllabus_id`),
  ADD KEY `topic_id` (`topic_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`syllabus_id`),
  ADD KEY `syllabus_id` (`syllabus_id`);

--
-- Indexes for table `learning_materials`
--
ALTER TABLE `learning_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `syllabus_topic_id` (`syllabus_topic_id`),
  ADD KEY `syllabus_id` (`syllabus_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `query_logs`
--
ALTER TABLE `query_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assessment_id` (`assessment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `syllabi`
--
ALTER TABLE `syllabi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `teacher_id` (`teacher_id`);

--
-- Indexes for table `syllabus_assignments`
--
ALTER TABLE `syllabus_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `syllabus_id` (`syllabus_id`);

--
-- Indexes for table `syllabus_templates`
--
ALTER TABLE `syllabus_templates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `syllabus_template_topics`
--
ALTER TABLE `syllabus_template_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indexes for table `syllabus_topics`
--
ALTER TABLE `syllabus_topics`
  ADD PRIMARY KEY (`id`),
  ADD KEY `syllabus_id` (`syllabus_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `topic_done_status`
--
ALTER TABLE `topic_done_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_syllabus` (`teacher_id`,`syllabus_id`);

--
-- Indexes for table `topic_progress`
--
ALTER TABLE `topic_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_progress` (`student_id`,`syllabus_topic_id`),
  ADD KEY `syllabus_topic_id` (`syllabus_topic_id`);

--
-- Indexes for table `topic_week_done`
--
ALTER TABLE `topic_week_done`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_topic` (`teacher_id`,`topic_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `learning_materials`
--
ALTER TABLE `learning_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `query_logs`
--
ALTER TABLE `query_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `syllabi`
--
ALTER TABLE `syllabi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `syllabus_assignments`
--
ALTER TABLE `syllabus_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `syllabus_templates`
--
ALTER TABLE `syllabus_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `syllabus_template_topics`
--
ALTER TABLE `syllabus_template_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `syllabus_topics`
--
ALTER TABLE `syllabus_topics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `topic_done_status`
--
ALTER TABLE `topic_done_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `topic_progress`
--
ALTER TABLE `topic_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `topic_week_done`
--
ALTER TABLE `topic_week_done`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `assessments`
--
ALTER TABLE `assessments`
  ADD CONSTRAINT `assessments_ibfk_1` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `assessments_ibfk_2` FOREIGN KEY (`topic_id`) REFERENCES `syllabus_topics` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assessments_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `enrollments`
--
ALTER TABLE `enrollments`
  ADD CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `learning_materials`
--
ALTER TABLE `learning_materials`
  ADD CONSTRAINT `learning_materials_ibfk_1` FOREIGN KEY (`syllabus_topic_id`) REFERENCES `syllabus_topics` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `learning_materials_ibfk_2` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `learning_materials_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `syllabi`
--
ALTER TABLE `syllabi`
  ADD CONSTRAINT `syllabi_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `syllabi_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `syllabus_assignments`
--
ALTER TABLE `syllabus_assignments`
  ADD CONSTRAINT `syllabus_assignments_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `syllabus_templates` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `syllabus_assignments_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `syllabus_assignments_ibfk_3` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `syllabus_templates`
--
ALTER TABLE `syllabus_templates`
  ADD CONSTRAINT `syllabus_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `syllabus_template_topics`
--
ALTER TABLE `syllabus_template_topics`
  ADD CONSTRAINT `syllabus_template_topics_ibfk_1` FOREIGN KEY (`template_id`) REFERENCES `syllabus_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `syllabus_topics`
--
ALTER TABLE `syllabus_topics`
  ADD CONSTRAINT `syllabus_topics_ibfk_1` FOREIGN KEY (`syllabus_id`) REFERENCES `syllabi` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topic_progress`
--
ALTER TABLE `topic_progress`
  ADD CONSTRAINT `topic_progress_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `topic_progress_ibfk_2` FOREIGN KEY (`syllabus_topic_id`) REFERENCES `syllabus_topics` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
