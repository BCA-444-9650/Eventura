<?php
/**
 * Eventura - Student View Event & Register
 */
require_once '../config.php';
startSecureSession();
requireRole('student');

$event_id = intval($_GET['id'] ?? 0);

if (!$event_id) {
    setFlashMessage('error', 'Invalid event.');
    redirect(SITE_URL . '/student/events.php');
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get event details
    $stmt = $pdo->prepare("SELECT e.*, u.full_name as creator_name,
                          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count,
                          CASE WHEN er.id IS NOT NULL THEN 1 ELSE 0 END as is_registered
                          FROM events e 
                          JOIN users u ON e.created_by = u.id
                          LEFT JOIN event_registrations er ON e.id = er.event_id AND er.user_id = ?
                          WHERE e.id = ? AND e.status = 'published'");
    $stmt->execute([$user_id, $event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        setFlashMessage('error', 'Event not found.');
        redirect(SITE_URL . '/student/events.php');
    }
    
    // Check if event is full
    $is_full = $event['max_participants'] > 0 && $event['registration_count'] >= $event['max_participants'];
    
} catch (Exception $e) {
    setFlashMessage('error', 'Error loading event.');
    redirect(SITE_URL . '/student/events.php');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $event['title']; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .event-detail-card { max-width: 800px; margin: 0 auto; padding: 40px; }
        .event-meta { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0; }
        .meta-item { text-align: center; padding: 20px; background: var(--bg-primary); border-radius: var(--radius-md); }
        .meta-item i { font-size: 1.5rem; color: var(--primary); margin-bottom: 10px; }
        .register-section { text-align: center; margin-top: 30px; padding-top: 30px; border-top: 1px solid var(--border-color); }
        @media (max-width: 600px) { .event-meta { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="dashboard student-dashboard">
    <header class="top-nav-bar">
        <div class="nav-brand"><a href="../index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;"><i class="fas fa-calendar-alt"></i><span><?php echo SITE_NAME; ?></span></a></div>
        <nav class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="events.php" class="active">Events</a>
            <a href="my_tickets.php">My Tickets</a>
            <a href="history.php">History</a>
        </nav>
        <div class="nav-actions">
            <button class="theme-toggle" onclick="toggleTheme()"><i class="fas fa-moon"></i></button>
            <div class="user-dropdown">
                <div class="user-trigger" onclick="toggleUserMenu()">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <span><?php echo $_SESSION['user_name']; ?></span>
                </div>
                <div class="dropdown-menu" id="userDropdown">
                    <a href="profile.php"><i class="fas fa-id-card"></i> My Profile</a>
                    <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </header>
    
    <main class="main-content student-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo $flash['message']; ?></div>
        <?php endif; ?>
        
        <div class="event-detail-card clay-card">
            <?php if ($event['food_available']): ?>
                <span class="badge badge-success" style="float: right;"><i class="fas fa-utensils"></i> Food Available</span>
            <?php endif; ?>
            
            <h1 style="margin-bottom: 10px;"><?php echo $event['title']; ?></h1>
            <p style="color: var(--text-secondary);">Organized by <?php echo $event['creator_name']; ?></p>
            
            <div class="event-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar"></i>
                    <p><strong>Date</strong></p>
                    <p><?php echo formatDate($event['event_date']); ?></p>
                </div>
                <div class="meta-item">
                    <i class="fas fa-clock"></i>
                    <p><strong>Time</strong></p>
                    <p><?php echo formatTime($event['event_time']); ?></p>
                </div>
                <div class="meta-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <p><strong>Venue</strong></p>
                    <p><?php echo $event['venue']; ?></p>
                </div>
            </div>
            
            <?php if ($event['description']): ?>
                <div style="margin: 30px 0;">
                    <h3>About This Event</h3>
                    <p style="line-height: 1.8;"><?php echo nl2br($event['description']); ?></p>
                </div>
            <?php endif; ?>
            
            <div style="background: var(--bg-primary); padding: 20px; border-radius: var(--radius-md); margin: 20px 0;">
                <p><i class="fas fa-users"></i> <strong><?php echo $event['registration_count']; ?></strong> students registered</p>
                <?php if ($event['max_participants'] > 0): ?>
                    <p><i class="fas fa-ticket-alt"></i> <strong><?php echo $event['max_participants'] - $event['registration_count']; ?></strong> spots remaining</p>
                <?php endif; ?>
            </div>
            
            <div class="register-section">
                <?php if ($event['is_registered']): ?>
                    <div class="alert alert-success" style="margin-bottom: 20px;">
                        <i class="fas fa-check-circle"></i> You are registered for this event!
                    </div>
                    <a href="view_ticket.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-ticket-alt"></i> View My Ticket
                    </a>
                <?php elseif ($is_full): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> This event is full.
                    </div>
                <?php elseif (strtotime($event['event_date']) < strtotime(date('Y-m-d'))): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> This event has passed.
                    </div>
                <?php else: ?>
                    <a href="register_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary btn-lg" onclick="return confirm('Register for this event?')">
                        <i class="fas fa-plus"></i> Register Now
                    </a>
                <?php endif; ?>
                <br><br>
                <a href="events.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Events</a>
            </div>
        </div>
    </main>
    
    <script src="<?php echo ASSETS_URL; ?>js/dashboard.js"></script>
</body>
</html>
