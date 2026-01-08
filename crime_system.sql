-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 08, 2026 at 06:41 AM
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
-- Database: `crime_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `crime_records`
--

CREATE TABLE `crime_records` (
  `id` int(11) NOT NULL,
  `nin` varchar(20) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `crime_type` varchar(100) NOT NULL,
  `crime_description` text DEFAULT NULL,
  `date_of_crime` date NOT NULL,
  `arrest_date` date DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `years_in_prison` int(11) DEFAULT 0,
  `months_in_prison` int(11) DEFAULT 0,
  `days_in_prison` int(11) DEFAULT 0,
  `status` enum('serving','released','pending') DEFAULT 'pending',
  `face_photo` varchar(255) DEFAULT NULL,
  `fingerprint` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `added_by` int(11) DEFAULT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `crime_records`
--

INSERT INTO `crime_records` (`id`, `nin`, `full_name`, `crime_type`, `crime_description`, `date_of_crime`, `arrest_date`, `release_date`, `years_in_prison`, `months_in_prison`, `days_in_prison`, `status`, `face_photo`, `fingerprint`, `address`, `phone`, `added_by`, `added_at`, `updated_at`) VALUES
(1, '123435363', 'sam john', 'Robbery', 'break and entring', '2026-01-10', '2025-02-11', '2027-05-05', 1, 1, 1, 'released', 'uploads/695a5a513ee4f.png', NULL, 'Ogboloma town Gbarain yenagoa bayelsa', '', 5, '2026-01-04 12:17:21', '2026-01-04 12:17:21'),
(2, '1122334455', 'mark kelvin', 'Theft', 'phone', '2024-02-06', '2024-02-09', '2026-01-05', 1, 1, 1, 'serving', 'uploads/695ccdcd2377b.png', NULL, 'Ogboloma town Gbarain yenagoa bayelsa', '09038729650', 4, '2026-01-06 08:54:37', '2026-01-06 08:54:37');

-- --------------------------------------------------------

--
-- Table structure for table `download_logs`
--

CREATE TABLE `download_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `record_id` int(11) NOT NULL,
  `download_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `role` enum('police','organization') NOT NULL,
  `police_id` varchar(50) DEFAULT NULL,
  `organization_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `role`, `police_id`, `organization_name`, `email`, `password`, `created_at`) VALUES
(1, 'organization', NULL, 'real estate', 'ezekieljeffrey35@gmail.com', '$2y$10$7.K/JFI8D4B8UJbpzK/A4eA8OouLix.srqC6wHYsu2eIWDMCWExAO', '2026-01-02 12:00:14'),
(4, 'police', 'POL001', NULL, 'jeffgrace363@gmail.com', '$2y$10$ZMe3hrsWrVvSzpXx9f4mW.E9gzExc4qzjjenH29oPWiDJjFujHomS', '2026-01-04 11:48:06'),
(5, 'police', 'POL002', NULL, 'ezekielanointed001@gmail.com', '$2y$10$U4X6b/jUzlaKfbzAOmQZb.rSkD7YOnQHeKr/vz61CyiUqZdzNdb0K', '2026-01-04 12:11:36');

-- --------------------------------------------------------

--
-- Table structure for table `verification_requests`
--

CREATE TABLE `verification_requests` (
  `id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `nin` varchar(20) NOT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `verification_requests`
--

INSERT INTO `verification_requests` (`id`, `organization_id`, `nin`, `request_date`, `status`, `approved_by`, `approval_date`, `notes`) VALUES
(1, 1, '123435363', '2026-01-04 12:33:57', 'approved', 4, '2026-01-04 12:34:23', ''),
(2, 1, '123435363', '2026-01-06 08:40:45', 'approved', 4, '2026-01-06 08:49:54', ''),
(3, 1, '1122334455', '2026-01-06 08:55:07', 'approved', 4, '2026-01-06 08:55:26', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `crime_records`
--
ALTER TABLE `crime_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nin` (`nin`),
  ADD KEY `added_by` (`added_by`);

--
-- Indexes for table `download_logs`
--
ALTER TABLE `download_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `record_id` (`record_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `unique_police_id` (`police_id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Indexes for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `organization_id` (`organization_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `crime_records`
--
ALTER TABLE `crime_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `download_logs`
--
ALTER TABLE `download_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `verification_requests`
--
ALTER TABLE `verification_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `crime_records`
--
ALTER TABLE `crime_records`
  ADD CONSTRAINT `crime_records_ibfk_1` FOREIGN KEY (`added_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `download_logs`
--
ALTER TABLE `download_logs`
  ADD CONSTRAINT `download_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `download_logs_ibfk_2` FOREIGN KEY (`record_id`) REFERENCES `crime_records` (`id`);

--
-- Constraints for table `verification_requests`
--
ALTER TABLE `verification_requests`
  ADD CONSTRAINT `verification_requests_ibfk_1` FOREIGN KEY (`organization_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `verification_requests_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
