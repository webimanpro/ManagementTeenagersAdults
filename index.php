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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت نوجوانان و بزرگسالان - <?php echo $page_title; ?></title>
    <link href="assets/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
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
        }

        .content-box {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            margin: 20px auto;
        }

        .welcome-message {
            text-align: center;
            padding: 2rem;
        }

        .welcome-icon {
            font-size: 4rem;
            color: #4a6cf7;
            margin-bottom: 1rem;
        }

        .welcome-title {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 700;
        }

        .welcome-text {
            font-size: 0.8rem;
            color: #555;
            line-height: 1.8;
            max-width: 600px;
            margin: 0 auto;
        }

        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #4a6cf7;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: #4a6cf7;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
        }

        .quick-actions .btn {
            margin: 5px;
            padding: 12px 20px;
            font-size: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .quick-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="main-content">
        <div class="content-box">
                <div class="welcome-message">
                    <div class="welcome-icon">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <h1 class="welcome-title">به مدیریت نوجوانان و بزرگسالان خوش آمدید</h1>
                    <p class="welcome-text">
                        این سیستم امکان مدیریت نوجوانان، بزرگسالان(برای ثبت نام - حضور و غیاب - دوره های آموزشی) را فراهم می‌کند.
                    </p>

                    <!-- آمار سریع -->
                    <div class="quick-stats">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number" id="teen-count">0</div>
                            <div class="stat-label">نوجوانان ثبت‌نام شده</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div class="stat-number" id="adult-count">0</div>
                            <div class="stat-label">بزرگسالان ثبت‌نام شده</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="stat-number" id="course-count">0</div>
                            <div class="stat-label">دوره‌های فعال</div>
                        </div>

                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-number" id="attendance-count">0</div>
                            <div class="stat-label">حضور و غیاب امروز</div>
                        </div>
                    </div>

                    <!-- دکمه‌های سریع -->
                    <div class="quick-actions mt-4">
                        <div class="row g-3 justify-content-center">
                            <?php if (isManagerOrAbove()): ?>
                            <div class="col-md-3 col-sm-6">
                                <a href="includes/regsiteruser.php" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-user-plus"></i>
                                    ثبت نام کاربران
                                </a>
								<a href="includes/listuser.php" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-user-plus"></i>
                                    لیست کاربران
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-3 col-sm-6">
                                <a href="includes/exceluser.php" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-clipboard-check"></i>
                                    ثبت نام اکسل
                                </a>
								<a href="includes/rollcalluser.php" class="btn btn-secondary btn-lg w-100">
                                    <i class="fas fa-user-check"></i>
                                    حضور و غیاب کاربران
                                </a>
                            </div>
                            <?php if (isManagerOrAbove()): ?>
                            <div class="col-md-3 col-sm-6">
                                <a href="includes/class.php" class="btn btn-info btn-lg w-100">
                                    <i class="fas fa-book"></i>
                                    مدیریت دوره‌ها
                                </a>
								<a href="includes/reportall.php" class="btn btn-info btn-lg w-100">
                                    <i class="fas fa-book"></i>
                                    گزارشات
                                </a>
                            </div>
                            <?php endif; ?>
                            <?php if (isAdmin()): ?>
                            <div class="col-md-3 col-sm-6">
                                <a href="includes/settingadmin.php" class="btn btn-warning btn-lg w-100">
                                    <i class="fas fa-cog"></i>
                                    تنظیمات سیستم
                                </a>
								<a href="includes/backuprestore.php" class="btn btn-warning btn-lg w-100">
                                    <i class="fas fa-cog"></i>
                                    پشتیبان گیری و بازیابی
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set the actual values from PHP variables
            document.getElementById('teen-count').textContent = <?php echo $teenCount; ?>;
            document.getElementById('adult-count').textContent = <?php echo $adultCount; ?>;
            document.getElementById('course-count').textContent = <?php echo $activeClassesCount; ?>;
            document.getElementById('attendance-count').textContent = <?php echo $todayAttendanceCount; ?>;
        });

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