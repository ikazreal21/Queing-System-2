-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 18, 2025 at 01:55 PM
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
-- Database: `queue`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'College Of Computer Science'),
(2, 'College of Nursing'),
(3, 'College of Psychology');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `name`) VALUES
(1, 'Good Moral'),
(3, 'Form 137'),
(4, 'Transcript of Records');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_codes`
--

CREATE TABLE `password_reset_codes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `reset_code` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset_codes`
--

INSERT INTO `password_reset_codes` (`id`, `user_id`, `reset_code`, `expires_at`) VALUES
(2, 21, '181624', '2025-09-15 11:30:22'),
(3, 22, '413188', '2025-09-15 11:52:15'),
(34, 20, '343271', '2025-09-17 16:22:04');

-- --------------------------------------------------------

--
-- Table structure for table `requests`
--

CREATE TABLE `requests` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `section` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `last_school_year` varchar(20) DEFAULT NULL,
  `last_semester` varchar(20) DEFAULT NULL,
  `documents` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Processing','To Be Claimed','Serving','Completed','Declined') NOT NULL DEFAULT 'Pending',
  `processing_time` datetime DEFAULT NULL,
  `decline_reason` text DEFAULT NULL,
  `claim_date` date DEFAULT NULL,
  `approved_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `processing_deadline` datetime DEFAULT NULL,
  `processing_start` datetime DEFAULT NULL,
  `processing_end` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `queueing_num` int(11) DEFAULT NULL,
  `serving_position` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `first_name`, `last_name`, `student_number`, `section`, `department_id`, `department`, `last_school_year`, `last_semester`, `documents`, `notes`, `attachment`, `created_at`, `status`, `processing_time`, `decline_reason`, `claim_date`, `approved_date`, `completed_date`, `scheduled_date`, `processing_deadline`, `processing_start`, `processing_end`, `updated_at`, `queueing_num`, `serving_position`) VALUES
(22, 'Sammy', 'Agagas', '1231312', '', NULL, '1', '2021-2022', 'Second Semester', 'Form 137', '', '1757952998_0_5. SENTINELS_POST-TEST SURVEY QUESTIONNAIRE.pdf', '2025-09-15 16:16:38', 'Declined', NULL, 'wrong docs', '2025-09-17', '2025-09-17 15:55:00', NULL, '2025-09-17 00:14:00', NULL, '2025-09-17 02:51:12', NULL, '2025-09-17 03:38:43', NULL, NULL),
(23, 'Sammy', 'Agagas', '215346526', '21314142412', NULL, '1', '2021-2022', 'Third Semester', 'Good Moral, Transcript of Records', '', '1757953026_0_5. SENTINELS_PRE-TEST SURVEY QUESTIONNAIRE.pdf', '2025-09-15 16:17:06', 'To Be Claimed', NULL, 's', '2025-09-18', '2025-09-18 18:48:59', NULL, '2025-09-18 18:49:00', NULL, NULL, '2025-09-18 18:48:59', '2025-09-18 19:29:25', 0, NULL),
(24, 'Sammy', 'Agagas', '2155163416515351135', '5636263473845', NULL, '1', '2023-2024', 'First Semester', 'Form 137, Good Moral, Transcript of Records', '', '1757955795_0_Sean Ariel Hilado Agustin (1).png', '2025-09-15 17:03:15', 'Serving', NULL, NULL, '2025-09-18', '2025-09-18 19:14:22', '2025-09-18 19:14:22', '2025-09-18 18:49:00', NULL, '2025-09-18 19:25:03', '2025-09-18 18:48:59', '2025-09-18 19:25:03', 4, 1),
(25, 'Sammy', 'Agagas', '357953245535', '123', NULL, '1', '2021-2022', 'Second Semester', 'Form 137, Good Moral, Transcript of Records', '', '1757960741_0_Sean Ariel Hilado Agustin (1).png', '2025-09-15 18:25:41', 'Completed', NULL, NULL, '2025-09-18', '2025-09-18 19:25:13', '2025-09-18 19:25:13', '2025-10-01 15:59:00', NULL, '2025-09-18 19:24:50', NULL, '2025-09-18 19:25:13', 0, NULL),
(28, 'nars', 'Salam', '213123', '4-Y6-4', NULL, '2', '2022-2023', 'Second Semester', 'Form 137, Transcript of Records', 'hell yteah', '1758177016_0_formal-pic.png, 1758177016_1_5. SENTINELS_POST-TEST SURVEY QUESTIONNAIRE.pdf, 1758177016_2_Black Modern Virtual Reality Presentation (1).jpg', '2025-09-18 06:30:16', 'Serving', NULL, '', '2025-09-18', '2025-09-18 18:36:59', NULL, '2025-09-18 18:37:00', NULL, '2025-09-18 19:25:59', '2025-09-18 18:36:59', '2025-09-18 19:25:59', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','user') NOT NULL,
  `department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `department_id`) VALUES
(6, 'Sammy', 'Agagas', 'sammy@gmail.com', '$2y$10$udQwNH9RPQoGzGVVzTePUeQJACsS/HL3XWKhGfBNuMyjP0y0IA3KC', 'user', NULL),
(11, 'Sean Ariel', 'Agustin', 'seanbading@gmail.com', '$2y$10$FNcSxZsBI0fZ22RtPXtZD.NhIi.55rZ4DPxODDZ4ibTwwcObhYYpW', 'admin', NULL),
(13, 'Erwin', 'Lebrino', 'erwin@gmail.com', '$2y$10$eyJgbQ9LLjsuqam6KJjjq.6ez9q/LXM5okb8IYJZLIs1fgERr1H4G', 'user', NULL),
(15, 'Geraldine', 'Morato', 'jads@gmail.com', '$2y$10$NbVcsIgztmtpEko7qBumreG7Hexy9sckTjwsNsUlFVd70Y9pvMsEW', 'staff', 1),
(16, 'Michael', 'Ursal', 'maykel@gmail.com', '$2y$10$M.yEWY2zHp/cpaMYvUJhieZVzFWfB8XJ9.kvm3E5qlwO1tbBe8GwK', 'user', NULL),
(17, 'Jads', 'Lebrino', 'jads1@gmail.com', '$2y$10$T7RDTm9x53LhTZDNvV2hFOl1rRkA04yq/eYFwyltBg.J63N7kQGya', 'staff', 2),
(18, 'bet', 'log', 'bet@gmail.com', '$2y$10$u626aw4vSAQzD21grf.jS.4QWunI0q3VeXLCbiOFnwEuuueKnXiw.', 'user', NULL),
(19, 'nars', 'Salam', 'nars@gmail.com', '$2y$10$c05pdy6.tYh2nhornbibyOMYgMm6R438J3fVZp5B2.TtxAobc53Q2', 'user', NULL),
(20, 'sean', 'agustin', 'seanariel56@gmail.com', '$2y$10$xp2HrzcWJ0aMjTV/QinxkeJvoo1yDkb3gWdvjWaNbUrDGKoLat9GK', 'user', NULL),
(21, 'michael', 'ursal', 'jvursal8140ant@student.fatima.edu.ph', '$2y$10$jmXgbSWJEjweXm0DtwUY2ORHq06a.UYzbHdUZgClYt2kPqjcHCk9G', 'user', NULL),
(22, 'erkupal', 'kunat', 'elebrino9971ant@student.fatima.edu.ph', '$2y$10$bVxmlbVjYXo8qj.wqzJugeM206RXz6QG6WyTToIf0BPVx5HwnOyDC', 'user', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `requests`
--
ALTER TABLE `requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_requests_department` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_department` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  ADD CONSTRAINT `password_reset_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `requests`
--
ALTER TABLE `requests`
  ADD CONSTRAINT `fk_requests_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
