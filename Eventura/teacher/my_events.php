<?php
/**
 * Eventura - Teacher My Events
 */
require_once '../config.php';
startSecureSession();
requireRole('teacher');

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get all published events (like admin dashboard)
    $stmt = $pdo->prepare("SELECT e.*, u.full_name as creator_name,
                          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count,
                          (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id AND status = 'attended') as attended_count
                          FROM events e 
                          JOIN users u ON e.created_by = u.id
                          WHERE e.status = 'published'
                          ORDER BY e.event_date DESC");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    $events = [];
}

$page_title = 'My Events';
include '../includes/header.php';
?>
<div class="page-header">
    <h1>My Events</h1>
    <a href="create_event.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Event</a>
</div>
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Date & Time</th>
                <th>Venue</th>
                <th>Registrations</th>
                <th>Attendance</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr><td colspan="6" class="text-center">No events created yet. <a href="create_event.php">Create one</a>.</td></tr>
            <?php else: foreach ($events as $event): ?>
                <tr>
                    <td><strong><?php echo $event['title']; ?></strong><?php if ($event['food_available']): ?> <span class="badge badge-success">Food</span><?php endif; ?></td>
                    <td><?php echo formatDate($event['event_date']); ?><br><small><?php echo formatTime($event['event_time']); ?></small></td>
                    <td><?php echo $event['venue']; ?></td>
                    <td><?php echo $event['registration_count']; ?></td>
                    <td><?php echo $event['attended_count']; ?> / <?php echo $event['registration_count']; ?></td>
                    <td><a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>
<?php include '../includes/footer.php'; ?>
