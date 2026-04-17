<?php
/**
 * Eventura - Complete Profile Page
 * For students to add their academic details
 */
require_once 'config.php';
startSecureSession();

// Check if coming from registration or is logged in
if (!isset($_SESSION['new_user_id']) && !isLoggedIn()) {
    redirect(SITE_URL . '/login.php');
}

// Get user info
if (isset($_SESSION['new_user_id'])) {
    $user_id = $_SESSION['new_user_id'];
    $role = $_SESSION['new_user_role'] ?? 'student';
    $full_name = $_SESSION['new_user_name'] ?? '';
} else {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['user_role'];
    $full_name = $_SESSION['user_name'];
    
    // Check if profile already completed
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            redirect(SITE_URL . '/dashboard.php');
        }
    } catch (Exception $e) {
        error_log("Profile check error: " . $e->getMessage());
    }
}

// Skip for teachers and admins - they don't need student profile
if ($role !== 'student') {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("UPDATE users SET profile_completed = TRUE WHERE id = ?");
        $stmt->execute([$user_id]);
        
        // Set session for logged in users
        if (isLoggedIn()) {
            setFlashMessage('success', 'Profile setup complete!');
            redirect(SITE_URL . '/dashboard.php');
        }
    } catch (Exception $e) {
        error_log("Profile update error: " . $e->getMessage());
    }
}

$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $roll_no = trim($_POST['roll_no'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year = intval($_POST['year'] ?? 0);
    $department = trim($_POST['department'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($student_id) || empty($roll_no) || empty($course) || empty($year)) {
        $error = 'Please fill in all required fields.';
    } else {
        try {
            $pdo = getDBConnection();
            
            // Check for duplicate student_id or roll_no
            $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE student_id = ? OR roll_no = ?");
            $stmt->execute([$student_id, $roll_no]);
            if ($stmt->fetch()) {
                $error = 'Student ID or Roll Number already exists.';
            } else {
                // Insert student profile
                $stmt = $pdo->prepare("INSERT INTO student_profiles 
                    (user_id, student_id, roll_no, course, year, department, phone) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $student_id, $roll_no, $course, $year, $department, $phone]);
                
                // Update user profile status
                $stmt = $pdo->prepare("UPDATE users SET profile_completed = TRUE WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Set session and redirect
                if (!isLoggedIn()) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_email'] = $_SESSION['new_user_email'] ?? '';
                    $_SESSION['user_name'] = $full_name;
                    $_SESSION['user_role'] = $role;
                    
                    unset($_SESSION['new_user_id']);
                    unset($_SESSION['new_user_role']);
                    unset($_SESSION['new_user_name']);
                    unset($_SESSION['new_user_email']);
                }
                
                setFlashMessage('success', 'Profile completed! Welcome to ' . SITE_NAME);
                redirect(SITE_URL . '/dashboard.php');
            }
        } catch (Exception $e) {
            $error = 'An error occurred. Please try again.';
            error_log("Profile completion error: " . $e->getMessage());
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
    <title>Complete Your Profile - <?php echo SITE_NAME; ?></title>
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
                    <i class="fas fa-id-card"></i>
                </div>
                <h1>Complete Your Profile</h1>
                <p>Welcome, <?php echo sanitize($full_name); ?>!</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form class="auth-form" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="student_id">
                            <i class="fas fa-id-badge"></i> Student ID *
                        </label>
                        <input type="text" id="student_id" name="student_id" class="form-control" 
                               placeholder="e.g., STU2024001" required 
                               value="<?php echo isset($_POST['student_id']) ? sanitize($_POST['student_id']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="roll_no">
                            <i class="fas fa-list-ol"></i> Roll Number *
                        </label>
                        <input type="text" id="roll_no" name="roll_no" class="form-control" 
                               placeholder="e.g., 45" required
                               value="<?php echo isset($_POST['roll_no']) ? sanitize($_POST['roll_no']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="course">
                            <i class="fas fa-graduation-cap"></i> Course *
                        </label>
                        <select id="course" name="course" class="form-control" required>
                            <option value="">Select Course</option>
                            <option value="BCA" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BCA') ? 'selected' : ''; ?>>BCA</option>
                            <option value="BBA" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BBA') ? 'selected' : ''; ?>>BBA</option>
                            <option value="B.Com" <?php echo (isset($_POST['course']) && $_POST['course'] == 'B.Com') ? 'selected' : ''; ?>>B.Com</option>
                            <option value="BA" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BA') ? 'selected' : ''; ?>>BA</option>
                            <option value="BSc" <?php echo (isset($_POST['course']) && $_POST['course'] == 'BSc') ? 'selected' : ''; ?>>BSc</option>
                            <option value="MCA" <?php echo (isset($_POST['course']) && $_POST['course'] == 'MCA') ? 'selected' : ''; ?>>MCA</option>
                            <option value="MBA" <?php echo (isset($_POST['course']) && $_POST['course'] == 'MBA') ? 'selected' : ''; ?>>MBA</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="year">
                            <i class="fas fa-calendar"></i> Year *
                        </label>
                        <select id="year" name="year" class="form-control" required>
                            <option value="">Select Year</option>
                            <option value="1" <?php echo (isset($_POST['year']) && $_POST['year'] == '1') ? 'selected' : ''; ?>>1st Year</option>
                            <option value="2" <?php echo (isset($_POST['year']) && $_POST['year'] == '2') ? 'selected' : ''; ?>>2nd Year</option>
                            <option value="3" <?php echo (isset($_POST['year']) && $_POST['year'] == '3') ? 'selected' : ''; ?>>3rd Year</option>
                            <option value="4" <?php echo (isset($_POST['year']) && $_POST['year'] == '4') ? 'selected' : ''; ?>>4th Year</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="department">
                        <i class="fas fa-building"></i> Department
                    </label>
                    <input type="text" id="department" name="department" class="form-control" 
                           placeholder="e.g., Computer Science"
                           value="<?php echo isset($_POST['department']) ? sanitize($_POST['department']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input type="tel" id="phone" name="phone" class="form-control" 
                           placeholder="e.g., +91 9876543210"
                           value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-check-circle"></i> Complete Profile
                </button>
            </form>
        </div>
    </div>
</body>
</html>
