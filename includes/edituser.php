<?php
// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
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

// Initialize variables
$q = trim($_GET['q'] ?? '');
$users = null;
$message = '';
$messageType = '';
$errors = [];

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = 'اطلاعات با موفقیت به‌روزرسانی شد.';
    $messageType = 'success';
}

// Check if UserID is provided in the URL
if (isset($_GET['UserID']) && is_numeric($_GET['UserID'])) {
    $UserID = (int)$_GET['UserID'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE UserID = ? LIMIT 1");
    $stmt->bind_param("i", $UserID);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $users = $result->fetch_assoc();
        } else {
            $message = 'هیچ نوجوانی با کد مشخص شده یافت نشد.';
            $messageType = 'danger';
        }
    } else {
        $message = 'خطا در دریافت اطلاعات: ' . $conn->error;
        $messageType = 'danger';
    }
    $stmt->close();
} 
// If no UserID, check for search query or success redirect
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($q)) {
    // Search for exact match by national code or system code
    $stmt = $conn->prepare("SELECT * FROM users WHERE UserMelli = ? OR UserSysCode = ? LIMIT 1");
    $stmt->bind_param("ss", $q, $q);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $users = $result->fetch_assoc();
        } else {
            $message = 'هیچ نوجوانی با مشخصات وارد شده یافت نشد.';
            $messageType = 'danger';
        }
    } else {
        $message = 'خطا در جستجو: ' . $conn->error;
        $messageType = 'danger';
    }
    $stmt->close();
}

// پردازش فرم ارسال شده
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['UserID'])) {
    $UserID = (int)$_POST['UserID'];
    
    // دریافت و اعتبارسنجی داده‌ها
    $UserSysCode = trim($_POST['UserSysCode'] ?? '');
    $UserMelli = trim($_POST['UserMelli'] ?? '');
    $UserName = trim($_POST['UserName'] ?? '');
    $UserFamily = trim($_POST['UserFamily'] ?? '');
    $UserFather = trim($_POST['UserFather'] ?? '');
    $UserMobile1 = trim($_POST['UserMobile1'] ?? '');
    $UserMobile2 = trim($_POST['UserMobile2'] ?? '');
    $UserDateBirth = trim($_POST['UserDateBirth'] ?? '');
    $UserRegDate = trim($_POST['UserRegDate'] ?? '');
    $UserActiveDate = trim($_POST['UserActiveDate'] ?? '');
    $UserSuspendDate = trim($_POST['UserSuspendDate'] ?? '');
    $UserPlaceBirth = trim($_POST['UserPlaceBirth'] ?? '');
    $UserPlaceCerti = trim($_POST['UserPlaceCerti'] ?? '');
    $UserBloodType = trim($_POST['UserBloodType'] ?? '');
    $UserEducation = trim($_POST['UserEducation'] ?? '');
    $UserAddress = trim($_POST['UserAddress'] ?? '');
    $UserZipCode = trim($_POST['UserZipCode'] ?? '');
    $UserStatus = trim($_POST['UserStatus'] ?? 'عادی');
    $UserCity = trim($_POST['UserCity'] ?? '');
    $UserBankName = trim($_POST['UserBankName'] ?? '');
    $UserAccountNumber = trim($_POST['UserAccountNumber'] ?? '');
    $UserCardNumber = trim($_POST['UserCardNumber'] ?? '');
    $UserShebaNumber = trim($_POST['UserShebaNumber'] ?? '');
    
    // تبدیل تاریخ‌های شمسی به میلادی - با مقدار پیش‌فرض NULL
    $UserDateBirthGregorian = !empty($UserDateBirth) ? to_gregorian_date($UserDateBirth) : null;
    $UserRegDateGregorian = !empty($UserRegDate) ? to_gregorian_date($UserRegDate) : null;
    $UserActiveDateGregorian = !empty($UserActiveDate) ? to_gregorian_date($UserActiveDate) : null;
    $UserSuspendDateGregorian = !empty($UserSuspendDate) ? to_gregorian_date($UserSuspendDate) : null;
    
    // مدیریت تصویر
    $imageUpdate = '';
    $deleteImageFlag = isset($_POST['delete_image']) && $_POST['delete_image'] == '1';
    
    if ($deleteImageFlag) {
        // حذف تصویر موجود
        if (!empty($users['UserImage']) && file_exists(__DIR__ . '/../' . $users['UserImage'])) {
            unlink(__DIR__ . '/../' . $users['UserImage']);
        }
        $imageUpdate = "UserImage = NULL";
    } elseif (isset($_FILES['UserImage']) && $_FILES['UserImage']['error'] === UPLOAD_ERR_OK) {
        // آپلود تصویر جدید
        $uploadDir = __DIR__ . '/../upload/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['UserImage']['name'], PATHINFO_EXTENSION);
        $fileName = 'users_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['UserImage']['tmp_name'], $filePath)) {
            // حذف تصویر قبلی اگر وجود دارد
            if (!empty($users['UserImage']) && file_exists(__DIR__ . '/../' . $users['UserImage'])) {
                unlink(__DIR__ . '/../' . $users['UserImage']);
            }
            $imageUpdate = "UserImage = '/upload/" . $fileName . "'";
        }
    }
    
    // اعتبارسنجی داده‌ها
    if (empty($UserSysCode)) {
        $errors['UserSysCode'] = 'کدسیستمی الزامی است';
    }
    if (empty($UserMelli) || strlen($UserMelli) !== 10 || !is_numeric($UserMelli)) {
        $errors['UserMelli'] = 'کدملی باید 10 رقمی باشد';
    }
    if (!empty($UserMobile1) && (strlen($UserMobile1) !== 11 || !is_numeric($UserMobile1))) {
        $errors['UserMobile1'] = 'شماره موبایل باید 11 رقمی باشد';
    }
    if (!empty($UserMobile2) && (strlen($UserMobile2) !== 11 || !is_numeric($UserMobile2))) {
        $errors['UserMobile2'] = 'شماره موبایل باید 11 رقمی باشد';
    }
    if (!empty($UserZipCode) && (strlen($UserZipCode) !== 10 || !is_numeric($UserZipCode))) {
        $errors['UserZipCode'] = 'کد پستی باید 10 رقمی باشد';
    }
    if (!empty($UserCardNumber) && (strlen($UserCardNumber) !== 16 || !is_numeric($UserCardNumber))) {
        $errors['UserCardNumber'] = 'شماره کارت باید 16 رقمی باشد';
    }
    if (!empty($UserShebaNumber) && !preg_match('/^IR\d{24}$/i', $UserShebaNumber)) {
        $errors['UserShebaNumber'] = 'شماره شبا باید با IR شروع شده و 26 رقم باشد';
    }
    
    // اگر خطایی وجود نداشت، اطلاعات را به‌روزرسانی کن
    if (empty($errors)) {
        // ساخت کوئری UPDATE
        $sql = "UPDATE users SET 
                UserSysCode = ?, 
                UserMelli = ?, 
                UserName = ?, 
                UserFamily = ?, 
                UserFather = ?, 
                UserMobile1 = ?, 
                UserMobile2 = ?, 
                UserDateBirth = ?, 
                UserRegDate = ?, 
                UserActiveDate = ?,
                UserSuspendDate = ?,
                UserPlaceBirth = ?, 
                UserPlaceCerti = ?, 
                UserBloodType = ?, 
                UserEducation = ?, 
                UserAddress = ?, 
                UserZipCode = ?, 
                UserStatus = ?,
                UserCity = ?,
                UserBankName = ?,
                UserAccountNumber = ?,
                UserCardNumber = ?,
                UserShebaNumber = ?,
                UserNumbersh = ?,
                UserMaritalStatus = ?,
                UserDutyStatus = ?,
                UserJobWork = ?,
                UserPhone = ?,
                UserEmail = ?,
                UserOtherActivity = ?";
        
        // اگر تصویر آپدیت شده باشد، به کوئری اضافه کن
        if (!empty($imageUpdate)) {
            $sql .= ", " . $imageUpdate;
        }
        
        $sql .= " WHERE UserID = ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // اگر تصویر آپدیت شده باشد، کوئری متفاوت است و نیازی به bind_param جداگانه نیست
            // چون تصویر مستقیماً در کوئری قرار می‌گیرد
            // Get the additional fields from POST
        $UserNumbersh = trim($_POST['UserNumbersh'] ?? '');
        $UserMaritalStatus = trim($_POST['UserMaritalStatus'] ?? '');
        $UserDutyStatus = trim($_POST['UserDutyStatus'] ?? '');
        $UserJobWork = trim($_POST['UserJobWork'] ?? '');
        $UserPhone = trim($_POST['UserPhone'] ?? '');
        $UserEmail = trim($_POST['UserEmail'] ?? '');
        $UserOtherActivity = trim($_POST['UserOtherActivity'] ?? '');

        if (empty($imageUpdate)) {
            // بدون تغییر تصویر - 31 پارامتر
            $stmt->bind_param("ssssssssssssssssssssssssssssssi", 
                $UserSysCode, $UserMelli, $UserName, $UserFamily, 
                $UserFather, $UserMobile1, $UserMobile2, $UserDateBirthGregorian,
                $UserRegDateGregorian, $UserActiveDateGregorian, $UserSuspendDateGregorian,
                $UserPlaceBirth, $UserPlaceCerti, $UserBloodType, 
                $UserEducation, $UserAddress, $UserZipCode, $UserStatus, $UserCity,
                $UserBankName, $UserAccountNumber, $UserCardNumber, $UserShebaNumber,
                $UserNumbersh, $UserMaritalStatus, $UserDutyStatus, $UserJobWork,
                $UserPhone, $UserEmail, $UserOtherActivity,
                $UserID
            );
        } else {
            // با تغییر تصویر - 31 پارامتر
            $stmt->bind_param("ssssssssssssssssssssssssssssssi", 
                $UserSysCode, $UserMelli, $UserName, $UserFamily, 
                $UserFather, $UserMobile1, $UserMobile2, $UserDateBirthGregorian,
                $UserRegDateGregorian, $UserActiveDateGregorian, $UserSuspendDateGregorian,
                $UserPlaceBirth, $UserPlaceCerti, $UserBloodType, 
                $UserEducation, $UserAddress, $UserZipCode, $UserStatus, $UserCity,
                $UserBankName, $UserAccountNumber, $UserCardNumber, $UserShebaNumber,
                $UserNumbersh, $UserMaritalStatus, $UserDutyStatus, $UserJobWork,
                $UserPhone, $UserEmail, $UserOtherActivity,
                $UserID
            );
            }
            
            if ($stmt->execute()) {
                // Get the updated data from the database
                $stmt = $conn->prepare("SELECT * FROM users WHERE UserID = ?");
                $stmt->bind_param("i", $UserID);
                $stmt->execute();
                $result = $stmt->get_result();
                $users = $result->fetch_assoc();
                
                $message = 'اطلاعات با موفقیت به‌روزرسانی شد.';
                $messageType = 'success';
                
                // ریدایرکت برای جلوگیری از ارسال مجدد فرم
                header("Location: edituser.php?UserID=" . $UserID . "&success=1");
                exit();
            } else {
                $message = 'خطا در به‌روزرسانی اطلاعات: ' . $conn->error;
                $messageType = 'danger';
            }
            $stmt->close();
        } else {
            $message = 'خطا در آماده‌سازی کوئری: ' . $conn->error;
            $messageType = 'danger';
        }
    } else {
        $message = 'لطفا خطاهای زیر را اصلاح کنید:';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ویرایش اطلاعات نوجوان</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/font-face.css" rel="stylesheet" />
    <link href="../assets/css/persian-datepicker.min.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        body {
            padding-bottom: 60px;
        }
        .form-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-label {
            font-weight: bold;
        }
        .search-box {
            background-color: #fff;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .search-form .input-group {
            flex-wrap: nowrap;
        }
        .search-form .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
            border-right: none;
        }
        .search-form .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
            border-left: none;
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

<div class="container py-4">
    <?php if (!isset($users)): ?>
    <div class="search-box container" style="margin-top:100px; margin-bottom:40px;">
        <div class="content-box" style="text-align:right;">
            <div class="d-flex justify-content-start align-items-center mb-3">
                <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                    <span class="me-2">بستن</span>
                    <span aria-hidden="true" class="fs-5">×</span>
                </a>
                <h2 class="mb-0">جستجوی نوجوان</h2>
            </div>
            <form method="get" class="search-form">
                <div class="input-group">
                    <input type="text" name="q" class="form-control form-control-lg" 
                           placeholder="کد ملی یا کدسیستمی را وارد کنید" 
                           value="<?php echo htmlspecialchars($q); ?>" required>
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i> جستجو
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($users)): ?>
        <div class="container" style="margin-top:100px; margin-bottom:40px;">
            <div class="content-box" style="text-align:right;">
                <div class="d-flex justify-content-start align-items-center mb-3">
                    <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                        <span class="me-2">بستن</span>
                        <span aria-hidden="true" class="fs-5">×</span>
                    </a>
                    <h2 class="mb-0">ویرایش اطلاعات نوجوان</h2>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="UserID" value="<?php echo $users['UserID']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="text-center mb-3">
                                <img src="<?php echo !empty($users['UserImage']) ? '../' . $users['UserImage'] : '../assets/img/avatarprofile.png'; ?>" 
                                     class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;" 
                                     id="previewImage" alt="تصویر پروفایل">
                                <div class="mt-2">
                                    <input type="file" class="form-control d-none" name="UserImage" id="UserImage" 
                                           accept="image/*">
                                    <input type="hidden" name="delete_image" id="deleteImageFlag" value="0">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="document.getElementById('UserImage').click();"
                                                title="تغییر تصویر">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                id="deleteImageBtn"
                                                title="حذف تصویر"
                                                <?php echo empty($users['UserImage']) ? 'disabled' : ''; ?>>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php if (isset($errors['UserImage'])): ?>
                                    <div class="field-error"><?php echo $errors['UserImage']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">کدسیستمی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php echo isset($errors['UserSysCode']) ? 'is-invalid' : ''; ?>" 
                                           name="UserSysCode" 
                                           value="<?php echo htmlspecialchars($users['UserSysCode']); ?>" required>
                                    <?php if (isset($errors['UserSysCode'])): ?>
                                        <div class="field-error"><?php echo $errors['UserSysCode']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">کدملی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php echo isset($errors['UserMelli']) ? 'is-invalid' : ''; ?>" 
                                           name="UserMelli" 
                                           value="<?php echo htmlspecialchars($users['UserMelli']); ?>" 
                                           required inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                                    <?php if (isset($errors['UserMelli'])): ?>
                                        <div class="field-error"><?php echo $errors['UserMelli']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">وضعیت</label>
                                    <select class="form-select" name="UserStatus">
                                        <option value="عادی" <?php echo $users['UserStatus'] === 'عادی' ? 'selected' : ''; ?>>عادی</option>
                                        <option value="فعال" <?php echo $users['UserStatus'] === 'فعال' ? 'selected' : ''; ?>>فعال</option>
                                        <option value="تعلیق" <?php echo $users['UserStatus'] === 'تعلیق' ? 'selected' : ''; ?>>تعلیق</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="UserName" 
                                           value="<?php echo htmlspecialchars($users['UserName']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="UserFamily" 
                                           value="<?php echo htmlspecialchars($users['UserFamily']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="UserFather" 
                                           value="<?php echo htmlspecialchars($users['UserFather']); ?>" required>
                                </div>
                                <!-- تلفن ثابت -->
                            <div class="col-md-3 mb-3">
                                <label class="form-label">تلفن ثابت</label>
                                <input name="UserPhone" class="form-control" 
                                       value="<?php echo htmlspecialchars($users['UserPhone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3 mb-3">
                            <label class="form-label">موبایل1</label>
                            <input type="tel" class="form-control <?php echo isset($errors['UserMobile1']) ? 'is-invalid' : ''; ?>" 
                                   name="UserMobile1" 
                                   value="<?php echo htmlspecialchars($users['UserMobile1'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                            <?php if (isset($errors['UserMobile1'])): ?>
                                <div class="field-error"><?php echo $errors['UserMobile1']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">موبایل2</label>
                            <input type="tel" class="form-control <?php echo isset($errors['UserMobile2']) ? 'is-invalid' : ''; ?>" 
                                   name="UserMobile2" 
                                   value="<?php echo htmlspecialchars($users['UserMobile2'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                            <?php if (isset($errors['UserMobile2'])): ?>
                                <div class="field-error"><?php echo $errors['UserMobile2']; ?></div>
                            <?php endif; ?>
                        </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- اطلاعات تکمیلی -->
                    <div class="col-12 mt-4">
                        <h5 class="border-bottom pb-2 mb-3">اطلاعات تکمیلی</h5>
                        <div class="row g-3">
                            <!-- شماره شناسنامه -->
                            <div class="col-md-3">
                                <label class="form-label">شماره شناسنامه</label>
                                <input name="UserNumbersh" class="form-control" 
                                       value="<?php echo htmlspecialchars($users['UserNumbersh'] ?? ''); ?>">
                            </div>

                            <!-- وضعیت تاهل -->
                            <div class="col-md-3">
                                <label class="form-label">وضعیت تاهل</label>
                                <select name="UserMaritalStatus" class="form-select">
                                    <option value="">انتخاب کنید</option>
                                    <option value="مجرد" <?php echo (isset($users['UserMaritalStatus']) && $users['UserMaritalStatus'] === 'مجرد') ? 'selected' : ''; ?>>مجرد</option>
                                    <option value="متاهل" <?php echo (isset($users['UserMaritalStatus']) && $users['UserMaritalStatus'] === 'متاهل') ? 'selected' : ''; ?>>متاهل</option>
                                </select>
                            </div>

                            <!-- وضعیت خدمت وظیفه -->
                            <div class="col-md-3">
                                <label class="form-label">وضعیت خدمت وظیفه</label>
                                <select name="UserDutyStatus" class="form-select">
                                    <option value="">انتخاب کنید</option>
                                    <option value="در حین خدمت" <?php echo (isset($users['UserDutyStatus']) && $users['UserDutyStatus'] === 'در حین خدمت') ? 'selected' : ''; ?>>در حین خدمت</option>
                                    <option value="کارت پایان خدمت" <?php echo (isset($users['UserDutyStatus']) && $users['UserDutyStatus'] === 'کارت پایان خدمت') ? 'selected' : ''; ?>>کارت پایان خدمت</option>
                                    <option value="معاف" <?php echo (isset($users['UserDutyStatus']) && $users['UserDutyStatus'] === 'معاف') ? 'selected' : ''; ?>>معاف</option>
                                    <option value="قبل از سن مشمولیت" <?php echo (isset($users['UserDutyStatus']) && $users['UserDutyStatus'] === 'قبل از سن مشمولیت') ? 'selected' : ''; ?>>قبل از سن مشمولیت</option>
                                    <option value="خرید خدمت" <?php echo (isset($users['UserDutyStatus']) && $users['UserDutyStatus'] === 'خرید خدمت') ? 'selected' : ''; ?>>خرید خدمت</option>
                                </select>
                            </div>

                            <!-- شغل -->
                            <div class="col-md-3">
                                <label class="form-label">شغل</label>
                                <input name="UserJobWork" class="form-control" 
                                       value="<?php echo htmlspecialchars($users['UserJobWork'] ?? ''); ?>">
                            </div>

                            <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">محل تولد</label>
                            <input type="text" class="form-control" name="UserPlaceBirth" 
                                   value="<?php echo htmlspecialchars($users['UserPlaceBirth'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">گروه خونی</label>
                            <select class="form-select" name="UserBloodType">
                                <option value="">انتخاب کنید</option>
                                <?php
                                $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                                foreach ($bloodTypes as $type) {
                                    $selected = (isset($users['UserBloodType']) && $users['UserBloodType'] === $type) ? 'selected' : '';
                                    echo "<option value=\"$type\" $selected>$type</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تحصیلات</label>
                            <select class="form-select" name="UserEducation">
                                <option value="">انتخاب کنید</option>
                                <?php
                                $educations = [
                                    'اول ابتدایی', 'دوم ابتدایی', 'سوم ابتدایی', 'چهارم ابتدایی', 
                                    'پنجم ابتدایی', 'ششم ابتدایی', 'هفتم', 'هشتم', 'نهم', 'دهم', 
                                    'یازدهم', 'دوازدهم', 'فارغ التحصیل', 'دانشجو', 'دیپلم', 
                                    'فوق دیپلم', 'لیسانس', 'فوق لیسانس', 'دکتری', 'سایر'
                                ];
                                foreach ($educations as $edu) {
                                    $selected = (isset($users['UserEducation']) && $users['UserEducation'] === $edu) ? 'selected' : '';
                                    echo "<option value=\"$edu\" $selected>$edu</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">محل صدور</label>
                            <input type="text" class="form-control" name="UserPlaceCerti" 
                                   value="<?php echo htmlspecialchars($users['UserPlaceCerti'] ?? ''); ?>">
                        </div>
                    </div>
                            <!-- تاریخ تولد -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ تولد</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="UserDateBirth" 
                                       value="<?php echo !empty($users['UserDateBirth']) ? to_persian_date($users['UserDateBirth']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت نام -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت عادی</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="UserRegDate" 
                                       value="<?php echo !empty($users['UserRegDate']) ? to_persian_date($users['UserRegDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت فعال -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت فعال</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="UserActiveDate" 
                                       value="<?php echo !empty($users['UserActiveDate']) ? to_persian_date($users['UserActiveDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت تعلیق -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت تعلیق</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="UserSuspendDate" 
                                       value="<?php echo !empty($users['UserSuspendDate']) ? to_persian_date($users['UserSuspendDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>
                            
                        </div>
                    </div>
                        
                    </div>
                    <div class="row">
                         <!-- ایمیل -->
                            <div class="col-md-3 mt-3">
                                <label class="form-label">ایمیل</label>
                                <input type="email" name="UserEmail" class="form-control" 
                                       value="<?php echo htmlspecialchars($users['UserEmail'] ?? ''); ?>">
                            </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">کد پستی</label>
                            <input type="text" class="form-control <?php echo isset($errors['UserZipCode']) ? 'is-invalid' : ''; ?>" 
                                   name="UserZipCode" 
                                   value="<?php echo htmlspecialchars($users['UserZipCode'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                            <?php if (isset($errors['UserZipCode'])): ?>
                                <div class="field-error"><?php echo $errors['UserZipCode']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">شهر</label>
                            <input type="text" class="form-control" name="UserCity" 
                                   value="<?php echo htmlspecialchars($users['UserCity'] ?? ''); ?>">
                    </div>
                    </div>
                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">آدرس</label>
                            <textarea class="form-control" name="UserAddress" rows="2"><?php echo htmlspecialchars($users['UserAddress'] ?? ''); ?></textarea>
                        </div>
                    </div>
                   
                        <div class="row">
                            <!-- فعالیت دیگر -->
                            <div class="col12 mb-3">
                                <label class="form-label">فعالیت دیگر (در صورت وجود)</label>
                                <textarea name="UserOtherActivity" class="form-control" rows="2"><?php echo htmlspecialchars($users['UserOtherActivity'] ?? ''); ?></textarea>
                                <small class="text-muted">در صورت انجام فعالیت ویژه مانند عضویت در هلال احمر و... در این قسمت وارد نمایید.</small>
                            </div>
                        </div>                    

                    <!-- بخش اطلاعات بانکی -->
                    <div class="col-12 mt-4">
                        <h5 class="border-bottom pb-2 mb-3">اطلاعات بانکی</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">نام بانک</label>
                                <select class="form-select" name="UserBankName">
                                    <option value="">انتخاب کنید</option>
                                    <?php
                                    $selectedBank = $users['UserBankName'] ?? '';
                                    foreach ($iranian_banks as $bank) {
                                        $selected = ($selectedBank === $bank) ? 'selected' : '';
                                        echo "<option value=\"$bank\" $selected>$bank</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره حساب</label>
                                <input type="text" class="form-control <?php echo isset($errors['UserAccountNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="UserAccountNumber" 
                                       value="<?php echo htmlspecialchars($users['UserAccountNumber'] ?? ''); ?>" 
                                       inputmode="numeric" maxlength="30">
                                <?php if (isset($errors['UserAccountNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['UserAccountNumber']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره کارت</label>
                                <input type="text" class="form-control <?php echo isset($errors['UserCardNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="UserCardNumber" 
                                       value="<?php echo htmlspecialchars($users['UserCardNumber'] ?? ''); ?>" 
                                       inputmode="numeric" maxlength="16" placeholder="6037991234567890">
                                <?php if (isset($errors['UserCardNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['UserCardNumber']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره شبا</label>
                                <input type="text" class="form-control <?php echo isset($errors['UserShebaNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="UserShebaNumber" 
                                       value="<?php echo htmlspecialchars($users['UserShebaNumber'] ?? ''); ?>" 
                                       maxlength="26" placeholder="IR120120000000003123456789">
                                <?php if (isset($errors['UserShebaNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['UserShebaNumber']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12 text-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="bi bi-save"></i> ذخیره تغییرات
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
// Image preview functionality
document.getElementById('UserImage').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
            document.getElementById('deleteImageBtn').disabled = false;
            document.getElementById('deleteImageFlag').value = '0';
        }
        reader.readAsDataURL(file);
    }
});

// Delete image functionality
document.getElementById('deleteImageBtn').addEventListener('click', function() {
    if (confirm('آیا از حذف تصویر اطمینان دارید؟')) {
        document.getElementById('previewImage').src = '../assets/img/avatarprofile.png';
        this.disabled = true;
        document.getElementById('deleteImageFlag').value = '1';
        document.getElementById('UserImage').value = '';
    }
});

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const UserMelliInput = document.querySelector('input[name="UserMelli"]');
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
</script>
<script src="../assets/js/persian-date.js"></script>
<script src="../assets/js/persian-datepicker.min.js"></script>

<script>
// Initialize date picker
$(document).ready(function() {
    $('.persian-date').persianDatepicker({
        format: 'YYYY/MM/DD', 
        autoClose: true,
        initialValue: true,
        initialValueType: 'persian',
        calendar: {
            persian: {
                locale: 'fa'
            }
        }
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