<?php
require_once __DIR__ . '/../config.php';
startSecureSession();

// Require login and admin role
requireAuth();
if (!hasRole('admin')) {
    setFlashMessage('error', 'Access denied. Admin access required.');
    redirect('../dashboard.php');
}

// Handle export requests
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['export'])) {
    $export_type = $_GET['export'];
    $format = $_GET['format'] ?? 'csv';
    
    switch ($export_type) {
        case 'users':
            exportUsers($format);
            break;
        case 'events':
            exportEvents($format);
            break;
        case 'registrations':
            exportRegistrations($format);
            break;
        case 'qr_usage':
            exportQRUsage($format);
            break;
        default:
            setFlashMessage('error', 'Invalid export type.');
            redirect('./');
    }
}

function exportUsers($format) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("
            SELECT u.id, u.full_name, u.email, u.role, u.profile_completed, u.created_at,
                   sp.student_id, sp.roll_no, sp.course, sp.year
            FROM users u
            LEFT JOIN student_profiles sp ON u.id = sp.user_id
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();
    } catch (Exception $e) {
        die('Error exporting users: ' . $e->getMessage());
    }
    
    $filename = 'eventura_users_' . date('Y-m-d') . '.' . $format;
    
    if ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Name', 'Email', 'Role', 'Profile Complete', 
            'Student ID', 'Roll Number', 'Course', 'Year', 'Created Date'
        ]);
        
        // CSV data
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['full_name'],
                $user['email'],
                $user['role'],
                $user['profile_completed'] ? 'Yes' : 'No',
                $user['student_id'] ?? 'N/A',
                $user['roll_no'] ?? 'N/A',
                $user['course'] ?? 'N/A',
                $user['year'] ?? 'N/A',
                $user['created_at']
            ]);
        }
        
        fclose($output);
    }
    exit;
}

function exportEvents($format) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->query("
            SELECT e.id, e.title, e.description, e.event_date, e.event_time, 
                   e.venue, e.food_available, e.max_participants, e.status, e.created_at,
                   u.full_name as created_by_name
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            ORDER BY e.created_at DESC
        ");
        $events = $stmt->fetchAll();
    } catch (Exception $e) {
        die('Error exporting events: ' . $e->getMessage());
    }
    
    $filename = 'eventura_events_' . date('Y-m-d') . '.' . $format;
    
    if ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'ID', 'Title', 'Description', 'Event Date', 'Event Time', 
            'Venue', 'Food Available', 'Max Participants', 'Created By', 'Created Date'
        ]);
        
        // CSV data
        foreach ($events as $event) {
            fputcsv($output, [
                $event['id'],
                $event['title'],
                $event['description'],
                $event['event_date'],
                $event['event_time'],
                $event['venue'],
                $event['food_available'] ? 'Yes' : 'No',
                $event['max_participants'] ?? 'Unlimited',
                $event['creator_name'],
                $event['created_at']
            ]);
        }
        
        fclose($output);
    }
    exit();
}

function exportRegistrations($registration_manager, $format) {
    // This is a simplified version - in production, you'd want to join with events and users
    $filename = 'eventura_registrations_' . date('Y-m-d') . '.' . $format;
    
    if ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Registration ID', 'Event ID', 'Student ID', 'Registration Date'
        ]);
        
        // In production, you'd fetch actual registration data with joins
        // For now, we'll create a placeholder
        fputcsv($output, [
            'Note: This is a sample export. In production, this would contain actual registration data.',
            '', '', '', ''
        ]);
        
        fclose($output);
    }
    exit();
}

function exportQRUsage($qr_manager, $format) {
    $validations_result = $qr_manager->getRecentValidations(1000);
    $validations = $validations_result['success'] ? $validations_result['validations'] : [];
    
    $filename = 'eventura_qr_usage_' . date('Y-m-d') . '.' . $format;
    
    if ($format == 'csv') {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'QR ID', 'Event Title', 'Student Name', 'QR Type', 
            'Used At', 'Validated By'
        ]);
        
        // CSV data
        foreach ($validations as $validation) {
            fputcsv($output, [
                $validation['id'],
                $validation['event_title'],
                $validation['student_name'],
                $validation['qr_type'],
                $validation['used_at'],
                $validation['validator_name'] ?? 'Unknown'
            ]);
        }
        
        fclose($output);
    }
    exit();
}

$flash = get_flash_message();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export Data - Eventura</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        .export-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .export-header {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .export-header h2 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 28px;
        }
        
        .export-header p {
            color: var(--text-secondary);
            font-size: 16px;
            line-height: 1.6;
        }
        
        .export-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .export-card {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            border: 1px solid var(--border-color);
        }
        
        .export-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
        }
        
        .export-icon {
            font-size: 48px;
            margin-bottom: 20px;
            display: block;
            text-align: center;
        }
        
        .export-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 15px;
            text-align: center;
        }
        
        .export-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .export-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .export-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: var(--border-radius-sm);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .export-btn.csv {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .export-btn.csv:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }
        
        .export-btn.excel {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: white;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
        }
        
        .export-btn.excel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
        }
        
        .export-stats {
            background: var(--bg-secondary);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-light);
            margin-bottom: 30px;
        }
        
        .export-stats h3 {
            color: var(--text-primary);
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: var(--bg-tertiary);
            border-radius: var(--border-radius-sm);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }
        
        .last-export {
            background: var(--bg-tertiary);
            border-radius: var(--border-radius-sm);
            padding: 15px;
            margin-top: 20px;
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
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
                <h1>Export Data</h1>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </header>

            <div class="content-wrapper">
                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type']; ?>">
                        <?php echo $flash['message']; ?>
                    </div>
                <?php endif; ?>

                <div class="export-container">
                    <!-- Header -->
                    <div class="export-header">
                        <h2>📊 Data Export Center</h2>
                        <p>Export system data in various formats for analysis and reporting. All exports are timestamped and include comprehensive data.</p>
                    </div>

                    <!-- Statistics -->
                    <div class="export-stats">
                        <h3>📈 Quick Statistics</h3>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php 
                                    $user_stats = $user_profile->getUserStatistics();
                                    echo $user_stats['success'] ? ($user_stats['stats']['by_role'][0]['count'] ?? 0) : 0;
                                    ?>
                                </div>
                                <div class="stat-label">Total Users</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php 
                                    $event_stats = $event_manager->getEventStatistics();
                                    echo $event_stats['success'] ? $event_stats['stats']['total_events'] : 0;
                                    ?>
                                </div>
                                <div class="stat-label">Total Events</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php 
                                    $reg_stats = $registration_manager->getRegistrationStatistics();
                                    echo $reg_stats['success'] ? $reg_stats['stats']['total_registrations'] : 0;
                                    ?>
                                </div>
                                <div class="stat-label">Total Registrations</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-number">
                                    <?php 
                                    $qr_stats = $qr_manager->getQRStatistics();
                                    echo $qr_stats['success'] ? ($qr_stats['stats']['overall_qr_stats'][0]['total'] ?? 0) : 0;
                                    ?>
                                </div>
                                <div class="stat-label">QR Codes Generated</div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Options -->
                    <div class="export-grid">
                        <!-- Users Export -->
                        <div class="export-card">
                            <div class="export-icon">👥</div>
                            <h3 class="export-title">Export Users</h3>
                            <p class="export-description">
                                Download complete user database including profiles, roles, and registration details. Perfect for user analysis and reporting.
                            </p>
                            <div class="export-actions">
                                <a href="?export=users&format=csv" class="export-btn csv">
                                    📄 Export as CSV
                                </a>
                                <a href="?export=users&format=excel" class="export-btn excel" onclick="alert('Excel export coming soon!'); return false;">
                                    📊 Export as Excel
                                </a>
                            </div>
                        </div>

                        <!-- Events Export -->
                        <div class="export-card">
                            <div class="export-icon">📅</div>
                            <h3 class="export-title">Export Events</h3>
                            <p class="export-description">
                                Export all events with details including dates, venues, participants, and creator information. Great for event analytics.
                            </p>
                            <div class="export-actions">
                                <a href="?export=events&format=csv" class="export-btn csv">
                                    📄 Export as CSV
                                </a>
                                <a href="?export=events&format=excel" class="export-btn excel" onclick="alert('Excel export coming soon!'); return false;">
                                    📊 Export as Excel
                                </a>
                            </div>
                        </div>

                        <!-- Registrations Export -->
                        <div class="export-card">
                            <div class="export-icon">📝</div>
                            <h3 class="export-title">Export Registrations</h3>
                            <p class="export-description">
                                Download all event registrations with student details and timestamps. Ideal for attendance tracking and participation analysis.
                            </p>
                            <div class="export-actions">
                                <a href="?export=registrations&format=csv" class="export-btn csv">
                                    📄 Export as CSV
                                </a>
                                <a href="?export=registrations&format=excel" class="export-btn excel" onclick="alert('Excel export coming soon!'); return false;">
                                    📊 Export as Excel
                                </a>
                            </div>
                        </div>

                        <!-- QR Usage Export -->
                        <div class="export-card">
                            <div class="export-icon">📱</div>
                            <h3 class="export-title">Export QR Usage</h3>
                            <p class="export-description">
                                Export QR code validation data including usage timestamps, validation details, and user information. Perfect for security analysis.
                            </p>
                            <div class="export-actions">
                                <a href="?export=qr_usage&format=csv" class="export-btn csv">
                                    📄 Export as CSV
                                </a>
                                <a href="?export=qr_usage&format=excel" class="export-btn excel" onclick="alert('Excel export coming soon!'); return false;">
                                    📊 Export as Excel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Last Export Info -->
                    <div class="last-export">
                        <strong>📝 Note:</strong> All exports are generated in real-time and include data as of the export time. 
                        Large datasets may take a few moments to process. CSV files can be opened in Excel, Google Sheets, or any spreadsheet application.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Add loading states for export buttons
        document.querySelectorAll('.export-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                if (this.getAttribute('onclick')) {
                    return; // Skip buttons with onclick handlers
                }
                
                const originalText = this.innerHTML;
                this.innerHTML = '⏳ Generating...';
                this.style.pointerEvents = 'none';
                
                // Reset after 3 seconds (in case of issues)
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.style.pointerEvents = 'auto';
                }, 3000);
            });
        });
    </script>
</body>
</html>
