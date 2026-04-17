<?php
require_once __DIR__ . '/../config.php';
startSecureSession();

// Require login and appropriate role
requireAuth();
if (!hasRole('admin') && !hasRole('teacher')) {
    setFlashMessage('error', 'Access denied. Only admins and teachers can validate QR codes.');
    redirect('../dashboard.php');
}

$validation_result = null;
$error_message = null;

// Handle QR validation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['qr_data'])) {
    $qr_data = $_POST['qr_data'];
    $validated_by = $_SESSION['user_id'];
    $validation_type = $_POST['validation_type'] ?? 'entry'; // 'entry' or 'food'
    
    try {
        $pdo = getDBConnection();
        
        // Find the QR code in database
        $stmt = $pdo->prepare("
            SELECT qr.*, er.event_id, er.user_id, e.title as event_title, u.full_name as student_name
            FROM qr_codes qr
            JOIN event_registrations er ON qr.registration_id = er.id
            JOIN events e ON er.event_id = e.id
            JOIN users u ON er.user_id = u.id
            WHERE qr.qr_data = ?
        ");
        $stmt->execute([$qr_data]);
        $qr_record = $stmt->fetch();
        
        if (!$qr_record) {
            $error_message = 'Invalid QR code.';
        } else {
            if ($validation_type === 'entry') {
                if ($qr_record['entry_used']) {
                    $error_message = 'This QR code has already been used for entry.';
                } else {
                    // Mark entry as used
                    $stmt = $pdo->prepare("
                        UPDATE qr_codes 
                        SET entry_used = TRUE, entry_used_at = NOW(), entry_used_by = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$validated_by, $qr_record['id']]);
                    
                    $validation_result = [
                        'success' => true,
                        'type' => 'entry',
                        'event_title' => $qr_record['event_title'],
                        'student_name' => $qr_record['student_name'],
                        'validated_at' => date('Y-m-d H:i:s')
                    ];
                }
            } elseif ($validation_type === 'food') {
                if ($qr_record['food_used']) {
                    $error_message = 'This QR code has already been used for food.';
                } else {
                    // Mark food as used
                    $stmt = $pdo->prepare("
                        UPDATE qr_codes 
                        SET food_used = TRUE, food_used_at = NOW(), food_used_by = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$validated_by, $qr_record['id']]);
                    
                    $validation_result = [
                        'success' => true,
                        'type' => 'food',
                        'event_title' => $qr_record['event_title'],
                        'student_name' => $qr_record['student_name'],
                        'validated_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }
    } catch (Exception $e) {
        $error_message = 'Error validating QR code: ' . $e->getMessage();
    }
}

// Get recent validations
try {
    $pdo = getDBConnection();
    $stmt = $pdo->query("
        SELECT qr.entry_used_at, qr.food_used_at, e.title as event_title, u.full_name as student_name,
               CASE WHEN qr.entry_used_by IS NOT NULL THEN 'Entry' ELSE 'Food' END as validation_type,
               validator.full_name as validated_by_name
        FROM qr_codes qr
        JOIN event_registrations er ON qr.registration_id = er.id
        JOIN events e ON er.event_id = e.id
        JOIN users u ON er.user_id = u.id
        LEFT JOIN users validator ON (qr.entry_used_by = validator.id OR qr.food_used_by = validator.id)
        WHERE (qr.entry_used_by IS NOT NULL OR qr.food_used_by IS NOT NULL)
        ORDER BY GREATEST(qr.entry_used_at, qr.food_used_at) DESC
        LIMIT 10
    ");
    $recent_validations = $stmt->fetchAll();
} catch (Exception $e) {
    $recent_validations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Validation - Eventura</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/theme.css">
    <style>
        .qr-scanner {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }
        
        .scanner-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .scanner-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .qr-input-area {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .qr-input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 16px;
            font-family: monospace;
            resize: vertical;
            min-height: 100px;
        }
        
        .validation-result {
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .validation-success {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .validation-error {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }
        
        .recent-validations {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }
        
        .validation-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .validation-item:last-child {
            border-bottom: none;
        }
        
        .validation-icon {
            font-size: 24px;
            margin-right: 15px;
        }
        
        .validation-details {
            flex: 1;
        }
        
        .validation-details h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .validation-details p {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        
        .validation-time {
            color: #999;
            font-size: 12px;
        }
        
        .btn-validate {
            background: linear-gradient(135deg, #007bff, #6610f2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-validate:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }
        
        .manual-input-hint {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
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
                <h1>QR Code Validation</h1>
                <div class="header-actions">
                    <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                </div>
            </header>

            <div class="content-wrapper">
                <!-- QR Scanner -->
                <div class="qr-scanner">
                    <div class="scanner-header">
                        <h2>📱 Scan QR Code</h2>
                        <p>Validate student entry and food coupons</p>
                    </div>

                    <div class="manual-input-hint">
                        <strong>📝 Manual Input:</strong> If you don't have a scanner, you can manually paste the QR code data below.
                    </div>

                    <form method="POST" class="validation-form">
                        <div class="qr-input-area">
                            <label for="qr_data" style="display: block; margin-bottom: 10px; font-weight: bold;">QR Code Data:</label>
                            <textarea 
                                id="qr_data" 
                                name="qr_data" 
                                class="qr-input" 
                                placeholder="Paste QR code data here or use a scanner to input automatically..."
                                required
                            ></textarea>
                        </div>
                        
                        <div style="text-align: center;">
                            <button type="submit" class="btn-validate">Validate QR Code</button>
                        </div>
                    </form>

                    <?php if ($validation_result): ?>
                        <div class="validation-result validation-success">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <span style="font-size: 48px; margin-right: 20px;">✅</span>
                                <div>
                                    <h3 style="margin: 0;">Validation Successful!</h3>
                                    <p style="margin: 5px 0;">QR code has been validated and marked as used.</p>
                                </div>
                            </div>
                            
                            <div style="background: rgba(255,255,255,0.2); border-radius: 10px; padding: 15px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                    <div>
                                        <strong>Student:</strong> <?php echo htmlspecialchars($validation_result['student_name']); ?>
                                    </div>
                                    <div>
                                        <strong>Event:</strong> <?php echo htmlspecialchars($validation_result['event_title']); ?>
                                    </div>
                                    <div>
                                        <strong>Type:</strong> <?php echo ucfirst($validation_result['qr_type']); ?>
                                    </div>
                                    <div>
                                        <strong>Time:</strong> <?php echo $validation_result['validated_at']; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error_message): ?>
                        <div class="validation-result validation-error">
                            <div style="display: flex; align-items: center;">
                                <span style="font-size: 48px; margin-right: 20px;">❌</span>
                                <div>
                                    <h3 style="margin: 0;">Validation Failed</h3>
                                    <p style="margin: 5px 0;"><?php echo htmlspecialchars($error_message); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Validations -->
                <div class="recent-validations">
                    <h3>Recent Validations</h3>
                    
                    <?php if (empty($recent_validations)): ?>
                        <p style="text-align: center; color: #666; padding: 20px;">No validations yet</p>
                    <?php else: ?>
                        <?php foreach ($recent_validations as $validation): ?>
                            <div class="validation-item">
                                <div class="validation-icon">
                                    <?php if ($validation['qr_type'] == 'entry'): ?>
                                        🚪
                                    <?php else: ?>
                                        🍕
                                    <?php endif; ?>
                                </div>
                                <div class="validation-details">
                                    <h4><?php echo htmlspecialchars($validation['student_name']); ?></h4>
                                    <p>
                                        <?php echo htmlspecialchars($validation['event_title']); ?> • 
                                        <?php echo ucfirst($validation['qr_type']); ?> • 
                                        Validated by <?php echo htmlspecialchars($validation['validator_name'] ?? 'Unknown'); ?>
                                    </p>
                                </div>
                                <div class="validation-time">
                                    <?php echo date('M d, h:i A', strtotime($validation['used_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/theme.js"></script>
    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Auto-focus on QR input
        document.getElementById('qr_data').focus();
        
        // Clear input after successful validation
        <?php if ($validation_result): ?>
            document.getElementById('qr_data').value = '';
        <?php endif; ?>
    </script>
</body>
</html>
