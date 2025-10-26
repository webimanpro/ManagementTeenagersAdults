-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 26, 2025 at 02:20 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

--
-- Database: `mdata`
--

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
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
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `email`, `phone`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$7D1KOe25ai.MAds/easHBO.s4xjMe1qFx4T3px737ECzwCuHNpDKm', 'مدیرسیستم', 'admin@admin.com', '09123456789', 'admin', '2025-09-13 16:51:58', '2025-10-07 09:19:59'),
(2, 'admin2', '$2y$10$nznafsHSazPlM6DYjJmWkOmWP5yGViE3JWXayawuDW0klg0/2orpa', 'مدیر', 'admin2@admin.com', '09191111111', 'manager', '2025-10-04 01:09:16', NULL),
(3, 'user', '$2y$10$eExWkTVG2Sf1o48Q6Z5cb.PzWBHE2NDXz4Pgb7G6Ofn2AmwTP/AA.', 'کاربر', 'user@admin.com', '09101111111', 'user', '2025-10-04 01:10:11', NULL);

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
-- Table structure for table `rollcalluser`
--

CREATE TABLE `rollcalluser` (
  `RollcallUserID` int(11) NOT NULL,
  `ClassID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `RollcallUserDate` date NOT NULL,
  `RollcallUserDay` varchar(20) NOT NULL COMMENT 'نام روز هفته',
  `Status` enum('حاضر','غایب','مرخصی') NOT NULL DEFAULT 'حاضر',
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
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
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
  `UserUpdated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `UserSysCode`, `UserMelli`, `UserName`, `UserFamily`, `UserFather`, `UserMobile1`, `UserMobile2`, `UserDateBirth`, `UserRegDate`, `UserStatus`, `UserPlaceBirth`, `UserPlaceCerti`, `UserBloodType`, `UserEducation`, `UserAddress`, `UserZipCode`, `UserCity`, `UserBankName`, `UserAccountNumber`, `UserCardNumber`, `UserShebaNumber`, `UserActiveDate`, `UserSuspendDate`, `UserNumbersh`, `UserMaritalStatus`, `UserOtherActivity`, `UserDutyStatus`, `UserJobWork`, `UserPhone`, `UserEmail`, `UserImage`, `UserCreated`, `UserUpdated`) VALUES
(1, '1', '1111111111', 'محمد', 'تست', 'سه تست', '9111111111', '9111111111', '2011-03-21', '2021-03-21', 'عادی', 'تست', 'شش تست', 'A+', 'هشتم', 'دوازدهم تست', '1111111111', 'نهم تست', NULL, NULL, NULL, NULL, NULL, NULL, '12345', NULL, NULL, NULL, 'کارمند', '2133333333', 'email@test.com', NULL, '2025-10-26 12:56:06', '2025-10-26 12:58:59'),
(2, '2', '1111111112', 'رضا', 'یک تست', 'چهار تست', '9111111112', '9111111112', '2011-03-22', '2021-03-22', 'فعال', 'تست', 'هفت تست', 'B+', 'هفتم', 'سیزدهم تست', '1111111112', 'دهم تست', NULL, NULL, NULL, NULL, NULL, NULL, '67890', NULL, NULL, NULL, 'کارمند', '2133333333', 'email@test.com', NULL, '2025-10-26 12:56:06', '2025-10-26 12:59:09'),
(3, '3', '1111111113', 'مهدی', 'دو تست', 'پنج تست', '9111111113', '9111111113', '2011-03-23', '2021-03-23', 'تعلیق', 'تست', 'هشت تست', 'AB+', 'نهم', 'چهاردهم تست', '1111111113', 'یازدهم تست', NULL, NULL, NULL, NULL, NULL, NULL, '54321', NULL, NULL, NULL, 'کارمند', '2133333333', 'email@test.com', NULL, '2025-10-26 12:56:06', '2025-10-26 12:59:12'),
(4, '4', '1111111114', 'علی', 'اکبری', 'محمدرضا', '', '', NULL, NULL, 'عادی', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, '', '', '', NULL, '2025-10-26 12:56:06', '2025-10-26 12:59:17'),
(5, '5', '1111111115', 'علیرضا', 'اکبری', 'محمدرضا', '', '', NULL, NULL, 'عادی', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, '', '', '', NULL, '2025-10-26 12:56:06', '2025-10-26 12:59:21'),
(6, '6', '1111111116', 'محمدرضا', 'اکبری', 'محمدرضا', '', '', NULL, NULL, 'عادی', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, '', '', '', NULL, '2025-10-26 12:56:06', '2025-10-26 12:59:25'),
(7, '7', '1111111117', 'طاهر', 'اکبری', 'محمدرضا', '', '', NULL, NULL, 'عادی', '', '', '', '', '', '', '', NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, NULL, NULL, '', '', '', NULL, '2025-10-26 12:56:06', '2025-10-26 12:59:28'),
(8, '8', '8888888888', 'عباس', 'عباسی', 'اکبر', '09191111111', '09111111111', '2001-11-03', '2019-03-21', 'فعال', 'تهران', 'تهران', 'O+', 'لیسانس', 'پاکدشت8', '1111111111', 'تهران', 'بانک ملت', '8888888888', '8888888888888888', 'IR123456789321456789033145', '2025-12-22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-10-26 12:58:31', '2025-10-26 12:59:32'),
(9, '9', '9999999999', 'حسین', 'حسینی', 'محمدرضا', '09999999999', '09999999999', '1971-03-21', '2011-03-21', 'تعلیق', 'تهران', 'تهران', 'AB-', 'فوق لیسانس', 'تهران9', '9999999999', 'تهران', 'بانک صادرات ایران', '999999999999', '9999999999999999', 'IR123456789321459989032145', '2021-03-21', '2026-03-21', '987654', 'متاهل', 'هلال احمر و...', 'کارت پایان خدمت', 'کارمند', '02199999999', 'testemail2@gmail.com', '/upload/1.jpg', '2025-10-26 13:09:42', '2025-10-26 13:10:18');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `rollcalluser`
--
ALTER TABLE `rollcalluser`
  ADD PRIMARY KEY (`RollcallUserID`),
  ADD UNIQUE KEY `unique_Rollcalluser` (`ClassID`,`UserID`,`RollcallUserDate`);

--
-- Indexes for table `sitesettings`
--
ALTER TABLE `sitesettings`
  ADD PRIMARY KEY (`SettingKey`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rollcalluser`
--
ALTER TABLE `rollcalluser`
  MODIFY `RollcallUserID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reportall`
--
ALTER TABLE `reportall`
  ADD CONSTRAINT `fk_reportall_class` FOREIGN KEY (`class_id`) REFERENCES `class` (`ClassID`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;