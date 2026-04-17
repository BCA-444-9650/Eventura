<?php
require_once __DIR__ . '/../config.php';
startSecureSession();

// Require login and admin role
requireAuth();
if (!hasRole('admin')) {
    setFlashMessage('error', 'Access denied. Admin access required.');
    redirect('../dashboard.php');
}

try {
    $pdo = getDBConnection();
    
    // Get event statistics
    $event_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_events,
            COUNT(CASE WHEN status = 'published' THEN 1 END) as published_events,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_events,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_events,
            COUNT(CASE WHEN event_date >= CURDATE() THEN 1 END) as upcoming_events
        FROM events
    ")->fetch();
    
    // Get registration statistics
    $registration_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_registrations,
            COUNT(CASE WHEN status = 'registered' THEN 1 END) as active_registrations,
            COUNT(CASE WHEN status = 'attended' THEN 1 END) as attended_registrations,
            COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_registrations
        FROM event_registrations
    ")->fetch();
    
    // Get QR statistics
    $qr_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_qr_codes,
            COUNT(CASE WHEN entry_used = TRUE THEN 1 END) as entry_used,
            COUNT(CASE WHEN food_used = TRUE THEN 1 END) as food_used
        FROM qr_codes
    ")->fetch();
    
    // Get user statistics
    $user_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_users,
            COUNT(CASE WHEN role = 'student' THEN 1 END) as students,
            COUNT(CASE WHEN role = 'teacher' THEN 1 END) as teachers,
            COUNT(CASE WHEN role = 'admin' THEN 1 END) as admins,
            COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_users
        FROM users
    ")->fetch();
    
} catch (Exception $e) {
    setFlashMessage('error', 'Error loading analytics: ' . $e->getMessage());
    $event_stats = $registration_stats = $qr_stats = $user_stats = [];
}

$flash = getFlashMessage();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Eventura</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        .analytics-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .analytics-header {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .analytics-header h2 {
            color: var(--text-primary);
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .analytics-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .analytics-card {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }
        
        .analytics-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .card-icon {
            font-size: 32px;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: var(--bg-tertiary);
            border-radius: var(--border-radius-sm);
        }
        
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }
        
        .chart-container {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
        }
        
        .chart-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 25px;
        }
        
        .chart-placeholder {
            height: 300px;
            background: var(--bg-tertiary);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        .list-container {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
        }
        
        .list-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 25px;
        }
        
        .data-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .data-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: var(--bg-tertiary);
            border-radius: var(--border-radius-sm);
            transition: var(--transition);
        }
        
        .data-item:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-light);
        }
        
        .data-label {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .data-value {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: var(--bg-tertiary);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 10px;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border-radius: 4px;
            transition: width 1s ease;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <?php include '../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="main-content">
            <header class="top-header">
                <h1>Analytics Dashboard</h1>
                <div class="header-actions">
                    <a href="export-data.php" class="btn btn-primary">Export Data</a>
                </div>
            </header>

            <div class="content-wrapper">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <div class="analytics-container">
                    <!-- Header -->
                    <div class="analytics-header">
                        <h2>📊 System Analytics</h2>
                        <p>Comprehensive overview of Eventura's performance and usage statistics</p>
                    </div>

                    <!-- Overview Statistics -->
                    <div class="analytics-grid">
                        <!-- Events Overview -->
                        <div class="analytics-card">
                            <div class="card-header">
                                <div class="card-icon">📅</div>
                                <h3 class="card-title">Events Overview</h3>
                            </div>
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $event_stats['success'] ? $event_stats['stats']['total_events'] : 0; ?></div>
                                    <div class="stat-label">Total Events</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $event_stats['success'] ? $event_stats['stats']['upcoming_events'] : 0; ?></div>
                                    <div class="stat-label">Upcoming</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $event_stats['success'] ? $event_stats['stats']['past_events'] : 0; ?></div>
                                    <div class="stat-label">Past Events</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $event_stats['success'] ? $event_stats['stats']['events_with_food'] : 0; ?></div>
                                    <div class="stat-label">With Food</div>
                                </div>
                            </div>
                        </div>

                        <!-- Users Overview -->
                        <div class="analytics-card">
                            <div class="card-header">
                                <div class="card-icon">👥</div>
                                <h3 class="card-title">Users Overview</h3>
                            </div>
                            <div class="stat-grid">
                                <?php if ($user_stats['success'] && !empty($user_stats['stats']['by_role'])): ?>
                                    <?php foreach ($user_stats['stats']['by_role'] as $role_stat): ?>
                                        <div class="stat-item">
                                            <div class="stat-number"><?php echo $role_stat['count']; ?></div>
                                            <div class="stat-label"><?php echo ucfirst($role_stat['role']); ?>s</div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="stat-item">
                                        <div class="stat-number">0</div>
                                        <div class="stat-label">Total Users</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Registrations Overview -->
                        <div class="analytics-card">
                            <div class="card-header">
                                <div class="card-icon">📝</div>
                                <h3 class="card-title">Registrations</h3>
                            </div>
                            <div class="stat-grid">
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $registration_stats['success'] ? $registration_stats['stats']['total_registrations'] : 0; ?></div>
                                    <div class="stat-label">Total</div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-number"><?php echo $registration_stats['success'] ? $registration_stats['stats']['events_this_month'] : 0; ?></div>
                                    <div class="stat-label">This Month</div>
                                </div>
                            </div>
                        </div>

                        <!-- QR Codes Overview -->
                        <div class="analytics-card">
                            <div class="card-header">
                                <div class="card-icon">📱</div>
                                <h3 class="card-title">QR Codes</h3>
                            </div>
                            <div class="stat-grid">
                                <?php if ($qr_stats['success'] && !empty($qr_stats['stats']['overall_qr_stats'])): ?>
                                    <?php foreach ($qr_stats['stats']['overall_qr_stats'] as $qr_stat): ?>
                                        <div class="stat-item">
                                            <div class="stat-number"><?php echo $qr_stat['total']; ?></div>
                                            <div class="stat-label"><?php echo ucfirst($qr_stat['qr_type']); ?> QRs</div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="stat-item">
                                        <div class="stat-number">0</div>
                                        <div class="stat-label">Total QRs</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Section -->
                    <div class="chart-container">
                        <h3 class="chart-title">📈 Registration Trends</h3>
                        <div class="chart-placeholder">
                            📊 Chart visualization coming soon! (Integration with Chart.js recommended)
                        </div>
                    </div>

                    <!-- Popular Events -->
                    <div class="list-container">
                        <h3 class="list-title">🔥 Popular Events</h3>
                        <div class="data-list">
                            <?php if ($registration_stats['success'] && !empty($registration_stats['stats']['popular_events'])): ?>
                                <?php foreach ($registration_stats['stats']['popular_events'] as $event): ?>
                                    <div class="data-item">
                                        <span class="data-label"><?php echo htmlspecialchars($event['title']); ?></span>
                                        <span class="data-value"><?php echo $event['registration_count']; ?> registrations</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="data-item">
                                    <span class="data-label">No event data available</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Course Distribution -->
                    <div class="list-container">
                        <h3 class="list-title">🎓 Course Distribution</h3>
                        <div class="data-list">
                            <?php if ($user_stats['success'] && !empty($user_stats['stats']['courses'])): ?>
                                <?php foreach ($user_stats['stats']['courses'] as $course): ?>
                                    <div class="data-item">
                                        <span class="data-label"><?php echo htmlspecialchars($course['course']); ?></span>
                                        <span class="data-value"><?php echo $course['count']; ?> students</span>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo min(100, ($course['count'] / 10) * 100); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="data-item">
                                    <span class="data-label">No course data available</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Top Validators -->
                    <div class="list-container">
                        <h3 class="list-title">🏆 Top QR Validators</h3>
                        <div class="data-list">
                            <?php if ($qr_stats['success'] && !empty($qr_stats['stats']['top_validators'])): ?>
                                <?php foreach ($qr_stats['stats']['top_validators'] as $validator): ?>
                                    <div class="data-item">
                                        <span class="data-label"><?php echo htmlspecialchars($validator['name']); ?></span>
                                        <span class="data-value"><?php echo $validator['validations']; ?> validations</span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="data-item">
                                    <span class="data-label">No validation data available</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Animate progress bars on page load
        document.addEventListener('DOMContentLoaded', function() {
            const progressFills = document.querySelectorAll('.progress-fill');
            progressFills.forEach((fill, index) => {
                setTimeout(() => {
                    fill.style.width = fill.style.width;
                }, index * 100);
            });
        });
    </script>
</body>
</html>
