-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2025 at 06:53 AM
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
-- Database: `testing_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `emp_id` varchar(10) NOT NULL,
  `emp_name` varchar(255) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `mobile_number` varchar(15) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT 0,
  `reset_token` varchar(64) NOT NULL,
  `token_expiry` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`emp_id`, `emp_name`, `designation`, `mobile_number`, `email`, `password`, `is_admin`, `reset_token`, `token_expiry`) VALUES
('EMP_0002', 'Sowmya R', 'Developer', '9876543210', 'sowmya@gmail.com', '$2y$10$7djY58/ilhJgmG4kpmZSjunLUy6Eq7WYorboRLknGOhXGmGtbaTtC', 1, '', 0),
('EMP_0003', 'Vaishnavi S', 'HR', '9638527410', 'vaish@gmail.com', '$2y$10$19BQm1b.nFy2H7DjebgO1OkFyXuN0zSW.2ym6q875SLA02ykz.W4O', 0, '', 0),
('EMP_0004', 'Archana R', 'Developer', '9876543215', 'archana@gmail.com', '$2y$10$DnsUzbT8KJ45IucQre9Q9entyXDYLOsdGiNAG/Yu5TvUxhBYZW/gC', 1, '', 0),
('EMP_0005', 'SUBHASHINI R', 'Developer', '9638527416', 'subha@gmail.com', '$2y$10$zGuGiNJu3iicRs2OAA/BBO3yvkmkYPY/lDp1hHfPFI9/Lqy2KSfL2', 1, '', 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`emp_id`),
  ADD UNIQUE KEY `mobile_number` (`mobile_number`),
  ADD UNIQUE KEY `email` (`email`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
