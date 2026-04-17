<?php
/**
 * Eventura - Student History Page
 */
require_once '../config.php';
startSecureSession();
requireRole('student');

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get student profile for display
    $stmt = $pdo->prepare("SELECT sp.* FROM student_profiles sp WHERE sp.user_id = ?");
    $stmt->execute([$user_id]);
    $student_profile = $stmt->fetch();
    
    // Get all event registrations with event details
    $stmt = $pdo->prepare("SELECT er.*, e.title, e.event_date, e.event_time, e.venue, e.status as event_status,
                                 e.food_available, e.description
                          FROM event_registrations er 
                          JOIN events e ON er.event_id = e.id 
                          WHERE er.user_id = ? 
                          ORDER BY e.event_date DESC, e.event_time DESC");
    $stmt->execute([$user_id]);
    $registrations = $stmt->fetchAll();
    
    // Get QR codes for registrations
    foreach ($registrations as &$reg) {
        $stmt = $pdo->prepare("SELECT qr_data, qr_image_path, entry_used, entry_used_at, food_used, food_used_at 
                              FROM qr_codes WHERE registration_id = ?");
        $stmt->execute([$reg['id']]);
        $qr_info = $stmt->fetch();
        $reg['qr_info'] = $qr_info;
    }
    
} catch (Exception $e) {
    $registrations = [];
    $student_profile = null;
    setFlashMessage('error', 'Error loading history. Please try again.');
}

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event History - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/style.css">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .history-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: var(--spacing-lg);
        }
        
        .history-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: var(--radius-lg);
            padding: var(--spacing-xl);
            color: white;
            margin-bottom: var(--spacing-lg);
            position: relative;
            overflow: hidden;
        }
        
        .history-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 250px;
            height: 250px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .history-header-content {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
        }
        
        .history-icon-large {
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
        
        .history-info-main {
            flex: 1;
        }
        
        .history-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--spacing-xs);
        }
        
        .history-subtitle {
            font-size: var(--font-size-md);
            opacity: 0.9;
        }
        
        .history-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .stat-card-history {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            text-align: center;
            box-shadow: var(--clay-shadow);
            transition: transform var(--transition-fast);
        }
        
        .stat-card-history:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon-history {
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
        
        .history-filters {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            margin-bottom: var(--spacing-lg);
            box-shadow: var(--clay-shadow);
        }
        
        .filter-group {
            display: flex;
            gap: var(--spacing-md);
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-select {
            padding: 10px 16px;
            border: 2px solid var(--border-color);
            border-radius: var(--radius-md);
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: inherit;
            font-size: var(--font-size-sm);
            min-width: 150px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .history-list {
            display: grid;
            gap: var(--spacing-md);
        }
        
        .history-item {
            background: var(--bg-card);
            border-radius: var(--radius-md);
            padding: var(--spacing-lg);
            box-shadow: var(--clay-shadow);
            transition: all var(--transition-fast);
            border-left: 4px solid transparent;
        }
        
        .history-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--clay-shadow-hover);
        }
        
        .history-item.attended {
            border-left-color: var(--success);
        }
        
        .history-item.registered {
            border-left-color: var(--info);
        }
        
        .history-item.cancelled {
            border-left-color: var(--danger);
        }
        
        .history-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: var(--spacing-md);
        }
        
        .history-item-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--spacing-xs);
        }
        
        .history-item-date {
            color: var(--text-muted);
            font-size: var(--font-size-sm);
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
        }
        
        .history-item-status {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: var(--spacing-xs);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 16px;
            font-size: var(--font-size-xs);
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .status-badge.attended {
            background: var(--success-light);
            color: var(--success);
        }
        
        .status-badge.registered {
            background: var(--info-light);
            color: var(--info);
        }
        
        .status-badge.cancelled {
            background: var(--danger-light);
            color: var(--danger);
        }
        
        .history-item-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-md);
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
        }
        
        .detail-item i {
            color: var(--primary);
            width: 16px;
        }
        
        .history-item-actions {
            display: flex;
            gap: var(--spacing-sm);
            flex-wrap: wrap;
        }
        
        .btn-view-ticket {
            background: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: var(--font-size-sm);
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            transition: all var(--transition-fast);
        }
        
        .btn-view-ticket:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }
        
        .btn-view-event {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            padding: 8px 16px;
            border-radius: var(--radius-md);
            text-decoration: none;
            font-size: var(--font-size-sm);
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-xs);
            transition: all var(--transition-fast);
        }
        
        .btn-view-event:hover {
            background: var(--border-color);
            transform: translateY(-1px);
        }
        
        .qr-status {
            display: flex;
            align-items: center;
            gap: var(--spacing-xs);
            font-size: var(--font-size-xs);
            color: var(--text-muted);
        }
        
        .qr-status.used {
            color: var(--success);
        }
        
        .qr-status.unused {
            color: var(--warning);
        }
        
        .empty-history {
            text-align: center;
            padding: var(--spacing-xl);
            color: var(--text-muted);
        }
        
        .empty-history i {
            font-size: 3rem;
            margin-bottom: var(--spacing-lg);
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .history-container {
                padding: var(--spacing-md);
            }
            
            .history-header-content {
                flex-direction: column;
                text-align: center;
                gap: var(--spacing-md);
            }
            
            .history-item-header {
                flex-direction: column;
                gap: var(--spacing-sm);
            }
            
            .history-item-status {
                align-items: flex-start;
            }
            
            .history-item-details {
                grid-template-columns: 1fr;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-select {
                width: 100%;
            }
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
            <a href="my_tickets.php">My Tickets</a>
            <a href="history.php" class="active">History</a>
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
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $flash['message']; ?>
            </div>
        <?php endif; ?>
        
        <div class="history-container">
            <div class="history-header">
                <div class="history-header-content">
                    <div class="history-icon-large">
                        <i class="fas fa-history"></i>
                    </div>
                    <div class="history-info-main">
                        <h1 class="history-title">Event History</h1>
                        <p class="history-subtitle">Your complete event participation record</p>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($registrations)): ?>
                <div class="history-stats">
                    <div class="stat-card-history">
                        <div class="stat-icon-history">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number"><?php echo count(array_filter($registrations, fn($r) => $r['status'] === 'attended')); ?></div>
                        <div class="stat-label">Attended Events</div>
                    </div>
                    
                    <div class="stat-card-history">
                        <div class="stat-icon-history">
                            <i class="fas fa-ticket-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo count(array_filter($registrations, fn($r) => $r['status'] === 'registered')); ?></div>
                        <div class="stat-label">Registered Events</div>
                    </div>
                    
                    <div class="stat-card-history">
                        <div class="stat-icon-history">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo count(array_filter($registrations, fn($r) => $r['status'] === 'cancelled')); ?></div>
                        <div class="stat-label">Cancelled Events</div>
                    </div>
                    
                    <div class="stat-card-history">
                        <div class="stat-icon-history">
                            <i class="fas fa-qrcode"></i>
                        </div>
                        <div class="stat-number"><?php echo count(array_filter($registrations, fn($r) => $r['qr_info']['entry_used'])); ?></div>
                        <div class="stat-label">QR Codes Used</div>
                    </div>
                </div>
                
                <div class="history-filters">
                    <div class="filter-group">
                        <select class="filter-select" id="statusFilter" onchange="filterHistory()">
                            <option value="all">All Status</option>
                            <option value="attended">Attended</option>
                            <option value="registered">Registered</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        
                        <select class="filter-select" id="dateFilter" onchange="filterHistory()">
                            <option value="all">All Dates</option>
                            <option value="upcoming">Upcoming</option>
                            <option value="past">Past Events</option>
                            <option value="this_month">This Month</option>
                        </select>
                    </div>
                </div>
                
                <div class="history-list" id="historyList">
                    <?php foreach ($registrations as $registration): ?>
                        <div class="history-item <?php echo $registration['status']; ?>" 
                             data-status="<?php echo $registration['status']; ?>" 
                             data-date="<?php echo $registration['event_date']; ?>">
                            <div class="history-item-header">
                                <div>
                                    <h3 class="history-item-title"><?php echo htmlspecialchars($registration['title']); ?></h3>
                                    <div class="history-item-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo formatDate($registration['event_date']); ?> at <?php echo formatTime($registration['event_time']); ?>
                                    </div>
                                </div>
                                <div class="history-item-status">
                                    <span class="status-badge <?php echo $registration['status']; ?>">
                                        <?php echo ucfirst($registration['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="history-item-details">
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($registration['venue']); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-utensils"></i>
                                    <span><?php echo $registration['food_available'] ? 'Food Available' : 'No Food'; ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <i class="fas fa-calendar-plus"></i>
                                    <span>Registered: <?php echo formatDate($registration['registration_date']); ?></span>
                                </div>
                                
                                <?php if ($registration['qr_info']): ?>
                                    <div class="detail-item">
                                        <i class="fas fa-qrcode"></i>
                                        <span class="qr-status <?php echo $registration['qr_info']['entry_used'] ? 'used' : 'unused'; ?>">
                                            Entry: <?php echo $registration['qr_info']['entry_used'] ? 'Used' : 'Not Used'; ?>
                                            <?php if ($registration['qr_info']['entry_used_at']): ?>
                                                (<?php echo formatDate($registration['qr_info']['entry_used_at']); ?>)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($registration['food_available']): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-hamburger"></i>
                                            <span class="qr-status <?php echo $registration['qr_info']['food_used'] ? 'used' : 'unused'; ?>">
                                                Food: <?php echo $registration['qr_info']['food_used'] ? 'Used' : 'Not Used'; ?>
                                                <?php if ($registration['qr_info']['food_used_at']): ?>
                                                    (<?php echo formatDate($registration['qr_info']['food_used_at']); ?>)
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="history-item-actions">
                                <?php if ($registration['status'] === 'registered'): ?>
                                    <a href="view_ticket.php?id=<?php echo $registration['id']; ?>" class="btn-view-ticket">
                                        <i class="fas fa-ticket-alt"></i> View Ticket
                                    </a>
                                <?php endif; ?>
                                
                                <a href="view_event.php?id=<?php echo $registration['event_id']; ?>" class="btn-view-event">
                                    <i class="fas fa-eye"></i> View Event
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-history">
                    <i class="fas fa-history"></i>
                    <h3>No Event History</h3>
                    <p>You haven't registered for any events yet.</p>
                    <a href="events.php" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> Browse Events
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="<?php echo ASSETS_URL; ?>js/dashboard.js"></script>
    <script>
        function filterHistory() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const historyItems = document.querySelectorAll('.history-item');
            
            historyItems.forEach(item => {
                const status = item.dataset.status;
                const eventDate = new Date(item.dataset.date);
                const today = new Date();
                
                let showByStatus = statusFilter === 'all' || status === statusFilter;
                let showByDate = true;
                
                if (dateFilter === 'upcoming') {
                    showByDate = eventDate >= today;
                } else if (dateFilter === 'past') {
                    showByDate = eventDate < today;
                } else if (dateFilter === 'this_month') {
                    showByDate = eventDate.getMonth() === today.getMonth() && 
                                  eventDate.getFullYear() === today.getFullYear();
                }
                
                item.style.display = showByStatus && showByDate ? 'block' : 'none';
            });
        }
        
        // Initialize filters
        filterHistory();
    </script>
</body>
</html>
