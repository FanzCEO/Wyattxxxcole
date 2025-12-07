<?php
/**
 * CJ Dropshipping API Integration
 * Full implementation for Dropshipping operations
 * Documentation: https://developers.cjdropshipping.com/
 */

namespace WyattXXXCole\Vendors\CJDropshipping;

class CJDropshippingAPI {
    private string $apiKey;
    private string $email;
    private string $apiBase = 'https://developers.cjdropshipping.com/api2.0/v1';
    private ?string $accessToken = null;

    public function __construct(string $apiKey, string $email) {
        $this->apiKey = $apiKey;
        $this->email = $email;
    }

    /**
     * Make API request to CJ Dropshipping
     */
    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'CJ-Access-Token: ' . ($this->accessToken ?? $this->apiKey),
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("CJ Dropshipping API Error: $error");
        }

        $decoded = json_decode($response, true);

        if (!$decoded['result']) {
            $errorMsg = $decoded['message'] ?? 'Unknown error';
            throw new \Exception("CJ Dropshipping API Error: $errorMsg");
        }

        return $decoded['data'] ?? $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // AUTHENTICATION
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get access token
     */
    public function getAccessToken(): string {
        $data = [
            'email' => $this->email,
            'password' => $this->apiKey
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiBase . '/authentication/getAccessToken');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($decoded['result'] && isset($decoded['data']['accessToken'])) {
            $this->accessToken = $decoded['data']['accessToken'];
            return $this->accessToken;
        }

        throw new \Exception("Failed to get access token: " . ($decoded['message'] ?? 'Unknown error'));
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCT SEARCH
    // ═══════════════════════════════════════════════════════════════

    /**
     * Search products by keyword
     */
    public function searchProducts(
        string $keyword,
        int $pageNum = 1,
        int $pageSize = 20,
        ?string $categoryId = null
    ): array {
        $params = [
            'productNameEn' => $keyword,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize
        ];

        if ($categoryId) {
            $params['categoryId'] = $categoryId;
        }

        return $this->request('GET', '/product/list?' . http_build_query($params));
    }

    /**
     * Get product details
     */
    public function getProduct(string $pid): array {
        return $this->request('GET', "/product/query?pid={$pid}");
    }

    /**
     * Get product variants
     */
    public function getProductVariants(string $pid): array {
        return $this->request('GET', "/product/variant/query?pid={$pid}");
    }

    /**
     * Get product categories
     */
    public function getCategories(?string $parentId = null): array {
        $endpoint = '/product/getCategory';
        if ($parentId) {
            $endpoint .= "?pid={$parentId}";
        }
        return $this->request('GET', $endpoint);
    }

    // ═══════════════════════════════════════════════════════════════
    // INVENTORY
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get product inventory
     */
    public function getInventory(string $vid): array {
        return $this->request('GET', "/product/stock?vid={$vid}");
    }

    /**
     * Get multiple product inventory
     */
    public function getBatchInventory(array $vids): array {
        return $this->request('POST', '/product/stock/queryByVids', ['vids' => $vids]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create order
     */
    public function createOrder(array $orderData): array {
        return $this->request('POST', '/shopping/order/createOrder', $orderData);
    }

    /**
     * Create order by product ID
     */
    public function createOrderByProduct(array $orderData): array {
        return $this->request('POST', '/shopping/order/createOrderByProductSku', $orderData);
    }

    /**
     * Get order list
     */
    public function getOrders(
        int $pageNum = 1,
        int $pageSize = 20,
        ?string $status = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): array {
        $params = [
            'pageNum' => $pageNum,
            'pageSize' => $pageSize
        ];

        if ($status) $params['orderStatus'] = $status;
        if ($startDate) $params['startDate'] = $startDate;
        if ($endDate) $params['endDate'] = $endDate;

        return $this->request('GET', '/shopping/order/list?' . http_build_query($params));
    }

    /**
     * Get order details
     */
    public function getOrder(string $orderId): array {
        return $this->request('GET', "/shopping/order/getOrderDetail?orderId={$orderId}");
    }

    /**
     * Cancel order
     */
    public function cancelOrder(string $orderId): array {
        return $this->request('DELETE', "/shopping/order/deleteOrder?orderId={$orderId}");
    }

    /**
     * Confirm order
     */
    public function confirmOrder(string $orderId): array {
        return $this->request('PATCH', "/shopping/order/confirmOrder?orderId={$orderId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPPING
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get shipping methods
     */
    public function getShippingMethods(
        string $startCountryCode,
        string $endCountryCode,
        ?string $productWeight = null
    ): array {
        $params = [
            'startCountryCode' => $startCountryCode,
            'endCountryCode' => $endCountryCode
        ];

        if ($productWeight) {
            $params['productWeight'] = $productWeight;
        }

        return $this->request('GET', '/logistic/freightCalculate?' . http_build_query($params));
    }

    /**
     * Calculate shipping cost
     */
    public function calculateShipping(array $shippingData): array {
        return $this->request('POST', '/logistic/freightCalculate', $shippingData);
    }

    /**
     * Get tracking info
     */
    public function getTracking(string $orderId): array {
        return $this->request('GET', "/logistic/getTrackInfo?orderId={$orderId}");
    }

    /**
     * Get tracking by number
     */
    public function getTrackingByNumber(string $trackingNumber): array {
        return $this->request('GET', "/logistic/getTrackInfo?trackNumber={$trackingNumber}");
    }

    // ═══════════════════════════════════════════════════════════════
    // DISPUTES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create dispute
     */
    public function createDispute(array $disputeData): array {
        return $this->request('POST', '/shopping/dispute/createDispute', $disputeData);
    }

    /**
     * Get disputes
     */
    public function getDisputes(int $pageNum = 1, int $pageSize = 20): array {
        return $this->request('GET', "/shopping/dispute/list?pageNum={$pageNum}&pageSize={$pageSize}");
    }

    // ═══════════════════════════════════════════════════════════════
    // SOURCING
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create sourcing request
     */
    public function createSourcingRequest(array $requestData): array {
        return $this->request('POST', '/product/sourcing/createSourcingRequest', $requestData);
    }

    /**
     * Get sourcing requests
     */
    public function getSourcingRequests(int $pageNum = 1, int $pageSize = 20): array {
        return $this->request('GET', "/product/sourcing/list?pageNum={$pageNum}&pageSize={$pageSize}");
    }

    // ═══════════════════════════════════════════════════════════════
    // WALLET
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get wallet balance
     */
    public function getBalance(): array {
        return $this->request('GET', '/shopping/pay/getBalance');
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build order data structure
     */
    public static function buildOrderData(
        string $orderNumber,
        array $shippingAddress,
        array $products,
        string $shippingMethod = 'CJPacket Ordinary'
    ): array {
        return [
            'orderNumber' => $orderNumber,
            'shippingCountryCode' => $shippingAddress['country_code'],
            'shippingCountry' => $shippingAddress['country'],
            'shippingProvince' => $shippingAddress['state'] ?? '',
            'shippingCity' => $shippingAddress['city'],
            'shippingAddress' => $shippingAddress['address1'],
            'shippingAddress2' => $shippingAddress['address2'] ?? '',
            'shippingCustomerName' => $shippingAddress['name'],
            'shippingZip' => $shippingAddress['zip'],
            'shippingPhone' => $shippingAddress['phone'] ?? '',
            'logisticName' => $shippingMethod,
            'remark' => '',
            'products' => array_map(function($p) {
                return [
                    'vid' => $p['variant_id'],
                    'quantity' => $p['quantity']
                ];
            }, $products)
        ];
    }

    /**
     * Order status codes
     */
    public static function getOrderStatuses(): array {
        return [
            'CREATED' => 'Order Created',
            'IN_CART' => 'In Cart',
            'UNPAID' => 'Waiting Payment',
            'UNSHIPPED' => 'Pending Fulfillment',
            'SHIPPED' => 'Shipped',
            'DELIVERED' => 'Delivered',
            'CANCELLED' => 'Cancelled'
        ];
    }
}
