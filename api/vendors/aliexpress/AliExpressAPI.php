<?php
/**
 * AliExpress API Integration
 * Full implementation for Dropshipping operations
 * Documentation: https://developers.aliexpress.com/
 */

namespace WyattXXXCole\Vendors\AliExpress;

class AliExpressAPI {
    private string $appKey;
    private string $appSecret;
    private string $accessToken;
    private string $apiBase = 'https://api-sg.aliexpress.com/sync';

    public function __construct(string $appKey, string $appSecret, string $accessToken) {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->accessToken = $accessToken;
    }

    /**
     * Generate signature for API request
     */
    private function generateSign(array $params): string {
        ksort($params);
        $signStr = $this->appSecret;

        foreach ($params as $key => $value) {
            $signStr .= $key . $value;
        }

        $signStr .= $this->appSecret;

        return strtoupper(md5($signStr));
    }

    /**
     * Make API request to AliExpress
     */
    private function request(string $method, array $params = []): array {
        $systemParams = [
            'app_key' => $this->appKey,
            'method' => $method,
            'sign_method' => 'md5',
            'timestamp' => date('Y-m-d H:i:s'),
            'format' => 'json',
            'v' => '2.0',
            'session' => $this->accessToken
        ];

        $allParams = array_merge($systemParams, $params);
        $allParams['sign'] = $this->generateSign($allParams);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiBase);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($allParams));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("AliExpress API Error: $error");
        }

        $decoded = json_decode($response, true);

        if (isset($decoded['error_response'])) {
            $errorMsg = $decoded['error_response']['msg'] ?? 'Unknown error';
            throw new \Exception("AliExpress API Error: $errorMsg");
        }

        return $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // DROPSHIPPER PRODUCT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get product info for dropshipping
     */
    public function getProductInfo(int $productId): array {
        return $this->request('aliexpress.ds.product.get', [
            'product_id' => $productId
        ]);
    }

    /**
     * Search products
     */
    public function searchProducts(
        string $keyword,
        int $pageNo = 1,
        int $pageSize = 50,
        ?string $categoryId = null,
        ?string $sortBy = null
    ): array {
        $params = [
            'keywords' => $keyword,
            'page_no' => $pageNo,
            'page_size' => $pageSize
        ];

        if ($categoryId) $params['category_id'] = $categoryId;
        if ($sortBy) $params['sort'] = $sortBy;

        return $this->request('aliexpress.ds.recommend.feed.get', $params);
    }

    /**
     * Get recommended products
     */
    public function getRecommendedProducts(int $pageNo = 1, int $pageSize = 50): array {
        return $this->request('aliexpress.ds.recommend.feed.get', [
            'page_no' => $pageNo,
            'page_size' => $pageSize
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // CATEGORIES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get category info
     */
    public function getCategoryInfo(int $categoryId): array {
        return $this->request('aliexpress.ds.category.get', [
            'category_id' => $categoryId
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDERS (Dropshipping)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create dropship order
     */
    public function createOrder(array $orderData): array {
        return $this->request('aliexpress.ds.order.create', $orderData);
    }

    /**
     * Place order (payment)
     */
    public function placeOrder(int $orderId): array {
        return $this->request('aliexpress.ds.order.pay', [
            'order_id' => $orderId
        ]);
    }

    /**
     * Get order details
     */
    public function getOrder(int $orderId): array {
        return $this->request('aliexpress.ds.order.get', [
            'order_id' => $orderId
        ]);
    }

    /**
     * Get order list
     */
    public function getOrders(
        int $pageNo = 1,
        int $pageSize = 50,
        ?string $orderStatus = null,
        ?string $startTime = null,
        ?string $endTime = null
    ): array {
        $params = [
            'page_no' => $pageNo,
            'page_size' => $pageSize
        ];

        if ($orderStatus) $params['order_status'] = $orderStatus;
        if ($startTime) $params['start_time'] = $startTime;
        if ($endTime) $params['end_time'] = $endTime;

        return $this->request('aliexpress.ds.order.query', $params);
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPPING & TRACKING
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get shipping info
     */
    public function getShippingInfo(int $productId, string $country): array {
        return $this->request('aliexpress.ds.freight.query', [
            'product_id' => $productId,
            'ship_to_country' => $country
        ]);
    }

    /**
     * Get tracking info
     */
    public function getTracking(int $orderId): array {
        return $this->request('aliexpress.ds.order.tracking.get', [
            'order_id' => $orderId
        ]);
    }

    /**
     * Get tracking by tracking number
     */
    public function getTrackingByNumber(string $trackingNumber, string $serviceName = ''): array {
        return $this->request('aliexpress.logistics.ds.trackinginfo.query', [
            'logistics_no' => $trackingNumber,
            'service_name' => $serviceName,
            'origin' => 'CN'
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // IMAGE SEARCH
    // ═══════════════════════════════════════════════════════════════

    /**
     * Search products by image
     */
    public function imageSearch(string $imageUrl): array {
        return $this->request('aliexpress.ds.image.search', [
            'image_url' => $imageUrl
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // MEMBER ACCOUNT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get account info
     */
    public function getAccountInfo(): array {
        return $this->request('aliexpress.ds.member.account.get');
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCT FEED
    // ═══════════════════════════════════════════════════════════════

    /**
     * Add product to feed
     */
    public function addProductToFeed(int $productId): array {
        return $this->request('aliexpress.ds.feedname.product.add', [
            'product_id' => $productId
        ]);
    }

    /**
     * Get product feed list
     */
    public function getProductFeed(int $pageNo = 1, int $pageSize = 50): array {
        return $this->request('aliexpress.ds.feedname.product.query', [
            'page_no' => $pageNo,
            'page_size' => $pageSize
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build order data structure
     */
    public static function buildOrderData(
        array $products,
        array $shippingAddress,
        string $shippingMethod = 'EMS_ZX_ZX_US'
    ): array {
        $productItems = [];
        foreach ($products as $product) {
            $productItems[] = [
                'product_id' => $product['product_id'],
                'sku_attr' => $product['sku_attr'] ?? '',
                'logistics_service_name' => $shippingMethod,
                'order_memo' => $product['memo'] ?? '',
                'product_count' => $product['quantity']
            ];
        }

        return [
            'product_items' => json_encode($productItems),
            'logistics_address' => json_encode([
                'contact_person' => $shippingAddress['name'],
                'address' => $shippingAddress['address1'],
                'address2' => $shippingAddress['address2'] ?? '',
                'city' => $shippingAddress['city'],
                'province' => $shippingAddress['state'] ?? '',
                'country' => $shippingAddress['country'],
                'zip' => $shippingAddress['zip'],
                'phone_country' => $shippingAddress['phone_country'] ?? '+1',
                'mobile_no' => $shippingAddress['phone'] ?? ''
            ])
        ];
    }

    /**
     * Get order status descriptions
     */
    public static function getOrderStatuses(): array {
        return [
            'PLACE_ORDER_SUCCESS' => 'Order Placed',
            'IN_CANCEL' => 'Cancellation Requested',
            'WAIT_SELLER_SEND_GOODS' => 'Awaiting Shipment',
            'SELLER_PART_SEND_GOODS' => 'Partially Shipped',
            'WAIT_BUYER_ACCEPT_GOODS' => 'In Transit',
            'FUND_PROCESSING' => 'Processing',
            'FINISH' => 'Completed',
            'IN_FROZEN' => 'Frozen',
            'IN_ISSUE' => 'Dispute',
            'IN_COMPLAINT' => 'Complaint',
            'RISK_CONTROL' => 'Risk Control'
        ];
    }

    /**
     * Common shipping methods
     */
    public static function getShippingMethods(): array {
        return [
            'CAINIAO_STANDARD' => 'Cainiao Standard',
            'CAINIAO_ECONOMY' => 'Cainiao Economy',
            'EMS_ZX_ZX_US' => 'ePacket',
            'YANWEN_JYT' => 'Yanwen Economic',
            'CHINA_POST_REGISTERED' => 'China Post Registered',
            'DHL_EXPRESS' => 'DHL Express',
            'FEDEX_EXPRESS' => 'FedEx Express',
            'UPS_EXPRESS' => 'UPS Express',
            'SF_EXPRESS' => 'SF Express'
        ];
    }
}
