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

// Initialize variables
$q = trim($_GET['q'] ?? '');
$teen = null;
$message = '';
$messageType = '';

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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['TeenID'])) {
    $TeenID = (int)$_POST['TeenID'];
    
    // Get form data
    $TeenSysCode = trim($_POST['TeenSysCode'] ?? '');
    $TeenMelli = trim($_POST['TeenMelli'] ?? '');
    $TeenName = trim($_POST['TeenName'] ?? '');
    $TeenFamily = trim($_POST['TeenFamily'] ?? '');
    $TeenFather = trim($_POST['TeenFather'] ?? '');
    $TeenMobile1 = trim($_POST['TeenMobile1'] ?? '');
    $TeenMobile2 = trim($_POST['TeenMobile2'] ?? '');
    $TeenDateBirth = !empty($_POST['TeenDateBirth']) ? $_POST['TeenDateBirth'] : null;
    $TeenRegDate = !empty($_POST['TeenRegDate']) ? $_POST['TeenRegDate'] : null; // اضافه شد
    $TeenPlaceBirth = trim($_POST['TeenPlaceBirth'] ?? '');
    $TeenPlaceCerti = trim($_POST['TeenPlaceCerti'] ?? '');
    $TeenBloodType = trim($_POST['TeenBloodType'] ?? '');
    $TeenEducation = trim($_POST['TeenEducation'] ?? '');
    $TeenAddress = trim($_POST['TeenAddress'] ?? '');
    $TeenZipCode = trim($_POST['TeenZipCode'] ?? '');
    $TeenStatus = trim($_POST['TeenStatus'] ?? 'عادی');
    $TeenCity = trim($_POST['TeenCity'] ?? '');
    $deleteImage = isset($_POST['delete_image']) && $_POST['delete_image'] == '1';
    
    // Handle image upload
    $imageUpdate = '';
    if (isset($_FILES['TeenImage']) && $_FILES['TeenImage']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['TeenImage'];
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $uploadDir = realpath(__DIR__ . '/..') . '/upload/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newFilename = 'teen_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $imageUpdate = ", TeenImage = '/upload/" . $newFilename . "'";
                
                // Delete old image if exists
                $stmt = $conn->prepare("SELECT TeenImage FROM Teen WHERE TeenID = ?");
                $stmt->bind_param("i", $TeenID);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (!empty($row['TeenImage']) && file_exists(realpath(__DIR__ . '/..') . $row['TeenImage'])) {
                        @unlink(realpath(__DIR__ . '/..') . $row['TeenImage']);
                    }
                }
                $stmt->close();
            }
        }
    } elseif ($deleteImage) {
        // Delete existing image if delete was requested
        $stmt = $conn->prepare("SELECT TeenImage FROM Teen WHERE TeenID = ?");
        $stmt->bind_param("i", $TeenID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['TeenImage']) && file_exists(realpath(__DIR__ . '/..') . $row['TeenImage'])) {
                @unlink(realpath(__DIR__ . '/..') . $row['TeenImage']);
            }
        }
        $stmt->close();
        $imageUpdate = ", TeenImage = NULL";
    }
    
    // Convert Jalali dates to Gregorian for database
    $TeenDateBirthGregorian = null;
    if (!empty($TeenDateBirth)) {
        $TeenDateBirthGregorian = to_gregorian_date($TeenDateBirth);
    }
    
    $TeenRegDateGregorian = null;
    if (!empty($TeenRegDate)) {
        $TeenRegDateGregorian = to_gregorian_date($TeenRegDate);
    }
    
    // Update the record in the database
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
            TeenPlaceBirth = ?, 
            TeenPlaceCerti = ?, 
            TeenBloodType = ?, 
            TeenEducation = ?, 
            TeenAddress = ?, 
            TeenZipCode = ?, 
            TeenStatus = ?,
            TeenCity = ?";
    
    // Add image update to SQL if needed
    if (!empty($imageUpdate)) {
        $sql .= $imageUpdate;
    }
    
    $sql .= " WHERE TeenID = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("sssssssssssssssssi", 
            $TeenSysCode, $TeenMelli, $TeenName, $TeenFamily, 
            $TeenFather, $TeenMobile1, $TeenMobile2, $TeenDateBirthGregorian,
            $TeenRegDateGregorian, $TeenPlaceBirth, $TeenPlaceCerti, $TeenBloodType, 
            $TeenEducation, $TeenAddress, $TeenZipCode, $TeenStatus, $TeenCity, $TeenID
        );
        
        if ($stmt->execute()) {
            // Get the updated data from the database
            $stmt = $conn->prepare("SELECT * FROM Teen WHERE TeenID = ?");
            $stmt->bind_param("i", $TeenID);
            $stmt->execute();
            $result = $stmt->get_result();
            $teen = $result->fetch_assoc();
            
            $message = 'اطلاعات با موفقیت به‌روزرسانی شد.';
            $messageType = 'success';
        } else {
            $message = 'خطا در به‌روزرسانی اطلاعات: ' . $conn->error;
            $messageType = 'danger';
        }
        $stmt->close();
    } else {
        $message = 'خطا در آماده‌سازی کوئری: ' . $conn->error;
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
            padding-bottom: 60px; /* Add padding to prevent footer from overlapping content */
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
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">کدسیستمی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="TeenSysCode" 
                                   value="<?php echo htmlspecialchars($teen['TeenSysCode']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">کدملی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="TeenMelli" 
                                   value="<?php echo htmlspecialchars($teen['TeenMelli']); ?>" required inputmode="numeric" pattern="^\d{10}$" maxlength="10">
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
                    <label class="form-label">تاریخ ثبت نام</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" class="form-control persian-date" name="TeenRegDate" 
                               value="<?php echo !empty($teen['TeenRegDate']) ? to_persian_date($teen['TeenRegDate']) : ''; ?>"
                               placeholder="1400/01/01">
                    </div>
                </div>

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
            </div>

            <div class="row">
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
                    <label class="form-label">موبایل1</label>
                    <input type="tel" class="form-control" name="TeenMobile1" 
                           value="<?php echo htmlspecialchars($teen['TeenMobile1'] ?? ''); ?>" inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">موبایل2</label>
                    <input type="tel" class="form-control" name="TeenMobile2" 
                           value="<?php echo htmlspecialchars($teen['TeenMobile2'] ?? ''); ?>" inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">محل صدور</label>
                    <input type="text" class="form-control" name="TeenPlaceCerti" 
                           value="<?php echo htmlspecialchars($teen['TeenPlaceCerti'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">کد پستی</label>
                    <input type="text" class="form-control" name="TeenZipCode" 
                           value="<?php echo htmlspecialchars($teen['TeenZipCode'] ?? ''); ?>" inputmode="numeric" pattern="^\d{10}$" maxlength="10">
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

            <div class="row mt-4">
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save"></i> ذخیره تغییرات
                    </button>
                </div>
            </div>
        </form>
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
        document.getElementById('TeenImage').value = ''; // Clear the file input
    }
});

// Real-time validation for national code
document.addEventListener('DOMContentLoaded', function() {
    const teenMelliInput = document.querySelector('input[name="TeenMelli"]');
    
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