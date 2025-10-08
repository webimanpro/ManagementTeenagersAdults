<?php

// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS SiteSettings (SettingKey VARCHAR(64) PRIMARY KEY, SettingValue TEXT) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

$errors = [];
$success = '';

// Helpers to read setting
function get_setting($conn, $key) {
    $stmt = $conn->prepare("SELECT SettingValue FROM SiteSettings WHERE SettingKey=?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $res = $stmt->get_result();
    $val = ($res && $res->num_rows) ? $res->fetch_assoc()['SettingValue'] : '';
    $stmt->close();
    return $val;
}

function set_setting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO SiteSettings (SettingKey, SettingValue) VALUES (?, ?) ON DUPLICATE KEY UPDATE SettingValue=VALUES(SettingValue)");
    $stmt->bind_param('ss', $key, $value);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

$curBg = get_setting($conn, 'background');

// Build background choices from assets/images
$imagesDir = realpath(__DIR__ . '/../assets/images');
$bgFiles = [];
if ($imagesDir && is_dir($imagesDir)) {
    foreach (['*.jpg','*.jpeg','*.png','*.webp'] as $pat) {
        foreach (glob($imagesDir . DIRECTORY_SEPARATOR . $pat) as $f) {
            $bgFiles[] = basename($f);
        }
    }
}
sort($bgFiles);

// Default background if not set
if (!$curBg) { 
    $curBg = '/assets/images/background-pic1.jpg'; 
    set_setting($conn, 'background', $curBg);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Background from select
    if (isset($_POST['background_name'])) {
        $bgName = $_POST['background_name'] ?? '';
        $bgPath = $curBg;
        
        if ($bgName && in_array($bgName, $bgFiles, true)) {
            $bgPath = '/assets/images/' . $bgName;
        } elseif ($bgName) {
            $errors[] = 'تصویر انتخابی معتبر نیست.';
        }

        if (!$errors) {
            if (set_setting($conn, 'background', $bgPath)) {
                $success = 'تنظیمات با موفقیت ذخیره شد. صفحه را رفرش کنید تا تغییرات اعمال شود.';
                $curBg = $bgPath;
            } else {
                $errors[] = 'خطا در ذخیره تنظیمات.';
            }
        }
    }
    
    // اضافه کردن بخش حذف اطلاعات دیتابیس
    if (isset($_POST['delete_database'])) {
        // حذف اطلاعات از جداول
        $tables = ['reportall', 'rollcalladult', 'rollcallteen', 'class', 'teen', 'adult'];
        $success_delete = true;
        
        foreach ($tables as $table) {
            $sql = "DELETE FROM $table";
            if (!$conn->query($sql)) {
                $success_delete = false;
                $errors[] = "خطا در حذف اطلاعات جدول $table: " . $conn->error;
                break;
            }
        }
        
        // ریست AUTO_INCREMENT برای هر جدول
        if ($success_delete) {
            $auto_increment_tables = [
                'adult' => 'AdultID',
                'class' => 'ClassID', 
                'rollcalladult' => 'RollcalladultID',
                'rollcallteen' => 'RollcallteenID',
                'teen' => 'TeenID',
                'reportall' => 'id'
            ];
            
            foreach ($auto_increment_tables as $table => $id_column) {
                $reset_sql = "ALTER TABLE `$table` AUTO_INCREMENT = 1";
                if (!$conn->query($reset_sql)) {
                    $success_delete = false;
                    $errors[] = "خطا در ریست شناسه جدول $table: " . $conn->error;
                    break;
                }
            }
        }
        
        if ($success_delete) {
            $success = 'تمامی اطلاعات با موفقیت حذف شدند و شناسه‌ها از 1 شروع خواهند شد.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>تنظیمات سیستم</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        body {
            background-image: url('<?php echo htmlspecialchars($curBg); ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-repeat: no-repeat;
        }
        
        .main-content {
            background: rgba(255, 255, 255, 0.95);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .bg-option {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 15px;
        }
        
        .bg-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .bg-option.selected {
            border-color: #4a6cf7;
        }
        
        .bg-thumbnail {
            width: 100%;
            height: 120px;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        .bg-name {
            padding: 8px;
            text-align: center;
            background: white;
            font-size: 0.85rem;
            border-top: 1px solid #eee;
        }
        
        .current-bg-indicator {
            background: #4a6cf7;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-top: 5px;
            display: inline-block;
        }

        .current-bg-info {
            background: #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-right: 4px solid #4a6cf7;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>

<div class="main-content">
    <div class="main-container">
        <div class="content-box">
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> بستن<span aria-hidden="true" class="fs-5">×</span>
            </a>
            
            <h1 class="mb-4"><i class="fas fa-cog"></i> تنظیمات سیستم</h1>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($errors): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo implode('<br>', $errors); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="post" id="settings-form">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <i class="fas fa-image"></i> تنظیمات ظاهری
                            </div>
                            <div class="card-body">
                                <div class="mb-4">
                                    <label class="form-label fw-bold">انتخاب تصویر پس‌زمینه</label>
                                    <p class="text-muted">تصویر انتخابی به عنوان پس‌زمینه تمام صفحات سیستم نمایش داده خواهد شد.</p>
                                    <!-- لیست تصاویر موجود -->
                                    <div class="mt-4">
                                        <label class="form-label fw-bold">انتخاب از تصاویر موجود:</label>
                                        <div class="row g-3 mt-2" id="bg-selector">
                                            <?php foreach($bgFiles as $file): 
                                                $bgPath = '/assets/images/' . $file;
                                                $isSelected = ($bgPath === $curBg);
                                            ?>
                                                <div class="col-md-3 col-sm-6">
                                                    <div class="bg-option <?php echo $isSelected ? 'selected' : ''; ?>" data-bg-name="<?php echo htmlspecialchars($file); ?>">
                                                        <div class="bg-thumbnail" style="background-image: url('<?php echo htmlspecialchars($bgPath); ?>')"></div>
                                                        <div class="bg-name"><?php echo htmlspecialchars($file); ?></div>
                                                        <?php if ($isSelected): ?>
                                                            <div class="current-bg-indicator">
                                                                <i class="fas fa-check"></i> فعلی
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        
                                        <!-- فیلد مخفی برای ارسال مقدار انتخاب شده -->
                                        <input type="hidden" name="background_name" id="selected_bg" value="<?php echo basename($curBg); ?>">
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> ذخیره تغییرات
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- بخش حذف اطلاعات دیتابیس -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="card border-danger">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-exclamation-triangle"></i> حذف کلیه اطلاعات سیستم
                        </div>
                        <div class="card-body">
                            <p class="card-text">
                                با کلیک بر روی دکمه زیر، کلیه اطلاعات زیر به طور کامل حذف خواهند شد:
                            </p>
                            <ul class="text-danger">
                                <li>اطلاعات نوجوانان (جدول teen)</li>
                                <li>اطلاعات بزرگسالان (جدول adult)</li>
                                <li>اطلاعات حضور و غیاب (جدول rollcallteen-rollcalladult)</li>
                                <li>اطلاعات دوره‌ها (جدول class)</li>
								<li>اطلاعات گزارشات (جدول reportall)</li>
                            </ul>
                            <p class="fw-bold">این عمل غیرقابل بازگشت است!</p>
                            
                            <button type="button" class="btn btn-danger btn-lg" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                <i class="fas fa-trash"></i> حذف اطلاعات دیتابیس
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal تأیید حذف -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle"></i> تایید حذف اطلاعات
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="fw-bold">آیا از حذف کلیه اطلاعات اطمینان دارید؟</p>
                <p>این عمل موارد زیر را حذف خواهد کرد:</p>
                <ul class="text-danger">
                    <li>تمامی اطلاعات نوجوانان</li>
                    <li>تمامی اطلاعات بزرگسالان</li>
                    <li>تمامی اطلاعات حضور و غیاب</li>
                    <li>تمامی اطلاعات دوره‌ها</li>
					<li>تمامی اطلاعات گزارشات</li>
                </ul>
                <p class="fw-bold text-danger">این عمل غیرقابل بازگشت است!</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times"></i> انصراف
                </button>
                <form method="post" style="display: inline;">
                    <button type="submit" name="delete_database" class="btn btn-danger">
                        <i class="fas fa-trash"></i> بله، حذف کن
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<!-- اضافه کردن فایل‌های JavaScript Bootstrap -->
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // انتخاب تصویر پس‌زمینه
    const bgOptions = document.querySelectorAll('.bg-option');
    const selectedBgInput = document.getElementById('selected_bg');
    
    bgOptions.forEach(option => {
        option.addEventListener('click', function() {
            // حذف انتخاب قبلی
            bgOptions.forEach(opt => opt.classList.remove('selected'));
            
            // انتخاب جدید
            this.classList.add('selected');
            const bgName = this.getAttribute('data-bg-name');
            selectedBgInput.value = bgName;
        });
    });

    // دیباگ Modal
    const deleteModal = document.getElementById('deleteModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            console.log('Modal در حال نمایش است');
        });
    }
});
</script>
</body>
</html>