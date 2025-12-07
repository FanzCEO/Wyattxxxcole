<?php
/**
 * CCBill API Integration
 * Full implementation for Adult-Friendly Payment Processing
 * Documentation: https://ccbill.com/developers
 */

namespace WyattXXXCole\Payments;

class CCBillAPI {
    private string $accountNumber;
    private string $subAccountNumber;
    private string $flexFormId;
    private string $salt;
    private string $apiBase = 'https://api.ccbill.com';
    private string $dataLinkBase = 'https://datalink.ccbill.com';
    private bool $testMode = false;

    public function __construct(
        string $accountNumber,
        string $subAccountNumber,
        string $flexFormId,
        string $salt,
        bool $testMode = false
    ) {
        $this->accountNumber = $accountNumber;
        $this->subAccountNumber = $subAccountNumber;
        $this->flexFormId = $flexFormId;
        $this->salt = $salt;
        $this->testMode = $testMode;
    }

    // ═══════════════════════════════════════════════════════════════
    // FLEXFORMS PAYMENT LINKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Generate FlexForms payment URL for one-time purchase
     */
    public function createPaymentLink(
        float $amount,
        int $currencyCode,
        string $formDigest,
        array $customFields = []
    ): string {
        $baseUrl = $this->testMode
            ? 'https://sandbox-api.ccbill.com/wap-frontflex/flexforms/'
            : 'https://api.ccbill.com/wap-frontflex/flexforms/';

        $params = [
            'clientAccnum' => $this->accountNumber,
            'clientSubacc' => $this->subAccountNumber,
            'formName' => $this->flexFormId,
            'formPrice' => number_format($amount, 2, '.', ''),
            'formPeriod' => '2', // Days (for initial period)
            'currencyCode' => $currencyCode,
            'formDigest' => $formDigest
        ];

        // Add custom fields (X-custom1, X-custom2, etc.)
        foreach ($customFields as $key => $value) {
            $params['X-' . $key] = $value;
        }

        return $baseUrl . $this->flexFormId . '?' . http_build_query($params);
    }

    /**
     * Generate FlexForms subscription URL
     */
    public function createSubscriptionLink(
        float $initialPrice,
        int $initialPeriod,
        float $recurringPrice,
        int $recurringPeriod,
        int $rebills,
        int $currencyCode,
        array $customFields = []
    ): string {
        // Generate form digest
        $formDigest = $this->generateFormDigest(
            $initialPrice,
            $initialPeriod,
            $recurringPrice,
            $recurringPeriod,
            $rebills,
            $currencyCode
        );

        $baseUrl = $this->testMode
            ? 'https://sandbox-api.ccbill.com/wap-frontflex/flexforms/'
            : 'https://api.ccbill.com/wap-frontflex/flexforms/';

        $params = [
            'clientAccnum' => $this->accountNumber,
            'clientSubacc' => $this->subAccountNumber,
            'formName' => $this->flexFormId,
            'initialPrice' => number_format($initialPrice, 2, '.', ''),
            'initialPeriod' => $initialPeriod,
            'recurringPrice' => number_format($recurringPrice, 2, '.', ''),
            'recurringPeriod' => $recurringPeriod,
            'numRebills' => $rebills, // 99 = infinite
            'currencyCode' => $currencyCode,
            'formDigest' => $formDigest
        ];

        foreach ($customFields as $key => $value) {
            $params['X-' . $key] = $value;
        }

        return $baseUrl . $this->flexFormId . '?' . http_build_query($params);
    }

    /**
     * Generate form digest (required for dynamic pricing)
     */
    public function generateFormDigest(
        float $initialPrice,
        int $initialPeriod,
        float $recurringPrice,
        int $recurringPeriod,
        int $rebills,
        int $currencyCode
    ): string {
        $stringToHash = sprintf(
            '%s%s%.2f%d%.2f%d%d%d%s',
            $this->accountNumber,
            $this->subAccountNumber,
            $initialPrice,
            $initialPeriod,
            $recurringPrice,
            $recurringPeriod,
            $rebills,
            $currencyCode,
            $this->salt
        );

        return md5($stringToHash);
    }

    /**
     * Generate single price form digest
     */
    public function generateSinglePriceDigest(float $price, int $period, int $currencyCode): string {
        $stringToHash = sprintf(
            '%.2f%d%d%s',
            $price,
            $period,
            $currencyCode,
            $this->salt
        );

        return md5($stringToHash);
    }

    // ═══════════════════════════════════════════════════════════════
    // DATALINK API (Subscription Management)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Make DataLink API request
     */
    private function dataLinkRequest(string $action, array $params = []): array {
        $url = $this->dataLinkBase . '/utils/subscriptionManagement.cgi';

        $params = array_merge([
            'clientAccnum' => $this->accountNumber,
            'usingSubacc' => $this->subAccountNumber,
            'action' => $action
        ], $params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("CCBill DataLink Error: $error");
        }

        // Parse response (key=value format)
        $result = [];
        $lines = explode("\n", $response);
        foreach ($lines as $line) {
            $parts = explode('=', trim($line), 2);
            if (count($parts) === 2) {
                $result[$parts[0]] = $parts[1];
            }
        }

        return $result;
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(string $subscriptionId): array {
        return $this->dataLinkRequest('cancelSubscription', [
            'subscriptionId' => $subscriptionId
        ]);
    }

    /**
     * Modify subscription (extend)
     */
    public function extendSubscription(string $subscriptionId, int $days): array {
        return $this->dataLinkRequest('extendSubscription', [
            'subscriptionId' => $subscriptionId,
            'daysToExtend' => $days
        ]);
    }

    /**
     * Get subscription status
     */
    public function getSubscriptionStatus(string $subscriptionId): array {
        return $this->dataLinkRequest('viewSubscriptionStatus', [
            'subscriptionId' => $subscriptionId
        ]);
    }

    /**
     * Charge by previous transaction
     */
    public function chargeByPrevious(string $subscriptionId, float $amount, int $currencyCode = 840): array {
        return $this->dataLinkRequest('chargeByPreviousTransactionId', [
            'subscriptionId' => $subscriptionId,
            'newAmount' => number_format($amount, 2, '.', ''),
            'currencyCode' => $currencyCode
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // RESTFUL API (RESTful Transactions)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Make RESTful API request
     */
    private function apiRequest(string $method, string $endpoint, array $data = [], ?string $authToken = null): array {
        $url = $this->apiBase . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Accept: application/vnd.mcn.transaction-service.api.v.2+json'
        ];

        if ($authToken) {
            $headers[] = 'Authorization: Bearer ' . $authToken;
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
            throw new \Exception("CCBill API Error: $error");
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $decoded['message'] ?? $decoded['error'] ?? 'Unknown error';
            throw new \Exception("CCBill API Error ($httpCode): $errorMsg");
        }

        return $decoded ?? [];
    }

    /**
     * Get OAuth token for API access
     */
    public function getAuthToken(string $merchantApiKey, string $secret): string {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->apiBase . '/oauth/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'grant_type' => 'client_credentials'
        ]));
        curl_setopt($ch, CURLOPT_USERPWD, $merchantApiKey . ':' . $secret);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded'
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $decoded = json_decode($response, true);

        if (isset($decoded['access_token'])) {
            return $decoded['access_token'];
        }

        throw new \Exception("Failed to get CCBill auth token");
    }

    /**
     * Create payment token (for stored cards)
     */
    public function createPaymentToken(string $authToken, array $paymentData): array {
        return $this->apiRequest('POST', '/payment-tokens/merchant-only', $paymentData, $authToken);
    }

    /**
     * Charge using payment token
     */
    public function chargePaymentToken(string $authToken, string $paymentToken, array $chargeData): array {
        return $this->apiRequest('POST', '/transactions/payment-tokens/' . $paymentToken, $chargeData, $authToken);
    }

    // ═══════════════════════════════════════════════════════════════
    // WEBHOOK HANDLING
    // ═══════════════════════════════════════════════════════════════

    /**
     * Verify webhook/postback signature
     */
    public function verifyPostback(array $postData): bool {
        // CCBill postbacks include responseDigest
        if (!isset($postData['responseDigest'])) {
            return false;
        }

        // Reconstruct digest based on event type
        $subscriptionId = $postData['subscriptionId'] ?? '';
        $transactionId = $postData['transactionId'] ?? '';

        // The digest calculation varies by event type
        // This is a common pattern for new sale events
        $expectedDigest = md5($subscriptionId . '1' . $this->salt);

        return hash_equals($expectedDigest, $postData['responseDigest']);
    }

    /**
     * Parse webhook payload
     */
    public static function parseWebhook(array $postData): array {
        return [
            'event_type' => $postData['eventType'] ?? self::determineEventType($postData),
            'subscription_id' => $postData['subscriptionId'] ?? null,
            'transaction_id' => $postData['transactionId'] ?? null,
            'amount' => isset($postData['billedAmount']) ? (float)$postData['billedAmount'] : null,
            'currency' => $postData['billedCurrencyCode'] ?? null,
            'customer_email' => $postData['email'] ?? null,
            'customer_name' => ($postData['customer_fname'] ?? '') . ' ' . ($postData['customer_lname'] ?? ''),
            'custom_fields' => [
                'custom1' => $postData['X-custom1'] ?? null,
                'custom2' => $postData['X-custom2'] ?? null,
                'custom3' => $postData['X-custom3'] ?? null
            ],
            'card_type' => $postData['cardType'] ?? null,
            'timestamp' => $postData['timestamp'] ?? date('Y-m-d H:i:s'),
            'raw' => $postData
        ];
    }

    /**
     * Determine event type from postback data
     */
    private static function determineEventType(array $data): string {
        if (isset($data['failureReason'])) {
            return 'TRANSACTION_DECLINED';
        }
        if (isset($data['cancellationReason'])) {
            return 'SUBSCRIPTION_CANCELLED';
        }
        if (isset($data['chargebackType'])) {
            return 'CHARGEBACK';
        }
        if (isset($data['refundReason'])) {
            return 'REFUND';
        }
        if (isset($data['subscriptionId']) && isset($data['transactionId'])) {
            return 'NEW_SALE_SUCCESS';
        }
        if (isset($data['renewalTransactionId'])) {
            return 'RENEWAL_SUCCESS';
        }

        return 'UNKNOWN';
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Currency codes
     */
    public static function getCurrencyCodes(): array {
        return [
            840 => 'USD',
            978 => 'EUR',
            826 => 'GBP',
            124 => 'CAD',
            036 => 'AUD',
            392 => 'JPY',
            756 => 'CHF'
        ];
    }

    /**
     * Get pricing periods
     */
    public static function getPricingPeriods(): array {
        return [
            2 => '2 days',
            3 => '3 days',
            4 => '4 days',
            5 => '5 days',
            6 => '6 days',
            7 => '7 days (1 week)',
            10 => '10 days',
            14 => '14 days (2 weeks)',
            30 => '30 days (1 month)',
            60 => '60 days (2 months)',
            90 => '90 days (3 months)',
            120 => '120 days (4 months)',
            180 => '180 days (6 months)',
            365 => '365 days (1 year)'
        ];
    }

    /**
     * Webhook event types
     */
    public static function getWebhookEventTypes(): array {
        return [
            'NewSaleSuccess' => 'New subscription sale completed',
            'NewSaleFailure' => 'New subscription sale failed',
            'RenewalSuccess' => 'Subscription renewal successful',
            'RenewalFailure' => 'Subscription renewal failed',
            'Cancellation' => 'Subscription cancelled',
            'Chargeback' => 'Chargeback received',
            'Refund' => 'Refund processed',
            'UserReactivation' => 'User reactivated subscription',
            'Expiration' => 'Subscription expired',
            'Upgrade' => 'Subscription upgraded',
            'BillingDateChange' => 'Billing date changed'
        ];
    }

    /**
     * Build subscription pricing object
     */
    public static function buildSubscription(
        float $monthlyPrice,
        ?float $trialPrice = null,
        int $trialDays = 0,
        bool $annual = false
    ): array {
        $subscription = [
            'initial_price' => $trialPrice ?? $monthlyPrice,
            'initial_period' => $trialDays > 0 ? $trialDays : 30,
            'recurring_price' => $monthlyPrice,
            'recurring_period' => $annual ? 365 : 30,
            'rebills' => 99 // Infinite
        ];

        return $subscription;
    }
}
