<?php
/**
 * Printify API Integration
 * Full implementation for Print-on-Demand operations
 * Documentation: https://developers.printify.com/docs/
 */

namespace WyattXXXCole\Vendors\Printify;

class PrintifyAPI {
    private string $apiKey;
    private string $apiBase = 'https://api.printify.com/v1';
    private ?string $shopId = null;

    public function __construct(string $apiKey, ?string $shopId = null) {
        $this->apiKey = $apiKey;
        $this->shopId = $shopId;
    }

    /**
     * Make API request to Printify
     */
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
            throw new \Exception("Printify API Error: $error");
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $decoded['message'] ?? $decoded['error'] ?? 'Unknown error';
            throw new \Exception("Printify API Error ($httpCode): $errorMsg");
        }

        return $decoded ?? [];
    }

    // ═══════════════════════════════════════════════════════════════
    // SHOPS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get list of shops
     */
    public function getShops(): array {
        return $this->request('GET', '/shops.json');
    }

    /**
     * Disconnect a shop
     */
    public function disconnectShop(string $shopId): array {
        return $this->request('DELETE', "/shops/{$shopId}/connection.json");
    }

    // ═══════════════════════════════════════════════════════════════
    // CATALOG (Print Providers & Products)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all blueprints (product types)
     */
    public function getBlueprints(): array {
        return $this->request('GET', '/catalog/blueprints.json');
    }

    /**
     * Get specific blueprint
     */
    public function getBlueprint(int $blueprintId): array {
        return $this->request('GET', "/catalog/blueprints/{$blueprintId}.json");
    }

    /**
     * Get print providers for a blueprint
     */
    public function getPrintProviders(int $blueprintId): array {
        return $this->request('GET', "/catalog/blueprints/{$blueprintId}/print_providers.json");
    }

    /**
     * Get variants for blueprint + print provider
     */
    public function getVariants(int $blueprintId, int $printProviderId): array {
        return $this->request('GET', "/catalog/blueprints/{$blueprintId}/print_providers/{$printProviderId}/variants.json");
    }

    /**
     * Get shipping info for blueprint + print provider
     */
    public function getShipping(int $blueprintId, int $printProviderId): array {
        return $this->request('GET', "/catalog/blueprints/{$blueprintId}/print_providers/{$printProviderId}/shipping.json");
    }

    /**
     * Get all print providers
     */
    public function getAllPrintProviders(): array {
        return $this->request('GET', '/catalog/print_providers.json');
    }

    /**
     * Get specific print provider
     */
    public function getPrintProvider(int $printProviderId): array {
        return $this->request('GET', "/catalog/print_providers/{$printProviderId}.json");
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTS (Your Store Products)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get all products in shop
     */
    public function getProducts(?string $shopId = null, int $limit = 100, int $page = 1): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('GET', "/shops/{$shop}/products.json?limit={$limit}&page={$page}");
    }

    /**
     * Get specific product
     */
    public function getProduct(string $productId, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('GET', "/shops/{$shop}/products/{$productId}.json");
    }

    /**
     * Create product
     */
    public function createProduct(array $productData, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/products.json", $productData);
    }

    /**
     * Update product
     */
    public function updateProduct(string $productId, array $productData, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('PUT', "/shops/{$shop}/products/{$productId}.json", $productData);
    }

    /**
     * Delete product
     */
    public function deleteProduct(string $productId, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('DELETE', "/shops/{$shop}/products/{$productId}.json");
    }

    /**
     * Publish product to sales channel
     */
    public function publishProduct(string $productId, array $publishData, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/products/{$productId}/publish.json", $publishData);
    }

    /**
     * Set product publish status succeeded
     */
    public function setPublishSucceeded(string $productId, array $external, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/products/{$productId}/publishing_succeeded.json", $external);
    }

    /**
     * Set product publish status failed
     */
    public function setPublishFailed(string $productId, string $reason, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/products/{$productId}/publishing_failed.json", ['reason' => $reason]);
    }

    /**
     * Unpublish product
     */
    public function unpublishProduct(string $productId, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/products/{$productId}/unpublish.json");
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get orders list
     */
    public function getOrders(?string $shopId = null, int $limit = 100, int $page = 1): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('GET', "/shops/{$shop}/orders.json?limit={$limit}&page={$page}");
    }

    /**
     * Get specific order
     */
    public function getOrder(string $orderId, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('GET', "/shops/{$shop}/orders/{$orderId}.json");
    }

    /**
     * Create order (submit for production)
     */
    public function createOrder(array $orderData, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/orders.json", $orderData);
    }

    /**
     * Send order to production
     */
    public function sendToProduction(string $orderId, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/orders/{$orderId}/send_to_production.json");
    }

    /**
     * Calculate shipping for order
     */
    public function calculateShipping(array $shippingData, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/orders/shipping.json", $shippingData);
    }

    /**
     * Cancel order
     */
    public function cancelOrder(string $orderId, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/orders/{$orderId}/cancel.json");
    }

    // ═══════════════════════════════════════════════════════════════
    // UPLOADS (Images)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get uploaded images
     */
    public function getUploads(int $limit = 100, int $page = 1): array {
        return $this->request('GET', "/uploads.json?limit={$limit}&page={$page}");
    }

    /**
     * Upload image from URL
     */
    public function uploadImage(string $fileName, string $url): array {
        return $this->request('POST', '/uploads/images.json', [
            'file_name' => $fileName,
            'url' => $url
        ]);
    }

    /**
     * Get upload by ID
     */
    public function getUpload(string $uploadId): array {
        return $this->request('GET', "/uploads/{$uploadId}.json");
    }

    /**
     * Archive upload
     */
    public function archiveUpload(string $uploadId): array {
        return $this->request('POST', "/uploads/{$uploadId}/archive.json");
    }

    // ═══════════════════════════════════════════════════════════════
    // WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get webhooks
     */
    public function getWebhooks(?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('GET', "/shops/{$shop}/webhooks.json");
    }

    /**
     * Create webhook
     */
    public function createWebhook(string $topic, string $url, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('POST', "/shops/{$shop}/webhooks.json", [
            'topic' => $topic,
            'url' => $url
        ]);
    }

    /**
     * Update webhook
     */
    public function updateWebhook(string $webhookId, string $url, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('PUT', "/shops/{$shop}/webhooks/{$webhookId}.json", ['url' => $url]);
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(string $webhookId, ?string $shopId = null): array {
        $shop = $shopId ?? $this->shopId;
        return $this->request('DELETE', "/shops/{$shop}/webhooks/{$webhookId}.json");
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build product data structure
     */
    public static function buildProductData(
        string $title,
        string $description,
        int $blueprintId,
        int $printProviderId,
        array $variants,
        array $printAreas
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'blueprint_id' => $blueprintId,
            'print_provider_id' => $printProviderId,
            'variants' => $variants,
            'print_areas' => $printAreas
        ];
    }

    /**
     * Build order data structure
     */
    public static function buildOrderData(
        string $externalId,
        array $lineItems,
        array $shippingAddress,
        string $shippingMethod = 'standard'
    ): array {
        return [
            'external_id' => $externalId,
            'label' => "Order {$externalId}",
            'line_items' => array_map(function($item) {
                return [
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity']
                ];
            }, $lineItems),
            'shipping_method' => $shippingMethod === 'express' ? 2 : 1,
            'address_to' => [
                'first_name' => $shippingAddress['first_name'],
                'last_name' => $shippingAddress['last_name'],
                'email' => $shippingAddress['email'],
                'phone' => $shippingAddress['phone'] ?? '',
                'country' => $shippingAddress['country'],
                'region' => $shippingAddress['state'] ?? '',
                'address1' => $shippingAddress['address1'],
                'address2' => $shippingAddress['address2'] ?? '',
                'city' => $shippingAddress['city'],
                'zip' => $shippingAddress['zip']
            ]
        ];
    }

    /**
     * Available webhook topics
     */
    public static function getWebhookTopics(): array {
        return [
            'product:deleted',
            'product:publish:started',
            'order:created',
            'order:updated',
            'order:sent-to-production',
            'order:shipment:created',
            'order:shipment:delivered'
        ];
    }
}
