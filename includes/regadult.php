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
    $AdultSysCode    = trim($_POST['AdultSysCode'] ?? '');
    $AdultMelli      = trim($_POST['AdultMelli'] ?? '');
    $AdultName       = trim($_POST['AdultName'] ?? '');
    $AdultFamily     = trim($_POST['AdultFamily'] ?? '');
    $AdultFather     = trim($_POST['AdultFather'] ?? '');
    $AdultMobile1    = trim($_POST['AdultMobile1'] ?? '');
    $AdultMobile2    = trim($_POST['AdultMobile2'] ?? '');
    $AdultDateBirth  = trim($_POST['AdultDateBirth'] ?? '');
    $AdultRegDate    = trim($_POST['AdultRegDate'] ?? '');
    $AdultActiveDate = trim($_POST['AdultActiveDate'] ?? '');
    $AdultSuspendDate = trim($_POST['AdultSuspendDate'] ?? '');
    $AdultStatus     = $_POST['AdultStatus'] ?? 'عادی';
    $AdultPlaceBirth = trim($_POST['AdultPlaceBirth'] ?? '');
    $AdultPlaceCerti = trim($_POST['AdultPlaceCerti'] ?? '');
    $AdultBloodType  = $_POST['AdultBloodType'] ?? null;
    $AdultEducation  = $_POST['AdultEducation'] ?? null;
    $AdultAddress    = trim($_POST['AdultAddress'] ?? '');
    $AdultZipCode    = trim($_POST['AdultZipCode'] ?? '');
    $AdultCity       = trim($_POST['AdultCity'] ?? '');
    $AdultImagePath  = null;
    
    // فیلدهای بانکی
    $AdultBankName       = trim($_POST['AdultBankName'] ?? '');
    $AdultAccountNumber  = trim($_POST['AdultAccountNumber'] ?? '');
    $AdultCardNumber     = trim($_POST['AdultCardNumber'] ?? '');
    $AdultShebaNumber    = trim($_POST['AdultShebaNumber'] ?? '');

    // Helpers
    $isDigits = function($s) { return $s !== '' && ctype_digit($s); };
    $isPersian = function($s) { return $s === '' || preg_match('/^[\x{0600}-\x{06FF}\s]+$/u', $s); };
    $isJalali = function($s) { return $s === '' || preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $s); };

    // Required fields
    if ($AdultSysCode === '' || $AdultMelli === '' || $AdultName === '' || $AdultFamily === '') {
        $errors[] = 'فیلدهای کدسیستمی، کدملی، نام و نام خانوادگی الزامی هستند.';
    } else {
        // Check for duplicate system code
        if (!$isDigits($AdultSysCode)) {
            $errors['AdultSysCode'] = 'کدسیستمی فقط عددی می باشد.';
        } else {
            $stmt = $conn->prepare("SELECT AdultID FROM Adult WHERE AdultSysCode = ?");
            $stmt->bind_param("s", $AdultSysCode);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors['AdultSysCode'] = 'این کدسیستمی قبلا ثبت شده است';
                }
            } else {
                $errors[] = 'خطا در بررسی کدسیستمی: ' . $conn->error;
            }
            $stmt->close();
        }

        // Check for duplicate national code
        if (!$isDigits($AdultMelli) || strlen($AdultMelli) !== 10) {
            $errors['AdultMelli'] = 'کد ملی باید عدد و دقیقا 10 رقم باشد.';
        } else {
            $stmt = $conn->prepare("SELECT AdultID FROM Adult WHERE AdultMelli = ?");
            $stmt->bind_param("s", $AdultMelli);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $errors['AdultMelli'] = 'این کدملی قبلا ثبت شده است';
                }
            } else {
                $errors[] = 'خطا در بررسی کد ملی: ' . $conn->error;
            }
            $stmt->close();
        }
    }

    // Numeric validations
    if ($AdultMobile1 !== '' && (!$isDigits($AdultMobile1) || strlen($AdultMobile1) !== 11)) {
        $errors['AdultMobile1'] = 'موبایل 1 باید عدد و دقیقا 11 رقم باشد.';
    }
    if ($AdultMobile2 !== '' && (!$isDigits($AdultMobile2) || strlen($AdultMobile2) !== 11)) {
        $errors['AdultMobile2'] = 'موبایل 2 باید عدد و دقیقا 11 رقم باشد.';
    }
    if ($AdultZipCode !== '' && (!$isDigits($AdultZipCode) || strlen($AdultZipCode) !== 10)) {
        $errors['AdultZipCode'] = 'کد پستی باید عدد و دقیقا 10 رقم باشد.';
    }

    // Persian-only fields
    if ($AdultName !== '' && !$isPersian($AdultName)) {
        $errors['AdultName'] = 'نام باید به صورت فارسی وارد شود.';
    }
    if ($AdultFamily !== '' && !$isPersian($AdultFamily)) {
        $errors['AdultFamily'] = 'نام خانوادگی باید به صورت فارسی وارد شود.';
    }
    if ($AdultFather !== '' && !$isPersian($AdultFather)) {
        $errors['AdultFather'] = 'نام پدر باید به صورت فارسی وارد شود.';
    }
    if ($AdultPlaceBirth !== '' && !$isPersian($AdultPlaceBirth)) {
        $errors['AdultPlaceBirth'] = 'محل تولد باید به صورت فارسی وارد شود.';
    }
    if ($AdultPlaceCerti !== '' && !$isPersian($AdultPlaceCerti)) {
        $errors['AdultPlaceCerti'] = 'محل صدور باید به صورت فارسی وارد شود.';
    }

    // Dates: Jalali format YYYY/MM/DD
    if ($AdultDateBirth !== '' && !$isJalali($AdultDateBirth)) {
        $errors['AdultDateBirth'] = 'تاریخ تولد باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($AdultRegDate !== '' && !$isJalali($AdultRegDate)) {
        $errors['AdultRegDate'] = 'تاریخ ثبت نام باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($AdultActiveDate !== '' && !$isJalali($AdultActiveDate)) {
        $errors['AdultActiveDate'] = 'تاریخ ثبت فعال باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }
    if ($AdultSuspendDate !== '' && !$isJalali($AdultSuspendDate)) {
        $errors['AdultSuspendDate'] = 'تاریخ ثبت تعلیق باید به صورت شمسی و فرمت 1400/01/01 باشد.';
    }

    // اعتبارسنجی فیلدهای بانکی
    if ($AdultAccountNumber !== '' && !$isDigits($AdultAccountNumber)) {
        $errors['AdultAccountNumber'] = 'شماره حساب باید عددی باشد.';
    }
    if ($AdultCardNumber !== '' && !$isDigits($AdultCardNumber)) {
        $errors['AdultCardNumber'] = 'شماره کارت باید عددی باشد.';
    }
    if ($AdultShebaNumber !== '' && !preg_match('/^IR\d{24}$/i', $AdultShebaNumber)) {
        $errors['AdultShebaNumber'] = 'شماره شبا باید با IR شروع شده و 26 رقم باشد.';
    }

    // File upload validation
    if (isset($_FILES['AdultImage']) && $_FILES['AdultImage']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['AdultImage'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors['AdultImage'] = 'خطا در بارگذاری تصویر.';
        } else {
            $allowedExt = ['jpg','jpeg','png'];
            $maxSize = 500 * 1024;
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt, true)) {
                $errors['AdultImage'] = 'فرمت مجاز تصویر فقط jpg, jpeg, png است.';
            }
            if ($file['size'] > $maxSize) {
                $errors['AdultImage'] = 'حجم تصویر باید کمتر از 500 کیلوبایت باشد.';
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($file['tmp_name']);
            $allowedMime = ['image/jpeg','image/png'];
            if (!in_array($mime, $allowedMime, true)) {
                $errors['AdultImage'] = 'نوع فایل تصویر معتبر نیست.';
            }
            
            if (!isset($errors['AdultImage'])) {
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
                    $AdultImagePath = '/upload/' . basename($destPath);
                } else {
                    $errors['AdultImage'] = 'انتقال فایل تصویر ناموفق بود.';
                }
            }
        }
    }

    if (empty($errors)) {
        // Convert Jalali dates to Gregorian for database
        $AdultDateBirthGregorian = !empty($AdultDateBirth) ? to_gregorian_date($AdultDateBirth) : null;
        $AdultRegDateGregorian = !empty($AdultRegDate) ? to_gregorian_date($AdultRegDate) : null;
        $AdultActiveDateGregorian = !empty($AdultActiveDate) ? to_gregorian_date($AdultActiveDate) : null;
        $AdultSuspendDateGregorian = !empty($AdultSuspendDate) ? to_gregorian_date($AdultSuspendDate) : null;

        // Insert into database
        $sql = "INSERT INTO Adult (AdultSysCode, AdultMelli, AdultName, AdultFamily, AdultFather, AdultMobile1, AdultMobile2, "
             . "AdultDateBirth, AdultRegDate, AdultActiveDate, AdultSuspendDate, AdultStatus, AdultPlaceBirth, AdultPlaceCerti, AdultBloodType, AdultEducation, "
             . "AdultAddress, AdultZipCode, AdultImage, AdultCity, AdultBankName, AdultAccountNumber, AdultCardNumber, AdultShebaNumber) "
             . "VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssssssssssssssss", 
            $AdultSysCode, $AdultMelli, $AdultName, $AdultFamily, $AdultFather, 
            $AdultMobile1, $AdultMobile2, $AdultDateBirthGregorian, $AdultRegDateGregorian, 
            $AdultActiveDateGregorian, $AdultSuspendDateGregorian, $AdultStatus, 
            $AdultPlaceBirth, $AdultPlaceCerti, $AdultBloodType, $AdultEducation, 
            $AdultAddress, $AdultZipCode, $AdultImagePath, $AdultCity,
            $AdultBankName, $AdultAccountNumber, $AdultCardNumber, $AdultShebaNumber
        );

        if ($stmt->execute()) {
            $success = 'ثبت نام با موفقیت انجام شد.';
            $_POST = [];
            unset($_FILES['AdultImage']);
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
    <title>ثبت نام بزرگسالان</title>
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
            <h2 class="mb-0">ثبت نام بزرگسالان</h2>
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
        $fieldErrors = ['AdultSysCode', 'AdultMelli', 'AdultName', 'AdultFamily', 'AdultFather', 
                       'AdultMobile1', 'AdultMobile2', 'AdultDateBirth', 'AdultRegDate', 'AdultActiveDate', 'AdultSuspendDate',
                       'AdultPlaceBirth', 'AdultPlaceCerti', 'AdultZipCode', 'AdultImage',
                       'AdultBankName', 'AdultAccountNumber', 'AdultCardNumber', 'AdultShebaNumber'];
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

        <form method="post" enctype="multipart/form-data" id="AdultForm">
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
                    <input type="file" class="form-control d-none" name="AdultImage" id="AdultImage" 
                           accept="image/*">
                    <button type="button" class="btn btn-outline-primary"
                            onclick="document.getElementById('AdultImage').click();">
                        <i class="bi bi-upload"></i> آپلود تصویر
                    </button>
                </div>
                <?php if (isset($errors['AdultImage'])): ?>
                    <div class="field-error"><?php echo $errors['AdultImage']; ?></div>
                <?php endif; ?>
            </div>

            <div class="row g-3">
                <!-- کدسیستمی -->
                <div class="col-md-3">
                    <label class="form-label">کدسیستمی <span class="text-danger">*</span></label>
                    <input name="AdultSysCode" class="form-control <?php echo isset($errors['AdultSysCode']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['AdultSysCode'] ?? ''); ?>" 
                           required inputmode="numeric">
                    <?php if (isset($errors['AdultSysCode'])): ?>
                        <div class="field-error"><?php echo $errors['AdultSysCode']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- کدملی -->
                <div class="col-md-3">
                    <label class="form-label">کدملی <span class="text-danger">*</span></label>
                    <input name="AdultMelli" class="form-control <?php echo isset($errors['AdultMelli']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['AdultMelli'] ?? ''); ?>" 
                           required inputmode="numeric" maxlength="10">
                    <?php if (isset($errors['AdultMelli'])): ?>
                        <div class="field-error"><?php echo $errors['AdultMelli']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام -->
                <div class="col-md-3">
                    <label class="form-label">نام <span class="text-danger">*</span></label>
                    <input name="AdultName" class="form-control <?php echo isset($errors['AdultName']) ? 'is-invalid' : ''; ?>" 
                           required pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['AdultName'] ?? ''); ?>">
                    <?php if (isset($errors['AdultName'])): ?>
                        <div class="field-error"><?php echo $errors['AdultName']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام خانوادگی -->
                <div class="col-md-3">
                    <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                    <input name="AdultFamily" class="form-control <?php echo isset($errors['AdultFamily']) ? 'is-invalid' : ''; ?>" 
                           required pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['AdultFamily'] ?? ''); ?>">
                    <?php if (isset($errors['AdultFamily'])): ?>
                        <div class="field-error"><?php echo $errors['AdultFamily']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- نام پدر -->
                <div class="col-md-3">
                    <label class="form-label">نام پدر</label>
                    <input name="AdultFather" class="form-control <?php echo isset($errors['AdultFather']) ? 'is-invalid' : ''; ?>" 
                           pattern="^[\u0600-\u06FF\s]+$" 
                           value="<?php echo htmlspecialchars($_POST['AdultFather'] ?? ''); ?>">
                    <?php if (isset($errors['AdultFather'])): ?>
                        <div class="field-error"><?php echo $errors['AdultFather']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- موبایل 1 -->
                <div class="col-md-3">
                    <label class="form-label">موبایل 1</label>
                    <input name="AdultMobile1" class="form-control <?php echo isset($errors['AdultMobile1']) ? 'is-invalid' : ''; ?>" 
                           inputmode="numeric" pattern="^\d{11}$" maxlength="11"
                           value="<?php echo htmlspecialchars($_POST['AdultMobile1'] ?? ''); ?>">
                    <?php if (isset($errors['AdultMobile1'])): ?>
                        <div class="field-error"><?php echo $errors['AdultMobile1']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- موبایل 2 -->
                <div class="col-md-3">
                    <label class="form-label">موبایل 2</label>
                    <input name="AdultMobile2" class="form-control <?php echo isset($errors['AdultMobile2']) ? 'is-invalid' : ''; ?>" 
                           inputmode="numeric" pattern="^\d{11}$" maxlength="11"
                           value="<?php echo htmlspecialchars($_POST['AdultMobile2'] ?? ''); ?>">
                    <?php if (isset($errors['AdultMobile2'])): ?>
                        <div class="field-error"><?php echo $errors['AdultMobile2']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- وضعیت -->
                <div class="col-md-3">
                    <label class="form-label">وضعیت</label>
                    <select name="AdultStatus" class="form-select">
                        <option value="عادی" <?php echo (isset($_POST['AdultStatus']) && $_POST['AdultStatus'] === 'عادی') ? 'selected' : ''; ?>>عادی</option>
                        <option value="فعال" <?php echo (isset($_POST['AdultStatus']) && $_POST['AdultStatus'] === 'فعال') ? 'selected' : ''; ?>>فعال</option>
                        <option value="تعلیق" <?php echo (isset($_POST['AdultStatus']) && $_POST['AdultStatus'] === 'تعلیق') ? 'selected' : ''; ?>>تعلیق</option>
                    </select>
                </div>

                <!-- تاریخ تولد -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ تولد</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" name="AdultDateBirth" class="form-control persian-date <?php echo isset($errors['AdultDateBirth']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['AdultDateBirth'] ?? ''); ?>" 
                               placeholder="1400/01/01">
                    </div>
                    <?php if (isset($errors['AdultDateBirth'])): ?>
                        <div class="field-error"><?php echo $errors['AdultDateBirth']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- تاریخ ثبت نام -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ ثبت عادی</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" name="AdultRegDate" class="form-control persian-date <?php echo isset($errors['AdultRegDate']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['AdultRegDate'] ?? ''); ?>" 
                               placeholder="1400/01/01">
                    </div>
                    <?php if (isset($errors['AdultRegDate'])): ?>
                        <div class="field-error"><?php echo $errors['AdultRegDate']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- تاریخ ثبت فعال -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ ثبت فعال</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" name="AdultActiveDate" class="form-control persian-date <?php echo isset($errors['AdultActiveDate']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['AdultActiveDate'] ?? ''); ?>" 
                               placeholder="1400/01/01">
                    </div>
                    <?php if (isset($errors['AdultActiveDate'])): ?>
                        <div class="field-error"><?php echo $errors['AdultActiveDate']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- تاریخ ثبت تعلیق -->
                <div class="col-md-3">
                    <label class="form-label">تاریخ ثبت تعلیق</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" name="AdultSuspendDate" class="form-control persian-date <?php echo isset($errors['AdultSuspendDate']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['AdultSuspendDate'] ?? ''); ?>" 
                               placeholder="1400/01/01">
                    </div>
                    <?php if (isset($errors['AdultSuspendDate'])): ?>
                        <div class="field-error"><?php echo $errors['AdultSuspendDate']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- محل تولد -->
                <div class="col-md-3">
                    <label class="form-label">محل تولد</label>
                    <input name="AdultPlaceBirth" class="form-control <?php echo isset($errors['AdultPlaceBirth']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['AdultPlaceBirth'] ?? ''); ?>">
                    <?php if (isset($errors['AdultPlaceBirth'])): ?>
                        <div class="field-error"><?php echo $errors['AdultPlaceBirth']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- محل صدور -->
                <div class="col-md-3">
                    <label class="form-label">محل صدور</label>
                    <input name="AdultPlaceCerti" class="form-control <?php echo isset($errors['AdultPlaceCerti']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['AdultPlaceCerti'] ?? ''); ?>">
                    <?php if (isset($errors['AdultPlaceCerti'])): ?>
                        <div class="field-error"><?php echo $errors['AdultPlaceCerti']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- گروه خونی -->
                <div class="col-md-3">
                    <label class="form-label">گروه خونی</label>
                    <select name="AdultBloodType" class="form-select">
                        <option value="">انتخاب نشده</option>
                        <?php
                        $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                        $selectedBlood = $_POST['AdultBloodType'] ?? '';
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
                    <select name="AdultEducation" class="form-select">
                        <option value="">انتخاب نشده</option>
                        <?php
                        $educations = [
                            'اول ابتدایی', 'دوم ابتدایی', 'سوم ابتدایی', 'چهارم ابتدایی', 
                            'پنجم ابتدایی', 'ششم ابتدایی', 'هفتم', 'هشتم', 'نهم', 'دهم', 
                            'یازدهم', 'دوازدهم', 'فارغ التحصیل', 'دانشجو', 'دیپلم', 
                            'فوق دیپلم', 'لیسانس', 'فوق لیسانس', 'دکتری', 'سایر'
                        ];
                        $selectedEdu = $_POST['AdultEducation'] ?? '';
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
                    <input name="AdultZipCode" class="form-control <?php echo isset($errors['AdultZipCode']) ? 'is-invalid' : ''; ?>" 
                           value="<?php echo htmlspecialchars($_POST['AdultZipCode'] ?? ''); ?>" inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                    <?php if (isset($errors['AdultZipCode'])): ?>
                        <div class="field-error"><?php echo $errors['AdultZipCode']; ?></div>
                    <?php endif; ?>
                </div>

                <!-- شهر -->
                <div class="col-md-3">
                    <label class="form-label">شهر</label>
                    <input name="AdultCity" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['AdultCity'] ?? ''); ?>">
                </div>

                <!-- آدرس -->
                <div class="col-12">
                    <label class="form-label">آدرس</label>
                    <textarea name="AdultAddress" class="form-control" rows="2"><?php echo htmlspecialchars($_POST['AdultAddress'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- بخش اطلاعات بانکی -->
						<div class="col-12 mt-4">
                        <h5 class="border-bottom pb-2 mb-3">اطلاعات بانکی</h5>
                <div class="row g-3">
                    <!-- نام بانک -->
                    <div class="col-md-3">
                        <label class="form-label">نام بانک</label>
                        <select name="AdultBankName" class="form-select">
                            <option value="">انتخاب نشده</option>
                            <?php
                            $selectedBank = $_POST['AdultBankName'] ?? '';
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
                        <input name="AdultAccountNumber" class="form-control <?php echo isset($errors['AdultAccountNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['AdultAccountNumber'] ?? ''); ?>" 
                               inputmode="numeric" maxlength="30">
                        <?php if (isset($errors['AdultAccountNumber'])): ?>
                            <div class="field-error"><?php echo $errors['AdultAccountNumber']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- شماره کارت -->
                    <div class="col-md-3">
                        <label class="form-label">شماره کارت</label>
                        <input name="AdultCardNumber" class="form-control <?php echo isset($errors['AdultCardNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['AdultCardNumber'] ?? ''); ?>" 
                               inputmode="numeric" maxlength="16" placeholder="6037991234567890">
                        <?php if (isset($errors['AdultCardNumber'])): ?>
                            <div class="field-error"><?php echo $errors['AdultCardNumber']; ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- شماره شبا -->
                    <div class="col-md-3">
                        <label class="form-label">شماره شبا</label>
                        <input name="AdultShebaNumber" class="form-control <?php echo isset($errors['AdultShebaNumber']) ? 'is-invalid' : ''; ?>" 
                               value="<?php echo htmlspecialchars($_POST['AdultShebaNumber'] ?? ''); ?>" 
                               maxlength="26" placeholder="IR120120000000003123456789">
                        <?php if (isset($errors['AdultShebaNumber'])): ?>
                            <div class="field-error"><?php echo $errors['AdultShebaNumber']; ?></div>
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
document.getElementById('AdultImage').addEventListener('change', function(e) {
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
    const AdultMelliInput = document.querySelector('input[name="AdultMelli"]');
    const AdultSysCodeInput = document.querySelector('input[name="AdultSysCode"]');
    const AdultShebaInput = document.querySelector('input[name="AdultShebaNumber"]');
    
    // کدملی validation
    AdultMelliInput.addEventListener('input', function() {
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
    AdultSysCodeInput.addEventListener('input', function() {
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
    AdultShebaInput.addEventListener('input', function() {
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