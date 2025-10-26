<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$errors = [];
$success = '';

$sessionUsername = $_SESSION['user_name'] ?? ($_SESSION['username'] ?? ($_SESSION['login_user'] ?? ''));
$sessionUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Load current user from existing `admins` table
$row = null;
if ($sessionUserId > 0) {
    if ($stmt = $conn->prepare("SELECT id, username, full_name, email, phone, role FROM admins WHERE id=? LIMIT 1")) {
        $stmt->bind_param('i', $sessionUserId);
        if ($stmt->execute()) { $row = $stmt->get_result()->fetch_assoc(); }
        $stmt->close();
    }
}
if (!$row && $sessionUsername) {
    if ($stmt = $conn->prepare("SELECT id, username, full_name, email, phone, role FROM admins WHERE username=? LIMIT 1")) {
        $stmt->bind_param('s', $sessionUsername);
        if ($stmt->execute()) { $row = $stmt->get_result()->fetch_assoc(); }
        $stmt->close();
    }
}

$currentUserRole = $row['role'] ?? 'user';

// Handle new user registration (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_new_user']) && $currentUserRole === 'admin') {
    $newUsername = trim($_POST['NewUsername'] ?? '');
    $newPassword = trim($_POST['NewPassword'] ?? '');
    $newFullName = trim($_POST['NewFullName'] ?? '');
    $newEmail = trim($_POST['NewEmail'] ?? '');
    $newPhone = trim($_POST['NewPhone'] ?? '');
    $newRole = $_POST['NewRole'] ?? 'user';
    
    if ($newUsername === '') { $errors[] = 'نام کاربری الزامی است.'; }
    if ($newPassword === '') { $errors[] = 'رمز عبور الزامی است.'; }
    if ($newFullName === '') { $errors[] = 'نام کامل الزامی است.'; }
    if (!in_array($newRole, ['admin','manager','user'], true)) { $errors[] = 'نقش نامعتبر است.'; }
    
    // بررسی تکراری نبودن نام کاربری
    if (!$errors) {
        $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username=?");
        $checkStmt->bind_param('s', $newUsername);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $errors[] = 'این نام کاربری قبلاً ثبت شده است.';
        }
        $checkStmt->close();
    }
    
    if (!$errors) {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $insertStmt = $conn->prepare("INSERT INTO admins (username, password, full_name, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param('ssssss', $newUsername, $hashedPassword, $newFullName, $newEmail, $newPhone, $newRole);
        
        if ($insertStmt->execute()) {
            $success = 'کاربر جدید با موفقیت ثبت شد.';
        } else {
            $errors[] = 'خطا در ثبت کاربر: ' . $conn->error;
        }
        $insertStmt->close();
    }
}

// Handle edit user (only for admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user']) && $currentUserRole === 'admin') {
    $editUserId = (int)$_POST['edit_user_id'];
    $editUsername = trim($_POST['EditUsername'] ?? '');
    $editPassword = trim($_POST['EditPassword'] ?? '');
    $editFullName = trim($_POST['EditFullName'] ?? '');
    $editEmail = trim($_POST['EditEmail'] ?? '');
    $editPhone = trim($_POST['EditPhone'] ?? '');
    $editRole = $_POST['EditRole'] ?? 'user';
    
    if ($editUsername === '') { $errors[] = 'نام کاربری الزامی است.'; }
    if ($editFullName === '') { $errors[] = 'نام کامل الزامی است.'; }
    if (!in_array($editRole, ['admin','manager','user'], true)) { $errors[] = 'نقش نامعتبر است.'; }
    
    // بررسی تکراری نبودن نام کاربری (به جز خود کاربر)
    if (!$errors && $editUserId > 0) {
        $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username=? AND id!=?");
        $checkStmt->bind_param('si', $editUsername, $editUserId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows > 0) {
            $errors[] = 'این نام کاربری قبلاً استفاده شده است.';
        }
        $checkStmt->close();
    }
    
    if (!$errors && $editUserId > 0) {
        if (!empty($editPassword)) {
            // با رمز عبور جدید
            $hashedPassword = password_hash($editPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE admins SET username=?, password=?, full_name=?, email=?, phone=?, role=? WHERE id=?");
            $updateStmt->bind_param('ssssssi', $editUsername, $hashedPassword, $editFullName, $editEmail, $editPhone, $editRole, $editUserId);
        } else {
            // بدون تغییر رمز عبور
            $updateStmt = $conn->prepare("UPDATE admins SET username=?, full_name=?, email=?, phone=?, role=? WHERE id=?");
            $updateStmt->bind_param('sssssi', $editUsername, $editFullName, $editEmail, $editPhone, $editRole, $editUserId);
        }
        
        if ($updateStmt->execute()) {
            $success = 'اطلاعات کاربر با موفقیت به‌روزرسانی شد.';
        } else {
            $errors[] = 'خطا در به‌روزرسانی: ' . $conn->error;
        }
        $updateStmt->close();
    }
}

// Handle delete user (only for admin)
if (isset($_GET['delete_user']) && $currentUserRole === 'admin') {
    $deleteId = (int)$_GET['delete_user'];
    if ($deleteId > 0 && $deleteId !== $sessionUserId) {
        $deleteStmt = $conn->prepare("DELETE FROM admins WHERE id=?");
        $deleteStmt->bind_param('i', $deleteId);
        if ($deleteStmt->execute()) {
            $success = 'کاربر با موفقیت حذف شد.';
        }
        $deleteStmt->close();
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['add_new_user'])) {
    $FullName = trim($_POST['FullName'] ?? '');
    $Username = trim($_POST['Username'] ?? '');
    $Password = trim($_POST['Password'] ?? '');
    $Mobile   = trim($_POST['Mobile'] ?? '');
    $Email    = trim($_POST['Email'] ?? '');
    $Role     = $_POST['Role'] ?? 'user';
    if ($Username === '') { $errors[] = 'نام کاربری الزامی است.'; }
    if ($FullName === '') { $errors[] = 'نام کامل الزامی است.'; }
    if (!in_array($Role, ['admin','manager','user'], true)) { $errors[] = 'نقش نامعتبر است.'; }

    if (!$errors) {
        $useId = isset($row['id']) && (int)$row['id']>0;
        if ($Password !== '') {
            $hash = password_hash($Password, PASSWORD_DEFAULT);
            if ($useId) {
                $sql = "UPDATE admins SET username=?, password=?, phone=?, email=?, role=?, full_name=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssssi', $Username, $hash, $Mobile, $Email, $Role, $FullName, $row['id']);
            } else {
                $sql = "UPDATE admins SET password=?, phone=?, email=?, role=?, full_name=? WHERE username=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ssssss', $hash, $Mobile, $Email, $Role, $FullName, $sessionUsername);
            }
        } else {
            if ($useId) {
                $sql = "UPDATE admins SET username=?, phone=?, email=?, role=?, full_name=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssssi', $Username, $Mobile, $Email, $Role, $FullName, $row['id']);
            } else {
                $sql = "UPDATE admins SET phone=?, email=?, role=?, full_name=? WHERE username=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('sssss', $Mobile, $Email, $Role, $FullName, $sessionUsername);
            }
        }
        if ($stmt && $stmt->execute()) {
            $success = 'پروفایل با موفقیت به‌روزرسانی شد.';
            // Sync common session keys
            $_SESSION['user_name'] = $Username;
            $_SESSION['username']  = $Username;
            if ($useId) { $_SESSION['user_id'] = (int)$row['id']; }
            // Reload row
            if ($useId) {
                $stmt2 = $conn->prepare("SELECT id, username, full_name, email, phone, role FROM admins WHERE id=?");
                $stmt2->bind_param('i', $row['id']);
            } else {
                $stmt2 = $conn->prepare("SELECT id, username, full_name, email, phone, role FROM admins WHERE username=?");
                $stmt2->bind_param('s', $Username);
            }
            $stmt2->execute();
            $row = $stmt2->get_result()->fetch_assoc();
            $stmt2->close();
        } else {
            $errors[] = 'خطا در ذخیره‌سازی: ' . $conn->error;
        }
        if ($stmt) { $stmt->close(); }
    }
}

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>پروفایل کاربر</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
</head>
<body>
<?php include __DIR__ . '/header.php'; ?>
<div class="container" style="margin-top:100px; margin-bottom:40px; text-align:right;">
    <div class="content-box">
        <div class="d-flex justify-content-start align-items-center mb-3">
            <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                <span class="me-2">بستن</span>
                <span aria-hidden="true" class="fs-5">×</span>
            </a>
            <h2 class="mb-0">پروفایل کاربر</h2>
        </div>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($errors): ?><div class="alert alert-danger"><?php echo implode('<br>', $errors); ?></div><?php endif; ?>

        <form method="post">
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">نام کامل</label><input name="FullName" class="form-control" value="<?php echo htmlspecialchars($row['full_name'] ?? ''); ?>" required></div>
                <div class="col-md-4"><label class="form-label">نام کاربری</label><input name="Username" class="form-control" value="<?php echo htmlspecialchars($row['username'] ?? $sessionUsername); ?>" required></div>
                <div class="col-md-4"><label class="form-label">رمز عبور (اختیاری)</label><input type="password" name="Password" class="form-control" placeholder="در صورت خالی بودن تغییری نمی‌کند"></div>
                <div class="col-md-4"><label class="form-label">موبایل</label><input name="Mobile" class="form-control" value="<?php echo htmlspecialchars($row['phone'] ?? ($_SESSION['phone'] ?? '')); ?>"></div>
                <div class="col-md-4"><label class="form-label">ایمیل</label><input type="email" name="Email" class="form-control" value="<?php echo htmlspecialchars($row['email'] ?? ($_SESSION['email'] ?? '')); ?>"></div>
                <div class="col-md-4"><label class="form-label">نقش</label>
                    <select name="Role" class="form-select">
                        <?php $roles=['admin'=>'مدیر کل','manager'=>'مدیر','user'=>'کاربر']; $cur=$row['role'] ?? ($_SESSION['role'] ?? 'user');
                        foreach($roles as $rk=>$rv){ echo '<option value="'.$rk.'"'.(($cur===$rk)?' selected':'').'>'.$rv.'</option>'; } ?>
                    </select>
                </div>
            </div>
            <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-save"></i> ذخیره پروفایل</button></div>
        </form>
    </div>

    <?php if ($currentUserRole === 'admin'): ?>
    <!-- بخش ثبت کاربر جدید (فقط برای مدیرکل) -->
    <div class="content-box mt-4">
        <h3 class="mb-3"><i class="bi bi-person-plus"></i> ثبت کاربر جدید</h3>
        <form method="post">
            <input type="hidden" name="add_new_user" value="1">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">نام کاربری <span class="text-danger">*</span></label>
                    <input name="NewUsername" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">رمز عبور <span class="text-danger">*</span></label>
                    <input type="password" name="NewPassword" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">نام کامل <span class="text-danger">*</span></label>
                    <input name="NewFullName" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">ایمیل</label>
                    <input type="email" name="NewEmail" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">موبایل</label>
                    <input name="NewPhone" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">نقش کاربر <span class="text-danger">*</span></label>
                    <select name="NewRole" class="form-select" required>
                        <option value="user">کاربر (دسترسی محدود به حضور و غیاب)</option>
                        <option value="manager">مدیر (دسترسی به نوجوانان، بزرگسالان و دوره‌ها)</option>
                        <option value="admin">مدیر کل (دسترسی کامل)</option>
                    </select>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-person-plus"></i> ثبت کاربر جدید
                </button>
            </div>
        </form>
    </div>

    <!-- لیست کاربران موجود -->
    <div class="content-box mt-4">
        <h3 class="mb-3"><i class="bi bi-people"></i> لیست کاربران</h3>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead class="table">
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
                    <?php
                    $alladmins = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
                    $counter = 1;
                    while ($user = $alladmins->fetch_assoc()):
                        $roleLabel = ['admin'=>'مدیر کل', 'manager'=>'مدیر', 'user'=>'کاربر'][$user['role']] ?? 'نامشخص';
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span class="badge bg-danger"><?php echo $roleLabel; ?></span>
                            <?php elseif ($user['role'] === 'manager'): ?>
                                <span class="badge bg-warning"><?php echo $roleLabel; ?></span>
                            <?php else: ?>
                                <span class="badge bg-info"><?php echo $roleLabel; ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('Y/m/d H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['id'] !== $sessionUserId): ?>
                                <button type="button" class="btn btn-sm btn-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                    <i class="bi bi-pencil"></i> ویرایش
                                </button>
                                <a href="?delete_user=<?php echo $user['id']; ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('آیا از حذف این کاربر اطمینان دارید؟')">
                                    <i class="bi bi-trash"></i> حذف
                                </a>
                            <?php else: ?>
                                <span class="text-muted">شما</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- مودال‌های ویرایش کاربران -->
    <?php
    // بازخوانی کاربران برای ایجاد مودال‌ها
    $alladminsForModals = $conn->query("SELECT * FROM admins ORDER BY created_at DESC");
    while ($user = $alladminsForModals->fetch_assoc()):
    ?>
    <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="post" action="">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="editUserModalLabel<?php echo $user['id']; ?>">
                            <i class="bi bi-pencil"></i> ویرایش کاربر: <?php echo htmlspecialchars($user['username']); ?>
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="edit_user" value="1">
                        <input type="hidden" name="edit_user_id" value="<?php echo $user['id']; ?>">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">نام کاربری <span class="text-danger">*</span></label>
                                <input type="text" name="EditUsername" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">رمز عبور <small class="text-muted">(برای عدم تغییر خالی بگذارید)</small></label>
                                <input type="password" name="EditPassword" class="form-control" placeholder="رمز عبور جدید">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">نام کامل <span class="text-danger">*</span></label>
                                <input type="text" name="EditFullName" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">ایمیل</label>
                                <input type="email" name="EditEmail" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">موبایل</label>
                                <input type="text" name="EditPhone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">نقش کاربر <span class="text-danger">*</span></label>
                                <select name="EditRole" class="form-select" required>
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>کاربر (فقط حضور و غیاب)</option>
                                    <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>مدیر (نوجوانان، بزرگسالان، دوره‌ها)</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>مدیر کل (دسترسی کامل)</option>
                                </select>
                                <small class="text-muted">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>کاربر:</strong> فقط حضور و غیاب |
                                    <strong>مدیر:</strong> همه به جز گزارشات |
                                    <strong>مدیرکل:</strong> دسترسی کامل
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x"></i> انصراف
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> ذخیره تغییرات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endwhile; ?>
    
    <?php endif; ?>
</div>
<?php include __DIR__ . '/footer.php'; ?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

