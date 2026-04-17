<?php
/**
 * Eventura - Teacher Participants View
 */
require_once '../config.php';
startSecureSession();
requireRole('teacher');

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get all events with participants (not just teacher's events)
    $stmt = $pdo->query("SELECT e.id, e.title, e.event_date, 
                          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as participant_count
                          FROM events e 
                          WHERE e.event_date >= CURDATE() AND (e.status = 'published' OR e.status IS NULL)
                          ORDER BY e.event_date ASC");
    $events = $stmt->fetchAll();
    
    // Get selected event participants
    $selected_event = null;
    $participants = [];
    
    if (isset($_GET['event_id'])) {
        $event_id = intval($_GET['event_id']);
        
        // Get any published event (not just teacher's events)
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND (status = 'published' OR status IS NULL)");
        $stmt->execute([$event_id]);
        $selected_event = $stmt->fetch();
        
        if ($selected_event) {
            $stmt = $pdo->prepare("SELECT er.*, u.full_name, u.email, 
                                  sp.student_id, sp.roll_no, sp.course, sp.year,
                                  qr.entry_used, qr.food_used
                                  FROM event_registrations er
                                  JOIN users u ON er.user_id = u.id
                                  JOIN student_profiles sp ON er.student_profile_id = sp.id
                                  LEFT JOIN qr_codes qr ON er.id = qr.registration_id
                                  WHERE er.event_id = ?
                                  ORDER BY er.registration_date DESC");
            $stmt->execute([$event_id]);
            $participants = $stmt->fetchAll();
        }
    }
    
} catch (Exception $e) {
    $events = [];
    $participants = [];
}

$page_title = 'Participants';
include '../includes/header.php';
?>
<div class="page-header">
    <h1>Event Participants</h1>
</div>

<div class="form-group" style="max-width: 400px; margin-bottom: 30px;">
    <label>Select Event</label>
    <select class="form-control" onchange="location = 'participants.php?event_id=' + this.value">
        <option value="">-- Select an event --</option>
        <?php foreach ($events as $event): ?>
            <option value="<?php echo $event['id']; ?>" <?php echo ($selected_event && $selected_event['id'] == $event['id']) ? 'selected' : ''; ?>>
                <?php echo $event['title']; ?> (<?php echo $event['participant_count']; ?> participants)
            </option>
        <?php endforeach; ?>
    </select>
</div>

<?php if ($selected_event): ?>
    <div class="clay-card" style="padding: 20px; margin-bottom: 20px;">
        <h3><?php echo $selected_event['title']; ?></h3>
        <p><i class="fas fa-calendar"></i> <?php echo formatDate($selected_event['event_date']); ?> | 
           <i class="fas fa-users"></i> <?php echo count($participants); ?> registered</p>
    </div>
    
    <!-- Desktop Table View -->
    <div class="table-container" style="display: block;">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Course/Year</th>
                    <th>Entry Status</th>
                    <th>Food Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($participants)): ?>
                    <tr><td colspan="5" class="text-center">No participants yet.</td></tr>
                <?php else: foreach ($participants as $p): ?>
                    <tr>
                        <td><strong><?php echo $p['full_name']; ?></strong></td>
                        <td><?php echo $p['student_id']; ?> (Roll: <?php echo $p['roll_no']; ?>)</td>
                        <td><?php echo $p['course']; ?> - Year <?php echo $p['year']; ?></td>
                        <td><?php echo $p['entry_used'] ? '<span class="badge badge-success"><i class="fas fa-check"></i> Entered</span>' : '<span class="badge badge-warning"><i class="fas fa-clock"></i> Not Entered</span>'; ?></td>
                        <td><?php 
                            if ($selected_event['food_available']) {
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
                <p>No participants yet.</p>
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
                                <span class="badge badge-success"><i class="fas fa-check"></i> Entered</span>
                            <?php else: ?>
                                <span class="badge badge-warning"><i class="fas fa-clock"></i> Not Entered</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="participant-details">
                        <div class="detail-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span><?php echo $p['course']; ?> - Year <?php echo $p['year']; ?></span>
                        </div>
                        <div class="detail-item">
                            <i class="fas fa-calendar"></i>
                            <span>Registered: <?php echo date('M d, Y', strtotime($p['registration_date'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="participant-actions">
                        <div class="action-item">
                            <i class="fas fa-door-open"></i>
                            <span>Entry: <?php echo $p['entry_used'] ? 'Scanned' : 'Not Scanned'; ?></span>
                        </div>
                        <div class="action-item">
                            <i class="fas fa-utensils"></i>
                            <span>Food: 
                                <?php 
                                if ($selected_event['food_available']) {
                                    echo $p['food_used'] ? '<span class="badge badge-success">Redeemed</span>' : ($p['entry_used'] ? '<span class="badge badge-warning">Available</span>' : '<span class="badge badge-secondary">Not Entered</span>');
                                } else {
                                    echo '<span class="badge badge-secondary">No Food</span>';
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="empty-state clay-card">
        <i class="fas fa-users"></i>
        <p>Select an event to view participants.</p>
    </div>
<?php endif; ?>

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
