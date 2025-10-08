<?php
// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../config/database.php';

$errors = [];
$success = '';
$preview_data = [];
$import_type = '';
$next_id = 0;

// تابع تبدیل تاریخ
function convertExcelDate($excelDate) {
    if (empty($excelDate) || $excelDate === '') {
        return null;
    }
    
    if (is_string($excelDate)) {
        $excelDate = trim($excelDate);
        
        // فرمت تاریخ شمسی: 1390/01/01
        if (preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $excelDate, $matches)) {
            return $matches[1] . '/' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '/' . str_pad($matches[3], 2, '0', STR_PAD_LEFT);
        }
        
        // فرمت تاریخ میلادی: 2021-01-01
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $excelDate, $matches)) {
            return $matches[1] . '-' . str_pad($matches[2], 2, '0', STR_PAD_LEFT) . '-' . str_pad($matches[3], 2, '0', STR_PAD_LEFT);
        }
        
        return $excelDate;
    }
    
    return null;
}

// تابع پاکسازی مقادیر
function cleanValue($value) {
    if ($value === null) {
        return '';
    }
    
    if (!is_string($value)) {
        $value = strval($value);
    }
    
    $value = trim($value);
    $value = stripslashes($value);
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    
    return $value;
}

// تابع تشخیص encoding
function detectEncoding($string) {
    $encodings = ['UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1', 'Windows-1252', 'Windows-1256'];
    
    foreach ($encodings as $encoding) {
        if (mb_detect_encoding($string, $encoding, true) === $encoding) {
            return $encoding;
        }
    }
    
    return 'UTF-8';
}

// تابع دریافت شناسه بعدی
function getNextId($conn, $import_type) {
    if ($import_type === 'teen') {
        $query = "SELECT MAX(TeenID) as max_id FROM teen";
    } else {
        $query = "SELECT MAX(AdultID) as max_id FROM adult";
    }
    
    $result = $conn->query($query);
    if ($result && $row = $result->fetch_assoc()) {
        return ($row['max_id'] ?? 0) + 1;
    }
    
    return 1;
}

// پردازش آپلود فایل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_type'])) {
        $import_type = $_POST['import_type'];
        // دریافت شناسه بعدی برای نوع انتخاب شده
        $next_id = getNextId($conn, $import_type);
    }
    
    if (isset($_POST['confirm_import']) && isset($_SESSION['preview_data']) && isset($_SESSION['import_type'])) {
        // وارد کردن داده‌ها به دیتابیس
        $preview_data = $_SESSION['preview_data'];
        $import_type = $_SESSION['import_type'];
        $inserted_count = 0;
        $error_count = 0;
        $error_messages = [];
        
        $conn->begin_transaction();
        
        try {
            foreach ($preview_data as $row_index => $row) {
                // بررسی فیلدهای ضروری
                if (empty($row['sys_code']) || empty($row['melli']) || empty($row['name']) || empty($row['family'])) {
                    $error_count++;
                    $error_messages[] = "ردیف {$row['row_num']}: فیلدهای ضروری (کدسیستمی، کدملی، نام، نام خانوادگی) باید پر شوند";
                    continue;
                }
                
                // بررسی وجود کدسیستمی تکراری
                if ($import_type === 'teen') {
                    $check_stmt = $conn->prepare("SELECT TeenID FROM teen WHERE TeenSysCode = ?");
                } else {
                    $check_stmt = $conn->prepare("SELECT AdultID FROM adult WHERE AdultSysCode = ?");
                }
                $check_stmt->bind_param('s', $row['sys_code']);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    $error_count++;
                    $error_messages[] = "ردیف {$row['row_num']}: کدسیستمی '{$row['sys_code']}' تکراری است";
                    $check_stmt->close();
                    continue;
                }
                $check_stmt->close();

                // وارد کردن داده
                if ($import_type === 'teen') {
                    $stmt = $conn->prepare("INSERT INTO teen (
                        TeenSysCode, TeenMelli, TeenName, TeenFamily, TeenFather, 
                        TeenMobile1, TeenMobile2, TeenDateBirth, TeenRegDate, TeenStatus,
                        TeenPlaceBirth, TeenPlaceCerti, TeenBloodType, TeenEducation, TeenCity, TeenZipCode, TeenAddress
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                } else {
                    $stmt = $conn->prepare("INSERT INTO adult (
                        AdultSysCode, AdultMelli, AdultName, AdultFamily, AdultFather, 
                        AdultMobile1, AdultMobile2, AdultDateBirth, AdultRegDate, AdultStatus,
                        AdultPlaceBirth, AdultPlaceCerti, AdultBloodType, AdultEducation, AdultCity, AdultZipCode, AdultAddress
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                }
                
                // تنظیم مقادیر پیش‌فرض برای فیلدهای اختیاری
                $status = in_array($row['status'], ['عادی', 'فعال', 'تعلیق']) ? $row['status'] : 'عادی';
                $blood_type = in_array($row['blood_type'], ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-']) ? $row['blood_type'] : '';
                
                $stmt->bind_param(
                    'sssssssssssssssss',
                    $row['sys_code'],
                    $row['melli'],
                    $row['name'],
                    $row['family'],
                    $row['father'],
                    $row['mobile1'],
                    $row['mobile2'],
                    $row['birth_date'],
                    $row['reg_date'],
                    $status,
                    $row['birth_place'],
                    $row['certi_place'],
                    $blood_type,
                    $row['education'],
                    $row['city'],
                    $row['zip_code'],
                    $row['address']
                );
                
                if ($stmt->execute()) {
                    $inserted_count++;
                } else {
                    $error_count++;
                    $error_messages[] = "ردیف {$row['row_num']}: خطا در ذخیره سازی - " . $stmt->error;
                }
                $stmt->close();
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = 'خطا در تراکنش دیتابیس: ' . $e->getMessage();
        }
        
        unset($_SESSION['preview_data']);
        unset($_SESSION['import_type']);
        unset($_SESSION['next_id']);
        
        if ($error_count === 0) {
            $success = "تمام $inserted_count رکورد با موفقیت وارد شدند.";
        } else {
            $success = "$inserted_count رکورد با موفقیت وارد شدند. $error_count رکورد با خطا مواجه شدند.";
            if (!empty($error_messages)) {
                $errors = array_merge($errors, array_slice($error_messages, 0, 10));
            }
        }
    }
    elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        // پردازش فایل CSV
        if (empty($import_type)) {
            $errors[] = 'لطفاً نوع وارد کردن اطلاعات را انتخاب کنید.';
        } else {
            $file_tmp_path = $_FILES['csv_file']['tmp_name'];
            $file_name = $_FILES['csv_file']['name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            if ($file_ext !== 'csv') {
                $errors[] = 'فقط فایل‌های CSV مجاز هستند.';
            } else {
                try {
                    // خواندن کل فایل برای تشخیص encoding
                    $file_content = file_get_contents($file_tmp_path);
                    $detected_encoding = detectEncoding($file_content);
                    
                    // اگر encoding یافت نشد، UTF-8 فرض می‌کنیم
                    if (!$detected_encoding) {
                        $detected_encoding = 'UTF-8';
                    }
                    
                    // تبدیل به UTF-8 اگر لازم باشد
                    if ($detected_encoding !== 'UTF-8') {
                        $file_content = mb_convert_encoding($file_content, 'UTF-8', $detected_encoding);
                        file_put_contents($file_tmp_path, $file_content);
                    }
                    
                    // خواندن فایل CSV با encoding صحیح
                    $rows = [];
                    if (($handle = fopen($file_tmp_path, 'r')) !== FALSE) {
                        // خواندن هدر
                        $header = fgetcsv($handle, 0, ',', '"', '\\');
                        
                        // خواندن داده‌ها
                        $row_number = 1;
                        while (($data = fgetcsv($handle, 0, ',', '"', '\\')) !== FALSE) {
                            $row_number++;
                            
                            // اطمینان از اینکه تعداد ستون‌ها مناسب است
                            if (count($data) < 17) {
                                // اگر ستون‌ها کمتر از ۱۷ هستند، با مقادیر خالی پر می‌کنیم
                                $data = array_pad($data, 17, '');
                            }
                            
                            // بررسی اینکه ردیف خالی نیست
                            $row_has_data = false;
                            foreach ($data as $value) {
                                if (!empty(trim($value))) {
                                    $row_has_data = true;
                                    break;
                                }
                            }
                            
                            if ($row_has_data) {
                                $preview_data[] = [
                                    'row_num' => $row_number,
                                    'sys_code' => cleanValue($data[0] ?? ''),
                                    'melli' => cleanValue($data[1] ?? ''),
                                    'name' => cleanValue($data[2] ?? ''),
                                    'family' => cleanValue($data[3] ?? ''),
                                    'father' => cleanValue($data[4] ?? ''),
                                    'mobile1' => cleanValue($data[5] ?? ''),
                                    'mobile2' => cleanValue($data[6] ?? ''),
                                    'birth_date' => convertExcelDate($data[7] ?? ''),
                                    'reg_date' => convertExcelDate($data[8] ?? ''),
                                    'status' => cleanValue($data[9] ?? 'عادی'),
                                    'birth_place' => cleanValue($data[10] ?? ''),
                                    'certi_place' => cleanValue($data[11] ?? ''),
                                    'blood_type' => cleanValue($data[12] ?? ''),
                                    'education' => cleanValue($data[13] ?? ''),
                                    'city' => cleanValue($data[14] ?? ''),
                                    'zip_code' => cleanValue($data[15] ?? ''),
                                    'address' => cleanValue($data[16] ?? '')
                                ];
                            }
                        }
                        fclose($handle);
                    } else {
                        $errors[] = 'خطا در باز کردن فایل CSV.';
                    }
                    
                    if (empty($preview_data)) {
                        $errors[] = 'هیچ داده‌ای در فایل CSV یافت نشد.';
                    } else {
                        // دریافت شناسه بعدی برای نوع انتخاب شده
                        $next_id = getNextId($conn, $import_type);
                        
                        $_SESSION['preview_data'] = $preview_data;
                        $_SESSION['import_type'] = $import_type;
                        $_SESSION['next_id'] = $next_id;
                        $success = count($preview_data) . ' رکورد برای پیش‌نمایش پیدا شد. شناسه شروع: ' . $next_id;
                    }
                } catch (Exception $e) {
                    $errors[] = 'خطا در خواندن فایل CSV: ' . $e->getMessage();
                }
            }
        }
    }
}

// اگر در حالت پیش‌نمایش هستیم، شناسه بعدی را از session بگیر
if (isset($_SESSION['next_id']) && isset($_SESSION['import_type'])) {
    $next_id = $_SESSION['next_id'];
    $import_type = $_SESSION['import_type'];
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>وارد کردن اطلاعات از اکسل</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        .upload-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px dashed #dee2e6;
        }
        .preview-table {
            font-size: 0.85rem;
        }
        .preview-table th {
            background-color: #4a6cf7;
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }
        .sample-file-link {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 15px;
            background: #28a745;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }
        .sample-file-link:hover {
            background: #218838;
            color: white;
        }
        .import-type-buttons .btn {
            margin: 0 5px;
        }
        .table-container {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        .required-field::after {
            content: " *";
            color: red;
        }
        .instructions {
            background: #e7f3ff;
            border-right: 4px solid #4a6cf7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .next-id-info {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-weight: bold;
        }
    </style>
</head>
<body class="class-management-page">
<?php include __DIR__ . '/header.php'; ?>

<div class="container main-container">
    <div class="content-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex justify-content-start align-items-center mb-3">
                <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                    <span class="me-2">بستن</span>
                    <span aria-hidden="true" class="fs-5">×</span>
                </a>
                <h2 class="mb-0">وارد کردن اطلاعات از اکسل</h2>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errors): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill"></i> 
                <?php echo implode('<br>', $errors); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- راهنمای تبدیل اکسل به CSV -->
        <div class="instructions">
            <h5><i class="bi bi-info-circle me-2"></i>راهنمای تبدیل فایل اکسل به CSV:</h5>
            <ol class="mb-0">
                <li>فایل اکسل خود را در Microsoft Excel باز کنید</li>
                <li>از منوی File گزینه <strong>Save As</strong> را انتخاب کنید</li>
                <li>در قسمت Save as type، گزینه <strong>CSV UTF-8 (Comma delimited) (*.csv)</strong> را انتخاب کنید</li>
                <li>فایل را با نام دلخواه ذخیره کنید</li>
                <li>فایل CSV ایجاد شده را در این صفحه آپلود کنید</li>
            </ol>
        </div>

        <!-- بخش انتخاب نوع و آپلود فایل -->
        <div class="upload-section">
            <form method="post" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <label class="form-label fw-bold">انتخاب نوع اطلاعات:</label>
                        <div class="import-type-buttons">
                            <input type="radio" class="btn-check" name="import_type" value="teen" id="teen_import" 
                                   <?php echo $import_type === 'teen' ? 'checked' : ''; ?> required
                                   onchange="this.form.submit()">
                            <label class="btn btn-outline-primary" for="teen_import">
                                <i class="bi bi-person me-2"></i>نوجوانان
                            </label>

                            <input type="radio" class="btn-check" name="import_type" value="adult" id="adult_import"
                                   <?php echo $import_type === 'adult' ? 'checked' : ''; ?> required
                                   onchange="this.form.submit()">
                            <label class="btn btn-outline-success" for="adult_import">
                                <i class="bi bi-person-badge me-2"></i>بزرگسالان
                            </label>
                        </div>
                        
                        <?php if ($import_type && $next_id > 0): ?>
                            <div class="next-id-info mt-2">
                                <i class="bi bi-info-circle me-2"></i>
                                شناسه شروع برای 
                                <?php echo $import_type === 'teen' ? 'نوجوانان' : 'بزرگسالان'; ?>: 
                                <span class="badge bg-primary"><?php echo $next_id; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-8 mb-3">
                        <label class="form-label fw-bold">بارگذاری فایل CSV:</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <div class="form-text">فقط فایل‌های CSV قابل قبول هستند. لطفاً فایل اکسل را به CSV UTF-8 تبدیل کنید.</div>
                    </div>
                    
                    <div class="col-md-4 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-upload me-2"></i>بارگذاری و پیش‌نمایش
                        </button>
                    </div>
                </div>
            </form>

            <!-- لینک دانلود فایل نمونه -->
            <div class="mt-4">
                <a href="../assets/img/sample.csv" class="sample-file-link" download>
                    <i class="bi bi-download me-2"></i>دانلود فایل نمونه CSV
                </a>
				<a href="../assets/img/excel.xlsm" class="sample-file-link" download>
                    <i class="bi bi-download me-2"></i>دانلود فایل نمونه xlsx
                </a>
                <div class="form-text mt-2">
                    <strong class="required-field">ساختار فایل نمونه (ستون‌های الزامی):</strong><br>
                    <span class="required-field">ستون A: کدسیستمی</span> | <span class="required-field">ستون B: کدملی</span> | 
                    <span class="required-field">ستون C: نام</span> | <span class="required-field">ستون D: نام خانوادگی</span> | 
                    ستون E: نام پدر | ستون F: موبایل1 | ستون G: موبایل2 | ستون H: تاریخ تولد | 
                    ستون I: تاریخ ثبت نام | ستون J: وضعیت | ستون K: محل تولد | ستون L: محل صدور | 
                    ستون M: گروه خونی | ستون N: وضعیت تحصیلی | ستون O: شهر | ستون P: کدپستی | ستون Q: آدرس
                </div>
				<div class="form-text mt-2">
                    <strong class="required-field">توضیحات فایل xlsx</strong><br>
                    <span class="required-field">وقتی فایل نمونه xlsx را دانلود کردید.</span> | <span class="required-field"> بعد از تکمیل اطلاعات بر روی شیت یک راست کلیک نموده و گزینه view code را بزنید</span> | 
                    <span class="required-field">در پنجره دستورات VBA با زدن دکمه F5 در همان مسیر از فایل شما یک خروجی CSV گرفته میشود </span> | <span class="required-field">فایل CSV را بارگذاری کنید</span> | 
                    و تمام
                </div>
            </div>
        </div>

        <!-- پیش‌نمایش داده‌ها -->
        <?php if (!empty($preview_data) && isset($_SESSION['preview_data'])): ?>
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="bi bi-eye me-2"></i>
                پیش‌نمایش اطلاعات - <?php echo $import_type === 'teen' ? 'نوجوانان' : 'بزرگسالان'; ?>
                <span class="badge bg-light text-dark"><?php echo count($preview_data); ?> رکورد</span>
                <span class="badge bg-warning ms-2">شناسه شروع: <?php echo $next_id; ?></span>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    لطفاً اطلاعات زیر را بررسی کرده و در صورت صحیح بودن، دکمه "تایید و وارد کردن اطلاعات" را بزنید.
                    <br><strong>توجه:</strong> شناسه‌های نمایش داده شده در جدول، شناسه‌هایی هستند که پس از ذخیره در دیتابیس اختصاص خواهند یافت.
                </div>
                
                <div class="table-container">
                    <table class="table table-bordered table-striped preview-table">
                        <thead>
                            <tr>
                                <th width="50">ردیف</th>
                                <th width="80">شناسه آینده</th>
                                <th>کدسیستمی</th>
                                <th>کدملی</th>
                                <th>نام</th>
                                <th>نام خانوادگی</th>
                                <th>نام پدر</th>
                                <th>موبایل1</th>
                                <th>موبایل2</th>
                                <th>تاریخ تولد</th>
                                <th>تاریخ ثبت نام</th>
                                <th>وضعیت</th>
                                <th>محل تولد</th>
                                <th>محل صدور</th>
                                <th>گروه خونی</th>
                                <th>تحصیلات</th>
                                <th>شهر</th>
                                <th>کدپستی</th>
                                <th>آدرس</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $current_id = $next_id;
                            foreach ($preview_data as $index => $row): 
                            ?>
                            <tr>
                                <td class="text-center"><?php echo $index + 1; ?></td>
                                <td class="text-center fw-bold text-primary"><?php echo $current_id; ?></td>
                                <td class="<?php echo empty($row['sys_code']) ? 'table-danger' : ''; ?>"><?php echo htmlspecialchars($row['sys_code']); ?></td>
                                <td class="<?php echo empty($row['melli']) ? 'table-danger' : ''; ?>"><?php echo htmlspecialchars($row['melli']); ?></td>
                                <td class="<?php echo empty($row['name']) ? 'table-danger' : ''; ?>"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="<?php echo empty($row['family']) ? 'table-danger' : ''; ?>"><?php echo htmlspecialchars($row['family']); ?></td>
                                <td><?php echo htmlspecialchars($row['father']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile1']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile2']); ?></td>
                                <td><?php echo htmlspecialchars($row['birth_date'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['reg_date'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge 
                                        <?php echo $row['status'] === 'فعال' ? 'bg-success' : 
                                              ($row['status'] === 'تعلیق' ? 'bg-warning' : 'bg-secondary'); ?>">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['birth_place']); ?></td>
                                <td><?php echo htmlspecialchars($row['certi_place']); ?></td>
                                <td>
                                    <?php if (!empty($row['blood_type'])): ?>
                                        <span class="badge bg-danger"><?php echo htmlspecialchars($row['blood_type']); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['education']); ?></td>
                                <td><?php echo htmlspecialchars($row['city']); ?></td>
                                <td><?php echo htmlspecialchars($row['zip_code']); ?></td>
                                <td><small><?php echo htmlspecialchars($row['address']); ?></small></td>
                            </tr>
                            <?php 
                            $current_id++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-center">
                    <form method="post">
                        <input type="hidden" name="confirm_import" value="1">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="bi bi-check-circle me-2"></i>تایید و وارد کردن اطلاعات
                        </button>
                        <a href="excelteenadult.php" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle me-2"></i>انصراف
                        </a>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // اعتبارسنجی فایل قبل از آپلود
    $('input[type="file"]').on('change', function() {
        var file = this.files[0];
        var fileExtension = file.name.split('.').pop().toLowerCase();
        
        if (fileExtension !== 'csv') {
            alert('لطفاً فقط فایل‌های CSV انتخاب کنید.');
            this.value = '';
        }
        
        if (file.size > 10 * 1024 * 1024) { // 10MB
            alert('حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.');
            this.value = '';
        }
    });

    // اعتبارسنجی قبل از تایید نهایی
    $('form').on('submit', function(e) {
        var importType = $('input[name="import_type"]:checked').val();
        var fileInput = $('input[type="file"]')[0];
        
        if (!importType && !$(this).find('input[name="confirm_import"]').length) {
            e.preventDefault();
            alert('لطفاً نوع وارد کردن اطلاعات را انتخاب کنید.');
            return false;
        }
        
        if (!fileInput.files[0] && !$(this).find('input[name="confirm_import"]').length) {
            e.preventDefault();
            alert('لطفاً یک فایل CSV انتخاب کنید.');
            return false;
        }
    });

    // نمایش هشدار قبل از تایید نهایی
    $('form').on('submit', function() {
        if ($(this).find('input[name="confirm_import"]').length) {
            return confirm('آیا از وارد کردن اطلاعات اطمینان دارید؟ این عمل قابل بازگشت نیست.');
        }
    });
});
</script>
</body>
</html>