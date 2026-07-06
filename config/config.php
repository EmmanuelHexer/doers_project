<?php
// config/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('APP_NAME', 'USTED-K Gym Center');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'http://localhost/deep');

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MEMBER_UPLOAD_PATH', UPLOAD_PATH . 'members/');

define('SESSION_TIMEOUT', 3600);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900);

date_default_timezone_set('Asia/Manila');