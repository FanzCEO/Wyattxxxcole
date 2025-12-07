/**
 * Admin Authentication Handler
 * Shows admin panel link when logged in
 */

(function() {
    const AUTH_KEY = 'wyatt_admin_auth';
    const AUTH_EXPIRY = 24 * 60 * 60 * 1000; // 24 hours

    // Check if admin is logged in
    function isAdminLoggedIn() {
        const auth = localStorage.getItem(AUTH_KEY);
        if (!auth) return false;

        try {
            const data = JSON.parse(auth);
            if (data.expires && data.expires > Date.now()) {
                return true;
            }
            // Expired, clear it
            localStorage.removeItem(AUTH_KEY);
            return false;
        } catch {
            return false;
        }
    }

    // Set admin logged in
    function setAdminLoggedIn(token) {
        localStorage.setItem(AUTH_KEY, JSON.stringify({
            token: token,
            expires: Date.now() + AUTH_EXPIRY,
            loggedIn: true
        }));
    }

    // Logout admin
    function adminLogout() {
        localStorage.removeItem(AUTH_KEY);
        window.location.reload();
    }

    // Create admin nav button
    function createAdminButton() {
        if (!isAdminLoggedIn()) return;

        // Create the admin button
        const adminBtn = document.createElement('a');
        adminBtn.href = 'admin/';
        adminBtn.className = 'nav__admin-btn';
        adminBtn.innerHTML = `
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
            </svg>
            <span>Admin</span>
        `;

        // Add styles
        const style = document.createElement('style');
        style.textContent = `
            .nav__admin-btn {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 1rem;
                background: linear-gradient(135deg, var(--whiskey, #C68E3F), var(--rust, #A44A2A));
                color: var(--bg-primary, #0D0B09);
                border-radius: 6px;
                text-decoration: none;
                font-weight: 600;
                font-size: 0.875rem;
                transition: all 0.3s ease;
                margin-left: 1rem;
            }
            .nav__admin-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 20px rgba(198, 142, 63, 0.4);
            }
            .nav__admin-btn svg {
                flex-shrink: 0;
            }
            @media (max-width: 768px) {
                .nav__admin-btn {
                    position: fixed;
                    bottom: 1rem;
                    right: 1rem;
                    z-index: 1000;
                    padding: 0.75rem 1.25rem;
                    border-radius: 50px;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                }
            }
        `;
        document.head.appendChild(style);

        // Insert into nav
        const navMenu = document.querySelector('.nav__menu');
        if (navMenu) {
            navMenu.parentNode.insertBefore(adminBtn, navMenu.nextSibling);
        } else {
            // Fallback: add to nav container
            const navContainer = document.querySelector('.nav__container');
            if (navContainer) {
                navContainer.appendChild(adminBtn);
            }
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', createAdminButton);

    // Expose functions globally
    window.WyattAdmin = {
        isLoggedIn: isAdminLoggedIn,
        login: setAdminLoggedIn,
        logout: adminLogout
    };
})();
