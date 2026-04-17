<?php
/**
 * Eventura - Google OAuth Callback Handler
 */
require_once '../config.php';
startSecureSession();

if (empty(GOOGLE_CLIENT_ID) || empty(GOOGLE_CLIENT_SECRET)) {
    setFlashMessage('error', 'Google login is not configured.');
    redirect(SITE_URL . '/login.php');
}

// Check for error
if (isset($_GET['error'])) {
    setFlashMessage('error', 'Google authentication failed. Please try again.');
    redirect(SITE_URL . '/login.php');
}

// Get authorization code
$code = $_GET['code'] ?? '';

if (empty($code)) {
    setFlashMessage('error', 'Invalid response from Google.');
    redirect(SITE_URL . '/login.php');
}

// Exchange code for access token
$token_url = 'https://oauth2.googleapis.com/token';
$data = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$token_data = json_decode($response, true);

if (!isset($token_data['access_token'])) {
    setFlashMessage('error', 'Failed to authenticate with Google.');
    redirect(SITE_URL . '/login.php');
}

// Get user info from Google
$userinfo_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userinfo_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token_data['access_token']]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$userinfo = curl_exec($ch);
curl_close($ch);

$google_user = json_decode($userinfo, true);

if (!isset($google_user['email'])) {
    setFlashMessage('error', 'Failed to get user information from Google.');
    redirect(SITE_URL . '/login.php');
}

$email = $google_user['email'];
$full_name = $google_user['name'] ?? '';
$google_id = $google_user['id'] ?? '';

try {
    $pdo = getDBConnection();
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR google_id = ?");
    $stmt->execute([$email, $google_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        // User exists - update Google ID if not set
        if (empty($user['google_id'])) {
            $stmt = $pdo->prepare("UPDATE users SET google_id = ? WHERE id = ?");
            $stmt->execute([$google_id, $user['id']]);
        }
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['auth_type'] = 'google';
        
        // Update last login
        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        setFlashMessage('success', 'Welcome back, ' . $user['full_name'] . '!');
        
        if (!$user['profile_completed']) {
            redirect(SITE_URL . '/complete_profile.php');
        }
        
        redirect(SITE_URL . '/dashboard.php');
    } else {
        // New user - create account
        $stmt = $pdo->prepare("INSERT INTO users (email, full_name, google_id, role, auth_type, password) 
                              VALUES (?, ?, ?, 'student', 'google', NULL)");
        $stmt->execute([$email, $full_name, $google_id]);
        
        $user_id = $pdo->lastInsertId();
        
        // Set session for profile completion
        $_SESSION['new_user_id'] = $user_id;
        $_SESSION['new_user_role'] = 'student';
        $_SESSION['new_user_name'] = $full_name;
        $_SESSION['new_user_email'] = $email;
        
        setFlashMessage('success', 'Welcome! Please complete your profile.');
        redirect(SITE_URL . '/complete_profile.php');
    }
    
} catch (Exception $e) {
    setFlashMessage('error', 'An error occurred. Please try again.');
    error_log("Google OAuth error: " . $e->getMessage());
    redirect(SITE_URL . '/login.php');
}
