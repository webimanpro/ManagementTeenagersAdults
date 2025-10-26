<?php

// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../config/database.php';

$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = '';
if ($q !== '') {
    $q_esc = $conn->real_escape_string($q);
    $where = "WHERE (UserMelli LIKE '%$q_esc%' OR UserSysCode LIKE '%$q_esc%' OR UserName LIKE '%$q_esc%' OR UserFamily LIKE '%$q_esc%')";
}

$total = 0;
$rsCount = $conn->query("SELECT COUNT(*) c FROM Users $where");
if ($rsCount) { $total = (int)$rsCount->fetch_assoc()['c']; }

$rs = $conn->query("SELECT * FROM users $where ORDER BY UserID DESC LIMIT $perPage OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>لیست نوجوانان</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
</head>
<body>
<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
include __DIR__ . '/header.php'; ?>
<div class="container" style="margin-top:100px; margin-bottom:40px; text-align:right;">
    <div class="content-box">
                <div class="d-flex justify-content-start align-items-center mb-3">
            <a href="../index.php" class="btn btn-outline-secondary d-flex align-items-center ms-2">
                <span class="me-2">بستن</span>
                <span aria-hidden="true" class="fs-5">×</span>
            </a>
            <h2 class="mb-0">لیست نوجوانان</h2>
        </div>
        <form class="mb-4" method="get">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input class="form-control form-control-lg" name="q" placeholder="جستجو بر اساس کد ملی، کدسیستمی، نام یا نام خانوادگی" value="<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($q); ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-secondary btn-lg me-2"><i class="bi bi-search"></i> جستجو</button>
                    <a href="?" class="btn btn-secondary btn-lg"><i class="bi bi-arrow-counterclockwise"></i> بازنشانی</a>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>کدسیستمی</th>
                        <th>کدملی</th>
                        <th>نام</th>
                        <th>نام خانوادگی</th>
                        <th>نام پدر</th>
                        <th>موبایل</th>
                        <th>شهر</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($rs && $rs->num_rows): while($row=$rs->fetch_assoc()): ?>
                    <tr>
                        <td><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($row['UserSysCode']); ?></td>
                        <td><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($row['UserMelli']); ?></td>
                        <td><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($row['UserName']); ?></td>
                        <td><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($row['UserFamily']); ?></td>
                        <td><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($row['UserFather']); ?></td>
                        <td><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($row['UserMobile1']); ?></td>
                        <td><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($row['UserCity'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
$statusClass = '';
                            if ($row['UserStatus'] === 'عادی') {
                                $statusClass = 'text-success';
                            } elseif ($row['UserStatus'] === 'فعال') {
                                $statusClass = 'text-warning';
                            } elseif ($row['UserStatus'] === 'تعلیق') {
                                $statusClass = 'text-danger';
                            }
                            ?>
                            <span class="<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $statusClass; ?>">
                                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo htmlspecialchars($row['UserStatus']); ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="edituser.php?UserID=<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $row['UserID']; ?>" title="ویرایش">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a class="btn btn-sm btn-info" href="viewuser.php?UserID=<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $row['UserID']; ?>" title="مشاهده">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endwhile; else: ?>
                    <tr><td colspan="9" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
                            رکوردی یافت نشد.
                        </div>
                    </td></tr>
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>
                </tbody>
            </table>
        </div>

        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($total > $perPage): ?>
        <nav aria-label="صفحه‌بندی" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
$totalPages = ceil($total / $perPage); ?>
                
                <!-- Previous Page Link -->
                <li class="page-item <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $page - 1; ?><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $q ? '&q=' . urlencode($q) : ''; ?>" aria-label="قبلی">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                
                <!-- Page Numbers -->
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <li class="page-item <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $i; ?><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $q ? '&q=' . urlencode($q) : ''; ?>">
                                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $i; ?>
                            </a>
                        </li>
                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endfor; ?>
                
                <!-- Next Page Link -->
                <li class="page-item <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $page + 1; ?><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $q ? '&q=' . urlencode($q) : ''; ?>" aria-label="بعدی">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <!-- Page Info -->
                <li class="page-item disabled">
                    <span class="page-link text-muted">
                        صفحه <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $page; ?> از <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $totalPages; ?>
                    </span>
                </li>
            </ul>
        </nav>
        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>

        <?php
        
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
$pages = (int)ceil($total / $perPage);
        if ($pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
for($p=1; $p<=$pages; $p++): ?>
                    <li class="page-item <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo ($p==$page)?'active':''; ?>">
                        <a class="page-link" href="?q=<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo urlencode($q); ?>&page=<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $p; ?>"><?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
echo $p; ?></a>
                    </li>
                <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endfor; ?>
            </ul>
        </nav>
        <?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
endif; ?>

        <div class="mt-4 text-center">
            <a href="regsiteruser.php" class="btn btn-primary btn-lg">
                <i class="bi bi-person-plus"></i> ثبت نام جدید
            </a>
        </div>
    </div>
</div>
<?php 
// ط¨ط±ط±ط³غŒ ط¯ط³طھط±ط³غŒ ع©ط§ط±ط¨ط±
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
include __DIR__ . '/footer.php'; ?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>