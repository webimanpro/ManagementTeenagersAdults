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
$adult = null;
$message = '';
$messageType = '';

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = 'اطلاعات با موفقیت به‌روزرسانی شد.';
    $messageType = 'success';
}

// Check if AdultID is provided in the URL
if (isset($_GET['AdultID']) && is_numeric($_GET['AdultID'])) {
    $adultID = (int)$_GET['AdultID'];
    $stmt = $conn->prepare("SELECT * FROM adult WHERE AdultID = ? LIMIT 1");
    $stmt->bind_param("i", $adultID);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $adult = $result->fetch_assoc();
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
    $stmt = $conn->prepare("SELECT * FROM adult WHERE AdultMelli = ? OR AdultSysCode = ? LIMIT 1");
    $stmt->bind_param("ss", $q, $q);
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $adult = $result->fetch_assoc();
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['AdultID'])) {
    $AdultID = (int)$_POST['AdultID'];
    
    // Get form data
    $AdultSysCode = trim($_POST['AdultSysCode'] ?? '');
    $AdultMelli = trim($_POST['AdultMelli'] ?? '');
    $AdultName = trim($_POST['AdultName'] ?? '');
    $AdultFamily = trim($_POST['AdultFamily'] ?? '');
    $AdultFather = trim($_POST['AdultFather'] ?? '');
    $AdultMobile1 = trim($_POST['AdultMobile1'] ?? '');
    $AdultMobile2 = trim($_POST['AdultMobile2'] ?? '');
    $AdultDateBirth = !empty($_POST['AdultDateBirth']) ? $_POST['AdultDateBirth'] : null;
    $AdultRegDate = !empty($_POST['AdultRegDate']) ? $_POST['AdultRegDate'] : null; // اضافه شد
    $AdultPlaceBirth = trim($_POST['AdultPlaceBirth'] ?? '');
    $AdultPlaceCerti = trim($_POST['AdultPlaceCerti'] ?? '');
    $AdultBloodType = trim($_POST['AdultBloodType'] ?? '');
    $AdultEducation = trim($_POST['AdultEducation'] ?? '');
    $AdultAddress = trim($_POST['AdultAddress'] ?? '');
    $AdultZipCode = trim($_POST['AdultZipCode'] ?? '');
    $AdultStatus = trim($_POST['AdultStatus'] ?? 'عادی');
    $AdultCity = trim($_POST['AdultCity'] ?? '');
    $deleteImage = isset($_POST['delete_image']) && $_POST['delete_image'] == '1';
    
    // Handle image upload
    $imageUpdate = '';
    if (isset($_FILES['AdultImage']) && $_FILES['AdultImage']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['AdultImage'];
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $uploadDir = realpath(__DIR__ . '/..') . '/upload/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $newFilename = 'adult_' . time() . '_' . uniqid() . '.' . $ext;
            $targetPath = $uploadDir . $newFilename;
            
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $imageUpdate = ", AdultImage = '/upload/" . $newFilename . "'";
                
                // Delete old image if exists
                $stmt = $conn->prepare("SELECT AdultImage FROM adult WHERE AdultID = ?");
                $stmt->bind_param("i", $AdultID);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    if (!empty($row['AdultImage']) && file_exists(realpath(__DIR__ . '/..') . $row['AdultImage'])) {
                        @unlink(realpath(__DIR__ . '/..') . $row['AdultImage']);
                    }
                }
                $stmt->close();
            }
        }
    } elseif ($deleteImage) {
        // Delete existing image if delete was requested
        $stmt = $conn->prepare("SELECT AdultImage FROM adult WHERE AdultID = ?");
        $stmt->bind_param("i", $AdultID);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if (!empty($row['AdultImage']) && file_exists(realpath(__DIR__ . '/..') . $row['AdultImage'])) {
                @unlink(realpath(__DIR__ . '/..') . $row['AdultImage']);
            }
        }
        $stmt->close();
        $imageUpdate = ", AdultImage = NULL";
    }
    
    // Convert Jalali dates to Gregorian for database
    $AdultDateBirthGregorian = null;
    if (!empty($AdultDateBirth)) {
        $AdultDateBirthGregorian = to_gregorian_date($AdultDateBirth);
    }
    
    $AdultRegDateGregorian = null;
    if (!empty($AdultRegDate)) {
        $AdultRegDateGregorian = to_gregorian_date($AdultRegDate);
    }
    
    // Update the record in the database
    $sql = "UPDATE adult SET 
            AdultSysCode = ?, 
            AdultMelli = ?, 
            AdultName = ?, 
            AdultFamily = ?, 
            AdultFather = ?, 
            AdultMobile1 = ?, 
            AdultMobile2 = ?, 
            AdultDateBirth = ?, 
            AdultRegDate = ?, 
            AdultPlaceBirth = ?, 
            AdultPlaceCerti = ?, 
            AdultBloodType = ?, 
            AdultEducation = ?, 
            AdultAddress = ?, 
            AdultZipCode = ?, 
            AdultStatus = ?,
            AdultCity = ?";
    
    // Add image update to SQL if needed
    if (!empty($imageUpdate)) {
        $sql .= $imageUpdate;
    }
    
    $sql .= " WHERE AdultID = ?";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        // Bind parameters
        $stmt->bind_param("sssssssssssssssssi", 
            $AdultSysCode, $AdultMelli, $AdultName, $AdultFamily, 
            $AdultFather, $AdultMobile1, $AdultMobile2, $AdultDateBirthGregorian,
            $AdultRegDateGregorian, $AdultPlaceBirth, $AdultPlaceCerti, $AdultBloodType, 
            $AdultEducation, $AdultAddress, $AdultZipCode, $AdultStatus, $AdultCity, $AdultID
        );
        
        if ($stmt->execute()) {
            // Get the updated data from the database
            $stmt = $conn->prepare("SELECT * FROM adult WHERE AdultID = ?");
            $stmt->bind_param("i", $AdultID);
            $stmt->execute();
            $result = $stmt->get_result();
            $adult = $result->fetch_assoc();
            
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
    <title>ویرایش اطلاعات بزرگسال</title>
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
    <?php if (!isset($adult)): ?>
	
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
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($adult)): ?>
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
            <input type="hidden" name="AdultID" value="<?php echo $adult['AdultID']; ?>">
            
            <div class="row mb-3">
                <div class="col-md-3">
                    <div class="text-center mb-3">
                        <img src="<?php echo !empty($adult['AdultImage']) ? '../' . $adult['AdultImage'] : '../assets/img/avatarprofile.png'; ?>" 
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
                                        <?php echo empty($adult['AdultImage']) ? 'disabled' : ''; ?>>
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
                            <input type="text" class="form-control" name="AdultSysCode" 
                                   value="<?php echo htmlspecialchars($adult['AdultSysCode']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">کدملی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="AdultMelli" 
                                   value="<?php echo htmlspecialchars($adult['AdultMelli']); ?>" required inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">وضعیت</label>
                            <select class="form-select" name="AdultStatus">
                                <option value="عادی" <?php echo $adult['AdultStatus'] === 'عادی' ? 'selected' : ''; ?>>عادی</option>
                                <option value="فعال" <?php echo $adult['AdultStatus'] === 'فعال' ? 'selected' : ''; ?>>فعال</option>
                                <option value="تعلیق" <?php echo $adult['AdultStatus'] === 'تعلیق' ? 'selected' : ''; ?>>تعلیق</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">نام <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="AdultName" 
                                   value="<?php echo htmlspecialchars($adult['AdultName']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">نام خانوادگی <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="AdultFamily" 
                                   value="<?php echo htmlspecialchars($adult['AdultFamily']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">نام پدر <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="AdultFather" 
                                   value="<?php echo htmlspecialchars($adult['AdultFather']); ?>" required>
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
                               value="<?php echo !empty($adult['AdultDateBirth']) ? to_persian_date($adult['AdultDateBirth']) : ''; ?>"
                               placeholder="1400/01/01">
                    </div>
                </div>

                <!-- تاریخ ثبت نام -->
                <div class="col-md-3 mb-3">
                    <label class="form-label">تاریخ ثبت نام</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                        <input type="text" class="form-control persian-date" name="AdultRegDate" 
                               value="<?php echo !empty($adult['AdultRegDate']) ? to_persian_date($adult['AdultRegDate']) : ''; ?>"
                               placeholder="1400/01/01">
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">محل تولد</label>
                    <input type="text" class="form-control" name="AdultPlaceBirth" 
                           value="<?php echo htmlspecialchars($adult['AdultPlaceBirth'] ?? ''); ?>">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">گروه خونی</label>
                    <select class="form-select" name="AdultBloodType">
                        <option value="">انتخاب کنید</option>
                        <?php
                        $bloodTypes = ['O+', 'O-', 'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-'];
                        foreach ($bloodTypes as $type) {
                            $selected = (isset($adult['AdultBloodType']) && $adult['AdultBloodType'] === $type) ? 'selected' : '';
                            echo "<option value=\"$type\" $selected>$type</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="row">
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
                            $selected = (isset($adult['AdultEducation']) && $adult['AdultEducation'] === $edu) ? 'selected' : '';
                            echo "<option value=\"$edu\" $selected>$edu</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">موبایل1</label>
                    <input type="tel" class="form-control" name="AdultMobile1" 
                           value="<?php echo htmlspecialchars($adult['AdultMobile1'] ?? ''); ?>" inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">موبایل2</label>
                    <input type="tel" class="form-control" name="AdultMobile2" 
                           value="<?php echo htmlspecialchars($adult['AdultMobile2'] ?? ''); ?>" inputmode="numeric" pattern="^\d{11}$" maxlength="11">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">محل صدور</label>
                    <input type="text" class="form-control" name="AdultPlaceCerti" 
                           value="<?php echo htmlspecialchars($adult['AdultPlaceCerti'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label class="form-label">کد پستی</label>
                    <input type="text" class="form-control" name="AdultZipCode" 
                           value="<?php echo htmlspecialchars($adult['AdultZipCode'] ?? ''); ?>" inputmode="numeric" pattern="^\d{10}$" maxlength="10">
                </div>
                <div class="col-md-3 mb-3">
                    <label class="form-label">شهر</label>
                    <input type="text" class="form-control" name="AdultCity" 
                           value="<?php echo htmlspecialchars($adult['AdultCity'] ?? ''); ?>">
                </div>
            </div>

            <div class="row">
                <div class="col-12 mb-3">
                    <label class="form-label">آدرس</label>
                    <textarea class="form-control" name="AdultAddress" rows="2"><?php echo htmlspecialchars($adult['AdultAddress'] ?? ''); ?></textarea>
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
        document.getElementById('AdultImage').value = ''; // Clear the file input
    }
});

// Real-time validation for national code
document.addEventListener('DOMContentLoaded', function() {
    const adultMelliInput = document.querySelector('input[name="AdultMelli"]');
    
    adultMelliInput.addEventListener('input', function() {
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