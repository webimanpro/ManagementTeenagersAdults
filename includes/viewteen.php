<?php

// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/jdf.php';

// Initialize variables
$teen = null;
$message = '';
$messageType = '';

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
} else {
    $message = 'کد نوجوان مشخص نشده است.';
    $messageType = 'danger';
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>مشاهده اطلاعات نوجوان</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/font-face.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        body {
            padding-bottom: 60px;
        }
        .profile-container {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .profile-header {
            border-bottom: 2px solid #dee2e6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .profile-image {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border: 3px solid #dee2e6;
        }
        .info-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        .info-value {
            color: #212529;
            margin-bottom: 15px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
include __DIR__ . '/header.php'; ?>

<div class="container py-4">
    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($message): ?>
        <div class="alert alert-<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>

    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (isset($teen)): ?>
    <div class="profile-container">
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo !empty($teen['TeenImage']) ? '../' . $teen['TeenImage'] : '../assets/img/avatarprofile.png'; ?>" 
                         class="profile-image rounded-circle" 
                         alt="تصویر پروفایل">
                </div>
                <div class="col-md-9">
                    <h2 class="mb-2"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenName'] . ' ' . $teen['TeenFamily']); ?></h2>
                    <p class="text-muted mb-1">کدسیستمی: <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenSysCode']); ?></p>
                    <p class="text-muted mb-1">کد ملی: <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenMelli']); ?></p>
                    <?php
                    
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
$statusClass = '';
                    if ($teen['TeenStatus'] === 'عادی') {
                        $statusClass = 'bg-success';
                    } elseif ($teen['TeenStatus'] === 'تعلیق') {
                        $statusClass = 'bg-warning';
                    } elseif ($teen['TeenStatus'] === 'فعال') {
                        $statusClass = 'bg-danger';
                    }
                    ?>
                    <span class="status-badge text-white <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $statusClass; ?>">
                        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenStatus']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h4 class="mb-3 text-primary">اطلاعات شخصی</h4>
                
                <div class="info-item">
                    <div class="info-label">نام پدر</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenFather']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">تاریخ تولد</div>
                    <div class="info-value">
                        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo !empty($teen['TeenDateBirth']) ? to_persian_date($teen['TeenDateBirth']) : 'ثبت نشده'; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">محل تولد</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenPlaceBirth'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">محل صدور</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenPlaceCerti'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">گروه خونی</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenBloodType'] ?? 'ثبت نشده'); ?></div>
                </div>
            </div>

            <div class="col-md-6">
                <h4 class="mb-3 text-primary">اطلاعات تماس و تحصیلی</h4>

                <div class="info-item">
                    <div class="info-label">موبایل 1</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenMobile1'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">موبایل 2</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenMobile2'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">تحصیلات</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenEducation'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">شهر</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenCity'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">کد پستی</div>
                    <div class="info-value"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($teen['TeenZipCode'] ?? 'ثبت نشده'); ?></div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3 text-primary">آدرس</h4>
                <div class="info-item">
                    <div class="info-value" style="border-bottom: none; padding: 15px; background-color: #fff; border-radius: 5px; min-height: 80px;">
                        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo !empty($teen['TeenAddress']) ? nl2br(htmlspecialchars($teen['TeenAddress'])) : 'آدرس ثبت نشده است.'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3 text-primary">اطلاعات سیستمی</h4>
                <div class="row">
                    <div class="col-md-4">
                        <div class="info-item">
                            <div class="info-label">تاریخ ثبت نام</div>
                            <div class="info-value">
                                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo !empty($teen['TeenRegDate']) ? to_persian_date($teen['TeenRegDate']) : 'ثبت نشده'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <div class="info-label">تاریخ ایجاد</div>
                            <div class="info-value">
                                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo to_persian_date($teen['TeenCreated']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-item">
                            <div class="info-label">آخرین بروزرسانی</div>
                            <div class="info-value">
                                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo to_persian_date($teen['TeenUpdated']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="editteen.php?TeenID=<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $teen['TeenID']; ?>" class="btn btn-primary px-4">
                    <i class="bi bi-pencil"></i> ویرایش اطلاعات
                </a>
                <a href="listteen.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-right"></i> بازگشت به لیست
                </a>
                <a href="regteen.php" class="btn btn-success me-2">
                    <i class="bi bi-person-plus"></i> ثبت نوجوان جدید
                </a>
            </div>
        </div>
    </div>
    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>
</div>

<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
include __DIR__ . '/footer.php'; ?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>