<?php
/**
 * Eventura - Admin All Registrations
 */
require_once '../config.php';
startSecureSession();
requireRole('admin');

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("SELECT er.*, e.title as event_title, e.event_date,
                        u.full_name, u.email, sp.student_id, sp.roll_no, sp.course,
                        qr.entry_used, qr.food_used
                        FROM event_registrations er
                        JOIN events e ON er.event_id = e.id
                        JOIN users u ON er.user_id = u.id
                        JOIN student_profiles sp ON er.student_profile_id = sp.id
                        LEFT JOIN qr_codes qr ON er.id = qr.registration_id
                        ORDER BY er.registration_date DESC");
    $registrations = $stmt->fetchAll();
    
} catch (Exception $e) {
    $registrations = [];
}

$page_title = 'All Registrations';
include '../includes/header.php';
?>
<div class="page-header">
    <h1>All Registrations</h1>
    <a href="reports.php" class="btn btn-primary"><i class="fas fa-download"></i> Export</a>
</div>

<!-- Desktop Table View -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Student</th>
                <th>Student ID</th>
                <th>Event</th>
                <th>Event Date</th>
                <th>Registered</th>
                <th>Entry</th>
                <th>Food</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registrations)): ?>
                <tr><td colspan="7" class="text-center">No registrations found.</td></tr>
            <?php else: foreach ($registrations as $r): ?>
                <tr>
                    <td><strong><?php echo $r['full_name']; ?></strong></td>
                    <td><?php echo $r['student_id']; ?> (<?php echo $r['roll_no']; ?>)</td>
                    <td><?php echo $r['event_title']; ?></td>
                    <td><?php echo formatDate($r['event_date']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($r['registration_date'])); ?></td>
                    <td><?php echo $r['entry_used'] ? '<span class="badge badge-success"><i class="fas fa-check"></i> Used</span>' : '<span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>'; ?></td>
                    <td><?php echo $r['food_used'] ? '<span class="badge badge-success"><i class="fas fa-check"></i> Redeemed</span>' : ($r['entry_used'] ? '<span class="badge badge-warning"><i class="fas fa-utensils"></i> Available</span>' : '-'); ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="mobile-registrations-container" style="display: none;">
    <?php if (empty($registrations)): ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>No registrations found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($registrations as $r): ?>
            <div class="registration-card">
                <div class="registration-header">
                    <div class="student-info">
                        <h4><?php echo $r['full_name']; ?></h4>
                        <span class="student-id"><?php echo $r['student_id']; ?> (Roll: <?php echo $r['roll_no']; ?>)</span>
                    </div>
                    <div class="registration-status">
                        <?php if ($r['entry_used']): ?>
                            <span class="badge badge-success"><i class="fas fa-check"></i> Entered</span>
                        <?php else: ?>
                            <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="registration-details">
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt"></i>
                        <div class="detail-content">
                            <span class="detail-label">Event</span>
                            <span class="detail-value"><?php echo $r['event_title']; ?></span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-calendar"></i>
                        <div class="detail-content">
                            <span class="detail-label">Event Date</span>
                            <span class="detail-value"><?php echo formatDate($r['event_date']); ?></span>
                        </div>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-clock"></i>
                        <div class="detail-content">
                            <span class="detail-label">Registered</span>
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($r['registration_date'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="registration-actions">
                    <div class="action-item">
                        <i class="fas fa-door-open"></i>
                        <span>Entry: <?php echo $r['entry_used'] ? 'Scanned' : 'Not Scanned'; ?></span>
                    </div>
                    <div class="action-item">
                        <i class="fas fa-utensils"></i>
                        <span>Food: <?php echo $r['food_used'] ? 'Redeemed' : ($r['entry_used'] ? 'Available' : 'Pending'); ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
