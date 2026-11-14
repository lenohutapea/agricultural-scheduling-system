<?php
// Database and app settings.
//
// Values come from environment variables (used by Docker and CI), then fall back
// to the defaults below, which match a fresh XAMPP install (user "root", no password).
//
// To run locally with different credentials without editing this file, create
// config.local.php (ignored by git), for example:
//
//   <?php
//   define('DB_USER', 'pertanian');
//   define('DB_PASS', 'your-password');

if (is_file(__DIR__ . '/config.local.php')) {
    require __DIR__ . '/config.local.php';
}

$env = static function (string $key, string $default): string {
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
};

defined('DB_HOST') || define('DB_HOST', $env('DB_HOST', '127.0.0.1'));
defined('DB_PORT') || define('DB_PORT', (int)$env('DB_PORT', '3306'));
defined('DB_NAME') || define('DB_NAME', $env('DB_NAME', 'pertanian'));
defined('DB_USER') || define('DB_USER', $env('DB_USER', 'root'));
defined('DB_PASS') || define('DB_PASS', $env('DB_PASS', ''));

defined('APP_NAME') || define('APP_NAME', 'Sistem Penjadwalan Pertanian');
defined('APP_TIMEZONE') || define('APP_TIMEZONE', $env('APP_TIMEZONE', 'Asia/Jakarta'));

unset($env);
