<?php
/**
 * Gooten API Integration
 * Full implementation for Print-on-Demand operations
 * Documentation: https://www.gooten.com/api/
 */

namespace WyattXXXCole\Vendors\Gooten;

class GootenAPI {
    private string $apiKey;
    private string $recipeId;
    private string $apiBase = 'https://api.print.io/api/v/5';

    public function __construct(string $apiKey, string $recipeId) {
        $this->apiKey = $apiKey;
        $this->recipeId = $recipeId;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $separator = strpos($url, '?') !== false ? '&' : '?';
        $url .= $separator . 'recipeId=' . $this->recipeId;

        $headers = [
            'Authorization: ' . $this->apiKey,
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
            throw new \Exception("Gooten API Error: " . ($decoded['ErrorMessage'] ?? 'Unknown error'));
        }

        return $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all products
     */
    public function getProducts(string $countryCode = 'US'): array {
        return $this->request('GET', "/source/api/products?countryCode={$countryCode}");
    }

    /**
     * Get product variants
     */
    public function getProductVariants(int $productId): array {
        return $this->request('GET', "/source/api/productvariants?productId={$productId}");
    }

    /**
     * Get product templates
     */
    public function getProductTemplates(string $sku): array {
        return $this->request('GET', "/source/api/producttemplates?sku={$sku}");
    }

    /**
     * Get product preview images
     */
    public function getProductPreviews(int $productId): array {
        return $this->request('GET', "/source/api/productpreviewimages?productId={$productId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create order
     */
    public function createOrder(array $orderData): array {
        return $this->request('POST', '/source/api/orders', $orderData);
    }

    /**
     * Get order
     */
    public function getOrder(string $orderId): array {
        return $this->request('GET', "/source/api/orders?id={$orderId}");
    }

    /**
     * Get orders
     */
    public function getOrders(?string $startDate = null, ?string $endDate = null): array {
        $params = [];
        if ($startDate) $params['startDate'] = $startDate;
        if ($endDate) $params['endDate'] = $endDate;

        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/source/api/orders/search{$query}");
    }

    /**
     * Submit order for production
     */
    public function submitOrder(string $orderId): array {
        return $this->request('POST', "/source/api/orders/{$orderId}/submit");
    }

    /**
     * Cancel order
     */
    public function cancelOrder(string $orderId): array {
        return $this->request('DELETE', "/source/api/orders/{$orderId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // PRICING & SHIPPING
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get shipping prices
     */
    public function getShippingPrices(array $orderData): array {
        return $this->request('POST', '/source/api/shippingprices', $orderData);
    }

    /**
     * Get order price estimate
     */
    public function getPriceEstimate(array $orderData): array {
        return $this->request('POST', '/source/api/priceestimate', $orderData);
    }

    // ═══════════════════════════════════════════════════════════════
    // COUNTRIES & CURRENCIES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get countries
     */
    public function getCountries(): array {
        return $this->request('GET', '/source/api/countries');
    }

    /**
     * Get currencies
     */
    public function getCurrencies(): array {
        return $this->request('GET', '/source/api/currencies');
    }

    // ═══════════════════════════════════════════════════════════════
    // IMAGES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Upload image from URL
     */
    public function uploadImage(string $url): array {
        return $this->request('POST', '/source/api/images', ['Url' => $url]);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build order data structure
     */
    public static function buildOrderData(
        array $shipToAddress,
        array $items,
        string $shippingMethod = 'Standard'
    ): array {
        return [
            'ShipToAddress' => [
                'FirstName' => $shipToAddress['first_name'],
                'LastName' => $shipToAddress['last_name'],
                'Line1' => $shipToAddress['address1'],
                'Line2' => $shipToAddress['address2'] ?? '',
                'City' => $shipToAddress['city'],
                'State' => $shipToAddress['state'] ?? '',
                'PostalCode' => $shipToAddress['zip'],
                'CountryCode' => $shipToAddress['country'],
                'Phone' => $shipToAddress['phone'] ?? '',
                'Email' => $shipToAddress['email'] ?? ''
            ],
            'Items' => array_map(function($item) {
                return [
                    'Quantity' => $item['quantity'],
                    'SKU' => $item['sku'],
                    'ShipCarrierMethodId' => $item['shipping_method_id'] ?? 1,
                    'Images' => array_map(function($img) {
                        return [
                            'Url' => $img['url'],
                            'Index' => $img['index'] ?? 0,
                            'ThumbnailUrl' => $img['thumbnail_url'] ?? $img['url'],
                            'ManipCommand' => $img['manip_command'] ?? ''
                        ];
                    }, $item['images'] ?? [])
                ];
            }, $items),
            'Payment' => [
                'CurrencyCode' => 'USD'
            ],
            'Meta' => [
                'IsPreSubmit' => false
            ]
        ];
    }
}
