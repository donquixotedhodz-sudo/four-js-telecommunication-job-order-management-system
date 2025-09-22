-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 22, 2025 at 04:53 AM
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
-- Database: `fourjs_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `ac_parts`
--

CREATE TABLE `ac_parts` (
  `id` int(11) NOT NULL,
  `part_name` varchar(255) NOT NULL,
  `part_code` varchar(100) DEFAULT NULL,
  `part_category` enum('compressor','condenser','evaporator','filter','capacitor','thermostat','fan_motor','refrigerant','electrical','other') NOT NULL,
  `compatible_brands` text DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `warranty_months` int(11) DEFAULT 12,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `category_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ac_parts`
--

INSERT INTO `ac_parts` (`id`, `part_name`, `part_code`, `part_category`, `compatible_brands`, `unit_price`, `labor_cost`, `warranty_months`, `created_at`, `updated_at`, `category_id`) VALUES
(2, 'Coolant', 'COOL-001', 'refrigerant', '[\"All Brands\"]', 5500.00, 500.00, 12, '2025-08-13 12:04:24', '2025-09-03 13:37:12', 8),
(3, 'Evaporator Coil', 'EVAP-001', 'evaporator', '[\"All Brands\"]', 1200.00, 500.00, 12, '2025-08-13 12:04:24', '2025-09-03 13:37:12', 3),
(5, 'Capacitor 35uF', 'CAP-35UF', 'compressor', '[\"All Brands\"]', 250.00, 100.00, 6, '2025-08-13 12:04:24', '2025-09-03 13:37:12', 1),
(6, 'Digital Thermostat', 'THERM-DIG-001', 'thermostat', '[\"All Brands\"]', 800.00, 300.00, 12, '2025-08-13 12:04:24', '2025-09-03 13:37:12', 6),
(7, 'Fan Motor 1/4HP', 'FAN-025HP', 'fan_motor', '[\"All Brands\"]', 1800.00, 400.00, 18, '2025-08-13 12:04:24', '2025-09-03 13:37:12', 7),
(9, 'Control Board', 'PCB-001', 'electrical', '[\"Carrier\", \"Daikin\", \"Panasonic\"]', 2500.00, 600.00, 12, '2025-08-13 12:04:24', '2025-09-03 13:37:12', 9),
(10, 'Drain Pump', 'PUMP-001', 'other', '[\"All Brands\"]', 600.00, 300.00, 12, '2025-08-13 12:04:24', '2025-09-03 13:37:12', 10);

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `name`, `email`, `phone`, `profile_picture`, `password`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Administrator - Staff', 'student.joshmcdowelltrapal@gmail.com', '09958714112', 'uploads/profile_pictures/profile_683fca5baffdd.jpg', '$2y$10$XjUWLHUsYb5Woq9Erh/xgOK3Aa756Lu2eSqKDjEfOhNxGX5jx4zzy', '2025-06-02 12:30:32', '2025-08-15 11:39:47'),
(6, 'fourjs-admin', '4Js Telecommunications', '4jstelcom@gmail.com', '09958714112', 'uploads/profile_pictures/admin_6_68b6cbcbb867f.jpg', '$2y$10$iwdteayO/dOg4tYQ0pWI6OZPgRMTPyh93fxny457oa5omJXNdjq9u', '2025-08-10 04:30:56', '2025-09-02 10:49:47'),
(7, 'josh-admin', 'Josh McDowell Trapal', 'joshmcdowellramireztrapal@gmail.com', '09958714112', 'uploads/profile_pictures/admin_689df1d24e917.jpg', '$2y$10$TvS3VYq13.TqZTEVuh6QsuMTULyfinWg2UAvTkWKhQpelllcxRieG', '2025-08-14 14:25:22', '2025-08-15 11:39:10'),
(8, 'test-admin', 'Four J\'s Aircon Services', '4jsadministrator@gmail.com', '09958714112', 'uploads/profile_pictures/admin_68b9688ce983e.jpg', '$2y$10$VbQTz0TtuYAwyhJ1nIE9eOMJY2204s9M4uzx3/Zv6mhc4lUNs1Yea', '2025-09-04 10:23:09', '2025-09-04 10:23:09');

-- --------------------------------------------------------

--
-- Table structure for table `aircon_hp`
--

CREATE TABLE `aircon_hp` (
  `id` int(11) NOT NULL,
  `hp` decimal(4,1) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aircon_hp`
--

INSERT INTO `aircon_hp` (`id`, `hp`, `price`, `created_at`, `updated_at`) VALUES
(1, 2.0, 1000.00, '2025-09-17 13:32:17', '2025-09-19 05:39:56'),
(2, 1.0, 500.00, '2025-09-17 13:58:59', '2025-09-19 07:07:52'),
(3, 1.5, 600.00, '2025-09-19 07:08:02', '2025-09-19 07:08:02');

-- --------------------------------------------------------

--
-- Table structure for table `aircon_installations`
--

CREATE TABLE `aircon_installations` (
  `id` int(11) NOT NULL,
  `job_order_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `has_filter` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `aircon_models`
--

CREATE TABLE `aircon_models` (
  `id` int(11) NOT NULL,
  `brand` varchar(50) NOT NULL,
  `model_name` varchar(100) NOT NULL,
  `hp` decimal(3,1) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `aircon_models`
--

INSERT INTO `aircon_models` (`id`, `brand`, `model_name`, `hp`, `price`, `created_at`) VALUES
(14, 'AUX', 'J Series - Wall Mounted - Inverter', 1.0, 34000.00, '2025-08-14 05:22:35'),
(15, 'AUX', 'J Series - Wall Mounted - Inverter', 2.0, 51799.00, '2025-08-14 05:22:59'),
(16, 'AUX', 'J Series - Wall Mounted - Inverter', 1.5, 38999.00, '2025-08-14 05:23:46'),
(17, 'AUX', 'F Series - Wall Mounted - Inverter', 1.0, 35499.00, '2025-08-14 05:24:35'),
(18, 'AUX', 'F Series - Wall Mounted - Inverter', 1.5, 39999.00, '2025-08-14 05:24:50'),
(19, 'AUX', 'F Series - Wall Mounted - Inverter', 2.0, 49999.00, '2025-08-14 05:25:07'),
(20, 'AUX', 'F Series - Wall Mounted - Inverter', 2.5, 59999.00, '2025-08-14 05:25:24'),
(21, 'AUX', 'J Series - Wall Mounted - Inverter', 3.0, 74999.00, '2025-08-14 05:26:07'),
(22, 'TCL', 'TAC-10CSD/KEI2 - Wall Mounted - Inverter', 1.0, 25998.00, '2025-08-14 05:29:10'),
(23, 'TCL', 'TAC-13CSD/KEI2 - Wall Mounted - Inverter', 1.5, 27998.00, '2025-08-14 05:29:41'),
(24, 'TCL', 'TAC-25CSD/KEI2 - Wall Mounted - Inverter', 2.5, 40998.00, '2025-08-14 05:30:21'),
(25, 'TCL', 'TAC-07CWI/UB2 - Window Type - Inverter', 0.7, 16998.00, '2025-08-14 05:32:09'),
(26, 'TCL', 'TAC-09CWI/UB2 - Window Type - Inverter', 1.0, 17998.00, '2025-08-14 05:32:46'),
(27, 'TCL', 'TAC-12CWI/UB2 - Window Type - Inverter', 1.5, 19998.00, '2025-08-14 05:33:20'),
(28, 'CHIQ', 'Morandi CSD-10DA - Wall Mounted - Inverter', 1.0, 18999.00, '2025-08-14 05:44:01'),
(29, 'CHIQ', 'Morandi CSD-15DA - Wall Mounted - Inverter', 1.5, 21500.00, '2025-08-14 05:44:42'),
(30, 'CHIQ', 'Morandi CSD-20DA - Wall Mounted - Inverter', 2.0, 27500.00, '2025-08-14 05:45:12'),
(31, 'CHIQ', 'Morandi CSD-25DA - Wall Mounted - Inverter', 2.5, 33000.00, '2025-08-14 05:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `cleaning_services`
--

CREATE TABLE `cleaning_services` (
  `id` int(11) NOT NULL,
  `service_name` varchar(255) NOT NULL,
  `service_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `service_type` enum('basic_cleaning','deep_cleaning','chemical_wash','coil_cleaning','filter_cleaning') NOT NULL DEFAULT 'basic_cleaning',
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_type` enum('per_unit','per_hour','per_service') DEFAULT 'per_unit',
  `aircon_type` enum('window','split','cassette','floor_standing','all') DEFAULT 'all'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cleaning_services`
--

INSERT INTO `cleaning_services` (`id`, `service_name`, `service_description`, `created_at`, `updated_at`, `service_type`, `base_price`, `unit_type`, `aircon_type`) VALUES
(1, 'Basic Cleaning', 'Standard cleaning of filters and external parts', '2025-08-14 01:47:15', '2025-09-17 13:55:40', 'basic_cleaning', 500.00, 'per_unit', 'all');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `address`, `email`, `created_at`) VALUES
(46, 'Josh McDowell Trapal', '09958714112', 'Ogbot, Bongabong, Oriental Mindoro', 'student.joshmcdowelltrapal@gmail.com', '2025-09-21 10:00:46'),
(47, 'Krisxan Castillon', '09958714112', 'Ogbot', 'joshmcdowelltrapal@gmail.com', '2025-09-21 10:28:49');

-- --------------------------------------------------------

--
-- Table structure for table `job_orders`
--

CREATE TABLE `job_orders` (
  `id` int(11) NOT NULL,
  `job_order_number` varchar(20) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_address` text NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `service_type` enum('installation','repair','survey','cleaning') NOT NULL,
  `aircon_model_id` int(11) DEFAULT NULL,
  `assigned_technician_id` int(11) DEFAULT NULL,
  `secondary_technician_id` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `price` decimal(10,2) DEFAULT NULL,
  `base_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `customer_id` int(11) DEFAULT NULL,
  `additional_fee` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `part_id` int(11) DEFAULT NULL,
  `cleaning_service_id` int(11) DEFAULT NULL,
  `aircon_hp_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_orders`
--

INSERT INTO `job_orders` (`id`, `job_order_number`, `customer_name`, `customer_address`, `customer_phone`, `customer_email`, `service_type`, `aircon_model_id`, `assigned_technician_id`, `secondary_technician_id`, `status`, `price`, `base_price`, `created_by`, `due_date`, `completed_at`, `created_at`, `updated_at`, `customer_id`, `additional_fee`, `discount`, `part_id`, `cleaning_service_id`, `aircon_hp_id`) VALUES
(180, 'JO-20250921-0065', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'joshmcdowelltrapal@gmail.com', 'survey', NULL, 9, 3, 'pending', 500.00, 500.00, 6, NULL, NULL, '2025-09-21 10:00:46', '2025-09-21 10:00:46', 46, 0.00, 0.00, NULL, NULL, NULL),
(181, 'JO-20250921-9057', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 30, 3, 9, 'pending', 27500.00, 27500.00, NULL, NULL, NULL, '2025-09-21 10:01:56', '2025-09-21 10:01:56', 46, 0.00, 0.00, NULL, NULL, NULL),
(182, 'JO-20250921-9871', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 28, 3, 9, 'pending', 18999.00, 18999.00, NULL, NULL, NULL, '2025-09-21 10:01:56', '2025-09-21 10:01:56', 46, 0.00, 0.00, NULL, NULL, NULL),
(183, 'JO-20250921-6620', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 30, 3, 9, 'pending', 27500.00, 27500.00, NULL, NULL, NULL, '2025-09-21 10:02:04', '2025-09-21 10:02:04', 46, 0.00, 0.00, NULL, NULL, NULL),
(184, 'JO-20250921-3727', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 28, 3, 9, 'in_progress', 18999.00, 18999.00, NULL, NULL, NULL, '2025-09-21 10:02:04', '2025-09-21 10:06:24', 46, 0.00, 0.00, NULL, NULL, NULL),
(185, 'JO-20250921-0073', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 3, 9, 'pending', 21500.00, 21500.00, NULL, NULL, NULL, '2025-09-21 10:07:19', '2025-09-21 10:07:19', 46, 0.00, 0.00, NULL, NULL, NULL),
(186, 'JO-20250921-1306', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'repair', 28, 12, NULL, 'pending', 2500.00, 2500.00, NULL, NULL, NULL, '2025-09-21 10:07:44', '2025-09-21 10:07:44', 46, 0.00, 0.00, 9, NULL, NULL),
(187, 'JO-20250921-4608', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 27, 12, 3, 'pending', 19998.00, 19998.00, NULL, NULL, NULL, '2025-09-21 10:13:00', '2025-09-21 10:13:00', 46, 0.00, 0.00, NULL, NULL, NULL),
(188, 'JO-20250921-9721', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 26, 12, 3, 'pending', 17998.00, 17998.00, NULL, NULL, NULL, '2025-09-21 10:13:00', '2025-09-21 10:13:00', 46, 0.00, 0.00, NULL, NULL, NULL),
(189, 'JO-20250921-4359', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 28, 9, 12, 'pending', 18999.00, 18999.00, NULL, NULL, NULL, '2025-09-21 10:13:36', '2025-09-21 10:13:36', 46, 0.00, 0.00, NULL, NULL, NULL),
(190, 'JO-20250921-6836', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 30, 9, NULL, 'pending', 27500.00, 27500.00, NULL, NULL, NULL, '2025-09-21 10:14:23', '2025-09-21 10:14:23', 46, 0.00, 0.00, NULL, NULL, NULL),
(191, 'JO-20250921-3721', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 'survey', NULL, 9, NULL, 'pending', 500.00, 500.00, 6, NULL, NULL, '2025-09-21 10:15:28', '2025-09-21 10:15:28', 46, 0.00, 0.00, NULL, NULL, NULL),
(192, 'JO-20250921-7942', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 28, 9, NULL, 'pending', 18999.00, 18999.00, NULL, NULL, NULL, '2025-09-21 10:15:40', '2025-09-21 10:15:40', 46, 0.00, 0.00, NULL, NULL, NULL),
(193, 'JO-20250921-0304', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 26, 3, 9, 'pending', 17998.00, 17998.00, NULL, NULL, NULL, '2025-09-21 10:19:54', '2025-09-21 10:19:54', 46, 0.00, 0.00, NULL, NULL, NULL),
(194, 'JO-20250921-1568', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 30, 9, NULL, 'pending', 27500.00, 27500.00, NULL, NULL, NULL, '2025-09-21 10:23:47', '2025-09-21 10:23:47', 46, 0.00, 0.00, NULL, NULL, NULL),
(195, 'JO-20250921-8629', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 3, NULL, 'pending', 21500.00, 21500.00, NULL, NULL, NULL, '2025-09-21 10:25:32', '2025-09-21 10:25:32', 46, 0.00, 0.00, NULL, NULL, NULL),
(196, 'JO-20250921-0735', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 27, 9, 12, 'pending', 19998.00, 19998.00, NULL, NULL, NULL, '2025-09-21 10:26:25', '2025-09-21 10:26:25', 46, 0.00, 0.00, NULL, NULL, NULL),
(197, 'JO-20250921-0014', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 26, 9, 12, 'pending', 17998.00, 17998.00, NULL, NULL, NULL, '2025-09-21 10:26:25', '2025-09-21 10:26:25', 46, 0.00, 0.00, NULL, NULL, NULL),
(198, 'JO-20250921-8675', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 'cleaning', NULL, 9, NULL, 'pending', 1000.00, 1000.00, 6, NULL, NULL, '2025-09-21 10:27:04', '2025-09-21 10:27:04', 46, 0.00, 0.00, NULL, 1, NULL),
(199, 'JO-20250921-1795', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 27, 9, NULL, 'pending', 19998.00, 19998.00, NULL, NULL, NULL, '2025-09-21 10:27:46', '2025-09-21 10:27:46', 46, 0.00, 0.00, NULL, NULL, NULL),
(200, 'JO-20250921-5442', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 24, 9, NULL, 'pending', 40998.00, 40998.00, NULL, NULL, NULL, '2025-09-21 10:27:46', '2025-09-21 10:27:46', 46, 0.00, 0.00, NULL, NULL, NULL),
(201, 'JO-20250921-4803', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 9, NULL, 'pending', 21500.00, 21500.00, NULL, NULL, NULL, '2025-09-21 10:27:46', '2025-09-21 10:27:46', 46, 0.00, 0.00, NULL, NULL, NULL),
(202, 'JO-20250921-5169', 'Krisxan Castillon', 'Ogbot', '09958714112', 'joshmcdowelltrapal@gmail.com', 'cleaning', 15, 3, NULL, 'pending', 500.00, 500.00, 6, NULL, NULL, '2025-09-21 10:28:49', '2025-09-21 10:28:49', 47, 0.00, 0.00, NULL, 1, NULL),
(203, 'JO-20250921-1193', 'Krisxan Castillon', 'Ogbot', '09958714112', 'joshmcdowelltrapal@gmail.com', 'cleaning', 16, 3, NULL, 'pending', 600.00, 600.00, 6, NULL, NULL, '2025-09-21 10:28:49', '2025-09-21 10:28:49', 47, 0.00, 0.00, NULL, 1, NULL),
(204, 'JO-20250921-9600', 'Krisxan Castillon', 'Ogbot', '09958714112', 'joshmcdowelltrapal@gmail.com', 'cleaning', 28, 3, NULL, 'pending', 600.00, 600.00, 6, NULL, NULL, '2025-09-21 10:28:49', '2025-09-21 10:28:49', 47, 0.00, 0.00, NULL, 1, NULL),
(205, 'JO-20250921-2725', 'Krisxan Castillon', 'Ogbot', '09958714112', NULL, 'repair', 17, 3, NULL, 'pending', 5500.00, 5500.00, NULL, NULL, NULL, '2025-09-21 10:29:27', '2025-09-21 10:29:27', 47, 0.00, 0.00, 2, NULL, NULL),
(206, 'JO-20250921-4466', 'Krisxan Castillon', 'Ogbot', '09958714112', NULL, 'repair', 28, 3, NULL, 'pending', 2500.00, 2500.00, NULL, NULL, NULL, '2025-09-21 10:29:27', '2025-09-21 10:29:27', 47, 0.00, 0.00, 9, NULL, NULL),
(207, 'JO-20250921-8259', 'Krisxan Castillon', 'Ogbot', '09958714112', NULL, 'repair', 28, 3, NULL, 'pending', 2500.00, 2500.00, NULL, NULL, NULL, '2025-09-21 10:29:27', '2025-09-21 10:29:27', 47, 0.00, 0.00, 9, NULL, NULL),
(208, 'JO-20250921-0653', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 3, 9, 'pending', 21500.00, 21500.00, NULL, NULL, NULL, '2025-09-21 11:17:16', '2025-09-21 11:17:16', 46, 0.00, 0.00, NULL, NULL, NULL),
(209, 'JO-20250921-1068', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 28, 3, 9, 'pending', 18999.00, 18999.00, NULL, NULL, NULL, '2025-09-21 11:17:16', '2025-09-21 11:17:16', 46, 0.00, 0.00, NULL, NULL, NULL),
(210, 'JO-20250921-9614', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 27, 3, 9, 'pending', 19998.00, 19998.00, NULL, NULL, NULL, '2025-09-21 11:17:16', '2025-09-21 11:17:16', 46, 0.00, 0.00, NULL, NULL, NULL),
(211, 'JO-20250921-6901', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 'cleaning', 29, 9, 3, 'pending', 600.00, 600.00, 6, NULL, NULL, '2025-09-21 11:20:48', '2025-09-21 11:20:48', 46, 0.00, 0.00, NULL, 1, NULL),
(212, 'JO-20250921-8847', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 'cleaning', 26, 9, 3, 'pending', 600.00, 600.00, 6, NULL, NULL, '2025-09-21 11:20:48', '2025-09-21 11:20:48', 46, 0.00, 0.00, NULL, 1, NULL),
(213, 'JO-20250921-5216', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'student.joshmcdowelltrapal@gmail.com', 'cleaning', 18, 9, 3, 'pending', 500.00, 500.00, 6, NULL, NULL, '2025-09-21 11:20:48', '2025-09-21 11:20:48', 46, 0.00, 0.00, NULL, 1, NULL),
(214, 'JO-20250921-4816', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 9, NULL, 'pending', 5807286.00, 21500.00, NULL, NULL, NULL, '2025-09-21 11:26:51', '2025-09-22 02:49:10', 46, 5785786.00, 0.00, NULL, NULL, NULL),
(215, 'JO-20250921-5121', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 28, 9, NULL, 'cancelled', 18999.00, 18999.00, NULL, NULL, NULL, '2025-09-21 11:26:51', '2025-09-22 02:46:38', 46, 0.00, 0.00, NULL, NULL, NULL),
(216, 'JO-20250921-5081', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 27, 9, NULL, 'cancelled', 19998.00, 19998.00, NULL, NULL, NULL, '2025-09-21 11:26:51', '2025-09-22 02:38:24', 46, 0.00, 0.00, NULL, NULL, NULL),
(217, 'JO-20250921-3625', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 3, 9, 'cancelled', 21500.00, 21500.00, NULL, NULL, NULL, '2025-09-21 11:31:34', '2025-09-22 02:34:35', 46, 0.00, 0.00, NULL, NULL, NULL),
(218, 'JO-20250921-1726', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 27, 3, 9, 'cancelled', 19998.00, 19998.00, NULL, NULL, NULL, '2025-09-21 11:31:34', '2025-09-22 02:33:53', 46, 0.00, 0.00, NULL, NULL, NULL),
(219, 'JO-20250921-1712', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 27, 3, 9, 'cancelled', 19998.00, 19998.00, NULL, NULL, NULL, '2025-09-21 11:31:34', '2025-09-22 02:34:09', 46, 0.00, 0.00, NULL, NULL, NULL),
(220, 'JO-20250921-4263', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 3, 12, 'cancelled', 21500.00, 21500.00, NULL, NULL, NULL, '2025-09-21 11:33:57', '2025-09-22 02:30:30', 46, 0.00, 0.00, NULL, NULL, NULL),
(221, 'JO-20250921-2175', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 27, 3, 12, 'completed', 19998.00, 19998.00, NULL, NULL, '2025-09-21 16:22:24', '2025-09-21 11:33:57', '2025-09-21 16:22:24', 46, 0.00, 0.00, NULL, NULL, NULL),
(222, 'JO-20250921-8431', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 9, NULL, 'completed', 21500.00, 21500.00, NULL, NULL, '2025-09-21 16:21:58', '2025-09-21 13:26:36', '2025-09-21 16:21:58', 46, 0.00, 0.00, NULL, NULL, NULL),
(223, 'JO-20250921-0497', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', NULL, 'installation', 29, 9, NULL, 'completed', 21500.00, 21500.00, NULL, NULL, '2025-09-21 16:22:18', '2025-09-21 13:26:36', '2025-09-21 16:22:18', 46, 0.00, 0.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `part_categories`
--

CREATE TABLE `part_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `part_categories`
--

INSERT INTO `part_categories` (`id`, `category_name`, `category_description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'compressor', 'Compressor parts and components', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12'),
(2, 'condenser', 'Condenser coils and related parts', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12'),
(3, 'evaporator', 'Evaporator coils and components', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12'),
(4, 'filter', 'Air filters and filtration systems', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12'),
(5, 'Capacitor - Test', 'Electrical capacitors', 1, '2025-09-03 13:37:12', '2025-09-03 13:52:08'),
(6, 'thermostat', 'Temperature control devices', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12'),
(7, 'fan_motor', 'Fan motors and related components', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12'),
(8, 'refrigerant', 'Refrigerant gases and chemicals', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12'),
(9, 'electrical', 'Electrical components and wiring', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12'),
(10, 'other', 'Miscellaneous parts and accessories', 1, '2025-09-03 13:37:12', '2025-09-03 13:37:12');

-- --------------------------------------------------------

--
-- Table structure for table `technicians`
--

CREATE TABLE `technicians` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `technicians`
--

INSERT INTO `technicians` (`id`, `username`, `password`, `name`, `email`, `phone`, `profile_picture`, `created_at`, `updated_at`) VALUES
(3, 'eciel', '$2y$10$UObZIT9OlApxi4rd/C47f.1WmkZJr3Vgq/Rs3ByDf1OkYfCIErsb.', 'Eciel Semeniano', 'ecielsemeniano@gmail.com', '09958714113', 'uploads/profile_pictures/technician_3_1758509562.png', '2025-06-03 06:56:19', '2025-09-22 02:52:42'),
(9, 'maan-admin', '$2y$10$tk3olYazbTW4nt5UOboJQOFEaEtSoo2BUTrFDWtVLAcrW17izQzqS', 'Marianne Dela Cruz', NULL, '09958714110', 'uploads/profile_pictures/technician_1755002855_689b37e7157d0.png', '2025-08-12 12:47:35', '2025-08-12 12:47:35'),
(12, 'tech001', '$2y$10$Uo1lHofYLbQlE4LSQKpm9OMIvV6RW1.VIUVe1.0s2LZv4VEYkj6tG', 'Technician-test', NULL, '09452592763', 'uploads/profile_pictures/technician_1756982354_68b96c52f1cee.png', '2025-09-04 10:39:15', '2025-09-04 10:39:15');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `ac_parts`
--
ALTER TABLE `ac_parts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ac_parts_category` (`part_category`),
  ADD KEY `idx_ac_parts_category_id` (`category_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `idx_username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`);

--
-- Indexes for table `aircon_hp`
--
ALTER TABLE `aircon_hp`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `aircon_installations`
--
ALTER TABLE `aircon_installations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `job_order_id` (`job_order_id`);

--
-- Indexes for table `cleaning_services`
--
ALTER TABLE `cleaning_services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customers_name_phone` (`name`,`phone`),
  ADD KEY `idx_customers_email` (`email`);

--
-- Indexes for table `job_orders`
--
ALTER TABLE `job_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_order_number` (`job_order_number`),
  ADD KEY `aircon_model_id` (`aircon_model_id`),
  ADD KEY `assigned_technician_id` (`assigned_technician_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `idx_job_orders_customer_id` (`customer_id`),
  ADD KEY `idx_job_orders_status` (`status`),
  ADD KEY `idx_job_orders_part_id` (`part_id`),
  ADD KEY `idx_job_orders_cleaning_service` (`cleaning_service_id`),
  ADD KEY `idx_secondary_technician` (`secondary_technician_id`),
  ADD KEY `idx_job_orders_customer_email` (`customer_email`);

--
-- Indexes for table `part_categories`
--
ALTER TABLE `part_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `technicians`
--
ALTER TABLE `technicians`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `ac_parts`
--
ALTER TABLE `ac_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `aircon_hp`
--
ALTER TABLE `aircon_hp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `aircon_installations`
--
ALTER TABLE `aircon_installations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cleaning_services`
--
ALTER TABLE `cleaning_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `job_orders`
--
ALTER TABLE `job_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=224;

--
-- AUTO_INCREMENT for table `part_categories`
--
ALTER TABLE `part_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `technicians`
--
ALTER TABLE `technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ac_parts`
--
ALTER TABLE `ac_parts`
  ADD CONSTRAINT `fk_ac_parts_category_id` FOREIGN KEY (`category_id`) REFERENCES `part_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `aircon_installations`
--
ALTER TABLE `aircon_installations`
  ADD CONSTRAINT `aircon_installations_ibfk_1` FOREIGN KEY (`job_order_id`) REFERENCES `job_orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `job_orders`
--
ALTER TABLE `job_orders`
  ADD CONSTRAINT `fk_job_orders_created_by` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_job_orders_customer_id` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_job_orders_part_id` FOREIGN KEY (`part_id`) REFERENCES `ac_parts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `job_orders_ibfk_1` FOREIGN KEY (`cleaning_service_id`) REFERENCES `cleaning_services` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
