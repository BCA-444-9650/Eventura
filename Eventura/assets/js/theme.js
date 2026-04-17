// Theme Toggle and Dark Mode JavaScript

// Theme Manager Class
class ThemeManager {
    constructor() {
        this.currentTheme = this.getStoredTheme() || 'light';
        this.init();
    }

    init() {
        // Apply stored theme on page load
        this.applyTheme(this.currentTheme);
        
        // Create theme toggle button
        this.createThemeToggle();
        
        // Listen for system theme changes
        this.listenForSystemThemeChanges();
        
        // Add keyboard shortcut (Ctrl/Cmd + Shift + D)
        this.addKeyboardShortcut();
    }

    getStoredTheme() {
        return localStorage.getItem('eventura-theme') || 'light';
    }

    setStoredTheme(theme) {
        localStorage.setItem('eventura-theme', theme);
    }

    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        this.currentTheme = theme;
        this.updateToggleButton();
        this.setStoredTheme(theme);
        
        // Dispatch custom event for theme change
        document.dispatchEvent(new CustomEvent('themeChanged', { 
            detail: { theme: theme } 
        }));
    }

    toggleTheme() {
        const newTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        
        // Add rotation animation to toggle button
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            toggle.classList.add('rotating');
            setTimeout(() => {
                toggle.classList.remove('rotating');
            }, 500);
        }
        
        this.applyTheme(newTheme);
        
        // Show theme change notification
        this.showThemeNotification(newTheme);
    }

    createThemeToggle() {
        // Check if toggle already exists
        if (document.querySelector('.theme-toggle')) {
            return;
        }

        const toggle = document.createElement('button');
        toggle.className = 'theme-toggle';
        toggle.setAttribute('aria-label', 'Toggle dark mode');
        toggle.setAttribute('title', 'Toggle dark mode (Ctrl+Shift+D)');
        
        toggle.innerHTML = `
            <svg class="sun-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 7c-2.76 0-5 2.24-5 5s2.24 5 5 5 5-2.24 5-5-2.24-5-5-5zM2 13h2c.55 0 1-.45 1-1s-.45-1-1-1H2c-.55 0-1 .45-1 1s.45 1 1 1zm18 0h2c.55 0 1-.45 1-1s-.45-1-1-1h-2c-.55 0-1 .45-1 1s.45 1 1 1zM11 2v2c0 .55.45 1 1 1s1-.45 1-1V2c0-.55-.45-1-1-1s-1 .45-1 1zm0 18v2c0 .55.45 1 1 1s1-.45 1-1v-2c0-.55-.45-1-1-1s-1 .45-1 1zM5.99 4.58c-.39-.39-1.03-.39-1.41 0-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0s.39-1.03 0-1.41L5.99 4.58zm12.37 12.37c-.39-.39-1.03-.39-1.41 0-.39.39-.39 1.03 0 1.41l1.06 1.06c.39.39 1.03.39 1.41 0 .39-.39.39-1.03 0-1.41l-1.06-1.06zm1.06-10.96c.39-.39.39-1.03 0-1.41-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06zM7.05 18.36c.39-.39.39-1.03 0-1.41-.39-.39-1.03-.39-1.41 0l-1.06 1.06c-.39.39-.39 1.03 0 1.41s1.03.39 1.41 0l1.06-1.06z"/>
            </svg>
            <svg class="moon-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M9 2c-1.05 0-2.05.16-3 .46 4.06 1.27 7 5.06 7 9.54 0 4.48-2.94 8.27-7 9.54.95.3 1.95.46 3 .46 5.52 0 10-4.48 10-10S14.52 2 9 2z"/>
            </svg>
        `;

        toggle.addEventListener('click', () => this.toggleTheme());
        
        // Add to body
        document.body.appendChild(toggle);
        
        // Position toggle based on page type
        this.positionThemeToggle();
    }

    positionThemeToggle() {
        const toggle = document.querySelector('.theme-toggle');
        if (!toggle) return;

        // Check if we're on an auth page
        const isAuthPage = document.querySelector('.auth-container');
        
        if (isAuthPage) {
            // Position on auth pages
            toggle.style.position = 'fixed';
            toggle.style.top = '20px';
            toggle.style.right = '20px';
            toggle.style.zIndex = '1001';
        } else {
            // Position on dashboard pages (adjust if sidebar exists)
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                toggle.style.position = 'fixed';
                toggle.style.top = '20px';
                toggle.style.right = '20px';
                toggle.style.zIndex = '1001';
            } else {
                // For pages without sidebar
                toggle.style.position = 'fixed';
                toggle.style.top = '20px';
                toggle.style.right = '20px';
                toggle.style.zIndex = '1001';
            }
        }
    }

    updateToggleButton() {
        const toggle = document.querySelector('.theme-toggle');
        if (!toggle) return;

        const sunIcon = toggle.querySelector('.sun-icon');
        const moonIcon = toggle.querySelector('.moon-icon');
        
        if (this.currentTheme === 'dark') {
            sunIcon.style.display = 'none';
            moonIcon.style.display = 'block';
            toggle.setAttribute('aria-label', 'Switch to light mode');
            toggle.setAttribute('title', 'Switch to light mode (Ctrl+Shift+D)');
        } else {
            sunIcon.style.display = 'block';
            moonIcon.style.display = 'none';
            toggle.setAttribute('aria-label', 'Switch to dark mode');
            toggle.setAttribute('title', 'Switch to dark mode (Ctrl+Shift+D)');
        }
    }

    listenForSystemThemeChanges() {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            mediaQuery.addEventListener('change', (e) => {
                // Only auto-switch if user hasn't manually set a preference
                if (!localStorage.getItem('eventura-theme')) {
                    const systemTheme = e.matches ? 'dark' : 'light';
                    this.applyTheme(systemTheme);
                }
            });
        }
    }

    addKeyboardShortcut() {
        document.addEventListener('keydown', (e) => {
            // Ctrl+Shift+D or Cmd+Shift+D
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleTheme();
            }
        });
    }

    showThemeNotification(theme) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = 'theme-notification';
        notification.style.cssText = `
            position: fixed;
            top: 80px;
            right: 20px;
            background: ${theme === 'dark' ? '#1a1d23' : '#ffffff'};
            color: ${theme === 'dark' ? '#ffffff' : '#333333'};
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            z-index: 1002;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            border: 1px solid ${theme === 'dark' ? '#3a3d47' : '#e9ecef'};
        `;

        const icon = theme === 'dark' ? '🌙' : '☀️';
        const message = theme === 'dark' ? 'Dark mode enabled' : 'Light mode enabled';
        
        notification.innerHTML = `<span style="font-size: 18px;">${icon}</span> ${message}`;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }

    // Public method to get current theme
    getCurrentTheme() {
        return this.currentTheme;
    }

    // Public method to set theme programmatically
    setTheme(theme) {
        if (theme === 'light' || theme === 'dark') {
            this.applyTheme(theme);
        }
    }
}

// Initialize theme manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.themeManager = new ThemeManager();
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ThemeManager;
}
