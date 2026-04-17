<?php
/**
 * Eventura - Student Dashboard
 */
require_once '../config.php';
startSecureSession();
requireRole('student');

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get student profile
    $stmt = $pdo->prepare("SELECT * FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    // Get my registrations
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $my_registrations = $stmt->fetchColumn();
    
    // Get upcoming registered events
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations er 
                          JOIN events e ON er.event_id = e.id 
                          WHERE er.user_id = ? AND e.event_date >= CURDATE()");
    $stmt->execute([$user_id]);
    $upcoming_registered = $stmt->fetchColumn();
    
    // Get attended events count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE user_id = ? AND status = 'attended'");
    $stmt->execute([$user_id]);
    $attended_count = $stmt->fetchColumn();
    
    // Get available upcoming events
    $stmt = $pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE() AND status = 'published'");
    $available_events = $stmt->fetchColumn();
    
    // Get my events with QR codes
    $stmt = $pdo->prepare("SELECT e.*, er.id as registration_id, er.status,
                          (SELECT qr_data FROM qr_codes WHERE registration_id = er.id) as qr_data
                          FROM events e 
                          JOIN event_registrations er ON e.id = er.event_id 
                          WHERE er.user_id = ? AND e.event_date >= CURDATE()
                          ORDER BY e.event_date ASC LIMIT 5");
    $stmt->execute([$user_id]);
    $my_events = $stmt->fetchAll();
    
    // Get upcoming events
    $stmt = $pdo->prepare("SELECT e.*, 
                          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count,
                          CASE WHEN er.id IS NOT NULL THEN 1 ELSE 0 END as is_registered
                          FROM events e 
                          LEFT JOIN event_registrations er ON e.id = er.event_id AND er.user_id = ?
                          WHERE e.event_date >= CURDATE() AND (e.status = 'published' OR e.status IS NULL)
                          ORDER BY e.event_date ASC LIMIT 5");
    $stmt->execute([$user_id]);
    $upcoming_events = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Student dashboard error: " . $e->getMessage());
    $profile = [];
    $my_registrations = $upcoming_registered = $attended_count = $available_events = 0;
    $my_events = [];
    $upcoming_events = [];
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dashboard student-dashboard">
    <!-- Top Navigation -->
    <header class="top-nav-bar">
        <div class="nav-brand">
            <a href="../index.php" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-calendar-alt"></i>
                <span><?php echo SITE_NAME; ?></span>
            </a>
        </div>
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <i class="fas fa-bars"></i>
        </button>
        <nav class="nav-links" id="mobileNav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="events.php">Events</a>
            <a href="my_tickets.php">My Tickets</a>
            <a href="history.php">History</a>
        </nav>
        
        <div class="nav-actions">
            <button class="theme-toggle" onclick="toggleTheme()">
                <i class="fas fa-moon"></i>
            </button>
            
            <div class="user-dropdown">
                <div class="user-trigger" onclick="toggleUserMenu()">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
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
    
    <!-- Main Content -->
    <main class="main-content student-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>
        
        <div class="welcome-section">
            <div class="welcome-text">
                <h1>Welcome, <?php echo $_SESSION['user_name']; ?>! 👋</h1>
                <?php if ($profile): ?>
                    <p><?php echo $profile['course']; ?> - Year <?php echo $profile['year']; ?> | Roll No: <?php echo $profile['roll_no']; ?></p>
                <?php endif; ?>
            </div>
            <a href="events.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Events
            </a>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid four-cols">
            <div class="stat-card clay-card">
                <div class="stat-icon registrations">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $my_registrations; ?></h3>
                    <p>My Registrations</p>
                </div>
            </div>
            
            <div class="stat-card clay-card">
                <div class="stat-icon upcoming">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $upcoming_registered; ?></h3>
                    <p>Upcoming Events</p>
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
            
            <div class="stat-card clay-card">
                <div class="stat-icon events">
                    <i class="fas fa-calendar-plus"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $available_events; ?></h3>
                    <p>Available Events</p>
                </div>
            </div>
        </div>
        
        <!-- Two Column Layout -->
        <div class="dashboard-columns">
            <!-- My Upcoming Events -->
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-ticket-alt"></i> My Tickets</h2>
                    <a href="my_tickets.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                
                <?php if (empty($my_events)): ?>
                    <div class="empty-state clay-card">
                        <i class="fas fa-ticket-alt"></i>
                        <p>No upcoming events registered yet.</p>
                        <a href="events.php" class="btn btn-primary">Browse Events</a>
                    </div>
                <?php else: ?>
                    <div class="event-cards">
                        <?php foreach ($my_events as $event): ?>
                            <div class="event-card clay-card">
                                <div class="event-header">
                                    <h3><?php echo $event['title']; ?></h3>
                                    <?php if ($event['food_available']): ?>
                                        <span class="badge badge-success"><i class="fas fa-utensils"></i> Food</span>
                                    <?php endif; ?>
                                </div>
                                <div class="event-details">
                                    <p><i class="fas fa-calendar"></i> <?php echo formatDate($event['event_date']); ?></p>
                                    <p><i class="fas fa-clock"></i> <?php echo formatTime($event['event_time']); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo $event['venue']; ?></p>
                                </div>
                                <div class="event-actions">
                                    <a href="view_ticket.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-qrcode"></i> Show Ticket
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Available Events -->
            <div class="content-section">
                <div class="section-header">
                    <h2><i class="fas fa-calendar"></i> Upcoming Events</h2>
                    <a href="events.php" class="btn btn-sm btn-secondary">View All</a>
                </div>
                
                <?php if (empty($upcoming_events)): ?>
                    <div class="empty-state clay-card">
                        <i class="fas fa-calendar"></i>
                        <p>No upcoming events available.</p>
                    </div>
                <?php else: ?>
                    <div class="event-list">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="event-list-item clay-card">
                                <div class="event-info">
                                    <h4><?php echo $event['title']; ?></h4>
                                    <p>
                                        <i class="fas fa-calendar"></i> <?php echo formatDate($event['event_date']); ?>
                                        <i class="fas fa-clock"></i> <?php echo formatTime($event['event_time']); ?>
                                    </p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo $event['venue']; ?></p>
                                    <?php if ($event['food_available']): ?>
                                        <span class="badge badge-success"><i class="fas fa-utensils"></i> Food Available</span>
                                    <?php endif; ?>
                                </div>
                                <div class="event-action">
                                    <?php if ($event['is_registered']): ?>
                                        <span class="badge badge-success"><i class="fas fa-check"></i> Registered</span>
                                    <?php else: ?>
                                        <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">Register</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Chatbot -->
    <div class="chatbot-container" id="chatbot">
        <div class="chatbot-header" onclick="toggleChatbot()">
            <i class="fas fa-robot"></i>
            <span>Eventura Assistant</span>
            <i class="fas fa-chevron-up" id="chatbotToggle"></i>
        </div>
        <div class="chatbot-body" id="chatbotBody">
            <div class="chat-messages" id="chatMessages">
                <div class="message bot">
                    <div class="message-content">
                        Hello! I'm Eventura Assistant. How can I help you today?
                    </div>
                </div>
            </div>
            <div class="chat-input">
                <input type="text" id="chatInput" placeholder="Type your question...">
                <button onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
            </div>
        </div>
    </div>
    
    <script src="<?php echo ASSETS_URL; ?>js/dashboard.js"></script>
    <script src="<?php echo ASSETS_URL; ?>js/chatbot.js"></script>
</body>
</html>
