/**
 * Wyatt World - Social Platform JavaScript
 * Handles navigation, interactions, and dynamic content
 */

(function() {
    'use strict';

    // DOM Elements
    const navItems = document.querySelectorAll('.world__nav-item');
    const sections = document.querySelectorAll('.world__section');
    const feedTabs = document.querySelectorAll('.world__tab');
    const messageTabs = document.querySelectorAll('.world__messages-tab');
    const chatItems = document.querySelectorAll('.world__chat-item');
    const chatWindow = document.getElementById('chatWindow');
    const chatBack = document.getElementById('chatBack');
    const signupModal = document.getElementById('signupModal');
    const tipModal = document.getElementById('tipModal');
    const joinBtn = document.getElementById('joinBtn');
    const loginBtn = document.getElementById('loginBtn');
    const postComposer = document.getElementById('postComposer');
    const postInput = document.getElementById('postInput');
    const submitPost = document.getElementById('submitPost');
    const newPostBtn = document.getElementById('newPostBtn');

    // State
    let currentSection = 'feed';
    let currentFeed = 'for-you';
    let isLoggedIn = false;
    let membershipTier = 'free'; // free, vip, inner-circle

    /**
     * Initialize the application
     */
    function init() {
        setupNavigation();
        setupFeedTabs();
        setupMessageTabs();
        setupChatSystem();
        setupModals();
        setupPostActions();
        setupPodcastPlayer();
        setupPolls();
        checkHashNavigation();
    }

    /**
     * Setup main navigation
     */
    function setupNavigation() {
        navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const sectionId = item.dataset.section;
                if (sectionId) {
                    switchSection(sectionId);
                }
            });
        });
    }

    /**
     * Switch between sections
     */
    function switchSection(sectionId) {
        // Update nav items
        navItems.forEach(item => {
            item.classList.toggle('active', item.dataset.section === sectionId);
        });

        // Update sections
        sections.forEach(section => {
            section.classList.toggle('active', section.id === `section-${sectionId}`);
        });

        currentSection = sectionId;
        window.location.hash = sectionId;
    }

    /**
     * Check URL hash for navigation
     */
    function checkHashNavigation() {
        const hash = window.location.hash.slice(1);
        if (hash && document.getElementById(`section-${hash}`)) {
            switchSection(hash);
        }
    }

    /**
     * Setup feed tabs
     */
    function setupFeedTabs() {
        feedTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                feedTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                currentFeed = tab.dataset.feed;
                loadFeed(currentFeed);
            });
        });
    }

    /**
     * Load feed content
     */
    function loadFeed(feedType) {
        // Placeholder for API call
        console.log('Loading feed:', feedType);
        // In production, this would fetch posts from the API
    }

    /**
     * Setup message tabs
     */
    function setupMessageTabs() {
        messageTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                messageTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                filterChats(tab.dataset.tab);
            });
        });
    }

    /**
     * Filter chat list
     */
    function filterChats(type) {
        // Placeholder for filtering logic
        console.log('Filtering chats:', type);
    }

    /**
     * Setup chat system
     */
    function setupChatSystem() {
        chatItems.forEach(item => {
            if (!item.classList.contains('world__chat-item--locked')) {
                item.addEventListener('click', () => {
                    openChat(item.dataset.chat);
                });
            } else {
                item.addEventListener('click', () => {
                    showUpgradePrompt();
                });
            }
        });

        if (chatBack) {
            chatBack.addEventListener('click', closeChat);
        }

        // Send message
        const sendBtn = document.getElementById('sendMessage');
        const messageInput = document.getElementById('messageInput');

        if (sendBtn && messageInput) {
            sendBtn.addEventListener('click', () => sendMessage(messageInput.value));
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    sendMessage(messageInput.value);
                }
            });
        }
    }

    /**
     * Open chat window
     */
    function openChat(chatId) {
        if (!chatWindow) return;

        chatWindow.classList.remove('hidden');
        // Load chat messages
        loadChatMessages(chatId);
    }

    /**
     * Close chat window
     */
    function closeChat() {
        if (chatWindow) {
            chatWindow.classList.add('hidden');
        }
    }

    /**
     * Load chat messages
     */
    function loadChatMessages(chatId) {
        // Placeholder for API call
        console.log('Loading chat:', chatId);
    }

    /**
     * Send message
     */
    function sendMessage(message) {
        if (!message.trim()) return;

        // Placeholder for API call
        console.log('Sending message:', message);

        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.value = '';
        }
    }

    /**
     * Setup modals
     */
    function setupModals() {
        // Join button
        if (joinBtn) {
            joinBtn.addEventListener('click', () => showModal(signupModal));
        }

        // Login button
        if (loginBtn) {
            loginBtn.addEventListener('click', () => showModal(signupModal));
        }

        // Close buttons
        document.querySelectorAll('.world__modal-close').forEach(btn => {
            btn.addEventListener('click', () => {
                btn.closest('.world__modal').classList.add('hidden');
            });
        });

        // Overlay click
        document.querySelectorAll('.world__modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', () => {
                overlay.closest('.world__modal').classList.add('hidden');
            });
        });

        // Tip buttons
        document.querySelectorAll('.world__action-btn--tip').forEach(btn => {
            btn.addEventListener('click', () => showModal(tipModal));
        });

        // Tip amount selection
        document.querySelectorAll('.world__tip-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.world__tip-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });

        // Upgrade buttons
        document.querySelectorAll('.world__upgrade-btn, .world__vault-unlock, .world__unlock-all').forEach(btn => {
            btn.addEventListener('click', showUpgradePrompt);
        });
    }

    /**
     * Show modal
     */
    function showModal(modal) {
        if (modal) {
            modal.classList.remove('hidden');
        }
    }

    /**
     * Show upgrade prompt
     */
    function showUpgradePrompt() {
        // In production, this would show a membership upgrade modal
        alert('Upgrade to VIP to unlock this feature! $19.99/month');
    }

    /**
     * Setup post actions
     */
    function setupPostActions() {
        // Like buttons
        document.querySelectorAll('.world__action-btn').forEach(btn => {
            if (btn.querySelector('path[d*="20.84 4.61"]')) { // Heart icon path
                btn.addEventListener('click', () => toggleLike(btn));
            }
        });

        // Post submission
        if (submitPost && postInput) {
            submitPost.addEventListener('click', () => {
                const content = postInput.value.trim();
                if (content) {
                    createPost(content);
                }
            });
        }

        // New post button
        if (newPostBtn) {
            newPostBtn.addEventListener('click', () => {
                if (postInput) {
                    postInput.focus();
                }
            });
        }
    }

    /**
     * Toggle like on post
     */
    function toggleLike(btn) {
        btn.classList.toggle('world__action-btn--liked');

        const countEl = btn.querySelector('span');
        if (countEl) {
            let count = parseFloat(countEl.textContent.replace('k', '')) * (countEl.textContent.includes('k') ? 1000 : 1);

            if (btn.classList.contains('world__action-btn--liked')) {
                count++;
            } else {
                count--;
            }

            if (count >= 1000) {
                countEl.textContent = (count / 1000).toFixed(1) + 'k';
            } else {
                countEl.textContent = count;
            }
        }
    }

    /**
     * Create new post
     */
    function createPost(content) {
        // Placeholder for API call
        console.log('Creating post:', content);

        if (postInput) {
            postInput.value = '';
        }

        // Show success feedback
        alert('Post created successfully!');
    }

    /**
     * Setup podcast player
     */
    function setupPodcastPlayer() {
        const playBtns = document.querySelectorAll('.world__podcast-play-btn, .world__podcast-ctrl--main');
        const progressBar = document.querySelector('.world__podcast-progress-bar');
        const progressFill = document.querySelector('.world__podcast-progress-fill');
        const timeDisplay = document.querySelectorAll('.world__podcast-time');

        let isPlaying = false;
        let currentTime = 0;
        const duration = 47 * 60 + 32; // 47:32 in seconds

        playBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                isPlaying = !isPlaying;
                updatePlayButton(btn, isPlaying);
            });
        });

        if (progressBar) {
            progressBar.addEventListener('click', (e) => {
                const rect = progressBar.getBoundingClientRect();
                const percent = (e.clientX - rect.left) / rect.width;
                currentTime = percent * duration;
                updateProgress(progressFill, timeDisplay, currentTime, duration);
            });
        }

        // Episode play buttons
        document.querySelectorAll('.world__episode-play').forEach(btn => {
            if (!btn.classList.contains('world__episode-play--locked')) {
                btn.addEventListener('click', () => {
                    // Simulate playing episode
                    console.log('Playing episode');
                });
            } else {
                btn.addEventListener('click', showUpgradePrompt);
            }
        });

        // Podcast filter
        document.querySelectorAll('.world__podcast-filter-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.world__podcast-filter-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
            });
        });
    }

    /**
     * Update play button state
     */
    function updatePlayButton(btn, playing) {
        const svg = btn.querySelector('svg');
        if (svg) {
            if (playing) {
                svg.innerHTML = '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>';
            } else {
                svg.innerHTML = '<path d="M8 5v14l11-7z"/>';
            }
        }
    }

    /**
     * Update progress display
     */
    function updateProgress(fill, timeDisplays, current, total) {
        if (fill) {
            fill.style.width = `${(current / total) * 100}%`;
        }

        if (timeDisplays && timeDisplays[0]) {
            timeDisplays[0].textContent = formatTime(current);
        }
    }

    /**
     * Format time as MM:SS
     */
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Setup poll interactions
     */
    function setupPolls() {
        document.querySelectorAll('.world__poll-option').forEach(option => {
            option.addEventListener('click', () => {
                if (!isLoggedIn) {
                    showModal(signupModal);
                    return;
                }

                // Mark as voted
                option.classList.add('voted');
                console.log('Voted for:', option.querySelector('.world__poll-text').textContent);
            });
        });
    }

    /**
     * Handle window resize
     */
    function handleResize() {
        // Responsive adjustments
        const isMobile = window.innerWidth < 768;

        if (isMobile && chatWindow && !chatWindow.classList.contains('hidden')) {
            // Make chat fullscreen on mobile
            chatWindow.style.right = '0';
            chatWindow.style.width = '100%';
        }
    }

    // Event listeners
    window.addEventListener('hashchange', checkHashNavigation);
    window.addEventListener('resize', handleResize);

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
