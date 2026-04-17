<?php
/**
 * Eventura - Events List (Admin)
 */
require_once '../config.php';
startSecureSession();
requireRole('admin');

try {
    $pdo = getDBConnection();
    
    $stmt = $pdo->query("SELECT e.*, u.full_name as creator_name,
                        (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as registration_count
                        FROM events e 
                        JOIN users u ON e.created_by = u.id 
                        ORDER BY e.event_date DESC");
    $events = $stmt->fetchAll();
} catch (Exception $e) {
    $events = [];
}

$page_title = 'All Events';
include '../includes/header.php';
?>
<div class="page-header">
    <h1>All Events</h1>
    <a href="create_event.php" class="btn btn-primary"><i class="fas fa-plus"></i> Create Event</a>
</div>

<!-- Desktop Table View -->
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Event</th>
                <th>Date & Time</th>
                <th>Venue</th>
                <th>Created By</th>
                <th>Registrations</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($events)): ?>
                <tr><td colspan="7" class="text-center">No events found.</td></tr>
            <?php else: foreach ($events as $event): ?>
                <tr>
                    <td><strong><?php echo $event['title']; ?></strong><?php if ($event['food_available']): ?> <span class="badge badge-success">Food</span><?php endif; ?></td>
                    <td><?php echo formatDate($event['event_date']); ?><br><small><?php echo formatTime($event['event_time']); ?></small></td>
                    <td><?php echo $event['venue']; ?></td>
                    <td><?php echo $event['creator_name']; ?></td>
                    <td><?php echo $event['registration_count']; ?></td>
                    <td><span class="badge badge-<?php echo $event['status'] === 'published' ? 'success' : ($event['status'] === 'draft' ? 'warning' : 'error'); ?>"><?php echo ucfirst($event['status']); ?></span></td>
                    <td>
                        <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></a>
                        <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i></a>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Mobile Card View -->
<div class="mobile-events-container" style="display: none;">
    <?php if (empty($events)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <p>No events found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="event-card-header">
                    <div>
                        <h3 class="event-card-title">
                            <?php echo $event['title']; ?>
                            <?php if ($event['food_available']): ?>
                                <span class="badge badge-success">Food</span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <div class="event-card-status">
                        <span class="badge badge-<?php echo $event['status'] === 'published' ? 'success' : ($event['status'] === 'draft' ? 'warning' : 'error'); ?>">
                            <?php echo ucfirst($event['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="event-card-details">
                    <div class="event-detail-item">
                        <i class="fas fa-calendar"></i>
                        <span><?php echo formatDate($event['event_date']); ?></span>
                    </div>
                    <div class="event-detail-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo formatTime($event['event_time']); ?></span>
                    </div>
                    <div class="event-detail-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span><?php echo $event['venue']; ?></span>
                    </div>
                    <div class="event-detail-item">
                        <i class="fas fa-user"></i>
                        <span><?php echo $event['creator_name']; ?></span>
                    </div>
                    <div class="event-detail-item">
                        <i class="fas fa-users"></i>
                        <span><?php echo $event['registration_count']; ?> Registrations</span>
                    </div>
                </div>
                
                <div class="event-card-meta">
                    <div class="event-registrations">
                        <i class="fas fa-chart-bar"></i>
                        <span><?php echo $event['registration_count']; ?> Registered</span>
                    </div>
                    <div class="event-actions">
                        <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary">
                            <i class="fas fa-edit"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
