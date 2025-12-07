<?php
/**
 * ShipStation API Integration
 * Multi-carrier shipping platform
 * Documentation: https://www.shipstation.com/docs/api/
 */

namespace WyattXXXCole\Shipping;

class ShipStationAPI {
    private string $apiKey;
    private string $apiSecret;
    private string $apiBase = 'https://ssapi.shipstation.com';

    public function __construct(string $apiKey, string $apiSecret) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $auth = base64_encode($this->apiKey . ':' . $this->apiSecret);
        $headers = [
            'Authorization: Basic ' . $auth,
            'Content-Type: application/json'
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
            throw new \Exception("ShipStation API Error: " . ($decoded['Message'] ?? 'Unknown error'));
        }

        return $decoded ?? [];
    }

    // ═══════════════════════════════════════════════════════════════
    // ORDERS
    // ═══════════════════════════════════════════════════════════════

    public function getOrders(array $params = []): array {
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/orders{$query}");
    }

    public function getOrder(int $orderId): array {
        return $this->request('GET', "/orders/{$orderId}");
    }

    public function createOrder(array $orderData): array {
        return $this->request('POST', '/orders/createorder', $orderData);
    }

    public function createOrders(array $orders): array {
        return $this->request('POST', '/orders/createorders', $orders);
    }

    public function deleteOrder(int $orderId): array {
        return $this->request('DELETE', "/orders/{$orderId}");
    }

    public function holdOrder(int $orderId, string $holdUntil): array {
        return $this->request('POST', "/orders/holduntil", [
            'orderId' => $orderId,
            'holdUntilDate' => $holdUntil
        ]);
    }

    public function restoreFromHold(int $orderId): array {
        return $this->request('POST', "/orders/restorefromhold", ['orderId' => $orderId]);
    }

    public function markAsShipped(int $orderId, string $carrierCode, ?string $trackingNumber = null): array {
        return $this->request('POST', "/orders/markasshipped", [
            'orderId' => $orderId,
            'carrierCode' => $carrierCode,
            'trackingNumber' => $trackingNumber
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPMENTS
    // ═══════════════════════════════════════════════════════════════

    public function getShipments(array $params = []): array {
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/shipments{$query}");
    }

    public function createShipmentLabel(array $shipmentData): array {
        return $this->request('POST', '/shipments/createlabel', $shipmentData);
    }

    public function voidLabel(int $shipmentId): array {
        return $this->request('POST', '/shipments/voidlabel', ['shipmentId' => $shipmentId]);
    }

    public function getRates(array $rateData): array {
        return $this->request('POST', '/shipments/getrates', $rateData);
    }

    // ═══════════════════════════════════════════════════════════════
    // CARRIERS
    // ═══════════════════════════════════════════════════════════════

    public function getCarriers(): array {
        return $this->request('GET', '/carriers');
    }

    public function getCarrier(string $carrierCode): array {
        return $this->request('GET', "/carriers/getcarrier?carrierCode={$carrierCode}");
    }

    public function getCarrierPackages(string $carrierCode): array {
        return $this->request('GET', "/carriers/listpackages?carrierCode={$carrierCode}");
    }

    public function getCarrierServices(string $carrierCode): array {
        return $this->request('GET', "/carriers/listservices?carrierCode={$carrierCode}");
    }

    // ═══════════════════════════════════════════════════════════════
    // STORES
    // ═══════════════════════════════════════════════════════════════

    public function getStores(): array {
        return $this->request('GET', '/stores');
    }

    public function getStore(int $storeId): array {
        return $this->request('GET', "/stores/{$storeId}");
    }

    public function refreshStore(int $storeId): array {
        return $this->request('POST', "/stores/refreshstore?storeId={$storeId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // WAREHOUSES
    // ═══════════════════════════════════════════════════════════════

    public function getWarehouses(): array {
        return $this->request('GET', '/warehouses');
    }

    public function createWarehouse(array $warehouseData): array {
        return $this->request('POST', '/warehouses/createwarehouse', $warehouseData);
    }

    // ═══════════════════════════════════════════════════════════════
    // PRODUCTS
    // ═══════════════════════════════════════════════════════════════

    public function getProducts(array $params = []): array {
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/products{$query}");
    }

    public function getProduct(int $productId): array {
        return $this->request('GET', "/products/{$productId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // CUSTOMERS
    // ═══════════════════════════════════════════════════════════════

    public function getCustomers(array $params = []): array {
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/customers{$query}");
    }

    public function getCustomer(int $customerId): array {
        return $this->request('GET', "/customers/{$customerId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // FULFILLMENTS
    // ═══════════════════════════════════════════════════════════════

    public function getFulfillments(array $params = []): array {
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/fulfillments{$query}");
    }

    // ═══════════════════════════════════════════════════════════════
    // WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    public function getWebhooks(): array {
        return $this->request('GET', '/webhooks');
    }

    public function subscribeWebhook(string $targetUrl, string $event): array {
        return $this->request('POST', '/webhooks/subscribe', [
            'target_url' => $targetUrl,
            'event' => $event,
            'friendly_name' => 'WyattXXXCole_' . $event
        ]);
    }

    public function unsubscribeWebhook(int $webhookId): array {
        return $this->request('DELETE', "/webhooks/{$webhookId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPERS
    // ═══════════════════════════════════════════════════════════════

    public static function buildOrderData(
        string $orderNumber,
        array $shippingAddress,
        array $items,
        array $options = []
    ): array {
        return [
            'orderNumber' => $orderNumber,
            'orderDate' => date('Y-m-d\TH:i:s'),
            'orderStatus' => $options['status'] ?? 'awaiting_shipment',
            'shipTo' => [
                'name' => $shippingAddress['name'],
                'street1' => $shippingAddress['address1'],
                'street2' => $shippingAddress['address2'] ?? '',
                'city' => $shippingAddress['city'],
                'state' => $shippingAddress['state'] ?? '',
                'postalCode' => $shippingAddress['zip'],
                'country' => $shippingAddress['country'],
                'phone' => $shippingAddress['phone'] ?? ''
            ],
            'items' => array_map(function($item) {
                return [
                    'sku' => $item['sku'],
                    'name' => $item['name'],
                    'quantity' => $item['quantity'],
                    'unitPrice' => $item['price'] ?? 0,
                    'weight' => $item['weight'] ?? null
                ];
            }, $items),
            'carrierCode' => $options['carrier'] ?? null,
            'serviceCode' => $options['service'] ?? null
        ];
    }

    public static function getWebhookEvents(): array {
        return [
            'ORDER_NOTIFY' => 'Order created/updated',
            'ITEM_ORDER_NOTIFY' => 'Order item changed',
            'SHIP_NOTIFY' => 'Shipment created',
            'ITEM_SHIP_NOTIFY' => 'Item shipped'
        ];
    }
}
