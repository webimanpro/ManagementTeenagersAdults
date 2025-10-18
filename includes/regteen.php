<?php
// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/jdf.php';

// لیست بانک‌های ایران
$iranian_banks = [
    'بانک ملی ایران',
    'بانک سپه',
    'بانک صنعت و معدن',
    'بانک کشاورزی',
    'بانک مسکن',
    'بانک توسعه صادرات ایران',
    'بانک توسعه تعاون',
    'پست بانک ایران',
    'بانک اقتصاد نوین',
    'بانک پارسیان',
    'بانک پاسارگاد',
    'بانک کارآفرین',
    'بانک سامان',
    'بانک سینا',
    'بانک خاورمیانه',
    'بانک شهر',
    'بانک دی',
    'بانک صادرات ایران',
    'بانک ملت',
    'بانک تجارت',
    'بانک رفاه کارگران',
    'بانک حکمت ایرانیان',
    'بانک گردشگری',
    'بانک ایران زمین',
    'بانک قرض الحسنه مهر',
    'بانک قرض الحسنه رسالت',
    'موسسه اعتباری کوثر',
    'موسسه اعتباری عسکریه'
];

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect inputs
    $TeenSysCode    = trim($_POST['TeenSysCode'] ?? '');
    $TeenMelli      = trim($_POST['TeenMelli'] ?? '');
    $TeenName       = trim($_POST['TeenName'] ?? '');
    $TeenFamily     = trim($_POST['TeenFamily'] ?? '');
    $TeenFather     = trim($_POST['TeenFather'] ?? '');
    $TeenMobile1    = trim($_POST['TeenMobile1'] ?? '');
    $TeenMobile2    = trim($_POST['TeenMobile2'] ?? '');
    $TeenDateBirth  = trim($_POST['TeenDateBirth'] ?? '');
    $TeenRegDate    = trim($_POST['TeenRegDate'] ?? '');
    $TeenActiveDate = trim($_POST['TeenActiveDate'] ?? '');
    $TeenSuspendDate = trim($_POST['TeenSuspendDate'] ?? '');
    $TeenStatus     = $_POST['TeenStatus'] ?? 'عادی';
    $TeenPlaceBirth = trim($_POST['TeenPlaceBirth'] ?? '');
    $TeenPlaceCerti = trim($_POST['TeenPlaceCerti'] ?? '');
    $TeenBloodType  = $_POST['TeenBloodType'] ?? null;
    $TeenEducation  = $_POST['TeenEducation'] ?? null;
    $TeenAddress    = trim($_POST['TeenAddress'] ?? '');
    $TeenZipCode    = trim($_POST['TeenZipCode'] ?? '');
    $TeenCity       = trim($_POST['TeenCity'] ?? '');
    $TeenImagePath  = null;
    
    // فیلدهای بانکی
    $TeenBankName       = trim($_POST['TeenBankName'] ?? '');
    $TeenAccountNumber  = trim($_POST['TeenAccountNumber'] ?? '');
    $TeenCardNumber     = trim($_POST['TeenCardNumber'] ?? '');
    $TeenShebaNumber    = trim($_POST['TeenShebaNumber'] ?? '');

    // Helpers
    $isDigits = function($s) { return $s !== '' && ctype_digit($s); };
    $isPersian = function($s) { return $s === '' || preg_match('/^[\x{0600}-\x{06FF}\s]+$/u', $s); };
    $isJalali = function($s) { return $s === '' || preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $s); };

    // Required fields
    if ($TeenSysCode === '' || $TeenMelli === '' || $TeenName === '' || $TeenFamily === '') {
        $errors[] = 'فیلدهای کدسیستمی، کدملی، نام و نام خانوادگی الزامی هستند.';
    } else {
        // Check for duplicate system code
        if (!$isDigits($TeenSysCode)) {
            $errors['TeenSysCode'] = 'کدسیستمی فقط عددی می باشد.';
        } else {
            $stmt = $conn->prepare("SELECT TeenID FROM Teen WHERE TeenSysCode = ?");
            $stmt->bind_param("s", $TeenSysCode);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors['TeenSysCode'] = 'این کدسیستمی قبلا ثبت شده است';
                }
            } else {
                $errors[] = 'خطا در بررسی کدسیستمی: ' . $conn->error;
            }
            $stmt->close();
        }

        // Check for duplicate national code
        if (!$isDigits($TeenMelli) || strlen($TeenMelli) !== 10) {
            $errors['TeenMelli'] = 'کد ملی باید عدد و دقیقا 10 رقم باشد.';
        } else {
            $stmt = $conn->prepare("SELECT TeenID FROM Teen WHERE TeenMelli = ?");
            $stmt->bind_param("s", $TeenMelli);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors['TeenMelli'] = 'این کدملی قبلا ثبت شده است';
                }
            } else {
                $errors[] = 'خطا در بررسی کد ملی: ' . $conn->error;
            }
            $stmt->close();
        }
    }

    // Numeric validations
    if ($TeenMobile1 !== '' && (!$isDigits($TeenMobile1) || strlen($TeenMobile1) !== 11)) {
        $errors['TeenMobile1'] = 'موبایل 1 باید عدد و دقیقا 11 رقم باشد.';
    }
    if ($TeenMobile2 !== '' && (!$isDigits($TeenMobile2) || strlen($TeenMobile2) !== 11)) {
        $errors['TeenMobile2'] = 'موبایل 2 باید عدد و دقیقا 11 رقم باشد.';
    }
    if ($TeenZipCode !== '' && (!$isDigits($TeenZipCode) || strlen($TeenZipCode) !== 10)) {
        $errors['TeenZipCode'] = 'کد پستی باید عدد و دقیقا 10 رقم باشد.';
    }

    // Persian-only fields
    if ($TeenName !== '' && !$isPersian($TeenName)) {
        $errors['TeenName'] = 'نام باید به صورت فارسی وارد شود.';
    }
    if ($TeenFamily !== '' && !$isPersian($TeenFamily)) {
        $errors['TeenFamily'] = 'نام خانوادگی باید به صورت فارسی وارد شود.';
    }
    if ($TeenFather !== '' && !$isPersian($TeenFather)) {
        $errors['TeenFather'] = 'نام پدر باید به صورت فارسی وارد شود.';
    }
    if ($TeenPlaceBirth !== '' && !$isPersian($TeenPlaceBirth)) {
        $errors['TeenPlaceBirth'] = 'محل تولد باید به صورت فارسی وارد شود.';
    }
    if ($TeenPlaceCerti !== '' && !$isPersian($TeenPlaceCerti)) {
        $errors['TeenPlaceCerti'] = 'محل صدور باید به صورت فارسی وارد شود.';
    }

    // Dates: Jalali format YYYY/MM/DD
    if ($TeenDateBirth !== '' && !$isJalali($TeenDateBirth)) {
        $errors['TeenDateBirth'] = 'تاریخ تولد باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($TeenRegDate !== '' && !$isJalali($TeenRegDate)) {
        $errors['TeenRegDate'] = 'تاریخ ثبت نام باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($TeenActiveDate !== '' && !$isJalali($TeenActiveDate)) {
        $errors['TeenActiveDate'] = 'تاریخ ثبت فعال باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($TeenSuspendDate !== '' && !$isJalali($TeenSuspendDate)) {
        $errors['TeenSuspendDate'] = 'تاریخ ثبت تعلیق باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }

    // اعتبارسنجی فیلدهای بانکی
    if ($TeenAccountNumber !== '' && !$isDigits($TeenAccountNumber)) {
        $errors['TeenAccountNumber'] = 'شماره حساب باید عددی باشد.';
    }
    if ($TeenCardNumber !== '' && !$isDigits($TeenCardNumber)) {
        $errors['TeenCardNumber'] = 'شماره کارت باید عددی باشد.';
    }
    if ($TeenShebaNumber !== '' && !preg_match('/^IR\d{24}$/i', $TeenShebaNumber)) {
        $errors['TeenShebaNumber'] = 'شماره شبا باید با IR شروع شده و 26 رقم باشد.';
    }

    // File upload validation
    if (isset($_FILES['TeenImage']) && $_FILES['TeenImage']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['TeenImage'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['TeenImage'] = 'خطا در بارگذاری تصویر.';
        } else {
            $allowedExt = ['jpg','jpeg','png'];
            $maxSize = 500 * 1024;
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors['TeenImage'] = 'فرمت مجاز تصویر فقط jpg, jpeg, png است.';
            }
            if ($file['size'] > $maxSize) {
                $errors['TeenImage'] = 'حجم تصویر باید کمتر از 500 کیلوبایت باشد.';
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            $allowedMime = ['image/jpeg','image/png'];
            if (!in_array($mime, $allowedMime, true)) {
                $errors['TeenImage'] = 'نوع فایل تصویر معتبر نیست.';
            }
            
            if (!isset($errors['TeenImage'])) {
                $uploadDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'upload';
                if (!is_dir($uploadDir)) { 
                    @mkdir($uploadDir, 0775, true); 
                }
                
                $originalName = basename($file['name']);
                $destPath = $uploadDir . DIRECTORY_SEPARATOR . $originalName;
                
                $counter = 1;
                $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                
                while (file_exists($destPath)) {
                    $newName = $nameWithoutExt . '_' . $counter . '.' . $extension;
                    $destPath = $uploadDir . DIRECTORY_SEPARATOR . $newName;
                    $counter++;
                }
                
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $TeenImagePath = '/upload/' . basename($destPath);
                } else {
                    $errors['TeenImage'] = 'انتقال فایل تصویر ناموفق بود.';
                }
            }
        }
    }

    if (empty($errors)) {
        // Convert Jalali dates to Gregorian for database
        $TeenDateBirthGregorian = !empty($TeenDateBirth) ? to_gregorian_date($TeenDateBirth) : null;
        $TeenRegDateGregorian = !empty($TeenRegDate) ? to_gregorian_date($TeenRegDate) : null;
        $TeenActiveDateGregorian = !empty($TeenActiveDate) ? to_gregorian_date($TeenActiveDate) : null;
        $TeenSuspendDateGregorian = !empty($TeenSuspendDate) ? to_gregorian_date($TeenSuspendDate) : null;

        // Insert into database
        $sql = "INSERT INTO teen (TeenSysCode, TeenMelli, TeenName, TeenFamily, TeenFather, TeenMobile1, TeenMobile2, "
             . "TeenDateBirth, TeenRegDate, TeenActiveDate, TeenSuspendDate, TeenStatus, TeenPlaceBirth, TeenPlaceCerti, TeenBloodType, TeenEducation, "
             . "TeenAddress, TeenZipCode, TeenImage, TeenCity, TeenBankName, TeenAccountNumber, TeenCardNumber, TeenShebaNumber) "
             . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssssssssssssss", 
            $TeenSysCode, $TeenMelli, $TeenName, $TeenFamily, $TeenFather, 
            $TeenMobile1, $TeenMobile2, $TeenDateBirthGregorian, $TeenRegDateGregorian, 
            $TeenActiveDateGregorian, $TeenSuspendDateGregorian, $TeenStatus, 
            $TeenPlaceBirth, $TeenPlaceCerti, $TeenBloodType, $TeenEducation, 
            $TeenAddress, $TeenZipCode, $TeenImagePath, $TeenCity,
            $TeenBankName, $TeenAccountNumber, $TeenCardNumber, $TeenShebaNumber
        );

        if ($stmt->execute()) {
            $success = 'ثبت نام با موفقیت انجام شد.';
            $_POST = [];
            unset($_FILES['TeenImage']);
        } else {
            $errors[] = 'خطا در ثبت اطلاعات: ' . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ثبت نام نوجوانان</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/persian-datepicker.min.css" />
    <style>
      .pdp-picker { z-index: 60000 !important; }
      .image-upload-container {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          margin-bottom: 2rem;
          padding: 2rem;
          border: 2px dashed #dee2e6;
          border-radius: 10px;
          background: #f8f9fa;
      }
      .preview-container {
          position: relative;
          margin-bottom: 1rem;
      }
      .upload-btn {
          margin-top: 1rem;
      }
      .field-error {
          color: #dc3545;
          font-size: 0.875rem;
          margin-top: 0.25rem;
          display: block;
      }
      .is-invalid {
          border-color: #dc3545 !important;
      }
      .bank-info-section {
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          color: white;
          border-radius: 10px;
          padding: 20px;
          margin: 20px 0;
      }
      .bank-info-section h5 {
          color: white;
          border-bottom: 2px solid rgba(255,255,255,0.3);
          padding-bottom: 10px;
      }
      .bank-info-section .form-label {
          color: white;
      }
    </style>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<div class="container" style="margin-top:100px; margin-bottom:40px;">
    <div class="content-box" style="text-align:right;">
        <div class="d-flex justify-content-start align-items-center mb-3">
            <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                <span class="me-2">بستن</span>
                <span aria-hidden="true" class="fs-5">×</span>
            </a>
            <h2 class="mb-0">ثبت نام نوجوانان</h2>
            <a href="excelteenadult.php" class="btn btn btn-primary btn-lg d-flex align-items-center ms-4">
                <span class="me-4">ورود اطلاعات با اکسل</span>
                <span aria-hidden="true" class="fs-5">×</span>
            </a>
        </div>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php
        $fieldErrors = ['TeenSysCode', 'TeenMelli', 'TeenName', 'TeenFamily', 'TeenFather', 
                       'TeenMobile1', 'TeenMobile2', 'TeenDateBirth', 'TeenRegDate', 'TeenActiveDate', 'TeenSuspendDate',
                       'TeenPlaceBirth', 'TeenPlaceCerti', 'TeenZipCode', 'TeenImage',
                       'TeenBankName', 'TeenAccountNumber', 'TeenCardNumber', 'TeenShebaNumber'];
        $generalErrors = array_filter($errors, function($key) use ($fieldErrors) {
            return !in_array($key, $fieldErrors);
        }, ARRAY_FILTER_USE_KEY);
        ?>
        
        <?php if ($generalErrors): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> <?php echo implode('<br>', $generalErrors); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" id="teenForm">
            <!-- Image Upload Section -->
            <div class="image-upload-container">
                <div class="preview-container">
                    <img src="../assets/img/avatarprofile.png" 
                         class="img-thumbnail rounded-circle" 
                         style="width: 150px; height: 150px; object-fit: cover;" 
                         id="previewImage" 
                         alt="تصویر پروفایل">
                </div>
                <div class="upload-btn">
                    <input type="file" class="form-control d-none" name="TeenImage" id="TeenImage" 
                           accept="image/*">
                    <button type="button" class="btn btn-outline-primary"
                            onclick="document.getElementById('TeenImage').click();">
                        <i class="bi bi-upload"></i> آپلود تصویر
                    </button>
                </div>
                <?php if (isset($errors['TeenImage'])): ?>
                    <div class="field-error"><?php echo $errors['TeenImage']; ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-3">
                <!-- کدسیستمی -->
                <div class="col-md-3">
                    <label class="form-label">کدسیستمی <span class="text-danger">*</span></label>
                    <input name="TeenSysCode" class="form-control <?php echo isset($errors['TeenSysCode']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['TeenSysCode'] ?? ''); ?>" 
                           required inputmode="numeric">
                    <?php if (isset($errors['TeenSysCode'])): ?>
                        <div class="field-error"><?php echo $errors['TeenSysCode']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- کدملی -->
                <div class="col-md-3">
                    <label class="form-label">کدملی <span class="text-danger">*</span></label>
                    <input name="TeenMelli" class="form-control <?php echo isset($errors['TeenMelli']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['TeenMelli'] ?? ''); ?>" 
                           required inputmode="numeric" maxlength="10">
                    <?php if (isset($errors['TeenMelli'])): ?>
                        <div class="field-error"><?php echo $errors['TeenMelli']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام -->
                <div class="col-md-3">
                    <label class="form-label">نام <span class="text-danger">*</span></label>
                    <input name="TeenName" class="form-control <?php echo isset($errors['TeenName']) ? 'is-invalid' : ''; ?>" 
                           required pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['TeenName'] ?? ''); ?>">
                    <?php if (isset($errors['TeenName'])): ?>
                        <div class="field-error"><?php echo $errors['TeenName']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام خانوادگی -->
                <div class="col-md-3">
                    <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                    <input name="TeenFamily" class="form-control <?php echo isset($errors['TeenFamily']) ? 'is-invalid' : ''; ?>" 
                           required pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['TeenFamily'] ?? ''); ?>">
                    <?php if (isset($errors['TeenFamily'])): ?>
                        <div class="field-error"><?php echo $errors['TeenFamily']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام پدر -->
                <div class="col-md-3">
                    <label class="form-label">نام پدر</label>
                    <input name="TeenFather" class="form-control <?php echo isset($errors['TeenFather']) ? 'is-invalid' : ''; ?>" 
                           pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['TeenFather'] ?? ''); ?>">
                    <?php if (isset($errors['TeenFather'])): ?>
                        <div class="field-error"><?php echo $errors['TeenFather']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- موبایل 1 -->
                <div class="col-md-3">
                    <label class="form-label">موبایل 1</label>
                    <input name="TeenMobile1" class="form-control <?php echo isset($errors['TeenMobile1']) ? 'is-invalid' : ''; ?>" 
                           inputmode="numeric" pattern="^\d{11}$" maxlength="11"
                           value="<?php echo htmlspecialchars($_POST['TeenMobile1'] ?? ''); ?>">
                    <?php if (isset($errors['TeenMobile1'])): ?>
                        <div class="field-error"><?php echo $errors['TeenMobile1']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- موبایل 2 -->
                <div class="col-md-3">
                    <label class="form-label">موبایل 2</label>
                    <input name="TeenMobile2" class="form-control <?php echo isset($errors['TeenMobile2']) ? 'is-invalid' : ''; ?>" 
                           inputmode="numeric" pattern="^\d{11}$" maxlength="11"
                           value="<?php echo htmlspecialchars($_POST['TeenMobile2'] ?? ''); ?>">
                    <?php if (isset($errors['TeenMobile2'])): ?>
                        <div class="field-error"><?php echo $errors['TeenMobile2']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- وضعیت -->
                <div class="col-md-3">
                    <label class="form-label">وضعیت</label>
                    <select name="TeenStatus" class="form-select">
                        <option value="عادی" <?php echo (isset($_POST['TeenStatus']) && $_POST['TeenStatus'] === 'عادی') ? 'selected' : ''; ?>>عادی</option>
                        <option value="فعال" <?php echo (isset($_POST['TeenStatus']) && $_POST['TeenStatus'] === 'فعال') ? 'selected' : ''; ?>>فعال</option>
                        <option value="تعلیق" <?php echo (isset($_POST['TeenStatus']) && $_POST['TeenStatus'] === 'تعلیق') ? 'selected' : ''; ?>>تعلیق</option>
                    </select>
                </div>

                <!-- تاریخ تولد -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ تولد</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" name="TeenDateBirth" class="form-control persian-date <?php echo isset($errors['TeenDateBirth']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['TeenDateBirth'] ?? ''); ?>" 
                               placeholder="1400/01/01">
                    </div>
                    <?php if (isset($errors['TeenDateBirth'])): ?>
                        <div class="field-error"><?php echo $errors['TeenDateBirth']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- تاریخ ثبت نام -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ ثبت عادی</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" name="TeenRegDate" class="form-control persian-date <?php echo isset($errors['TeenRegDate']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['TeenRegDate'] ?? ''); ?>" 
                               placeholder="1400/01/01">
                    </div>
                    <?php if (isset($errors['TeenRegDate'])): ?>
                        <div class="field-error"><?php echo $errors['TeenRegDate']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- تاریخ ثبت فعال -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ ثبت فعال</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" name="TeenActiveDate" class="form-control persian-date <?php echo isset($errors['TeenActiveDate']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['TeenActiveDate'] ?? ''); ?>" 
                               placeholder="1400/01/01">
                    </div>
                    <?php if (isset($errors['TeenActiveDate'])): ?>
                        <div class="field-error"><?php echo $errors['TeenActiveDate']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- تاریخ ثبت تعلیق -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ ثبت تعلیق</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" name="TeenSuspendDate" class="form-control persian-date <?php echo isset($errors['TeenSuspendDate']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['TeenSuspendDate'] ?? ''); ?>" 
                               placeholder="1400/01/01">
                    </div>
                    <?php if (isset($errors['TeenSuspendDate'])): ?>
                        <div class="field-error"><?php echo $errors['TeenSuspendDate']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- محل تولد -->
                <div class="col-md-3">
                    <label class="form-label">محل تولد</label>
                    <input name="TeenPlaceBirth" class="form-control <?php echo isset($errors['TeenPlaceBirth']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['TeenPlaceBirth'] ?? ''); ?>">
                    <?php if (isset($errors['TeenPlaceBirth'])): ?>
                        <div class="field-error"><?php echo $errors['TeenPlaceBirth']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- محل صدور -->
                <div class="col-md-3">
                    <label class="form-label">محل صدور</label>
                    <input name="TeenPlaceCerti" class="form-control <?php echo isset($errors['TeenPlaceCerti']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['TeenPlaceCerti'] ?? ''); ?>">
                    <?php if (isset($errors['TeenPlaceCerti'])): ?>
                        <div class="field-error"><?php echo $errors['TeenPlaceCerti']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- گروه خونی -->
                <div class="col-md-3">
                    <label class="form-label">گروه خونی</label>
                    <select name="TeenBloodType" class="form-select">
                        <option value="">انتخاب نشده</option>
                        <?php
                        $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                        $selectedBlood = $_POST['TeenBloodType'] ?? '';
                        foreach ($bloodTypes as $type) {
                            $selected = ($selectedBlood === $type) ? 'selected' : '';
                            echo "<option value=\"$type\" $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- تحصیلات -->
                <div class="col-md-3">
                    <label class="form-label">تحصیلات</label>
                    <select name="TeenEducation" class="form-select">
                        <option value="">انتخاب نشده</option>
                        <?php
                        $educations = [
                            'اول ابتدایی', 'دوم ابتدایی', 'سوم ابتدایی', 'چهارم ابتدایی', 
                            'پنجم ابتدایی', 'ششم ابتدایی', 'هفتم', 'هشتم', 'نهم', 'دهم', 
                            'یازدهم', 'دوازدهم', 'فارغ التحصیل', 'دانشجو', 'دیپلم', 
                            'فوق دیپلم', 'لیسانس', 'فوق لیسانس', 'دکتری', 'سایر'
                        ];
                        $selectedEdu = $_POST['TeenEducation'] ?? '';
                        foreach ($educations as $edu) {
                            $selected = ($selectedEdu === $edu) ? 'selected' : '';
                            echo "<option value=\"$edu\" $selected>$edu</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- کدپستی -->
                <div class="col-md-3">
                    <label class="form-label">کدپستی</label>
                    <input name="TeenZipCode" class="form-control <?php echo isset($errors['TeenZipCode']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['TeenZipCode'] ?? ''); ?>" inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                    <?php if (isset($errors['TeenZipCode'])): ?>
                        <div class="field-error"><?php echo $errors['TeenZipCode']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- شهر -->
                <div class="col-md-3">
                    <label class="form-label">شهر</label>
                    <input name="TeenCity" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['TeenCity'] ?? ''); ?>">
                </div>

                <!-- آدرس -->
                <div class="col-12">
                    <label class="form-label">آدرس</label>
                    <textarea name="TeenAddress" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['TeenAddress'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- بخش اطلاعات بانکی -->
						<div class="col-12 mt-4">
                        <h5 class="border-bottom pb-2 mb-3">اطلاعات بانکی</h5>
                <div class="row g-3">
                    <!-- نام بانک -->
                    <div class="col-md-3">
                        <label class="form-label">نام بانک</label>
                        <select name="TeenBankName" class="form-select">
                            <option value="">انتخاب نشده</option>
                            <?php
                            $selectedBank = $_POST['TeenBankName'] ?? '';
                            foreach ($iranian_banks as $bank) {
                                $selected = ($selectedBank === $bank) ? 'selected' : '';
                                echo "<option value=\"$bank\" $selected>$bank</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- شماره حساب -->
                    <div class="col-md-3">
                        <label class="form-label">شماره حساب</label>
                        <input name="TeenAccountNumber" class="form-control <?php echo isset($errors['TeenAccountNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['TeenAccountNumber'] ?? ''); ?>" 
                               inputmode="numeric" maxlength="30">
                        <?php if (isset($errors['TeenAccountNumber'])): ?>
                            <div class="field-error"><?php echo $errors['TeenAccountNumber']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- شماره کارت -->
                    <div class="col-md-3">
                        <label class="form-label">شماره کارت</label>
                        <input name="TeenCardNumber" class="form-control <?php echo isset($errors['TeenCardNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['TeenCardNumber'] ?? ''); ?>" 
                               inputmode="numeric" maxlength="16" placeholder="6037991234567890">
                        <?php if (isset($errors['TeenCardNumber'])): ?>
                            <div class="field-error"><?php echo $errors['TeenCardNumber']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- شماره شبا -->
                    <div class="col-md-3">
                        <label class="form-label">شماره شبا</label>
                        <input name="TeenShebaNumber" class="form-control <?php echo isset($errors['TeenShebaNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['TeenShebaNumber'] ?? ''); ?>" 
                               maxlength="26" placeholder="IR120120000000003123456789">
                        <?php if (isset($errors['TeenShebaNumber'])): ?>
                            <div class="field-error"><?php echo $errors['TeenShebaNumber']; ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="mt-4 text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-save"></i> ثبت اطلاعات
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/persian-date.js"></script>
<script src="../assets/js/persian-datepicker.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>

<script>
// Image preview functionality
document.getElementById('TeenImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const teenMelliInput = document.querySelector('input[name="TeenMelli"]');
    const teenSysCodeInput = document.querySelector('input[name="TeenSysCode"]');
    const teenShebaInput = document.querySelector('input[name="TeenShebaNumber"]');
    
    // کدملی validation
    teenMelliInput.addEventListener('input', function() {
        const value = this.value;
        if (value.length !== 10 && value.length > 0) {
            this.classList.add('is-invalid');
            showFieldError(this, 'کدملی باید 10 رقمی باشد');
        } else {
            this.classList.remove('is-invalid');
            removeFieldError(this);
        }
    });
    
    // کدسیستمی validation
    teenSysCodeInput.addEventListener('input', function() {
        const value = this.value;
        if (!/^\d*$/.test(value) && value.length > 0) {
            this.classList.add('is-invalid');
            showFieldError(this, 'کدسیستمی فقط عددی می باشد');
        } else {
            this.classList.remove('is-invalid');
            removeFieldError(this);
        }
    });
    
    // شماره شبا validation
    teenShebaInput.addEventListener('input', function() {
        const value = this.value.toUpperCase();
        this.value = value;
        
        if (value !== '' && !/^IR\d{24}$/.test(value)) {
            this.classList.add('is-invalid');
            showFieldError(this, 'شماره شبا باید با IR شروع شده و 26 رقم باشد');
        } else {
            this.classList.remove('is-invalid');
            removeFieldError(this);
        }
    });
    
    function showFieldError(input, message) {
        removeFieldError(input);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        input.parentNode.appendChild(errorDiv);
    }
    
    function removeFieldError(input) {
        const existingError = input.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
});

// Datepicker initialization
$(function(){
    $('.persian-date').persianDatepicker({
        format: 'YYYY/MM/DD',
        autoClose: true,
        initialValue: false,
        observer: true,
        calendar: { persian: { locale: 'fa' } }
    });

    // Calendar icon click handler
    $('.input-group-text').on('click', function() {
        var $input = $(this).siblings('input.persian-date');
        if ($input.length) {
            $input.trigger('focus');
            try { $input.persianDatepicker('show'); } catch (e) {}
        }
    });
});
</script>
</body>
</html>