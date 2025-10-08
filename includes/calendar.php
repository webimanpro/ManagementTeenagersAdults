<?php
// Start session and basic includes
if (session_status() === PHP_SESSION_NONE) { 
    @session_start();
}
ob_start(); // Start output buffering

// Include required files
require_once 'jdf.php';

// Set timezone
$tz = new DateTimeZone('Asia/Tehran');
$now = new DateTime('now', $tz);

// Helper: Build Jalali month grid data
function build_jalali_month($jy, $jm) {
    // Days in each Jalali month
    $j_days_in_month = [31,31,31,31,31,31,30,30,30,30,30,29];
    
    // Check for leap year in Esfand
    if ($jm === 12 && function_exists('j_is_leap') && j_is_leap($jy)) { 
        $j_days_in_month[11] = 30; 
    }

    // Convert first day of this Jalali month to Gregorian to get weekday
    [$gy, $gm, $gd] = jalali_to_gregorian($jy, $jm, 1);
    $dt = DateTime::createFromFormat('Y-n-j', "$gy-$gm-$gd", new DateTimeZone('Asia/Tehran'));
    if (!$dt) {
        $dt = new DateTime('now', new DateTimeZone('Asia/Tehran'));
    }
    
    $weekday = (int)$dt->format('w'); // 0=Sun .. 6=Sat
    // Convert to index 0..6 where 0=Sat (شنبه)
    $start_index = ($weekday + 1) % 7;

    $days = $j_days_in_month[$jm-1];
    $cells = [];
    
    // Empty cells for days before the start of month
    for ($i = 0; $i < $start_index; $i++) { 
        $cells[] = null; 
    }
    
    // Days of the month
    for ($d = 1; $d <= $days; $d++) { 
        $cells[] = $d; 
    }
    
    return $cells;
}

// Helper: Build Gregorian month grid data
function build_gregorian_month($gy, $gm) {
    $first = DateTime::createFromFormat('Y-n-j', "$gy-$gm-1", new DateTimeZone('Asia/Tehran'));
    if (!$first) {
        $first = new DateTime('now', new DateTimeZone('Asia/Tehran'));
    }
    
    $weekday = (int)$first->format('w'); // 0=Sun .. 6=Sat
    $start_index = ($weekday + 1) % 7; // shift so Saturday=0
    $days = (int)$first->format('t');
    
    $cells = [];
    for ($i = 0; $i < $start_index; $i++) { 
        $cells[] = null; 
    }
    for ($d = 1; $d <= $days; $d++) { 
        $cells[] = $d; 
    }
    
    return $cells;
}

// Helper: Build Hijri month grid
function build_hijri_month($dt, $tz) {
    $cells = [];
    $hy = 0;
    $hm = 0;
    
    if (class_exists('IntlDateFormatter')) {
        try {
            // Use current Gregorian date to determine Hijri year/month
            $fmtYear = new IntlDateFormatter('fa_IR@calendar=islamic', IntlDateFormatter::FULL, IntlDateFormatter::NONE, $tz->getName(), IntlDateFormatter::TRADITIONAL, 'yyyy');
            $fmtMonth = new IntlDateFormatter('fa_IR@calendar=islamic', IntlDateFormatter::FULL, IntlDateFormatter::NONE, $tz->getName(), IntlDateFormatter::TRADITIONAL, 'M');
            
            $ts = $dt->getTimestamp();
            $hy = (int)$fmtYear->format($ts);
            $hm = (int)$fmtMonth->format($ts);

            // Find first day of Hijri month
            $probe = clone $dt;
            for ($i = 0; $i < 35; $i++) {
                $dNum = (int)(new IntlDateFormatter('en_US@calendar=islamic', 0, 0, $tz->getName(), IntlDateFormatter::TRADITIONAL, 'd'))->format($probe);
                if ($dNum === 1) { 
                    break; 
                }
                $probe->modify('-1 day');
            }
            
            $fmtFirstDayW = new IntlDateFormatter('en_US@calendar=islamic', 0, 0, $tz->getName(), IntlDateFormatter::TRADITIONAL, 'e');
            $startWeekIdx = ((int)$fmtFirstDayW->format($probe) + 5) % 7;

            // Build calendar cells
            for ($i = 0; $i < $startWeekIdx; $i++) { 
                $cells[] = null; 
            }
            
            $roll = clone $probe;
            $curMonth = (int)$fmtMonth->format($roll);
            
            while (true) {
                $dayNum = (int)(new IntlDateFormatter('en_US@calendar=islamic', 0, 0, $tz->getName(), IntlDateFormatter::TRADITIONAL, 'd'))->format($roll);
                $cells[] = $dayNum;
                $roll->modify('+1 day');
                $m2 = (int)$fmtMonth->format($roll);
                if ($m2 !== $curMonth) break;
            }
            
        } catch (Exception $e) {
            // Fallback if Intl fails
            $cells = [];
        }
    }
    
    // Fallback: simple 30-day grid
    if (empty($cells)) {
        $weekday = 0;
        $cells = array_fill(0, $weekday, null);
        for ($d = 1; $d <= 30; $d++) { 
            $cells[] = $d; 
        }
        $hy = 1446;
        $hm = 4;
    }
    
    return [$cells, $hy, $hm];
}

// Prepare current months
$gy = (int)$now->format('Y');
$gm = (int)$now->format('n');
$gd = (int)$now->format('j');

// Convert to Jalali
[$jy, $jm, $jd] = gregorian_to_jalali($gy, $gm, $gd);

// Build calendar grids
$jalaliCells = build_jalali_month($jy, $jm);
$gregorianCells = build_gregorian_month($gy, $gm);
[$hijriCells, $hy, $hm] = build_hijri_month($now, $tz);

// Week day names
$persianWeek = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج']; // شنبه تا جمعه
$gregWeek = ['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
$hijriWeek = ['سبت', 'احد', 'اثنين', 'ثلاثاء', 'اربعاء', 'خميس', 'جمعه'];

// Determine today's Hijri day number for highlighting
$hijriToday = null;
if (class_exists('IntlDateFormatter')) {
    try {
        $fmtHijriDay = new IntlDateFormatter('en_US@calendar=islamic', IntlDateFormatter::FULL, IntlDateFormatter::NONE, $tz->getName(), IntlDateFormatter::TRADITIONAL, 'd');
        $hijriToday = (int)$fmtHijriDay->format($now);
    } catch (Exception $e) {
        $hijriToday = null;
    }
}

// Fallback Hijri conversion
if ($hijriToday === null) {
    if (!function_exists('gregorian_to_hijri')) {
        function gregorian_to_hijri($gy, $gm, $gd) {
            if (function_exists('cal_to_jd')) {
                $jd = cal_to_jd(CAL_GREGORIAN, $gm, $gd, $gy);
            } else {
                $a = (int)((14 - $gm) / 12);
                $y = $gy + 4800 - $a;
                $m = $gm + 12 * $a - 3;
                $jd = $gd + (int)((153 * $m + 2) / 5) + 365 * $y + (int)($y / 4) - (int)($y / 100) + (int)($y / 400) - 32045;
            }
            $l = $jd - 1948440 + 10632;
            $n = (int)(($l - 1) / 10631);
            $l = $l - 10631 * $n + 354;
            $j = (int)((10985 - $l) / 5316) * (int)((50 * $l) / 17719) + (int)($l / 5670) * (int)((43 * $l) / 15238);
            $l = $l - (int)((30 - $j) / 15) * (int)((17719 * $j) / 50) - (int)($j / 16) * (int)((15238 * $j) / 43) + 29;
            $m = (int)((24 * $l) / 709);
            $d = $l - (int)((709 * $m) / 24);
            $y = 30 * $n + $j - 30;
            return [$y, $m, $d];
        }
    }
    [$hy2, $hm2, $hd2] = gregorian_to_hijri($gy, $gm, $gd);
    $hijriToday = (int)$hd2;
}

// Now include header after all processing
require_once 'header.php';
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>تقویم - سیستم مدیریت</title>
    <link href="../assets/css/bootstrap.rtl.min.css" rel="stylesheet" />
    <link href="../assets/css/font-awesome.min.css" rel="stylesheet" />
    <link href="../assets/css/style.css" rel="stylesheet" />
    <style>
        .calendar-wrapper {
            margin-top: 100px;
            margin-bottom: 50px;
        }
        
        .calendar-grids {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .calendar-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .calendar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.15);
        }
        
        .calendar-card h3 {
            background: linear-gradient(135deg, #4a6cf7, #6a11cb);
            color: white;
            margin: 0;
            padding: 15px;
            font-size: 1.1rem;
            text-align: center;
            font-weight: 600;
        }
        
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            padding: 15px;
            background: #f8f9fa;
        }
        
        .calendar-grid .cell {
            padding: 12px 5px;
            text-align: center;
            border-radius: 8px;
            font-size: 0.9rem;
            min-height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .calendar-grid .head {
            background: #4a6cf7;
            color: white;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        .calendar-grid .day {
            background: white;
            border: 1px solid #e9ecef;
            cursor: pointer;
        }
        
        .calendar-grid .day:hover {
            background: #e7f1ff;
            border-color: #4a6cf7;
        }
        
        .calendar-grid .today {
            background: #4a6cf7 !important;
            color: white !important;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(74, 108, 247, 0.4);
            transform: scale(1.05);
        }
        
        .calendar-grid .cell:not(.head):not(.day) {
            background: transparent;
            border: none;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .page-header h1 {
            margin: 0;
            font-size: 2.2rem;
            font-weight: 700;
        }
        
        .page-header .lead {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-top: 10px;
        }
        
        @media (max-width: 768px) {
            .calendar-grids {
                grid-template-columns: 1fr;
            }
            
            .calendar-wrapper {
                margin-top: 80px;
                padding: 10px;
            }
            
            .page-header h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container calendar-wrapper container">
        <div class="content-box">
            <a href="../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-right"></i> بستن<span aria-hidden="true" class="fs-5">×</span>
            </a> 
        <div class="page-header">
            <h1><i class="fas fa-calendar-alt"></i> تقویم امروز</h1>
            <p class="lead">نمایش همزمان تقویم شمسی، قمری و میلادی</p>
        </div>
        
        <div class="calendar-grids">
            <!-- Jalali Calendar -->
            <div class="calendar-card">
                <h3>
                    <i class="fas fa-sun"></i> 
                    تقویم شمسی - 
                    <?php 
                    $persianMonths = ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'];
                    echo $persianMonths[$jm-1] . ' ' . $jy;
                    ?>
                </h3>
                <div class="calendar-grid">
                    <?php foreach ($persianWeek as $w): ?>
                        <div class="cell head"><?php echo $w; ?></div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($jalaliCells as $d): ?>
                        <?php if ($d === null): ?>
                            <div class="cell"></div>
                        <?php else: ?>
                            <?php 
                            $isToday = ($d === $jd);
                            $cls = 'cell day' . ($isToday ? ' today' : '');
                            ?>
                            <div class="<?php echo $cls; ?>"><?php echo $d; ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Hijri Calendar -->
            <div class="calendar-card">
                <h3>
                    <i class="fas fa-moon"></i> 
                    <?php
                    $hTitle = 'تقویم قمری';
                    $hijriMonths = ['محرم','صفر','ربيع الأول','ربيع الآخر','جمادى الأولى','جمادى الآخرة','رجب','شعبان','رمضان','شوال','ذو القعدة','ذو الحجة'];
                    $hmIndex = max(1, min(12, (int)$hm));
                    $hTitle .= ' - ' . $hijriMonths[$hmIndex-1] . ' ' . (int)$hy;
                    echo $hTitle;
                    ?>
                </h3>
                <div class="calendar-grid">
                    <?php foreach ($hijriWeek as $w): ?>
                        <div class="cell head"><?php echo $w; ?></div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($hijriCells as $d): ?>
                        <?php if ($d === null): ?>
                            <div class="cell"></div>
                        <?php else: ?>
                            <?php 
                            $isTodayHijri = ($hijriToday !== null && $d === $hijriToday);
                            $cls = 'cell day' . ($isTodayHijri ? ' today' : '');
                            ?>
                            <div class="<?php echo $cls; ?>"><?php echo $d; ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Gregorian Calendar -->
            <div class="calendar-card">
                <h3>
                    <i class="fas fa-calendar-alt"></i> 
                    <?php
                    $gregMonths = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                    echo 'تقویم میلادی - ' . $gregMonths[$gm-1] . ' ' . $gy;
                    ?>
                </h3>
                <div class="calendar-grid">
                    <?php foreach ($gregWeek as $w): ?>
                        <div class="cell head"><?php echo $w; ?></div>
                    <?php endforeach; ?>
                    
                    <?php foreach ($gregorianCells as $d): ?>
                        <?php if ($d === null): ?>
                            <div class="cell"></div>
                        <?php else: ?>
                            <?php 
                            $isToday = ($d === $gd);
                            $cls = 'cell day' . ($isToday ? ' today' : '');
                            ?>
                            <div class="<?php echo $cls; ?>"><?php echo $d; ?></div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
		</div>
    </div>

    <?php include 'footer.php'; ?>
    
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php ob_end_flush(); // End output buffering and flush ?>