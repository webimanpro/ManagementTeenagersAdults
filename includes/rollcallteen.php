<?php

// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../config/database.php';
require_once 'jdf.php';

// بررسی لاگین بودن کاربر
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// تابع بررسی اینکه آیا کاربر مدیر است


// تابع تبدیل تاریخ شمسی به میلادی
function shamsiToGregorian($shamsiDate) {
    if (empty($shamsiDate)) return null;
    
    $parts = explode('/', $shamsiDate);
    if (count($parts) !== 3) return null;
    
    list($year, $month, $day) = $parts;
    
    $timestamp = jalali_to_gregorian($year, $month, $day);
    if ($timestamp) {
        return $timestamp[0] . '-' . sprintf('%02d', $timestamp[1]) . '-' . sprintf('%02d', $timestamp[2]);
    }
    
    return null;
}

// تابع تبدیل تاریخ میلادی به شمسی
function gregorianToShamsi($gregorianDate) {
    if (empty($gregorianDate) || $gregorianDate == '0000-00-00') return '';
    
    $timestamp = strtotime($gregorianDate);
    if ($timestamp === false) return '';
    
    $jdate = gregorian_to_jalali(date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp));
    return $jdate[0] . '/' . sprintf('%02d', $jdate[1]) . '/' . sprintf('%02d', $jdate[2]);
}

// توابع تبدیل تاریخ
function jalali_to_gregorian($j_y, $j_m, $j_d) {
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);
    
    $jy = $j_y - 979;
    $jm = $j_m - 1;
    $jd = $j_d - 1;

    $j_day_no = 365 * $jy + floor($jy / 33) * 8 + floor(($jy % 33 + 3) / 4);
    for ($i = 0; $i < $jm; ++$i)
        $j_day_no += $j_days_in_month[$i];

    $j_day_no += $jd;

    $g_day_no = $j_day_no + 79;

    $gy = 1600 + 400 * floor($g_day_no / 146097);
    $g_day_no = $g_day_no % 146097;

    $leap = true;
    if ($g_day_no >= 36525) {
        $g_day_no--;
        $gy += 100 * floor($g_day_no / 36524);
        $g_day_no = $g_day_no % 36524;

        if ($g_day_no >= 365)
            $g_day_no++;
        else
            $leap = false;
    }

    $gy += 4 * floor($g_day_no / 1461);
    $g_day_no %= 1461;

    if ($g_day_no >= 366) {
        $leap = false;
        $g_day_no--;
        $gy += floor($g_day_no / 365);
        $g_day_no = $g_day_no % 365;
    }

    for ($i = 0; $g_day_no >= $g_days_in_month[$i] + ($i == 1 && $leap); $i++)
        $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);
    $gm = $i + 1;
    $gd = $g_day_no + 1;

    return array($gy, $gm, $gd);
}

function gregorian_to_jalali($g_y, $g_m, $g_d) {
    $g_days_in_month = array(31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
    $j_days_in_month = array(31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29);

    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;

    $g_day_no = 365 * $gy + floor(($gy + 3) / 4) - floor(($gy + 99) / 100) + floor(($gy + 399) / 400);

    for ($i = 0; $i < $gm; ++$i)
        $g_day_no += $g_days_in_month[$i];
    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0)))
        $g_day_no++;
    $g_day_no += $gd;

    $j_day_no = $g_day_no - 79;

    $j_np = floor($j_day_no / 12053);
    $j_day_no = $j_day_no % 12053;

    $jy = 979 + 33 * $j_np + 4 * floor($j_day_no / 1461);
    $j_day_no %= 1461;

    if ($j_day_no >= 366) {
        $jy += floor(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }

    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
        $j_day_no -= $j_days_in_month[$i];
    $jm = $i + 1;
    $jd = $j_day_no + 1;

    return array($jy, $jm, $jd);
}

// ایجاد جدول حضور و غیاب اگر وجود ندارد
$conn->query("CREATE TABLE IF NOT EXISTS `rollcallteen` (
  `RollcallteenID` int(11) NOT NULL AUTO_INCREMENT,
  `ClassID` int(11) NOT NULL,
  `TeenID` int(11) NOT NULL,
  `RollcallteenDate` date NOT NULL,
  `RollcallteenDay` varchar(20) NOT NULL COMMENT 'نام روز هفته',
  `Status` enum('present','absent','excused') NOT NULL DEFAULT 'present',
  `Notes` text DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`RollcallteenID`),
  UNIQUE KEY `unique_Rollcallteen` (`ClassID`,`TeenID`,`RollcallteenDate`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$errors = [];
$success = '';
$classId = $_GET['class_id'] ?? 0;
$selectedYear = $_GET['year'] ?? '';
$selectedMonth = $_GET['month'] ?? '';
$selectedDay = $_GET['day'] ?? '';

// تاریخ پیش‌فرض (امروز به شمسی)
$currentShamsiDate = gregorianToShamsi(date('Y-m-d'));
$currentParts = explode('/', $currentShamsiDate);
$currentYear = $currentParts[0] ?? '1400';
$currentMonth = $currentParts[1] ?? '01';
$currentDay = $currentParts[2] ?? '01';

// تنظیم مقادیر پیش‌فرض
if (!$selectedYear) $selectedYear = $currentYear;
if (!$selectedMonth) $selectedMonth = $currentMonth;
if (!$selectedDay) $selectedDay = $currentDay;

// ساخت تاریخ کامل
$rollcallteenShamsiDate = $selectedYear . '/' . $selectedMonth . '/' . $selectedDay;
$RollcallteenDate = shamsiToGregorian($rollcallteenShamsiDate) ?? date('Y-m-d');

// دریافت اطلاعات دوره
$classData = [];
$teens = [];
$RollcallteenData = [];

if ($classId && $selectedYear && $selectedMonth && $selectedDay) {
    // دریافت اطلاعات دوره
    $stmt = $conn->prepare("SELECT * FROM `Class` WHERE ClassID = ?");
    $stmt->bind_param('i', $classId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $classData = $result->fetch_assoc();
        
        // دریافت لیست نوجوانان دوره
        $classUsers = json_decode($classData['CalssUsers'] ?? '[]', true);
        $teenIds = [];
        
        foreach ($classUsers as $user) {
            if ($user['type'] === 'teen') {
                $teenIds[] = $user['id'];
            }
        }
        
        if (!empty($teenIds)) {
            $placeholders = str_repeat('?,', count($teenIds) - 1) . '?';
            $stmt = $conn->prepare("SELECT * FROM `Teen` WHERE TeenID IN ($placeholders) ORDER BY TeenFamily, TeenName");
            $stmt->bind_param(str_repeat('i', count($teenIds)), ...$teenIds);
            $stmt->execute();
            $teens = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        }
        
        // دریافت اطلاعات حضور و غیاب برای تاریخ انتخاب شده
        $stmt = $conn->prepare("SELECT * FROM `rollcallteen` WHERE ClassID = ? AND RollcallteenDate = ?");
        $stmt->bind_param('is', $classId, $RollcallteenDate);
        $stmt->execute();
        $rollcallteenResult = $stmt->get_result();
        
        while ($row = $rollcallteenResult->fetch_assoc()) {
            $RollcallteenData[$row['TeenID']] = $row;
        }
    } else {
        $errors[] = 'دوره مورد نظر یافت نشد.';
    }
    $stmt->close();
}

// پردازش ارسال فرم حضور و غیاب
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_rollcallteen'])) {
    $classId = $_POST['class_id'] ?? 0;
    $selectedYear = $_POST['year'] ?? '';
    $selectedMonth = $_POST['month'] ?? '';
    $selectedDay = $_POST['day'] ?? '';
    
    if (empty($classId)) {
        $errors[] = 'لطفاً یک دوره انتخاب کنید.';
    }
    
    if (empty($selectedYear) || empty($selectedMonth) || empty($selectedDay)) {
        $errors[] = 'لطفاً سال، ماه و روز را انتخاب کنید.';
    }
    
    // ساخت تاریخ کامل
    $rollcallteenShamsiDate = $selectedYear . '/' . $selectedMonth . '/' . $selectedDay;
    $rollcallteenDate = shamsiToGregorian($rollcallteenShamsiDate);
    if (!$rollcallteenDate) {
        $errors[] = 'تاریخ وارد شده معتبر نیست.';
    }
    
    if (!$errors) {
        // نام روز هفته
        $dayNames = ['Saturday' => 'شنبه', 'Sunday' => 'یکشنبه', 'Monday' => 'دوشنبه', 
                    'Tuesday' => 'سه شنبه', 'Wednesday' => 'چهارشنبه', 
                    'Thursday' => 'پنجشنبه', 'Friday' => 'جمعه'];
        $dayName = $dayNames[date('l', strtotime($RollcallteenDate))];
        
        $conn->begin_transaction();
        
        try {
            foreach ($_POST['rollcallteen'] as $teenId => $status) {
                $notes = $_POST['notes'][$teenId] ?? '';
                
                // بررسی آیا رکورد قبلی وجود دارد
                $checkStmt = $conn->prepare("SELECT RollcallteenID FROM `rollcallteen` WHERE ClassID = ? AND TeenID = ? AND RollcallteenDate = ?");
                $checkStmt->bind_param('iis', $classId, $teenId, $RollcallteenDate);
                $checkStmt->execute();
                $exists = $checkStmt->get_result()->num_rows > 0;
                $checkStmt->close();
                
                if ($exists) {
                    // به روزرسانی رکورد موجود
                    $stmt = $conn->prepare("UPDATE `rollcallteen` SET Status = ?, Notes = ? WHERE ClassID = ? AND TeenID = ? AND RollcallteenDate = ?");
                    $stmt->bind_param('ssiis', $status, $notes, $classId, $teenId, $RollcallteenDate);
                } else {
                    // درج رکورد جدید
                    $stmt = $conn->prepare("INSERT INTO `rollcallteen` (ClassID, TeenID, RollcallteenDate, RollcallteenDay, Status, Notes) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('iissss', $classId, $teenId, $RollcallteenDate, $dayName, $status, $notes);
                }
                
                $stmt->execute();
                $stmt->close();
            }
            
            $conn->commit();
            $success = 'حضور و غیاب با موفقیت ثبت شد.';
            
            // ریدایرکت به صفحه اصلی بعد از ثبت موفق
            header('Location: rollcallteen.php?success=1&class_id=' . $classId . '&year=' . $selectedYear . '&month=' . $selectedMonth . '&day=' . $selectedDay);
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'خطا در ثبت حضور و غیاب: ' . $e->getMessage();
        }
    }
}

// دریافت لیست دوره‌ها
$classes = $conn->query("SELECT ClassID, ClassName FROM `Class` ORDER BY ClassName");

// تولید لیست سال‌ها (از 1390 تا 1410)
$years = [];
for ($i = 1390; $i <= 1410; $i++) {
    $years[] = $i;
}

// لیست ماه‌ها
$months = [
    '01' => 'فروردین',
    '02' => 'اردیبهشت',
    '03' => 'خرداد',
    '04' => 'تیر',
    '05' => 'مرداد',
    '06' => 'شهریور',
    '07' => 'مهر',
    '08' => 'آبان',
    '09' => 'آذر',
    '10' => 'دی',
    '11' => 'بهمن',
    '12' => 'اسفند'
];

// لیست روزها
$days = [];
for ($i = 1; $i <= 31; $i++) {
    $days[sprintf('%02d', $i)] = sprintf('%02d', $i);
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>حضور و غیاب نوجوانان</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        .date-selectors {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }
        .form-control:focus, .form-select:focus {
            border-color: #4a6cf7;
            box-shadow: 0 0 0 0.2rem rgba(74, 108, 247, 0.25);
        }
        .rollcallteen-table {
            margin-top: 20px;
        }
        .btn-group-sm .btn {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .report-btn-container {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body class="class-management-page">
<?php include __DIR__ . '/header.php'; ?>

<div class="container main-container">
    <div class="content-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex justify-content-start align-items-center mb-3">
                <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                    <span class="me-2">بستن</span>
                    <span aria-hidden="true" class="fs-5">×</span>
                </a>
                <h2 class="mb-0">حضور و غیاب نوجوانان</h2>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i> حضور و غیاب با موفقیت ثبت شد.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errors): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill"></i> 
                <?php echo implode('<br>', $errors); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- فرم انتخاب دوره و تاریخ -->
        <form method="get" class="mb-4">
            <div class="date-selectors">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">انتخاب دوره</label>
                        <select name="class_id" class="form-select" required>
                            <option value="">-- انتخاب دوره --</option>
                            <?php while ($class = $classes->fetch_assoc()): ?>
                                <option value="<?php echo $class['ClassID']; ?>" 
                                    <?php echo $classId == $class['ClassID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['ClassName']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">سال</label>
                        <select name="year" class="form-select" required>
                            <option value="">-- انتخاب سال --</option>
                            <?php foreach ($years as $year): ?>
                                <option value="<?php echo $year; ?>" 
                                    <?php echo $selectedYear == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">ماه</label>
                        <select name="month" class="form-select" required>
                            <option value="">-- انتخاب ماه --</option>
                            <?php foreach ($months as $key => $month): ?>
                                <option value="<?php echo $key; ?>" 
                                    <?php echo $selectedMonth == $key ? 'selected' : ''; ?>>
                                    <?php echo $month; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label class="form-label">روز</label>
                        <select name="day" class="form-select" required>
                            <option value="">-- انتخاب روز --</option>
                            <?php foreach ($days as $key => $day): ?>
                                <option value="<?php echo $key; ?>" 
                                    <?php echo $selectedDay == $key ? 'selected' : ''; ?>>
                                    <?php echo $day; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-6">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-2"></i>نمایش لیست نوجوانان
                        </button>
                <!-- دکمه مشاهده گزارش نوجوانان - فقط برای مدیران -->
                <?php if (isAdmin()): ?>
                        <a href="reportteen.php?class_id=<?php echo $classId; ?>&year=<?php echo $selectedYear; ?>&month=<?php echo $selectedMonth; ?>" 
                           class="btn btn-info col-6" 
                           target="_blank">
                            <i class="bi bi-bar-chart me-2"></i>مشاهده گزارش نوجوانان
                        </a>
                    <?php endif; ?>
					</div>
            </div>
        </form>

        <?php if ($classId && $selectedYear && $selectedMonth && $selectedDay && !empty($teens)): ?>
        <!-- فرم حضور و غیاب -->
        <form method="post" id="rollcallteen-form">
            <input type="hidden" name="submit_rollcallteen" value="1">
            <input type="hidden" name="class_id" value="<?php echo $classId; ?>">
            <input type="hidden" name="year" value="<?php echo $selectedYear; ?>">
            <input type="hidden" name="month" value="<?php echo $selectedMonth; ?>">
            <input type="hidden" name="day" value="<?php echo $selectedDay; ?>">
            
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-people-fill me-2"></i>
                    لیست نوجوانان دوره: <strong><?php echo htmlspecialchars($classData['ClassName']); ?></strong>
                    - تاریخ: <strong><?php echo $selectedYear . '/' . $selectedMonth . '/' . $selectedDay; ?></strong>
                </div>
                
                <div class="card-body">
                    <div class="table-responsive rollcallteen-table">
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="50" class="text-center">#</th>
                                    <th>نام و نام خانوادگی</th>
                                    <th width="120" class="text-center">کدسیستمی</th>
                                    <th width="120" class="text-center">کد ملی</th>
                                    <th width="200" class="text-center">وضعیت حضور</th>
                                    <th width="250">توضیحات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teens as $index => $teen): 
                                    $rollcallteen = $RollcallteenData[$teen['TeenID']] ?? [];
                                    $currentStatus = $rollcallteen['Status'] ?? 'absent';
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $index + 1; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($teen['TeenName'] . ' ' . $teen['TeenFamily']); ?></strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-secondary"><?php echo htmlspecialchars($teen['TeenSysCode']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-muted"><?php echo htmlspecialchars($teen['TeenMelli']); ?></span>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <input type="radio" class="btn-check" name="rollcallteen[<?php echo $teen['TeenID']; ?>]" 
                                                   id="present_<?php echo $teen['TeenID']; ?>" value="present" 
                                                   <?php echo $currentStatus === 'present' ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-success" for="present_<?php echo $teen['TeenID']; ?>">
                                                <i class="bi bi-check-lg"></i> حاضر
                                            </label>

                                            <input type="radio" class="btn-check" name="rollcallteen[<?php echo $teen['TeenID']; ?>]" 
                                                   id="absent_<?php echo $teen['TeenID']; ?>" value="absent" 
                                                   <?php echo $currentStatus === 'absent' ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-danger" for="absent_<?php echo $teen['TeenID']; ?>">
                                                <i class="bi bi-x-lg"></i> غایب
                                            </label>

                                            <input type="radio" class="btn-check" name="rollcallteen[<?php echo $teen['TeenID']; ?>]" 
                                                   id="excused_<?php echo $teen['TeenID']; ?>" value="excused" 
                                                   <?php echo $currentStatus === 'excused' ? 'checked' : ''; ?>>
                                            <label class="btn btn-outline-warning" for="excused_<?php echo $teen['TeenID']; ?>">
                                                <i class="bi bi-clock"></i> مرخصی
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" name="notes[<?php echo $teen['TeenID']; ?>]" 
                                               class="form-control form-control-sm" 
                                               placeholder="توضیحات (اختیاری)"
                                               value="<?php echo htmlspecialchars($rollcallteen['Notes'] ?? ''); ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- آمار حضور و غیاب -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <i class="bi bi-graph-up me-2"></i>آمار حضور و غیاب
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 bg-success bg-opacity-10 stat-card">
                                                <h4 class="text-success" id="present-count">0</h4>
                                                <small class="text-muted">حاضرین</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 bg-danger bg-opacity-10 stat-card">
                                                <h4 class="text-danger" id="absent-count">0</h4>
                                                <small class="text-muted">غایبین</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border rounded p-3 bg-warning bg-opacity-10 stat-card">
                                                <h4 class="text-warning" id="excused-count">0</h4>
                                                <small class="text-muted">مرخصی</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-save me-2"></i>ثبت حضور و غیاب
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <?php elseif ($classId && $selectedYear && $selectedMonth && $selectedDay && empty($teens)): ?>
            <div class="alert alert-warning text-center">
                <i class="bi bi-exclamation-triangle me-2"></i>
                هیچ نوجوانی در این دوره ثبت نام نکرده است.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // محاسبه آمار
    function updateStatistics() {
        const presentCount = $('input[value="present"]:checked').length;
        const absentCount = $('input[value="absent"]:checked').length;
        const excusedCount = $('input[value="excused"]:checked').length;
        
        $('#present-count').text(presentCount);
        $('#absent-count').text(absentCount);
        $('#excused-count').text(excusedCount);
    }
    
    // به روزرسانی آمار هنگام تغییر وضعیت
    $(document).on('change', 'input[type="radio"]', function() {
        updateStatistics();
    });
    
    // محاسبه اولیه آمار
    updateStatistics();
});

// تابع تنظیم تاریخ امروز
function setTodayDate() {
    const today = new Date();
    const persianDate = new Date().toLocaleDateString('fa-IR');
    const parts = persianDate.split('/');
    
    if (parts.length === 3) {
        const year = parts[0];
        const month = parts[1].padStart(2, '0');
        const day = parts[2].padStart(2, '0');
        
        $('select[name="year"]').val(year);
        $('select[name="month"]').val(month);
        $('select[name="day"]').val(day);
    }
}

// اعتبارسنجی فرم قبل از ارسال
document.getElementById('rollcallteen-form')?.addEventListener('submit', function(e) {
    if (!confirm('آیا از ثبت حضور و غیاب اطمینان دارید؟')) {
        e.preventDefault();
    }
});
</script>
</body>
</html>