<?php

// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
// Start session and includes
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

require_once '../config/database.php';

// Include jdf for date conversion
require_once 'jdf.php';

// Initialize variables
$selected_teen = $_GET['teen_id'] ?? '';
$selected_status = $_GET['status'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// دریافت اطلاعات دوره از پارامترهای GET (اگر وجود داشته باشد)
$selected_class_id = $_GET['class_id'] ?? '';
$selected_class_name = '';

// اگر class_id مشخص شده باشد، نام دوره را دریافت کنید
if ($selected_class_id) {
    $class_stmt = $conn->prepare("SELECT ClassName FROM `Class` WHERE ClassID = ?");
    $class_stmt->bind_param('i', $selected_class_id);
    $class_stmt->execute();
    $class_result = $class_stmt->get_result();
    if ($class_result->num_rows > 0) {
        $class_data = $class_result->fetch_assoc();
        $selected_class_name = $class_data['ClassName'];
    }
    $class_stmt->close();
}

// تاریخ پیش‌فرض (امروز به شمسی)
$currentShamsiDate = gregorianToShamsi(date('Y-m-d'));
$currentParts = explode('/', $currentShamsiDate);
$currentYear = $currentParts[0] ?? '1400';
$currentMonth = $currentParts[1] ?? '01';

// Year and month selection (Jalali) - default to current month and year
$selected_year = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$selected_month = isset($_GET['month']) ? str_pad((int)$_GET['month'], 2, '0', STR_PAD_LEFT) : $currentMonth;

// Generate Jalali years (1400-1420)
$jalali_years = [];
for ($year = 1400; $year <= 1420; $year++) {
    $jalali_years[] = $year;
}

// Jalali months
$jalali_months = [
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

// Convert Jalali to Gregorian for database query
function jalali_to_gregorian_for_report($j_y, $j_m) {
    // تبدیل پارامترها به integer
    $j_y = (int)$j_y;
    $j_m = (int)$j_m;
    
    // تبدیل ماه و سال شمسی به میلادی
    // اولین روز ماه شمسی را به میلادی تبدیل می‌کنیم
    $g_date = jalali_to_gregorian($j_y, $j_m, 1);
    return $g_date[0] . '-' . str_pad($g_date[1], 2, '0', STR_PAD_LEFT);
}

// تبدیل تاریخ انتخابی به میلادی
$selected_month_gregorian = jalali_to_gregorian_for_report($selected_year, $selected_month);

// Get all teens with search and pagination - ORDER BY TeenSysCode DESC
$teens = [];
$total_teens = 0;

// Build query for teens - مرتب‌سازی بر اساس کدسیستمی از آخر به اول
$teen_query = "SELECT SQL_CALC_FOUND_ROWS TeenID, TeenName, TeenFamily, TeenSysCode, TeenMelli FROM teen WHERE TeenStatus = 'عادی'";
$count_query = "SELECT COUNT(*) as total FROM teen WHERE TeenStatus = 'عادی'";

$params = [];
$types = "";

if ($search_query) {
    $teen_query .= " AND (TeenSysCode LIKE ? OR TeenMelli LIKE ? OR TeenName LIKE ? OR TeenFamily LIKE ?)";
    $count_query .= " AND (TeenSysCode LIKE ? OR TeenMelli LIKE ? OR TeenName LIKE ? OR TeenFamily LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_fill(0, 4, $search_param);
    $types = "ssss";
}

// مرتب‌سازی بر اساس کدسیستمی از آخر به اول
$teen_query .= " ORDER BY CAST(TeenSysCode AS UNSIGNED) DESC LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;
$types .= "ii";

$teen_stmt = $conn->prepare($teen_query);

if ($params) {
    $teen_stmt->bind_param($types, ...$params);
}

$teen_stmt->execute();
$teen_result = $teen_stmt->get_result();

while ($row = $teen_result->fetch_assoc()) {
    $teens[$row['TeenID']] = $row;
}

// Get total count for pagination
if ($search_query) {
    $count_stmt = $conn->prepare($count_query);
    $search_param = "%$search_query%";
    $count_stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
} else {
    $count_stmt = $conn->prepare($count_query);
}

$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

$teen_stmt->close();
$count_stmt->close();

// Get rollcallteen statistics
$rollcallteen_stats = [];
$rollcallteen_details = [];

// اگر teen_id انتخاب شده باشد، فقط اطلاعات همان کاربر را بگیر
if ($selected_teen) {
    // فقط یک کاربر خاص
    $teen_ids = [$selected_teen];
} else {
    // همه کاربران
    $teen_ids = array_keys($teens);
}

// فقط در صورتی که فیلتر اعمال شده باشد (سال و ماه انتخاب شده باشند) آمار را بگیر
$show_report = false;
if (!empty($teen_ids) && isset($_GET['year']) && isset($_GET['month'])) {
    $show_report = true;
    
    $placeholders = str_repeat('?,', count($teen_ids) - 1) . '?';
    
    // ساخت کوئری بر اساس اینکه آیا دوره انتخاب شده یا نه
    if ($selected_class_id) {
        // اگر دوره انتخاب شده باشد، فقط نوجوانان آن دوره را نمایش بده
        $query = "
            SELECT 
                t.TeenID,
                t.TeenName,
                t.TeenFamily,
                t.TeenSysCode,
                t.TeenMelli,
                a.Status,
                COUNT(a.Status) as status_count,
                GROUP_CONCAT(DATE_FORMAT(a.RollcallteenDate, '%Y-%m-%d') ORDER BY a.RollcallteenDate) as dates
            FROM teen t
            LEFT JOIN rollcallteen a ON t.TeenID = a.TeenID 
                AND DATE_FORMAT(a.RollcallteenDate, '%Y-%m') = ?
                AND a.ClassID = ?
            WHERE t.TeenID IN ($placeholders)
            GROUP BY t.TeenID, t.TeenName, t.TeenFamily, t.TeenSysCode, t.TeenMelli, a.Status
            ORDER BY CAST(t.TeenSysCode AS UNSIGNED) DESC
        ";
        
        $params = array_merge([$selected_month_gregorian, $selected_class_id], $teen_ids);
        $types = "si" . str_repeat("i", count($teen_ids));
    } else {
        // اگر دوره انتخاب نشده باشد، همه نوجوانان را نمایش بده
        $query = "
            SELECT 
                t.TeenID,
                t.TeenName,
                t.TeenFamily,
                t.TeenSysCode,
                t.TeenMelli,
                a.Status,
                COUNT(a.Status) as status_count,
                GROUP_CONCAT(DATE_FORMAT(a.RollcallteenDate, '%Y-%m-%d') ORDER BY a.RollcallteenDate) as dates
            FROM teen t
            LEFT JOIN rollcallteen a ON t.TeenID = a.TeenID 
                AND DATE_FORMAT(a.RollcallteenDate, '%Y-%m') = ?
            WHERE t.TeenID IN ($placeholders)
            GROUP BY t.TeenID, t.TeenName, t.TeenFamily, t.TeenSysCode, t.TeenMelli, a.Status
            ORDER BY CAST(t.TeenSysCode AS UNSIGNED) DESC
        ";
        
        $params = array_merge([$selected_month_gregorian], $teen_ids);
        $types = "s" . str_repeat("i", count($teen_ids));
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $teen_id = $row['TeenID'];
        
        if (!isset($rollcallteen_stats[$teen_id])) {
            $rollcallteen_stats[$teen_id] = [
                'TeenName' => $row['TeenName'],
                'TeenFamily' => $row['TeenFamily'],
                'TeenSysCode' => $row['TeenSysCode'],
                'TeenMelli' => $row['TeenMelli'],
                'present' => 0,
                'absent' => 0,
                'excused' => 0,
                'total' => 0
            ];
        }
        
        if ($row['Status']) {
            $rollcallteen_stats[$teen_id][$row['Status']] = (int)$row['status_count'];
            $rollcallteen_stats[$teen_id]['total'] += (int)$row['status_count'];
            
            // Store dates for details
            if ($row['dates']) {
                $dates = explode(',', $row['dates']);
                foreach ($dates as $date) {
                    $rollcallteen_details[$teen_id][$row['Status']][] = $date;
                }
            }
        }
    }
    $stmt->close();
}

// If specific status is selected, get detailed dates
$status_dates = [];
if ($selected_teen && $selected_status) {
    $detail_stmt = $conn->prepare("
        SELECT RollcallteenDate 
        FROM rollcallteen 
        WHERE TeenID = ? AND Status = ? AND DATE_FORMAT(RollcallteenDate, '%Y-%m') = ?
        ORDER BY RollcallteenDate
    ");
    $detail_stmt->bind_param("iss", $selected_teen, $selected_status, $selected_month_gregorian);
    $detail_stmt->execute();
    $detail_result = $detail_stmt->get_result();
    
    while ($row = $detail_result->fetch_assoc()) {
        $status_dates[] = $row['RollcallteenDate'];
    }
    $detail_stmt->close();
}

// اگر teen_id انتخاب شده اما در آمار نیست، اطلاعات آن را جداگانه بگیر
if ($selected_teen && !isset($rollcallteen_stats[$selected_teen])) {
    $single_teen_stmt = $conn->prepare("
        SELECT TeenID, TeenName, TeenFamily, TeenSysCode, TeenMelli 
        FROM teen 
        WHERE TeenID = ? AND TeenStatus = 'عادی'
    ");
    $single_teen_stmt->bind_param("i", $selected_teen);
    $single_teen_stmt->execute();
    $single_teen_result = $single_teen_stmt->get_result();
    
    if ($single_teen_row = $single_teen_result->fetch_assoc()) {
        $rollcallteen_stats[$selected_teen] = [
            'TeenName' => $single_teen_row['TeenName'],
            'TeenFamily' => $single_teen_row['TeenFamily'],
            'TeenSysCode' => $single_teen_row['TeenSysCode'],
            'TeenMelli' => $single_teen_row['TeenMelli'],
            'present' => 0,
            'absent' => 0,
            'excused' => 0,
            'total' => 0
        ];
    }
    $single_teen_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارش حضور و غیاب نوجوانان</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .report-container {
            margin-top: 100px;
            margin-bottom: 50px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #4a6cf7;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #4a6cf7;
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .status-badge:hover {
            transform: scale(1.05);
        }
        
        .status-present {
            background: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .status-excused {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }
        
        .status-active {
            box-shadow: 0 0 0 3px rgba(74, 108, 247, 0.3);
        }
        
        .date-list {
            max-height: 300px;
            overflow-y: auto;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
        }
        
        .date-item {
            background: white;
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 5px;
            border-left: 3px solid #4a6cf7;
            font-family: 'Sahel', sans-serif;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(74, 108, 247, 0.05);
        }
        
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
        }
        
        .page-header .lead {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
        }
        
        .pagination .page-link {
            color: #4a6cf7;
            border: 1px solid #dee2e6;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #4a6cf7;
            border-color: #4a6cf7;
            color: white;
        }
        
        .pagination .page-item .page-link {
            background-color: white;
        }
        
        .pagination .page-item.active .page-link {
            background-color: #4a6cf7;
            border-color: #4a6cf7;
            color: white;
        }
        
        .pagination .page-link:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #4a6cf7;
        }
        
        /* هدر جدول با رنگ مشکی */
        .table-dark-custom {
            background-color: #000000 !important;
            color: white !important;
        }
        
        .table-dark-custom th {
            background-color: #000000 !important;
            border-color: #444444 !important;
            color: white !important;
            font-weight: 600;
        }
        
        .teen-details-header {
            background: linear-gradient(135deg, #4a6cf7, #6a11cb);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .back-button {
            margin-bottom: 20px;
        }
        
        .filter-row {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* استایل‌های جدید برای فیلترها در یک خط */
        .compact-form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }
        
        .form-group-compact {
            flex: 1;
            min-width: 120px;
        }
        
        .form-group-compact-search {
            flex: 2;
            min-width: 300px;
        }
        
        .compact-select {
            width: 100%;
        }
        
        .no-data-message {
            text-align: center;
            padding: 50px 20px;
            color: #6c757d;
        }
        
        .no-data-message i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        
        @media (max-width: 768px) {
            .report-container {
                margin-top: 80px;
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .compact-form-row {
                flex-direction: column;
            }
            
            .form-group-compact,
            .form-group-compact-search {
                min-width: 100%;
            }
        }
    </style>
</head>
<body>
<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
include __DIR__ . '/header.php'; ?>
    <div class="report-container container">
        
        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($selected_teen): ?>
            <!-- نمایش جزئیات یک کاربر خاص -->
            <div class="back-button">
                <a href="reportteen.php?<?php 
                    
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo http_build_query([
                        'year' => $selected_year,
                        'month' => $selected_month,
                        'search' => $search_query,
                        'page' => $page
                    ]); 
                ?>" class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-arrow-right"></i> بازگشت به لیست همه
                </a>
            </div>
            
            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (isset($rollcallteen_stats[$selected_teen])): 
                $teen_stats = $rollcallteen_stats[$selected_teen];
            ?>
                <div class="teen-details-header">
    <h2>
        <i class="fas fa-user"></i>
        جزئیات حضور و غیاب 
        <?php 
        // بررسی دسترسی کاربر
        require_once __DIR__ . '/check_access.php';
        requireAccess(basename(__FILE__));
        echo htmlspecialchars($teen_stats['TeenFamily'] . ' ' . $teen_stats['TeenName']); ?>
    </h2>
    <p class="mb-0">
        کدسیستمی: <?php 
        // بررسی دسترسی کاربر
        require_once __DIR__ . '/check_access.php';
        requireAccess(basename(__FILE__));
        echo $teen_stats['TeenSysCode']; ?> | 
        کد ملی: <?php 
        // بررسی دسترسی کاربر
        require_once __DIR__ . '/check_access.php';
        requireAccess(basename(__FILE__));
        echo $teen_stats['TeenMelli']; ?> |
        ماه: <?php 
        // بررسی دسترسی کاربر
        require_once __DIR__ . '/check_access.php';
        requireAccess(basename(__FILE__));
        echo $jalali_months[$selected_month] . ' ' . $selected_year; ?>
        <?php if ($selected_class_name): ?>
            | دوره: <?php 
            // بررسی دسترسی کاربر
            require_once __DIR__ . '/check_access.php';
            requireAccess(basename(__FILE__));
            echo htmlspecialchars($selected_class_name); ?>
        <?php endif; ?>
    </p>
</div>

                <!-- آمار کاربر -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <div class="stat-number"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $teen_stats['present']; ?></div>
                            <div class="stat-label">روز حضور</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <div class="stat-number"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $teen_stats['absent']; ?></div>
                            <div class="stat-label">روز غیبت</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="stat-card text-center">
                            <div class="stat-number"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $teen_stats['excused']; ?></div>
                            <div class="stat-label">روز مرخصی</div>
                        </div>
                    </div>
                </div>

                <!-- جزئیات روزهای حضور -->
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($teen_stats['present'] > 0): ?>
                    <div class="filter-section">
                        <h4 class="text-success">
                            <i class="fas fa-calendar-check"></i>
                            روزهای حضور (<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $teen_stats['present']; ?> روز)
                        </h4>
                        <div class="date-list">
                            <?php 
                            
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
$present_dates = $rollcallteen_details[$selected_teen]['present'] ?? [];
                            foreach ($present_dates as $date): 
                                list($g_y, $g_m, $g_d) = explode('-', $date);
                                $j_date = gregorian_to_jalali($g_y, $g_m, $g_d);
                                $persian_date = $j_date[0] . '/' . str_pad($j_date[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($j_date[2], 2, '0', STR_PAD_LEFT);
                            ?>
                                <div class="date-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $persian_date; ?>
                                </div>
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endforeach; ?>
                        </div>
                    </div>
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>

                <!-- جزئیات روزهای غیبت -->
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($teen_stats['absent'] > 0): ?>
                    <div class="filter-section">
                        <h4 class="text-danger">
                            <i class="fas fa-calendar-times"></i>
                            روزهای غیبت (<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $teen_stats['absent']; ?> روز)
                        </h4>
                        <div class="date-list">
                            <?php 
                            
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
$absent_dates = $rollcallteen_details[$selected_teen]['absent'] ?? [];
                            foreach ($absent_dates as $date): 
                                list($g_y, $g_m, $g_d) = explode('-', $date);
                                $j_date = gregorian_to_jalali($g_y, $g_m, $g_d);
                                $persian_date = $j_date[0] . '/' . str_pad($j_date[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($j_date[2], 2, '0', STR_PAD_LEFT);
                            ?>
                                <div class="date-item">
                                    <i class="fas fa-times-circle text-danger"></i>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $persian_date; ?>
                                </div>
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endforeach; ?>
                        </div>
                    </div>
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>

                <!-- جزئیات روزهای مرخصی -->
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($teen_stats['excused'] > 0): ?>
                    <div class="filter-section">
                        <h4 class="text-warning">
                            <i class="fas fa-calendar-minus"></i>
                            روزهای مرخصی (<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $teen_stats['excused']; ?> روز)
                        </h4>
                        <div class="date-list">
                            <?php 
                            
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
$excused_dates = $rollcallteen_details[$selected_teen]['excused'] ?? [];
                            foreach ($excused_dates as $date): 
                                list($g_y, $g_m, $g_d) = explode('-', $date);
                                $j_date = gregorian_to_jalali($g_y, $g_m, $g_d);
                                $persian_date = $j_date[0] . '/' . str_pad($j_date[1], 2, '0', STR_PAD_LEFT) . '/' . str_pad($j_date[2], 2, '0', STR_PAD_LEFT);
                            ?>
                                <div class="date-item">
                                    <i class="fas fa-minus-circle text-warning"></i>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $persian_date; ?>
                                </div>
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endforeach; ?>
                        </div>
                    </div>
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>

            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
else: ?>
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
                    اطلاعاتی برای این نوجوان یافت نشد.
                </div>
            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>

        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
else: ?>
            <div class="container" style="margin-top:100px; margin-bottom:40px; text-align:right;">
    <div class="content-box">
                <div class="d-flex justify-content-start align-items-center mb-3">
            <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                <span class="me-2">بستن</span>
                <span aria-hidden="true" class="fs-5">×</span>
            </a>
            <h2 class="mb-0">گزارش حضور و غیاب نوجوانان</h2>
        </div>
            <!-- Filters -->
            <div class="filter-row">
                <form method="GET" class="compact-form-row">
                    <div class="form-group-compact">
                        <label class="form-label">سال:</label>
                        <select name="year" class="form-select compact-select" required>
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
foreach ($jalali_years as $year): ?>
                                <option value="<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $year; ?>" <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $selected_year == $year ? 'selected' : ''; ?>>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $year; ?>
                                </option>
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group-compact">
                        <label class="form-label">ماه:</label>
                        <select name="month" class="form-select compact-select" required>
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
foreach ($jalali_months as $key => $month): ?>
                                <option value="<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $key; ?>" <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $selected_month == $key ? 'selected' : ''; ?>>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $month; ?>
                                </option>
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group-compact-search">
                        <label class="form-label">جستجو (کدسیستمی، کد ملی، نام):</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="جستجو..." value="<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> اعمال فیلتر
                            </button>
                            <a href="reportteen.php" class="btn btn-primary" style="background-color:#ff0000;">
                                <i class="fas fa-times"></i> حذف فیلتر
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Main Report Table - فقط وقتی فیلتر اعمال شده باشد نمایش داده شود -->
            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($show_report): ?>
                <div class="filter-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="fas fa-users"></i>
                            لیست نوجوانان (مرتب‌شده بر اساس کدسیستمی - جدیدترین اول)
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($search_query): ?>
                                <span class="badge bg-primary">نتایج جستجو: "<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($search_query); ?>"</span>
                            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>
                        </h5>
                        <span class="text-muted">نمایش <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo count($rollcallteen_stats); ?> از <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $total_rows; ?> نوجوان</span>
                    </div>
                    
                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (!empty($rollcallteen_stats)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark-custom">
                                    <tr>
                                        <th>#</th>
                                        <th>نام و نام خانوادگی</th>
                                        <th>کدسیستمی</th>
                                        <th>کد ملی</th>
                                        <th>حضور</th>
                                        <th>غیبت</th>
                                        <th>مرخصی</th>
                                        <th>جمع کل</th>
                                        <th>عملیات</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
$counter = ($page - 1) * $per_page + 1; ?>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
foreach ($rollcallteen_stats as $teen_id => $stats): ?>
                                        <tr>
                                            <td><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $counter++; ?></td>
                                            <td>
                                                <strong><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($stats['TeenFamily'] . ' ' . $stats['TeenName']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-secondary"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $stats['TeenSysCode']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $stats['TeenMelli']; ?></span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-present">
                                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $stats['present']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-absent">
                                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $stats['absent']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-excused">
                                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $stats['excused']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $stats['total']; ?></strong>
                                            </td>
                                            <td>
                                                <a href="reportteen.php?<?php 
                                                    
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo http_build_query([
                                                        'teen_id' => $teen_id,
                                                        'year' => $selected_year,
                                                        'month' => $selected_month,
                                                        'search' => $search_query,
                                                        'page' => $page
                                                    ]); 
                                                ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-chart-pie"></i> جزئیات
                                                </a>
                                            </td>
                                        </tr>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php 
                                            
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo http_build_query([
                                                'year' => $selected_year,
                                                'month' => $selected_month,
                                                'search' => $search_query,
                                                'page' => $page - 1
                                            ]); 
                                        ?>">
                                            <i class="fas fa-chevron-right"></i> قبلی
                                        </a>
                                    </li>
                                    
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php 
                                                
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo http_build_query([
                                                    'year' => $selected_year,
                                                    'month' => $selected_month,
                                                    'search' => $search_query,
                                                    'page' => $i
                                                ]); 
                                            ?>"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $i; ?></a>
                                        </li>
                                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endfor; ?>
                                    
                                    <li class="page-item <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php 
                                            
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo http_build_query([
                                                'year' => $selected_year,
                                                'month' => $selected_month,
                                                'search' => $search_query,
                                                'page' => $page + 1
                                            ]); 
                                        ?>">
                                            بعدی <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>
                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
else: ?>
                        <div class="no-data-message">
                            <i class="fas fa-chart-bar"></i>
                            <h4>داده‌ای برای نمایش وجود ندارد</h4>
                            <p>هیچ اطلاعات حضور و غیابی برای ماه <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $jalali_months[$selected_month] . ' ' . $selected_year; ?> یافت نشد.</p>
                        </div>
                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>
                </div>
            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
else: ?>
                <!-- پیام قبل از اعمال فیلتر -->
                <div class="filter-section text-center">
                    <div class="no-data-message">
                        <i class="fas fa-filter"></i>
                        <h4>لطفاً فیلترها را اعمال کنید</h4>
                        <p>برای مشاهده گزارش حضور و غیاب، سال و ماه مورد نظر را انتخاب کرده و دکمه "اعمال فیلتر" را بزنید.</p>
                        <p class="text-muted">سال و ماه پیش‌فرض بر اساس تاریخ امروز (<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $selected_year . '/' . $jalali_months[$selected_month]; ?>) تنظیم شده است.</p>
                    </div>
                </div>
            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>
        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>
    </div>

</div>
<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
include 'footer.php'; ?>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>