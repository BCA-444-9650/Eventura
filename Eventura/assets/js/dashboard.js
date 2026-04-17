/**
 * Eventura - Dashboard JavaScript
 * Theme handling, sidebar toggle, and UI interactions
 */

// Theme Management
function initTheme() {
    const savedTheme = localStorage.getItem('eventura-theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);
}

function toggleTheme() {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('eventura-theme', newTheme);
    updateThemeIcon(newTheme);
}

function updateThemeIcon(theme) {
    const icon = document.querySelector('.theme-toggle i');
    if (icon) {
        icon.className = theme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
    }
}

// Sidebar Toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (sidebar) {
        const isOpen = sidebar.classList.contains('show');
        
        if (isOpen) {
            sidebar.classList.remove('show');
            document.body.style.overflow = '';
            // Remove overlay
            const overlay = document.querySelector('.sidebar-overlay');
            if (overlay) overlay.remove();
        } else {
            sidebar.classList.add('show');
            document.body.style.overflow = 'hidden';
            // Add overlay for mobile
            if (window.innerWidth <= 768) {
                const overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    z-index: 999;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                `;
                document.body.appendChild(overlay);
                
                // Animate overlay
                setTimeout(() => overlay.style.opacity = '1', 10);
                
                // Close sidebar on overlay click
                overlay.addEventListener('click', () => toggleSidebar());
            }
        }
    }
}

// Mobile Menu Toggle for Student Navbar
function toggleMobileMenu() {
    const navLinks = document.getElementById('mobileNav');
    if (navLinks) {
        navLinks.classList.toggle('show');
    }
}

// User Dropdown
function toggleUserMenu() {
    const dropdown = document.getElementById('userDropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

// Close dropdowns when clicking outside
function handleOutsideClick(e) {
    // Close user dropdown
    const userDropdown = document.getElementById('userDropdown');
    const userTrigger = document.querySelector('.user-trigger');
    
    if (userDropdown && userTrigger && !userTrigger.contains(e.target)) {
        userDropdown.classList.remove('show');
    }
    
    // Close sidebar on mobile when clicking outside
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    const overlay = document.querySelector('.sidebar-overlay');
    
    if (sidebar && menuToggle && 
        sidebar.classList.contains('show') && 
        !sidebar.contains(e.target) && 
        !menuToggle.contains(e.target) &&
        !overlay?.contains(e.target)) {
        toggleSidebar();
    }
}

// Flash Messages Auto-dismiss
function initFlashMessages() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    initTheme();
    initFlashMessages();
    initFormValidation();
    
    // Handle outside clicks
    document.addEventListener('click', handleOutsideClick);
    
    // Handle escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const sidebar = document.querySelector('.sidebar');
            const userDropdown = document.getElementById('userDropdown');
            
            if (sidebar && sidebar.classList.contains('show')) {
                toggleSidebar();
            }
            if (userDropdown) {
                userDropdown.classList.remove('show');
            }
        }
    });
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            handleResize();
        }, 250);
    });
    
    // Initialize mobile optimizations
    initMobileOptimizations();
});

// Handle window resize
function handleResize() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    // Close sidebar on desktop resize
    if (window.innerWidth > 768 && sidebar && sidebar.classList.contains('show')) {
        toggleSidebar();
    }
    
    // Remove overlay if switching to desktop
    if (window.innerWidth > 768 && overlay) {
        overlay.remove();
        document.body.style.overflow = '';
    }
}

// Mobile optimizations
function initMobileOptimizations() {
    // Add touch-friendly interactions
    if ('ontouchstart' in window) {
        document.body.classList.add('touch-device');
    }
    
    // Handle orientation changes
    window.addEventListener('orientationchange', function() {
        setTimeout(function() {
            handleResize();
        }, 100);
    });
    
    // Improve table scrolling on mobile
    const tables = document.querySelectorAll('.table-container');
    tables.forEach(table => {
        table.addEventListener('touchstart', function() {
            this.style.overflowX = 'auto';
        });
    });
}

// QR Scanner functions (placeholder for scanner page)
function startQRScanner() {
    // Implementation will be in qr_scanner.php
    console.log('Starting QR scanner...');
}

// Print functionality
function printTicket() {
    window.print();
}

// Export to CSV
function exportToCSV(filename, data) {
    const csvContent = "data:text/csv;charset=utf-8," + data.map(e => e.join(",")).join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
