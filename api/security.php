<?php
/**
 * SECURITY CONFIGURATION
 * Centralized security functions for all API endpoints
 * Includes: CORS, CSRF, Rate Limiting, Security Headers
 */

// Start session for CSRF tokens (only if not already started)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==================== CONFIGURATION ====================

// Allowed origins for CORS (add your domains here)
define('ALLOWED_ORIGINS', [
    'https://wyattxxxcole.com',
    'https://www.wyattxxxcole.com',
    'http://localhost',
    'http://localhost:3000',
    'http://localhost:8080',
    'http://127.0.0.1'
]);

// Rate limiting configuration
define('RATE_LIMIT_WINDOW', 60); // seconds
define('RATE_LIMIT_MAX_REQUESTS', 100); // max requests per window
define('RATE_LIMIT_FILE', __DIR__ . '/.rate_limits.json');

// CSRF token lifetime (24 hours)
define('CSRF_TOKEN_LIFETIME', 86400);

// ==================== CORS HANDLING ====================

function handleCORS() {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    // Check if origin is allowed
    if (in_array($origin, ALLOWED_ORIGINS)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        // For development/same-origin requests without Origin header
        // Only allow if no origin (same-origin request)
        if (empty($origin)) {
            header('Access-Control-Allow-Origin: *');
        }
        // If unknown origin, don't set CORS header (browser will block)
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token, X-Requested-With');
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Max-Age: 86400'); // 24 hours

    // Handle preflight
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit();
    }
}

// ==================== SECURITY HEADERS ====================

function setSecurityHeaders() {
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');

    // Clickjacking protection
    header('X-Frame-Options: DENY');

    // XSS protection (legacy browsers)
    header('X-XSS-Protection: 1; mode=block');

    // Referrer policy
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Content Security Policy for API
    header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

    // Strict Transport Security (HTTPS only)
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }

    // Permissions Policy
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

// ==================== CSRF PROTECTION ====================

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) ||
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token = null) {
    // Get token from header or parameter
    if ($token === null) {
        $headers = getallheaders();
        $token = $headers['X-CSRF-Token'] ?? $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    }

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    // Check token expiration
    if (empty($_SESSION['csrf_token_time']) || (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_LIFETIME) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function requireCSRF() {
    // Only require CSRF for state-changing methods
    if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE', 'PATCH'])) {
        // Skip CSRF for API token-authenticated requests
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
            return true; // Token-based auth, skip CSRF
        }

        if (!validateCSRFToken()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Invalid or missing CSRF token']);
            exit();
        }
    }
    return true;
}

// ==================== RATE LIMITING ====================

function getRateLimitData() {
    if (!file_exists(RATE_LIMIT_FILE)) {
        return [];
    }
    $data = json_decode(file_get_contents(RATE_LIMIT_FILE), true);
    return is_array($data) ? $data : [];
}

function saveRateLimitData($data) {
    file_put_contents(RATE_LIMIT_FILE, json_encode($data));
}

function checkRateLimit($identifier = null) {
    // Use IP address as identifier if not provided
    if ($identifier === null) {
        $identifier = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        // Handle comma-separated IPs from proxies
        if (strpos($identifier, ',') !== false) {
            $identifier = trim(explode(',', $identifier)[0]);
        }
    }

    $data = getRateLimitData();
    $currentTime = time();
    $windowStart = $currentTime - RATE_LIMIT_WINDOW;

    // Clean old entries
    foreach ($data as $ip => $requests) {
        $data[$ip] = array_filter($requests, function($time) use ($windowStart) {
            return $time > $windowStart;
        });
        if (empty($data[$ip])) {
            unset($data[$ip]);
        }
    }

    // Check current IP
    $requests = $data[$identifier] ?? [];
    $requestCount = count($requests);

    if ($requestCount >= RATE_LIMIT_MAX_REQUESTS) {
        // Rate limited
        $retryAfter = RATE_LIMIT_WINDOW - ($currentTime - min($requests));
        header('Retry-After: ' . $retryAfter);
        header('X-RateLimit-Limit: ' . RATE_LIMIT_MAX_REQUESTS);
        header('X-RateLimit-Remaining: 0');
        header('X-RateLimit-Reset: ' . ($currentTime + $retryAfter));

        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Rate limit exceeded. Please try again later.',
            'retry_after' => $retryAfter
        ]);
        exit();
    }

    // Add current request
    $data[$identifier][] = $currentTime;
    saveRateLimitData($data);

    // Set rate limit headers
    header('X-RateLimit-Limit: ' . RATE_LIMIT_MAX_REQUESTS);
    header('X-RateLimit-Remaining: ' . (RATE_LIMIT_MAX_REQUESTS - $requestCount - 1));
    header('X-RateLimit-Reset: ' . ($currentTime + RATE_LIMIT_WINDOW));

    return true;
}

// ==================== AUTHENTICATION ====================
// Note: These functions may be overridden by individual API files with custom auth logic

if (!function_exists('getStoredToken')) {
    function getStoredToken() {
        $tokenFile = __DIR__ . '/.admin_token';
        if (file_exists($tokenFile)) {
            return trim(file_get_contents($tokenFile));
        }
        return null;
    }
}

if (!function_exists('checkAuth')) {
    function checkAuth($required = true) {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? $_GET['token'] ?? '';
        $token = str_replace('Bearer ', '', $token);

        $storedToken = getStoredToken();

        // Secure token validation
        if (!empty($token) && !empty($storedToken) && hash_equals($storedToken, $token)) {
            return true;
        }

        if ($required) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            exit();
        }

        return false;
    }
}

// ==================== INPUT SANITIZATION ====================

if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map('sanitizeInput', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('sanitizeFilename')) {
    function sanitizeFilename($filename) {
        // Remove path traversal attempts
        $filename = basename($filename);
        // Remove dangerous characters
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
        return $filename;
    }
}

// ==================== INITIALIZATION ====================

if (!function_exists('initSecurity')) {
    function initSecurity($options = []) {
        $defaults = [
            'cors' => true,
            'headers' => true,
            'rateLimit' => true,
            'csrf' => false, // Disabled by default for API endpoints using token auth
        ];

        $options = array_merge($defaults, $options);

        // Set JSON content type
        header('Content-Type: application/json');

        // Apply security measures
        if ($options['cors']) {
            handleCORS();
        }

        if ($options['headers']) {
            setSecurityHeaders();
        }

        if ($options['rateLimit']) {
            checkRateLimit();
        }

        if ($options['csrf']) {
            requireCSRF();
        }
    }
}

// ==================== HELPER FUNCTIONS ====================

if (!function_exists('respond')) {
    function respond($data, $code = 200) {
        http_response_code($code);
        echo json_encode($data);
        exit();
    }
}

if (!function_exists('handleError')) {
    function handleError($message, $code = 400) {
        respond(['success' => false, 'error' => $message], $code);
    }
}

// Get CSRF token for client
function getCSRFTokenResponse() {
    return [
        'success' => true,
        'csrf_token' => generateCSRFToken()
    ];
}
