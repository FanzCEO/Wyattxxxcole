<?php
/**
 * WYATT XXX COLE - Unified API Endpoint
 * Simple router for all vendor, shipping, and payment APIs
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load environment
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

// Load vendor config
$config = [];
if (file_exists(__DIR__ . '/vendors/vendor-config.php')) {
    $config = require __DIR__ . '/vendors/vendor-config.php';
}

// Get action and parameters
$action = $_GET['action'] ?? 'info';
$vendor = $_GET['vendor'] ?? null;

try {
    switch ($action) {
        case 'info':
            $result = [
                'name' => 'WYATT XXX COLE API',
                'version' => '1.0.0',
                'status' => 'running',
                'php_version' => PHP_VERSION,
                'timestamp' => date('c')
            ];
            break;

        case 'health':
            $result = [
                'status' => 'healthy',
                'vendors' => [
                    'pod' => count($config['pod_vendors'] ?? []),
                    'dropship' => count($config['dropship_vendors'] ?? []),
                    'shipping' => count($config['shipping_partners'] ?? []),
                    'payments' => count($config['payment_processors'] ?? [])
                ],
                'env_loaded' => !empty($_ENV['ADMIN_USERNAME']),
                'config_loaded' => !empty($config)
            ];
            break;

        case 'vendors':
            $result = [
                'pod_vendors' => array_keys($config['pod_vendors'] ?? []),
                'dropship_vendors' => array_keys($config['dropship_vendors'] ?? []),
                'shipping_partners' => array_keys($config['shipping_partners'] ?? []),
                'payment_processors' => array_keys($config['payment_processors'] ?? [])
            ];
            break;

        case 'config':
            // Sanitized config (no API keys)
            $result = [];
            foreach (['pod_vendors', 'dropship_vendors', 'shipping_partners', 'payment_processors'] as $type) {
                $result[$type] = [];
                foreach ($config[$type] ?? [] as $name => $vendor) {
                    $result[$type][$name] = [
                        'name' => $vendor['name'] ?? $name,
                        'enabled' => $vendor['enabled'] ?? false,
                        'priority' => $vendor['priority'] ?? 99,
                        'api_base' => $vendor['api_base'] ?? null,
                        'features' => $vendor['features'] ?? []
                    ];
                }
            }
            break;

        case 'taxonomy':
            if (file_exists(__DIR__ . '/taxonomy/product-taxonomy.php')) {
                $result = require __DIR__ . '/taxonomy/product-taxonomy.php';
            } else {
                $result = ['error' => 'Taxonomy not found'];
            }
            break;

        case 'test':
            // Test vendor API connectivity
            $vendor = $_GET['vendor'] ?? 'printful';
            $result = [
                'vendor' => $vendor,
                'configured' => !empty($_ENV[strtoupper($vendor) . '_API_KEY']),
                'api_file_exists' => false,
                'status' => 'unconfigured'
            ];

            // Check if API file exists
            $apiFiles = [
                'printful' => __DIR__ . '/vendors/printful/PrintfulAPI.php',
                'printify' => __DIR__ . '/vendors/printify/PrintifyAPI.php',
                'cjdropshipping' => __DIR__ . '/vendors/cjdropshipping/CJDropshippingAPI.php',
                'aliexpress' => __DIR__ . '/vendors/aliexpress/AliExpressAPI.php',
                'easypost' => __DIR__ . '/shipping/EasyPostAPI.php',
                'ccbill' => __DIR__ . '/payments/CCBillAPI.php'
            ];

            if (isset($apiFiles[$vendor]) && file_exists($apiFiles[$vendor])) {
                $result['api_file_exists'] = true;
                $result['status'] = $result['configured'] ? 'ready' : 'missing_api_key';
            }
            break;

        case 'endpoints':
            $result = [
                'info' => 'GET /api/api.php?action=info',
                'health' => 'GET /api/api.php?action=health',
                'vendors' => 'GET /api/api.php?action=vendors',
                'config' => 'GET /api/api.php?action=config',
                'taxonomy' => 'GET /api/api.php?action=taxonomy',
                'test' => 'GET /api/api.php?action=test&vendor=printful',
                'webhooks' => [
                    'printful' => 'POST /api/webhooks/WebhookHandler.php?provider=printful',
                    'printify' => 'POST /api/webhooks/WebhookHandler.php?provider=printify',
                    'ccbill' => 'POST /api/webhooks/WebhookHandler.php?provider=ccbill',
                    'nowpayments' => 'POST /api/webhooks/WebhookHandler.php?provider=nowpayments',
                    'coinbase' => 'POST /api/webhooks/WebhookHandler.php?provider=coinbase'
                ]
            ];
            break;

        default:
            throw new Exception("Unknown action: {$action}");
    }

    echo json_encode(['success' => true, 'data' => $result], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
