<?php
/**
 * Eventura - Main Entry Point (Redirect to login)
 */
require_once 'config.php';
startSecureSession();

if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard.php');
} else {
    redirect(SITE_URL . '/login.php');
}
