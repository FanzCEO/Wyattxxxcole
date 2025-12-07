<?php
/**
 * Printful API Integration
 * Full implementation for Print-on-Demand operations
 * Documentation: https://developers.printful.com/docs/
 */

namespace WyattXXXCole\Vendors\Printful;

class PrintfulAPI {
    private string $apiKey;
    private string $apiBase = 'https://api.printful.com';
    private ?string $storeId = null;

    public function __construct(string $apiKey, ?string $storeId = null) {
        $this->apiKey = $apiKey;
        $this->storeId = $storeId;
    }

    /**
     * Make API request to Printful
     */
    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        if ($this->storeId) {
            $headers[] = 'X-PF-Store-Id: ' . $this->storeId;
        }

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
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("Printful API Error: $error");
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $decoded['error']['message'] ?? 'Unknown error';
            throw new \Exception("Printful API Error ($httpCode): $errorMsg");
        }

        return $decoded['result'] ?? $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // STORE MANAGEMENT
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get list of stores
     */
    public function getStores(): array {
        return $this->request('GET', '/stores');
    }

    /**
     * Get store info
     */
    public function getStore(?int $storeId = null): array {
        $endpoint = $storeId ? "/stores/{$storeId}" : '/stores';
        return $this->request('GET', $endpoint);
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCT CATALOG
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all products in catalog
     */
    public function getCatalogProducts(?string $category = null): array {
        $endpoint = '/products';
        if ($category) {
            $endpoint .= '?category_id=' . urlencode($category);
        }
        return $this->request('GET', $endpoint);
    }

    /**
     * Get specific product details
     */
    public function getCatalogProduct(int $productId): array {
        return $this->request('GET', "/products/{$productId}");
    }

    /**
     * Get product variants
     */
    public function getProductVariants(int $productId): array {
        return $this->request('GET', "/products/{$productId}");
    }

    /**
     * Get product categories
     */
    public function getCategories(): array {
        return $this->request('GET', '/categories');
    }

    /**
     * Get printfiles for a product
     */
    public function getPrintfiles(int $productId): array {
        return $this->request('GET', "/mockup-generator/printfiles/{$productId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // SYNC PRODUCTS (Your Store Products)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all sync products
     */
    public function getSyncProducts(int $limit = 100, int $offset = 0): array {
        return $this->request('GET', "/sync/products?limit={$limit}&offset={$offset}");
    }

    /**
     * Get specific sync product
     */
    public function getSyncProduct($productId): array {
        return $this->request('GET', "/sync/products/{$productId}");
    }

    /**
     * Create sync product
     */
    public function createSyncProduct(array $productData): array {
        return $this->request('POST', '/sync/products', $productData);
    }

    /**
     * Update sync product
     */
    public function updateSyncProduct($productId, array $productData): array {
        return $this->request('PUT', "/sync/products/{$productId}", $productData);
    }

    /**
     * Delete sync product
     */
    public function deleteSyncProduct($productId): array {
        return $this->request('DELETE', "/sync/products/{$productId}");
    }

    /**
     * Get sync variant
     */
    public function getSyncVariant(int $variantId): array {
        return $this->request('GET', "/sync/variant/{$variantId}");
    }

    /**
     * Update sync variant
     */
    public function updateSyncVariant(int $variantId, array $variantData): array {
        return $this->request('PUT', "/sync/variant/{$variantId}", $variantData);
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get orders list
     */
    public function getOrders(int $limit = 100, int $offset = 0, string $status = null): array {
        $endpoint = "/orders?limit={$limit}&offset={$offset}";
        if ($status) {
            $endpoint .= "&status={$status}";
        }
        return $this->request('GET', $endpoint);
    }

    /**
     * Get specific order
     */
    public function getOrder($orderId): array {
        return $this->request('GET', "/orders/{$orderId}");
    }

    /**
     * Create order
     */
    public function createOrder(array $orderData, bool $confirm = false): array {
        $endpoint = '/orders';
        if ($confirm) {
            $endpoint .= '?confirm=true';
        }
        return $this->request('POST', $endpoint, $orderData);
    }

    /**
     * Confirm draft order
     */
    public function confirmOrder($orderId): array {
        return $this->request('POST', "/orders/{$orderId}/confirm");
    }

    /**
     * Cancel order
     */
    public function cancelOrder($orderId): array {
        return $this->request('DELETE', "/orders/{$orderId}");
    }

    /**
     * Estimate order costs
     */
    public function estimateOrderCosts(array $orderData): array {
        return $this->request('POST', '/orders/estimate-costs', $orderData);
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPPING
    // ═══════════════════════════════════════════════════════════════

    /**
     * Calculate shipping rates
     */
    public function calculateShipping(array $shippingData): array {
        return $this->request('POST', '/shipping/rates', $shippingData);
    }

    /**
     * Get country list
     */
    public function getCountries(): array {
        return $this->request('GET', '/countries');
    }

    /**
     * Get tax rates
     */
    public function getTaxRates(): array {
        return $this->request('GET', '/tax/rates');
    }

    // ═══════════════════════════════════════════════════════════════
    // MOCKUP GENERATOR
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create mockup generation task
     */
    public function createMockupTask(int $productId, array $files, array $options = []): array {
        $data = [
            'variant_ids' => $options['variant_ids'] ?? [],
            'files' => $files,
            'format' => $options['format'] ?? 'jpg',
            'option_groups' => $options['option_groups'] ?? []
        ];
        return $this->request('POST', "/mockup-generator/create-task/{$productId}", $data);
    }

    /**
     * Get mockup task result
     */
    public function getMockupTaskResult(string $taskKey): array {
        return $this->request('GET', "/mockup-generator/task?task_key={$taskKey}");
    }

    /**
     * Get mockup templates
     */
    public function getMockupTemplates(int $productId): array {
        return $this->request('GET', "/mockup-generator/templates/{$productId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // FILE LIBRARY
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get files from library
     */
    public function getFiles(int $limit = 100, int $offset = 0): array {
        return $this->request('GET', "/files?limit={$limit}&offset={$offset}");
    }

    /**
     * Add file to library
     */
    public function addFile(string $url, string $filename = null): array {
        $data = ['url' => $url];
        if ($filename) {
            $data['filename'] = $filename;
        }
        return $this->request('POST', '/files', $data);
    }

    /**
     * Get file info
     */
    public function getFile(int $fileId): array {
        return $this->request('GET', "/files/{$fileId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get webhook configuration
     */
    public function getWebhooks(): array {
        return $this->request('GET', '/webhooks');
    }

    /**
     * Set webhook URL
     */
    public function setWebhook(string $url, array $types = []): array {
        $data = ['url' => $url];
        if (!empty($types)) {
            $data['types'] = $types;
        }
        return $this->request('POST', '/webhooks', $data);
    }

    /**
     * Disable webhooks
     */
    public function disableWebhooks(): array {
        return $this->request('DELETE', '/webhooks');
    }

    /**
     * Verify webhook signature
     */
    public static function verifyWebhookSignature(string $payload, string $signature, string $secret): bool {
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));
        return hash_equals($expectedSignature, $signature);
    }

    // ═══════════════════════════════════════════════════════════════
    // WAREHOUSE PRODUCTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get warehouse products
     */
    public function getWarehouseProducts(int $limit = 100, int $offset = 0): array {
        return $this->request('GET', "/warehouse/products?limit={$limit}&offset={$offset}");
    }

    /**
     * Get specific warehouse product
     */
    public function getWarehouseProduct(int $productId): array {
        return $this->request('GET', "/warehouse/products/{$productId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // ECOMMERCE PLATFORM SYNC
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get ecommerce platform sync status
     */
    public function getEcommerceSyncStatus(): array {
        return $this->request('GET', '/ecommerce/sync/products');
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build order data structure
     */
    public static function buildOrderData(
        array $recipient,
        array $items,
        array $options = []
    ): array {
        return [
            'recipient' => [
                'name' => $recipient['name'],
                'address1' => $recipient['address1'],
                'address2' => $recipient['address2'] ?? '',
                'city' => $recipient['city'],
                'state_code' => $recipient['state_code'] ?? '',
                'country_code' => $recipient['country_code'],
                'zip' => $recipient['zip'],
                'phone' => $recipient['phone'] ?? '',
                'email' => $recipient['email'] ?? ''
            ],
            'items' => array_map(function($item) {
                return [
                    'variant_id' => $item['variant_id'] ?? null,
                    'sync_variant_id' => $item['sync_variant_id'] ?? null,
                    'external_id' => $item['external_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'files' => $item['files'] ?? [],
                    'options' => $item['options'] ?? []
                ];
            }, $items),
            'retail_costs' => $options['retail_costs'] ?? null,
            'gift' => $options['gift'] ?? null,
            'packing_slip' => $options['packing_slip'] ?? null
        ];
    }

    /**
     * Build file structure for print
     */
    public static function buildFileData(
        string $url,
        string $type = 'default',
        array $position = []
    ): array {
        $file = [
            'type' => $type,
            'url' => $url
        ];

        if (!empty($position)) {
            $file['position'] = $position;
        }

        return $file;
    }
}
