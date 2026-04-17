<?php
/**
 * Eventura - Event Registration Handler
 */
require_once '../config.php';
startSecureSession();
requireRole('student');

$event_id = intval($_GET['event_id'] ?? 0);

if (!$event_id) {
    setFlashMessage('error', 'Invalid event.');
    redirect(SITE_URL . '/student/events.php');
}

try {
    $pdo = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    // Get student profile
    $stmt = $pdo->prepare("SELECT id FROM student_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $profile = $stmt->fetch();
    
    if (!$profile) {
        setFlashMessage('error', 'Please complete your profile first.');
        redirect(SITE_URL . '/complete_profile.php');
    }
    
    $student_profile_id = $profile['id'];
    
    // Get event details
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND status = 'published'");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch();
    
    if (!$event) {
        setFlashMessage('error', 'Event not found.');
        redirect(SITE_URL . '/student/events.php');
    }
    
    // Check if event is in the past
    if (strtotime($event['event_date']) < strtotime(date('Y-m-d'))) {
        setFlashMessage('error', 'This event has already passed.');
        redirect(SITE_URL . '/student/events.php');
    }
    
    // Check if already registered
    $stmt = $pdo->prepare("SELECT id FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $stmt->execute([$event_id, $user_id]);
    if ($stmt->fetch()) {
        setFlashMessage('warning', 'You are already registered for this event.');
        redirect(SITE_URL . '/student/my_tickets.php');
    }
    
    // Check max participants
    if ($event['max_participants'] > 0) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM event_registrations WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $current_registrations = $stmt->fetchColumn();
        
        if ($current_registrations >= $event['max_participants']) {
            setFlashMessage('error', 'This event is full.');
            redirect(SITE_URL . '/student/events.php');
        }
    }
    
    // Create registration
    $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id, student_profile_id) VALUES (?, ?, ?)");
    $stmt->execute([$event_id, $user_id, $student_profile_id]);
    
    $registration_id = $pdo->lastInsertId();
    
    // Generate QR Code
    require_once '../includes/qr_generator.php';
    $qr_data = generateQRCode($registration_id, $event_id, $user_id);
    
    // Save QR code to database
    $stmt = $pdo->prepare("INSERT INTO qr_codes (registration_id, qr_data) VALUES (?, ?)");
    $stmt->execute([$registration_id, $qr_data]);
    
    setFlashMessage('success', 'Successfully registered! View your ticket.');
    redirect(SITE_URL . '/student/view_ticket.php?event_id=' . $event_id);
    
} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    setFlashMessage('error', 'Registration failed. Please try again.');
    redirect(SITE_URL . '/student/events.php');
}
