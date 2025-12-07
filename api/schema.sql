-- WYATT XXX COLE Admin System Database Schema
-- Run this to set up the admin panel tables

-- Admin trusted sessions (for IP/device verification)
CREATE TABLE IF NOT EXISTS admin_trusted_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    location_country VARCHAR(4),
    location_city VARCHAR(64),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_used_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username_ip (admin_username, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin login challenges (6-digit verification codes)
CREATE TABLE IF NOT EXISTS admin_login_challenges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(64) NOT NULL,
    challenge_id VARCHAR(64) NOT NULL UNIQUE,
    code_hash VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    attempts INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    INDEX idx_challenge (challenge_id),
    INDEX idx_username (admin_username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin login logs (audit trail)
CREATE TABLE IF NOT EXISTS admin_login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(64),
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success TINYINT(1) NOT NULL,
    reason VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (admin_username),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admin actions log (for audit trail)
CREATE TABLE IF NOT EXISTS admin_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_username VARCHAR(64) NOT NULL,
    action_type VARCHAR(64) NOT NULL,
    target_type VARCHAR(64),
    target_id VARCHAR(64),
    metadata JSON,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (admin_username),
    INDEX idx_action (action_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Site settings (key-value store for editable content)
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(128) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'html', 'json', 'boolean', 'number') DEFAULT 'text',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email templates
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(64) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (template_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Custom content requests (booking system)
CREATE TABLE IF NOT EXISTS custom_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) NOT NULL,
    email VARCHAR(255) NOT NULL,
    request_type ENUM('custom_video', 'custom_photos', 'live_session', 'sexting', 'other') NOT NULL,
    details TEXT,
    preferences TEXT,
    limits TEXT,
    duration VARCHAR(64),
    contact_method VARCHAR(64),
    status ENUM('new', 'reviewed', 'approved', 'in_progress', 'delivered', 'cancelled') DEFAULT 'new',
    price DECIMAL(10,2),
    admin_notes TEXT,
    delivery_link TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Email subscribers
CREATE TABLE IF NOT EXISTS email_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    source VARCHAR(64) DEFAULT 'footer',
    subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Click analytics (platform link tracking)
CREATE TABLE IF NOT EXISTS click_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(64) NOT NULL,
    source_page VARCHAR(128),
    ip_address VARCHAR(45),
    user_agent TEXT,
    clicked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_platform (platform),
    INDEX idx_clicked (clicked_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- CUSTOM ANALYTICS SYSTEM
-- =====================================================

-- Visitor sessions (tracks unique visitors)
CREATE TABLE IF NOT EXISTS analytics_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    visitor_id VARCHAR(64) NOT NULL,
    ip_address VARCHAR(45),
    country VARCHAR(2),
    city VARCHAR(100),
    region VARCHAR(100),
    device_type ENUM('desktop', 'mobile', 'tablet') DEFAULT 'desktop',
    browser VARCHAR(50),
    browser_version VARCHAR(20),
    os VARCHAR(50),
    os_version VARCHAR(20),
    screen_width INT,
    screen_height INT,
    language VARCHAR(10),
    timezone VARCHAR(50),
    referrer TEXT,
    referrer_domain VARCHAR(255),
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    utm_term VARCHAR(100),
    utm_content VARCHAR(100),
    landing_page VARCHAR(500),
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    page_views INT DEFAULT 0,
    events INT DEFAULT 0,
    duration_seconds INT DEFAULT 0,
    is_bounce TINYINT(1) DEFAULT 1,
    INDEX idx_visitor (visitor_id),
    INDEX idx_started (started_at),
    INDEX idx_country (country),
    INDEX idx_device (device_type),
    INDEX idx_referrer (referrer_domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Page views (every page visit)
CREATE TABLE IF NOT EXISTS analytics_pageviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    visitor_id VARCHAR(64) NOT NULL,
    page_url VARCHAR(500) NOT NULL,
    page_path VARCHAR(255) NOT NULL,
    page_title VARCHAR(255),
    previous_page VARCHAR(500),
    time_on_page INT DEFAULT 0,
    scroll_depth INT DEFAULT 0,
    viewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_visitor (visitor_id),
    INDEX idx_page (page_path),
    INDEX idx_viewed (viewed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events (clicks, interactions, conversions)
CREATE TABLE IF NOT EXISTS analytics_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL,
    visitor_id VARCHAR(64) NOT NULL,
    event_category VARCHAR(50) NOT NULL,
    event_action VARCHAR(50) NOT NULL,
    event_label VARCHAR(255),
    event_value DECIMAL(10,2),
    page_url VARCHAR(500),
    element_id VARCHAR(100),
    element_class VARCHAR(255),
    element_text VARCHAR(255),
    metadata JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_category (event_category),
    INDEX idx_action (event_action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Real-time active visitors (cleaned up periodically)
CREATE TABLE IF NOT EXISTS analytics_realtime (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(64) NOT NULL UNIQUE,
    visitor_id VARCHAR(64) NOT NULL,
    current_page VARCHAR(500),
    page_title VARCHAR(255),
    country VARCHAR(2),
    city VARCHAR(100),
    device_type VARCHAR(20),
    referrer_domain VARCHAR(255),
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Daily aggregated stats (for fast dashboard loading)
CREATE TABLE IF NOT EXISTS analytics_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    date DATE NOT NULL UNIQUE,
    visitors INT DEFAULT 0,
    sessions INT DEFAULT 0,
    pageviews INT DEFAULT 0,
    avg_session_duration INT DEFAULT 0,
    bounce_rate DECIMAL(5,2) DEFAULT 0,
    new_visitors INT DEFAULT 0,
    returning_visitors INT DEFAULT 0,
    top_pages JSON,
    top_referrers JSON,
    countries JSON,
    devices JSON,
    browsers JSON,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Conversion goals
CREATE TABLE IF NOT EXISTS analytics_goals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goal_name VARCHAR(100) NOT NULL,
    goal_type ENUM('pageview', 'event', 'duration', 'pages_per_session') NOT NULL,
    goal_value VARCHAR(255),
    goal_operator ENUM('equals', 'contains', 'starts_with', 'greater_than', 'less_than') DEFAULT 'equals',
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Goal completions
CREATE TABLE IF NOT EXISTS analytics_conversions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    goal_id INT NOT NULL,
    session_id VARCHAR(64) NOT NULL,
    visitor_id VARCHAR(64) NOT NULL,
    conversion_value DECIMAL(10,2),
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_goal (goal_id),
    INDEX idx_completed (completed_at),
    FOREIGN KEY (goal_id) REFERENCES analytics_goals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
