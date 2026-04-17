<?php
/**
 * Eventura - Teacher Dashboard
 */
require_once '../config.php';
startSecureSession();
requireRole('teacher');

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get all published events (for all teachers to see)
    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE status = 'published' OR status IS NULL");
    $my_events = $stmt->fetchColumn();
    
    // Get upcoming events
    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND (status = 'published' OR status IS NULL)");
    $upcoming_events = $stmt->fetchColumn();
    
    // Get total registrations for all events
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations er 
                          JOIN events e ON er.event_id = e.id 
                          WHERE e.status = 'published' OR e.status IS NULL");
    $total_registrations = $stmt->fetchColumn();
    
    // Get attended count
    $stmt = $pdo->query("SELECT COUNT(*) FROM event_registrations er 
                          JOIN events e ON er.event_id = e.id 
                          WHERE er.status = 'attended' AND (e.status = 'published' OR e.status IS NULL)");
    $attended_count = $stmt->fetchColumn();
    
    // Get all events list (not just my events)
    $stmt = $pdo->query("SELECT e.*, u.full_name as creator_name,
                          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count,
                          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'attended') as attended_count
                          FROM events e 
                          JOIN users u ON e.created_by = u.id
                          WHERE e.status = 'published' OR e.status IS NULL
                          ORDER BY e.event_date DESC LIMIT 10");
    $events = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Teacher dashboard error: " . $e->getMessage());
    $my_events = $upcoming_events = $total_registrations = $attended_count = 0;
    $events = [];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard teacher-dashboard">
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
            <a href="profile.php" class="nav-link">
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
            
            <div class="page-header">
                <h1>Teacher Dashboard</h1>
                <p>Manage your events and track attendance.</p>
            </div>
            
            <div class="stats-grid four-cols">
                <div class="stat-card clay-card">
                    <div class="stat-icon events">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $my_events; ?></h3>
                        <p>My Events</p>
                    </div>
                </div>
                
                <div class="stat-card clay-card">
                    <div class="stat-icon upcoming">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $upcoming_events; ?></h3>
                        <p>Upcoming</p>
                    </div>
                </div>
                
                <div class="stat-card clay-card">
                    <div class="stat-icon registrations">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $total_registrations; ?></h3>
                        <p>Registrations</p>
                    </div>
                </div>
                
                <div class="stat-card clay-card">
                    <div class="stat-icon attended">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $attended_count; ?></h3>
                        <p>Attended</p>
                    </div>
                </div>
            </div>
            
            <div class="quick-actions">
                <a href="create_event.php" class="action-card clay-card">
                    <div class="action-icon">
                        <i class="fas fa-plus"></i>
                    </div>
                    <span>Create Event</span>
                </a>
                <a href="qr_scanner.php" class="action-card clay-card">
                    <div class="action-icon">
                        <i class="fas fa-qrcode"></i>
                    </div>
                    <span>Scan QR Code</span>
                </a>
                <a href="participants.php" class="action-card clay-card">
                    <div class="action-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span>View Participants</span>
                </a>
            </div>
            
            <div class="content-section">
                <div class="section-header">
                    <h2>My Events</h2>
                    <a href="my_events.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                
                <!-- Desktop Table View -->
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Event</th>
                                <th>Date & Time</th>
                                <th>Venue</th>
                                <th>Registrations</th>
                                <th>Attendance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($events)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">No events created yet. <a href="create_event.php">Create one now</a>.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $event): ?>
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
                                        <td><?php echo $event['registration_count']; ?></td>
                                        <td>
                                            <?php echo $event['attended_count']; ?> / <?php echo $event['registration_count']; ?>
                                            <?php if ($event['registration_count'] > 0): ?>
                                                <br><small>(<?php echo round(($event['attended_count'] / $event['registration_count']) * 100); ?>%)</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $event['status'] === 'published' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($event['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Card View -->
                <div class="mobile-events-container" style="display: none;">
                    <?php if (empty($events)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No events created yet. <a href="create_event.php">Create one now</a>.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($events as $event): ?>
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
                                        <span class="badge badge-<?php echo $event['status'] === 'published' ? 'success' : 'warning'; ?>">
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
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $event['registration_count']; ?> Registered</span>
                                    </div>
                                    <div class="event-detail-item">
                                        <i class="fas fa-check-circle"></i>
                                        <span><?php echo $event['attended_count']; ?> Attended 
                                            <?php if ($event['registration_count'] > 0): ?>
                                                (<?php echo round(($event['attended_count'] / $event['registration_count']) * 100); ?>%)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="event-card-meta">
                                    <div class="event-registrations">
                                        <i class="fas fa-chart-pie"></i>
                                        <span>Attendance: <?php echo $event['attended_count']; ?>/<?php echo $event['registration_count']; ?></span>
                                    </div>
                                    <div class="event-actions">
                                        <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary">
                                            <i class="fas fa-edit"></i>
                                        </a>
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
