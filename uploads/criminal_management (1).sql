-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2025 at 03:16 AM
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
-- Database: `criminal_management`
--

DELIMITER $$
--
-- Procedures
--
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
CREATE TABLE `active_cases` (
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

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `arrests` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `criminal_id` int(11) NOT NULL,
  `arresting_officer` int(11) NOT NULL,
  `arrest_date` datetime NOT NULL,
  `arrest_location` varchar(200) NOT NULL,
  `charges` text NOT NULL,
  `arrest_notes` text DEFAULT NULL,
  `status` enum('pending','booked','released','transferred') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `cases` (
  `id` int(11) NOT NULL,
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
  `closed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
DELIMITER $$
CREATE TRIGGER `after_case_insert` AFTER INSERT ON `cases` FOR EACH ROW BEGIN
    INSERT INTO activity_log (user_id, action, table_name, record_id, details)
    VALUES (NEW.created_by, 'create', 'cases', NEW.id, CONCAT('Created case: ', NEW.case_number));
END
$$
DELIMITER ;
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

CREATE TABLE `case_criminals` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `criminal_id` int(11) NOT NULL,
  `role_in_case` enum('suspect','witness','victim','accomplice') NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `case_notes` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `note_type` enum('update','finding','witness_statement','suspect_interview','evidence_analysis','other') DEFAULT 'update',
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `is_private` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
CREATE TABLE `case_summary` (
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

CREATE TABLE `court_appearances` (
  `id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `criminal_id` int(11) NOT NULL,
  `court_name` varchar(100) NOT NULL,
  `court_date` datetime NOT NULL,
  `appearance_type` enum('arraignment','preliminary_hearing','trial','sentencing','appeal','other') NOT NULL,
  `outcome` text DEFAULT NULL,
  `next_hearing_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `criminals` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `evidence` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text NOT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
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
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
CREATE TABLE `wanted_criminals` (
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

CREATE TABLE `witnesses` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Structure for view `active_cases`
--
DROP TABLE IF EXISTS `active_cases`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `active_cases`  AS SELECT `c`.`id` AS `id`, `c`.`case_number` AS `case_number`, `c`.`title` AS `title`, `c`.`description` AS `description`, `c`.`case_type` AS `case_type`, `c`.`priority` AS `priority`, `c`.`status` AS `status`, `c`.`location` AS `location`, `c`.`incident_date` AS `incident_date`, `c`.`reported_date` AS `reported_date`, `c`.`assigned_officer` AS `assigned_officer`, `c`.`lead_investigator` AS `lead_investigator`, `c`.`estimated_value` AS `estimated_value`, `c`.`created_by` AS `created_by`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, `c`.`closed_at` AS `closed_at`, `u`.`fullname` AS `assigned_officer_name`, `li`.`fullname` AS `lead_investigator_name` FROM ((`cases` `c` left join `users` `u` on(`c`.`assigned_officer` = `u`.`id`)) left join `users` `li` on(`c`.`lead_investigator` = `li`.`id`)) WHERE `c`.`status` in ('open','under_investigation') ;

-- --------------------------------------------------------

--
-- Structure for view `case_summary`
--
DROP TABLE IF EXISTS `case_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `case_summary`  AS SELECT `c`.`id` AS `id`, `c`.`case_number` AS `case_number`, `c`.`title` AS `title`, `c`.`case_type` AS `case_type`, `c`.`priority` AS `priority`, `c`.`status` AS `status`, `c`.`incident_date` AS `incident_date`, count(distinct `cc`.`criminal_id`) AS `suspect_count`, count(distinct `e`.`id`) AS `evidence_count`, count(distinct `a`.`id`) AS `arrest_count` FROM (((`cases` `c` left join `case_criminals` `cc` on(`c`.`id` = `cc`.`case_id`)) left join `evidence` `e` on(`c`.`id` = `e`.`case_id`)) left join `arrests` `a` on(`c`.`id` = `a`.`case_id`)) GROUP BY `c`.`id` ;

-- --------------------------------------------------------

--
-- Structure for view `wanted_criminals`
--
DROP TABLE IF EXISTS `wanted_criminals`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `wanted_criminals`  AS SELECT `c`.`id` AS `id`, `c`.`fullname` AS `fullname`, `c`.`alias` AS `alias`, `c`.`date_of_birth` AS `date_of_birth`, `c`.`gender` AS `gender`, `c`.`nationality` AS `nationality`, `c`.`identification_number` AS `identification_number`, `c`.`address` AS `address`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`height` AS `height`, `c`.`weight` AS `weight`, `c`.`eye_color` AS `eye_color`, `c`.`hair_color` AS `hair_color`, `c`.`distinguishing_marks` AS `distinguishing_marks`, `c`.`photo` AS `photo`, `c`.`fingerprint_data` AS `fingerprint_data`, `c`.`dna_sample` AS `dna_sample`, `c`.`status` AS `status`, `c`.`risk_level` AS `risk_level`, `c`.`created_by` AS `created_by`, `c`.`created_at` AS `created_at`, `c`.`updated_at` AS `updated_at`, count(`cc`.`case_id`) AS `case_count` FROM (`criminals` `c` left join `case_criminals` `cc` on(`c`.`id` = `cc`.`criminal_id`)) WHERE `c`.`status` = 'wanted' GROUP BY `c`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `arrests`
--
ALTER TABLE `arrests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `arresting_officer` (`arresting_officer`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_criminal_id` (`criminal_id`),
  ADD KEY `idx_arrest_date` (`arrest_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `case_number` (`case_number`),
  ADD KEY `lead_investigator` (`lead_investigator`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_case_number` (`case_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_case_type` (`case_type`),
  ADD KEY `idx_incident_date` (`incident_date`),
  ADD KEY `idx_assigned_officer` (`assigned_officer`);

--
-- Indexes for table `case_criminals`
--
ALTER TABLE `case_criminals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_case_criminal` (`case_id`,`criminal_id`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_criminal_id` (`criminal_id`);

--
-- Indexes for table `case_notes`
--
ALTER TABLE `case_notes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_note_type` (`note_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `court_appearances`
--
ALTER TABLE `court_appearances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_criminal_id` (`criminal_id`),
  ADD KEY `idx_court_date` (`court_date`),
  ADD KEY `idx_appearance_type` (`appearance_type`);

--
-- Indexes for table `criminals`
--
ALTER TABLE `criminals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `identification_number` (`identification_number`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_fullname` (`fullname`),
  ADD KEY `idx_identification_number` (`identification_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_risk_level` (`risk_level`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `evidence`
--
ALTER TABLE `evidence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `evidence_number` (`evidence_number`),
  ADD KEY `collected_by` (`collected_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_evidence_number` (`evidence_number`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_collected_date` (`collected_date`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `badge_number` (`badge_number`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_department` (`department`);

--
-- Indexes for table `witnesses`
--
ALTER TABLE `witnesses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `interviewed_by` (`interviewed_by`),
  ADD KEY `idx_case_id` (`case_id`),
  ADD KEY `idx_fullname` (`fullname`),
  ADD KEY `idx_statement_date` (`statement_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `arrests`
--
ALTER TABLE `arrests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `case_criminals`
--
ALTER TABLE `case_criminals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `case_notes`
--
ALTER TABLE `case_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `court_appearances`
--
ALTER TABLE `court_appearances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `criminals`
--
ALTER TABLE `criminals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `evidence`
--
ALTER TABLE `evidence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `witnesses`
--
ALTER TABLE `witnesses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
