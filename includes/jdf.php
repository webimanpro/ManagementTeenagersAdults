<?php
/**
 * Jalali(Jalali) DateTime Class. Supports years higher than 2038.
 *
 * Copyright (c) 2019-2023 Reza Ahmadi
 * Released under the MIT License.
 * @released 2019-10-12
 * @updated 2023-10-10
 * @author Reza Ahmadi <me@rezaahmadi.net>
 * @link https://rezaahmadi.net/programming/php/date-jalali-php/
 * @license MIT
 */

if (!function_exists('jdate')) {
    function jdate($format, $timestamp = '', $timezone = 'Asia/Tehran', $tr_num = 'fa')
    {
        // Default timezone
        if (function_exists('date_default_timezone_get')) {
            // Validate timezone
            $validTimezones = timezone_identifiers_list();
            if (empty($timezone) || !in_array($timezone, $validTimezones)) {
                $timezone = 'Asia/Tehran'; // Fallback to Tehran timezone
            }
            
            // Set the timezone
            date_default_timezone_set($timezone);
        } else {
            putenv("TZ=$timezone");
        }
        // If timestamp is not provided, use current time
        if ($timestamp === '') {
            $timestamp = time();
        }

        // If timestamp is string, convert to integer
        if (is_string($timestamp)) {
            $timestamp = strtotime($timestamp);
        }

        // Create DateTime object from timestamp
        $date = new DateTime();
        $date->setTimestamp($timestamp);

        // Get date parts
        $year = (int)$date->format('Y');
        $month = (int)$date->format('n');
        $day = (int)$date->format('j');
        $hour = (int)$date->format('G');
        $minute = (int)$date->format('i');
        $second = (int)$date->format('s');

        // Convert Gregorian to Jalali
        $date = gregorian_to_jalali($year, $month, $day);
        $year = $date[0];
        $month = $date[1];
        $day = $date[2];

        // Format the date
        $format = str_split($format);
        $output = '';
        
        foreach ($format as $char) {
            switch ($char) {
                // Day
                case 'd':
                    $output .= ($day < 10) ? '0' . $day : $day;
                    break;
                case 'j':
                    $output .= $day;
                    break;
                
                // Month
                case 'm':
                    $output .= ($month < 10) ? '0' . $month : $month;
                    break;
                case 'n':
                    $output .= $month;
                    break;
                case 'F':
                    $output .= jdate_month_name($month);
                    break;
                case 'M':
                    $output .= mb_substr(jdate_month_name($month), 0, 3, 'UTF-8');
                    break;
                
                // Year
                case 'Y':
                    $output .= $year;
                    break;
                case 'y':
                    $output .= substr($year, -2);
                    break;
                
                // Time
                case 'H':
                    $output .= ($hour < 10) ? '0' . $hour : $hour;
                    break;
                case 'i':
                    $output .= ($minute < 10) ? '0' . $minute : $minute;
                    break;
                case 's':
                    $output .= ($second < 10) ? '0' . $second : $second;
                    break;
                
                // Day of week
                case 'l':
                    $output .= jdate_day_name(jdayofweek($year, $month, $day));
                    break;
                case 'D':
                    $output .= mb_substr(jdate_day_name(jdayofweek($year, $month, $day)), 0, 3, 'UTF-8');
                    break;
                
                // Escaped characters
                case '\\':
                    $output .= array_shift($format);
                    break;
                
                default:
                    $output .= $char;
            }
        }

        // Convert numbers to Persian if needed
        if ($tr_num === 'fa') {
            $output = str_replace(
                ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'],
                ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'],
                $output
            );
        }

        return $output;
    }
}

/**
 * Convert Jalali date to Gregorian date
 * Returns array [gy, gm, gd]
 */
 if (!function_exists('jalali_to_gregorian')) {
function jalali_to_gregorian($j_y, $j_m, $j_d)
{
    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $jy = $j_y - 979;
    $jm = $j_m - 1;
    $jd = $j_d - 1;

    $j_day_no = 365 * $jy + (int)($jy / 33) * 8 + (int)((($jy % 33) + 3) / 4);
    for ($i = 0; $i < $jm; ++$i) {
        $j_day_no += $j_days_in_month[$i];
    }
    $j_day_no += $jd;

    $g_day_no = $j_day_no + 79;

    $gy = 1600 + 400 * (int)($g_day_no / 146097);
    $g_day_no %= 146097;

    $leap = true;
    if ($g_day_no >= 36525) {
        $g_day_no--;
        $gy += 100 * (int)($g_day_no / 36524);
        $g_day_no %= 36524;

        if ($g_day_no >= 365) {
            $g_day_no++;
        } else {
            $leap = false;
        }
    }

    $gy += 4 * (int)($g_day_no / 1461);
    $g_day_no %= 1461;

    if ($g_day_no >= 366) {
        $leap = false;
        $g_day_no--;
        $gy += (int)($g_day_no / 365);
        $g_day_no %= 365;
    }

    for ($i = 0; $g_day_no >= $g_days_in_month[$i] + (int)($i == 1 && $leap); $i++) {
        $g_day_no -= $g_days_in_month[$i] + (int)($i == 1 && $leap);
    }
    $gm = $i + 1;
    $gd = $g_day_no + 1;

    return [$gy, $gm, $gd];
}
}
/**
 * Check if a given Jalali year is leap
 */
if (!function_exists('j_is_leap')) {
function j_is_leap($jy)
{
    // Leap years in Jalali calendar occur in a 33-year cycle with leap years at years 1,5,9,13,17,22,26,30
    $breaks = [
        -61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181,
        1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178
    ];
    $leapJ = -14;
    $jp = $breaks[0];

    $j = 1;
    do {
        $jm = $breaks[$j];
        $jump = $jm - $jp;
        if ($jy < $jm) {
            $N = $jy - $jp;
            $leapJ += (int)($N / 33) * 8 + (int)((($N % 33) + 3) / 4);
            $leap = (($N + 1) % 33) - 1;
            if ($leap == -1) $leap = 32;
            return ($leap == 0 || $leap == 4 || $leap == 8 || $leap == 12 || $leap == 16 || $leap == 20 || $leap == 24 || $leap == 28);
        }
        $leapJ += (int)($jump / 33) * 8 + (int)((($jump % 33) + 3) / 4);
        $jp = $jm;
        $j++;
    } while ($j < count($breaks));
    return false;
}
}
/**
 * Convert Gregorian date to Jalali date
 */
 if (!function_exists('gregorian_to_jalali')) {
function gregorian_to_jalali($g_y, $g_m, $g_d)
{
    $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

    $gy = $g_y - 1600;
    $gm = $g_m - 1;
    $gd = $g_d - 1;

    $g_day_no = 365 * $gy + (int)(($gy + 3) / 4) - (int)(($gy + 99) / 100) + (int)(($gy + 399) / 400);
    
    for ($i = 0; $i < $gm; ++$i) {
        $g_day_no += $g_days_in_month[$i];
    }
    
    if ($gm > 1 && (($gy % 4 == 0 && $gy % 100 != 0) || ($gy % 400 == 0))) {
        // Leap year
        $g_day_no++;
    }
    
    $g_day_no += $gd;
    $j_day_no = $g_day_no - 79;
    $j_np = (int)($j_day_no / 12053);
    $j_day_no %= 12053;
    $jy = 979 + 33 * $j_np + 4 * (int)($j_day_no / 1461);
    $j_day_no %= 1461;
    
    if ($j_day_no >= 366) {
        $jy += (int)(($j_day_no - 1) / 365);
        $j_day_no = ($j_day_no - 1) % 365;
    }
    
    for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i) {
        $j_day_no -= $j_days_in_month[$i];
    }
    
    $jm = $i + 1;
    $jd = $j_day_no + 1;
    
    return [$jy, $jm, $jd];
}
}
/**
 * Get Jalali month name
 */
if (!function_exists('jdate_month_name')) {
function jdate_month_name($month)
{
    $months = [
        1 => 'فروردین',
        'اردیبهشت',
        'خرداد',
        'تیر',
        'مرداد',
        'شهریور',
        'مهر',
        'آبان',
        'آذر',
        'دی',
        'بهمن',
        'اسفند'
    ];
    
    return $months[$month] ?? '';
}
}
/**
 * Get day of week (0-6)
 */
if (!function_exists('jdayofweek')) {
function jdayofweek($year, $month, $day)
{
    if ($month < 3) {
        $month += 12;
        $year--;
    }
    
    $a = (int)($year / 100);
    $b = (int)($a / 4);
    $c = 2 - $a + $b;
    $d = (int)(365.25 * ($year + 4716));
    $e = (int)(30.6001 * ($month + 1));
    
    $jd = $c + $d + $e + $day - 1524.5;
    $day_of_week = (int)($jd + 1.5) % 7;
    
    return $day_of_week;
}
}
/**
 * Get Jalali day name
 */
 if (!function_exists('jdate_day_name')) {
function jdate_day_name($day_of_week)
{
    $days = [
        'یکشنبه',
        'دوشنبه',
        'سه‌شنبه',
        'چهارشنبه',
        'پنج‌شنبه',
        'جمعه',
        'شنبه'
    ];
    
    return $days[$day_of_week] ?? '';
}
 }