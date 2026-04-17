<?php
/**
 * Eventura - Admin Dashboard
 */
require_once '../config.php';
startSecureSession();
requireRole('admin');

try {
    $pdo = getDBConnection();
    
    // Get statistics
    $stats = [];
    
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = TRUE");
    $stats['total_users'] = $stmt->fetchColumn();
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND is_active = TRUE");
    $stats['total_students'] = $stmt->fetchColumn();
    
    // Total teachers
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND is_active = TRUE");
    $stats['total_teachers'] = $stmt->fetchColumn();
    
    // Total events
    $stmt = $pdo->query("SELECT COUNT(*) FROM events");
    $stats['total_events'] = $stmt->fetchColumn();
    
    // Upcoming events
    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status = 'published'");
    $stats['upcoming_events'] = $stmt->fetchColumn();
    
    // Total registrations
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations");
    $stats['total_registrations'] = $stmt->fetchColumn();
    
    // Recent events
    $stmt = $pdo->query("SELECT e.*, u.full_name as creator_name, 
                        (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
                        FROM events e 
                        JOIN users u ON e.created_by = u.id 
                        ORDER BY e.created_at DESC LIMIT 5");
    $recent_events = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Admin dashboard error: " . $e->getMessage());
    $stats = [
        'total_users' => 0, 'total_students' => 0, 'total_teachers' => 0,
        'total_events' => 0, 'upcoming_events' => 0, 'total_registrations' => 0
    ];
    $recent_events = [];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard admin-dashboard">
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
            <a href="dashboard.php" class="nav-link active">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="events.php" class="nav-link">
                <i class="fas fa-calendar"></i>
                <span>Events</span>
            </a>
            <a href="users.php" class="nav-link">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <a href="registrations.php" class="nav-link">
                <i class="fas fa-clipboard-list"></i>
                <span>Registrations</span>
            </a>
            <a href="qr_scanner.php" class="nav-link">
                <i class="fas fa-qrcode"></i>
                <span>QR Scanner</span>
            </a>
            <a href="reports.php" class="nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navigation -->
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
                        <span class="user-role">Administrator</span>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user-shield"></i>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                    <?php echo $flash['message']; ?>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>Admin Dashboard</h1>
                <p>Welcome back! Here's what's happening today.</p>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card clay-card">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_users']; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="stat-card clay-card">
                    <div class="stat-icon students">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_students']; ?></h3>
                        <p>Students</p>
                    </div>
                </div>
                
                <div class="stat-card clay-card">
                    <div class="stat-icon teachers">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_teachers']; ?></h3>
                        <p>Teachers</p>
                    </div>
                </div>
                
                <div class="stat-card clay-card">
                    <div class="stat-icon events">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_events']; ?></h3>
                        <p>Total Events</p>
                    </div>
                </div>
                
                <div class="stat-card clay-card">
                    <div class="stat-icon upcoming">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['upcoming_events']; ?></h3>
                        <p>Upcoming Events</p>
                    </div>
                </div>
                
                <div class="stat-card clay-card">
                    <div class="stat-icon registrations">
                        <i class="fas fa-clipboard-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $stats['total_registrations']; ?></h3>
                        <p>Registrations</p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="create_event.php" class="action-card clay-card">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <span>Create Event</span>
                </a>
                <a href="users.php?action=add" class="action-card clay-card">
                    <div class="action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <span>Add User</span>
                </a>
                <a href="qr_scanner.php" class="action-card clay-card">
                    <div class="action-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <span>Scan QR</span>
                </a>
                <a href="reports.php" class="action-card clay-card">
                    <div class="action-icon">
                        <i class="fas fa-download"></i>
                    </div>
                    <span>Export Data</span>
                </a>
            </div>
            
            <!-- Recent Events -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Recent Events</h2>
                    <a href="events.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                
                <!-- Desktop Table View -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date & Time</th>
                                <th>Venue</th>
                                <th>Created By</th>
                                <th>Registrations</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recent_events)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No events created yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recent_events as $event): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $event['title']; ?></strong>
                                            <?php if ($event['food_available']): ?>
                                                <span class="badge badge-success">Food</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo formatDate($event['event_date']); ?><br>
                                            <small><?php echo formatTime($event['event_time']); ?></small>
                                        </td>
                                        <td><?php echo $event['venue']; ?></td>
                                        <td><?php echo $event['creator_name']; ?></td>
                                        <td><?php echo $event['registration_count']; ?></td>
                                        <td>
                                            <span class="badge badge-<?php 
                                                echo $event['status'] === 'published' ? 'success' : 
                                                     ($event['status'] === 'draft' ? 'warning' : 'error'); 
                                            ?>">
                                                <?php echo ucfirst($event['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Card View -->
                <div class="mobile-events-container" style="display: none;">
                    <?php if (empty($recent_events)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No events created yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_events as $event): ?>
                            <div class="event-card">
                                <div class="event-card-header">
                                    <div>
                                        <h3 class="event-card-title">
                                            <?php echo $event['title']; ?>
                                            <?php if ($event['food_available']): ?>
                                                <span class="badge badge-success">Food</span>
                                            <?php endif; ?>
                                        </h3>
                                    </div>
                                    <div class="event-card-status">
                                        <span class="badge badge-<?php 
                                            echo $event['status'] === 'published' ? 'success' : 
                                                 ($event['status'] === 'draft' ? 'warning' : 'error'); 
                                        ?>">
                                            <?php echo ucfirst($event['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="event-card-details">
                                    <div class="event-detail-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo formatDate($event['event_date']); ?></span>
                                    </div>
                                    <div class="event-detail-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo formatTime($event['event_time']); ?></span>
                                    </div>
                                    <div class="event-detail-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo $event['venue']; ?></span>
                                    </div>
                                    <div class="event-detail-item">
                                        <i class="fas fa-user"></i>
                                        <span><?php echo $event['creator_name']; ?></span>
                                    </div>
                                </div>
                                
                                <div class="event-card-meta">
                                    <div class="event-registrations">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $event['registration_count']; ?> Registrations</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <script src="<?php echo ASSETS_URL; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
