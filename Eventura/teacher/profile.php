<?php
/**
 * Eventura - Teacher Profile Page
 */
require_once '../config.php';
startSecureSession();
requireRole('teacher');

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    // Get statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_events FROM events WHERE created_by = ?");
    $stmt->execute([$user_id]);
    $stats['total_events'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as published_events FROM events WHERE created_by = ? AND status = 'published'");
    $stmt->execute([$user_id]);
    $stats['published_events'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_registrations FROM event_registrations er 
                          JOIN events e ON er.event_id = e.id 
                          WHERE e.created_by = ?");
    $stmt->execute([$user_id]);
    $stats['total_registrations'] = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as upcoming_events FROM events 
                          WHERE created_by = ? AND event_date >= CURDATE() AND status = 'published'");
    $stmt->execute([$user_id]);
    $stats['upcoming_events'] = $stmt->fetchColumn();
    
} catch (Exception $e) {
    $profile = [];
    $stats = ['total_events' => 0, 'published_events' => 0, 'total_registrations' => 0, 'upcoming_events' => 0];
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        setFlashMessage('error', 'Invalid request. Please try again.');
    } elseif ($action === 'update_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setFlashMessage('error', 'All password fields are required.');
        } elseif ($new_password !== $confirm_password) {
            setFlashMessage('error', 'New passwords do not match.');
        } elseif (strlen($new_password) < 8) {
            setFlashMessage('error', 'Password must be at least 8 characters long.');
        } else {
            try {
                $pdo = getDBConnection();
                
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($current_password, $user['password'])) {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $user_id]);
                    
                    setFlashMessage('success', 'Password updated successfully!');
                } else {
                    setFlashMessage('error', 'Current password is incorrect.');
                }
            } catch (Exception $e) {
                setFlashMessage('error', 'Error updating password. Please try again.');
            }
        }
    } elseif ($action === 'update_profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $department = trim($_POST['department'] ?? '');
        
        if (empty($full_name) || empty($email)) {
            setFlashMessage('error', 'Name and email are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('error', 'Please enter a valid email address.');
        } else {
            try {
                $pdo = getDBConnection();
                
                // Check if email is being changed and if it's already taken
                if ($email !== $profile['email']) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $user_id]);
                    if ($stmt->fetch()) {
                        setFlashMessage('error', 'Email address is already in use.');
                        redirect('profile.php');
                    }
                }
                
                // Update profile
                $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, department = ? WHERE id = ?");
                $stmt->execute([$full_name, $email, $department, $user_id]);
                
                // Update session name if changed
                if ($full_name !== $_SESSION['user_name']) {
                    $_SESSION['user_name'] = $full_name;
                }
                
                setFlashMessage('success', 'Profile updated successfully!');
                redirect('profile.php');
            } catch (Exception $e) {
                setFlashMessage('error', 'Error updating profile. Please try again.');
            }
        }
    }
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: var(--spacing-lg);
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            color: white;
            margin-bottom: var(--spacing-lg);
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .profile-header-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }
        
        .profile-avatar-large {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            flex-shrink: 0;
        }
        
        .profile-info-main {
            flex: 1;
        }
        
        .profile-name {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--spacing-xs);
        }
        
        .profile-email {
            font-size: var(--font-size-md);
            opacity: 0.9;
            margin-bottom: var(--spacing-xs);
        }
        
        .profile-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 16px;
            font-size: var(--font-size-xs);
            font-weight: 500;
            backdrop-filter: blur(10px);
        }
        
        .profile-actions {
            display: flex;
            gap: var(--spacing-sm);
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .stat-card-profile {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            text-align: center;
            box-shadow: var(--clay-shadow);
            transition: transform var(--transition-fast);
        }
        
        .stat-card-profile:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon-profile {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-sm);
            color: white;
            font-size: 1.25rem;
        }
        
        .stat-number {
            font-size: var(--font-size-xl);
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: var(--font-size-xs);
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
        }
        
        .details-section {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            box-shadow: var(--clay-shadow);
        }
        
        .section-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            margin-bottom: var(--spacing-md);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
        }
        
        .section-title i {
            color: var(--primary);
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: var(--text-muted);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            font-size: var(--font-size-sm);
        }
        
        .detail-value {
            color: var(--text-primary);
            font-weight: 600;
            font-size: var(--font-size-sm);
        }
        
        .btn-edit-profile {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            font-size: var(--font-size-sm);
        }
        
        .btn-edit-profile:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: var(--bg-card);
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: var(--clay-shadow);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-lg);
        }
        
        .modal-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--text-muted);
            cursor: pointer;
            padding: var(--spacing-xs);
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
        }
        
        .modal-close:hover {
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .form-group {
            margin-bottom: var(--spacing-lg);
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: var(--spacing-xs);
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            font-family: inherit;
            font-size: var(--font-size-sm);
            transition: all var(--transition-fast);
            background: var(--bg-primary);
            color: var(--text-primary);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-actions {
            display: flex;
            gap: var(--spacing-md);
            justify-content: flex-end;
            margin-top: var(--spacing-xl);
        }
        
        .password-strength {
            margin-top: var(--spacing-xs);
            font-size: var(--font-size-xs);
            color: var(--text-muted);
        }
        
        .password-strength.weak {
            color: var(--danger);
        }
        
        .password-strength.medium {
            color: var(--warning);
        }
        
        .password-strength.strong {
            color: var(--success);
        }
        
        .password-input-group {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 4px;
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
            font-size: 1rem;
        }
        
        .password-toggle:hover {
            color: var(--primary);
            background: var(--bg-primary);
        }
        
        .password-toggle:focus {
            outline: none;
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .profile-container {
                padding: var(--spacing-md);
            }
            
            .profile-header-content {
                flex-direction: column;
                text-align: center;
                gap: var(--spacing-md);
            }
            
            .profile-actions {
                justify-content: center;
            }
            
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: var(--spacing-sm);
            }
            
            .profile-details {
                grid-template-columns: 1fr;
            }
            
            .profile-name {
                font-size: var(--font-size-xl);
            }
        }
        
        @media (max-width: 480px) {
            .profile-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: var(--spacing-xs);
            }
            
            .stat-card-profile {
                padding: var(--spacing-sm);
            }
            
            .stat-icon-profile {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }
            
            .stat-number {
                font-size: var(--font-size-md);
            }
            
            .stat-label {
                font-size: 0.65rem;
            }
        }
    </style>
</head>
<body class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <a href="../index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-calendar-alt"></i>
                    <span><?php echo SITE_NAME; ?></span>
                </a>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="my_events.php" class="nav-link">
                <i class="fas fa-calendar"></i>
                <span>My Events</span>
            </a>
            <a href="create_event.php" class="nav-link">
                <i class="fas fa-plus-circle"></i>
                <span>Create Event</span>
            </a>
            <a href="participants.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Participants</span>
            </a>
            <a href="qr_scanner.php" class="nav-link">
                <i class="fas fa-qrcode"></i>
                <span>QR Scanner</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="profile.php" class="nav-link active">
                <i class="fas fa-user"></i>
                <span>My Profile</span>
            </a>
            <a href="../logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <header class="top-nav">
            <button class="menu-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="nav-right">
                <button class="theme-toggle" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>
                </button>
                
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                        <span class="user-role">Teacher</span>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                </div>
            </div>
        </header>
        
        <div class="dashboard-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-header-content">
                        <div class="profile-avatar-large">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="profile-info-main">
                            <h1 class="profile-name"><?php echo $profile['full_name'] ?? 'Teacher'; ?></h1>
                            <p class="profile-email"><?php echo $profile['email'] ?? ''; ?></p>
                            <span class="profile-badge">
                                <i class="fas fa-chalkboard-teacher"></i> <?php echo ucfirst($profile['role'] ?? 'Teacher'); ?>
                            </span>
                        </div>
                        <div class="profile-actions">
                            <button class="btn btn-edit-profile" onclick="openEditProfileModal()">
                                <i class="fas fa-edit"></i> Edit Profile
                            </button>
                            <button class="btn btn-edit-profile" onclick="openPasswordModal()">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-card-profile">
                        <div class="stat-icon-profile">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_events']; ?></div>
                        <div class="stat-label">Total Events</div>
                    </div>
                    
                    <div class="stat-card-profile">
                        <div class="stat-icon-profile">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['published_events']; ?></div>
                        <div class="stat-label">Published Events</div>
                    </div>
                    
                    <div class="stat-card-profile">
                        <div class="stat-icon-profile">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                        <div class="stat-label">Total Registrations</div>
                    </div>
                    
                    <div class="stat-card-profile">
                        <div class="stat-icon-profile">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['upcoming_events']; ?></div>
                        <div class="stat-label">Upcoming Events</div>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="details-section">
                        <h2 class="section-title">
                            <i class="fas fa-user"></i> Personal Information
                        </h2>
                        
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="fas fa-envelope"></i> Email Address
                            </span>
                            <span class="detail-value"><?php echo $profile['email'] ?? 'Not provided'; ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="fas fa-building"></i> Faculty/Department
                            </span>
                            <span class="detail-value"><?php echo $profile['department'] ?? 'Not specified'; ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="fas fa-calendar-alt"></i> Member Since
                            </span>
                            <span class="detail-value"><?php echo formatDate($profile['created_at']); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="fas fa-sign-in-alt"></i> Last Login
                            </span>
                            <span class="detail-value"><?php echo $profile['last_login'] ? formatDate($profile['last_login']) : 'Never'; ?></span>
                        </div>
                    </div>
                    
                    <div class="details-section">
                        <h2 class="section-title">
                            <i class="fas fa-chart-bar"></i> Account Statistics
                        </h2>
                        
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="fas fa-check-circle"></i> Account Status
                            </span>
                            <span class="detail-value">
                                <?php echo $profile['is_active'] ? '<span style="color: var(--success);">Active</span>' : '<span style="color: var(--danger);">Inactive</span>'; ?>
                            </span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="fas fa-shield-alt"></i> Authentication
                            </span>
                            <span class="detail-value"><?php echo ucfirst($profile['auth_type'] ?? 'Email'); ?></span>
                        </div>
                        
                        <div class="detail-item">
                            <span class="detail-label">
                                <i class="fas fa-id-card"></i> Profile Completion
                            </span>
                            <span class="detail-value">
                                <?php echo $profile['profile_completed'] ? '<span style="color: var(--success);">Complete</span>' : '<span style="color: var(--warning);">Incomplete</span>'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Edit Profile Modal -->
    <div id="editProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Profile</h3>
                <button class="modal-close" onclick="closeModal('editProfileModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="form-group">
                    <label for="full_name">
                        <i class="fas fa-user"></i> Full Name *
                    </label>
                    <input type="text" id="full_name" name="full_name" class="form-control" 
                           placeholder="Enter your full name" required
                           value="<?php echo $profile['full_name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email Address *
                    </label>
                    <input type="email" id="email" name="email" class="form-control" 
                           placeholder="Enter your email address" required
                           value="<?php echo $profile['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="department">
                        <i class="fas fa-building"></i> Faculty/Department
                    </label>
                    <input type="text" id="department" name="department" class="form-control" 
                           placeholder="Enter your faculty or department"
                           value="<?php echo $profile['department'] ?? ''; ?>">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editProfileModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Change Password</h3>
                <button class="modal-close" onclick="closeModal('passwordModal')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" action="" onsubmit="return validatePasswordForm()">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="update_password">
                
                <div class="form-group">
                    <label for="current_password">
                        <i class="fas fa-lock"></i> Current Password *
                    </label>
                    <div class="password-input-group">
                        <input type="password" id="current_password" name="current_password" class="form-control" 
                               placeholder="Enter your current password" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('current_password')">
                            <i class="fas fa-eye" id="current_password_icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-key"></i> New Password *
                    </label>
                    <div class="password-input-group">
                        <input type="password" id="new_password" name="new_password" class="form-control" 
                               placeholder="Enter new password (min 8 characters)" required
                               oninput="checkPasswordStrength(this.value)">
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('new_password')">
                            <i class="fas fa-eye" id="new_password_icon"></i>
                        </button>
                    </div>
                    <div id="passwordStrength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-check"></i> Confirm New Password *
                    </label>
                    <div class="password-input-group">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                               placeholder="Confirm new password" required>
                        <button type="button" class="password-toggle" onclick="togglePasswordVisibility('confirm_password')">
                            <i class="fas fa-eye" id="confirm_password_icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('passwordModal')">
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>js/dashboard.js"></script>
    <script>
        function openEditProfileModal() {
            document.getElementById('editProfileModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function openPasswordModal() {
            document.getElementById('passwordModal').classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.body.style.overflow = '';
            
            // Reset password form
            if (modalId === 'passwordModal') {
                document.querySelector('#passwordModal form').reset();
                document.getElementById('passwordStrength').textContent = '';
                document.getElementById('passwordStrength').className = 'password-strength';
            }
        }
        
        function togglePasswordVisibility(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const iconField = document.getElementById(fieldId + '_icon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                iconField.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                iconField.className = 'fas fa-eye';
            }
        }
        
        function checkPasswordStrength(password) {
            const strengthEl = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            
            if (strength === 0) {
                strengthEl.textContent = '';
                strengthEl.className = 'password-strength';
            } else if (strength <= 2) {
                strengthEl.textContent = 'Weak password';
                strengthEl.className = 'password-strength weak';
            } else if (strength === 3) {
                strengthEl.textContent = 'Medium strength';
                strengthEl.className = 'password-strength medium';
            } else {
                strengthEl.textContent = 'Strong password';
                strengthEl.className = 'password-strength strong';
            }
        }
        
        function validatePasswordForm() {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('New passwords do not match!');
                return false;
            }
            
            if (newPassword.length < 8) {
                alert('Password must be at least 8 characters long!');
                return false;
            }
            
            return true;
        }
        
        // Close modals on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = '';
            }
        });
        
        // Close modals on outside click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
                document.body.style.overflow = '';
            }
        });
    </script>
</body>
</html>
