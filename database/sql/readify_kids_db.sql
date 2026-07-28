-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2026 at 08:03 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `readify_kids_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `achievements`
--

CREATE TABLE `achievements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `achievement_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `criteria` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `reading_material_id` bigint(20) UNSIGNED DEFAULT NULL,
  `activity_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `activity_type` enum('Phonics','Vocabulary','Word Recognition','Sound Blending','Read Aloud','Word Game') NOT NULL,
  `level` int(11) NOT NULL,
  `difficulty_level` enum('Easy','Medium','Hard') DEFAULT NULL,
  `duration_minutes` int(11) NOT NULL DEFAULT 15,
  `points_reward` int(11) NOT NULL DEFAULT 10,
  `is_published` tinyint(1) NOT NULL DEFAULT 0,
  `allow_reattempt` tinyint(1) NOT NULL DEFAULT 1,
  `adaptive_difficulty` tinyint(1) NOT NULL DEFAULT 1,
  `battle_mode` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `teacher_id`, `reading_material_id`, `activity_name`, `description`, `activity_type`, `level`, `difficulty_level`, `duration_minutes`, `points_reward`, `is_published`, `allow_reattempt`, `adaptive_difficulty`, `battle_mode`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'The Cat', 'read aloud', 'Word Game', 2, 'Medium', 5, 100, 1, 1, 0, 1, '2026-07-20 01:53:54', '2026-07-21 00:22:15'),
(2, 1, NULL, 'The chicken', 'chick', 'Read Aloud', 1, 'Easy', 2, 60, 1, 1, 0, 0, '2026-07-20 06:57:12', '2026-07-20 06:57:12'),
(3, 1, NULL, 'The chicken', 'chick', 'Read Aloud', 1, 'Easy', 2, 60, 1, 1, 0, 0, '2026-07-20 06:57:49', '2026-07-20 06:57:49'),
(4, 1, 2, 'MAMA', 'MAMAMO', 'Read Aloud', 1, 'Easy', 2, 50, 1, 1, 0, 0, '2026-07-20 22:05:21', '2026-07-20 22:32:13'),
(5, 1, 3, 'The SUN', 'basahin molang kitde', 'Word Game', 1, 'Easy', 3, 460, 1, 1, 0, 1, '2026-07-20 22:57:21', '2026-07-21 00:20:57'),
(6, 1, 4, 'My Mother', 'The Mother Story', 'Read Aloud', 2, 'Medium', 3, 80, 1, 1, 1, 0, '2026-07-21 03:09:13', '2026-07-21 03:09:13'),
(7, 1, 5, 'The Sun', 'Best for young listeners. Uses short sentences and focuses on basic concepts like heat and light.', 'Read Aloud', 2, 'Medium', 3, 500, 1, 1, 1, 0, '2026-07-21 03:11:03', '2026-07-23 18:51:43'),
(8, 1, NULL, 'MELO', 'MELO THE DOG', 'Word Game', 3, 'Hard', 3, 50, 1, 1, 1, 1, '2026-07-21 03:14:11', '2026-07-21 03:14:11'),
(9, 1, NULL, 'Cellphone', 'iphone', 'Word Game', 1, 'Easy', 17, 100, 1, 1, 1, 1, '2026-07-21 23:44:57', '2026-07-21 23:44:57'),
(10, 1, NULL, 'Shet', 'the shet guy', 'Word Game', 1, 'Easy', 6, 100, 1, 1, 1, 1, '2026-07-22 01:02:33', '2026-07-22 01:02:33'),
(11, 1, NULL, 'Run', 'Runner', 'Word Game', 1, 'Easy', 10, 50, 1, 1, 1, 1, '2026-07-22 02:39:47', '2026-07-22 02:39:47'),
(12, 1, NULL, 'The Legend', 'the legend king', 'Word Game', 3, 'Hard', 20, 250, 1, 1, 1, 0, '2026-07-22 04:55:46', '2026-07-22 04:55:46'),
(13, 1, 6, 'The Water Jog', 'big', 'Read Aloud', 2, 'Medium', 2, 50, 1, 1, 1, 0, '2026-07-22 22:27:35', '2026-07-22 22:27:35'),
(14, 1, 7, 'lurenz', 'yeh', 'Read Aloud', 1, 'Easy', 3, 501, 1, 1, 1, 0, '2026-07-23 17:17:52', '2026-07-23 17:17:52');

-- --------------------------------------------------------

--
-- Table structure for table `activity_results`
--

CREATE TABLE `activity_results` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `mistakes` int(11) NOT NULL DEFAULT 0,
  `time_spent` int(11) DEFAULT NULL,
  `attempts` int(11) NOT NULL DEFAULT 1,
  `status` enum('in_progress','completed') NOT NULL DEFAULT 'completed',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_results`
--

INSERT INTO `activity_results` (`id`, `student_id`, `activity_id`, `score`, `mistakes`, `time_spent`, `attempts`, `status`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 65.00, 0, NULL, 1, 'completed', '2026-07-21 06:46:38', '2026-07-20 22:46:38', '2026-07-20 22:46:38'),
(2, 1, 5, 13.60, 0, NULL, 1, 'completed', '2026-07-23 09:03:09', '2026-07-20 23:00:36', '2026-07-23 01:03:09'),
(3, 1, 1, 76.30, 0, NULL, 1, 'completed', '2026-07-27 13:08:20', '2026-07-22 04:51:38', '2026-07-27 05:08:20'),
(4, 2, 5, 13.70, 0, NULL, 1, 'completed', '2026-07-22 14:42:57', '2026-07-22 06:42:57', '2026-07-22 06:42:57'),
(5, 1, 8, 18.10, 0, NULL, 1, 'completed', '2026-07-22 14:51:15', '2026-07-22 06:51:15', '2026-07-22 06:51:15'),
(6, 3, 14, 100.00, 0, NULL, 1, 'completed', '2026-07-24 01:19:12', '2026-07-23 17:19:12', '2026-07-23 17:19:12'),
(7, 3, 1, 38.10, 0, NULL, 1, 'completed', '2026-07-24 02:50:26', '2026-07-23 18:50:26', '2026-07-23 18:50:26'),
(8, 3, 7, 100.00, 0, NULL, 1, 'completed', '2026-07-24 02:53:31', '2026-07-23 18:53:31', '2026-07-23 18:53:31'),
(9, 2, 14, 85.00, 0, NULL, 1, 'completed', '2026-07-24 05:17:40', '2026-07-23 21:17:40', '2026-07-23 21:17:40'),
(10, 1, 9, 42.80, 0, NULL, 1, 'completed', '2026-07-27 13:20:48', '2026-07-27 05:20:48', '2026-07-27 05:20:48');

-- --------------------------------------------------------

--
-- Table structure for table `activity_word_bank`
--

CREATE TABLE `activity_word_bank` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `word` text NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `type` enum('word','phrase','paragraph') NOT NULL DEFAULT 'word',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_word_bank`
--

INSERT INTO `activity_word_bank` (`id`, `activity_id`, `word`, `order`, `type`, `created_at`, `updated_at`) VALUES
(1, 4, 'health', 0, 'word', '2026-07-20 22:32:13', '2026-07-20 22:32:13'),
(2, 4, 'hyper', 1, 'word', '2026-07-20 22:32:13', '2026-07-20 22:32:13'),
(3, 4, 'mole', 2, 'word', '2026-07-20 22:32:13', '2026-07-20 22:32:13'),
(4, 4, 'goal', 3, 'word', '2026-07-20 22:32:13', '2026-07-20 22:32:13'),
(5, 4, 'pass', 4, 'word', '2026-07-20 22:32:13', '2026-07-20 22:32:13'),
(6, 4, 'hot', 5, 'word', '2026-07-20 22:32:13', '2026-07-20 22:32:13'),
(14, 5, 'PEN', 0, 'word', '2026-07-21 00:20:57', '2026-07-21 00:20:57'),
(15, 5, 'BALLPEN', 1, 'word', '2026-07-21 00:20:57', '2026-07-21 00:20:57'),
(16, 5, 'BALL', 2, 'word', '2026-07-21 00:20:57', '2026-07-21 00:20:57'),
(17, 5, 'YUNGSTUNA', 3, 'word', '2026-07-21 00:20:57', '2026-07-21 00:20:57'),
(18, 5, 'AWIT', 4, 'word', '2026-07-21 00:20:57', '2026-07-21 00:20:57'),
(19, 5, 'FINE', 5, 'word', '2026-07-21 00:20:57', '2026-07-21 00:20:57'),
(20, 5, 'SHOWWING', 6, 'word', '2026-07-21 00:20:57', '2026-07-21 00:20:57'),
(21, 1, 'Dark coffe', 0, 'word', '2026-07-21 00:22:15', '2026-07-21 00:22:15'),
(22, 1, 'Brown Sugar', 1, 'word', '2026-07-21 00:22:15', '2026-07-21 00:22:15'),
(23, 1, 'White Milk', 2, 'word', '2026-07-21 00:22:15', '2026-07-21 00:22:15'),
(24, 1, 'big fat', 3, 'word', '2026-07-21 00:22:15', '2026-07-21 00:22:15'),
(25, 8, 'The dog is fast', 0, 'word', '2026-07-21 03:14:11', '2026-07-21 03:14:11'),
(26, 8, 'the dog is short', 1, 'word', '2026-07-21 03:14:11', '2026-07-21 03:14:11'),
(27, 8, 'The dog is running fast', 2, 'paragraph', '2026-07-21 03:14:11', '2026-07-21 03:14:11'),
(28, 8, 'the dog is a beast', 3, 'word', '2026-07-21 03:14:11', '2026-07-21 03:14:11'),
(29, 9, 'cat', 0, 'word', '2026-07-21 23:44:57', '2026-07-21 23:44:57'),
(30, 9, 'big dog', 1, 'word', '2026-07-21 23:44:57', '2026-07-21 23:44:57'),
(31, 9, 'a fat cat sat on a mat', 2, 'paragraph', '2026-07-21 23:44:57', '2026-07-21 23:44:57'),
(32, 9, 'the big red dog ran fast', 3, 'paragraph', '2026-07-21 23:44:57', '2026-07-21 23:44:57'),
(33, 10, 'the', 0, 'word', '2026-07-22 01:02:33', '2026-07-22 01:02:33'),
(34, 10, 'mother', 1, 'word', '2026-07-22 01:02:33', '2026-07-22 01:02:33'),
(35, 10, 'great', 2, 'word', '2026-07-22 01:02:33', '2026-07-22 01:02:33'),
(36, 10, 'defeat', 3, 'word', '2026-07-22 01:02:33', '2026-07-22 01:02:33'),
(37, 10, 'enemy', 4, 'word', '2026-07-22 01:02:33', '2026-07-22 01:02:33'),
(38, 11, 'run', 0, 'word', '2026-07-22 02:39:47', '2026-07-22 02:39:47'),
(39, 11, 'Runner', 1, 'word', '2026-07-22 02:39:47', '2026-07-22 02:39:47'),
(40, 11, 'run fast', 2, 'word', '2026-07-22 02:39:47', '2026-07-22 02:39:47'),
(41, 11, 'run slow', 3, 'word', '2026-07-22 02:39:47', '2026-07-22 02:39:47'),
(42, 11, 'go run', 4, 'word', '2026-07-22 02:39:47', '2026-07-22 02:39:47'),
(43, 12, 'The legend of titan', 0, 'word', '2026-07-22 04:55:46', '2026-07-22 04:55:46'),
(44, 12, 'You need something else', 1, 'phrase', '2026-07-22 04:55:46', '2026-07-22 04:55:46'),
(45, 12, 'I want to kill you', 2, 'word', '2026-07-22 04:55:46', '2026-07-22 04:55:46'),
(46, 12, 'Should i go in the river', 3, 'paragraph', '2026-07-22 04:55:46', '2026-07-22 04:55:46'),
(47, 12, 'I want something to eat', 4, 'paragraph', '2026-07-22 04:55:46', '2026-07-22 04:55:46'),
(48, 12, 'The legend is making some mistake', 5, 'paragraph', '2026-07-22 04:55:46', '2026-07-22 04:55:46');

-- --------------------------------------------------------

--
-- Table structure for table `badges`
--

CREATE TABLE `badges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `badge_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `badge_icon` varchar(255) DEFAULT NULL,
  `criteria` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `enemies`
--

CREATE TABLE `enemies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `sprite` varchar(255) NOT NULL,
  `max_hp` int(11) NOT NULL,
  `level` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enemies`
--

INSERT INTO `enemies` (`id`, `name`, `sprite`, `max_hp`, `level`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Letter Goblin', '👺', 500, 1, 'A sneaky goblin who scrambles letters!', '2026-07-20 01:42:27', '2026-07-20 01:42:27'),
(2, 'Word Witch', '🧙‍♀️', 750, 2, 'A witch who casts word-confusion spells!', '2026-07-20 01:42:27', '2026-07-20 01:42:27'),
(3, 'Story Dragon', '🐉', 1000, 3, 'A fearsome dragon who swallows whole stories!', '2026-07-20 01:42:27', '2026-07-20 01:42:27');

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `recording_id` bigint(20) UNSIGNED NOT NULL,
  `pronunciation_score` int(11) DEFAULT NULL,
  `fluency_score` int(11) DEFAULT NULL,
  `accuracy_score` int(11) DEFAULT NULL,
  `comprehension_score` int(11) DEFAULT NULL,
  `proficiency_level` enum('Beginner','Developing','Proficient','Advanced') DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`id`, `teacher_id`, `recording_id`, `pronunciation_score`, `fluency_score`, `accuracy_score`, `comprehension_score`, `proficiency_level`, `feedback`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, 4, 3, 2, 'Developing', 'oy galing ni kupal a', '2026-07-20 22:46:38', '2026-07-20 22:46:38'),
(2, 1, 2, 5, 4, 4, 5, 'Developing', 'goog job', '2026-07-20 23:00:36', '2026-07-20 23:00:36'),
(3, 1, 6, 5, 5, 5, 5, 'Proficient', NULL, '2026-07-23 17:19:12', '2026-07-23 17:19:12'),
(4, 1, 7, 5, 5, 5, 5, 'Proficient', NULL, '2026-07-23 18:53:31', '2026-07-23 18:53:31'),
(5, 1, 8, 5, 4, 3, 5, 'Developing', NULL, '2026-07-23 21:17:40', '2026-07-23 21:17:40');

-- --------------------------------------------------------

--
-- Table structure for table `game_rounds`
--

CREATE TABLE `game_rounds` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `game_session_id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `word_or_passage` varchar(1000) NOT NULL,
  `recording_path` varchar(255) DEFAULT NULL,
  `ml_score` decimal(5,2) DEFAULT NULL,
  `teacher_score` decimal(5,2) DEFAULT NULL,
  `final_score` decimal(5,2) DEFAULT NULL,
  `damage_dealt` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','ml_scored','teacher_reviewed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_rounds`
--

INSERT INTO `game_rounds` (`id`, `game_session_id`, `student_id`, `word_or_passage`, `recording_path`, `ml_score`, `teacher_score`, `final_score`, `damage_dealt`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Dark coffe', 'game_recordings/FFY8H0QpR3kH2zdIVl1Gsp2z7txMOmiUmLA7fxXx.webm', 95.24, NULL, 95.24, 95, 'ml_scored', '2026-07-22 04:45:29', '2026-07-22 04:45:29'),
(2, 1, 1, 'Brown Sugar', 'game_recordings/LCRAZVTJLmP5HKvITkorQC2Vs6bcWN8OsvjdPdbA.webm', 44.44, NULL, 44.44, 44, 'ml_scored', '2026-07-22 04:46:24', '2026-07-22 04:46:24'),
(3, 1, 1, 'White Milk', 'game_recordings/CwXZBmhJjd2nomUJMSl2F41Ad6qxEEvqRRDYUVhg.webm', 73.68, NULL, 73.68, 74, 'ml_scored', '2026-07-22 04:47:51', '2026-07-22 04:47:51'),
(4, 1, 1, 'big fat', 'game_recordings/Q1F1UQ0Wy58njXsTxj307YpkRZwUQnQkDYBwAi0J.webm', 33.33, NULL, 33.33, 33, 'ml_scored', '2026-07-22 04:48:25', '2026-07-22 04:48:25'),
(5, 1, 1, 'Dark coffe', 'game_recordings/rwuUvdMQHlljFD8zfRDYeN2El3lifhwGswIX5Qnv.webm', 64.00, NULL, 64.00, 64, 'ml_scored', '2026-07-22 04:50:26', '2026-07-22 04:50:26'),
(6, 1, 1, 'Brown Sugar', 'game_recordings/zgnGt6M0NwuaUYQ4TsLQo9vhQQOZrxhSrYA40g95.webm', 100.00, NULL, 100.00, 100, 'ml_scored', '2026-07-22 04:50:47', '2026-07-22 04:50:47'),
(7, 1, 1, 'White Milk', 'game_recordings/vqKhVdgSUtuO0VmGGOWOJT7jFTufkzD6oK1EyDiJ.webm', 31.58, NULL, 31.58, 32, 'ml_scored', '2026-07-22 04:51:11', '2026-07-22 04:51:11'),
(8, 1, 1, 'big fat', 'game_recordings/0BQhdOX0QDvORKoFKbhtqupvcPR3eAn8HtQUA2x6.webm', 62.50, NULL, 62.50, 63, 'ml_scored', '2026-07-22 04:51:38', '2026-07-22 04:51:38'),
(9, 6, 1, 'The dog is fast', 'game_recordings/FeTPRMz4cAuWfvebMK7iSD3kyjf3nizndezCsm6b.webm', 96.77, NULL, 96.77, 194, 'ml_scored', '2026-07-22 04:58:00', '2026-07-22 04:58:00'),
(10, 8, 2, 'PEN', 'game_recordings/U1sE7BZgk8QIti58XjJEq7zppRwWp4I7hcXgarzd.webm', 57.14, NULL, 57.14, 57, 'ml_scored', '2026-07-22 06:40:08', '2026-07-22 06:40:08'),
(11, 8, 2, 'BALLPEN', 'game_recordings/1AZ9bY2vAZiCumbAjyl69TXJ28HNDfYMNOB5I8fE.webm', 52.63, NULL, 52.63, 53, 'ml_scored', '2026-07-22 06:40:45', '2026-07-22 06:40:45'),
(12, 8, 2, 'BALL', 'game_recordings/0ld0MNCQ81eusnMQu9YYGrNmi4S8QBV74bDYpvz0.webm', 100.00, NULL, 100.00, 100, 'ml_scored', '2026-07-22 06:41:18', '2026-07-22 06:41:18'),
(13, 8, 2, 'YUNGSTUNA', 'game_recordings/TfpIi50ljKCWqiJ4fEB3syEhMPVIHq15zZUBbLXd.webm', 73.68, NULL, 73.68, 74, 'ml_scored', '2026-07-22 06:41:32', '2026-07-22 06:41:32'),
(14, 8, 2, 'AWIT', 'game_recordings/wD5kzrn6ieVTzGwxlA0dWpjSmKQubZclWzcsUKDK.webm', 14.81, NULL, 14.81, 15, 'ml_scored', '2026-07-22 06:41:56', '2026-07-22 06:41:56'),
(15, 8, 2, 'FINE', 'game_recordings/POcmgQbmZFVqY0ScLA8iTpX8CREWYPRBTETVCU3X.webm', 100.00, NULL, 100.00, 100, 'ml_scored', '2026-07-22 06:42:16', '2026-07-22 06:42:16'),
(16, 8, 2, 'SHOWWING', 'game_recordings/K6yWL1WR2lK0GfstXqtJEauv0zfgwLFuTUpOlcK3.webm', 93.33, NULL, 93.33, 93, 'ml_scored', '2026-07-22 06:42:40', '2026-07-22 06:42:40'),
(17, 8, 2, 'PEN', 'game_recordings/YFas0VzuWmWDQG9lyqp3Zv1BgJromiK5brtcdOP3.webm', 57.14, NULL, 57.14, 57, 'ml_scored', '2026-07-22 06:42:56', '2026-07-22 06:42:56'),
(18, 6, 1, 'the dog is short', 'game_recordings/Bf8CDATVQFSjelq6EHeswiM2D2e6Bmv7i5oXgvvh.ogg', 88.24, NULL, 88.24, 176, 'ml_scored', '2026-07-22 06:49:54', '2026-07-22 06:49:54'),
(19, 6, 1, 'The dog is running fast', 'game_recordings/YzLXXBM422xy7LlTgTbwYwzxw1eA9cdUkH6I53lG.ogg', 97.87, NULL, 97.87, 196, 'ml_scored', '2026-07-22 06:50:12', '2026-07-22 06:50:12'),
(20, 6, 1, 'the dog is a beast', 'game_recordings/if5Uhio0qN0pa5pOSeBAiO8XoxPnu83z4aZ8QW1g.ogg', 97.30, NULL, 97.30, 195, 'ml_scored', '2026-07-22 06:50:33', '2026-07-22 06:50:33'),
(21, 6, 1, 'The dog is fast', 'game_recordings/U0PsBapbJPCzvxTF1rgdWYOHnGCcVfMqPivdjaLx.ogg', 96.77, NULL, 96.77, 194, 'ml_scored', '2026-07-22 06:50:59', '2026-07-22 06:50:59'),
(22, 6, 1, 'the dog is short', 'game_recordings/QjHIX6fQpgyTmCBnwOSjqjTbwxY0OdTwpoWbmLL4.ogg', 65.31, NULL, 65.31, 131, 'ml_scored', '2026-07-22 06:51:15', '2026-07-22 06:51:15'),
(23, 5, 1, 'the', 'game_recordings/LCCaV0iBAy7pAlNJJm2F2G39MZPlcRkaFf5bU0vg.webm', 28.57, NULL, 28.57, 29, 'ml_scored', '2026-07-22 23:04:35', '2026-07-22 23:04:35'),
(24, 5, 1, 'mother', 'game_recordings/2pBJicplggBz1AsqgQjxXEu2JkitfRVMW0KdGJz8.webm', 92.31, NULL, 92.31, 92, 'ml_scored', '2026-07-22 23:05:07', '2026-07-22 23:05:07'),
(25, 5, 1, 'great', 'game_recordings/TDLwzzDvsmzhehUbMlUuZieskhfxXv0ODwiIV4Jb.webm', 90.91, NULL, 90.91, 91, 'ml_scored', '2026-07-22 23:05:29', '2026-07-22 23:05:29'),
(26, 5, 1, 'defeat', 'game_recordings/DR0XYfWNSHWpLS8jZrJEaMjGmSvdWxdIFY1coJU9.webm', 100.00, NULL, 100.00, 100, 'ml_scored', '2026-07-22 23:05:50', '2026-07-22 23:05:50'),
(27, 5, 1, 'enemy', 'game_recordings/DMzfcbIsGnK5nmlw2UmRFhklJL3OXWKuNj5BJgoz.webm', 100.00, NULL, 100.00, 100, 'ml_scored', '2026-07-22 23:06:05', '2026-07-22 23:06:05'),
(28, 7, 1, 'Dark coffe', 'game_recordings/oHF0sdGZm1LergyG7yjyNNDMVwUev8qMgiwsF7kQ.webm', 58.33, NULL, 58.33, 87, 'ml_scored', '2026-07-23 01:00:01', '2026-07-23 01:00:01'),
(29, 7, 1, 'Brown Sugar', 'game_recordings/ftqFsPXEFrBQYsBsdnyw05nSE64mhJkb1OcGClqB.webm', 100.00, NULL, 100.00, 150, 'ml_scored', '2026-07-23 01:00:13', '2026-07-23 01:00:13'),
(30, 7, 1, 'White Milk', 'game_recordings/L2S5LRNFClX1tD5vm8xKmycK0tDTezIpfm1FXsod.webm', 95.24, NULL, 95.24, 143, 'ml_scored', '2026-07-23 01:00:27', '2026-07-23 01:00:27'),
(31, 7, 1, 'big fat', 'game_recordings/Wy1tRQaLPjCYIXmRSQhdxPkkPKfdMPTWOecnpKtF.webm', 93.33, NULL, 93.33, 140, 'ml_scored', '2026-07-23 01:00:41', '2026-07-23 01:00:41'),
(32, 7, 1, 'Dark coffe', 'game_recordings/4az7jryla6ZVWRiQwIPSJKBH0rAuZdOxsotmuUbQ.webm', 95.24, NULL, 95.24, 143, 'ml_scored', '2026-07-23 01:00:57', '2026-07-23 01:00:57'),
(33, 7, 1, 'Brown Sugar', 'game_recordings/oWrZiCOEi2O6LbuE3wpKDCu3hahOyK4dgxpAvd62.webm', 100.00, NULL, 100.00, 150, 'ml_scored', '2026-07-23 01:01:11', '2026-07-23 01:01:11'),
(34, 2, 1, 'PEN', 'game_recordings/Y8wted7uzPp0soKO7FfhjeJJlWXbYwCoXlvmm7HD.webm', 66.67, NULL, 66.67, 67, 'ml_scored', '2026-07-23 01:01:50', '2026-07-23 01:01:50'),
(35, 2, 1, 'BALLPEN', 'game_recordings/7B7wIy5R8bn42jxT7T8YrVxTmZiNvLk3aZ8HROlX.webm', 93.33, NULL, 93.33, 93, 'ml_scored', '2026-07-23 01:02:01', '2026-07-23 01:02:01'),
(36, 2, 1, 'BALL', 'game_recordings/FvgLibggOrszINifLhHK7ndhTXr8XP5JtS2lRDjd.webm', 66.67, NULL, 66.67, 67, 'ml_scored', '2026-07-23 01:02:13', '2026-07-23 01:02:13'),
(37, 2, 1, 'YUNGSTUNA', 'game_recordings/brdI6jyGesSLhH1q71uhEaJB6HC3ropzAgUccKal.webm', 42.86, NULL, 42.86, 43, 'ml_scored', '2026-07-23 01:02:25', '2026-07-23 01:02:25'),
(38, 2, 1, 'AWIT', 'game_recordings/JNWYaTdaDbOoMpm112EdFdjSdQajnXkuu0JmlOKy.webm', 36.36, NULL, 36.36, 36, 'ml_scored', '2026-07-23 01:02:36', '2026-07-23 01:02:36'),
(39, 2, 1, 'FINE', 'game_recordings/Z8wXB1AJJbUA4Mfe8MXiC9rhiQSeVUokdsHHWG9H.webm', 88.89, NULL, 88.89, 89, 'ml_scored', '2026-07-23 01:02:47', '2026-07-23 01:02:47'),
(40, 2, 1, 'SHOWWING', 'game_recordings/Y7ifSm5qN0WlcbWIPYfzbUiYPeIPu1CBOHrjUB5v.webm', 93.33, NULL, 93.33, 93, 'ml_scored', '2026-07-23 01:02:59', '2026-07-23 01:02:59'),
(41, 2, 1, 'PEN', 'game_recordings/418NQ1t74oBr0z7ijql0lNmFgq4YKZItuG1AY5sI.webm', 57.14, NULL, 57.14, 57, 'ml_scored', '2026-07-23 01:03:09', '2026-07-23 01:03:09'),
(42, 11, 3, 'Dark coffe', 'game_recordings/PwNuq7SeNDxhH3AYWhvER48dgzRNFfiDFgtWW5wH.webm', 90.91, NULL, 90.91, 136, 'ml_scored', '2026-07-23 17:30:39', '2026-07-23 17:30:39'),
(43, 11, 3, 'Brown Sugar', 'game_recordings/z45wiE4RKGQDWd3JD912XdqVBSLHE0yb08JO6T9v.webm', 100.00, NULL, 100.00, 150, 'ml_scored', '2026-07-23 18:49:33', '2026-07-23 18:49:33'),
(44, 11, 3, 'White Milk', 'game_recordings/ZgNxfWD4qYXeb0rwezFeK412KwmV2qFngKtwVw0s.webm', NULL, NULL, NULL, 0, 'pending', '2026-07-23 18:50:11', '2026-07-23 18:50:11'),
(45, 11, 3, 'big fat', 'game_recordings/F2schzgiVvQzrsJDm2BnZOsvOJdr7UuouNjcKcpN.webm', NULL, NULL, NULL, 0, 'pending', '2026-07-23 18:50:26', '2026-07-23 18:50:26'),
(46, 4, 1, 'cat', 'game_recordings/OwwkWRTW00hGsagUCMqEQvAs8t2yxNkpn7QEQa5p.webm', 7.69, NULL, 7.69, 8, 'ml_scored', '2026-07-26 20:55:15', '2026-07-26 20:55:15'),
(47, 10, 1, 'Dark coffe', 'game_recordings/9wX9hUfRRaDphIR3qKU191Hi8oVFMccKp7XEP3sm.webm', 95.24, NULL, 95.24, 143, 'ml_scored', '2026-07-26 22:35:52', '2026-07-26 22:35:52'),
(48, 10, 1, 'Brown Sugar', 'game_recordings/RhPknUTCEGntsOhuQOEIEs4r3w06B5f9whgDSoFX.webm', 100.00, NULL, 100.00, 150, 'ml_scored', '2026-07-26 22:36:08', '2026-07-26 22:36:08'),
(49, 10, 1, 'White Milk', 'game_recordings/MYc6VIHeQSeF85gzRvgdS9kOuyc27vkpCPLcOQNc.webm', 58.33, NULL, 58.33, 87, 'ml_scored', '2026-07-26 22:36:50', '2026-07-26 22:36:50'),
(50, 10, 1, 'big fat', 'game_recordings/m6xDpUk1CfOGEeISy358bQpmkhGc5kFJHJLrNwe1.webm', 100.00, NULL, 100.00, 150, 'ml_scored', '2026-07-26 22:37:05', '2026-07-26 22:37:05'),
(51, 15, 1, 'Dark coffe', 'game_recordings/8U9FREFpkbr3MYS9ZPpTGVsPEV3bFarjVGCrNjZf.webm', 85.71, NULL, 85.71, 129, 'ml_scored', '2026-07-27 05:07:37', '2026-07-27 05:07:37'),
(52, 15, 1, 'Brown Sugar', 'game_recordings/sTuYdB5pbYD4cOJcWBe2n90Eznrz0WYtzmOwCNP3.webm', 100.00, NULL, 100.00, 150, 'ml_scored', '2026-07-27 05:07:53', '2026-07-27 05:07:53'),
(53, 15, 1, 'White Milk', 'game_recordings/2vRDMQH8p59o5LutjLXumQ6NMU8lnP1J2AdNE3uj.webm', 95.24, NULL, 95.24, 143, 'ml_scored', '2026-07-27 05:08:03', '2026-07-27 05:08:03'),
(54, 15, 1, 'big fat', 'game_recordings/iNxdWs3ZzTtNquq2euemltaINTBywUsmyCNcUmF9.webm', 100.00, NULL, 100.00, 150, 'ml_scored', '2026-07-27 05:08:20', '2026-07-27 05:08:20'),
(55, 4, 1, 'big dog', 'game_recordings/KZAgdhW2q8cv84Q3oPorrRFpWkrqPOdX0A8Wk6y6.webm', 100.00, NULL, 100.00, 100, 'ml_scored', '2026-07-27 05:19:41', '2026-07-27 05:19:41'),
(56, 4, 1, 'a fat cat sat on a mat', 'game_recordings/VjWmja1BENKWhzUtl4opHaIcZfo9qAKFstTjSKy0.webm', 8.33, NULL, 8.33, 8, 'ml_scored', '2026-07-27 05:20:29', '2026-07-27 05:20:29'),
(57, 4, 1, 'the big red dog ran fast', 'game_recordings/NjQDzGkRMEk84ECbJGsdEBk7Hb6ugD9a0NeEVFNp.webm', 97.96, NULL, 97.96, 98, 'ml_scored', '2026-07-27 05:20:48', '2026-07-27 05:20:48');

-- --------------------------------------------------------

--
-- Table structure for table `game_sessions`
--

CREATE TABLE `game_sessions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `enemy_id` bigint(20) UNSIGNED NOT NULL,
  `enemy_current_hp` int(11) NOT NULL,
  `enemy_max_hp` int(11) NOT NULL,
  `total_damage` int(11) NOT NULL DEFAULT 0,
  `rounds_played` int(11) NOT NULL DEFAULT 0,
  `status` enum('ongoing','won','lost') DEFAULT 'ongoing',
  `points_earned` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `game_sessions`
--

INSERT INTO `game_sessions` (`id`, `student_id`, `activity_id`, `enemy_id`, `enemy_current_hp`, `enemy_max_hp`, `total_damage`, `rounds_played`, `status`, `points_earned`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 1, 0, 500, 505, 8, 'won', 100, '2026-07-20 01:54:13', '2026-07-22 04:51:38'),
(2, 1, 5, 2, 0, 500, 545, 8, 'won', 460, '2026-07-21 00:01:05', '2026-07-23 01:03:09'),
(3, 1, 11, 1, 500, 500, 0, 0, 'ongoing', 0, '2026-07-22 02:41:09', '2026-07-22 02:41:09'),
(4, 1, 9, 1, 286, 500, 214, 4, 'lost', 0, '2026-07-22 03:28:46', '2026-07-27 05:20:48'),
(5, 1, 10, 1, 88, 500, 412, 5, 'ongoing', 0, '2026-07-22 04:10:27', '2026-07-22 23:06:05'),
(6, 1, 8, 3, 0, 1000, 1086, 6, 'won', 50, '2026-07-22 04:57:41', '2026-07-22 06:51:15'),
(7, 1, 1, 2, 0, 750, 813, 6, 'won', 100, '2026-07-22 05:45:17', '2026-07-23 01:01:11'),
(8, 2, 5, 1, 0, 500, 549, 8, 'won', 460, '2026-07-22 06:20:50', '2026-07-22 06:42:57'),
(9, 2, 5, 1, 500, 500, 0, 0, 'ongoing', 0, '2026-07-22 06:46:59', '2026-07-22 06:46:59'),
(10, 1, 1, 2, 220, 750, 530, 4, 'lost', 0, '2026-07-23 01:19:31', '2026-07-26 22:37:06'),
(11, 3, 1, 2, 464, 750, 286, 4, 'lost', 0, '2026-07-23 17:30:25', '2026-07-23 18:50:26'),
(12, 3, 5, 1, 500, 500, 0, 0, 'ongoing', 0, '2026-07-23 18:46:14', '2026-07-23 18:46:14'),
(13, 3, 9, 1, 500, 500, 0, 0, 'ongoing', 0, '2026-07-23 18:48:00', '2026-07-23 18:48:00'),
(14, 2, 1, 2, 750, 750, 0, 0, 'ongoing', 0, '2026-07-23 21:53:24', '2026-07-23 21:53:24'),
(15, 1, 1, 2, 178, 750, 572, 4, 'lost', 0, '2026-07-26 22:47:37', '2026-07-27 05:08:20'),
(16, 1, 5, 1, 500, 500, 0, 0, 'ongoing', 0, '2026-07-27 05:38:12', '2026-07-27 05:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `leaderboard`
--

CREATE TABLE `leaderboard` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '2026_07_01_081229_create_users_table', 1),
(2, '2026_07_01_081349_create_teachers_table', 1),
(3, '2026_07_01_081359_create_students_table', 1),
(4, '2026_07_01_081407_create_reading_materials_table', 1),
(5, '2026_07_01_081414_create_activities_table', 1),
(6, '2026_07_01_081422_create_activity_word_bank_table', 1),
(7, '2026_07_01_081429_create_activity_results_table', 1),
(8, '2026_07_01_081440_create_voice_recordings_table', 1),
(9, '2026_07_01_081447_create_evaluations_table', 1),
(10, '2026_07_01_081453_create_badges_table', 1),
(11, '2026_07_01_081500_create_student_badges_table', 1),
(12, '2026_07_01_081505_create_rewards_table', 1),
(13, '2026_07_01_081513_create_student_rewards_table', 1),
(14, '2026_07_01_081520_create_achievements_table', 1),
(15, '2026_07_01_081526_create_student_achievements_table', 1),
(16, '2026_07_01_081533_create_leaderboard_table', 1),
(17, '2026_07_01_081539_create_ml_predictions_table', 1),
(18, '2026_07_01_084743_create_sessions_table', 1),
(19, '2026_07_17_074629_create_cache_table', 1),
(20, '2026_07_20_091327_create_enemies_table', 1),
(21, '2026_07_20_091328_create_game_sessions_table', 1),
(22, '2026_07_20_091329_create_game_rounds_table', 1),
(23, '2026_07_20_135854_add_battle_mode_to_activities_table', 2),
(24, '2026_07_20_140003_add_order_and_type_to_activity_word_bank_table', 3);

-- --------------------------------------------------------

--
-- Table structure for table `ml_predictions`
--

CREATE TABLE `ml_predictions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED DEFAULT NULL,
  `predicted_level` enum('Beginner','Developing','Proficient','Advanced') DEFAULT NULL,
  `prediction_confidence` decimal(5,2) DEFAULT NULL,
  `recommended_difficulty` enum('Easy','Medium','Hard') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reading_materials`
--

CREATE TABLE `reading_materials` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `difficulty_level` enum('Easy','Medium','Hard') DEFAULT NULL,
  `level` int(11) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `reading_materials`
--

INSERT INTO `reading_materials` (`id`, `teacher_id`, `title`, `content`, `difficulty_level`, `level`, `file_path`, `created_at`, `updated_at`) VALUES
(1, 1, 'The Cat', 'the boy is good', 'Easy', 1, NULL, '2026-07-20 01:53:54', '2026-07-20 01:53:54'),
(2, 1, 'MAMA', 'Mahal mopaba ako', 'Easy', 1, NULL, '2026-07-20 22:05:21', '2026-07-20 22:32:13'),
(3, 1, 'The SUN', 'REY KABA REY AKO EDI IKAW DIN', 'Easy', 1, NULL, '2026-07-20 22:57:21', '2026-07-20 23:59:40'),
(4, 1, 'My Mother', '\"There is no one quite like my mother. Every morning, she greets the day with a warm smile and a comforting hug. She is my biggest teacher, my truest friend, and the person who always knows how to make everything better when things go wrong. Whether she is helping me with my homework, reading me bedtime stories, or simply listening to me talk about my day, her love gives me the confidence to try my best. I admire her kindness and strength, and I am so grateful to have her by my side.\"', 'Medium', 2, NULL, '2026-07-21 03:09:13', '2026-07-21 03:09:13'),
(5, 1, 'The Sun', '\"The sun is a very big, hot star. It is a bright ball of fire in the sky. The sun gives Earth heat and light. Without the sun, our planet would be very cold and dark. Plants need the sun\'s warm light to grow, and people need it to stay warm during the day.\"', 'Medium', 2, NULL, '2026-07-21 03:11:03', '2026-07-23 18:51:43'),
(6, 1, 'The Water Jog', 'The water from the Jog is dirty', 'Medium', 2, NULL, '2026-07-22 22:27:35', '2026-07-22 22:27:35'),
(7, 1, 'lurenz', 'lurenz the great is noob', 'Easy', 1, NULL, '2026-07-23 17:17:52', '2026-07-23 17:17:52');

-- --------------------------------------------------------

--
-- Table structure for table `rewards`
--

CREATE TABLE `rewards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reward_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `points_required` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('lC0dYoNHWbR9UbCQsONduQX8L5qF45s1uhrZqdP4', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.130.0 Chrome/148.0.7778.280 Electron/42.6.0 Safari/537.36', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoieXBtVDJjQUJYSzlkdHlyenpLcUJyNGFTVWNybWJHMXNNcWtHYlc5cyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7czo1OiJyb3V0ZSI7czo1OiJsb2dpbiI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1785217466);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `teacher_id` bigint(20) UNSIGNED NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `current_level` int(11) NOT NULL DEFAULT 1,
  `total_points` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `user_id`, `teacher_id`, `student_number`, `firstname`, `lastname`, `section`, `current_level`, `total_points`, `created_at`, `updated_at`) VALUES
(1, 2, 1, '10258954', 'Joween', 'Medina', 'Section A', 3, 1110, '2026-07-20 01:49:52', '2026-07-23 21:18:01'),
(2, 3, 1, '20100589', 'Princess', 'Edurese', 'Section B', 2, 961, '2026-07-22 06:18:44', '2026-07-23 21:17:40'),
(3, 4, 1, '20104568', 'Lurenz', 'Edurese', 'Section B', 3, 1001, '2026-07-23 17:16:52', '2026-07-23 18:53:31');

-- --------------------------------------------------------

--
-- Table structure for table `student_achievements`
--

CREATE TABLE `student_achievements` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `achievement_id` bigint(20) UNSIGNED NOT NULL,
  `earned_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_badges`
--

CREATE TABLE `student_badges` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `badge_id` bigint(20) UNSIGNED NOT NULL,
  `awarded_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_rewards`
--

CREATE TABLE `student_rewards` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `reward_id` bigint(20) UNSIGNED NOT NULL,
  `claimed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `lastname` varchar(100) DEFAULT NULL,
  `school_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `user_id`, `firstname`, `lastname`, `school_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'rey', 'edurese', 'sagana elementary school', '2026-07-20 01:49:21', '2026-07-20 01:49:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('teacher','student') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'rey', 'rey@gmail.com', '$2y$12$GU/Ln7u8IW4LUdDXOMxpou2O4KoqyqwJF1RMFzzdBlSoBDSSnVgEi', 'teacher', 'active', '2026-07-20 01:49:21', '2026-07-20 01:49:21'),
(2, 'jowen', NULL, '$2y$12$KJq7ksJIqaXDDJyFgyp1hOtbVlI0q5r./BMw5EdHzhrIqz9U53VTS', 'student', 'active', '2026-07-20 01:49:52', '2026-07-20 01:49:52'),
(3, 'cess', NULL, '$2y$12$71fpXQClAiV5FgyfQZZaCOrLy22H6w.Sh8uvT073YtQXHd05pwhoC', 'student', 'active', '2026-07-22 06:18:44', '2026-07-22 06:18:44'),
(4, 'lurenz', NULL, '$2y$12$3hRBJdpT1wa9.kOBBLcj4u9YfnHotxReJDt30b3xX/TG/v518wyvG', 'student', 'active', '2026-07-23 17:16:52', '2026-07-23 17:16:52');

-- --------------------------------------------------------

--
-- Table structure for table `voice_recordings`
--

CREATE TABLE `voice_recordings` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `student_id` bigint(20) UNSIGNED NOT NULL,
  `activity_id` bigint(20) UNSIGNED NOT NULL,
  `recording_path` varchar(255) NOT NULL,
  `attempt_number` int(11) NOT NULL DEFAULT 1,
  `status` enum('pending','evaluated') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `voice_recordings`
--

INSERT INTO `voice_recordings` (`id`, `student_id`, `activity_id`, `recording_path`, `attempt_number`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'recordings/lbUHlnH60zuZ3pgUMIaX9DTS877BBIJ2k9JOOWMs.ogg', 1, 'evaluated', '2026-07-20 22:10:01', '2026-07-20 22:46:38'),
(2, 1, 5, 'recordings/Jl7yn7rcZuxR9IiMJNTGJOYN5hcEogI5JwFmGRyh.ogg', 1, 'evaluated', '2026-07-20 23:00:03', '2026-07-20 23:00:36'),
(3, 1, 3, 'recordings/T8BCX21SqPF7kJgxwimjbfBbyp8H1NYGGal9p9EL.ogg', 1, 'pending', '2026-07-21 21:55:54', '2026-07-21 21:55:54'),
(4, 1, 3, 'recordings/zvxmqb6b1ubAhTC9ghRLuZMY0AfjWWLXda8SLIx0.ogg', 2, 'pending', '2026-07-22 02:46:40', '2026-07-22 02:46:40'),
(5, 1, 3, 'recordings/8TbKGG7bzNwYIonMCf5vTteOiyyt6xX4ScaZooEx.ogg', 3, 'pending', '2026-07-23 00:54:19', '2026-07-23 00:54:19'),
(6, 3, 14, 'recordings/WSdPLbaZVWLtKiXbNKZMcqvxdobUgTgdKuJ5sivv.webm', 1, 'evaluated', '2026-07-23 17:18:41', '2026-07-23 17:19:12'),
(7, 3, 7, 'recordings/5ECzCTYJgxumNexj1EBa4ZgQR4N0xfKvPcx9RYd1.webm', 1, 'evaluated', '2026-07-23 18:52:30', '2026-07-23 18:53:31'),
(8, 2, 14, 'recordings/pgFHbo7iuQnJ39s8autfX9Mpo0h0ChD5oXLYAqGY.ogg', 1, 'evaluated', '2026-07-23 21:17:15', '2026-07-23 21:17:40');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `achievements`
--
ALTER TABLE `achievements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activities_teacher_id_foreign` (`teacher_id`),
  ADD KEY `activities_reading_material_id_foreign` (`reading_material_id`);

--
-- Indexes for table `activity_results`
--
ALTER TABLE `activity_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_results_student_id_foreign` (`student_id`),
  ADD KEY `activity_results_activity_id_foreign` (`activity_id`);

--
-- Indexes for table `activity_word_bank`
--
ALTER TABLE `activity_word_bank`
  ADD PRIMARY KEY (`id`),
  ADD KEY `activity_word_bank_activity_id_foreign` (`activity_id`);

--
-- Indexes for table `badges`
--
ALTER TABLE `badges`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `enemies`
--
ALTER TABLE `enemies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `evaluations_teacher_id_foreign` (`teacher_id`),
  ADD KEY `evaluations_recording_id_foreign` (`recording_id`);

--
-- Indexes for table `game_rounds`
--
ALTER TABLE `game_rounds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_rounds_game_session_id_foreign` (`game_session_id`),
  ADD KEY `game_rounds_student_id_foreign` (`student_id`);

--
-- Indexes for table `game_sessions`
--
ALTER TABLE `game_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `game_sessions_student_id_foreign` (`student_id`),
  ADD KEY `game_sessions_activity_id_foreign` (`activity_id`),
  ADD KEY `game_sessions_enemy_id_foreign` (`enemy_id`);

--
-- Indexes for table `leaderboard`
--
ALTER TABLE `leaderboard`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leaderboard_student_id_unique` (`student_id`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ml_predictions_student_id_foreign` (`student_id`),
  ADD KEY `ml_predictions_activity_id_foreign` (`activity_id`);

--
-- Indexes for table `reading_materials`
--
ALTER TABLE `reading_materials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reading_materials_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `rewards`
--
ALTER TABLE `rewards`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `students_student_number_unique` (`student_number`),
  ADD KEY `students_user_id_foreign` (`user_id`),
  ADD KEY `students_teacher_id_foreign` (`teacher_id`);

--
-- Indexes for table `student_achievements`
--
ALTER TABLE `student_achievements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_achievements_student_id_foreign` (`student_id`),
  ADD KEY `student_achievements_achievement_id_foreign` (`achievement_id`);

--
-- Indexes for table `student_badges`
--
ALTER TABLE `student_badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_badges_student_id_foreign` (`student_id`),
  ADD KEY `student_badges_badge_id_foreign` (`badge_id`);

--
-- Indexes for table `student_rewards`
--
ALTER TABLE `student_rewards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_rewards_student_id_foreign` (`student_id`),
  ADD KEY `student_rewards_reward_id_foreign` (`reward_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teachers_user_id_foreign` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_username_unique` (`username`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `voice_recordings`
--
ALTER TABLE `voice_recordings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `voice_recordings_student_id_foreign` (`student_id`),
  ADD KEY `voice_recordings_activity_id_foreign` (`activity_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `achievements`
--
ALTER TABLE `achievements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `activity_results`
--
ALTER TABLE `activity_results`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `activity_word_bank`
--
ALTER TABLE `activity_word_bank`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `badges`
--
ALTER TABLE `badges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `enemies`
--
ALTER TABLE `enemies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `game_rounds`
--
ALTER TABLE `game_rounds`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT for table `game_sessions`
--
ALTER TABLE `game_sessions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `leaderboard`
--
ALTER TABLE `leaderboard`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reading_materials`
--
ALTER TABLE `reading_materials`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `rewards`
--
ALTER TABLE `rewards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_achievements`
--
ALTER TABLE `student_achievements`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_badges`
--
ALTER TABLE `student_badges`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_rewards`
--
ALTER TABLE `student_rewards`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `voice_recordings`
--
ALTER TABLE `voice_recordings`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activities`
--
ALTER TABLE `activities`
  ADD CONSTRAINT `activities_reading_material_id_foreign` FOREIGN KEY (`reading_material_id`) REFERENCES `reading_materials` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `activities_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_results`
--
ALTER TABLE `activity_results`
  ADD CONSTRAINT `activity_results_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `activity_results_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_word_bank`
--
ALTER TABLE `activity_word_bank`
  ADD CONSTRAINT `activity_word_bank_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_recording_id_foreign` FOREIGN KEY (`recording_id`) REFERENCES `voice_recordings` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_rounds`
--
ALTER TABLE `game_rounds`
  ADD CONSTRAINT `game_rounds_game_session_id_foreign` FOREIGN KEY (`game_session_id`) REFERENCES `game_sessions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_rounds_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_sessions`
--
ALTER TABLE `game_sessions`
  ADD CONSTRAINT `game_sessions_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_sessions_enemy_id_foreign` FOREIGN KEY (`enemy_id`) REFERENCES `enemies` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `game_sessions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `leaderboard`
--
ALTER TABLE `leaderboard`
  ADD CONSTRAINT `leaderboard_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ml_predictions`
--
ALTER TABLE `ml_predictions`
  ADD CONSTRAINT `ml_predictions_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ml_predictions_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reading_materials`
--
ALTER TABLE `reading_materials`
  ADD CONSTRAINT `reading_materials_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_achievements`
--
ALTER TABLE `student_achievements`
  ADD CONSTRAINT `student_achievements_achievement_id_foreign` FOREIGN KEY (`achievement_id`) REFERENCES `achievements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_achievements_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_badges`
--
ALTER TABLE `student_badges`
  ADD CONSTRAINT `student_badges_badge_id_foreign` FOREIGN KEY (`badge_id`) REFERENCES `badges` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_badges_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_rewards`
--
ALTER TABLE `student_rewards`
  ADD CONSTRAINT `student_rewards_reward_id_foreign` FOREIGN KEY (`reward_id`) REFERENCES `rewards` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_rewards_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `voice_recordings`
--
ALTER TABLE `voice_recordings`
  ADD CONSTRAINT `voice_recordings_activity_id_foreign` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `voice_recordings_student_id_foreign` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
