<?php
/**
 * Eventura - Main Dashboard (Role-based redirect)
 */
require_once 'config.php';
startSecureSession();
requireAuth();

// Redirect based on role
if (hasRole('admin')) {
    redirect(SITE_URL . '/admin/dashboard.php');
} elseif (hasRole('teacher')) {
    redirect(SITE_URL . '/teacher/dashboard.php');
} else {
    redirect(SITE_URL . '/student/dashboard.php');
}
