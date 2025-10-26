<?php
// Include the jdf (Jalali Date) library if not already included
if (!function_exists('jdate')) {
    @include_once 'jdf.php';
}

// Get current date and time (server/system time)
$now = new DateTime('now', new DateTimeZone('Asia/Tehran'));

// Helper: pad 2 digits
$pad2 = function($n){ return str_pad((string)$n, 2, '0', STR_PAD_LEFT); };

// Build dates in requested format
$gregorian_date = $now->format('Y/m/d');
$time = $now->format('H:i:s');

// Build Jalali date string (1404/07/08 format)
if (function_exists('jdate')) {
    $jalali_date = jdate('Y/m/d', $now->getTimestamp());
} else {
    // Fallback conversion
    function gregorian_to_jalali($gy, $gm, $gd) {
        $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
        $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
        $days = 355666 + (365 * $gy) + (int)(($gy2 + 3) / 4) - (int)(($gy2 + 99) / 100) + (int)(($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
        $jy = -1595 + (33 * (int)($days / 12053));
        $days %= 12053;
        $jy += 4 * (int)($days / 1461);
        $days %= 1461;
        if ($days > 365) { 
            $jy += (int)(($days - 1) / 365); 
            $days = ($days - 1) % 365; 
        }
        if ($days < 186) { 
            $jm = 1 + (int)($days / 31); 
            $jd = 1 + ($days % 31); 
        } else { 
            $jm = 7 + (int)(($days - 186) / 30); 
            $jd = 1 + (($days - 186) % 30); 
        }
        return [$jy, $jm, $jd];
    }
    
    [$jy, $jm, $jd] = gregorian_to_jalali((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
    $jalali_date = $jy . '/' . $pad2($jm) . '/' . $pad2($jd);
}

// Build Hijri date string (1447/04/07 format)
if (!function_exists('gregorian_to_hijri')) {
    function gregorian_to_hijri($gy, $gm, $gd) {
        if (function_exists('cal_to_jd')) {
            $jd = cal_to_jd(CAL_GREGORIAN, $gm, $gd, $gy);
        } else {
            // Basic JD fallback
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

[$hy, $hm, $hd] = gregorian_to_hijri((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
$hijri_date = $hy . '/' . $pad2($hm) . '/' . $pad2($hd);
?>

<div class="datetime-widget">
    <!-- Time -->
    <div class="datetime-item time">
        <i class="fas fa-clock"></i>
        <span class="time-value"><?php echo $time; ?></span>
    </div>
    
    <!-- Persian Date -->
    <div class="datetime-item">
        <i class="fas fa-sun"></i>
        <span class="date-value">شمسی: <?php echo $jalali_date; ?></span>
    </div>
    
    <!-- Islamic Date -->
    <div class="datetime-item">
        <i class="fas fa-moon"></i>
        <span class="date-value">قمری: <?php echo $hijri_date; ?></span>
    </div>
    
    <!-- Gregorian Date -->
    <div class="datetime-item">
        <i class="fas fa-calendar-alt"></i>
        <span class="date-value">میلادی: <?php echo $gregorian_date; ?></span>
    </div>

    <!-- Calendar Button -->
    <div class="datetime-item">
        <a href="/includes/calendar.php" class="btn-calendar">
            <i class="fas fa-calendar-week"></i>
            <span>مشاهده تقویم</span>
        </a>
    </div>
</div>

<style>
.datetime-widget {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    padding: 15px;
    margin: 0;
    font-family: 'Sahel', Tahoma, sans-serif;
    width: 240px;
    position: fixed;
    top: 80px;
    left: 15px;
    z-index: 1000;
    line-height: 1.3;
    text-align: center;
    border: 1px solid #e9ecef;
}

.datetime-item {
    color: #333;
    font-size: 1em;
    padding: 8px 0;
    margin: 3px 0;
    display: flex;
    justify-content: center;
    align-items: center;
    border-bottom: 1px solid #f1f3f5;
    transition: all 0.2s ease;
}

.datetime-item:hover {
    transform: translateX(-3px);
}

.datetime-item:last-child {
    border-bottom: none;
    padding-top: 10px;
}

.datetime-item i {
    margin-left: 8px;
    color: #4a6cf7;
    font-size: 1em;
    width: 18px;
    text-align: center;
}

.time-value {
    font-weight: 700;
    font-size: 1.2em;
    color: #4a6cf7;
    direction: ltr;
    display: block;
}

.date-value {
    font-weight: 600;
    color: #495057;
    direction: rtl;
    font-size: 1em;
}

.btn-calendar {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #4a6cf7;
    color: white;
    padding: 8px 12px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    font-size: 0.8em;
}

.btn-calendar:hover {
    background: #3a5bd9;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(74, 108, 247, 0.3);
}

.btn-calendar i {
    margin: 0;
    font-size: 0.9em;
    color: white;
    transition: transform 0.3s ease;
}

.btn-calendar:hover i {
    transform: scale(1.1);
}

/* Responsive */
@media (max-width: 768px) {
    .datetime-widget {
        position: relative;
        top: auto;
        left: auto;
        width: 100%;
        margin: 10px 0;
        max-width: 280px;
    }
}
</style>

<script>
// Update time every second
function updateTime() {
    const now = new Date();
    const timeElement = document.querySelector('.datetime-widget .time-value');
    if (timeElement) {
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        timeElement.textContent = `${hours}:${minutes}:${seconds}`;
    }
}

// Update time immediately and then every second
updateTime();
setInterval(updateTime, 1000);
</script>