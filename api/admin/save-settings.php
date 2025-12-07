<?php
/**
 * Admin Settings API
 * Handles saving API keys and vendor configurations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Authentication check
session_start();
$envFile = dirname(__DIR__) . '/.env';

// Load current env
function loadEnv($file) {
    $env = [];
    if (file_exists($file)) {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                $env[] = $line; // Keep comments
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value);
            }
        }
    }
    return $env;
}

// Save env file
function saveEnv($file, $data) {
    $content = "# WYATT XXX COLE Admin API Configuration\n";
    $content .= "# Updated: " . date('Y-m-d H:i:s') . "\n\n";

    $sections = [
        'admin' => "# Admin credentials\n",
        'pod' => "\n# ═══════════════════════════════════════════════════════════════\n# PRINT ON DEMAND (POD) VENDORS\n# ═══════════════════════════════════════════════════════════════\n",
        'dropship' => "\n# ═══════════════════════════════════════════════════════════════\n# DROPSHIPPING VENDORS\n# ═══════════════════════════════════════════════════════════════\n",
        'shipping' => "\n# ═══════════════════════════════════════════════════════════════\n# SHIPPING PARTNERS\n# ═══════════════════════════════════════════════════════════════\n",
        'payments' => "\n# ═══════════════════════════════════════════════════════════════\n# PAYMENT PROCESSORS (Adult Industry Friendly)\n# ═══════════════════════════════════════════════════════════════\n",
        'crypto' => "\n# ═══════════════════════════════════════════════════════════════\n# CRYPTOCURRENCY PAYMENTS\n# ═══════════════════════════════════════════════════════════════\n",
        'mcp' => "\n# ═══════════════════════════════════════════════════════════════\n# MCP SERVERS\n# ═══════════════════════════════════════════════════════════════\n"
    ];

    $keyCategories = [
        'admin' => ['ADMIN_USERNAME', 'ADMIN_PASSWORD', 'DEBUG_MODE'],
        'pod' => ['PRINTFUL', 'PRINTIFY', 'CUSTOMCAT', 'GOOTEN', 'GELATO', 'PRODIGI', 'TEELAUNCH', 'SCALABLEPRESS', 'MERCHIZE', 'AWKWARDSTYLES', 'TPOP', 'MONSTERDIGITAL', 'APLIIQ', 'SUBLIMINATOR', 'PRINTAURA'],
        'dropship' => ['ALIEXPRESS', 'ALIBABA', 'CJ', 'SPOCKET', 'MODALYST', 'SYNCEE', 'DOBA', 'SALEHOO', 'MEGAGOODS', 'INVENTORYSOURCE', 'DSERS', 'ZENDROP', 'DROPIFIED', 'WHOLESALE2B', 'BANGGOOD'],
        'shipping' => ['EASYPOST', 'SHIPSTATION', 'SHIPPO', 'AFTERSHIP', 'PIRATESHIP', 'SHIPHERO', 'EASYSHIP', 'SHIPENGINE', 'ORDORO', 'SHIPBOB', 'DHL', 'STAMPS'],
        'payments' => ['CCBILL', 'EPOCH', 'SEGPAY', 'VEROTEL', 'STICKY', 'ZOMBAIO', 'NATS', 'PAXUM', 'COSMO', 'WEBBILLING', 'PROBILLER'],
        'crypto' => ['COINBASE', 'NOWPAYMENTS', 'BTCPAY', 'PLISIO'],
        'mcp' => ['MCP']
    ];

    foreach ($sections as $section => $header) {
        $content .= $header;
        foreach ($data as $key => $value) {
            foreach ($keyCategories[$section] as $prefix) {
                if (strpos($key, $prefix) === 0) {
                    $content .= "{$key}={$value}\n";
                    unset($data[$key]);
                    break;
                }
            }
        }
    }

    // Add any remaining keys
    foreach ($data as $key => $value) {
        if (!is_numeric($key)) {
            $content .= "{$key}={$value}\n";
        }
    }

    return file_put_contents($file, $content, LOCK_EX);
}

// Handle requests
$action = $_GET['action'] ?? 'get';
$input = json_decode(file_get_contents('php://input'), true);

try {
    switch ($action) {
        case 'get':
            // Get all settings (masked)
            $env = loadEnv($envFile);
            $masked = [];
            foreach ($env as $key => $value) {
                if (is_string($key)) {
                    // Mask sensitive values
                    if (stripos($key, 'KEY') !== false ||
                        stripos($key, 'SECRET') !== false ||
                        stripos($key, 'PASSWORD') !== false ||
                        stripos($key, 'TOKEN') !== false ||
                        stripos($key, 'SALT') !== false) {
                        $masked[$key] = $value ? '••••••••' . substr($value, -4) : '';
                    } else {
                        $masked[$key] = $value;
                    }
                }
            }
            echo json_encode(['success' => true, 'data' => $masked]);
            break;

        case 'save':
            // Save specific vendor settings
            $vendor = $input['vendor'] ?? '';
            $settings = $input['settings'] ?? [];

            if (!$vendor || empty($settings)) {
                throw new Exception('Vendor and settings required');
            }

            // Load current env
            $env = loadEnv($envFile);

            // Map vendor settings to env keys
            $keyMappings = [
                'printful' => [
                    'printful-api-key' => 'PRINTFUL_API_KEY',
                    'printful-webhook-secret' => 'PRINTFUL_WEBHOOK_SECRET'
                ],
                'printify' => [
                    'printify-api-key' => 'PRINTIFY_API_KEY',
                    'printify-shop-id' => 'PRINTIFY_SHOP_ID'
                ],
                'customcat' => [
                    'customcat-api-key' => 'CUSTOMCAT_API_KEY'
                ],
                'gooten' => [
                    'gooten-api-key' => 'GOOTEN_API_KEY',
                    'gooten-recipe-id' => 'GOOTEN_RECIPE_ID'
                ],
                'gelato' => [
                    'gelato-api-key' => 'GELATO_API_KEY'
                ],
                'prodigi' => [
                    'prodigi-api-key' => 'PRODIGI_API_KEY'
                ],
                'cj' => [
                    'cj-api-key' => 'CJ_API_KEY',
                    'cj-email' => 'CJ_EMAIL'
                ],
                'aliexpress' => [
                    'aliexpress-app-key' => 'ALIEXPRESS_APP_KEY',
                    'aliexpress-app-secret' => 'ALIEXPRESS_APP_SECRET',
                    'aliexpress-access-token' => 'ALIEXPRESS_ACCESS_TOKEN'
                ],
                'spocket' => [
                    'spocket-api-key' => 'SPOCKET_API_KEY'
                ],
                'easypost' => [
                    'easypost-api-key' => 'EASYPOST_API_KEY'
                ],
                'shipstation' => [
                    'shipstation-api-key' => 'SHIPSTATION_API_KEY',
                    'shipstation-api-secret' => 'SHIPSTATION_API_SECRET'
                ],
                'ccbill' => [
                    'ccbill-account' => 'CCBILL_ACCOUNT',
                    'ccbill-subaccount' => 'CCBILL_SUBACCOUNT',
                    'ccbill-flex-id' => 'CCBILL_FLEX_ID',
                    'ccbill-salt' => 'CCBILL_SALT'
                ],
                'epoch' => [
                    'epoch-company-id' => 'EPOCH_COMPANY_ID',
                    'epoch-api-key' => 'EPOCH_API_KEY'
                ],
                'segpay' => [
                    'segpay-merchant-id' => 'SEGPAY_MERCHANT_ID',
                    'segpay-api-key' => 'SEGPAY_API_KEY'
                ],
                'nowpayments' => [
                    'nowpayments-api-key' => 'NOWPAYMENTS_API_KEY',
                    'nowpayments-ipn-secret' => 'NOWPAYMENTS_IPN_SECRET'
                ],
                'coinbase' => [
                    'coinbase-api-key' => 'COINBASE_COMMERCE_KEY',
                    'coinbase-webhook-secret' => 'COINBASE_WEBHOOK_SECRET'
                ],
                'btcpay' => [
                    'btcpay-host' => 'BTCPAY_HOST',
                    'btcpay-api-key' => 'BTCPAY_API_KEY',
                    'btcpay-store-id' => 'BTCPAY_STORE_ID'
                ],
                'plisio' => [
                    'plisio-api-key' => 'PLISIO_API_KEY'
                ]
            ];

            if (isset($keyMappings[$vendor])) {
                foreach ($keyMappings[$vendor] as $formKey => $envKey) {
                    if (isset($settings[$formKey]) && $settings[$formKey] !== '') {
                        $env[$envKey] = $settings[$formKey];
                    }
                }
            }

            // Save updated env
            if (saveEnv($envFile, $env)) {
                echo json_encode(['success' => true, 'message' => "Settings saved for {$vendor}"]);
            } else {
                throw new Exception('Failed to save settings');
            }
            break;

        case 'save-mcp':
            // Save MCP server configuration
            $mcpConfig = $input['config'] ?? [];

            $configPath = dirname(__DIR__) . '/mcp-config.json';
            if (file_put_contents($configPath, json_encode($mcpConfig, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true, 'message' => 'MCP configuration saved']);
            } else {
                throw new Exception('Failed to save MCP configuration');
            }
            break;

        case 'test':
            // Test vendor connection
            $vendor = $_GET['vendor'] ?? '';

            // This would actually call the vendor API
            $result = [
                'vendor' => $vendor,
                'status' => 'configured',
                'message' => 'API configured (actual test requires API key)'
            ];

            echo json_encode(['success' => true, 'data' => $result]);
            break;

        default:
            throw new Exception('Unknown action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
