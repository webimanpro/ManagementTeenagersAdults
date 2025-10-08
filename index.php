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
        <div class="main-container">
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
                                <a href="includes/regteen.php" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-user-plus"></i>
                                    ثبت نوجوان جدید
                                </a>
								<a href="includes/regadult.php" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-user-plus"></i>
                                    ثبت بزرگسالان جدید
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-3 col-sm-6">
                                <a href="includes/rollcallteen.php" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-clipboard-check"></i>
                                    حضور و غیاب نوجوانان
                                </a>
								<a href="includes/rollcalladult.php" class="btn btn-secondary btn-lg w-100">
                                    <i class="fas fa-user-check"></i>
                                    حضور و غیاب بزرگسالان
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
        // شبیه‌سازی آمار (در نسخه واقعی باید از AJAX استفاده شود)
        document.addEventListener('DOMContentLoaded', function() {
            // شبیه‌سازی اعداد تصادفی برای نمایش
            document.getElementById('teen-count').textContent = Math.floor(Math.random() * 50) + 20;
            document.getElementById('adult-count').textContent = Math.floor(Math.random() * 30) + 10;
            document.getElementById('course-count').textContent = Math.floor(Math.random() * 10) + 5;
            document.getElementById('attendance-count').textContent = Math.floor(Math.random() * 40) + 15;

            // انیمیشن شمارش
            function animateCount(element, finalValue, duration = 2000) {
                let start = 0;
                const increment = finalValue / (duration / 50);
                const timer = setInterval(() => {
                    start += increment;
                    if (start >= finalValue) {
                        element.textContent = finalValue;
                        clearInterval(timer);
                    } else {
                        element.textContent = Math.floor(start);
                    }
                }, 50);
            }

            // اجرای انیمیشن برای همه آمار
            const statElements = document.querySelectorAll('.stat-number');
            statElements.forEach(element => {
                const finalValue = parseInt(element.textContent);
                element.textContent = '0';
                setTimeout(() => {
                    animateCount(element, finalValue);
                }, 500);
            });
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