<?php
/**
 * Eventura - Teacher View Event (Link to Admin Version)
 */
require_once '../config.php';
startSecureSession();
requireRole('teacher');

// Include the shared view_event.php from admin
include '../admin/view_event.php';
