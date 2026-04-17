<?php
/**
 * Eventura - Reports & CSV Export (Admin)
 */
require_once '../config.php';
startSecureSession();
requireRole('admin');

try {
    $pdo = getDBConnection();
    
    // Get all events for filter
    $stmt = $pdo->query("SELECT id, title FROM events ORDER BY event_date DESC");
    $events = $stmt->fetchAll();
    
} catch (Exception $e) {
    $events = [];
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $event_id = intval($_GET['event_id'] ?? 0);
    
    try {
        if ($event_id) {
            // Export specific event participants
            $stmt = $pdo->prepare("SELECT u.full_name, sp.student_id, sp.roll_no, sp.course, sp.year,
                                  sp.department, sp.phone, e.title as event_title, e.event_date,
                                  er.registration_date, er.status,
                                  CASE WHEN qr.entry_used THEN 'Yes' ELSE 'No' END as attended,
                                  CASE WHEN qr.food_used THEN 'Yes' ELSE 'No' END as food_redeemed
                                  FROM event_registrations er
                                  JOIN users u ON er.user_id = u.id
                                  JOIN student_profiles sp ON er.student_profile_id = sp.id
                                  JOIN events e ON er.event_id = e.id
                                  LEFT JOIN qr_codes qr ON er.id = qr.registration_id
                                  WHERE er.event_id = ?");
            $stmt->execute([$event_id]);
            $filename = 'event_participants_' . $event_id . '_' . date('Y-m-d') . '.csv';
        } else {
            // Export all registrations
            $stmt = $pdo->query("SELECT u.full_name, sp.student_id, sp.roll_no, sp.course, sp.year,
                               e.title as event_title, e.event_date, er.status
                               FROM event_registrations er
                               JOIN users u ON er.user_id = u.id
                               JOIN student_profiles sp ON er.student_profile_id = sp.id
                               JOIN events e ON er.event_id = e.id
                               ORDER BY e.event_date DESC");
            $filename = 'all_registrations_' . date('Y-m-d') . '.csv';
        }
        
        $data = $stmt->fetchAll();
        
        if (empty($data)) {
            setFlashMessage('error', 'No data to export.');
            redirect(SITE_URL . '/admin/reports.php');
        }
        
        // Output CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Headers
        fputcsv($output, array_keys($data[0]));
        
        // Data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
        
    } catch (Exception $e) {
        setFlashMessage('error', 'Export failed.');
        redirect(SITE_URL . '/admin/reports.php');
    }
}

$page_title = 'Reports';
include '../includes/header.php';
?>
<div class="page-header">
    <h1>Reports & Exports</h1>
</div>
<div class="clay-card" style="padding: 30px; max-width: 600px;">
    <h3 style="margin-bottom: 20px;"><i class="fas fa-download"></i> Export Data</h3>
    
    <form method="GET" action="">
        <input type="hidden" name="export" value="csv">
        
        <div class="form-group">
            <label>Select Event (Optional - leave empty for all)</label>
            <select name="event_id" class="form-control">
                <option value="">All Events</option>
                <?php foreach ($events as $event): ?>
                    <option value="<?php echo $event['id']; ?>"><?php echo $event['title']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-file-csv"></i> Export as CSV
        </button>
    </form>
    
    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border-color);">
        <h4 style="margin-bottom: 15px;">Quick Reports</h4>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?export=csv" class="btn btn-secondary btn-sm">All Registrations</a>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
