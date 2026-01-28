<?php
// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../config/database.php';

// Ensure Class table exists with correct structure
$conn->query("CREATE TABLE IF NOT EXISTS `class` (
  `ClassID` int(11) NOT NULL AUTO_INCREMENT,
  `ClassName` varchar(100) NOT NULL COMMENT 'نام دوره',
  `ClassDateStart` date DEFAULT NULL COMMENT 'تاریخ شروع دوره',
  `ClassDateEnd` date DEFAULT NULL COMMENT 'تاریخ پایان دوره',
  `ClassTime` varchar(20) DEFAULT NULL COMMENT 'ساعت دوره',
  `ClassTeacher` varchar(100) DEFAULT NULL COMMENT 'مربی دوره',
  `ClassPlace` varchar(100) DEFAULT NULL COMMENT 'مکان دوره',
  `ClassDescription` text DEFAULT NULL COMMENT 'توضیحات دوره',
  `CalssUsers` text DEFAULT NULL COMMENT 'کاربران دوره',
  PRIMARY KEY (`ClassID`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

$errors = [];
$success = '';

// Handle search functionality
$searchQuery = $_POST['search_query'] ?? '';
$searchResults = [];

// Clear search if needed
if (isset($_POST['clear_search'])) {
    $searchQuery = '';
    $searchResults = [];
}

// Handle clear form
if (isset($_POST['clear_form'])) {
    unset($_SESSION['form_data']);
    $searchQuery = '';
    $searchResults = [];
    // ریدایرکت به صفحه خالی
    header('Location: class.php');
    exit();
}

// Store form data in session to preserve it during search
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['search_users']) || isset($_POST['clear_search'])) {
        // فقط برای جستجو، داده‌های فرم را در سشن ذخیره کنیم
        $_SESSION['form_data'] = [
            'ClassName' => $_POST['ClassName'] ?? '',
            'ClassTime' => $_POST['ClassTime'] ?? '',
            'ClassTeacher' => $_POST['ClassTeacher'] ?? '',
            'ClassDateStart' => $_POST['ClassDateStart'] ?? '',
            'ClassDateEnd' => $_POST['ClassDateEnd'] ?? '',
            'ClassPlace' => $_POST['ClassPlace'] ?? '',
            'ClassDescription' => $_POST['ClassDescription'] ?? '',
            'CalssUsers' => $_POST['CalssUsers'] ?? '[]'
        ];
    }
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
    $stmt = $conn->prepare("SELECT * FROM `class` WHERE ClassID = ?");
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['search_users']) && !isset($_POST['clear_search']) && !isset($_POST['clear_form'])) {
    
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

    // اعتبارسنجی فرمت تاریخ میلادی (yyyy-mm-dd)
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
            $stmt = $conn->prepare("UPDATE `class` SET ClassName=?, ClassDateStart=?, ClassDateEnd=?, ClassTime=?, ClassTeacher=?, ClassPlace=?, ClassDescription=?, CalssUsers=? WHERE ClassID=?");
            if ($stmt) {
                $stmt->bind_param('ssssssssi', $ClassName, $ClassDateStart, $ClassDateEnd, $ClassTime, $ClassTeacher, $ClassPlace, $ClassDescription, $CalssUsers, $editId);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = 'دوره با موفقیت به‌روزرسانی شد.';
                    // پاک کردن داده‌های فرم از سشن
                    unset($_SESSION['form_data']);
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
            // Insert new class
            $stmt = $conn->prepare("INSERT INTO `class` (ClassName, ClassDateStart, ClassDateEnd, ClassTime, ClassTeacher, ClassPlace, ClassDescription, CalssUsers) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param('ssssssss', $ClassName, $ClassDateStart, $ClassDateEnd, $ClassTime, $ClassTeacher, $ClassPlace, $ClassDescription, $CalssUsers);
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = 'دوره جدید با موفقیت ثبت شد.';
                    // پاک کردن داده‌های فرم از سشن
                    unset($_SESSION['form_data']);
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

// بارگذاری کاربران انتخاب شده
$selectedUsers = [];
if ($editMode) {
    // در حالت ویرایش، از دیتابیس بخوانیم
    if (!empty($classData['CalssUsers']) && $classData['CalssUsers'] !== '[]') {
        $decoded = json_decode($classData['CalssUsers'], true);
        if (is_array($decoded)) {
            $selectedUsers = $decoded;
        }
    }
} elseif (isset($_SESSION['form_data']['CalssUsers'])) {
    // در حالت ایجاد جدید، از سشن بخوانیم
    $decoded = json_decode($_SESSION['form_data']['CalssUsers'], true);
    if (is_array($decoded)) {
        $selectedUsers = $decoded;
    }
} elseif (!empty($classData['CalssUsers']) && $classData['CalssUsers'] !== '[]') {
    // حالت عادی
    $decoded = json_decode($classData['CalssUsers'], true);
    if (is_array($decoded)) {
        $selectedUsers = $decoded;
    }
}

// Create array for quick lookup
$selectedUserIds = [];
foreach ($selectedUsers as $user) {
    if (isset($user['id'])) {
        $selectedUserIds[$user['id']] = $user;
    }
}

// Handle user search
$searchedUsers = [];
if (isset($_POST['search_users']) && !empty($searchQuery)) {
    $searchWhere = "1=1";
    if (!empty($searchQuery)) {
        $searchWhere .= " AND (UserSysCode LIKE '%" . $conn->real_escape_string($searchQuery) . "%' 
                        OR UserMelli LIKE '%" . $conn->real_escape_string($searchQuery) . "%'
                        OR UserName LIKE '%" . $conn->real_escape_string($searchQuery) . "%'
                        OR UserFamily LIKE '%" . $conn->real_escape_string($searchQuery) . "%'
                        OR UserMobile1 LIKE '%" . $conn->real_escape_string($searchQuery) . "%'
                        OR UserMobile2 LIKE '%" . $conn->real_escape_string($searchQuery) . "%')";
    }
    
    $searchQuerySQL = "SELECT UserID, UserSysCode, UserMelli, UserName, UserFamily, UserMobile1, UserMobile2
               FROM users 
               WHERE {$searchWhere} 
               ORDER BY CAST(UserSysCode AS UNSIGNED)";
    $searchResult = $conn->query($searchQuerySQL);
    if ($searchResult && $searchResult->num_rows > 0) {
        while($user = $searchResult->fetch_assoc()) {
            $searchedUsers[] = $user;
        }
    }
}

// Get all classes
$classes = $conn->query("SELECT * FROM `class` ORDER BY ClassID DESC");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مدیریت دوره - سیستم مدیریت</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        .user-list-container {
            max-height: 500px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
        }
        .user-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1px;
            background: #f8f9fa;
        }
        .user-item {
            padding: 12px 15px;
            background: white;
            cursor: pointer;
            transition: all 0.2s ease;
            border-bottom: 1px solid #f8f9fa;
        }
        .user-item:hover {
            background-color: #f8f9fa;
        }
        .user-item.selected {
            background-color: #e7f3ff;
            border-right: 3px solid #0d6efd;
        }
        .user-checkbox {
            margin-left: 10px;
        }
        .user-info {
            flex: 1;
        }
        .user-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        .user-details {
            font-size: 0.8rem;
            color: #6c757d;
        }
        .user-code {
            background: #f8f9fa;
            padding: 1px 6px;
            border-radius: 3px;
            font-family: monospace;
            font-size: 0.75rem;
        }
        .selection-info {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            border-radius: 8px;
        }
        .selected-users-container {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 15px;
            margin-bottom: 20px;
        }
        .selected-user-item {
            background: white;
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .remove-user-btn {
            color: #dc3545;
            cursor: pointer;
            font-size: 1.2rem;
        }
        .class-management-page .content-box {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        .date-input {
            direction: ltr;
            text-align: left;
        }
        @media (max-width: 768px) {
            .user-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="class-management-page">
<?php include __DIR__ . '/header.php'; ?>

<div class="container main-container" style="margin-top: 100px; margin-bottom: 40px;">
    <div class="content-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center">
                <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center me-3">
                    <i class="bi bi-arrow-right me-2"></i>
                    <span>بستن</span>
                </a>
                <h2 class="mb-0">
                    <i class="bi bi-calendar-check me-2"></i>مدیریت دوره‌ها
                </h2>
            </div>
            <?php if ($editMode): ?>
                <span class="badge bg-warning text-dark">
                    <i class="bi bi-pencil me-1"></i>حالت ویرایش
                </span>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill me-2"></i> 
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> 
                <?php foreach ($errors as $error): ?>
                    <?php echo $error; ?><br>
                <?php endforeach; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="post" class="mb-4" id="class-form">
            <?php if ($editMode): ?>
                <input type="hidden" name="edit_id" value="<?php echo $editId; ?>">
            <?php endif; ?>
            
            <!-- بخش اطلاعات اصلی دوره -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">نام دوره <span class="text-danger">*</span></label>
                    <input type="text" name="ClassName" class="form-control" 
                           value="<?php 
                                if ($editMode) {
                                    echo htmlspecialchars($classData['ClassName'] ?? '');
                                } elseif (isset($_SESSION['form_data']['ClassName'])) {
                                    echo htmlspecialchars($_SESSION['form_data']['ClassName']);
                                } else {
                                    echo htmlspecialchars($classData['ClassName'] ?? '');
                                }
                           ?>" 
                           placeholder="نام دوره را وارد کنید" required>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">ساعت دوره</label>
                    <input type="text" name="ClassTime" class="form-control" placeholder="مثال: 16:00-18:00" 
                           value="<?php 
                                if ($editMode) {
                                    echo htmlspecialchars($classData['ClassTime'] ?? '');
                                } elseif (isset($_SESSION['form_data']['ClassTime'])) {
                                    echo htmlspecialchars($_SESSION['form_data']['ClassTime']);
                                } else {
                                    echo htmlspecialchars($classData['ClassTime'] ?? '');
                                }
                           ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">مربی دوره</label>
                    <input type="text" name="ClassTeacher" class="form-control" 
                           value="<?php 
                                if ($editMode) {
                                    echo htmlspecialchars($classData['ClassTeacher'] ?? '');
                                } elseif (isset($_SESSION['form_data']['ClassTeacher'])) {
                                    echo htmlspecialchars($_SESSION['form_data']['ClassTeacher']);
                                } else {
                                    echo htmlspecialchars($classData['ClassTeacher'] ?? '');
                                }
                           ?>">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">تاریخ شروع <small class="text-muted">(YYYY-MM-DD)</small></label>
                    <input type="text" name="ClassDateStart" class="form-control date-input" 
                           value="<?php 
                                if ($editMode) {
                                    echo !empty($classData['ClassDateStart']) && $classData['ClassDateStart'] != '0000-00-00' ? $classData['ClassDateStart'] : '';
                                } elseif (isset($_SESSION['form_data']['ClassDateStart'])) {
                                    echo htmlspecialchars($_SESSION['form_data']['ClassDateStart']);
                                } else {
                                    echo !empty($classData['ClassDateStart']) && $classData['ClassDateStart'] != '0000-00-00' ? $classData['ClassDateStart'] : '';
                                }
                           ?>"
                           placeholder="YYYY-MM-DD">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">تاریخ پایان <small class="text-muted">(YYYY-MM-DD)</small></label>
                    <input type="text" name="ClassDateEnd" class="form-control date-input" 
                           value="<?php 
                                if ($editMode) {
                                    echo !empty($classData['ClassDateEnd']) && $classData['ClassDateEnd'] != '0000-00-00' ? $classData['ClassDateEnd'] : '';
                                } elseif (isset($_SESSION['form_data']['ClassDateEnd'])) {
                                    echo htmlspecialchars($_SESSION['form_data']['ClassDateEnd']);
                                } else {
                                    echo !empty($classData['ClassDateEnd']) && $classData['ClassDateEnd'] != '0000-00-00' ? $classData['ClassDateEnd'] : '';
                                }
                           ?>"
                           placeholder="YYYY-MM-DD">
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">مکان دوره</label>
                    <input type="text" name="ClassPlace" class="form-control" 
                           value="<?php 
                                if ($editMode) {
                                    echo htmlspecialchars($classData['ClassPlace'] ?? '');
                                } elseif (isset($_SESSION['form_data']['ClassPlace'])) {
                                    echo htmlspecialchars($_SESSION['form_data']['ClassPlace']);
                                } else {
                                    echo htmlspecialchars($classData['ClassPlace'] ?? '');
                                }
                           ?>">
                </div>
                
                <div class="col-12">
                    <label class="form-label">توضیحات دوره</label>
                    <textarea name="ClassDescription" class="form-control" rows="2" placeholder="توضیحات مربوط به دوره..."><?php 
                        if ($editMode) {
                            echo htmlspecialchars($classData['ClassDescription'] ?? '');
                        } elseif (isset($_SESSION['form_data']['ClassDescription'])) {
                            echo htmlspecialchars($_SESSION['form_data']['ClassDescription']);
                        } else {
                            echo htmlspecialchars($classData['ClassDescription'] ?? '');
                        }
                    ?></textarea>
                </div>
            </div>

            <!-- بخش انتخاب کاربران -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">
                            <i class="bi bi-people-fill me-2"></i>انتخاب کاربران دوره
                        </h5>
                        <div class="selection-info px-3 py-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <span class="fw-bold">کاربران انتخاب شده: </span>
                            <span id="selected-count" class="badge bg-primary"><?php echo count($selectedUsers); ?></span>
                        </div>
                    </div>

                    <!-- نمایش کاربران انتخاب شده -->
                    <?php if (!empty($selectedUsers)): ?>
                        <div class="selected-users-container mb-4">
                            <h6 class="mb-3">
                                <i class="bi bi-person-check me-2"></i>کاربران انتخاب شده
                            </h6>
                            <div id="selected-users-list">
                                <?php foreach ($selectedUsers as $user): ?>
                                    <?php if (isset($user['id']) && isset($user['name'])): ?>
                                        <div class="selected-user-item" data-user-id="<?php echo $user['id']; ?>">
                                            <span><?php echo htmlspecialchars($user['name']); ?> (کد: <?php echo htmlspecialchars($user['code'] ?? ''); ?>)</span>
                                            <i class="bi bi-x-circle remove-user-btn" onclick="removeSelectedUser('<?php echo $user['id']; ?>')"></i>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- جستجوی کاربران -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row g-2 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label">جستجوی کاربران</label>
                                    <input type="text" 
                                           name="search_query" 
                                           class="form-control" 
                                           placeholder="جستجو با نام، نام خانوادگی، کدسیستمی، کدملی یا موبایل..."
                                           value="<?php echo htmlspecialchars($searchQuery); ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary w-50" name="search_users">
                                            <i class="bi bi-search me-1"></i>جستجو
                                        </button>
                                        <?php if (!empty($searchQuery)): ?>
                                            <button type="submit" class="btn btn-secondary w-50" name="clear_search" title="پاک کردن جستجو">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- لیست کاربران حاصل از جستجو -->
                    <?php if (!empty($searchedUsers) || !empty($selectedUsers)): ?>
                        <div class="card">
                            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="bi bi-search me-2"></i>
                                    نتایج جستجو و کاربران انتخاب شده
                                    <span class="badge bg-light text-dark ms-2">
                                        <?php 
                                            $totalUsers = count($searchedUsers);
                                            $selectedCount = count($selectedUserIds);
                                            echo $totalUsers . ' کاربر یافت شد' . ($selectedCount > 0 ? ' (' . $selectedCount . ' انتخاب شده)' : '');
                                        ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body p-0">
                                <div class="user-list-container">
                                    <div class="user-list">
                                        <?php 
                                        // نمایش کاربران جستجو شده
                                        foreach ($searchedUsers as $user): 
                                            $isSelected = isset($selectedUserIds[$user['UserID']]);
                                        ?>
                                        <div class="user-item <?php echo $isSelected ? 'selected' : ''; ?>" 
                                             data-user-id="<?php echo $user['UserID']; ?>">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input user-checkbox" 
                                                       type="checkbox" 
                                                       value='<?php echo json_encode([
                                                           'id' => $user['UserID'],
                                                           'code' => $user['UserSysCode'],
                                                           'name' => $user['UserName'] . ' ' . $user['UserFamily']
                                                       ]); ?>'
                                                       id="user_<?php echo $user['UserID']; ?>"
                                                       <?php echo $isSelected ? 'checked' : ''; ?>>
                                                <div class="user-info ms-3">
                                                    <div class="user-name">
                                                        <?php echo htmlspecialchars($user['UserName'] . ' ' . $user['UserFamily']); ?>
                                                        <?php if ($isSelected): ?>
                                                            <span class="badge bg-success ms-2">انتخاب شده</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="user-details">
                                                        <span class="me-3">
                                                            کدسیستمی: <span class="user-code"><?php echo htmlspecialchars($user['UserSysCode']); ?></span>
                                                        </span>
                                                        <span class="me-3">
                                                            کدملی: <span class="user-code"><?php echo htmlspecialchars($user['UserMelli']); ?></span>
                                                        </span>
                                                        <?php if ($user['UserMobile1']): ?>
                                                            <span class="me-3">
                                                                موبایل: <span class="user-code"><?php echo htmlspecialchars($user['UserMobile1']); ?></span>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <?php 
                                        // نمایش کاربران انتخاب شده که ممکن است در نتایج جستجو نباشند
                                        foreach ($selectedUserIds as $userId => $selectedUser):
                                            // بررسی آیا کاربر در نتایج جستجو هست یا نه
                                            $foundInSearch = false;
                                            foreach ($searchedUsers as $searchedUser) {
                                                if ($searchedUser['UserID'] == $userId) {
                                                    $foundInSearch = true;
                                                    break;
                                                }
                                            }
                                            
                                            if (!$foundInSearch && isset($selectedUser['id'])): 
                                        ?>
                                        <div class="user-item selected" data-user-id="<?php echo $selectedUser['id']; ?>">
                                            <div class="d-flex align-items-center">
                                                <input class="form-check-input user-checkbox" 
                                                       type="checkbox" 
                                                       value='<?php echo json_encode([
                                                           'id' => $selectedUser['id'],
                                                           'code' => $selectedUser['code'],
                                                           'name' => $selectedUser['name']
                                                       ]); ?>'
                                                       id="user_<?php echo $selectedUser['id']; ?>"
                                                       checked>
                                                <div class="user-info ms-3">
                                                    <div class="user-name">
                                                        <?php echo htmlspecialchars($selectedUser['name']); ?>
                                                        <span class="badge bg-success ms-2">انتخاب شده</span>
                                                    </div>
                                                    <div class="user-details">
                                                        <span class="me-3">
                                                            کدسیستمی: <span class="user-code"><?php echo htmlspecialchars($selectedUser['code']); ?></span>
                                                        </span>
                                                        <span class="me-3 text-muted">
                                                            (این کاربر در نتایج جستجوی فعلی نیست)
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                        
                                        <?php if (empty($searchedUsers) && empty($selectedUserIds)): ?>
                                            <div class="p-4 text-center text-muted w-100">
                                                <?php if (!empty($searchQuery)): ?>
                                                    <i class="bi bi-search display-4 d-block mb-3"></i>
                                                    <h5>هیچ کاربری یافت نشد</h5>
                                                    <p class="mb-0">لطفا عبارت جستجو را تغییر دهید</p>
                                                <?php else: ?>
                                                    <i class="bi bi-people display-4 d-block mb-3"></i>
                                                    <h5>کاربری یافت نشد</h5>
                                                    <p class="mb-0">برای جستجوی کاربران، عبارتی در کادر جستجو وارد کنید</p>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php elseif (!empty($searchQuery)): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            هیچ کاربری با عبارت "<?php echo htmlspecialchars($searchQuery); ?>" یافت نشد.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <input type="hidden" name="CalssUsers" id="CalssUsers" value="<?php 
                if ($editMode) {
                    echo htmlspecialchars($classData['CalssUsers'] ?? '[]');
                } elseif (isset($_SESSION['form_data']['CalssUsers'])) {
                    echo htmlspecialchars($_SESSION['form_data']['CalssUsers']);
                } else {
                    echo htmlspecialchars($classData['CalssUsers'] ?? '[]');
                }
            ?>">
            
            <!-- دکمه‌های عملیات -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" name="submit_class">
                            <i class="bi <?php echo $editMode ? 'bi-check-circle' : 'bi-plus-circle'; ?> me-2"></i>
                            <?php echo $editMode ? 'ذخیره تغییرات' : 'ایجاد دوره جدید'; ?>
                        </button>
                        <?php if ($editMode): ?>
                            <a href="class.php" class="btn btn-secondary btn-lg">
                                <i class="bi bi-x-circle me-2"></i>انصراف
                            </a>
                        <?php else: ?>
                            <button type="submit" class="btn btn-warning btn-lg" name="clear_form" onclick="return confirm('آیا مطمئن هستید که می‌خواهید همه اطلاعات فرم را پاک کنید؟')">
                                <i class="bi bi-eraser me-2"></i>حذف اطلاعات فرم
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>

        <!-- جدول نمایش دوره‌ها -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">
                        <i class="bi bi-list-task me-2"></i>لیست دوره‌ها
                    </h4>
                    <span class="badge bg-primary fs-6">
                        <?php echo $classes ? $classes->num_rows : 0; ?> دوره
                    </span>
                </div>
                
                <?php if ($classes && $classes->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered table-striped">
                            <thead class="table">
                                <tr>
                                    <th width="60" class="text-center">#</th>
                                    <th>نام دوره</th>
                                    <th width="150">مربی</th>
                                    <th width="120" class="text-center">تاریخ شروع</th>
                                    <th width="120" class="text-center">تاریخ پایان</th>
                                    <th width="100" class="text-center">ساعت</th>
                                    <th width="100" class="text-center">کاربران</th>
                                    <th width="90" class="text-center">عملیات</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php 
                            $classes->data_seek(0);
                            while($row = $classes->fetch_assoc()): 
                                $usersCount = 0;
                                if (!empty($row['CalssUsers']) && $row['CalssUsers'] !== '[]') {
                                    $users = json_decode($row['CalssUsers'], true);
                                    if (is_array($users)) {
                                        $usersCount = count($users);
                                    }
                                }
                            ?>
                                <tr>
                                    <td class="text-center fw-bold"><?php echo $row['ClassID']; ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($row['ClassName']); ?></div>
                                        <?php if (!empty($row['ClassPlace'])): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['ClassPlace']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['ClassTeacher'])): ?>
                                            <span class="text-dark"><?php echo htmlspecialchars($row['ClassTeacher']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['ClassDateStart']) && $row['ClassDateStart'] != '0000-00-00'): ?>
                                            <span class="badge bg-light text-dark border"><?php echo $row['ClassDateStart']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['ClassDateEnd']) && $row['ClassDateEnd'] != '0000-00-00'): ?>
                                            <span class="badge bg-light text-dark border"><?php echo $row['ClassDateEnd']; ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($row['ClassTime'])): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($row['ClassTime']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?php echo $usersCount > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?php echo $usersCount; ?> نفر
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="?edit=<?php echo $row['ClassID']; ?>" class="btn btn-warning btn-sm" title="ویرایش دوره">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="rollcalluser.php?class_id=<?php echo $row['ClassID']; ?>" class="btn btn-info btn-sm" title="حضور و غیاب">
                                            <i class="bi bi-clipboard-check"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-calendar-x display-1 d-block mb-3"></i>
                        <h4 class="mb-3">دوره‌ای ثبت نشده است</h4>
                        <p class="mb-4">برای ایجاد دوره جدید از فرم بالا استفاده کنید</p>
                        <a href="class.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle me-2"></i>ایجاد اولین دوره
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // تابع بروزرسانی تعداد کاربران انتخاب شده
    function updateSelectionCounts() {
        let selectedUsers = [];
        try {
            const calssUsers = $('#CalssUsers').val();
            selectedUsers = JSON.parse(calssUsers);
        } catch(e) {
            selectedUsers = [];
        }
        $('#selected-count').text(selectedUsers.length);
    }
    
    // تابع بروزرسانی لیست کاربران انتخاب شده در نمایش
    function updateSelectedUsersList() {
        let selectedUsers = [];
        try {
            const calssUsers = $('#CalssUsers').val();
            selectedUsers = JSON.parse(calssUsers);
        } catch(e) {
            selectedUsers = [];
        }
        
        const $selectedList = $('#selected-users-list');
        $selectedList.empty();
        
        if (selectedUsers.length === 0) {
            if ($selectedList.closest('.selected-users-container').length) {
                $selectedList.closest('.selected-users-container').hide();
            }
        } else {
            if ($selectedList.closest('.selected-users-container').length) {
                $selectedList.closest('.selected-users-container').show();
            }
            selectedUsers.forEach(function(user) {
                const userHtml = `
                    <div class="selected-user-item" data-user-id="${user.id}">
                        <span>${user.name} (کد: ${user.code})</span>
                        <i class="bi bi-x-circle remove-user-btn" onclick="removeSelectedUser('${user.id}')"></i>
                    </div>
                `;
                $selectedList.append(userHtml);
            });
        }
        
        updateSelectionCounts();
    }
    
    // تابع حذف کاربر از لیست انتخاب‌شده‌ها
    window.removeSelectedUser = function(userId) {
        let selectedUsers = [];
        try {
            const calssUsers = $('#CalssUsers').val();
            selectedUsers = JSON.parse(calssUsers);
        } catch(e) {
            selectedUsers = [];
        }
        
        // حذف کاربر با شناسه موردنظر
        selectedUsers = selectedUsers.filter(function(user) {
            return user.id !== userId;
        });
        
        // آپدیت فیلد مخفی
        $('#CalssUsers').val(JSON.stringify(selectedUsers));
        
        // آپدیت چک‌باکس‌ها
        $(`input.user-checkbox[value*='"id":"${userId}"']`).prop('checked', false);
        $(`.user-item[data-user-id="${userId}"]`).removeClass('selected');
        $(`.user-item[data-user-id="${userId}"] .badge.bg-success`).remove();
        
        updateSelectedUsersList();
    };
    
    // تابع افزودن کاربر به لیست انتخاب‌شده‌ها
    function addSelectedUser(userData) {
        let selectedUsers = [];
        try {
            const calssUsers = $('#CalssUsers').val();
            selectedUsers = JSON.parse(calssUsers);
        } catch(e) {
            selectedUsers = [];
        }
        
        // بررسی اینکه آیا کاربر قبلاً انتخاب شده یا نه
        const existingIndex = selectedUsers.findIndex(function(user) {
            return user.id === userData.id;
        });
        
        if (existingIndex === -1) {
            selectedUsers.push({
                id: userData.id.toString(),
                code: userData.code || '',
                name: userData.name || '',
                type: 'teen'
            });
            
            // آپدیت فیلد مخفی
            $('#CalssUsers').val(JSON.stringify(selectedUsers));
            
            // آپدیت نمایش
            $(`.user-item[data-user-id="${userData.id}"]`).addClass('selected');
            if (!$(`.user-item[data-user-id="${userData.id}"] .badge.bg-success`).length) {
                $(`.user-item[data-user-id="${userData.id}"] .user-name`).append('<span class="badge bg-success ms-2">انتخاب شده</span>');
            }
            
            updateSelectedUsersList();
            return true;
        }
        return false;
    }
    
    // Handle checkbox changes
    $(document).on('change', 'input.user-checkbox', function() {
        if ($(this).is(':checked')) {
            try {
                const userData = JSON.parse($(this).val());
                addSelectedUser(userData);
            } catch(e) {
                console.error('Error parsing user data:', e);
            }
        } else {
            // اگر تیک برداشته شد
            try {
                const userData = JSON.parse($(this).val());
                window.removeSelectedUser(userData.id);
            } catch(e) {
                console.error('Error parsing user data:', e);
            }
        }
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
        if (submitter.length && (submitter.attr('name') === 'search_users' || submitter.attr('name') === 'clear_search' || submitter.attr('name') === 'clear_form')) {
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
        
        // Validate date format
        const dateStart = $('input[name="ClassDateStart"]').val().trim();
        const dateEnd = $('input[name="ClassDateEnd"]').val().trim();
        
        const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
        
        if (dateStart && !dateRegex.test(dateStart)) {
            e.preventDefault();
            alert('فرمت تاریخ شروع نامعتبر است. لطفا به صورت YYYY-MM-DD وارد کنید.');
            $('input[name="ClassDateStart"]').focus();
            return false;
        }
        
        if (dateEnd && !dateRegex.test(dateEnd)) {
            e.preventDefault();
            alert('فرمت تاریخ پایان نامعتبر است. لطفا به صورت YYYY-MM-DD وارد کنید.');
            $('input[name="ClassDateEnd"]').focus();
            return false;
        }
        
        return true;
    });
    
    // Initialize on page load
    updateSelectionCounts();
    if ($('#selected-users-list').children().length > 0) {
        $('#selected-users-list').closest('.selected-users-container').show();
    }
    
    // تابع برای فرمت تاریخ هنگام تایپ
    $('input[name="ClassDateStart"], input[name="ClassDateEnd"]').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        
        if (value.length > 4) {
            value = value.substring(0, 4) + '-' + value.substring(4);
        }
        if (value.length > 7) {
            value = value.substring(0, 7) + '-' + value.substring(7, 9);
        }
        
        $(this).val(value);
    });
});
</script>
</body>
</html>