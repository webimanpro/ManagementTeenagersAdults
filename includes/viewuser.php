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
$users = null;
$message = '';
$messageType = '';

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
        .bank-info-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .bank-info-section .info-label {
            color: white;
        }
        .bank-info-section .info-value {
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.3);
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>

<div class="container py-4">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($users)): ?>
    <div class="profile-container">
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <img src="<?php echo !empty($users['UserImage']) ? '../' . $users['UserImage'] : '../assets/img/avatarprofile.png'; ?>" 
                         class="profile-image rounded-circle" 
                         alt="تصویر پروفایل">
                </div>
                <div class="col-md-9">
                    <h2 class="mb-2"><?php echo htmlspecialchars($users['UserName'] . ' ' . $users['UserFamily']); ?></h2>
                    <p class="text-muted mb-1">کدسیستمی: <?php echo htmlspecialchars($users['UserSysCode']); ?></p>
                    <p class="text-muted mb-1">کد ملی: <?php echo htmlspecialchars($users['UserMelli']); ?></p>
                    <?php
                    $statusClass = '';
                    if ($users['UserStatus'] === 'عادی') {
                        $statusClass = 'bg-success';
                    } elseif ($users['UserStatus'] === 'تعلیق') {
                        $statusClass = 'bg-warning';
                    } elseif ($users['UserStatus'] === 'فعال') {
                        $statusClass = 'bg-danger';
                    }
                    ?>
                    <span class="status-badge text-white <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($users['UserStatus']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h4 class="mb-3 text-primary">اطلاعات شخصی</h4>
                
                <div class="info-item">
                    <div class="info-label">نام پدر</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserFather']); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">تاریخ تولد</div>
                    <div class="info-value">
                        <?php echo !empty($users['UserDateBirth']) ? to_persian_date($users['UserDateBirth']) : 'ثبت نشده'; ?>
                    </div>
                </div>

                <div class="info-item">
                    <div class="info-label">محل تولد</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserPlaceBirth'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">محل صدور</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserPlaceCerti'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">گروه خونی</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserBloodType'] ?? 'ثبت نشده'); ?></div>
                </div>
            </div>

            <div class="col-md-6">
                <h4 class="mb-3 text-primary">اطلاعات تماس و تحصیلی</h4>

                <div class="info-item">
                    <div class="info-label">موبایل 1</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserMobile1'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">موبایل 2</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserMobile2'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">تحصیلات</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserEducation'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">شهر</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserCity'] ?? 'ثبت نشده'); ?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">کد پستی</div>
                    <div class="info-value"><?php echo htmlspecialchars($users['UserZipCode'] ?? 'ثبت نشده'); ?></div>
                </div>
            </div>
        </div>

        <!-- اطلاعات تکمیلی -->
        <div class="col-12 mt-4">
            <h5 class="border-bottom pb-2 mb-3">اطلاعات تکمیلی</h5>
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="info-item">
                        <span class="info-label">شماره شناسنامه:</span>
                        <span class="info-value"><?php echo htmlspecialchars($users['UserNumbersh'] ?? '-'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="info-item">
                        <span class="info-label">وضعیت تاهل:</span>
                        <span class="info-value"><?php echo htmlspecialchars($users['UserMaritalStatus'] ?? '-'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="info-item">
                        <span class="info-label">وضعیت خدمت وظیفه:</span>
                        <span class="info-value"><?php echo htmlspecialchars($users['UserDutyStatus'] ?? '-'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="info-item">
                        <span class="info-label">شغل:</span>
                        <span class="info-value"><?php echo htmlspecialchars($users['UserJobWork'] ?? '-'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="info-item">
                        <span class="info-label">تلفن ثابت:</span>
                        <span class="info-value"><?php echo htmlspecialchars($users['UserPhone'] ?? '-'); ?></span>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="info-item">
                        <span class="info-label">ایمیل:</span>
                        <span class="info-value"><?php echo !empty($users['UserEmail']) ? htmlspecialchars($users['UserEmail']) : '-'; ?></span>
                    </div>
                </div>
                
                <?php if (!empty($users['UserOtherActivity'])): ?>
                <div class="col-12">
                    <div class="info-item">
                        <span class="info-label">فعالیت‌های دیگر:</span>
                        <span class="info-value"><?php echo nl2br(htmlspecialchars($users['UserOtherActivity'])); ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- بخش اطلاعات بانکی -->
        <div class="col-12 mt-4">
            <h5 class="border-bottom pb-2 mb-3">اطلاعات بانکی</h5>
            <div class="row">
                <div class="col-md-3">
                    <div class="info-item">
                        <div class="info-label">نام بانک</div>
                        <div class="info-value"><?php echo htmlspecialchars($users['UserBankName'] ?? 'ثبت نشده'); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <div class="info-label">شماره حساب</div>
                        <div class="info-value"><?php echo htmlspecialchars($users['UserAccountNumber'] ?? 'ثبت نشده'); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <div class="info-label">شماره کارت</div>
                        <div class="info-value"><?php echo htmlspecialchars($users['UserCardNumber'] ?? 'ثبت نشده'); ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="info-item">
                        <div class="info-label">شماره شبا</div>
                        <div class="info-value"><?php echo htmlspecialchars($users['UserShebaNumber'] ?? 'ثبت نشده'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3 text-primary">آدرس</h4>
                <div class="info-item">
                    <div class="info-value" style="border-bottom: none; padding: 15px; background-color: #fff; border-radius: 5px; min-height: 80px;">
                        <?php echo !empty($users['UserAddress']) ? nl2br(htmlspecialchars($users['UserAddress'])) : 'آدرس ثبت نشده است.'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <h4 class="mb-3 text-primary">اطلاعات سیستمی</h4>
                <div class="row">
                    <div class="col-md-3">
                        <div class="info-item">
                            <div class="info-label">تاریخ ثبت عادی</div>
                            <div class="info-value">
                                <?php echo !empty($users['UserRegDate']) ? to_persian_date($users['UserRegDate']) : 'ثبت نشده'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <div class="info-label">تاریخ ثبت فعال</div>
                            <div class="info-value">
                                <?php echo !empty($users['UserActiveDate']) ? to_persian_date($users['UserActiveDate']) : 'ثبت نشده'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <div class="info-label">تاریخ ثبت تعلیق</div>
                            <div class="info-value">
                                <?php echo !empty($users['UserSuspendDate']) ? to_persian_date($users['UserSuspendDate']) : 'ثبت نشده'; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="info-item">
                            <div class="info-label">تاریخ ایجاد</div>
                            <div class="info-value">
                                <?php echo to_persian_date($users['UserCreated']); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">آخرین بروزرسانی</div>
                            <div class="info-value">
                                <?php echo to_persian_date($users['UserUpdated']); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="edituser.php?UserID=<?php echo $users['UserID']; ?>" class="btn btn-primary px-4">
                    <i class="bi bi-pencil"></i> ویرایش اطلاعات
                </a>
                <a href="listuser.php" class="btn btn-outline-secondary me-2">
                    <i class="bi bi-arrow-right"></i> بازگشت به لیست
                </a>
                <a href="regsiteruser.php" class="btn btn-success me-2">
                    <i class="bi bi-person-plus"></i> ثبت نام کاربر
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/footer.php'; ?>

<script src="../assets/js/jquery-3.7.1.min.js"></script>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>