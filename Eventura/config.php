<?php
/**
 * Eventura - Main Configuration
 * Application-wide settings and constants
 */

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('Asia/Kolkata'); // Adjust as needed

// Site Configuration
define('SITE_NAME', 'Eventura');
define('SITE_URL', 'http://localhost/Eventura'); // Change in production
define('ADMIN_EMAIL', 'admin@eventura.com');

// Paths
define('BASE_PATH', __DIR__ . '/');
define('UPLOADS_PATH', BASE_PATH . 'uploads/');
define('QR_PATH', UPLOADS_PATH . 'qr_codes/');
define('INCLUDES_PATH', BASE_PATH . 'includes/');

// URLs
define('ASSETS_URL', SITE_URL . '/assets/');
define('UPLOADS_URL', SITE_URL . '/uploads/');
define('QR_URL', SITE_URL . '/uploads/qr_codes/');

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_LIFETIME', 3600); // 1 hour

// Google OAuth Settings (fill these in OR use Admin Settings panel)
// define('GOOGLE_CLIENT_ID', '');
// define('GOOGLE_CLIENT_SECRET', '');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google_callback.php');

// Email Settings (SMTP) - fill these in OR use Admin Settings panel
// define('SMTP_HOST', 'smtp.gmail.com');
// define('SMTP_PORT', 587);
// define('SMTP_USERNAME', '');
// define('SMTP_PASSWORD', '');
define('SMTP_FROM_EMAIL', 'noreply@eventura.com');
define('SMTP_FROM_NAME', 'Eventura');

// QR Code Settings
define('QR_CODE_SIZE', 300);
define('QR_CODE_MARGIN', 10);

// Auto-create directories
if (!file_exists(UPLOADS_PATH)) mkdir(UPLOADS_PATH, 0755, true);
if (!file_exists(QR_PATH)) mkdir(QR_PATH, 0755, true);

// Include database configuration
require_once __DIR__ . '/config/database.php';

/**
 * Load dynamic settings from database
 * This allows admin panel settings to override config constants
 */
function loadDynamicSettings() {
    try {
        // Check if system_settings table exists before querying
        $pdo = getDBConnection();
        $stmt = $pdo->query("SHOW TABLES LIKE 'system_settings'");
        $table_exists = $stmt->rowCount() > 0;
        
        if ($table_exists) {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            // Override Google OAuth settings if present in database and not already defined
            if (!empty($settings['google_client_id'])) {
                define('GOOGLE_CLIENT_ID', $settings['google_client_id']);
            }
            if (!empty($settings['google_client_secret'])) {
                define('GOOGLE_CLIENT_SECRET', $settings['google_client_secret']);
            }
            
            // Override SMTP settings if present and not already defined
            if (!empty($settings['smtp_host'])) {
                define('SMTP_HOST', $settings['smtp_host']);
            }
            if (!empty($settings['smtp_port'])) {
                define('SMTP_PORT', (int)$settings['smtp_port']);
            }
            if (!empty($settings['smtp_username'])) {
                define('SMTP_USERNAME', $settings['smtp_username']);
            }
            if (!empty($settings['smtp_password'])) {
                define('SMTP_PASSWORD', $settings['smtp_password']);
            }
        }
        
        // Override site settings (removed unused dynamic constants)
        
    } catch (Exception $e) {
        // Silently fail - config constants will be used as defaults
        error_log("Failed to load dynamic settings: " . $e->getMessage());
    }
}

// Load dynamic settings from database FIRST
loadDynamicSettings();

// Google OAuth Settings (only define if not already set by database)
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', '');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', '');
}
if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google_callback.php');
}

// Email Settings (SMTP) - only define if not already set by database
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', 'smtp.gmail.com');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', 587);
}
if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', '');
}
if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', '');
}
if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', 'noreply@eventura.com');
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', 'Eventura');
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Redirect helper
 */
function redirect($path) {
    header("Location: " . $path);
    exit();
}

/**
 * Flash message helper
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check user role
 */
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page.');
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireAuth();
    if (!hasRole($role)) {
        setFlashMessage('error', 'You do not have permission to access this page.');
        redirect(SITE_URL . '/dashboard.php');
    }
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Format date
 */
function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

/**
 * Format time
 */
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

/**
 * Generate unique ID
 */
function generateUniqueId($prefix = '') {
    return $prefix . uniqid() . bin2hex(random_bytes(4));
}
