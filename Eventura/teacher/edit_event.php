<?php
/**
 * Eventura - Teacher Edit Event
 */
require_once '../config.php';
startSecureSession();
requireRole('teacher');

// Include the shared edit_event.php from admin
include '../admin/edit_event.php';
