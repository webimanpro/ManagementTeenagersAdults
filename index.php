<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration and functions
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/check_access.php';

// Check if user is logged in
if (!is_logged_in()) {
    // Redirect to login page if not logged in
    header('Location: includes/login.php');
	exit();
}

// دریافت نقش کاربر جاری
$currentUserRole = getCurrentUserRole();

// Initialize system settings
initialize_system_settings();

// Get background setting
$background_image = get_background_image();

// Get current year
$currentYear = date('Y');

// Initialize statistics variables
$teenCount = 0;
$adultCount = 0;
$activeClassesCount = 0;
$todayAttendanceCount = 0;

// Get user statistics (teens and adults)
$sql = "SELECT 
            SUM(CASE 
                WHEN UserDateBirth IS NOT NULL AND 
                     (DATEDIFF(CURRENT_DATE, UserDateBirth) / 365.25) < 18 
                THEN 1 
                ELSE 0 
            END) as teen_count,
            SUM(CASE 
                WHEN UserDateBirth IS NOT NULL AND 
                     (DATEDIFF(CURRENT_DATE, UserDateBirth) / 365.25) >= 18 
                THEN 1 
                ELSE 0 
            END) as adult_count,
            SUM(CASE WHEN UserDateBirth IS NULL THEN 1 ELSE 0 END) as unknown_age_count
        FROM users";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $teenCount = (int)$row['teen_count'];
    $adultCount = (int)$row['adult_count'];
    $unknownAgeCount = (int)$row['unknown_age_count'];
    
    // If we have users with unknown age, you might want to handle them (e.g., show a warning)
    if ($unknownAgeCount > 0) {
        // You could log this or show a message to the admin
        error_log("Warning: Found $unknownAgeCount users without birthdate information");
    }
}

// Get active classes count
$sql = "SELECT COUNT(*) as count FROM class";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $activeClassesCount = (int)$row['count'];
}

// Get today's attendance count
$today = date('Y-m-d');
$sql = "SELECT COUNT(DISTINCT UserID) as count FROM rollcalluser WHERE DATE(RollcallUserDate) = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('s', $today);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $todayAttendanceCount = (int)$row['count'];
        }
    } else {
        error_log("Error getting today's attendance: " . $stmt->error);
    }
    $stmt->close();
}

// Set page title
$page_title = 'خانه';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>پایگاه بسیج - <?php echo $page_title; ?></title>
    <link href="assets/css/bootstrap.rtl.min.css" rel="stylesheet">
	<link href="assets/css/font-face.css" rel="stylesheet">
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* استایل داینامیک برای پس‌زمینه */
        body {
            background: url('<?php echo htmlspecialchars($background_image); ?>') no-repeat center center fixed !important;
            background-size: cover !important;
            background-attachment: fixed !important;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        /* بهبود خوانایی محتوا روی پس‌زمینه */
        .main-content {
            background: transparent !important;
            margin-top: 80px;
            padding-bottom: 80px;
        }
        
        /* استایل آمار پایین سمت چپ */
        .stats-sidebar {
            position: fixed;
            bottom: 70px;
            left: 20px;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .stat-icon-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            cursor: pointer;
            border-right: 3px solid #4a6cf7;
            min-width: 200px;
        }
        
        .stat-icon-card:hover {
            transform: translateX(-5px);
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stat-icon-card .icon {
            font-size: 1.8rem;
            color: #4a6cf7;
        }
        
        .stat-icon-card .info {
            display: flex;
            flex-direction: column;
        }
        
        .stat-icon-card .number {
            font-size: 1.3rem;
            font-weight: 700;
            color: #2c3e50;
            line-height: 1.2;
        }
        
        .stat-icon-card .label {
            font-size: 0.8rem;
            color: #666;
        }
        
        /* استایل دکمه‌های میانبر پایین سمت راست */
        .actions-sidebar {
            position: fixed;
            bottom: 70px;
            right: 20px;
            z-index: 1050;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .action-icon-btn {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            position: relative;
        }
        
        .action-icon-btn:hover {
            transform: scale(1.1);
            background: rgba(255, 255, 255, 1);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .action-icon-btn i {
            font-size: 1rem;
            color: #4a6cf7;
        }
        
        /* tooltip برای دکمه‌های میانبر */
        .action-icon-btn .tooltip-text {
            visibility: hidden;
            background-color: rgba(0, 0, 0, 0.85);
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px 10px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            right: 50%;
            transform: translateX(50%);
            white-space: nowrap;
            font-size: 0.8rem;
            font-family: 'Sahel', Tahoma, sans-serif;
            opacity: 0;
            transition: opacity 0.3s;
            pointer-events: none;
        }
        
        .action-icon-btn .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            right: 50%;
            transform: translateX(50%);
            border-width: 5px;
            border-style: solid;
            border-color: rgba(0, 0, 0, 0.85) transparent transparent transparent;
        }
        
        .action-icon-btn:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* رنگ‌های متفاوت برای دکمه‌های مختلف */
        .action-icon-btn.primary i { color: #4a6cf7; }
        .action-icon-btn.success i { color: #28a745; }
        .action-icon-btn.secondary i { color: #6c757d; }
        .action-icon-btn.info i { color: #17a2b8; }
        .action-icon-btn.warning i { color: #ffc107; }
        .action-icon-btn.danger i { color: #dc3545; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .stats-sidebar {
                bottom: 60px;
                left: 10px;
                gap: 8px;
            }
            
            .stat-icon-card {
                padding: 8px 15px;
                min-width: 150px;
            }
            
            .stat-icon-card .icon {
                font-size: 1.3rem;
            }
            
            .stat-icon-card .number {
                font-size: 1rem;
            }
            
            .stat-icon-card .label {
                font-size: 0.7rem;
            }
            
            .actions-sidebar {
                bottom: 60px;
                right: 10px;
                gap: 8px;
            }
            
            .action-icon-btn {
                width: 45px;
                height: 45px;
            }
            
            .action-icon-btn i {
                font-size: 1.3rem;
            }
            
            .action-icon-btn .tooltip-text {
                font-size: 0.7rem;
                white-space: nowrap;
            }
        }
        
        @media (max-width: 480px) {
            .stats-sidebar {
                bottom: 55px;
                left: 8px;
                gap: 6px;
            }
            
            .stat-icon-card {
                padding: 6px 12px;
                min-width: 130px;
            }
            
            .stat-icon-card .icon {
                font-size: 1.1rem;
            }
            
            .stat-icon-card .number {
                font-size: 0.9rem;
            }
            
            .stat-icon-card .label {
                font-size: 0.65rem;
            }
            
            .actions-sidebar {
                bottom: 55px;
                right: 8px;
                gap: 6px;
            }
            
            .action-icon-btn {
                width: 40px;
                height: 40px;
            }
            
            .action-icon-btn i {
                font-size: 1.1rem;
            }
            
            .main-content {
                margin-top: 60px;
                padding-bottom: 70px;
            }
        }
        
        /* حالت افقی موبایل */
        @media (max-width: 768px) and (orientation: landscape) {
            .stats-sidebar {
                bottom: 50px;
                flex-direction: row;
                flex-wrap: wrap;
                left: 10px;
                right: auto;
                gap: 8px;
            }
            
            .stat-icon-card {
                min-width: auto;
                padding: 6px 12px;
            }
            
            .actions-sidebar {
                bottom: 50px;
                flex-direction: row;
                flex-wrap: wrap;
                right: 10px;
                gap: 8px;
            }
            
            .action-icon-btn {
                width: 40px;
                height: 40px;
            }
        }
        
        /* صفحه اصلی خالی */
        .welcome-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 160px);
            text-align: center;
        }
        
        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }
        
        .welcome-card h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
        }
        
        .welcome-card p {
            color: #666;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="welcome-container">
            <div class="welcome-card">
                <i class="fas fa-tachometer-alt" style="font-size: 3rem; color: #4a6cf7; margin-bottom: 0.1rem;"></i>
                <h4>سیستم مدیریت پایگاه بسیج شهید</h4>
				<p></p>
                <small class="text-muted">برای دسترسی به امکانات، از دکمه‌های میانبر سمت راست استفاده کنید</small>
            </div>
        </div>
    </div>

    <!-- آمار پایین سمت چپ -->
    <div class="stats-sidebar">
        <div class="stat-icon-card">
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="info">
                <div class="number"><?php echo $teenCount; ?></div>
                <div class="label">نوجوان</div>
            </div>
        </div>
        
        <div class="stat-icon-card">
            <div class="icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <div class="info">
                <div class="number"><?php echo $adultCount; ?></div>
                <div class="label">بزرگسال</div>
            </div>
        </div>
        
        <div class="stat-icon-card">
            <div class="icon">
                <i class="fas fa-book"></i>
            </div>
            <div class="info">
                <div class="number"><?php echo $activeClassesCount; ?></div>
                <div class="label">دوره‌ها</div>
            </div>
        </div>
        
        <div class="stat-icon-card">
            <div class="icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="info">
                <div class="number"><?php echo $todayAttendanceCount; ?></div>
                <div class="label">حضور و غیاب</div>
            </div>
        </div>
    </div>

    <!-- دکمه‌های میانبر پایین سمت راست -->
    <div class="actions-sidebar">
        <?php if (isManagerOrAbove()): ?>
        <a href="includes/regsiteruser.php" class="action-icon-btn primary">
            <i class="fas fa-user-plus"></i>
            <span class="tooltip-text">ثبت نام</span>
        </a>
        <a href="includes/listuser.php" class="action-icon-btn primary">
            <i class="fas fa-list"></i>
            <span class="tooltip-text">لیست کاربران</span>
        </a>
        <?php endif; ?>
        
        <a href="includes/exceluser.php" class="action-icon-btn success">
            <i class="fas fa-file-excel"></i>
            <span class="tooltip-text">ثبت نام اکسل</span>
        </a>
        
        <a href="includes/rollcalluser.php" class="action-icon-btn secondary">
            <i class="fas fa-user-check"></i>
            <span class="tooltip-text">حضور و غیاب</span>
        </a>
        
        <?php if (isManagerOrAbove()): ?>
        <a href="includes/class.php" class="action-icon-btn info">
            <i class="fas fa-book"></i>
            <span class="tooltip-text">دوره‌ها</span>
        </a>
        <a href="includes/reportall.php" class="action-icon-btn info">
            <i class="fas fa-chart-bar"></i>
            <span class="tooltip-text">گزارشات</span>
        </a>
        <?php endif; ?>
        
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
	<script src="assets/js/fontawesome.min.js"></script>
	
    <script>
        // به‌روزرسانی تاریخ و زمان
        function updatePersianDateTime() {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                weekday: 'long',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                calendar: 'persian',
                numberingSystem: 'latn',
                hour12: false
            };
            
            try {
                const formatter = new Intl.DateTimeFormat('fa-IR', options);
                const dateTimeElements = document.querySelectorAll('#persian-date-time');
                dateTimeElements.forEach(element => {
                    element.textContent = formatter.format(now);
                });
            } catch (error) {
                console.log('Persian date formatting not supported');
                // Fallback to simple date
                const dateTimeElements = document.querySelectorAll('#persian-date-time');
                dateTimeElements.forEach(element => {
                    element.textContent = now.toLocaleString('fa-IR');
                });
            }
        }

        // به‌روزرسانی هر ثانیه
        updatePersianDateTime();
        setInterval(updatePersianDateTime, 1000);
    </script>
</body>
</html>