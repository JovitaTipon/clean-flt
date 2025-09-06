-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 06:17 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `onlinecarbooking`
--

-- --------------------------------------------------------

--
-- Table structure for table `login_logs`
--

CREATE TABLE `login_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(250) NOT NULL,
  `user_type` varchar(50) NOT NULL,
  `login_time` text NOT NULL,
  `createdat` date NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_logs`
--

INSERT INTO `login_logs` (`id`, `user_id`, `user_type`, `login_time`, `createdat`) VALUES
(1, 0, 'admin', '2025-08-11 03:18:06', '2025-08-11'),
(2, 0, 'user', '2025-08-11 03:16:40', '2025-08-11');

-- --------------------------------------------------------

--
-- Table structure for table `tms_admin`
--

CREATE TABLE `tms_admin` (
  `a_id` int(11) NOT NULL,
  `a_name` varchar(200) NOT NULL,
  `a_email` varchar(200) NOT NULL,
  `a_pwd` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tms_admin`
--

INSERT INTO `tms_admin` (`a_id`, `a_name`, `a_email`, `a_pwd`) VALUES
(3, '', 'admin@gmail.com', '$2y$10$fAIUbxhK/sEWluSFpNbTUeMeQYjKoToz9anTnD4YK7dOP9u7acJWO');

-- --------------------------------------------------------

--
-- Table structure for table `tms_audit_log`
--

CREATE TABLE `tms_audit_log` (
  `id` int(11) NOT NULL,
  `actor_type` enum('admin','driver') NOT NULL,
  `actor_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `booking_u_id` int(11) NOT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tms_audit_log`
--

INSERT INTO `tms_audit_log` (`id`, `actor_type`, `actor_id`, `action`, `booking_u_id`, `details`, `created_at`) VALUES
(1, 'admin', 3, 'cancel', 15, '[]', '2025-09-01 02:24:32'),
(2, 'admin', 3, 'cancel', 16, '[]', '2025-09-01 02:24:44'),
(3, 'admin', 3, 'cancel', 17, '[]', '2025-09-01 02:25:00'),
(4, 'admin', 3, 'cancel', 18, '[]', '2025-09-01 02:25:02'),
(5, 'admin', 3, 'cancel', 19, '[]', '2025-09-01 02:25:04'),
(6, 'admin', 3, 'cancel', 13, '[]', '2025-09-01 02:25:09'),
(7, 'admin', 3, 'cancel', 21, '[]', '2025-09-01 02:26:48'),
(8, 'admin', 3, 'cancel', 13, '[]', '2025-09-01 02:40:13'),
(9, 'admin', 3, 'cancel', 13, '[]', '2025-09-01 04:25:22'),
(10, 'admin', 3, 'restore_cancelled', 13, '[]', '2025-09-01 04:55:03'),
(11, 'admin', 3, 'cancel', 13, '[]', '2025-09-01 04:55:13'),
(12, 'admin', 3, 'delete_cancelled', 13, '[]', '2025-09-01 04:55:18'),
(13, 'admin', 3, 'delete_cancelled', 16, '[]', '2025-09-01 05:25:50'),
(14, 'admin', 3, 'delete_cancelled', 18, '[]', '2025-09-01 05:25:53'),
(15, 'admin', 3, 'delete_cancelled', 19, '[]', '2025-09-01 05:25:56'),
(16, 'admin', 3, 'delete_cancelled', 14, '[]', '2025-09-01 05:26:01'),
(17, 'admin', 3, 'delete_cancelled', 15, '[]', '2025-09-03 01:05:38'),
(18, 'admin', 3, 'delete_cancelled', 17, '[]', '2025-09-03 01:05:41'),
(19, 'admin', 3, 'restore_cancelled', 21, '[]', '2025-09-03 01:05:44'),
(20, 'admin', 3, 'cancel', 22, '[]', '2025-09-04 12:01:33'),
(21, 'admin', 3, 'delete_cancelled', 22, '[]', '2025-09-04 12:02:07');

-- --------------------------------------------------------

--
-- Table structure for table `tms_bookings`
--

CREATE TABLE `tms_bookings` (
  `booking_id` int(10) UNSIGNED NOT NULL,
  `client_id` int(10) UNSIGNED NOT NULL,
  `driver_id` int(10) UNSIGNED DEFAULT NULL,
  `vehicle_id` int(10) UNSIGNED DEFAULT NULL,
  `pickup_point` varchar(255) NOT NULL,
  `dropoff_point` varchar(255) NOT NULL,
  `pickup_lat` decimal(10,7) DEFAULT NULL,
  `pickup_lng` decimal(10,7) DEFAULT NULL,
  `dropoff_lat` decimal(10,7) DEFAULT NULL,
  `dropoff_lng` decimal(10,7) DEFAULT NULL,
  `contact_phone` varchar(30) DEFAULT NULL,
  `seats_reserved` tinyint(3) UNSIGNED DEFAULT 1,
  `scheduled_at` datetime DEFAULT NULL,
  `booking_created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','accepted','declined','cancelled','completed') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','paid','partial') NOT NULL DEFAULT 'unpaid',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tms_bookings`
--

INSERT INTO `tms_bookings` (`booking_id`, `client_id`, `driver_id`, `vehicle_id`, `pickup_point`, `dropoff_point`, `pickup_lat`, `pickup_lng`, `dropoff_lat`, `dropoff_lng`, `contact_phone`, `seats_reserved`, `scheduled_at`, `booking_created_at`, `status`, `payment_status`, `notes`) VALUES
(1, 2, 5, 3, '100 Main St, Town', 'Airport Terminal 1', NULL, NULL, NULL, NULL, '+639171234567', 3, '2025-08-12 14:00:00', '2025-08-11 20:18:51', 'pending', 'unpaid', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `tms_driver_report`
--

CREATE TABLE `tms_driver_report` (
  `report_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `trip_date` date NOT NULL,
  `shift_start` datetime DEFAULT NULL,
  `shift_end` datetime DEFAULT NULL,
  `odometer_start` int(11) DEFAULT NULL,
  `odometer_end` int(11) DEFAULT NULL,
  `total_km` decimal(8,1) DEFAULT NULL,
  `fuel_used_liters` decimal(8,2) DEFAULT NULL,
  `route_from` varchar(120) DEFAULT NULL,
  `route_to` varchar(120) DEFAULT NULL,
  `pickups` int(11) DEFAULT NULL,
  `dropoffs` int(11) DEFAULT NULL,
  `passengers_moved` int(11) DEFAULT NULL,
  `incident_level` enum('OK','Minor','Major') NOT NULL DEFAULT 'OK',
  `status` enum('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tms_feedback`
--

CREATE TABLE `tms_feedback` (
  `f_id` int(11) NOT NULL,
  `f_uname` varchar(200) NOT NULL,
  `f_content` longtext NOT NULL,
  `f_status` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tms_feedback`
--

INSERT INTO `tms_feedback` (`f_id`, `f_uname`, `f_content`, `f_status`) VALUES
(1, 'Elliot Gape', 'This is a demo feedback text. This is a demo feedback text. This is a demo feedback text.', 'Published'),
(2, 'Mark L. Anderson', 'Sample Feedback Text for testing! Sample Feedback Text for testing! Sample Feedback Text for testing!', 'Published'),
(3, 'Liam Moore ', 'test number 3', '');

-- --------------------------------------------------------

--
-- Table structure for table `tms_pwd_resets`
--

CREATE TABLE `tms_pwd_resets` (
  `r_id` int(11) NOT NULL,
  `r_email` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tms_pwd_resets`
--

INSERT INTO `tms_pwd_resets` (`r_id`, `r_email`) VALUES
(2, 'admin@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `tms_report_media`
--

CREATE TABLE `tms_report_media` (
  `media_id` int(11) NOT NULL,
  `report_id` int(11) NOT NULL,
  `path` varchar(255) NOT NULL,
  `caption` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tms_syslogs`
--

CREATE TABLE `tms_syslogs` (
  `l_id` int(11) NOT NULL,
  `u_id` varchar(200) NOT NULL,
  `u_email` varchar(200) NOT NULL,
  `u_ip` varbinary(200) NOT NULL,
  `u_city` varchar(200) NOT NULL,
  `u_country` varchar(200) NOT NULL,
  `pickup_point` text NOT NULL,
  `dropoff_point` text NOT NULL,
  `driver_id` text NOT NULL,
  `booking_date` text NOT NULL,
  `u_logintime` timestamp(6) NOT NULL DEFAULT current_timestamp(6) ON UPDATE current_timestamp(6)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tms_user`
--

CREATE TABLE `tms_user` (
  `u_id` int(11) NOT NULL,
  `u_fname` varchar(200) NOT NULL,
  `u_lname` varchar(200) NOT NULL,
  `u_car_pax` text NOT NULL,
  `u_phone` varchar(32) DEFAULT NULL,
  `u_addr` varchar(200) NOT NULL,
  `u_category` varchar(200) NOT NULL,
  `u_email` varchar(200) NOT NULL,
  `u_pwd` varchar(50) NOT NULL,
  `u_car_type` varchar(200) NOT NULL,
  `u_car_driver` text NOT NULL,
  `u_car_regno` varchar(200) NOT NULL,
  `u_car_bookdate` varchar(200) NOT NULL,
  `u_car_pickup` varchar(250) NOT NULL,
  `u_car_destination` varchar(250) NOT NULL,
  `u_car_book_status` varchar(200) NOT NULL,
  `u_car_date` text NOT NULL,
  `u_car_time` text NOT NULL,
  `createdat` int(255) NOT NULL DEFAULT current_timestamp(),
  `u_car_createdat` int(255) NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tms_user`
--

INSERT INTO `tms_user` (`u_id`, `u_fname`, `u_lname`, `u_car_pax`, `u_phone`, `u_addr`, `u_category`, `u_email`, `u_pwd`, `u_car_type`, `u_car_driver`, `u_car_regno`, `u_car_bookdate`, `u_car_pickup`, `u_car_destination`, `u_car_book_status`, `u_car_date`, `u_car_time`, `createdat`, `u_car_createdat`) VALUES
(20, 'Felicity Piastri', '', '3', NULL, '', 'User', '', '$2y$10$hrxrah2GosDTS59qB81R3.0ya6b/UWPJo7CFmw5bX7I', 'SUV', 'Kimi', '123', '', 'AUF', 'Clark', 'Pending', '2025-10-21', '17:00', 2147483647, 2147483647),
(21, 'Oscar Norris', '', '3', NULL, '', 'User', '', '$2y$10$RPmClYkM86d5B4DuJmhv7eidH/1QCUs0lzpdMw7BC4m', 'SUV', 'Keihle Dianne', '123', '', 'AUF', 'Clark', 'Pending', '2025-09-10', '05:26', 2147483647, 2147483647),
(23, 'Nimi', '', '1', NULL, '', 'User', '', '$2y$10$rpuskFwW0YuBfRrwRWPSUOh7T8po8n3ElL/2pXn0wMN', 'SUV', 'Joshua Visbal', '123', '', 'AUF', 'SM Pampanga', 'Pending', '2025-09-04', '17:00', 2147483647, 2147483647);

-- --------------------------------------------------------

--
-- Table structure for table `tms_user_add_driver`
--

CREATE TABLE `tms_user_add_driver` (
  `d_u_id` int(11) NOT NULL,
  `u_id` int(50) NOT NULL,
  `u_fname` varchar(50) NOT NULL,
  `u_lname` varchar(50) NOT NULL,
  `u_phone` varchar(32) DEFAULT NULL,
  `u_addr` text NOT NULL,
  `u_car_type` text NOT NULL,
  `u_car_regno` text NOT NULL,
  `u_car_bookdate` text NOT NULL,
  `u_car_book_status` text NOT NULL,
  `u_category` text NOT NULL,
  `u_email` text NOT NULL,
  `u_pwd` text NOT NULL,
  `createdat` date NOT NULL DEFAULT current_timestamp(),
  `is_archived` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tms_user_add_driver`
--

INSERT INTO `tms_user_add_driver` (`d_u_id`, `u_id`, `u_fname`, `u_lname`, `u_phone`, `u_addr`, `u_car_type`, `u_car_regno`, `u_car_bookdate`, `u_car_book_status`, `u_category`, `u_email`, `u_pwd`, `createdat`, `is_archived`) VALUES
(8, 0, 'Shane', 'Lopez', '09446872447', 'taga san fernando, pampanga', 'Bus', '123', '', 'Available', 'Driver', 'shaaane@mail.com', '', '2025-09-04', 0);

-- --------------------------------------------------------

--
-- Table structure for table `tms_vehicle`
--

CREATE TABLE `tms_vehicle` (
  `v_id` int(11) NOT NULL,
  `v_name` varchar(200) NOT NULL,
  `v_reg_no` varchar(200) NOT NULL,
  `v_pass_no` varchar(200) NOT NULL,
  `v_driver` varchar(200) NOT NULL,
  `v_category` varchar(200) NOT NULL,
  `v_dpic` varchar(200) NOT NULL,
  `v_status` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tms_vehicle`
--

INSERT INTO `tms_vehicle` (`v_id`, `v_name`, `v_reg_no`, `v_pass_no`, `v_driver`, `v_category`, `v_dpic`, `v_status`) VALUES
(3, 'Euro Bond', 'CA7766', '50', 'Vincent Pelletier', 'Bus', 'images.jpg', 'Booked'),
(4, 'Honda Accord', 'CA2077', '5', 'Joseph Yung', 'Bus', '', 'Booked'),
(5, 'Volkswagen Passat', 'CA1690', '5', 'Jesse Robinson', 'Sedan', 'volkswagen-passat-500.jpg', 'Available'),
(6, 'Nissan Rogue', 'CA1001', '7', 'Demo User', 'SUV', 'Nissan_Rogue_SV_2021.jpg', 'Available'),
(7, 'Subaru Legacy', 'CA7700', '5', 'John Settles', 'Bus', '', 'Available');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `login_logs`
--
ALTER TABLE `login_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tms_admin`
--
ALTER TABLE `tms_admin`
  ADD PRIMARY KEY (`a_id`);

--
-- Indexes for table `tms_audit_log`
--
ALTER TABLE `tms_audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tms_bookings`
--
ALTER TABLE `tms_bookings`
  ADD PRIMARY KEY (`booking_id`),
  ADD KEY `idx_driver_status_time` (`driver_id`,`status`,`scheduled_at`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `tms_driver_report`
--
ALTER TABLE `tms_driver_report`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `idx_trip_date` (`trip_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_driver` (`driver_id`),
  ADD KEY `fk_report_vehicle` (`vehicle_id`);

--
-- Indexes for table `tms_feedback`
--
ALTER TABLE `tms_feedback`
  ADD PRIMARY KEY (`f_id`);

--
-- Indexes for table `tms_pwd_resets`
--
ALTER TABLE `tms_pwd_resets`
  ADD PRIMARY KEY (`r_id`);

--
-- Indexes for table `tms_report_media`
--
ALTER TABLE `tms_report_media`
  ADD PRIMARY KEY (`media_id`),
  ADD KEY `fk_media_report` (`report_id`);

--
-- Indexes for table `tms_syslogs`
--
ALTER TABLE `tms_syslogs`
  ADD PRIMARY KEY (`l_id`);

--
-- Indexes for table `tms_user`
--
ALTER TABLE `tms_user`
  ADD PRIMARY KEY (`u_id`);

--
-- Indexes for table `tms_user_add_driver`
--
ALTER TABLE `tms_user_add_driver`
  ADD PRIMARY KEY (`d_u_id`);

--
-- Indexes for table `tms_vehicle`
--
ALTER TABLE `tms_vehicle`
  ADD PRIMARY KEY (`v_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `login_logs`
--
ALTER TABLE `login_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tms_admin`
--
ALTER TABLE `tms_admin`
  MODIFY `a_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tms_audit_log`
--
ALTER TABLE `tms_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tms_bookings`
--
ALTER TABLE `tms_bookings`
  MODIFY `booking_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tms_driver_report`
--
ALTER TABLE `tms_driver_report`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tms_feedback`
--
ALTER TABLE `tms_feedback`
  MODIFY `f_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tms_pwd_resets`
--
ALTER TABLE `tms_pwd_resets`
  MODIFY `r_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tms_report_media`
--
ALTER TABLE `tms_report_media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tms_syslogs`
--
ALTER TABLE `tms_syslogs`
  MODIFY `l_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tms_user`
--
ALTER TABLE `tms_user`
  MODIFY `u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `tms_user_add_driver`
--
ALTER TABLE `tms_user_add_driver`
  MODIFY `d_u_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tms_vehicle`
--
ALTER TABLE `tms_vehicle`
  MODIFY `v_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tms_driver_report`
--
ALTER TABLE `tms_driver_report`
  ADD CONSTRAINT `fk_report_driver` FOREIGN KEY (`driver_id`) REFERENCES `tms_user_add_driver` (`d_u_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_report_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `tms_vehicle` (`v_id`) ON DELETE SET NULL;

--
-- Constraints for table `tms_report_media`
--
ALTER TABLE `tms_report_media`
  ADD CONSTRAINT `fk_media_report` FOREIGN KEY (`report_id`) REFERENCES `tms_driver_report` (`report_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
