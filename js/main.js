/**
 * WYATT XXX COLE - COUNTRY BOTTOM BOY
 * Main JavaScript File
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initNavigation();
    initTabs();
    initForms();
    initAnimations();
    initCalendar();
    initCommunity();
    initShop();
    initPortfolio();
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
 * Form Handling - Connected to Backend API
 */
function initForms() {
    const forms = document.querySelectorAll('form');

    forms.forEach(form => {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            // Get form data
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const formId = this.id || this.getAttribute('data-form-type');

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

            if (!isValid) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }

            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"], .btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Submitting...';
            }

            try {
                let response;

                // Route to correct API endpoint based on form
                if (formId === 'studio-inquiry-form' || formId === 'epk-form') {
                    response = await API.submitStudioInquiry(data);
                } else if (formId === 'professional-booking-form') {
                    response = await API.submitProfessionalBooking(data);
                } else if (formId === 'creator-collab-form') {
                    response = await API.submitCreatorCollab(data);
                } else if (formId === 'newsletter-form') {
                    response = await API.subscribeNewsletter(data.email, 'shop');
                } else if (formId === 'schedule-notify-form') {
                    response = await API.signUpForNotification(data.email, data.city);
                } else {
                    // Default to general contact
                    response = await API.submitContactForm(data);
                }

                // Show success message
                showNotification(response.message || 'Form submitted successfully! We\'ll be in touch soon.', 'success');

                // Reset form
                this.reset();

            } catch (error) {
                showNotification(error.message || 'Something went wrong. Please try again.', 'error');
            } finally {
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                }
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
        background: ${type === 'success' ? 'var(--whiskey)' : type === 'error' ? 'var(--alert-red)' : 'var(--whiskey)'};
        color: ${type === 'success' || type === 'error' ? '#000' : '#fff'};
        border-radius: var(--radius-md);
        font-family: var(--font-heading);
        font-size: 0.875rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        z-index: 10000;
        animation: slideIn 0.3s ease forwards;
        box-shadow: 0 0 20px ${type === 'success' ? 'rgba(218, 165, 77, 0.5)' : type === 'error' ? 'rgba(92, 64, 51, 0.5)' : 'rgba(198, 142, 63, 0.5)'};
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
 * Calendar Component - Connected to Backend API
 */
function initCalendar() {
    const prevBtn = document.getElementById('prevMonth');
    const nextBtn = document.getElementById('nextMonth');
    const titleEl = document.getElementById('calendarTitle');
    const calendarGrid = document.querySelector('.calendar__grid');

    if (!prevBtn || !nextBtn || !titleEl) return;

    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();

    async function loadCalendar() {
        titleEl.textContent = `${months[currentMonth]} ${currentYear}`;

        if (!calendarGrid) return;

        try {
            const data = await API.getCalendar(currentYear, currentMonth + 1);
            renderCalendar(data.calendar);
        } catch (error) {
            console.error('Failed to load calendar:', error);
        }
    }

    function renderCalendar(calendarData) {
        if (!calendarGrid) return;

        // Clear existing days (keep headers)
        const days = calendarGrid.querySelectorAll('.calendar__day:not(.calendar__day--header)');
        days.forEach(day => day.remove());

        // Get first day of month
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const today = new Date();

        // Add empty cells for days before first day
        for (let i = 0; i < firstDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar__day calendar__day--empty';
            calendarGrid.appendChild(emptyDay);
        }

        // Add days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${currentYear}-${String(currentMonth + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayData = calendarData[dateStr] || { status: 'available' };

            const dayEl = document.createElement('div');
            dayEl.className = 'calendar__day';
            dayEl.textContent = day;

            // Add status class
            if (dayData.status === 'booked') {
                dayEl.classList.add('calendar__day--booked');
            } else if (dayData.status === 'available') {
                dayEl.classList.add('calendar__day--available');
            } else if (dayData.status === 'off') {
                dayEl.classList.add('calendar__day--off');
            }

            // Check if today
            if (currentYear === today.getFullYear() &&
                currentMonth === today.getMonth() &&
                day === today.getDate()) {
                dayEl.classList.add('calendar__day--today');
            }

            calendarGrid.appendChild(dayEl);
        }
    }

    prevBtn.addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        loadCalendar();
    });

    nextBtn.addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        loadCalendar();
    });

    // Load upcoming cities
    loadCities();

    // Initialize
    loadCalendar();
}

async function loadCities() {
    const citiesContainer = document.querySelector('.cities-list');
    if (!citiesContainer) return;

    try {
        const data = await API.getCities();
        // Cities are already rendered in HTML, but we can update dynamically if needed
        console.log('Cities loaded:', data.locations);
    } catch (error) {
        console.error('Failed to load cities:', error);
    }
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
        this.style.borderColor = 'var(--whiskey)';
        this.style.background = 'rgba(198, 142, 63, 0.1)';
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

/**
 * Community Page - Load Posts and Handle Interactions
 */
async function initCommunity() {
    const feedContainer = document.querySelector('.community-feed, .feed');
    if (!feedContainer) return;

    let currentPage = 1;
    const postsPerPage = 10;

    // Load initial posts
    await loadPosts();

    // Load more button
    const loadMoreBtn = document.querySelector('.load-more-btn, [data-load-more]');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', async () => {
            currentPage++;
            await loadPosts(true);
        });
    }

    async function loadPosts(append = false) {
        try {
            const data = await API.getPosts(currentPage, postsPerPage);

            if (!append) {
                // Clear existing posts except pinned template
                const existingPosts = feedContainer.querySelectorAll('.post:not(.post--template)');
                existingPosts.forEach(p => p.remove());
            }

            data.posts.forEach(post => {
                const postEl = createPostElement(post);
                feedContainer.appendChild(postEl);
            });

            // Hide load more if no more posts
            if (loadMoreBtn && !data.pagination.hasMore) {
                loadMoreBtn.style.display = 'none';
            }

        } catch (error) {
            console.error('Failed to load posts:', error);
        }
    }

    function createPostElement(post) {
        const article = document.createElement('article');
        article.className = 'post card';
        if (post.isPinned) article.classList.add('post--pinned');
        article.dataset.postId = post.id;

        let mediaHtml = '';
        if (post.mediaUrl) {
            mediaHtml = `<div class="post__media"><img src="${post.mediaUrl}" alt="Post media"></div>`;
        }

        let pollHtml = '';
        if (post.poll) {
            const optionsHtml = post.poll.options.map((opt, idx) => {
                const percentage = post.poll.totalVotes > 0
                    ? Math.round((opt.votes / post.poll.totalVotes) * 100)
                    : 0;
                return `
                    <div class="poll__option" data-poll-id="${post.poll.id}" data-option="${idx}">
                        <span class="poll__text">${opt.text}</span>
                        <div class="poll__bar" style="width: ${percentage}%"></div>
                        <span class="poll__percent">${percentage}%</span>
                    </div>
                `;
            }).join('');

            pollHtml = `
                <div class="post__poll">
                    ${optionsHtml}
                    <div class="poll__total">${post.poll.totalVotes.toLocaleString()} votes</div>
                </div>
            `;
        }

        const tagsHtml = post.tags.map(tag => `<span class="tag">#${tag}</span>`).join(' ');

        article.innerHTML = `
            <div class="post__header">
                <div class="post__avatar">${post.avatar}</div>
                <div class="post__meta">
                    <strong class="post__author">${post.author}</strong>
                    <span class="post__time">${post.timestamp}</span>
                </div>
                ${post.isPinned ? '<span class="post__pinned-badge">PINNED</span>' : ''}
            </div>
            <div class="post__content">
                <p>${post.content}</p>
                ${mediaHtml}
                ${pollHtml}
            </div>
            <div class="post__tags">${tagsHtml}</div>
            <div class="post__actions">
                <button class="post__action post__action--like" data-post-id="${post.id}">
                    <span>♡</span> ${post.likes}
                </button>
                <button class="post__action post__action--comment" data-post-id="${post.id}">
                    <span>💬</span> ${post.comments}
                </button>
                <button class="post__action post__action--share">
                    <span>↗</span> Share
                </button>
            </div>
        `;

        // Add event listeners
        const likeBtn = article.querySelector('.post__action--like');
        likeBtn.addEventListener('click', () => handleLike(post.id, likeBtn));

        const pollOptions = article.querySelectorAll('.poll__option');
        pollOptions.forEach(opt => {
            opt.addEventListener('click', () => handlePollVote(opt));
        });

        return article;
    }

    async function handleLike(postId, btn) {
        try {
            const data = await API.likePost(postId);
            const icon = data.liked ? '♥' : '♡';
            btn.innerHTML = `<span>${icon}</span> ${data.likes}`;
            btn.style.color = data.liked ? 'var(--alert-red)' : '';
        } catch (error) {
            console.error('Failed to like post:', error);
        }
    }

    async function handlePollVote(optionEl) {
        const pollId = optionEl.dataset.pollId;
        const optionIndex = parseInt(optionEl.dataset.option);

        try {
            const data = await API.voteOnPoll(pollId, optionIndex);

            // Update all options in this poll
            const pollContainer = optionEl.parentElement;
            const options = pollContainer.querySelectorAll('.poll__option');

            data.options.forEach((opt, idx) => {
                const optEl = options[idx];
                if (optEl) {
                    optEl.querySelector('.poll__bar').style.width = `${opt.percentage}%`;
                    optEl.querySelector('.poll__percent').textContent = `${opt.percentage}%`;
                }
            });

            pollContainer.querySelector('.poll__total').textContent = `${data.totalVotes.toLocaleString()} votes`;
            showNotification('Vote recorded!', 'success');

        } catch (error) {
            showNotification(error.message || 'Failed to vote', 'error');
        }
    }

    // Load trending tags
    loadTrendingTags();
}

async function loadTrendingTags() {
    const tagsContainer = document.querySelector('.trending-tags, .sidebar__tags');
    if (!tagsContainer) return;

    try {
        const data = await API.getTrendingTags();
        // Tags are already rendered in HTML, but we can update dynamically
        console.log('Trending tags:', data.trending);
    } catch (error) {
        console.error('Failed to load tags:', error);
    }
}

/**
 * Shop Page - Load Products and Handle Cart
 */
async function initShop() {
    const productsGrid = document.querySelector('.products-grid, .shop__grid');
    if (!productsGrid) return;

    // Simple cart state
    window.cart = JSON.parse(localStorage.getItem('wxc_cart') || '[]');

    // Load products
    await loadProducts();

    // Category filter tabs
    const categoryTabs = document.querySelectorAll('.shop__tab, [data-category]');
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', async () => {
            categoryTabs.forEach(t => t.classList.remove('active', 'tab--active'));
            tab.classList.add('active', 'tab--active');
            const category = tab.dataset.category || tab.dataset.tab || 'all';
            await loadProducts(category);
        });
    });

    async function loadProducts(category = 'all') {
        try {
            const data = await API.getProducts(category);
            renderProducts(data.products);
        } catch (error) {
            console.error('Failed to load products:', error);
        }
    }

    function renderProducts(products) {
        productsGrid.innerHTML = '';

        products.forEach(product => {
            const productEl = document.createElement('div');
            productEl.className = 'product card';
            productEl.dataset.productId = product.id;
            productEl.dataset.category = product.category;

            productEl.innerHTML = `
                <div class="product__image">
                    <div class="product__placeholder">${product.title.charAt(0)}</div>
                </div>
                <div class="product__info">
                    <h3 class="product__title">${product.title}</h3>
                    <p class="product__price">$${product.price.toFixed(2)}</p>
                    <span class="product__tag">${product.category}</span>
                </div>
                <button class="btn btn--sm product__add-to-cart" data-product='${JSON.stringify(product)}'>
                    ${product.inStock ? 'Add to Cart' : 'Out of Stock'}
                </button>
            `;

            if (!product.inStock) {
                productEl.querySelector('.product__add-to-cart').disabled = true;
            }

            productEl.querySelector('.product__add-to-cart').addEventListener('click', (e) => {
                e.stopPropagation();
                addToCart(product);
            });

            productsGrid.appendChild(productEl);
        });
    }

    function addToCart(product) {
        const existing = window.cart.find(item => item.productId === product.id);
        if (existing) {
            existing.quantity++;
        } else {
            window.cart.push({
                productId: product.id,
                title: product.title,
                price: product.price,
                quantity: 1
            });
        }
        localStorage.setItem('wxc_cart', JSON.stringify(window.cart));
        updateCartBadge();
        showNotification(`${product.title} added to cart!`, 'success');
    }

    function updateCartBadge() {
        const badge = document.querySelector('.cart-badge');
        if (badge) {
            const totalItems = window.cart.reduce((sum, item) => sum + item.quantity, 0);
            badge.textContent = totalItems;
            badge.style.display = totalItems > 0 ? 'block' : 'none';
        }
    }

    updateCartBadge();
}

/**
 * Portfolio Page - Load Gallery Items
 */
async function initPortfolio() {
    const galleryGrid = document.querySelector('.gallery, .portfolio__grid');
    if (!galleryGrid) return;

    // Load portfolio items
    await loadPortfolio();

    // Category tabs
    const portfolioTabs = document.querySelectorAll('.portfolio__tab, [data-portfolio-category]');
    portfolioTabs.forEach(tab => {
        tab.addEventListener('click', async () => {
            portfolioTabs.forEach(t => t.classList.remove('active', 'tab--active'));
            tab.classList.add('active', 'tab--active');
            const category = tab.dataset.portfolioCategory || tab.dataset.tab || 'all';
            await loadPortfolio(category);
        });
    });

    async function loadPortfolio(category = null) {
        try {
            const data = await API.getPortfolioItems(category);
            renderPortfolio(data.items);
        } catch (error) {
            console.error('Failed to load portfolio:', error);
        }
    }

    function renderPortfolio(items) {
        // Keep existing structure if items already exist
        const existingItems = galleryGrid.querySelectorAll('.gallery__item');
        if (existingItems.length > 0) {
            console.log('Portfolio items loaded:', items.length);
            return; // Use existing HTML items for now
        }

        galleryGrid.innerHTML = '';

        items.forEach(item => {
            const itemEl = document.createElement('div');
            itemEl.className = 'gallery__item';
            itemEl.dataset.itemId = item.id;
            itemEl.dataset.category = item.category;

            const tagsHtml = item.tags.map(tag => `<span class="gallery__tag">${tag}</span>`).join('');

            itemEl.innerHTML = `
                <div class="gallery__image">
                    ${item.imageUrl ? `<img src="${item.imageUrl}" alt="${item.title}">` : `<div class="gallery__placeholder">${item.title.charAt(0)}</div>`}
                </div>
                <div class="gallery__overlay">
                    <h4 class="gallery__title">${item.title}</h4>
                    <div class="gallery__tags">${tagsHtml}</div>
                </div>
            `;

            itemEl.addEventListener('click', () => openLightbox(item));
            galleryGrid.appendChild(itemEl);
        });
    }

    function openLightbox(item) {
        // Simple lightbox implementation
        const lightbox = document.createElement('div');
        lightbox.className = 'lightbox';
        lightbox.innerHTML = `
            <div class="lightbox__backdrop"></div>
            <div class="lightbox__content">
                <button class="lightbox__close">&times;</button>
                ${item.videoUrl
                    ? `<iframe src="${item.videoUrl}" frameborder="0" allowfullscreen></iframe>`
                    : `<img src="${item.imageUrl || ''}" alt="${item.title}">`
                }
                <div class="lightbox__info">
                    <h3>${item.title}</h3>
                    <p>${item.description || ''}</p>
                </div>
            </div>
        `;

        lightbox.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
        `;

        lightbox.querySelector('.lightbox__backdrop').style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
        `;

        lightbox.querySelector('.lightbox__content').style.cssText = `
            position: relative;
            max-width: 90%;
            max-height: 90%;
            text-align: center;
        `;

        lightbox.querySelector('.lightbox__close').style.cssText = `
            position: absolute;
            top: -40px;
            right: 0;
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
        `;

        const closeBtn = lightbox.querySelector('.lightbox__close');
        const backdrop = lightbox.querySelector('.lightbox__backdrop');

        closeBtn.addEventListener('click', () => lightbox.remove());
        backdrop.addEventListener('click', () => lightbox.remove());
        document.addEventListener('keydown', function escHandler(e) {
            if (e.key === 'Escape') {
                lightbox.remove();
                document.removeEventListener('keydown', escHandler);
            }
        });

        document.body.appendChild(lightbox);
    }
}

console.log('WXXXC - Country Bred. Fully Loaded.');
