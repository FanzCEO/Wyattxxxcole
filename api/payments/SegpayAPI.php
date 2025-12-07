<?php
/**
 * Segpay Payment API Integration
 * Adult-Friendly Payment Processing
 * Documentation: https://segpay.com/developers
 */

namespace WyattXXXCole\Payments;

class SegpayAPI {
    private string $merchantId;
    private string $apiKey;
    private string $apiBase = 'https://api.segpay.com/v2';
    private bool $testMode = false;

    public function __construct(string $merchantId, string $apiKey, bool $testMode = false) {
        $this->merchantId = $merchantId;
        $this->apiKey = $apiKey;
        $this->testMode = $testMode;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'X-Merchant-ID: ' . $this->merchantId,
            'Authorization: Bearer ' . $this->apiKey,
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

        return json_decode($response, true) ?? [];
    }

    // ═══════════════════════════════════════════════════════════════
    // PAYMENT LINKS
    // ═══════════════════════════════════════════════════════════════

    public function createPaymentLink(
        float $amount,
        string $packageId,
        string $orderId,
        array $options = []
    ): string {
        $baseUrl = $this->testMode
            ? 'https://sandbox.segpay.com/billing/payment'
            : 'https://secure.segpay.com/billing/payment';

        $params = [
            'x-eticketid' => $this->merchantId,
            'x-packageid' => $packageId,
            'x-amount' => number_format($amount, 2, '.', ''),
            'x-orderno' => $orderId
        ];

        if (isset($options['email'])) {
            $params['email'] = $options['email'];
        }
        if (isset($options['username'])) {
            $params['username'] = $options['username'];
        }
        if (isset($options['return_url'])) {
            $params['x-successurl'] = $options['return_url'];
        }
        if (isset($options['cancel_url'])) {
            $params['x-declineurl'] = $options['cancel_url'];
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    public function createSubscriptionLink(
        float $initialAmount,
        float $recurringAmount,
        int $recurringDays,
        string $packageId,
        string $orderId,
        array $options = []
    ): string {
        $baseUrl = $this->testMode
            ? 'https://sandbox.segpay.com/billing/payment'
            : 'https://secure.segpay.com/billing/payment';

        $params = [
            'x-eticketid' => $this->merchantId,
            'x-packageid' => $packageId,
            'x-initial' => number_format($initialAmount, 2, '.', ''),
            'x-amount' => number_format($recurringAmount, 2, '.', ''),
            'x-period' => $recurringDays,
            'x-orderno' => $orderId,
            'x-rebills' => $options['rebills'] ?? 99
        ];

        return $baseUrl . '?' . http_build_query($params);
    }

    // ═══════════════════════════════════════════════════════════════
    // TRANSACTIONS
    // ═══════════════════════════════════════════════════════════════

    public function getTransaction(string $transactionId): array {
        return $this->request('GET', "/transactions/{$transactionId}");
    }

    public function refund(string $transactionId, ?float $amount = null): array {
        $data = [];
        if ($amount !== null) {
            $data['amount'] = $amount;
        }
        return $this->request('POST', "/transactions/{$transactionId}/refund", $data);
    }

    // ═══════════════════════════════════════════════════════════════
    // SUBSCRIPTIONS
    // ═══════════════════════════════════════════════════════════════

    public function getSubscription(string $purchaseId): array {
        return $this->request('GET', "/subscriptions/{$purchaseId}");
    }

    public function cancelSubscription(string $purchaseId): array {
        return $this->request('DELETE', "/subscriptions/{$purchaseId}");
    }

    public function updateSubscription(string $purchaseId, array $data): array {
        return $this->request('PATCH', "/subscriptions/{$purchaseId}", $data);
    }

    // ═══════════════════════════════════════════════════════════════
    // WEBHOOKS / POSTBACKS
    // ═══════════════════════════════════════════════════════════════

    public function verifyPostback(array $data): bool {
        // Segpay sends a hash for verification
        $hash = $data['hash'] ?? '';
        unset($data['hash']);

        ksort($data);
        $signString = implode('', $data) . $this->apiKey;
        $expectedHash = md5($signString);

        return hash_equals($expectedHash, $hash);
    }

    public static function parsePostback(array $data): array {
        return [
            'event_type' => $data['transtype'] ?? 'unknown',
            'transaction_id' => $data['transid'] ?? null,
            'purchase_id' => $data['purchaseid'] ?? null,
            'order_id' => $data['orderno'] ?? null,
            'amount' => isset($data['price']) ? (float)$data['price'] : null,
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? null,
            'status' => $data['approved'] ?? null,
            'raw' => $data
        ];
    }

    public static function getTransactionTypes(): array {
        return [
            'AUTH' => 'Authorization',
            'SALE' => 'Sale',
            'REBILL' => 'Recurring billing',
            'VOID' => 'Void',
            'REFUND' => 'Refund',
            'CHARGEBACK' => 'Chargeback'
        ];
    }
}
