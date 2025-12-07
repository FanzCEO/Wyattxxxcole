<?php
/**
 * BoyFanz Integration Plugin
 * Syncs content from boyfanz.com/wyatt_xxx_cole to wyattxxxcole.com
 *
 * This plugin connects to the BoyFanz API to pull:
 * - Profile data
 * - Posts/content
 * - Media files
 * - Subscriber counts
 * - Stats
 */

namespace WyattXXXCole\Integrations;

class BoyFanzSync {
    private string $apiBase = 'https://boyfanz.com/api';
    private string $username = 'wyatt_xxx_cole';
    private ?string $apiToken = null;
    private string $cacheDir;
    private int $cacheDuration = 300; // 5 minutes

    // Static profile data (updated manually or via admin)
    private array $staticProfile = [
        'username' => 'wyatt_xxx_cole',
        'name' => 'Wyatt XXX Cole',
        'title' => 'Wyatt XXX Cole',
        'bio' => 'Country Bred. Fully Loaded.',
        'avatar' => '',
        'location' => 'Nashville, TN',
        'website' => 'https://wyattxxxcole.com',
        'followers' => 0,
        'posts' => 0,
        'verified' => true,
        'source' => 'boyfanz.com'
    ];

    public function __construct() {
        $this->cacheDir = dirname(__DIR__) . '/cache/boyfanz/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
        $this->loadConfig();
    }

    /**
     * Load API configuration from .env
     */
    private function loadConfig(): void {
        $envFile = dirname(dirname(__DIR__)) . '/api/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    if (trim($key) === 'BOYFANZ_API_TOKEN') {
                        $this->apiToken = trim($value);
                    }
                    if (trim($key) === 'BOYFANZ_USERNAME') {
                        $this->username = trim($value);
                    }
                }
            }
        }
    }

    /**
     * Make API request to BoyFanz
     */
    private function apiRequest(string $endpoint, array $params = [], string $method = 'GET'): ?array {
        $url = $this->apiBase . $endpoint;

        if ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: WyattXXXCole-Sync/1.0'
        ];

        if ($this->apiToken) {
            $headers[] = 'Authorization: Bearer ' . $this->apiToken;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("BoyFanz API Error: $error");
            return null;
        }

        if ($httpCode >= 400) {
            error_log("BoyFanz API HTTP Error: $httpCode - $response");
            return null;
        }

        return json_decode($response, true);
    }

    /**
     * Get cached data or fetch fresh
     */
    private function getCached(string $key, callable $fetcher): ?array {
        $cacheFile = $this->cacheDir . md5($key) . '.json';

        if (file_exists($cacheFile)) {
            $cached = json_decode(file_get_contents($cacheFile), true);
            if ($cached && isset($cached['expires']) && $cached['expires'] > time()) {
                return $cached['data'];
            }
        }

        $data = $fetcher();

        if ($data !== null) {
            file_put_contents($cacheFile, json_encode([
                'expires' => time() + $this->cacheDuration,
                'data' => $data
            ]));
        }

        return $data;
    }

    /**
     * Clear cache
     */
    public function clearCache(): void {
        $files = glob($this->cacheDir . '*.json');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // PUBLIC PROFILE DATA
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get creator profile data
     */
    public function getProfile(): ?array {
        return $this->getCached('profile_' . $this->username, function() {
            // Try public profile endpoint first
            $data = $this->apiRequest('/creators/' . $this->username);

            if (!$data) {
                // Fallback: scrape public profile page
                $data = $this->scrapePublicProfile();
            }

            // Final fallback: use static profile data
            if (!$data || empty($data['name'])) {
                $data = $this->staticProfile;
            }

            return $data;
        });
    }

    /**
     * Scrape public profile as fallback
     */
    private function scrapePublicProfile(): ?array {
        $url = 'https://boyfanz.com/' . $this->username;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; WyattXXXCole-Sync/1.0)',
            CURLOPT_TIMEOUT => 30
        ]);

        $html = curl_exec($ch);
        curl_close($ch);

        if (!$html) return null;

        // Parse profile data from HTML/JSON-LD
        $profile = [
            'username' => $this->username,
            'source' => 'boyfanz.com',
            'scraped' => true
        ];

        // Look for JSON-LD structured data
        if (preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
            $jsonLd = json_decode($matches[1], true);
            if ($jsonLd) {
                $profile['name'] = $jsonLd['name'] ?? null;
                $profile['description'] = $jsonLd['description'] ?? null;
                $profile['image'] = $jsonLd['image'] ?? null;
            }
        }

        // Extract meta tags
        if (preg_match('/<meta property="og:title" content="([^"]+)"/', $html, $m)) {
            $profile['title'] = html_entity_decode($m[1]);
        }
        if (preg_match('/<meta property="og:description" content="([^"]+)"/', $html, $m)) {
            $profile['bio'] = html_entity_decode($m[1]);
        }
        if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $m)) {
            $profile['avatar'] = $m[1];
        }

        // Try to extract follower count
        if (preg_match('/(\d+(?:,\d+)*)\s*(?:followers|fans|subscribers)/i', $html, $m)) {
            $profile['followers'] = (int) str_replace(',', '', $m[1]);
        }

        // Try to extract post count
        if (preg_match('/(\d+(?:,\d+)*)\s*(?:posts|photos|videos)/i', $html, $m)) {
            $profile['posts'] = (int) str_replace(',', '', $m[1]);
        }

        return $profile;
    }

    /**
     * Get public posts/content
     */
    public function getPosts(int $limit = 10, int $offset = 0): ?array {
        return $this->getCached("posts_{$this->username}_{$limit}_{$offset}", function() use ($limit, $offset) {
            return $this->apiRequest('/creators/' . $this->username . '/posts', [
                'limit' => $limit,
                'offset' => $offset,
                'public' => true
            ]);
        });
    }

    /**
     * Get media/gallery items
     */
    public function getMedia(int $limit = 20): ?array {
        return $this->getCached("media_{$this->username}_{$limit}", function() use ($limit) {
            return $this->apiRequest('/creators/' . $this->username . '/media', [
                'limit' => $limit,
                'public' => true
            ]);
        });
    }

    /**
     * Get stats/analytics
     */
    public function getStats(): ?array {
        return $this->getCached('stats_' . $this->username, function() {
            // This would require authenticated API access
            // For now, return data from profile
            $profile = $this->getProfile();

            return [
                'followers' => $profile['followers'] ?? 0,
                'posts' => $profile['posts'] ?? 0,
                'likes' => $profile['likes'] ?? 0,
                'views' => $profile['views'] ?? 0,
                'source' => 'boyfanz.com',
                'synced_at' => date('c')
            ];
        });
    }

    // ═══════════════════════════════════════════════════════════════
    // SYNC OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Full sync - pull all data and save locally
     */
    public function fullSync(): array {
        $results = [
            'success' => true,
            'synced_at' => date('c'),
            'profile' => null,
            'posts' => null,
            'media' => null,
            'stats' => null,
            'errors' => []
        ];

        // Clear old cache
        $this->clearCache();

        // Sync profile
        try {
            $results['profile'] = $this->getProfile();
            if (!$results['profile']) {
                $results['errors'][] = 'Failed to sync profile';
            }
        } catch (\Exception $e) {
            $results['errors'][] = 'Profile sync error: ' . $e->getMessage();
        }

        // Sync posts
        try {
            $results['posts'] = $this->getPosts(50);
        } catch (\Exception $e) {
            $results['errors'][] = 'Posts sync error: ' . $e->getMessage();
        }

        // Sync media
        try {
            $results['media'] = $this->getMedia(50);
        } catch (\Exception $e) {
            $results['errors'][] = 'Media sync error: ' . $e->getMessage();
        }

        // Sync stats
        try {
            $results['stats'] = $this->getStats();
        } catch (\Exception $e) {
            $results['errors'][] = 'Stats sync error: ' . $e->getMessage();
        }

        // Save sync results
        $this->saveSyncData($results);

        $results['success'] = empty($results['errors']);
        return $results;
    }

    /**
     * Save synced data to local JSON file
     */
    private function saveSyncData(array $data): void {
        $syncFile = dirname(__DIR__) . '/data/boyfanz-sync.json';
        $dataDir = dirname($syncFile);

        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }

        file_put_contents($syncFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Get last sync data
     */
    public function getLastSync(): ?array {
        $syncFile = dirname(__DIR__) . '/data/boyfanz-sync.json';

        if (file_exists($syncFile)) {
            return json_decode(file_get_contents($syncFile), true);
        }

        return null;
    }

    // ═══════════════════════════════════════════════════════════════
    // WIDGET DATA
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get data formatted for website widgets
     */
    public function getWidgetData(): array {
        $sync = $this->getLastSync();
        $profile = $sync['profile'] ?? $this->getProfile();
        $stats = $sync['stats'] ?? $this->getStats();

        return [
            'platform' => 'BoyFanz',
            'profile_url' => 'https://boyfanz.com/' . $this->username,
            'username' => $this->username,
            'display_name' => $profile['name'] ?? $profile['title'] ?? 'Wyatt XXX Cole',
            'bio' => $profile['bio'] ?? $profile['description'] ?? '',
            'avatar' => $profile['avatar'] ?? $profile['image'] ?? '',
            'followers' => $stats['followers'] ?? $profile['followers'] ?? 0,
            'posts' => $stats['posts'] ?? $profile['posts'] ?? 0,
            'synced_at' => $sync['synced_at'] ?? date('c'),
            'cta' => [
                'text' => 'Follow on BoyFanz',
                'url' => 'https://boyfanz.com/' . $this->username
            ]
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// API ENDPOINT HANDLER
// ═══════════════════════════════════════════════════════════════════════════

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    $sync = new BoyFanzSync();
    $action = $_GET['action'] ?? 'widget';

    try {
        switch ($action) {
            case 'profile':
                $result = $sync->getProfile();
                break;

            case 'posts':
                $limit = (int)($_GET['limit'] ?? 10);
                $offset = (int)($_GET['offset'] ?? 0);
                $result = $sync->getPosts($limit, $offset);
                break;

            case 'media':
                $limit = (int)($_GET['limit'] ?? 20);
                $result = $sync->getMedia($limit);
                break;

            case 'stats':
                $result = $sync->getStats();
                break;

            case 'sync':
                // Full sync - requires auth
                $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
                $token = str_replace('Bearer ', '', $authHeader);

                // Simple token check - use .env SYNC_TOKEN
                $envToken = getenv('SYNC_TOKEN') ?: '';
                if ($envToken && $token !== $envToken) {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                    exit;
                }

                $result = $sync->fullSync();
                break;

            case 'last-sync':
                $result = $sync->getLastSync();
                break;

            case 'widget':
            default:
                $result = $sync->getWidgetData();
                break;
        }

        echo json_encode(['success' => true, 'data' => $result]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
