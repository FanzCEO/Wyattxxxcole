<?php
/**
 * CustomCat API Integration
 * Full implementation for Print-on-Demand operations
 * Documentation: https://customcat.com/api-documentation/
 */

namespace WyattXXXCole\Vendors\CustomCat;

class CustomCatAPI {
    private string $apiKey;
    private string $apiBase = 'https://api.customcat.com/v1';

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

        if ($httpCode >= 400 || (isset($decoded['success']) && !$decoded['success'])) {
            throw new \Exception("CustomCat API Error: " . ($decoded['message'] ?? 'Unknown error'));
        }

        return $decoded['data'] ?? $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all products
     */
    public function getProducts(): array {
        return $this->request('GET', '/products');
    }

    /**
     * Get product by ID
     */
    public function getProduct(int $productId): array {
        return $this->request('GET', "/products/{$productId}");
    }

    /**
     * Get product variants
     */
    public function getProductVariants(int $productId): array {
        return $this->request('GET', "/products/{$productId}/variants");
    }

    /**
     * Get product categories
     */
    public function getCategories(): array {
        return $this->request('GET', '/categories');
    }

    // ═══════════════════════════════════════════════════════════════
    // DESIGNS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Upload design
     */
    public function uploadDesign(string $imageUrl, string $name): array {
        return $this->request('POST', '/designs', [
            'image_url' => $imageUrl,
            'name' => $name
        ]);
    }

    /**
     * Get designs
     */
    public function getDesigns(): array {
        return $this->request('GET', '/designs');
    }

    /**
     * Get design
     */
    public function getDesign(int $designId): array {
        return $this->request('GET', "/designs/{$designId}");
    }

    /**
     * Delete design
     */
    public function deleteDesign(int $designId): array {
        return $this->request('DELETE', "/designs/{$designId}");
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
    public function getOrders(?int $page = 1, ?int $perPage = 25): array {
        return $this->request('GET', "/orders?page={$page}&per_page={$perPage}");
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
    public function getShippingRates(array $shippingData): array {
        return $this->request('POST', '/shipping/rates', $shippingData);
    }

    /**
     * Get shipping methods
     */
    public function getShippingMethods(): array {
        return $this->request('GET', '/shipping/methods');
    }

    // ═══════════════════════════════════════════════════════════════
    // MOCKUPS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Generate mockup
     */
    public function generateMockup(int $productId, int $variantId, string $designUrl): array {
        return $this->request('POST', '/mockups', [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'design_url' => $designUrl
        ]);
    }

    /**
     * Get mockup status
     */
    public function getMockupStatus(string $mockupId): array {
        return $this->request('GET', "/mockups/{$mockupId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build order data structure
     */
    public static function buildOrderData(
        string $externalId,
        array $shippingAddress,
        array $items,
        string $shippingMethod = 'standard'
    ): array {
        return [
            'external_id' => $externalId,
            'shipping_address' => [
                'name' => $shippingAddress['name'],
                'address1' => $shippingAddress['address1'],
                'address2' => $shippingAddress['address2'] ?? '',
                'city' => $shippingAddress['city'],
                'state' => $shippingAddress['state'] ?? '',
                'zip' => $shippingAddress['zip'],
                'country' => $shippingAddress['country'],
                'phone' => $shippingAddress['phone'] ?? '',
                'email' => $shippingAddress['email'] ?? ''
            ],
            'shipping_method' => $shippingMethod,
            'items' => array_map(function($item) {
                return [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'design_url' => $item['design_url'],
                    'design_placement' => $item['placement'] ?? 'front'
                ];
            }, $items)
        ];
    }
}
