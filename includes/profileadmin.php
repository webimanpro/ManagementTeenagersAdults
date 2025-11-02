<?php
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jdf.php';

$errors = [];
$success = '';

// شناسایی کاربر جلسه
$sessionUsername = $_SESSION['user_name'] ?? ($_SESSION['username'] ?? ($_SESSION['login_user'] ?? ''));
$sessionUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// بارگذاری اطلاعات کاربر فعلی
$currentUser = null;
if ($sessionUserId > 0) {
    $stmt = $conn->prepare("SELECT ID, username, full_name, email, phone, role FROM admins WHERE ID = ? LIMIT 1");
    $stmt->bind_param('i', $sessionUserId);
    if ($stmt->execute()) {
        $currentUser = $stmt->get_result()->fetch_assoc();
    }
    $stmt->close();
}

if (!$currentUser && $sessionUsername) {
    $stmt = $conn->prepare("SELECT ID, username, full_name, email, phone, role FROM admins WHERE username = ? LIMIT 1");
    $stmt->bind_param('s', $sessionUsername);
    if ($stmt->execute()) {
        $currentUser = $stmt->get_result()->fetch_assoc();
    }
    $stmt->close();
}

$currentUserRole = $currentUser['role'] ?? 'user';
$currentUserId = $currentUser['ID'] ?? 0;

// پردازش فرم‌ها
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // به‌روزرسانی پروفایل کاربر فعلی
    if (isset($_POST['update_profile'])) {
        $fullName = trim($_POST['FullName'] ?? '');
        $username = trim($_POST['Username'] ?? '');
        $password = trim($_POST['Password'] ?? '');
        $mobile = trim($_POST['Mobile'] ?? '');
        $email = trim($_POST['Email'] ?? '');
        $role = $_POST['Role'] ?? 'user';
        
        // اعتبارسنجی
        if (empty($username)) {
            $errors[] = 'نام کاربری الزامی است.';
        }
        if (empty($fullName)) {
            $errors[] = 'نام کامل الزامی است.';
        }
        if (!in_array($role, ['admin', 'manager', 'user'], true)) {
            $errors[] = 'نقش نامعتبر است.';
        }
        
        if (empty($errors)) {
            if (!empty($password)) {
                // به‌روزرسانی با رمز عبور جدید
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE admins SET username = ?, password = ?, full_name = ?, email = ?, phone = ?, role = ? WHERE ID = ?");
                $stmt->bind_param('ssssssi', $username, $hashedPassword, $fullName, $email, $mobile, $role, $currentUserId);
            } else {
                // به‌روزرسانی بدون تغییر رمز عبور
                $stmt = $conn->prepare("UPDATE admins SET username = ?, full_name = ?, email = ?, phone = ?, role = ? WHERE ID = ?");
                $stmt->bind_param('sssssi', $username, $fullName, $email, $mobile, $role, $currentUserId);
            }
            
            if ($stmt->execute()) {
                $success = 'پروفایل با موفقیت به‌روزرسانی شد.';
                
                // به‌روزرسانی سشن
                $_SESSION['user_name'] = $username;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                
                // بارگذاری مجدد اطلاعات کاربر
                $stmt = $conn->prepare("SELECT ID, username, full_name, email, phone, role FROM admins WHERE ID = ?");
                $stmt->bind_param('i', $currentUserId);
                $stmt->execute();
                $currentUser = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            } else {
                $errors[] = 'خطا در به‌روزرسانی پروفایل: ' . $conn->error;
            }
        }
    }
    
    // ثبت کاربر جدید (فقط برای مدیرکل)
    if (isset($_POST['add_new_user']) && $currentUserRole === 'admin') {
        $newUsername = trim($_POST['NewUsername'] ?? '');
        $newPassword = trim($_POST['NewPassword'] ?? '');
        $newFullName = trim($_POST['NewFullName'] ?? '');
        $newEmail = trim($_POST['NewEmail'] ?? '');
        $newPhone = trim($_POST['NewPhone'] ?? '');
        $newRole = $_POST['NewRole'] ?? 'user';
        
        // اعتبارسنجی
        if (empty($newUsername)) {
            $errors[] = 'نام کاربری الزامی است.';
        }
        if (empty($newPassword)) {
            $errors[] = 'رمز عبور الزامی است.';
        }
        if (empty($newFullName)) {
            $errors[] = 'نام کامل الزامی است.';
        }
        if (!in_array($newRole, ['admin', 'manager', 'user'], true)) {
            $errors[] = 'نقش نامعتبر است.';
        }
        
        // بررسی تکراری نبودن نام کاربری
        if (empty($errors)) {
            $checkStmt = $conn->prepare("SELECT ID FROM admins WHERE username = ?");
            $checkStmt->bind_param('s', $newUsername);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $errors[] = 'این نام کاربری قبلاً ثبت شده است.';
            }
            $checkStmt->close();
        }
        
        if (empty($errors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO admins (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
            $insertStmt->bind_param('ssssss', $newUsername, $hashedPassword, $newFullName, $newEmail, $newPhone, $newRole);
            
            if ($insertStmt->execute()) {
                $success = 'کاربر جدید با موفقیت ثبت شد.';
            } else {
                $errors[] = 'خطا در ثبت کاربر جدید: ' . $conn->error;
            }
            $insertStmt->close();
        }
    }
    
    // ویرایش کاربر (فقط برای مدیرکل)
    if (isset($_POST['edit_user']) && $currentUserRole === 'admin') {
        $editUserId = (int)($_POST['edit_user_id'] ?? 0);
        $editUsername = trim($_POST['EditUsername'] ?? '');
        $editPassword = trim($_POST['EditPassword'] ?? '');
        $editFullName = trim($_POST['EditFullName'] ?? '');
        $editEmail = trim($_POST['EditEmail'] ?? '');
        $editPhone = trim($_POST['EditPhone'] ?? '');
        $editRole = $_POST['EditRole'] ?? 'user';
        
        // اعتبارسنجی
        if (empty($editUsername)) {
            $errors[] = 'نام کاربری الزامی است.';
        }
        if (empty($editFullName)) {
            $errors[] = 'نام کامل الزامی است.';
        }
        if (!in_array($editRole, ['admin', 'manager', 'user'], true)) {
            $errors[] = 'نقش نامعتبر است.';
        }
        if ($editUserId === 0) {
            $errors[] = 'شناسه کاربر نامعتبر است.';
        }
        
        // بررسی تکراری نبودن نام کاربری (به جز خود کاربر)
        if (empty($errors) && $editUserId > 0) {
            $checkStmt = $conn->prepare("SELECT ID FROM admins WHERE username = ? AND ID != ?");
            $checkStmt->bind_param('si', $editUsername, $editUserId);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $errors[] = 'این نام کاربری قبلاً استفاده شده است.';
            }
            $checkStmt->close();
        }
        
        if (empty($errors) && $editUserId > 0) {
            if (!empty($editPassword)) {
                // به‌روزرسانی با رمز عبور جدید
                $hashedPassword = password_hash($editPassword, PASSWORD_DEFAULT);
                $updateStmt = $conn->prepare("UPDATE admins SET username = ?, password = ?, full_name = ?, email = ?, phone = ?, role = ? WHERE ID = ?");
                $updateStmt->bind_param('ssssssi', $editUsername, $hashedPassword, $editFullName, $editEmail, $editPhone, $editRole, $editUserId);
            } else {
                // به‌روزرسانی بدون تغییر رمز عبور
                $updateStmt = $conn->prepare("UPDATE admins SET username = ?, full_name = ?, email = ?, phone = ?, role = ? WHERE ID = ?");
                $updateStmt->bind_param('sssssi', $editUsername, $editFullName, $editEmail, $editPhone, $editRole, $editUserId);
            }
            
            if ($updateStmt->execute()) {
                $success = 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.';
            } else {
                $errors[] = 'خطا در به‌روزرسانی کاربر: ' . $conn->error;
            }
            $updateStmt->close();
        }
    }
}

// حذف کاربر (فقط برای مدیرکل)
if (isset($_GET['delete_user']) && $currentUserRole === 'admin') {
    $deleteId = (int)$_GET['delete_user'];
    if ($deleteId > 0 && $deleteId !== $currentUserId) {
        $deleteStmt = $conn->prepare("DELETE FROM admins WHERE ID = ?");
        $deleteStmt->bind_param('i', $deleteId);
        if ($deleteStmt->execute()) {
            $success = 'کاربر با موفقیت حذف شد.';
            // ریدایرکت برای جلوگیری از ارسال مجدد فرم
            header("Location: profileadmin.php?success=1");
            exit();
        } else {
            $errors[] = 'خطا در حذف کاربر: ' . $conn->error;
        }
        $deleteStmt->close();
    } elseif ($deleteId === $currentUserId) {
        $errors[] = 'شما نمی‌توانید حساب کاربری خود را حذف کنید.';
    }
}

// دریافت لیست تمام کاربران (فقط برای مدیرکل)
$allUsers = [];
if ($currentUserRole === 'admin') {
    $result = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
    if ($result) {
        $allUsers = $result->fetch_all(MYSQLI_ASSOC);
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>پروفایل کاربر - سیستم مدیریت</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        .modal-backdrop {
            z-index: 1040;
        }
        .modal {
            z-index: 1050;
        }
        .table-actions {
            white-space: nowrap;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>

<div class="container" style="margin-top: 100px; margin-bottom: 40px;">
    
    <!-- پیام‌های سیستم -->
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <?php foreach ($errors as $error): ?>
                <?php echo $error; ?><br>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- بخش پروفایل کاربر -->
    <div class="content-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">
                <i class="bi bi-person-circle me-2"></i>پروفایل کاربر
            </h2>
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg me-1"></i>بستن
            </a>
        </div>

        <form method="post">
            <input type="hidden" name="update_profile" value="1">
            
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">نام کامل <span class="text-danger">*</span></label>
                    <input type="text" name="FullName" class="form-control" 
                           value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">نام کاربری <span class="text-danger">*</span></label>
                    <input type="text" name="Username" class="form-control" 
                           value="<?php echo htmlspecialchars($currentUser['username'] ?? $sessionUsername); ?>" required>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">رمز عبور جدید</label>
                    <input type="password" name="Password" class="form-control" 
                           placeholder="در صورت تغییر رمز عبور وارد کنید">
                    <small class="text-muted">در صورت خالی گذاشتن، رمز عبور تغییر نمی‌کند</small>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">موبایل</label>
                    <input type="text" name="Mobile" class="form-control" 
                           value="<?php echo htmlspecialchars($currentUser['phone'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">ایمیل</label>
                    <input type="email" name="Email" class="form-control" 
                           value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>">
                </div>
                
                <div class="col-md-6">
                    <label class="form-label">نقش کاربری</label>
                    <select name="Role" class="form-select">
                        <?php
                        $roles = [
                            'admin' => 'مدیر کل',
                            'manager' => 'مدیر', 
                            'user' => 'کاربر'
                        ];
                        $currentRole = $currentUser['role'] ?? 'user';
                        foreach ($roles as $key => $value): 
                        ?>
                            <option value="<?php echo $key; ?>" <?php echo ($currentRole === $key) ? 'selected' : ''; ?>>
                                <?php echo $value; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-2"></i>ذخیره تغییرات
                </button>
            </div>
        </form>
    </div>

    <!-- بخش مدیریت کاربران (فقط برای مدیرکل) -->
    <?php if ($currentUserRole === 'admin'): ?>
    
        <!-- ثبت کاربر جدید -->
        <div class="content-box mt-4">
            <h3 class="mb-3">
                <i class="bi bi-person-plus me-2"></i>ثبت کاربر جدید
            </h3>
            
            <form method="post">
                <input type="hidden" name="add_new_user" value="1">
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">نام کاربری <span class="text-danger">*</span></label>
                        <input type="text" name="NewUsername" class="form-control" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">رمز عبور <span class="text-danger">*</span></label>
                        <input type="password" name="NewPassword" class="form-control" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">نام کامل <span class="text-danger">*</span></label>
                        <input type="text" name="NewFullName" class="form-control" required>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">ایمیل</label>
                        <input type="email" name="NewEmail" class="form-control">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">موبایل</label>
                        <input type="text" name="NewPhone" class="form-control">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">نقش کاربر <span class="text-danger">*</span></label>
                        <select name="NewRole" class="form-select" required>
                            <option value="user">کاربر (دسترسی محدود)</option>
                            <option value="manager">مدیر (دسترسی متوسط)</option>
                            <option value="admin">مدیر کل (دسترسی کامل)</option>
                        </select>
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-person-plus me-2"></i>ثبت کاربر جدید
                    </button>
                </div>
            </form>
        </div>

        <!-- لیست کاربران -->
        <div class="content-box mt-4">
            <h3 class="mb-3">
                <i class="bi bi-people me-2"></i>لیست کاربران سیستم
            </h3>
            
            <?php if (empty($allUsers)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    هیچ کاربری در سیستم ثبت نشده است.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ردیف</th>
                                <th>نام کاربری</th>
                                <th>نام کامل</th>
                                <th>ایمیل</th>
                                <th>موبایل</th>
                                <th>نقش</th>
                                <th>تاریخ ثبت</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allUsers as $index => $user): ?>
                                <?php 
                                $userId = $user['ID'] ?? 0;
                                $isCurrentUser = ($userId === $currentUserId);
                                $roleLabels = [
                                    'admin' => ['label' => 'مدیر کل', 'class' => 'danger'],
                                    'manager' => ['label' => 'مدیر', 'class' => 'warning'],
                                    'user' => ['label' => 'کاربر', 'class' => 'info']
                                ];
                                $userRole = $user['role'] ?? 'user';
                                $roleInfo = $roleLabels[$userRole] ?? $roleLabels['user'];
                                ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $roleInfo['class']; ?>">
                                            <?php echo $roleInfo['label']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($user['created_at'])) {
                                            echo jdate('Y/m/d', strtotime($user['created_at']));
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td class="table-actions">
                                        <?php if (!$isCurrentUser): ?>
                                            <!-- دکمه ویرایش -->
                                            <button type="button" class="btn btn-sm btn-primary me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editUserModal<?php echo $userId; ?>">
                                                <i class="bi bi-pencil"></i> ویرایش
                                            </button>
                                            <!-- دکمه حذف -->
                                            <a href="?delete_user=<?php echo $userId; ?>" 
                                               class="btn btn-sm btn-danger" 
                                               onclick="return confirmDelete('<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="bi bi-trash"></i> حذف
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted fw-bold">حساب فعلی</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- مودال‌های ویرایش کاربران -->
        <?php foreach ($allUsers as $user): ?>
            <?php 
            $userId = $user['ID'] ?? 0;
            if ($userId > 0 && $userId !== $currentUserId): 
            ?>
                <div class="modal fade" id="editUserModal<?php echo $userId; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $userId; ?>" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="editUserModalLabel<?php echo $userId; ?>">
                                    <i class="bi bi-pencil me-2"></i>
                                    ویرایش کاربر: <?php echo htmlspecialchars($user['username']); ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <form method="post">
                                <input type="hidden" name="edit_user" value="1">
                                <input type="hidden" name="edit_user_id" value="<?php echo $userId; ?>">
                                
                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">نام کاربری <span class="text-danger">*</span></label>
                                            <input type="text" name="EditUsername" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">رمز عبور جدید</label>
                                            <input type="password" name="EditPassword" class="form-control" 
                                                   placeholder="برای عدم تغییر خالی بگذارید">
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">نام کامل <span class="text-danger">*</span></label>
                                            <input type="text" name="EditFullName" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">ایمیل</label>
                                            <input type="email" name="EditEmail" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <label class="form-label">موبایل</label>
                                            <input type="text" name="EditPhone" class="form-control" 
                                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                        
                                        <div class="col-12">
                                            <label class="form-label">نقش کاربر <span class="text-danger">*</span></label>
                                            <select name="EditRole" class="form-select" required>
                                                <?php foreach ($roles as $key => $value): ?>
                                                    <option value="<?php echo $key; ?>" 
                                                        <?php echo ($user['role'] === $key) ? 'selected' : ''; ?>>
                                                        <?php echo $value; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="bi bi-x me-1"></i>انصراف
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-check me-1"></i>ذخیره تغییرات
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    
    <?php endif; ?>

</div>

<?php include __DIR__ . '/footer.php'; ?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
// تابع برای تأیید حذف کاربر
function confirmDelete(username) {
    return confirm('آیا از حذف کاربر "' + username + '" اطمینان دارید؟\nاین عمل غیرقابل بازگشت است.');
}

// فعال کردن مودال‌های بوت‌استرپ
document.addEventListener('DOMContentLoaded', function() {
    // اطمینان از فعال بودن مودال‌ها
    var modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        modal.addEventListener('show.bs.modal', function (event) {
            console.log('Modal opening:', this.id);
        });
    });
    
    console.log('Profile admin page loaded successfully');
});
</script>
</body>
</html>