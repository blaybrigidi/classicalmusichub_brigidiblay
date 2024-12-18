-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 18, 2024 at 04:26 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `classical_music_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `composer_id` int(11) DEFAULT NULL,
  `composition_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communities`
--

CREATE TABLE `communities` (
  `community_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `creator_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `image_path` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `category` enum('Classical','Baroque','Romantic','Contemporary','Theory','Performance','General') NOT NULL DEFAULT 'General',
  `rules` text DEFAULT NULL,
  `guidelines` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `communities`
--

INSERT INTO `communities` (`community_id`, `name`, `description`, `creator_id`, `created_at`, `image_path`, `created_by`, `category`, `rules`, `guidelines`) VALUES
(1, 'Classical Piano Lovers', 'A community for piano enthusiasts', 1, '2024-12-15 23:44:59', NULL, NULL, 'General', NULL, NULL),
(2, 'Symphony Orchestra', 'Discuss your favorite symphonies', 1, '2024-12-15 23:44:59', NULL, NULL, 'General', NULL, NULL),
(3, 'Opera Appreciation', 'For opera lovers and newcomers', 1, '2024-12-15 23:44:59', NULL, NULL, 'General', NULL, NULL),
(4, 'Chamber Music Society', 'Small ensemble appreciation', 1, '2024-12-15 23:44:59', NULL, NULL, 'General', NULL, NULL),
(5, 'Piano Masterclass', 'A community for classical piano enthusiasts', NULL, '2024-12-16 07:11:08', NULL, NULL, 'Classical', NULL, NULL),
(6, 'Baroque Ensemble', 'Discuss baroque music and performance', NULL, '2024-12-16 07:11:08', NULL, NULL, 'Baroque', NULL, NULL),
(7, 'Romantic Era Appreciation', 'For lovers of romantic period music', NULL, '2024-12-16 07:11:08', NULL, NULL, 'Romantic', NULL, NULL),
(8, 'Modern Classical', 'Exploring contemporary classical music', NULL, '2024-12-16 07:11:08', NULL, NULL, 'Contemporary', NULL, NULL),
(9, 'Music Theory Study Group', 'Learn and discuss music theory', NULL, '2024-12-16 07:11:08', NULL, NULL, 'Theory', NULL, NULL),
(10, 'Performance Techniques', 'Share and learn performance tips', NULL, '2024-12-16 07:11:08', NULL, NULL, 'Performance', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `community_events`
--

CREATE TABLE `community_events` (
  `event_id` int(11) NOT NULL,
  `community_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `event_type` enum('online','in-person','hybrid') DEFAULT 'online',
  `location` varchar(255) DEFAULT NULL,
  `max_participants` int(11) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_events`
--

INSERT INTO `community_events` (`event_id`, `community_id`, `title`, `description`, `event_date`, `event_type`, `location`, `max_participants`, `created_by`, `created_at`) VALUES
(1, 6, 'Fun For Jammies', 'Bleh', '2024-12-19 07:50:00', 'in-person', 'Pull up to the hive', 4, 7, '2024-12-16 07:50:31'),
(2, 6, 'daf', 'dfadf', '2025-01-04 07:50:00', 'in-person', 'afda', 5, 7, '2024-12-16 07:50:49'),
(3, 6, 'You until', 'let\'s go', '2024-12-20 07:52:00', 'online', 'Slide', 6, 7, '2024-12-16 07:52:18'),
(4, 6, 'dfda', 'fdafa', '2024-12-21 07:53:00', 'online', 'dfa', 12, 7, '2024-12-16 07:53:26'),
(5, 6, 'hd', 'afda', '2024-12-29 07:53:00', 'online', 'dd', 2, 7, '2024-12-16 07:53:46'),
(6, 1, 'Classical Music Listening Event', 'Just looking for friends who want to listen to music together.', '2024-12-20 04:00:00', 'in-person', '12 Fake Road', 10, 19, '2024-12-17 16:59:48'),
(7, 1, 'dafa', 'dafa', '1212-11-01 12:12:00', 'online', 'dfa', 10, 21, '2024-12-17 20:12:33'),
(8, 1, 'dafaf', 'dfa', '0032-03-11 12:33:00', 'in-person', 'ets', 12, 21, '2024-12-17 20:13:22'),
(9, 3, 'Opera Concert Soon', 'Hey guys im looking for people to go with, I hear changeee will be singing.', '2024-12-24 08:00:00', 'in-person', 'Manchester Square', 4, 21, '2024-12-17 20:24:52');

-- --------------------------------------------------------

--
-- Table structure for table `community_members`
--

CREATE TABLE `community_members` (
  `community_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `role` enum('member','moderator','admin') DEFAULT 'member'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_members`
--

INSERT INTO `community_members` (`community_id`, `user_id`, `joined_at`, `role`) VALUES
(1, 1, '2024-12-15 23:44:59', 'admin'),
(1, 7, '2024-12-16 07:27:43', 'member'),
(1, 19, '2024-12-17 16:58:02', 'member'),
(1, 21, '2024-12-17 19:28:19', 'member'),
(2, 1, '2024-12-15 23:44:59', 'admin'),
(2, 7, '2024-12-16 07:33:29', 'member'),
(2, 21, '2024-12-17 20:15:19', 'member'),
(3, 1, '2024-12-15 23:44:59', 'admin'),
(3, 7, '2024-12-16 07:43:29', 'member'),
(3, 21, '2024-12-17 19:53:23', 'member'),
(3, 23, '2024-12-17 20:30:33', 'member'),
(4, 1, '2024-12-15 23:44:59', 'admin'),
(4, 7, '2024-12-16 07:38:36', 'member'),
(5, 7, '2024-12-16 08:00:26', 'member'),
(5, 21, '2024-12-17 19:57:43', 'member'),
(5, 25, '2024-12-18 15:17:55', 'member'),
(6, 7, '2024-12-16 07:47:49', 'member');

-- --------------------------------------------------------

--
-- Table structure for table `community_posts`
--

CREATE TABLE `community_posts` (
  `post_id` int(11) NOT NULL,
  `community_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `community_posts`
--

INSERT INTO `community_posts` (`post_id`, `community_id`, `user_id`, `content`, `created_at`) VALUES
(1, 2, 7, 'We\'ll sing hallelujah', '2024-12-16 07:34:14'),
(2, 4, 7, 'Till He Comes Again', '2024-12-16 07:38:45'),
(3, 1, 19, 'I recently heard this symphony played by Beethoven I think. It was mainly in B sharp. Does anyone know the name?', '2024-12-17 16:59:03'),
(4, 3, 21, 'I hear that members of a major orchestra have said they fear it will \"never play again” after a ballet company announced it was considering working more closely with an opera company. Is this true?', '2024-12-17 20:23:57'),
(5, 3, 23, 'What’s happening in the opera world? La Scala opened its season with Verdi’s *La Forza del Destino*, featuring Anna Netrebko, while the English National Opera revived The Pirates of Penzance. Glyndebourne welcomed new board members, Virginia Opera appointed an interim CEO, and France is buzzing with premieres like George Benjamin’s *Picture a Day Like This* and Puccini’s rare Edgar. Just thought you\'d want to know :)', '2024-12-17 20:39:29');

-- --------------------------------------------------------

--
-- Table structure for table `composers`
--

CREATE TABLE `composers` (
  `composer_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `death_date` date DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `birth_place` varchar(100) DEFAULT NULL,
  `death_place` varchar(100) DEFAULT NULL,
  `biography` text DEFAULT NULL,
  `portrait_url` varchar(255) DEFAULT NULL,
  `era` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `composers`
--

INSERT INTO `composers` (`composer_id`, `name`, `birth_date`, `death_date`, `nationality`, `birth_place`, `death_place`, `biography`, `portrait_url`, `era`, `created_at`, `updated_at`) VALUES
(1, 'Beethovens', '1802-12-11', '1900-11-12', 'Italian', NULL, NULL, 'Yes', NULL, 'Modern', '2024-12-16 09:46:33', '2024-12-17 16:29:11'),
(2, 'Mozart', '1111-11-05', '1122-12-08', 'French', NULL, NULL, 'He played well', NULL, 'Classical', '2024-12-17 18:48:46', '2024-12-17 18:48:46'),
(3, 'Edward Gynt', '1400-12-11', '1490-11-12', 'Norwegian', NULL, NULL, 'He is a norweigan pianist', NULL, 'Medieval', '2024-12-17 20:28:00', '2024-12-17 20:28:00');

-- --------------------------------------------------------

--
-- Table structure for table `composer_followers`
--

CREATE TABLE `composer_followers` (
  `composer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `composer_preferences`
--

CREATE TABLE `composer_preferences` (
  `user_id` int(11) NOT NULL,
  `composer` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compositions`
--

CREATE TABLE `compositions` (
  `composition_id` int(11) NOT NULL,
  `composer_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `opus_number` varchar(50) DEFAULT NULL,
  `year_composed` int(11) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `audio_path` varchar(255) DEFAULT NULL,
  `sheet_music_path` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `period` varchar(100) DEFAULT NULL,
  `genre` varchar(100) DEFAULT NULL,
  `difficulty_level` enum('Beginner','Intermediate','Advanced','Professional') DEFAULT NULL,
  `sheet_music_file` varchar(255) DEFAULT NULL,
  `preview_file` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `compositions`
--

INSERT INTO `compositions` (`composition_id`, `composer_id`, `title`, `opus_number`, `year_composed`, `duration`, `description`, `audio_path`, `sheet_music_path`, `created_at`, `updated_at`, `period`, `genre`, `difficulty_level`, `sheet_music_file`, `preview_file`, `uploaded_by`) VALUES
(1, 1, 'Marching in', NULL, NULL, NULL, 'Nice music', NULL, NULL, '2024-12-16 11:00:27', '2024-12-16 11:00:27', 'Baroque', 'Funny', 'Intermediate', 'uploads/sheet_music/6760084b68dc7_64d1055efc005e2ac2016909_A3-Value Proposition Canvas-2023.pdf', NULL, NULL),
(2, 2, 'Requiem', NULL, NULL, NULL, 'A beautiful piece', NULL, NULL, '2024-12-17 18:53:07', '2024-12-17 18:53:07', 'Medieval', 'Classical', 'Professional', 'uploads/sheet_music/6761c893763a7_score_0.pdf', NULL, NULL),
(3, 3, 'Peer Gynt', NULL, NULL, NULL, 'A good tune for beginners to learn', NULL, NULL, '2024-12-17 20:28:26', '2024-12-17 20:28:26', 'Medieval', 'Classical Piano', 'Beginner', 'uploads/sheet_music/6761deeab31aa_94069.pdf', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `discussions`
--

CREATE TABLE `discussions` (
  `discussion_id` int(11) NOT NULL,
  `community_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `event_participants`
--

CREATE TABLE `event_participants` (
  `event_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` enum('going','maybe','not_going') DEFAULT 'going',
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_participants`
--

INSERT INTO `event_participants` (`event_id`, `user_id`, `status`, `joined_at`) VALUES
(9, 23, 'going', '2024-12-17 20:39:55');

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `user_id` int(11) NOT NULL,
  `composition_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`user_id`, `composition_id`, `created_at`) VALUES
(19, 1, '2024-12-17 17:47:35'),
(21, 2, '2024-12-17 19:01:37'),
(23, 1, '2024-12-17 20:41:34'),
(23, 3, '2024-12-17 20:40:46'),
(25, 2, '2024-12-18 15:17:48'),
(25, 3, '2024-12-18 15:17:48');

-- --------------------------------------------------------

--
-- Table structure for table `playlists`
--

CREATE TABLE `playlists` (
  `playlist_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlists`
--

INSERT INTO `playlists` (`playlist_id`, `user_id`, `name`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(11, 7, 'i hat this', 'dafadddd', 1, '2024-12-16 01:55:29', '2024-12-16 02:01:46'),
(12, 7, 'Grits', 'dd', 1, '2024-12-16 02:17:10', '2024-12-16 02:17:10'),
(13, 7, '1', 'yeah', 1, '2024-12-16 02:18:31', '2024-12-16 02:18:31'),
(14, 7, 'daf', 'ds', 1, '2024-12-16 02:23:01', '2024-12-16 02:23:01'),
(15, 11, 'Study Playlist', 'Rest and Relaxation', 1, '2024-12-16 11:01:25', '2024-12-16 11:01:25'),
(16, 11, 'dd', 'dd', 1, '2024-12-16 11:05:45', '2024-12-16 11:05:45'),
(17, 19, 'Study Music', 'I want to get all As', 1, '2024-12-17 16:57:26', '2024-12-17 16:57:26'),
(18, 21, 'Music To Sleep', '', 1, '2024-12-17 19:01:52', '2024-12-17 19:01:52'),
(19, 23, 'Becoming Mozart', 'I plan on learning all songs I put in this playlist', 1, '2024-12-17 20:41:50', '2024-12-17 20:41:50'),
(20, 25, 'dfa', 'dfada', 1, '2024-12-18 15:14:55', '2024-12-18 15:14:55');

-- --------------------------------------------------------

--
-- Table structure for table `playlist_items`
--

CREATE TABLE `playlist_items` (
  `playlist_id` int(11) NOT NULL,
  `composition_id` int(11) NOT NULL,
  `position` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `playlist_items`
--

INSERT INTO `playlist_items` (`playlist_id`, `composition_id`, `position`, `added_at`) VALUES
(15, 1, NULL, '2024-12-16 11:24:31'),
(17, 1, NULL, '2024-12-17 16:57:41'),
(18, 2, NULL, '2024-12-17 19:01:56'),
(19, 1, NULL, '2024-12-17 20:42:00'),
(19, 2, NULL, '2024-12-17 20:41:56'),
(20, 2, NULL, '2024-12-18 15:17:44'),
(20, 3, NULL, '2024-12-18 15:17:41');

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `like_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `post_replies`
--

CREATE TABLE `post_replies` (
  `reply_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `post_replies`
--

INSERT INTO `post_replies` (`reply_id`, `post_id`, `user_id`, `content`, `created_at`) VALUES
(1, 2, 7, 'Hi man', '2024-12-16 07:38:55'),
(2, 4, 23, 'I heard that as well. However, she hasn\'t spoken on it personally so don\'t be too worried', '2024-12-17 20:30:52');

-- --------------------------------------------------------

--
-- Table structure for table `timeline_events`
--

CREATE TABLE `timeline_events` (
  `event_id` int(11) NOT NULL,
  `composer_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_type` enum('birth','death','composition','performance','life_event') NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timeline_events`
--

INSERT INTO `timeline_events` (`event_id`, `composer_id`, `title`, `description`, `event_date`, `event_type`, `image_url`, `created_at`) VALUES
(1, 1, 'Beethovens Birth', 'He was born in Bethlehem', '1400-12-12', 'birth', NULL, '2024-12-16 10:27:29'),
(2, 1, 'Beethoven First Plays The Piano', 'This is the first recorded moment where Beethoven plays the piano. The start of greatness', '1775-05-05', 'birth', NULL, '2024-12-17 19:00:53'),
(3, 1, 'Beethoven First Concert', 'Sold out his first concert', '1790-12-11', 'birth', NULL, '2024-12-17 20:29:20'),
(4, NULL, 'Beethoven Mom Conceived Him', 'When the greatest was conceived', '1399-11-11', 'birth', NULL, '2024-12-17 20:29:48');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role` enum('enthusiast','educator','admin','system_admin') DEFAULT 'enthusiast',
  `composer_preferences` text DEFAULT NULL,
  `status` enum('active','suspended') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `password_hash`, `bio`, `created_at`, `updated_at`, `role`, `composer_preferences`, `status`) VALUES
(1, 'glimmerse', 'glimmerse@mail.com', '$2y$10$M1sEyyOMZ/9LFh6C1D2Pi.77efz4w6lVfu60noy4dFvY3vRjcBgyu', 'glimmerse', '2024-12-14 19:23:07', '2024-12-14 19:23:07', 'educator', 'a:2:{i:0;s:7:\"debussy\";i:1;s:8:\"schubert\";}', 'active'),
(2, 'glimmerselinked', 'glimmerselinked@gmail.com', '$2y$10$bxUZg0kYWP1KuiQwwxQPDuymIMEv31J/RZF/boXkkaPWqpbQu4YiO', 'glimmerselinked', '2024-12-14 19:25:46', '2024-12-14 19:25:46', 'educator', 'a:3:{i:0;s:4:\"bach\";i:1;s:7:\"debussy\";i:2;s:6:\"brahms\";}', 'active'),
(3, 'grimed', 'grimed@gmail.com', '$2y$10$.281ZIExsKQ/MrQSRp4RU.Gaqs7LaOseS1xSU/MFQFhJsIX/DSP02', 'grits', '2024-12-14 19:30:13', '2024-12-14 19:30:13', 'educator', 'a:2:{i:0;s:9:\"beethoven\";i:1;s:6:\"brahms\";}', 'active'),
(4, 'uncle', 'unc@gmail.com', '$2y$10$8cq5D1YO9OURhB3dR7TwHO3eUVa/4aSl4x2pAixhU0ymNSuGe6oXy', 'dafdaf', '2024-12-15 10:55:12', '2024-12-15 10:55:12', 'educator', 'a:2:{i:0;s:6:\"chopin\";i:1;s:7:\"debussy\";}', 'active'),
(5, 'kwasi', 'agyeman@gmail.com', '$2y$10$qQI/9iHO8opQqMwcP1SqyOlTDExTffcNivatrl/a.2k8FhL8m1Dk.', 'yes', '2024-12-15 17:16:17', '2024-12-15 17:16:17', 'enthusiast', 'a:2:{i:0;s:6:\"chopin\";i:1;s:7:\"debussy\";}', 'active'),
(6, 'kwasid', 'dagyeman@gmail.com', '$2y$10$ofEsTVjFAkTDFfBWjQFYEOqYmTeIX2lbcAxpPRkhturI7iQH9jLTG', 'yes', '2024-12-15 17:17:43', '2024-12-15 17:17:43', 'enthusiast', 'a:2:{i:0;s:6:\"chopin\";i:1;s:7:\"debussy\";}', 'active'),
(7, 'Yessirski', 'abcd@gmail.com', '$2y$10$QkAlJiIaAKy6ovZkSDv0F.N7fWFyKBtVfbp4qCvkF/ZqpfhFL9r46', 'fdafdas', '2024-12-15 17:19:54', '2024-12-16 08:42:48', 'enthusiast', NULL, 'active'),
(8, 'blaybrigs', 'brigidi@gmail.com', '$2y$10$qOg4y7siA8T7kImnVKgTsOTOVhzM5Uui630Aoq108/9f9v3tCnH82', 'grits', '2024-12-16 08:48:44', '2024-12-16 08:48:44', 'enthusiast', NULL, 'active'),
(9, 'brigidiackah', 'brigidiackah@gmail.com', '$2y$10$H/tNtKVml6a/NzgxEGin7.cqp/fGZXfnk9L52jiEW/kxIPNYMtA3W', 'Yessir', '2024-12-16 08:57:34', '2024-12-16 08:57:34', 'enthusiast', NULL, 'active'),
(10, 'brigsackah', 'brigsackah@gmail.com', '$2y$10$yjAlOe5JgTd6si1.bBAbAuRn1ecLJbqBocyksW80fcaHLkNBJHmUy', 'yessir', '2024-12-16 09:03:30', '2024-12-16 09:03:30', 'educator', NULL, 'active'),
(11, 'blayblay', 'blayblay@gmail.com', '$2y$10$htJUvT.IxLy6PIPm3xsgYObWbo0CLEPGWL07/UjRmWxDqUWJmZHKy', 'bl', '2024-12-16 11:01:06', '2024-12-16 11:01:06', 'enthusiast', NULL, 'active'),
(12, 'grimreaper', 'reaper@gmail.com', '$2y$10$2UE8XiLY2Y6wYIRU1rIILO0BEZYXkDSOm2yFsB7nECK56mSBmH5tG', 'All Hail Reaper', '2024-12-16 13:44:23', '2024-12-16 13:44:23', 'educator', NULL, 'active'),
(13, 'ackahblay', 'blaybrigs@gmail.com', '$2y$10$bQI9Kr2zMOp9c5AKRcf0B.WJ2YNQu2SqD0nkuqpZTijbMnr3veuBC', 'Yes sir', '2024-12-16 17:11:54', '2024-12-16 17:11:54', 'enthusiast', NULL, 'active'),
(14, 'layman', 'layman@gmail.com', '$2y$10$RNknPIgirw.d2Sh5QywQqOZGjZ0Uyi/yr1WyDIGxDKcpge1lVoHha', 'Yes sir', '2024-12-16 17:13:14', '2024-12-16 17:13:14', 'educator', NULL, 'active'),
(15, 'brigsdateach', 'brigsdateach@gmail.com', '$2y$10$9gQQLuJ3c2/qI0Qz923FNu.g7gCGfY4kFvLYsg3NC/vgHLAmHR1uO', 'brigs da teach', '2024-12-17 16:28:51', '2024-12-17 16:28:51', 'educator', NULL, 'active'),
(16, 'manman', 'man@gmail.com', '$2y$10$bR/ICPV3a21Zq/6xkWHcFuq8nksGbVGk2zoy5.wwdutvGZz3QI6tS', 'man', '2024-12-17 16:36:53', '2024-12-17 16:36:53', 'educator', NULL, 'active'),
(17, 'man', 'manman@gmail.com', '$2y$10$3OG/MCIR1h6YT1S8j3dxvOc9LR2waD2M1Yn96V6GKkcN8w4HdmaRi', 'da', '2024-12-17 16:54:18', '2024-12-17 16:54:18', 'educator', NULL, 'active'),
(18, 'iamaman', 'manmanman@gmail.com', '$2y$10$W3KHjJ4II5.dboB7vY4vp.UrPz.QYUh9LIkU9Gc7SFgn0ntJ7qHH.', 'afa', '2024-12-17 16:55:19', '2024-12-17 16:55:19', 'educator', NULL, 'active'),
(19, 'manchild', 'mankey@gmail.com', '$2y$10$KW4jKsj7ZHDi37pQbd271.0Dh2xBzF8EcPq.hmR7sEA6wFAF/DB/S', 'list', '2024-12-17 16:56:59', '2024-12-17 22:28:20', 'enthusiast', NULL, 'active'),
(20, 'christmastime', 'chrismastime@gmail.com', '$2y$10$/3g1gDZBIQobxmBsMZIRM.b4aAD6R.Q6WGC58flfq/vXQh9FnZAG6', 'Love Yourself', '2024-12-17 18:44:53', '2024-12-17 18:44:53', 'educator', NULL, 'active'),
(21, 'beethovenfan', 'mozart@gmail.com', '$2y$10$oteRp3Heyot9xw0UhReYzOl4shv.eJNgKt5lKIjTz7zvRzhnunvCG', 'I love Music', '2024-12-17 19:01:24', '2024-12-17 19:01:24', 'enthusiast', NULL, 'active'),
(22, 'teacher', 'teacher@gmail.com', '$2y$10$sIWsbVHh0GEYkJebzEonpOX.2VIitYyZsSF3QWxAujWen.Qvdon36', 'I am a teacher', '2024-12-17 20:27:18', '2024-12-17 20:27:18', 'educator', NULL, 'active'),
(23, 'brigidiblay', 'student@gmail.com', '$2y$10$T8/e1x8fKwekLY6Hq7grWOHeg8S0rOSRmr75cCURmlVftzpASStQy', 'student profile', '2024-12-17 20:30:25', '2024-12-17 22:27:49', 'enthusiast', NULL, 'active'),
(24, 'superadmin', 'superadmin@gmail.com', '$2y$10$epSNXOCpXckU932XEJvNEuhmBEKuE.U03TgelbcqEZ.caDSjKl2QW', NULL, '2024-12-17 21:44:28', '2024-12-17 21:44:28', 'admin', NULL, 'active'),
(25, 'dropout', 'dropout@gmail.com', '$2y$10$z2aWdEpIciO/85gRt/t01u1nXBcr4HvpAKOtW4wNXpV1INCgmPYx2', 'Pass', '2024-12-18 15:14:34', '2024-12-18 15:14:34', 'enthusiast', NULL, 'active');

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `user_id` int(11) NOT NULL,
  `composition_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_following`
--

CREATE TABLE `user_following` (
  `follower_id` int(11) NOT NULL,
  `following_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `composer_id` (`composer_id`),
  ADD KEY `composition_id` (`composition_id`);

--
-- Indexes for table `communities`
--
ALTER TABLE `communities`
  ADD PRIMARY KEY (`community_id`),
  ADD KEY `creator_id` (`creator_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `community_events`
--
ALTER TABLE `community_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `community_id` (`community_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `community_members`
--
ALTER TABLE `community_members`
  ADD PRIMARY KEY (`community_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `community_id` (`community_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `composers`
--
ALTER TABLE `composers`
  ADD PRIMARY KEY (`composer_id`);

--
-- Indexes for table `composer_followers`
--
ALTER TABLE `composer_followers`
  ADD PRIMARY KEY (`composer_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `composer_preferences`
--
ALTER TABLE `composer_preferences`
  ADD PRIMARY KEY (`user_id`,`composer`);

--
-- Indexes for table `compositions`
--
ALTER TABLE `compositions`
  ADD PRIMARY KEY (`composition_id`),
  ADD KEY `fk_composer_composition` (`composer_id`),
  ADD KEY `uploaded_by` (`uploaded_by`);

--
-- Indexes for table `discussions`
--
ALTER TABLE `discussions`
  ADD PRIMARY KEY (`discussion_id`),
  ADD KEY `community_id` (`community_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD PRIMARY KEY (`event_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`user_id`,`composition_id`),
  ADD KEY `composition_id` (`composition_id`);

--
-- Indexes for table `playlists`
--
ALTER TABLE `playlists`
  ADD PRIMARY KEY (`playlist_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `playlist_items`
--
ALTER TABLE `playlist_items`
  ADD PRIMARY KEY (`playlist_id`,`composition_id`),
  ADD KEY `composition_id` (`composition_id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`like_id`),
  ADD UNIQUE KEY `unique_like` (`post_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `post_replies`
--
ALTER TABLE `post_replies`
  ADD PRIMARY KEY (`reply_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `timeline_events`
--
ALTER TABLE `timeline_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `composer_id` (`composer_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`user_id`,`composition_id`),
  ADD KEY `composition_id` (`composition_id`);

--
-- Indexes for table `user_following`
--
ALTER TABLE `user_following`
  ADD PRIMARY KEY (`follower_id`,`following_id`),
  ADD KEY `following_id` (`following_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communities`
--
ALTER TABLE `communities`
  MODIFY `community_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `community_events`
--
ALTER TABLE `community_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `community_posts`
--
ALTER TABLE `community_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `composers`
--
ALTER TABLE `composers`
  MODIFY `composer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `compositions`
--
ALTER TABLE `compositions`
  MODIFY `composition_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `discussions`
--
ALTER TABLE `discussions`
  MODIFY `discussion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `playlists`
--
ALTER TABLE `playlists`
  MODIFY `playlist_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `post_likes`
--
ALTER TABLE `post_likes`
  MODIFY `like_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `post_replies`
--
ALTER TABLE `post_replies`
  MODIFY `reply_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `timeline_events`
--
ALTER TABLE `timeline_events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`composer_id`) REFERENCES `composers` (`composer_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `comments_ibfk_3` FOREIGN KEY (`composition_id`) REFERENCES `compositions` (`composition_id`) ON DELETE SET NULL;

--
-- Constraints for table `communities`
--
ALTER TABLE `communities`
  ADD CONSTRAINT `communities_ibfk_1` FOREIGN KEY (`creator_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `communities_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `community_events`
--
ALTER TABLE `community_events`
  ADD CONSTRAINT `community_events_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `community_events_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `community_members`
--
ALTER TABLE `community_members`
  ADD CONSTRAINT `community_members_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`),
  ADD CONSTRAINT `community_members_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `community_posts`
--
ALTER TABLE `community_posts`
  ADD CONSTRAINT `community_posts_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`),
  ADD CONSTRAINT `community_posts_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `composer_followers`
--
ALTER TABLE `composer_followers`
  ADD CONSTRAINT `composer_followers_ibfk_1` FOREIGN KEY (`composer_id`) REFERENCES `composers` (`composer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `composer_followers_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `composer_preferences`
--
ALTER TABLE `composer_preferences`
  ADD CONSTRAINT `composer_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `compositions`
--
ALTER TABLE `compositions`
  ADD CONSTRAINT `compositions_ibfk_1` FOREIGN KEY (`composer_id`) REFERENCES `composers` (`composer_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `compositions_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_composer_composition` FOREIGN KEY (`composer_id`) REFERENCES `composers` (`composer_id`) ON DELETE SET NULL;

--
-- Constraints for table `discussions`
--
ALTER TABLE `discussions`
  ADD CONSTRAINT `discussions_ibfk_1` FOREIGN KEY (`community_id`) REFERENCES `communities` (`community_id`),
  ADD CONSTRAINT `discussions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `event_participants`
--
ALTER TABLE `event_participants`
  ADD CONSTRAINT `event_participants_ibfk_1` FOREIGN KEY (`event_id`) REFERENCES `community_events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `event_participants_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorites_ibfk_2` FOREIGN KEY (`composition_id`) REFERENCES `compositions` (`composition_id`) ON DELETE CASCADE;

--
-- Constraints for table `playlists`
--
ALTER TABLE `playlists`
  ADD CONSTRAINT `playlists_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `playlist_items`
--
ALTER TABLE `playlist_items`
  ADD CONSTRAINT `playlist_items_ibfk_1` FOREIGN KEY (`playlist_id`) REFERENCES `playlists` (`playlist_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `playlist_items_ibfk_2` FOREIGN KEY (`composition_id`) REFERENCES `compositions` (`composition_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `post_replies`
--
ALTER TABLE `post_replies`
  ADD CONSTRAINT `post_replies_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `community_posts` (`post_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `post_replies_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `timeline_events`
--
ALTER TABLE `timeline_events`
  ADD CONSTRAINT `timeline_events_ibfk_1` FOREIGN KEY (`composer_id`) REFERENCES `composers` (`composer_id`) ON DELETE SET NULL;

--
-- Constraints for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_favorites_ibfk_2` FOREIGN KEY (`composition_id`) REFERENCES `compositions` (`composition_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_following`
--
ALTER TABLE `user_following`
  ADD CONSTRAINT `user_following_ibfk_1` FOREIGN KEY (`follower_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_following_ibfk_2` FOREIGN KEY (`following_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
