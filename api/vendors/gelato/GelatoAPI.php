<?php
/**
 * Gelato API Integration
 * Global Print-on-Demand network with 100+ production facilities
 * Documentation: https://developers.gelato.com/
 */

namespace WyattXXXCole\Vendors\Gelato;

class GelatoAPI {
    private string $apiKey;
    private string $apiBase = 'https://api.gelato.com/v3';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'X-API-KEY: ' . $this->apiKey,
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
            throw new \Exception("Gelato API Error: " . ($decoded['message'] ?? 'Unknown error'));
        }

        return $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTS
    // ═══════════════════════════════════════════════════════════════

    public function getCatalog(?string $country = null): array {
        $endpoint = '/products';
        if ($country) $endpoint .= "?country={$country}";
        return $this->request('GET', $endpoint);
    }

    public function getProduct(string $productUid): array {
        return $this->request('GET', "/products/{$productUid}");
    }

    public function getProductPrices(string $productUid, string $country): array {
        return $this->request('GET', "/products/{$productUid}/prices?country={$country}");
    }

    public function getCoverDimensions(string $productUid): array {
        return $this->request('GET', "/products/{$productUid}/cover-dimensions");
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDERS
    // ═══════════════════════════════════════════════════════════════

    public function createOrder(array $orderData): array {
        return $this->request('POST', '/orders', $orderData);
    }

    public function getOrder(string $orderId): array {
        return $this->request('GET', "/orders/{$orderId}");
    }

    public function getOrders(?int $limit = 100, ?int $offset = 0): array {
        return $this->request('GET', "/orders?limit={$limit}&offset={$offset}");
    }

    public function cancelOrder(string $orderId): array {
        return $this->request('DELETE', "/orders/{$orderId}");
    }

    public function quoteOrder(array $orderData): array {
        return $this->request('POST', '/orders/quote', $orderData);
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPMENTS
    // ═══════════════════════════════════════════════════════════════

    public function getShipmentMethods(string $country): array {
        return $this->request('GET', "/shipment-methods?country={$country}");
    }

    // ═══════════════════════════════════════════════════════════════
    // STORES
    // ═══════════════════════════════════════════════════════════════

    public function getStores(): array {
        return $this->request('GET', '/stores');
    }

    public function createStore(array $storeData): array {
        return $this->request('POST', '/stores', $storeData);
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    public static function buildOrderData(
        string $orderReferenceId,
        array $shippingAddress,
        array $items,
        string $shippingMethod = 'normal'
    ): array {
        return [
            'orderReferenceId' => $orderReferenceId,
            'customerReferenceId' => $shippingAddress['customer_id'] ?? $orderReferenceId,
            'currency' => 'USD',
            'items' => array_map(function($item) {
                return [
                    'itemReferenceId' => $item['reference_id'] ?? uniqid(),
                    'productUid' => $item['product_uid'],
                    'files' => $item['files'] ?? [],
                    'quantity' => $item['quantity']
                ];
            }, $items),
            'shippingAddress' => [
                'firstName' => $shippingAddress['first_name'],
                'lastName' => $shippingAddress['last_name'],
                'addressLine1' => $shippingAddress['address1'],
                'addressLine2' => $shippingAddress['address2'] ?? '',
                'city' => $shippingAddress['city'],
                'state' => $shippingAddress['state'] ?? '',
                'postCode' => $shippingAddress['zip'],
                'country' => $shippingAddress['country'],
                'email' => $shippingAddress['email'] ?? '',
                'phone' => $shippingAddress['phone'] ?? ''
            ],
            'shipmentMethodUid' => $shippingMethod
        ];
    }
}
