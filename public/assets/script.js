// Modern JavaScript for enhanced UX - Fixed Version
document.addEventListener('DOMContentLoaded', function() {
    // Global functions that need to be accessible
    window.showAlert = function(message, type = 'info', duration = 5000) {
        // Alert icons
        const icons = {
            success: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg>`,
            info: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v4"></path><path d="M12 16h.01"></path></svg>`,
            warning: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"></path><path d="M12 17h.01"></path><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"></path></svg>`,
            error: `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18"></path><path d="M6 6l12 12"></path></svg>`
        };
        // Create alert element
        const alert = document.createElement('div');
        alert.className = `alert alert--${type}`;
        alert.setAttribute('role', type === 'error' || type === 'warning' ? 'alert' : 'status');
        alert.setAttribute('aria-live', type === 'error' || type === 'warning' ? 'assertive' : 'polite');

        // Close button icon
        const closeIcon = `<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>`;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    };

    // Add loading states to buttons
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.type === 'submit') {
                this.style.opacity = '0.7';
                this.style.pointerEvents = 'none';
                setTimeout(() => {
                    this.style.opacity = '1';
                    this.style.pointerEvents = 'auto';
                }, 2000);
            }
        });
    });

    // Add smooth tab transitions
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            // Add ripple effect
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });

    // Add form validation feedback
    const inputs = document.querySelectorAll('.input');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.checkValidity()) {
                this.style.borderColor = 'var(--success-solid)';
            } else {
                this.style.borderColor = 'var(--danger-solid)';
            }
        });
        
        input.addEventListener('focus', function() {
            this.style.borderColor = 'var(--primary-solid)';
        });
    });

    // Enhanced hover effects for cards with tilt
    const cards = document.querySelectorAll('.card');
    
    // Define event handlers outside the loop to prevent memory leaks
    const handleCardEnter = function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        const rotateX = Math.min(Math.max((y / rect.height) * 10, -10), 10);
        const rotateY = Math.min(Math.max(-(x / rect.width) * 10, -10), 10);
        
        this.style.transform = `translateY(-6px) scale(1.02) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
        this.style.transition = 'transform 0.1s ease';
    };
    
    const handleCardMove = function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left - rect.width / 2;
        const y = e.clientY - rect.top - rect.height / 2;
        const rotateX = Math.min(Math.max((y / rect.height) * 10, -10), 10);
        const rotateY = Math.min(Math.max(-(x / rect.width) * 10, -10), 10);
        
        this.style.transform = `translateY(-6px) scale(1.02) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`;
    };
    
    const handleCardLeave = function() {
        this.style.transform = 'translateY(0) scale(1) rotateX(0deg) rotateY(0deg)';
        this.style.transition = 'transform 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    };
    
    cards.forEach(card => {
        card.addEventListener('mouseenter', handleCardEnter);
        card.addEventListener('mousemove', handleCardMove);
        card.addEventListener('mouseleave', handleCardLeave);
    });

    // Add ripple effect CSS
    const style = document.createElement('style');
    style.textContent = `
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        .toast-success {
            background: #E7F8F3;
            border: 1.5px solid #A7E9D2;
            color: #0D9B6D;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.06);
            font-size: 1rem;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            animation: slideIn 0.3s ease-out;
        }
        .toast-success .toast-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            background: #A7E9D2;
            border-radius: 50%;
            color: #0D9B6D;
            margin-right: 8px;
        }
        .toast-success .toast-close {
            background: transparent;
            border: none;
            color: #0D9B6D;
            opacity: 0.6;
            font-size: 1.1rem;
            margin-left: auto;
            cursor: pointer;
            border-radius: 4px;
            padding: 2px 6px;
            transition: background 0.2s, opacity 0.2s;
        }
        .toast-success .toast-close:hover {
            background: rgba(13, 155, 109, 0.08);
            opacity: 1;
        }
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);

    // Enhanced alert management
    const setupAlerts = () => {
        const alerts = document.querySelectorAll('.alert:not([data-initialized])');
        alerts.forEach(alert => {
            // Mark as initialized to prevent double setup
            alert.setAttribute('data-initialized', 'true');
            
            // Add close button if not present
            if (!alert.querySelector('.alert-close')) {
                const closeBtn = document.createElement('button');
                closeBtn.className = 'alert-close';
                closeBtn.innerHTML = '×'; // Fixed: proper multiplication symbol
                closeBtn.addEventListener('click', () => hideAlert(alert));
                alert.appendChild(closeBtn);
            }

            // Auto-hide after 5 seconds
            setTimeout(() => hideAlert(alert), 5000);
        });
    };

    const hideAlert = (alert) => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-20px)';
        setTimeout(() => alert.remove(), 300);
    };

    // Initial setup
    setupAlerts();

    // Setup new alerts when content changes
    const alertObserver = new MutationObserver(() => {
        setupAlerts();
    });

    alertObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K for search (if search input exists)
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            const searchInput = document.querySelector('input[name="q"]');
            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
            }
        }
        
        // Escape to close modals/alerts
        if (e.key === 'Escape') {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => alert.remove());
        }
    });

    /* Dynamic tooltip for collapsed sidebar: create one tooltip element and reuse it */
    // (function setupSidebarTooltip() {
    //     const sidebar = document.querySelector('.vertical-sidebar');
    //     if (!sidebar) return;

    //     const tooltip = document.createElement('div');
    //     tooltip.className = 'sidebar-tooltip';
    //     tooltip.setAttribute('role', 'tooltip');
    //     tooltip.style.position = 'fixed';
    //     tooltip.style.pointerEvents = 'none';
    //     tooltip.style.opacity = '0';
    //     tooltip.style.transform = 'translateY(-6px)';
    //     tooltip.style.transition = 'opacity 0.15s ease, transform 0.15s ease';
    //     tooltip.style.zIndex = '2000';
    //     tooltip.style.padding = '8px 12px';
    //     tooltip.style.background = 'rgba(17,24,39,0.95)';
    //     tooltip.style.color = '#fff';
    //     tooltip.style.borderRadius = '6px';
    //     tooltip.style.fontSize = '0.9rem';
    //     tooltip.style.fontWeight = '600';
    //     tooltip.style.boxShadow = '0 8px 20px rgba(2,6,23,0.5)';
    //     tooltip.style.whiteSpace = 'nowrap';
    //     document.body.appendChild(tooltip);

    //     let showTimeout = null;

    //     let lastTooltipTarget = null;

    //     function showTooltipFor(link) {
    //         if (!link) return;
    //         const label = link.getAttribute('data-tooltip') || link.textContent.trim();
    //         if (!label) return;

    //         if (link.hasAttribute('title')) {
    //             try {
    //                 link.dataset.origTitle = link.getAttribute('title');
    //                 link.removeAttribute('title');
    //             } catch (err) {
    //             }
    //         }
    //         lastTooltipTarget = link;

    //         tooltip.textContent = label;

    //         const icon = link.querySelector('.icon, .logout-svg-wrapper');
    //         const rect = (icon || link).getBoundingClientRect();

    //         const left = rect.right + 12; 
    //         const top = rect.top + rect.height / 2;

    //         tooltip.style.left = left + 'px';
    //         tooltip.style.top = top + 'px';
    //         tooltip.style.transform = 'translateY(-50%) translateY(-6px)';
    //         clearTimeout(showTimeout);
    //         showTimeout = setTimeout(() => {
    //             tooltip.style.opacity = '1';
    //             tooltip.style.transform = 'translateY(-50%) translateY(0)';
    //         }, 60);
    //     }

    //     function hideTooltip() {
    //         clearTimeout(showTimeout);
    //         tooltip.style.opacity = '0';
    //         tooltip.style.transform = 'translateY(-50%) translateY(-6px)';

    //         if (lastTooltipTarget) {
    //             try {
    //                 if (lastTooltipTarget.dataset && lastTooltipTarget.dataset.origTitle) {
    //                     lastTooltipTarget.setAttribute('title', lastTooltipTarget.dataset.origTitle);
    //                     delete lastTooltipTarget.dataset.origTitle;
    //                 }
    //             } catch (err) {
    //             }
    //             lastTooltipTarget = null;
    //         }
    //     }

    //     sidebar.addEventListener('pointerenter', (e) => {
    //         const link = e.target.closest('.sidebar__link');
    //         if (!link) return;

    //         const collapsed = document.querySelector('.vertical-sidebar .checkbox-input:not(:checked)');
    //         if (!collapsed) return;
    //         showTooltipFor(link);
    //     }, { capture: true });

    //     sidebar.addEventListener('pointerleave', (e) => {
    //         const related = e.relatedTarget;
    //         if (related && document.body.contains(related) && related.closest('.vertical-sidebar')) {

    //             return;
    //         }
    //         hideTooltip();
    //     });

    //     sidebar.addEventListener('focusin', (e) => {
    //         const link = e.target.closest('.sidebar__link');
    //         if (!link) return;
    //         const collapsed = document.querySelector('.vertical-sidebar .checkbox-input:not(:checked)');
    //         if (!collapsed) return;
    //         showTooltipFor(link);
    //     });
    //     sidebar.addEventListener('focusout', (e) => {
    //         hideTooltip();
    //     });


    //     window.addEventListener('scroll', hideTooltip, true);
    //     window.addEventListener('resize', hideTooltip);

    //     document.addEventListener('keydown', (e) => { if (e.key === 'Escape') hideTooltip(); });
    // })();

    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Fixed form submission handling
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Prevent double submissions
            if (this.dataset.submitting === 'true') {
                e.preventDefault();
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !this.hasAttribute('data-ajax')) {
                // Only modify UI for non-AJAX forms
                this.dataset.submitting = 'true';
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid transparent; border-top: 2px solid currentColor; border-radius: 50%; animation: spin 1s linear infinite;"></span> Loading...';
                submitBtn.disabled = true;

                // Reset form state after a delay (assuming form will redirect/reload)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                    this.dataset.submitting = 'false';
                }, 3000);
            }
            
            // For AJAX forms, you would handle the submission here with fetch()
            // and call showToast() based on the response
        });
    });

    // Add spin animation CSS
    const spinStyle = document.createElement('style');
    spinStyle.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    `;
    document.head.appendChild(spinStyle);

    // Initialize charts with proper loading detection
    initializeChartsWhenReady();
    
    // Add parallax effect to background
    if (document.querySelector('.login-container')) {
        window.addEventListener('mousemove', function(e) {
            const mouseX = e.clientX / window.innerWidth - 0.5;
            const mouseY = e.clientY / window.innerHeight - 0.5;
            
            const container = document.querySelector('.login-container');
            if (container) {
                container.style.transform = `translate(${mouseX * 20}px, ${mouseY * 20}px)`;
            }
        });
    }
    
    // Add intersection observer for fade-in animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };
    
    const fadeObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards';
                fadeObserver.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // Observe all cards for animation
    document.querySelectorAll('.card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        fadeObserver.observe(card);
    });

    // Add loading state management
    window.addEventListener('beforeunload', function() {
        document.body.style.opacity = '0.7';
        document.body.style.pointerEvents = 'none';
    });

    // Add page load animation
    window.addEventListener('load', function() {
        document.body.style.opacity = '1';
    });

    // Add additional styles for fadeInUp and scrollbar
    const additionalStyles = document.createElement('style');
    additionalStyles.textContent = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .perspective {
            perspective: 1000px;
        }

        .preserve-3d {
            transform-style: preserve-3d;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #7c8bea 0%, #8b5aa2 100%);
        }
    `;
    document.head.appendChild(additionalStyles);
});

// Fixed Chart initialization with proper loading detection
function initializeChartsWhenReady() {
    function checkAndInit() {
        if (typeof Chart !== 'undefined') {
            initializeCharts();
        } else {
            // Check again after a short delay
            setTimeout(checkAndInit, 100);
        }
    }
    
    // If Chart.js is loaded via script tag, wait for it
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', checkAndInit);
    } else {
        checkAndInit();
    }
}

// Chart initialization function with light theme support
function initializeCharts() {
    // Light theme colors
    const chartColors = {
        primary: '#3b82f6',
        success: '#10b981',
        warning: '#f59e0b',
        danger: '#ef4444',
        info: '#06b6d4',
        text: '#1e293b',
        textSecondary: '#64748b',
        border: '#e2e8f0',
        background: '#ffffff'
    };
    
    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart');
    if (attendanceCtx) {
        const chartData = JSON.parse(attendanceCtx.dataset.chartData || '{}');

        try {
            if (chartData.labels && chartData.data) {
                new Chart(attendanceCtx, {
                type: 'line',
                data: {
                        labels: chartData.labels,
                        datasets: [{
                            label: 'Present Employees',
                            data: chartData.data,
                        borderColor: chartColors.primary,
                        backgroundColor: chartColors.primary + '20',
                        pointBackgroundColor: chartColors.primary,
                        pointBorderColor: chartColors.background,
                        pointBorderWidth: 2,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    plugins: {
                        legend: {
                            labels: {
                                color: chartColors.text,
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 14,
                                    weight: 500
                                },
                                padding: 20
                            }
                        },
                        tooltip: {
                            backgroundColor: chartColors.background,
                            titleColor: chartColors.text,
                            bodyColor: chartColors.textSecondary,
                            borderColor: chartColors.border,
                            borderWidth: 1,
                            cornerRadius: 8,
                            padding: 12
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: chartColors.textSecondary,
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            },
                            grid: {
                                color: chartColors.border,
                                borderDash: [2, 4]
                            },
                            border: {
                                color: chartColors.border
                            }
                        },
                        x: {
                            ticks: {
                                color: chartColors.textSecondary,
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 12
                                }
                            },
                            grid: {
                                color: chartColors.border,
                                borderDash: [2, 4]
                            },
                            border: {
                                color: chartColors.border
                            }
                        }
                    }
                }
            });
            } else {
                attendanceCtx.innerHTML = '<div class="chart-placeholder">📊 No attendance data available for the last 7 days.</div>';
            }
        } catch (error) {
            console.error('Error creating attendance chart:', error);
            attendanceCtx.innerHTML = '<div class="chart-error">⚠️ Error loading attendance chart</div>';
        }
    }

    // Project Progress Chart
    const progressCtx = document.getElementById('progressChart');
    if (progressCtx) {
        try {
            new Chart(progressCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Pending'],
                    datasets: [{
                        data: [45, 35, 20],
                        backgroundColor: [
                            chartColors.success,
                            chartColors.warning,
                            chartColors.info
                        ],
                        borderColor: chartColors.background,
                        borderWidth: 3,
                        hoverBorderWidth: 4,
                        hoverOffset: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '60%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: chartColors.text,
                                font: {
                                    family: 'Inter, sans-serif',
                                    size: 13,
                                    weight: 500
                                },
                                padding: 20,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            backgroundColor: chartColors.background,
                            titleColor: chartColors.text,
                            bodyColor: chartColors.textSecondary,
                            borderColor: chartColors.border,
                            borderWidth: 1,
                            cornerRadius: 8,
                            padding: 12,
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return `${label}: ${percentage}%`;
                                }
                            }
                        }
                    },
                    elements: {
                        arc: {
                            borderRadius: 4
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error creating progress chart:', error);
            progressCtx.innerHTML = '<div class="chart-error">⚠️ Error loading progress chart</div>';
        }
    }

    // Add fallback for missing charts
    const allChartElements = document.querySelectorAll('[id$="Chart"]');
    allChartElements.forEach(element => {
        if (!element.innerHTML.trim()) {
            element.innerHTML = '<div class="chart-placeholder">📊 Chart will appear here when data is available</div>';
        }
    });
}

// Dismiss static alerts with close button
document.addEventListener('click', function(e) {
  if (e.target.closest('.alert__close')) {
    const alert = e.target.closest('.alert');
    if (alert) alert.remove();
  }
});

// Toggle password visibility
function togglePasswordVisibility() {
    var input = document.getElementById("login-password");
    var iconShow = document.getElementById("eye-icon-show");
    var iconHide = document.getElementById("eye-icon-hide");
    if (input && input.type === "password") {
        input.type = "text";
        if (iconShow) iconShow.style.display = "none";
        if (iconHide) iconHide.style.display = "block";
    } else if (input) {
        input.type = "password";
        if (iconShow) iconShow.style.display = "block";
        if (iconHide) iconHide.style.display = "none";
    }
}
