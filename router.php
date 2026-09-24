<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;

// ၁။ ဖိုင်အဖြစ် တကယ်ရှိနေပါက (admin.php, admin-login.php, tools/seed.php, assets စသည်)
if ($uri !== '/' && is_file($file)) {
    return false;
}

// ၂။ .php Extension မပါဘဲ ခေါ်ဆိုပါက (ဥပမာ /admin သို့မဟုတ် /admin-login)
if (is_file($file . '.php')) {
    require $file . '.php';
    exit;
}

// ၃။ သီးသန့် အခြားမရှိသော လမ်းကြောင်းများကိုသာ index.php သို့ ပို့မည်
require __DIR__ . '/index.php';
