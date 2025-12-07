<?php
/**
 * Unified Vendor Manager
 * Manages all POD, Dropshipping, Shipping, and Payment vendors
 * WYATT XXX COLE - Multi-Vendor E-Commerce Platform
 */

namespace WyattXXXCole;

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'WyattXXXCole\\';
    $baseDir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $_ENV[trim($name)] = trim($value);
            putenv(trim($name) . '=' . trim($value));
        }
    }
}

use WyattXXXCole\Vendors\Printful\PrintfulAPI;
use WyattXXXCole\Vendors\Printify\PrintifyAPI;
use WyattXXXCole\Vendors\CJDropshipping\CJDropshippingAPI;
use WyattXXXCole\Vendors\AliExpress\AliExpressAPI;
use WyattXXXCole\Vendors\Gooten\GootenAPI;
use WyattXXXCole\Vendors\CustomCat\CustomCatAPI;
use WyattXXXCole\Vendors\Spocket\SpocketAPI;
use WyattXXXCole\Shipping\EasyPostAPI;
use WyattXXXCole\Payments\CCBillAPI;
use WyattXXXCole\Payments\NOWPaymentsAPI;
use WyattXXXCole\Payments\PlisioAPI;
use WyattXXXCole\Payments\BTCPayServerAPI;
use WyattXXXCole\Payments\CoinbaseCommerceAPI;
use WyattXXXCole\Payments\CryptoPaymentManager;

class VendorManager {
    private array $vendors = [];
    private array $config;
    private static ?VendorManager $instance = null;

    private function __construct() {
        $this->config = require __DIR__ . '/vendors/vendor-config.php';
        $this->initializeVendors();
    }

    /**
     * Get singleton instance
     */
    public static function getInstance(): VendorManager {
        if (self::$instance === null) {
            self::$instance = new VendorManager();
        }
        return self::$instance;
    }

    /**
     * Initialize all configured vendors
     */
    private function initializeVendors(): void {
        // POD Vendors
        if ($this->isEnabled('printful')) {
            $this->vendors['printful'] = new PrintfulAPI(
                $_ENV['PRINTFUL_API_KEY'] ?? ''
            );
        }

        if ($this->isEnabled('printify')) {
            $this->vendors['printify'] = new PrintifyAPI(
                $_ENV['PRINTIFY_API_KEY'] ?? '',
                $_ENV['PRINTIFY_SHOP_ID'] ?? null
            );
        }

        if ($this->isEnabled('customcat')) {
            $this->vendors['customcat'] = new CustomCatAPI(
                $_ENV['CUSTOMCAT_API_KEY'] ?? ''
            );
        }

        if ($this->isEnabled('gooten')) {
            $this->vendors['gooten'] = new GootenAPI(
                $_ENV['GOOTEN_API_KEY'] ?? '',
                $_ENV['GOOTEN_RECIPE_ID'] ?? ''
            );
        }

        // Dropshipping Vendors
        if ($this->isEnabled('cjdropshipping')) {
            $this->vendors['cjdropshipping'] = new CJDropshippingAPI(
                $_ENV['CJ_API_KEY'] ?? '',
                $_ENV['CJ_EMAIL'] ?? ''
            );
        }

        if ($this->isEnabled('aliexpress')) {
            $this->vendors['aliexpress'] = new AliExpressAPI(
                $_ENV['ALIEXPRESS_APP_KEY'] ?? '',
                $_ENV['ALIEXPRESS_APP_SECRET'] ?? '',
                $_ENV['ALIEXPRESS_ACCESS_TOKEN'] ?? ''
            );
        }

        if ($this->isEnabled('spocket')) {
            $this->vendors['spocket'] = new SpocketAPI(
                $_ENV['SPOCKET_API_KEY'] ?? ''
            );
        }

        // Shipping
        if ($this->isEnabled('easypost')) {
            $this->vendors['easypost'] = new EasyPostAPI(
                $_ENV['EASYPOST_API_KEY'] ?? ''
            );
        }

        // Payments
        if ($this->isEnabled('ccbill')) {
            $this->vendors['ccbill'] = new CCBillAPI(
                $_ENV['CCBILL_ACCOUNT'] ?? '',
                $_ENV['CCBILL_SUBACCOUNT'] ?? '',
                $_ENV['CCBILL_FLEX_ID'] ?? '',
                $_ENV['CCBILL_SALT'] ?? ''
            );
        }

        // Crypto Payments
        $cryptoManager = new CryptoPaymentManager();

        if (!empty($_ENV['NOWPAYMENTS_API_KEY'])) {
            $cryptoManager->registerProvider('nowpayments', new NOWPaymentsAPI(
                $_ENV['NOWPAYMENTS_API_KEY']
            ));
        }

        if (!empty($_ENV['PLISIO_API_KEY'])) {
            $cryptoManager->registerProvider('plisio', new PlisioAPI(
                $_ENV['PLISIO_API_KEY']
            ));
        }

        if (!empty($_ENV['BTCPAY_API_KEY'])) {
            $cryptoManager->registerProvider('btcpay', new BTCPayServerAPI(
                $_ENV['BTCPAY_HOST'] ?? '',
                $_ENV['BTCPAY_API_KEY'],
                $_ENV['BTCPAY_STORE_ID'] ?? ''
            ));
        }

        if (!empty($_ENV['COINBASE_COMMERCE_KEY'])) {
            $cryptoManager->registerProvider('coinbase', new CoinbaseCommerceAPI(
                $_ENV['COINBASE_COMMERCE_KEY'],
                $_ENV['COINBASE_WEBHOOK_SECRET'] ?? null
            ));
        }

        $this->vendors['crypto'] = $cryptoManager;
    }

    /**
     * Check if vendor is enabled
     */
    private function isEnabled(string $vendor): bool {
        // Check POD vendors
        if (isset($this->config['pod_vendors'][$vendor])) {
            return $this->config['pod_vendors'][$vendor]['enabled'] ?? false;
        }
        // Check dropship vendors
        if (isset($this->config['dropship_vendors'][$vendor])) {
            return $this->config['dropship_vendors'][$vendor]['enabled'] ?? false;
        }
        // Check shipping partners
        if (isset($this->config['shipping_partners'][$vendor])) {
            return $this->config['shipping_partners'][$vendor]['enabled'] ?? false;
        }
        // Check payment processors
        if (isset($this->config['payment_processors'][$vendor])) {
            return $this->config['payment_processors'][$vendor]['enabled'] ?? false;
        }
        return false;
    }

    /**
     * Get vendor instance
     */
    public function getVendor(string $name) {
        if (!isset($this->vendors[$name])) {
            throw new \Exception("Vendor '{$name}' not found or not configured");
        }
        return $this->vendors[$name];
    }

    /**
     * Get all active vendors
     */
    public function getActiveVendors(): array {
        return array_keys($this->vendors);
    }

    /**
     * Get vendor config
     */
    public function getConfig(): array {
        return $this->config;
    }

    // ═══════════════════════════════════════════════════════════════
    // UNIFIED PRODUCT OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Search products across all enabled vendors
     */
    public function searchProducts(string $query, array $vendors = []): array {
        $results = [];
        $searchVendors = empty($vendors) ? ['printful', 'printify', 'cjdropshipping', 'aliexpress', 'spocket'] : $vendors;

        foreach ($searchVendors as $vendorName) {
            if (!isset($this->vendors[$vendorName])) continue;

            try {
                $vendor = $this->vendors[$vendorName];

                switch ($vendorName) {
                    case 'printful':
                        $products = $vendor->getCatalogProducts();
                        $results[$vendorName] = $this->normalizeProducts($products, $vendorName);
                        break;
                    case 'printify':
                        $products = $vendor->getBlueprints();
                        $results[$vendorName] = $this->normalizeProducts($products, $vendorName);
                        break;
                    case 'cjdropshipping':
                        $products = $vendor->searchProducts($query);
                        $results[$vendorName] = $this->normalizeProducts($products, $vendorName);
                        break;
                    case 'aliexpress':
                        $products = $vendor->searchProducts($query);
                        $results[$vendorName] = $this->normalizeProducts($products, $vendorName);
                        break;
                    case 'spocket':
                        $products = $vendor->searchProducts($query);
                        $results[$vendorName] = $this->normalizeProducts($products, $vendorName);
                        break;
                }
            } catch (\Exception $e) {
                $results[$vendorName] = ['error' => $e->getMessage()];
            }
        }

        return $results;
    }

    /**
     * Normalize products to common format
     */
    private function normalizeProducts(array $products, string $vendor): array {
        $normalized = [];

        foreach ($products as $product) {
            $normalized[] = [
                'vendor' => $vendor,
                'id' => $product['id'] ?? $product['pid'] ?? null,
                'name' => $product['name'] ?? $product['title'] ?? $product['productNameEn'] ?? '',
                'description' => $product['description'] ?? '',
                'price' => $product['price'] ?? $product['sellPrice'] ?? 0,
                'image' => $product['image'] ?? $product['thumbnail'] ?? $product['productImage'] ?? '',
                'category' => $product['category'] ?? '',
                'raw' => $product
            ];
        }

        return $normalized;
    }

    // ═══════════════════════════════════════════════════════════════
    // UNIFIED ORDER OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create order with best vendor
     */
    public function createOrder(array $orderData, ?string $preferredVendor = null): array {
        $vendor = $preferredVendor ?? $this->selectBestVendor($orderData);

        if (!isset($this->vendors[$vendor])) {
            throw new \Exception("Vendor '{$vendor}' not available");
        }

        $api = $this->vendors[$vendor];

        // Route to appropriate vendor
        switch ($vendor) {
            case 'printful':
                return $api->createOrder(PrintfulAPI::buildOrderData(
                    $orderData['shipping'],
                    $orderData['items']
                ));
            case 'printify':
                return $api->createOrder(PrintifyAPI::buildOrderData(
                    $orderData['order_id'],
                    $orderData['items'],
                    $orderData['shipping']
                ));
            case 'cjdropshipping':
                return $api->createOrder(CJDropshippingAPI::buildOrderData(
                    $orderData['order_id'],
                    $orderData['shipping'],
                    $orderData['items']
                ));
            default:
                throw new \Exception("Order creation not implemented for vendor: {$vendor}");
        }
    }

    /**
     * Select best vendor based on product and shipping
     */
    private function selectBestVendor(array $orderData): string {
        // Logic to select best vendor based on:
        // - Product availability
        // - Shipping costs
        // - Shipping time
        // - Current vendor status

        // Default to Printful for POD items
        if ($this->isPODProduct($orderData)) {
            return 'printful';
        }

        // Default to CJ Dropshipping for other items
        return 'cjdropshipping';
    }

    /**
     * Check if order contains POD products
     */
    private function isPODProduct(array $orderData): bool {
        foreach ($orderData['items'] as $item) {
            if (isset($item['type']) && $item['type'] === 'pod') {
                return true;
            }
            if (isset($item['vendor']) && in_array($item['vendor'], ['printful', 'printify', 'customcat', 'gooten'])) {
                return true;
            }
        }
        return false;
    }

    // ═══════════════════════════════════════════════════════════════
    // UNIFIED SHIPPING OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get shipping rates from all carriers
     */
    public function getShippingRates(array $from, array $to, array $parcel): array {
        $rates = [];

        if (isset($this->vendors['easypost'])) {
            try {
                $easypost = $this->vendors['easypost'];
                $rates['easypost'] = $easypost->getRates($from, $to, $parcel);
            } catch (\Exception $e) {
                $rates['easypost'] = ['error' => $e->getMessage()];
            }
        }

        return $rates;
    }

    /**
     * Get cheapest shipping rate
     */
    public function getCheapestRate(array $from, array $to, array $parcel): ?array {
        $allRates = $this->getShippingRates($from, $to, $parcel);
        $cheapest = null;

        foreach ($allRates as $provider => $rates) {
            if (isset($rates['error'])) continue;

            foreach ($rates as $rate) {
                if ($cheapest === null || (float)$rate['rate'] < (float)$cheapest['rate']) {
                    $cheapest = array_merge($rate, ['provider' => $provider]);
                }
            }
        }

        return $cheapest;
    }

    // ═══════════════════════════════════════════════════════════════
    // UNIFIED PAYMENT OPERATIONS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create payment link
     */
    public function createPaymentLink(
        float $amount,
        string $orderId,
        string $processor = 'ccbill',
        array $options = []
    ): string {
        if ($processor === 'crypto') {
            return $this->createCryptoPayment($amount, $orderId, $options);
        }

        if (!isset($this->vendors[$processor])) {
            throw new \Exception("Payment processor '{$processor}' not available");
        }

        $api = $this->vendors[$processor];

        switch ($processor) {
            case 'ccbill':
                $digest = $api->generateSinglePriceDigest($amount, 30, 840);
                return $api->createPaymentLink($amount, 840, $digest, [
                    'custom1' => $orderId
                ]);
            default:
                throw new \Exception("Payment link creation not implemented for: {$processor}");
        }
    }

    /**
     * Create crypto payment
     */
    public function createCryptoPayment(
        float $amount,
        string $orderId,
        array $options = []
    ): array {
        $cryptoManager = $this->vendors['crypto'];
        $provider = $options['provider'] ?? 'nowpayments';

        return $cryptoManager->createPayment(
            $amount,
            'USD',
            $orderId,
            $provider,
            $options
        );
    }

    // ═══════════════════════════════════════════════════════════════
    // HEALTH CHECK
    // ═══════════════════════════════════════════════════════════════

    /**
     * Check vendor health status
     */
    public function healthCheck(): array {
        $status = [];

        foreach ($this->vendors as $name => $vendor) {
            try {
                switch ($name) {
                    case 'printful':
                        $vendor->getStores();
                        $status[$name] = 'healthy';
                        break;
                    case 'printify':
                        $vendor->getShops();
                        $status[$name] = 'healthy';
                        break;
                    case 'crypto':
                        $status[$name] = 'configured';
                        break;
                    default:
                        $status[$name] = 'configured';
                }
            } catch (\Exception $e) {
                $status[$name] = 'error: ' . $e->getMessage();
            }
        }

        return $status;
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// API ENDPOINT HANDLER
// ═══════════════════════════════════════════════════════════════════════════

// Handle API requests if this file is called directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    try {
        $manager = VendorManager::getInstance();

        $action = $_GET['action'] ?? 'health';
        $result = [];

        switch ($action) {
            case 'health':
                $result = $manager->healthCheck();
                break;
            case 'vendors':
                $result = $manager->getActiveVendors();
                break;
            case 'config':
                $result = $manager->getConfig();
                break;
            case 'search':
                $query = $_GET['q'] ?? '';
                $result = $manager->searchProducts($query);
                break;
            case 'shipping':
                $input = json_decode(file_get_contents('php://input'), true);
                $result = $manager->getShippingRates(
                    $input['from'] ?? [],
                    $input['to'] ?? [],
                    $input['parcel'] ?? []
                );
                break;
            default:
                throw new \Exception("Unknown action: {$action}");
        }

        echo json_encode(['success' => true, 'data' => $result]);

    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
