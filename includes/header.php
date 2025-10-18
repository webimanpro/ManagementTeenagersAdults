<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) { 
    @session_start(); // اضافه کردن @ برای جلوگیری از warning
}

// Set headers before any output
@header('Content-Type: text/html; charset=utf-8');
ob_start(); // Start output buffering

// Set the base URL
$__rootBase = 'http://localhost';

// Load DB to read settings and user info
@require_once __DIR__ . '/../config/database.php';

// بارگذاری سیستم کنترل دسترسی
@require_once __DIR__ . '/check_access.php';
$currentUserRole = getCurrentUserRole();
$globalBg = null;
if (isset($conn) && $conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS SiteSettings (SettingKey VARCHAR(64) PRIMARY KEY, SettingValue TEXT) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $res = $conn->query("SELECT SettingValue FROM SiteSettings WHERE SettingKey = 'background'");
    if ($res && $res->num_rows) {
        $globalBg = $res->fetch_assoc()['SettingValue'];
    }
}

// اگر پس‌زمینه‌ای تنظیم نشده، از پیش‌فرض استفاده کن
if (!$globalBg) {
    $globalBg = '/assets/images/background-pic1.jpeg';
}

// Resolve logged-in user's full name from users table
$sessionUsername = $_SESSION['user_name'] ?? ($_SESSION['username'] ?? ($_SESSION['login_user'] ?? ''));
$displayName = $sessionUsername ?: 'کاربر';
if (isset($conn) && $conn && !empty($sessionUsername)) {
    $u = $sessionUsername;
    if ($stmt = $conn->prepare("SELECT full_name FROM users WHERE username=? LIMIT 1")) {
        $stmt->bind_param('s', $u);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) { $displayName = $row['full_name'] ?: $displayName; }
        }
        $stmt->close();
    }
}

// تابع برای دریافت تاریخ شمسی جاری
function getCurrentJalaliDate() {
    if (function_exists('jdate')) {
        return jdate('Y/m/d');
    } else {
        // Fallback conversion
        $now = new DateTime('now', new DateTimeZone('Asia/Tehran'));
        $gy = (int)$now->format('Y');
        $gm = (int)$now->format('m');
        $gd = (int)$now->format('d');
        
        $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * (int)($days / 12053));
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) { 
            $jy += (int)(($days - 1) / 365); 
            $days = ($days - 1) % 365; 
        }
        if ($days < 186) { 
            $jm = 1 + (int)($days / 31); 
            $jd = 1 + ($days % 31); 
        } else { 
            $jm = 7 + (int)(($days - 186) / 30); 
            $jd = 1 + (($days - 186) % 30); 
        }
        
        return $jy . '/' . str_pad($jm, 2, '0', STR_PAD_LEFT) . '/' . str_pad($jd, 2, '0', STR_PAD_LEFT);
    }
}
?>

<style>
/* استایل داینامیک برای پس‌زمینه */
body {
    background: url('<?php 
        $bgUrl = $globalBg;
        if (strpos($bgUrl, '/') === 0) {
            // اگر آدرس نسبی است، آدرس کامل بساز
            $bgUrl = rtrim($__rootBase, '/') . $bgUrl;
        }
        echo htmlspecialchars($bgUrl); 
    ?>') no-repeat center center fixed !important;
    background-size: cover !important;
    background-attachment: fixed !important;
}

/* برای صفحات خاص که پس‌زمینه مخصوص دارند، استایل جداگانه */
body.class-management-page {
    background: url('<?php echo htmlspecialchars($bgUrl); ?>') no-repeat center center fixed !important;
    background-size: cover !important;
}

/* بهبود خوانایی محتوا روی پس‌زمینه */
.content-box {
    background: rgba(255, 255, 255, 0.95) !important;
    backdrop-filter: blur(10px) !important;
}

.card {
    background: rgba(255, 255, 255, 0.98) !important;
}
/* استایل ویجت تاریخ در هدر */
.date-widget-header {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    margin-right: 10px;
}

.date-widget-header i {
    color: #fff;
    margin-left: 8px;
    font-size: 0.9em;
}

.date-widget-header span {
    color: #fff;
    font-weight: 600;
    font-size: 0.9em;
    direction: rtl;
}

/* responsive adjustments */
@media (max-width: 768px) {
    .date-widget-header {
        padding: 6px 8px;
        margin-right: 5px;
    }
    
    .date-widget-header span {
        font-size: 0.8em;
    }
}

@media (max-width: 480px) {
    .date-widget-header {
        display: none; /* در موبایل مخفی شود */
    }
}
</style>

<header class="main-header">
    <div class="header-container"> 
        <!-- Navigation Menu -->
        <nav class="nav-section right">
            <ul class="nav-links">
                <li class="nav-item">
                    <a href="<?php echo $__rootBase; ?>" class="nav-link" title="خانه">
                        <i class="bi bi-house-door"></i>
                    </a>
                </li>
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-users"></i>
                        نوجوانان
                        <i class="fas fa-chevron-down" style="font-size: 0.8em; margin-right: 5px;"></i>
                    </a>
                    <ul class="header-submenu">
                        <?php if (isManagerOrAbove()): ?>
                        <li><a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/regteen.php"><i class="fas fa-user-plus"></i> ثبت نام</a></li>
                        <li><a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/editteen.php"><i class="fas fa-edit"></i> ویرایش</a></li>
                        <li><a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/listteen.php"><i class="fas fa-list"></i> لیست</a></li>
                        <?php endif; ?>
                        <li><a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/rollcallteen.php"><i class="fas fa-clipboard-check"></i> حضور و غیاب</a></li>   
                    </ul>
                </li>
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-friends"></i>
                        بزرگسالان
                        <i class="fas fa-chevron-down" style="font-size: 0.8em; margin-right: 5px;"></i>
                    </a>
					<ul class="header-submenu">
                        <?php if (isManagerOrAbove()): ?>
                        <li><a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/regadult.php"><i class="fas fa-user-plus"></i> ثبت نام</a></li>
                        <li><a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/editadult.php"><i class="fas fa-edit"></i> ویرایش</a></li>
                        <li><a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/listadult.php"><i class="fas fa-list"></i> لیست</a></li>
                        <?php endif; ?>
                        <li><a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/rollcalladult.php"><i class="fas fa-clipboard-check"></i> حضور و غیاب</a></li>
                    </ul>
                </li>
                <?php if (isManagerOrAbove()): ?>
                <li class="nav-item">
                    <a href="<?php echo $__rootBase; ?>/includes/class.php" class="nav-link">
                        <i class="fas fa-book"></i>
                        دوره‌ها
                    </a>
                </li>
                <?php endif; ?>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a href="<?php echo $__rootBase; ?>/includes/reportall.php" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        گزارشات
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <!-- Desktop User Menu -->
        <nav class="nav-section left">
            <ul class="nav-links">
                <li class="nav-item has-submenu">
                    <a href="#" class="nav-link" role="button" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-user-circle"></i>
                        <span class="user-display-name">حساب کاربری</span>
                        <i class="fas fa-chevron-down" style="font-size: 0.8em; margin-right: 5px;"></i>
                    </a>
                    <ul class="header-submenu user-submenu">
                        <li class="user-info-header">
                            <div class="d-flex align-items-center px-3 py-2">
                                <i class="fas fa-user-circle me-2" style="font-size: 1.5rem;"></i>
                                <div>
                                    <div class="username"><?php echo htmlspecialchars($displayName); ?></div>
                                </div>
                            </div>
                        </li>
                        <li class="dropdown-divider"></li>
                        <li>
                            <a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/profileadmin.php">
                                <i class="fas fa-user me-2"></i>
                                پروفایل کاربری
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                        <li>
                            <a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/settingadmin.php">
                                <i class="fas fa-cog me-2"></i>
                                تنظیمات سیستم
                            </a>
                        </li>
                        <li>
                            <a class="header-submenu-link" href="<?php echo $__rootBase; ?>/includes/backuprestore.php">
                                <i class="fas fa-cloud-download-alt me-2"></i>
                                پشتیبان‌گیری و بازیابی
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="dropdown-divider"></li>
                        <li>
                            <a class="header-submenu-link text-danger" href="<?php echo $__rootBase; ?>/includes/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>
                                خروج از سیستم
                            </a>
                        </li>
                    </ul>
                        </li>
                
                <!-- ویجت تاریخ شمسی -->
                <li class="nav-item">
                    <div class="date-widget-header">
                        <span class="mb-3><i class="bi bi-calendar"></i>&nbsp; تاریخ امروز &nbsp;</span>
                        <span id="header-jalali-date"> <?php echo getCurrentJalaliDate(); ?></span>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</header>

<script>
// تابع برای به‌روزرسانی تاریخ شمسی
function updateJalaliDate() {
    const now = new Date();
    const dateElement = document.getElementById('header-jalali-date');
    
    if (dateElement) {
        try {
            // استفاده از Intl برای تاریخ شمسی
            const formatter = new Intl.DateTimeFormat('fa-IR', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                calendar: 'persian'
            });
            dateElement.textContent = formatter.format(now);
        } catch (error) {
            // Fallback در صورت عدم پشتیبانی مرورگر
            console.log('Persian calendar not supported, using fallback');
            // در اینجا می‌توانید از کتابخانه‌های خارجی استفاده کنید
        }
    }
}

// به‌روزرسانی اولیه و سپس هر دقیقه
updateJalaliDate();
setInterval(updateJalaliDate, 60000); // هر دقیقه به‌روزرسانی شود

// Function to update Persian date and time
function updatePersianDateTime() {
    const now = new Date();
    
    // Convert to Persian date
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
    }
}

// Update time every second
updatePersianDateTime();
setInterval(updatePersianDateTime, 1000);

// Enhanced submenu functionality
document.addEventListener('DOMContentLoaded', function () {
    const submenuItems = document.querySelectorAll('.main-header .has-submenu');
    
    submenuItems.forEach(function (item) {
        const link = item.querySelector('.nav-link');
        const submenu = item.querySelector('.header-submenu');
        
        if (!submenu || !link) return;

        // Hover behavior for desktop
        item.addEventListener('mouseenter', function () {
            if (window.innerWidth > 991) {
                showSubmenu();
            }
        });
        
        item.addEventListener('mouseleave', function () {
            if (window.innerWidth > 991) {
                hideSubmenu();
            }
        });

        // Click behavior for mobile/touch
        link.addEventListener('click', function (e) {
            if (window.innerWidth <= 991) {
                e.preventDefault();
                e.stopPropagation();
                
                const isOpen = submenu.style.display === 'block';
                
                // Close all other submenus
                document.querySelectorAll('.header-submenu').forEach(menu => {
                    if (menu !== submenu) {
                        menu.style.display = '';
                        menu.style.opacity = '';
                        menu.style.visibility = '';
                        menu.style.transform = '';
                    }
                });
                
                document.querySelectorAll('.nav-link').forEach(navLink => {
                    if (navLink !== link) {
                        navLink.setAttribute('aria-expanded', 'false');
                    }
                });
                
                if (isOpen) {
                    hideSubmenu();
                } else {
                    showSubmenu();
                }
            }
        });

        function showSubmenu() {
            submenu.style.display = 'block';
            // Force reflow
            submenu.offsetHeight;
            submenu.style.opacity = '1';
            submenu.style.visibility = 'visible';
            submenu.style.transform = 'translateY(0)';
            link.setAttribute('aria-expanded', 'true');
        }

        function hideSubmenu() {
            submenu.style.display = '';
            submenu.style.opacity = '';
            submenu.style.visibility = '';
            submenu.style.transform = '';
            link.setAttribute('aria-expanded', 'false');
        }
    });

    // Close submenus when clicking outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.has-submenu')) {
            document.querySelectorAll('.header-submenu').forEach(submenu => {
                submenu.style.display = '';
                submenu.style.opacity = '';
                submenu.style.visibility = '';
                submenu.style.transform = '';
            });
            document.querySelectorAll('.nav-link').forEach(link => {
                link.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 991) {
            // Reset all submenus on desktop
            document.querySelectorAll('.header-submenu').forEach(submenu => {
                submenu.style.display = '';
                submenu.style.opacity = '';
                submenu.style.visibility = '';
                submenu.style.transform = '';
            });
        }
    });
});

// Close submenus when pressing Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.header-submenu').forEach(submenu => {
            submenu.style.display = '';
            submenu.style.opacity = '';
            submenu.style.visibility = '';
            submenu.style.transform = '';
        });
        document.querySelectorAll('.nav-link').forEach(link => {
            link.setAttribute('aria-expanded', 'false');
        });
    }
});
</script>

<style>
/* Additional styles for header improvements */
.user-submenu {
    min-width: 240px !important;
}

.user-info-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    margin-bottom: 0.5rem;
}

.user-info-header .username {
    font-weight: 600;
    font-size: 0.9rem;
    color: #333;
}

.user-info-header .user-role {
    font-size: 0.75rem;
}

.user-display-name {
    margin: 0 8px;
    max-width: 120px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Improve submenu arrow positioning */
.main-header .header-submenu::before {
    right: 20px !important;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .user-display-name {
        max-width: 100px;
    }
}

@media (max-width: 1100px) {
    .user-display-name {
        display: none;
    }
    
    .nav-link span:not(.user-display-name) {
        font-size: 0.9rem;
    }
}

@media (max-width: 991px) {
    .main-header .nav-links {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .nav-item {
        height: auto;
    }
    
    .nav-link {
        padding: 0.5rem 1rem;
        height: auto;
    }
    
    .header-submenu {
        position: fixed !important;
        top: var(--header-height) !important;
        left: 50% !important;
        right: auto !important;
        transform: translateX(-50%) !important;
        width: 90% !important;
        max-width: 300px !important;
    }
}
</style>
<?php ob_flush(); // Flush the output buffer ?>
