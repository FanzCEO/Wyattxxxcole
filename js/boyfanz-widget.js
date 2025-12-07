/**
 * BoyFanz Integration Widget
 * Simple subscribe widget for boyfanz.com/wyatt_xxx_cole
 */

class BoyFanzWidget {
    constructor(options = {}) {
        this.apiEndpoint = options.apiEndpoint || '/api/integrations/BoyFanzSync.php';
        this.containerId = options.containerId || 'boyfanz-widget';
        this.refreshInterval = options.refreshInterval || 300000; // 5 minutes
        this.data = null;
        this.config = null;
        this.refreshTimer = null;
    }

    /**
     * Initialize the widget
     */
    async init() {
        await this.fetchData();
        await this.fetchConfig();
        this.render();
    }

    /**
     * Fetch data from API
     */
    async fetchData() {
        try {
            const response = await fetch(`${this.apiEndpoint}?action=widget`);
            const result = await response.json();

            if (result.success && result.data) {
                this.data = result.data;
            }
        } catch (error) {
            console.error('BoyFanz Widget: Failed to fetch data', error);
        }
    }

    /**
     * Fetch widget config from localStorage or API
     */
    async fetchConfig() {
        // Load config from localStorage (set via admin panel)
        const savedConfig = localStorage.getItem('boyfanz_widget_config');
        if (savedConfig) {
            this.config = JSON.parse(savedConfig);
        } else {
            // Default config
            this.config = {
                profileImage: 'images/logo.png',
                logoImage: '',
                buttonText: 'Subscribe Now',
                profileUrl: 'https://boyfanz.com/wyatt_xxx_cole'
            };
        }
    }

    /**
     * Render the widget
     */
    render() {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        const profileUrl = this.config?.profileUrl || this.data?.cta?.url || 'https://boyfanz.com/wyatt_xxx_cole';
        const profileImage = this.config?.profileImage || this.data?.avatar || 'images/logo.png';
        const logoImage = this.config?.logoImage || '';
        const buttonText = this.config?.buttonText || 'Subscribe Now';

        container.innerHTML = `
            <a href="${profileUrl}" target="_blank" rel="noopener" class="boyfanz-widget">
                <div class="boyfanz-widget__image-container">
                    <img src="${profileImage}" alt="Subscribe on BoyFanz" class="boyfanz-widget__image">
                    ${logoImage ? `<img src="${logoImage}" alt="BoyFanz" class="boyfanz-widget__logo">` : ''}
                </div>
                <button class="boyfanz-widget__cta">${buttonText}</button>
            </a>
        `;

        this.injectStyles();
    }

    /**
     * Inject widget styles
     */
    injectStyles() {
        if (document.getElementById('boyfanz-widget-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'boyfanz-widget-styles';
        styles.textContent = `
            .boyfanz-widget {
                display: block;
                text-decoration: none;
                max-width: 320px;
                transition: transform 0.3s ease;
            }

            .boyfanz-widget:hover {
                transform: translateY(-5px);
            }

            .boyfanz-widget__image-container {
                position: relative;
                border-radius: 16px;
                overflow: hidden;
                border: 2px solid var(--whiskey, #C68E3F);
                margin-bottom: 1rem;
            }

            .boyfanz-widget__image {
                width: 100%;
                height: auto;
                display: block;
                aspect-ratio: 1;
                object-fit: cover;
            }

            .boyfanz-widget__logo {
                position: absolute;
                bottom: 10px;
                right: 10px;
                width: 80px;
                height: auto;
                filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));
            }

            .boyfanz-widget__cta {
                display: block;
                width: 100%;
                padding: 1rem 1.5rem;
                background: linear-gradient(135deg, var(--whiskey, #C68E3F), var(--rust, #A44A2A));
                border: none;
                border-radius: 8px;
                color: var(--bg-primary, #0D0B09);
                font-family: 'Source Sans Pro', sans-serif;
                font-size: 1rem;
                font-weight: 700;
                text-align: center;
                text-transform: uppercase;
                letter-spacing: 2px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .boyfanz-widget:hover .boyfanz-widget__cta {
                box-shadow: 0 10px 30px rgba(198, 142, 63, 0.4);
            }
        `;
        document.head.appendChild(styles);
    }

    /**
     * Format number for display
     */
    formatNumber(num) {
        if (!num) return '0';
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M';
        }
        if (num >= 1000) {
            return (num / 1000).toFixed(1) + 'K';
        }
        return num.toString();
    }

    /**
     * Format date for display
     */
    formatDate(dateStr) {
        if (!dateStr) return 'Never';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + ' min ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + ' hr ago';
        return date.toLocaleDateString();
    }

    /**
     * Start auto-refresh
     */
    startAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }

        this.refreshTimer = setInterval(async () => {
            await this.fetchData();
            this.render();
        }, this.refreshInterval);
    }

    /**
     * Stop auto-refresh
     */
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
    }

    /**
     * Manual refresh
     */
    async refresh() {
        await this.fetchData();
        this.render();
    }
}

// Auto-initialize if container exists
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('boyfanz-widget');
    if (container) {
        const widget = new BoyFanzWidget();
        widget.init();
        window.boyFanzWidget = widget;
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BoyFanzWidget;
}
