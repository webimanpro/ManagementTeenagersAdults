<?php
// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
// Start session and includes
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
// شروع output buffering برای جلوگیری از خطای TCPDF
ob_start();
// بررسی اینکه آیا درخواست export است
$is_export_request = isset($_POST['export_excel']) || isset($_POST['print_report']) || isset($_POST['export_pdf']);
// اگر درخواست export نیست، header را include می‌کنیم
if (!$is_export_request) {
    require_once 'header.php';
}
require_once '../config/database.php';
// Include jdf for date conversion
require_once 'jdf.php';
// Include TCPDF for PDF export
require_once '../assets/tcpdf/tcpdf.php';

// Initialize variables from POST or GET or SESSION
if (isset($_POST['export_excel']) || isset($_POST['print_report']) || isset($_POST['export_pdf'])) {
    $step = 5;
} else {
    $step = $_GET['step'] ?? ($_POST['step'] ?? 1);
}

// Initialize selected_users to true by default
$selected_users = true;

// اگر در مرحله اول هستیم و دکمه‌ای زده نشده، همه انتخاب‌ها را پاک کن
if ($step == 1 && empty($_POST) && empty($_GET)) {
    $selected_users = true;
    $report_type = '';
    $selected_year = '';
    $selected_month = '';
    $selected_class = '';
    $syscode_from = '';
    $syscode_to = '';
    $selected_fields = [];
    $header_desc = '';
    $footer_desc = '';
    $report_name = '';
    
    // همچنین sessionها را پاک کن
    unset(
        $_SESSION['selected_users'],
        $_SESSION['report_type'],
        $_SESSION['selected_year'],
        $_SESSION['selected_month'],
        $_SESSION['selected_class'],
        $_SESSION['syscode_from'],
        $_SESSION['syscode_to'],
        $_SESSION['selected_fields'],
        $_SESSION['header_desc'],
        $_SESSION['footer_desc'],
        $_SESSION['report_name']
    );
}

$saved_report_id = $_GET['saved_report'] ?? '';

// ابتدا مقادیر را از session بخوانیم (اگر وجود دارند)
$selected_users = $_SESSION['selected_users'] ?? true;
$report_type = $_SESSION['report_type'] ?? '';
$selected_year = $_SESSION['selected_year'] ?? '';
$selected_month = $_SESSION['selected_month'] ?? '';
$selected_class = $_SESSION['selected_class'] ?? '';
$syscode_from = $_SESSION['syscode_from'] ?? '';
$syscode_to = $_SESSION['syscode_to'] ?? '';
$selected_fields = $_SESSION['selected_fields'] ?? [];
$header_desc = $_SESSION['header_desc'] ?? '';
$footer_desc = $_SESSION['footer_desc'] ?? '';
$report_name = $_SESSION['report_name'] ?? '';

// حالا اگر مقادیر در POST وجود دارند، آن‌ها را جایگزین کنیم
if (isset($_POST['users'])) {
    $selected_users = true;
}
if (isset($_POST['report_type'])) {
    $report_type = $_POST['report_type'];
}
if (isset($_POST['year'])) {
    $selected_year = $_POST['year'];
}
if (isset($_POST['month'])) {
    $selected_month = $_POST['month'];
}
if (isset($_POST['class_id'])) {
    $selected_class = $_POST['class_id'];
}
if (isset($_POST['syscode_from'])) {
    $syscode_from = $_POST['syscode_from'];
}
if (isset($_POST['syscode_to'])) {
    $syscode_to = $_POST['syscode_to'];
}
if (isset($_POST['fields'])) {
    $selected_fields = $_POST['fields'];
}
if (isset($_POST['header_desc'])) {
    $header_desc = $_POST['header_desc'];
}
if (isset($_POST['footer_desc'])) {
    $footer_desc = $_POST['footer_desc'];
}
if (isset($_POST['report_name'])) {
    $report_name = $_POST['report_name'];
}

// Step 5: ذخیره گزارش
$save_report = isset($_POST['save_report']) ? true : false;

// تعریف فیلدهای موجود برای کاربران با نام‌های واقعی ستون‌ها
$users_fields = [
    'UserSysCode' => 'کدسیستمی',
    'UserMelli' => 'کدملی',
    'UserName' => 'نام',
    'UserFamily' => 'نام خانوادگی',
    'UserFather' => 'نام پدر',
    'UserMobile1' => 'موبایل1',
    'UserMobile2' => 'موبایل2',
    'UserPhone' => 'تلفن ثابت',
    'UserEmail' => 'ایمیل',
    'UserDateBirth' => 'تاریخ تولد',
    'UserRegDate' => 'تاریخ ثبت عادی',
    'UserActiveDate' => 'تاریخ ثبت فعال',
    'UserSuspendDate' => 'تاریخ ثبت تعلیق',
    'UserStatus' => 'وضعیت',
    'UserPlaceBirth' => 'محل تولد',
    'UserPlaceCerti' => 'محل صدور',
    'UserBloodType' => 'گروه خونی',
    'UserEducation' => 'وضعیت تحصیلی',
    'UserNumbersh' => 'شماره شناسنامه',
    'UserMaritalStatus' => 'وضعیت تاهل',
    'UserDutyStatus' => 'وضعیت خدمت',
    'UserJobWork' => 'شغل',
    'UserBankName' => 'نام بانک',
    'UserAccountNumber' => 'شماره حساب بانکی',
    'UserCardNumber' => 'شماره کارت بانکی',
    'UserShebaNumber' => 'شماره شبا بانکی',
    'UserCity' => 'شهر',
    'UserZipCode' => 'کدپستی',
    'UserAddress' => 'آدرس',
    'UserOtherActivity' => 'فعالیت های دیگر'
];

// فیلدهای پیشفرض
$default_fields = ['UserSysCode', 'UserMelli', 'UserName', 'UserFamily', 'UserFather'];

// تنظیم فیلدهای پیش‌فرض
if (empty($selected_fields)) {
    $selected_fields = $default_fields;
}

// تاریخ پیش‌فرض (امروز به شمسی)
$currentShamsiDate = gregorianToShamsi(date('Y-m-d'));
$currentParts = explode('/', $currentShamsiDate);
$currentYear = $currentParts[0] ?? '1400';
$currentMonth = $currentParts[1] ?? '01';

// تنظیم مقادیر پیش‌فرض
if (!$selected_year) $selected_year = $currentYear;
if (!$selected_month) $selected_month = $currentMonth;

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

// تابع تبدیل تاریخ میلادی به شمسی
function gregorianToShamsi($gregorianDate) {
    if (empty($gregorianDate) || $gregorianDate == '0000-00-00') return '';
    $timestamp = strtotime($gregorianDate);
    if ($timestamp === false) return '';
    $jdate = gregorian_to_jalali(date('Y', $timestamp), date('m', $timestamp), date('d', $timestamp));
    return $jdate[0] . '/' . sprintf('%02d', $jdate[1]) . '/' . sprintf('%02d', $jdate[2]);
}

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

// دریافت گزارشات ذخیره شده
$saved_reports = [];
$saved_reports_stmt = $conn->prepare("SELECT * FROM reportall ORDER BY created_at DESC");
if ($saved_reports_stmt) {
    $saved_reports_stmt->execute();
    $saved_reports_result = $saved_reports_stmt->get_result();
    while ($row = $saved_reports_result->fetch_assoc()) {
        $saved_reports[] = $row;
    }
    $saved_reports_stmt->close();
}

// اگر گزارش ذخیره شده انتخاب شده باشد
if ($saved_report_id) {
    $load_report_stmt = $conn->prepare("SELECT * FROM reportall WHERE id = ?");
    $load_report_stmt->bind_param("i", $saved_report_id);
    $load_report_stmt->execute();
    $load_report_result = $load_report_stmt->get_result();
    if ($load_report_result->num_rows > 0) {
        $saved_report = $load_report_result->fetch_assoc();
        $selected_users = $saved_report['include_users'];
        $report_type = $saved_report['report_type'];
        $selected_year = $saved_report['report_year'];
        $selected_month = $saved_report['report_month'];
        $selected_class = $saved_report['class_id'];
        $syscode_from = $saved_report['syscode_from'];
        $syscode_to = $saved_report['syscode_to'];
        $header_desc = $saved_report['header_desc'];
        $footer_desc = $saved_report['footer_desc'];
        $report_name = $saved_report['report_name'];
        // بازیابی فیلدهای انتخاب شده
        if (!empty($saved_report['selected_fields'])) {
            $selected_fields = json_decode($saved_report['selected_fields'], true);
        }
        $step = 5; // برو به مرحله نهایی
    }
    $load_report_stmt->close();
}

// دریافت لیست دوره‌ها
$classes = [];
$classes_stmt = $conn->prepare("SELECT ClassID, ClassName FROM class ORDER BY ClassName");
if ($classes_stmt) {
    $classes_stmt->execute();
    $classes_result = $classes_stmt->get_result();
    while ($row = $classes_result->fetch_assoc()) {
        $classes[] = $row;
    }
    $classes_stmt->close();
}

// پردازش فرم‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step1'])) {
        // ذخیره در session
        $_SESSION['selected_users'] = $selected_users;
        $_SESSION['report_type'] = $report_type;
        
        if (empty($report_type)) {
            $error = "لطفاً نوع گزارش را انتخاب کنید.";
            $step = 1;
        } else {
            $step = 2;
        }
    } elseif (isset($_POST['step2'])) {
        // ذخیره در session
        $_SESSION['selected_users'] = $selected_users;
        $_SESSION['report_type'] = $report_type;
        $_SESSION['selected_year'] = $selected_year;
        $_SESSION['selected_month'] = $selected_month;
        $_SESSION['selected_class'] = $selected_class;
        $_SESSION['syscode_from'] = $syscode_from;
        $_SESSION['syscode_to'] = $syscode_to;
        
        if ($report_type === 'registration') {
            // برای گزارش ثبت نام، بررسی محدوده کدسیستمی
            $step = 3; // رفتن به مرحله انتخاب فیلدها
        } else {
            // برای سایر گزارشات، بررسی سال و ماه
            if (empty($selected_year) || empty($selected_month)) {
                $error = "لطفاً سال و ماه را انتخاب کنید.";
                $step = 2;
            } elseif ($report_type === 'class' && empty($selected_class)) {
                $error = "لطفاً دوره را انتخاب کنید.";
                $step = 2;
            } else {
                $step = 3; // رفتن به مرحله انتخاب فیلدها
            }
        }
    } elseif (isset($_POST['step3'])) {
        // ذخیره در session
        $_SESSION['selected_fields'] = $selected_fields;
        
        // بررسی انتخاب حداقل یک فیلد
        if (empty($selected_fields)) {
            $error = "لطفاً حداقل یک فیلد را انتخاب کنید.";
            $step = 3;
        } else {
            $step = 4;
        }
    } elseif (isset($_POST['step4'])) {
        // ذخیره در session
        $_SESSION['header_desc'] = $header_desc;
        $_SESSION['footer_desc'] = $footer_desc;
        $_SESSION['report_name'] = $report_name;
        
        $step = 5;
        
        // ذخیره گزارش اگر کاربر درخواست کرده باشد
        if ($save_report && !empty($report_name)) {
            $selected_fields_json = json_encode($selected_fields);
            $include_users = $selected_users ? 1 : 0;
            
            $save_stmt = $conn->prepare("INSERT INTO reportall (report_name, include_users, report_type, report_year, report_month, class_id, syscode_from, syscode_to, selected_fields, header_desc, footer_desc, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $save_stmt->bind_param("sisssssssss", $report_name, $include_users, $report_type, $selected_year, $selected_month, $selected_class, $syscode_from, $syscode_to, $selected_fields_json, $header_desc, $footer_desc);
            $save_stmt->execute();
            $save_stmt->close();
            $success = "گزارش با موفقیت ذخیره شد.";
        }
    } elseif (isset($_POST['edit_parameters'])) {
        // پاک کردن تمام sessionهای مرتبط با گزارش
        unset(
            $_SESSION['selected_users'],
            $_SESSION['report_type'],
            $_SESSION['selected_year'],
            $_SESSION['selected_month'],
            $_SESSION['selected_class'],
            $_SESSION['syscode_from'],
            $_SESSION['syscode_to'],
            $_SESSION['selected_fields'],
            $_SESSION['header_desc'],
            $_SESSION['footer_desc'],
            $_SESSION['report_name']
        );
        $step = 1;
    }
}

// تولید گزارش
$report_data = [];
$report_title = '';

if ($step >= 5 && $selected_users) {
    // ساخت عنوان گزارش
    $report_title = "گزارش ";
    
    if ($report_type === 'registration') {
        $report_title .= "ثبت نام ";
        if (!empty($syscode_from) && !empty($syscode_to)) {
            $report_title .= "(کدسیستمی از " . $syscode_from . " تا " . $syscode_to . ") ";
        } elseif (!empty($syscode_from)) {
            $report_title .= "(کدسیستمی از " . $syscode_from . " به بالا) ";
        } elseif (!empty($syscode_to)) {
            $report_title .= "(کدسیستمی تا " . $syscode_to . ") ";
        } else {
            $report_title .= "(همه کاربران) ";
        }
    } elseif ($report_type === 'attendance') {
        $report_title .= "حضور و غیاب ";
        if (!empty($selected_class)) {
            // پیدا کردن نام دوره
            foreach ($classes as $class) {
                if ($class['ClassID'] == $selected_class) {
                    $report_title .= $class['ClassName'] . " ";
                    break;
                }
            }
        }
        $report_title .= $jalali_months[$selected_month] . " " . $selected_year;
    } elseif ($report_type === 'class') {
        $report_title .= "دوره ";
        // پیدا کردن نام دوره
        foreach ($classes as $class) {
            if ($class['ClassID'] == $selected_class) {
                $report_title .= $class['ClassName'] . " ";
                break;
            }
        }
        $report_title .= $jalali_months[$selected_month] . " " . $selected_year;
    }

    $report_title .= " (کاربران)";

    // تولید داده‌های گزارش بر اساس نوع
    if ($report_type === 'registration') {
        // گزارش ثبت نام با فیلتر کدسیستمی
        $query = "SELECT " . implode(", ", $selected_fields) . " FROM users WHERE 1=1";
        $params = [];
        $types = "";
        
        // اضافه کردن شرایط فیلتر کدسیستمی
        if (!empty($syscode_from)) {
            $query .= " AND CAST(UserSysCode AS UNSIGNED) >= ?";
            $params[] = $syscode_from;
            $types .= "s";
        }
        if (!empty($syscode_to)) {
            $query .= " AND CAST(UserSysCode AS UNSIGNED) <= ?";
            $params[] = $syscode_to;
            $types .= "s";
        }
        
        $query .= " ORDER BY CAST(UserSysCode AS UNSIGNED)";
        
        $stmt = $conn->prepare($query);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            // تبدیل تاریخ‌ها به شمسی
            foreach ($row as $key => $value) {
                if (($key === 'UserRegDate' || $key === 'UserDateBirth' || $key === 'UserActiveDate' || $key === 'UserSuspendDate') && 
                    !empty($value) && $value != '0000-00-00') {
                    $row[$key] = gregorianToShamsi($value);
                }
            }
            $report_data[] = $row;
        }
        $stmt->close();
        
    } elseif ($report_type === 'attendance') {
        // گزارش حضور و غیاب
        // تبدیل تاریخ شمسی به میلادی برای کوئری
        $start_date_shamsi = $selected_year . '/' . $selected_month . '/01';
        $end_date_shamsi = $selected_year . '/' . $selected_month . '/31';
        $start_date = shamsiToGregorian($start_date_shamsi);
        $end_date = shamsiToGregorian($end_date_shamsi);
        
        if (!empty($selected_class)) {
            // گزارش حضور و غیاب برای دوره خاص
            // ابتدا اطلاعات دوره را بگیریم
            $class_stmt = $conn->prepare("SELECT CalssUsers FROM class WHERE ClassID = ?");
            $class_stmt->bind_param("i", $selected_class);
            $class_stmt->execute();
            $class_result = $class_stmt->get_result();
            $class_data = $class_result->fetch_assoc();
            $class_stmt->close();
            
            $class_users = [];
            if (!empty($class_data['CalssUsers']) && $class_data['CalssUsers'] !== '[]') {
                $class_users = json_decode($class_data['CalssUsers'], true);
            }
            
            // استخراج کاربران دوره
            $user_ids = [];
            foreach ($class_users as $user) {
                if (isset($user['type']) && $user['type'] === 'teen') {
                    $user_ids[] = $user['id'];
                }
            }
            
            if (!empty($user_ids)) {
                $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                $query = "SELECT " . implode(", ", $selected_fields) . ",
                                 SUM(CASE WHEN r.Status = 'حاضر' THEN 1 ELSE 0 END) as present_count,
                                 SUM(CASE WHEN r.Status = 'غایب' THEN 1 ELSE 0 END) as absent_count,
                                 SUM(CASE WHEN r.Status = 'مرخصی' THEN 1 ELSE 0 END) as excused_count
                          FROM users u
                          LEFT JOIN rollcalluser r ON u.UserID = r.UserID 
                                 AND r.ClassID = ? 
                                 AND r.RollcallUserDate BETWEEN ? AND ?
                          WHERE u.UserID IN ($placeholders)
                          GROUP BY u.UserID
                          ORDER BY CAST(u.UserSysCode AS UNSIGNED)";
                
                $stmt = $conn->prepare($query);
                $params = array_merge([$selected_class, $start_date, $end_date], $user_ids);
                $types = "iss" . str_repeat("i", count($user_ids));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $report_data[] = $row;
                }
                $stmt->close();
            }
        } else {
            // گزارش حضور و غیاب کلیه کاربران
            $query = "SELECT " . implode(", ", $selected_fields) . ",
                             SUM(CASE WHEN r.Status = 'حاضر' THEN 1 ELSE 0 END) as present_count,
                             SUM(CASE WHEN r.Status = 'غایب' THEN 1 ELSE 0 END) as absent_count,
                             SUM(CASE WHEN r.Status = 'مرخصی' THEN 1 ELSE 0 END) as excused_count
                      FROM users u
                      LEFT JOIN rollcalluser r ON u.UserID = r.UserID 
                             AND r.RollcallUserDate BETWEEN ? AND ?
                      GROUP BY u.UserID
                      ORDER BY CAST(u.UserSysCode AS UNSIGNED)";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $start_date, $end_date);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $stmt->close();
        }
        
    } elseif ($report_type === 'class' && $selected_class) {
        // گزارش دوره
        // تبدیل تاریخ شمسی به میلادی برای کوئری
        $start_date_shamsi = $selected_year . '/' . $selected_month . '/01';
        $end_date_shamsi = $selected_year . '/' . $selected_month . '/31';
        $start_date = shamsiToGregorian($start_date_shamsi);
        $end_date = shamsiToGregorian($end_date_shamsi);
        
        // ابتدا اطلاعات دوره را بگیریم
        $class_stmt = $conn->prepare("SELECT CalssUsers FROM class WHERE ClassID = ?");
        $class_stmt->bind_param("i", $selected_class);
        $class_stmt->execute();
        $class_result = $class_stmt->get_result();
        $class_data = $class_result->fetch_assoc();
        $class_stmt->close();
        
        $class_users = [];
        if (!empty($class_data['CalssUsers']) && $class_data['CalssUsers'] !== '[]') {
            $class_users = json_decode($class_data['CalssUsers'], true);
        }
        
        // استخراج کاربران دوره
        $user_ids = [];
        foreach ($class_users as $user) {
            if (isset($user['type']) && $user['type'] === 'teen') {
                $user_ids[] = $user['id'];
            }
        }
        
        if (!empty($user_ids)) {
            $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
            $query = "SELECT " . implode(", ", $selected_fields) . ",
                             SUM(CASE WHEN r.Status = 'حاضر' THEN 1 ELSE 0 END) as present_count,
                             SUM(CASE WHEN r.Status = 'غایب' THEN 1 ELSE 0 END) as absent_count,
                             SUM(CASE WHEN r.Status = 'مرخصی' THEN 1 ELSE 0 END) as excused_count
                      FROM users u
                      LEFT JOIN rollcalluser r ON u.UserID = r.UserID 
                             AND r.ClassID = ? 
                             AND r.RollcallUserDate BETWEEN ? AND ?
                      WHERE u.UserID IN ($placeholders)
                      GROUP BY u.UserID
                      ORDER BY CAST(u.UserSysCode AS UNSIGNED)";
            
            $stmt = $conn->prepare($query);
            $params = array_merge([$selected_class, $start_date, $end_date], $user_ids);
            $types = "iss" . str_repeat("i", count($user_ids));
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $report_data[] = $row;
            }
            $stmt->close();
        }
    }
}

// خروجی اکسل
if (isset($_POST['export_excel']) && !empty($report_data)) {
    // پاک کردن output buffer
    ob_end_clean();
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . date('Y-m-d_H-i-s') . '.xls"');
    
    echo '<html dir="rtl">';
    echo '<head><meta charset="UTF-8"></head>';
    echo '<body>';
    echo '<table border="1" style="width:100%; border-collapse: collapse;">';
    
    // هدر گزارش
    $colspan = count($selected_fields);
    if ($report_type === 'attendance' || $report_type === 'class') {
        $colspan += 4;
    }
    echo '<tr><th colspan="' . $colspan . '" style="background-color: #4a6cf7; color: white; font-size: 16px; padding: 15px;">' . $report_title . '</th></tr>';
    
    // توضیحات هدر
    if (!empty($header_desc)) {
        echo '<tr><td colspan="' . $colspan . '" style="background-color: #f8f9fa; padding: 10px; text-align: right;"><strong>توضیحات:</strong><br>' . nl2br(htmlspecialchars($header_desc)) . '</td></tr>';
    }
    
    // هدر جدول
    echo '<tr style="background-color: #e9ecef;">';
    echo '<th style="border: 1px solid #ddd; padding: 8px;">ردیف</th>';
    foreach ($selected_fields as $field) {
        $field_name = $users_fields[$field] ?? $field;
        echo '<th style="border: 1px solid #ddd; padding: 8px;">' . $field_name . '</th>';
    }
    if ($report_type === 'attendance' || $report_type === 'class') {
        echo '<th style="border: 1px solid #ddd; padding: 8px;">حضور</th>';
        echo '<th style="border: 1px solid #ddd; padding: 8px;">غیبت</th>';
        echo '<th style="border: 1px solid #ddd; padding: 8px;">مرخصی</th>';
        echo '<th style="border: 1px solid #ddd; padding: 8px;">جمع</th>';
    }
    echo '</tr>';
    
    // داده‌ها
    $counter = 1;
    foreach ($report_data as $row) {
        echo '<tr>';
        echo '<td style="border: 1px solid #ddd; padding: 8px;">' . $counter++ . '</td>';
        foreach ($selected_fields as $field) {
            $value = $row[$field] ?? '';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . htmlspecialchars($value) . '</td>';
        }
        if ($report_type === 'attendance' || $report_type === 'class') {
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . ($row['present_count'] ?? 0) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . ($row['absent_count'] ?? 0) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . ($row['excused_count'] ?? 0) . '</td>';
            echo '<td style="border: 1px solid #ddd; padding: 8px;">' . (($row['present_count'] ?? 0) + ($row['absent_count'] ?? 0) + ($row['excused_count'] ?? 0)) . '</td>';
        }
        echo '</tr>';
    }
    
    // توضیحات فوتر
    if (!empty($footer_desc)) {
        echo '<tr><td colspan="' . $colspan . '" style="background-color: #f8f9fa; padding: 10px; text-align: right;"><strong>توضیحات پایانی:</strong><br>' . nl2br(htmlspecialchars($footer_desc)) . '</td></tr>';
    }
    
    // اطلاعات پایین جدول
    echo '<tr><td colspan="' . $colspan . '" style="background-color: #e9ecef; padding: 8px; text-align: center; font-size: 12px;">';
    echo 'تعداد رکوردها: ' . count($report_data) . ' | تاریخ تولید: ' . gregorianToShamsi(date('Y-m-d')) . ' | زمان تولید: ' . date('H:i:s');
    echo '</td></tr>';
    echo '</table>';
    echo '</body></html>';
    exit;
}

// چاپ گزارش
if (isset($_POST['print_report']) && !empty($report_data)) {
    // پاک کردن output buffer
    ob_end_clean();
    echo '<!DOCTYPE html>';
    echo '<html dir="rtl">';
    echo '<head>';
    echo '<meta charset="UTF-8">';
    echo '<title>چاپ ' . $report_title . '</title>';
    echo '<style>';
    echo 'body { font-family: "B Nazanin", Tahoma, sans-serif; direction: rtl; margin: 20px; }';
    echo '.print-header { text-align: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #333; }';
    echo '.print-header h1 { color: #2c3e50; margin: 0; }';
    echo '.print-table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
    echo '.print-table th { background-color: #34495e; color: white; padding: 10px; border: 1px solid #ddd; }';
    echo '.print-table td { padding: 8px; border: 1px solid #ddd; text-align: center; }';
    echo '.print-table tr:nth-child(even) { background-color: #f2f2f2; }';
    echo '.header-desc { background-color: #ecf0f1; padding: 15px; margin-bottom: 20px; border-radius: 5px; }';
    echo '.footer-desc { background-color: #ecf0f1; padding: 15px; margin-top: 20px; border-radius: 5px; }';
    echo '.print-info { text-align: center; margin-top: 20px; font-size: 12px; color: #7f8c8d; }';
    echo '@media print {';
    echo '  .no-print { display: none; }';
    echo '  body { margin: 0; }';
    echo '}';
    echo '</style>';
    echo '</head>';
    echo '<body>';
    
    echo '<div class="print-header">';
    echo '<h1>' . $report_title . '</h1>';
    echo '</div>';
    
    if (!empty($header_desc)) {
        echo '<div class="header-desc">';
        echo '<strong>توضیحات:</strong><br>' . nl2br(htmlspecialchars($header_desc));
        echo '</div>';
    }
    
    echo '<table class="print-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ردیف</th>';
    foreach ($selected_fields as $field) {
        $field_name = $users_fields[$field] ?? $field;
        echo '<th>' . $field_name . '</th>';
    }
    if ($report_type === 'attendance' || $report_type === 'class') {
        echo '<th>حضور</th>';
        echo '<th>غیبت</th>';
        echo '<th>مرخصی</th>';
        echo '<th>جمع</th>';
    }
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    $counter = 1;
    foreach ($report_data as $row) {
        echo '<tr>';
        echo '<td>' . $counter++ . '</td>';
        foreach ($selected_fields as $field) {
            $value = $row[$field] ?? '';
            echo '<td>' . htmlspecialchars($value) . '</td>';
        }
        if ($report_type === 'attendance' || $report_type === 'class') {
            echo '<td>' . ($row['present_count'] ?? 0) . '</td>';
            echo '<td>' . ($row['absent_count'] ?? 0) . '</td>';
            echo '<td>' . ($row['excused_count'] ?? 0) . '</td>';
            echo '<td>' . (($row['present_count'] ?? 0) + ($row['absent_count'] ?? 0) + ($row['excused_count'] ?? 0)) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
    
    if (!empty($footer_desc)) {
        echo '<div class="footer-desc">';
        echo '<strong>توضیحات پایانی:</strong><br>' . nl2br(htmlspecialchars($footer_desc));
        echo '</div>';
    }
    
    echo '<div class="print-info">';
    echo '<p>تعداد رکوردها: ' . count($report_data) . ' | تاریخ تولید: ' . gregorianToShamsi(date('Y-m-d')) . ' | زمان تولید: ' . date('H:i:s') . '</p>';
    echo '</div>';
    
    echo '<div class="no-print" style="text-align: center; margin-top: 20px;">';
    echo '<button onclick="window.print()" style="padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">چاپ گزارش</button>';
    echo '<a href="reportall.php" style="display:inline-block;padding:10px 20px;background:#e74c3c;color:white;border-radius:5px;text-decoration:none;">بستن</a>';
    echo '</div>';
    
    echo '</body>';
    echo '</html>';
    exit;
}

// خروجی PDF
if (isset($_POST['export_pdf']) && !empty($report_data)) {
    // پاک کردن output buffer
    ob_end_clean();
    
    // ایجاد شیء PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // تنظیمات سند
    $pdf->SetCreator('System');
    $pdf->SetAuthor('System');
    $pdf->SetTitle($report_title);
    $pdf->SetSubject('Report');
    
    // حذف هدر و فوتر پیشفرض
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // افزودن صفحه
    $pdf->AddPage();
    
    // تنظیم فونت
    $pdf->SetFont('dejavusans', '', 10);
    
    // عنوان گزارش
    $pdf->SetFont('dejavusans', 'B', 16);
    $pdf->Cell(0, 10, $report_title, 0, 1, 'C');
    $pdf->Ln(5);
    
    // توضیحات هدر
    if (!empty($header_desc)) {
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, 'توضیحات:', 0, 1, 'R');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 10, $header_desc, 0, 'R');
        $pdf->Ln(5);
    }
    
    // محاسبه عرض ستون‌ها
    $col_count = count($selected_fields) + 1;
    if ($report_type === 'attendance' || $report_type === 'class') {
        $col_count += 4;
    }
    $col_width = 190 / $col_count;
    
    // هدر جدول
    $pdf->SetFillColor(52, 73, 94);
    $pdf->SetTextColor(255);
    $pdf->SetFont('dejavusans', 'B', 10);
    
    $pdf->Cell($col_width, 10, 'ردیف', 1, 0, 'C', true);
    foreach ($selected_fields as $field) {
        $field_name = $users_fields[$field] ?? $field;
        $pdf->Cell($col_width, 10, $field_name, 1, 0, 'C', true);
    }
    if ($report_type === 'attendance' || $report_type === 'class') {
        $pdf->Cell($col_width, 10, 'حضور', 1, 0, 'C', true);
        $pdf->Cell($col_width, 10, 'غیبت', 1, 0, 'C', true);
        $pdf->Cell($col_width, 10, 'مرخصی', 1, 0, 'C', true);
        $pdf->Cell($col_width, 10, 'جمع', 1, 0, 'C', true);
    }
    $pdf->Ln();
    
    // داده‌ها
    $pdf->SetTextColor(0);
    $pdf->SetFont('dejavusans', '', 9);
    $counter = 1;
    $fill = false;
    
    foreach ($report_data as $row) {
        // رنگ پس‌زمینه متناوب
        if ($fill) {
            $pdf->SetFillColor(245, 245, 245);
        } else {
            $pdf->SetFillColor(255, 255, 255);
        }
        $fill = !$fill;
        
        $pdf->Cell($col_width, 8, $counter++, 1, 0, 'C', true);
        foreach ($selected_fields as $field) {
            $value = $row[$field] ?? '';
            $pdf->Cell($col_width, 8, $value, 1, 0, 'C', true);
        }
        if ($report_type === 'attendance' || $report_type === 'class') {
            $pdf->Cell($col_width, 8, $row['present_count'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($col_width, 8, $row['absent_count'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($col_width, 8, $row['excused_count'] ?? 0, 1, 0, 'C', true);
            $pdf->Cell($col_width, 8, (($row['present_count'] ?? 0) + ($row['absent_count'] ?? 0) + ($row['excused_count'] ?? 0)), 1, 0, 'C', true);
        }
        $pdf->Ln();
    }
    
    $pdf->Ln(10);
    
    // توضیحات فوتر
    if (!empty($footer_desc)) {
        $pdf->SetFont('dejavusans', 'B', 12);
        $pdf->Cell(0, 10, 'توضیحات پایانی:', 0, 1, 'R');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->MultiCell(0, 10, $footer_desc, 0, 'R');
        $pdf->Ln(5);
    }
    
    // اطلاعات پایین
    $pdf->SetFont('dejavusans', '', 8);
    $pdf->Cell(0, 10, 'تعداد رکوردها: ' . count($report_data) . ' | تاریخ تولید: ' . gregorianToShamsi(date('Y-m-d')) . ' | زمان تولید: ' . date('H:i:s'), 0, 1, 'C');
    
    // خروجی PDF
    $pdf->Output('report_' . date('Y-m-d_H-i-s') . '.pdf', 'D');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارشات جامع</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .report-container {
            margin-top: 100px;
            margin-bottom: 50px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 10px;
            font-weight: bold;
            position: relative;
        }
        .step.active {
            background: #4a6cf7;
            color: white;
        }
        .step.completed {
            background: #28a745;
            color: white;
        }
        .step-line {
            flex: 1;
            height: 3px;
            background: #e9ecef;
            margin: 0 5px;
            align-self: center;
        }
        .step-line.completed {
            background: #28a745;
        }
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
        }
        .selection-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .selection-card:hover {
            border-color: #4a6cf7;
            transform: translateY(-2px);
        }
        .selection-card.selected {
            border-color: #4a6cf7;
            background: rgba(74, 108, 247, 0.05);
        }
        .report-result {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        .table-hover tbody tr:hover {
            background-color: rgba(74, 108, 247, 0.05);
        }
        .table-dark-custom {
            background-color: #000000 !important;
            color: white !important;
        }
        .saved-reports-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .syscode-range {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .description-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .fields-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .field-group {
            margin-bottom: 25px;
        }
        .field-group h5 {
            border-bottom: 2px solid #4a6cf7;
            padding-bottom: 8px;
            margin-bottom: 15px;
            color: #4a6cf7;
        }
        .field-checkbox {
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .report-container {
                margin-top: 80px;
                padding: 10px;
            }
            .form-section {
                padding: 20px;
            }
            .step {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            .action-buttons {
                flex-direction: column;
            }
            .action-buttons .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="report-container container content-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex justify-content-start align-items-center mb-3">
                <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                    <span class="me-2">بستن</span>
                    <span aria-hidden="true" class="fs-5">×</span>
                </a>
                <h2 class="mb-0">گزارشات جامع سیستم</h2>
            </div>
        </div>

        <?php if (!empty($saved_reports)): ?>
        <div class="saved-reports-section">
            <h4><i class="fas fa-save"></i> گزارشات ذخیره شده</h4>
            <div class="row">
                <?php foreach ($saved_reports as $saved_report): ?>
                <div class="col-md-6 mb-2">
                    <a href="reportall.php?saved_report=<?php echo $saved_report['id']; ?>" 
                       class="btn btn-outline-primary w-100 text-start">
                        <i class="fas fa-file-alt me-2"></i>
                        <?php echo htmlspecialchars($saved_report['report_name']); ?>
                        <small class="text-muted d-block">
                            <?php 
                            $type_text = '';
                            if ($saved_report['report_type'] === 'registration') $type_text = 'ثبت نام';
                            elseif ($saved_report['report_type'] === 'attendance') $type_text = 'حضور و غیاب';
                            elseif ($saved_report['report_type'] === 'class') $type_text = 'دوره';
                            echo $type_text . ' - ' . $jalali_months[$saved_report['report_month']] . ' ' . $saved_report['report_year'];
                            ?>
                        </small>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- نمایش خطاها -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- نشانگر مراحل -->
        <div class="step-indicator">
            <div class="step <?php echo $step >= 1 ? 'completed' : ($step == 1 ? 'active' : ''); ?>">1</div>
            <div class="step-line <?php echo $step >= 2 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 2 ? 'completed' : ($step == 2 ? 'active' : ''); ?>">2</div>
            <div class="step-line <?php echo $step >= 3 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 3 ? 'completed' : ($step == 3 ? 'active' : ''); ?>">3</div>
            <div class="step-line <?php echo $step >= 4 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 4 ? 'completed' : ($step == 4 ? 'active' : ''); ?>">4</div>
            <div class="step-line <?php echo $step >= 5 ? 'completed' : ''; ?>"></div>
            <div class="step <?php echo $step >= 5 ? 'completed' : ($step == 5 ? 'active' : ''); ?>">5</div>
        </div>

        <form method="post" id="report-form">
            <!-- Step 1: انتخاب نوع گزارش -->
            <?php if ($step == 1): ?>
            <div class="form-section">
                <h3 class="mb-4"><i class="fas fa-chart-pie"></i> مرحله 1: انتخاب نوع گزارش</h3>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="selection-card <?php echo $report_type === 'registration' ? 'selected' : ''; ?>" onclick="selectReportType('registration')">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="report_type" value="registration" id="registration" <?php echo $report_type === 'registration' ? 'checked' : ''; ?>>
                                <label class="form-check-label w-100" for="registration">
                                    <h5><i class="fas fa-user-plus text-info"></i> لیست ثبت نام</h5>
                                    <p class="text-muted mb-0">لیست کاربران با فیلتر کدسیستمی</p>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="selection-card <?php echo $report_type === 'attendance' ? 'selected' : ''; ?>" onclick="selectReportType('attendance')">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="report_type" value="attendance" id="attendance" <?php echo $report_type === 'attendance' ? 'checked' : ''; ?>>
                                <label class="form-check-label w-100" for="attendance">
                                    <h5><i class="fas fa-clipboard-check text-warning"></i> لیست حضور و غیاب</h5>
                                    <p class="text-muted mb-0">گزارش آمار حضور و غیاب کاربران</p>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="selection-card <?php echo $report_type === 'class' ? 'selected' : ''; ?>" onclick="selectReportType('class')">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="report_type" value="class" id="class" <?php echo $report_type === 'class' ? 'checked' : ''; ?>>
                                <label class="form-check-label w-100" for="class">
                                    <h5><i class="fas fa-book text-success"></i> لیست دوره</h5>
                                    <p class="text-muted mb-0">گزارش کاربران دوره‌های خاص</p>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="../index.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-home"></i> صفحه اصلی
                    </a>
                    <button type="submit" name="step1" class="btn btn-primary btn-lg">
                        مرحله بعد <i class="fas fa-arrow-left"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 2: انتخاب تاریخ و دوره یا محدوده کدسیستمی -->
            <?php if ($step == 2): ?>
            <div class="form-section">
                <h3 class="mb-4"><i class="fas fa-calendar-alt"></i> مرحله 2: 
                    <?php if ($report_type === 'registration'): ?>
                        انتخاب محدوده کدسیستمی
                    <?php else: ?>
                        انتخاب بازه زمانی <?php echo ($report_type === 'class' || $report_type === 'attendance') ? 'و دوره' : ''; ?>
                    <?php endif; ?>
                </h3>
                
                <?php if ($report_type === 'registration'): ?>
                <!-- برای گزارش ثبت نام: محدوده کدسیستمی -->
                <div class="syscode-range">
                    <h5 class="text-info mb-3"><i class="fas fa-sort-numeric-up"></i> محدوده کدسیستمی</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">از کدسیستمی:</label>
                            <input type="number" name="syscode_from" class="form-control" placeholder="کدسیستمی شروع" 
                                   value="<?php echo htmlspecialchars($syscode_from); ?>" dir="ltr">
                            <small class="form-text text-muted">خالی بگذارید برای همه کدهای بزرگتر</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">تا کدسیستمی:</label>
                            <input type="number" name="syscode_to" class="form-control" placeholder="کدسیستمی پایان" 
                                   value="<?php echo htmlspecialchars($syscode_to); ?>" dir="ltr">
                            <small class="form-text text-muted">خالی بگذارید برای همه کدهای کوچکتر</small>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i>
                        برای گزارش ثبت نام، محدوده کدسیستمی را مشخص کنید. اگر هر دو فیلد خالی باشد، همه کاربران نمایش داده می‌شوند.
                    </div>
                </div>
                <?php else: ?>
                <!-- برای سایر گزارشات: سال و ماه -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">سال:</label>
                        <select name="year" class="form-select" required>
                            <?php foreach ($jalali_years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $selected_year == $year ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">ماه:</label>
                        <select name="month" class="form-select" required>
                            <?php foreach ($jalali_months as $key => $month): ?>
                                <option value="<?php echo $key; ?>" <?php echo $selected_month == $key ? 'selected' : ''; ?>>
                                    <?php echo $month; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($report_type === 'class' || $report_type === 'attendance'): ?>
                    <div class="col-12">
                        <label class="form-label">دوره:</label>
                        <select name="class_id" class="form-select" <?php echo $report_type === 'class' ? 'required' : ''; ?>>
                            <option value="">-- انتخاب دوره --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['ClassID']; ?>" <?php echo $selected_class == $class['ClassID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['ClassName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($report_type === 'attendance'): ?>
                        <small class="form-text text-muted">برای گزارش حضور و غیاب کلیه کاربران، این فیلد را خالی بگذارید</small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="reportall.php?step=1" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-right"></i> مرحله قبل
                    </a>
                    <button type="submit" name="step2" class="btn btn-primary btn-lg">
                        مرحله بعد <i class="fas fa-arrow-left"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 3: انتخاب فیلدها -->
            <?php if ($step == 3): ?>
            <div class="form-section">
                <h3 class="mb-4"><i class="fas fa-list-check"></i> مرحله 3: انتخاب فیلدهای گزارش</h3>
                <div class="fields-section">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        فیلدهای مورد نظر خود برای نمایش در گزارش را انتخاب کنید. فیلدهای انتخاب شده در خروجی چاپ و اکسل نمایش داده خواهند شد.
                    </div>
                    <div class="field-group">
                        <h5><i class="fas fa-users text-primary"></i> فیلدهای کاربران</h5>
                        <div class="row">
                            <?php foreach ($users_fields as $field => $label): ?>
                            <div class="col-md-4 col-sm-6 field-checkbox">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="fields[]" 
                                           value="<?php echo $field; ?>" 
                                           id="field_user_<?php echo $field; ?>"
                                           <?php echo in_array($field, $selected_fields) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="field_user_<?php echo $field; ?>">
                                        <?php echo $label; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="reportall.php?step=2" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-right"></i> مرحله قبل
                    </a>
                    <button type="submit" name="step3" class="btn btn-primary btn-lg">
                        مرحله بعد <i class="fas fa-arrow-left"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 4: توضیحات هدر و فوتر -->
            <?php if ($step == 4): ?>
            <div class="form-section">
                <h3 class="mb-4"><i class="fas fa-file-alt"></i> مرحله 4: توضیحات گزارش</h3>
                <div class="description-section">
                    <h5 class="text-info mb-3"><i class="fas fa-heading"></i> توضیحات هدر گزارش</h5>
                    <div class="mb-4">
                        <label class="form-label">توضیحات بالای گزارش (اختیاری):</label>
                        <textarea name="header_desc" class="form-control" rows="4" placeholder="توضیحات یا یادداشت‌هایی که می‌خواهید در بالای گزارش نمایش داده شود..."><?php echo htmlspecialchars($header_desc); ?></textarea>
                        <small class="form-text text-muted">این توضیحات در بالای گزارش و قبل از جدول داده‌ها نمایش داده می‌شود.</small>
                    </div>
                    <h5 class="text-info mb-3"><i class="fas fa-file-alt"></i> توضیحات فوتر گزارش</h5>
                    <div class="mb-4">
                        <label class="form-label">توضیحات پایین گزارش (اختیاری):</label>
                        <textarea name="footer_desc" class="form-control" rows="4" placeholder="توضیحات یا یادداشت‌هایی که می‌خواهید در پایین گزارش نمایش داده شود..."><?php echo htmlspecialchars($footer_desc); ?></textarea>
                        <small class="form-text text-muted">این توضیحات در پایین گزارش و بعد از جدول داده‌ها نمایش داده می‌شود.</small>
                    </div>
                </div>
                <div class="action-buttons">
                    <a href="reportall.php?step=3" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-right"></i> مرحله قبل
                    </a>
                    <button type="submit" name="step4" class="btn btn-primary btn-lg">
                        مشاهده گزارش <i class="fas fa-chart-bar"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 5: نمایش گزارش و ذخیره -->
            <?php if ($step == 5): ?>
            <div class="form-section">
                <h3 class="mb-4"><i class="fas fa-file-alt"></i> مرحله 5: گزارش نهایی</h3>
                
                <!-- فرم ذخیره گزارش -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-save"></i> ذخیره گزارش
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">نام گزارش:</label>
                                        <input type="text" name="report_name" class="form-control" placeholder="یک نام برای گزارش خود انتخاب کنید..." value="<?php echo htmlspecialchars($report_name); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">&nbsp;</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" name="save_report" id="save_report" <?php echo $save_report ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="save_report">
                                                ذخیره این گزارش
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- دکمه‌های عملیات -->
                <div class="action-buttons mb-4">
                    <button type="submit" name="edit_parameters" class="btn btn-warning btn-lg">
                        <i class="fas fa-edit"></i> ویرایش پارامترها
                    </button>
                    <?php if (!empty($report_data)): ?>
                    <button type="submit" name="export_excel" class="btn btn-success btn-lg">
                        <i class="fas fa-file-excel"></i> خروجی Excel
                    </button>
                    <button type="submit" name="print_report" class="btn btn-primary btn-lg">
                        <i class="fas fa-print"></i> چاپ گزارش
                    </button>
                    <button type="submit" name="export_pdf" class="btn btn-danger btn-lg">
                        <i class="fas fa-file-pdf"></i> خروجی PDF
                    </button>
                    <?php endif; ?>
                </div>
                
                <!-- نمایش گزارش -->
                <?php if (!empty($report_data)): ?>
                <div class="report-result">
                    <h4 class="text-center mb-4"><?php echo $report_title; ?></h4>
                    
                    <?php if (!empty($header_desc)): ?>
                    <div class="alert alert-info">
                        <h5><i class="fas fa-heading"></i> توضیحات:</h5>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($header_desc)); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark-custom">
                                <tr>
                                    <th>#</th>
                                    <?php foreach ($selected_fields as $field): ?>
                                        <?php $field_name = $users_fields[$field] ?? $field; ?>
                                        <th><?php echo $field_name; ?></th>
                                    <?php endforeach; ?>
                                    <?php if ($report_type === 'attendance' || $report_type === 'class'): ?>
                                        <th>حضور</th>
                                        <th>غیبت</th>
                                        <th>مرخصی</th>
                                        <th>جمع</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($report_data as $row): ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <?php foreach ($selected_fields as $field): ?>
                                            <td><?php echo htmlspecialchars($row[$field] ?? ''); ?></td>
                                        <?php endforeach; ?>
                                        <?php if ($report_type === 'attendance' || $report_type === 'class'): ?>
                                            <td>
                                                <span class="badge bg-success"><?php echo $row['present_count'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $row['absent_count'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning"><?php echo $row['excused_count'] ?? 0; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo ($row['present_count'] ?? 0) + ($row['absent_count'] ?? 0) + ($row['excused_count'] ?? 0); ?></strong>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if (!empty($footer_desc)): ?>
                    <div class="alert alert-secondary mt-3">
                        <h5><i class="fas fa-file-alt"></i> توضیحات پایانی:</h5>
                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($footer_desc)); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3 text-center text-muted">
                        <i class="fas fa-info-circle"></i>
                        تعداد رکوردها: <?php echo count($report_data); ?> |
                        تاریخ تولید: <?php echo gregorianToShamsi(date('Y-m-d')); ?>
                    </div>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning text-center">
                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i><br>
                        هیچ داده‌ای برای نمایش یافت نشد.
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSelection(type) {
            const checkbox = document.getElementById(type);
            checkbox.checked = !checkbox.checked;
            const card = checkbox.closest('.selection-card');
            if (checkbox.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        }
        
        function selectReportType(type) {
            document.getElementById(type).checked = true;
            // Remove selected class from all cards
            document.querySelectorAll('.selection-card').forEach(card => {
                card.classList.remove('selected');
            });
            // Add selected class to clicked card
            document.getElementById(type).closest('.selection-card').classList.add('selected');
        }
        
        // Initialize selection cards
        document.addEventListener('DOMContentLoaded', function() {
            // Checkboxes
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                const card = checkbox.closest('.selection-card');
                if (checkbox.checked) {
                    card.classList.add('selected');
                }
            });
            
            // Radio buttons
            document.querySelectorAll('input[type="radio"]').forEach(radio => {
                if (radio.checked) {
                    radio.closest('.selection-card').classList.add('selected');
                }
            });
        });
    </script>
</body>
</html>