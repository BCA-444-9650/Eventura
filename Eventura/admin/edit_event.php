<?php
/**
 * Eventura - Edit Event (Admin/Teacher)
 */
require_once '../config.php';
startSecureSession();

// Allow both admin and teacher
if (!hasRole('admin') && !hasRole('teacher')) {
    setFlashMessage('error', 'Access denied.');
    redirect(SITE_URL . '/dashboard.php');
}

$event_id = intval($_GET['id'] ?? 0);

if (!$event_id) {
    setFlashMessage('error', 'Invalid event.');
    redirect(SITE_URL . (hasRole('admin') ? '/admin/events.php' : '/teacher/my_events.php'));
}

// Get event details
try {
    $pdo = getDBConnection();
    
    // Check if user has permission to edit this event
    if (hasRole('admin')) {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND created_by = ?");
    }
    
    if (hasRole('admin')) {
        $stmt->execute([$event_id]);
    } else {
        $stmt->execute([$event_id, $_SESSION['user_id']]);
    }
    
    $event = $stmt->fetch();
    
    if (!$event) {
        setFlashMessage('error', 'Event not found or access denied.');
        redirect(SITE_URL . (hasRole('admin') ? '/admin/events.php' : '/teacher/my_events.php'));
    }
    
} catch (Exception $e) {
    setFlashMessage('error', 'Error loading event.');
    redirect(SITE_URL . (hasRole('admin') ? '/admin/events.php' : '/teacher/my_events.php'));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $venue = trim($_POST['venue'] ?? '');
    $food_available = isset($_POST['food_available']) ? 1 : 0;
    $max_participants = intval($_POST['max_participants'] ?? 0);
    $status = $_POST['status'] ?? 'published';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request. Please try again.';
    } elseif (empty($title) || empty($event_date) || empty($event_time) || empty($venue)) {
        $error = 'Please fill in all required fields.';
    } elseif (strtotime($event_date) < strtotime(date('Y-m-d'))) {
        $error = 'Event date cannot be in the past.';
    } else {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("UPDATE events SET 
                title = ?, description = ?, event_date = ?, event_time = ?, 
                venue = ?, food_available = ?, max_participants = ?, status = ? 
                WHERE id = ?");
            $stmt->execute([
                $title, $description, $event_date, $event_time, 
                $venue, $food_available, $max_participants, $status, $event_id
            ]);
            
            $success = 'Event updated successfully!';
            
            // Update event data for form
            $event['title'] = $title;
            $event['description'] = $description;
            $event['event_date'] = $event_date;
            $event['event_time'] = $event_time;
            $event['venue'] = $venue;
            $event['food_available'] = $food_available;
            $event['max_participants'] = $max_participants;
            $event['status'] = $status;
            
        } catch (Exception $e) {
            $error = 'Error updating event. Please try again.';
        }
    }
}

$page_title = 'Edit Event';
include '../includes/header.php';

// Generate CSRF token for the form
$csrf_token = generateCSRFToken();
?>
<style>
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--spacing-lg);
    margin-bottom: var(--spacing-lg);
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    margin-bottom: var(--spacing-sm);
    font-weight: 500;
    color: var(--text-primary);
}

.form-control {
    padding: var(--spacing-md);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: var(--font-size-md);
    transition: all var(--transition-fast);
    background: var(--bg-card);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
    font-family: inherit;
}

.checkbox-group {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    margin: var(--spacing-md) 0;
}

.checkbox-group input[type="checkbox"] {
    width: 18px;
    height: 18px;
    accent-color: var(--primary);
}

.btn-group {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-md);
    }
    
    .form-control {
        font-size: 16px; /* Prevent zoom on iOS */
        min-height: 44px;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        width: 100%;
    }
}
</style>

<div class="page-header">
    <h1>Edit Event</h1>
    <a href="<?php echo hasRole('admin') ? 'events.php' : 'my_events.php'; ?>" class="btn btn-sm btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<form method="POST" class="clay-card" style="padding: var(--spacing-xl);">
    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
    
    <div class="form-grid">
        <div class="form-group">
            <label for="title">Event Title *</label>
            <input type="text" id="title" name="title" class="form-control" 
                   value="<?php echo htmlspecialchars($event['title']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="venue">Venue *</label>
            <input type="text" id="venue" name="venue" class="form-control" 
                   value="<?php echo htmlspecialchars($event['venue']); ?>" required>
        </div>
    </div>
    
    <div class="form-grid">
        <div class="form-group">
            <label for="event_date">Event Date *</label>
            <input type="date" id="event_date" name="event_date" class="form-control" 
                   value="<?php echo $event['event_date']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="event_time">Event Time *</label>
            <input type="time" id="event_time" name="event_time" class="form-control" 
                   value="<?php echo $event['event_time']; ?>" required>
        </div>
    </div>
    
    <div class="form-group">
        <label for="max_participants">Maximum Participants (0 for unlimited)</label>
        <input type="number" id="max_participants" name="max_participants" class="form-control" 
               value="<?php echo $event['max_participants']; ?>" min="0">
    </div>
    
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" class="form-control" 
                  placeholder="Enter event details..."><?php echo htmlspecialchars($event['description']); ?></textarea>
    </div>
    
    <div class="checkbox-group">
        <input type="checkbox" id="food_available" name="food_available" 
               <?php echo $event['food_available'] ? 'checked' : ''; ?>>
        <label for="food_available">Food Available</label>
    </div>
    
    <?php if (hasRole('admin')): ?>
    <div class="form-group">
        <label for="status">Event Status</label>
        <select id="status" name="status" class="form-control">
            <option value="published" <?php echo $event['status'] === 'published' ? 'selected' : ''; ?>>Published</option>
            <option value="draft" <?php echo $event['status'] === 'draft' ? 'selected' : ''; ?>>Draft</option>
            <option value="cancelled" <?php echo $event['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
    </div>
    <?php endif; ?>
    
    <div class="btn-group">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Update Event
        </button>
        <a href="<?php echo hasRole('admin') ? 'events.php' : 'my_events.php'; ?>" class="btn btn-secondary">
            <i class="fas fa-times"></i> Cancel
        </a>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
