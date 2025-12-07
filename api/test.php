<?php
/**
 * API Test Endpoint
 */

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'WYATT XXX COLE API is running',
    'php_version' => PHP_VERSION,
    'endpoints' => [
        'vendors' => '/api/VendorManager.php?action=vendors',
        'health' => '/api/VendorManager.php?action=health',
        'config' => '/api/VendorManager.php?action=config',
        'search' => '/api/VendorManager.php?action=search&q=keyword',
        'webhooks' => '/api/webhooks/WebhookHandler.php?provider=printful'
    ],
    'api_files' => [
        // POD Vendors
        'PrintfulAPI' => file_exists(__DIR__ . '/vendors/printful/PrintfulAPI.php'),
        'PrintifyAPI' => file_exists(__DIR__ . '/vendors/printify/PrintifyAPI.php'),
        'CustomCatAPI' => file_exists(__DIR__ . '/vendors/customcat/CustomCatAPI.php'),
        'GootenAPI' => file_exists(__DIR__ . '/vendors/gooten/GootenAPI.php'),
        'GelatoAPI' => file_exists(__DIR__ . '/vendors/gelato/GelatoAPI.php'),
        'ProdigiAPI' => file_exists(__DIR__ . '/vendors/prodigi/ProdigiAPI.php'),
        'SpocketAPI' => file_exists(__DIR__ . '/vendors/spocket/SpocketAPI.php'),
        // Dropshipping
        'CJDropshippingAPI' => file_exists(__DIR__ . '/vendors/cjdropshipping/CJDropshippingAPI.php'),
        'AliExpressAPI' => file_exists(__DIR__ . '/vendors/aliexpress/AliExpressAPI.php'),
        // Shipping
        'EasyPostAPI' => file_exists(__DIR__ . '/shipping/EasyPostAPI.php'),
        'ShipStationAPI' => file_exists(__DIR__ . '/shipping/ShipStationAPI.php'),
        // Payments
        'CCBillAPI' => file_exists(__DIR__ . '/payments/CCBillAPI.php'),
        'EpochAPI' => file_exists(__DIR__ . '/payments/EpochAPI.php'),
        'SegpayAPI' => file_exists(__DIR__ . '/payments/SegpayAPI.php'),
        'CryptoPaymentAPI' => file_exists(__DIR__ . '/payments/CryptoPaymentAPI.php'),
        // Core
        'VendorManager' => file_exists(__DIR__ . '/VendorManager.php'),
        'WebhookHandler' => file_exists(__DIR__ . '/webhooks/WebhookHandler.php')
    ],
    'total_apis' => 17
]);
