
--
-- Database: `mdata`
--
CREATE DATABASE IF NOT EXISTS `mdata` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mdata`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
CREATE TABLE IF NOT EXISTS `admins` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','manager','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`ID`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$7D1KOe25ai.MAds/easHBO.s4xjMe1qFx4T3px737ECzwCuHNpDKm', 'مدیرسیستم', 'admin@admin.com', '09123456789', 'admin', '2025-09-13 13:21:58', '2025-10-07 05:49:59'),
(2, 'admin2', '$2y$10$U3GIAs3S9raBZud7PGzcCepIRvLoz/DAqwlv76rhvE1Ws5TFOZ62u', 'مدیر', 'admin2@admin.com', '09191111111', 'manager', '2025-11-02 05:03:14', NULL),
(3, 'user', '$2y$10$awMCNZzoIOqCd064adxSaum86abyV15r0k7JO3Fw3XhYUSSFGbSsC', 'کاربر', 'user@admin.com', '09101111111', 'user', '2025-11-02 05:03:37', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `class`
--

DROP TABLE IF EXISTS `class`;
CREATE TABLE IF NOT EXISTS `class` (
  `ClassID` int(11) NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(100) NOT NULL COMMENT 'نام دوره',
  `ClassDateStart` date DEFAULT NULL COMMENT 'تاریخ شروع دوره',
  `ClassDateEnd` date DEFAULT NULL COMMENT 'تاریخ پایان دوره',
  `ClassTime` varchar(20) DEFAULT NULL COMMENT 'ساعت دوره',
  `ClassTeacher` varchar(100) DEFAULT NULL COMMENT 'مربی دوره',
  `ClassPlace` varchar(100) DEFAULT NULL COMMENT 'مکان دوره',
  `ClassDescription` text DEFAULT NULL COMMENT 'توضیحات دوره',
  `CalssUsers` text DEFAULT NULL COMMENT 'کاربران دوره',
  PRIMARY KEY (`ClassID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportall`
--

DROP TABLE IF EXISTS `reportall`;
CREATE TABLE IF NOT EXISTS `reportall` (
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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `report_type` (`report_type`),
  KEY `created_at` (`created_at`),
  KEY `fk_reportall_class` (`class_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='جدول ذخیره گزارشات';

-- --------------------------------------------------------

--
-- Table structure for table `rollcalluser`
--

DROP TABLE IF EXISTS `rollcalluser`;
CREATE TABLE IF NOT EXISTS `rollcalluser` (
  `RollcallUserID` int(11) NOT NULL AUTO_INCREMENT,
  `ClassID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `RollcallUserDate` date NOT NULL,
  `RollcallUserDay` varchar(20) NOT NULL COMMENT 'نام روز هفته',
  `Status` enum('حاضر','غایب','مرخصی') NOT NULL DEFAULT 'حاضر',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`RollcallUserID`),
  UNIQUE KEY `unique_Rollcalluser` (`ClassID`,`UserID`,`RollcallUserDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sitesettings`
--

DROP TABLE IF EXISTS `sitesettings`;
CREATE TABLE IF NOT EXISTS `sitesettings` (
  `SettingKey` varchar(64) NOT NULL,
  `SettingValue` text DEFAULT NULL,
  PRIMARY KEY (`SettingKey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sitesettings`
--

INSERT INTO `sitesettings` (`SettingKey`, `SettingValue`) VALUES
('background', '/assets/images/pic1.jpeg');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `UserID` int(11) NOT NULL AUTO_INCREMENT,
  `UserSysCode` varchar(20) NOT NULL COMMENT 'کدسیستمی',
  `UserMelli` varchar(10) NOT NULL COMMENT 'کدملی',
  `UserName` varchar(50) NOT NULL COMMENT 'نام',
  `UserFamily` varchar(50) NOT NULL COMMENT 'نام خانوادگی',
  `UserFather` varchar(50) NOT NULL COMMENT 'نام پدر',
  `UserMobile1` varchar(11) DEFAULT NULL COMMENT 'موبایل 1',
  `UserMobile2` varchar(11) DEFAULT NULL COMMENT 'موبایل 2',
  `UserDateBirth` date DEFAULT NULL COMMENT 'تاریخ تولد',
  `UserRegDate` date DEFAULT NULL COMMENT 'تاریخ ثبت نام',
  `UserStatus` enum('عادی','فعال','تعلیق') NOT NULL DEFAULT 'عادی' COMMENT 'وضعیت',
  `UserPlaceBirth` varchar(100) DEFAULT NULL COMMENT 'محل تولد',
  `UserPlaceCerti` varchar(100) DEFAULT NULL COMMENT 'محل صدور',
  `UserBloodType` enum('O+','O-','A+','A-','B+','B-','AB+','AB-') DEFAULT NULL COMMENT 'گروه خونی',
  `UserEducation` enum('اول ابتدایی','دوم ابتدایی','سوم ابتدایی','چهارم ابتدایی','پنجم ابتدایی','ششم ابتدایی','هفتم','هشتم','نهم','دهم','یازدهم','دوازدهم','فارغ التحصیل','دانشجو','دیپلم','فوق دیپلم','لیسانس','فوق لیسانس','دکتری','سایر') DEFAULT NULL COMMENT 'تحصیلات',
  `UserAddress` text DEFAULT NULL COMMENT 'آدرس',
  `UserZipCode` varchar(10) DEFAULT NULL COMMENT 'کدپستی',
  `UserCity` varchar(100) DEFAULT NULL COMMENT 'شهر',
  `UserBankName` varchar(100) DEFAULT NULL COMMENT 'نام بانک',
  `UserAccountNumber` varchar(30) DEFAULT NULL COMMENT 'شماره حساب',
  `UserCardNumber` varchar(20) DEFAULT NULL COMMENT 'شماره کارت',
  `UserShebaNumber` varchar(30) DEFAULT NULL COMMENT 'شماره شبا',
  `UserActiveDate` date DEFAULT NULL COMMENT 'تاریخ ثبت فعال',
  `UserSuspendDate` date DEFAULT NULL COMMENT 'تاریخ ثبت تعلیق',
  `UserNumbersh` varchar(10) DEFAULT NULL COMMENT 'شماره شناسنامه',
  `UserMaritalStatus` enum('مجرد','متاهل') DEFAULT NULL COMMENT 'وضعیت تاهل',
  `UserOtherActivity` text DEFAULT NULL COMMENT 'فعالیت‌های دیگر',
  `UserDutyStatus` enum('در حین خدمت','کارت پایان خدمت','معاف','قبل از سن مشمولیت','خرید خدمت') DEFAULT NULL COMMENT 'وضعیت خدمت وظیفه',
  `UserJobWork` varchar(100) DEFAULT NULL COMMENT 'شغل',
  `UserPhone` varchar(15) DEFAULT NULL COMMENT 'تلفن ثابت',
  `UserEmail` varchar(100) DEFAULT NULL COMMENT 'ایمیل',
  `UserImage` varchar(255) DEFAULT NULL COMMENT 'تصویر پروفایل',
  `UserCreated` timestamp NOT NULL DEFAULT current_timestamp(),
  `UserUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reportall`
--
ALTER TABLE `reportall`
  ADD CONSTRAINT `fk_reportall_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`ClassID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;
