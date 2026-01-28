<?php
// بررسی دسترسی کاربر
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
    $where = "WHERE (AdultMelli LIKE '%$q_esc%' OR AdultSysCode LIKE '%$q_esc%' OR AdultName LIKE '%$q_esc%' OR AdultFamily LIKE '%$q_esc%')";
}

$total = 0;
$rsCount = $conn->query("SELECT COUNT(*) c FROM adult $where");
if ($rsCount) { $total = (int)$rsCount->fetch_assoc()['c']; }

$rs = $conn->query("SELECT * FROM adult $where ORDER BY AdultID DESC LIMIT $perPage OFFSET $offset");
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>لیست بزرگسالان</title>
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
            <h2 class="mb-0">لیست بزرگسالان</h2>
        </div>
        
        <form class="mb-4" method="get">
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input class="form-control form-control-lg" name="q" placeholder="جستجو بر اساس کد ملی، کدسیستمی، نام یا نام خانوادگی" value="<?php echo htmlspecialchars($q); ?>">
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
                <?php if ($rs && $rs->num_rows): while($row=$rs->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['AdultSysCode']); ?></td>
                        <td><?php echo htmlspecialchars($row['AdultMelli']); ?></td>
                        <td><?php echo htmlspecialchars($row['AdultName']); ?></td>
                        <td><?php echo htmlspecialchars($row['AdultFamily']); ?></td>
                        <td><?php echo htmlspecialchars($row['AdultFather']); ?></td>
                        <td><?php echo htmlspecialchars($row['AdultMobile1']); ?></td>
                        <td><?php echo htmlspecialchars($row['AdultCity'] ?? '-'); ?></td>
                        <td>
                            <?php
                            $statusClass = '';
                            if ($row['AdultStatus'] === 'فعال') {
                                $statusClass = 'text-success';
                            } elseif ($row['AdultStatus'] === 'غیرفعال') {
                                $statusClass = 'text-warning';
                            } elseif ($row['AdultStatus'] === 'تعلیق') {
                                $statusClass = 'text-danger';
                            }
                            ?>
                            <span class="<?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($row['AdultStatus']); ?>
                            </span>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="editadult.php?AdultID=<?php echo $row['AdultID']; ?>" title="ویرایش">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a class="btn btn-sm btn-info" href="viewadult.php?AdultID=<?php echo $row['AdultID']; ?>" title="مشاهده">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="9" class="text-center py-4">
                        <div class="text-muted">
                            <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
                            رکوردی یافت نشد.
                        </div>
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($total > $perPage): ?>
        <nav aria-label="صفحه‌بندی" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php $totalPages = ceil($total / $perPage); ?>
                
                <!-- Previous Page Link -->
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>" aria-label="قبلی">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                
                <!-- Page Numbers -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <!-- Next Page Link -->
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $q ? '&q=' . urlencode($q) : ''; ?>" aria-label="بعدی">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <!-- Page Info -->
                <li class="page-item disabled">
                    <span class="page-link text-muted">
                        صفحه <?php echo $page; ?> از <?php echo $totalPages; ?>
                    </span>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

        <?php
        $pages = (int)ceil($total / $perPage);
        if ($pages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <?php for($p=1; $p<=$pages; $p++): ?>
                    <li class="page-item <?php echo ($p==$page)?'active':''; ?>">
                        <a class="page-link" href="?q=<?php echo urlencode($q); ?>&page=<?php echo $p; ?>"><?php echo $p; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>

        <div class="mt-4 text-center">
            <a href="regadult.php" class="btn btn-primary btn-lg">
                <i class="bi bi-person-plus"></i> ثبت بزرگسال جدید
            </a>
        </div>
    </div>
</div>
<?php include __DIR__ . '/footer.php'; ?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>