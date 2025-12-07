<?php
/**
 * Cryptocurrency Payment API Integration
 * Supports: NOWPayments, Plisio, BTCPay Server, Coinbase Commerce
 * All adult-friendly cryptocurrency payment processors
 */

namespace WyattXXXCole\Payments;

// ═══════════════════════════════════════════════════════════════════════════
// NOWPAYMENTS API (Adult-Friendly)
// Documentation: https://nowpayments.io/help/api-documentation
// ═══════════════════════════════════════════════════════════════════════════

class NOWPaymentsAPI {
    private string $apiKey;
    private string $apiBase = 'https://api.nowpayments.io/v1';
    private ?string $ipnSecret = null;

    public function __construct(string $apiKey, ?string $ipnSecret = null) {
        $this->apiKey = $apiKey;
        $this->ipnSecret = $ipnSecret;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'x-api-key: ' . $this->apiKey,
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
            throw new \Exception("NOWPayments Error: " . ($decoded['message'] ?? 'Unknown error'));
        }

        return $decoded;
    }

    /**
     * Get API status
     */
    public function getStatus(): array {
        return $this->request('GET', '/status');
    }

    /**
     * Get available currencies
     */
    public function getCurrencies(): array {
        return $this->request('GET', '/currencies');
    }

    /**
     * Get minimum payment amount
     */
    public function getMinimumAmount(string $currencyFrom, ?string $currencyTo = null): array {
        $endpoint = "/min-amount?currency_from={$currencyFrom}";
        if ($currencyTo) {
            $endpoint .= "&currency_to={$currencyTo}";
        }
        return $this->request('GET', $endpoint);
    }

    /**
     * Get estimated price
     */
    public function getEstimatedPrice(float $amount, string $currencyFrom, string $currencyTo): array {
        return $this->request('GET', "/estimate?amount={$amount}&currency_from={$currencyFrom}&currency_to={$currencyTo}");
    }

    /**
     * Create payment
     */
    public function createPayment(
        float $priceAmount,
        string $priceCurrency,
        string $payCurrency,
        string $orderId,
        ?string $orderDescription = null,
        ?string $ipnCallbackUrl = null,
        ?string $successUrl = null,
        ?string $cancelUrl = null
    ): array {
        $data = [
            'price_amount' => $priceAmount,
            'price_currency' => $priceCurrency,
            'pay_currency' => $payCurrency,
            'order_id' => $orderId
        ];

        if ($orderDescription) $data['order_description'] = $orderDescription;
        if ($ipnCallbackUrl) $data['ipn_callback_url'] = $ipnCallbackUrl;
        if ($successUrl) $data['success_url'] = $successUrl;
        if ($cancelUrl) $data['cancel_url'] = $cancelUrl;

        return $this->request('POST', '/payment', $data);
    }

    /**
     * Create invoice
     */
    public function createInvoice(
        float $priceAmount,
        string $priceCurrency,
        string $orderId,
        ?string $orderDescription = null,
        ?string $successUrl = null,
        ?string $cancelUrl = null
    ): array {
        $data = [
            'price_amount' => $priceAmount,
            'price_currency' => $priceCurrency,
            'order_id' => $orderId
        ];

        if ($orderDescription) $data['order_description'] = $orderDescription;
        if ($successUrl) $data['success_url'] = $successUrl;
        if ($cancelUrl) $data['cancel_url'] = $cancelUrl;

        return $this->request('POST', '/invoice', $data);
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId): array {
        return $this->request('GET', "/payment/{$paymentId}");
    }

    /**
     * Get payments list
     */
    public function getPayments(int $limit = 10, int $page = 0, ?string $orderId = null): array {
        $params = "limit={$limit}&page={$page}";
        if ($orderId) $params .= "&order_id={$orderId}";
        return $this->request('GET', "/payment/?{$params}");
    }

    /**
     * Verify IPN signature
     */
    public function verifyIPN(string $payload, string $signature): bool {
        if (!$this->ipnSecret) return false;
        $expectedSignature = hash_hmac('sha512', $payload, $this->ipnSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Supported cryptocurrencies
     */
    public static function getSupportedCryptos(): array {
        return [
            'btc' => 'Bitcoin',
            'eth' => 'Ethereum',
            'ltc' => 'Litecoin',
            'xrp' => 'Ripple',
            'bch' => 'Bitcoin Cash',
            'doge' => 'Dogecoin',
            'usdt' => 'Tether (USDT)',
            'usdc' => 'USD Coin',
            'bnb' => 'Binance Coin',
            'trx' => 'Tron',
            'sol' => 'Solana',
            'ada' => 'Cardano',
            'dot' => 'Polkadot',
            'matic' => 'Polygon',
            'avax' => 'Avalanche',
            'xlm' => 'Stellar',
            'xmr' => 'Monero',
            'dai' => 'DAI',
            'shib' => 'Shiba Inu'
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// PLISIO API (Adult-Friendly)
// Documentation: https://plisio.net/documentation
// ═══════════════════════════════════════════════════════════════════════════

class PlisioAPI {
    private string $apiKey;
    private string $apiBase = 'https://plisio.net/api/v1';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    private function request(string $endpoint, array $params = []): array {
        $params['api_key'] = $this->apiKey;
        $url = $this->apiBase . $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if ($decoded['status'] === 'error') {
            throw new \Exception("Plisio Error: " . ($decoded['data']['message'] ?? 'Unknown error'));
        }

        return $decoded['data'] ?? $decoded;
    }

    /**
     * Get balance
     */
    public function getBalance(string $currency): array {
        return $this->request('/balances/' . $currency);
    }

    /**
     * Get currencies
     */
    public function getCurrencies(string $fiatCurrency = 'USD'): array {
        return $this->request('/currencies/' . $fiatCurrency);
    }

    /**
     * Create invoice
     */
    public function createInvoice(
        string $currency,
        string $orderId,
        float $amount,
        string $orderName,
        ?string $email = null,
        ?string $callbackUrl = null,
        ?string $successUrl = null,
        ?string $failUrl = null
    ): array {
        $params = [
            'currency' => $currency,
            'order_number' => $orderId,
            'amount' => $amount,
            'order_name' => $orderName,
            'source_currency' => 'USD'
        ];

        if ($email) $params['email'] = $email;
        if ($callbackUrl) $params['callback_url'] = $callbackUrl;
        if ($successUrl) $params['success_callback_url'] = $successUrl;
        if ($failUrl) $params['fail_callback_url'] = $failUrl;

        return $this->request('/invoices/new', $params);
    }

    /**
     * Get invoice info
     */
    public function getInvoice(string $invoiceId): array {
        return $this->request('/operations/' . $invoiceId);
    }

    /**
     * Get invoice commission
     */
    public function getCommission(string $currency, float $amount, string $feePlan = 'normal'): array {
        return $this->request('/operations/commission', [
            'psys_cid' => $currency,
            'source_amount' => $amount,
            'source_currency' => 'USD',
            'fee_plan' => $feePlan
        ]);
    }

    /**
     * Withdraw funds
     */
    public function withdraw(string $currency, float $amount, string $address, string $feePlan = 'normal'): array {
        return $this->request('/operations/withdraw', [
            'psys_cid' => $currency,
            'amount' => $amount,
            'to' => $address,
            'fee_plan' => $feePlan
        ]);
    }

    /**
     * Verify callback signature
     */
    public function verifyCallback(array $data, string $signature): bool {
        ksort($data);
        $signString = json_encode($data, JSON_UNESCAPED_UNICODE);
        $expectedSignature = hash_hmac('sha1', $signString, $this->apiKey);
        return hash_equals($expectedSignature, $signature);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// BTCPAY SERVER API (Self-Hosted)
// Documentation: https://docs.btcpayserver.org/API/Greenfield/v1/
// ═══════════════════════════════════════════════════════════════════════════

class BTCPayServerAPI {
    private string $host;
    private string $apiKey;
    private string $storeId;

    public function __construct(string $host, string $apiKey, string $storeId) {
        $this->host = rtrim($host, '/');
        $this->apiKey = $apiKey;
        $this->storeId = $storeId;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->host . '/api/v1' . $endpoint;

        $headers = [
            'Authorization: token ' . $this->apiKey,
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
            throw new \Exception("BTCPay Error: " . ($decoded['message'] ?? json_encode($decoded)));
        }

        return $decoded ?? [];
    }

    /**
     * Get server info
     */
    public function getServerInfo(): array {
        return $this->request('GET', '/server/info');
    }

    /**
     * Get store info
     */
    public function getStore(): array {
        return $this->request('GET', "/stores/{$this->storeId}");
    }

    /**
     * Create invoice
     */
    public function createInvoice(
        float $amount,
        string $currency,
        ?string $orderId = null,
        ?string $buyerEmail = null,
        ?string $redirectUrl = null,
        ?array $metadata = null
    ): array {
        $data = [
            'amount' => $amount,
            'currency' => $currency,
            'checkout' => [
                'speedPolicy' => 'HighSpeed'
            ]
        ];

        if ($orderId) $data['metadata']['orderId'] = $orderId;
        if ($buyerEmail) $data['metadata']['buyerEmail'] = $buyerEmail;
        if ($redirectUrl) $data['checkout']['redirectURL'] = $redirectUrl;
        if ($metadata) $data['metadata'] = array_merge($data['metadata'] ?? [], $metadata);

        return $this->request('POST', "/stores/{$this->storeId}/invoices", $data);
    }

    /**
     * Get invoice
     */
    public function getInvoice(string $invoiceId): array {
        return $this->request('GET', "/stores/{$this->storeId}/invoices/{$invoiceId}");
    }

    /**
     * Get invoice payment methods
     */
    public function getInvoicePaymentMethods(string $invoiceId): array {
        return $this->request('GET', "/stores/{$this->storeId}/invoices/{$invoiceId}/payment-methods");
    }

    /**
     * Get invoices
     */
    public function getInvoices(?string $orderId = null, ?string $status = null): array {
        $params = [];
        if ($orderId) $params['orderId'] = $orderId;
        if ($status) $params['status'] = $status;

        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/stores/{$this->storeId}/invoices{$query}");
    }

    /**
     * Mark invoice as paid (testing)
     */
    public function markInvoicePaid(string $invoiceId): array {
        return $this->request('POST', "/stores/{$this->storeId}/invoices/{$invoiceId}/status", [
            'status' => 'Settled'
        ]);
    }

    /**
     * Create webhook
     */
    public function createWebhook(string $url, array $events): array {
        return $this->request('POST', "/stores/{$this->storeId}/webhooks", [
            'url' => $url,
            'enabled' => true,
            'automaticRedelivery' => true,
            'authorizedEvents' => [
                'everything' => false,
                'specificEvents' => $events
            ]
        ]);
    }

    /**
     * Get webhooks
     */
    public function getWebhooks(): array {
        return $this->request('GET', "/stores/{$this->storeId}/webhooks");
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature, string $secret): bool {
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Invoice statuses
     */
    public static function getInvoiceStatuses(): array {
        return [
            'New' => 'Invoice created, awaiting payment',
            'Processing' => 'Payment received, awaiting confirmations',
            'Settled' => 'Payment confirmed',
            'Expired' => 'Invoice expired without payment',
            'Invalid' => 'Payment invalid or insufficient'
        ];
    }

    /**
     * Webhook events
     */
    public static function getWebhookEvents(): array {
        return [
            'InvoiceCreated',
            'InvoiceReceivedPayment',
            'InvoiceProcessing',
            'InvoiceExpired',
            'InvoiceSettled',
            'InvoiceInvalid',
            'InvoicePaymentSettled'
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// COINBASE COMMERCE API
// Documentation: https://commerce.coinbase.com/docs/
// ═══════════════════════════════════════════════════════════════════════════

class CoinbaseCommerceAPI {
    private string $apiKey;
    private string $apiBase = 'https://api.commerce.coinbase.com';
    private ?string $webhookSecret = null;

    public function __construct(string $apiKey, ?string $webhookSecret = null) {
        $this->apiKey = $apiKey;
        $this->webhookSecret = $webhookSecret;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'X-CC-Api-Key: ' . $this->apiKey,
            'X-CC-Version: 2018-03-22',
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
            $error = $decoded['error']['message'] ?? 'Unknown error';
            throw new \Exception("Coinbase Commerce Error: {$error}");
        }

        return $decoded['data'] ?? $decoded;
    }

    /**
     * Create charge
     */
    public function createCharge(
        string $name,
        string $description,
        float $amount,
        string $currency = 'USD',
        ?string $redirectUrl = null,
        ?string $cancelUrl = null,
        ?array $metadata = null
    ): array {
        $data = [
            'name' => $name,
            'description' => $description,
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => $currency
            ]
        ];

        if ($redirectUrl) $data['redirect_url'] = $redirectUrl;
        if ($cancelUrl) $data['cancel_url'] = $cancelUrl;
        if ($metadata) $data['metadata'] = $metadata;

        return $this->request('POST', '/charges', $data);
    }

    /**
     * Get charge
     */
    public function getCharge(string $chargeId): array {
        return $this->request('GET', "/charges/{$chargeId}");
    }

    /**
     * List charges
     */
    public function listCharges(?int $limit = null): array {
        $endpoint = '/charges';
        if ($limit) $endpoint .= "?limit={$limit}";
        return $this->request('GET', $endpoint);
    }

    /**
     * Cancel charge
     */
    public function cancelCharge(string $chargeId): array {
        return $this->request('POST', "/charges/{$chargeId}/cancel");
    }

    /**
     * Create checkout
     */
    public function createCheckout(
        string $name,
        string $description,
        float $amount,
        string $currency = 'USD',
        ?string $requestedInfo = null
    ): array {
        $data = [
            'name' => $name,
            'description' => $description,
            'pricing_type' => 'fixed_price',
            'local_price' => [
                'amount' => number_format($amount, 2, '.', ''),
                'currency' => $currency
            ]
        ];

        if ($requestedInfo) $data['requested_info'] = $requestedInfo;

        return $this->request('POST', '/checkouts', $data);
    }

    /**
     * Get checkout
     */
    public function getCheckout(string $checkoutId): array {
        return $this->request('GET', "/checkouts/{$checkoutId}");
    }

    /**
     * List checkouts
     */
    public function listCheckouts(?int $limit = null): array {
        $endpoint = '/checkouts';
        if ($limit) $endpoint .= "?limit={$limit}";
        return $this->request('GET', $endpoint);
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(string $payload, string $signature): bool {
        if (!$this->webhookSecret) return false;
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Parse webhook event
     */
    public static function parseWebhookEvent(array $eventData): array {
        return [
            'id' => $eventData['id'] ?? null,
            'type' => $eventData['type'] ?? null,
            'resource' => $eventData['data']['resource'] ?? null,
            'code' => $eventData['data']['code'] ?? null,
            'name' => $eventData['data']['name'] ?? null,
            'description' => $eventData['data']['description'] ?? null,
            'payments' => $eventData['data']['payments'] ?? [],
            'timeline' => $eventData['data']['timeline'] ?? [],
            'metadata' => $eventData['data']['metadata'] ?? [],
            'created_at' => $eventData['data']['created_at'] ?? null
        ];
    }

    /**
     * Webhook event types
     */
    public static function getWebhookEventTypes(): array {
        return [
            'charge:created' => 'New charge created',
            'charge:confirmed' => 'Charge has been confirmed',
            'charge:failed' => 'Charge failed',
            'charge:delayed' => 'Payment delayed',
            'charge:pending' => 'Payment pending',
            'charge:resolved' => 'Charge resolved'
        ];
    }

    /**
     * Charge statuses
     */
    public static function getChargeStatuses(): array {
        return [
            'NEW' => 'Charge created, awaiting payment',
            'PENDING' => 'Payment detected, awaiting confirmation',
            'COMPLETED' => 'Payment confirmed',
            'EXPIRED' => 'Charge expired without payment',
            'UNRESOLVED' => 'Payment received but action needed',
            'RESOLVED' => 'Unresolved charge has been resolved',
            'CANCELED' => 'Charge was canceled'
        ];
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// UNIFIED CRYPTO PAYMENT MANAGER
// ═══════════════════════════════════════════════════════════════════════════

class CryptoPaymentManager {
    private array $providers = [];
    private string $defaultProvider;

    public function __construct(string $defaultProvider = 'nowpayments') {
        $this->defaultProvider = $defaultProvider;
    }

    /**
     * Register a payment provider
     */
    public function registerProvider(string $name, $provider): void {
        $this->providers[$name] = $provider;
    }

    /**
     * Get provider
     */
    public function getProvider(string $name) {
        if (!isset($this->providers[$name])) {
            throw new \Exception("Crypto provider '{$name}' not registered");
        }
        return $this->providers[$name];
    }

    /**
     * Create payment using default or specified provider
     */
    public function createPayment(
        float $amount,
        string $currency,
        string $orderId,
        ?string $provider = null,
        array $options = []
    ): array {
        $provider = $provider ?? $this->defaultProvider;
        $api = $this->getProvider($provider);

        switch ($provider) {
            case 'nowpayments':
                return $api->createPayment(
                    $amount,
                    $currency,
                    $options['crypto_currency'] ?? 'btc',
                    $orderId,
                    $options['description'] ?? null,
                    $options['callback_url'] ?? null,
                    $options['success_url'] ?? null,
                    $options['cancel_url'] ?? null
                );

            case 'plisio':
                return $api->createInvoice(
                    $options['crypto_currency'] ?? 'BTC',
                    $orderId,
                    $amount,
                    $options['name'] ?? 'Order ' . $orderId,
                    $options['email'] ?? null,
                    $options['callback_url'] ?? null,
                    $options['success_url'] ?? null,
                    $options['cancel_url'] ?? null
                );

            case 'btcpay':
                return $api->createInvoice(
                    $amount,
                    $currency,
                    $orderId,
                    $options['email'] ?? null,
                    $options['success_url'] ?? null,
                    $options['metadata'] ?? null
                );

            case 'coinbase':
                return $api->createCharge(
                    $options['name'] ?? 'Order ' . $orderId,
                    $options['description'] ?? 'Payment for order ' . $orderId,
                    $amount,
                    $currency,
                    $options['success_url'] ?? null,
                    $options['cancel_url'] ?? null,
                    ['order_id' => $orderId]
                );

            default:
                throw new \Exception("Unknown provider: {$provider}");
        }
    }

    /**
     * Get payment status
     */
    public function getPaymentStatus(string $paymentId, ?string $provider = null): array {
        $provider = $provider ?? $this->defaultProvider;
        $api = $this->getProvider($provider);

        switch ($provider) {
            case 'nowpayments':
                return $api->getPaymentStatus($paymentId);
            case 'plisio':
                return $api->getInvoice($paymentId);
            case 'btcpay':
                return $api->getInvoice($paymentId);
            case 'coinbase':
                return $api->getCharge($paymentId);
            default:
                throw new \Exception("Unknown provider: {$provider}");
        }
    }

    /**
     * Get all registered providers
     */
    public function getProviders(): array {
        return array_keys($this->providers);
    }
}
