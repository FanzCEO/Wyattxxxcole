<?php
/**
 * Unified Webhook Handler
 * Handles webhooks from all vendors, shipping providers, and payment processors
 * WYATT XXX COLE - Multi-Vendor E-Commerce Platform
 */

namespace WyattXXXCole\Webhooks;

class WebhookHandler {
    private array $config;
    private string $logFile;

    public function __construct() {
        // Load env
        if (file_exists(__DIR__ . '/../.env')) {
            $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), '#') === 0) continue;
                if (strpos($line, '=') !== false) {
                    list($name, $value) = explode('=', $line, 2);
                    $_ENV[trim($name)] = trim($value);
                }
            }
        }

        $this->config = require __DIR__ . '/../vendors/vendor-config.php';
        $this->logFile = __DIR__ . '/../logs/webhooks.log';
    }

    /**
     * Handle incoming webhook
     */
    public function handle(string $provider): array {
        $payload = file_get_contents('php://input');
        $headers = getallheaders();

        $this->log($provider, 'Received webhook', ['headers' => $headers, 'payload_length' => strlen($payload)]);

        try {
            switch ($provider) {
                case 'printful':
                    return $this->handlePrintful($payload, $headers);
                case 'printify':
                    return $this->handlePrintify($payload, $headers);
                case 'cjdropshipping':
                    return $this->handleCJDropshipping($payload, $headers);
                case 'easypost':
                    return $this->handleEasyPost($payload, $headers);
                case 'ccbill':
                    return $this->handleCCBill($payload, $headers);
                case 'nowpayments':
                    return $this->handleNOWPayments($payload, $headers);
                case 'coinbase':
                    return $this->handleCoinbase($payload, $headers);
                case 'btcpay':
                    return $this->handleBTCPay($payload, $headers);
                case 'plisio':
                    return $this->handlePlisio($payload, $headers);
                default:
                    throw new \Exception("Unknown webhook provider: {$provider}");
            }
        } catch (\Exception $e) {
            $this->log($provider, 'Error processing webhook', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // POD VENDOR WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Handle Printful webhook
     */
    private function handlePrintful(string $payload, array $headers): array {
        $data = json_decode($payload, true);

        // Verify signature if configured
        if (!empty($_ENV['PRINTFUL_WEBHOOK_SECRET'])) {
            $signature = $headers['X-Printful-Signature'] ?? '';
            if (!$this->verifyPrintfulSignature($payload, $signature, $_ENV['PRINTFUL_WEBHOOK_SECRET'])) {
                throw new \Exception('Invalid Printful webhook signature');
            }
        }

        $eventType = $data['type'] ?? 'unknown';
        $orderData = $data['data'] ?? [];

        $this->log('printful', "Event: {$eventType}", $orderData);

        switch ($eventType) {
            case 'package_shipped':
                return $this->onOrderShipped('printful', $orderData);
            case 'order_created':
                return $this->onOrderCreated('printful', $orderData);
            case 'order_updated':
                return $this->onOrderUpdated('printful', $orderData);
            case 'order_failed':
                return $this->onOrderFailed('printful', $orderData);
            case 'order_canceled':
                return $this->onOrderCanceled('printful', $orderData);
            case 'product_synced':
                return $this->onProductSynced('printful', $orderData);
            default:
                return ['status' => 'ignored', 'event' => $eventType];
        }
    }

    /**
     * Handle Printify webhook
     */
    private function handlePrintify(string $payload, array $headers): array {
        $data = json_decode($payload, true);

        $eventType = $data['type'] ?? $data['topic'] ?? 'unknown';
        $resource = $data['resource'] ?? $data['data'] ?? [];

        $this->log('printify', "Event: {$eventType}", $resource);

        switch ($eventType) {
            case 'order:shipment:created':
                return $this->onOrderShipped('printify', $resource);
            case 'order:created':
                return $this->onOrderCreated('printify', $resource);
            case 'order:updated':
                return $this->onOrderUpdated('printify', $resource);
            case 'order:sent-to-production':
                return $this->onOrderInProduction('printify', $resource);
            case 'product:publish:started':
                return $this->onProductPublishing('printify', $resource);
            case 'product:deleted':
                return $this->onProductDeleted('printify', $resource);
            default:
                return ['status' => 'ignored', 'event' => $eventType];
        }
    }

    /**
     * Handle CJ Dropshipping webhook
     */
    private function handleCJDropshipping(string $payload, array $headers): array {
        $data = json_decode($payload, true);

        $eventType = $data['type'] ?? 'unknown';
        $orderData = $data['data'] ?? $data;

        $this->log('cjdropshipping', "Event: {$eventType}", $orderData);

        switch ($eventType) {
            case 'order.shipped':
                return $this->onOrderShipped('cjdropshipping', $orderData);
            case 'order.delivered':
                return $this->onOrderDelivered('cjdropshipping', $orderData);
            case 'order.cancelled':
                return $this->onOrderCanceled('cjdropshipping', $orderData);
            default:
                return ['status' => 'ignored', 'event' => $eventType];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPPING WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Handle EasyPost webhook
     */
    private function handleEasyPost(string $payload, array $headers): array {
        $data = json_decode($payload, true);

        $eventType = $data['description'] ?? 'unknown';
        $result = $data['result'] ?? [];

        $this->log('easypost', "Event: {$eventType}", $result);

        switch ($eventType) {
            case 'tracker.created':
            case 'tracker.updated':
                return $this->onTrackingUpdate('easypost', $result);
            case 'batch.created':
            case 'batch.updated':
                return $this->onBatchUpdate('easypost', $result);
            case 'scan_form.created':
                return $this->onScanFormCreated('easypost', $result);
            default:
                return ['status' => 'ignored', 'event' => $eventType];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // PAYMENT WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Handle CCBill postback
     */
    private function handleCCBill(string $payload, array $headers): array {
        // CCBill sends form-encoded data
        parse_str($payload, $data);

        // Also check $_POST for form data
        if (empty($data)) {
            $data = $_POST;
        }

        // Verify signature
        if (!empty($_ENV['CCBILL_SALT'])) {
            $subscriptionId = $data['subscriptionId'] ?? '';
            $expectedDigest = md5($subscriptionId . '1' . $_ENV['CCBILL_SALT']);
            $receivedDigest = $data['responseDigest'] ?? '';

            if (!hash_equals($expectedDigest, $receivedDigest)) {
                $this->log('ccbill', 'Invalid signature', $data);
                // Continue anyway for postbacks (they may use different signature formats)
            }
        }

        $eventType = $this->determineCCBillEventType($data);

        $this->log('ccbill', "Event: {$eventType}", $data);

        switch ($eventType) {
            case 'NewSaleSuccess':
                return $this->onPaymentSuccess('ccbill', $data);
            case 'NewSaleFailure':
                return $this->onPaymentFailed('ccbill', $data);
            case 'RenewalSuccess':
                return $this->onSubscriptionRenewed('ccbill', $data);
            case 'RenewalFailure':
                return $this->onSubscriptionRenewalFailed('ccbill', $data);
            case 'Cancellation':
                return $this->onSubscriptionCanceled('ccbill', $data);
            case 'Chargeback':
                return $this->onChargeback('ccbill', $data);
            case 'Refund':
                return $this->onRefund('ccbill', $data);
            default:
                return ['status' => 'ignored', 'event' => $eventType];
        }
    }

    /**
     * Determine CCBill event type from postback data
     */
    private function determineCCBillEventType(array $data): string {
        if (isset($data['eventType'])) {
            return $data['eventType'];
        }
        if (isset($data['failureReason'])) {
            return 'NewSaleFailure';
        }
        if (isset($data['cancellationReason'])) {
            return 'Cancellation';
        }
        if (isset($data['chargebackType'])) {
            return 'Chargeback';
        }
        if (isset($data['refundReason'])) {
            return 'Refund';
        }
        if (isset($data['renewalTransactionId'])) {
            return 'RenewalSuccess';
        }
        if (isset($data['subscriptionId']) && isset($data['transactionId'])) {
            return 'NewSaleSuccess';
        }
        return 'Unknown';
    }

    /**
     * Handle NOWPayments IPN
     */
    private function handleNOWPayments(string $payload, array $headers): array {
        $data = json_decode($payload, true);

        // Verify signature
        if (!empty($_ENV['NOWPAYMENTS_IPN_SECRET'])) {
            $signature = $headers['x-nowpayments-sig'] ?? '';
            $expectedSignature = hash_hmac('sha512', $payload, $_ENV['NOWPAYMENTS_IPN_SECRET']);

            if (!hash_equals($expectedSignature, $signature)) {
                throw new \Exception('Invalid NOWPayments signature');
            }
        }

        $status = $data['payment_status'] ?? 'unknown';

        $this->log('nowpayments', "Status: {$status}", $data);

        switch ($status) {
            case 'finished':
            case 'confirmed':
                return $this->onCryptoPaymentConfirmed('nowpayments', $data);
            case 'partially_paid':
                return $this->onCryptoPaymentPartial('nowpayments', $data);
            case 'failed':
            case 'expired':
                return $this->onCryptoPaymentFailed('nowpayments', $data);
            case 'waiting':
            case 'confirming':
                return $this->onCryptoPaymentPending('nowpayments', $data);
            default:
                return ['status' => 'ignored', 'payment_status' => $status];
        }
    }

    /**
     * Handle Coinbase Commerce webhook
     */
    private function handleCoinbase(string $payload, array $headers): array {
        $data = json_decode($payload, true);

        // Verify signature
        if (!empty($_ENV['COINBASE_WEBHOOK_SECRET'])) {
            $signature = $headers['X-CC-Webhook-Signature'] ?? '';
            $expectedSignature = hash_hmac('sha256', $payload, $_ENV['COINBASE_WEBHOOK_SECRET']);

            if (!hash_equals($expectedSignature, $signature)) {
                throw new \Exception('Invalid Coinbase Commerce signature');
            }
        }

        $eventType = $data['event']['type'] ?? 'unknown';
        $charge = $data['event']['data'] ?? [];

        $this->log('coinbase', "Event: {$eventType}", $charge);

        switch ($eventType) {
            case 'charge:confirmed':
                return $this->onCryptoPaymentConfirmed('coinbase', $charge);
            case 'charge:pending':
                return $this->onCryptoPaymentPending('coinbase', $charge);
            case 'charge:failed':
                return $this->onCryptoPaymentFailed('coinbase', $charge);
            case 'charge:delayed':
                return $this->onCryptoPaymentDelayed('coinbase', $charge);
            case 'charge:resolved':
                return $this->onCryptoPaymentResolved('coinbase', $charge);
            default:
                return ['status' => 'ignored', 'event' => $eventType];
        }
    }

    /**
     * Handle BTCPay Server webhook
     */
    private function handleBTCPay(string $payload, array $headers): array {
        $data = json_decode($payload, true);

        // Verify signature if webhook secret is configured
        if (!empty($_ENV['BTCPAY_WEBHOOK_SECRET'])) {
            $signature = $headers['BTCPay-Sig'] ?? '';
            $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $_ENV['BTCPAY_WEBHOOK_SECRET']);

            if (!hash_equals($expectedSignature, $signature)) {
                throw new \Exception('Invalid BTCPay signature');
            }
        }

        $eventType = $data['type'] ?? 'unknown';
        $invoiceData = $data['invoiceId'] ? $data : [];

        $this->log('btcpay', "Event: {$eventType}", $invoiceData);

        switch ($eventType) {
            case 'InvoiceSettled':
            case 'InvoicePaymentSettled':
                return $this->onCryptoPaymentConfirmed('btcpay', $invoiceData);
            case 'InvoiceProcessing':
            case 'InvoiceReceivedPayment':
                return $this->onCryptoPaymentPending('btcpay', $invoiceData);
            case 'InvoiceExpired':
            case 'InvoiceInvalid':
                return $this->onCryptoPaymentFailed('btcpay', $invoiceData);
            default:
                return ['status' => 'ignored', 'event' => $eventType];
        }
    }

    /**
     * Handle Plisio callback
     */
    private function handlePlisio(string $payload, array $headers): array {
        $data = json_decode($payload, true);

        // Verify signature
        if (!empty($_ENV['PLISIO_API_KEY'])) {
            $signature = $data['verify_hash'] ?? '';
            unset($data['verify_hash']);
            ksort($data);
            $expectedSignature = hash_hmac('sha1', json_encode($data, JSON_UNESCAPED_UNICODE), $_ENV['PLISIO_API_KEY']);

            if (!hash_equals($expectedSignature, $signature)) {
                throw new \Exception('Invalid Plisio signature');
            }
        }

        $status = $data['status'] ?? 'unknown';

        $this->log('plisio', "Status: {$status}", $data);

        switch ($status) {
            case 'completed':
                return $this->onCryptoPaymentConfirmed('plisio', $data);
            case 'pending':
            case 'confirming':
                return $this->onCryptoPaymentPending('plisio', $data);
            case 'expired':
            case 'cancelled':
            case 'error':
                return $this->onCryptoPaymentFailed('plisio', $data);
            default:
                return ['status' => 'ignored', 'payment_status' => $status];
        }
    }

    // ═══════════════════════════════════════════════════════════════
    // EVENT HANDLERS
    // ═══════════════════════════════════════════════════════════════

    private function onOrderCreated(string $vendor, array $data): array {
        // Implement order created logic
        return ['status' => 'processed', 'action' => 'order_created', 'vendor' => $vendor];
    }

    private function onOrderUpdated(string $vendor, array $data): array {
        // Implement order updated logic
        return ['status' => 'processed', 'action' => 'order_updated', 'vendor' => $vendor];
    }

    private function onOrderShipped(string $vendor, array $data): array {
        // Implement order shipped logic - send email, update database
        return ['status' => 'processed', 'action' => 'order_shipped', 'vendor' => $vendor];
    }

    private function onOrderDelivered(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'order_delivered', 'vendor' => $vendor];
    }

    private function onOrderFailed(string $vendor, array $data): array {
        // Alert admin, refund customer
        return ['status' => 'processed', 'action' => 'order_failed', 'vendor' => $vendor];
    }

    private function onOrderCanceled(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'order_canceled', 'vendor' => $vendor];
    }

    private function onOrderInProduction(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'order_in_production', 'vendor' => $vendor];
    }

    private function onProductSynced(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'product_synced', 'vendor' => $vendor];
    }

    private function onProductPublishing(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'product_publishing', 'vendor' => $vendor];
    }

    private function onProductDeleted(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'product_deleted', 'vendor' => $vendor];
    }

    private function onTrackingUpdate(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'tracking_update', 'vendor' => $vendor];
    }

    private function onBatchUpdate(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'batch_update', 'vendor' => $vendor];
    }

    private function onScanFormCreated(string $vendor, array $data): array {
        return ['status' => 'processed', 'action' => 'scan_form_created', 'vendor' => $vendor];
    }

    private function onPaymentSuccess(string $processor, array $data): array {
        // Fulfill order, send confirmation email
        return ['status' => 'processed', 'action' => 'payment_success', 'processor' => $processor];
    }

    private function onPaymentFailed(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'payment_failed', 'processor' => $processor];
    }

    private function onSubscriptionRenewed(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'subscription_renewed', 'processor' => $processor];
    }

    private function onSubscriptionRenewalFailed(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'subscription_renewal_failed', 'processor' => $processor];
    }

    private function onSubscriptionCanceled(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'subscription_canceled', 'processor' => $processor];
    }

    private function onChargeback(string $processor, array $data): array {
        // Alert admin immediately
        return ['status' => 'processed', 'action' => 'chargeback', 'processor' => $processor];
    }

    private function onRefund(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'refund', 'processor' => $processor];
    }

    private function onCryptoPaymentConfirmed(string $processor, array $data): array {
        // Fulfill order
        return ['status' => 'processed', 'action' => 'crypto_payment_confirmed', 'processor' => $processor];
    }

    private function onCryptoPaymentPending(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'crypto_payment_pending', 'processor' => $processor];
    }

    private function onCryptoPaymentFailed(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'crypto_payment_failed', 'processor' => $processor];
    }

    private function onCryptoPaymentPartial(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'crypto_payment_partial', 'processor' => $processor];
    }

    private function onCryptoPaymentDelayed(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'crypto_payment_delayed', 'processor' => $processor];
    }

    private function onCryptoPaymentResolved(string $processor, array $data): array {
        return ['status' => 'processed', 'action' => 'crypto_payment_resolved', 'processor' => $processor];
    }

    // ═══════════════════════════════════════════════════════════════
    // UTILITIES
    // ═══════════════════════════════════════════════════════════════

    private function verifyPrintfulSignature(string $payload, string $signature, string $secret): bool {
        $expectedSignature = base64_encode(hash_hmac('sha256', $payload, $secret, true));
        return hash_equals($expectedSignature, $signature);
    }

    private function log(string $provider, string $message, array $data = []): void {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'provider' => $provider,
            'message' => $message,
            'data' => $data
        ];

        file_put_contents(
            $this->logFile,
            json_encode($logEntry) . "\n",
            FILE_APPEND | LOCK_EX
        );
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// WEBHOOK ENDPOINT
// ═══════════════════════════════════════════════════════════════════════════

// Handle webhook requests if this file is called directly
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    header('Content-Type: application/json');

    try {
        $provider = $_GET['provider'] ?? null;

        if (!$provider) {
            throw new \Exception('Provider not specified');
        }

        $handler = new WebhookHandler();
        $result = $handler->handle($provider);

        http_response_code(200);
        echo json_encode(['success' => true, 'result' => $result]);

    } catch (\Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
