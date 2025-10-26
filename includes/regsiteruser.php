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
    $UserSysCode    = trim($_POST['UserSysCode'] ?? '');
    $UserMelli      = trim($_POST['UserMelli'] ?? '');
    $UserName       = trim($_POST['UserName'] ?? '');
    $UserFamily     = trim($_POST['UserFamily'] ?? '');
    $UserFather     = trim($_POST['UserFather'] ?? '');
    $UserMobile1    = trim($_POST['UserMobile1'] ?? '');
    $UserMobile2    = trim($_POST['UserMobile2'] ?? '');
    $UserDateBirth  = trim($_POST['UserDateBirth'] ?? '');
    $UserRegDate    = trim($_POST['UserRegDate'] ?? '');
    $UserActiveDate = trim($_POST['UserActiveDate'] ?? '');
    $UserSuspendDate = trim($_POST['UserSuspendDate'] ?? '');
    $UserStatus     = $_POST['UserStatus'] ?? 'عادی';
    $UserPlaceBirth = trim($_POST['UserPlaceBirth'] ?? '');
    $UserPlaceCerti = trim($_POST['UserPlaceCerti'] ?? '');
    $UserBloodType  = $_POST['UserBloodType'] ?? null;
    $UserEducation  = $_POST['UserEducation'] ?? null;
    $UserAddress    = trim($_POST['UserAddress'] ?? '');
    $UserZipCode    = trim($_POST['UserZipCode'] ?? '');
    $UserCity       = trim($_POST['UserCity'] ?? '');
    $UserImagePath  = null;
    
    // فیلدهای بانکی
    $UserBankName       = trim($_POST['UserBankName'] ?? '');
    $UserAccountNumber  = trim($_POST['UserAccountNumber'] ?? '');
    $UserCardNumber     = trim($_POST['UserCardNumber'] ?? '');
    $UserShebaNumber    = trim($_POST['UserShebaNumber'] ?? '');

    // Helpers
    $isDigits = function($s) { return $s !== '' && ctype_digit($s); };
    $isPersian = function($s) { return $s === '' || preg_match('/^[\x{0600}-\x{06FF}\s]+$/u', $s); };
    $isJalali = function($s) { return $s === '' || preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $s); };

    // Required fields
    if ($UserSysCode === '' || $UserMelli === '' || $UserName === '' || $UserFamily === '') {
        $errors[] = 'فیلدهای کدسیستمی، کدملی، نام و نام خانوادگی الزامی هستند.';
    } else {
        // Check for duplicate system code
        if (!$isDigits($UserSysCode)) {
            $errors['UserSysCode'] = 'کدسیستمی فقط عددی می باشد.';
        } else {
            $stmt = $conn->prepare("SELECT UserID FROM Users WHERE UserSysCode = ?");
            $stmt->bind_param("s", $UserSysCode);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors['UserSysCode'] = 'این کدسیستمی قبلا ثبت شده است';
                }
            } else {
                $errors[] = 'خطا در بررسی کدسیستمی: ' . $conn->error;
            }
            $stmt->close();
        }

        // Check for duplicate national code
        if (!$isDigits($UserMelli) || strlen($UserMelli) !== 10) {
            $errors['UserMelli'] = 'کد ملی باید عدد و دقیقا 10 رقم باشد.';
        } else {
            $stmt = $conn->prepare("SELECT UserID FROM Users WHERE UserMelli = ?");
            $stmt->bind_param("s", $UserMelli);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors['UserMelli'] = 'این کدملی قبلا ثبت شده است';
                }
            } else {
                $errors[] = 'خطا در بررسی کد ملی: ' . $conn->error;
            }
            $stmt->close();
        }
    }

    // Numeric validations
    if ($UserMobile1 !== '' && (!$isDigits($UserMobile1) || strlen($UserMobile1) !== 11)) {
        $errors['UserMobile1'] = 'موبایل 1 باید عدد و دقیقا 11 رقم باشد.';
    }
    if ($UserMobile2 !== '' && (!$isDigits($UserMobile2) || strlen($UserMobile2) !== 11)) {
        $errors['UserMobile2'] = 'موبایل 2 باید عدد و دقیقا 11 رقم باشد.';
    }
    if ($UserZipCode !== '' && (!$isDigits($UserZipCode) || strlen($UserZipCode) !== 10)) {
        $errors['UserZipCode'] = 'کد پستی باید عدد و دقیقا 10 رقم باشد.';
    }

    // Persian-only fields
    if ($UserName !== '' && !$isPersian($UserName)) {
        $errors['UserName'] = 'نام باید به صورت فارسی وارد شود.';
    }
    if ($UserFamily !== '' && !$isPersian($UserFamily)) {
        $errors['UserFamily'] = 'نام خانوادگی باید به صورت فارسی وارد شود.';
    }
    if ($UserFather !== '' && !$isPersian($UserFather)) {
        $errors['UserFather'] = 'نام پدر باید به صورت فارسی وارد شود.';
    }
    if ($UserPlaceBirth !== '' && !$isPersian($UserPlaceBirth)) {
        $errors['UserPlaceBirth'] = 'محل تولد باید به صورت فارسی وارد شود.';
    }
    if ($UserPlaceCerti !== '' && !$isPersian($UserPlaceCerti)) {
        $errors['UserPlaceCerti'] = 'محل صدور باید به صورت فارسی وارد شود.';
    }

    // Dates: Jalali format YYYY/MM/DD
    if ($UserDateBirth !== '' && !$isJalali($UserDateBirth)) {
        $errors['UserDateBirth'] = 'تاریخ تولد باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($UserRegDate !== '' && !$isJalali($UserRegDate)) {
        $errors['UserRegDate'] = 'تاریخ ثبت نام باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($UserActiveDate !== '' && !$isJalali($UserActiveDate)) {
        $errors['UserActiveDate'] = 'تاریخ ثبت فعال باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($UserSuspendDate !== '' && !$isJalali($UserSuspendDate)) {
        $errors['UserSuspendDate'] = 'تاریخ ثبت تعلیق باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }

    // اعتبارسنجی فیلدهای بانکی
    if ($UserAccountNumber !== '' && !$isDigits($UserAccountNumber)) {
        $errors['UserAccountNumber'] = 'شماره حساب باید عددی باشد.';
    }
    if ($UserCardNumber !== '' && !$isDigits($UserCardNumber)) {
        $errors['UserCardNumber'] = 'شماره کارت باید عددی باشد.';
    }
    if ($UserShebaNumber !== '' && !preg_match('/^IR\d{24}$/i', $UserShebaNumber)) {
        $errors['UserShebaNumber'] = 'شماره شبا باید با IR شروع شده و 26 رقم باشد.';
    }

    // File upload validation
    if (isset($_FILES['UserImage']) && $_FILES['UserImage']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['UserImage'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['UserImage'] = 'خطا در بارگذاری تصویر.';
        } else {
            $allowedExt = ['jpg','jpeg','png'];
            $maxSize = 500 * 1024;
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors['UserImage'] = 'فرمت مجاز تصویر فقط jpg, jpeg, png است.';
            }
            if ($file['size'] > $maxSize) {
                $errors['UserImage'] = 'حجم تصویر باید کمتر از 500 کیلوبایت باشد.';
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            $allowedMime = ['image/jpeg','image/png'];
            if (!in_array($mime, $allowedMime, true)) {
                $errors['UserImage'] = 'نوع فایل تصویر معتبر نیست.';
            }
            
            if (!isset($errors['UserImage'])) {
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
                    $UserImagePath = '/upload/' . basename($destPath);
                } else {
                    $errors['UserImage'] = 'انتقال فایل تصویر ناموفق بود.';
                }
            }
        }
    }

    if (empty($errors)) {
        // Convert Jalali dates to Gregorian for database
        $UserDateBirthGregorian = !empty($UserDateBirth) ? to_gregorian_date($UserDateBirth) : null;
        $UserRegDateGregorian = !empty($UserRegDate) ? to_gregorian_date($UserRegDate) : null;
        $UserActiveDateGregorian = !empty($UserActiveDate) ? to_gregorian_date($UserActiveDate) : null;
        $UserSuspendDateGregorian = !empty($UserSuspendDate) ? to_gregorian_date($UserSuspendDate) : null;

        // Get the next available UserID
        $result = $conn->query("SELECT MAX(UserID) + 1 AS next_id FROM users");
        $next_id = 1; // Default if table is empty
        if ($result && $row = $result->fetch_assoc()) {
            $next_id = $row['next_id'] ?: 1;
        }

        // Get form values for additional fields
        $UserPhone = trim($_POST['UserPhone'] ?? '');
        $UserNumbersh = trim($_POST['UserNumbersh'] ?? '');
        $UserMaritalStatus = trim($_POST['UserMaritalStatus'] ?? null);
        $UserDutyStatus = trim($_POST['UserDutyStatus'] ?? null);
        $UserJobWork = trim($_POST['UserJobWork'] ?? '');
        $UserEmail = trim($_POST['UserEmail'] ?? '');
        $UserOtherActivity = trim($_POST['UserOtherActivity'] ?? '');

        // Insert into database with all fields
        $sql = "INSERT INTO Users (
            UserID, UserSysCode, UserMelli, UserName, UserFamily, UserFather, 
            UserMobile1, UserMobile2, UserDateBirth, UserRegDate, UserActiveDate, 
            UserSuspendDate, UserStatus, UserPlaceBirth, UserPlaceCerti, 
            UserBloodType, UserEducation, UserAddress, UserZipCode, UserImage, 
            UserCity, UserBankName, UserAccountNumber, UserCardNumber, 
            UserShebaNumber, UserPhone, UserNumbersh, UserMaritalStatus, 
            UserDutyStatus, UserJobWork, UserEmail, UserOtherActivity
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "isssssssssssssssssssssssssssssss", 
            $next_id,
            $UserSysCode, $UserMelli, $UserName, $UserFamily, $UserFather, 
            $UserMobile1, $UserMobile2, $UserDateBirthGregorian, $UserRegDateGregorian, 
            $UserActiveDateGregorian, $UserSuspendDateGregorian, $UserStatus, 
            $UserPlaceBirth, $UserPlaceCerti, $UserBloodType, $UserEducation, 
            $UserAddress, $UserZipCode, $UserImagePath, $UserCity,
            $UserBankName, $UserAccountNumber, $UserCardNumber, $UserShebaNumber,
            $UserPhone, $UserNumbersh, $UserMaritalStatus, $UserDutyStatus, 
            $UserJobWork, $UserEmail, $UserOtherActivity
        );

        if ($stmt->execute()) {
            $success = 'ثبت نام با موفقیت انجام شد.';
            $_POST = [];
            unset($_FILES['UserImage']);
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
            <a href="exceluser.php" class="btn btn btn-primary btn-lg d-flex align-items-center ms-4">
                <span class="me-4">ثبت نام اکسل</span>
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
        $fieldErrors = ['UserSysCode', 'UserMelli', 'UserName', 'UserFamily', 'UserFather', 
                       'UserMobile1', 'UserMobile2', 'UserDateBirth', 'UserRegDate', 'UserActiveDate', 'UserSuspendDate',
                       'UserPlaceBirth', 'UserPlaceCerti', 'UserZipCode', 'UserImage',
                       'UserBankName', 'UserAccountNumber', 'UserCardNumber', 'UserShebaNumber'];
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

        <form method="post" enctype="multipart/form-data" id="UserForm">
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
                    <input type="file" class="form-control d-none" name="UserImage" id="UserImage" 
                           accept="image/*">
                    <button type="button" class="btn btn-outline-primary"
                            onclick="document.getElementById('UserImage').click();">
                        <i class="bi bi-upload"></i> آپلود تصویر
                    </button>
                </div>
                <?php if (isset($errors['UserImage'])): ?>
                    <div class="field-error"><?php echo $errors['UserImage']; ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-3">
                <!-- کدسیستمی -->
                <div class="col-md-3">
                    <label class="form-label">کدسیستمی <span class="text-danger">*</span></label>
                    <input name="UserSysCode" class="form-control <?php echo isset($errors['UserSysCode']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['UserSysCode'] ?? ''); ?>" 
                           required inputmode="numeric">
                    <?php if (isset($errors['UserSysCode'])): ?>
                        <div class="field-error"><?php echo $errors['UserSysCode']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- کدملی -->
                <div class="col-md-3">
                    <label class="form-label">کدملی <span class="text-danger">*</span></label>
                    <input name="UserMelli" class="form-control <?php echo isset($errors['UserMelli']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['UserMelli'] ?? ''); ?>" 
                           required inputmode="numeric" maxlength="10">
                    <?php if (isset($errors['UserMelli'])): ?>
                        <div class="field-error"><?php echo $errors['UserMelli']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام -->
                <div class="col-md-3">
                    <label class="form-label">نام <span class="text-danger">*</span></label>
                    <input name="UserName" class="form-control <?php echo isset($errors['UserName']) ? 'is-invalid' : ''; ?>" 
                           required pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['UserName'] ?? ''); ?>">
                    <?php if (isset($errors['UserName'])): ?>
                        <div class="field-error"><?php echo $errors['UserName']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام خانوادگی -->
                <div class="col-md-3">
                    <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                    <input name="UserFamily" class="form-control <?php echo isset($errors['UserFamily']) ? 'is-invalid' : ''; ?>" 
                           required pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['UserFamily'] ?? ''); ?>">
                    <?php if (isset($errors['UserFamily'])): ?>
                        <div class="field-error"><?php echo $errors['UserFamily']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام پدر -->
                <div class="col-md-3">
                    <label class="form-label">نام پدر</label>
                    <input name="UserFather" class="form-control <?php echo isset($errors['UserFather']) ? 'is-invalid' : ''; ?>" 
                           pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['UserFather'] ?? ''); ?>">
                    <?php if (isset($errors['UserFather'])): ?>
                        <div class="field-error"><?php echo $errors['UserFather']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- موبایل1 -->
                <div class="col-md-3">
                    <label class="form-label">موبایل1</label>
                    <input name="UserMobile1" class="form-control <?php echo isset($errors['UserMobile1']) ? 'is-invalid' : ''; ?>" 
                           inputmode="numeric" pattern="^\d{11}$" maxlength="11"
                           value="<?php echo htmlspecialchars($_POST['UserMobile1'] ?? ''); ?>">
                    <?php if (isset($errors['UserMobile1'])): ?>
                        <div class="field-error"><?php echo $errors['UserMobile1']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- موبایل 2 -->
                <div class="col-md-3">
                    <label class="form-label">موبایل2</label>
                    <input name="UserMobile2" class="form-control <?php echo isset($errors['UserMobile2']) ? 'is-invalid' : ''; ?>" 
                           inputmode="numeric" pattern="^\d{11}$" maxlength="11"
                           value="<?php echo htmlspecialchars($_POST['UserMobile2'] ?? ''); ?>">
                    <?php if (isset($errors['UserMobile2'])): ?>
                        <div class="field-error"><?php echo $errors['UserMobile2']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- وضعیت -->
                <div class="col-md-3">
                    <label class="form-label">وضعیت</label>
                    <select name="UserStatus" class="form-select">
                        <option value="عادی" <?php echo (isset($_POST['UserStatus']) && $_POST['UserStatus'] === 'عادی') ? 'selected' : ''; ?>>عادی</option>
                        <option value="فعال" <?php echo (isset($_POST['UserStatus']) && $_POST['UserStatus'] === 'فعال') ? 'selected' : ''; ?>>فعال</option>
                        <option value="تعلیق" <?php echo (isset($_POST['UserStatus']) && $_POST['UserStatus'] === 'تعلیق') ? 'selected' : ''; ?>>تعلیق</option>
                    </select>
                </div>

            </div>

            <!-- اطلاعات تکمیلی -->
            <div class="col-12 mt-4">
                <h5 class="border-bottom pb-2 mb-3">اطلاعات تکمیلی</h5>
                <div class="row g-3">
                    <!-- شماره شناسنامه -->
                    <div class="col-md-3">
                        <label class="form-label">شماره شناسنامه</label>
                        <input name="UserNumbersh" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['UserNumbersh'] ?? ''); ?>">
                    </div>

                    <!-- وضعیت تاهل -->
                    <div class="col-md-3">
                        <label class="form-label">وضعیت تاهل</label>
                        <select name="UserMaritalStatus" class="form-select">
                            <option value="">انتخاب کنید</option>
                            <option value="مجرد" <?php echo (isset($_POST['UserMaritalStatus']) && $_POST['UserMaritalStatus'] === 'مجرد') ? 'selected' : ''; ?>>مجرد</option>
                            <option value="متاهل" <?php echo (isset($_POST['UserMaritalStatus']) && $_POST['UserMaritalStatus'] === 'متاهل') ? 'selected' : ''; ?>>متاهل</option>
                        </select>
                    </div>

                    <!-- وضعیت خدمت وظیفه -->
                    <div class="col-md-3">
                        <label class="form-label">وضعیت خدمت وظیفه</label>
                        <select name="UserDutyStatus" class="form-select">
                            <option value="">انتخاب کنید</option>
                            <option value="در حین خدمت" <?php echo (isset($_POST['UserDutyStatus']) && $_POST['UserDutyStatus'] === 'در حین خدمت') ? 'selected' : ''; ?>>در حین خدمت</option>
                            <option value="کارت پایان خدمت" <?php echo (isset($_POST['UserDutyStatus']) && $_POST['UserDutyStatus'] === 'کارت پایان خدمت') ? 'selected' : ''; ?>>کارت پایان خدمت</option>
                            <option value="معاف" <?php echo (isset($_POST['UserDutyStatus']) && $_POST['UserDutyStatus'] === 'معاف') ? 'selected' : ''; ?>>معاف</option>
                            <option value="قبل از سن مشمولیت" <?php echo (isset($_POST['UserDutyStatus']) && $_POST['UserDutyStatus'] === 'قبل از سن مشمولیت') ? 'selected' : ''; ?>>قبل از سن مشمولیت</option>
                            <option value="خرید خدمت" <?php echo (isset($_POST['UserDutyStatus']) && $_POST['UserDutyStatus'] === 'خرید خدمت') ? 'selected' : ''; ?>>خرید خدمت</option>
                        </select>
                    </div>

                    <!-- شغل -->
                    <div class="col-md-3">
                        <label class="form-label">شغل</label>
                        <input name="UserJobWork" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['UserJobWork'] ?? ''); ?>">
                    </div>

                    <!-- تلفن ثابت -->
                    <div class="col-md-3">
                        <label class="form-label">تلفن ثابت</label>
                        <input name="UserPhone" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['UserPhone'] ?? ''); ?>">
                    </div>

                    <!-- ایمیل -->
                    <div class="col-md-3">
                        <label class="form-label">ایمیل</label>
                        <input type="email" name="UserEmail" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['UserEmail'] ?? ''); ?>">
                    </div>

                    <!-- گروه خونی -->
                    <div class="col-md-3">
                        <label class="form-label">گروه خونی</label>
                        <select name="UserBloodType" class="form-select">
                            <option value="">انتخاب نشده</option>
                            <?php
                            $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                            $selectedBlood = $_POST['UserBloodType'] ?? '';
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
                        <select name="UserEducation" class="form-select">
                            <option value="">انتخاب نشده</option>
                            <?php
                            $educations = [
                                'اول ابتدایی', 'دوم ابتدایی', 'سوم ابتدایی', 'چهارم ابتدایی', 
                                'پنجم ابتدایی', 'ششم ابتدایی', 'هفتم', 'هشتم', 'نهم', 'دهم', 
                                'یازدهم', 'دوازدهم', 'فارغ التحصیل', 'دانشجو', 'دیپلم', 
                                'فوق دیپلم', 'لیسانس', 'فوق لیسانس', 'دکتری', 'سایر'
                            ];
                            $selectedEdu = $_POST['UserEducation'] ?? '';
                            foreach ($educations as $edu) {
                                $selected = ($selectedEdu === $edu) ? 'selected' : '';
                                echo "<option value=\"$edu\" $selected>$edu</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- تاریخ تولد -->
                    <div class="col-md-3">
                        <label class="form-label">تاریخ تولد</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input type="text" name="UserDateBirth" class="form-control persian-date <?php echo isset($errors['UserDateBirth']) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($_POST['UserDateBirth'] ?? ''); ?>" 
                                   placeholder="1400/01/01">
                        </div>
                        <?php if (isset($errors['UserDateBirth'])): ?>
                            <div class="field-error"><?php echo $errors['UserDateBirth']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- تاریخ ثبت عادی -->
                    <div class="col-md-3">
                        <label class="form-label">تاریخ ثبت عادی</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input type="text" name="UserRegDate" class="form-control persian-date <?php echo isset($errors['UserRegDate']) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($_POST['UserRegDate'] ?? ''); ?>" 
                                   placeholder="1400/01/01">
                        </div>
                        <?php if (isset($errors['UserRegDate'])): ?>
                            <div class="field-error"><?php echo $errors['UserRegDate']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- تاریخ ثبت فعال -->
                    <div class="col-md-3">
                        <label class="form-label">تاریخ ثبت فعال</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input type="text" name="UserActiveDate" class="form-control persian-date <?php echo isset($errors['UserActiveDate']) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($_POST['UserActiveDate'] ?? ''); ?>" 
                                   placeholder="1400/01/01">
                        </div>
                        <?php if (isset($errors['UserActiveDate'])): ?>
                            <div class="field-error"><?php echo $errors['UserActiveDate']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- تاریخ ثبت تعلیق -->
                    <div class="col-md-3">
                        <label class="form-label">تاریخ ثبت تعلیق</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                            <input type="text" name="UserSuspendDate" class="form-control persian-date <?php echo isset($errors['UserSuspendDate']) ? 'is-invalid' : ''; ?>" 
                                   value="<?php echo htmlspecialchars($_POST['UserSuspendDate'] ?? ''); ?>" 
                                   placeholder="1400/01/01">
                        </div>
                        <?php if (isset($errors['UserSuspendDate'])): ?>
                            <div class="field-error"><?php echo $errors['UserSuspendDate']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- محل تولد -->
                    <div class="col-md-3">
                        <label class="form-label">محل تولد</label>
                        <input name="UserPlaceBirth" class="form-control <?php echo isset($errors['UserPlaceBirth']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['UserPlaceBirth'] ?? ''); ?>">
                        <?php if (isset($errors['UserPlaceBirth'])): ?>
                            <div class="field-error"><?php echo $errors['UserPlaceBirth']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- محل صدور -->
                    <div class="col-md-3">
                        <label class="form-label">محل صدور</label>
                        <input name="UserPlaceCerti" class="form-control <?php echo isset($errors['UserPlaceCerti']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['UserPlaceCerti'] ?? ''); ?>">
                        <?php if (isset($errors['UserPlaceCerti'])): ?>
                            <div class="field-error"><?php echo $errors['UserPlaceCerti']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- کدپستی -->
                    <div class="col-md-3">
                        <label class="form-label">کدپستی</label>
                        <input name="UserZipCode" class="form-control <?php echo isset($errors['UserZipCode']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['UserZipCode'] ?? ''); ?>" inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                        <?php if (isset($errors['UserZipCode'])): ?>
                            <div class="field-error"><?php echo $errors['UserZipCode']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- شهر -->
                    <div class="col-md-3">
                        <label class="form-label">شهر</label>
                        <input name="UserCity" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['UserCity'] ?? ''); ?>">
                    </div>
                     <!-- فعالیت دیگر -->
                    <div class="col-md-6">
                        <label class="form-label">فعالیت دیگر (در صورت وجود)</label>
                        <textarea name="UserOtherActivity" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['UserOtherActivity'] ?? ''); ?></textarea>
                        <small class="text-muted">در صورت انجام فعالیت ویژه مانند عضویت در هلال احمر و... در این قسمت وارد نمایید.</small>
                    </div>
                    <!-- آدرس -->
                    <div class="col-6">
                        <label class="form-label">آدرس</label>
                        <textarea name="UserAddress" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['UserAddress'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- بخش اطلاعات بانکی -->
            <div class="col-12 mt-4">
                <h5 class="border-bottom pb-2 mb-3">اطلاعات بانکی</h5>
                <div class="row g-3">
                    <!-- نام بانک -->
                    <div class="col-md-3">
                        <label class="form-label">نام بانک</label>
                        <select name="UserBankName" class="form-select">
                            <option value="">انتخاب نشده</option>
                            <?php
                            $selectedBank = $_POST['UserBankName'] ?? '';
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
                        <input name="UserAccountNumber" class="form-control <?php echo isset($errors['UserAccountNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['UserAccountNumber'] ?? ''); ?>" 
                               inputmode="numeric" maxlength="30">
                        <?php if (isset($errors['UserAccountNumber'])): ?>
                            <div class="field-error"><?php echo $errors['UserAccountNumber']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- شماره کارت -->
                    <div class="col-md-3">
                        <label class="form-label">شماره کارت</label>
                        <input name="UserCardNumber" class="form-control <?php echo isset($errors['UserCardNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['UserCardNumber'] ?? ''); ?>" 
                               inputmode="numeric" maxlength="16" placeholder="6037991234567890">
                        <?php if (isset($errors['UserCardNumber'])): ?>
                            <div class="field-error"><?php echo $errors['UserCardNumber']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- شماره شبا -->
                    <div class="col-md-3">
                        <label class="form-label">شماره شبا</label>
                        <input name="UserShebaNumber" class="form-control <?php echo isset($errors['UserShebaNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['UserShebaNumber'] ?? ''); ?>" 
                               maxlength="26" placeholder="IR120120000000003123456789">
                        <?php if (isset($errors['UserShebaNumber'])): ?>
                            <div class="field-error"><?php echo $errors['UserShebaNumber']; ?></div>
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
document.getElementById('UserImage').addEventListener('change', function(e) {
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
    const UserMelliInput = document.querySelector('input[name="UserMelli"]');
    const UserSysCodeInput = document.querySelector('input[name="UserSysCode"]');
    const UserShebaInput = document.querySelector('input[name="UserShebaNumber"]');
    
    // کدملی validation
    UserMelliInput.addEventListener('input', function() {
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
    UserSysCodeInput.addEventListener('input', function() {
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
    UserShebaInput.addEventListener('input', function() {
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