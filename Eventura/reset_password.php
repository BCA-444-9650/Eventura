<?php
/**
 * Eventura - Reset Password Page
 */
require_once 'config.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard.php');
}

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($token) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill all required fields.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if token exists and is not expired
            $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $error = 'Invalid or expired reset token. Please request a new password reset.';
            } else {
                // Update password and clear reset token
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
                $stmt->execute([$hashed_password, $user['id']]);
                
                $success = 'Password has been reset successfully. You can now login with your new password.';
                
                // Redirect to login after 3 seconds
                header('refresh:3;url=login.php');
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Password reset error: " . $e->getMessage());
        }
    }
} elseif (!empty($token)) {
    // Validate token on page load
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Invalid or expired reset token. Please request a new password reset.';
        }
    } catch (Exception $e) {
        $error = 'An error occurred. Please try again later.';
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
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
                    <i class="fas fa-key"></i>
                </div>
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Reset Your Password</p>
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
            <?php else: ?>
                <?php if (empty($error)): ?>
                    <form class="auth-form" method="POST" action="">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <div class="form-group">
                            <label for="password">
                                <i class="fas fa-lock"></i> New Password
                            </label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   placeholder="Enter new password" required minlength="8">
                            <div class="form-hint">Password must be at least 8 characters long</div>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                   placeholder="Confirm new password" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-key"></i> Reset Password
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="auth-footer">
                <p>Remember your password? <a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Password strength indicator
        document.addEventListener('DOMContentLoaded', function() {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            
            function checkPasswordStrength() {
                const value = password.value;
                let strength = 0;
                
                if (value.length >= 8) strength++;
                if (value.match(/[a-z]/)) strength++;
                if (value.match(/[A-Z]/)) strength++;
                if (value.match(/[0-9]/)) strength++;
                if (value.match(/[^a-zA-Z0-9]/)) strength++;
                
                return strength;
            }
            
            function updatePasswordIndicator() {
                const strength = checkPasswordStrength();
                const indicator = document.createElement('div');
                indicator.className = 'password-strength';
                
                if (strength <= 2) {
                    indicator.textContent = 'Weak';
                    indicator.style.color = '#ef4444';
                } else if (strength <= 3) {
                    indicator.textContent = 'Medium';
                    indicator.style.color = '#f59e0b';
                } else {
                    indicator.textContent = 'Strong';
                    indicator.style.color = '#22c55e';
                }
                
                // Remove existing indicator
                const existing = password.parentNode.querySelector('.password-strength');
                if (existing) existing.remove();
                
                // Add new indicator
                password.parentNode.appendChild(indicator);
            }
            
            password.addEventListener('input', updatePasswordIndicator);
            
            // Check if passwords match
            function checkPasswordMatch() {
                if (confirmPassword.value && password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity('Passwords do not match');
                } else {
                    confirmPassword.setCustomValidity('');
                }
            }
            
            password.addEventListener('input', checkPasswordMatch);
            confirmPassword.addEventListener('input', checkPasswordMatch);
        });
    </script>
    
    <script src="<?php echo ASSETS_URL; ?>js/theme.js"></script>
</body>
</html>
