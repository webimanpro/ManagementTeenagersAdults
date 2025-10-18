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
$teen = null;
$message = '';
$messageType = '';
$errors = [];

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = 'اطلاعات با موفقیت به‌روزرسانی شد.';
    $messageType = 'success';
}

// Check if TeenID is provided in the URL
if (isset($_GET['TeenID']) && is_numeric($_GET['TeenID'])) {
    $teenID = (int)$_GET['TeenID'];
    $stmt = $conn->prepare("SELECT * FROM Teen WHERE TeenID = ? LIMIT 1");
    $stmt->bind_param("i", $teenID);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $teen = $result->fetch_assoc();
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
// If no TeenID, check for search query or success redirect
elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($q)) {
    // Search for exact match by national code or system code
    $stmt = $conn->prepare("SELECT * FROM Teen WHERE TeenMelli = ? OR TeenSysCode = ? LIMIT 1");
    $stmt->bind_param("ss", $q, $q);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $teen = $result->fetch_assoc();
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TeenID'])) {
    $TeenID = (int)$_POST['TeenID'];
    
    // دریافت و اعتبارسنجی داده‌ها
    $TeenSysCode = trim($_POST['TeenSysCode'] ?? '');
    $TeenMelli = trim($_POST['TeenMelli'] ?? '');
    $TeenName = trim($_POST['TeenName'] ?? '');
    $TeenFamily = trim($_POST['TeenFamily'] ?? '');
    $TeenFather = trim($_POST['TeenFather'] ?? '');
    $TeenMobile1 = trim($_POST['TeenMobile1'] ?? '');
    $TeenMobile2 = trim($_POST['TeenMobile2'] ?? '');
    $TeenDateBirth = trim($_POST['TeenDateBirth'] ?? '');
    $TeenRegDate = trim($_POST['TeenRegDate'] ?? '');
    $TeenActiveDate = trim($_POST['TeenActiveDate'] ?? '');
    $TeenSuspendDate = trim($_POST['TeenSuspendDate'] ?? '');
    $TeenPlaceBirth = trim($_POST['TeenPlaceBirth'] ?? '');
    $TeenPlaceCerti = trim($_POST['TeenPlaceCerti'] ?? '');
    $TeenBloodType = trim($_POST['TeenBloodType'] ?? '');
    $TeenEducation = trim($_POST['TeenEducation'] ?? '');
    $TeenAddress = trim($_POST['TeenAddress'] ?? '');
    $TeenZipCode = trim($_POST['TeenZipCode'] ?? '');
    $TeenStatus = trim($_POST['TeenStatus'] ?? 'عادی');
    $TeenCity = trim($_POST['TeenCity'] ?? '');
    $TeenBankName = trim($_POST['TeenBankName'] ?? '');
    $TeenAccountNumber = trim($_POST['TeenAccountNumber'] ?? '');
    $TeenCardNumber = trim($_POST['TeenCardNumber'] ?? '');
    $TeenShebaNumber = trim($_POST['TeenShebaNumber'] ?? '');
    
    // تبدیل تاریخ‌های شمسی به میلادی - با مقدار پیش‌فرض NULL
    $TeenDateBirthGregorian = !empty($TeenDateBirth) ? to_gregorian_date($TeenDateBirth) : null;
    $TeenRegDateGregorian = !empty($TeenRegDate) ? to_gregorian_date($TeenRegDate) : null;
    $TeenActiveDateGregorian = !empty($TeenActiveDate) ? to_gregorian_date($TeenActiveDate) : null;
    $TeenSuspendDateGregorian = !empty($TeenSuspendDate) ? to_gregorian_date($TeenSuspendDate) : null;
    
    // مدیریت تصویر
    $imageUpdate = '';
    $deleteImageFlag = isset($_POST['delete_image']) && $_POST['delete_image'] == '1';
    
    if ($deleteImageFlag) {
        // حذف تصویر موجود
        if (!empty($teen['TeenImage']) && file_exists(__DIR__ . '/../' . $teen['TeenImage'])) {
            unlink(__DIR__ . '/../' . $teen['TeenImage']);
        }
        $imageUpdate = "TeenImage = NULL";
    } elseif (isset($_FILES['TeenImage']) && $_FILES['TeenImage']['error'] === UPLOAD_ERR_OK) {
        // آپلود تصویر جدید
        $uploadDir = __DIR__ . '/../upload/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileExtension = pathinfo($_FILES['TeenImage']['name'], PATHINFO_EXTENSION);
        $fileName = 'teen_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['TeenImage']['tmp_name'], $filePath)) {
            // حذف تصویر قبلی اگر وجود دارد
            if (!empty($teen['TeenImage']) && file_exists(__DIR__ . '/../' . $teen['TeenImage'])) {
                unlink(__DIR__ . '/../' . $teen['TeenImage']);
            }
            $imageUpdate = "TeenImage = '/upload/" . $fileName . "'";
        }
    }
    
    // اعتبارسنجی داده‌ها
    if (empty($TeenSysCode)) {
        $errors['TeenSysCode'] = 'کدسیستمی الزامی است';
    }
    if (empty($TeenMelli) || strlen($TeenMelli) !== 10 || !is_numeric($TeenMelli)) {
        $errors['TeenMelli'] = 'کدملی باید 10 رقمی باشد';
    }
    if (!empty($TeenMobile1) && (strlen($TeenMobile1) !== 11 || !is_numeric($TeenMobile1))) {
        $errors['TeenMobile1'] = 'شماره موبایل باید 11 رقمی باشد';
    }
    if (!empty($TeenMobile2) && (strlen($TeenMobile2) !== 11 || !is_numeric($TeenMobile2))) {
        $errors['TeenMobile2'] = 'شماره موبایل باید 11 رقمی باشد';
    }
    if (!empty($TeenZipCode) && (strlen($TeenZipCode) !== 10 || !is_numeric($TeenZipCode))) {
        $errors['TeenZipCode'] = 'کد پستی باید 10 رقمی باشد';
    }
    if (!empty($TeenCardNumber) && (strlen($TeenCardNumber) !== 16 || !is_numeric($TeenCardNumber))) {
        $errors['TeenCardNumber'] = 'شماره کارت باید 16 رقمی باشد';
    }
    if (!empty($TeenShebaNumber) && !preg_match('/^IR\d{24}$/i', $TeenShebaNumber)) {
        $errors['TeenShebaNumber'] = 'شماره شبا باید با IR شروع شده و 26 رقم باشد';
    }
    
    // اگر خطایی وجود نداشت، اطلاعات را به‌روزرسانی کن
    if (empty($errors)) {
        // ساخت کوئری UPDATE
        $sql = "UPDATE Teen SET 
                TeenSysCode = ?, 
                TeenMelli = ?, 
                TeenName = ?, 
                TeenFamily = ?, 
                TeenFather = ?, 
                TeenMobile1 = ?, 
                TeenMobile2 = ?, 
                TeenDateBirth = ?, 
                TeenRegDate = ?, 
                TeenActiveDate = ?,
                TeenSuspendDate = ?,
                TeenPlaceBirth = ?, 
                TeenPlaceCerti = ?, 
                TeenBloodType = ?, 
                TeenEducation = ?, 
                TeenAddress = ?, 
                TeenZipCode = ?, 
                TeenStatus = ?,
                TeenCity = ?,
                TeenBankName = ?,
                TeenAccountNumber = ?,
                TeenCardNumber = ?,
                TeenShebaNumber = ?";
        
        // اگر تصویر آپدیت شده باشد، به کوئری اضافه کن
        if (!empty($imageUpdate)) {
            $sql .= ", " . $imageUpdate;
        }
        
        $sql .= " WHERE TeenID = ?";
        
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            // اگر تصویر آپدیت شده باشد، کوئری متفاوت است و نیازی به bind_param جداگانه نیست
            // چون تصویر مستقیماً در کوئری قرار می‌گیرد
            if (empty($imageUpdate)) {
                // بدون تغییر تصویر - 24 پارامتر
                $stmt->bind_param("sssssssssssssssssssssssi", 
                    $TeenSysCode, $TeenMelli, $TeenName, $TeenFamily, 
                    $TeenFather, $TeenMobile1, $TeenMobile2, $TeenDateBirthGregorian,
                    $TeenRegDateGregorian, $TeenActiveDateGregorian, $TeenSuspendDateGregorian,
                    $TeenPlaceBirth, $TeenPlaceCerti, $TeenBloodType, 
                    $TeenEducation, $TeenAddress, $TeenZipCode, $TeenStatus, $TeenCity,
                    $TeenBankName, $TeenAccountNumber, $TeenCardNumber, $TeenShebaNumber,
                    $TeenID
                );
            } else {
                // با تغییر تصویر - 24 پارامتر (همان تعداد)
                $stmt->bind_param("sssssssssssssssssssssssi", 
                    $TeenSysCode, $TeenMelli, $TeenName, $TeenFamily, 
                    $TeenFather, $TeenMobile1, $TeenMobile2, $TeenDateBirthGregorian,
                    $TeenRegDateGregorian, $TeenActiveDateGregorian, $TeenSuspendDateGregorian,
                    $TeenPlaceBirth, $TeenPlaceCerti, $TeenBloodType, 
                    $TeenEducation, $TeenAddress, $TeenZipCode, $TeenStatus, $TeenCity,
                    $TeenBankName, $TeenAccountNumber, $TeenCardNumber, $TeenShebaNumber,
                    $TeenID
                );
            }
            
            if ($stmt->execute()) {
                // Get the updated data from the database
                $stmt = $conn->prepare("SELECT * FROM Teen WHERE TeenID = ?");
                $stmt->bind_param("i", $TeenID);
                $stmt->execute();
                $result = $stmt->get_result();
                $teen = $result->fetch_assoc();
                
                $message = 'اطلاعات با موفقیت به‌روزرسانی شد.';
                $messageType = 'success';
                
                // ریدایرکت برای جلوگیری از ارسال مجدد فرم
                header("Location: editteen.php?TeenID=" . $TeenID . "&success=1");
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
    <?php if (!isset($teen)): ?>
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

    <?php if (isset($teen)): ?>
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
                    <input type="hidden" name="TeenID" value="<?php echo $teen['TeenID']; ?>">
                    
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="text-center mb-3">
                                <img src="<?php echo !empty($teen['TeenImage']) ? '../' . $teen['TeenImage'] : '../assets/img/avatarprofile.png'; ?>" 
                                     class="img-thumbnail" style="width: 150px; height: 150px; object-fit: cover;" 
                                     id="previewImage" alt="تصویر پروفایل">
                                <div class="mt-2">
                                    <input type="file" class="form-control d-none" name="TeenImage" id="TeenImage" 
                                           accept="image/*">
                                    <input type="hidden" name="delete_image" id="deleteImageFlag" value="0">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="document.getElementById('TeenImage').click();"
                                                title="تغییر تصویر">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" 
                                                id="deleteImageBtn"
                                                title="حذف تصویر"
                                                <?php echo empty($teen['TeenImage']) ? 'disabled' : ''; ?>>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php if (isset($errors['TeenImage'])): ?>
                                    <div class="field-error"><?php echo $errors['TeenImage']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">کدسیستمی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php echo isset($errors['TeenSysCode']) ? 'is-invalid' : ''; ?>" 
                                           name="TeenSysCode" 
                                           value="<?php echo htmlspecialchars($teen['TeenSysCode']); ?>" required>
                                    <?php if (isset($errors['TeenSysCode'])): ?>
                                        <div class="field-error"><?php echo $errors['TeenSysCode']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">کدملی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php echo isset($errors['TeenMelli']) ? 'is-invalid' : ''; ?>" 
                                           name="TeenMelli" 
                                           value="<?php echo htmlspecialchars($teen['TeenMelli']); ?>" 
                                           required inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                                    <?php if (isset($errors['TeenMelli'])): ?>
                                        <div class="field-error"><?php echo $errors['TeenMelli']; ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">وضعیت</label>
                                    <select class="form-select" name="TeenStatus">
                                        <option value="عادی" <?php echo $teen['TeenStatus'] === 'عادی' ? 'selected' : ''; ?>>عادی</option>
                                        <option value="فعال" <?php echo $teen['TeenStatus'] === 'فعال' ? 'selected' : ''; ?>>فعال</option>
                                        <option value="تعلیق" <?php echo $teen['TeenStatus'] === 'تعلیق' ? 'selected' : ''; ?>>تعلیق</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="TeenName" 
                                           value="<?php echo htmlspecialchars($teen['TeenName']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="TeenFamily" 
                                           value="<?php echo htmlspecialchars($teen['TeenFamily']); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="TeenFather" 
                                           value="<?php echo htmlspecialchars($teen['TeenFather']); ?>" required>
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
                                <input type="text" class="form-control persian-date" name="TeenDateBirth" 
                                       value="<?php echo !empty($teen['TeenDateBirth']) ? to_persian_date($teen['TeenDateBirth']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت نام -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت عادی</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="TeenRegDate" 
                                       value="<?php echo !empty($teen['TeenRegDate']) ? to_persian_date($teen['TeenRegDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت فعال -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت فعال</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="TeenActiveDate" 
                                       value="<?php echo !empty($teen['TeenActiveDate']) ? to_persian_date($teen['TeenActiveDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>

                        <!-- تاریخ ثبت تعلیق -->
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تاریخ ثبت تعلیق</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                                <input type="text" class="form-control persian-date" name="TeenSuspendDate" 
                                       value="<?php echo !empty($teen['TeenSuspendDate']) ? to_persian_date($teen['TeenSuspendDate']) : ''; ?>"
                                       placeholder="1400/01/01">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">محل تولد</label>
                            <input type="text" class="form-control" name="TeenPlaceBirth" 
                                   value="<?php echo htmlspecialchars($teen['TeenPlaceBirth'] ?? ''); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">گروه خونی</label>
                            <select class="form-select" name="TeenBloodType">
                                <option value="">انتخاب کنید</option>
                                <?php
                                $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                                foreach ($bloodTypes as $type) {
                                    $selected = (isset($teen['TeenBloodType']) && $teen['TeenBloodType'] === $type) ? 'selected' : '';
                                    echo "<option value=\"$type\" $selected>$type</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">تحصیلات</label>
                            <select class="form-select" name="TeenEducation">
                                <option value="">انتخاب کنید</option>
                                <?php
                                $educations = [
                                    'اول ابتدایی', 'دوم ابتدایی', 'سوم ابتدایی', 'چهارم ابتدایی', 
                                    'پنجم ابتدایی', 'ششم ابتدایی', 'هفتم', 'هشتم', 'نهم', 'دهم', 
                                    'یازدهم', 'دوازدهم', 'فارغ التحصیل', 'دانشجو', 'دیپلم', 
                                    'فوق دیپلم', 'لیسانس', 'فوق لیسانس', 'دکتری', 'سایر'
                                ];
                                foreach ($educations as $edu) {
                                    $selected = (isset($teen['TeenEducation']) && $teen['TeenEducation'] === $edu) ? 'selected' : '';
                                    echo "<option value=\"$edu\" $selected>$edu</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">محل صدور</label>
                            <input type="text" class="form-control" name="TeenPlaceCerti" 
                                   value="<?php echo htmlspecialchars($teen['TeenPlaceCerti'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">موبایل1</label>
                            <input type="tel" class="form-control <?php echo isset($errors['TeenMobile1']) ? 'is-invalid' : ''; ?>" 
                                   name="TeenMobile1" 
                                   value="<?php echo htmlspecialchars($teen['TeenMobile1'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                            <?php if (isset($errors['TeenMobile1'])): ?>
                                <div class="field-error"><?php echo $errors['TeenMobile1']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">موبایل2</label>
                            <input type="tel" class="form-control <?php echo isset($errors['TeenMobile2']) ? 'is-invalid' : ''; ?>" 
                                   name="TeenMobile2" 
                                   value="<?php echo htmlspecialchars($teen['TeenMobile2'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                            <?php if (isset($errors['TeenMobile2'])): ?>
                                <div class="field-error"><?php echo $errors['TeenMobile2']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">کد پستی</label>
                            <input type="text" class="form-control <?php echo isset($errors['TeenZipCode']) ? 'is-invalid' : ''; ?>" 
                                   name="TeenZipCode" 
                                   value="<?php echo htmlspecialchars($teen['TeenZipCode'] ?? ''); ?>" 
                                   inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                            <?php if (isset($errors['TeenZipCode'])): ?>
                                <div class="field-error"><?php echo $errors['TeenZipCode']; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">شهر</label>
                            <input type="text" class="form-control" name="TeenCity" 
                                   value="<?php echo htmlspecialchars($teen['TeenCity'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label">آدرس</label>
                            <textarea class="form-control" name="TeenAddress" rows="2"><?php echo htmlspecialchars($teen['TeenAddress'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- بخش اطلاعات بانکی -->
						<div class="col-12 mt-4">
                        <h5 class="border-bottom pb-2 mb-3">اطلاعات بانکی</h5>
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label">نام بانک</label>
                                <select class="form-select" name="TeenBankName">
                                    <option value="">انتخاب کنید</option>
                                    <?php
                                    $selectedBank = $teen['TeenBankName'] ?? '';
                                    foreach ($iranian_banks as $bank) {
                                        $selected = ($selectedBank === $bank) ? 'selected' : '';
                                        echo "<option value=\"$bank\" $selected>$bank</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره حساب</label>
                                <input type="text" class="form-control <?php echo isset($errors['TeenAccountNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="TeenAccountNumber" 
                                       value="<?php echo htmlspecialchars($teen['TeenAccountNumber'] ?? ''); ?>" 
                                       inputmode="numeric" maxlength="30">
                                <?php if (isset($errors['TeenAccountNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['TeenAccountNumber']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره کارت</label>
                                <input type="text" class="form-control <?php echo isset($errors['TeenCardNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="TeenCardNumber" 
                                       value="<?php echo htmlspecialchars($teen['TeenCardNumber'] ?? ''); ?>" 
                                       inputmode="numeric" maxlength="16" placeholder="6037991234567890">
                                <?php if (isset($errors['TeenCardNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['TeenCardNumber']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">شماره شبا</label>
                                <input type="text" class="form-control <?php echo isset($errors['TeenShebaNumber']) ? 'is-invalid' : ''; ?>" 
                                       name="TeenShebaNumber" 
                                       value="<?php echo htmlspecialchars($teen['TeenShebaNumber'] ?? ''); ?>" 
                                       maxlength="26" placeholder="IR120120000000003123456789">
                                <?php if (isset($errors['TeenShebaNumber'])): ?>
                                    <div class="field-error"><?php echo $errors['TeenShebaNumber']; ?></div>
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
document.getElementById('TeenImage').addEventListener('change', function(e) {
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
        document.getElementById('TeenImage').value = '';
    }
});

// Real-time validation
document.addEventListener('DOMContentLoaded', function() {
    const teenMelliInput = document.querySelector('input[name="TeenMelli"]');
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