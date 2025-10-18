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
$Adult = null;
$message = '';
$messageType = '';
$errors = [];

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = 'اطلاعات با موفقیت به‌روزرسانی شد.';
    $messageType = 'success';
}

// Check if AdultID is provided in the URL
if (isset($_GET['AdultID']) && is_numeric($_GET['AdultID'])) {
    $AdultID = (int)$_GET['AdultID'];
    $stmt = $conn->prepare("SELECT * FROM Adult WHERE AdultID = ? LIMIT 1");
    $stmt->bind_param("i", $AdultID);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $Adult = $result->fetch_assoc();
        } else {
            $message = 'هیچ بزرگسالی با کد مشخص شده یافت نشد.';
            $messageType = 'danger';
        }
    } else {
        $message = 'خطا در دریافت اطلاعات: ' . $conn->error;
        $messageType = 'danger';
    }
    $stmt->close();
} 
// If no AdultID, check for search query or success redirect
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($q)) {
    // Search for exact match by national code or system code
    $stmt = $conn->prepare("SELECT * FROM Adult WHERE AdultMelli = ? OR AdultSysCode = ? LIMIT 1");
    $stmt->bind_param("ss", $q, $q);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $Adult = $result->fetch_assoc();
        } else {
            $message = 'هیچ بزرگسالی با مشخصات وارد شده یافت نشد.';
            $messageType = 'danger';
        }
    } else {
        $message = 'خطا در جستجو: ' . $conn->error;
        $messageType = 'danger';
    }
    $stmt->close();
}

// پردازش فرم ارسال شده
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['AdultID'])) {
    $AdultID = (int)$_POST['AdultID'];
    
    // دریافت و اعتبارسنجی داده‌ها
    $AdultSysCode = trim($_POST['AdultSysCode'] ?? '');
    $AdultMelli = trim($_POST['AdultMelli'] ?? '');
    $AdultName = trim($_POST['AdultName'] ?? '');
    $AdultFamily = trim($_POST['AdultFamily'] ?? '');
    $AdultFather = trim($_POST['AdultFather'] ?? '');
    $AdultMobile1 = trim($_POST['AdultMobile1'] ?? '');
    $AdultMobile2 = trim($_POST['AdultMobile2'] ?? '');
    $AdultDateBirth = trim($_POST['AdultDateBirth'] ?? '');
    $AdultRegDate = trim($_POST['AdultRegDate'] ?? '');
    $AdultActiveDate = trim($_POST['AdultActiveDate'] ?? '');
    $AdultSuspendDate = trim($_POST['AdultSuspendDate'] ?? '');
    $AdultPlaceBirth = trim($_POST['AdultPlaceBirth'] ?? '');
    $AdultPlaceCerti = trim($_POST['AdultPlaceCerti'] ?? '');
    $AdultBloodType = trim($_POST['AdultBloodType'] ?? '');
    $AdultEducation = trim($_POST['AdultEducation'] ?? '');
    $AdultAddress = trim($_POST['AdultAddress'] ?? '');
    $AdultZipCode = trim($_POST['AdultZipCode'] ?? '');
    $AdultStatus = trim($_POST['AdultStatus'] ?? 'عادی');
    $AdultCity = trim($_POST['AdultCity'] ?? '');
    $AdultBankName = trim($_POST['AdultBankName'] ?? '');
    $AdultAccountNumber = trim($_POST['AdultAccountNumber'] ?? '');
    $AdultCardNumber = trim($_POST['AdultCardNumber'] ?? '');
    $AdultShebaNumber = trim($_POST['AdultShebaNumber'] ?? '');
    
    // تبدیل تاریخ‌های شمسی به میلادی - با مقدار پیش‌فرض NULL
    $AdultDateBirthGregorian = !empty($AdultDateBirth) ? to_gregorian_date($AdultDateBirth) : null;
    $AdultRegDateGregorian = !empty($AdultRegDate) ? to_gregorian_date($AdultRegDate) : null;
    $AdultActiveDateGregorian = !empty($AdultActiveDate) ? to_gregorian_date($AdultActiveDate) : null;
    $AdultSuspendDateGregorian = !empty($AdultSuspendDate) ? to_gregorian_date($AdultSuspendDate) : null;
    
    // مدیریت تصویر
    $imageUpdate = '';
    $deleteImageFlag = isset($_POST['delete_image']) && $_POST['delete_image'] == '1';
    
    if ($deleteImageFlag) {
        // حذف تصویر موجود
        if (!empty($Adult['AdultImage']) && file_exists(__DIR__ . '/../' . $Adult['AdultImage'])) {
            unlink(__DIR__ . '/../' . $Adult['AdultImage']);
        }
        $imageUpdate = "AdultImage = NULL";
    } elseif (isset($_FILES['AdultImage']) && $_FILES['AdultImage']['error'] === UPLOAD_ERR_OK) {
        // آپلود تصویر جدید
        $uploadDir = __DIR__ . '/../upload/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['AdultImage']['name'], PATHINFO_EXTENSION);
        $fileName = 'Adult_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['AdultImage']['tmp_name'], $filePath)) {
            // حذف تصویر قبلی اگر وجود دارد
            if (!empty($Adult['AdultImage']) && file_exists(__DIR__ . '/../' . $Adult['AdultImage'])) {
                unlink(__DIR__ . '/../' . $Adult['AdultImage']);
            }
            $imageUpdate = "AdultImage = '/upload/" . $fileName . "'";
        }
    }
    
    // اعتبارسنجی داده‌ها
    if (empty($AdultSysCode)) {
        $errors['AdultSysCode'] = 'کدسیستمی الزامی است';
    }
    if (empty($AdultMelli) || strlen($AdultMelli) !== 10 || !is_numeric($AdultMelli)) {
        $errors['AdultMelli'] = 'کدملی باید 10 رقمی باشد';
    }
    if (!empty($AdultMobile1) && (strlen($AdultMobile1) !== 11 || !is_numeric($AdultMobile1))) {
        $errors['AdultMobile1'] = 'شماره موبایل باید 11 رقمی باشد';
    }
    if (!empty($AdultMobile2) && (strlen($AdultMobile2) !== 11 || !is_numeric($AdultMobile2))) {
        $errors['AdultMobile2'] = 'شماره موبایل باید 11 رقمی باشد';
    }
    if (!empty($AdultZipCode) && (strlen($AdultZipCode) !== 10 || !is_numeric($AdultZipCode))) {
        $errors['AdultZipCode'] = 'کد پستی باید 10 رقمی باشد';
    }
    if (!empty($AdultCardNumber) && (strlen($AdultCardNumber) !== 16 || !is_numeric($AdultCardNumber))) {
        $errors['AdultCardNumber'] = 'شماره کارت باید 16 رقمی باشد';
    }
    if (!empty($AdultShebaNumber) && !preg_match('/^IR\d{24}$/i', $AdultShebaNumber)) {
        $errors['AdultShebaNumber'] = 'شماره شبا باید با IR شروع شده و 26 رقم باشد';
    }
    
    // اگر خطایی وجود نداشت، اطلاعات را به‌روزرسانی کن
    if (empty($errors)) {
        // ساخت کوئری UPDATE
        $sql = "UPDATE Adult SET 
                AdultSysCode = ?, 
                AdultMelli = ?, 
                AdultName = ?, 
                AdultFamily = ?, 
                AdultFather = ?, 
                AdultMobile1 = ?, 
                AdultMobile2 = ?, 
                AdultDateBirth = ?, 
                AdultRegDate = ?, 
                AdultActiveDate = ?,
                AdultSuspendDate = ?,
                AdultPlaceBirth = ?, 
                AdultPlaceCerti = ?, 
                AdultBloodType = ?, 
                AdultEducation = ?, 
                AdultAddress = ?, 
                AdultZipCode = ?, 
                AdultStatus = ?,
                AdultCity = ?,
                AdultBankName = ?,
                AdultAccountNumber = ?,
                AdultCardNumber = ?,
                AdultShebaNumber = ?";
        
        // اگر تصویر آپدیت شده باشد، به کوئری اضافه کن
        if (!empty($imageUpdate)) {
            $sql .= ", " . $imageUpdate;
        }
        
        $sql .= " WHERE AdultID = ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // اگر تصویر آپدیت شده باشد، کوئری متفاوت است و نیازی به bind_param جداگانه نیست
            // چون تصویر مستقیماً در کوئری قرار می‌گیرد
            if (empty($imageUpdate)) {
                // بدون تغییر تصویر - 24 پارامتر
                $stmt->bind_param("sssssssssssssssssssssssi", 
                    $AdultSysCode, $AdultMelli, $AdultName, $AdultFamily, 
                    $AdultFather, $AdultMobile1, $AdultMobile2, $AdultDateBirthGregorian,
                    $AdultRegDateGregorian, $AdultActiveDateGregorian, $AdultSuspendDateGregorian,
                    $AdultPlaceBirth, $AdultPlaceCerti, $AdultBloodType, 
                    $AdultEducation, $AdultAddress, $AdultZipCode, $AdultStatus, $AdultCity,
                    $AdultBankName, $AdultAccountNumber, $AdultCardNumber, $AdultShebaNumber,
                    $AdultID
                );
            } else {
                // با تغییر تصویر - 24 پارامتر (همان تعداد)
                $stmt->bind_param("sssssssssssssssssssssssi", 
                    $AdultSysCode, $AdultMelli, $AdultName, $AdultFamily, 
                    $AdultFather, $AdultMobile1, $AdultMobile2, $AdultDateBirthGregorian,
                    $AdultRegDateGregorian, $AdultActiveDateGregorian, $AdultSuspendDateGregorian,
                    $AdultPlaceBirth, $AdultPlaceCerti, $AdultBloodType, 
                    $AdultEducation, $AdultAddress, $AdultZipCode, $AdultStatus, $AdultCity,
                    $AdultBankName, $AdultAccountNumber, $AdultCardNumber, $AdultShebaNumber,
                    $AdultID
                );
            }
            
            if ($stmt->execute()) {
                // Get the updated data from the database
                $stmt = $conn->prepare("SELECT * FROM Adult WHERE AdultID = ?");
                $stmt->bind_param("i", $AdultID);
                $stmt->execute();
                $result = $stmt->get_result();
                $Adult = $result->fetch_assoc();
                
                $message = 'اطلاعات با موفقیت به‌روزرسانی شد.';
                $messageType = 'success';
                
                // ریدایرکت برای جلوگیری از ارسال مجدد فرم
                header("Location: editadult.php?AdultID=" . $AdultID . "&success=1");
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
    <title>ویرایش اطلاعات بزرگسال</title>
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
    <?php if (!isset($Adult)): ?>
    <div class="search-box container" style="margin-top:100px; margin-bottom:40px;">
        <div class="content-box" style="text-align:right;">
            <div class="d-flex justify-content-start align-items-center mb-3">
                <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                    <span class="me-2">بستن</span>
                    <span aria-hidden="true" class="fs-5">×</span>
                </a>
                <h2 class="mb-0">جستجوی بزرگسال</h2>
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

    <?php if (isset($Adult)): ?>
        <div class="container" style="margin-top:100px; margin-bottom:40px;">
            <div class="content-box" style="text-align:right;">
                <div class="d-flex justify-content-start align-items-center mb-3">
                    <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                        <span class="me-2">بستن</span>
                        <span aria-hidden="true" class="fs-5">×</span>
                    </a>
                    <h2 class="mb-0">ویرایش اطلاعات بزرگسال</h2>
                </div>
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="AdultID" value="<?php echo $Adult['AdultID']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="text-center mb-3">
                                <img src="<?php echo !empty($Adult['AdultImage']) ? '../' . $Adult['AdultImage'] : '../assets/img/avatarprofile.png'; ?>" 
                                     class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;" 
                                     id="previewImage" alt="تصویر پروفایل">
                                <div class="mt-2">
                                    <input type="file" class="form-control d-none" name="AdultImage" id="AdultImage" 
                                           accept="image/*">
                                    <input type="hidden" name="delete_image" id="deleteImageFlag" value="0">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="document.getElementById('AdultImage').click();"
                                                title="تغییر تصویر">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                id="deleteImageBtn"
                                                title="حذف تصویر"
                                                <?php echo empty($Adult['AdultImage']) ? 'disabled' : ''; ?>>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php if (isset($errors['AdultImage'])): ?>
                                    <div class="field-error"><?php echo $errors['AdultImage']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">کدسیستمی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php echo isset($errors['AdultSysCode']) ? 'is-invalid' : ''; ?>" 
                                           name="AdultSysCode" 
                                           value="<?php echo htmlspecialchars($Adult['AdultSysCode']); ?>" required>
                                    <?php if (isset($errors['AdultSysCode'])): ?>
                                        <div class="field-error"><?php echo $errors['AdultSysCode']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">کدملی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php echo isset($errors['AdultMelli']) ? 'is-invalid' : ''; ?>" 
                                           name="AdultMelli" 
                                           value="<?php echo htmlspecialchars($Adult['AdultMelli']); ?>" 
                                           required inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                                    <?php if (isset($errors['AdultMelli'])): ?>
                                        <div class="field-error"><?php echo $errors['AdultMelli']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">وضعیت</label>
                                    <select class="form-select" name="AdultStatus">
                                        <option value="عادی" <?php echo $Adult['AdultStatus'] === 'عادی' ? 'selected' : ''; ?>>عادی</option>
                                        <option value="فعال" <?php echo $Adult['AdultStatus'] === 'فعال' ? 'selected' : ''; ?>>فعال</option>
                                        <option value="تعلیق" <?php echo $Adult['AdultStatus'] === 'تعلیق' ? 'selected' : ''; ?>>تعلیق</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="AdultName" 
                                           value="<?php echo htmlspecialchars($Adult['AdultName']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="AdultFamily" 
                                           value="<?php echo htmlspecialchars($Adult['AdultFamily']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="AdultFather" 
                                           value="<?php echo htmlspecialchars($Adult['AdultFather']); ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- تاریخ تولد -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ تولد</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="AdultDateBirth" 
                                       value="<?php echo !empty($Adult['AdultDateBirth']) ? to_persian_date($Adult['AdultDateBirth']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت نام -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت عادی</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="AdultRegDate" 
                                       value="<?php echo !empty($Adult['AdultRegDate']) ? to_persian_date($Adult['AdultRegDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت فعال -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت فعال</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="AdultActiveDate" 
                                       value="<?php echo !empty($Adult['AdultActiveDate']) ? to_persian_date($Adult['AdultActiveDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت تعلیق -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت تعلیق</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="AdultSuspendDate" 
                                       value="<?php echo !empty($Adult['AdultSuspendDate']) ? to_persian_date($Adult['AdultSuspendDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">محل تولد</label>
                            <input type="text" class="form-control" name="AdultPlaceBirth" 
                                   value="<?php echo htmlspecialchars($Adult['AdultPlaceBirth'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">گروه خونی</label>
                            <select class="form-select" name="AdultBloodType">
                                <option value="">انتخاب کنید</option>
                                <?php
                                $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                                foreach ($bloodTypes as $type) {
                                    $selected = (isset($Adult['AdultBloodType']) && $Adult['AdultBloodType'] === $type) ? 'selected' : '';
                                    echo "<option value=\"$type\" $selected>$type</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تحصیلات</label>
                            <select class="form-select" name="AdultEducation">
                                <option value="">انتخاب کنید</option>
                                <?php
                                $educations = [
                                    'اول ابتدایی', 'دوم ابتدایی', 'سوم ابتدایی', 'چهارم ابتدایی', 
                                    'پنجم ابتدایی', 'ششم ابتدایی', 'هفتم', 'هشتم', 'نهم', 'دهم', 
                                    'یازدهم', 'دوازدهم', 'فارغ التحصیل', 'دانشجو', 'دیپلم', 
                                    'فوق دیپلم', 'لیسانس', 'فوق لیسانس', 'دکتری', 'سایر'
                                ];
                                foreach ($educations as $edu) {
                                    $selected = (isset($Adult['AdultEducation']) && $Adult['AdultEducation'] === $edu) ? 'selected' : '';
                                    echo "<option value=\"$edu\" $selected>$edu</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">محل صدور</label>
                            <input type="text" class="form-control" name="AdultPlaceCerti" 
                                   value="<?php echo htmlspecialchars($Adult['AdultPlaceCerti'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">موبایل1</label>
                            <input type="tel" class="form-control <?php echo isset($errors['AdultMobile1']) ? 'is-invalid' : ''; ?>" 
                                   name="AdultMobile1" 
                                   value="<?php echo htmlspecialchars($Adult['AdultMobile1'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                            <?php if (isset($errors['AdultMobile1'])): ?>
                                <div class="field-error"><?php echo $errors['AdultMobile1']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">موبایل2</label>
                            <input type="tel" class="form-control <?php echo isset($errors['AdultMobile2']) ? 'is-invalid' : ''; ?>" 
                                   name="AdultMobile2" 
                                   value="<?php echo htmlspecialchars($Adult['AdultMobile2'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                            <?php if (isset($errors['AdultMobile2'])): ?>
                                <div class="field-error"><?php echo $errors['AdultMobile2']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">کد پستی</label>
                            <input type="text" class="form-control <?php echo isset($errors['AdultZipCode']) ? 'is-invalid' : ''; ?>" 
                                   name="AdultZipCode" 
                                   value="<?php echo htmlspecialchars($Adult['AdultZipCode'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                            <?php if (isset($errors['AdultZipCode'])): ?>
                                <div class="field-error"><?php echo $errors['AdultZipCode']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">شهر</label>
                            <input type="text" class="form-control" name="AdultCity" 
                                   value="<?php echo htmlspecialchars($Adult['AdultCity'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">آدرس</label>
                            <textarea class="form-control" name="AdultAddress" rows="2"><?php echo htmlspecialchars($Adult['AdultAddress'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- بخش اطلاعات بانکی -->
						<div class="col-12 mt-4">
                        <h5 class="border-bottom pb-2 mb-3">اطلاعات بانکی</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">نام بانک</label>
                                <select class="form-select" name="AdultBankName">
                                    <option value="">انتخاب کنید</option>
                                    <?php
                                    $selectedBank = $Adult['AdultBankName'] ?? '';
                                    foreach ($iranian_banks as $bank) {
                                        $selected = ($selectedBank === $bank) ? 'selected' : '';
                                        echo "<option value=\"$bank\" $selected>$bank</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره حساب</label>
                                <input type="text" class="form-control <?php echo isset($errors['AdultAccountNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="AdultAccountNumber" 
                                       value="<?php echo htmlspecialchars($Adult['AdultAccountNumber'] ?? ''); ?>" 
                                       inputmode="numeric" maxlength="30">
                                <?php if (isset($errors['AdultAccountNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['AdultAccountNumber']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره کارت</label>
                                <input type="text" class="form-control <?php echo isset($errors['AdultCardNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="AdultCardNumber" 
                                       value="<?php echo htmlspecialchars($Adult['AdultCardNumber'] ?? ''); ?>" 
                                       inputmode="numeric" maxlength="16" placeholder="6037991234567890">
                                <?php if (isset($errors['AdultCardNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['AdultCardNumber']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره شبا</label>
                                <input type="text" class="form-control <?php echo isset($errors['AdultShebaNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="AdultShebaNumber" 
                                       value="<?php echo htmlspecialchars($Adult['AdultShebaNumber'] ?? ''); ?>" 
                                       maxlength="26" placeholder="IR120120000000003123456789">
                                <?php if (isset($errors['AdultShebaNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['AdultShebaNumber']; ?></div>
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
document.getElementById('AdultImage').addEventListener('change', function(e) {
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
        document.getElementById('AdultImage').value = '';
    }
});

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const AdultMelliInput = document.querySelector('input[name="AdultMelli"]');
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