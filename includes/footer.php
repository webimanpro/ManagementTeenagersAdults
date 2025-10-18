<?php
// Include jdf library if not already included
if (!function_exists('jdate')) {
    require_once __DIR__ . '/jdf.php';
}
?>

<footer class="site-footer">
    <div class="container">
        <div class="footer-text">
            <i class="fas fa-copyright"></i>
            مدیریت نوجوانان و بزرگسالان - <?php echo jdate('Y'); ?> | نسخه 1.0
        </div>
    </div>
</footer>

<style>
/* Additional footer styles */
.site-footer {
    background: #111 !important;
    border-top: 1px solid #333;
}

.footer-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
    color: #eee;
}

.footer-text i {
    color: #4a6cf7;
}

/* Ensure footer stays at bottom */
body {
    position: relative;
    min-height: 100vh;
}

.main-content {
    flex: 1;
    padding-bottom: var(--footer-height);
}
</style>