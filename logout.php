<?php
// ============================================================
// logout.php - COMPLETE LOGOUT HANDLER
// ============================================================
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Destroy session
session_destroy();

// Clear remember me cookie
setcookie('member_remember', '', time() - 3600, '/');

// Redirect to home page
header('Location: index.php');
exit;
?>