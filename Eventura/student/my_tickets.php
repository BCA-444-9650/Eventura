<?php
/**
 * Eventura - Student My Tickets
 */
require_once '../config.php';
startSecureSession();
requireRole('student');

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get my tickets
    $stmt = $pdo->prepare("SELECT e.*, er.id as registration_id, er.status as reg_status,
                          qr.qr_data, qr.entry_used, qr.food_used
                          FROM event_registrations er
                          JOIN events e ON er.event_id = e.id
                          JOIN qr_codes qr ON er.id = qr.registration_id
                          WHERE er.user_id = ? AND e.event_date >= CURDATE()
                          ORDER BY e.event_date ASC");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll();
    
} catch (Exception $e) {
    $tickets = [];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tickets - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard student-dashboard">
    <header class="top-nav-bar">
        <div class="nav-brand"><a href="../index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;"><i class="fas fa-calendar-alt"></i><span><?php echo SITE_NAME; ?></span></a></div>
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></button>
        <nav class="nav-links" id="mobileNav">
            <a href="dashboard.php">Dashboard</a>
            <a href="events.php">Events</a>
            <a href="my_tickets.php" class="active">My Tickets</a>
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
        
        <div class="page-header"><h1>My Tickets</h1></div>
        
        <?php if (empty($tickets)): ?>
            <div class="empty-state clay-card">
                <i class="fas fa-ticket-alt"></i>
                <p>No active tickets. <a href="events.php">Browse events</a> to register.</p>
            </div>
        <?php else: ?>
            <div class="event-cards">
                <?php foreach ($tickets as $ticket): ?>
                    <div class="event-card clay-card">
                        <div class="event-header">
                            <h3><?php echo $ticket['title']; ?></h3>
                            <?php if ($ticket['food_available']): ?><span class="badge badge-success"><i class="fas fa-utensils"></i> Food</span><?php endif; ?>
                        </div>
                        <div class="event-details">
                            <p><i class="fas fa-calendar"></i> <?php echo formatDate($ticket['event_date']); ?></p>
                            <p><i class="fas fa-clock"></i> <?php echo formatTime($ticket['event_time']); ?></p>
                            <p><i class="fas fa-map-marker-alt"></i> <?php echo $ticket['venue']; ?></p>
                            <p>
                                <i class="fas fa-check-circle"></i> 
                                <?php if ($ticket['entry_used']): ?>
                                    <span style="color: var(--success);">Entry Validated</span>
                                <?php else: ?>
                                    <span>Entry Pending</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($ticket['food_available']): ?>
                                <p>
                                    <i class="fas fa-utensils"></i>
                                    <?php if ($ticket['food_used']): ?>
                                        <span style="color: var(--success);">Food Coupon Used</span>
                                    <?php else: ?>
                                        <span>Food Coupon Available</span>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="event-actions">
                            <a href="view_ticket.php?event_id=<?php echo $ticket['id']; ?>" class="btn btn-primary"><i class="fas fa-qrcode"></i> Show Ticket</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    
    <script src="<?php echo ASSETS_URL; ?>js/dashboard.js"></script>
</body>
</html>
