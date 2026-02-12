-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2025 at 03:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `criminal_management`
--
CREATE DATABASE IF NOT EXISTS `criminal_management` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `criminal_management`;

DELIMITER $$
--
-- Procedures
--
DROP PROCEDURE IF EXISTS `GetCaseDetails`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetCaseDetails` (IN `case_id` INT)   BEGIN
    SELECT 
        c.*,
        u.fullname as assigned_officer_name,
        li.fullname as lead_investigator_name
    FROM cases c
    LEFT JOIN users u ON c.assigned_officer = u.id
    LEFT JOIN users li ON c.lead_investigator = li.id
    WHERE c.id = case_id;
END$$

DROP PROCEDURE IF EXISTS `GetCriminalCases`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetCriminalCases` (IN `criminal_id` INT)   BEGIN
    SELECT 
        c.*,
        cc.role_in_case,
        cc.notes as case_notes
    FROM cases c
    JOIN case_criminals cc ON c.id = cc.case_id
    WHERE cc.criminal_id = criminal_id
    ORDER BY c.incident_date DESC;
END$$

DROP PROCEDURE IF EXISTS `GetOfficerStats`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `GetOfficerStats` (IN `officer_id` INT)   BEGIN
    SELECT 
        COUNT(DISTINCT c.id) as total_cases,
        COUNT(DISTINCT CASE WHEN c.status = 'open' THEN c.id END) as open_cases,
        COUNT(DISTINCT CASE WHEN c.status = 'closed' THEN c.id END) as closed_cases,
        COUNT(DISTINCT a.id) as total_arrests,
        COUNT(DISTINCT e.id) as evidence_collected
    FROM users u
    LEFT JOIN cases c ON u.id = c.assigned_officer OR u.id = c.lead_investigator
    LEFT JOIN arrests a ON u.id = a.arresting_officer
    LEFT JOIN evidence e ON u.id = e.collected_by
    WHERE u.id = officer_id;
END$$

DROP PROCEDURE IF EXISTS `SearchCriminals`$$
CREATE DEFINER=`root`@`localhost` PROCEDURE `SearchCriminals` (IN `search_term` VARCHAR(100))   BEGIN
    SELECT * FROM criminals 
    WHERE fullname LIKE CONCAT('%', search_term, '%')
    OR alias LIKE CONCAT('%', search_term, '%')
    OR identification_number LIKE CONCAT('%', search_term, '%')
    ORDER BY created_at DESC;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Stand-in structure for view `active_cases`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `active_cases`;
CREATE TABLE IF NOT EXISTS `active_cases` (
`id` int(11)
,`case_number` varchar(50)
,`title` varchar(200)
,`description` text
,`case_type` enum('homicide','robbery','assault','drug_trafficking','fraud','burglary','theft','domestic_violence','sexual_assault','other')
,`priority` enum('low','medium','high','urgent')
,`status` enum('open','under_investigation','pending_trial','closed','cold_case')
,`location` varchar(200)
,`incident_date` datetime
,`reported_date` datetime
,`assigned_officer` int(11)
,`lead_investigator` int(11)
,`estimated_value` decimal(12,2)
,`created_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`closed_at` timestamp
,`assigned_officer_name` varchar(100)
,`lead_investigator_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `activity_log`:
--   `user_id`
--       `users` -> `id`
--

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'login', 'users', 1, 'Administrator logged in successfully', '192.168.1.100', NULL, '2025-07-22 04:46:49'),
(2, 2, 'create', 'cases', 1, 'Created new case CASE-2024-001', '192.168.1.101', NULL, '2025-07-22 04:46:49'),
(3, 2, 'update', 'cases', 1, 'Updated case status to under_investigation', '192.168.1.101', NULL, '2025-07-22 04:46:49'),
(4, 4, 'create', 'evidence', 3, 'Added evidence EVD-2024-003 to case', '192.168.1.102', NULL, '2025-07-22 04:46:49'),
(5, 1, 'create', 'users', 6, 'Created new user account for Lisa Davis', '192.168.1.100', NULL, '2025-07-22 04:46:49'),
(6, 1, 'update', 'criminals', 1, 'Updated criminal: James Rodriguez', NULL, NULL, '2025-07-22 05:25:27'),
(7, 1, 'create', 'criminals', 6, 'Added new criminal: Pablo', NULL, NULL, '2025-07-22 09:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `arrests`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `arrests`;
CREATE TABLE IF NOT EXISTS `arrests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `criminal_id` int(11) NOT NULL,
  `arresting_officer` int(11) NOT NULL,
  `arrest_date` datetime NOT NULL,
  `arrest_location` varchar(200) NOT NULL,
  `charges` text NOT NULL,
  `arrest_notes` text DEFAULT NULL,
  `status` enum('pending','booked','released','transferred') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `arresting_officer` (`arresting_officer`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_criminal_id` (`criminal_id`),
  KEY `idx_arrest_date` (`arrest_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `arrests`:
--   `case_id`
--       `cases` -> `id`
--   `criminal_id`
--       `criminals` -> `id`
--   `arresting_officer`
--       `users` -> `id`
--

--
-- Dumping data for table `arrests`
--

INSERT INTO `arrests` (`id`, `case_id`, `criminal_id`, `arresting_officer`, `arrest_date`, `arrest_location`, `charges`, `arrest_notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 2, '2024-01-16 10:30:00', '456 Oak Ave, City, State', 'Armed Robbery, Aggravated Assault, Possession of Firearm', 'Suspect apprehended without incident', 'booked', '2025-07-22 04:46:48', '2025-07-22 04:46:48'),
(2, 2, 3, 4, '2024-01-20 03:00:00', 'Interstate 95, Mile Marker 45', 'Drug Trafficking, Possession with Intent to Distribute, Money Laundering', 'Multiple suspects arrested in coordinated operation', 'booked', '2025-07-22 04:46:48', '2025-07-22 04:46:48'),
(3, 2, 4, 4, '2024-01-20 03:00:00', 'Interstate 95, Mile Marker 45', 'Drug Trafficking, Money Laundering, Conspiracy', 'Accomplice arrested with primary suspect', 'booked', '2025-07-22 04:46:48', '2025-07-22 04:46:48'),
(4, 3, 5, 2, '2024-01-27 14:00:00', '654 Maple Dr, City, State', 'Burglary, Theft, Criminal Mischief', 'Suspect found with stolen property', 'booked', '2025-07-22 04:46:48', '2025-07-22 04:46:48');

--
-- Triggers `arrests`
--
DROP TRIGGER IF EXISTS `after_arrest_insert`;
DELIMITER $$
CREATE TRIGGER `after_arrest_insert` AFTER INSERT ON `arrests` FOR EACH ROW BEGIN
    INSERT INTO activity_log (user_id, action, table_name, record_id, details)
    VALUES (NEW.arresting_officer, 'create', 'arrests', NEW.id, 'New arrest recorded');
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `cases`;
CREATE TABLE IF NOT EXISTS `cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_number` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `case_type` enum('homicide','robbery','assault','drug_trafficking','fraud','burglary','theft','domestic_violence','sexual_assault','other') NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','under_investigation','pending_trial','closed','cold_case') DEFAULT 'open',
  `location` varchar(200) NOT NULL,
  `incident_date` datetime NOT NULL,
  `reported_date` datetime NOT NULL,
  `assigned_officer` int(11) DEFAULT NULL,
  `lead_investigator` int(11) DEFAULT NULL,
  `estimated_value` decimal(12,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `closed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `case_number` (`case_number`),
  KEY `lead_investigator` (`lead_investigator`),
  KEY `created_by` (`created_by`),
  KEY `idx_case_number` (`case_number`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_case_type` (`case_type`),
  KEY `idx_incident_date` (`incident_date`),
  KEY `idx_assigned_officer` (`assigned_officer`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `cases`:
--   `assigned_officer`
--       `users` -> `id`
--   `lead_investigator`
--       `users` -> `id`
--   `created_by`
--       `users` -> `id`
--

--
-- Dumping data for table `cases`
--

INSERT INTO `cases` (`id`, `case_number`, `title`, `description`, `case_type`, `priority`, `status`, `location`, `incident_date`, `reported_date`, `assigned_officer`, `lead_investigator`, `estimated_value`, `created_by`, `created_at`, `updated_at`, `closed_at`) VALUES
(1, 'CASE-2024-001', 'Downtown Bank Robbery', 'Armed robbery at First National Bank, downtown branch. Suspects used automatic weapons and escaped with approximately $50,000.', 'robbery', 'high', 'under_investigation', '123 Main St, Downtown', '2024-01-15 14:30:00', '2024-01-15 14:35:00', 2, 2, 50000.00, 1, '2025-07-22 04:46:45', '2025-07-22 04:46:45', NULL),
(2, 'CASE-2024-002', 'Highway Drug Bust', 'Large-scale drug trafficking operation intercepted on Interstate 95. Multiple suspects arrested with significant quantities of narcotics.', 'drug_trafficking', 'urgent', 'pending_trial', 'Interstate 95, Mile Marker 45', '2024-01-20 02:15:00', '2024-01-20 02:20:00', 3, 4, 250000.00, 1, '2025-07-22 04:46:45', '2025-07-22 04:46:45', NULL),
(3, 'CASE-2024-003', 'Residential Burglary Spree', 'Series of residential burglaries in the Oakwood neighborhood. Pattern suggests organized crime involvement.', 'burglary', 'medium', 'open', 'Oakwood Neighborhood', '2024-01-25 19:00:00', '2024-01-26 08:00:00', 2, 2, 15000.00, 1, '2025-07-22 04:46:45', '2025-07-22 04:46:45', NULL),
(4, 'CASE-2024-004', 'Nightclub Shooting', 'Fatal shooting at Club Paradise. Multiple victims, gang-related violence suspected.', 'homicide', 'urgent', 'under_investigation', '456 Club St, Entertainment District', '2024-01-30 23:45:00', '2024-01-31 00:05:00', 5, 4, 0.00, 1, '2025-07-22 04:46:45', '2025-07-22 04:46:45', NULL),
(5, 'CASE-2024-005', 'Corporate Fraud Scheme', 'Complex financial fraud involving multiple shell companies and millions in stolen funds.', 'fraud', 'high', 'open', 'Downtown Business District', '2024-02-05 09:00:00', '2024-02-05 10:30:00', 2, 2, 2500000.00, 1, '2025-07-22 04:46:45', '2025-07-22 04:46:45', NULL);

--
-- Triggers `cases`
--
DROP TRIGGER IF EXISTS `after_case_insert`;
DELIMITER $$
CREATE TRIGGER `after_case_insert` AFTER INSERT ON `cases` FOR EACH ROW BEGIN
    INSERT INTO activity_log (user_id, action, table_name, record_id, details)
    VALUES (NEW.created_by, 'create', 'cases', NEW.id, CONCAT('Created case: ', NEW.case_number));
END
$$
DELIMITER ;
DROP TRIGGER IF EXISTS `after_case_update`;
DELIMITER $$
CREATE TRIGGER `after_case_update` AFTER UPDATE ON `cases` FOR EACH ROW BEGIN
    IF OLD.status != NEW.status THEN
        INSERT INTO activity_log (user_id, action, table_name, record_id, details)
        VALUES (NEW.created_by, 'update', 'cases', NEW.id, CONCAT('Case status changed from ', OLD.status, ' to ', NEW.status));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `case_criminals`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `case_criminals`;
CREATE TABLE IF NOT EXISTS `case_criminals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `criminal_id` int(11) NOT NULL,
  `role_in_case` enum('suspect','witness','victim','accomplice') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_case_criminal` (`case_id`,`criminal_id`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_criminal_id` (`criminal_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `case_criminals`:
--   `case_id`
--       `cases` -> `id`
--   `criminal_id`
--       `criminals` -> `id`
--

--
-- Dumping data for table `case_criminals`
--

INSERT INTO `case_criminals` (`id`, `case_id`, `criminal_id`, `role_in_case`, `notes`, `created_at`) VALUES
(1, 1, 1, 'suspect', 'Primary suspect, identified through surveillance footage', '2025-07-22 04:46:47'),
(2, 1, 2, 'accomplice', 'Driver of getaway vehicle', '2025-07-22 04:46:47'),
(3, 2, 3, 'suspect', 'Ring leader of drug trafficking operation', '2025-07-22 04:46:47'),
(4, 2, 4, 'accomplice', 'Money launderer for the operation', '2025-07-22 04:46:47'),
(5, 3, 5, 'suspect', 'Main suspect in burglary spree', '2025-07-22 04:46:47'),
(6, 4, 1, 'suspect', 'Identified as shooter through witness statements', '2025-07-22 04:46:47'),
(7, 5, 3, 'suspect', 'Mastermind behind fraud scheme', '2025-07-22 04:46:47');

-- --------------------------------------------------------

--
-- Table structure for table `case_notes`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `case_notes`;
CREATE TABLE IF NOT EXISTS `case_notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_type` enum('update','finding','witness_statement','suspect_interview','evidence_analysis','other') DEFAULT 'update',
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `is_private` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_note_type` (`note_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `case_notes`:
--   `case_id`
--       `cases` -> `id`
--   `user_id`
--       `users` -> `id`
--

--
-- Dumping data for table `case_notes`
--

INSERT INTO `case_notes` (`id`, `case_id`, `user_id`, `note_type`, `title`, `content`, `is_private`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'update', 'Surveillance Analysis Complete', 'Forensic analysis of bank surveillance footage completed. Positive identification of primary suspect James Rodriguez. Vehicle description and license plate obtained.', 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(2, 1, 2, 'finding', 'Ballistics Report', 'Shell casings match weapon used in previous robberies. Same weapon used in three other cases in the past 6 months.', 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(3, 2, 4, 'update', 'Drug Analysis Results', 'Substances confirmed as cocaine and heroin. Total weight: 25kg cocaine, 5kg heroin. Street value approximately $250,000.', 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(4, 2, 4, 'witness_statement', 'Truck Driver Statement', 'Truck driver witnessed suspicious activity at rest stop. Provided detailed description of suspects and vehicle.', 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(5, 3, 2, 'update', 'Pattern Analysis', 'Burglaries follow consistent pattern. All homes targeted were unoccupied during business hours. Entry through rear windows or doors.', 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49');

-- --------------------------------------------------------

--
-- Stand-in structure for view `case_summary`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `case_summary`;
CREATE TABLE IF NOT EXISTS `case_summary` (
`id` int(11)
,`case_number` varchar(50)
,`title` varchar(200)
,`case_type` enum('homicide','robbery','assault','drug_trafficking','fraud','burglary','theft','domestic_violence','sexual_assault','other')
,`priority` enum('low','medium','high','urgent')
,`status` enum('open','under_investigation','pending_trial','closed','cold_case')
,`incident_date` datetime
,`suspect_count` bigint(21)
,`evidence_count` bigint(21)
,`arrest_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `court_appearances`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `court_appearances`;
CREATE TABLE IF NOT EXISTS `court_appearances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `criminal_id` int(11) NOT NULL,
  `court_name` varchar(100) NOT NULL,
  `court_date` datetime NOT NULL,
  `appearance_type` enum('arraignment','preliminary_hearing','trial','sentencing','appeal','other') NOT NULL,
  `outcome` text DEFAULT NULL,
  `next_hearing_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_criminal_id` (`criminal_id`),
  KEY `idx_court_date` (`court_date`),
  KEY `idx_appearance_type` (`appearance_type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `court_appearances`:
--   `case_id`
--       `cases` -> `id`
--   `criminal_id`
--       `criminals` -> `id`
--

--
-- Dumping data for table `court_appearances`
--

INSERT INTO `court_appearances` (`id`, `case_id`, `criminal_id`, `court_name`, `court_date`, `appearance_type`, `outcome`, `next_hearing_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 'City Criminal Court', '2024-02-15 09:00:00', 'arraignment', 'Bail set at $100,000', '2024-03-15 09:00:00', 'Defendant pleaded not guilty. Trial date set.', '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(2, 2, 3, 'Federal District Court', '2024-02-20 10:00:00', 'arraignment', 'No bail - flight risk', '2024-04-20 10:00:00', 'Federal charges filed. Defendant held without bail.', '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(3, 2, 4, 'Federal District Court', '2024-02-20 10:00:00', 'arraignment', 'Bail set at $50,000', '2024-04-20 10:00:00', 'Co-defendant in federal case.', '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(4, 3, 5, 'City Criminal Court', '2024-02-25 14:00:00', 'preliminary_hearing', 'Case bound over for trial', '2024-03-25 14:00:00', 'Sufficient evidence to proceed to trial.', '2025-07-22 04:46:49', '2025-07-22 04:46:49');

-- --------------------------------------------------------

--
-- Table structure for table `criminals`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `criminals`;
CREATE TABLE IF NOT EXISTS `criminals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `alias` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `nationality` varchar(50) DEFAULT NULL,
  `identification_number` varchar(50) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `height` decimal(5,2) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `eye_color` varchar(20) DEFAULT NULL,
  `hair_color` varchar(20) DEFAULT NULL,
  `distinguishing_marks` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `fingerprint_data` text DEFAULT NULL,
  `dna_sample` varchar(255) DEFAULT NULL,
  `status` enum('wanted','arrested','convicted','released','deceased') DEFAULT 'wanted',
  `risk_level` enum('low','medium','high','extreme') DEFAULT 'medium',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `identification_number` (`identification_number`),
  KEY `created_by` (`created_by`),
  KEY `idx_fullname` (`fullname`),
  KEY `idx_identification_number` (`identification_number`),
  KEY `idx_status` (`status`),
  KEY `idx_risk_level` (`risk_level`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `criminals`:
--   `created_by`
--       `users` -> `id`
--

--
-- Dumping data for table `criminals`
--

INSERT INTO `criminals` (`id`, `fullname`, `alias`, `date_of_birth`, `gender`, `nationality`, `identification_number`, `address`, `phone`, `email`, `height`, `weight`, `eye_color`, `hair_color`, `distinguishing_marks`, `photo`, `fingerprint_data`, `dna_sample`, `status`, `risk_level`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'James Rodriguez', 'El Diablo', '1985-03-15', 'male', 'Mexican', 'CR001', '123 Main St, City, State', '+1-555-0101', '', 175.50, 80.20, 'brown', 'black', '', 'uploads/criminal_photos/687f20c6ebdfc_Kochunni.jpg', NULL, NULL, 'wanted', 'high', 1, '2025-07-22 04:46:44', '2025-07-22 05:25:26'),
(2, 'Maria Garcia', 'La Reina', '1990-07-22', 'female', 'Colombian', 'CR002', '456 Oak Ave, City, State', '+1-555-0102', NULL, 162.00, 55.80, 'green', 'brown', NULL, NULL, NULL, NULL, 'arrested', 'medium', 1, '2025-07-22 04:46:44', '2025-07-22 04:46:44'),
(3, 'David Thompson', 'The Ghost', '1982-11-08', 'male', 'American', 'CR003', '789 Pine Rd, City, State', '+1-555-0103', NULL, 180.00, 85.50, 'blue', 'blonde', NULL, NULL, NULL, NULL, 'convicted', 'extreme', 1, '2025-07-22 04:46:44', '2025-07-22 04:46:44'),
(4, 'Jennifer Lee', 'Dragon Lady', '1988-05-12', 'female', 'Chinese', 'CR004', '321 Elm St, City, State', '+1-555-0104', NULL, 165.00, 60.00, 'brown', 'black', NULL, NULL, NULL, NULL, 'wanted', 'high', 1, '2025-07-22 04:46:44', '2025-07-22 04:46:44'),
(5, 'Carlos Martinez', 'El Jefe', '1980-12-03', 'male', 'Mexican', 'CR005', '654 Maple Dr, City, State', '+1-555-0105', NULL, 178.00, 82.00, 'brown', 'brown', NULL, NULL, NULL, NULL, 'arrested', 'medium', 1, '2025-07-22 04:46:44', '2025-07-22 04:46:44'),
(6, 'Pablo', 'Morgado', '2000-07-15', 'male', 'Spain', 'CR007', 'Alians Arena', '00445500', '', 160.00, 65.00, 'brown', 'blonde', 'tattoo in left hand', 'uploads/criminal_photos/687f5cb7248af_Snapchat-199680607.jpg', NULL, NULL, 'wanted', 'medium', 1, '2025-07-22 09:41:11', '2025-07-22 09:41:11');

-- --------------------------------------------------------

--
-- Table structure for table `evidence`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `evidence`;
CREATE TABLE IF NOT EXISTS `evidence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `evidence_number` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `type` enum('physical','digital','document','photograph','video','audio','biological','trace') NOT NULL,
  `location_found` varchar(200) NOT NULL,
  `collected_by` int(11) NOT NULL,
  `collected_date` datetime NOT NULL,
  `status` enum('collected','analyzed','stored','returned','destroyed') DEFAULT 'collected',
  `storage_location` varchar(200) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `evidence_number` (`evidence_number`),
  KEY `collected_by` (`collected_by`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_evidence_number` (`evidence_number`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_collected_date` (`collected_date`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `evidence`:
--   `case_id`
--       `cases` -> `id`
--   `collected_by`
--       `users` -> `id`
--

--
-- Dumping data for table `evidence`
--

INSERT INTO `evidence` (`id`, `case_id`, `evidence_number`, `name`, `description`, `type`, `location_found`, `collected_by`, `collected_date`, `status`, `storage_location`, `file_path`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 'EVD-2024-001', 'Surveillance Video', 'Bank security camera footage showing robbery in progress', 'digital', 'First National Bank, Downtown', 2, '2024-01-15 15:00:00', 'analyzed', 'Evidence Room A-1', NULL, NULL, '2025-07-22 04:46:47', '2025-07-22 04:46:47'),
(2, 1, 'EVD-2024-002', 'Spent Shell Casings', '9mm shell casings found at scene', 'physical', 'Bank lobby floor', 2, '2024-01-15 15:30:00', 'analyzed', 'Evidence Room B-2', NULL, NULL, '2025-07-22 04:46:47', '2025-07-22 04:46:47'),
(3, 2, 'EVD-2024-003', 'Drug Parcels', 'Multiple packages containing cocaine and heroin', 'physical', 'Vehicle trunk', 4, '2024-01-20 03:00:00', 'analyzed', 'Evidence Room C-3', NULL, NULL, '2025-07-22 04:46:47', '2025-07-22 04:46:47'),
(4, 2, 'EVD-2024-004', 'Cell Phone', 'Suspect cell phone with incriminating messages', 'digital', 'Suspect pocket', 4, '2024-01-20 03:15:00', 'analyzed', 'Evidence Room D-4', NULL, NULL, '2025-07-22 04:46:47', '2025-07-22 04:46:47'),
(5, 3, 'EVD-2024-005', 'Tool Marks', 'Tool marks on forced entry points', 'physical', 'Multiple residences', 2, '2024-01-26 09:00:00', 'analyzed', 'Evidence Room E-5', NULL, NULL, '2025-07-22 04:46:47', '2025-07-22 04:46:47');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `system_settings`:
--

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `is_public`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'Criminal Management System', 'Name of the system', 1, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(2, 'site_description', 'Advanced law enforcement platform for secure criminal record management', 'System description', 1, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(3, 'max_login_attempts', '5', 'Maximum failed login attempts before account lockout', 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(4, 'session_timeout', '3600', 'Session timeout in seconds', 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(5, 'password_min_length', '8', 'Minimum password length requirement', 1, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(6, 'enable_registration', 'true', 'Whether new user registration is enabled', 1, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(7, 'maintenance_mode', 'false', 'Whether the system is in maintenance mode', 1, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(8, 'contact_email', 'support@criminalmanagement.com', 'System contact email', 1, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(9, 'emergency_contact', '+1-555-123-4567', 'Emergency contact number', 1, '2025-07-22 04:46:49', '2025-07-22 04:46:49');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `badge_number` varchar(20) NOT NULL,
  `department` varchar(50) NOT NULL,
  `role` enum('admin','officer','investigator') NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `profile_image` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `badge_number` (`badge_number`),
  KEY `idx_username` (`username`),
  KEY `idx_email` (`email`),
  KEY `idx_role` (`role`),
  KEY `idx_status` (`status`),
  KEY `idx_department` (`department`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `users`:
--

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `username`, `badge_number`, `department`, `role`, `password`, `status`, `profile_image`, `phone`, `address`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'System Administrator', 'admin@criminalmanagement.com', 'admin', 'ADMIN001', 'Administrative', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NULL, NULL, NULL, '2025-07-22 04:46:42', '2025-07-22 04:46:42', NULL),
(2, 'John Smith', 'john.smith@police.gov', 'jsmith', 'PD001', 'Patrol', 'officer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', '68830922114cc_officer.jpg', '', NULL, '2025-07-22 04:46:44', '2025-07-25 04:33:38', NULL),
(3, 'Sarah Johnson', 'sarah.johnson@police.gov', 'sjohnson', 'PD002', 'Detective', 'investigator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NULL, NULL, NULL, '2025-07-22 04:46:44', '2025-07-22 04:46:44', NULL),
(4, 'Michael Brown', 'michael.brown@police.gov', 'mbrown', 'PD003', 'Traffic', 'officer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NULL, NULL, NULL, '2025-07-22 04:46:44', '2025-07-22 04:46:44', NULL),
(5, 'Lisa Davis', 'lisa.davis@police.gov', 'ldavis', 'PD004', 'Narcotics', 'investigator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NULL, NULL, NULL, '2025-07-22 04:46:44', '2025-07-22 04:46:44', NULL),
(6, 'Robert Wilson', 'robert.wilson@police.gov', 'rwilson', 'PD005', 'Special Operations', 'officer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active', NULL, NULL, NULL, '2025-07-22 04:46:44', '2025-07-22 04:46:44', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `wanted_criminals`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `wanted_criminals`;
CREATE TABLE IF NOT EXISTS `wanted_criminals` (
`id` int(11)
,`fullname` varchar(100)
,`alias` varchar(100)
,`date_of_birth` date
,`gender` enum('male','female','other')
,`nationality` varchar(50)
,`identification_number` varchar(50)
,`address` text
,`phone` varchar(20)
,`email` varchar(100)
,`height` decimal(5,2)
,`weight` decimal(5,2)
,`eye_color` varchar(20)
,`hair_color` varchar(20)
,`distinguishing_marks` text
,`photo` varchar(255)
,`fingerprint_data` text
,`dna_sample` varchar(255)
,`status` enum('wanted','arrested','convicted','released','deceased')
,`risk_level` enum('low','medium','high','extreme')
,`created_by` int(11)
,`created_at` timestamp
,`updated_at` timestamp
,`case_count` bigint(21)
);

-- --------------------------------------------------------

--
-- Table structure for table `witnesses`
--
-- Creation: Jul 22, 2025 at 04:46 AM
--

DROP TABLE IF EXISTS `witnesses`;
CREATE TABLE IF NOT EXISTS `witnesses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('male','female','other') DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `statement` text NOT NULL,
  `statement_date` datetime NOT NULL,
  `interviewed_by` int(11) NOT NULL,
  `is_confidential` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `interviewed_by` (`interviewed_by`),
  KEY `idx_case_id` (`case_id`),
  KEY `idx_fullname` (`fullname`),
  KEY `idx_statement_date` (`statement_date`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- RELATIONSHIPS FOR TABLE `witnesses`:
--   `case_id`
--       `cases` -> `id`
--   `interviewed_by`
--       `users` -> `id`
--

--
-- Dumping data for table `witnesses`
--

INSERT INTO `witnesses` (`id`, `case_id`, `fullname`, `date_of_birth`, `gender`, `phone`, `email`, `address`, `statement`, `statement_date`, `interviewed_by`, `is_confidential`, `created_at`, `updated_at`) VALUES
(1, 1, 'Margaret Williams', '1965-08-12', 'female', '+1-555-0201', 'margaret.williams@email.com', '789 Bank St, Downtown', 'I was working as a teller when the robbery occurred. Two men entered with guns drawn. They were wearing ski masks but I could see one had a distinctive tattoo on his hand.', '2024-01-15 16:00:00', 2, 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(2, 1, 'Robert Chen', '1980-03-25', 'male', '+1-555-0202', 'robert.chen@email.com', '456 Security Ave, Downtown', 'I was the security guard on duty. I tried to intervene but was threatened with a weapon. I noticed one suspect had a limp when walking.', '2024-01-15 16:30:00', 2, 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(3, 2, 'Truck Driver Johnson', '1975-11-08', 'male', '+1-555-0203', NULL, 'Truck Stop, Mile Marker 45', 'I saw suspicious activity at the rest stop. Several men were loading packages into a van. They seemed nervous and were working quickly.', '2024-01-20 04:00:00', 4, 1, '2025-07-22 04:46:49', '2025-07-22 04:46:49'),
(4, 4, 'Club Security Guard', '1988-06-15', 'male', '+1-555-0204', 'security@clubparadise.com', '456 Club St, Entertainment District', 'I was working the door when the shooting started. I saw a man in a black hoodie fire multiple shots into the crowd. He then ran out the back exit.', '2024-01-31 01:00:00', 4, 0, '2025-07-22 04:46:49', '2025-07-22 04:46:49');

-- --------------------------------------------------------

--
-- Structure for view `active_cases` exported as a table
--
DROP TABLE IF EXISTS `active_cases`;
CREATE TABLE IF NOT EXISTS `active_cases`(
    `id` int(11) NOT NULL DEFAULT '0',
    `case_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
    `description` text COLLATE utf8mb4_general_ci NOT NULL,
    `case_type` enum('homicide','robbery','assault','drug_trafficking','fraud','burglary','theft','domestic_violence','sexual_assault','other') COLLATE utf8mb4_general_ci NOT NULL,
    `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_general_ci DEFAULT 'medium',
    `status` enum('open','under_investigation','pending_trial','closed','cold_case') COLLATE utf8mb4_general_ci DEFAULT 'open',
    `location` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
    `incident_date` datetime NOT NULL,
    `reported_date` datetime NOT NULL,
    `assigned_officer` int(11) DEFAULT NULL,
    `lead_investigator` int(11) DEFAULT NULL,
    `estimated_value` decimal(12,2) DEFAULT '0.00',
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT 'current_timestamp()',
    `updated_at` timestamp NOT NULL DEFAULT 'current_timestamp()',
    `closed_at` timestamp DEFAULT NULL,
    `assigned_officer_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `lead_investigator_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL
);

-- --------------------------------------------------------

--
-- Structure for view `case_summary` exported as a table
--
DROP TABLE IF EXISTS `case_summary`;
CREATE TABLE IF NOT EXISTS `case_summary`(
    `id` int(11) NOT NULL DEFAULT '0',
    `case_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
    `case_type` enum('homicide','robbery','assault','drug_trafficking','fraud','burglary','theft','domestic_violence','sexual_assault','other') COLLATE utf8mb4_general_ci NOT NULL,
    `priority` enum('low','medium','high','urgent') COLLATE utf8mb4_general_ci DEFAULT 'medium',
    `status` enum('open','under_investigation','pending_trial','closed','cold_case') COLLATE utf8mb4_general_ci DEFAULT 'open',
    `incident_date` datetime NOT NULL,
    `suspect_count` bigint(21) NOT NULL DEFAULT '0',
    `evidence_count` bigint(21) NOT NULL DEFAULT '0',
    `arrest_count` bigint(21) NOT NULL DEFAULT '0'
);

-- --------------------------------------------------------

--
-- Structure for view `wanted_criminals` exported as a table
--
DROP TABLE IF EXISTS `wanted_criminals`;
CREATE TABLE IF NOT EXISTS `wanted_criminals`(
    `id` int(11) NOT NULL DEFAULT '0',
    `fullname` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
    `alias` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `date_of_birth` date NOT NULL,
    `gender` enum('male','female','other') COLLATE utf8mb4_general_ci NOT NULL,
    `nationality` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `identification_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
    `address` text COLLATE utf8mb4_general_ci DEFAULT NULL,
    `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `height` decimal(5,2) DEFAULT NULL,
    `weight` decimal(5,2) DEFAULT NULL,
    `eye_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `hair_color` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `distinguishing_marks` text COLLATE utf8mb4_general_ci DEFAULT NULL,
    `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `fingerprint_data` text COLLATE utf8mb4_general_ci DEFAULT NULL,
    `dna_sample` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
    `status` enum('wanted','arrested','convicted','released','deceased') COLLATE utf8mb4_general_ci DEFAULT 'wanted',
    `risk_level` enum('low','medium','high','extreme') COLLATE utf8mb4_general_ci DEFAULT 'medium',
    `created_by` int(11) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT 'current_timestamp()',
    `updated_at` timestamp NOT NULL DEFAULT 'current_timestamp()',
    `case_count` bigint(21) NOT NULL DEFAULT '0'
);

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `arrests`
--
ALTER TABLE `arrests`
  ADD CONSTRAINT `arrests_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `arrests_ibfk_2` FOREIGN KEY (`criminal_id`) REFERENCES `criminals` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `arrests_ibfk_3` FOREIGN KEY (`arresting_officer`) REFERENCES `users` (`id`);

--
-- Constraints for table `cases`
--
ALTER TABLE `cases`
  ADD CONSTRAINT `cases_ibfk_1` FOREIGN KEY (`assigned_officer`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cases_ibfk_2` FOREIGN KEY (`lead_investigator`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cases_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `case_criminals`
--
ALTER TABLE `case_criminals`
  ADD CONSTRAINT `case_criminals_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `case_criminals_ibfk_2` FOREIGN KEY (`criminal_id`) REFERENCES `criminals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `case_notes`
--
ALTER TABLE `case_notes`
  ADD CONSTRAINT `case_notes_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `case_notes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `court_appearances`
--
ALTER TABLE `court_appearances`
  ADD CONSTRAINT `court_appearances_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `court_appearances_ibfk_2` FOREIGN KEY (`criminal_id`) REFERENCES `criminals` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `criminals`
--
ALTER TABLE `criminals`
  ADD CONSTRAINT `criminals_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `evidence`
--
ALTER TABLE `evidence`
  ADD CONSTRAINT `evidence_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evidence_ibfk_2` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `witnesses`
--
ALTER TABLE `witnesses`
  ADD CONSTRAINT `witnesses_ibfk_1` FOREIGN KEY (`case_id`) REFERENCES `cases` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `witnesses_ibfk_2` FOREIGN KEY (`interviewed_by`) REFERENCES `users` (`id`);


--
-- Metadata
--
USE `phpmyadmin`;

--
-- Metadata for table active_cases
--

--
-- Metadata for table activity_log
--

--
-- Metadata for table arrests
--

--
-- Metadata for table cases
--

--
-- Metadata for table case_criminals
--

--
-- Metadata for table case_notes
--

--
-- Metadata for table case_summary
--

--
-- Metadata for table court_appearances
--

--
-- Metadata for table criminals
--

--
-- Metadata for table evidence
--

--
-- Metadata for table system_settings
--

--
-- Metadata for table users
--

--
-- Metadata for table wanted_criminals
--

--
-- Metadata for table witnesses
--

--
-- Metadata for database criminal_management
--
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
