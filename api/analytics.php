<?php
/**
 * WYATT XXX COLE - Custom Analytics API
 * Records and serves analytics data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load environment
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

// Database connection
$db = null;

function getDB() {
    global $db;
    if ($db !== null) return $db;

    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? '';
    $user = $_ENV['DB_USER'] ?? '';
    $pass = $_ENV['DB_PASSWORD'] ?? '';

    if (empty($dbname) || empty($user)) return null;

    try {
        $db = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
    } catch (PDOException $e) {
        error_log("Analytics DB error: " . $e->getMessage());
        return null;
    }
}

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data);
    exit();
}

function getClientIP() {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    return $ip;
}

// Get geo data from IP (using free ip-api.com)
function getGeoFromIP($ip) {
    // Skip for localhost/private IPs
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0']) ||
        preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.)/', $ip)) {
        return ['country' => 'XX', 'city' => 'Local', 'region' => ''];
    }

    $cacheFile = sys_get_temp_dir() . '/geo_' . md5($ip) . '.json';

    // Cache for 24 hours
    if (file_exists($cacheFile) && filemtime($cacheFile) > time() - 86400) {
        return json_decode(file_get_contents($cacheFile), true);
    }

    try {
        $ctx = stream_context_create(['http' => ['timeout' => 2]]);
        $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,regionName", false, $ctx);

        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                $geo = [
                    'country' => $data['countryCode'] ?? 'XX',
                    'city' => $data['city'] ?? '',
                    'region' => $data['regionName'] ?? ''
                ];
                file_put_contents($cacheFile, json_encode($geo));
                return $geo;
            }
        }
    } catch (Exception $e) {}

    return ['country' => 'XX', 'city' => '', 'region' => ''];
}

// ==================== RECORDING HANDLERS ====================

function handleSessionStart($input) {
    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    $sessionId = $input['sessionId'] ?? '';
    $visitorId = $input['visitorId'] ?? '';

    if (empty($sessionId) || empty($visitorId)) {
        respond(['success' => false, 'error' => 'Missing IDs']);
    }

    $ip = getClientIP();
    $geo = getGeoFromIP($ip);

    try {
        $stmt = $db->prepare("
            INSERT INTO analytics_sessions
            (session_id, visitor_id, ip_address, country, city, region, device_type,
             browser, browser_version, os, os_version, screen_width, screen_height,
             language, timezone, referrer, referrer_domain, utm_source, utm_medium,
             utm_campaign, utm_term, utm_content, landing_page)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE last_activity = NOW()
        ");

        $stmt->execute([
            $sessionId,
            $visitorId,
            $ip,
            $geo['country'],
            $geo['city'],
            $geo['region'],
            $input['deviceType'] ?? 'desktop',
            $input['browser'] ?? '',
            $input['browserVersion'] ?? '',
            $input['os'] ?? '',
            $input['osVersion'] ?? '',
            intval($input['screenWidth'] ?? 0),
            intval($input['screenHeight'] ?? 0),
            $input['language'] ?? '',
            $input['timezone'] ?? '',
            $input['referrer'] ?? '',
            $input['referrerDomain'] ?? '',
            $input['utmSource'] ?? '',
            $input['utmMedium'] ?? '',
            $input['utmCampaign'] ?? '',
            $input['utmTerm'] ?? '',
            $input['utmContent'] ?? '',
            $input['landingPage'] ?? ''
        ]);

        respond(['success' => true]);
    } catch (PDOException $e) {
        error_log("Session start error: " . $e->getMessage());
        respond(['success' => false, 'error' => 'DB error']);
    }
}

function handlePageView($input) {
    $db = getDB();
    if (!$db) respond(['success' => true]); // Fail silently

    $sessionId = $input['sessionId'] ?? '';
    $visitorId = $input['visitorId'] ?? '';

    if (empty($sessionId) || empty($visitorId)) {
        respond(['success' => true]);
    }

    try {
        // Insert page view
        $stmt = $db->prepare("
            INSERT INTO analytics_pageviews
            (session_id, visitor_id, page_url, page_path, page_title, previous_page)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $sessionId,
            $visitorId,
            $input['pageUrl'] ?? '',
            $input['pagePath'] ?? '',
            $input['pageTitle'] ?? '',
            $input['previousPage'] ?? ''
        ]);

        // Update session page count
        $db->prepare("
            UPDATE analytics_sessions
            SET page_views = page_views + 1,
                is_bounce = IF(page_views > 0, 0, 1),
                last_activity = NOW()
            WHERE session_id = ?
        ")->execute([$sessionId]);

        respond(['success' => true]);
    } catch (PDOException $e) {
        error_log("Pageview error: " . $e->getMessage());
        respond(['success' => true]);
    }
}

function handlePageExit($input) {
    $db = getDB();
    if (!$db) respond(['success' => true]);

    $sessionId = $input['sessionId'] ?? '';

    try {
        // Update the most recent page view for this session
        $stmt = $db->prepare("
            UPDATE analytics_pageviews
            SET time_on_page = ?, scroll_depth = ?
            WHERE session_id = ? AND page_url = ?
            ORDER BY viewed_at DESC LIMIT 1
        ");

        $stmt->execute([
            intval($input['timeOnPage'] ?? 0),
            intval($input['scrollDepth'] ?? 0),
            $sessionId,
            $input['pageUrl'] ?? ''
        ]);

        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => true]);
    }
}

function handleEvent($input) {
    $db = getDB();
    if (!$db) respond(['success' => true]);

    $sessionId = $input['sessionId'] ?? '';
    $visitorId = $input['visitorId'] ?? '';

    try {
        $stmt = $db->prepare("
            INSERT INTO analytics_events
            (session_id, visitor_id, event_category, event_action, event_label,
             event_value, page_url, element_id, element_class, element_text)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $sessionId,
            $visitorId,
            $input['eventCategory'] ?? '',
            $input['eventAction'] ?? '',
            $input['eventLabel'] ?? '',
            $input['eventValue'] ?? null,
            $input['pageUrl'] ?? '',
            $input['elementId'] ?? '',
            $input['elementClass'] ?? '',
            $input['elementText'] ?? ''
        ]);

        // Update session event count
        $db->prepare("
            UPDATE analytics_sessions SET events = events + 1, last_activity = NOW()
            WHERE session_id = ?
        ")->execute([$sessionId]);

        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => true]);
    }
}

function handleRealtimeUpdate($input) {
    $db = getDB();
    if (!$db) respond(['success' => true]);

    $sessionId = $input['sessionId'] ?? '';
    $visitorId = $input['visitorId'] ?? '';

    $ip = getClientIP();
    $geo = getGeoFromIP($ip);

    try {
        $stmt = $db->prepare("
            INSERT INTO analytics_realtime
            (session_id, visitor_id, current_page, page_title, country, city, device_type, referrer_domain)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                current_page = VALUES(current_page),
                page_title = VALUES(page_title),
                last_seen = NOW()
        ");

        $stmt->execute([
            $sessionId,
            $visitorId,
            $input['currentPage'] ?? '',
            $input['pageTitle'] ?? '',
            $geo['country'],
            $geo['city'],
            $input['deviceType'] ?? 'desktop',
            $input['referrerDomain'] ?? ''
        ]);

        // Clean up old realtime entries (older than 5 minutes)
        $db->exec("DELETE FROM analytics_realtime WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => true]);
    }
}

function handleHeartbeat($input) {
    $db = getDB();
    if (!$db) respond(['success' => true]);

    $sessionId = $input['sessionId'] ?? '';

    try {
        // Update session last activity and calculate duration
        $db->prepare("
            UPDATE analytics_sessions
            SET last_activity = NOW(),
                duration_seconds = TIMESTAMPDIFF(SECOND, started_at, NOW())
            WHERE session_id = ?
        ")->execute([$sessionId]);

        // Update realtime
        $db->prepare("
            UPDATE analytics_realtime
            SET last_seen = NOW(), current_page = ?
            WHERE session_id = ?
        ")->execute([$input['currentPage'] ?? '', $sessionId]);

        respond(['success' => true]);
    } catch (PDOException $e) {
        respond(['success' => true]);
    }
}

// ==================== RETRIEVAL HANDLERS (for admin dashboard) ====================

function checkAdminAuth() {
    $token = $_GET['token'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = str_replace('Bearer ', '', $token);

    $tokenFile = __DIR__ . '/tokens/' . $token . '.json';
    if (!file_exists($tokenFile)) return false;

    $data = json_decode(file_get_contents($tokenFile), true);
    return $data && $data['expires'] > time();
}

function handleGetDashboardStats() {
    if (!checkAdminAuth()) respond(['success' => false, 'error' => 'Unauthorized'], 401);

    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    $days = intval($_GET['days'] ?? 7);

    try {
        // Get overview stats
        $stats = [];

        // Today's stats
        $today = $db->query("
            SELECT
                COUNT(DISTINCT visitor_id) as visitors,
                COUNT(*) as sessions,
                SUM(page_views) as pageviews,
                AVG(duration_seconds) as avg_duration,
                AVG(is_bounce) * 100 as bounce_rate
            FROM analytics_sessions
            WHERE DATE(started_at) = CURDATE()
        ")->fetch(PDO::FETCH_ASSOC);

        $stats['today'] = [
            'visitors' => intval($today['visitors'] ?? 0),
            'sessions' => intval($today['sessions'] ?? 0),
            'pageviews' => intval($today['pageviews'] ?? 0),
            'avgDuration' => round($today['avg_duration'] ?? 0),
            'bounceRate' => round($today['bounce_rate'] ?? 0, 1)
        ];

        // Period stats
        $period = $db->prepare("
            SELECT
                COUNT(DISTINCT visitor_id) as visitors,
                COUNT(*) as sessions,
                SUM(page_views) as pageviews,
                AVG(duration_seconds) as avg_duration,
                AVG(is_bounce) * 100 as bounce_rate
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $period->execute([$days]);
        $periodData = $period->fetch(PDO::FETCH_ASSOC);

        $stats['period'] = [
            'days' => $days,
            'visitors' => intval($periodData['visitors'] ?? 0),
            'sessions' => intval($periodData['sessions'] ?? 0),
            'pageviews' => intval($periodData['pageviews'] ?? 0),
            'avgDuration' => round($periodData['avg_duration'] ?? 0),
            'bounceRate' => round($periodData['bounce_rate'] ?? 0, 1)
        ];

        // Real-time visitors
        $realtime = $db->query("
            SELECT COUNT(*) as count FROM analytics_realtime
            WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ")->fetch(PDO::FETCH_ASSOC);
        $stats['realtime'] = intval($realtime['count'] ?? 0);

        respond(['success' => true, 'stats' => $stats]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleGetTrafficChart() {
    if (!checkAdminAuth()) respond(['success' => false, 'error' => 'Unauthorized'], 401);

    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    $days = intval($_GET['days'] ?? 7);

    try {
        $stmt = $db->prepare("
            SELECT
                DATE(started_at) as date,
                COUNT(DISTINCT visitor_id) as visitors,
                COUNT(*) as sessions,
                SUM(page_views) as pageviews
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(started_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$days]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond(['success' => true, 'data' => $data]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleGetTopPages() {
    if (!checkAdminAuth()) respond(['success' => false, 'error' => 'Unauthorized'], 401);

    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    $days = intval($_GET['days'] ?? 7);
    $limit = intval($_GET['limit'] ?? 10);

    try {
        $stmt = $db->prepare("
            SELECT
                page_path,
                page_title,
                COUNT(*) as views,
                COUNT(DISTINCT visitor_id) as unique_views,
                AVG(time_on_page) as avg_time,
                AVG(scroll_depth) as avg_scroll
            FROM analytics_pageviews
            WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY page_path, page_title
            ORDER BY views DESC
            LIMIT ?
        ");
        $stmt->execute([$days, $limit]);
        $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond(['success' => true, 'pages' => $pages]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleGetReferrers() {
    if (!checkAdminAuth()) respond(['success' => false, 'error' => 'Unauthorized'], 401);

    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    $days = intval($_GET['days'] ?? 7);

    try {
        $stmt = $db->prepare("
            SELECT
                CASE
                    WHEN referrer_domain = '' THEN 'Direct'
                    ELSE referrer_domain
                END as source,
                COUNT(*) as sessions,
                COUNT(DISTINCT visitor_id) as visitors,
                AVG(is_bounce) * 100 as bounce_rate
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY source
            ORDER BY sessions DESC
            LIMIT 15
        ");
        $stmt->execute([$days]);
        $referrers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond(['success' => true, 'referrers' => $referrers]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleGetDevices() {
    if (!checkAdminAuth()) respond(['success' => false, 'error' => 'Unauthorized'], 401);

    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    $days = intval($_GET['days'] ?? 7);

    try {
        // Device types
        $devices = $db->prepare("
            SELECT device_type, COUNT(*) as count
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY device_type
            ORDER BY count DESC
        ");
        $devices->execute([$days]);

        // Browsers
        $browsers = $db->prepare("
            SELECT browser, COUNT(*) as count
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY browser
            ORDER BY count DESC
            LIMIT 10
        ");
        $browsers->execute([$days]);

        // OS
        $os = $db->prepare("
            SELECT os, COUNT(*) as count
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY os
            ORDER BY count DESC
            LIMIT 10
        ");
        $os->execute([$days]);

        respond([
            'success' => true,
            'devices' => $devices->fetchAll(PDO::FETCH_ASSOC),
            'browsers' => $browsers->fetchAll(PDO::FETCH_ASSOC),
            'os' => $os->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleGetCountries() {
    if (!checkAdminAuth()) respond(['success' => false, 'error' => 'Unauthorized'], 401);

    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    $days = intval($_GET['days'] ?? 7);

    try {
        $stmt = $db->prepare("
            SELECT
                country,
                city,
                COUNT(*) as sessions,
                COUNT(DISTINCT visitor_id) as visitors
            FROM analytics_sessions
            WHERE started_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY country, city
            ORDER BY sessions DESC
            LIMIT 20
        ");
        $stmt->execute([$days]);
        $locations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond(['success' => true, 'locations' => $locations]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleGetRealtime() {
    if (!checkAdminAuth()) respond(['success' => false, 'error' => 'Unauthorized'], 401);

    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    try {
        $visitors = $db->query("
            SELECT
                session_id, current_page, page_title, country, city, device_type, referrer_domain,
                TIMESTAMPDIFF(SECOND, last_seen, NOW()) as seconds_ago
            FROM analytics_realtime
            WHERE last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY last_seen DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        respond(['success' => true, 'visitors' => $visitors, 'count' => count($visitors)]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleGetEvents() {
    if (!checkAdminAuth()) respond(['success' => false, 'error' => 'Unauthorized'], 401);

    $db = getDB();
    if (!$db) respond(['success' => false, 'error' => 'DB unavailable']);

    $days = intval($_GET['days'] ?? 7);

    try {
        $stmt = $db->prepare("
            SELECT
                event_category,
                event_action,
                event_label,
                COUNT(*) as count
            FROM analytics_events
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY event_category, event_action, event_label
            ORDER BY count DESC
            LIMIT 50
        ");
        $stmt->execute([$days]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond(['success' => true, 'events' => $events]);
    } catch (PDOException $e) {
        respond(['success' => false, 'error' => $e->getMessage()]);
    }
}

// ==================== ROUTER ====================

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    // Recording endpoints (no auth required - called from frontend)
    case 'session_start':
        handleSessionStart($input);
        break;
    case 'pageview':
        handlePageView($input);
        break;
    case 'page_exit':
        handlePageExit($input);
        break;
    case 'event':
        handleEvent($input);
        break;
    case 'realtime_update':
        handleRealtimeUpdate($input);
        break;
    case 'heartbeat':
        handleHeartbeat($input);
        break;

    // Retrieval endpoints (admin auth required)
    case 'dashboard':
        handleGetDashboardStats();
        break;
    case 'traffic':
        handleGetTrafficChart();
        break;
    case 'pages':
        handleGetTopPages();
        break;
    case 'referrers':
        handleGetReferrers();
        break;
    case 'devices':
        handleGetDevices();
        break;
    case 'countries':
        handleGetCountries();
        break;
    case 'realtime':
        handleGetRealtime();
        break;
    case 'events':
        handleGetEvents();
        break;

    default:
        respond(['success' => true]); // Silent success for invalid actions
}
?>
