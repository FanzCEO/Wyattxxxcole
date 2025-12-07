<?php
/**
 * Spocket API Integration
 * Full implementation for Dropshipping operations
 * Documentation: https://spocket.co/integrations
 */

namespace WyattXXXCole\Vendors\Spocket;

class SpocketAPI {
    private string $apiKey;
    private string $apiBase = 'https://api.spocket.co/v1';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new \Exception("Spocket API Error: " . ($decoded['message'] ?? 'Unknown error'));
        }

        return $decoded['data'] ?? $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Search products
     */
    public function searchProducts(
        string $query,
        ?string $category = null,
        ?string $country = null,
        int $page = 1,
        int $limit = 20
    ): array {
        $params = [
            'q' => $query,
            'page' => $page,
            'limit' => $limit
        ];

        if ($category) $params['category'] = $category;
        if ($country) $params['shipping_country'] = $country;

        return $this->request('GET', '/products?' . http_build_query($params));
    }

    /**
     * Get product details
     */
    public function getProduct(string $productId): array {
        return $this->request('GET', "/products/{$productId}");
    }

    /**
     * Get imported products
     */
    public function getImportedProducts(int $page = 1, int $limit = 20): array {
        return $this->request('GET', "/products/imported?page={$page}&limit={$limit}");
    }

    /**
     * Import product to store
     */
    public function importProduct(string $productId): array {
        return $this->request('POST', "/products/{$productId}/import");
    }

    /**
     * Get product categories
     */
    public function getCategories(): array {
        return $this->request('GET', '/categories');
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create order
     */
    public function createOrder(array $orderData): array {
        return $this->request('POST', '/orders', $orderData);
    }

    /**
     * Get order
     */
    public function getOrder(string $orderId): array {
        return $this->request('GET', "/orders/{$orderId}");
    }

    /**
     * Get orders
     */
    public function getOrders(int $page = 1, int $limit = 20, ?string $status = null): array {
        $params = ['page' => $page, 'limit' => $limit];
        if ($status) $params['status'] = $status;
        return $this->request('GET', '/orders?' . http_build_query($params));
    }

    /**
     * Pay for order
     */
    public function payOrder(string $orderId): array {
        return $this->request('POST', "/orders/{$orderId}/pay");
    }

    /**
     * Cancel order
     */
    public function cancelOrder(string $orderId): array {
        return $this->request('POST', "/orders/{$orderId}/cancel");
    }

    /**
     * Get order tracking
     */
    public function getOrderTracking(string $orderId): array {
        return $this->request('GET', "/orders/{$orderId}/tracking");
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPPING
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get shipping rates
     */
    public function getShippingRates(string $productId, string $countryCode): array {
        return $this->request('GET', "/products/{$productId}/shipping?country={$countryCode}");
    }

    // ═══════════════════════════════════════════════════════════════
    // STORE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get store info
     */
    public function getStore(): array {
        return $this->request('GET', '/store');
    }

    /**
     * Get store settings
     */
    public function getStoreSettings(): array {
        return $this->request('GET', '/store/settings');
    }

    /**
     * Update store settings
     */
    public function updateStoreSettings(array $settings): array {
        return $this->request('PUT', '/store/settings', $settings);
    }

    // ═══════════════════════════════════════════════════════════════
    // SUPPLIERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get suppliers
     */
    public function getSuppliers(): array {
        return $this->request('GET', '/suppliers');
    }

    /**
     * Get supplier details
     */
    public function getSupplier(string $supplierId): array {
        return $this->request('GET', "/suppliers/{$supplierId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build order data structure
     */
    public static function buildOrderData(
        string $externalOrderId,
        array $shippingAddress,
        array $items
    ): array {
        return [
            'external_order_id' => $externalOrderId,
            'shipping_address' => [
                'first_name' => $shippingAddress['first_name'],
                'last_name' => $shippingAddress['last_name'],
                'address1' => $shippingAddress['address1'],
                'address2' => $shippingAddress['address2'] ?? '',
                'city' => $shippingAddress['city'],
                'province' => $shippingAddress['state'] ?? '',
                'postal_code' => $shippingAddress['zip'],
                'country_code' => $shippingAddress['country'],
                'phone' => $shippingAddress['phone'] ?? '',
                'email' => $shippingAddress['email'] ?? ''
            ],
            'line_items' => array_map(function($item) {
                return [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity']
                ];
            }, $items)
        ];
    }

    /**
     * Order statuses
     */
    public static function getOrderStatuses(): array {
        return [
            'pending' => 'Order pending payment',
            'paid' => 'Order paid, awaiting fulfillment',
            'processing' => 'Order being processed',
            'shipped' => 'Order shipped',
            'delivered' => 'Order delivered',
            'cancelled' => 'Order cancelled',
            'refunded' => 'Order refunded'
        ];
    }
}
