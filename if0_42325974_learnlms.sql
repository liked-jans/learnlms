-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql305.infinityfree.com
-- Generation Time: Sep 14, 2026 at 05:43 AM
-- Server version: 11.4.13-MariaDB
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
(178, 2, 'Logged in', 'Authentication', '2026-08-08 11:30:40'),
(179, 1, 'Logged in', 'Authentication', '2026-08-20 18:44:57'),
(180, 2, 'Logged in', 'Authentication', '2026-08-21 05:58:24'),
(181, 1, 'Logged in', 'Authentication', '2026-08-24 07:45:56'),
(182, 1, 'Logged out', 'Authentication', '2026-08-24 07:47:11'),
(183, 1, 'Logged in', 'Authentication', '2026-08-24 07:49:21'),
(184, 1, 'Logged out', 'Authentication', '2026-08-24 07:49:51'),
(185, 2, 'Logged in', 'Authentication', '2026-08-24 07:50:03'),
(186, 2, 'Logged out', 'Authentication', '2026-08-24 07:50:37'),
(187, 1, 'Logged in', 'Authentication', '2026-08-24 07:50:53'),
(188, 1, 'Logged out', 'Authentication', '2026-08-24 08:01:00'),
(189, 2, 'Logged in', 'Authentication', '2026-08-24 08:01:14'),
(190, 2, 'Logged out', 'Authentication', '2026-08-24 08:03:09'),
(191, 1, 'Logged in', 'Authentication', '2026-08-24 08:03:21'),
(192, 1, 'Logged in', 'Authentication', '2026-08-24 10:07:12'),
(193, 2, 'Logged in', 'Authentication', '2026-08-24 14:50:23'),
(194, 2, 'Logged out', 'Authentication', '2026-08-24 14:50:38'),
(195, 1, 'Logged in', 'Authentication', '2026-08-24 14:50:50'),
(196, 1, 'Logged out', 'Authentication', '2026-08-24 16:22:14'),
(197, 2, 'Logged in', 'Authentication', '2026-08-24 16:23:08'),
(198, 2, 'Logged out', 'Authentication', '2026-08-24 16:51:49'),
(199, 3, 'Logged in', 'Authentication', '2026-08-24 16:52:04'),
(200, 3, 'Logged out', 'Authentication', '2026-08-24 16:55:11'),
(201, 2, 'Logged in', 'Authentication', '2026-08-24 16:55:20'),
(202, 2, 'Logged in', 'Authentication', '2026-08-26 03:14:09'),
(203, 2, 'Logged in', 'Authentication', '2026-08-27 03:39:50'),
(204, 2, 'Logged out', 'Authentication', '2026-08-27 06:08:38'),
(205, 1, 'Logged in', 'Authentication', '2026-08-27 06:08:50'),
(206, 1, 'Logged in', 'Authentication', '2026-08-28 02:12:25'),
(207, 2, 'Logged in', 'Authentication', '2026-08-28 02:18:56'),
(208, 2, 'Logged out', 'Authentication', '2026-08-28 02:20:54'),
(209, 1, 'Logged in', 'Authentication', '2026-08-28 02:21:03'),
(210, 1, 'Logged out', 'Authentication', '2026-08-28 02:24:12'),
(211, 2, 'Logged in', 'Authentication', '2026-08-28 02:24:28'),
(212, 2, 'Logged out', 'Authentication', '2026-08-28 02:27:00'),
(213, 1, 'Logged in', 'Authentication', '2026-08-28 02:27:18'),
(214, 1, 'Logged out', 'Authentication', '2026-08-28 02:33:49'),
(215, 2, 'Logged in', 'Authentication', '2026-08-28 02:34:00'),
(216, 2, 'Logged out', 'Authentication', '2026-08-28 07:51:28'),
(217, 3, 'Logged in', 'Authentication', '2026-08-28 07:51:59'),
(218, 3, 'Logged out', 'Authentication', '2026-08-28 07:53:35'),
(219, 2, 'Logged in', 'Authentication', '2026-08-28 07:53:47'),
(220, 2, 'Logged out', 'Authentication', '2026-08-28 07:54:26'),
(221, 3, 'Logged in', 'Authentication', '2026-08-28 07:55:21'),
(222, 3, 'Logged out', 'Authentication', '2026-08-28 07:56:09'),
(223, 2, 'Logged in', 'Authentication', '2026-08-28 07:56:21'),
(224, 2, 'Logged out', 'Authentication', '2026-08-28 07:59:55'),
(225, 3, 'Logged in', 'Authentication', '2026-08-28 08:00:15'),
(226, 2, 'Logged in', 'Authentication', '2026-08-28 08:01:29'),
(227, 2, 'Logged out', 'Authentication', '2026-08-28 08:04:09'),
(228, 1, 'Logged in', 'Authentication', '2026-08-28 08:04:24'),
(229, 1, 'Logged out', 'Authentication', '2026-08-28 08:04:37'),
(230, 1, 'Logged in', 'Authentication', '2026-08-28 08:07:24'),
(231, 1, 'Logged out', 'Authentication', '2026-08-28 08:08:25'),
(232, 2, 'Logged in', 'Authentication', '2026-08-28 08:08:32'),
(233, 2, 'Logged out', 'Authentication', '2026-08-28 08:08:57'),
(234, 3, 'Logged in', 'Authentication', '2026-08-28 08:09:12'),
(235, 3, 'Logged out', 'Authentication', '2026-08-28 08:10:01'),
(236, 1, 'Logged in', 'Authentication', '2026-08-28 08:10:29'),
(237, 1, 'Logged out', 'Authentication', '2026-08-28 08:11:24'),
(238, 2, 'Logged in', 'Authentication', '2026-08-28 08:12:02'),
(239, 2, 'Logged out', 'Authentication', '2026-08-28 08:12:56'),
(240, 3, 'Logged in', 'Authentication', '2026-08-28 08:13:24'),
(241, 3, 'Logged out', 'Authentication', '2026-08-28 11:07:24'),
(242, 1, 'Logged in', 'Authentication', '2026-08-28 11:07:42'),
(243, 1, 'Logged in', 'Authentication', '2026-08-28 11:08:59'),
(244, 1, 'Logged out', 'Authentication', '2026-08-28 11:09:11'),
(245, 2, 'Logged in', 'Authentication', '2026-08-28 11:09:17'),
(246, 2, 'Logged out', 'Authentication', '2026-08-28 11:09:48'),
(247, 3, 'Logged in', 'Authentication', '2026-08-28 11:10:02'),
(248, 2, 'Logged in', 'Authentication', '2026-08-28 11:11:18'),
(249, 2, 'Logged out', 'Authentication', '2026-08-28 11:11:30'),
(250, 1, 'Logged in', 'Authentication', '2026-08-28 11:11:41'),
(251, 1, 'Logged out', 'Authentication', '2026-08-28 11:11:57'),
(252, 3, 'Logged in', 'Authentication', '2026-08-28 11:12:15'),
(253, 1, 'Logged in', 'Authentication', '2026-08-29 01:00:57'),
(254, 1, 'Logged out', 'Authentication', '2026-08-29 01:05:28'),
(255, 2, 'Logged in', 'Authentication', '2026-08-29 01:05:38'),
(256, 3, 'Logged out', 'Authentication', '2026-08-29 01:07:44'),
(257, 2, 'Logged in', 'Authentication', '2026-08-29 01:07:52'),
(258, 2, 'Logged out', 'Authentication', '2026-08-29 01:08:13'),
(259, 3, 'Logged in', 'Authentication', '2026-08-29 01:08:25'),
(260, 3, 'Logged out', 'Authentication', '2026-08-29 01:09:32'),
(261, 2, 'Logged in', 'Authentication', '2026-08-29 01:09:38'),
(262, 2, 'Logged out', 'Authentication', '2026-08-29 01:12:19'),
(263, 3, 'Logged in', 'Authentication', '2026-08-29 01:12:34'),
(264, 3, 'Logged out', 'Authentication', '2026-08-29 01:12:53'),
(265, 2, 'Logged in', 'Authentication', '2026-08-29 01:12:58'),
(266, 1, 'Logged in', 'Authentication', '2026-08-30 10:09:00'),
(267, 1, 'Logged out', 'Authentication', '2026-08-30 10:09:19'),
(268, 2, 'Logged in', 'Authentication', '2026-08-30 10:09:29'),
(269, 2, 'Logged out', 'Authentication', '2026-08-30 11:03:04'),
(270, 1, 'Logged in', 'Authentication', '2026-08-30 11:03:32'),
(271, 1, 'Logged out', 'Authentication', '2026-08-30 11:04:28'),
(272, 2, 'Logged in', 'Authentication', '2026-08-30 11:05:15'),
(273, 2, 'Logged out', 'Authentication', '2026-08-30 11:06:21'),
(274, 3, 'Logged in', 'Authentication', '2026-08-30 11:06:26'),
(275, 1, 'Logged in', 'Authentication', '2026-08-30 11:10:01'),
(276, 1, 'Logged out', 'Authentication', '2026-08-30 11:10:09'),
(277, 2, 'Logged in', 'Authentication', '2026-08-30 20:25:00'),
(278, 2, 'Logged out', 'Authentication', '2026-08-30 20:27:02'),
(279, 3, 'Logged in', 'Authentication', '2026-08-30 20:27:10'),
(280, 1, 'Logged in', 'Authentication', '2026-08-31 04:05:44'),
(281, 1, 'Logged out', 'Authentication', '2026-08-31 04:21:51'),
(282, 2, 'Logged in', 'Authentication', '2026-08-31 04:22:05'),
(283, 3, 'Logged in', 'Authentication', '2026-08-31 04:24:10'),
(284, 2, 'Logged out', 'Authentication', '2026-08-31 07:23:40'),
(285, 1, 'Logged in', 'Authentication', '2026-08-31 07:23:56'),
(286, 1, 'Logged out', 'Authentication', '2026-08-31 08:07:09'),
(287, 2, 'Logged in', 'Authentication', '2026-08-31 08:07:24'),
(288, 3, 'Logged out', 'Authentication', '2026-08-31 08:08:51'),
(289, 1, 'Logged in', 'Authentication', '2026-08-31 08:08:59'),
(290, 1, 'Logged out', 'Authentication', '2026-08-31 08:10:50'),
(291, 2, 'Logged out', 'Authentication', '2026-08-31 08:22:12'),
(292, 3, 'Logged in', 'Authentication', '2026-08-31 08:22:28'),
(293, 3, 'Logged out', 'Authentication', '2026-08-31 09:02:35'),
(294, 1, 'Logged in', 'Authentication', '2026-08-31 09:18:25'),
(295, 2, 'Logged in', 'Authentication', '2026-08-31 18:40:11'),
(296, 2, 'Logged out', 'Authentication', '2026-08-31 18:49:09'),
(297, 3, 'Logged in', 'Authentication', '2026-08-31 18:50:10'),
(298, 3, 'Logged out', 'Authentication', '2026-08-31 18:51:30'),
(299, 3, 'Logged in', 'Authentication', '2026-09-01 01:18:20'),
(300, 3, 'Logged out', 'Authentication', '2026-09-01 01:52:52'),
(301, 2, 'Logged in', 'Authentication', '2026-09-01 01:53:19'),
(302, 2, 'Logged in', 'Authentication', '2026-09-01 01:54:47'),
(303, 3, 'Logged in', 'Authentication', '2026-09-01 01:55:53'),
(304, 2, 'Logged in', 'Authentication', '2026-09-02 14:37:42'),
(305, 2, 'Logged in', 'Authentication', '2026-09-02 15:16:30'),
(306, 1, 'Logged in', 'Authentication', '2026-09-03 05:29:34'),
(307, 1, 'Logged out', 'Authentication', '2026-09-03 06:10:40'),
(308, 2, 'Logged in', 'Authentication', '2026-09-03 06:11:13'),
(309, 2, 'Logged out', 'Authentication', '2026-09-03 07:14:05'),
(310, 3, 'Logged in', 'Authentication', '2026-09-03 07:14:18'),
(311, 3, 'Logged out', 'Authentication', '2026-09-03 07:29:53'),
(312, 1, 'Logged in', 'Authentication', '2026-09-03 07:30:15'),
(313, 1, 'Logged out', 'Authentication', '2026-09-03 07:56:39'),
(314, 3, 'Logged in', 'Authentication', '2026-09-03 07:56:56'),
(315, 3, 'Logged out', 'Authentication', '2026-09-03 08:10:22'),
(316, 1, 'Logged in', 'Authentication', '2026-09-03 08:10:41'),
(317, 1, 'Logged out', 'Authentication', '2026-09-03 08:12:11'),
(318, 2, 'Logged in', 'Authentication', '2026-09-03 08:12:55');
INSERT INTO `activity_logs` (`id`, `user_id`, `description`, `category`, `created_at`) VALUES
(319, 2, 'Logged out', 'Authentication', '2026-09-03 08:53:14'),
(320, 3, 'Logged in', 'Authentication', '2026-09-03 08:53:32'),
(321, 3, 'Logged out', 'Authentication', '2026-09-03 08:58:17'),
(322, 1, 'Logged in', 'Authentication', '2026-09-03 08:58:27'),
(323, 1, 'Logged out', 'Authentication', '2026-09-03 09:15:17'),
(324, 2, 'Logged in', 'Authentication', '2026-09-03 16:33:56'),
(325, 2, 'Logged out', 'Authentication', '2026-09-03 17:03:44'),
(326, 3, 'Logged in', 'Authentication', '2026-09-03 17:03:54'),
(327, 3, 'Logged out', 'Authentication', '2026-09-03 17:04:41'),
(328, 2, 'Logged in', 'Authentication', '2026-09-03 17:04:53'),
(329, 2, 'Logged in', 'Authentication', '2026-09-03 17:06:10'),
(330, 2, 'Logged out', 'Authentication', '2026-09-03 17:06:37'),
(331, 3, 'Logged in', 'Authentication', '2026-09-03 17:06:50'),
(332, 3, 'Logged out', 'Authentication', '2026-09-03 17:10:32'),
(333, 1, 'Logged in', 'Authentication', '2026-09-03 17:10:47'),
(334, 2, 'Logged in', 'Authentication', '2026-09-04 05:36:24'),
(335, 2, 'Logged in', 'Authentication', '2026-09-04 05:40:46'),
(336, 2, 'Logged out', 'Authentication', '2026-09-04 05:44:15'),
(337, 3, 'Logged in', 'Authentication', '2026-09-04 05:44:30'),
(338, 3, 'Logged out', 'Authentication', '2026-09-04 05:45:00'),
(339, 1, 'Logged in', 'Authentication', '2026-09-04 05:45:08'),
(340, 1, 'Logged out', 'Authentication', '2026-09-04 06:16:18'),
(341, 2, 'Logged in', 'Authentication', '2026-09-04 06:16:25'),
(342, 2, 'Logged out', 'Authentication', '2026-09-04 06:25:13'),
(343, 1, 'Logged in', 'Authentication', '2026-09-04 06:25:22'),
(344, 2, 'Logged out', 'Authentication', '2026-09-04 06:47:13'),
(345, 3, 'Logged in', 'Authentication', '2026-09-04 06:47:32'),
(346, 1, 'Logged out', 'Authentication', '2026-09-04 08:36:24'),
(347, 1, 'Logged in', 'Authentication', '2026-09-04 08:36:30'),
(348, 1, 'Logged in', 'Authentication', '2026-09-04 08:40:11'),
(349, 3, 'Logged out', 'Authentication', '2026-09-04 08:47:24'),
(350, 1, 'Logged in', 'Authentication', '2026-09-04 09:33:31'),
(351, 3, 'Logged in', 'Authentication', '2026-09-05 13:25:34'),
(352, 2, 'Logged in', 'Authentication', '2026-09-06 07:35:04'),
(353, 2, 'Logged out', 'Authentication', '2026-09-06 07:35:33'),
(354, 1, 'Logged in', 'Authentication', '2026-09-06 07:35:40'),
(355, 1, 'Logged out', 'Authentication', '2026-09-06 12:33:40'),
(356, 2, 'Logged in', 'Authentication', '2026-09-06 12:33:46'),
(357, 1, 'Logged in', 'Authentication', '2026-09-07 12:35:29'),
(358, 1, 'Logged out', 'Authentication', '2026-09-08 05:23:30'),
(359, 2, 'Logged in', 'Authentication', '2026-09-08 05:23:36'),
(360, 1, 'Logged in', 'Authentication', '2026-09-09 06:52:10'),
(361, 1, 'Logged out', 'Authentication', '2026-09-09 06:52:36'),
(362, 1, 'Logged in', 'Authentication', '2026-09-09 23:27:59'),
(363, 1, 'Logged out', 'Authentication', '2026-09-09 23:48:42'),
(364, 2, 'Logged in', 'Authentication', '2026-09-09 23:48:50'),
(365, 2, 'Logged out', 'Authentication', '2026-09-09 23:50:56'),
(366, 3, 'Logged in', 'Authentication', '2026-09-09 23:51:01'),
(367, 3, 'Logged out', 'Authentication', '2026-09-09 23:51:34'),
(368, 2, 'Logged in', 'Authentication', '2026-09-09 23:51:40'),
(369, 2, 'Logged out', 'Authentication', '2026-09-10 00:05:50'),
(370, 3, 'Logged in', 'Authentication', '2026-09-10 00:06:02'),
(371, 3, 'Logged out', 'Authentication', '2026-09-10 00:07:21'),
(372, 1, 'Logged in', 'Authentication', '2026-09-10 00:10:22'),
(373, 1, 'Logged in', 'Authentication', '2026-09-10 00:17:49'),
(374, 1, 'Logged in', 'Authentication', '2026-09-10 00:18:26'),
(375, 1, 'Logged out', 'Authentication', '2026-09-10 03:55:36'),
(376, 1, 'Logged in', 'Authentication', '2026-09-11 02:18:49'),
(377, 1, 'Logged in', 'Authentication', '2026-09-11 05:17:29'),
(378, 1, 'Logged out', 'Authentication', '2026-09-11 06:29:23'),
(379, 2, 'Logged in', 'Authentication', '2026-09-11 06:31:19'),
(380, 2, 'Logged out', 'Authentication', '2026-09-11 06:34:13'),
(381, 1, 'Logged in', 'Authentication', '2026-09-11 06:34:19'),
(382, 1, 'Logged out', 'Authentication', '2026-09-11 06:35:44'),
(383, 2, 'Logged in', 'Authentication', '2026-09-11 06:35:51'),
(384, 2, 'Logged out', 'Authentication', '2026-09-11 06:37:19'),
(385, 1, 'Logged in', 'Authentication', '2026-09-11 06:40:01'),
(386, 1, 'Logged out', 'Authentication', '2026-09-11 06:44:29'),
(387, 2, 'Logged in', 'Authentication', '2026-09-11 06:44:34'),
(388, 2, 'Logged out', 'Authentication', '2026-09-11 07:24:54'),
(389, 1, 'Logged in', 'Authentication', '2026-09-11 07:25:00'),
(390, 1, 'Logged out', 'Authentication', '2026-09-11 09:01:52'),
(391, 1, 'Logged in', 'Authentication', '2026-09-11 09:02:07'),
(392, 1, 'Logged out', 'Authentication', '2026-09-11 09:04:57'),
(393, 2, 'Logged in', 'Authentication', '2026-09-11 09:05:16'),
(394, 2, 'Logged out', 'Authentication', '2026-09-11 09:07:42'),
(395, 1, 'Logged in', 'Authentication', '2026-09-11 09:07:59'),
(396, 1, 'Logged out', 'Authentication', '2026-09-11 09:14:41'),
(397, 2, 'Logged in', 'Authentication', '2026-09-11 09:16:12'),
(398, 2, 'Logged out', 'Authentication', '2026-09-11 09:19:27'),
(399, 1, 'Logged in', 'Authentication', '2026-09-11 09:20:37'),
(400, 3, 'Logged in', 'Authentication', '2026-09-11 09:20:42'),
(401, 1, 'Logged out', 'Authentication', '2026-09-11 09:22:49'),
(402, 3, 'Logged in', 'Authentication', '2026-09-11 09:23:03'),
(403, 3, 'Logged out', 'Authentication', '2026-09-11 09:25:24'),
(404, 1, 'Logged in', 'Authentication', '2026-09-11 09:25:37'),
(405, 1, 'Logged out', 'Authentication', '2026-09-11 10:43:14'),
(406, 3, 'Logged in', 'Authentication', '2026-09-11 10:43:19'),
(407, 3, 'Logged out', 'Authentication', '2026-09-11 11:11:47'),
(408, 1, 'Logged in', 'Authentication', '2026-09-11 11:11:56'),
(409, 1, 'Logged out', 'Authentication', '2026-09-11 11:13:59'),
(410, 2, 'Logged in', 'Authentication', '2026-09-11 11:14:05'),
(411, 2, 'Logged out', 'Authentication', '2026-09-11 11:14:56'),
(412, 3, 'Logged in', 'Authentication', '2026-09-11 11:15:01'),
(413, 3, 'Logged out', 'Authentication', '2026-09-11 11:16:23'),
(414, 1, 'Logged in', 'Authentication', '2026-09-11 11:17:06'),
(415, 1, 'Logged out', 'Authentication', '2026-09-11 11:25:06'),
(416, 1, 'Logged out', 'Authentication', '2026-09-11 11:46:49'),
(417, 2, 'Logged in', 'Authentication', '2026-09-11 11:48:09'),
(418, 1, 'Logged in', 'Authentication', '2026-09-11 12:01:31'),
(419, 1, 'Logged in', 'Authentication', '2026-09-11 12:18:26'),
(420, 1, 'Logged out', 'Authentication', '2026-09-11 12:26:59'),
(421, 41, 'Logged in', 'Authentication', '2026-09-11 12:27:05'),
(422, 41, 'Logged out', 'Authentication', '2026-09-11 12:28:01'),
(423, 1, 'Logged in', 'Authentication', '2026-09-11 12:28:12'),
(424, 1, 'Logged in', 'Authentication', '2026-09-11 12:29:33'),
(425, 2, 'Logged in', 'Authentication', '2026-09-11 13:29:29'),
(426, 13, 'Logged in', 'Authentication', '2026-09-11 14:02:57'),
(427, 2, 'Logged out', 'Authentication', '2026-09-11 14:09:59'),
(428, 1, 'Logged out', 'Authentication', '2026-09-11 14:17:19'),
(429, 2, 'Logged out', 'Authentication', '2026-09-11 15:01:00'),
(430, 1, 'Logged in', 'Authentication', '2026-09-11 15:01:42'),
(431, 45, 'Logged in', 'Authentication', '2026-09-11 15:28:05'),
(432, 45, 'Logged in', 'Authentication', '2026-09-11 15:40:43'),
(433, 45, 'Logged out', 'Authentication', '2026-09-11 15:47:27'),
(434, 59, 'Logged in', 'Authentication', '2026-09-11 15:47:38'),
(435, 1, 'Logged out', 'Authentication', '2026-09-11 15:48:13'),
(436, 45, 'Logged in', 'Authentication', '2026-09-11 15:48:25'),
(437, 1, 'Logged in', 'Authentication', '2026-09-11 15:56:52'),
(438, 1, 'Logged out', 'Authentication', '2026-09-11 16:16:34'),
(439, 45, 'Logged in', 'Authentication', '2026-09-11 16:16:49'),
(440, 59, 'Logged out', 'Authentication', '2026-09-11 16:59:17'),
(441, 1, 'Logged in', 'Authentication', '2026-09-11 16:59:31'),
(442, 45, 'Logged out', 'Authentication', '2026-09-11 17:03:53'),
(443, 59, 'Logged in', 'Authentication', '2026-09-11 17:04:08'),
(444, 1, 'Logged out', 'Authentication', '2026-09-11 17:47:46'),
(445, 59, 'Logged in', 'Authentication', '2026-09-11 17:47:51'),
(446, 1, 'Logged out', 'Authentication', '2026-09-11 17:51:30'),
(447, 59, 'Logged in', 'Authentication', '2026-09-11 17:51:56'),
(448, 1, 'Logged in', 'Authentication', '2026-09-11 17:59:46'),
(449, 1, 'Logged out', 'Authentication', '2026-09-11 18:01:31'),
(450, 45, 'Logged in', 'Authentication', '2026-09-11 18:01:53'),
(451, 45, 'Logged out', 'Authentication', '2026-09-11 18:28:02'),
(452, 1, 'Logged in', 'Authentication', '2026-09-11 18:28:23'),
(453, 1, 'Logged out', 'Authentication', '2026-09-11 18:29:54'),
(454, 45, 'Logged in', 'Authentication', '2026-09-11 18:30:08'),
(455, 45, 'Logged out', 'Authentication', '2026-09-11 18:32:34'),
(456, 59, 'Logged out', 'Authentication', '2026-09-11 18:32:59'),
(457, 1, 'Logged in', 'Authentication', '2026-09-11 18:33:12'),
(458, 45, 'Logged in', 'Authentication', '2026-09-11 18:33:38'),
(459, 1, 'Logged out', 'Authentication', '2026-09-11 18:35:00'),
(460, 59, 'Logged in', 'Authentication', '2026-09-11 18:35:14'),
(461, 45, 'Logged out', 'Authentication', '2026-09-11 18:48:54'),
(462, 1, 'Logged in', 'Authentication', '2026-09-11 18:49:12'),
(463, 1, 'Logged out', 'Authentication', '2026-09-11 18:50:23'),
(464, 41, 'Logged in', 'Authentication', '2026-09-11 18:50:59'),
(465, 59, 'Logged out', 'Authentication', '2026-09-11 18:52:52'),
(466, 1, 'Logged in', 'Authentication', '2026-09-11 18:53:09'),
(467, 41, 'Logged out', 'Authentication', '2026-09-11 18:59:06'),
(468, 59, 'Logged in', 'Authentication', '2026-09-11 18:59:33'),
(469, 59, 'Logged out', 'Authentication', '2026-09-11 19:03:29'),
(470, 1, 'Logged in', 'Authentication', '2026-09-11 19:03:37'),
(471, 1, 'Logged out', 'Authentication', '2026-09-11 19:04:31'),
(472, 1, 'Logged in', 'Authentication', '2026-09-11 19:05:24'),
(473, 1, 'Logged out', 'Authentication', '2026-09-11 19:05:39'),
(474, 1, 'Logged in', 'Authentication', '2026-09-11 19:06:23'),
(475, 1, 'Logged out', 'Authentication', '2026-09-11 19:06:42'),
(476, 39, 'Logged in', 'Authentication', '2026-09-11 19:07:02'),
(477, 39, 'Logged out', 'Authentication', '2026-09-11 19:12:16'),
(478, 59, 'Logged in', 'Authentication', '2026-09-11 19:12:24'),
(479, 59, 'Logged out', 'Authentication', '2026-09-11 19:12:36'),
(480, 39, 'Logged in', 'Authentication', '2026-09-11 19:12:54'),
(481, 39, 'Logged out', 'Authentication', '2026-09-11 19:14:56'),
(482, 59, 'Logged in', 'Authentication', '2026-09-11 19:15:02'),
(483, 59, 'Logged out', 'Authentication', '2026-09-11 19:16:26'),
(484, 39, 'Logged in', 'Authentication', '2026-09-11 19:16:39'),
(485, 39, 'Logged out', 'Authentication', '2026-09-11 19:19:01'),
(486, 59, 'Logged in', 'Authentication', '2026-09-11 19:19:34'),
(487, 59, 'Logged out', 'Authentication', '2026-09-11 19:21:00'),
(488, 39, 'Logged in', 'Authentication', '2026-09-11 19:21:11'),
(489, 39, 'Logged out', 'Authentication', '2026-09-11 19:25:26'),
(490, 1, 'Logged in', 'Authentication', '2026-09-11 19:25:36'),
(491, 1, 'Logged out', 'Authentication', '2026-09-11 19:29:57'),
(492, 39, 'Logged in', 'Authentication', '2026-09-11 19:31:52'),
(493, 39, 'Logged out', 'Authentication', '2026-09-11 19:32:38'),
(494, 59, 'Logged out', 'Authentication', '2026-09-11 19:36:47'),
(495, 1, 'Logged in', 'Authentication', '2026-09-11 19:36:55'),
(496, 45, 'Logged out', 'Authentication', '2026-09-11 20:26:54'),
(497, 59, 'Logged in', 'Authentication', '2026-09-11 20:27:10'),
(498, 59, 'Logged out', 'Authentication', '2026-09-11 20:32:07'),
(499, 39, 'Logged in', 'Authentication', '2026-09-11 20:33:02'),
(500, 1, 'Logged out', 'Authentication', '2026-09-11 20:36:37'),
(501, 59, 'Logged in', 'Authentication', '2026-09-11 20:36:47'),
(502, 59, 'Logged out', 'Authentication', '2026-09-11 20:37:53'),
(503, 39, 'Logged in', 'Authentication', '2026-09-11 20:38:13'),
(504, 39, 'Logged out', 'Authentication', '2026-09-11 20:39:29'),
(505, 59, 'Logged in', 'Authentication', '2026-09-11 20:39:39'),
(506, 59, 'Logged out', 'Authentication', '2026-09-11 20:40:00'),
(507, 39, 'Logged in', 'Authentication', '2026-09-11 20:40:13'),
(508, 39, 'Logged out', 'Authentication', '2026-09-11 20:40:47'),
(509, 59, 'Logged in', 'Authentication', '2026-09-11 20:41:00'),
(510, 59, 'Logged out', 'Authentication', '2026-09-11 20:42:04'),
(511, 39, 'Logged in', 'Authentication', '2026-09-11 20:42:17'),
(512, 39, 'Logged out', 'Authentication', '2026-09-11 20:42:45'),
(513, 1, 'Logged out', 'Authentication', '2026-09-11 20:43:34'),
(514, 1, 'Logged in', 'Authentication', '2026-09-11 20:43:42'),
(515, 45, 'Logged in', 'Authentication', '2026-09-11 20:44:01'),
(516, 45, 'Logged out', 'Authentication', '2026-09-11 20:44:46'),
(517, 90, 'Logged in', 'Authentication', '2026-09-11 20:48:35'),
(518, 1, 'Logged out', 'Authentication', '2026-09-11 20:50:08'),
(519, 59, 'Logged in', 'Authentication', '2026-09-11 20:50:17'),
(520, 90, 'Logged out', 'Authentication', '2026-09-11 21:01:10'),
(521, 39, 'Logged out', 'Authentication', '2026-09-11 21:01:47'),
(522, 1, 'Logged in', 'Authentication', '2026-09-11 21:02:07'),
(523, 39, 'Logged in', 'Authentication', '2026-09-11 21:02:55'),
(524, 39, 'Logged out', 'Authentication', '2026-09-11 21:28:57'),
(525, 59, 'Logged in', 'Authentication', '2026-09-11 21:29:31'),
(526, 59, 'Logged out', 'Authentication', '2026-09-11 21:35:57'),
(527, 39, 'Logged in', 'Authentication', '2026-09-11 21:36:23'),
(528, 39, 'Logged out', 'Authentication', '2026-09-11 21:49:40'),
(529, 45, 'Logged in', 'Authentication', '2026-09-11 21:50:17'),
(530, 1, 'Logged in', 'Authentication', '2026-09-11 22:08:21'),
(531, 1, 'Logged out', 'Authentication', '2026-09-11 22:37:57'),
(532, 59, 'Logged in', 'Authentication', '2026-09-11 22:38:05'),
(533, 59, 'Logged out', 'Authentication', '2026-09-11 22:38:45'),
(534, 59, 'Logged out', 'Authentication', '2026-09-11 22:39:48'),
(535, 1, 'Logged in', 'Authentication', '2026-09-11 22:39:53'),
(536, 90, 'Logged in', 'Authentication', '2026-09-11 22:40:38'),
(537, 45, 'Logged out', 'Authentication', '2026-09-11 22:59:52'),
(538, 1, 'Logged in', 'Authentication', '2026-09-11 23:00:24'),
(539, 90, 'Logged out', 'Authentication', '2026-09-12 02:25:27'),
(540, 1, 'Logged in', 'Authentication', '2026-09-12 02:25:42'),
(541, 1, 'Logged out', 'Authentication', '2026-09-12 02:30:25'),
(542, 59, 'Logged in', 'Authentication', '2026-09-12 02:30:39'),
(543, 59, 'Logged out', 'Authentication', '2026-09-12 02:32:02'),
(544, 39, 'Logged in', 'Authentication', '2026-09-12 02:32:45'),
(545, 39, 'Logged out', 'Authentication', '2026-09-12 02:33:13'),
(546, 1, 'Logged in', 'Authentication', '2026-09-12 02:33:25'),
(547, 1, 'Logged out', 'Authentication', '2026-09-12 02:35:10'),
(548, 39, 'Logged in', 'Authentication', '2026-09-12 02:35:27'),
(549, 39, 'Logged out', 'Authentication', '2026-09-12 02:35:50'),
(550, 90, 'Logged in', 'Authentication', '2026-09-12 02:36:06'),
(551, 90, 'Logged out', 'Authentication', '2026-09-12 02:46:31'),
(552, 44, 'Logged in', 'Authentication', '2026-09-12 02:46:50'),
(553, 44, 'Logged out', 'Authentication', '2026-09-12 02:49:35'),
(554, 1, 'Logged in', 'Authentication', '2026-09-12 02:50:08'),
(555, 1, 'Logged out', 'Authentication', '2026-09-12 02:53:31'),
(556, 39, 'Logged in', 'Authentication', '2026-09-12 02:53:48'),
(557, 39, 'Logged out', 'Authentication', '2026-09-12 02:54:53'),
(558, 1, 'Logged in', 'Authentication', '2026-09-12 02:55:05'),
(559, 1, 'Logged out', 'Authentication', '2026-09-12 02:55:56'),
(560, 39, 'Logged in', 'Authentication', '2026-09-12 02:56:13'),
(561, 39, 'Logged out', 'Authentication', '2026-09-12 02:56:54'),
(562, 90, 'Logged in', 'Authentication', '2026-09-12 02:57:15'),
(563, 90, 'Logged out', 'Authentication', '2026-09-12 02:57:30'),
(564, 59, 'Logged in', 'Authentication', '2026-09-12 02:57:45'),
(565, 59, 'Logged out', 'Authentication', '2026-09-12 02:58:32'),
(566, 45, 'Logged in', 'Authentication', '2026-09-12 02:58:47'),
(567, 45, 'Logged out', 'Authentication', '2026-09-12 02:59:39'),
(568, 1, 'Logged in', 'Authentication', '2026-09-12 03:00:57'),
(569, 1, 'Logged out', 'Authentication', '2026-09-12 03:01:23'),
(570, 43, 'Logged in', 'Authentication', '2026-09-12 03:02:04'),
(571, 1, 'Logged out', 'Authentication', '2026-09-12 04:02:16'),
(572, 1, 'Logged in', 'Authentication', '2026-09-12 04:17:48'),
(573, 1, 'Logged in', 'Authentication', '2026-09-12 04:43:27'),
(574, 1, 'Logged out', 'Authentication', '2026-09-12 04:43:36'),
(575, 1, 'Logged in', 'Authentication', '2026-09-12 04:44:47'),
(576, 1, 'Logged out', 'Authentication', '2026-09-12 04:46:58'),
(577, 45, 'Logged in', 'Authentication', '2026-09-12 04:47:24'),
(578, 1, 'Logged out', 'Authentication', '2026-09-12 04:48:27'),
(579, 39, 'Logged in', 'Authentication', '2026-09-12 04:49:13'),
(580, 39, 'Logged out', 'Authentication', '2026-09-12 04:49:39'),
(581, 1, 'Logged in', 'Authentication', '2026-09-12 04:49:47'),
(582, 45, 'Logged in', 'Authentication', '2026-09-12 04:50:31'),
(583, 45, 'Logged out', 'Authentication', '2026-09-12 04:53:09'),
(584, 59, 'Logged in', 'Authentication', '2026-09-12 04:53:25'),
(585, 1, 'Logged out', 'Authentication', '2026-09-12 04:53:36'),
(586, 45, 'Logged in', 'Authentication', '2026-09-12 04:53:47'),
(587, 45, 'Logged in', 'Authentication', '2026-09-12 04:57:44'),
(588, 45, 'Logged out', 'Authentication', '2026-09-12 05:00:22'),
(589, 39, 'Logged in', 'Authentication', '2026-09-12 05:00:37'),
(590, 45, 'Logged out', 'Authentication', '2026-09-12 05:01:32'),
(591, 1, 'Logged in', 'Authentication', '2026-09-12 05:01:44'),
(592, 1, 'Logged out', 'Authentication', '2026-09-12 05:05:44'),
(593, 39, 'Logged out', 'Authentication', '2026-09-12 05:06:23'),
(594, 45, 'Logged in', 'Authentication', '2026-09-12 05:07:50'),
(595, 59, 'Logged in', 'Authentication', '2026-09-12 05:10:08'),
(596, 59, 'Logged out', 'Authentication', '2026-09-12 05:52:44'),
(597, 1, 'Logged in', 'Authentication', '2026-09-13 06:27:50'),
(598, 45, 'Logged in', 'Authentication', '2026-09-13 15:58:24');

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
(12, 1, NULL, 'Exam', 'Exam will be on Sept.30 2026', 'all', '2026-09-12 04:48:04');

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
  `attachment_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `assessments`
--

INSERT INTO `assessments` (`id`, `syllabus_id`, `topic_id`, `teacher_id`, `title`, `description`, `type`, `max_score`, `due_date`, `is_closed`, `delivery_mode`, `attachment_path`, `created_at`) VALUES
(40, 61, NULL, 90, 'A', 'A', 'assignment', '100.00', NULL, 0, 'both', 'assess_6aa469fb97533.png', '2026-09-11 20:52:12'),
(41, 67, NULL, 45, 'sdf', 'sdf', 'quiz', '100.00', NULL, 0, 'both', NULL, '2026-09-11 22:29:12'),
(43, 67, NULL, 45, 'test', 'test', 'quiz', '100.00', NULL, 0, 'both', NULL, '2026-09-12 04:58:17'),
(44, 67, NULL, 45, 'Test', 'Test', 'quiz', '100.00', NULL, 0, 'both', NULL, '2026-09-12 05:09:48');

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
(54, 5, 'ISSMA413', 'IS Strategy,Management And Acquisition', '', 3, 4, '1st', 'active', '2026-08-24 08:19:15'),
(59, 5, 'ADET413', 'Application Development and Emerging Technologies', '', 3, 4, '1st', 'active', '2026-08-24 16:14:55'),
(60, 5, 'HCI413', 'HUMAN COMPUTER INTERACTION', '', 3, 4, '1st', 'active', '2026-08-24 16:16:19'),
(65, 5, 'PROMAN413', 'IS Project Management2', '', 3, 4, '1st', 'active', '2026-08-24 16:17:29'),
(66, 5, 'CAP413', 'Capstone2', '', 2, 4, '1st', 'active', '2026-08-24 16:18:03'),
(70, 5, 'ADV08', 'Data Mining', '', 3, 4, '1st', 'active', '2026-08-24 16:18:38');

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
(85, 59, 61, '2026-09-11 20:50:31', 'enrolled'),
(89, 59, 50, '2026-09-12 02:49:18', 'enrolled'),
(90, 76, 50, '2026-09-12 02:49:28', 'enrolled'),
(91, 54, 67, '2026-09-13 16:16:36', 'enrolled');

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
(25, NULL, 61, 90, 'Syllabus', '', 'document', 'mat_6aa4bcfc3412a.pdf', '', 'both', '2026-09-12 02:46:20'),
(26, NULL, 50, 44, 'Syllabus', 'in the file', 'document', 'mat_6aa4bd797034d.pdf', '', 'both', '2026-09-12 02:48:25'),
(27, NULL, 67, 45, 'Syllabus', '', 'document', 'mat_6aa4c0128459e.pdf', '', 'both', '2026-09-12 02:59:30'),
(28, NULL, 67, 45, 'Syllabus', '', 'document', 'mat_6aa4daa771be3.pdf', '', 'both', '2026-09-12 04:52:54'),
(31, NULL, 69, 39, 'Introduction to is project management', '', 'document', 'mat_6aa4dd5208873.pdf', '', 'both', '2026-09-12 05:04:17');

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
(33, 40, 59, 'sub_6aa46a10eee8d.jpg', '', '7.00', '', '2026-09-11 20:52:33', '2026-09-11 20:52:58', 'graded'),
(34, 41, 59, NULL, '', NULL, NULL, '2026-09-11 22:38:22', NULL, 'submitted'),
(35, 43, 59, NULL, '', NULL, NULL, '2026-09-12 05:10:37', NULL, 'submitted'),
(36, 44, 59, NULL, '', '4.50', '', '2026-09-12 05:11:32', '2026-09-13 16:18:14', 'graded');

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
  `syllabus_file` varchar(255) DEFAULT NULL,
  `external_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `syllabi`
--

INSERT INTO `syllabi` (`id`, `course_id`, `teacher_id`, `academic_year`, `semester`, `section_name`, `course_description`, `course_outcomes`, `grading_system`, `status`, `image_path`, `syllabus_file`, `external_url`, `created_at`, `updated_at`) VALUES
(47, 60, 40, '2025-2026', '1st', '', 'in the file', 'in the file', NULL, 'published', NULL, 'sylfile_6aa408b09e9ad.pdf', '', '2026-09-11 13:57:04', '2026-09-11 13:57:04'),
(48, 54, 43, '2025-2026', '1st', '', 'in the file', 'in the file', NULL, 'published', 'syl_6aa4c12a92788.webp', 'sylfile_6aa409f311da0.pdf', '', '2026-09-11 14:02:26', '2026-09-12 03:04:10'),
(50, 59, 44, '2025-2026', '1st', '', 'in the file', 'in the file', NULL, 'published', NULL, 'sylfile_6aa40c566518d.pdf', '', '2026-09-11 14:12:38', '2026-09-11 14:12:38'),
(61, 66, 90, '2025-2026', '1st', '', 'Capstone 2 focuses on the completion and implementation of an Information System Project', '', NULL, 'published', NULL, NULL, '', '2026-09-11 20:49:25', '2026-09-12 02:40:21'),
(67, 70, 45, '2025-2026', '1st', '', '=', '=', NULL, 'published', NULL, 'sylfile_6aa47abedf1ec.pdf', '', '2026-09-11 22:03:42', '2026-09-11 23:00:46'),
(69, 65, 39, '2025-2026', '1st', NULL, 'test', 'test', NULL, 'published', NULL, NULL, NULL, '2026-09-12 05:01:09', '2026-09-12 05:01:57');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completion_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `syllabus_topics`
--

INSERT INTO `syllabus_topics` (`id`, `syllabus_id`, `week_number`, `topic_title`, `topic_description`, `learning_outcomes`, `delivery_mode`, `online_platform`, `resources`, `assessment_type`, `sort_order`, `created_at`, `is_completed`, `completion_notes`) VALUES
(99, 61, 1, '1. Project Review and Requirements', 'Focuses on implementation of Information System', 'Complete and Implement an Information System based on approved requirementband design specification', 'online', '', '', '', 0, '2026-09-12 02:43:33', 0, NULL);

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
(1, 0, '2026-09-11 15:02:39');

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
(16, 'Niel', '$2y$10$mj6nWXhizwfGoDebjfb/xu4rC/1BnugW9LQphb2U4/Iv33VPZxkRi', 'Niel John Marcial', 'Niel.Marcial@gmail.com', 'student', NULL, 'active', '2026-03-17 09:03:42', '2026-03-17 09:03:42'),
(29, 'admin2', '$2y$10$WfoqA.t4AmT4wODqhnH9xuyhARJNRn/ZZBIfiP6jcJAx9oCE7Eq2q', 'Rafael Claveria', 'Rafael.Claveria@gmail.com', 'admin', NULL, 'active', '2026-03-27 15:08:42', '2026-03-27 15:11:56'),
(39, 'Albert Buenafe', '$2y$10$xJtQhYeckJsABE.K7gue3OiTNBhIxLZda6UFFrGxAZ2zkp/sD7PZy', 'Albert  Buenafe', 'albertbuenafe@gmail.com', 'teacher', NULL, 'active', '2026-09-10 00:30:09', '2026-09-10 00:30:09'),
(40, 'Eazylle Conception', '$2y$10$HLnjO6eXIq.H/urasgfao.VGlm3eRfcYal4.XxqpBsnCRPOO5etUK', 'Eazylle Conception', 'eazylleconception@gmail.com', 'teacher', NULL, 'active', '2026-09-10 00:31:23', '2026-09-10 00:31:23'),
(43, 'Famie', '$2y$10$A3zO0NdSuv4uMFmgn8tPE.OvRYeRWM1jMwuBOPENbfnbRKk1QzAKW', 'Famie Rose Bilbao', 'famierose@gmail.com', 'teacher', NULL, 'active', '2026-09-11 13:58:35', '2026-09-11 13:58:35'),
(44, 'Redgie', '$2y$10$WxzrlaFrph1K9YdGw/OByuVrR9NW5sNakr82shyAtpyZrev0mSzu6', 'Redgie Pomario', 'redgiepomario@gmail.com', 'teacher', NULL, 'active', '2026-09-11 13:59:23', '2026-09-11 13:59:23'),
(45, 'Jeffred', '$2y$10$JuJWhrEYay1Pss4UiJyoyOXA9PEQG7fWZHKcdWvErpxrd9R82jCRi', 'Jeffred Lim', 'jeffredlim@gmail.com', 'teacher', NULL, 'active', '2026-09-11 14:09:16', '2026-09-11 14:09:16'),
(46, 'Abrasdo', '$2y$10$0fyPxCR902zgZerdWC5iq.T8qytmRmmrrvxyPnPB0uvoMzRGOCOUq', 'Abrasado,Jhona', 'abrasadojhona@gmail.com', 'student', NULL, 'active', '2026-09-11 14:19:21', '2026-09-11 14:19:21'),
(47, 'Agata', '$2y$10$pVRh9wVyGxYr9k1tIg1h3OA7sA99QQ7eNd2xFe9JBQWulZesUIDY6', 'Jenille Agata', 'jenilleagata@gmail.com', 'student', NULL, 'active', '2026-09-11 14:20:55', '2026-09-11 14:20:55'),
(48, 'Aguillion', '$2y$10$5P0uVtKGpSFoazNkwAOeHOGrR7eyjwtZe5wBYFmc1wtmrSqNdt/bC', 'Christopher Jay Aguillion', 'cristopherjayaguillion@gmail.com', 'student', NULL, 'active', '2026-09-11 14:21:51', '2026-09-11 14:21:51'),
(49, 'Aguirre', '$2y$10$ZVBltg/8uqFm.3IERXl7hOIho8sOKD.mMTIeUW7BeFolup6huFy3K', 'Christine Aguirre', 'christineaguirre@gmail.com', 'student', NULL, 'active', '2026-09-11 14:22:41', '2026-09-11 14:22:41'),
(50, 'Alvior', '$2y$10$v35VXMuW5878hw2szzFV0O0dahlaQpt4zJEwg.9zge3QpSLNo1sYO', 'Winnie Andree Alvior', 'winnieandreealvior@gmail.com', 'student', NULL, 'active', '2026-09-11 14:23:22', '2026-09-11 14:23:22'),
(51, 'Balmera', '$2y$10$yK8z/B2Ub0o66cRY3yAiOeH4CK5fZdZYzECuLBSGEBWwdPi6t5S6C', 'Jenny Balmera', 'jennybalmera@gmail.com', 'student', NULL, 'active', '2026-09-11 14:23:51', '2026-09-11 14:23:51'),
(52, 'Bellanio', '$2y$10$j3xBv88w1A6l0rplV0qko.B1pdvP9Q8r7r/5OHoSCbOPJyW69LBeK', 'Sherry Lou Bellanio', 'sherryloubellanio@gmail.com', 'student', NULL, 'active', '2026-09-11 14:24:37', '2026-09-11 14:24:37'),
(53, 'Billiones', '$2y$10$VT8hwS8EwlPdGClw3nG9deeAa5gRkRP6tn9DKbcGiMxQIpeZw8gaG', 'Khea Billiones', 'kheabilliones@gmail.com', 'student', NULL, 'active', '2026-09-11 14:25:47', '2026-09-11 14:25:47'),
(54, 'Campos', '$2y$10$yogvnHlTeUXjQTaWdCboO.fauYC5qmYVjv6o6kL//JLrReijrFN6q', 'Hercan  John Campos', 'hercanjohncampos@gmail.com', 'student', NULL, 'active', '2026-09-11 14:26:50', '2026-09-11 14:26:50'),
(55, 'Camposa', '$2y$10$zzRyaPq8UlI.1lNOIIGjUuz6Mu7QN.EBlSaCMT1LTrUNlb2QZekRm', 'Rodgie Camposa Jr', 'rodjiecamposa@gmail.com', 'student', NULL, 'active', '2026-09-11 14:27:37', '2026-09-11 14:27:37'),
(56, 'Caseres', '$2y$10$WzjxGhC46cmDH6xK3rGKfejhT/6YeOgpOYQT7wQz6fEbCjE1cQmOC', 'Jessa Mae Caseres', 'jessamaecaseres@gmail.com', 'student', NULL, 'active', '2026-09-11 14:28:34', '2026-09-11 14:28:34'),
(57, 'Clamor', '$2y$10$pWQWLp.URx3QF4oi7X/u1e7wBq2OkRUBQlk1PULy4QXAR1RcNs8aq', 'Thresia Ann Clamor', 'thresiaannclamor@gmail.com', 'student', NULL, 'active', '2026-09-11 14:29:53', '2026-09-11 14:29:53'),
(58, 'david', '$2y$10$O87L8emFCmeonsaMIKtAN.KY/KQHv4TswDMKu72nDFiFKNsyWS8cG', 'Reno David', 'renodavid@gmail.com', 'student', NULL, 'active', '2026-09-11 14:30:23', '2026-09-11 14:57:59'),
(59, 'Cristian', '$2y$10$azGQklOSk8EgWsc0/QrVpeE/UXxaRnVWUwUXNDD5rrtS8/EybwE7.', 'Cristian Felix Demateo', 'cristianfelix@gmail.com', 'student', NULL, 'active', '2026-09-11 14:31:42', '2026-09-11 14:31:42'),
(60, 'Justine', '$2y$10$9It40BZFhZQt4.10S1ZR7ekUNR.YVgCcXyjn5LW5y9B/hnsJaVt0W', 'Justine Joy Deniola', 'deniolajustinejoy171@gmail.com', 'student', NULL, 'active', '2026-09-11 14:32:50', '2026-09-11 14:32:50'),
(61, 'Dexter', '$2y$10$xFNh3Jqp.pzCx9VcOucPauHU4qOaZ5xbQMNbrRtfj.EpB5jvi15La', 'John Dexter Detomal', 'detomal@gmail.com', 'student', NULL, 'active', '2026-09-11 14:33:38', '2026-09-11 14:33:38'),
(62, 'Randell', '$2y$10$rYKW1IIxGj5JcK4LK4ENB.Tvo8str./sdunP5oijtttbrcVXOhlJS', 'Randell John Doromal', 'doromal@gmail.com', 'student', NULL, 'active', '2026-09-11 14:34:20', '2026-09-11 14:34:20'),
(63, 'Abigail', '$2y$10$Yn5dmK7DniBp4cp81/CzLuj8umEMAxWigZJwQzXDXthKLGnxTw7NC', 'Abigail Marie Ebanez', 'ebanez@gmail.com', 'student', NULL, 'active', '2026-09-11 14:35:10', '2026-09-11 14:35:10'),
(64, 'Franniel', '$2y$10$pcKH9rekyiiNLr3YbctZwuGkOOfGhotRR5Ldyu3/k3iwU4EQcWTte', 'Franniel Emmanuel', 'emmanuel@gmail.com', 'student', NULL, 'active', '2026-09-11 14:35:50', '2026-09-11 14:35:50'),
(65, 'Renalyn', '$2y$10$d.y9tyxCJRGpQ8sdeDJ6/OKSm2ueJx94gqs3SHTNwuboS3IAAuFG2', 'Renalyn Engalla', 'engalla@gmail.com', 'student', NULL, 'active', '2026-09-11 14:36:36', '2026-09-11 14:36:36'),
(66, 'Michael', '$2y$10$AIAkToxl68pQ3Ecb/BW1QOrpdpRWXRVBnYHvn3citVioSMoWPZq5y', 'John Michael Escobar', 'escobar@gmail.com', 'student', NULL, 'active', '2026-09-11 14:37:07', '2026-09-11 14:37:07'),
(67, 'Yuan', '$2y$10$4OniSzs5CLTKJ8O2pxjAB./wXuljFGwXn.VjaF5Sc0DmN3FOcr8hq', 'Jammir Yuan Gomez', 'gomez@gmail.com', 'student', NULL, 'active', '2026-09-11 14:37:38', '2026-09-11 14:37:38'),
(68, 'Johanna', '$2y$10$i0yGKsDZIBfsM0dqXEidcu59yX9vj1lcjBVFuNtdhWNXsoE53P9C2', 'Johanna Granflor', 'granflor@gmail.com', 'student', NULL, 'active', '2026-09-11 14:38:29', '2026-09-11 14:38:29'),
(69, 'Jay', '$2y$10$0kFBP89o19uabR5olywDiOxHHuN15./AyiNSH8qtRCmPSiJ5.g33e', 'Jay Oliver Helhang', 'helhang@gmail.com', 'student', NULL, 'active', '2026-09-11 14:39:08', '2026-09-11 14:39:08'),
(70, 'Jiro', '$2y$10$D39jZ8pgsuZbfY99rdVe/eSYMdLbLcmtv6kIEUun8wPYLZRSLPpBe', 'Jiro Ignacio', 'ignacio@gmail.com', 'student', NULL, 'active', '2026-09-11 14:39:41', '2026-09-11 14:39:41'),
(71, 'archie', '$2y$10$N/ebBo3E0cPcmfGvxgTgGeKHi0HNWWivKpLdv7WA/i7fb1fz8oaqK', 'Archie Jalea', 'jalea@gmail.com', 'student', NULL, 'active', '2026-09-11 14:42:34', '2026-09-11 14:42:34'),
(72, 'Javier', '$2y$10$xr7irtFQtxGLg21j.haIruysfBjYrcYc1aOWmPQVXceSALs.tSDPG', 'Kenjie Javier', 'javierkenji@gmail.com', 'student', NULL, 'active', '2026-09-11 14:45:13', '2026-09-11 14:45:13'),
(73, 'Manangkil', '$2y$10$zRXeQbBlJGIpHKFHhXa/4uAZybDtqYTgWrqOsdR9.huc9aTnWP0Ge', 'Rojan Joash Manangkil', 'manangkilrojan@gmail.com', 'student', NULL, 'active', '2026-09-11 14:46:17', '2026-09-11 14:46:17'),
(74, 'Marcial', '$2y$10$SzKR0ePunTLDsunMMpAuzeQFhuU62LBSo50NK/Epa1jtLkEWfpvG2', 'Niel John Marcial', 'Nieljanmarcial@gmail.com', 'student', NULL, 'active', '2026-09-11 14:46:59', '2026-09-11 14:46:59'),
(75, 'Miranda', '$2y$10$F1YkTlx.BhNzLHVOnzThxu.G20kBF1vniWFakSS78XG15dMtIptGG', 'Diane Miranda', 'dianemiranda@gmail.com', 'student', NULL, 'active', '2026-09-11 14:47:38', '2026-09-11 14:47:38'),
(76, 'Nomat', '$2y$10$3.aTg3lKqFRo95vfLwo6a.AxuDWzi11J9F/6oIXPrJKq0V7t/Bl2O', 'Emmanuelle Margo Nomat', 'emmanuellemargonomat@gmail.com', 'student', NULL, 'active', '2026-09-11 14:48:51', '2026-09-11 14:48:51'),
(77, 'Ofracio', '$2y$10$5gphH4h9QWpztFWRNYK6heESJetiR7fFJ6R6jnPl4MUdbndts0eYm', 'Kenneth Joseph Ofracio', 'kennethjosephofracio@gmail.com', 'student', NULL, 'active', '2026-09-11 14:49:21', '2026-09-11 14:49:21'),
(78, 'Palanog', '$2y$10$pv8D3H3w6KW6Ay1uAjkd/OU4FqmC84YySkcv9V6vyuUnhQqNiQEd.', 'John Lloyd Palanog', 'johnlloydpalanog@gmail.com', 'student', NULL, 'active', '2026-09-11 14:50:01', '2026-09-11 14:50:01'),
(79, 'Pampag', '$2y$10$mwSVT1ODsMLx096OKzzvqu39y7YbvwwZwBwjAh1rIuPLF9BAFK0Ca', 'Green Mark Pampag', 'greenmarkpampag@gmail.com', 'student', NULL, 'active', '2026-09-11 14:50:26', '2026-09-11 14:50:26'),
(80, 'Panoncio', '$2y$10$VxCuZB1.6Q7A4r7vj4neq.F8ukS/tbwyHbWdH0k3sHiIJ2Y6BX/rK', 'Qiarah Jean Panoncio', 'qiarahjeanpanoncio@gmail.com', 'student', NULL, 'active', '2026-09-11 14:50:55', '2026-09-11 14:50:55'),
(81, 'Relor', '$2y$10$nshjJH.wGEdAWjufiShA5.nkdbLD/9OzV4fnLycDKzv4yIDGJDney', 'Joan Relor', 'joanrelor@gmail.com', 'student', NULL, 'active', '2026-09-11 14:51:36', '2026-09-11 14:51:36'),
(82, 'Rico', '$2y$10$CHhQV87iP9s.kuy9GkOO0O.Zw2y6wpHSPFQ9.cZBok1o0U1a3u/ky', 'Cris John Rico', 'crisjohnrico@gmail.com', 'student', NULL, 'active', '2026-09-11 14:52:08', '2026-09-11 14:52:08'),
(83, 'JohndelRico', '$2y$10$JduZKJxiJHnIOeJpSeV3putt/plf8jNMf2TcZHsSAYZGEmzOGZgri', 'Johndel Rico', 'johndelrico@gmail.com', 'student', NULL, 'active', '2026-09-11 14:53:17', '2026-09-11 14:53:17'),
(84, 'Romo', '$2y$10$HG0SqJoR8ZaIsinzW4pNxe.uEszZpct.SP/UcRo1.4zYCYia.Y3Im', 'Regan Romo', 'reganromo@gmail.com', 'student', NULL, 'active', '2026-09-11 14:53:51', '2026-09-11 14:53:51'),
(85, 'Saraus', '$2y$10$/iWyOU.xFah1T/3htqOwUeWj7W9aaxK0oWFJygFfpR64ZPr56s0cO', 'Natasha Andrea Saraus', 'natashaandreasaraus@gmail.com', 'student', NULL, 'inactive', '2026-09-11 14:54:27', '2026-09-11 14:58:09'),
(86, 'Tenepre', '$2y$10$rwM3Qpx03.w4kYiYRO6A2.YCFNKpytr7k3o/3VLI/2Y2.A2Kh5qSW', 'Charlene Tenepre', 'charlenetenepre@gmail.com', 'student', NULL, 'active', '2026-09-11 14:54:55', '2026-09-11 14:54:55'),
(87, 'Tirol', '$2y$10$Rx1NT5HO//vwYgnjlbZ01eUNuClfAPPIQ8/RVw2M26oxnikm6DuoC', 'John Benz Tirol', 'johnbenztirol@gmail.com', 'student', NULL, 'active', '2026-09-11 14:55:19', '2026-09-11 14:55:19'),
(88, 'Tobongbanua', '$2y$10$mRs6WD2C3QPKUQE5euVZ/eFXktEf7xq2cgoayb6SygB9NcnAsF/me', 'Aljohn Tobongbanua', 'aljohntobongbanua@gmail.com', 'student', NULL, 'active', '2026-09-11 14:56:05', '2026-09-11 14:56:05'),
(89, 'Villarin', '$2y$10$rIqREUZXCSBEPz87WOq05OjrW5WeHl/pNQjv5D0cb/V55x4.vxz/O', 'Vincent Villarin', 'vincentvillarin@gmail.com', 'student', NULL, 'active', '2026-09-11 14:56:55', '2026-09-11 14:56:55'),
(90, 'Jacildo', '$2y$10$aCSsI4bSYN1jqVDy8gomtuyDsnnXr7vp158qFnTIxgwgnCoVpEjJW', 'Kaye Jacildo', 'kayejacildo@gmail.com', 'teacher', NULL, 'active', '2026-09-11 20:48:17', '2026-09-11 22:41:34');

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=599;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `assessments`
--
ALTER TABLE `assessments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `enrollments`
--
ALTER TABLE `enrollments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=92;

--
-- AUTO_INCREMENT for table `learning_materials`
--
ALTER TABLE `learning_materials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `query_logs`
--
ALTER TABLE `query_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `syllabi`
--
ALTER TABLE `syllabi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `topic_done_status`
--
ALTER TABLE `topic_done_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `topic_progress`
--
ALTER TABLE `topic_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `topic_week_done`
--
ALTER TABLE `topic_week_done`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

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
