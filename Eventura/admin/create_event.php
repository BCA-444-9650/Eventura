<?php
/**
 * Eventura - Create Event (Admin/Teacher)
 */
require_once '../config.php';
startSecureSession();

// Allow both admin and teacher
if (!hasRole('admin') && !hasRole('teacher')) {
    setFlashMessage('error', 'Access denied.');
    redirect(SITE_URL . '/dashboard.php');
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
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!verifyCSRFToken($csrf_token)) {
        $error = 'Invalid request.';
    } elseif (empty($title) || empty($event_date) || empty($event_time) || empty($venue)) {
        $error = 'Please fill all required fields.';
    } elseif (strlen($title) < 3) {
        $error = 'Event title must be at least 3 characters long.';
    } elseif (strlen($title) > 100) {
        $error = 'Event title must not exceed 100 characters.';
    } elseif (strtotime($event_date) < strtotime(date('Y-m-d'))) {
        $error = 'Event date cannot be in the past.';
    } elseif (strlen($venue) < 3) {
        $error = 'Venue must be at least 3 characters long.';
    } elseif ($max_participants < 0) {
        $error = 'Maximum participants cannot be negative.';
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, venue, food_available, max_participants, created_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'published')");
            $stmt->execute([$title, $description, $event_date, $event_time, $venue, $food_available, $max_participants, $_SESSION['user_id']]);
            
            $event_id = $pdo->lastInsertId();
            
            // Send notifications to all students (admin feature)
            if (hasRole('admin')) {
                // Email notification system disabled - students will check events manually
                // TODO: Implement email notification system if needed
            }
            
            setFlashMessage('success', 'Event created successfully!');
            redirect(SITE_URL . (hasRole('admin') ? '/admin/events.php' : '/teacher/my_events.php'));
            
        } catch (Exception $e) {
            $error = 'Error creating event. Please try again.';
        }
    }
}

$csrf_token = generateCSRFToken();
$page_title = 'Create Event';
include '../includes/header.php';
?>

<div class="create-event-container">
    <div class="create-event-header">
        <div class="header-content">
            <h1><i class="fas fa-calendar-plus"></i> Create New Event</h1>
            <p>Fill in the details below to create your event</p>
        </div>
        <div class="header-visual">
            <i class="fas fa-calendar-alt"></i>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error alert-dismissible">
            <i class="fas fa-exclamation-circle"></i>
            <span><?php echo $error; ?></span>
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="create-event-form">
        <form method="POST" id="createEventForm" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            
            <!-- Basic Information Section -->
            <div class="form-section">
                <div class="section-header">
                    <h3><i class="fas fa-info-circle"></i> Basic Information</h3>
                    <p>Essential details about your event</p>
                </div>
                
                <div class="form-grid">
                    <div class="form-group full-width">
                        <label for="title">
                            <i class="fas fa-heading"></i> Event Title *
                            <span class="char-count">0/100</span>
                        </label>
                        <input type="text" id="title" name="title" class="form-control" 
                               placeholder="Enter event title" required maxlength="100"
                               value="<?php echo isset($_POST['title']) ? sanitize($_POST['title']) : ''; ?>">
                        <div class="form-hint">Choose a descriptive title for your event</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description">
                            <i class="fas fa-align-left"></i> Description
                            <span class="optional">Optional</span>
                        </label>
                        <textarea id="description" name="description" class="form-control" 
                                  rows="4" placeholder="Describe your event..." maxlength="500"><?php echo isset($_POST['description']) ? sanitize($_POST['description']) : ''; ?></textarea>
                        <div class="form-hint">Provide details about what participants can expect</div>
                    </div>
                </div>
            </div>

            <!-- Schedule Section -->
            <div class="form-section">
                <div class="section-header">
                    <h3><i class="fas fa-clock"></i> Schedule</h3>
                    <p>When and where your event will take place</p>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="event_date">
                            <i class="fas fa-calendar"></i> Event Date *
                        </label>
                        <input type="date" id="event_date" name="event_date" class="form-control" required 
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo isset($_POST['event_date']) ? $_POST['event_date'] : ''; ?>">
                        <div class="form-hint">Select the date of your event</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="event_time">
                            <i class="fas fa-clock"></i> Event Time *
                        </label>
                        <input type="time" id="event_time" name="event_time" class="form-control" required
                               value="<?php echo isset($_POST['event_time']) ? $_POST['event_time'] : ''; ?>">
                        <div class="form-hint">Choose the start time</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="venue">
                            <i class="fas fa-map-marker-alt"></i> Venue *
                        </label>
                        <input type="text" id="venue" name="venue" class="form-control" 
                               placeholder="e.g., Main Auditorium, Room 101" required
                               value="<?php echo isset($_POST['venue']) ? sanitize($_POST['venue']) : ''; ?>">
                        <div class="form-hint">Specify the location where the event will be held</div>
                    </div>
                </div>
            </div>

            <!-- Settings Section -->
            <div class="form-section">
                <div class="section-header">
                    <h3><i class="fas fa-cog"></i> Event Settings</h3>
                    <p>Configure participation and availability</p>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="max_participants">
                            <i class="fas fa-users"></i> Maximum Participants
                        </label>
                        <input type="number" id="max_participants" name="max_participants" 
                               class="form-control" min="0" max="10000" value="0"
                               value="<?php echo isset($_POST['max_participants']) ? $_POST['max_participants'] : '0'; ?>">
                        <div class="form-hint">0 = Unlimited participants</div>
                    </div>
                    
                    <div class="form-group">
                        <label class="toggle-label">
                            <i class="fas fa-utensils"></i> Food Available
                            <div class="toggle-switch">
                                <input type="checkbox" id="food_available" name="food_available" value="1"
                                    <?php echo isset($_POST['food_available']) ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </div>
                        </label>
                        <div class="form-hint">Check if food will be provided</div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large">
                    <i class="fas fa-plus-circle"></i>
                    <span>Create Event</span>
                </button>
                <a href="<?php echo hasRole('admin') ? 'events.php' : 'my_events.php'; ?>" class="btn btn-secondary btn-large">
                    <i class="fas fa-times"></i>
                    <span>Cancel</span>
                </a>
            </div>
        </form>
    </div>
</div>

<style>
/* Create Event Page Styles */
.create-event-container {
    max-width: 900px;
    margin: 0 auto;
    padding: var(--spacing-lg);
}

.create-event-header {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: var(--radius-lg);
    padding: var(--spacing-xl);
    margin-bottom: var(--spacing-lg);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
}

.create-event-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.header-content {
    position: relative;
    z-index: 1;
    flex: 1;
}

.header-content h1 {
    font-size: var(--font-size-2xl);
    font-weight: 700;
    margin-bottom: var(--spacing-xs);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.header-content p {
    font-size: var(--font-size-md);
    opacity: 0.9;
    margin: 0;
}

.header-visual {
    position: relative;
    z-index: 1;
    font-size: 4rem;
    opacity: 0.3;
    animation: float 3s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

.create-event-form {
    background: var(--bg-card);
    border-radius: var(--radius-lg);
    box-shadow: var(--clay-shadow);
    overflow: hidden;
}

.form-section {
    padding: var(--spacing-xl);
    border-bottom: 1px solid var(--border-color);
}

.form-section:last-child {
    border-bottom: none;
}

.section-header {
    margin-bottom: var(--spacing-lg);
}

.section-header h3 {
    font-size: var(--font-size-xl);
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--spacing-xs);
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.section-header h3 i {
    color: var(--primary);
    font-size: 1.2rem;
}

.section-header p {
    color: var(--text-secondary);
    margin: 0;
    font-size: var(--font-size-sm);
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--spacing-lg);
}

.form-group {
    position: relative;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-weight: 500;
    margin-bottom: var(--spacing-sm);
    color: var(--text-primary);
    font-size: var(--font-size-sm);
}

.form-group label i {
    color: var(--primary);
    font-size: 0.9rem;
    width: 16px;
    text-align: center;
}

.char-count {
    margin-left: auto;
    font-size: 0.75rem;
    color: var(--text-muted);
    font-weight: 400;
}

.optional {
    margin-left: var(--spacing-xs);
    font-size: 0.75rem;
    color: var(--text-muted);
    font-weight: 400;
    background: var(--bg-primary);
    padding: 2px 6px;
    border-radius: 10px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    font-family: inherit;
    font-size: var(--font-size-sm);
    transition: all var(--transition-fast);
    background: var(--bg-primary);
    color: var(--text-primary);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    transform: translateY(-1px);
}

.form-control.error {
    border-color: var(--danger);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.form-control.success {
    border-color: var(--success);
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
}

.form-hint {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: var(--spacing-xs);
    line-height: 1.4;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

/* Toggle Switch */
.toggle-label {
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
}

.toggle-switch {
    position: relative;
    width: 50px;
    height: 26px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--bg-primary);
    border: 2px solid var(--border-color);
    transition: 0.3s;
    border-radius: 26px;
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 2px;
    bottom: 2px;
    background: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider {
    background: var(--primary);
    border-color: var(--primary);
}

input:checked + .toggle-slider:before {
    transform: translateX(24px);
}

/* Alert Styles */
.alert {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
    position: relative;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: var(--danger);
}

.alert-dismissible {
    padding-right: 40px;
}

.alert-close {
    position: absolute;
    top: 50%;
    right: var(--spacing-sm);
    transform: translateY(-50%);
    background: none;
    border: none;
    color: inherit;
    cursor: pointer;
    padding: var(--spacing-xs);
    border-radius: var(--radius-sm);
    opacity: 0.7;
    transition: opacity 0.2s;
}

.alert-close:hover {
    opacity: 1;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-xl);
    background: var(--bg-primary);
}

.btn-large {
    padding: 14px 28px;
    font-size: var(--font-size-md);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    min-width: 140px;
    justify-content: center;
}

.btn-large i {
    font-size: 1rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .create-event-container {
        padding: var(--spacing-md);
    }
    
    .create-event-header {
        flex-direction: column;
        text-align: center;
        gap: var(--spacing-md);
        padding: var(--spacing-lg);
    }
    
    .header-visual {
        font-size: 3rem;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
        gap: var(--spacing-md);
    }
    
    .form-section {
        padding: var(--spacing-lg);
    }
    
    .form-actions {
        flex-direction: column;
        padding: var(--spacing-lg);
    }
    
    .btn-large {
        width: 100%;
    }
}

@media (max-width: 480px) {
    .create-event-header h1 {
        font-size: var(--font-size-xl);
    }
    
    .form-section {
        padding: var(--spacing-md);
    }
    
    .form-control {
        padding: 10px 14px;
    }
}
</style>

<script>
// Form validation and interactions
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createEventForm');
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const dateInput = document.getElementById('event_date');
    const timeInput = document.getElementById('event_time');
    const venueInput = document.getElementById('venue');
    const maxParticipantsInput = document.getElementById('max_participants');
    
    // Character counter for title
    titleInput.addEventListener('input', function() {
        const charCount = this.value.length;
        const charCountSpan = this.parentElement.querySelector('.char-count');
        charCountSpan.textContent = `${charCount}/100`;
        
        if (charCount > 80) {
            charCountSpan.style.color = 'var(--warning)';
        } else {
            charCountSpan.style.color = 'var(--text-muted)';
        }
    });
    
    // Real-time validation
    function validateField(field, condition, errorMessage) {
        if (condition) {
            field.classList.remove('error');
            field.classList.add('success');
            return true;
        } else {
            field.classList.remove('success');
            field.classList.add('error');
            return false;
        }
    }
    
    titleInput.addEventListener('blur', function() {
        validateField(this, this.value.length >= 3 && this.value.length <= 100);
    });
    
    venueInput.addEventListener('blur', function() {
        validateField(this, this.value.length >= 3);
    });
    
    dateInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        validateField(this, selectedDate >= today);
    });
    
    maxParticipantsInput.addEventListener('input', function() {
        validateField(this, this.value >= 0 && this.value <= 10000);
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate all required fields
        if (!validateField(titleInput, titleInput.value.length >= 3 && titleInput.value.length <= 100)) {
            isValid = false;
        }
        
        if (!validateField(dateInput, dateInput.value && new Date(dateInput.value) >= new Date().setHours(0,0,0,0))) {
            isValid = false;
        }
        
        if (!validateField(timeInput, timeInput.value)) {
            isValid = false;
        }
        
        if (!validateField(venueInput, venueInput.value.length >= 3)) {
            isValid = false;
        }
        
        if (!validateField(maxParticipantsInput, maxParticipantsInput.value >= 0 && maxParticipantsInput.value <= 10000)) {
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            const firstError = form.querySelector('.form-control.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });
    
    // Auto-save to localStorage
    function saveFormData() {
        const formData = {
            title: titleInput.value,
            description: descriptionInput.value,
            event_date: dateInput.value,
            event_time: timeInput.value,
            venue: venueInput.value,
            max_participants: maxParticipantsInput.value,
            food_available: document.getElementById('food_available').checked
        };
        localStorage.setItem('createEventDraft', JSON.stringify(formData));
    }
    
    // Load draft from localStorage
    function loadFormData() {
        const draft = localStorage.getItem('createEventDraft');
        if (draft) {
            const formData = JSON.parse(draft);
            if (!titleInput.value) titleInput.value = formData.title || '';
            if (!descriptionInput.value) descriptionInput.value = formData.description || '';
            if (!dateInput.value) dateInput.value = formData.event_date || '';
            if (!timeInput.value) timeInput.value = formData.event_time || '';
            if (!venueInput.value) venueInput.value = formData.venue || '';
            if (!maxParticipantsInput.value) maxParticipantsInput.value = formData.max_participants || '0';
            if (!document.getElementById('food_available').checked) document.getElementById('food_available').checked = formData.food_available || false;
            
            // Update character counter
            const charCount = titleInput.value.length;
            const charCountSpan = titleInput.parentElement.querySelector('.char-count');
            charCountSpan.textContent = `${charCount}/100`;
        }
    }
    
    // Auto-save on input change
    [titleInput, descriptionInput, dateInput, timeInput, venueInput, maxParticipantsInput].forEach(input => {
        input.addEventListener('input', saveFormData);
    });
    
    document.getElementById('food_available').addEventListener('change', saveFormData);
    
    // Load draft on page load
    loadFormData();
    
    // Clear draft on successful submission
    form.addEventListener('submit', function() {
        localStorage.removeItem('createEventDraft');
    });
});
</script>

<?php include '../includes/footer.php'; ?>
