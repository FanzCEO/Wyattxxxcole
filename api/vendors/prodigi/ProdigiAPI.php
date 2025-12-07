<?php
/**
 * Prodigi API Integration
 * Global print API with white-label fulfillment
 * Documentation: https://www.prodigi.com/print-api/docs/
 */

namespace WyattXXXCole\Vendors\Prodigi;

class ProdigiAPI {
    private string $apiKey;
    private string $apiBase;
    private bool $sandbox;

    public function __construct(string $apiKey, bool $sandbox = false) {
        $this->apiKey = $apiKey;
        $this->sandbox = $sandbox;
        $this->apiBase = $sandbox
            ? 'https://api.sandbox.prodigi.com/v4.0'
            : 'https://api.prodigi.com/v4.0';
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'X-API-Key: ' . $this->apiKey,
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
            throw new \Exception("Prodigi API Error: " . ($decoded['message'] ?? json_encode($decoded)));
        }

        return $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTS
    // ═══════════════════════════════════════════════════════════════

    public function getProducts(?string $sku = null): array {
        $endpoint = '/products';
        if ($sku) $endpoint .= "?sku={$sku}";
        return $this->request('GET', $endpoint);
    }

    public function getProductDetails(string $sku): array {
        return $this->request('GET', "/products/{$sku}");
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

    public function getOrders(?int $top = 100, ?int $skip = 0): array {
        return $this->request('GET', "/orders?\$top={$top}&\$skip={$skip}");
    }

    public function getOrderActions(string $orderId): array {
        return $this->request('GET', "/orders/{$orderId}/actions");
    }

    public function cancelOrder(string $orderId): array {
        return $this->request('POST', "/orders/{$orderId}/actions/cancel");
    }

    // ═══════════════════════════════════════════════════════════════
    // QUOTES
    // ═══════════════════════════════════════════════════════════════

    public function createQuote(array $quoteData): array {
        return $this->request('POST', '/quotes', $quoteData);
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPMENTS
    // ═══════════════════════════════════════════════════════════════

    public function getShipments(string $orderId): array {
        return $this->request('GET', "/orders/{$orderId}/shipments");
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    public static function buildOrderData(
        string $merchantReference,
        array $shippingAddress,
        array $items,
        string $shippingMethod = 'Standard'
    ): array {
        return [
            'merchantReference' => $merchantReference,
            'shippingMethod' => $shippingMethod,
            'recipient' => [
                'name' => $shippingAddress['name'],
                'address' => [
                    'line1' => $shippingAddress['address1'],
                    'line2' => $shippingAddress['address2'] ?? '',
                    'postalOrZipCode' => $shippingAddress['zip'],
                    'townOrCity' => $shippingAddress['city'],
                    'stateOrCounty' => $shippingAddress['state'] ?? '',
                    'countryCode' => $shippingAddress['country']
                ],
                'email' => $shippingAddress['email'] ?? '',
                'phoneNumber' => $shippingAddress['phone'] ?? ''
            ],
            'items' => array_map(function($item) {
                return [
                    'merchantReference' => $item['reference'] ?? uniqid(),
                    'sku' => $item['sku'],
                    'copies' => $item['quantity'],
                    'sizing' => $item['sizing'] ?? 'fillPrintArea',
                    'assets' => $item['assets'] ?? []
                ];
            }, $items)
        ];
    }

    public static function getShippingMethods(): array {
        return [
            'Budget' => 'Budget shipping (7-10 business days)',
            'Standard' => 'Standard shipping (5-7 business days)',
            'Express' => 'Express shipping (2-4 business days)',
            'Overnight' => 'Overnight shipping (1 business day)'
        ];
    }
}
