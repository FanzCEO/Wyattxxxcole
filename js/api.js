/**
 * WYATT XXX COLE - API Client
 * Handles all communication with the backend API
 */

const API = {
    // Base URL - change this for production
    baseUrl: 'http://localhost:3000/api',

    // Helper to get visitor ID for voting/likes
    getVisitorId() {
        let visitorId = localStorage.getItem('wxc_visitor_id');
        if (!visitorId) {
            visitorId = 'v_' + Math.random().toString(36).substring(2) + Date.now().toString(36);
            localStorage.setItem('wxc_visitor_id', visitorId);
        }
        return visitorId;
    },

    // Generic fetch wrapper
    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            'X-Visitor-Id': this.getVisitorId(),
            ...options.headers
        };

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // ========================================
    // BOOKING & CONTACT
    // ========================================

    async submitStudioInquiry(formData) {
        return this.request('/booking/studio-inquiry', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
    },

    async submitProfessionalBooking(formData) {
        return this.request('/booking/professional', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
    },

    async submitCreatorCollab(formData) {
        return this.request('/contact/creator-collab', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
    },

    async submitContactForm(formData) {
        return this.request('/contact/general', {
            method: 'POST',
            body: JSON.stringify(formData)
        });
    },

    // ========================================
    // SHOP
    // ========================================

    async getProducts(category = null) {
        const query = category && category !== 'all' ? `?category=${category}` : '';
        return this.request(`/shop/products${query}`);
    },

    async getProduct(slug) {
        return this.request(`/shop/products/${slug}`);
    },

    async getCategories() {
        return this.request('/shop/categories');
    },

    async createOrder(orderData) {
        return this.request('/shop/orders', {
            method: 'POST',
            body: JSON.stringify(orderData)
        });
    },

    async getOrderStatus(orderNumber) {
        return this.request(`/shop/orders/${orderNumber}`);
    },

    async subscribeNewsletter(email, source = 'shop') {
        return this.request('/shop/newsletter', {
            method: 'POST',
            body: JSON.stringify({ email, source })
        });
    },

    async unsubscribeNewsletter(email) {
        return this.request('/shop/newsletter/unsubscribe', {
            method: 'POST',
            body: JSON.stringify({ email })
        });
    },

    // ========================================
    // SCHEDULE
    // ========================================

    async getCalendar(year, month) {
        return this.request(`/schedule/calendar/${year}/${month}`);
    },

    async getCities() {
        return this.request('/schedule/cities');
    },

    async signUpForNotification(email, city) {
        return this.request('/schedule/notify', {
            method: 'POST',
            body: JSON.stringify({ email, city })
        });
    },

    async getUpcomingDates() {
        return this.request('/schedule/upcoming');
    },

    // ========================================
    // COMMUNITY
    // ========================================

    async getPosts(page = 1, limit = 10) {
        return this.request(`/community/posts?page=${page}&limit=${limit}`);
    },

    async getPost(id) {
        return this.request(`/community/posts/${id}`);
    },

    async likePost(postId) {
        return this.request(`/community/posts/${postId}/like`, {
            method: 'POST'
        });
    },

    async commentOnPost(postId, authorName, content) {
        return this.request(`/community/posts/${postId}/comment`, {
            method: 'POST',
            body: JSON.stringify({ authorName, content })
        });
    },

    async voteOnPoll(pollId, optionIndex) {
        return this.request(`/community/polls/${pollId}/vote`, {
            method: 'POST',
            body: JSON.stringify({ optionIndex })
        });
    },

    async getTrendingTags() {
        return this.request('/community/tags');
    },

    // ========================================
    // PORTFOLIO
    // ========================================

    async getPortfolioItems(category = null) {
        const query = category && category !== 'all' ? `?category=${category}` : '';
        return this.request(`/portfolio/items${query}`);
    },

    async getPortfolioItem(id) {
        return this.request(`/portfolio/items/${id}`);
    },

    async getPortfolioCategories() {
        return this.request('/portfolio/categories');
    },

    async getPortfolioTags() {
        return this.request('/portfolio/tags');
    },

    // ========================================
    // SITE SETTINGS
    // ========================================

    async getSiteSettings() {
        return this.request('/settings/public');
    },

    // Apply background images from settings
    async applyBackgrounds() {
        try {
            const data = await this.getSiteSettings();
            const settings = data.settings;

            // Apply hero background if set
            if (settings.hero_background_url) {
                const heroBackground = document.querySelector('.hero__background');
                if (heroBackground) {
                    heroBackground.style.backgroundImage = `url('${settings.hero_background_url}')`;
                }
            }

            // Apply world background if set
            if (settings.world_background_url) {
                const worldElement = document.querySelector('.world');
                if (worldElement) {
                    worldElement.style.backgroundImage = `url('${settings.world_background_url}')`;
                    worldElement.style.backgroundSize = 'cover';
                    worldElement.style.backgroundPosition = 'center';
                    worldElement.style.backgroundAttachment = 'fixed';
                }
            }

            return settings;
        } catch (error) {
            console.error('Failed to load site settings:', error);
            return null;
        }
    }
};

// Auto-apply backgrounds when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    API.applyBackgrounds();
});

// Export for use in other scripts
window.API = API;
