// assets/js/script.js

// ============================================
// USTED-K GYM CENTER - Main JavaScript
// Premium Interactions & Animations
// ============================================

// ===== 1. DOM Ready =====
document.addEventListener('DOMContentLoaded', function() {
    initSidebar();
    initSearch();
    initNotifications();
    initCharts();
    initStaggerAnimations();
    initScrollAnimations();
    initFormValidation();
});

// ===== 2. Sidebar Toggle =====
function initSidebar() {
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (toggle && sidebar) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('open');
            // For mobile
            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('open');
            }
        });
        
        // Close sidebar on outside click (mobile)
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 992) {
                if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                    sidebar.classList.remove('open');
                }
            }
        });
        
        // Handle resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                sidebar.classList.remove('open');
            }
        });
    }
}

// ===== 3. Search Functionality =====
function initSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                if (query) {
                    // Check if on admin page
                    if (window.location.pathname.includes('admin_')) {
                        window.location.href = window.location.pathname + '?search=' + encodeURIComponent(query);
                    } else if (window.location.pathname.includes('dashboard.php') || 
                               window.location.pathname.includes('members.php') ||
                               window.location.pathname.includes('payments.php')) {
                        // Member pages - simple search
                        alert('Searching for: ' + query);
                    }
                }
            }
        });
    }
}

// ===== 4. Notifications =====
function initNotifications() {
    const notifBtn = document.querySelector('.nav-icon .fa-bell')?.closest('.nav-icon');
    if (notifBtn) {
        notifBtn.addEventListener('click', function() {
            // Toggle notification panel or mark as read
            const dot = this.querySelector('.dot');
            if (dot) {
                dot.style.display = 'none';
                // Could add AJAX call to mark notifications as read
                fetch('ajax/mark_notifications.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' }
                }).catch(() => {});
            }
        });
    }
}

// ===== 5. Charts =====
function initCharts() {
    // Charts are initialized in page-specific scripts
    // This is just a placeholder for common chart configuration
    if (typeof Chart !== 'undefined') {
        // Default chart colors
        Chart.defaults.color = '#A68A7A';
        Chart.defaults.borderColor = 'rgba(255,107,0,0.1)';
    }
}

// ===== 6. Stagger Animations =====
function initStaggerAnimations() {
    const staggerElements = document.querySelectorAll('.stagger-children');
    staggerElements.forEach(container => {
        const children = container.children;
        Array.from(children).forEach((child, index) => {
            child.style.animationDelay = (index * 0.05) + 's';
            child.style.opacity = '0';
            child.style.animation = 'fadeIn 0.6s ease forwards';
            child.style.animationDelay = (index * 0.05) + 's';
        });
    });
}

// ===== 7. Scroll Animations =====
function initScrollAnimations() {
    const scrollElements = document.querySelectorAll('.scroll-fade');
    
    if (scrollElements.length === 0) return;
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: '0px 0px -50px 0px'
    });
    
    scrollElements.forEach(el => observer.observe(el));
}

// ===== 8. Form Validation =====
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const required = this.querySelectorAll('[required]');
            let valid = true;
            
            required.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('error');
                    valid = false;
                } else {
                    field.classList.remove('error');
                }
            });
            
            // Password match validation
            const password = this.querySelector('input[name="password"]');
            const confirm = this.querySelector('input[name="confirm_password"]');
            if (password && confirm && password.value !== confirm.value) {
                confirm.classList.add('error');
                alert('Passwords do not match!');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
                // Scroll to first error
                const firstError = this.querySelector('.error');
                if (firstError) {
                    firstError.focus();
                }
            }
        });
        
        // Remove error on focus
        form.querySelectorAll('.form-control').forEach(field => {
            field.addEventListener('focus', function() {
                this.classList.remove('error');
            });
        });
    });
}

// ===== 9. Toast Notifications =====
function showToast(message, type = 'success') {
    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} animate-fade-in`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.maxWidth = '400px';
    toast.style.boxShadow = 'var(--shadow-lg)';
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i> ${message}`;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
}

// ===== 10. Copy to Clipboard =====
function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard!', 'success');
        }).catch(() => {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    showToast('Copied to clipboard!', 'success');
}

// ===== 11. Confirm Dialog =====
function confirmAction(message, callback) {
    if (confirm(message)) {
        callback();
    }
}

// ===== 12. Format Currency =====
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// ===== 13. Time Ago =====
function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    const intervals = [
        { label: 'year', seconds: 31536000 },
        { label: 'month', seconds: 2592000 },
        { label: 'day', seconds: 86400 },
        { label: 'hour', seconds: 3600 },
        { label: 'minute', seconds: 60 }
    ];
    
    for (const interval of intervals) {
        const count = Math.floor(seconds / interval.seconds);
        if (count >= 1) {
            return count + ' ' + interval.label + (count > 1 ? 's' : '') + ' ago';
        }
    }
    return 'Just now';
}

// ===== 14. Loader =====
function showLoader() {
    const loader = document.createElement('div');
    loader.className = 'loading-overlay';
    loader.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                    background: rgba(0,0,0,0.5); display: flex; align-items: center; 
                    justify-content: center; z-index: 9999;">
            <div class="loading-spinner"></div>
        </div>
    `;
    document.body.appendChild(loader);
    return loader;
}

function hideLoader(loader) {
    if (loader) {
        loader.remove();
    }
}

// ===== 15. Dark Mode Toggle (Optional) =====
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

// Check saved preference
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}

// ===== 16. Export Functions =====
// Make functions globally accessible
window.showToast = showToast;
window.copyToClipboard = copyToClipboard;
window.confirmAction = confirmAction;
window.formatCurrency = formatCurrency;
window.timeAgo = timeAgo;
window.toggleDarkMode = toggleDarkMode;
window.showLoader = showLoader;
window.hideLoader = hideLoader;

console.log('🏋️ USTED-K Gym Center - Scripts Loaded Successfully');
console.log('💪 Train Hard. Stay Healthy. Become Stronger.');