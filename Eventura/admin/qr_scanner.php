<?php
/**
 * Eventura - QR Code Scanner & Validator (Redesigned)
 */
require_once '../config.php';
startSecureSession();

// Allow admin and teacher
if (!hasRole('admin') && !hasRole('teacher')) {
    setFlashMessage('error', 'Access denied.');
    redirect(SITE_URL . '/dashboard.php');
}

$result = null;
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qr_data = $_POST['qr_data'] ?? '';
    $validation_type = $_POST['validation_type'] ?? 'entry';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request.';
    } elseif (empty($qr_data)) {
        $error = 'No QR code data provided.';
    } else {
        try {
            $pdo = getDBConnection();
            
            $stmt = $pdo->prepare("SELECT qr.*, er.user_id, er.event_id, er.status as reg_status,
                                  e.title as event_title, e.event_date, e.food_available,
                                  u.full_name, sp.student_id, sp.roll_no, sp.course
                                  FROM qr_codes qr
                                  JOIN event_registrations er ON qr.registration_id = er.id
                                  JOIN events e ON er.event_id = e.id
                                  JOIN users u ON er.user_id = u.id
                                  LEFT JOIN student_profiles sp ON er.user_id = sp.user_id
                                  WHERE qr.qr_data = ?");
            $stmt->execute([$qr_data]);
            $qr = $stmt->fetch();
            
            if (!$qr) {
                $error = 'Invalid QR code. Not found in database.';
            } else {
                $already_used = ($validation_type === 'entry' && $qr['entry_used']) || 
                               ($validation_type === 'food' && $qr['food_used']);
                
                if ($already_used) {
                    $used_at = $validation_type === 'entry' ? $qr['entry_used_at'] : $qr['food_used_at'];
                    $result = [
                        'status' => 'already_used',
                        'student' => $qr['full_name'],
                        'student_id' => $qr['student_id'],
                        'roll_no' => $qr['roll_no'],
                        'course' => $qr['course'],
                        'event' => $qr['event_title'],
                        'event_date' => $qr['event_date'],
                        'food_available' => $qr['food_available'],
                        'used_at' => $used_at,
                        'validation_type' => $validation_type
                    ];
                } else {
                    $column = $validation_type === 'entry' ? 'entry_used' : 'food_used';
                    $time_column = $validation_type === 'entry' ? 'entry_used_at' : 'food_used_at';
                    $by_column = $validation_type === 'entry' ? 'entry_used_by' : 'food_used_by';
                    
                    $stmt = $pdo->prepare("UPDATE qr_codes 
                        SET {$column} = TRUE, {$time_column} = NOW(), {$by_column} = ? 
                        WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id'], $qr['id']]);
                    
                    if ($validation_type === 'entry') {
                        $stmt = $pdo->prepare("UPDATE event_registrations SET status = 'attended' WHERE id = ?");
                        $stmt->execute([$qr['registration_id']]);
                    }
                    
                    $success = ucfirst($validation_type) . ' validated successfully!';
                    $result = [
                        'status' => 'valid',
                        'student' => $qr['full_name'],
                        'student_id' => $qr['student_id'],
                        'roll_no' => $qr['roll_no'],
                        'course' => $qr['course'],
                        'event' => $qr['event_title'],
                        'event_date' => $qr['event_date'],
                        'food_available' => $qr['food_available'],
                        'validation_type' => $validation_type
                    ];
                }
            }
        } catch (Exception $e) {
            $error = 'Validation error occurred.';
            error_log("QR validation error: " . $e->getMessage());
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'QR Scanner';
include '../includes/header.php';
?>
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<style>
.scanner-container {
    max-width: 500px;
    margin: 0 auto;
    padding: 20px;
}

/* Glass Card */
.glass-card {
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 24px;
    padding: 30px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

/* Mode Icons */
.mode-icons {
    display: flex;
    justify-content: center;
    gap: 40px;
    margin-bottom: 25px;
}
.mode-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    opacity: 0.4;
    transition: all 0.3s ease;
}
.mode-item.active {
    opacity: 1;
}
.mode-item.active .mode-circle {
    background: var(--primary);
    box-shadow: 0 0 20px rgba(99, 102, 241, 0.4);
}
.mode-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: var(--bg-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    transition: all 0.3s ease;
}
.mode-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--text-secondary);
}

/* Scanner Frame */
.scanner-frame {
    position: relative;
    width: 100%;
    max-width: 400px;
    margin: 0 auto 20px;
    aspect-ratio: 1;
    background: #000;
    border-radius: 20px;
    overflow: visible;
}
.scanner-frame.inactive {
    background: linear-gradient(145deg, #1a1a2e, #0f0f1a);
}
.scanner-frame.inactive::before {
    content: '';
    position: absolute;
    inset: 20px;
    border: 2px dashed rgba(99, 102, 241, 0.3);
    border-radius: 16px;
    pointer-events: none;
}
.scanner-frame.inactive::after {
    content: '\f030';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 3rem;
    color: var(--primary);
    opacity: 0.15;
    pointer-events: none;
}

/* Corner Accents */
.corner {
    position: absolute;
    width: 40px;
    height: 40px;
    border-color: var(--primary);
    border-style: solid;
    opacity: 0;
    transition: opacity 0.3s;
    z-index: 10;
}
.scanner-frame.active .corner {
    opacity: 1;
}
.corner-tl { top: 15px; left: 15px; border-width: 4px 0 0 4px; border-radius: 4px 0 0 0; }
.corner-tr { top: 15px; right: 15px; border-width: 4px 4px 0 0; border-radius: 0 4px 0 0; }
.corner-bl { bottom: 15px; left: 15px; border-width: 0 0 4px 4px; border-radius: 0 0 0 4px; }
.corner-br { bottom: 15px; right: 15px; border-width: 0 4px 4px 0; border-radius: 0 0 4px 0; }

/* Scan Line Animation */
.scan-line {
    position: absolute;
    left: 5%;
    right: 5%;
    height: 3px;
    background: linear-gradient(90deg, transparent, var(--primary), var(--primary-light), var(--primary), transparent);
    box-shadow: 0 0 10px var(--primary), 0 0 20px var(--primary);
    opacity: 0;
    z-index: 10;
}
.scanner-frame.active .scan-line {
    opacity: 1;
    animation: scan 1.5s linear infinite;
}
@keyframes scan {
    0% { top: 5%; }
    50% { top: 95%; }
    100% { top: 5%; }
}

#reader { 
    width: 100%; 
    height: 100%; 
    position: absolute;
    top: 0;
    left: 0;
    z-index: 1;
}
#reader video { 
    width: 100% !important;
    height: 100% !important;
    object-fit: cover;
    border-radius: 20px;
}

/* Aggressively hide default html5-qrcode UI */
#reader * { border: none !important; }
#reader__scan_region,
#reader__scan_region * { background: transparent !important; border: none !important; }
#qr-shaded-region { display: none !important; }
#reader__dashboard_section { display: none !important; }
#reader__dashboard_section_csr { display: none !important; }
#reader__scan_region img { display: none !important; }
#reader canvas { background: transparent !important; border: none !important; }

/* Controls */
.scanner-controls {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
}
.ctrl-btn {
    flex: 1;
    padding: 14px;
    border: none;
    border-radius: 12px;
    font-size: 0.9rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: all 0.2s;
}
.ctrl-btn.primary {
    background: var(--primary);
    color: white;
}
.ctrl-btn.primary:hover {
    background: var(--primary-light);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
}
.ctrl-btn.secondary {
    background: var(--bg-primary);
    color: var(--text-secondary);
    border: 1px solid var(--border-color);
}
.ctrl-btn.secondary:hover {
    border-color: var(--primary);
    color: var(--primary);
}

/* Input Toggle */
.input-toggle {
    display: flex;
    background: rgba(255,255,255,0.03);
    border-radius: 12px;
    padding: 4px;
    margin-bottom: 20px;
}
.toggle-option {
    flex: 1;
    padding: 10px;
    border: none;
    border-radius: 10px;
    background: transparent;
    color: var(--text-muted);
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}
.toggle-option.active {
    background: var(--bg-card);
    color: var(--primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

/* Manual Input */
.manual-panel {
    display: none;
}
.manual-panel.active {
    display: block;
    animation: fadeIn 0.3s ease;
}
.code-input {
    width: 100%;
    padding: 16px;
    font-family: 'SF Mono', monospace;
    font-size: 0.9rem;
    background: rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 12px;
    color: var(--text-primary);
    resize: none;
    margin-bottom: 12px;
}
.code-input:focus {
    outline: none;
    border-color: var(--primary);
}
.code-input::placeholder {
    color: var(--text-muted);
}

/* Status Badge */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 15px;
}
.status-badge.idle { background: rgba(255,255,255,0.05); color: var(--text-muted); }
.status-badge.scanning { background: rgba(247,183,49,0.15); color: var(--warning); }
.status-badge.success { background: rgba(16,185,129,0.15); color: var(--success); }
.status-badge.error { background: rgba(239,68,68,0.15); color: var(--error); }

/* Result Card */
.scan-result {
    margin-top: 20px;
    background: var(--bg-card);
    border-radius: 20px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.05);
    animation: slideUp 0.4s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

.result-top {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.05));
}
.result-top.valid { background: linear-gradient(135deg, rgba(16,185,129,0.15), rgba(52,211,153,0.05)); }
.result-top.invalid { background: linear-gradient(135deg, rgba(239,68,68,0.15), rgba(248,113,113,0.05)); }
.result-top.used { background: linear-gradient(135deg, rgba(247,183,49,0.15), rgba(251,191,36,0.05)); }

.result-avatar {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    background: rgba(255,255,255,0.1);
}
.result-top.valid .result-avatar { background: rgba(16,185,129,0.2); color: var(--success); }
.result-top.invalid .result-avatar { background: rgba(239,68,68,0.2); color: var(--error); }
.result-top.used .result-avatar { background: rgba(247,183,49,0.2); color: var(--warning); }

.result-meta h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
}
.result-meta span {
    font-size: 0.8rem;
    color: var(--text-muted);
}

.result-type-tag {
    margin-left: auto;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
}
.result-type-tag.entry { background: rgba(99,102,241,0.2); color: var(--primary); }
.result-type-tag.food { background: rgba(16,185,129,0.2); color: var(--success); }

.result-details {
    padding: 20px;
}
.detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.detail-box {
    background: rgba(255,255,255,0.03);
    padding: 12px 16px;
    border-radius: 12px;
}
.detail-box.full { grid-column: 1 / -1; }
.detail-box label {
    display: block;
    font-size: 0.7rem;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
}
.detail-box value {
    display: block;
    font-size: 0.95rem;
    font-weight: 600;
    color: var(--text-primary);
}

.result-action {
    padding: 15px 20px;
    border-top: 1px solid rgba(255,255,255,0.05);
    text-align: center;
}
.btn-reset {
    padding: 10px 24px;
    border-radius: 10px;
    font-size: 0.85rem;
}

.scanner-section { display: none; }
.scanner-section.active { display: block; }

@media (max-width: 480px) {
    .glass-card { padding: 20px; }
    .mode-icons { gap: 30px; }
    .mode-circle { width: 50px; height: 50px; font-size: 1.2rem; }
    .detail-grid { grid-template-columns: 1fr; }
}
</style>

<div class="scanner-container">
    <div class="glass-card">
        <div class="page-header" style="text-align: center; margin-bottom: 25px;">
            <h1 style="font-size: 1.5rem; margin: 0;"><i class="fas fa-qrcode"></i> QR Scanner</h1>
        </div>

        <?php if ($error): ?><div class="alert alert-error" style="margin-bottom: 15px;"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success" style="margin-bottom: 15px;"><?php echo $success; ?></div><?php endif; ?>

        <!-- Mode Selection -->
        <div class="mode-icons">
            <div class="mode-item <?php echo (!isset($_POST['validation_type']) || $_POST['validation_type'] === 'entry') ? 'active' : ''; ?>" onclick="setMode('entry', this)">
                <div class="mode-circle"><i class="fas fa-door-open"></i></div>
                <span class="mode-label">Entry</span>
            </div>
            <div class="mode-item <?php echo (isset($_POST['validation_type']) && $_POST['validation_type'] === 'food') ? 'active' : ''; ?>" onclick="setMode('food', this)">
                <div class="mode-circle"><i class="fas fa-utensils"></i></div>
                <span class="mode-label">Food</span>
            </div>
        </div>

        <!-- Input Toggle -->
        <div class="input-toggle">
            <button type="button" class="toggle-option active" onclick="showInput('camera', this)">
                <i class="fas fa-camera"></i> Camera
            </button>
            <button type="button" class="toggle-option" onclick="showInput('manual', this)">
                <i class="fas fa-keyboard"></i> Manual
            </button>
        </div>

        <form method="POST" id="qrForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <input type="hidden" name="validation_type" id="validationType" value="<?php echo $_POST['validation_type'] ?? 'entry'; ?>">

            <!-- Camera Section -->
            <div id="cameraSection" class="scanner-section active">
                <div class="scanner-frame inactive" id="scannerFrame">
                    <div class="corner corner-tl"></div>
                    <div class="corner corner-tr"></div>
                    <div class="corner corner-bl"></div>
                    <div class="corner corner-br"></div>
                    <div class="scan-line"></div>
                    <div id="reader"></div>
                </div>

                <div id="statusBadge" class="status-badge idle">
                    <i class="fas fa-video-slash"></i> Camera is off
                </div>

                <div class="scanner-controls">
                    <button type="button" id="scanStartBtn" class="ctrl-btn primary" onclick="startCamera()">
                        <i class="fas fa-play"></i> Start Scan
                    </button>
                    <button type="button" id="scanStopBtn" class="ctrl-btn secondary" onclick="stopCamera()" style="display: none;">
                        <i class="fas fa-stop"></i> Stop
                    </button>
                </div>
            </div>

            <!-- Manual Section -->
            <div id="manualSection" class="scanner-section manual-panel">
                <textarea name="qr_data" id="qrCodeInput" class="code-input" placeholder="Paste QR code data here..." rows="3"></textarea>
                <button type="submit" class="ctrl-btn primary" style="width: 100%;">
                    <i class="fas fa-check-circle"></i> Validate Code
                </button>
            </div>
        </form>

        <!-- Result -->
        <?php if ($result): ?>
            <div class="scan-result">
                <div class="result-top <?php echo $result['status'] === 'valid' ? 'valid' : ($result['status'] === 'already_used' ? 'used' : 'invalid'); ?>">
                    <div class="result-avatar">
                        <i class="fas fa-<?php echo $result['status'] === 'valid' ? 'check' : ($result['status'] === 'already_used' ? 'exclamation' : 'times'); ?>"></i>
                    </div>
                    <div class="result-meta">
                        <h3><?php echo $result['status'] === 'valid' ? 'Valid' : ($result['status'] === 'already_used' ? 'Used' : 'Invalid'); ?></h3>
                        <span><?php echo $result['status'] === 'valid' ? 'Access granted' : ($result['status'] === 'already_used' ? 'Previously scanned' : 'Unknown code'); ?></span>
                    </div>
                    <span class="result-type-tag <?php echo $result['validation_type']; ?>">
                        <?php echo ucfirst($result['validation_type']); ?>
                    </span>
                </div>
                
                <div class="result-details">
                    <div class="detail-grid">
                        <div class="detail-box full">
                            <label>Student</label>
                            <value><?php echo $result['student']; ?></value>
                        </div>
                        <?php if ($result['status'] === 'valid'): ?>
                            <div class="detail-box">
                                <label>ID</label>
                                <value><?php echo $result['student_id'] ?? 'N/A'; ?></value>
                            </div>
                            <div class="detail-box">
                                <label>Roll No</label>
                                <value><?php echo $result['roll_no'] ?? 'N/A'; ?></value>
                            </div>
                            <div class="detail-box">
                                <label>Course</label>
                                <value><?php echo $result['course'] ?? 'N/A'; ?></value>
                            </div>
                        <?php endif; ?>
                        <?php if ($result['status'] === 'already_used'): ?>
                            <div class="detail-box full">
                                <label>Used At</label>
                                <value><?php echo date('M d, Y h:i A', strtotime($result['used_at'])); ?></value>
                            </div>
                        <?php endif; ?>
                        <div class="detail-box full">
                            <label>Event</label>
                            <value><?php echo $result['event']; ?></value>
                        </div>
                    </div>
                    
                    <?php if ($result['validation_type'] === 'food' && !$result['food_available']): ?>
                        <div class="alert alert-error" style="margin-top: 15px; padding: 12px; font-size: 0.85rem;">
                            <i class="fas fa-exclamation-triangle"></i> Food not available for this event
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="result-action">
                    <button type="button" class="btn btn-secondary btn-reset" onclick="resetScanner()">
                        <i class="fas fa-redo"></i> Scan Another
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Enhanced QR Scanner with Sound Effects and Haptic Feedback
let qrScanner = null;
let isScanning = false;
let audioContext = null;

// Initialize audio context for sound effects
function initAudio() {
    if (!audioContext) {
        audioContext = new (window.AudioContext || window.webkitAudioContext)();
    }
}

// Play sound effect
function playSound(frequency, duration, type = 'sine') {
    initAudio();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = frequency;
    oscillator.type = type;
    
    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration);
    
    oscillator.start(audioContext.currentTime);
    oscillator.stop(audioContext.currentTime + duration);
}

// Haptic feedback (if supported)
function triggerHaptic(pattern = 'short') {
    if ('vibrate' in navigator) {
        switch(pattern) {
            case 'short':
                navigator.vibrate(50);
                break;
            case 'long':
                navigator.vibrate(200);
                break;
            case 'double':
                navigator.vibrate([50, 50, 50]);
                break;
            case 'success':
                navigator.vibrate([100, 50, 100]);
                break;
            case 'error':
                navigator.vibrate([200, 100, 200]);
                break;
        }
    }
}

// Sound effect presets
const sounds = {
    start: () => { playSound(440, 0.1); playSound(880, 0.1); },
    scan: () => { playSound(1000, 0.05); },
    success: () => { playSound(523, 0.1); playSound(659, 0.1); playSound(784, 0.2); },
    error: () => { playSound(300, 0.2); playSound(200, 0.3); },
    click: () => { playSound(800, 0.05); },
    switch: () => { playSound(600, 0.08); }
};

function setMode(mode, element) {
    sounds.switch();
    triggerHaptic('short');
    
    document.getElementById('validationType').value = mode;
    document.querySelectorAll('.mode-item').forEach(el => el.classList.remove('active'));
    element.classList.add('active');
    
    // Add visual feedback
    element.style.transform = 'scale(0.95)';
    setTimeout(() => {
        element.style.transform = '';
    }, 150);
}

function showInput(type, btn) {
    sounds.click();
    triggerHaptic('short');
    
    document.querySelectorAll('.toggle-option').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.scanner-section').forEach(s => s.classList.remove('active'));
    document.getElementById(type + 'Section').classList.add('active');
    if (type === 'manual') stopCamera();
    
    // Focus input if manual mode
    if (type === 'manual') {
        setTimeout(() => {
            document.getElementById('qrCodeInput').focus();
        }, 300);
    }
}

function startCamera() {
    if (isScanning) return;
    
    sounds.start();
    triggerHaptic('short');
    isScanning = true;
    
    const frame = document.getElementById('scannerFrame');
    const badge = document.getElementById('statusBadge');
    
    frame.classList.remove('inactive');
    frame.classList.add('active');
    badge.className = 'status-badge scanning';
    badge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Starting...';
    document.getElementById('scanStartBtn').style.display = 'none';
    
    qrScanner = new Html5Qrcode('reader');
    qrScanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 280, height: 280 } },
        (text) => {
            sounds.success();
            triggerHaptic('success');
            
            badge.className = 'status-badge success';
            badge.innerHTML = '<i class="fas fa-check"></i> Detected!';
            document.getElementById('qrCodeInput').value = text;
            
            // Delay form submission to show success feedback
            setTimeout(() => {
                stopCamera();
                document.getElementById('qrForm').submit();
            }, 500);
        },
        () => {
            // Optional: Play subtle scanning sound
            if (Math.random() < 0.1) { // Play occasionally
                sounds.scan();
            }
        }
    ).then(() => {
        badge.className = 'status-badge scanning';
        badge.innerHTML = '<i class="fas fa-video"></i> Scanning...';
        document.getElementById('scanStopBtn').style.display = 'flex';
        
        // Hide default html5-qrcode UI elements
        setTimeout(() => {
            const shadedRegion = document.querySelector('#reader__scan_region #qr-shaded-region');
            if (shadedRegion) shadedRegion.style.display = 'none';
            
            const scanRegion = document.querySelector('#reader__scan_region');
            if (scanRegion) {
                scanRegion.style.background = 'transparent';
                const img = scanRegion.querySelector('img');
                if (img) img.style.display = 'none';
            }
            
            const dashboard = document.querySelector('#reader__dashboard_section');
            if (dashboard) dashboard.style.display = 'none';
            
            const canvases = document.querySelectorAll('#reader canvas');
            canvases.forEach(c => {
                if (c.style.border) c.style.border = 'none';
                c.style.background = 'transparent';
            });
        }, 100);
    }).catch((err) => {
        sounds.error();
        triggerHaptic('error');
        
        badge.className = 'status-badge error';
        badge.innerHTML = '<i class="fas fa-exclamation-circle"></i> Camera Error';
        document.getElementById('scanStartBtn').style.display = 'flex';
        frame.classList.add('inactive');
        frame.classList.remove('active');
        isScanning = false;
        
        console.error('Camera error:', err);
    });
}

function stopCamera() {
    if (!qrScanner || !isScanning) return;
    
    sounds.click();
    triggerHaptic('short');
    
    qrScanner.stop().then(() => {
        qrScanner.clear();
        qrScanner = null;
        isScanning = false;
        
        const frame = document.getElementById('scannerFrame');
        const badge = document.getElementById('statusBadge');
        
        frame.classList.add('inactive');
        frame.classList.remove('active');
        badge.className = 'status-badge idle';
        badge.innerHTML = '<i class="fas fa-video-slash"></i> Camera is off';
        document.getElementById('scanStartBtn').style.display = 'flex';
        document.getElementById('scanStopBtn').style.display = 'none';
    }).catch(() => {
        isScanning = false;
    });
}

function resetScanner() {
    sounds.click();
    triggerHaptic('short');
    
    document.getElementById('qrCodeInput').value = '';
    window.location.href = window.location.pathname;
}

// Enhanced paste handling with feedback
document.getElementById('qrCodeInput').addEventListener('paste', function(e) {
    sounds.click();
    triggerHaptic('short');
    
    setTimeout(() => { 
        if (this.value.trim()) {
            sounds.success();
            triggerHaptic('success');
            document.getElementById('qrForm').submit();
        }
    }, 100);
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Don't trigger shortcuts when typing in input
    if (e.target.tagName === 'TEXTAREA' || e.target.tagName === 'INPUT') {
        if (e.key === 'Enter' && e.target.tagName === 'TEXTAREA') {
            e.preventDefault();
            if (e.target.value.trim()) {
                sounds.success();
                triggerHaptic('success');
                document.getElementById('qrForm').submit();
            }
        }
        return;
    }
    
    // Global shortcuts
    switch(e.key.toLowerCase()) {
        case 's':
            if (!isScanning) {
                e.preventDefault();
                startCamera();
            }
            break;
        case 'x':
            if (isScanning) {
                e.preventDefault();
                stopCamera();
            }
            break;
        case 'm':
            e.preventDefault();
            // Toggle between camera and manual
            const manualBtn = document.querySelector('.toggle-option:not(.active)');
            if (manualBtn) manualBtn.click();
            break;
        case '1':
            e.preventDefault();
            document.querySelector('.mode-item:first-child').click();
            break;
        case '2':
            e.preventDefault();
            document.querySelector('.mode-item:last-child').click();
            break;
        case 'r':
        case 'escape':
            e.preventDefault();
            resetScanner();
            break;
    }
});

// Add visual feedback to all buttons
document.querySelectorAll('button').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!this.classList.contains('toggle-option') && !this.classList.contains('mode-item')) {
            sounds.click();
            triggerHaptic('short');
        }
        
        // Visual feedback
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = '';
        }, 150);
    });
});

// Initialize page with subtle animation
document.addEventListener('DOMContentLoaded', function() {
    // Add entrance animation
    const card = document.querySelector('.glass-card');
    card.style.opacity = '0';
    card.style.transform = 'translateY(20px)';
    
    setTimeout(() => {
        card.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
        card.style.opacity = '1';
        card.style.transform = 'translateY(0)';
    }, 100);
    
    // Show keyboard shortcuts hint
    setTimeout(() => {
        const hint = document.createElement('div');
        hint.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.75rem;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
        `;
        hint.innerHTML = 'Press <kbd>S</kbd> to scan, <kbd>M</kbd> for manual, <kbd>1/2</kbd> for modes';
        document.body.appendChild(hint);
        
        setTimeout(() => hint.style.opacity = '0.8', 100);
        setTimeout(() => {
            hint.style.opacity = '0';
            setTimeout(() => hint.remove(), 300);
        }, 4000);
    }, 2000);
});
</script>

<?php include '../includes/footer.php'; ?>
