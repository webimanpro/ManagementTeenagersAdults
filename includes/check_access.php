<?php
/**
 * سیستم کنترل دسترسی کاربران
 * 
 * سطوح دسترسی:
 * - admin (مدیرکل): دسترسی کامل به همه بخش‌ها
 * - manager (مدیر): دسترسی به نوجوانان، بزرگسالان و دوره‌ها
 * - user (کاربر): فقط حضور و غیاب در نوجوانان و بزرگسالان
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

// دریافت اطلاعات کاربر جاری
function getCurrentUserRole() {
    global $conn;
    
    $sessionUsername = $_SESSION['user_name'] ?? ($_SESSION['username'] ?? ($_SESSION['login_user'] ?? ''));
    $sessionUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
    
    $role = 'guest';
    
    if ($sessionUserId > 0) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $sessionUserId);
        if ($stmt->execute()) {
            $result = $stmt->get_result()->fetch_assoc();
            $role = $result['role'] ?? 'guest';
        }
        $stmt->close();
    } elseif ($sessionUsername) {
        $stmt = $conn->prepare("SELECT role FROM users WHERE username=? LIMIT 1");
        $stmt->bind_param('s', $sessionUsername);
        if ($stmt->execute()) {
            $result = $stmt->get_result()->fetch_assoc();
            $role = $result['role'] ?? 'guest';
        }
        $stmt->close();
    }
    
    return $role;
}

// بررسی دسترسی به صفحه
function checkPageAccess($pageName) {
    $userRole = getCurrentUserRole();
    
    // مدیرکل به همه چیز دسترسی دارد
    if ($userRole === 'admin') {
        return true;
    }
    
    // تعریف دسترسی‌ها بر اساس صفحات
    $accessControl = [
        // صفحات مدیریتی
        'profileadmin.php' => ['admin', 'manager', 'user'],
        'settingadmin.php' => ['admin'],
        'backuprestore.php' => ['admin'],
        'reportall.php' => ['admin'],  // فقط مدیرکل به گزارشات دسترسی دارد
        
        // نوجوانان
        'regteen.php' => ['admin', 'manager'],
        'editteen.php' => ['admin', 'manager'],
        'listteen.php' => ['admin', 'manager'],  // فقط مدیر و مدیرکل
        'rollcallteen.php' => ['admin', 'manager', 'user'],  // همه
        'presentteen.php' => ['admin', 'manager', 'user'],  // همه
        'viewteen.php' => ['admin', 'manager'],
        'reportteen.php' => ['admin'],  // فقط مدیرکل
        
        // بزرگسالان
        'regadult.php' => ['admin', 'manager'],
        'editadult.php' => ['admin', 'manager'],
        'listadult.php' => ['admin', 'manager'],  // فقط مدیر و مدیرکل
        'rollcalladult.php' => ['admin', 'manager', 'user'],  // همه
        'presentadult.php' => ['admin', 'manager', 'user'],  // همه
        'viewadult.php' => ['admin', 'manager'],
        'reportadult.php' => ['admin'],  // فقط مدیرکل
        
        // دوره‌ها
        'class.php' => ['admin', 'manager'],
        'classadd.php' => ['admin', 'manager'],
        'classedit.php' => ['admin', 'manager'],
        
        // سایر صفحات
        'index.php' => ['admin', 'manager', 'user'],
        'dashboard.php' => ['admin', 'manager', 'user'],
    ];
    
    // بررسی دسترسی
    if (isset($accessControl[$pageName])) {
        return in_array($userRole, $accessControl[$pageName]);
    }
    
    // اگر صفحه در لیست نبود، فقط admin دسترسی دارد
    return $userRole === 'admin';
}

// بررسی دسترسی و در صورت عدم دسترسی، هدایت به صفحه خطا
function requireAccess($pageName) {
    if (!checkPageAccess($pageName)) {
        header('HTTP/1.1 403 Forbidden');
        die('
        <!DOCTYPE html>
        <html lang="fa" dir="rtl">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>عدم دسترسی</title>
            <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet">
            <style>
                body { display: flex; align-items: center; justify-content: center; height: 100vh; background: #f8f9fa; }
                .error-box { text-align: center; padding: 40px; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .error-box i { font-size: 80px; color: #dc3545; margin-bottom: 20px; }
                .error-box h1 { font-size: 32px; margin-bottom: 15px; color: #333; }
                .error-box p { color: #666; margin-bottom: 25px; }
            </style>
        </head>
        <body>
            <div class="error-box">
                <i class="bi bi-shield-x"></i>
                <h1>عدم دسترسی</h1>
                <p>شما به این صفحه دسترسی ندارید.</p>
                <a href="../index.php" class="btn btn-primary">بازگشت به صفحه اصلی</a>
            </div>
        </body>
        </html>
        ');
    }
}

// تابع کمکی برای بررسی نقش کاربر
function hasRole($role) {
    $userRole = getCurrentUserRole();
    if (is_array($role)) {
        return in_array($userRole, $role);
    }
    return $userRole === $role;
}

// تابع کمکی برای بررسی اینکه آیا کاربر admin است
function isAdmin() {
    return getCurrentUserRole() === 'admin';
}

// تابع کمکی برای بررسی اینکه آیا کاربر manager یا admin است
function isManagerOrAbove() {
    $role = getCurrentUserRole();
    return in_array($role, ['admin', 'manager']);
}
