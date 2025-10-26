<?php
/**
 * Management System - Functions File
 * 
 * This file contains all the essential functions used throughout the system.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to a specific URL
 * 
 * @param string $url The URL to redirect to
 * @return void
 */
function redirect($url) {
    header("Location: " . $url);
    exit();
}

/**
 * Sanitize input data
 * 
 * @param string $data The data to be sanitized
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) || isset($_SESSION['username']) || isset($_SESSION['login_user']);
}

/**
 * Require login to access a page
 * 
 * @return void
 */
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

/**
 * Check if user has specific role
 * 
 * @param string $role The role to check
 * @return bool True if user has the role, false otherwise
 */
function has_role($role) {
    if (!is_logged_in()) return false;
    return ($_SESSION['user_role'] ?? '') === $role;
}

/**
 * Require specific role to access a page
 * 
 * @param string $role The required role
 * @return void
 */
function require_role($role) {
    require_login();
    
    if (!has_role($role)) {
        $_SESSION['error'] = 'شما مجوز دسترسی به این صفحه را ندارید.';
        redirect('index.php');
    }
}

/**
 * Generate CSRF token
 * 
 * @return string The generated token
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * 
 * @param string $token The token to verify
 * @return bool True if token is valid, false otherwise
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get database connection
 * 
 * @return mysqli Database connection
 */
function get_db_connection() {
    global $conn;
    
    if (!isset($conn)) {
        require_once __DIR__ . '/../config/database.php';
    }
    
    return $conn;
}

/**
 * Get setting from database
 * 
 * @param string $key Setting key
 * @param string $default Default value if not found
 * @return string Setting value
 */
function get_setting($key, $default = '') {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare("SELECT SettingValue FROM SiteSettings WHERE SettingKey = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['SettingValue'];
    }
    
    return $default;
}

/**
 * Set setting in database
 * 
 * @param string $key Setting key
 * @param string $value Setting value
 * @return bool Success status
 */
function set_setting($key, $value) {
    $conn = get_db_connection();
    
    $stmt = $conn->prepare("INSERT INTO SiteSettings (SettingKey, SettingValue) VALUES (?, ?) ON DUPLICATE KEY UPDATE SettingValue = ?");
    $stmt->bind_param('sss', $key, $value, $value);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get background image URL
 * 
 * @return string Background image URL
 */
function get_background_image() {
    $bg = get_setting('background', '/assets/images/background-pic1.jpeg');
    
    // If it's a relative path, construct full URL
    if (strpos($bg, '/') === 0) {
        $rootBase = 'http://' . $_SERVER['HTTP_HOST'];
        $bg = rtrim($rootBase, '/') . $bg;
    }
    
    return $bg;
}

/**
 * Format Gregorian date to Persian (Jalali) date
 * 
 * @param string $date The Gregorian date (YYYY-MM-DD)
 * @param string $format The output format
 * @return string Formatted Persian date
 */
function to_persian_date($date, $format = 'Y/m/d') {
    // Check if the date is valid
    if (empty($date) || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') {
        return '';
    }
    
    // Extract date part only (in case it's datetime)
    $date = substr($date, 0, 10);
    
    // Load jdf functions if not already loaded
    if (!function_exists('gregorian_to_jalali')) {
        require_once __DIR__ . '/jdf.php';
    }
    
    // Convert Gregorian to Jalali using jdf.php functions
    list($g_year, $g_month, $g_day) = explode('-', $date);
    $j_date = gregorian_to_jalali($g_year, $g_month, $g_day);
    list($j_year, $j_month, $j_day) = $j_date;
    
    // Format the date according to the requested format
    $formatted = str_replace(
        ['Y', 'm', 'd'],
        [$j_year, sprintf('%02d', $j_month), sprintf('%02d', $j_day)],
        $format
    );
    
    return $formatted;
}

/**
 * Convert Persian (Jalali) date to Gregorian date
 * 
 * @param string $persian_date The Persian date (YYYY/MM/DD)
 * @return string Gregorian date (YYYY-MM-DD)
 */
function to_gregorian_date($persian_date) {
    if (empty($persian_date)) {
        return null;
    }
    
    // Load jdf functions if not already loaded
    if (!function_exists('jalali_to_gregorian')) {
        require_once __DIR__ . '/jdf.php';
    }
    
    // Convert Jalali to Gregorian using jdf.php functions
    list($j_year, $j_month, $j_day) = explode('/', $persian_date);
    $g_date = jalali_to_gregorian($j_year, $j_month, $j_day);
    list($g_year, $g_month, $g_day) = $g_date;
    
    return sprintf('%04d-%02d-%02d', $g_year, $g_month, $g_day);
}

/**
 * Log error to file
 * 
 * @param string $message The error message
 * @param string $level The error level (error, warning, info, etc.)
 * @return void
 */
function log_error($message, $level = 'error') {
    $log_file = __DIR__ . '/../logs/' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

/**
 * Display flash message
 * 
 * @param string $type The type of message (success, error, warning, info)
 * @param string $message The message to display
 * @return void
 */
function set_flash_message($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 * 
 * @return array|null The flash message or null if none exists
 */
function get_flash_message() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Display flash messages if any exist
 * 
 * @return void
 */
function display_flash_messages() {
    $flash = get_flash_message();
    if ($flash) {
        $type = $flash['type'];
        $message = $flash['message'];
        
        // Map type to Bootstrap alert classes
        $alert_classes = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'info' => 'alert-info'
        ];
        
        $alert_class = $alert_classes[$type] ?? 'alert-info';
        
        echo "<div class='alert $alert_class alert-dismissible fade show' role='alert'>";
        echo '<i class="fas fa-info-circle me-2"></i>';
        echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo "</div>";
    }
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the random string
 * @return string The generated string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $random_string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $random_string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $random_string;
}

/**
 * Upload file with validation
 * 
 * @param array $file The $_FILES array element
 * @param string $target_dir The target directory
 * @param array $allowed_types Allowed file types
 * @param int $max_size Maximum file size in bytes
 * @return array Result with status and message/filename
 */
function upload_file($file, $target_dir, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [
            'status' => 'error',
            'message' => 'خطا در آپلود فایل. لطفا مجددا تلاش کنید.'
        ];
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        return [
            'status' => 'error',
            'message' => 'حجم فایل نباید بیشتر از ' . ($max_size / 1024 / 1024) . ' مگابایت باشد.'
        ];
    }
    
    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Check if file type is allowed
    if (!in_array($file_extension, $allowed_types)) {
        return [
            'status' => 'error',
            'message' => 'نوع فایل مجاز نیست. انواع مجاز: ' . implode(', ', $allowed_types)
        ];
    }
    
    // Generate unique filename
    $filename = uniqid() . '.' . $file_extension;
    $target_file = rtrim($target_dir, '/') . '/' . $filename;
    
    // Create target directory if it doesn't exist
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return [
            'status' => 'success',
            'filename' => $filename,
            'path' => $target_file
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'خطا در ذخیره فایل. لطفا مجددا تلاش کنید.'
        ];
    }
}

/**
 * Get current URL
 * 
 * @return string The current URL
 */
function get_current_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    
    return "$protocol://$host$uri";
}

/**
 * Get client IP address
 * 
 * @return string The client IP address
 */
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    return $ip;
}

/**
 * Check if request is AJAX
 * 
 * @return bool True if request is AJAX, false otherwise
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Return JSON response
 * 
 * @param mixed $data The data to encode as JSON
 * @param int $status_code HTTP status code
 * @return void
 */
function json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Get current user information
 * 
 * @return array|null User data or null if not logged in
 */
function get_current_user_data() {
    if (!is_logged_in()) {
        return null;
    }
    
    // Get user data from session or database
    if (isset($_SESSION['user_data'])) {
        return $_SESSION['user_data'];
    }
    
    // If user data is not in session, get it from database
    try {
        $conn = get_db_connection();
        $user_id = $_SESSION['user_id'] ?? ($_SESSION['login_user'] ?? null);
        
        if ($user_id) {
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? OR username = ?");
            $stmt->bind_param('ss', $user_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            
            if ($user) {
                // Store in session for future use
                $_SESSION['user_data'] = $user;
                return $user;
            }
        }
    } catch (Exception $e) {
        error_log('Error getting user data: ' . $e->getMessage());
    }
    
    return null;
}

/**
 * Get list of available background images
 * 
 * @return array List of background image files
 */
function get_background_images() {
    $imagesDir = realpath(__DIR__ . '/../assets/images');
    $bgFiles = [];
    
    if ($imagesDir && is_dir($imagesDir)) {
        foreach (['*.jpg','*.jpeg','*.png','*.webp'] as $pattern) {
            foreach (glob($imagesDir . DIRECTORY_SEPARATOR . $pattern) as $file) {
                $bgFiles[] = basename($file);
            }
        }
    }
    
    sort($bgFiles);
    return $bgFiles;
}

/**
 * Validate and sanitize file name
 * 
 * @param string $filename File name to sanitize
 * @return string Sanitized file name
 */
function sanitize_filename($filename) {
    // Remove path information
    $filename = basename($filename);
    
    // Replace spaces with underscores
    $filename = str_replace(' ', '_', $filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    
    // Limit length
    if (strlen($filename) > 255) {
        $filename = substr($filename, 0, 255);
    }
    
    return $filename;
}

/**
 * Get file extension
 * 
 * @param string $filename File name
 * @return string File extension
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is image
 * 
 * @param string $file_path File path
 * @return bool True if file is image
 */
function is_image_file($file_path) {
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $extension = get_file_extension($file_path);
    
    return in_array($extension, $allowed_types);
}

// Alias for backward compatibility
if (!function_exists('get_current_user') && function_exists('get_current_user_data')) {
    function get_current_user() {
        return get_current_user_data();
    }
}

/**
 * Initialize system settings
 * 
 * @return void
 */
function initialize_system_settings() {
    $conn = get_db_connection();
    
    // Ensure settings table exists
    $conn->query("CREATE TABLE IF NOT EXISTS SiteSettings (
        SettingKey VARCHAR(64) PRIMARY KEY, 
        SettingValue TEXT
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Set default background if not set
    $current_bg = get_setting('background');
    if (empty($current_bg)) {
        set_setting('background', '/assets/images/background-pic1.jpeg');
    }
}
?>