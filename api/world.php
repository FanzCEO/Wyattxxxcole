<?php
/**
 * WYATT XXX COLE - World API
 * Handles user registration, login, email verification, and admin moderation
 *
 * SECURITY FEATURES:
 * - Centralized security configuration
 * - Rate limiting per IP (100 requests/minute)
 * - Brute force protection (5 attempts, 15 min lockout)
 * - Input sanitization
 * - Token-based authentication
 * - Password hashing with bcrypt
 * - Security headers
 */

// Load centralized security configuration
require_once __DIR__ . '/security.php';

// Initialize security (CORS, headers, rate limiting)
initSecurity([
    'cors' => true,
    'headers' => true,
    'rateLimit' => true,
    'csrf' => false
]);

// Security constants
define('RATE_LIMIT_FILE', __DIR__ . '/security/rate_limits.json');
define('LOGIN_ATTEMPTS_FILE', __DIR__ . '/security/login_attempts.json');
define('MAX_REQUESTS_PER_MINUTE', 60);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_MINUTES', 15);

// Data files
define('USERS_FILE', __DIR__ . '/world_users.json');
define('POSTS_FILE', __DIR__ . '/world_posts.json');
define('COMMENTS_FILE', __DIR__ . '/world_comments.json');
define('LIKES_FILE', __DIR__ . '/world_likes.json');
define('SITE_URL', 'https://wyattxxxcole.com');
define('SITE_NAME', 'WYATT XXX COLE World');
define('FROM_EMAIL', 'noreply@wyattxxxcole.com');

// Ensure security directory exists
if (!is_dir(__DIR__ . '/security')) {
    mkdir(__DIR__ . '/security', 0700, true);
}

// ============ SECURITY FUNCTIONS ============

function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

function checkRateLimit() {
    $ip = getClientIP();
    $now = time();
    $limits = [];

    if (file_exists(RATE_LIMIT_FILE)) {
        $limits = json_decode(file_get_contents(RATE_LIMIT_FILE), true) ?: [];
    }

    // Clean old entries
    foreach ($limits as $checkIp => $data) {
        if ($data['timestamp'] < $now - 60) {
            unset($limits[$checkIp]);
        }
    }

    if (isset($limits[$ip])) {
        if ($limits[$ip]['count'] >= MAX_REQUESTS_PER_MINUTE) {
            http_response_code(429);
            echo json_encode(['error' => 'Too many requests. Please wait a moment.']);
            exit();
        }
        $limits[$ip]['count']++;
    } else {
        $limits[$ip] = ['count' => 1, 'timestamp' => $now];
    }

    @file_put_contents(RATE_LIMIT_FILE, json_encode($limits));
}

function checkLoginAttempts($email) {
    $ip = getClientIP();
    $now = time();
    $attempts = [];

    if (file_exists(LOGIN_ATTEMPTS_FILE)) {
        $attempts = json_decode(file_get_contents(LOGIN_ATTEMPTS_FILE), true) ?: [];
    }

    // Clean old lockouts
    foreach ($attempts as $key => $data) {
        if (isset($data['lockout_until']) && $data['lockout_until'] < $now && $data['lockout_until'] > 0) {
            unset($attempts[$key]);
        }
    }

    $key = md5($ip . strtolower($email));

    if (isset($attempts[$key])) {
        if (isset($attempts[$key]['lockout_until']) && $attempts[$key]['lockout_until'] > $now) {
            $remaining = ceil(($attempts[$key]['lockout_until'] - $now) / 60);
            return ['blocked' => true, 'minutes' => $remaining];
        }
        if ($attempts[$key]['count'] >= MAX_LOGIN_ATTEMPTS) {
            $attempts[$key]['lockout_until'] = $now + (LOGIN_LOCKOUT_MINUTES * 60);
            @file_put_contents(LOGIN_ATTEMPTS_FILE, json_encode($attempts));
            return ['blocked' => true, 'minutes' => LOGIN_LOCKOUT_MINUTES];
        }
    }

    return ['blocked' => false];
}

function recordFailedLogin($email) {
    $ip = getClientIP();
    $now = time();
    $attempts = [];

    if (file_exists(LOGIN_ATTEMPTS_FILE)) {
        $attempts = json_decode(file_get_contents(LOGIN_ATTEMPTS_FILE), true) ?: [];
    }

    $key = md5($ip . strtolower($email));

    if (isset($attempts[$key])) {
        $attempts[$key]['count']++;
    } else {
        $attempts[$key] = ['count' => 1, 'lockout_until' => 0];
    }

    @file_put_contents(LOGIN_ATTEMPTS_FILE, json_encode($attempts));
}

function clearLoginAttempts($email) {
    $ip = getClientIP();
    $attempts = [];

    if (file_exists(LOGIN_ATTEMPTS_FILE)) {
        $attempts = json_decode(file_get_contents(LOGIN_ATTEMPTS_FILE), true) ?: [];
    }

    $key = md5($ip . strtolower($email));
    unset($attempts[$key]);
    @file_put_contents(LOGIN_ATTEMPTS_FILE, json_encode($attempts));
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function isValidUsername($username) {
    // 3-20 chars, alphanumeric and underscores only
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

function isStrongPassword($password) {
    // At least 8 chars
    return strlen($password) >= 8;
}

// Apply rate limiting
checkRateLimit();

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function handleError($message, $code = 400) {
    respond(['success' => false, 'error' => $message], $code);
}

function getUsers() {
    if (file_exists(USERS_FILE)) {
        $data = json_decode(file_get_contents(USERS_FILE), true);
        if ($data) return $data;
    }
    return [];
}

function saveUsers($users) {
    return file_put_contents(USERS_FILE, json_encode($users, JSON_PRETTY_PRINT));
}

function getPosts() {
    if (file_exists(POSTS_FILE)) {
        $data = json_decode(file_get_contents(POSTS_FILE), true);
        if ($data) return $data;
    }
    return [];
}

function savePosts($posts) {
    return file_put_contents(POSTS_FILE, json_encode($posts, JSON_PRETTY_PRINT));
}

function getComments() {
    if (file_exists(COMMENTS_FILE)) {
        $data = json_decode(file_get_contents(COMMENTS_FILE), true);
        if ($data) return $data;
    }
    return [];
}

function saveComments($comments) {
    return file_put_contents(COMMENTS_FILE, json_encode($comments, JSON_PRETTY_PRINT));
}

function getLikes() {
    if (file_exists(LIKES_FILE)) {
        $data = json_decode(file_get_contents(LIKES_FILE), true);
        if ($data) return $data;
    }
    return [];
}

function saveLikes($likes) {
    return file_put_contents(LIKES_FILE, json_encode($likes, JSON_PRETTY_PRINT));
}

function getUserFromToken() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if (empty($token)) return null;

    $users = getUsers();
    foreach ($users as $user) {
        if (isset($user['token']) && $user['token'] === $token) {
            if (isset($user['tokenExpires']) && strtotime($user['tokenExpires']) > time()) {
                return $user;
            }
        }
    }
    return null;
}

function generateToken() {
    return bin2hex(random_bytes(32));
}

function generateVerificationCode() {
    return strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function checkAdminAuth() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $_GET['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    $tokenFile = __DIR__ . '/.admin_token';
    if (file_exists($tokenFile) && !empty($token)) {
        $storedToken = trim(file_get_contents($tokenFile));
        return !empty($storedToken) && hash_equals($storedToken, $token);
    }
    return false;
}

function sendVerificationEmail($email, $username, $code) {
    $subject = "Verify your " . SITE_NAME . " account";
    $verifyLink = SITE_URL . "/verify.html?email=" . urlencode($email) . "&code=" . $code;

    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #1a1a1a; color: #f5ede4; padding: 20px; }
            .container { max-width: 500px; margin: 0 auto; background: #111; padding: 30px; border-radius: 10px; border: 1px solid #c68e3f; }
            h1 { color: #c68e3f; margin-bottom: 20px; }
            .code { font-size: 32px; font-weight: bold; color: #c68e3f; letter-spacing: 5px; padding: 20px; background: #222; border-radius: 8px; text-align: center; margin: 20px 0; }
            .button { display: inline-block; background: linear-gradient(135deg, #c68e3f, #a44a2a); color: #000; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; }
            .footer { margin-top: 30px; color: #888; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Welcome, $username!</h1>
            <p>Thanks for joining " . SITE_NAME . ". Use this verification code to activate your account:</p>
            <div class='code'>$code</div>
            <p>Or click the button below:</p>
            <p><a href='$verifyLink' class='button'>Verify Email</a></p>
            <p class='footer'>If you didn't create this account, you can ignore this email.<br>This code expires in 24 hours.</p>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . SITE_NAME . " <" . FROM_EMAIL . ">" . "\r\n";

    return @mail($email, $subject, $message, $headers);
}

// Get action from URL path or query string
$action = $_GET['action'] ?? '';

// Also check URL path for /api/world/register or /api/world/login style URLs
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (preg_match('/\/world\/(register|login|verify-email|resend)/', $requestUri, $matches)) {
    $action = $matches[1];
}

// Get JSON input
$rawInput = file_get_contents('php://input');
$cleanedInput = str_replace('\\!', '!', $rawInput);
$input = json_decode($cleanedInput, true) ?: [];

switch ($action) {
    case 'register':
        handleRegister($input);
        break;
    case 'login':
        handleLogin($input);
        break;
    case 'verify':
        handleVerifyToken();
        break;
    case 'verify-email':
        handleVerifyEmail($input);
        break;
    case 'resend':
        handleResendVerification($input);
        break;
    // Admin endpoints
    case 'list-users':
        if (!checkAdminAuth()) handleError('Unauthorized', 401);
        handleListUsers();
        break;
    case 'update-user':
        if (!checkAdminAuth()) handleError('Unauthorized', 401);
        handleUpdateUser($input);
        break;
    case 'delete-user':
        if (!checkAdminAuth()) handleError('Unauthorized', 401);
        handleDeleteUser($input);
        break;
    case 'ban-user':
        if (!checkAdminAuth()) handleError('Unauthorized', 401);
        handleBanUser($input);
        break;
    case 'unban-user':
        if (!checkAdminAuth()) handleError('Unauthorized', 401);
        handleUnbanUser($input);
        break;
    case 'approve-user':
        if (!checkAdminAuth()) handleError('Unauthorized', 401);
        handleApproveUser($input);
        break;
    case 'update-tier':
        if (!checkAdminAuth()) handleError('Unauthorized', 401);
        handleUpdateTier($input);
        break;
    // Post endpoints
    case 'get-posts':
        handleGetPosts();
        break;
    case 'create-post':
        handleCreatePost($input);
        break;
    case 'delete-post':
        handleDeletePost($input);
        break;
    case 'toggle-pin':
        handleTogglePin($input);
        break;
    // Like endpoints
    case 'like':
        handleLike($input);
        break;
    case 'unlike':
        handleUnlike($input);
        break;
    // Comment endpoints
    case 'get-comments':
        handleGetComments();
        break;
    case 'create-comment':
        handleCreateComment($input);
        break;
    case 'delete-comment':
        handleDeleteComment($input);
        break;
    default:
        handleError('Invalid action');
}

function handleRegister($input) {
    $username = trim($input['username'] ?? '');
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';

    // Validate inputs
    if (empty($username)) {
        handleError('Username is required');
    }
    if (strlen($username) < 3) {
        handleError('Username must be at least 3 characters');
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        handleError('Username can only contain letters, numbers, and underscores');
    }

    if (empty($email)) {
        handleError('Email is required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        handleError('Invalid email format');
    }

    if (empty($password)) {
        handleError('Password is required');
    }
    if (strlen($password) < 6) {
        handleError('Password must be at least 6 characters');
    }

    // Check if user already exists
    $users = getUsers();

    foreach ($users as $user) {
        if (strtolower($user['email']) === strtolower($email)) {
            handleError('Email already registered');
        }
        if (strtolower($user['username']) === strtolower($username)) {
            handleError('Username already taken');
        }
    }

    // Generate verification code
    $verificationCode = generateVerificationCode();

    // Create new user
    $newUser = [
        'id' => uniqid('user_', true),
        'username' => $username,
        'email' => strtolower($email),
        'password' => hashPassword($password),
        'tier' => 'free',
        'status' => 'pending', // pending, active, banned
        'emailVerified' => false,
        'verificationCode' => $verificationCode,
        'verificationExpires' => date('c', strtotime('+24 hours')),
        'createdAt' => date('c'),
        'lastLogin' => null
    ];

    $users[] = $newUser;

    if (saveUsers($users)) {
        // Send verification email
        $emailSent = sendVerificationEmail($email, $username, $verificationCode);

        respond([
            'success' => true,
            'message' => 'Account created! Please check your email to verify your account.',
            'emailSent' => $emailSent,
            'requiresVerification' => true
        ]);
    }

    handleError('Failed to create account. Please try again.');
}

function handleLogin($input) {
    $email = sanitizeInput(trim($input['email'] ?? ''));
    $password = $input['password'] ?? '';

    if (empty($email)) {
        handleError('Email is required');
    }
    if (empty($password)) {
        handleError('Password is required');
    }

    // Check for brute force attacks
    $loginCheck = checkLoginAttempts($email);
    if ($loginCheck['blocked']) {
        handleError("Too many failed attempts. Try again in {$loginCheck['minutes']} minutes.", 429);
    }

    $users = getUsers();

    foreach ($users as &$user) {
        if (strtolower($user['email']) === strtolower($email)) {
            // Check if banned
            if (isset($user['status']) && $user['status'] === 'banned') {
                handleError('Your account has been suspended. Contact support for help.', 403);
            }

            // Check if email verified
            if (isset($user['emailVerified']) && !$user['emailVerified']) {
                handleError('Please verify your email before logging in. Check your inbox for the verification code.', 403);
            }

            // Check password
            if (verifyPassword($password, $user['password'])) {
                // Clear failed attempts on success
                clearLoginAttempts($email);

                // Generate token
                $token = generateToken();

                // Update last login
                $user['lastLogin'] = date('c');
                $user['token'] = $token;
                $user['tokenExpires'] = date('c', strtotime('+7 days'));
                saveUsers($users);

                respond([
                    'success' => true,
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'tier' => $user['tier'],
                        'status' => $user['status'] ?? 'active'
                    ]
                ]);
            } else {
                // Record failed attempt
                recordFailedLogin($email);
                handleError('Invalid password', 401);
            }
        }
    }

    handleError('Email not found', 401);
}

function handleVerifyEmail($input) {
    $email = trim($input['email'] ?? $_GET['email'] ?? '');
    $code = trim($input['code'] ?? $_GET['code'] ?? '');

    if (empty($email) || empty($code)) {
        handleError('Email and verification code are required');
    }

    $users = getUsers();

    foreach ($users as &$user) {
        if (strtolower($user['email']) === strtolower($email)) {
            // Check if already verified
            if (isset($user['emailVerified']) && $user['emailVerified']) {
                respond([
                    'success' => true,
                    'message' => 'Email already verified. You can sign in.'
                ]);
            }

            // Check code
            if (!isset($user['verificationCode']) || strtoupper($user['verificationCode']) !== strtoupper($code)) {
                handleError('Invalid verification code');
            }

            // Check expiration
            if (isset($user['verificationExpires']) && strtotime($user['verificationExpires']) < time()) {
                handleError('Verification code has expired. Request a new one.');
            }

            // Verify the user
            $user['emailVerified'] = true;
            $user['status'] = 'active';
            unset($user['verificationCode']);
            unset($user['verificationExpires']);
            saveUsers($users);

            respond([
                'success' => true,
                'message' => 'Email verified successfully! You can now sign in.'
            ]);
        }
    }

    handleError('Email not found');
}

function handleResendVerification($input) {
    $email = trim($input['email'] ?? '');

    if (empty($email)) {
        handleError('Email is required');
    }

    $users = getUsers();

    foreach ($users as &$user) {
        if (strtolower($user['email']) === strtolower($email)) {
            if (isset($user['emailVerified']) && $user['emailVerified']) {
                handleError('Email already verified');
            }

            // Generate new code
            $code = generateVerificationCode();
            $user['verificationCode'] = $code;
            $user['verificationExpires'] = date('c', strtotime('+24 hours'));
            saveUsers($users);

            // Send email
            $emailSent = sendVerificationEmail($email, $user['username'], $code);

            respond([
                'success' => true,
                'message' => 'Verification email sent! Check your inbox.',
                'emailSent' => $emailSent
            ]);
        }
    }

    handleError('Email not found');
}

function handleVerifyToken() {
    $headers = getallheaders();
    $token = $headers['Authorization'] ?? $_GET['token'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    if (empty($token)) {
        handleError('No token provided', 401);
    }

    $users = getUsers();

    foreach ($users as $user) {
        if (isset($user['token']) && $user['token'] === $token) {
            // Check expiration
            if (isset($user['tokenExpires']) && strtotime($user['tokenExpires']) > time()) {
                respond([
                    'success' => true,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'tier' => $user['tier'],
                        'status' => $user['status'] ?? 'active'
                    ]
                ]);
            } else {
                handleError('Token expired', 401);
            }
        }
    }

    handleError('Invalid token', 401);
}

// ============ ADMIN FUNCTIONS ============

function handleListUsers() {
    $users = getUsers();

    // Remove sensitive data
    $safeUsers = array_map(function($user) {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'tier' => $user['tier'] ?? 'free',
            'status' => $user['status'] ?? 'active',
            'emailVerified' => $user['emailVerified'] ?? false,
            'createdAt' => $user['createdAt'] ?? null,
            'lastLogin' => $user['lastLogin'] ?? null
        ];
    }, $users);

    respond([
        'success' => true,
        'users' => array_values($safeUsers),
        'total' => count($safeUsers)
    ]);
}

function handleUpdateUser($input) {
    $userId = $input['id'] ?? '';

    if (empty($userId)) {
        handleError('User ID is required');
    }

    $users = getUsers();
    $found = false;

    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            // Update allowed fields
            if (isset($input['tier'])) {
                $user['tier'] = $input['tier'];
            }
            if (isset($input['status'])) {
                $user['status'] = $input['status'];
            }
            if (isset($input['username'])) {
                $user['username'] = $input['username'];
            }
            $found = true;
            break;
        }
    }

    if (!$found) {
        handleError('User not found');
    }

    if (saveUsers($users)) {
        respond(['success' => true, 'message' => 'User updated']);
    }

    handleError('Failed to update user');
}

function handleDeleteUser($input) {
    $userId = $input['id'] ?? $_GET['id'] ?? '';

    if (empty($userId)) {
        handleError('User ID is required');
    }

    $users = getUsers();
    $newUsers = array_filter($users, function($user) use ($userId) {
        return $user['id'] !== $userId;
    });

    if (count($newUsers) === count($users)) {
        handleError('User not found');
    }

    if (saveUsers(array_values($newUsers))) {
        respond(['success' => true, 'message' => 'User deleted']);
    }

    handleError('Failed to delete user');
}

function handleBanUser($input) {
    $userId = $input['id'] ?? '';

    if (empty($userId)) {
        handleError('User ID is required');
    }

    $users = getUsers();

    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['status'] = 'banned';
            $user['bannedAt'] = date('c');
            $user['bannedReason'] = $input['reason'] ?? 'Violated terms of service';
            // Invalidate token
            unset($user['token']);
            unset($user['tokenExpires']);

            if (saveUsers($users)) {
                respond(['success' => true, 'message' => 'User banned']);
            }
        }
    }

    handleError('User not found');
}

function handleUnbanUser($input) {
    $userId = $input['id'] ?? '';

    if (empty($userId)) {
        handleError('User ID is required');
    }

    $users = getUsers();

    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['status'] = 'active';
            unset($user['bannedAt']);
            unset($user['bannedReason']);

            if (saveUsers($users)) {
                respond(['success' => true, 'message' => 'User unbanned']);
            }
        }
    }

    handleError('User not found');
}

function handleApproveUser($input) {
    $userId = $input['id'] ?? '';

    if (empty($userId)) {
        handleError('User ID is required');
    }

    $users = getUsers();

    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['status'] = 'active';
            $user['emailVerified'] = true;
            unset($user['verificationCode']);
            unset($user['verificationExpires']);

            if (saveUsers($users)) {
                respond(['success' => true, 'message' => 'User approved and verified']);
            }
        }
    }

    handleError('User not found');
}

function handleUpdateTier($input) {
    $userId = $input['id'] ?? '';
    $tier = $input['tier'] ?? '';

    if (empty($userId)) {
        handleError('User ID is required');
    }

    $validTiers = ['free', 'admin'];
    if (!in_array($tier, $validTiers)) {
        handleError('Invalid tier. Must be: free or admin');
    }

    $users = getUsers();

    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['tier'] = $tier;

            if (saveUsers($users)) {
                respond(['success' => true, 'message' => "User tier updated to $tier"]);
            }
        }
    }

    handleError('User not found');
}

// ============ POST FUNCTIONS ============

function handleGetPosts() {
    $posts = getPosts();
    $likes = getLikes();
    $comments = getComments();
    $users = getUsers();

    // Get current user for personalized data
    $currentUser = getUserFromToken();

    // Create username lookup
    $userLookup = [];
    foreach ($users as $user) {
        $userLookup[$user['id']] = $user['username'];
    }

    // Add like counts, comment counts, and user data to posts
    $enrichedPosts = array_map(function($post) use ($likes, $comments, $userLookup, $currentUser) {
        $postLikes = array_filter($likes, fn($l) => $l['postId'] === $post['id']);
        $postComments = array_filter($comments, fn($c) => $c['postId'] === $post['id']);

        $post['likeCount'] = count($postLikes);
        $post['commentCount'] = count($postComments);
        $post['username'] = $userLookup[$post['userId']] ?? 'Unknown';

        // Check if current user liked this post
        if ($currentUser) {
            $post['isLiked'] = !empty(array_filter($postLikes, fn($l) => $l['userId'] === $currentUser['id']));
        } else {
            $post['isLiked'] = false;
        }

        return $post;
    }, $posts);

    // Sort by createdAt descending (newest first)
    usort($enrichedPosts, fn($a, $b) => strtotime($b['createdAt']) - strtotime($a['createdAt']));

    respond([
        'success' => true,
        'posts' => array_values($enrichedPosts)
    ]);
}

function handleCreatePost($input) {
    $user = getUserFromToken();
    if (!$user) {
        handleError('You must be logged in to post', 401);
    }

    $content = trim($input['content'] ?? '');

    if (empty($content)) {
        handleError('Post content is required');
    }

    if (strlen($content) > 1000) {
        handleError('Post content must be under 1000 characters');
    }

    $posts = getPosts();

    $newPost = [
        'id' => uniqid('post_', true),
        'userId' => $user['id'],
        'content' => $content,
        'tier' => $input['tier'] ?? 'free', // free, vip
        'createdAt' => date('c'),
        'isPinned' => false
    ];

    array_unshift($posts, $newPost); // Add to beginning
    savePosts($posts);

    $newPost['username'] = $user['username'];
    $newPost['likeCount'] = 0;
    $newPost['commentCount'] = 0;
    $newPost['isLiked'] = false;

    respond([
        'success' => true,
        'post' => $newPost
    ]);
}

function handleDeletePost($input) {
    $user = getUserFromToken();
    if (!$user) {
        handleError('You must be logged in', 401);
    }

    $postId = $input['id'] ?? $_GET['id'] ?? '';

    if (empty($postId)) {
        handleError('Post ID is required');
    }

    $posts = getPosts();
    $found = false;

    foreach ($posts as $index => $post) {
        if ($post['id'] === $postId) {
            // Check ownership (or admin)
            if ($post['userId'] !== $user['id'] && !checkAdminAuth()) {
                handleError('You can only delete your own posts', 403);
            }
            unset($posts[$index]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        handleError('Post not found');
    }

    savePosts(array_values($posts));

    // Also delete associated likes and comments
    $likes = getLikes();
    $likes = array_filter($likes, fn($l) => $l['postId'] !== $postId);
    saveLikes(array_values($likes));

    $comments = getComments();
    $comments = array_filter($comments, fn($c) => $c['postId'] !== $postId);
    saveComments(array_values($comments));

    respond(['success' => true, 'message' => 'Post deleted']);
}

function handleTogglePin($input) {
    if (!checkAdminAuth()) {
        handleError('Admin access required', 401);
    }

    $postId = $input['id'] ?? '';

    if (empty($postId)) {
        handleError('Post ID is required');
    }

    $posts = getPosts();
    $found = false;
    $isPinned = false;

    foreach ($posts as &$post) {
        if ($post['id'] === $postId) {
            $post['isPinned'] = !($post['isPinned'] ?? false);
            $isPinned = $post['isPinned'];
            $found = true;
            break;
        }
    }

    if (!$found) {
        handleError('Post not found');
    }

    savePosts($posts);

    respond([
        'success' => true,
        'isPinned' => $isPinned,
        'message' => $isPinned ? 'Post pinned' : 'Post unpinned'
    ]);
}

// ============ LIKE FUNCTIONS ============

function handleLike($input) {
    $user = getUserFromToken();
    if (!$user) {
        handleError('You must be logged in to like posts', 401);
    }

    $postId = $input['postId'] ?? '';

    if (empty($postId)) {
        handleError('Post ID is required');
    }

    // Check post exists
    $posts = getPosts();
    $postExists = !empty(array_filter($posts, fn($p) => $p['id'] === $postId));
    if (!$postExists) {
        handleError('Post not found');
    }

    $likes = getLikes();

    // Check if already liked
    $existingLike = array_filter($likes, fn($l) => $l['postId'] === $postId && $l['userId'] === $user['id']);
    if (!empty($existingLike)) {
        respond(['success' => true, 'message' => 'Already liked']);
        return;
    }

    $likes[] = [
        'id' => uniqid('like_', true),
        'postId' => $postId,
        'userId' => $user['id'],
        'createdAt' => date('c')
    ];

    saveLikes($likes);

    // Get new count
    $newCount = count(array_filter($likes, fn($l) => $l['postId'] === $postId));

    respond([
        'success' => true,
        'likeCount' => $newCount
    ]);
}

function handleUnlike($input) {
    $user = getUserFromToken();
    if (!$user) {
        handleError('You must be logged in', 401);
    }

    $postId = $input['postId'] ?? '';

    if (empty($postId)) {
        handleError('Post ID is required');
    }

    $likes = getLikes();
    $likes = array_filter($likes, fn($l) => !($l['postId'] === $postId && $l['userId'] === $user['id']));
    saveLikes(array_values($likes));

    // Get new count
    $newCount = count(array_filter($likes, fn($l) => $l['postId'] === $postId));

    respond([
        'success' => true,
        'likeCount' => $newCount
    ]);
}

// ============ COMMENT FUNCTIONS ============

function handleGetComments() {
    $postId = $_GET['postId'] ?? '';

    if (empty($postId)) {
        handleError('Post ID is required');
    }

    $comments = getComments();
    $users = getUsers();

    // Create username lookup
    $userLookup = [];
    foreach ($users as $user) {
        $userLookup[$user['id']] = $user['username'];
    }

    // Filter and enrich comments for this post
    $postComments = array_filter($comments, fn($c) => $c['postId'] === $postId);
    $postComments = array_map(function($comment) use ($userLookup) {
        $comment['username'] = $userLookup[$comment['userId']] ?? 'Unknown';
        return $comment;
    }, $postComments);

    // Sort by createdAt ascending (oldest first for comments)
    usort($postComments, fn($a, $b) => strtotime($a['createdAt']) - strtotime($b['createdAt']));

    respond([
        'success' => true,
        'comments' => array_values($postComments)
    ]);
}

function handleCreateComment($input) {
    $user = getUserFromToken();
    if (!$user) {
        handleError('You must be logged in to comment', 401);
    }

    $postId = $input['postId'] ?? '';
    $content = trim($input['content'] ?? '');

    if (empty($postId)) {
        handleError('Post ID is required');
    }

    if (empty($content)) {
        handleError('Comment content is required');
    }

    if (strlen($content) > 500) {
        handleError('Comment must be under 500 characters');
    }

    // Check post exists
    $posts = getPosts();
    $postExists = !empty(array_filter($posts, fn($p) => $p['id'] === $postId));
    if (!$postExists) {
        handleError('Post not found');
    }

    $comments = getComments();

    $newComment = [
        'id' => uniqid('comment_', true),
        'postId' => $postId,
        'userId' => $user['id'],
        'content' => $content,
        'createdAt' => date('c')
    ];

    $comments[] = $newComment;
    saveComments($comments);

    $newComment['username'] = $user['username'];

    respond([
        'success' => true,
        'comment' => $newComment
    ]);
}

function handleDeleteComment($input) {
    $user = getUserFromToken();
    if (!$user) {
        handleError('You must be logged in', 401);
    }

    $commentId = $input['id'] ?? $_GET['id'] ?? '';

    if (empty($commentId)) {
        handleError('Comment ID is required');
    }

    $comments = getComments();
    $found = false;

    foreach ($comments as $index => $comment) {
        if ($comment['id'] === $commentId) {
            // Check ownership (or admin)
            if ($comment['userId'] !== $user['id'] && !checkAdminAuth()) {
                handleError('You can only delete your own comments', 403);
            }
            unset($comments[$index]);
            $found = true;
            break;
        }
    }

    if (!$found) {
        handleError('Comment not found');
    }

    saveComments(array_values($comments));

    respond(['success' => true, 'message' => 'Comment deleted']);
}
?>
