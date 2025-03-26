-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2025 at 04:28 AM
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
-- Table structure for table `bug`
--

CREATE TABLE `bug` (
  `id` varchar(20) NOT NULL,
  `testcase_id` varchar(20) NOT NULL,
  `bug_type` varchar(255) NOT NULL,
  `device_name` varchar(255) NOT NULL,
  `android_version` varchar(50) NOT NULL,
  `tested_by_name` varchar(255) NOT NULL,
  `tested_at` datetime NOT NULL,
  `actual_result` text NOT NULL,
  `testing_result` text NOT NULL,
  `file_attachment` varchar(255) DEFAULT NULL,
  `Module_name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `Product_name` varchar(255) DEFAULT NULL,
  `Version` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tested_by_id` varchar(255) DEFAULT NULL,
  `cleared_flag` tinyint(1) DEFAULT 0,
  `precondition` text DEFAULT NULL,
  `test_steps` text NOT NULL,
  `expected_results` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bug`
--

INSERT INTO `bug` (`id`, `testcase_id`, `bug_type`, `device_name`, `android_version`, `tested_by_name`, `tested_at`, `actual_result`, `testing_result`, `file_attachment`, `Module_name`, `description`, `Product_name`, `Version`, `created_at`, `tested_by_id`, `cleared_flag`, `precondition`, `test_steps`, `expected_results`) VALUES
('BID_0000001', 'NTC_000000001', 'High', 'Nothing', '15', 'NARMADHADEVI S', '2025-03-25 11:01:26', 'narmadha', '', '', 'TC_PurchaseApproval_01', 'Verify display of purchased products list', 'ProcuraAdmin', 'V1.0', '2025-03-25 10:03:35', '0', 0, 'Purchased products exist in the database.', 'Launch the app and view the purchased product list.', 'The list should display all purchased products (milk and non-milk) with their respective details.'),
('BID_0000002', 'NTC_000000001', 'High', 'Nothing', '15', 'NARMADHADEVI S', '2025-03-25 11:01:26', 'semburaj', '', '', 'TC_PurchaseApproval_01', 'Verify display of purchased products list', 'ProcuraAdmin', 'V1.0', '2025-03-25 10:03:53', '0', 0, 'Purchased products exist in the database.', 'Launch the app and view the purchased product list.', 'The list should display all purchased products (milk and non-milk) with their respective details.'),
('BID_0000003', 'NTC_000000001', 'Low', 'Nothing', '15', 'NARMADHADEVI S', '2025-03-25 11:06:58', 'subha', '', '', 'TC_PurchaseApproval_01', 'Verify display of purchased products list', 'ProcuraAdmin', 'V1.0', '2025-03-25 10:07:11', '0', 0, 'Purchased products exist in the database.', 'Launch the app and view the purchased product list.', 'The list should display all purchased products (milk and non-milk) with their respective details.'),
('BID_0000004', 'NTC_000000001', 'High', 'Redmi note 10S', '10', 'NARMADHADEVI S', '2025-03-25 11:10:46', 'failed', 'Fail', '', 'TC_PurchaseApproval_01', 'Verify display of purchased products list', 'ProcuraAdmin', 'V1.0', '2025-03-25 10:11:55', '0', 0, 'Purchased products exist in the database.', 'Launch the app and view the purchased product list.', 'The list should display all purchased products (milk and non-milk) with their respective details.'),
('BID_0000005', 'NTC_000000001', 'High', 'Iphone', '12', 'NARMADHADEVI S', '2025-03-25 11:12:45', 'subhaa', 'Fail', '', 'TC_PurchaseApproval_01', 'Verify display of purchased products list', 'ProcuraAdmin', 'V1.0', '2025-03-25 10:12:59', '0', 0, 'Purchased products exist in the database.', 'Launch the app and view the purchased product list.', 'The list should display all purchased products (milk and non-milk) with their respective details.'),
('BID_0000006', 'NTC_000000001', 'Low', 'iphone', '14`', 'NARMADHADEVI S', '2025-03-25 11:36:07', 'kokul', 'Fail', '', 'TC_PurchaseApproval_01', 'Verify display of purchased products list', 'ProcuraAdmin', 'V1.0', '2025-03-25 10:38:22', '0', 0, 'Purchased products exist in the database.', 'Launch the app and view the purchased product list.', 'The list should display all purchased products (milk and non-milk) with their respective details.');

--
-- Triggers `bug`
--
DELIMITER $$
CREATE TRIGGER `before_insert_bug` BEFORE INSERT ON `bug` FOR EACH ROW BEGIN
    DECLARE new_id VARCHAR(20);
    
    -- Find the last inserted ID and generate the next one
    SELECT IFNULL(MAX(id), 'BID_0000000') INTO new_id FROM bug;
    
    -- Extract the numeric part and increment it
    SET new_id = CONCAT('BID_', LPAD(SUBSTRING(new_id, 5) + 1, 7, '0'));

    -- Assign the new ID to the record
    SET NEW.id = new_id;
END
$$
DELIMITER ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bug`
--
ALTER TABLE `bug`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_bug_testcase` (`testcase_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
