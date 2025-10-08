<?php
// assets/PhpSpreadsheet/autoload.php

// فایل‌های اصلی
require_once 'Spreadsheet.php';
require_once 'IOFactory.php';

// فایل‌های Shared
require_once 'Shared/File.php';
require_once 'Shared/StringHelper.php';
require_once 'Shared/Date.php';

// فایل‌های Reader
require_once 'Reader/Xlsx.php';
require_once 'Reader/Xls.php';
require_once 'Reader/Csv.php';
require_once 'Reader/Xml.php';
require_once 'Reader/Ods.php';
require_once 'Reader/Slk.php';
require_once 'Reader/Gnumeric.php';
require_once 'Reader/Html.php';

// فایل‌های Writer  
require_once 'Writer/Xlsx.php';
require_once 'Writer/Xls.php';
require_once 'Writer/Csv.php';
require_once 'Writer/Ods.php';
require_once 'Writer/Html.php';

// فایل‌های Cell
require_once 'Cell/Coordinate.php';
require_once 'Cell/Cell.php';

// فایل‌های دیگر
require_once 'Worksheet/Worksheet.php';
require_once 'Worksheet/Row.php';
require_once 'Worksheet/CellIterator.php';
require_once 'Style/Style.php';
require_once 'Calculation/Calculation.php';
?>