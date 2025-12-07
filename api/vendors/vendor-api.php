<?php
/**
 * WYATT XXX COLE - Unified Vendor API
 * Handles all POD, Dropshipping, and Fulfillment operations
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/vendor-config.php';
$config = require __DIR__ . '/vendor-config.php';

// ═══════════════════════════════════════════════════════════════
// BASE VENDOR CLASS
// ═══════════════════════════════════════════════════════════════

abstract class VendorAPI {
    protected $config;
    protected $name;

    public function __construct($config) {
        $this->config = $config;
        $this->name = $config['name'] ?? 'Unknown';
    }

    abstract public function getProducts($params = []);
    abstract public function createOrder($orderData);
    abstract public function getOrderStatus($orderId);
    abstract public function getShippingRates($address, $items);

    protected function makeRequest($endpoint, $method = 'GET', $data = null, $headers = []) {
        $url = $this->config['api_base'] . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $defaultHeaders = ['Content-Type: application/json'];
        $headers = array_merge($defaultHeaders, $headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("API Error: $error");
        }

        return [
            'status' => $httpCode,
            'data' => json_decode($response, true),
            'raw' => $response
        ];
    }
}

// ═══════════════════════════════════════════════════════════════
// PRINTFUL API
// ═══════════════════════════════════════════════════════════════

class PrintfulAPI extends VendorAPI {
    public function getProducts($params = []) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        $endpoint = '/store/products';
        if (!empty($params['limit'])) $endpoint .= '?limit=' . $params['limit'];
        return $this->makeRequest($endpoint, 'GET', null, $headers);
    }

    public function getCatalog($categoryId = null) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        $endpoint = '/products';
        if ($categoryId) $endpoint .= '/' . $categoryId;
        return $this->makeRequest($endpoint, 'GET', null, $headers);
    }

    public function createProduct($productData) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/store/products', 'POST', $productData, $headers);
    }

    public function createOrder($orderData) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/orders', 'POST', $orderData, $headers);
    }

    public function getOrderStatus($orderId) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/orders/' . $orderId, 'GET', null, $headers);
    }

    public function getShippingRates($address, $items) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        $data = [
            'recipient' => $address,
            'items' => $items
        ];
        return $this->makeRequest('/shipping/rates', 'POST', $data, $headers);
    }

    public function estimateCosts($items) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/orders/estimate-costs', 'POST', ['items' => $items], $headers);
    }
}

// ═══════════════════════════════════════════════════════════════
// PRINTIFY API
// ═══════════════════════════════════════════════════════════════

class PrintifyAPI extends VendorAPI {
    public function getProducts($params = []) {
        $shopId = $this->config['shop_id'];
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest("/shops/{$shopId}/products.json", 'GET', null, $headers);
    }

    public function getCatalog($blueprintId = null) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        $endpoint = '/catalog/blueprints.json';
        if ($blueprintId) $endpoint = "/catalog/blueprints/{$blueprintId}.json";
        return $this->makeRequest($endpoint, 'GET', null, $headers);
    }

    public function getPrintProviders($blueprintId) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest("/catalog/blueprints/{$blueprintId}/print_providers.json", 'GET', null, $headers);
    }

    public function createProduct($productData) {
        $shopId = $this->config['shop_id'];
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest("/shops/{$shopId}/products.json", 'POST', $productData, $headers);
    }

    public function createOrder($orderData) {
        $shopId = $this->config['shop_id'];
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest("/shops/{$shopId}/orders.json", 'POST', $orderData, $headers);
    }

    public function getOrderStatus($orderId) {
        $shopId = $this->config['shop_id'];
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest("/shops/{$shopId}/orders/{$orderId}.json", 'GET', null, $headers);
    }

    public function getShippingRates($address, $items) {
        $shopId = $this->config['shop_id'];
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest("/shops/{$shopId}/orders/shipping.json", 'POST', [
            'line_items' => $items,
            'address_to' => $address
        ], $headers);
    }
}

// ═══════════════════════════════════════════════════════════════
// CUSTOMCAT API
// ═══════════════════════════════════════════════════════════════

class CustomCatAPI extends VendorAPI {
    public function getProducts($params = []) {
        $headers = ['X-API-KEY: ' . $this->config['api_key']];
        return $this->makeRequest('/products', 'GET', null, $headers);
    }

    public function createOrder($orderData) {
        $headers = ['X-API-KEY: ' . $this->config['api_key']];
        return $this->makeRequest('/orders', 'POST', $orderData, $headers);
    }

    public function getOrderStatus($orderId) {
        $headers = ['X-API-KEY: ' . $this->config['api_key']];
        return $this->makeRequest('/orders/' . $orderId, 'GET', null, $headers);
    }

    public function getShippingRates($address, $items) {
        $headers = ['X-API-KEY: ' . $this->config['api_key']];
        return $this->makeRequest('/shipping/quote', 'POST', [
            'address' => $address,
            'items' => $items
        ], $headers);
    }
}

// ═══════════════════════════════════════════════════════════════
// GOOTEN API
// ═══════════════════════════════════════════════════════════════

class GootenAPI extends VendorAPI {
    public function getProducts($params = []) {
        $recipeId = $this->config['recipe_id'];
        return $this->makeRequest("/products?recipeId={$recipeId}");
    }

    public function createOrder($orderData) {
        $orderData['RecipeId'] = $this->config['recipe_id'];
        return $this->makeRequest('/orders', 'POST', $orderData);
    }

    public function getOrderStatus($orderId) {
        return $this->makeRequest('/orders/' . $orderId);
    }

    public function getShippingRates($address, $items) {
        return $this->makeRequest('/shippingprices', 'POST', [
            'ShipToAddress' => $address,
            'Items' => $items
        ]);
    }
}

// ═══════════════════════════════════════════════════════════════
// CJ DROPSHIPPING API
// ═══════════════════════════════════════════════════════════════

class CJDropshippingAPI extends VendorAPI {
    private $accessToken;

    private function getAccessToken() {
        if ($this->accessToken) return $this->accessToken;

        $response = $this->makeRequest('/authentication/getAccessToken', 'POST', [
            'email' => $this->config['email'],
            'password' => $this->config['api_key']
        ]);

        $this->accessToken = $response['data']['data']['accessToken'] ?? '';
        return $this->accessToken;
    }

    public function getProducts($params = []) {
        $token = $this->getAccessToken();
        $headers = ['CJ-Access-Token: ' . $token];
        return $this->makeRequest('/product/list', 'GET', null, $headers);
    }

    public function searchProducts($keyword, $pageNum = 1) {
        $token = $this->getAccessToken();
        $headers = ['CJ-Access-Token: ' . $token];
        return $this->makeRequest('/product/list', 'POST', [
            'productNameEn' => $keyword,
            'pageNum' => $pageNum,
            'pageSize' => 20
        ], $headers);
    }

    public function createOrder($orderData) {
        $token = $this->getAccessToken();
        $headers = ['CJ-Access-Token: ' . $token];
        return $this->makeRequest('/shopping/order/createOrder', 'POST', $orderData, $headers);
    }

    public function getOrderStatus($orderId) {
        $token = $this->getAccessToken();
        $headers = ['CJ-Access-Token: ' . $token];
        return $this->makeRequest('/shopping/order/getOrderDetail?orderId=' . $orderId, 'GET', null, $headers);
    }

    public function getShippingRates($address, $items) {
        $token = $this->getAccessToken();
        $headers = ['CJ-Access-Token: ' . $token];
        return $this->makeRequest('/logistic/freightCalculate', 'POST', [
            'countryCode' => $address['country'],
            'products' => $items
        ], $headers);
    }
}

// ═══════════════════════════════════════════════════════════════
// ALIEXPRESS API
// ═══════════════════════════════════════════════════════════════

class AliExpressAPI extends VendorAPI {
    private function generateSign($params) {
        ksort($params);
        $str = $this->config['app_secret'];
        foreach ($params as $k => $v) {
            $str .= $k . $v;
        }
        $str .= $this->config['app_secret'];
        return strtoupper(md5($str));
    }

    public function getProducts($params = []) {
        $apiParams = [
            'app_key' => $this->config['app_key'],
            'access_token' => $this->config['access_token'],
            'method' => 'aliexpress.ds.product.get',
            'timestamp' => date('Y-m-d H:i:s'),
            'sign_method' => 'md5',
            'v' => '2.0'
        ];
        $apiParams['sign'] = $this->generateSign($apiParams);

        $query = http_build_query($apiParams);
        return $this->makeRequest('?' . $query);
    }

    public function searchProducts($keyword, $pageNo = 1) {
        $apiParams = [
            'app_key' => $this->config['app_key'],
            'access_token' => $this->config['access_token'],
            'method' => 'aliexpress.affiliate.product.query',
            'timestamp' => date('Y-m-d H:i:s'),
            'sign_method' => 'md5',
            'v' => '2.0',
            'keywords' => $keyword,
            'page_no' => $pageNo
        ];
        $apiParams['sign'] = $this->generateSign($apiParams);

        $query = http_build_query($apiParams);
        return $this->makeRequest('?' . $query);
    }

    public function createOrder($orderData) {
        $apiParams = [
            'app_key' => $this->config['app_key'],
            'access_token' => $this->config['access_token'],
            'method' => 'aliexpress.ds.order.create',
            'timestamp' => date('Y-m-d H:i:s'),
            'sign_method' => 'md5',
            'v' => '2.0'
        ];
        $apiParams = array_merge($apiParams, $orderData);
        $apiParams['sign'] = $this->generateSign($apiParams);

        return $this->makeRequest('', 'POST', $apiParams);
    }

    public function getOrderStatus($orderId) {
        $apiParams = [
            'app_key' => $this->config['app_key'],
            'access_token' => $this->config['access_token'],
            'method' => 'aliexpress.ds.order.get',
            'timestamp' => date('Y-m-d H:i:s'),
            'sign_method' => 'md5',
            'v' => '2.0',
            'order_id' => $orderId
        ];
        $apiParams['sign'] = $this->generateSign($apiParams);

        $query = http_build_query($apiParams);
        return $this->makeRequest('?' . $query);
    }

    public function getShippingRates($address, $items) {
        $apiParams = [
            'app_key' => $this->config['app_key'],
            'access_token' => $this->config['access_token'],
            'method' => 'aliexpress.logistics.buyer.freight.calculate',
            'timestamp' => date('Y-m-d H:i:s'),
            'sign_method' => 'md5',
            'v' => '2.0'
        ];
        $apiParams['sign'] = $this->generateSign($apiParams);

        return $this->makeRequest('', 'POST', array_merge($apiParams, [
            'country' => $address['country'],
            'product_items' => $items
        ]));
    }
}

// ═══════════════════════════════════════════════════════════════
// SPOCKET API
// ═══════════════════════════════════════════════════════════════

class SpocketAPI extends VendorAPI {
    public function getProducts($params = []) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/products', 'GET', null, $headers);
    }

    public function searchProducts($query, $page = 1) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/products/search?q=' . urlencode($query) . '&page=' . $page, 'GET', null, $headers);
    }

    public function createOrder($orderData) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/orders', 'POST', $orderData, $headers);
    }

    public function getOrderStatus($orderId) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/orders/' . $orderId, 'GET', null, $headers);
    }

    public function getShippingRates($address, $items) {
        $headers = ['Authorization: Bearer ' . $this->config['api_key']];
        return $this->makeRequest('/shipping/rates', 'POST', [
            'destination' => $address,
            'items' => $items
        ], $headers);
    }
}

// ═══════════════════════════════════════════════════════════════
// EASYPOST SHIPPING API
// ═══════════════════════════════════════════════════════════════

class EasyPostAPI {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->config['api_base'] . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->config['api_key'] . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function createAddress($address) {
        return $this->makeRequest('/addresses', 'POST', ['address' => $address]);
    }

    public function verifyAddress($addressId) {
        return $this->makeRequest("/addresses/{$addressId}/verify", 'GET');
    }

    public function createShipment($from, $to, $parcel) {
        return $this->makeRequest('/shipments', 'POST', [
            'shipment' => [
                'from_address' => $from,
                'to_address' => $to,
                'parcel' => $parcel
            ]
        ]);
    }

    public function buyShipment($shipmentId, $rateId) {
        return $this->makeRequest("/shipments/{$shipmentId}/buy", 'POST', [
            'rate' => ['id' => $rateId]
        ]);
    }

    public function getTracker($trackingCode, $carrier) {
        return $this->makeRequest('/trackers', 'POST', [
            'tracker' => [
                'tracking_code' => $trackingCode,
                'carrier' => $carrier
            ]
        ]);
    }

    public function getRates($from, $to, $parcel) {
        $shipment = $this->createShipment($from, $to, $parcel);
        return $shipment['rates'] ?? [];
    }
}

// ═══════════════════════════════════════════════════════════════
// SHIPSTATION API
// ═══════════════════════════════════════════════════════════════

class ShipStationAPI {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->config['api_base'] . $endpoint;
        $auth = base64_encode($this->config['api_key'] . ':' . $this->config['api_secret']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function getOrders($params = []) {
        $query = http_build_query($params);
        return $this->makeRequest('/orders?' . $query);
    }

    public function createOrder($orderData) {
        return $this->makeRequest('/orders/createorder', 'POST', $orderData);
    }

    public function createLabel($shipmentData) {
        return $this->makeRequest('/shipments/createlabel', 'POST', $shipmentData);
    }

    public function getRates($rateData) {
        return $this->makeRequest('/shipments/getrates', 'POST', $rateData);
    }

    public function getCarriers() {
        return $this->makeRequest('/carriers');
    }

    public function trackShipment($trackingNumber) {
        return $this->makeRequest('/shipments?trackingNumber=' . $trackingNumber);
    }
}

// ═══════════════════════════════════════════════════════════════
// SHIPPO API
// ═══════════════════════════════════════════════════════════════

class ShippoAPI {
    private $config;

    public function __construct($config) {
        $this->config = $config;
    }

    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->config['api_base'] . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: ShippoToken ' . $this->config['api_key'],
            'Content-Type: application/json'
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    public function createAddress($address) {
        return $this->makeRequest('/addresses/', 'POST', $address);
    }

    public function validateAddress($addressId) {
        return $this->makeRequest("/addresses/{$addressId}/validate/");
    }

    public function createParcel($parcel) {
        return $this->makeRequest('/parcels/', 'POST', $parcel);
    }

    public function createShipment($shipment) {
        return $this->makeRequest('/shipments/', 'POST', $shipment);
    }

    public function getRates($shipmentId) {
        return $this->makeRequest("/shipments/{$shipmentId}/rates/");
    }

    public function purchaseLabel($rateId) {
        return $this->makeRequest('/transactions/', 'POST', [
            'rate' => $rateId,
            'async' => false
        ]);
    }

    public function track($carrier, $trackingNumber) {
        return $this->makeRequest("/tracks/{$carrier}/{$trackingNumber}/");
    }
}

// ═══════════════════════════════════════════════════════════════
// UNIFIED VENDOR MANAGER
// ═══════════════════════════════════════════════════════════════

class VendorManager {
    private $config;
    private $vendors = [];
    private $shippingProviders = [];

    public function __construct($config) {
        $this->config = $config;
        $this->initializeVendors();
        $this->initializeShipping();
    }

    private function initializeVendors() {
        // POD Vendors
        foreach ($this->config['pod_vendors'] as $key => $vendorConfig) {
            if (!$vendorConfig['enabled'] || empty($vendorConfig['api_key'] ?? '')) continue;

            switch ($key) {
                case 'printful':
                    $this->vendors[$key] = new PrintfulAPI($vendorConfig);
                    break;
                case 'printify':
                    $this->vendors[$key] = new PrintifyAPI($vendorConfig);
                    break;
                case 'customcat':
                    $this->vendors[$key] = new CustomCatAPI($vendorConfig);
                    break;
                case 'gooten':
                    $this->vendors[$key] = new GootenAPI($vendorConfig);
                    break;
            }
        }

        // Dropship Vendors
        foreach ($this->config['dropship_vendors'] as $key => $vendorConfig) {
            if (!$vendorConfig['enabled']) continue;

            switch ($key) {
                case 'cjdropshipping':
                    if (!empty($vendorConfig['api_key'])) {
                        $this->vendors[$key] = new CJDropshippingAPI($vendorConfig);
                    }
                    break;
                case 'aliexpress':
                    if (!empty($vendorConfig['app_key'])) {
                        $this->vendors[$key] = new AliExpressAPI($vendorConfig);
                    }
                    break;
                case 'spocket':
                    if (!empty($vendorConfig['api_key'])) {
                        $this->vendors[$key] = new SpocketAPI($vendorConfig);
                    }
                    break;
            }
        }
    }

    private function initializeShipping() {
        foreach ($this->config['shipping_partners'] as $key => $providerConfig) {
            if (!$providerConfig['enabled']) continue;

            switch ($key) {
                case 'easypost':
                    if (!empty($providerConfig['api_key'])) {
                        $this->shippingProviders[$key] = new EasyPostAPI($providerConfig);
                    }
                    break;
                case 'shipstation':
                    if (!empty($providerConfig['api_key'])) {
                        $this->shippingProviders[$key] = new ShipStationAPI($providerConfig);
                    }
                    break;
                case 'shippo':
                    if (!empty($providerConfig['api_key'])) {
                        $this->shippingProviders[$key] = new ShippoAPI($providerConfig);
                    }
                    break;
            }
        }
    }

    public function getVendor($key) {
        return $this->vendors[$key] ?? null;
    }

    public function getShippingProvider($key) {
        return $this->shippingProviders[$key] ?? null;
    }

    public function getAllVendors() {
        return array_keys($this->vendors);
    }

    public function getAllShippingProviders() {
        return array_keys($this->shippingProviders);
    }

    public function getVendorConfig($type = 'all') {
        switch ($type) {
            case 'pod':
                return $this->config['pod_vendors'];
            case 'dropship':
                return $this->config['dropship_vendors'];
            case 'shipping':
                return $this->config['shipping_partners'];
            default:
                return [
                    'pod_vendors' => $this->config['pod_vendors'],
                    'dropship_vendors' => $this->config['dropship_vendors'],
                    'shipping_partners' => $this->config['shipping_partners']
                ];
        }
    }

    // Smart order routing - finds best vendor based on criteria
    public function findBestVendor($productType, $destination, $priority = 'cost') {
        $candidates = [];

        foreach ($this->config['pod_vendors'] as $key => $vendor) {
            if (!$vendor['enabled']) continue;
            if (!in_array($productType, $vendor['categories'] ?? [])) continue;

            $score = 0;

            // Check destination country support
            if (in_array($destination, $vendor['fulfillment_countries'])) {
                $score += 10;
            }

            // Priority scoring
            if ($priority === 'speed') {
                $score += (10 - ($vendor['avg_production_days'] ?? 5));
            } else {
                $score += (20 - $vendor['priority']);
            }

            $candidates[$key] = $score;
        }

        arsort($candidates);
        return array_key_first($candidates);
    }

    // Get shipping rates from all providers
    public function getShippingRates($from, $to, $parcel) {
        $rates = [];

        foreach ($this->shippingProviders as $key => $provider) {
            try {
                $providerRates = $provider->getRates($from, $to, $parcel);
                $rates[$key] = $providerRates;
            } catch (Exception $e) {
                $rates[$key] = ['error' => $e->getMessage()];
            }
        }

        return $rates;
    }
}

// ═══════════════════════════════════════════════════════════════
// API ENDPOINT HANDLER
// ═══════════════════════════════════════════════════════════════

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

function handleError($message, $code = 400) {
    respond(['success' => false, 'error' => $message], $code);
}

// Initialize manager
$manager = new VendorManager($config);

// Route handling
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$vendor = $_GET['vendor'] ?? $_POST['vendor'] ?? '';

switch ($action) {
    case 'list-vendors':
        respond([
            'success' => true,
            'vendors' => $manager->getVendorConfig()
        ]);
        break;

    case 'list-pod':
        respond([
            'success' => true,
            'vendors' => $manager->getVendorConfig('pod')
        ]);
        break;

    case 'list-dropship':
        respond([
            'success' => true,
            'vendors' => $manager->getVendorConfig('dropship')
        ]);
        break;

    case 'list-shipping':
        respond([
            'success' => true,
            'providers' => $manager->getVendorConfig('shipping')
        ]);
        break;

    case 'get-products':
        if (!$vendor) handleError('Vendor required');
        $api = $manager->getVendor($vendor);
        if (!$api) handleError('Vendor not found or not configured');

        try {
            $products = $api->getProducts($_GET);
            respond(['success' => true, 'products' => $products]);
        } catch (Exception $e) {
            handleError($e->getMessage());
        }
        break;

    case 'search-products':
        if (!$vendor) handleError('Vendor required');
        $keyword = $_GET['q'] ?? $_POST['q'] ?? '';
        if (!$keyword) handleError('Search keyword required');

        $api = $manager->getVendor($vendor);
        if (!$api) handleError('Vendor not found');

        try {
            if (method_exists($api, 'searchProducts')) {
                $results = $api->searchProducts($keyword);
                respond(['success' => true, 'results' => $results]);
            } else {
                handleError('Search not supported for this vendor');
            }
        } catch (Exception $e) {
            handleError($e->getMessage());
        }
        break;

    case 'create-order':
        if (!$vendor) handleError('Vendor required');
        $api = $manager->getVendor($vendor);
        if (!$api) handleError('Vendor not found');

        $orderData = json_decode(file_get_contents('php://input'), true);
        if (!$orderData) handleError('Order data required');

        try {
            $result = $api->createOrder($orderData);
            respond(['success' => true, 'order' => $result]);
        } catch (Exception $e) {
            handleError($e->getMessage());
        }
        break;

    case 'order-status':
        if (!$vendor) handleError('Vendor required');
        $orderId = $_GET['order_id'] ?? '';
        if (!$orderId) handleError('Order ID required');

        $api = $manager->getVendor($vendor);
        if (!$api) handleError('Vendor not found');

        try {
            $status = $api->getOrderStatus($orderId);
            respond(['success' => true, 'status' => $status]);
        } catch (Exception $e) {
            handleError($e->getMessage());
        }
        break;

    case 'shipping-rates':
        $input = json_decode(file_get_contents('php://input'), true);
        $from = $input['from'] ?? [];
        $to = $input['to'] ?? [];
        $parcel = $input['parcel'] ?? [];

        if (empty($from) || empty($to) || empty($parcel)) {
            handleError('From address, to address, and parcel required');
        }

        try {
            $rates = $manager->getShippingRates($from, $to, $parcel);
            respond(['success' => true, 'rates' => $rates]);
        } catch (Exception $e) {
            handleError($e->getMessage());
        }
        break;

    case 'find-best-vendor':
        $productType = $_GET['product_type'] ?? 'apparel';
        $destination = $_GET['destination'] ?? 'US';
        $priority = $_GET['priority'] ?? 'cost';

        $best = $manager->findBestVendor($productType, $destination, $priority);
        respond([
            'success' => true,
            'recommended_vendor' => $best,
            'vendor_config' => $config['pod_vendors'][$best] ?? null
        ]);
        break;

    default:
        respond([
            'success' => true,
            'message' => 'WYATT XXX COLE Vendor API',
            'version' => '1.0.0',
            'available_actions' => [
                'list-vendors' => 'List all vendor configurations',
                'list-pod' => 'List POD vendors',
                'list-dropship' => 'List dropshipping vendors',
                'list-shipping' => 'List shipping providers',
                'get-products' => 'Get products from vendor (requires vendor param)',
                'search-products' => 'Search products (requires vendor and q params)',
                'create-order' => 'Create order with vendor (POST)',
                'order-status' => 'Get order status (requires vendor and order_id)',
                'shipping-rates' => 'Get shipping rates (POST with from/to/parcel)',
                'find-best-vendor' => 'Find best vendor for product type'
            ],
            'active_vendors' => $manager->getAllVendors(),
            'active_shipping' => $manager->getAllShippingProviders()
        ]);
}
