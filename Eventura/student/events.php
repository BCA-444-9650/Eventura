<?php
/**
 * Eventura - Student Events (Browse & Register)
 */
require_once '../config.php';
startSecureSession();
requireRole('student');

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get all upcoming events with registration status
    $stmt = $pdo->prepare("SELECT e.*, 
                          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count,
                          CASE WHEN er.id IS NOT NULL THEN 1 ELSE 0 END as is_registered,
                          er.id as my_registration_id
                          FROM events e 
                          LEFT JOIN event_registrations er ON e.id = er.event_id AND er.user_id = ?
                          WHERE e.event_date >= CURDATE() AND (e.status = 'published' OR e.status IS NULL)
                          ORDER BY e.event_date ASC");
    $stmt->execute([$user_id]);
    $events = $stmt->fetchAll();
    
} catch (Exception $e) {
    $events = [];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css?v=<?php echo time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard student-dashboard">
    <header class="top-nav-bar">
        <div class="nav-brand"><a href="../index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;"><i class="fas fa-calendar-alt"></i><span><?php echo SITE_NAME; ?></span></a></div>
        <button class="mobile-nav-toggle" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></button>
        <nav class="nav-links" id="mobileNav">
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
                    <i class="fas fa-chevron-down"></i>
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
        
        <div class="page-header"><h1>Upcoming Events</h1></div>
        
        <?php if (empty($events)): ?>
            <div class="empty-state clay-card">
                <i class="fas fa-calendar"></i>
                <p>No upcoming events available.</p>
            </div>
        <?php else: ?>
            <div class="event-cards">
                <?php foreach ($events as $event): ?>
                    <div class="event-card clay-card">
                        <div class="event-header">
                            <h3><?php echo $event['title']; ?></h3>
                            <?php if ($event['food_available']): ?><span class="badge badge-success"><i class="fas fa-utensils"></i> Food</span><?php endif; ?>
                        </div>
                        <div class="event-details">
                            <p><i class="fas fa-calendar"></i> <?php echo formatDate($event['event_date']); ?></p>
                            <p><i class="fas fa-clock"></i> <?php echo formatTime($event['event_time']); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo $event['venue']; ?></p>
                            <p><i class="fas fa-users"></i> <?php echo $event['registration_count']; ?> registered</p>
                        </div>
                        <?php if ($event['description']): ?>
                            <p style="color: var(--text-secondary); font-size: var(--font-size-sm); margin-bottom: 15px;"><?php echo substr($event['description'], 0, 100); ?>...</p>
                        <?php endif; ?>
                        <div class="event-actions">
                            <?php if ($event['is_registered']): ?>
                                <a href="view_ticket.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary"><i class="fas fa-ticket-alt"></i> View Ticket</a>
                            <?php else: ?>
                                <a href="register_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-primary"><i class="fas fa-plus"></i> Register Now</a>
                            <?php endif; ?>
                            <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-secondary">Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <script src="<?php echo ASSETS_URL; ?>js/dashboard.js?v=<?php echo time(); ?>"></script>
</body>
</html>
