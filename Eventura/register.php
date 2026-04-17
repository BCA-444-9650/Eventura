<?php
/**
 * Eventura - Registration Page
 */
require_once 'config.php';
startSecureSession();

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(SITE_URL . '/dashboard.php');
}

$error = '';
$success = '';

function isValidFullName($full_name) {
    return (bool) preg_match('/^[A-Za-z]+(?: [A-Za-z]+)*$/', $full_name);
}

function isStrongPassword($password) {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password);
}

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'student'; // Force student role for public registration
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validation
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!isValidFullName($full_name)) {
        $error = 'Full name must contain only letters and spaces.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!isStrongPassword($password)) {
        $error = 'Password must be at least 8 characters and include uppercase, lowercase, and a number.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email already registered. Please login instead.';
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, role, auth_type) VALUES (?, ?, ?, ?, 'email')");
                $stmt->execute([$email, $hashed_password, $full_name, $role]);
                
                $user_id = $pdo->lastInsertId();
                
                // Set session for profile completion
                $_SESSION['new_user_id'] = $user_id;
                $_SESSION['new_user_role'] = $role;
                $_SESSION['new_user_name'] = $full_name;
                $_SESSION['new_user_email'] = $email;
                
                $success = 'Registration successful! Please complete your profile.';
                
                // Redirect to profile completion after short delay
                header("Refresh: 1; URL=" . SITE_URL . '/complete_profile.php');
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again later.';
            error_log("Registration error: " . $e->getMessage());
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
    <title>Register - <?php echo SITE_NAME; ?></title>
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
                <h1>Create Account</h1>
                <p>Join <?php echo SITE_NAME; ?> today</p>
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
                    <div class="loading-spinner"></div>
                </div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="" <?php echo $success ? 'style="display:none;"' : ''; ?>>
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           placeholder="Enter your full name" required maxlength="60"
                           pattern="[A-Za-z]+(?: [A-Za-z]+)*"
                           title="Name can contain only letters and spaces."
                           value="<?php echo isset($_POST['full_name']) ? sanitize($_POST['full_name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email" required maxlength="120"
                           value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="Create a password" required minlength="8" maxlength="72"
                               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}"
                               title="Password must be at least 8 characters and include uppercase, lowercase, and a number.">
                        <button type="button" class="toggle-password" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small class="help-text">Use at least 8 characters with uppercase, lowercase, and a number</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Confirm your password" required maxlength="72">
                        <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <?php if (!empty(GOOGLE_CLIENT_ID) && empty($success)): ?>
            <div class="social-divider">
                <span>or sign up with</span>
            </div>
            
            <a href="auth/google_login.php" class="btn btn-google btn-block">
                <i class="fab fa-google"></i> Sign up with Google
            </a>
            <?php endif; ?>
            
            <div class="auth-footer" <?php echo $success ? 'style="display:none;"' : ''; ?>>
                <p>Already have an account? <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
    
    <script>
    const registerForm = document.querySelector('.auth-form');
    const fullNameInput = document.getElementById('full_name');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');

    fullNameInput.addEventListener('input', function () {
        const isValid = /^[A-Za-z]+(?: [A-Za-z]+)*$/.test(this.value.trim());
        this.setCustomValidity(this.value && !isValid ? 'Name can contain only letters and spaces.' : '');
    });

    passwordInput.addEventListener('input', function () {
        const isValid = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(this.value);
        this.setCustomValidity(isValid ? '' : 'Password must be at least 8 characters and include uppercase, lowercase, and a number.');
    });

    confirmPasswordInput.addEventListener('input', function () {
        this.setCustomValidity(this.value !== passwordInput.value ? 'Passwords do not match.' : '');
    });

    passwordInput.addEventListener('input', function () {
        confirmPasswordInput.setCustomValidity(
            confirmPasswordInput.value && confirmPasswordInput.value !== this.value ? 'Passwords do not match.' : ''
        );
    });

    registerForm.addEventListener('submit', function (event) {
        fullNameInput.value = fullNameInput.value.trim().replace(/\s+/g, ' ');

        if (!this.checkValidity()) {
            event.preventDefault();
            this.reportValidity();
        }
    });

    function togglePassword(fieldId) {
        const passwordInput = document.getElementById(fieldId);
        const icon = document.querySelector(`#${fieldId} + .toggle-password i, #${fieldId} ~ .toggle-password i`);
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    </script>
</body>
</html>
