-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2025 at 12:39 PM
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
(1, 'Bachelor of Science in Nursing'),
(2, 'Bachelor of Science in Pharmacy'),
(3, 'Bachelor of Science in Physical Therapy'),
(4, 'Bachelor of Science in Medical Laboratory Science (Medical Technology'),
(5, 'Bachelor of Science in International Hospitality Management'),
(6, 'Bachelor of Elementary Education'),
(7, 'Bachelor of Special Needs Education Major in Early Childhood Education'),
(8, 'Bachelor of Science in Criminology'),
(9, 'Bachelor of Science in International Tourism Management'),
(10, 'Bachelor of Science in Information Technology'),
(11, 'Bachelor of Science in Accountancy'),
(12, 'Bachelor of Science in Accounting Information System'),
(13, 'Bachelor of Science in Business Administration Major in Banking'),
(14, 'Bachelor of Science in Business Administration Major in Marketing Management'),
(15, 'Bachelor of Science in Business Administration Major in Operations and Supply Chain Management'),
(16, 'Bachelor of Science in Psychology');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `processing_days` int(11) NOT NULL DEFAULT 1,
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`id`, `name`, `processing_days`, `fee`) VALUES
(1, 'Good Moral', 1, 0.00),
(3, 'Form 137', 1, 0.00),
(4, 'Transcript of Records', 1, 0.00),
(7, 'Certificate of Graduation', 7, 0.00),
(8, 'Certificate of Enrollment', 1, 0.00),
(9, 'Honorable Dismissal', 3, 0.00),
(10, 'Student Clearance', 3, 0.00),
(11, 'Certification of Units Earned', 1, 0.00),
(12, 'Certification of General Weighted Average', 1, 0.00),
(14, 'Certificate of Registration (COR)', 1, 0.00),
(15, 'Certificate of Enrollment Verification', 1, 0.00),
(16, 'Certificate of Transfer Credentials', 3, 100.00),
(17, 'Certificate of Academic Standing', 2, 50.00),
(18, 'Certificate of No Pending Case', 1, 50.00),
(19, 'Certificate of Authentication and Verification (CAV)', 5, 200.00),
(20, 'Certificate of English as Medium of Instruction (EMI)', 2, 100.00),
(21, 'Certificate of Graduation (for Employment)', 3, 100.00),
(22, 'Certificate of Non-Issuance of Diploma', 2, 50.00),
(23, 'Certificate of School Records', 2, 100.00),
(24, 'Certificate of Attendance', 1, 0.00),
(25, 'Certificate of Admission', 1, 0.00),
(26, 'Certificate of Academic Rank (for honor students)', 3, 50.00),
(27, 'Certificate of Enrollment Status', 1, 0.00),
(28, 'Diploma (Re-Issuance)', 7, 500.00),
(29, 'CTC / Certified True Copy of Documents', 2, 100.00);

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
(3, 22, '413188', '2025-09-15 11:52:15');

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
  `served_by` int(11) DEFAULT NULL,
  `walk_in` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = Walk-In, 0 = Online/Regular Request'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requests`
--

INSERT INTO `requests` (`id`, `first_name`, `last_name`, `student_number`, `section`, `department_id`, `department`, `last_school_year`, `last_semester`, `documents`, `notes`, `attachment`, `created_at`, `status`, `processing_time`, `decline_reason`, `claim_date`, `approved_date`, `completed_date`, `scheduled_date`, `processing_deadline`, `processing_start`, `processing_end`, `updated_at`, `queueing_num`, `serving_position`, `served_by`, `walk_in`) VALUES
(67, 'Sammy', 'Agagas', '03220009971', 'BSN 3-Y1-3', NULL, '2', '2024-2025', 'Third Semester', 'Good Moral', '', '1758464709_0_Momo icon.jpg', '2025-09-21 14:25:09', 'Completed', '0000-00-00 00:00:00', NULL, NULL, '2025-09-22 00:23:00', '2025-09-22 00:23:00', '2025-09-22 16:25:22', '2025-09-22 22:25:09', '2025-09-21 23:52:12', '2025-09-21 22:25:37', '2025-09-22 00:23:00', 0, NULL, 15, 0),
(68, 'Michael', 'Ursal', '03220009971', 'BSN 3-Y1-3', NULL, '1', '2022-2023', 'Second Semester', 'Certificate of Graduation', '', '1758466061_0_permit1.pdf', '2025-09-21 14:47:41', 'Completed', '0000-00-00 00:00:00', NULL, NULL, '2025-09-27 13:23:11', '2025-09-27 13:23:11', '2025-09-28 16:48:11', '2025-09-28 22:47:41', '2025-09-27 13:22:09', '2025-09-21 22:48:14', '2025-09-27 13:23:11', 0, NULL, 17, 0),
(69, 'Michael', 'Ursal', '03220009971', 'BSN 3-Y1-3', NULL, '2', '2022-2023', 'Second Semester', 'Transcript of Records', '', '1758471709_0_★.jpg', '2025-09-21 16:21:49', 'Completed', '0000-00-00 00:00:00', NULL, NULL, '2025-09-27 13:23:39', '2025-09-27 13:23:39', '2025-09-22 18:22:19', '2025-09-23 00:21:49', '2025-09-27 13:22:12', '2025-09-22 00:22:23', '2025-09-27 13:23:39', 0, NULL, 17, 0),
(70, 'Erwin', 'Lebrino', '03220009971', 'BSP 4-Y2-3', NULL, '3', '2023-2024', 'Third Semester', 'Good Moral', '', '1758950373_0_monalisa.jpg', '2025-09-27 05:19:33', 'Completed', '0000-00-00 00:00:00', NULL, NULL, '2025-09-27 13:24:34', '2025-09-27 13:24:34', '2025-09-28 07:20:12', '2025-09-28 13:19:33', '2025-09-27 13:22:15', '2025-09-27 13:21:38', '2025-09-27 13:24:34', 0, NULL, 17, 0),
(71, 'Maria Boy Nigga', 'Ursal', '03220009971', 'HM 1-Y-3', NULL, '3', '2022-2023', 'Third Semester', 'Form 137', '', '1758950449_50801697_228087584741527_7892674040500846592_n.jpg', '2025-09-27 05:20:49', 'Completed', NULL, NULL, NULL, '2025-09-27 13:24:36', '2025-09-27 13:24:36', '2025-09-28 00:00:00', NULL, '2025-09-27 13:22:17', '2025-09-28 00:00:00', '2025-09-27 13:24:36', 0, NULL, 17, 1),
(73, 'Sammy', 'Agagas', '03220009971', 'BSN 3-Y1-3', NULL, '2', '2022-2023', 'Second Semester', 'Form 137', '', '1758960310_0_1. SENTINELS_Application for Ethical Review Form.pdf', '2025-09-27 08:05:10', 'Completed', '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, '2025-09-28 16:05:10', '2025-09-28 16:05:10', '2025-09-27 16:05:10', NULL, '2025-09-27 16:05:10', 0, NULL, NULL, 0),
(74, 'Sammy', 'Agagas', '03220009971', 'BSN 3-Y1-3', NULL, '2', '2024-2025', 'Second Semester', 'Good Moral', '', '1758981380_0_6. SENTINELS_Informed Consent Form (1).pdf', '2025-09-27 13:56:20', 'Serving', '0000-00-00 00:00:00', NULL, NULL, '2025-09-27 21:57:01', NULL, '2025-09-28 15:56:43', '2025-09-28 21:56:20', '2025-10-16 23:19:13', '2025-09-27 21:57:01', '2025-10-16 23:19:13', 1, 1, 15, 0),
(76, 'Erwin', 'Lebrino', '', '', NULL, '', '', '', 'Certificate of Graduation, Form 137, Good Moral, Transcript of Records', '', NULL, '2025-10-12 08:20:51', 'Completed', '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, '2025-10-19 16:20:51', '2025-10-19 16:20:51', '2025-10-12 16:20:51', NULL, '2025-10-12 16:20:51', 0, NULL, NULL, 0),
(77, 'Erwin', 'Lebrino', '', '', NULL, '', '', '', 'Form 137, Transcript of Records', '', NULL, '2025-10-12 08:21:07', 'Completed', '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, '2025-10-13 16:21:07', '2025-10-13 16:21:07', '2025-10-12 16:21:07', NULL, '2025-10-12 16:21:07', 0, NULL, NULL, 0),
(78, 'Erwin', 'Lebrino', '03220008140', 'BSIT 4-Y1-3', NULL, '1', '2024-2025', 'First Semester', 'Certificate of Graduation, Form 137, Good Moral, Transcript of Records', 'test', '1760257311_0_Untitled Project.jpg', '2025-10-12 08:21:51', 'Completed', '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, '2025-10-19 16:21:51', '2025-10-19 16:21:51', '2025-10-12 16:21:51', NULL, '2025-10-12 16:21:51', 0, NULL, NULL, 0),
(79, 'Liam', 'Reyes', '03220000872', '', NULL, '10', '2021-2022', 'Second Semester', 'Certificate of Enrollment, Certificate of Graduation', '', '1760374790_0_Gemini_Generated_Image_2dkltq2dkltq2dkl.png', '2025-10-13 16:59:50', 'In Queue Now', '0000-00-00 00:00:00', NULL, '2025-10-16', '2025-10-16 22:15:38', NULL, '2025-10-23 16:13:40', '2025-10-21 00:59:50', NULL, '2025-10-16 22:15:38', '2025-10-16 23:58:22', 0, NULL, 15, 0),
(80, 'Sofia', 'Delos Santos', '03225174372', '', NULL, '10', '2021-2022', 'First Semester', 'Certificate of Academic Rank (for honor students), Certificate of Transfer Credentials, Diploma (Re-Issuance)', '', '1760427695_0_Gemini_Generated_Image_2dkltq2dkltq2dkl.png', '2025-10-17 07:41:35', 'Completed', '0000-00-00 00:00:00', NULL, '2025-10-14', '2025-10-14 15:49:53', '2025-10-14 15:49:53', '2025-10-21 09:48:39', '2025-10-21 15:41:35', '2025-10-14 15:49:26', '2025-10-14 15:48:45', '2025-10-14 15:49:53', 0, NULL, 15, 0),
(81, 'Sammy', 'Agagas', '03220008596', 'BSIT 4-Y1-1', NULL, '10', '2020-2021', 'Second Semester', 'Certificate of Academic Rank (for honor students), Certificate of Enrollment Verification, Certificate of Registration (COR)', '', '1760622022_0_Gemini_Generated_Image_2dkltq2dkltq2dkl.png', '2025-10-17 13:40:22', 'Declined', '0000-00-00 00:00:00', 'test', '2025-10-17', '2025-10-16 22:15:37', NULL, NULL, '2025-10-19 21:40:22', NULL, NULL, '2025-10-17 04:02:02', 2, 2, 15, 0),
(82, 'sean', 'agustin', '03220008724', 'BSIT 4-Y1-2', NULL, '10', '2023-2024', 'Second Semester', 'Certificate of Enrollment Verification, Certificate of Non-Issuance of Diploma, Form 137, Honorable Dismissal', 'need', '1760623313_0_AgustinSeanAriel -20251015.pdf', '2025-10-17 14:01:53', 'Serving', '0000-00-00 00:00:00', NULL, '2025-10-17', '2025-10-16 22:15:36', NULL, '2025-10-19 16:13:37', '2025-10-19 22:01:53', '2025-10-17 03:39:29', '2025-10-16 22:15:36', '2025-10-17 03:39:29', 1, 1, 15, 0),
(83, 'Chloe', 'Cruz', '03220000109', 'BSIT 3-B1-2', NULL, '10', '2022-2023', 'First Semester', 'Certificate of Academic Rank (for honor students), Certification of General Weighted Average', 'test', '1760623455_0_download (2) (1).jpg', '2025-10-17 14:04:15', 'In Queue Now', '0000-00-00 00:00:00', NULL, '2025-10-17', '2025-10-16 23:06:09', '2025-10-16 23:06:09', '2025-10-19 16:15:27', '2025-10-19 22:04:15', NULL, '2025-10-16 22:15:34', '2025-10-17 00:52:29', 0, NULL, 15, 0),
(84, 'Sophia', 'Lopez', '032265552360', 'BSIT 2-B5-3', NULL, '10', '2021-2022', 'Second Semester', 'Certificate of Academic Rank (for honor students), Certificate of English as Medium of Instruction (EMI)', 'test', '1760631166_0_Gemini_Generated_Image_2dkltq2dkltq2dkl.png', '2025-10-16 16:12:46', 'In Queue Now', '0000-00-00 00:00:00', NULL, '2025-10-17', '2025-10-17 00:12:54', NULL, '2025-10-19 18:12:52', '2025-10-20 00:12:46', NULL, '2025-10-17 00:12:54', '2025-10-17 00:43:31', 0, NULL, 15, 0),
(85, 'sean', 'agustin', '03220008724', '', NULL, '10', '2022-2023', 'Third Semester', 'Certificate of Academic Rank (for honor students), Certificate of Attendance', '', '1760638826_0_Gemini_Generated_Image_2dkltq2dkltq2dkl.png', '2025-10-16 18:20:26', 'In Queue Now', '0000-00-00 00:00:00', NULL, '2025-10-17', '2025-10-17 02:59:32', NULL, '2025-10-19 20:59:28', '2025-10-20 02:20:26', '2025-10-17 02:59:28', '2025-10-17 02:59:32', '2025-10-17 03:36:50', 3, NULL, 0, 0),
(86, 'Sammy', 'Agagas', '03220008596', 'BSIT 4-Y6-2', NULL, '10', '2022-2023', 'Second Semester', 'Certificate of Academic Rank (for honor students), Form 137', '', '1760641120_0_profile.jpg', '2025-10-16 18:58:40', 'To Be Claimed', '0000-00-00 00:00:00', NULL, NULL, '2025-10-17 03:55:09', NULL, '2025-10-19 21:55:07', '2025-10-20 02:58:40', '2025-10-17 03:55:07', '2025-10-17 03:55:09', '2025-10-17 03:55:09', 4, 4, NULL, 0);

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
(20, 15, 10);

-- --------------------------------------------------------

--
-- Table structure for table `strands`
--

CREATE TABLE `strands` (
  `id` int(11) NOT NULL,
  `name` varchar(250) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `strands`
--

INSERT INTO `strands` (`id`, `name`) VALUES
(2, 'GAS'),
(3, 'ABM'),
(4, 'HUMSS'),
(5, 'STEM');

-- --------------------------------------------------------

--
-- Table structure for table `student_database`
--

CREATE TABLE `student_database` (
  `student_id` int(11) NOT NULL,
  `student_num` varchar(11) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `strands` varchar(100) DEFAULT NULL,
  `college` tinyint(1) DEFAULT 0,
  `shs` tinyint(1) DEFAULT 0,
  `alumni` tinyint(1) DEFAULT 0,
  `graduating` tinyint(1) DEFAULT 0,
  `new_student` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_database`
--

INSERT INTO `student_database` (`student_id`, `student_num`, `section`, `first_name`, `last_name`, `department`, `strands`, `college`, `shs`, `alumni`, `graduating`, `new_student`) VALUES
(1, NULL, 'A', 'Zara', 'Cruz', NULL, 'STEM', 0, 1, 0, 0, 1),
(2, NULL, 'A', 'Mia', 'Lopez', NULL, 'ABM', 0, 1, 0, 0, 1),
(3, NULL, 'B', 'Ella', 'Garcia', NULL, 'HUMSS', 0, 1, 0, 0, 1),
(4, NULL, 'B', 'Sofia', 'Delos Santos', NULL, 'STEM', 0, 1, 0, 0, 1),
(5, NULL, 'C', 'Ava', 'Morales', NULL, 'ABM', 0, 1, 0, 0, 1),
(6, NULL, 'C', 'Isla', 'Fernandez', NULL, 'HUMSS', 0, 1, 0, 0, 1),
(7, NULL, 'D', 'Luna', 'Garcia', NULL, 'STEM', 0, 1, 0, 0, 1),
(8, NULL, 'D', 'Nina', 'Reyes', NULL, 'ABM', 0, 1, 0, 0, 1),
(9, NULL, 'A', 'Chloe', 'Cruz', NULL, 'HUMSS', 0, 1, 0, 0, 1),
(10, NULL, 'A', 'Leah', 'Torres', NULL, 'STEM', 0, 1, 0, 0, 1),
(11, NULL, 'B', 'Aria', 'Velasco', NULL, 'ABM', 0, 1, 0, 0, 1),
(12, NULL, 'B', 'Zoe', 'Fernandez', NULL, 'HUMSS', 0, 1, 0, 0, 1),
(13, NULL, 'C', 'Maya', 'Garcia', NULL, 'STEM', 0, 1, 0, 0, 1),
(14, NULL, 'C', 'Althea', 'Reyes', NULL, 'ABM', 0, 1, 0, 0, 1),
(15, NULL, 'D', 'Iris', 'Morales', NULL, 'HUMSS', 0, 1, 0, 0, 1),
(16, '03220008701', 'A', 'Liam', 'Reyes', 'Computer Science', NULL, 1, 0, 0, 0, 0),
(17, '03220008702', 'A', 'Kai', 'Santos', 'Information Technology', NULL, 1, 0, 0, 0, 0),
(18, '03220008703', 'B', 'Noah', 'Torres', 'Engineering', NULL, 1, 0, 0, 0, 0),
(19, '03220008704', 'B', 'Ethan', 'Velasco', 'Business Administration', NULL, 1, 0, 0, 0, 0),
(20, '03220008705', 'C', 'Lucas', 'Ramos', 'Information Systems', NULL, 1, 0, 0, 0, 0),
(21, '03220008706', 'C', 'Leo', 'De Guzman', 'Computer Engineering', NULL, 1, 0, 0, 0, 0),
(22, '03220008707', 'D', 'Jace', 'Mendoza', 'Information Technology', NULL, 1, 0, 0, 0, 0),
(23, '03220008708', 'D', 'Asher', 'Villanueva', 'Marketing', NULL, 1, 0, 0, 0, 0),
(24, '03220008709', 'A', 'Mateo', 'Santos', 'Computer Science', NULL, 1, 0, 0, 0, 0),
(25, '03220008710', 'A', 'Aidan', 'Torres', 'Business Administration', NULL, 1, 0, 0, 0, 0),
(26, '03220008711', 'B', 'Ryan', 'Delos Santos', 'Information Systems', NULL, 1, 0, 0, 0, 0),
(27, '03220008712', 'B', 'Nathan', 'Garcia', 'Engineering', NULL, 1, 0, 0, 0, 0),
(28, '03220008713', 'C', 'Daniel', 'De Guzman', 'Information Technology', NULL, 1, 0, 0, 0, 0),
(29, '03220008714', 'C', 'Gabriel', 'Mendoza', 'Marketing', NULL, 1, 0, 0, 0, 0),
(30, '03220008715', 'D', 'Sebastian', 'Villanueva', 'Computer Engineering', NULL, 1, 0, 0, 0, 0),
(31, '03220008716', 'A', 'Nathaniel', 'Delos Santos', 'Computer Science', NULL, 1, 0, 0, 1, 0),
(32, '03220008717', 'A', 'Samuel', 'Mendoza', 'Marketing', NULL, 1, 0, 0, 1, 0),
(33, '03220008718', 'B', 'David', 'Villanueva', 'Computer Engineering', NULL, 1, 0, 0, 1, 0),
(34, '03220008719', 'B', 'Caleb', 'Santos', 'Information Systems', NULL, 1, 0, 0, 1, 0),
(35, '03220008720', 'C', 'Isaac', 'Torres', 'Business Administration', NULL, 1, 0, 0, 1, 0),
(36, '03220008721', 'C', 'Ethan', 'Garcia', NULL, 'STEM', 0, 1, 0, 1, 0),
(37, '03220008722', 'D', 'Liam', 'Velasco', NULL, 'ABM', 0, 1, 0, 1, 0),
(38, '03220008723', 'D', 'Mia', 'Fernandez', NULL, 'HUMSS', 0, 1, 0, 1, 0),
(39, '03220008724', 'A', 'Noah', 'Garcia', 'Information Technology', NULL, 1, 0, 0, 1, 0),
(40, '03220008725', 'A', 'Zara', 'Santos', 'Computer Science', NULL, 1, 0, 0, 1, 0),
(41, '03220008726', 'B', 'Kai', 'Reyes', 'Information Systems', NULL, 1, 0, 1, 0, 0),
(42, '03220008727', 'B', 'Sofia', 'Lopez', NULL, 'STEM', 0, 1, 1, 0, 0),
(43, '03220008728', 'C', 'Ella', 'Torres', NULL, 'HUMSS', 0, 1, 1, 0, 0),
(44, '03220008729', 'C', 'Ava', 'Velasco', 'Business Administration', NULL, 1, 0, 1, 0, 0),
(45, '03220008730', 'D', 'Jace', 'Delos Santos', 'Marketing', NULL, 1, 0, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `student_num` varchar(11) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff','user') NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `counter_no` int(11) DEFAULT NULL COMMENT 'Counter/Teller number for staff',
  `section` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `strands` varchar(50) DEFAULT NULL,
  `college` tinyint(1) DEFAULT 0,
  `shs` tinyint(1) DEFAULT 0,
  `alumni` tinyint(1) DEFAULT 0,
  `graduating` tinyint(1) DEFAULT 0,
  `new_student` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `student_num`, `first_name`, `last_name`, `email`, `password`, `role`, `department_id`, `counter_no`, `section`, `department`, `strands`, `college`, `shs`, `alumni`, `graduating`, `new_student`) VALUES
(6, '03220008596', 'Sammy', 'Agagas', 'sammy@gmail.com', '$2y$10$udQwNH9RPQoGzGVVzTePUeQJACsS/HL3XWKhGfBNuMyjP0y0IA3KC', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(11, NULL, 'Sean Ariel', 'Agustin', 'seanbading@gmail.com', '$2y$10$FNcSxZsBI0fZ22RtPXtZD.NhIi.55rZ4DPxODDZ4ibTwwcObhYYpW', 'admin', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(13, NULL, 'Erwin', 'Lebrino', 'erwin@gmail.com', '$2y$10$eyJgbQ9LLjsuqam6KJjjq.6ez9q/LXM5okb8IYJZLIs1fgERr1H4G', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(15, NULL, 'Geraldine', 'Morato', 'jads@gmail.com', '$2y$10$NbVcsIgztmtpEko7qBumreG7Hexy9sckTjwsNsUlFVd70Y9pvMsEW', 'staff', NULL, 1, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(16, NULL, 'Michael', 'Ursal', 'maykel@gmail.com', '$2y$10$M.yEWY2zHp/cpaMYvUJhieZVzFWfB8XJ9.kvm3E5qlwO1tbBe8GwK', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(17, NULL, 'Jads', 'Lebrino', 'jads1@gmail.com', '$2y$10$T7RDTm9x53LhTZDNvV2hFOl1rRkA04yq/eYFwyltBg.J63N7kQGya', 'staff', NULL, 2, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(18, NULL, 'bet', 'log', 'bet@gmail.com', '$2y$10$u626aw4vSAQzD21grf.jS.4QWunI0q3VeXLCbiOFnwEuuueKnXiw.', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(19, NULL, 'nars', 'Salam', 'nars@gmail.com', '$2y$10$c05pdy6.tYh2nhornbibyOMYgMm6R438J3fVZp5B2.TtxAobc53Q2', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(20, '03220008724', 'sean', 'agustin', 'seanariel56@gmail.com', '$2y$10$2Qn27X3M7pzGAPj6b4PkdeLS3LKF0NIFQuKXyDXxLdvbj9Itb2Pk2', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0),
(21, NULL, 'michael', 'ursal', 'jvursal8140ant@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(22, NULL, 'erkupal', 'kunat', 'elebrino9971ant@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(24, NULL, 'Maria Boy Nigga', 'Morato', 'maria@gmail.com', '$2y$10$H518AC4cDx/15M.hshSXvOAFVzfP42cx0hp7KFn..8yksvD51rKBi', 'staff', NULL, 5, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(25, NULL, 'Kotoko', 'Chan', 'kotoko@gmail.com', '$2y$10$aUxPy/Fq2hNnSgo3n0oIYOBO8lU0pnuN6.3s4.NuM60w82QtHpPlK', 'staff', NULL, 6, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(36, NULL, 'teststudent11', 'student11', 'student11@gmail.com', '$2y$10$DzAxn7R1JSl9XBmxHLSX6OJjFvHUm6CcH4hNxalyfK50p0aFyNL16', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(37, NULL, 'student1', 'student1', 'student123@gmail.com', '$2y$10$2VVQ2JjIQ50SNu2KGzaA5.7p9xP9w.ea.p/RIZvHJozKriUCmzWmm', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(40, NULL, 'student23', 'student23', 'student23@gmail.com', '$2y$10$c.2txbbmGfB4InI0bmJza.gSS86IbdmswBDVHIWQHw3yqLvaEcEYu', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(41, NULL, 'vic', 'lop', 'lopez@gmail.com', '$2y$10$1xqWQzcwGI6K4RtEVU9Zz.KrKTJsZBCTawkde00B4uuZjlwF8eCPW', 'user', NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 1),
(107, '03225174372', 'Sofia', 'Delos Santos', 'sofia.delossantos@student.appsidedown.edu.ph', '$2y$10$aBHxbB50ld5I8NNRufyp7uTxSMpmmaPd7jtR0PgpTPbsSKZnASbZy', 'user', NULL, NULL, 'SHS-12-A', NULL, 'STEM', 0, 1, 0, 0, 0),
(311, '03220000101', 'Zara', 'Cruz', 'zara.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', NULL, 'STEM', 0, 1, 0, 0, 0),
(312, '03220000102', 'Mia', 'Lopez', 'mia.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', NULL, 'ABM', 0, 1, 0, 0, 0),
(313, '03220000103', 'Ella', 'Garcia', 'ella.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', NULL, 'HUMSS', 0, 1, 0, 0, 0),
(314, '03220000104', 'Sofia', 'Delos Santos', 'sofia.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', NULL, 'STEM', 0, 1, 0, 0, 0),
(315, '03220000105', 'Ava', 'Morales', 'ava.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'C', NULL, 'ABM', 0, 1, 0, 0, 0),
(316, '03220000106', 'Isla', 'Fernandez', 'isla.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'C', NULL, 'HUMSS', 0, 1, 0, 0, 0),
(317, '03220000107', 'Luna', 'Garcia', 'luna.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'D', NULL, 'STEM', 0, 1, 0, 0, 0),
(318, '03220000108', 'Nina', 'Reyes', 'nina.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'D', NULL, 'ABM', 0, 1, 0, 0, 0),
(319, '03220000109', 'Chloe', 'Cruz', 'chloe.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', NULL, 'HUMSS', 0, 1, 0, 0, 0),
(320, '03220000110', 'Leah', 'Torres', 'leah.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', NULL, 'STEM', 0, 1, 0, 0, 0),
(321, '03220450560', 'Zara', 'Santos', 'zara.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', NULL, 'STEM', 0, 1, 0, 0, 0),
(322, '03221053723', 'Mia', 'Fernandez', 'mia.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', NULL, 'ABM', 0, 1, 0, 0, 0),
(323, NULL, 'Ella', 'Torres', 'ella.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', NULL, 'HUMSS', 0, 1, 0, 0, 1),
(324, NULL, 'Sofia', 'Lopez', 'sofia.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', NULL, 'STEM', 0, 1, 0, 0, 1),
(325, NULL, 'Ava', 'Velasco', 'ava.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'C', NULL, 'ABM', 0, 1, 0, 0, 1),
(326, NULL, 'Isla', 'Ramos', 'isla.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'C', NULL, 'HUMSS', 0, 1, 0, 0, 1),
(327, NULL, 'Luna', 'Villanueva', 'luna.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'D', NULL, 'STEM', 0, 1, 0, 0, 1),
(328, NULL, 'Nina', 'Garcia', 'nina.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'D', NULL, 'ABM', 0, 1, 0, 0, 1),
(329, NULL, 'Chloe', 'Reyes', 'chloe.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', NULL, 'HUMSS', 0, 1, 0, 0, 1),
(330, NULL, 'Leah', 'Delos Santos', 'leah.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', NULL, 'STEM', 0, 1, 0, 0, 1),
(331, '03220008701', 'Liam', 'Reyes', 'liam.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', 'Computer Science', NULL, 1, 0, 0, 0, 0),
(332, '03220008702', 'Kai', 'Santos', 'kai.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', 'Information Technology', NULL, 1, 0, 0, 0, 0),
(333, '03220008703', 'Noah', 'Torres', 'noah.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', 'Engineering', NULL, 1, 0, 0, 0, 0),
(334, '03220008704', 'Ethan', 'Velasco', 'ethan.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', 'Business Administration', NULL, 1, 0, 0, 0, 0),
(335, '03220008705', 'Lucas', 'Ramos', 'lucas.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'C', 'Information Systems', NULL, 1, 0, 0, 0, 0),
(336, '03220008706', 'Leo', 'De Guzman', 'leo.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'C', 'Computer Engineering', NULL, 1, 0, 0, 0, 0),
(337, '03220008707', 'Jace', 'Mendoza', 'jace.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'D', 'Information Technology', NULL, 1, 0, 0, 0, 0),
(338, '03220008708', 'Asher', 'Villanueva', 'asher.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'D', 'Marketing', NULL, 1, 0, 0, 0, 0),
(339, '03220008709', 'Mateo', 'Santos', 'mateo.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', 'Computer Science', NULL, 1, 0, 0, 0, 0),
(340, '03220008710', 'Aidan', 'Torres', 'aidan.old@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', 'Business Administration', NULL, 1, 0, 0, 0, 0),
(341, NULL, 'Nathaniel', 'Delos Santos', 'nathaniel.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', 'Computer Science', NULL, 1, 0, 0, 0, 1),
(342, NULL, 'Samuel', 'Mendoza', 'samuel.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', 'Marketing', NULL, 1, 0, 0, 0, 1),
(343, NULL, 'David', 'Villanueva', 'david.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', 'Computer Engineering', NULL, 1, 0, 0, 0, 1),
(344, NULL, 'Caleb', 'Santos', 'caleb.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', 'Information Systems', NULL, 1, 0, 0, 0, 1),
(345, NULL, 'Isaac', 'Torres', 'isaac.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'C', 'Business Administration', NULL, 1, 0, 0, 0, 1),
(346, NULL, 'Ethan', 'Garcia', 'ethan.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'C', 'STEM', NULL, 1, 0, 0, 0, 1),
(347, NULL, 'Liam', 'Velasco', 'liam.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'D', 'ABM', NULL, 1, 0, 0, 0, 1),
(348, NULL, 'Mia', 'Fernandez', 'mia.new2@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'D', 'HUMSS', NULL, 1, 0, 0, 0, 1),
(349, NULL, 'Noah', 'Garcia', 'noah.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'A', 'Information Technology', NULL, 1, 0, 0, 0, 1),
(350, '03226555236', 'Sophia', 'Lopez', 'sophia.new@student.fatima.edu.ph', '$2y$10$i/kDr/2fxEbXvpYWvAAxe.S5yHqGRdMlbjIVOp92kArPMQpMcjLPC', 'user', NULL, NULL, 'B', 'Computer Science', NULL, 1, 0, 0, 0, 0);

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
-- Indexes for table `strands`
--
ALTER TABLE `strands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_database`
--
ALTER TABLE `student_database`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `student_num` (`student_num`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `password_reset_codes`
--
ALTER TABLE `password_reset_codes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `requests`
--
ALTER TABLE `requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `staff_departments`
--
ALTER TABLE `staff_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `strands`
--
ALTER TABLE `strands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_database`
--
ALTER TABLE `student_database`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=351;

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
