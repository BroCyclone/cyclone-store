<?php
// router.php - PHP built-in server အတွက် Routing များကို စီမံပေးသည့် script

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Requested တောင်းဆိုထားသော static file (CSS, JS, Image စသည်) ရှိလျှင် တိုက်ရိုက် ပြသမည်
if ($uri !== '/' && file_exists(__DIR__ . $uri) && !is_dir(__DIR__ . $uri)) {
    return false;
}

// ကျန် Route တောင်းဆိုမှု အားလုံးကို index.php သို့ လွှဲပေးမည်
require_once __DIR__ . '/index.php';

