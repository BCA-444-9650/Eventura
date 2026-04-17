<?php
/**
 * Eventura - Teacher Create Event (Link to Admin Version)
 */
require_once '../config.php';
startSecureSession();
requireRole('teacher');

// Include the shared create_event.php from admin
include '../admin/create_event.php';
?>
