<?php
/**
 * Eventura - Logout Handler
 */
require_once 'config.php';
startSecureSession();

// Clear all session data
$_SESSION = [];

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

// Destroy session
session_destroy();

// Redirect to login
redirect(SITE_URL . '/login.php');
