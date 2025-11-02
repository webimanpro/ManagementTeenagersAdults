<?php
// بررسی دسترسی کاربر
require_once __DIR__ . '/check_access.php';
requireAccess(basename(__FILE__));
if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/jdf.php';

// دریافت پارامترهای جستجو و فیلتر
$q = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$age_group_filter = $_GET['age_group'] ?? '';
$birth_year = $_GET['birth_year'] ?? '';
$birth_month = $_GET['birth_month'] ?? '';
$show_filters = isset($_GET['show_filters']) ? (bool)$_GET['show_filters'] : false;

// ساخت شرط WHERE برای فیلترها
$where_conditions = [];

if ($q !== '') {
    $q_esc = $conn->real_escape_string($q);
    $where_conditions[] = "(UserMelli LIKE '%$q_esc%' OR UserSysCode LIKE '%$q_esc%' OR UserName LIKE '%$q_esc%' OR UserFamily LIKE '%$q_esc%')";
}

if ($status_filter !== '') {
    $status_esc = $conn->real_escape_string($status_filter);
    $where_conditions[] = "UserStatus = '$status_esc'";
}

// فیلتر رده سنی
if ($age_group_filter !== '') {
    if ($age_group_filter === 'نوجوان') {
        // کاربران زیر 18 سال
        $where_conditions[] = "(YEAR(CURDATE()) - YEAR(UserDateBirth) < 18 OR (YEAR(CURDATE()) - YEAR(UserDateBirth) = 18 AND (MONTH(CURDATE()) < MONTH(UserDateBirth) OR (MONTH(CURDATE()) = MONTH(UserDateBirth) AND DAY(CURDATE()) < DAY(UserDateBirth)))))";
    } elseif ($age_group_filter === 'بزرگسال') {
        // کاربران 18 سال و بالاتر
        $where_conditions[] = "(YEAR(CURDATE()) - YEAR(UserDateBirth) > 18 OR (YEAR(CURDATE()) - YEAR(UserDateBirth) = 18 AND (MONTH(CURDATE()) > MONTH(UserDateBirth) OR (MONTH(CURDATE()) = MONTH(UserDateBirth) AND DAY(CURDATE()) >= DAY(UserDateBirth)))))";
    }
}

// فیلتر بر اساس سال تولد شمسی
if ($birth_year !== '') {
    $birth_year_esc = $conn->real_escape_string($birth_year);
    
    // تبدیل سال شمسی به محدوده میلادی
    $gregorian_start = jalali_to_gregorian($birth_year_esc, 1, 1);
    $gregorian_end = jalali_to_gregorian($birth_year_esc + 1, 1, 1);
    
    $start_date = $gregorian_start[0] . '-' . sprintf('%02d', $gregorian_start[1]) . '-' . sprintf('%02d', $gregorian_start[2]);
    $end_date = $gregorian_end[0] . '-' . sprintf('%02d', $gregorian_end[1]) . '-' . sprintf('%02d', $gregorian_end[2]);
    
    $where_conditions[] = "(UserDateBirth >= '$start_date' AND UserDateBirth < '$end_date')";
}

// فیلتر بر اساس ماه تولد شمسی
if ($birth_month !== '') {
    $birth_month_esc = (int)$birth_month;
    
    // برای هر ماه شمسی، محدوده میلادی تقریبی را محاسبه می‌کنیم
    $month_conditions = [];
    
    // سال شمسی فعلی برای محاسبه
    $current_jalali = gregorian_to_jalali(date('Y'), date('m'), date('d'));
    $current_jalali_year = $current_jalali[0];
    
    // محدوده ماه شمسی در میلادی
    $start_month_jalali = jalali_to_gregorian($current_jalali_year, $birth_month_esc, 1);
    $end_month_jalali = jalali_to_gregorian($current_jalali_year, $birth_month_esc + 1, 1);
    
    if ($birth_month_esc == 12) {
        $end_month_jalali = jalali_to_gregorian($current_jalali_year + 1, 1, 1);
    }
    
    $start_month_gregorian = $start_month_jalali[0] . '-' . sprintf('%02d', $start_month_jalali[1]) . '-' . sprintf('%02d', $start_month_jalali[2]);
    $end_month_gregorian = $end_month_jalali[0] . '-' . sprintf('%02d', $end_month_jalali[1]) . '-' . sprintf('%02d', $end_month_jalali[2]);
    
    $month_conditions[] = "(MONTH(UserDateBirth) = MONTH('$start_month_gregorian'))";
    
    if (!empty($month_conditions)) {
        $where_conditions[] = "(" . implode(' OR ', $month_conditions) . ")";
    }
}

$where = '';
if (!empty($where_conditions)) {
    $where = "WHERE " . implode(' AND ', $where_conditions);
}

// دریافت کل کاربران
$limit = 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// اگر درخواست AJAX برای بارگذاری بیشتر است
if (isset($_GET['action']) && $_GET['action'] === 'load_more') {
    $rs = $conn->query("SELECT * FROM users $where ORDER BY UserID DESC LIMIT $offset, $limit");
    
    if ($rs && $rs->num_rows > 0) {
        while($row = $rs->fetch_assoc()) {
            // محاسبه سن کاربر
            $age = '';
            if (!empty($row['UserDateBirth']) && $row['UserDateBirth'] != '0000-00-00') {
                $birth_date = new DateTime($row['UserDateBirth']);
                $today = new DateTime();
                $age = $today->diff($birth_date)->y;
            }
            ?>
            <tr data-userid="<?php echo $row['UserID']; ?>">
                <td><?php echo htmlspecialchars($row['UserSysCode']); ?></td>
                <td><?php echo htmlspecialchars($row['UserMelli']); ?></td>
                <td><?php echo htmlspecialchars($row['UserName']); ?></td>
                <td><?php echo htmlspecialchars($row['UserFamily']); ?></td>
                <td><?php echo htmlspecialchars($row['UserFather']); ?></td>
                <td><?php echo htmlspecialchars($row['UserMobile1']); ?></td>
                <td>
                    <?php
                    $statusClass = '';
                    if ($row['UserStatus'] === 'عادی') {
                        $statusClass = 'text-success';
                    } elseif ($row['UserStatus'] === 'فعال') {
                        $statusClass = 'text-warning';
                    } elseif ($row['UserStatus'] === 'تعلیق') {
                        $statusClass = 'text-danger';
                    }
                    ?>
                    <span class="<?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($row['UserStatus']); ?>
                    </span>
                    <?php if ($age !== ''): ?>
                        <br><small class="text-muted">سن: <?php echo $age; ?> سال</small>
                    <?php endif; ?>
                </td>
                <td>
                    <a class="btn btn-sm btn-primary" href="edituser.php?UserID=<?php echo $row['UserID']; ?>" title="ویرایش">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <a class="btn btn-sm btn-info" href="viewuser.php?UserID=<?php echo $row['UserID']; ?>" title="مشاهده">
                        <i class="bi bi-eye"></i>
                    </a>
                </td>
            </tr>
            <?php
        }
    }
    exit;
}

// دریافت کاربران برای نمایش اولیه
$rs = $conn->query("SELECT * FROM users $where ORDER BY UserID DESC LIMIT $limit");
$total_users = $conn->query("SELECT COUNT(*) as total FROM users $where")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>لیست نوجوانان و بزرگسالان</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .filters-toggle {
            cursor: pointer;
        }
        .advanced-filters {
            display: none;
            margin-top: 15px;
        }
        .user-count {
            background-color: #e9ecef;
            padding: 8px 15px;
            border-radius: 5px;
            font-weight: bold;
        }
        .age-badge {
            font-size: 0.75rem;
            padding: 2px 6px;
        }
    </style>
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
            <h2 class="mb-0">لیست نوجوانان و بزرگسالان</h2>
        </div>

        <!-- فرم جستجوی اصلی -->
        <form class="mb-4" method="get" id="mainForm">
            <input type="hidden" name="show_filters" id="showFiltersInput" value="<?php echo $show_filters ? '1' : '0'; ?>">
            
            <div class="row g-2 align-items-center">
                <div class="col-md-8">
                    <input class="form-control form-control-lg" name="q" placeholder="جستجو بر اساس کدملی، کدسیستمی، نام یا نام خانوادگی" value="<?php echo htmlspecialchars($q); ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-primary btn-lg me-2"><i class="bi bi-search"></i> جستجو</button>
                    <span class="btn btn-secondary btn-lg"><i class="bi bi-people-fill"></i> تعداد: <span id="totalUsers"><?php echo $total_users; ?></span></span>
                </div>
            </div>

            <!-- دکمه نمایش/مخفی کردن فیلترهای پیشرفته -->
            <div class="row mt-3">
                <div class="col-12">
                    <div class="form-check filters-toggle" onclick="toggleAdvancedFilters()">
                        <input class="form-check-input" type="checkbox" id="showFilters" <?php echo $show_filters ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="showFilters">
                            <i class="bi bi-funnel"></i> فیلترهای پیشرفته
                        </label>
                    </div>
                </div>
            </div>

            <!-- فیلترهای پیشرفته -->
            <div class="advanced-filters card mt-3" id="advancedFilters" style="<?php echo $show_filters ? 'display: block;' : 'display: none;'; ?>">
                <div class="card-body">
                    <h5 class="card-title">فیلترهای پیشرفته</h5>
                    <div class="row">
                        <!-- فیلتر وضعیت -->
                        <div class="col-md-6 mb-3">
                            <h6 class="mb-3">وضعیت:</h6>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusAll" value="" 
                                    <?php echo $status_filter === '' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="statusAll">همه</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusActive" value="فعال" 
                                    <?php echo $status_filter === 'فعال' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="statusActive">فعال</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusNormal" value="عادی" 
                                    <?php echo $status_filter === 'عادی' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="statusNormal">عادی</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="status" id="statusSuspended" value="تعلیق" 
                                    <?php echo $status_filter === 'تعلیق' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="statusSuspended">تعلیق</label>
                            </div>
                        </div>
                        
                        <!-- فیلتر سن -->
                        <div class="col-md-6 mb-3">
                            <h6 class="mb-3">رده سنی:</h6>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="age_group" id="ageAll" value="" 
                                    <?php echo $age_group_filter === '' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ageAll">همه</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="age_group" id="ageTeen" value="نوجوان" 
                                    <?php echo $age_group_filter === 'نوجوان' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ageTeen">نوجوانان (زیر ۱۸ سال)</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="age_group" id="ageAdult" value="بزرگسال" 
                                    <?php echo $age_group_filter === 'بزرگسال' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ageAdult">بزرگسالان (۱۸ سال و بالاتر)</label>
                            </div>
                        </div>
                    </div>

                    <!-- فیلتر تاریخ تولد -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <h6 class="mb-3">تاریخ تولد (شمسی):</h6>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label for="birth_year" class="form-label">سال تولد</label>
                                    <select class="form-select" name="birth_year" id="birth_year">
                                        <option value="">همه سال‌ها</option>
                                        <?php
                                        // نمایش سال‌های شمسی از 1300 تا 1404
                                        $current_shamsi_year = 1404;
                                        for ($year = $current_shamsi_year; $year >= 1300; $year--) {
                                            $selected = ($birth_year == $year) ? 'selected' : '';
                                            echo "<option value='$year' $selected>$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="birth_month" class="form-label">ماه تولد</label>
                                    <select class="form-select" name="birth_month" id="birth_month">
                                        <option value="">همه ماه‌ها</option>
                                        <?php
                                        $months = [
                                            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
                                            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
                                            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
                                            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
                                        ];
                                        foreach ($months as $num => $name) {
                                            $selected = ($birth_month == $num) ? 'selected' : '';
                                            echo "<option value='$num' $selected>$name</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> اعمال فیلترها
                            </button>
                            <a href="listuser.php" class="btn btn-outline-secondary">حذف فیلترها</a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- جدول کاربران -->
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
                        <th>وضعیت و سن</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php 
                    $loaded_count = 0;
                    if ($rs && $rs->num_rows > 0): 
                        while($row = $rs->fetch_assoc()): 
                            $loaded_count++;
                            
                            // محاسبه سن کاربر
                            $age = '';
                            $age_badge = '';
                            if (!empty($row['UserDateBirth']) && $row['UserDateBirth'] != '0000-00-00') {
                                $birth_date = new DateTime($row['UserDateBirth']);
                                $today = new DateTime();
                                $age = $today->diff($birth_date)->y;
                                
                                if ($age < 18) {
                                    $age_badge = '<span class="badge bg-info age-badge">نوجوان</span>';
                                } else {
                                    $age_badge = '<span class="badge bg-success age-badge">بزرگسال</span>';
                                }
                            }
                    ?>
                    <tr data-userid="<?php echo $row['UserID']; ?>">
                        <td><?php echo htmlspecialchars($row['UserSysCode']); ?></td>
                        <td><?php echo htmlspecialchars($row['UserMelli']); ?></td>
                        <td><?php echo htmlspecialchars($row['UserName']); ?></td>
                        <td><?php echo htmlspecialchars($row['UserFamily']); ?></td>
                        <td><?php echo htmlspecialchars($row['UserFather']); ?></td>
                        <td><?php echo htmlspecialchars($row['UserMobile1']); ?></td>
                        <td>
                            <?php
                            $statusClass = '';
                            if ($row['UserStatus'] === 'عادی') {
                                $statusClass = 'text-success';
                            } elseif ($row['UserStatus'] === 'فعال') {
                                $statusClass = 'text-warning';
                            } elseif ($row['UserStatus'] === 'تعلیق') {
                                $statusClass = 'text-danger';
                            }
                            ?>
                            <span class="<?php echo $statusClass; ?>">
                                <?php echo htmlspecialchars($row['UserStatus']); ?>
                            </span>
                            <?php if ($age !== ''): ?>
                                <br>
                                <small class="text-muted">سن: <?php echo $age; ?> سال</small>
                                <?php echo $age_badge; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn btn-sm btn-primary" href="edituser.php?UserID=<?php echo $row['UserID']; ?>" title="ویرایش">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a class="btn btn-sm btn-info" href="viewuser.php?UserID=<?php echo $row['UserID']; ?>" title="مشاهده">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-exclamation-circle fs-1 d-block mb-2"></i>
                                <?php echo ($q !== '' || $status_filter !== '' || $age_group_filter !== '' || $birth_year !== '' || $birth_month !== '') ? 
                                    'هیچ کاربری با فیلترهای اعمال شده یافت نشد.' : 
                                    'هیچ کاربری در سیستم ثبت نشده است.'; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- اسپینر بارگذاری -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">در حال بارگذاری...</span>
            </div>
            <p class="mt-2">در حال بارگذاری اطلاعات بیشتر...</p>
        </div>

        <!-- اطلاعات بارگذاری شده -->
        <div class="text-center mt-3">
            <p class="text-muted" id="loadInfo">
                <span id="loadedCount"><?php echo $loaded_count; ?></span> از 
                <span id="totalCount"><?php echo $total_users; ?></span> رکورد نمایش داده شده
            </p>
        </div>

        <div class="mt-4 text-center">
            <a href="regsiteruser.php" class="btn btn-primary btn-lg">
                <i class="bi bi-person-plus"></i> ثبت نام جدید
            </a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/footer.php'; ?>
<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script>
let currentOffset = <?php echo $limit; ?>;
let isLoading = false;
let hasMore = true;
const totalUsers = <?php echo $total_users; ?>;
let loadedCount = <?php echo $loaded_count; ?>;

// تابع برای نمایش/مخفی کردن فیلترهای پیشرفته
function toggleAdvancedFilters() {
    const filters = document.getElementById('advancedFilters');
    const checkbox = document.getElementById('showFilters');
    const hiddenInput = document.getElementById('showFiltersInput');
    
    if (filters.style.display === 'none') {
        filters.style.display = 'block';
        checkbox.checked = true;
        hiddenInput.value = '1';
    } else {
        filters.style.display = 'none';
        checkbox.checked = false;
        hiddenInput.value = '0';
    }
}

// تابع برای بارگذاری خودکار هنگام اسکرول
window.addEventListener('scroll', function() {
    if (isLoading || !hasMore) return;

    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    
    // اگر کاربر به انتهای صفحه نزدیک شد
    if (scrollTop + clientHeight >= scrollHeight - 100) {
        loadMoreUsers();
    }
});

// تابع برای بارگذاری کاربران بیشتر
function loadMoreUsers() {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    document.getElementById('loadingSpinner').style.display = 'block';
    
    // ساخت پارامترهای URL
    const params = new URLSearchParams({
        q: '<?php echo $q; ?>',
        status: '<?php echo $status_filter; ?>',
        age_group: '<?php echo $age_group_filter; ?>',
        birth_year: '<?php echo $birth_year; ?>',
        birth_month: '<?php echo $birth_month; ?>',
        offset: currentOffset,
        action: 'load_more'
    });
    
    fetch('listuser.php?' + params.toString())
        .then(response => response.text())
        .then(data => {
            if (data.trim() !== '') {
                document.getElementById('usersTableBody').insertAdjacentHTML('beforeend', data);
                currentOffset += <?php echo $limit; ?>;
                loadedCount += (data.match(/<tr/g) || []).length;
                document.getElementById('loadedCount').textContent = loadedCount;
                
                // بررسی آیا کاربر بیشتری وجود دارد
                if ((data.match(/<tr/g) || []).length < <?php echo $limit; ?>) {
                    hasMore = false;
                }
            } else {
                hasMore = false;
            }
        })
        .catch(error => {
            console.error('Error loading more users:', error);
        })
        .finally(() => {
            isLoading = false;
            document.getElementById('loadingSpinner').style.display = 'none';
        });
}

// اگر تعداد کاربران بارگذاری شده کمتر از کل کاربران باشد، امکان بارگذاری بیشتر وجود دارد
if (loadedCount >= totalUsers) {
    hasMore = false;
    document.getElementById('loadingSpinner').style.display = 'none';
}

// برای بارگذاری اولیه اگر صفحه کوتاه باشد
setTimeout(() => {
    const { scrollTop, scrollHeight, clientHeight } = document.documentElement;
    if (scrollHeight <= clientHeight + 100 && hasMore && !isLoading) {
        loadMoreUsers();
    }
}, 1000);
</script>
</body>
</html>