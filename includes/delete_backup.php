<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    die('دسترسی غیرمجاز');
}

if (!isset($_GET['file'])) {
    $_SESSION['error'] = 'فایل مورد نظر مشخص نشده است';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

$backupDir = __DIR__ . '/../backups/';
$fileName = basename($_GET['file']);
$filePath = $backupDir . $fileName;

// Check if file exists and is in the backups directory
if (!file_exists($filePath) || !is_file($filePath) || 
    strpos(realpath($filePath), realpath($backupDir)) !== 0) {
    $_SESSION['error'] = 'فایل مورد نظر یافت نشد';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}

// Delete the file
if (unlink($filePath)) {
    $_SESSION['success'] = 'فایل پشتیبان با موفقیت حذف شد';
} else {
    $_SESSION['error'] = 'خطا در حذف فایل پشتیبان';
}

header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();
