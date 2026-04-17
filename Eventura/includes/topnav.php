<?php
/**
 * Eventura - Top Navigation Include
 */
?>
<header class="top-nav">
    <button class="menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="nav-right">
        <button class="theme-toggle" onclick="toggleTheme()">
            <i class="fas fa-moon"></i>
        </button>
        
        <div class="user-menu">
            <div class="user-info">
                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
            </div>
            <div class="user-avatar">
                <i class="fas fa-<?php echo $_SESSION['user_role'] === 'admin' ? 'user-shield' : ($_SESSION['user_role'] === 'teacher' ? 'chalkboard-teacher' : 'user'); ?>"></i>
            </div>
        </div>
    </div>
</header>
