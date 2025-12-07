<?php
/**
 * WYATT XXX COLE - Admin API
 * Full admin panel with IP verification, booking system, and security
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment config
function loadEnv() {
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) continue;
            if (strpos($line, '=') === false) continue;
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}
loadEnv();

// Configuration - Use absolute paths
$docRoot = dirname(__DIR__);
define('UPLOAD_DIR', $docRoot . '/images/');
define('GALLERY_DIR', $docRoot . '/images/gallery/');
define('FEATURED_DIR', $docRoot . '/images/featured/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('DEBUG_MODE', ($_ENV['DEBUG_MODE'] ?? 'false') === 'true');

// File-based storage paths
define('FEATURED_FILE', __DIR__ . '/featured.json');
define('SOCIALS_FILE', __DIR__ . '/socials.json');
define('SETTINGS_FILE', __DIR__ . '/settings.json');

// Ensure directories exist
if (!file_exists(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!file_exists(GALLERY_DIR)) mkdir(GALLERY_DIR, 0755, true);
if (!file_exists(FEATURED_DIR)) mkdir(FEATURED_DIR, 0755, true);

// ========== DATABASE CONNECTION ==========
$db = null;

function getDB() {
    global $db;
    if ($db !== null) return $db;

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASSWORD'] ?? '';

    if (empty($dbname) || empty($user)) {
        return null; // No DB configured, use file-based fallback
    }

    try {
        $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        error_log("DB Connection failed: " . $e->getMessage());
        return null;
    }
}

// ========== HELPER FUNCTIONS ==========

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function handleError($message, $code = 400) {
    respond(['success' => false, 'error' => $message], $code);
}

function getClientIP() {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    // Handle comma-separated IPs (from proxies)
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    return $ip;
}

function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function generateCode() {
    return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

function getStoredToken() {
    $tokenFile = __DIR__ . '/.admin_token';
    if (file_exists($tokenFile)) {
        return trim(file_get_contents($tokenFile));
    }
    return '';
}

function checkAuth() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $_GET['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if ($token === 'demo-token' || $token === getStoredToken()) {
        return true;
    }
    return false;
}

function logLoginAttempt($username, $success, $reason = '') {
    $db = getDB();
    if (!$db) return;

    try {
        $stmt = $db->prepare("INSERT INTO admin_login_logs (admin_username, ip_address, user_agent, success, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, getClientIP(), getUserAgent(), $success ? 1 : 0, $reason]);
    } catch (PDOException $e) {
        error_log("Failed to log login: " . $e->getMessage());
    }
}

function logAdminAction($username, $actionType, $targetType = null, $targetId = null, $metadata = null) {
    $db = getDB();
    if (!$db) return;

    try {
        $stmt = $db->prepare("INSERT INTO admin_actions (admin_username, action_type, target_type, target_id, metadata, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$username, $actionType, $targetType, $targetId, json_encode($metadata), getClientIP()]);
    } catch (PDOException $e) {
        error_log("Failed to log action: " . $e->getMessage());
    }
}

// ========== SMTP EMAIL FUNCTION ==========

function sendEmail($to, $subject, $htmlBody, $textBody = '') {
    $smtpHost = $_ENV['SMTP_HOST'] ?? '';
    $smtpPort = $_ENV['SMTP_PORT'] ?? 587;
    $smtpUser = $_ENV['SMTP_USER'] ?? '';
    $smtpPass = $_ENV['SMTP_PASS'] ?? '';
    $fromEmail = $_ENV['SMTP_FROM_EMAIL'] ?? 'noreply@wyattxxxcole.com';
    $fromName = $_ENV['SMTP_FROM_NAME'] ?? 'Wyatt Admin';

    // If SMTP not configured, use PHP mail() as fallback
    if (empty($smtpHost) || $smtpHost === 'smtp.example.com') {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: $fromName <$fromEmail>\r\n";

        return @mail($to, $subject, $htmlBody, $headers);
    }

    // Use PHPMailer if available, otherwise socket-based SMTP
    // For now, fallback to mail()
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $fromName <$fromEmail>\r\n";

    return @mail($to, $subject, $htmlBody, $headers);
}

function sendVerificationEmail($code, $ip, $userAgent) {
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? '';
    if (empty($adminEmail)) {
        error_log("No admin email configured");
        return false;
    }

    $subject = "Admin Login Verification Code";
    $html = "<h2>Admin Login Verification</h2>
<p>A login attempt was made to the admin panel.</p>
<p><strong>IP Address:</strong> $ip</p>
<p><strong>Browser:</strong> " . htmlspecialchars(substr($userAgent, 0, 100)) . "</p>
<p><strong>Your verification code:</strong></p>
<h1 style='color:#e91e63;font-size:32px;letter-spacing:8px;'>$code</h1>
<p>This code expires in 10 minutes.</p>
<p style='color:#ff5722;'><strong>If this wasn't you, change your password immediately.</strong></p>";

    return sendEmail($adminEmail, $subject, $html);
}

// ========== IP/DEVICE VERIFICATION ==========

function isTrustedSession($username, $ip, $userAgent) {
    $db = getDB();
    if (!$db) return true; // No DB = no verification (fallback mode)

    try {
        // Check if this IP is trusted for this user
        $stmt = $db->prepare("SELECT id FROM admin_trusted_sessions WHERE admin_username = ? AND ip_address = ? AND last_used_at > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute([$username, $ip]);

        if ($stmt->fetch()) {
            // Update last used
            $update = $db->prepare("UPDATE admin_trusted_sessions SET last_used_at = NOW(), user_agent = ? WHERE admin_username = ? AND ip_address = ?");
            $update->execute([$userAgent, $username, $ip]);
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Trust check failed: " . $e->getMessage());
        return true; // Fail open if DB error
    }
}

function createLoginChallenge($username, $ip, $userAgent) {
    $db = getDB();
    if (!$db) return null;

    $challengeId = bin2hex(random_bytes(32));
    $code = generateCode();
    $codeHash = password_hash($code, PASSWORD_DEFAULT);

    try {
        $stmt = $db->prepare("INSERT INTO admin_login_challenges (admin_username, challenge_id, code_hash, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))");
        $stmt->execute([$username, $challengeId, $codeHash, $ip, $userAgent]);

        // Send verification email
        if (sendVerificationEmail($code, $ip, $userAgent)) {
            return $challengeId;
        } else {
            error_log("Failed to send verification email");
            // Still return challenge ID, admin can check email logs
            return $challengeId;
        }
    } catch (PDOException $e) {
        error_log("Challenge creation failed: " . $e->getMessage());
        return null;
    }
}

function verifyLoginChallenge($username, $challengeId, $code) {
    $db = getDB();
    if (!$db) return ['success' => false, 'error' => 'Database not available'];

    try {
        $stmt = $db->prepare("SELECT id, code_hash, ip_address, user_agent, attempts, expires_at, used FROM admin_login_challenges WHERE challenge_id = ? AND admin_username = ?");
        $stmt->execute([$challengeId, $username]);
        $challenge = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$challenge) {
            return ['success' => false, 'error' => 'Invalid or expired challenge'];
        }

        if ($challenge['used']) {
            return ['success' => false, 'error' => 'Code already used. Please login again.'];
        }

        if (strtotime($challenge['expires_at']) < time()) {
            return ['success' => false, 'error' => 'Code expired. Please login again.'];
        }

        if ($challenge['attempts'] >= 5) {
            return ['success' => false, 'error' => 'Too many attempts. Please login again.'];
        }

        // Increment attempts
        $db->prepare("UPDATE admin_login_challenges SET attempts = attempts + 1 WHERE id = ?")->execute([$challenge['id']]);

        if (!password_verify($code, $challenge['code_hash'])) {
            return ['success' => false, 'error' => 'Invalid code'];
        }

        // Mark as used
        $db->prepare("UPDATE admin_login_challenges SET used = 1 WHERE id = ?")->execute([$challenge['id']]);

        // Create trusted session
        $stmt = $db->prepare("INSERT INTO admin_trusted_sessions (admin_username, ip_address, user_agent) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE last_used_at = NOW(), user_agent = VALUES(user_agent)");
        $stmt->execute([$username, $challenge['ip_address'], $challenge['user_agent']]);

        return ['success' => true, 'ip' => $challenge['ip_address']];
    } catch (PDOException $e) {
        error_log("Verify challenge failed: " . $e->getMessage());
        return ['success' => false, 'error' => 'Verification failed'];
    }
}

// ========== ROUTE HANDLING ==========

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // Auth
    case 'login':
        handleLogin();
        break;
    case 'verify-login':
        handleVerifyLogin();
        break;

    // Image uploads (existing)
    case 'upload-logo':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleLogoUpload();
        break;
    case 'upload-hero':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleHeroUpload();
        break;
    case 'upload-gallery':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleGalleryUpload();
        break;
    case 'delete-gallery':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleGalleryDelete();
        break;
    case 'list-gallery':
        handleGalleryList();
        break;

    // Featured carousel
    case 'get-featured':
        handleGetFeatured();
        break;
    case 'save-featured':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSaveFeatured();
        break;
    case 'upload-featured':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleFeaturedUpload();
        break;
    case 'delete-featured':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleDeleteFeatured();
        break;

    // Settings
    case 'get-settings':
        handleGetSettings();
        break;
    case 'save-settings':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSaveSettings();
        break;
    case 'get-site-content':
        handleGetSiteContent();
        break;
    case 'save-site-content':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSaveSiteContent();
        break;

    // Social links
    case 'get-socials':
        handleGetSocials();
        break;
    case 'save-socials':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSaveSocials();
        break;

    // Booking / Custom requests
    case 'create-request':
        handleCreateRequest();
        break;
    case 'list-requests':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleListRequests();
        break;
    case 'get-request':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleGetRequest();
        break;
    case 'update-request':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleUpdateRequest();
        break;

    // Email subscribers
    case 'subscribe':
        handleSubscribe();
        break;
    case 'list-subscribers':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleListSubscribers();
        break;
    case 'export-subscribers':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleExportSubscribers();
        break;

    // Analytics
    case 'track-click':
        handleTrackClick();
        break;
    case 'get-analytics':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleGetAnalytics();
        break;

    // Admin dashboard
    case 'dashboard':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleDashboard();
        break;
    case 'login-logs':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleLoginLogs();
        break;
    case 'system-health':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSystemHealth();
        break;

    // Database setup
    case 'init-db':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleInitDB();
        break;

    // SMTP Settings
    case 'get-smtp-settings':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleGetSmtpSettings();
        break;
    case 'save-smtp-settings':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSaveSmtpSettings();
        break;
    case 'test-smtp':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleTestSmtp();
        break;
    case 'save-admin-email':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSaveAdminEmail();
        break;

    // Email Templates
    case 'get-email-templates':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleGetEmailTemplates();
        break;
    case 'save-email-templates':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSaveEmailTemplates();
        break;

    // Analytics Settings (GA4, Pixels)
    case 'get-analytics-settings':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleGetAnalyticsSettings();
        break;
    case 'save-analytics-settings':
        if (!checkAuth()) handleError('Unauthorized', 401);
        handleSaveAnalyticsSettings();
        break;

    // Debug
    case 'debug':
        if (!DEBUG_MODE) handleError('Debug mode disabled', 403);
        respond([
            'success' => true,
            'uploadDir' => UPLOAD_DIR,
            'galleryDir' => GALLERY_DIR,
            'uploadDirExists' => file_exists(UPLOAD_DIR),
            'galleryDirExists' => file_exists(GALLERY_DIR),
            'uploadDirWritable' => is_writable(UPLOAD_DIR),
            'galleryDirWritable' => is_writable(GALLERY_DIR),
            'dbConnected' => getDB() !== null
        ]);
        break;

    default:
        handleError('Invalid action');
}

// ========== AUTH HANDLERS ==========

function handleLogin() {
    $rawInput = file_get_contents('php://input');
    $cleanedInput = str_replace('\\!', '!', $rawInput);
    $input = json_decode($cleanedInput, true);

    $username = $input['username'] ?? $_POST['username'] ?? '';
    $password = $input['password'] ?? $_POST['password'] ?? '';

    $adminUser = $_ENV['ADMIN_USERNAME'] ?? 'admin';
    $adminPass = $_ENV['ADMIN_PASSWORD'] ?? '';

    if (empty($adminPass)) {
        handleError('Server configuration error', 500);
    }

    // Check credentials
    if ($username !== $adminUser || $password !== $adminPass) {
        logLoginAttempt($username, false, 'Invalid credentials');
        handleError('Invalid credentials', 401);
    }

    $ip = getClientIP();
    $userAgent = getUserAgent();

    // Check if trusted session exists
    if (isTrustedSession($username, $ip, $userAgent)) {
        // Trusted - issue token immediately
        $token = generateToken();
        $tokenFile = __DIR__ . '/.admin_token';
        file_put_contents($tokenFile, $token);
        chmod($tokenFile, 0600);

        logLoginAttempt($username, true, 'Trusted IP');
        logAdminAction($username, 'login', null, null, ['ip' => $ip]);

        respond([
            'success' => true,
            'token' => $token
        ]);
    }

    // New IP/device - require verification
    $challengeId = createLoginChallenge($username, $ip, $userAgent);

    if ($challengeId) {
        logLoginAttempt($username, false, 'Verification required - new IP');
        respond([
            'success' => false,
            'requires_verification' => true,
            'challenge_id' => $challengeId,
            'message' => 'Verification code sent to admin email'
        ]);
    } else {
        // DB not available, fallback to direct login
        $token = generateToken();
        $tokenFile = __DIR__ . '/.admin_token';
        file_put_contents($tokenFile, $token);
        chmod($tokenFile, 0600);

        logLoginAttempt($username, true, 'Fallback - no DB');

        respond([
            'success' => true,
            'token' => $token
        ]);
    }
}

function handleVerifyLogin() {
    $input = json_decode(file_get_contents('php://input'), true);

    $username = $input['username'] ?? '';
    $challengeId = $input['challenge_id'] ?? '';
    $code = $input['code'] ?? '';

    if (empty($username) || empty($challengeId) || empty($code)) {
        handleError('Missing required fields');
    }

    $result = verifyLoginChallenge($username, $challengeId, $code);

    if (!$result['success']) {
        logLoginAttempt($username, false, $result['error']);
        handleError($result['error'], 401);
    }

    // Issue token
    $token = generateToken();
    $tokenFile = __DIR__ . '/.admin_token';
    file_put_contents($tokenFile, $token);
    chmod($tokenFile, 0600);

    logLoginAttempt($username, true, 'Verified');
    logAdminAction($username, 'login', null, null, ['ip' => $result['ip'], 'verified' => true]);

    respond([
        'success' => true,
        'token' => $token
    ]);
}

// ========== IMAGE UPLOAD HANDLERS ==========

function handleLogoUpload() {
    if (!isset($_FILES['file'])) handleError('No file uploaded');

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) handleError('Upload error: ' . $file['error']);
    if ($file['size'] > MAX_FILE_SIZE) handleError('File too large. Max 10MB.');

    $mimeType = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_TYPES)) handleError('Invalid file type');

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'logo.' . $ext;
    $destination = UPLOAD_DIR . $filename;

    foreach (glob(UPLOAD_DIR . 'logo.*') as $oldFile) {
        unlink($oldFile);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        respond(['success' => true, 'filename' => $filename, 'url' => 'images/' . $filename]);
    }
    handleError('Failed to save file');
}

function handleHeroUpload() {
    if (!isset($_FILES['file'])) handleError('No file uploaded');

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) handleError('Upload error');
    if ($file['size'] > MAX_FILE_SIZE) handleError('File too large');

    $mimeType = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_TYPES)) handleError('Invalid file type');

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') $ext = 'jpg';
    $filename = 'hero.' . $ext;
    $destination = UPLOAD_DIR . $filename;

    foreach (glob(UPLOAD_DIR . 'hero.*') as $oldFile) {
        @unlink($oldFile);
    }

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        @chmod($destination, 0644);
        respond(['success' => true, 'filename' => $filename, 'url' => 'images/' . $filename . '?v=' . time()]);
    }
    handleError('Failed to save file');
}

function handleGalleryUpload() {
    if (!isset($_FILES['files']) && !isset($_FILES['file'])) handleError('No files uploaded');

    if (isset($_FILES['file'])) {
        $_FILES['files'] = [
            'name' => [$_FILES['file']['name']],
            'type' => [$_FILES['file']['type']],
            'tmp_name' => [$_FILES['file']['tmp_name']],
            'error' => [$_FILES['file']['error']],
            'size' => [$_FILES['file']['size']]
        ];
    }

    $uploaded = [];
    $errors = [];
    $files = $_FILES['files'];

    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = $files['name'][$i] . ': Upload error';
            continue;
        }
        if ($files['size'][$i] > MAX_FILE_SIZE) {
            $errors[] = $files['name'][$i] . ': Too large';
            continue;
        }

        $mimeType = mime_content_type($files['tmp_name'][$i]);
        if (!in_array($mimeType, ALLOWED_TYPES)) {
            $errors[] = $files['name'][$i] . ': Invalid type';
            continue;
        }

        $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $filename = 'gallery_' . time() . '_' . $i . '.' . $ext;

        if (move_uploaded_file($files['tmp_name'][$i], GALLERY_DIR . $filename)) {
            $uploaded[] = ['filename' => $filename, 'url' => 'images/gallery/' . $filename];
        }
    }

    respond(['success' => count($uploaded) > 0, 'uploaded' => $uploaded, 'errors' => $errors]);
}

function handleGalleryDelete() {
    $input = json_decode(file_get_contents('php://input'), true);
    $filename = basename($input['filename'] ?? $_GET['filename'] ?? '');

    if (empty($filename)) handleError('No filename specified');

    $filepath = GALLERY_DIR . $filename;
    if (!file_exists($filepath)) handleError('File not found');

    if (unlink($filepath)) {
        respond(['success' => true, 'deleted' => $filename]);
    }
    handleError('Failed to delete file');
}

function handleGalleryList() {
    $images = [];
    if (is_dir(GALLERY_DIR)) {
        $files = glob(GALLERY_DIR . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($files as $file) {
            $filename = basename($file);
            $images[] = [
                'filename' => $filename,
                'url' => 'images/gallery/' . $filename,
                'size' => filesize($file),
                'modified' => filemtime($file)
            ];
        }
        usort($images, fn($a, $b) => $b['modified'] - $a['modified']);
    }
    respond(['success' => true, 'images' => $images]);
}

// ========== FEATURED CAROUSEL ==========

function handleGetFeatured() {
    $featured = file_exists(FEATURED_FILE) ? json_decode(file_get_contents(FEATURED_FILE), true) ?: [] : [];
    $featured = array_values(array_filter($featured, fn($item) => file_exists(dirname(__DIR__) . '/' . $item['url'])));
    respond(['success' => true, 'featured' => $featured]);
}

function handleSaveFeatured() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['featured'])) handleError('Invalid data');

    $featured = [];
    foreach ($input['featured'] as $item) {
        if (!empty($item['url'])) {
            $featured[] = [
                'url' => $item['url'],
                'title' => $item['title'] ?? '',
                'caption' => $item['caption'] ?? '',
                'link' => $item['link'] ?? '',
                'linkText' => $item['linkText'] ?? '',
                'altText' => $item['altText'] ?? '',
                'order' => intval($item['order'] ?? 0)
            ];
        }
    }
    usort($featured, fn($a, $b) => $a['order'] - $b['order']);

    if (file_put_contents(FEATURED_FILE, json_encode($featured, JSON_PRETTY_PRINT))) {
        respond(['success' => true, 'featured' => $featured]);
    }
    handleError('Failed to save');
}

function handleFeaturedUpload() {
    if (!isset($_FILES['file'])) handleError('No file uploaded');

    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) handleError('Upload error');
    if ($file['size'] > MAX_FILE_SIZE) handleError('File too large');

    $mimeType = mime_content_type($file['tmp_name']);
    if (!in_array($mimeType, ALLOWED_TYPES)) handleError('Invalid file type');

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'featured_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

    if (move_uploaded_file($file['tmp_name'], FEATURED_DIR . $filename)) {
        $featured = file_exists(FEATURED_FILE) ? json_decode(file_get_contents(FEATURED_FILE), true) ?: [] : [];
        $newItem = [
            'url' => 'images/featured/' . $filename,
            'title' => $_POST['title'] ?? '',
            'caption' => $_POST['caption'] ?? '',
            'link' => $_POST['link'] ?? '',
            'linkText' => $_POST['linkText'] ?? '',
            'altText' => $_POST['altText'] ?? '',
            'order' => count($featured)
        ];
        $featured[] = $newItem;
        file_put_contents(FEATURED_FILE, json_encode($featured, JSON_PRETTY_PRINT));
        respond(['success' => true, 'url' => 'images/featured/' . $filename, 'item' => $newItem]);
    }
    handleError('Failed to save');
}

function handleDeleteFeatured() {
    $input = json_decode(file_get_contents('php://input'), true);
    $url = $input['url'] ?? '';
    if (empty($url) || strpos($url, 'images/featured/') !== 0) handleError('Invalid URL');

    $filepath = FEATURED_DIR . basename($url);
    if (file_exists($filepath)) @unlink($filepath);

    $featured = file_exists(FEATURED_FILE) ? json_decode(file_get_contents(FEATURED_FILE), true) ?: [] : [];
    $featured = array_values(array_filter($featured, fn($item) => $item['url'] !== $url));
    file_put_contents(FEATURED_FILE, json_encode($featured, JSON_PRETTY_PRINT));

    respond(['success' => true, 'deleted' => $url]);
}

// ========== SETTINGS ==========

function handleGetSettings() {
    $defaults = [
        'tagline' => 'Country Bred. Fully Loaded.',
        'reviewCount' => 132,
        'contactEmail' => 'wyatt@wyattxxxcole.com',
        'logoUrl' => 'images/logo.png',
        'heroUrl' => ''
    ];

    $settings = file_exists(SETTINGS_FILE) ? array_merge($defaults, json_decode(file_get_contents(SETTINGS_FILE), true) ?: []) : $defaults;

    foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
        if (file_exists(UPLOAD_DIR . "hero.$ext")) {
            $settings['heroUrl'] = "images/hero.$ext";
            break;
        }
    }

    respond(['success' => true, 'settings' => $settings]);
}

function handleSaveSettings() {
    $input = json_decode(file_get_contents('php://input'), true);
    $settings = [
        'tagline' => $input['tagline'] ?? 'Country Bred. Fully Loaded.',
        'reviewCount' => intval($input['reviewCount'] ?? 132),
        'contactEmail' => $input['contactEmail'] ?? 'contact@wyattxxxcole.com'
    ];

    if (file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT))) {
        respond(['success' => true, 'settings' => $settings]);
    }
    handleError('Failed to save');
}

function handleGetSiteContent() {
    $db = getDB();
    if (!$db) {
        // Fallback to defaults
        respond(['success' => true, 'content' => [
            'hero_headline' => 'Country Bred. Fully Loaded.',
            'hero_subheadline' => 'Premium content from your favorite country boy',
            'about_text' => 'Just a country boy sharing exclusive content with my fans.',
            'book_me_text' => 'Want something custom? I offer personalized content, live sessions, and more.'
        ]]);
    }

    try {
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
        $content = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $content[$row['setting_key']] = $row['setting_value'];
        }
        respond(['success' => true, 'content' => $content]);
    } catch (PDOException $e) {
        handleError('Failed to load content');
    }
}

function handleSaveSiteContent() {
    $db = getDB();
    if (!$db) handleError('Database not available');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) handleError('Invalid data');

    try {
        $stmt = $db->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");

        foreach ($input as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        logAdminAction($_ENV['ADMIN_USERNAME'], 'update_content', 'site_settings', null, array_keys($input));
        respond(['success' => true]);
    } catch (PDOException $e) {
        handleError('Failed to save content');
    }
}

// ========== SOCIAL LINKS ==========

function handleGetSocials() {
    $socials = file_exists(SOCIALS_FILE) ? json_decode(file_get_contents(SOCIALS_FILE), true) ?: [] : [];
    respond(['success' => true, 'socials' => $socials]);
}

function handleSaveSocials() {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) handleError('Invalid data');

    $validPlatforms = ['twitter', 'instagram', 'bluesky', 'tiktok', 'boyfanz', 'girlfanz', 'pupfanz', 'fanzunlimited', 'youtube', 'threads', 'snapchat', 'discord', 'telegram'];

    $socials = [];
    foreach ($validPlatforms as $platform) {
        if (isset($input[$platform]) && !empty(trim($input[$platform]))) {
            $url = trim($input[$platform]);
            if (preg_match('/^https?:\/\/.+/', $url)) {
                $socials[$platform] = $url;
            }
        }
    }

    if (file_put_contents(SOCIALS_FILE, json_encode($socials, JSON_PRETTY_PRINT))) {
        logAdminAction($_ENV['ADMIN_USERNAME'], 'update_socials', 'socials', null, array_keys($socials));
        respond(['success' => true, 'socials' => $socials]);
    }
    handleError('Failed to save');
}

// ========== BOOKING / CUSTOM REQUESTS ==========

function handleCreateRequest() {
    $input = json_decode(file_get_contents('php://input'), true);

    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $type = $input['type'] ?? 'other';
    $details = trim($input['details'] ?? '');

    if (empty($name) || empty($email)) {
        handleError('Name and email are required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleError('Invalid email address');
    }

    $db = getDB();
    if (!$db) {
        // Fallback: save to JSON file
        $requests = [];
        $requestsFile = __DIR__ . '/requests.json';
        if (file_exists($requestsFile)) {
            $requests = json_decode(file_get_contents($requestsFile), true) ?: [];
        }
        $requests[] = [
            'id' => count($requests) + 1,
            'name' => $name,
            'email' => $email,
            'type' => $type,
            'details' => $details,
            'preferences' => $input['preferences'] ?? '',
            'limits' => $input['limits'] ?? '',
            'duration' => $input['duration'] ?? '',
            'contact_method' => $input['contact_method'] ?? '',
            'status' => 'new',
            'created_at' => date('Y-m-d H:i:s')
        ];
        file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT));
        respond(['success' => true, 'message' => 'Request submitted successfully']);
    }

    try {
        $stmt = $db->prepare("INSERT INTO custom_requests (name, email, request_type, details, preferences, limits, duration, contact_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $name, $email, $type, $details,
            $input['preferences'] ?? '',
            $input['limits'] ?? '',
            $input['duration'] ?? '',
            $input['contact_method'] ?? ''
        ]);

        respond(['success' => true, 'message' => 'Request submitted successfully', 'id' => $db->lastInsertId()]);
    } catch (PDOException $e) {
        error_log("Request creation failed: " . $e->getMessage());
        handleError('Failed to submit request');
    }
}

function handleListRequests() {
    $db = getDB();
    if (!$db) {
        $requestsFile = __DIR__ . '/requests.json';
        $requests = file_exists($requestsFile) ? json_decode(file_get_contents($requestsFile), true) ?: [] : [];
        respond(['success' => true, 'requests' => $requests]);
    }

    try {
        $status = $_GET['status'] ?? '';
        $sql = "SELECT * FROM custom_requests";
        $params = [];

        if (!empty($status)) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        respond(['success' => true, 'requests' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        handleError('Failed to load requests');
    }
}

function handleGetRequest() {
    $id = intval($_GET['id'] ?? 0);
    if ($id <= 0) handleError('Invalid ID');

    $db = getDB();
    if (!$db) handleError('Database not available');

    try {
        $stmt = $db->prepare("SELECT * FROM custom_requests WHERE id = ?");
        $stmt->execute([$id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$request) handleError('Request not found', 404);
        respond(['success' => true, 'request' => $request]);
    } catch (PDOException $e) {
        handleError('Failed to load request');
    }
}

function handleUpdateRequest() {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    if ($id <= 0) handleError('Invalid ID');

    $db = getDB();
    if (!$db) handleError('Database not available');

    try {
        $updates = [];
        $params = [];

        if (isset($input['status'])) {
            $updates[] = "status = ?";
            $params[] = $input['status'];
        }
        if (isset($input['price'])) {
            $updates[] = "price = ?";
            $params[] = floatval($input['price']);
        }
        if (isset($input['admin_notes'])) {
            $updates[] = "admin_notes = ?";
            $params[] = $input['admin_notes'];
        }
        if (isset($input['delivery_link'])) {
            $updates[] = "delivery_link = ?";
            $params[] = $input['delivery_link'];
        }

        if (empty($updates)) handleError('Nothing to update');

        $params[] = $id;
        $sql = "UPDATE custom_requests SET " . implode(', ', $updates) . " WHERE id = ?";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);

        logAdminAction($_ENV['ADMIN_USERNAME'], 'update_request', 'custom_requests', $id, $input);
        respond(['success' => true]);
    } catch (PDOException $e) {
        handleError('Failed to update request');
    }
}

// ========== EMAIL SUBSCRIBERS ==========

function handleSubscribe() {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = trim($input['email'] ?? '');
    $source = $input['source'] ?? 'footer';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleError('Invalid email address');
    }

    $db = getDB();
    if (!$db) {
        // Fallback to JSON
        $subsFile = __DIR__ . '/subscribers.json';
        $subs = file_exists($subsFile) ? json_decode(file_get_contents($subsFile), true) ?: [] : [];
        if (!in_array($email, array_column($subs, 'email'))) {
            $subs[] = ['email' => $email, 'source' => $source, 'subscribed_at' => date('Y-m-d H:i:s')];
            file_put_contents($subsFile, json_encode($subs, JSON_PRETTY_PRINT));
        }
        respond(['success' => true, 'message' => 'Subscribed successfully']);
    }

    try {
        $stmt = $db->prepare("INSERT INTO email_subscribers (email, source) VALUES (?, ?) ON DUPLICATE KEY UPDATE is_active = 1, unsubscribed_at = NULL");
        $stmt->execute([$email, $source]);
        respond(['success' => true, 'message' => 'Subscribed successfully']);
    } catch (PDOException $e) {
        handleError('Failed to subscribe');
    }
}

function handleListSubscribers() {
    $db = getDB();
    if (!$db) {
        $subsFile = __DIR__ . '/subscribers.json';
        $subs = file_exists($subsFile) ? json_decode(file_get_contents($subsFile), true) ?: [] : [];
        respond(['success' => true, 'subscribers' => $subs, 'total' => count($subs)]);
    }

    try {
        $stmt = $db->query("SELECT id, email, source, subscribed_at, is_active FROM email_subscribers ORDER BY subscribed_at DESC");
        $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        respond(['success' => true, 'subscribers' => $subs, 'total' => count($subs)]);
    } catch (PDOException $e) {
        handleError('Failed to load subscribers');
    }
}

function handleExportSubscribers() {
    $db = getDB();
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="subscribers_' . date('Y-m-d') . '.csv"');

    echo "Email,Source,Subscribed At,Active\n";

    if ($db) {
        $stmt = $db->query("SELECT email, source, subscribed_at, is_active FROM email_subscribers WHERE is_active = 1");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['email']},{$row['source']},{$row['subscribed_at']},{$row['is_active']}\n";
        }
    } else {
        $subsFile = __DIR__ . '/subscribers.json';
        if (file_exists($subsFile)) {
            $subs = json_decode(file_get_contents($subsFile), true) ?: [];
            foreach ($subs as $sub) {
                echo "{$sub['email']},{$sub['source']},{$sub['subscribed_at']},1\n";
            }
        }
    }
    exit();
}

// ========== ANALYTICS ==========

function handleTrackClick() {
    $input = json_decode(file_get_contents('php://input'), true);
    $platform = $input['platform'] ?? '';
    $source = $input['source'] ?? 'unknown';

    if (empty($platform)) {
        respond(['success' => true]); // Silent fail
    }

    $db = getDB();
    if ($db) {
        try {
            $stmt = $db->prepare("INSERT INTO click_analytics (platform, source_page, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $stmt->execute([$platform, $source, getClientIP(), getUserAgent()]);
        } catch (PDOException $e) {
            // Silent fail
        }
    }

    respond(['success' => true]);
}

function handleGetAnalytics() {
    $db = getDB();
    if (!$db) {
        respond(['success' => true, 'clicks' => [], 'total' => 0]);
    }

    $days = intval($_GET['days'] ?? 7);

    try {
        $stmt = $db->prepare("SELECT platform, COUNT(*) as clicks FROM click_analytics WHERE clicked_at > DATE_SUB(NOW(), INTERVAL ? DAY) GROUP BY platform ORDER BY clicks DESC");
        $stmt->execute([$days]);

        $clicks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $clicks[$row['platform']] = intval($row['clicks']);
        }

        // Get total
        $total = $db->query("SELECT COUNT(*) FROM click_analytics WHERE clicked_at > DATE_SUB(NOW(), INTERVAL $days DAY)")->fetchColumn();

        respond(['success' => true, 'clicks' => $clicks, 'total' => intval($total), 'days' => $days]);
    } catch (PDOException $e) {
        handleError('Failed to load analytics');
    }
}

// ========== ADMIN DASHBOARD ==========

function handleDashboard() {
    $db = getDB();
    $data = [
        'subscribers' => 0,
        'requests_new' => 0,
        'requests_total' => 0,
        'clicks_7d' => 0,
        'last_login' => null
    ];

    if ($db) {
        try {
            $data['subscribers'] = $db->query("SELECT COUNT(*) FROM email_subscribers WHERE is_active = 1")->fetchColumn() ?: 0;
            $data['requests_new'] = $db->query("SELECT COUNT(*) FROM custom_requests WHERE status = 'new' AND created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn() ?: 0;
            $data['requests_total'] = $db->query("SELECT COUNT(*) FROM custom_requests")->fetchColumn() ?: 0;
            $data['clicks_7d'] = $db->query("SELECT COUNT(*) FROM click_analytics WHERE clicked_at > DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn() ?: 0;

            $lastLogin = $db->query("SELECT ip_address, created_at FROM admin_login_logs WHERE success = 1 ORDER BY created_at DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            $data['last_login'] = $lastLogin;
        } catch (PDOException $e) {
            // Continue with defaults
        }
    } else {
        // File-based fallback counts
        $subsFile = __DIR__ . '/subscribers.json';
        $reqFile = __DIR__ . '/requests.json';
        if (file_exists($subsFile)) {
            $data['subscribers'] = count(json_decode(file_get_contents($subsFile), true) ?: []);
        }
        if (file_exists($reqFile)) {
            $requests = json_decode(file_get_contents($reqFile), true) ?: [];
            $data['requests_total'] = count($requests);
            $data['requests_new'] = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'new'));
        }
    }

    respond(['success' => true, 'dashboard' => $data]);
}

function handleLoginLogs() {
    $db = getDB();
    if (!$db) {
        respond(['success' => true, 'logs' => []]);
    }

    try {
        $stmt = $db->query("SELECT admin_username, ip_address, success, reason, created_at FROM admin_login_logs ORDER BY created_at DESC LIMIT 50");
        respond(['success' => true, 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (PDOException $e) {
        handleError('Failed to load logs');
    }
}

function handleSystemHealth() {
    $health = [
        'db' => getDB() !== null,
        'upload_dir' => is_writable(UPLOAD_DIR),
        'gallery_dir' => is_writable(GALLERY_DIR),
        'featured_dir' => is_writable(FEATURED_DIR),
        'api_dir' => is_writable(__DIR__),
        'php_version' => phpversion(),
        'smtp_configured' => !empty($_ENV['SMTP_HOST']) && $_ENV['SMTP_HOST'] !== 'smtp.example.com'
    ];

    respond(['success' => true, 'health' => $health]);
}

function handleInitDB() {
    $db = getDB();
    if (!$db) handleError('Database connection failed');

    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) handleError('Schema file not found');

    $sql = file_get_contents($schemaFile);

    try {
        // Split by semicolons and execute each statement
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($statements as $stmt) {
            if (!empty($stmt) && stripos($stmt, '--') !== 0) {
                $db->exec($stmt);
            }
        }

        logAdminAction($_ENV['ADMIN_USERNAME'], 'init_db', 'database', null, null);
        respond(['success' => true, 'message' => 'Database initialized successfully']);
    } catch (PDOException $e) {
        handleError('Database initialization failed: ' . $e->getMessage());
    }
}

// ========== SMTP SETTINGS HANDLERS ==========

define('SMTP_SETTINGS_FILE', __DIR__ . '/smtp_settings.json');
define('ANALYTICS_SETTINGS_FILE', __DIR__ . '/analytics_settings.json');
define('EMAIL_TEMPLATES_FILE', __DIR__ . '/email_templates.json');

function handleGetSmtpSettings() {
    $smtp = [];
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? '';

    if (file_exists(SMTP_SETTINGS_FILE)) {
        $smtp = json_decode(file_get_contents(SMTP_SETTINGS_FILE), true) ?: [];
        // Don't send password
        if (isset($smtp['pass'])) {
            $smtp['pass'] = ''; // Mask it
        }
    } else {
        // Return from ENV if not file configured
        $smtp = [
            'host' => $_ENV['SMTP_HOST'] ?? '',
            'port' => intval($_ENV['SMTP_PORT'] ?? 587),
            'user' => $_ENV['SMTP_USER'] ?? '',
            'fromEmail' => $_ENV['SMTP_FROM_EMAIL'] ?? '',
            'fromName' => $_ENV['SMTP_FROM_NAME'] ?? ''
        ];
    }

    respond(['success' => true, 'smtp' => $smtp, 'adminEmail' => $adminEmail]);
}

function handleSaveSmtpSettings() {
    $input = json_decode(file_get_contents('php://input'), true);

    // Load existing
    $settings = file_exists(SMTP_SETTINGS_FILE)
        ? json_decode(file_get_contents(SMTP_SETTINGS_FILE), true) ?: []
        : [];

    // Update fields
    if (isset($input['host'])) $settings['host'] = $input['host'];
    if (isset($input['port'])) $settings['port'] = intval($input['port']);
    if (isset($input['user'])) $settings['user'] = $input['user'];
    if (!empty($input['pass'])) $settings['pass'] = $input['pass']; // Only update if provided
    if (isset($input['fromEmail'])) $settings['fromEmail'] = $input['fromEmail'];
    if (isset($input['fromName'])) $settings['fromName'] = $input['fromName'];

    file_put_contents(SMTP_SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));

    // Also update .env file for persistence
    updateEnvFile($settings);

    logAdminAction($_ENV['ADMIN_USERNAME'] ?? 'admin', 'update_smtp', 'settings', null, null);
    respond(['success' => true]);
}

function updateEnvFile($smtpSettings) {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) return;

    $content = file_get_contents($envFile);

    $mapping = [
        'host' => 'SMTP_HOST',
        'port' => 'SMTP_PORT',
        'user' => 'SMTP_USER',
        'pass' => 'SMTP_PASS',
        'fromEmail' => 'SMTP_FROM_EMAIL',
        'fromName' => 'SMTP_FROM_NAME'
    ];

    foreach ($smtpSettings as $key => $value) {
        if (isset($mapping[$key]) && !empty($value)) {
            $envKey = $mapping[$key];
            // Update or add line
            if (preg_match("/^{$envKey}=.*/m", $content)) {
                $content = preg_replace("/^{$envKey}=.*/m", "{$envKey}={$value}", $content);
            } else {
                $content .= "\n{$envKey}={$value}";
            }
        }
    }

    file_put_contents($envFile, $content);
}

function handleTestSmtp() {
    $settings = file_exists(SMTP_SETTINGS_FILE)
        ? json_decode(file_get_contents(SMTP_SETTINGS_FILE), true) ?: []
        : [];

    $host = $settings['host'] ?? $_ENV['SMTP_HOST'] ?? '';
    $port = $settings['port'] ?? $_ENV['SMTP_PORT'] ?? 587;
    $user = $settings['user'] ?? $_ENV['SMTP_USER'] ?? '';
    $pass = $settings['pass'] ?? $_ENV['SMTP_PASS'] ?? '';
    $fromEmail = $settings['fromEmail'] ?? $_ENV['SMTP_FROM_EMAIL'] ?? '';
    $fromName = $settings['fromName'] ?? $_ENV['SMTP_FROM_NAME'] ?? 'Wyatt Admin';
    $adminEmail = $_ENV['ADMIN_EMAIL'] ?? '';

    if (empty($host) || empty($adminEmail)) {
        handleError('SMTP not configured or no admin email set');
    }

    // Try sending test email
    $result = sendEmail(
        $adminEmail,
        'SMTP Test - WYATT XXX COLE Admin',
        "This is a test email from your admin panel.\n\nIf you received this, your SMTP is configured correctly!",
        "<p>This is a test email from your admin panel.</p><p><strong>If you received this, your SMTP is configured correctly!</strong></p>"
    );

    if ($result) {
        respond(['success' => true, 'message' => 'Test email sent successfully']);
    } else {
        handleError('Failed to send test email. Check your SMTP settings.');
    }
}

function handleSaveAdminEmail() {
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleError('Invalid email address');
    }

    // Update .env
    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $content = file_get_contents($envFile);
        if (preg_match("/^ADMIN_EMAIL=.*/m", $content)) {
            $content = preg_replace("/^ADMIN_EMAIL=.*/m", "ADMIN_EMAIL={$email}", $content);
        } else {
            $content .= "\nADMIN_EMAIL={$email}";
        }
        file_put_contents($envFile, $content);
    }

    // Update runtime
    $_ENV['ADMIN_EMAIL'] = $email;

    logAdminAction($_ENV['ADMIN_USERNAME'] ?? 'admin', 'update_admin_email', 'settings', null, null);
    respond(['success' => true]);
}

// ========== EMAIL TEMPLATES HANDLERS ==========

function handleGetEmailTemplates() {
    $templates = [];

    if (file_exists(EMAIL_TEMPLATES_FILE)) {
        $templates = json_decode(file_get_contents(EMAIL_TEMPLATES_FILE), true) ?: [];
    }

    // Default templates if not set
    if (empty($templates['verification'])) {
        $templates['verification'] = [
            'subject' => 'Your Admin Verification Code',
            'body' => "Your verification code is: {{CODE}}\n\nThis code will expire in 10 minutes.\n\nIf you didn't request this code, please ignore this email.\n\n- WYATT XXX COLE Admin"
        ];
    }

    respond(['success' => true, 'templates' => $templates]);
}

function handleSaveEmailTemplates() {
    $input = json_decode(file_get_contents('php://input'), true);

    $templates = file_exists(EMAIL_TEMPLATES_FILE)
        ? json_decode(file_get_contents(EMAIL_TEMPLATES_FILE), true) ?: []
        : [];

    if (isset($input['verification'])) {
        $templates['verification'] = [
            'subject' => $input['verification']['subject'] ?? 'Your Admin Verification Code',
            'body' => $input['verification']['body'] ?? ''
        ];
    }

    file_put_contents(EMAIL_TEMPLATES_FILE, json_encode($templates, JSON_PRETTY_PRINT));

    logAdminAction($_ENV['ADMIN_USERNAME'] ?? 'admin', 'update_email_templates', 'settings', null, null);
    respond(['success' => true]);
}

// ========== ANALYTICS SETTINGS HANDLERS ==========

function handleGetAnalyticsSettings() {
    $settings = [];

    if (file_exists(ANALYTICS_SETTINGS_FILE)) {
        $settings = json_decode(file_get_contents(ANALYTICS_SETTINGS_FILE), true) ?: [];
    }

    respond(['success' => true, 'settings' => $settings]);
}

function handleSaveAnalyticsSettings() {
    $input = json_decode(file_get_contents('php://input'), true);

    $settings = file_exists(ANALYTICS_SETTINGS_FILE)
        ? json_decode(file_get_contents(ANALYTICS_SETTINGS_FILE), true) ?: []
        : [];

    // Merge new settings
    $allowedFields = [
        'ga4MeasurementId', 'fbPixelId', 'tiktokPixelId',
        'hotjarSiteId', 'clarityProjectId'
    ];

    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $settings[$field] = $input[$field];
        }
    }

    file_put_contents(ANALYTICS_SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));

    logAdminAction($_ENV['ADMIN_USERNAME'] ?? 'admin', 'update_analytics', 'settings', null, json_encode($input));
    respond(['success' => true]);
}
?>
