<?php
/**
 * Cyclone Store — configuration.
 *
 * Change the admin password by generating a new hash:
 *   php -r "echo password_hash('your-new-password', PASSWORD_DEFAULT);"
 * and pasting it into ADMIN_PASSWORD_HASH below.
 */

define('APP_NAME', 'App Store By Cyclone');
define('APP_TAGLINE', 'Your private collection of useful apps, games and tools.');

define('ADMIN_EMAIL', 'admin@cyclone.local');
define('ADMIN_PASSWORD_HASH', '$2y$12$hUnz0rEIS5lCTIqd4ruTr.Fn7Dgc1d2EgmFB64KHgLVwEqER7otIS');

define('DB_PATH', __DIR__ . '/../data/appstore.sqlite');
define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('ICON_DIR', UPLOAD_DIR . '/icons');
define('FILE_DIR', UPLOAD_DIR . '/files');

define('MAX_ICON_MB', 2);    // app icon upload cap
define('MAX_FILE_MB', 25);   // apk/zip cap — keep well inside the 100 MB account quota

// Pretty URLs (/apps/slug). Set to false if Apache mod_rewrite is unavailable.
define('PRETTY_URLS', true);

define('CATEGORIES', ['Tools', 'Games', 'Productivity', 'Music', 'Photo', 'Video', 'Social', 'Education']);
