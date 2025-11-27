/**
 * WYATT XXX COLE - NEON CYBER REBEL
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initNavigation();
    initTabs();
    initForms();
    initAnimations();
    initCalendar();
});

/**
 * Mobile Navigation Toggle
 */
function initNavigation() {
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.getElementById('navLinks');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            navToggle.classList.toggle('active');
        });

        // Close menu when clicking a link
        const links = navLinks.querySelectorAll('.nav__link');
        links.forEach(link => {
            link.addEventListener('click', function() {
                navLinks.classList.remove('active');
                navToggle.classList.remove('active');
            });
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!navToggle.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('active');
                navToggle.classList.remove('active');
            }
        });
    }

    // Navbar scroll effect
    const nav = document.getElementById('nav');
    if (nav) {
        let lastScroll = 0;

        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;

            if (currentScroll > 100) {
                nav.style.background = 'rgba(5, 6, 10, 0.95)';
            } else {
                nav.style.background = 'rgba(5, 6, 10, 0.9)';
            }

            lastScroll = currentScroll;
        });
    }
}

/**
 * Tab Component
 */
function initTabs() {
    const tabContainers = document.querySelectorAll('.tabs');

    tabContainers.forEach(container => {
        const tabs = container.querySelectorAll('.tab');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetId = this.getAttribute('data-tab');

                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('tab--active'));

                // Add active class to clicked tab
                this.classList.add('tab--active');

                // Hide all tab content
                const allContent = document.querySelectorAll('.tab-content');
                allContent.forEach(content => {
                    content.classList.remove('tab-content--active');
                });

                // Show target content
                const targetContent = document.getElementById(targetId);
                if (targetContent) {
                    targetContent.classList.add('tab-content--active');
                }
            });
        });
    });
}

/**
 * Form Handling
 */
function initForms() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            // Simple validation
            let isValid = true;
            const requiredFields = this.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--alert-red)';

                    // Reset border after 3 seconds
                    setTimeout(() => {
                        field.style.borderColor = '';
                    }, 3000);
                }
            });

            if (isValid) {
                // Show success message
                showNotification('Form submitted successfully! We\'ll be in touch soon.', 'success');

                // Reset form
                this.reset();

                // Log form data (for demo purposes)
                console.log('Form submitted:', data);
            } else {
                showNotification('Please fill in all required fields.', 'error');
            }
        });

        // Add focus effects to inputs
        const inputs = form.querySelectorAll('.form__input, .form__textarea, .form__select');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });

            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });
    });
}

/**
 * Show Notification
 */
function showNotification(message, type = 'info') {
    // Remove existing notification
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
        existingNotification.remove();
    }

    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 1rem 2rem;
        background: ${type === 'success' ? 'var(--neon-green)' : type === 'error' ? 'var(--alert-red)' : 'var(--neon-purple)'};
        color: ${type === 'success' || type === 'error' ? '#000' : '#fff'};
        border-radius: var(--radius-md);
        font-family: var(--font-heading);
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        z-index: 10000;
        animation: slideIn 0.3s ease forwards;
        box-shadow: 0 0 20px ${type === 'success' ? 'rgba(57, 255, 20, 0.5)' : type === 'error' ? 'rgba(255, 31, 61, 0.5)' : 'rgba(155, 29, 255, 0.5)'};
    `;
    notification.textContent = message;

    // Add close button
    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '&times;';
    closeBtn.style.cssText = `
        margin-left: 1rem;
        background: none;
        border: none;
        color: inherit;
        font-size: 1.25rem;
        cursor: pointer;
        opacity: 0.7;
    `;
    closeBtn.addEventListener('click', () => notification.remove());
    notification.appendChild(closeBtn);

    document.body.appendChild(notification);

    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

/**
 * Scroll Animations
 */
function initAnimations() {
    // Intersection Observer for fade-in animations
    const observerOptions = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    // Observe elements that should animate on scroll
    const animateElements = document.querySelectorAll('.card, .stat, .gallery__item, .product');
    animateElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // Add animation class handler
    document.querySelectorAll('.animate-fade-in').forEach(el => {
        el.style.opacity = '1';
        el.style.transform = 'translateY(0)';
    });
}

/**
 * Calendar Component (Basic)
 */
function initCalendar() {
    const prevBtn = document.getElementById('prevMonth');
    const nextBtn = document.getElementById('nextMonth');
    const titleEl = document.getElementById('calendarTitle');

    if (!prevBtn || !nextBtn || !titleEl) return;

    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();

    function updateTitle() {
        titleEl.textContent = `${months[currentMonth]} ${currentYear}`;
    }

    prevBtn.addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        updateTitle();
    });

    nextBtn.addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        updateTitle();
    });

    // Initialize
    updateTitle();
}

/**
 * Smooth Scroll for anchor links
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;

        e.preventDefault();
        const target = document.querySelector(targetId);

        if (target) {
            const navHeight = document.querySelector('.nav')?.offsetHeight || 0;
            const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - navHeight - 20;

            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });
        }
    });
});

/**
 * Gallery Lightbox (Basic)
 */
document.querySelectorAll('.gallery__item').forEach(item => {
    item.addEventListener('click', function() {
        // Placeholder for lightbox functionality
        console.log('Gallery item clicked - implement lightbox here');
    });
});

/**
 * Post Action Handlers (Community Page)
 */
document.querySelectorAll('.post__action').forEach(action => {
    action.addEventListener('click', function() {
        // Simple toggle effect for demo
        if (this.querySelector('span').innerHTML === '♡') {
            this.querySelector('span').innerHTML = '♥';
            this.style.color = 'var(--alert-red)';
        }
    });
});

/**
 * Product Card Click Handler
 */
document.querySelectorAll('.product').forEach(product => {
    product.addEventListener('click', function() {
        // Placeholder for product detail modal
        const title = this.querySelector('.product__title')?.textContent;
        console.log(`Product clicked: ${title}`);
    });
});

/**
 * Poll Vote Handler (Community Page)
 */
document.querySelectorAll('.post__content .card[style*="cursor: pointer"]').forEach(option => {
    option.addEventListener('click', function() {
        // Simple visual feedback
        this.style.borderColor = 'var(--neon-purple)';
        this.style.background = 'rgba(155, 29, 255, 0.1)';
    });
});

/**
 * Glitch Effect on Hover (Title)
 */
const glitchElements = document.querySelectorAll('.hero__title');
glitchElements.forEach(el => {
    el.addEventListener('mouseenter', function() {
        this.style.animation = 'glitch 0.3s infinite';
    });

    el.addEventListener('mouseleave', function() {
        this.style.animation = 'glitch 2s infinite';
    });
});

/**
 * Button Ripple Effect
 */
document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        const ripple = document.createElement('span');
        ripple.style.cssText = `
            position: absolute;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            transform: translate(-50%, -50%) scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
            left: ${x}px;
            top: ${y}px;
        `;

        this.style.position = 'relative';
        this.style.overflow = 'hidden';
        this.appendChild(ripple);

        setTimeout(() => ripple.remove(), 600);
    });
});

// Add ripple keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: translate(-50%, -50%) scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

/**
 * Lazy Loading Images (when real images are added)
 */
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                if (img.dataset.src) {
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                }
                imageObserver.unobserve(img);
            }
        });
    });

    document.querySelectorAll('img[data-src]').forEach(img => {
        imageObserver.observe(img);
    });
}

console.log('WXXXC - Neon Cyber Rebel | Site Loaded');
