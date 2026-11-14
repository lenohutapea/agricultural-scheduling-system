<?php
declare(strict_types=1);

require __DIR__ . '/../config.php';

date_default_timezone_set(APP_TIMEZONE);

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

require __DIR__ . '/db.php';
require __DIR__ . '/functions.php';
require __DIR__ . '/jadwal.php';
require __DIR__ . '/layout.php';
