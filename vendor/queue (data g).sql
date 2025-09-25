-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 21, 2025 at 03:36 PM
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
  `name` varchar(255) NOT NULL,
  `processing_days` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `name`, `processing_days`) VALUES
(1, 'Good Moral', 1),
(3, 'Form 137', 1),
(4, 'Transcript of Records', 1),
(7, 'Certificate of Graduation', 7);

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
(35, 20, '716968', '2025-09-20 15:01:13');

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
  `status` enum('Pending','Processing','To Be Claimed','Serving','Completed','Declined','In Queue Now') NOT NULL DEFAULT 'Pending',
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
  `serving_position` int(11) DEFAULT NULL,
  `walk_in` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Walk-In, 0 = Online/Regular Request'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `first_name`, `last_name`, `student_number`, `section`, `department_id`, `department`, `last_school_year`, `last_semester`, `documents`, `notes`, `attachment`, `created_at`, `status`, `processing_time`, `decline_reason`, `claim_date`, `approved_date`, `completed_date`, `scheduled_date`, `processing_deadline`, `processing_start`, `processing_end`, `updated_at`, `queueing_num`, `serving_position`, `walk_in`) VALUES
(29, 'Sammy', 'Agagas', '03220009971', 'BSIT 4-Y1-2', NULL, '1', '2021-2022', 'Second Semester', 'Transcript of Records', '', '1758206399_0_★.jpg', '2025-09-18 14:39:59', 'Completed', NULL, NULL, '2025-09-19', '2025-09-20 12:41:00', '2025-09-20 12:41:00', '2025-09-18 23:16:00', NULL, '2025-09-19 00:04:01', '2025-09-18 23:16:00', '2025-09-20 12:41:00', 0, NULL, 0),
(30, 'Erwin', 'Lebrino', '03220009971', 'BSIT 4-Y1-2', NULL, '1', '2023-2024', 'Third Semester', 'Form 137', '', '1758207856_0__MOMO.jpg', '2025-09-18 15:04:16', 'Completed', NULL, NULL, '2025-09-19', '2025-09-19 00:14:25', '2025-09-19 00:14:25', '2025-09-18 23:16:00', NULL, '2025-09-19 00:03:57', '2025-09-18 23:15:59', '2025-09-19 00:14:25', 0, NULL, 0),
(31, 'Michael', 'Ursal', '03220009971', 'BSIT 4-Y1-2', NULL, '1', '2021-2022', 'Third Semester', 'Good Moral, Transcript of Records', '', '1758208884_0_590e786a-4e66-4506-88ca-406d707630a4.jpg', '2025-09-18 15:21:24', 'Completed', NULL, NULL, '2025-09-19', '2025-09-20 15:30:59', '2025-09-20 15:30:59', '2025-09-18 23:23:00', NULL, '2025-09-19 00:20:55', '2025-09-18 23:22:24', '2025-09-20 15:30:59', 0, NULL, 0),
(32, 'nars', 'Salam', '03220009971', 'BSIT 4-Y1-2', NULL, '1', '2024-2025', 'Second Semester', 'Form 137', '', '1758209752_0_TEST_GRANT_LUIS.pdf', '2025-09-18 15:35:52', 'To Be Claimed', NULL, 'No reason provided', '2025-09-19', '2025-09-21 17:50:49', '2025-09-19 00:10:49', '2025-09-22 11:49:36', NULL, '2025-09-21 17:49:36', '2025-09-21 17:50:49', '2025-09-21 17:50:49', 0, NULL, 0),
(33, 'Sammy', 'Agagas', '03220009971', 'BSIT 4-Y1-2', NULL, '1', '2022-2023', 'Second Semester', 'Certificate of Graduation', 'asda', '1758346630_0_ACTIVITY#3-MIDTERM_Lebrino.pdf', '2025-09-20 05:37:10', 'Serving', NULL, '', '2025-09-21', '2025-09-21 04:10:07', NULL, '2025-09-27 22:10:03', NULL, '2025-09-21 18:19:37', '2025-09-21 04:10:07', '2025-09-21 18:19:37', 1, 1, 0),
(34, 'Sammy', 'Agagas', '', '', NULL, '', '', '', 'Certificate of Graduation, Good Moral', '', NULL, '2025-09-20 11:58:13', 'Pending', NULL, NULL, NULL, NULL, NULL, '2025-09-27 21:20:59', NULL, NULL, NULL, '2025-09-21 03:21:01', NULL, NULL, 0),
(35, 'Erwin', 'Lebrino', '0322954515', '4-Y1-2', NULL, '3', '2020-2021', 'Third Semester', 'Certificate of Graduation, Good Moral, Transcript of Records', 'hehe', '1758384223_0_download (2) (1).jpg', '2025-09-20 16:03:43', 'Declined', NULL, '12', '2025-09-21', '2025-09-21 03:58:52', NULL, '2025-09-28 09:20:33', NULL, '2025-09-21 15:20:33', '2025-09-21 03:58:52', '2025-09-21 15:58:11', 0, NULL, 0),
(36, 'nars', 'Salam', '03222958511', '4-Y6-4', NULL, '2', '2021-2022', 'Third Semester', 'Certificate of Graduation, Form 137, Transcript of Records', 'rahg', '1758384293_0_formal-pic.png', '2025-09-20 16:04:53', 'To Be Claimed', NULL, NULL, NULL, '2025-09-21 21:20:20', NULL, '2025-09-28 12:50:12', NULL, '2025-09-21 18:50:12', '2025-09-21 21:20:20', '2025-09-21 21:20:20', NULL, NULL, 0),
(37, 'Sammy', 'Agagas', '0322765582', '4-Y1-2', NULL, '1', '2021-2022', 'First Semester', 'Certificate of Graduation, Form 137', 'test', '1758386132_0_formal-pic.png', '2025-09-20 16:35:32', 'Serving', NULL, '', '2025-09-21', '2025-09-21 15:49:31', NULL, '2025-09-28 09:47:20', NULL, '2025-09-21 18:19:53', '2025-09-21 15:49:31', '2025-09-21 18:19:53', 0, 3, 0),
(38, 'sean', 'agustin', '03220008724', 'BSPSYCH-Y2-6', NULL, '3', '2022-2023', 'Second Semester', 'Certificate of Graduation, Form 137', 'i need you more today', '1758386281_0_image_2025-09-21_003740312.png', '2025-09-20 16:38:01', 'Processing', NULL, 'wr', NULL, '2025-09-21 01:57:31', NULL, '2025-09-28 11:52:13', NULL, '2025-09-21 17:52:13', NULL, '2025-09-21 17:52:13', NULL, NULL, 0),
(39, 'Sammy', 'Agagas', '4214442', '4-Y1-2', NULL, '1', '2020-2021', 'Second Semester', 'Certificate of Graduation, Form 137', 'TEST THIS', '1758435952_0_formal-pic.png', '2025-09-21 06:25:52', 'Serving', NULL, NULL, '2025-09-21', '2025-09-21 15:41:21', NULL, '2025-09-28 09:36:33', NULL, '2025-09-21 18:19:51', '2025-09-21 15:41:21', '2025-09-21 18:19:51', 0, 2, 0),
(65, 'james', 'begino', '03229425', '4-Y1-2', NULL, '2', '2021-2022', 'Second Semester', 'Certificate of Graduation, Form 137', 'sdasd', '1758461255_formal-pic.png', '2025-09-21 13:27:35', 'In Queue Now', NULL, NULL, NULL, '2025-09-21 15:27:35', NULL, '2025-09-28 00:00:00', NULL, '2025-09-21 15:27:35', '2025-09-28 00:00:00', '2025-09-21 21:29:58', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff_departments`
--

CREATE TABLE `staff_departments` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_departments`
--

INSERT INTO `staff_departments` (`id`, `staff_id`, `department_id`) VALUES
(7, 24, 1),
(8, 24, 2),
(10, 17, 2),
(16, 15, 2);

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
  `department_id` int(11) DEFAULT NULL,
  `counter_no` int(11) DEFAULT NULL COMMENT 'Counter/Teller number for staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `password`, `role`, `department_id`, `counter_no`) VALUES
(6, 'Sammy', 'Agagas', 'sammy@gmail.com', '$2y$10$udQwNH9RPQoGzGVVzTePUeQJACsS/HL3XWKhGfBNuMyjP0y0IA3KC', 'user', NULL, NULL),
(11, 'Sean Ariel', 'Agustin', 'seanbading@gmail.com', '$2y$10$FNcSxZsBI0fZ22RtPXtZD.NhIi.55rZ4DPxODDZ4ibTwwcObhYYpW', 'admin', NULL, NULL),
(13, 'Erwin', 'Lebrino', 'erwin@gmail.com', '$2y$10$eyJgbQ9LLjsuqam6KJjjq.6ez9q/LXM5okb8IYJZLIs1fgERr1H4G', 'user', NULL, NULL),
(15, 'Geraldine', 'Morato', 'jads@gmail.com', '$2y$10$NbVcsIgztmtpEko7qBumreG7Hexy9sckTjwsNsUlFVd70Y9pvMsEW', 'staff', 1, 1),
(16, 'Michael', 'Ursal', 'maykel@gmail.com', '$2y$10$M.yEWY2zHp/cpaMYvUJhieZVzFWfB8XJ9.kvm3E5qlwO1tbBe8GwK', 'user', NULL, NULL),
(17, 'Jads', 'Lebrino', 'jads1@gmail.com', '$2y$10$T7RDTm9x53LhTZDNvV2hFOl1rRkA04yq/eYFwyltBg.J63N7kQGya', 'staff', 2, 2),
(18, 'bet', 'log', 'bet@gmail.com', '$2y$10$u626aw4vSAQzD21grf.jS.4QWunI0q3VeXLCbiOFnwEuuueKnXiw.', 'user', NULL, NULL),
(19, 'nars', 'Salam', 'nars@gmail.com', '$2y$10$c05pdy6.tYh2nhornbibyOMYgMm6R438J3fVZp5B2.TtxAobc53Q2', 'user', NULL, NULL),
(20, 'sean', 'agustin', 'seanariel56@gmail.com', '$2y$10$xp2HrzcWJ0aMjTV/QinxkeJvoo1yDkb3gWdvjWaNbUrDGKoLat9GK', 'user', NULL, NULL),
(21, 'michael', 'ursal', 'jvursal8140ant@student.fatima.edu.ph', '$2y$10$jmXgbSWJEjweXm0DtwUY2ORHq06a.UYzbHdUZgClYt2kPqjcHCk9G', 'user', NULL, NULL),
(22, 'erkupal', 'kunat', 'elebrino9971ant@student.fatima.edu.ph', '$2y$10$bVxmlbVjYXo8qj.wqzJugeM206RXz6QG6WyTToIf0BPVx5HwnOyDC', 'user', NULL, NULL),
(24, 'Maria Boy Nigga', 'Morato', 'maria@gmail.com', '$2y$10$H518AC4cDx/15M.hshSXvOAFVzfP42cx0hp7KFn..8yksvD51rKBi', 'staff', NULL, 4);

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
-- Indexes for table `staff_departments`
--
ALTER TABLE `staff_departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `department_id` (`department_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `staff_departments`
--
ALTER TABLE `staff_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

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
-- Constraints for table `staff_departments`
--
ALTER TABLE `staff_departments`
  ADD CONSTRAINT `staff_departments_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
