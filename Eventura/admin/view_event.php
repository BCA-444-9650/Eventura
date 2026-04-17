<?php
/**
 * Eventura - View Event Details (Shared for Admin/Teacher)
 */
require_once '../config.php';
startSecureSession();

// Allow admin and teacher
if (!hasRole('admin') && !hasRole('teacher')) {
    setFlashMessage('error', 'Access denied.');
    redirect(SITE_URL . '/dashboard.php');
}

$event_id = intval($_GET['id'] ?? 0);

if (!$event_id) {
    setFlashMessage('error', 'Invalid event.');
    redirect(SITE_URL . (hasRole('admin') ? '/admin/events.php' : '/teacher/my_events.php'));
}

try {
    $pdo = getDBConnection();
    
    // Get event details
    $stmt = $pdo->prepare("SELECT e.*, u.full_name as creator_name 
                          FROM events e 
                          JOIN users u ON e.created_by = u.id 
                          WHERE e.id = ?");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        setFlashMessage('error', 'Event not found.');
        redirect(SITE_URL . (hasRole('admin') ? '/admin/events.php' : '/teacher/my_events.php'));
    }
    
    // Get participants
    $stmt = $pdo->prepare("SELECT er.*, u.full_name, u.email, sp.student_id, sp.roll_no, sp.course, sp.year,
                          qr.entry_used, qr.food_used
                          FROM event_registrations er
                          JOIN users u ON er.user_id = u.id
                          JOIN student_profiles sp ON er.student_profile_id = sp.id
                          LEFT JOIN qr_codes qr ON er.id = qr.registration_id
                          WHERE er.event_id = ?
                          ORDER BY er.registration_date DESC");
    $stmt->execute([$event_id]);
    $participants = $stmt->fetchAll();
    
    // Stats
    $total = count($participants);
    $attended = count(array_filter($participants, fn($p) => $p['entry_used']));
    $food_redeemed = count(array_filter($participants, fn($p) => $p['food_used']));
    
} catch (Exception $e) {
    setFlashMessage('error', 'Error loading event.');
    redirect(SITE_URL . (hasRole('admin') ? '/admin/events.php' : '/teacher/my_events.php'));
}

$page_title = 'Event Details';
include '../includes/header.php';
?>
<style>
.event-details { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
.event-info { padding: 30px; }
.event-stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
.stat-box { text-align: center; padding: 20px; background: var(--bg-primary); border-radius: var(--radius-md); }
.stat-box h4 { font-size: 2rem; color: var(--primary); }

/* Only fix description alignment */
.event-info p[style*="margin-top: 15px"] {
    line-height: 1.6;
    padding: var(--spacing-md);
    background: var(--bg-primary);
    border-radius: var(--radius-sm);
    border-left: 4px solid var(--primary);
    margin-top: var(--spacing-lg) !important;
    margin-bottom: var(--spacing-md) !important;
}

.event-info p[style*="margin-top: 15px"] strong {
    display: block;
    margin-bottom: var(--spacing-sm);
    color: var(--text-primary);
}

.event-info p[style*="margin-top: 15px"] br {
    display: none;
}

@media (max-width: 768px) { 
    .event-details { grid-template-columns: 1fr; } 
    .event-stats { grid-template-columns: repeat(3, 1fr) !important; gap: 10px; }
    .stat-box { 
        padding: 15px; 
        background: var(--bg-card);
        box-shadow: var(--clay-shadow);
        border: 1px solid var(--border-color);
        transition: all var(--transition-fast);
    }
    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: var(--clay-shadow-hover);
    }
    .stat-box h4 { font-size: 1.5rem; }
    .stat-box p { font-size: var(--font-size-sm); margin-top: 5px; }
    
    .event-info p[style*="margin-top: 15px"] {
        padding: var(--spacing-sm);
        font-size: var(--font-size-sm);
    }
}

@media (max-width: 480px) { 
    .event-stats { grid-template-columns: 1fr !important; gap: 15px; }
    .stat-box { padding: 20px; }
    .stat-box h4 { font-size: 1.8rem; }
    .stat-box p { font-size: var(--font-size-md); }
    
    .event-info p[style*="margin-top: 15px"] {
        padding: var(--spacing-sm);
        font-size: var(--font-size-sm);
    }
}
</style>

<div class="page-header">
    <h1><?php echo $event['title']; ?></h1>
    <a href="<?php echo hasRole('admin') ? 'events.php' : 'my_events.php'; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="event-details">
    <div class="event-info clay-card">
        <h3 style="margin-bottom: 20px;">Event Information</h3>
        <p><i class="fas fa-calendar"></i> <strong>Date:</strong> <?php echo formatDate($event['event_date']); ?></p>
        <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo formatTime($event['event_time']); ?></p>
        <p><i class="fas fa-map-marker-alt"></i> <strong>Venue:</strong> <?php echo $event['venue']; ?></p>
        <p><i class="fas fa-user"></i> <strong>Created by:</strong> <?php echo $event['creator_name']; ?></p>
        <?php if ($event['description']): ?>
            <p style="margin-top: 15px;"><strong>Description:</strong> <?php echo nl2br($event['description']); ?></p>
        <?php endif; ?>
        <?php if ($event['food_available']): ?>
            <span class="badge badge-success" style="margin-top: 15px;"><i class="fas fa-utensils"></i> Food Available</span>
        <?php endif; ?>
    </div>
    
    <div class="clay-card" style="padding: 30px;">
        <h3 style="margin-bottom: 20px;">Statistics</h3>
        <div class="event-stats">
            <div class="stat-box">
                <h4><?php echo $total; ?></h4>
                <p>Registered</p>
            </div>
            <div class="stat-box">
                <h4><?php echo $attended; ?></h4>
                <p>Attended</p>
            </div>
            <div class="stat-box">
                <h4><?php echo $food_redeemed; ?></h4>
                <p>Food Redeemed</p>
            </div>
        </div>
        <a href="qr_scanner.php" class="btn btn-primary btn-block" style="margin-top: 20px;"><i class="fas fa-qrcode"></i> Open QR Scanner</a>
    </div>
</div>

<div class="content-section">
    <h3 style="margin-bottom: 20px;">Participants (<?php echo $total; ?>)</h3>
    
    <!-- Desktop Table View -->
    <div class="table-container" style="display: block;">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Course</th>
                    <th>Registered</th>
                    <th>Entry</th>
                    <th>Food</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($participants)): ?>
                    <tr><td colspan="6" class="text-center">No registrations yet.</td></tr>
                <?php else: foreach ($participants as $p): ?>
                    <tr>
                        <td><strong><?php echo $p['full_name']; ?></strong></td>
                        <td><?php echo $p['student_id']; ?> (Roll: <?php echo $p['roll_no']; ?>)</td>
                        <td><?php echo $p['course']; ?> Y<?php echo $p['year']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($p['registration_date'])); ?></td>
                        <td><?php echo $p['entry_used'] ? '<span class="badge badge-success">Used</span>' : '<span class="badge badge-warning">Pending</span>'; ?></td>
                        <td><?php 
                            if ($event['food_available']) {
                                echo $p['food_used'] ? '<span class="badge badge-success"><i class="fas fa-check-circle"></i> Redeemed</span>' : ($p['entry_used'] ? '<span class="badge badge-warning"><i class="fas fa-utensils"></i> Available</span>' : '<span class="badge badge-secondary"><i class="fas fa-clock"></i> Not Entered</span>');
                            } else {
                                echo '<span class="badge badge-secondary"><i class="fas fa-times-circle"></i> No Food</span>';
                            }
                        ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Mobile Card View -->
    <div class="mobile-participants-container" style="display: none;">
        <?php if (empty($participants)): ?>
            <div class="empty-state">
                <i class="fas fa-users"></i>
                <p>No registrations yet.</p>
            </div>
        <?php else: ?>
            <?php foreach ($participants as $p): ?>
                <div class="participant-card">
                    <div class="participant-header">
                        <div class="participant-name">
                            <h4><?php echo $p['full_name']; ?></h4>
                            <span class="participant-id"><?php echo $p['student_id']; ?> (Roll: <?php echo $p['roll_no']; ?>)</span>
                        </div>
                        <div class="participant-status">
                            <?php if ($p['entry_used']): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Attended</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="participant-details">
                        <div class="detail-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?php echo $p['course']; ?> Y<?php echo $p['year']; ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar"></i>
                            <span>Registered: <?php echo date('M d, Y', strtotime($p['registration_date'])); ?></span>
                        </div>
                        <?php if ($event['food_available']): ?>
                            <div class="detail-item">
                                <i class="fas fa-utensils"></i>
                                <span>Food: <?php echo $p['food_used'] ? '<span class="badge badge-success">Redeemed</span>' : ($p['entry_used'] ? '<span class="badge badge-warning">Available</span>' : '<span class="badge badge-secondary">Not Entered</span>'); ?></span>
                            </div>
                        <?php else: ?>
                            <div class="detail-item">
                                <i class="fas fa-utensils"></i>
                                <span>Food: <span class="badge badge-secondary">No Food</span></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="participant-actions">
                        <div class="action-item">
                            <i class="fas fa-qrcode"></i>
                            <span>Entry: <?php echo $p['entry_used'] ? 'Scanned' : 'Not Scanned'; ?></span>
                        </div>
                        <?php if ($event['food_available']): ?>
                            <div class="action-item">
                                <i class="fas fa-utensils"></i>
                                <span>Food: <?php echo $p['food_used'] ? 'Redeemed' : 'Pending'; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
// Handle responsive view switching
function handleResponsiveView() {
    const tableContainer = document.querySelector('.table-container');
    const mobileContainer = document.querySelector('.mobile-participants-container');
    
    if (window.innerWidth <= 768) {
        // Mobile view
        if (tableContainer) tableContainer.style.display = 'none';
        if (mobileContainer) mobileContainer.style.display = 'block';
    } else {
        // Desktop view
        if (tableContainer) tableContainer.style.display = 'block';
        if (mobileContainer) mobileContainer.style.display = 'none';
    }
}

// Initialize on load and resize
document.addEventListener('DOMContentLoaded', handleResponsiveView);
window.addEventListener('resize', handleResponsiveView);
</script>
