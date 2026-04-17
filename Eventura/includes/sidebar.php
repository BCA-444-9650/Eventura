<?php
/**
 * Eventura - Sidebar Include
 */
$is_admin = hasRole('admin');
$is_teacher = hasRole('teacher');
?>
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
        <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if ($is_admin): ?>
        <a href="events.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'event') !== false ? 'active' : ''; ?>">
            <i class="fas fa-calendar"></i>
            <span>All Events</span>
        </a>
        <a href="users.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'user') !== false ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        <a href="registrations.php" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'registration') !== false ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>Registrations</span>
        </a>
        <?php else: ?>
        <a href="my_events.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'my_events.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar"></i>
            <span>My Events</span>
        </a>
        <a href="create_event.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'create_event.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Create Event</span>
        </a>
        <a href="participants.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'participants.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Participants</span>
        </a>
        <?php endif; ?>
        
        <a href="qr_scanner.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'qr_scanner.php' ? 'active' : ''; ?>">
            <i class="fas fa-qrcode"></i>
            <span>QR Scanner</span>
        </a>
        
        <?php if ($is_admin): ?>
        <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <?php endif; ?>
    </nav>
    
    <div class="sidebar-footer">
        <?php if (!$is_admin): ?>
        <a href="<?php echo $is_teacher ? 'profile.php' : 'student/profile.php'; ?>" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], 'profile.php') !== false ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>My Profile</span>
        </a>
        <?php endif; ?>
        <a href="../logout.php" class="nav-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
