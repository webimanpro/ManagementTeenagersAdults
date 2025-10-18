-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 18, 2025 at 11:31 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

--
-- Database: `mdata`
--

-- --------------------------------------------------------

--
-- Table structure for table `adult`
--

CREATE TABLE `adult` (
  `AdultID` int(11) NOT NULL,
  `AdultSysCode` varchar(20) NOT NULL COMMENT 'کدسیستمی',
  `AdultMelli` varchar(10) NOT NULL COMMENT 'کدملی',
  `AdultName` varchar(50) NOT NULL COMMENT 'نام',
  `AdultFamily` varchar(50) NOT NULL COMMENT 'نام خانوادگی',
  `AdultFather` varchar(50) NOT NULL COMMENT 'نام پدر',
  `AdultMobile1` varchar(11) DEFAULT NULL COMMENT 'موبایل 1',
  `AdultMobile2` varchar(11) DEFAULT NULL COMMENT 'موبایل 2',
  `AdultDateBirth` date DEFAULT NULL COMMENT 'تاریخ تولد',
  `AdultRegDate` date DEFAULT NULL COMMENT 'تاریخ ثبت نام',
  `AdultStatus` enum('عادی','فعال','تعلیق') NOT NULL DEFAULT 'عادی' COMMENT 'وضعیت',
  `AdultPlaceBirth` varchar(100) DEFAULT NULL COMMENT 'محل تولد',
  `AdultPlaceCerti` varchar(100) DEFAULT NULL COMMENT 'محل صدور',
  `AdultBloodType` enum('O+','O-','A+','A-','B+','B-','AB+','AB-') DEFAULT NULL COMMENT 'گروه خونی',
  `AdultEducation` enum('اول ابتدایی','دوم ابتدایی','سوم ابتدایی','چهارم ابتدایی','پنجم ابتدایی','ششم ابتدایی','هفتم','هشتم','نهم','دهم','یازدهم','دوازدهم','فارغ التحصیل','دانشجو','دیپلم','فوق دیپلم','لیسانس','فوق لیسانس','دکتری','سایر') DEFAULT NULL COMMENT 'تحصیلات',
  `AdultAddress` text DEFAULT NULL COMMENT 'آدرس',
  `AdultZipCode` varchar(10) DEFAULT NULL COMMENT 'کدپستی',
  `AdultImage` varchar(255) DEFAULT NULL COMMENT 'تصویر پروفایل',
  `AdultCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `AdultUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `AdultCity` varchar(100) DEFAULT NULL COMMENT 'شهر',
  `AdultBankName` varchar(100) DEFAULT NULL COMMENT 'نام بانک',
  `AdultAccountNumber` varchar(30) DEFAULT NULL COMMENT 'شماره حساب',
  `AdultCardNumber` varchar(20) DEFAULT NULL COMMENT 'شماره کارت',
  `AdultShebaNumber` varchar(30) DEFAULT NULL COMMENT 'شماره شبا',
  `AdultActiveDate` date DEFAULT NULL COMMENT 'تاریخ ثبت فعال',
  `AdultSuspendDate` date DEFAULT NULL COMMENT 'تاریخ ثبت تعلیق'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `adult`
--

INSERT INTO `adult` (`AdultID`, `AdultSysCode`, `AdultMelli`, `AdultName`, `AdultFamily`, `AdultFather`, `AdultMobile1`, `AdultMobile2`, `AdultDateBirth`, `AdultRegDate`, `AdultStatus`, `AdultPlaceBirth`, `AdultPlaceCerti`, `AdultBloodType`, `AdultEducation`, `AdultAddress`, `AdultZipCode`, `AdultImage`, `AdultCreated`, `AdultUpdated`, `AdultCity`, `AdultBankName`, `AdultAccountNumber`, `AdultCardNumber`, `AdultShebaNumber`, `AdultActiveDate`, `AdultSuspendDate`) VALUES
(1, '1', '1111111111', 'مهدی', 'محمدی', 'یاور', '09111111111', '09111111111', '2001-03-21', '2021-03-21', 'عادی', 'تهران', 'تهران', 'O+', 'دهم', 'تهران تهران1', '1111111111', '/upload/adult_1759836013_bbd3d3d0.jpg', '2025-10-07 11:20:13', '2025-10-07 11:22:58', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(2, '2', '2222222222', 'محمدرضا', 'محمدیان', 'حمید', '09222222222', '09222222222', '2001-04-21', '2021-04-21', 'عادی', 'تهران', 'تهران', 'O-', 'یازدهم', 'تهران تهران2', '2222222222', '/upload/adult_1759836062_e133a218.jpg', '2025-10-07 11:21:02', '2025-10-07 11:23:01', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(3, '3', '3333333333', 'علیرضا', 'حیدریان', 'اکبر', '09333333333', '09333333333', '2001-05-22', '2021-05-22', 'فعال', 'تهران', 'تهران', 'A+', 'دوازدهم', 'تهران تهران3', '3333333333', '/upload/adult_1759836126_b2fe6ae8.jpg', '2025-10-07 11:22:06', '2025-10-07 11:23:04', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(4, '4', '4444444444', 'علیرضا', 'حیدری', 'حمیدرضا', '09444444444', '09444444444', '2001-06-22', '2021-06-22', 'فعال', 'تهران', 'تهران', 'A-', 'دانشجو', 'تهران تهران4', '4444444444', '/upload/adult_1759836270_36da6743.jpg', '2025-10-07 11:24:30', '2025-10-07 11:24:30', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(5, '5', '5555555555', 'عباس', 'عباسی', 'اصغر', '09555555555', '09555555555', '2001-07-23', '2021-07-23', 'تعلیق', 'تهران', 'تهران', 'B+', 'دیپلم', 'تهران تهران5', '5555555555', '/upload/adult_1759836333_4b650f43.jpg', '2025-10-07 11:25:33', '2025-10-07 11:25:33', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(6, '6', '6666666666', 'یاور', 'یاوری', 'حیدر', '09666666666', '09666666666', '2001-08-23', '2021-08-23', 'تعلیق', 'تهران', 'تهران', 'B-', 'فوق دیپلم', 'تهران تهران6', '6666666666', '/upload/adult_1759836408_bb8bbfa5.jpg', '2025-10-07 11:26:48', '2025-10-07 11:26:48', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(7, '7', '7777777777', 'تستا', 'تستاا', 'تستااا', '9111111111', '9111111111', '1380-01-01', '1400-01-01', 'عادی', 'تست', 'تست', 'A+', 'هشتم', 'تست', '1111111111', NULL, '2025-10-08 06:15:15', '2025-10-08 06:15:15', 'تست', NULL, NULL, NULL, NULL, NULL, NULL),
(8, '8', '7777777778', 'تستب', 'تستبب', 'تستببب', '9111111112', '9111111112', '1380-01-02', '1400-01-02', 'فعال', 'تست', 'تست', 'B+', 'هشتم', 'تست', '1111111112', NULL, '2025-10-08 06:15:15', '2025-10-08 06:15:15', 'تست', NULL, NULL, NULL, NULL, NULL, NULL),
(9, '9', '7777777779', 'تستپ', 'تستپپ', 'تستپپپ', '09111111113', '09111111113', '1986-07-27', '2021-04-21', 'فعال', 'تست', 'تست', 'AB+', 'هشتم', 'تست', '1111111113', NULL, '2025-10-08 06:15:15', '2025-10-18 09:28:08', 'تست', 'بانک دی', '5355635656536356', '2343434324234343', 'IR454352345435345345345345', '2025-04-11', NULL),
(10, '11', '4757785785', 'اتابتباتب', 'یلالیالیا', 'یلاایلا', '09111111111', '09111111111', '1986-08-06', '2021-03-21', 'عادی', 'تست', 'تهران', 'AB-', 'دکتری', 'اتباتباتابت', '5345345453', NULL, '2025-10-18 09:30:59', '2025-10-18 09:30:59', 'لبالبا', 'بانک صادرات ایران', '546565635656536356', '3563565365363563', 'IR454352345435345345315345', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

CREATE TABLE `class` (
  `ClassID` int(11) NOT NULL,
  `ClassName` varchar(100) NOT NULL COMMENT 'نام دوره',
  `ClassDateStart` date DEFAULT NULL COMMENT 'تاریخ شروع دوره',
  `ClassDateEnd` date DEFAULT NULL COMMENT 'تاریخ پایان دوره',
  `ClassTime` varchar(20) DEFAULT NULL COMMENT 'ساعت دوره',
  `ClassTeacher` varchar(100) DEFAULT NULL COMMENT 'مربی دوره',
  `ClassPlace` varchar(100) DEFAULT NULL COMMENT 'مکان دوره',
  `ClassDescription` text DEFAULT NULL COMMENT 'توضیحات دوره',
  `CalssUsers` text DEFAULT NULL COMMENT 'کاربران دوره',
  `UserType` enum('teen','adult') DEFAULT 'teen'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class`
--

INSERT INTO `class` (`ClassID`, `ClassName`, `ClassDateStart`, `ClassDateEnd`, `ClassTime`, `ClassTeacher`, `ClassPlace`, `ClassDescription`, `CalssUsers`, `UserType`) VALUES
(0, 'تست دوره3', '1404-02-01', '1404-03-01', '17:00-18:00', 'مربی تست دوره3', 'مکان تست دوره3', 'توضیحات دوره3', '[{\"id\":\"1\",\"type\":\"teen\",\"name\":\"حسین قوچی\",\"code\":\"1\"},{\"id\":\"2\",\"type\":\"teen\",\"name\":\"علی علیا\",\"code\":\"2\"},{\"id\":\"3\",\"type\":\"teen\",\"name\":\"اکبر اکبری\",\"code\":\"3\"},{\"id\":\"4\",\"type\":\"teen\",\"name\":\"عباس عباسی\",\"code\":\"4\"},{\"id\":\"5\",\"type\":\"teen\",\"name\":\"علی علا\",\"code\":\"5\"},{\"id\":\"1\",\"type\":\"adult\",\"name\":\"مهدی محمدی\",\"code\":\"1\"},{\"id\":\"2\",\"type\":\"adult\",\"name\":\"محمدرضا محمدیان\",\"code\":\"2\"},{\"id\":\"3\",\"type\":\"adult\",\"name\":\"علیرضا حیدریان\",\"code\":\"3\"},{\"id\":\"4\",\"type\":\"adult\",\"name\":\"علیرضا حیدری\",\"code\":\"4\"},{\"id\":\"5\",\"type\":\"adult\",\"name\":\"عباس عباسی\",\"code\":\"5\"}]', 'teen'),
(1, 'تست دوره1', '1404-01-01', '1404-02-01', '16:00-18:00', 'مربی تست دوره1', 'مکان تست دوره1', 'توضیحات تست دوره1', '[{\"id\":\"1\",\"type\":\"teen\",\"name\":\"حسین قوچی\",\"code\":\"1\"},{\"id\":\"2\",\"type\":\"teen\",\"name\":\"علی علیا\",\"code\":\"2\"},{\"id\":\"3\",\"type\":\"teen\",\"name\":\"اکبر اکبری\",\"code\":\"3\"},{\"id\":\"4\",\"type\":\"teen\",\"name\":\"عباس عباسی\",\"code\":\"4\"},{\"id\":\"5\",\"type\":\"teen\",\"name\":\"علی علا\",\"code\":\"5\"},{\"id\":\"6\",\"type\":\"teen\",\"name\":\"مهدی مهرانی\",\"code\":\"6\"},{\"id\":\"7\",\"type\":\"teen\",\"name\":\"رضا داوری\",\"code\":\"7\"},{\"id\":\"8\",\"type\":\"teen\",\"name\":\"عباس عبدی\",\"code\":\"8\"},{\"id\":\"9\",\"type\":\"teen\",\"name\":\"محمدرضا محمدی پور\",\"code\":\"9\"}]', 'teen'),
(2, 'تست دوره2', '1404-01-01', '1404-02-01', '16:00-18:00', 'مربی تست دوره2', 'مکان تست دوره2', 'توضیحات تست دوره2', '[{\"id\":\"1\",\"type\":\"adult\",\"name\":\"مهدی محمدی\",\"code\":\"1\"},{\"id\":\"2\",\"type\":\"adult\",\"name\":\"محمدرضا محمدیان\",\"code\":\"2\"},{\"id\":\"3\",\"type\":\"adult\",\"name\":\"علیرضا حیدریان\",\"code\":\"3\"},{\"id\":\"4\",\"type\":\"adult\",\"name\":\"علیرضا حیدری\",\"code\":\"4\"},{\"id\":\"5\",\"type\":\"adult\",\"name\":\"عباس عباسی\",\"code\":\"5\"},{\"id\":\"6\",\"type\":\"adult\",\"name\":\"یاور یاوری\",\"code\":\"6\"}]', 'teen');

-- --------------------------------------------------------

--
-- Table structure for table `reportall`
--

CREATE TABLE `reportall` (
  `id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL COMMENT 'نام گزارش',
  `include_teens` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'شامل نوجوانان',
  `include_adults` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'شامل بزرگسالان',
  `report_type` enum('registration','attendance','class') NOT NULL COMMENT 'نوع گزارش',
  `report_year` int(4) NOT NULL COMMENT 'سال گزارش',
  `report_month` varchar(2) NOT NULL COMMENT 'ماه گزارش',
  `class_id` int(11) DEFAULT NULL COMMENT 'آیدی دوره (برای گزارشات دوره)',
  `syscode_from` varchar(20) DEFAULT NULL COMMENT 'کدسیستمی شروع (برای گزارش ثبت نام)',
  `syscode_to` varchar(20) DEFAULT NULL COMMENT 'کدسیستمی پایان (برای گزارش ثبت نام)',
  `selected_fields` text DEFAULT NULL COMMENT 'فیلدهای انتخاب شده برای گزارش',
  `header_desc` text DEFAULT NULL COMMENT 'توضیحات هدر گزارش',
  `footer_desc` text DEFAULT NULL COMMENT 'توضیحات فوتر گزارش',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول ذخیره گزارشات';

-- --------------------------------------------------------

--
-- Table structure for table `rollcalladult`
--

CREATE TABLE `rollcalladult` (
  `RollcalladultID` int(11) NOT NULL,
  `ClassID` int(11) NOT NULL,
  `AdultID` int(11) NOT NULL,
  `RollcalladultDate` date NOT NULL,
  `RollcalladultDay` varchar(20) NOT NULL COMMENT 'نام روز هفته',
  `Status` enum('present','absent','excused') NOT NULL DEFAULT 'absent',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `rollcallteen`
--

CREATE TABLE `rollcallteen` (
  `RollcallteenID` int(11) NOT NULL,
  `ClassID` int(11) NOT NULL,
  `TeenID` int(11) NOT NULL,
  `RollcallteenDate` date NOT NULL,
  `RollcallteenDay` varchar(20) NOT NULL COMMENT 'نام روز هفته',
  `Status` enum('present','absent','excused') NOT NULL DEFAULT 'absent',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitesettings`
--

CREATE TABLE `sitesettings` (
  `SettingKey` varchar(64) NOT NULL,
  `SettingValue` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sitesettings`
--

INSERT INTO `sitesettings` (`SettingKey`, `SettingValue`) VALUES
('background', '/assets/images/background-pic1.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `teen`
--

CREATE TABLE `teen` (
  `TeenID` int(11) NOT NULL,
  `TeenSysCode` varchar(20) NOT NULL COMMENT 'کدسیستمی',
  `TeenMelli` varchar(10) NOT NULL COMMENT 'کدملی',
  `TeenName` varchar(50) NOT NULL COMMENT 'نام',
  `TeenFamily` varchar(50) NOT NULL COMMENT 'نام خانوادگی',
  `TeenFather` varchar(50) NOT NULL COMMENT 'نام پدر',
  `TeenMobile1` varchar(11) DEFAULT NULL COMMENT 'موبایل 1',
  `TeenMobile2` varchar(11) DEFAULT NULL COMMENT 'موبایل 2',
  `TeenDateBirth` date DEFAULT NULL COMMENT 'تاریخ تولد',
  `TeenRegDate` date DEFAULT NULL COMMENT 'تاریخ ثبت نام',
  `TeenStatus` enum('عادی','فعال','تعلیق') NOT NULL DEFAULT 'عادی' COMMENT 'وضعیت',
  `TeenPlaceBirth` varchar(100) DEFAULT NULL COMMENT 'محل تولد',
  `TeenPlaceCerti` varchar(100) DEFAULT NULL COMMENT 'محل صدور',
  `TeenBloodType` enum('O+','O-','A+','A-','B+','B-','AB+','AB-') DEFAULT NULL COMMENT 'گروه خونی',
  `TeenEducation` enum('اول ابتدایی','دوم ابتدایی','سوم ابتدایی','چهارم ابتدایی','پنجم ابتدایی','ششم ابتدایی','هفتم','هشتم','نهم','دهم','یازدهم','دوازدهم','فارغ التحصیل','دانشجو','دیپلم','فوق دیپلم','لیسانس','فوق لیسانس','دکتری','سایر') DEFAULT NULL COMMENT 'تحصیلات',
  `TeenAddress` text DEFAULT NULL COMMENT 'آدرس',
  `TeenZipCode` varchar(10) DEFAULT NULL COMMENT 'کدپستی',
  `TeenImage` varchar(255) DEFAULT NULL COMMENT 'تصویر پروفایل',
  `TeenCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `TeenUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `TeenCity` varchar(100) DEFAULT NULL COMMENT 'شهر',
  `TeenBankName` varchar(100) DEFAULT NULL COMMENT 'نام بانک',
  `TeenAccountNumber` varchar(30) DEFAULT NULL COMMENT 'شماره حساب',
  `TeenCardNumber` varchar(20) DEFAULT NULL COMMENT 'شماره کارت',
  `TeenShebaNumber` varchar(30) DEFAULT NULL COMMENT 'شماره شبا',
  `TeenActiveDate` date DEFAULT NULL COMMENT 'تاریخ ثبت فعال',
  `TeenSuspendDate` date DEFAULT NULL COMMENT 'تاریخ ثبت تعلیق'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teen`
--

INSERT INTO `teen` (`TeenID`, `TeenSysCode`, `TeenMelli`, `TeenName`, `TeenFamily`, `TeenFather`, `TeenMobile1`, `TeenMobile2`, `TeenDateBirth`, `TeenRegDate`, `TeenStatus`, `TeenPlaceBirth`, `TeenPlaceCerti`, `TeenBloodType`, `TeenEducation`, `TeenAddress`, `TeenZipCode`, `TeenImage`, `TeenCreated`, `TeenUpdated`, `TeenCity`, `TeenBankName`, `TeenAccountNumber`, `TeenCardNumber`, `TeenShebaNumber`, `TeenActiveDate`, `TeenSuspendDate`) VALUES
(1, '1', '1111111111', 'تستا', 'تستاا', 'تستااا', '9111111111', '9111111111', '2011-03-21', '2021-03-21', 'عادی', 'تست', 'تست', 'A+', 'هشتم', 'تست', '1111111111', '/upload/2.jpg', '2025-10-07 06:03:05', '2025-10-18 06:11:14', 'تست', NULL, NULL, NULL, NULL, NULL, NULL),
(2, '2', '1111111112', 'تستب', 'تستبب', 'تستببب', '9111111112', '9111111112', '2011-03-22', '2021-03-22', 'فعال', 'تست', 'تست', 'B+', 'هشتم', 'تست', '1111111112', NULL, '2025-10-07 06:19:20', '2025-10-18 06:11:14', 'تست', NULL, NULL, NULL, NULL, NULL, NULL),
(3, '3', '1111111113', 'تستپ', 'تستپپ', 'تستپپپ', '9111111113', '9111111113', '2011-03-23', '2021-03-23', 'تعلیق', 'تست', 'تست', 'AB+', 'هشتم', 'تست', '1111111113', '/upload/teen_1759818085_b74fbd9e.jpg', '2025-10-07 06:21:25', '2025-10-18 06:11:14', 'تست', NULL, NULL, NULL, NULL, NULL, NULL),
(4, '4', '4444444444', 'عباس', 'عباسی', 'اکبر', '09444444444', '09444444444', '2011-06-22', '2021-06-22', 'عادی', 'تهران', 'تهران', 'A-', 'چهارم ابتدایی', 'تهران4', '4444444444', '/upload/teen_1759818408_68e4b2a8a4b7f.jpg', '2025-10-07 06:24:19', '2025-10-07 08:33:03', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(5, '5', '5555555555', 'علی', 'علا', 'علیرضا', '09555555555', '09555555555', '2011-07-23', '2021-07-23', 'عادی', 'تهران', 'تهران', 'B+', 'پنجم ابتدایی', 'تهران5', '5555555555', '/upload/teen_1759818416_68e4b2b069cb6.jpg', '2025-10-07 06:25:35', '2025-10-07 08:15:48', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(6, '6', '6666666666', 'مهدی', 'مهرانی', 'علی', '09666666666', '09666666666', '2011-08-23', '2021-08-23', 'فعال', 'تهران', 'تهران', 'B-', 'ششم ابتدایی', 'تهران6', '6666666666', '/upload/teen_1759826103_918655dc.jpg', '2025-10-07 08:35:03', '2025-10-07 08:35:03', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(7, '7', '7777777777', 'رضا', 'داوری', 'محمد', '09777777777', '09777777777', '2011-09-23', '2021-09-23', 'فعال', 'تهران', 'تهران', 'AB+', 'هفتم', 'تهران7', '7777777777', '/upload/teen_1759826208_68e4d12048299.jpg', '2025-10-07 08:36:20', '2025-10-07 08:36:48', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(8, '8', '8888888888', 'عباس', 'عبدی', 'رضا', '09888888888', '09888888888', '2011-10-23', '2021-10-23', 'تعلیق', 'تهران', 'تهران', 'AB-', 'هشتم', 'تهران8', '8888888888', '/upload/teen_1759826271_614ccb23.jpg', '2025-10-07 08:37:51', '2025-10-07 08:37:51', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(9, '9', '9999999999', 'محمدرضا', 'محمدی پور', 'علیرضا', '09999999999', '09999999999', '2011-11-22', '2021-11-22', 'تعلیق', 'تهران', 'تهران', '', 'نهم', 'تهران9', '9999999999', '/upload/teen_1759826340_588d9673.jpg', '2025-10-07 08:39:00', '2025-10-07 08:39:00', 'تهران', NULL, NULL, NULL, NULL, NULL, NULL),
(10, '10', '1010101010', 'علی', 'عیا', 'رضا', '9101010101', '9101010101', '1380-01-01', '1400-01-01', 'عادی', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(11, '11', '1010101011', 'علیرضا', 'عیان', 'یاور', '9101010102', '9101010102', '1380-01-02', '1400-01-02', 'عادی', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(12, '12', '1010101012', 'محمد', 'محمدی', 'حسن', '9101010103', '9101010103', '1380-01-03', '1400-01-03', 'فعال', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(13, '13', '1010101013', 'محمدرضا', 'محمدی پور', 'حسین', '9101010104', '9101010104', '1380-01-04', '1400-01-04', 'فعال', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(14, '14', '1010101014', 'مهدی', 'مهرابی', 'اصغر', '9101010105', '9101010105', '1380-01-05', '1400-01-05', 'فعال', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(15, '15', '1010101015', 'حسین', 'مرادی', 'اکبر', '9101010106', '9101010106', '1380-01-06', '1400-01-06', 'تعلیق', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(16, '16', '1010101016', 'حمید', 'مرادپور', 'قاسم', '9101010107', '9101010107', '1380-01-07', '1400-01-07', 'تعلیق', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(17, '17', '1010101017', 'حمیدرضا', 'عسگری', 'علی', '9101010108', '9101010108', '1380-01-08', '1400-01-08', 'عادی', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(18, '18', '1010101018', 'محسن', 'نیک', 'رضا', '09101010109', '09101010109', '1986-11-03', '2021-08-31', 'فعال', 'تهران', 'تهران', 'O+', 'لیسانس', 'تهران 18', '3391956181', NULL, '2025-10-08 05:56:09', '2025-10-18 08:49:48', 'تهران', 'بانک ملت', '23451111', '6037212121212323', 'IR123456789123654789632118', '2024-02-25', NULL),
(19, '19', '1010101019', 'اکبر', 'گفتار', 'علی', '9101010110', '9101010110', '1380-01-10', '1400-01-10', 'عادی', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(20, '20', '1010101020', 'عباس', 'عباسی', 'رضا', '9101010111', '9101010111', '1380-01-11', '1400-01-11', 'عادی', 'تهران', 'تهران', '', '', '', '', NULL, '2025-10-08 05:56:09', '2025-10-08 05:56:09', '', NULL, NULL, NULL, NULL, NULL, NULL),
(21, '21', '2121212121', 'محمدعلی', 'علوندی', 'رضا', '09991999932', '09135898098', '1992-02-01', '2021-12-31', 'فعال', 'تهران', 'تهران', 'AB+', 'لیسانس', 'تهران21', '2121211212', NULL, '2025-10-18 08:31:26', '2025-10-18 08:31:26', 'تهران', 'بانک ملی ایران', '212121212121', '6037212121212121', 'IR123456789123654789632121', '2024-07-22', NULL),
(22, '22', '9511236547', 'علی', 'علا', 'علیرضا', '09900920728', '09194398098', '2011-11-03', '2021-07-23', 'فعال', 'رذرذرذ', 'پاکدشت', 'B-', 'دکتری', 'بلبسیلبشلبس', '3333333333', '/upload/adult_1759827204_2a7f5602_1.jpg', '2025-10-18 09:19:29', '2025-10-18 09:21:14', 'تهران', 'بانک ملت', '324234423423', '2343434231111114', 'IR123456789123654789432154', '2024-07-22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','manager','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$7D1KOe25ai.MAds/easHBO.s4xjMe1qFx4T3px737ECzwCuHNpDKm', 'مدیرسیستم', 'admin@admin.com', '09123456789', 'admin', '2025-09-13 20:21:58', '2025-10-07 12:49:59'),
(2, 'admin2', '$2y$10$nznafsHSazPlM6DYjJmWkOmWP5yGViE3JWXayawuDW0klg0/2orpa', 'مدیر', 'admin2@admin.com', '09191111111', 'manager', '2025-10-04 04:39:16', NULL),
(3, 'user', '$2y$10$eExWkTVG2Sf1o48Q6Z5cb.PzWBHE2NDXz4Pgb7G6Ofn2AmwTP/AA.', 'کاربر', 'user@admin.com', '09101111111', 'user', '2025-10-04 04:40:11', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `adult`
--
ALTER TABLE `adult`
  ADD PRIMARY KEY (`AdultID`);

--
-- Indexes for table `class`
--
ALTER TABLE `class`
  ADD PRIMARY KEY (`ClassID`);

--
-- Indexes for table `reportall`
--
ALTER TABLE `reportall`
  ADD PRIMARY KEY (`id`),
  ADD KEY `report_type` (`report_type`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `fk_reportall_class` (`class_id`);

--
-- Indexes for table `rollcalladult`
--
ALTER TABLE `rollcalladult`
  ADD PRIMARY KEY (`RollcalladultID`),
  ADD UNIQUE KEY `unique_rollcalladult` (`ClassID`,`AdultID`,`RollcalladultDate`);

--
-- Indexes for table `rollcallteen`
--
ALTER TABLE `rollcallteen`
  ADD PRIMARY KEY (`RollcallteenID`),
  ADD UNIQUE KEY `unique_rollcallteen` (`ClassID`,`TeenID`,`RollcallteenDate`),
  ADD KEY `TeenID` (`TeenID`);

--
-- Indexes for table `sitesettings`
--
ALTER TABLE `sitesettings`
  ADD PRIMARY KEY (`SettingKey`);

--
-- Indexes for table `teen`
--
ALTER TABLE `teen`
  ADD PRIMARY KEY (`TeenID`),
  ADD UNIQUE KEY `idx_TeenMelli` (`TeenMelli`),
  ADD UNIQUE KEY `idx_TeenSysCode` (`TeenSysCode`),
  ADD KEY `idx_TeenName` (`TeenName`),
  ADD KEY `idx_TeenFamily` (`TeenFamily`),
  ADD KEY `idx_TeenMobile1` (`TeenMobile1`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `adult`
--
ALTER TABLE `adult`
  MODIFY `AdultID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `teen`
--
ALTER TABLE `teen`
  MODIFY `TeenID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reportall`
--
ALTER TABLE `reportall`
  ADD CONSTRAINT `fk_reportall_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`ClassID`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `rollcallteen`
--
ALTER TABLE `rollcallteen`
  ADD CONSTRAINT `rollcallteen_ibfk_1` FOREIGN KEY (`ClassID`) REFERENCES `class` (`ClassID`),
  ADD CONSTRAINT `rollcallteen_ibfk_2` FOREIGN KEY (`TeenID`) REFERENCES `teen` (`TeenID`);
COMMIT;