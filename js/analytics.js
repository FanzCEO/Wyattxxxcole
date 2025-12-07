/**
 * WYATT XXX COLE - Custom Analytics Tracker
 * Lightweight, privacy-respecting analytics
 */
(function() {
    'use strict';

    const ANALYTICS_ENDPOINT = '/api/analytics.php';
    const SESSION_TIMEOUT = 30 * 60 * 1000; // 30 minutes
    const HEARTBEAT_INTERVAL = 15000; // 15 seconds

    // Storage keys
    const VISITOR_KEY = 'wyatt_visitor_id';
    const SESSION_KEY = 'wyatt_session_id';
    const SESSION_START_KEY = 'wyatt_session_start';
    const LAST_ACTIVITY_KEY = 'wyatt_last_activity';
    const PAGE_ENTER_KEY = 'wyatt_page_enter';

    // State
    let visitorId = null;
    let sessionId = null;
    let currentPageView = null;
    let scrollDepth = 0;
    let heartbeatTimer = null;

    // ==================== UTILITIES ====================

    function generateId() {
        return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
            const r = Math.random() * 16 | 0;
            const v = c === 'x' ? r : (r & 0x3 | 0x8);
            return v.toString(16);
        });
    }

    function getStorage(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }

    function setStorage(key, value) {
        try {
            localStorage.setItem(key, value);
        } catch (e) {}
    }

    function getDeviceType() {
        const ua = navigator.userAgent;
        if (/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i.test(ua)) {
            return 'tablet';
        }
        if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated|(hpw|web)OS|Opera M(obi|ini)/.test(ua)) {
            return 'mobile';
        }
        return 'desktop';
    }

    function getBrowser() {
        const ua = navigator.userAgent;
        let browser = 'Unknown';
        let version = '';

        if (ua.indexOf('Firefox') > -1) {
            browser = 'Firefox';
            version = ua.match(/Firefox\/(\d+)/)?.[1] || '';
        } else if (ua.indexOf('Opera') > -1 || ua.indexOf('OPR') > -1) {
            browser = 'Opera';
            version = ua.match(/(?:Opera|OPR)\/(\d+)/)?.[1] || '';
        } else if (ua.indexOf('Edge') > -1 || ua.indexOf('Edg') > -1) {
            browser = 'Edge';
            version = ua.match(/(?:Edge|Edg)\/(\d+)/)?.[1] || '';
        } else if (ua.indexOf('Chrome') > -1) {
            browser = 'Chrome';
            version = ua.match(/Chrome\/(\d+)/)?.[1] || '';
        } else if (ua.indexOf('Safari') > -1) {
            browser = 'Safari';
            version = ua.match(/Version\/(\d+)/)?.[1] || '';
        } else if (ua.indexOf('MSIE') > -1 || ua.indexOf('Trident') > -1) {
            browser = 'IE';
            version = ua.match(/(?:MSIE |rv:)(\d+)/)?.[1] || '';
        }

        return { browser, version };
    }

    function getOS() {
        const ua = navigator.userAgent;
        let os = 'Unknown';
        let version = '';

        if (ua.indexOf('Windows') > -1) {
            os = 'Windows';
            if (ua.indexOf('Windows NT 10') > -1) version = '10';
            else if (ua.indexOf('Windows NT 6.3') > -1) version = '8.1';
            else if (ua.indexOf('Windows NT 6.2') > -1) version = '8';
            else if (ua.indexOf('Windows NT 6.1') > -1) version = '7';
        } else if (ua.indexOf('Mac') > -1) {
            os = 'macOS';
            version = ua.match(/Mac OS X (\d+[._]\d+)/)?.[1]?.replace('_', '.') || '';
        } else if (ua.indexOf('Android') > -1) {
            os = 'Android';
            version = ua.match(/Android (\d+\.?\d*)/)?.[1] || '';
        } else if (ua.indexOf('iOS') > -1 || ua.indexOf('iPhone') > -1 || ua.indexOf('iPad') > -1) {
            os = 'iOS';
            version = ua.match(/OS (\d+[._]\d+)/)?.[1]?.replace('_', '.') || '';
        } else if (ua.indexOf('Linux') > -1) {
            os = 'Linux';
        }

        return { os, version };
    }

    function getUTMParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            source: params.get('utm_source') || '',
            medium: params.get('utm_medium') || '',
            campaign: params.get('utm_campaign') || '',
            term: params.get('utm_term') || '',
            content: params.get('utm_content') || ''
        };
    }

    function getReferrerDomain() {
        if (!document.referrer) return '';
        try {
            const url = new URL(document.referrer);
            // Exclude self-referrals
            if (url.hostname === window.location.hostname) return '';
            return url.hostname;
        } catch (e) {
            return '';
        }
    }

    // ==================== API CALLS ====================

    function sendBeacon(action, data) {
        const payload = JSON.stringify({
            action,
            ...data,
            visitorId,
            sessionId,
            timestamp: Date.now()
        });

        // Use sendBeacon for reliability (works even on page close)
        if (navigator.sendBeacon) {
            navigator.sendBeacon(ANALYTICS_ENDPOINT, payload);
        } else {
            // Fallback to fetch
            fetch(ANALYTICS_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: payload,
                keepalive: true
            }).catch(() => {});
        }
    }

    async function sendAsync(action, data) {
        try {
            const response = await fetch(ANALYTICS_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action,
                    ...data,
                    visitorId,
                    sessionId,
                    timestamp: Date.now()
                })
            });
            return await response.json();
        } catch (e) {
            console.warn('Analytics error:', e);
            return null;
        }
    }

    // ==================== SESSION MANAGEMENT ====================

    function initSession() {
        // Get or create visitor ID (persistent)
        visitorId = getStorage(VISITOR_KEY);
        if (!visitorId) {
            visitorId = generateId();
            setStorage(VISITOR_KEY, visitorId);
        }

        // Check for existing session
        const lastActivity = parseInt(getStorage(LAST_ACTIVITY_KEY) || '0');
        const now = Date.now();

        if (now - lastActivity > SESSION_TIMEOUT) {
            // Session expired, create new one
            sessionId = generateId();
            setStorage(SESSION_KEY, sessionId);
            setStorage(SESSION_START_KEY, now.toString());
            startNewSession();
        } else {
            // Continue existing session
            sessionId = getStorage(SESSION_KEY) || generateId();
            setStorage(SESSION_KEY, sessionId);
        }

        setStorage(LAST_ACTIVITY_KEY, now.toString());
    }

    function startNewSession() {
        const browserInfo = getBrowser();
        const osInfo = getOS();
        const utm = getUTMParams();

        sendAsync('session_start', {
            deviceType: getDeviceType(),
            browser: browserInfo.browser,
            browserVersion: browserInfo.version,
            os: osInfo.os,
            osVersion: osInfo.version,
            screenWidth: window.screen.width,
            screenHeight: window.screen.height,
            language: navigator.language,
            timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
            referrer: document.referrer,
            referrerDomain: getReferrerDomain(),
            utmSource: utm.source,
            utmMedium: utm.medium,
            utmCampaign: utm.campaign,
            utmTerm: utm.term,
            utmContent: utm.content,
            landingPage: window.location.href
        });
    }

    // ==================== PAGE VIEW TRACKING ====================

    function trackPageView() {
        const previousPage = getStorage('wyatt_previous_page') || '';

        currentPageView = {
            url: window.location.href,
            path: window.location.pathname,
            title: document.title,
            previousPage,
            enterTime: Date.now()
        };

        setStorage(PAGE_ENTER_KEY, currentPageView.enterTime.toString());
        setStorage('wyatt_previous_page', window.location.href);

        sendAsync('pageview', {
            pageUrl: currentPageView.url,
            pagePath: currentPageView.path,
            pageTitle: currentPageView.title,
            previousPage
        });

        // Update realtime
        sendBeacon('realtime_update', {
            currentPage: window.location.href,
            pageTitle: document.title
        });
    }

    function trackPageExit() {
        if (!currentPageView) return;

        const timeOnPage = Math.round((Date.now() - currentPageView.enterTime) / 1000);

        sendBeacon('page_exit', {
            pageUrl: currentPageView.url,
            pagePath: currentPageView.path,
            timeOnPage,
            scrollDepth
        });
    }

    // ==================== SCROLL TRACKING ====================

    function updateScrollDepth() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const currentDepth = docHeight > 0 ? Math.round((scrollTop / docHeight) * 100) : 100;

        if (currentDepth > scrollDepth) {
            scrollDepth = currentDepth;
        }
    }

    // ==================== EVENT TRACKING ====================

    window.wyattTrack = function(category, action, label, value) {
        sendAsync('event', {
            eventCategory: category,
            eventAction: action,
            eventLabel: label || '',
            eventValue: value || null,
            pageUrl: window.location.href
        });
    };

    // Auto-track clicks on links
    function trackClicks() {
        document.addEventListener('click', function(e) {
            const target = e.target.closest('a, button, [data-track]');
            if (!target) return;

            const isExternal = target.tagName === 'A' && target.hostname !== window.location.hostname;
            const trackData = target.dataset.track;

            if (isExternal || trackData) {
                const category = trackData || (isExternal ? 'outbound' : 'click');
                const action = isExternal ? 'click' : (target.tagName.toLowerCase());
                const label = target.href || target.innerText?.substring(0, 100) || target.id;

                sendBeacon('event', {
                    eventCategory: category,
                    eventAction: action,
                    eventLabel: label,
                    pageUrl: window.location.href,
                    elementId: target.id || '',
                    elementClass: target.className || '',
                    elementText: target.innerText?.substring(0, 100) || ''
                });
            }
        });
    }

    // ==================== HEARTBEAT ====================

    function startHeartbeat() {
        heartbeatTimer = setInterval(function() {
            setStorage(LAST_ACTIVITY_KEY, Date.now().toString());

            sendBeacon('heartbeat', {
                currentPage: window.location.href
            });
        }, HEARTBEAT_INTERVAL);
    }

    function stopHeartbeat() {
        if (heartbeatTimer) {
            clearInterval(heartbeatTimer);
            heartbeatTimer = null;
        }
    }

    // ==================== INITIALIZATION ====================

    function init() {
        // Don't track if DNT is enabled (respect privacy)
        if (navigator.doNotTrack === '1') {
            console.log('Analytics: DNT enabled, not tracking');
            return;
        }

        // Don't track bots
        if (/bot|crawler|spider|crawling/i.test(navigator.userAgent)) {
            return;
        }

        initSession();
        trackPageView();
        trackClicks();
        startHeartbeat();

        // Scroll tracking
        let scrollTimer;
        window.addEventListener('scroll', function() {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(updateScrollDepth, 100);
        }, { passive: true });

        // Track when leaving
        window.addEventListener('beforeunload', trackPageExit);
        window.addEventListener('pagehide', trackPageExit);

        // Handle visibility changes
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                stopHeartbeat();
            } else {
                setStorage(LAST_ACTIVITY_KEY, Date.now().toString());
                startHeartbeat();
            }
        });

        // SPA support - track route changes
        let lastPath = window.location.pathname;
        const observer = new MutationObserver(function() {
            if (window.location.pathname !== lastPath) {
                trackPageExit();
                lastPath = window.location.pathname;
                scrollDepth = 0;
                trackPageView();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Expose for manual tracking
    window.WyattAnalytics = {
        track: window.wyattTrack,
        getVisitorId: () => visitorId,
        getSessionId: () => sessionId
    };

})();
