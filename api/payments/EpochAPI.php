<?php
/**
 * Epoch Payment API Integration
 * Adult-Friendly Payment Processing
 * Documentation: https://epoch.com/documentation
 */

namespace WyattXXXCole\Payments;

class EpochAPI {
    private string $companyId;
    private string $apiKey;
    private string $apiBase = 'https://secure.epoch.com/api';
    private bool $testMode = false;

    public function __construct(string $companyId, string $apiKey, bool $testMode = false) {
        $this->companyId = $companyId;
        $this->apiKey = $apiKey;
        $this->testMode = $testMode;
    }

    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'X-Company-ID: ' . $this->companyId,
            'X-API-Key: ' . $this->apiKey,
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
        string $currency,
        string $orderId,
        array $options = []
    ): string {
        $baseUrl = $this->testMode
            ? 'https://sandbox.epoch.com/join'
            : 'https://secure.epoch.com/join';

        $params = [
            'co_code' => $this->companyId,
            'product_id' => $options['product_id'] ?? '1',
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => $currency,
            'order_id' => $orderId,
            'pi_code' => 'epoch1'
        ];

        if (isset($options['return_url'])) {
            $params['return_url'] = $options['return_url'];
        }
        if (isset($options['cancel_url'])) {
            $params['cancel_url'] = $options['cancel_url'];
        }
        if (isset($options['email'])) {
            $params['email'] = $options['email'];
        }

        return $baseUrl . '?' . http_build_query($params);
    }

    public function createSubscriptionLink(
        float $initialAmount,
        float $recurringAmount,
        int $recurringDays,
        string $orderId,
        array $options = []
    ): string {
        $baseUrl = $this->testMode
            ? 'https://sandbox.epoch.com/join'
            : 'https://secure.epoch.com/join';

        $params = [
            'co_code' => $this->companyId,
            'product_id' => $options['product_id'] ?? '1',
            'initial_amount' => number_format($initialAmount, 2, '.', ''),
            'recurring_amount' => number_format($recurringAmount, 2, '.', ''),
            'recurring_period' => $recurringDays,
            'order_id' => $orderId,
            'pi_code' => 'epoch1',
            'type' => 'subscription'
        ];

        return $baseUrl . '?' . http_build_query($params);
    }

    // ═══════════════════════════════════════════════════════════════
    // TRANSACTIONS
    // ═══════════════════════════════════════════════════════════════

    public function getTransaction(string $transactionId): array {
        return $this->request('GET', "/transactions/{$transactionId}");
    }

    public function getTransactions(array $params = []): array {
        $query = $params ? '?' . http_build_query($params) : '';
        return $this->request('GET', "/transactions{$query}");
    }

    public function refundTransaction(string $transactionId, ?float $amount = null): array {
        $data = [];
        if ($amount !== null) {
            $data['amount'] = $amount;
        }
        return $this->request('POST', "/transactions/{$transactionId}/refund", $data);
    }

    // ═══════════════════════════════════════════════════════════════
    // SUBSCRIPTIONS
    // ═══════════════════════════════════════════════════════════════

    public function getSubscription(string $subscriptionId): array {
        return $this->request('GET', "/subscriptions/{$subscriptionId}");
    }

    public function cancelSubscription(string $subscriptionId): array {
        return $this->request('POST', "/subscriptions/{$subscriptionId}/cancel");
    }

    public function pauseSubscription(string $subscriptionId, int $days): array {
        return $this->request('POST', "/subscriptions/{$subscriptionId}/pause", [
            'days' => $days
        ]);
    }

    public function resumeSubscription(string $subscriptionId): array {
        return $this->request('POST', "/subscriptions/{$subscriptionId}/resume");
    }

    // ═══════════════════════════════════════════════════════════════
    // WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    public function verifyPostback(array $data, string $signature): bool {
        $expectedSignature = md5(json_encode($data) . $this->apiKey);
        return hash_equals($expectedSignature, $signature);
    }

    public static function parsePostback(array $data): array {
        return [
            'event_type' => $data['event'] ?? 'unknown',
            'transaction_id' => $data['trans_id'] ?? null,
            'subscription_id' => $data['subscription_id'] ?? null,
            'order_id' => $data['order_id'] ?? null,
            'amount' => isset($data['amount']) ? (float)$data['amount'] : null,
            'currency' => $data['currency'] ?? 'USD',
            'status' => $data['status'] ?? null,
            'email' => $data['email'] ?? null,
            'raw' => $data
        ];
    }

    public static function getPostbackEvents(): array {
        return [
            'sale_success' => 'Successful sale',
            'sale_failure' => 'Failed sale',
            'subscription_created' => 'New subscription',
            'subscription_renewed' => 'Subscription renewed',
            'subscription_cancelled' => 'Subscription cancelled',
            'chargeback' => 'Chargeback received',
            'refund' => 'Refund processed'
        ];
    }
}
