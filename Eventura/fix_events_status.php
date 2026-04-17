yes
<?php
/**
 * Fix events with NULL status - Run this once to update all events
 */
require_once '../config.php';
startSecureSession();

if (!hasRole('admin')) {
    die('Admin access required');
}

try {
    $pdo = getDBConnection();
    
    // Update any events with NULL or empty status to 'published'
    $stmt = $pdo->query("UPDATE events SET status = 'published' WHERE status IS NULL OR status = ''");
    $updated = $stmt->rowCount();
    
    echo "Updated {$updated} events to status 'published'.<br>";
    echo "All events should now be visible to teachers.";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
