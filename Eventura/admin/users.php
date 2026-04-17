<?php
/**
 * Eventura - Admin Users Management
 */
require_once '../config.php';
startSecureSession();
requireRole('admin');

try {
    $pdo = getDBConnection();
    
    // Handle teacher creation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_teacher'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $department = trim($_POST['department'] ?? '');
        $csrf_token = $_POST['csrf_token'] ?? '';
        
        if (!verifyCSRFToken($csrf_token)) {
            setFlashMessage('error', 'Invalid request.');
        } elseif (empty($full_name) || empty($email) || empty($password)) {
            setFlashMessage('error', 'Please fill all required fields.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlashMessage('error', 'Invalid email address.');
        } elseif (strlen($password) < 8) {
            setFlashMessage('error', 'Password must be at least 8 characters.');
        } else {
            try {
                // Check if email exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    setFlashMessage('error', 'Email already registered.');
                } else {
                    // Create teacher account
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, full_name, department, role, auth_type, profile_completed, is_active) VALUES (?, ?, ?, ?, 'teacher', 'email', TRUE, TRUE)");
                    $stmt->execute([$email, $hashed_password, $full_name, $department]);
                    setFlashMessage('success', 'Teacher account created successfully!');
                }
            } catch (Exception $e) {
                setFlashMessage('error', 'Error creating teacher account.');
            }
        }
        redirect(SITE_URL . '/admin/users.php');
    }
    
    // Handle user activation/deletion
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $user_id = intval($_GET['id']);
        $action = $_GET['action'];
        
        if ($action === 'deactivate') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
            $stmt->execute([$user_id]);
            setFlashMessage('success', 'User deactivated.');
        } elseif ($action === 'activate') {
            $stmt = $pdo->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
            $stmt->execute([$user_id]);
            setFlashMessage('success', 'User activated.');
        } elseif ($action === 'delete') {
            // Prevent deletion of current admin user
            if ($user_id == $_SESSION['user_id']) {
                setFlashMessage('error', 'You cannot delete your own account.');
            } else {
                try {
                    // Start transaction for safe deletion
                    $pdo->beginTransaction();
                    
                    // Delete related records first (due to foreign key constraints)
                    // Delete student profile if exists
                    $stmt = $pdo->prepare("DELETE FROM student_profiles WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete event registrations
                    $stmt = $pdo->prepare("DELETE FROM event_registrations WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Finally delete the user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    
                    $pdo->commit();
                    setFlashMessage('success', 'User deleted successfully.');
                } catch (Exception $e) {
                    $pdo->rollback();
                    setFlashMessage('error', 'Error deleting user: ' . $e->getMessage());
                }
            }
        }
        redirect(SITE_URL . '/admin/users.php');
    }
    
    // Get filter from URL
    $role_filter = $_GET['role'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $page = max(1, intval($_GET['page'] ?? 1));
    $per_page = 20;
    $offset = ($page - 1) * $per_page;

    // Get users with optional role filter and search
    $sql = "SELECT u.*, sp.student_id, sp.roll_no, sp.course, sp.year, 
                     DATE_FORMAT(u.last_login, '%Y-%m-%d %H:%i') as last_login,
                     (SELECT COUNT(*) FROM event_registrations er WHERE er.user_id = u.id) as event_count
                     FROM users u
                     LEFT JOIN student_profiles sp ON u.id = sp.user_id";
    $params = [];
    
    if ($role_filter !== 'all') {
        $sql .= " WHERE u.role = ?";
        $params[] = $role_filter;
    }
    
    if (!empty($search)) {
        $sql .= ($role_filter !== 'all' ? " AND (" : " WHERE (");
        $sql .= "u.full_name LIKE ? OR u.email LIKE ?";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $sql .= ")";
    }
    
    $sql .= " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $per_page;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Get total count for pagination
    $count_sql = "SELECT COUNT(*) FROM users u 
                   LEFT JOIN student_profiles sp ON u.id = sp.user_id";
    $count_params = [];
    
    if ($role_filter !== 'all') {
        $count_sql .= " WHERE u.role = ?";
        $count_params[] = $role_filter;
    } else {
        $count_params = [];
    }
    
    if (!empty($search)) {
        $count_sql .= ($role_filter !== 'all' ? " AND (" : " WHERE (");
        $count_sql .= "u.full_name LIKE ? OR u.email LIKE ?";
        $count_params[] = "%$search%";
        $count_params[] = "%$search%";
        $count_sql .= ")";
    }
    
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($count_params);
    $total_users = $count_stmt->fetchColumn();
    $total_pages = ceil($total_users / $per_page);
    
} catch (Exception $e) {
    $users = [];
}

$page_title = 'Users';
include '../includes/header.php';
?>
<div class="page-header">
    <h1>User Management</h1>
    <button class="btn btn-primary" onclick="showCreateTeacherModal()">
        <i class="fas fa-user-plus"></i> Create Teacher Account
    </button>
</div>

<!-- Pagination Info -->
<?php if ($total_pages > 1): ?>
    <div class="pagination-info">
        <span>Showing <?php echo min($per_page, $total_users - $offset); ?> of <?php echo $total_users; ?> users</span>
    </div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="filter-tabs">
    <a href="?role=all" class="filter-tab <?php echo $role_filter === 'all' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> All Users
    </a>
    <a href="?role=teacher" class="filter-tab <?php echo $role_filter === 'teacher' ? 'active' : ''; ?>">
        <i class="fas fa-chalkboard-teacher"></i> Teachers
    </a>
    <a href="?role=student" class="filter-tab <?php echo $role_filter === 'student' ? 'active' : ''; ?>">
        <i class="fas fa-user-graduate"></i> Students
    </a>
</div>

<!-- Search Bar -->
<div class="search-container">
    <div class="search-box">
        <div class="search-input-wrapper">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="userSearch" class="form-control search-input" placeholder="Search users by name or email...">
            <button type="button" class="search-clear-btn" onclick="clearSearch()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>
<div class="table-container">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Student Info</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="7" class="text-center">No users found.</td></tr>
            <?php else: foreach ($users as $user): ?>
                <tr>
                    <td><strong><?php echo $user['full_name']; ?></strong></td>
                    <td><?php echo $user['email']; ?></td>
                    <td><span class="badge badge-<?php echo $user['role'] === 'admin' ? 'error' : ($user['role'] === 'teacher' ? 'warning' : 'info'); ?>"><?php echo ucfirst($user['role']); ?></span></td>
                    <td><?php if ($user['student_id']): echo $user['student_id'] . ' | ' . $user['course'] . ' Y' . $user['year']; else: echo '-'; endif; ?></td>
                    <td><span class="badge badge-<?php echo $user['is_active'] ? 'success' : 'error'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <?php if ($user['is_active']): ?>
                                    <a href="?action=deactivate&id=<?php echo $user['id']; ?>" class="btn btn-deactivate" title="Deactivate User" onclick="return confirm('Deactivate this user?')">
                                        <i class="fas fa-pause"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="?action=activate&id=<?php echo $user['id']; ?>" class="btn btn-activate" title="Activate User" onclick="return confirm('Activate this user?')">
                                        <i class="fas fa-play"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-delete" title="Delete User" onclick="return confirm('Are you sure you want to delete this user permanently? This action cannot be undone and will remove all associated data.')">
                                    <i class="fas fa-trash-alt"></i>
                                </a>
                            <?php else: ?>
                                <span class="btn btn-current" title="Current User">
                                    <i class="fas fa-shield-alt"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination Controls -->
<?php if ($total_pages > 1): ?>
    <div class="pagination-controls">
        <?php if ($page > 1): ?>
            <a href="?role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">
                <i class="fas fa-chevron-left"></i> Previous
            </a>
        <?php endif; ?>
        
        <span class="page-info">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
        
        <?php if ($page < $total_pages): ?>
            <a href="?role=<?php echo $role_filter; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">
                Next <i class="fas fa-chevron-right"></i>
            </a>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Mobile Card View -->
<div class="mobile-users-container" style="display: none;">
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <i class="fas fa-users"></i>
            <p>No users found.</p>
        </div>
    <?php else: ?>
        <?php foreach ($users as $user): ?>
            <div class="user-card">
                <div class="user-header">
                    <div class="user-info">
                        <h4><?php echo $user['full_name']; ?></h4>
                        <span class="user-email"><?php echo $user['email']; ?></span>
                        <span class="user-id">ID: <?php echo $user['id']; ?></span>
                    </div>
                    <div class="user-role">
                        <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'error' : ($user['role'] === 'teacher' ? 'warning' : 'info'); ?>">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="user-details">
                    <div class="detail-item">
                        <i class="fas fa-info-circle"></i>
                        <div class="detail-content">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">
                                <span class="badge badge-<?php echo $user['is_active'] ? 'success' : 'error'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </span>
                        </div>
                    </div>
                    
                    <?php if ($user['student_id']): ?>
                    <div class="detail-item">
                        <i class="fas fa-graduation-cap"></i>
                        <div class="detail-content">
                            <span class="detail-label">Student Info:</span>
                            <span class="detail-value"><?php echo $user['full_name']; ?> | <?php echo $user['student_id']; ?> | <?php echo $user['course']; ?> Y<?php echo $user['year']; ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="detail-item">
                        <i class="fas fa-user-tie"></i>
                        <div class="detail-content">
                            <span class="detail-label">Department:</span>
                            <span class="detail-value"><?php echo $user['department'] ?: 'Not specified'; ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <i class="fas fa-calendar"></i>
                        <div class="detail-content">
                            <span class="detail-label">Joined:</span>
                            <span class="detail-value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                    </div>
                    
                    <?php if ($user['last_login']): ?>
                    <div class="detail-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <div class="detail-content">
                            <span class="detail-label">Last Login:</span>
                            <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="detail-item">
                        <i class="fas fa-sign-in-alt"></i>
                        <div class="detail-content">
                            <span class="detail-label">Last Login:</span>
                            <span class="detail-value">Never logged in</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="user-actions">
                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                        <?php if ($user['is_active']): ?>
                            <a href="?action=deactivate&id=<?php echo $user['id']; ?>" class="action-btn btn-deactivate" title="Deactivate User" onclick="return confirm('Deactivate this user?')">
                                <i class="fas fa-pause"></i>
                                <span>Deactivate</span>
                            </a>
                        <?php else: ?>
                            <a href="?action=activate&id=<?php echo $user['id']; ?>" class="action-btn btn-activate" title="Activate User" onclick="return confirm('Activate this user?')">
                                <i class="fas fa-play"></i>
                                <span>Activate</span>
                            </a>
                        <?php endif; ?>
                        
                        <a href="?action=delete&id=<?php echo $user['id']; ?>" class="action-btn btn-delete" title="Delete User" onclick="return confirm('Are you sure you want to delete this user permanently? This action cannot be undone and will remove all associated data.')">
                            <i class="fas fa-trash-alt"></i>
                            <span>Delete</span>
                        </a>
                    <?php else: ?>
                        <div class="action-btn btn-current">
                            <i class="fas fa-shield-alt"></i>
                            <span>Current User</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Create Teacher Modal -->
<div id="createTeacherModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create Teacher Account</h3>
            <button class="modal-close" onclick="hideCreateTeacherModal()">&times;</button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="create_teacher" value="1">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>Faculty/Department</label>
                <input type="text" name="department" class="form-control" placeholder="Enter faculty or department">
            </div>
            
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required minlength="8">
                <small>Minimum 8 characters</small>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="hideCreateTeacherModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Teacher</button>
            </div>
        </form>
    </div>
</div>

<style>
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: var(--bg-card);
    padding: 2rem;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}
.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}
.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 1.5rem;
}

/* Filter Tabs Styles */
.filter-tabs {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 2rem;
    background: var(--bg-card);
    padding: 0.5rem;
    border-radius: 12px;
    box-shadow: var(--clay-shadow);
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-secondary);
    font-weight: 500;
    transition: all var(--transition-fast);
    border: 2px solid transparent;
}

.filter-tab:hover {
    background: var(--bg-primary);
    color: var(--text-primary);
    transform: translateY(-1px);
}

.filter-tab.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary-dark);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.filter-tab i {
    font-size: 0.9rem;
}

/* Action Buttons Styles */
.action-buttons {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    justify-content: flex-end;
}

.action-buttons .btn {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-size: 0.9rem;
    position: relative;
    overflow: hidden;
    border: none;
    cursor: pointer;
    text-decoration: none;
}

.action-buttons .btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1));
    transform: translateX(-100%);
    transition: transform 0.6s;
}

.action-buttons .btn:hover::before {
    transform: translateX(100%);
}

.action-but.action-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.action-buttons .btn:active {
    transform: translateY(-1px) scale(0.98);
}

.action-buttons .btn-deactivate {
    background: linear-gradient(135deg, #f59e0b, #f97316);
    color: white;
}

.action-buttons .btn-deactivate:hover {
    background: linear-gradient(135deg, #f97316, #ea580c);
    box-shadow: 0 8px 25px rgba(249, 115, 22, 0.3);
}

.action-buttons .btn-activate {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
}

.action-buttons .btn-activate:hover {
    background: linear-gradient(135deg, #059669, #047857);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
}

.action-buttons .btn-delete {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
}

.action-buttons .btn-delete:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    box-shadow: 0 8px 25px rgba(239, 68, 68, 0.3);
}

.action-buttons .btn-current {
    background: linear-gradient(135deg, #6b7280, #4b5563);
    color: white;
    cursor: default;
}

.action-buttons .btn-current:hover {
    transform: none;
    box-shadow: none;
}

.action-buttons .btn i {
    position: relative;
    z-index: 1;
}

/* Search Input Styles */
.search-container {
    margin-bottom: 2rem;
    overflow-x: hidden; /* Prevent horizontal scroll */
}

.search-box {
    background: var(--bg-card);
    padding: 1rem;
    border-radius: 12px;
    box-shadow: var(--clay-shadow);
    overflow-x: hidden; /* Prevent horizontal scroll */
}

.search-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.search-input {
    width: 100%;
    padding-left: 3rem !important;
    padding-right: 3rem !important;
    border: 2px solid var(--border-color);
    border-radius: 25px;
    background: var(--bg-primary);
    color: var(--text-primary);
    font-size: 0.95rem;
    transition: all var(--transition-fast);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-icon {
    position: absolute;
    left: 1rem;
    color: var(--text-secondary);
    font-size: 0.9rem;
    pointer-events: none;
    z-index: 2;
}

.search-clear-btn {
    position: absolute;
    right: 0.75rem;
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all var(--transition-fast);
    z-index: 2;
}

.search-clear-btn:hover {
    background: var(--bg-hover);
    color: var(--text-primary);
}

.search-clear-btn:active {
    transform: scale(0.95);
}

/* Mobile Filter Tabs */
@media (max-width: 768px) {
    .filter-tabs {
        flex-wrap: wrap;
        gap: 0.25rem;
        padding: 0.25rem;
    }
    
    .filter-tab {
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
        flex: 1;
        min-width: calc(33.333% - 0.17rem);
        text-align: center;
        justify-content: center;
    }
    
    .filter-tab i {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    .filter-tabs {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .filter-tab {
        min-width: 100%;
        padding: 0.75rem;
        font-size: 0.9rem;
    }
}
</style>

<script>
function showCreateTeacherModal() {
    document.getElementById('createTeacherModal').style.display = 'flex';
}

function hideCreateTeacherModal() {
    document.getElementById('createTeacherModal').style.display = 'none';
}

// Search functionality
function searchUsers() {
    const searchInput = document.getElementById('userSearch');
    const searchTerm = searchInput.value.toLowerCase().trim();
    const userCards = document.querySelectorAll('.user-card');
    const tableRows = document.querySelectorAll('.table tbody tr');
    
    if (searchTerm === '') {
        // Show all users
        userCards.forEach(card => card.style.display = 'block');
        tableRows.forEach(row => row.style.display = '');
    } else {
        // Filter users
        userCards.forEach(card => {
            const userName = card.querySelector('h4').textContent.toLowerCase();
            const userEmail = card.querySelector('.user-email').textContent.toLowerCase();
            const userId = card.querySelector('.user-id').textContent.toLowerCase();
            
            if (userName.includes(searchTerm) || userEmail.includes(searchTerm) || userId.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Filter table rows
        tableRows.forEach((row, index) => {
            if (index === 0) return; // Skip header row
            
            const nameCell = row.querySelector('td:nth-child(1) strong');
            const emailCell = row.querySelector('td:nth-child(2)');
            
            if (nameCell && emailCell) {
                const name = nameCell.textContent.toLowerCase();
                const email = emailCell.textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }
}

function clearSearch() {
    document.getElementById('userSearch').value = '';
    searchUsers();
}

// Real-time search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('input', searchUsers);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
