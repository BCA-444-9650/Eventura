<?php
/**
 * Eventura - View Ticket (Student)
 */
require_once '../config.php';
startSecureSession();
requireRole('student');

$event_id = intval($_GET['event_id'] ?? 0);
$user_id = $_SESSION['user_id'];

if (!$event_id) {
    setFlashMessage('error', 'Invalid event.');
    redirect(SITE_URL . '/student/my_tickets.php');
}

try {
    $pdo = getDBConnection();
    
    // Get registration with QR code
    $stmt = $pdo->prepare("SELECT er.*, e.*, qr.qr_data,
                          sp.student_id, sp.roll_no, sp.course, sp.year
                          FROM event_registrations er
                          JOIN events e ON er.event_id = e.id
                          JOIN qr_codes qr ON er.id = qr.registration_id
                          JOIN student_profiles sp ON er.student_profile_id = sp.id
                          WHERE er.event_id = ? AND er.user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        setFlashMessage('error', 'Ticket not found.');
        redirect(SITE_URL . '/student/my_tickets.php');
    }
    
} catch (Exception $e) {
    setFlashMessage('error', 'Error loading ticket.');
    redirect(SITE_URL . '/student/my_tickets.php');
}

require_once '../includes/qr_generator.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ticket - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .ticket-container { max-width: 500px; margin: 0 auto; }
        .ticket-card { 
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white; 
            padding: 40px; 
            border-radius: var(--radius-xl);
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .ticket-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            pointer-events: none;
        }
        .ticket-header { margin-bottom: 30px; position: relative; z-index: 1; }
        .ticket-header h2 { font-size: var(--font-size-2xl); margin-bottom: 10px; }
        .ticket-qr { 
            background: white; 
            padding: 20px; 
            border-radius: var(--radius-lg);
            display: inline-block;
            margin: 20px 0;
            position: relative;
            z-index: 1;
        }
        .ticket-info { 
            text-align: left; 
            margin-top: 30px; 
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: var(--radius-md);
            position: relative;
            z-index: 1;
        }
        .ticket-info p { margin-bottom: 10px; display: flex; align-items: center; gap: 10px; }
        .ticket-actions { 
            margin-top: 30px; 
            display: flex; 
            gap: 15px; 
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        .ticket-actions .btn { background: white; color: var(--primary); }
        .ticket-actions .btn:hover { background: var(--bg-primary); }
        .notches {
            position: absolute;
            left: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            background: var(--bg-primary);
            border-radius: 50%;
        }
        .notches::after {
            content: '';
            position: absolute;
            right: -470px;
            top: 0;
            width: 30px;
            height: 30px;
            background: var(--bg-primary);
            border-radius: 50%;
        }
        @media print {
            body * { visibility: hidden; }
            .ticket-container, .ticket-container * { visibility: visible; }
            .ticket-container { position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%); }
            .ticket-actions { display: none; }
        }
    </style>
</head>
<body class="dashboard student-dashboard">
    <header class="top-nav-bar">
        <div class="nav-brand"><i class="fas fa-calendar-alt"></i><span><?php echo SITE_NAME; ?></span></div>
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
        <div class="page-header" style="text-align: center;">
            <h1>Your Event Ticket</h1>
        </div>
        
        <div class="ticket-container">
            <div class="ticket-card clay-card">
                <div class="notches"></div>
                
                <div class="ticket-header">
                    <i class="fas fa-ticket-alt" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <h2><?php echo $ticket['title']; ?></h2>
                    <p style="opacity: 0.9;"><?php echo SITE_NAME; ?> Event Ticket</p>
                </div>
                
                <div class="ticket-qr">
                    <?php echo getQRDisplay($ticket['qr_data'], 200); ?>
                </div>
                
                <div class="ticket-info">
                    <p><i class="fas fa-user"></i> <strong><?php echo $_SESSION['user_name']; ?></strong></p>
                    <p><i class="fas fa-id-card"></i> <?php echo $ticket['student_id']; ?> | Roll: <?php echo $ticket['roll_no']; ?></p>
                    <p><i class="fas fa-graduation-cap"></i> <?php echo $ticket['course']; ?> - Year <?php echo $ticket['year']; ?></p>
                    <p><i class="fas fa-calendar"></i> <?php echo formatDate($ticket['event_date']); ?></p>
                    <p><i class="fas fa-clock"></i> <?php echo formatTime($ticket['event_time']); ?></p>
                    <p><i class="fas fa-map-marker-alt"></i> <?php echo $ticket['venue']; ?></p>
                    <?php if ($ticket['food_available']): ?>
                        <p><i class="fas fa-utensils"></i> <span style="color: #4ade80;">Food Coupon Included</span></p>
                    <?php endif; ?>
                </div>
                
                <div class="ticket-actions">
                    <button onclick="window.print()" class="btn"><i class="fas fa-print"></i> Print</button>
                    <a href="my_tickets.php" class="btn"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
            </div>
        </div>
    </main>
    
    <script src="<?php echo ASSETS_URL; ?>js/dashboard.js"></script>
</body>
</html>
