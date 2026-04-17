<?php
/**
 * Eventura - Teacher QR Scanner (Link to Admin Scanner)
 */
require_once '../config.php';
startSecureSession();
requireRole('teacher');

// Use the same scanner as admin
include '../admin/qr_scanner.php';
