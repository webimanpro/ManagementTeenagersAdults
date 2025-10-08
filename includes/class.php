<?php

// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

// Ensure Class table exists
$conn->query("CREATE TABLE IF NOT EXISTS `Class` (
  `ClassID` int(11) NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(100) NOT NULL,
  `ClassDateStart` date DEFAULT NULL,
  `ClassDateEnd` date DEFAULT NULL,
  `ClassTime` varchar(20) DEFAULT NULL,
  `ClassTeacher` varchar(100) DEFAULT NULL,
  `ClassPlace` varchar(100) DEFAULT NULL,
  `ClassDescription` text DEFAULT NULL,
  `CalssUsers` text DEFAULT NULL,
  PRIMARY KEY (`ClassID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$errors = [];
$success = '';

// Handle search functionality first
$teenSearch = $_POST['teen_search'] ?? '';
$adultSearch = $_POST['adult_search'] ?? '';

// Clear search if needed
if (isset($_POST['clear_teen_search'])) {
    $teenSearch = '';
}
if (isset($_POST['clear_adult_search'])) {
    $adultSearch = '';
}

// Handle add new class
$editMode = isset($_GET['edit']) && is_numeric($_GET['edit']);
$classData = [
    'ClassName' => '',
    'ClassDateStart' => '',
    'ClassDateEnd' => '',
    'ClassTime' => '',
    'ClassTeacher' => '',
    'ClassPlace' => '',
    'ClassDescription' => '',
    'CalssUsers' => '[]'
];

// Handle edit mode
$editId = 0;
if ($editMode) {
    $editId = (int)$_GET['edit'];
    $stmt = $conn->prepare("SELECT * FROM `Class` WHERE ClassID = ?");
    $stmt->bind_param('i', $editId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $classData = $result->fetch_assoc();
    } else {
        $errors[] = 'دوره مورد نظر یافت نشد.';
        $editMode = false;
    }
    $stmt->close();
}

// Handle form submission - ONLY if not a search request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['search_teen']) && !isset($_POST['search_adult']) && !isset($_POST['clear_teen_search']) && !isset($_POST['clear_adult_search'])) {
    
    // Check if it's edit mode from hidden field
    if (isset($_POST['edit_id']) && is_numeric($_POST['edit_id']) && $_POST['edit_id'] > 0) {
        $editMode = true;
        $editId = (int)$_POST['edit_id'];
    }
    
    $ClassName = trim($_POST['ClassName'] ?? '');
    $ClassDateStart = $_POST['ClassDateStart'] ?? null;
    $ClassDateEnd = $_POST['ClassDateEnd'] ?? null;
    $ClassTime = trim($_POST['ClassTime'] ?? '');
    $ClassTeacher = trim($_POST['ClassTeacher'] ?? '');
    $ClassPlace = trim($_POST['ClassPlace'] ?? '');
    $ClassDescription = trim($_POST['ClassDescription'] ?? '');
    $CalssUsers = trim($_POST['CalssUsers'] ?? '[]');

    // Validate required fields
    if (empty($ClassName)) { 
        $errors[] = 'نام دوره الزامی است.'; 
    }

    // Validate JSON format for CalssUsers
    if ($CalssUsers !== '[]') {
        $testJson = json_decode($CalssUsers);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'فرمت داده‌های کاربران نامعتبر است.';
            $CalssUsers = '[]';
        }
    }

    // اعتبارسنجی فرمت تاریخ میلادی
    if ($ClassDateStart && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ClassDateStart)) {
        $errors[] = 'فرمت تاریخ شروع نامعتبر است. باید به صورت YYYY-MM-DD باشد.';
    }
    
    if ($ClassDateEnd && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $ClassDateEnd)) {
        $errors[] = 'فرمت تاریخ پایان نامعتبر است. باید به صورت YYYY-MM-DD باشد.';
    }

    // اگر خطایی وجود ندارد، ذخیره سازی انجام شود
    if (empty($errors)) {
        if ($editMode) {
            // Update existing class
            $stmt = $conn->prepare("UPDATE `Class` SET ClassName=?, ClassDateStart=?, ClassDateEnd=?, ClassTime=?, ClassTeacher=?, ClassPlace=?, ClassDescription=?, CalssUsers=? WHERE ClassID=?");
            if ($stmt) {
                $stmt->bind_param('ssssssssi', $ClassName, $ClassDateStart, $ClassDateEnd, $ClassTime, $ClassTeacher, $ClassPlace, $ClassDescription, $CalssUsers, $editId);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = 'دوره با موفقیت به‌روزرسانی شد.';
                    header('Location: class.php?success=1');
                    exit();
                } else {
                    $errors[] = 'خطا در به‌روزرسانی دوره: ' . $conn->error;
                }
                $stmt->close();
            } else {
                $errors[] = 'خطا در آماده‌سازی کوئری به‌روزرسانی: ' . $conn->error;
            }
        } else {
            // Insert new class - مهم: ClassID حذف شده تا AUTO_INCREMENT کار کند
            $stmt = $conn->prepare("INSERT INTO `Class` (ClassName, ClassDateStart, ClassDateEnd, ClassTime, ClassTeacher, ClassPlace, ClassDescription, CalssUsers) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssssssss', $ClassName, $ClassDateStart, $ClassDateEnd, $ClassTime, $ClassTeacher, $ClassPlace, $ClassDescription, $CalssUsers);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = 'دوره جدید با موفقیت ثبت شد.';
                    header('Location: class.php?success=1');
                    exit();
                } else {
                    $errors[] = 'خطا در ثبت دوره جدید: ' . $conn->error;
                }
                $stmt->close();
            } else {
                $errors[] = 'خطا در آماده‌سازی کوئری ثبت: ' . $conn->error;
            }
        }
    }
}

// نمایش پیام موفقیت بعد از ریدایرکت
if (isset($_GET['success']) && isset($_SESSION['success_message'])) {
    $success = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Build queries with search
$teenQuery = "SELECT TeenID as id, TeenName as firstName, TeenFamily as lastName, TeenSysCode as sysCode, TeenMelli as melli, 'teen' as type FROM Teen";
if (!empty($teenSearch)) {
    $teenQuery .= " WHERE TeenSysCode LIKE '%" . $conn->real_escape_string($teenSearch) . "%' OR TeenMelli LIKE '%" . $conn->real_escape_string($teenSearch) . "%'";
}
$teenQuery .= " ORDER BY CAST(TeenSysCode AS UNSIGNED)";
$teens = $conn->query($teenQuery);

$adultQuery = "SELECT AdultID as id, AdultName as firstName, AdultFamily as lastName, AdultSysCode as sysCode, AdultMelli as melli, 'adult' as type FROM Adult";
if (!empty($adultSearch)) {
    $adultQuery .= " WHERE AdultSysCode LIKE '%" . $conn->real_escape_string($adultSearch) . "%' OR AdultMelli LIKE '%" . $conn->real_escape_string($adultSearch) . "%'";
}
$adultQuery .= " ORDER BY CAST(AdultSysCode AS UNSIGNED)";
$adults = $conn->query($adultQuery);

$rs = $conn->query("SELECT * FROM `Class` ORDER BY ClassID DESC");

// Parse selected users for edit mode
$selectedUsers = [];
if (!empty($classData['CalssUsers']) && $classData['CalssUsers'] !== '[]') {
    $decoded = json_decode($classData['CalssUsers'], true);
    if (is_array($decoded)) {
        $selectedUsers = $decoded;
    }
}

// Create array for quick lookup
$selectedUserIds = [];
foreach ($selectedUsers as $user) {
    if (isset($user['id']) && isset($user['type'])) {
        $selectedUserIds[$user['type'] . '_' . $user['id']] = true;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت دوره</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
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
                <h2 class="mb-0">مدیریت دوره</h2>
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

        <form method="post" class="mb-4" id="class-form">
            <?php if ($editMode): ?>
                <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">
            <?php endif; ?>
            
            <!-- بخش اطلاعات اصلی دوره -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <label class="form-label">نام دوره <span class="text-danger">*</span></label>
                    <input name="ClassName" class="form-control" 
                           value="<?php echo htmlspecialchars($classData['ClassName'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">تاریخ شروع</label>
                    <input type="date" name="ClassDateStart" class="form-control date-input" 
                           value="<?php echo !empty($classData['ClassDateStart']) && $classData['ClassDateStart'] != '0000-00-00' ? $classData['ClassDateStart'] : ''; ?>">
                    <small class="form-text text-muted">فرمت: YYYY-MM-DD</small>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">تاریخ پایان</label>
                    <input type="date" name="ClassDateEnd" class="form-control date-input" 
                           value="<?php echo !empty($classData['ClassDateEnd']) && $classData['ClassDateEnd'] != '0000-00-00' ? $classData['ClassDateEnd'] : ''; ?>">
                    <small class="form-text text-muted">فرمت: YYYY-MM-DD</small>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">ساعت دوره</label>
                    <input name="ClassTime" class="form-control" placeholder="مثال: 16:00-18:00" 
                           value="<?php echo htmlspecialchars($classData['ClassTime'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">مربی دوره</label>
                    <input name="ClassTeacher" class="form-control" 
                           value="<?php echo htmlspecialchars($classData['ClassTeacher'] ?? ''); ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">مکان دوره</label>
                    <input name="ClassPlace" class="form-control" 
                           value="<?php echo htmlspecialchars($classData['ClassPlace'] ?? ''); ?>">
                </div>
            </div>

            <!-- بخش انتخاب کاربران -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">انتخاب کاربران دوره</h5>
                    <div class="alert selection-info">
                        <i class="bi bi-people-fill"></i> 
                        کاربران انتخاب شده: <span id="selected-count"><?php echo count($selectedUsers); ?></span>
                        <span class="badge bg-light text-dark ms-2" id="teens-count">0 نوجوان</span>
                        <span class="badge bg-light text-dark ms-2" id="adults-count">0 بزرگسال</span>
                    </div>
                </div>

                <!-- لیست نوجوانان -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-people-fill me-2"></i>لیست نوجوانان
                            </div>
                            <?php if ($teens): ?>
                                <span class="badge bg-light text-dark"><?php echo $teens->num_rows; ?> نفر</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- جستجوی نوجوانان -->
                        <div class="card-body py-2 border-bottom">
                            <div class="d-flex gap-2">
                                <input type="text" 
                                       name="teen_search" 
                                       class="form-control form-control-sm" 
                                       placeholder="جستجو با کدسیستمی یا کد ملی..."
                                       value="<?php echo htmlspecialchars($teenSearch); ?>"
                                       dir="ltr">
                                <button type="submit" class="btn btn-success btn-sm" name="search_teen">
                                    <i class="bi bi-search"></i>
                                </button>
                                <?php if (!empty($teenSearch)): ?>
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" name="clear_teen_search">
                                        <i class="bi bi-x"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="user-list">
                                <?php
                                if ($teens && $teens->num_rows > 0) {
                                    $teens->data_seek(0);
                                    $teenCount = 0;
                                    while($user = $teens->fetch_assoc()) {
                                        $key = 'teen_' . $user['id'];
                                        $isSelected = isset($selectedUserIds[$key]);
                                        if ($isSelected) $teenCount++;
                                ?>
                                <div class="user-item <?php echo $isSelected ? 'selected' : ''; ?>" 
                                     data-id="<?php echo $user['id']; ?>" 
                                     data-type="<?php echo $user['type']; ?>">
                                    <input class="form-check-input user-checkbox" type="checkbox" 
                                           value='<?php echo json_encode(['id' => $user['id'], 'type' => 'teen']); ?>'
                                           id="teen_<?php echo $user['id']; ?>"
                                           <?php echo $isSelected ? 'checked' : ''; ?>>
                                    <label class="form-check-label d-flex align-items-center w-100" for="teen_<?php echo $user['id']; ?>">
                                        <span class="user-name ms-2">
                                            <i class="bi bi-person-circle me-2"></i>
                                            <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?>
                                        </span>
                                        <div class="d-flex flex-column align-items-start ms-2">
                                            <span class="user-code"><?php echo htmlspecialchars($user['sysCode']); ?></span>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['melli']); ?></small>
                                        </div>
                                    </label>
                                </div>
                                <?php
                                    }
                                } else {
                                    echo '<div class="p-3 text-muted text-center">';
                                    if (!empty($teenSearch)) {
                                        echo '<i class="bi bi-search me-2"></i>هیچ نوجوانی با مشخصات جستجو شده یافت نشد';
                                    } else {
                                        echo '<i class="bi bi-people"></i> نوجوانی یافت نشد';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- لیست بزرگسالان -->
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <div>
                                <i class="bi bi-people-fill me-2"></i>لیست بزرگسالان
                            </div>
                            <?php if ($adults): ?>
                                <span class="badge bg-light text-dark"><?php echo $adults->num_rows; ?> نفر</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- جستجوی بزرگسالان -->
                        <div class="card-body py-2 border-bottom">
                            <div class="d-flex gap-2">
                                <input type="text" 
                                       name="adult_search" 
                                       class="form-control form-control-sm" 
                                       placeholder="جستجو با کدسیستمی یا کد ملی..."
                                       value="<?php echo htmlspecialchars($adultSearch); ?>"
                                       dir="ltr">
                                <button type="submit" class="btn btn-success btn-sm" name="search_adult">
                                    <i class="bi bi-search"></i>
                                </button>
                                <?php if (!empty($adultSearch)): ?>
                                    <button type="submit" class="btn btn-outline-secondary btn-sm" name="clear_adult_search">
                                        <i class="bi bi-x"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card-body p-0">
                            <div class="user-list">
                                <?php
                                if ($adults && $adults->num_rows > 0) {
                                    $adults->data_seek(0);
                                    $adultCount = 0;
                                    while($user = $adults->fetch_assoc()) {
                                        $key = 'adult_' . $user['id'];
                                        $isSelected = isset($selectedUserIds[$key]);
                                        if ($isSelected) $adultCount++;
                                ?>
                                <div class="user-item <?php echo $isSelected ? 'selected' : ''; ?>" 
                                     data-id="<?php echo $user['id']; ?>" 
                                     data-type="<?php echo $user['type']; ?>">
                                    <input class="form-check-input user-checkbox" type="checkbox" 
                                           value='<?php echo json_encode(['id' => $user['id'], 'type' => 'adult']); ?>'
                                           id="adult_<?php echo $user['id']; ?>"
                                           <?php echo $isSelected ? 'checked' : ''; ?>>
                                    <label class="form-check-label d-flex align-items-center w-100" for="adult_<?php echo $user['id']; ?>">
                                        <span class="user-name ms-2">
                                            <i class="bi bi-person-badge me-2"></i>
                                            <?php echo htmlspecialchars($user['firstName'] . ' ' . $user['lastName']); ?>
                                        </span>
                                        <div class="d-flex flex-column align-items-start ms-2">
                                            <span class="user-code"><?php echo htmlspecialchars($user['sysCode']); ?></span>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['melli']); ?></small>
                                        </div>
                                    </label>
                                </div>
                                <?php
                                    }
                                } else {
                                    echo '<div class="p-3 text-muted text-center">';
                                    if (!empty($adultSearch)) {
                                        echo '<i class="bi bi-search me-2"></i>هیچ بزرگسالی با مشخصات جستجو شده یافت نشد';
                                    } else {
                                        echo '<i class="bi bi-people"></i> بزرگسالی یافت نشد';
                                    }
                                    echo '</div>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="CalssUsers" id="CalssUsers" value="<?php echo htmlspecialchars($classData['CalssUsers'] ?? '[]'); ?>">
            
            <!-- بخش توضیحات -->
            <div class="row mb-4">
                <div class="col-12">
                    <label class="form-label">توضیحات دوره</label>
                    <textarea name="ClassDescription" class="form-control" rows="3" placeholder="توضیحات مربوط به دوره..."><?php echo htmlspecialchars($classData['ClassDescription'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <!-- دکمه‌های عملیات -->
            <div class="row">
                <div class="col-12">
                    <button type="submit" class="btn btn-primary btn-lg" name="submit_class">
                        <i class="bi <?php echo $editMode ? 'bi-check-circle' : 'bi-plus-circle'; ?> me-2"></i>
                        <?php echo $editMode ? 'ذخیره تغییرات' : 'افزودن دوره'; ?>
                    </button>
                    <?php if ($editMode): ?>
                        <a href="class.php" class="btn btn-secondary btn-lg">
                            <i class="bi bi-x-circle me-2"></i>انصراف
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <!-- جدول نمایش دوره‌ها -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">لیست دوره‌ها</h5>
                    <span class="badge bg-primary"><?php echo $rs ? $rs->num_rows : 0; ?> دوره</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr class="table-primary">
                                <th width="50" class="text-center">#</th>
                                <th>نام دوره</th>
                                <th width="150">مربی</th>
                                <th width="120" class="text-center">تاریخ شروع</th>
                                <th width="120" class="text-center">تاریخ پایان</th>
                                <th width="120" class="text-center">کاربران</th>
                                <th width="100" class="text-center">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        if ($rs && $rs->num_rows > 0): 
                            $rs->data_seek(0);
                            while($row = $rs->fetch_assoc()): 
                                $usersCount = 0;
                                $teensCount = 0;
                                $adultsCount = 0;
                                
                                if (!empty($row['CalssUsers']) && $row['CalssUsers'] !== '[]') {
                                    $users = json_decode($row['CalssUsers'], true);
                                    if (is_array($users)) {
                                        $usersCount = count($users);
                                        foreach ($users as $user) {
                                            if ($user['type'] === 'teen') $teensCount++;
                                            if ($user['type'] === 'adult') $adultsCount++;
                                        }
                                    }
                                }
                        ?>
                            <tr>
                                <td class="text-center fw-bold text-muted"><?php echo $row['ClassID']; ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['ClassName']); ?></div>
                                </td>
                                <td>
                                    <span class="text-primary"><?php echo htmlspecialchars($row['ClassTeacher']); ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?php echo !empty($row['ClassDateStart']) && $row['ClassDateStart'] != '0000-00-00' ? $row['ClassDateStart'] : '-'; ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border"><?php echo !empty($row['ClassDateEnd']) && $row['ClassDateEnd'] != '0000-00-00' ? $row['ClassDateEnd'] : '-'; ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <?php if ($teensCount > 0): ?>
                                            <span class="badge bg-primary" title="نوجوانان"><?php echo $teensCount; ?></span>
                                        <?php endif; ?>
                                        <?php if ($adultsCount > 0): ?>
                                            <span class="badge bg-success" title="بزرگسالان"><?php echo $adultsCount; ?></span>
                                        <?php endif; ?>
                                        <?php if ($usersCount === 0): ?>
                                            <span class="badge bg-secondary">0</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <a href="?edit=<?php echo $row['ClassID']; ?>" class="btn btn-warning btn-sm" title="ویرایش دوره">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x display-4 d-block mb-3"></i>
                                    <span class="fs-5">دوره‌ای ثبت نشده است</span>
                                    <br>
                                    <small class="text-muted">برای ایجاد دوره جدید از فرم بالا استفاده کنید</small>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // Update selection counts
    function updateSelectionCounts() {
        const totalCount = $('input.user-checkbox:checked').length;
        const teensCount = $('input.user-checkbox:checked').filter(function() {
            const userData = JSON.parse($(this).val());
            return userData.type === 'teen';
        }).length;
        const adultsCount = $('input.user-checkbox:checked').filter(function() {
            const userData = JSON.parse($(this).val());
            return userData.type === 'adult';
        }).length;
        
        $('#selected-count').text(totalCount);
        $('#teens-count').text(teensCount + ' نوجوان');
        $('#adults-count').text(adultsCount + ' بزرگسال');
    }
    
    // Update the hidden input with selected users
    function updateUsersHiddenInput() {
        const selectedUsers = [];
        
        $('input.user-checkbox:checked').each(function() {
            try {
                const userData = JSON.parse($(this).val());
                const $item = $(this).closest('.user-item');
                
                if (userData && userData.id && userData.type) {
                    selectedUsers.push({
                        id: userData.id.toString(),
                        type: userData.type,
                        name: $item.find('.user-name').text().trim(),
                        code: $item.find('.user-code').text().trim()
                    });
                }
            } catch (e) {
                console.error('Error parsing user data:', e);
            }
        });
        
        const jsonData = JSON.stringify(selectedUsers);
        $('#CalssUsers').val(jsonData);
        updateSelectionCounts();
    }
    
    // Handle checkbox changes
    $(document).on('change', 'input.user-checkbox', function() {
        const $item = $(this).closest('.user-item');
        if ($(this).is(':checked')) {
            $item.addClass('selected');
        } else {
            $item.removeClass('selected');
        }
        updateUsersHiddenInput();
    });
    
    // Toggle selection on item click
    $(document).on('click', '.user-item', function(e) {
        if (!$(e.target).is('input') && !$(e.target).is('label') && !$(e.target).is('i')) {
            const $checkbox = $(this).find('input.user-checkbox');
            $checkbox.prop('checked', !$checkbox.prop('checked')).trigger('change');
        }
    });
    
    // Handle form submission
    $('#class-form').on('submit', function(e) {
        // Only process if it's the main submit button
        const submitter = $(e.originalEvent?.submitter);
        if (submitter.length && (submitter.attr('name') === 'search_teen' || submitter.attr('name') === 'search_adult' || submitter.attr('name') === 'clear_teen_search' || submitter.attr('name') === 'clear_adult_search')) {
            return true; // Allow search form to submit
        }
        
        // Validate required fields
        const className = $('input[name="ClassName"]').val().trim();
        if (!className) {
            e.preventDefault();
            alert('لطفا نام دوره را وارد کنید.');
            $('input[name="ClassName"]').focus();
            return false;
        }
        
        updateUsersHiddenInput();
        return true;
    });
    
    // Initialize on page load
    updateSelectionCounts();
});
</script>
</body>
</html>