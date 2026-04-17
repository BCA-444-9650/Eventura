<?php
/**
 * Eventura - Forgot Password Page
 */
require_once 'config.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard.php');
}

$error = '';
$success = '';

// Handle forgot password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ? AND is_active = TRUE");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $reset_expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Save reset token
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$reset_token, $reset_expires, $user['id']]);
                
                // In a real application, you would send an email here
                // For now, we'll just show success message
                $success = 'Password reset instructions have been sent to your email address.';
                
                // Log the reset token for development (remove in production)
                error_log("Password reset token for $email: " . SITE_URL . "/reset_password.php?token=" . $reset_token);
            } else {
                // Don't reveal if email exists or not for security
                $success = 'If an account with that email exists, password reset instructions have been sent.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Forgot password error: " . $e->getMessage());
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card clay-card">
            <div class="auth-logo">
                <div class="logo-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Smart Event Management System</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email address" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-paper-plane"></i> Send Reset Instructions
                </button>
            </form>
            
            <div class="auth-footer">
                <p>Remember your password? <a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>js/theme.js"></script>
</body>
</html>
