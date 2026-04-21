-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Apr 20, 2026 at 06:15 PM
-- Server version: 11.8.6-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u608908096_easyenglish`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `access` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `anouncement`
--

CREATE TABLE `anouncement` (
  `id` int(11) NOT NULL,
  `link` varchar(500) NOT NULL,
  `major` varchar(20) NOT NULL,
  `is_seen` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `apps`
--

CREATE TABLE `apps` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` varchar(200) NOT NULL,
  `url` varchar(500) NOT NULL,
  `cover` varchar(500) NOT NULL,
  `icon` varchar(500) NOT NULL,
  `type` varchar(30) NOT NULL,
  `click` int(11) NOT NULL,
  `show_on` tinyint(1) NOT NULL,
  `active_course` int(11) NOT NULL,
  `student_learning` varchar(5) NOT NULL,
  `major` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `artists`
--

CREATE TABLE `artists` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `nation` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE `blocks` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `blocked_user_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cards`
--

CREATE TABLE `cards` (
  `id` int(11) NOT NULL,
  `deck_id` int(10) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `word` varchar(255) NOT NULL,
  `ipa` varchar(255) DEFAULT NULL,
  `pronunciation_audio` varchar(500) DEFAULT NULL,
  `parts_of_speech` text DEFAULT NULL,
  `burmese_translation` text DEFAULT NULL,
  `example_sentences` text DEFAULT NULL,
  `synonyms` text DEFAULT NULL,
  `antonyms` text DEFAULT NULL,
  `relatived` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

CREATE TABLE `certificates` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` varchar(128) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cn_game_words`
--

CREATE TABLE `cn_game_words` (
  `id` int(11) NOT NULL,
  `display_word` varchar(100) NOT NULL,
  `display_image` varchar(1000) NOT NULL,
  `display_audio` varchar(1000) NOT NULL,
  `category` tinyint(4) NOT NULL,
  `a` varchar(50) NOT NULL,
  `b` varchar(50) NOT NULL,
  `c` varchar(50) NOT NULL,
  `ans` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cn_user_datas`
--

CREATE TABLE `cn_user_datas` (
  `id` bigint(20) NOT NULL,
  `phone` bigint(20) NOT NULL,
  `is_vip` tinyint(4) NOT NULL,
  `study_time` time DEFAULT NULL,
  `game_score` int(11) NOT NULL,
  `basic_exam` int(11) NOT NULL,
  `token` varchar(500) NOT NULL,
  `login_time` int(11) NOT NULL,
  `first_join` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_active` timestamp NULL DEFAULT NULL,
  `song` int(11) NOT NULL,
  `discuss_count` int(11) NOT NULL,
  `learn_count` int(11) NOT NULL,
  `auth_token` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cn_word_of_days`
--

CREATE TABLE `cn_word_of_days` (
  `id` int(11) NOT NULL,
  `chinese` varchar(100) NOT NULL,
  `myanmar` varchar(100) NOT NULL,
  `speech` varchar(20) NOT NULL,
  `example` text NOT NULL,
  `thumb` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment`
--

CREATE TABLE `comment` (
  `id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `writer_id` bigint(20) NOT NULL,
  `body` text NOT NULL,
  `image` varchar(1000) NOT NULL,
  `time` bigint(20) NOT NULL,
  `parent` bigint(20) NOT NULL DEFAULT 0,
  `likes` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

CREATE TABLE `comment_likes` (
  `id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `conversations`
--

CREATE TABLE `conversations` (
  `id` int(10) UNSIGNED NOT NULL,
  `user1_id` bigint(10) UNSIGNED NOT NULL COMMENT 'First user in conversation',
  `user2_id` bigint(10) UNSIGNED NOT NULL COMMENT 'Second user in conversation',
  `last_message_at` timestamp NULL DEFAULT NULL,
  `major` varchar(125) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `costs`
--

CREATE TABLE `costs` (
  `id` int(11) NOT NULL,
  `cost_category_id` int(11) NOT NULL,
  `title` varchar(225) NOT NULL,
  `amount` int(11) NOT NULL,
  `major` varchar(10) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `transfer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cost_categories`
--

CREATE TABLE `cost_categories` (
  `id` int(11) NOT NULL,
  `title` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` smallint(3) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(50) NOT NULL,
  `certificate_title` varchar(225) NOT NULL,
  `lessons_count` smallint(3) NOT NULL,
  `cover_url` varchar(500) NOT NULL,
  `web_cover` varchar(500) NOT NULL,
  `description` varchar(1000) NOT NULL,
  `details` text NOT NULL,
  `is_vip` tinyint(4) NOT NULL,
  `duration` tinyint(3) NOT NULL,
  `background_color` varchar(225) NOT NULL,
  `fee` int(10) NOT NULL,
  `enroll` int(11) NOT NULL,
  `rating` float NOT NULL,
  `major` varchar(10) NOT NULL,
  `sorting` int(11) NOT NULL,
  `preview` varchar(1000) NOT NULL,
  `certificate_code` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_categories`
--

CREATE TABLE `course_categories` (
  `id` int(11) NOT NULL,
  `title` varchar(225) NOT NULL,
  `project_name` varchar(225) NOT NULL,
  `keyword` varchar(225) NOT NULL,
  `icon` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_enroll`
--

CREATE TABLE `course_enroll` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `course_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `progress` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `decks`
--

CREATE TABLE `decks` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ee_error_speech`
--

CREATE TABLE `ee_error_speech` (
  `id` int(11) NOT NULL,
  `speech_id` int(11) NOT NULL,
  `phone` bigint(20) NOT NULL,
  `error_speech` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ee_game_words`
--

CREATE TABLE `ee_game_words` (
  `id` int(11) NOT NULL,
  `display_word` varchar(100) NOT NULL,
  `display_image` varchar(1000) NOT NULL,
  `display_audio` varchar(1000) NOT NULL,
  `category` tinyint(4) NOT NULL,
  `a` varchar(50) NOT NULL,
  `b` varchar(50) NOT NULL,
  `c` varchar(50) NOT NULL,
  `ans` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ee_saturation`
--

CREATE TABLE `ee_saturation` (
  `id` int(11) NOT NULL,
  `saturation_id` bigint(20) NOT NULL,
  `title` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ee_speakingtrainer`
--

CREATE TABLE `ee_speakingtrainer` (
  `id` int(11) NOT NULL,
  `person_a` varchar(1000) NOT NULL,
  `person_a_mm` varchar(1000) NOT NULL,
  `person_b` varchar(10000) NOT NULL,
  `person_b_mm` varchar(1000) NOT NULL,
  `request_count` int(11) NOT NULL DEFAULT 0,
  `saturation_id` bigint(20) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ee_user_datas`
--

CREATE TABLE `ee_user_datas` (
  `id` bigint(20) NOT NULL,
  `phone` bigint(20) NOT NULL,
  `is_vip` tinyint(4) NOT NULL,
  `gold_plan` tinyint(1) NOT NULL,
  `study_time` time DEFAULT NULL,
  `level_test` int(11) NOT NULL,
  `basic_exam` int(11) NOT NULL DEFAULT 0,
  `game_score` int(11) NOT NULL,
  `speaking_level` int(11) NOT NULL DEFAULT 1,
  `token` varchar(500) NOT NULL,
  `login_time` int(11) NOT NULL,
  `first_join` timestamp NULL DEFAULT '0000-00-00 00:00:00',
  `last_active` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `song` int(11) NOT NULL,
  `General` int(11) NOT NULL,
  `learn_count` int(11) NOT NULL,
  `discuss_count` int(11) NOT NULL,
  `auth_token` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `korea` text NOT NULL,
  `korea_count` int(11) NOT NULL,
  `english` text NOT NULL,
  `english_count` int(11) NOT NULL,
  `chinese` text NOT NULL,
  `chinese_count` int(11) NOT NULL,
  `japanese` text NOT NULL,
  `japanese_count` int(11) NOT NULL,
  `russian` text NOT NULL,
  `russian_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `friend_requests`
--

CREATE TABLE `friend_requests` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `korea` text NOT NULL,
  `korea_count` int(11) NOT NULL,
  `english` text NOT NULL,
  `english_count` int(11) NOT NULL,
  `chinese` text NOT NULL,
  `chinese_count` int(11) NOT NULL,
  `japanese` text NOT NULL,
  `japanese_count` int(11) NOT NULL,
  `russian` text NOT NULL,
  `russian_count` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `functions`
--

CREATE TABLE `functions` (
  `id` int(11) NOT NULL,
  `title` varchar(225) NOT NULL,
  `link_url` varchar(500) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `function_type` tinyint(2) NOT NULL,
  `function_id` tinyint(2) NOT NULL,
  `major` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `funds`
--

CREATE TABLE `funds` (
  `id` int(11) NOT NULL,
  `title` varchar(225) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `amount` int(11) NOT NULL,
  `current_balance` int(11) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `staff_id` int(11) NOT NULL,
  `transfer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hidden_posts`
--

CREATE TABLE `hidden_posts` (
  `id` int(11) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jp_game_words`
--

CREATE TABLE `jp_game_words` (
  `id` int(11) NOT NULL,
  `display_word` varchar(100) NOT NULL,
  `display_image` varchar(1000) NOT NULL,
  `display_audio` varchar(1000) NOT NULL,
  `category` tinyint(4) NOT NULL,
  `a` varchar(50) NOT NULL,
  `b` varchar(50) NOT NULL,
  `c` varchar(50) NOT NULL,
  `ans` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jp_user_datas`
--

CREATE TABLE `jp_user_datas` (
  `id` bigint(20) NOT NULL,
  `phone` bigint(20) NOT NULL,
  `is_vip` tinyint(4) NOT NULL,
  `gold_plan` tinyint(4) NOT NULL,
  `study_time` time DEFAULT NULL,
  `basic_exam` int(11) NOT NULL,
  `game_score` int(11) NOT NULL,
  `token` varchar(500) NOT NULL,
  `login_time` int(11) NOT NULL,
  `first_join` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_active` timestamp NULL DEFAULT NULL,
  `song` int(11) NOT NULL,
  `discuss_count` int(11) NOT NULL,
  `learn_count` int(11) NOT NULL,
  `auth_token` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jp_word_of_days`
--

CREATE TABLE `jp_word_of_days` (
  `id` int(11) NOT NULL,
  `japanese` varchar(100) NOT NULL,
  `myanmar` varchar(100) NOT NULL,
  `speech` varchar(20) NOT NULL,
  `example` text NOT NULL,
  `thumb` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ko_game_words`
--

CREATE TABLE `ko_game_words` (
  `id` int(11) NOT NULL,
  `display_word` varchar(100) NOT NULL,
  `display_image` varchar(1000) NOT NULL,
  `display_audio` varchar(1000) NOT NULL,
  `category` tinyint(4) NOT NULL,
  `a` varchar(50) NOT NULL,
  `b` varchar(50) NOT NULL,
  `c` varchar(50) NOT NULL,
  `ans` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ko_user_datas`
--

CREATE TABLE `ko_user_datas` (
  `id` bigint(20) NOT NULL,
  `phone` bigint(20) NOT NULL,
  `is_vip` tinyint(4) NOT NULL,
  `gold_plan` tinyint(1) NOT NULL,
  `study_time` time DEFAULT NULL,
  `basic_exam` int(11) NOT NULL,
  `levelone_exam` int(11) NOT NULL,
  `game_score` int(11) NOT NULL,
  `token` varchar(500) NOT NULL,
  `login_time` int(11) NOT NULL,
  `first_join` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_active` timestamp NULL DEFAULT NULL,
  `song` int(11) NOT NULL,
  `discuss_count` int(11) NOT NULL,
  `learn_count` int(11) NOT NULL,
  `auth_token` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ko_word_of_days`
--

CREATE TABLE `ko_word_of_days` (
  `id` int(11) NOT NULL,
  `korea` varchar(100) NOT NULL,
  `myanmar` varchar(100) NOT NULL,
  `speech` varchar(20) NOT NULL,
  `example` text NOT NULL,
  `thumb` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `id` int(10) UNSIGNED NOT NULL,
  `name` varchar(100) NOT NULL,
  `display_name` varchar(100) DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `module_code` varchar(10) DEFAULT NULL,
  `primary_color` varchar(20) DEFAULT NULL,
  `secondary_color` varchar(20) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `notification_owner_id` varchar(20) DEFAULT NULL,
  `firebase_topic` varchar(100) DEFAULT NULL,
  `user_data_table_prefix` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `learners`
--

CREATE TABLE `learners` (
  `id` bigint(20) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `learner_name` varchar(50) NOT NULL,
  `learner_email` varchar(100) NOT NULL,
  `learner_phone` bigint(20) NOT NULL,
  `password` varchar(225) NOT NULL,
  `learner_image` varchar(500) NOT NULL,
  `cover_image` varchar(1000) NOT NULL,
  `gender` varchar(10) NOT NULL,
  `bd_day` varchar(5) NOT NULL,
  `bd_month` varchar(15) NOT NULL,
  `bd_year` varchar(5) NOT NULL,
  `work` varchar(100) NOT NULL,
  `education` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `bio` varchar(1000) NOT NULL,
  `otp` int(11) NOT NULL,
  `auth_token` varchar(225) NOT NULL,
  `auth_token_mobile` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `id` int(11) NOT NULL,
  `category_id` int(5) NOT NULL,
  `date` bigint(20) NOT NULL,
  `isVideo` tinyint(4) NOT NULL,
  `isVip` tinyint(4) NOT NULL,
  `isChannel` tinyint(4) NOT NULL,
  `link` varchar(1000) NOT NULL,
  `title_mini` varchar(225) DEFAULT NULL,
  `title` text NOT NULL,
  `major` varchar(50) NOT NULL,
  `cate` varchar(5) NOT NULL,
  `thumbnail` varchar(500) NOT NULL,
  `duration` int(11) NOT NULL DEFAULT 0,
  `notes` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons_categories`
--

CREATE TABLE `lessons_categories` (
  `id` int(5) NOT NULL,
  `course_id` smallint(3) NOT NULL,
  `category` char(128) NOT NULL,
  `category_title` varchar(128) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `sort_order` bigint(20) NOT NULL,
  `major` char(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `pdf_file` varchar(500) NOT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `major` varchar(225) NOT NULL DEFAULT 'english',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `live_classes`
--

CREATE TABLE `live_classes` (
  `id` int(11) NOT NULL,
  `title` varchar(225) NOT NULL,
  `link` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(10) UNSIGNED NOT NULL,
  `conversation_id` int(10) UNSIGNED NOT NULL,
  `sender_id` bigint(10) UNSIGNED NOT NULL,
  `message_type` enum('text','voice','image') DEFAULT 'text',
  `message_text` text DEFAULT NULL COMMENT 'For text messages',
  `file_path` varchar(500) DEFAULT NULL COMMENT 'For voice and image messages',
  `file_size` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'File size in bytes',
  `is_read` tinyint(1) DEFAULT 0,
  `major` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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

-- --------------------------------------------------------

--
-- Table structure for table `mylikes`
--

CREATE TABLE `mylikes` (
  `id` bigint(20) NOT NULL,
  `content_id` bigint(20) NOT NULL,
  `likes` text NOT NULL,
  `rowNo` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `comment_id` bigint(20) NOT NULL,
  `owner_id` bigint(20) NOT NULL,
  `writer_id` bigint(20) NOT NULL,
  `action` int(11) NOT NULL,
  `time` bigint(20) NOT NULL,
  `seen` tinyint(4) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_action`
--

CREATE TABLE `notification_action` (
  `id` tinyint(11) NOT NULL,
  `action` int(11) NOT NULL,
  `action_name` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_plans`
--

CREATE TABLE `package_plans` (
  `id` int(11) NOT NULL,
  `course_category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_plan_courses`
--

CREATE TABLE `package_plan_courses` (
  `id` int(11) NOT NULL,
  `package_id` int(11) NOT NULL,
  `course_id` smallint(3) NOT NULL,
  `is_required` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `contact_name` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `private_code` varchar(125) NOT NULL,
  `total_codes_generated` int(11) DEFAULT 0,
  `total_codes_used` int(11) DEFAULT 0,
  `phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(500) NOT NULL,
  `website` varchar(500) DEFAULT NULL,
  `address` text NOT NULL,
  `city` varchar(225) NOT NULL,
  `state` varchar(125) NOT NULL,
  `national_id_card_number` varchar(225) NOT NULL,
  `national_id_card_front_image` varchar(500) NOT NULL,
  `national_id_card_back_image` varchar(500) NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT 10.00,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `account_verified` tinyint(4) NOT NULL,
  `verification_token` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_token_expires` timestamp NULL DEFAULT NULL,
  `verification_code` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_earnings`
--

CREATE TABLE `partner_earnings` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `target_course_id` int(11) DEFAULT NULL,
  `target_package_id` int(11) DEFAULT NULL,
  `learner_phone` bigint(20) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `commission_rate` decimal(5,2) NOT NULL,
  `amount_received` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_password_reset_tokens`
--

CREATE TABLE `partner_password_reset_tokens` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `token` text NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_payment_histories`
--

CREATE TABLE `partner_payment_histories` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `payment_method` varchar(100) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','received','rejected') DEFAULT 'pending',
  `transaction_screenshot` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_payment_methods`
--

CREATE TABLE `partner_payment_methods` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `payment_method` varchar(100) NOT NULL,
  `account_number` varchar(255) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_sessions`
--

CREATE TABLE `partner_sessions` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expired_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `amount` int(11) NOT NULL,
  `major` varchar(10) NOT NULL,
  `screenshot` varchar(1000) NOT NULL,
  `approve` tinyint(1) NOT NULL,
  `transaction_id` varchar(50) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL,
  `learner_id` bigint(20) NOT NULL,
  `body` text NOT NULL,
  `blog_title` varchar(1000) NOT NULL,
  `post_like` int(11) NOT NULL,
  `comments` int(11) NOT NULL,
  `image` varchar(500) NOT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `vimeo` varchar(500) NOT NULL,
  `has_video` tinyint(4) NOT NULL,
  `share` bigint(20) NOT NULL DEFAULT 0,
  `view_count` int(11) NOT NULL,
  `share_count` int(11) NOT NULL DEFAULT 0,
  `show_on_blog` int(11) NOT NULL,
  `hide` tinyint(1) NOT NULL,
  `major` varchar(20) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pricings`
--

CREATE TABLE `pricings` (
  `id` int(11) NOT NULL,
  `app_id` int(11) NOT NULL,
  `pricing` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `questions`
--

CREATE TABLE `questions` (
  `id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `question` varchar(1000) NOT NULL,
  `answer` varchar(1000) NOT NULL,
  `major` varchar(20) NOT NULL,
  `time` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ratings`
--

CREATE TABLE `ratings` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `user_id` bigint(20) NOT NULL,
  `star` int(11) NOT NULL,
  `review` text NOT NULL,
  `time` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report`
--

CREATE TABLE `report` (
  `id` bigint(20) NOT NULL,
  `post_id` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requestedsongs`
--

CREATE TABLE `requestedsongs` (
  `id` int(11) NOT NULL,
  `artist_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `vote` int(11) NOT NULL,
  `is_voted` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roadmaps`
--

CREATE TABLE `roadmaps` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `major` varchar(225) NOT NULL,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ru_game_words`
--

CREATE TABLE `ru_game_words` (
  `id` int(11) NOT NULL,
  `display_word` varchar(100) NOT NULL,
  `display_image` varchar(1000) NOT NULL,
  `display_audio` varchar(1000) NOT NULL,
  `category` tinyint(4) NOT NULL,
  `a` varchar(50) NOT NULL,
  `b` varchar(50) NOT NULL,
  `c` varchar(50) NOT NULL,
  `ans` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ru_user_datas`
--

CREATE TABLE `ru_user_datas` (
  `id` bigint(20) NOT NULL,
  `phone` bigint(20) NOT NULL,
  `is_vip` tinyint(4) NOT NULL,
  `gold_plan` tinyint(4) NOT NULL,
  `study_time` time DEFAULT NULL,
  `basic_exam` int(11) NOT NULL,
  `levelone_exam` int(11) NOT NULL,
  `game_score` int(11) NOT NULL,
  `token` varchar(500) NOT NULL,
  `login_time` int(11) NOT NULL,
  `first_join` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `last_active` timestamp NULL DEFAULT NULL,
  `song` int(11) NOT NULL,
  `discuss_count` int(11) NOT NULL,
  `learn_count` int(11) NOT NULL,
  `auth_token` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ru_word_of_days`
--

CREATE TABLE `ru_word_of_days` (
  `id` int(11) NOT NULL,
  `russian` varchar(100) NOT NULL,
  `myanmar` varchar(100) NOT NULL,
  `speech` varchar(20) NOT NULL,
  `example` text NOT NULL,
  `thumb` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salaries`
--

CREATE TABLE `salaries` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `amount` int(11) NOT NULL,
  `project` varchar(225) NOT NULL,
  `date` date NOT NULL DEFAULT current_timestamp(),
  `transfer_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `save_replies`
--

CREATE TABLE `save_replies` (
  `id` int(11) NOT NULL,
  `title` varchar(225) NOT NULL,
  `message` text NOT NULL,
  `major` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Songs`
--

CREATE TABLE `Songs` (
  `id` int(11) NOT NULL,
  `song_id` bigint(20) NOT NULL,
  `title` varchar(50) NOT NULL,
  `artist` varchar(50) NOT NULL,
  `drama` varchar(100) NOT NULL,
  `like_count` int(11) NOT NULL,
  `comment_count` int(11) NOT NULL,
  `download_count` int(11) NOT NULL,
  `url` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `rank` varchar(225) NOT NULL,
  `ranking` int(11) NOT NULL,
  `project` varchar(11) NOT NULL,
  `present` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `studies`
--

CREATE TABLE `studies` (
  `id` bigint(20) NOT NULL,
  `learner_id` bigint(20) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `frequent` smallint(3) NOT NULL,
  `exercise_mark` smallint(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `study_plan`
--

CREATE TABLE `study_plan` (
  `id` smallint(4) NOT NULL,
  `course_id` smallint(3) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `day` smallint(3) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int(11) NOT NULL,
  `name` varchar(225) NOT NULL,
  `profile` varchar(500) NOT NULL,
  `rank` varchar(30) NOT NULL,
  `facebook` varchar(1000) NOT NULL,
  `telegram` varchar(1000) NOT NULL,
  `youtube` varchar(1000) NOT NULL,
  `description` text NOT NULL,
  `qualification` text CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `experience` text NOT NULL,
  `total_course` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `Timer`
--

CREATE TABLE `Timer` (
  `id` int(11) NOT NULL,
  `korea` int(11) NOT NULL,
  `english` int(11) NOT NULL,
  `chinese` int(11) NOT NULL,
  `japanese` int(11) NOT NULL,
  `russian` int(11) NOT NULL,
  `day` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_card_states`
--

CREATE TABLE `user_card_states` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `card_id` bigint(20) UNSIGNED NOT NULL,
  `ef` decimal(5,2) NOT NULL DEFAULT 2.50,
  `interval_` int(11) NOT NULL DEFAULT 0,
  `repetitions` int(11) NOT NULL DEFAULT 0,
  `due_at` int(11) DEFAULT NULL,
  `suspended` tinyint(1) NOT NULL DEFAULT 0,
  `paused_until` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_learning_progress`
--

CREATE TABLE `user_learning_progress` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `deck_id` int(10) UNSIGNED NOT NULL,
  `current_learning_day` int(11) NOT NULL DEFAULT 1,
  `last_session_date` date NOT NULL,
  `total_learning_days` int(11) NOT NULL DEFAULT 1,
  `streak_count` int(11) NOT NULL DEFAULT 1,
  `longest_streak` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_roadmaps`
--

CREATE TABLE `user_roadmaps` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` bigint(10) UNSIGNED NOT NULL,
  `roadmap_id` int(11) NOT NULL,
  `checklist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`checklist`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_word_skips`
--

CREATE TABLE `user_word_skips` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `card_id` bigint(20) UNSIGNED NOT NULL,
  `language_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(50) NOT NULL,
  `skipped_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `VipUsers`
--

CREATE TABLE `VipUsers` (
  `id` bigint(20) NOT NULL,
  `phone` bigint(20) NOT NULL,
  `course` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `major` varchar(30) NOT NULL,
  `date` date DEFAULT current_timestamp(),
  `deleted_account` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `weekSongs`
--

CREATE TABLE `weekSongs` (
  `id` int(11) NOT NULL,
  `song_name` varchar(100) NOT NULL,
  `votes` int(11) NOT NULL,
  `major` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `WordOfDay`
--

CREATE TABLE `WordOfDay` (
  `id` int(11) NOT NULL,
  `english` varchar(100) NOT NULL,
  `myanmar` varchar(100) NOT NULL,
  `speech` varchar(20) NOT NULL,
  `example` text NOT NULL,
  `thumb` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admins_email_unique` (`email`);

--
-- Indexes for table `anouncement`
--
ALTER TABLE `anouncement`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `apps`
--
ALTER TABLE `apps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `artists`
--
ALTER TABLE `artists`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `blocks`
--
ALTER TABLE `blocks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cards`
--
ALTER TABLE `cards`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_deck_id` (`deck_id`),
  ADD KEY `idx_language_id` (`language_id`),
  ADD KEY `idx_language_deck` (`language_id`,`deck_id`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cn_game_words`
--
ALTER TABLE `cn_game_words`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cn_user_datas`
--
ALTER TABLE `cn_user_datas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone` (`phone`);

--
-- Indexes for table `cn_word_of_days`
--
ALTER TABLE `cn_word_of_days`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comment`
--
ALTER TABLE `comment`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `writer_id` (`writer_id`),
  ADD KEY `time` (`time`);

--
-- Indexes for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `conversations`
--
ALTER TABLE `conversations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_conversation` (`user1_id`,`user2_id`,`major`) USING BTREE,
  ADD KEY `idx_user1` (`user1_id`),
  ADD KEY `idx_user2` (`user2_id`),
  ADD KEY `idx_last_message` (`last_message_at`);

--
-- Indexes for table `costs`
--
ALTER TABLE `costs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cost_categories`
--
ALTER TABLE `cost_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`);

--
-- Indexes for table `course_categories`
--
ALTER TABLE `course_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course_enroll`
--
ALTER TABLE `course_enroll`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `decks`
--
ALTER TABLE `decks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_language_id` (`language_id`);

--
-- Indexes for table `ee_error_speech`
--
ALTER TABLE `ee_error_speech`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ee_game_words`
--
ALTER TABLE `ee_game_words`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ee_saturation`
--
ALTER TABLE `ee_saturation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `saturation_id` (`saturation_id`);

--
-- Indexes for table `ee_speakingtrainer`
--
ALTER TABLE `ee_speakingtrainer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ee_user_datas`
--
ALTER TABLE `ee_user_datas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone` (`phone`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `functions`
--
ALTER TABLE `functions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `funds`
--
ALTER TABLE `funds`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hidden_posts`
--
ALTER TABLE `hidden_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `jp_game_words`
--
ALTER TABLE `jp_game_words`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jp_user_datas`
--
ALTER TABLE `jp_user_datas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone` (`phone`);

--
-- Indexes for table `jp_word_of_days`
--
ALTER TABLE `jp_word_of_days`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ko_game_words`
--
ALTER TABLE `ko_game_words`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ko_user_datas`
--
ALTER TABLE `ko_user_datas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone` (`phone`);

--
-- Indexes for table `ko_word_of_days`
--
ALTER TABLE `ko_word_of_days`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `learners`
--
ALTER TABLE `learners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learner_name` (`learner_name`),
  ADD KEY `learner_phone` (`learner_phone`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`id`),
  ADD KEY `isVideo` (`isVideo`),
  ADD KEY `isVip` (`isVip`),
  ADD KEY `major` (`major`);
ALTER TABLE `lessons` ADD FULLTEXT KEY `title` (`title`);

--
-- Indexes for table `lessons_categories`
--
ALTER TABLE `lessons_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `library_books`
--
ALTER TABLE `library_books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `live_classes`
--
ALTER TABLE `live_classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_conversation` (`conversation_id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_read` (`is_read`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `mylikes`
--
ALTER TABLE `mylikes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `owner_id` (`owner_id`),
  ADD KEY `writer_id` (`writer_id`),
  ADD KEY `action` (`action`),
  ADD KEY `time` (`time`),
  ADD KEY `seen` (`seen`);

--
-- Indexes for table `notification_action`
--
ALTER TABLE `notification_action`
  ADD PRIMARY KEY (`id`),
  ADD KEY `action` (`action`);

--
-- Indexes for table `package_plans`
--
ALTER TABLE `package_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_category_id` (`course_category_id`);

--
-- Indexes for table `package_plan_courses`
--
ALTER TABLE `package_plan_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_package_course` (`package_id`,`course_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `partner_earnings`
--
ALTER TABLE `partner_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_promotion_codes_partner` (`partner_id`),
  ADD KEY `idx_promotion_codes_status` (`status`);

--
-- Indexes for table `partner_password_reset_tokens`
--
ALTER TABLE `partner_password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `partner_payment_histories`
--
ALTER TABLE `partner_payment_histories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partner_payment_histories_partner_id` (`partner_id`),
  ADD KEY `idx_partner_payment_histories_status` (`status`),
  ADD KEY `idx_partner_payment_histories_created_at` (`created_at`),
  ADD KEY `idx_partner_payment_histories_partner_status` (`partner_id`,`status`);

--
-- Indexes for table `partner_payment_methods`
--
ALTER TABLE `partner_payment_methods`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partner_payment_methods_partner_id` (`partner_id`),
  ADD KEY `idx_partner_payment_methods_payment_method` (`payment_method`);

--
-- Indexes for table `partner_sessions`
--
ALTER TABLE `partner_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD KEY `password_resets_email_index` (`email`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `learner_id` (`learner_id`),
  ADD KEY `post_like` (`post_like`),
  ADD KEY `comments` (`comments`),
  ADD KEY `has_image` (`has_video`),
  ADD KEY `major` (`major`);

--
-- Indexes for table `pricings`
--
ALTER TABLE `pricings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `questions`
--
ALTER TABLE `questions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ratings`
--
ALTER TABLE `ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token_hash`),
  ADD KEY `idx_admin` (`admin_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indexes for table `report`
--
ALTER TABLE `report`
  ADD PRIMARY KEY (`id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `requestedsongs`
--
ALTER TABLE `requestedsongs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `artist_id` (`artist_id`);

--
-- Indexes for table `roadmaps`
--
ALTER TABLE `roadmaps`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ru_game_words`
--
ALTER TABLE `ru_game_words`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ru_user_datas`
--
ALTER TABLE `ru_user_datas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `phone` (`phone`);

--
-- Indexes for table `ru_word_of_days`
--
ALTER TABLE `ru_word_of_days`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salaries`
--
ALTER TABLE `salaries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `save_replies`
--
ALTER TABLE `save_replies`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `Songs`
--
ALTER TABLE `Songs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `song_id` (`song_id`),
  ADD KEY `artist` (`artist`),
  ADD KEY `title` (`title`),
  ADD KEY `like_count` (`like_count`),
  ADD KEY `comment_count` (`comment_count`),
  ADD KEY `download_count` (`download_count`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `studies`
--
ALTER TABLE `studies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `learner_id` (`learner_id`),
  ADD KEY `lesson_id` (`lesson_id`);

--
-- Indexes for table `study_plan`
--
ALTER TABLE `study_plan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `Timer`
--
ALTER TABLE `Timer`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- Indexes for table `user_card_states`
--
ALTER TABLE `user_card_states`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_card` (`user_id`,`card_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_card_id` (`card_id`),
  ADD KEY `idx_due_at` (`due_at`),
  ADD KEY `idx_paused_until` (`paused_until`);

--
-- Indexes for table `user_learning_progress`
--
ALTER TABLE `user_learning_progress`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_word_skips`
--
ALTER TABLE `user_word_skips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_card_id` (`card_id`),
  ADD KEY `idx_language_id` (`language_id`),
  ADD KEY `idx_user_language` (`user_id`,`language_id`);

--
-- Indexes for table `VipUsers`
--
ALTER TABLE `VipUsers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course` (`course`),
  ADD KEY `phone` (`phone`),
  ADD KEY `major` (`major`);

--
-- Indexes for table `weekSongs`
--
ALTER TABLE `weekSongs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `WordOfDay`
--
ALTER TABLE `WordOfDay`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `anouncement`
--
ALTER TABLE `anouncement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `apps`
--
ALTER TABLE `apps`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `artists`
--
ALTER TABLE `artists`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blocks`
--
ALTER TABLE `blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cards`
--
ALTER TABLE `cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_game_words`
--
ALTER TABLE `cn_game_words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_user_datas`
--
ALTER TABLE `cn_user_datas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cn_word_of_days`
--
ALTER TABLE `cn_word_of_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment`
--
ALTER TABLE `comment`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `comment_likes`
--
ALTER TABLE `comment_likes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `conversations`
--
ALTER TABLE `conversations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `costs`
--
ALTER TABLE `costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cost_categories`
--
ALTER TABLE `cost_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` smallint(3) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_categories`
--
ALTER TABLE `course_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_enroll`
--
ALTER TABLE `course_enroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `decks`
--
ALTER TABLE `decks`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ee_error_speech`
--
ALTER TABLE `ee_error_speech`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ee_game_words`
--
ALTER TABLE `ee_game_words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ee_saturation`
--
ALTER TABLE `ee_saturation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ee_speakingtrainer`
--
ALTER TABLE `ee_speakingtrainer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ee_user_datas`
--
ALTER TABLE `ee_user_datas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `functions`
--
ALTER TABLE `functions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `funds`
--
ALTER TABLE `funds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hidden_posts`
--
ALTER TABLE `hidden_posts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jp_game_words`
--
ALTER TABLE `jp_game_words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jp_user_datas`
--
ALTER TABLE `jp_user_datas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jp_word_of_days`
--
ALTER TABLE `jp_word_of_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ko_game_words`
--
ALTER TABLE `ko_game_words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ko_user_datas`
--
ALTER TABLE `ko_user_datas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ko_word_of_days`
--
ALTER TABLE `ko_word_of_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `learners`
--
ALTER TABLE `learners`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lessons_categories`
--
ALTER TABLE `lessons_categories`
  MODIFY `id` int(5) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `live_classes`
--
ALTER TABLE `live_classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mylikes`
--
ALTER TABLE `mylikes`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_action`
--
ALTER TABLE `notification_action`
  MODIFY `id` tinyint(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_plans`
--
ALTER TABLE `package_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_plan_courses`
--
ALTER TABLE `package_plan_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_earnings`
--
ALTER TABLE `partner_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_password_reset_tokens`
--
ALTER TABLE `partner_password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_payment_histories`
--
ALTER TABLE `partner_payment_histories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_payment_methods`
--
ALTER TABLE `partner_payment_methods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_sessions`
--
ALTER TABLE `partner_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pricings`
--
ALTER TABLE `pricings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `questions`
--
ALTER TABLE `questions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ratings`
--
ALTER TABLE `ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report`
--
ALTER TABLE `report`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requestedsongs`
--
ALTER TABLE `requestedsongs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roadmaps`
--
ALTER TABLE `roadmaps`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ru_game_words`
--
ALTER TABLE `ru_game_words`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ru_user_datas`
--
ALTER TABLE `ru_user_datas`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ru_word_of_days`
--
ALTER TABLE `ru_word_of_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salaries`
--
ALTER TABLE `salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `save_replies`
--
ALTER TABLE `save_replies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `Songs`
--
ALTER TABLE `Songs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staffs`
--
ALTER TABLE `staffs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `studies`
--
ALTER TABLE `studies`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `study_plan`
--
ALTER TABLE `study_plan`
  MODIFY `id` smallint(4) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_card_states`
--
ALTER TABLE `user_card_states`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_learning_progress`
--
ALTER TABLE `user_learning_progress`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_word_skips`
--
ALTER TABLE `user_word_skips`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `VipUsers`
--
ALTER TABLE `VipUsers`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `weekSongs`
--
ALTER TABLE `weekSongs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `WordOfDay`
--
ALTER TABLE `WordOfDay`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comment`
--
ALTER TABLE `comment`
  ADD CONSTRAINT `comment_ibfk_1` FOREIGN KEY (`writer_id`) REFERENCES `learners` (`learner_phone`),
  ADD CONSTRAINT `comment_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`);

--
-- Constraints for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `learners` (`learner_phone`);

--
-- Constraints for table `ee_user_datas`
--
ALTER TABLE `ee_user_datas`
  ADD CONSTRAINT `ee_user_datas_ibfk_1` FOREIGN KEY (`phone`) REFERENCES `learners` (`learner_phone`);

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `fk_messages_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notification`
--
ALTER TABLE `notification`
  ADD CONSTRAINT `notification_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`),
  ADD CONSTRAINT `notification_ibfk_2` FOREIGN KEY (`writer_id`) REFERENCES `learners` (`learner_phone`),
  ADD CONSTRAINT `notification_ibfk_3` FOREIGN KEY (`action`) REFERENCES `notification_action` (`action`);

--
-- Constraints for table `package_plans`
--
ALTER TABLE `package_plans`
  ADD CONSTRAINT `package_plans_ibfk_1` FOREIGN KEY (`course_category_id`) REFERENCES `course_categories` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_plan_courses`
--
ALTER TABLE `package_plan_courses`
  ADD CONSTRAINT `package_plan_courses_ibfk_1` FOREIGN KEY (`package_id`) REFERENCES `package_plans` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_plan_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_earnings`
--
ALTER TABLE `partner_earnings`
  ADD CONSTRAINT `partner_earnings_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_password_reset_tokens`
--
ALTER TABLE `partner_password_reset_tokens`
  ADD CONSTRAINT `partner_password_reset_tokens_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`);

--
-- Constraints for table `partner_payment_histories`
--
ALTER TABLE `partner_payment_histories`
  ADD CONSTRAINT `partner_payment_histories_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_payment_methods`
--
ALTER TABLE `partner_payment_methods`
  ADD CONSTRAINT `partner_payment_methods_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_sessions`
--
ALTER TABLE `partner_sessions`
  ADD CONSTRAINT `partner_sessions_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`learner_id`) REFERENCES `learners` (`learner_phone`);

--
-- Constraints for table `report`
--
ALTER TABLE `report`
  ADD CONSTRAINT `report_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`);

--
-- Constraints for table `requestedsongs`
--
ALTER TABLE `requestedsongs`
  ADD CONSTRAINT `requestedsongs_ibfk_1` FOREIGN KEY (`artist_id`) REFERENCES `artists` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
