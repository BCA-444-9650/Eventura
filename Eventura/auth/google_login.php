<?php
/**
 * Eventura - Google OAuth Login Handler
 */
require_once '../config.php';
startSecureSession();

// Check if Google OAuth is configured
if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    setFlashMessage('error', 'Google login is not configured.');
    redirect(SITE_URL . '/login.php');
}

// Google OAuth URL
$google_oauth_url = 'https://accounts.google.com/o/oauth2/v2/auth';
$scope = 'email profile';

// Build the authorization URL
$params = [
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => $scope,
    'access_type' => 'offline',
    'prompt' => 'consent'
];

$auth_url = $google_oauth_url . '?' . http_build_query($params);

// Redirect to Google
redirect($auth_url);
