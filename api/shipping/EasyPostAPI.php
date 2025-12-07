<?php
/**
 * EasyPost API Integration
 * Full implementation for Multi-Carrier Shipping operations
 * Documentation: https://www.easypost.com/docs/api
 */

namespace WyattXXXCole\Shipping;

class EasyPostAPI {
    private string $apiKey;
    private string $apiBase = 'https://api.easypost.com/v2';

    public function __construct(string $apiKey) {
        $this->apiKey = $apiKey;
    }

    /**
     * Make API request to EasyPost
     */
    private function request(string $method, string $endpoint, array $data = []): array {
        $url = $this->apiBase . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->apiKey . ':');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $headers = ['Content-Type: application/json'];

        switch (strtoupper($method)) {
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new \Exception("EasyPost API Error: $error");
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMsg = $decoded['error']['message'] ?? 'Unknown error';
            throw new \Exception("EasyPost API Error ($httpCode): $errorMsg");
        }

        return $decoded;
    }

    // ═══════════════════════════════════════════════════════════════
    // ADDRESSES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create an address
     */
    public function createAddress(array $addressData): array {
        return $this->request('POST', '/addresses', ['address' => $addressData]);
    }

    /**
     * Create and verify address
     */
    public function createAndVerifyAddress(array $addressData): array {
        return $this->request('POST', '/addresses/create_and_verify', ['address' => $addressData]);
    }

    /**
     * Get address
     */
    public function getAddress(string $addressId): array {
        return $this->request('GET', "/addresses/{$addressId}");
    }

    /**
     * Verify address
     */
    public function verifyAddress(string $addressId): array {
        return $this->request('GET', "/addresses/{$addressId}/verify");
    }

    /**
     * Get all addresses
     */
    public function getAddresses(int $pageSize = 20, ?string $beforeId = null): array {
        $params = ['page_size' => $pageSize];
        if ($beforeId) $params['before_id'] = $beforeId;
        return $this->request('GET', '/addresses?' . http_build_query($params));
    }

    // ═══════════════════════════════════════════════════════════════
    // PARCELS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create a parcel
     */
    public function createParcel(float $length, float $width, float $height, float $weight): array {
        return $this->request('POST', '/parcels', [
            'parcel' => [
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'weight' => $weight
            ]
        ]);
    }

    /**
     * Get parcel
     */
    public function getParcel(string $parcelId): array {
        return $this->request('GET', "/parcels/{$parcelId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // SHIPMENTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create shipment
     */
    public function createShipment(array $shipmentData): array {
        return $this->request('POST', '/shipments', ['shipment' => $shipmentData]);
    }

    /**
     * Get shipment
     */
    public function getShipment(string $shipmentId): array {
        return $this->request('GET', "/shipments/{$shipmentId}");
    }

    /**
     * Get shipments list
     */
    public function getShipments(int $pageSize = 20, ?string $beforeId = null): array {
        $params = ['page_size' => $pageSize];
        if ($beforeId) $params['before_id'] = $beforeId;
        return $this->request('GET', '/shipments?' . http_build_query($params));
    }

    /**
     * Buy shipment (purchase label)
     */
    public function buyShipment(string $shipmentId, string $rateId, ?float $insurance = null): array {
        $data = ['rate' => ['id' => $rateId]];
        if ($insurance) $data['insurance'] = $insurance;
        return $this->request('POST', "/shipments/{$shipmentId}/buy", $data);
    }

    /**
     * Regenerate rates for shipment
     */
    public function regenerateRates(string $shipmentId): array {
        return $this->request('POST', "/shipments/{$shipmentId}/rerate");
    }

    /**
     * Convert label format
     */
    public function convertLabel(string $shipmentId, string $format = 'PNG'): array {
        return $this->request('GET', "/shipments/{$shipmentId}/label?file_format={$format}");
    }

    /**
     * Insure shipment
     */
    public function insureShipment(string $shipmentId, float $amount): array {
        return $this->request('POST', "/shipments/{$shipmentId}/insure", ['amount' => $amount]);
    }

    /**
     * Refund shipment
     */
    public function refundShipment(string $shipmentId): array {
        return $this->request('POST', "/shipments/{$shipmentId}/refund");
    }

    // ═══════════════════════════════════════════════════════════════
    // RATES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Get rates (via shipment)
     */
    public function getRates(array $from, array $to, array $parcel): array {
        $shipment = $this->createShipment([
            'from_address' => $from,
            'to_address' => $to,
            'parcel' => $parcel
        ]);

        return $shipment['rates'] ?? [];
    }

    /**
     * Get lowest rate
     */
    public function getLowestRate(string $shipmentId, ?array $carriers = null, ?array $services = null): ?array {
        $shipment = $this->getShipment($shipmentId);
        $rates = $shipment['rates'] ?? [];

        if (empty($rates)) return null;

        // Filter by carriers if specified
        if ($carriers) {
            $rates = array_filter($rates, fn($r) => in_array($r['carrier'], $carriers));
        }

        // Filter by services if specified
        if ($services) {
            $rates = array_filter($rates, fn($r) => in_array($r['service'], $services));
        }

        // Sort by rate and return lowest
        usort($rates, fn($a, $b) => (float)$a['rate'] <=> (float)$b['rate']);

        return $rates[0] ?? null;
    }

    // ═══════════════════════════════════════════════════════════════
    // TRACKERS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create tracker
     */
    public function createTracker(string $trackingCode, string $carrier): array {
        return $this->request('POST', '/trackers', [
            'tracker' => [
                'tracking_code' => $trackingCode,
                'carrier' => $carrier
            ]
        ]);
    }

    /**
     * Get tracker
     */
    public function getTracker(string $trackerId): array {
        return $this->request('GET', "/trackers/{$trackerId}");
    }

    /**
     * Get trackers list
     */
    public function getTrackers(int $pageSize = 20, ?string $beforeId = null): array {
        $params = ['page_size' => $pageSize];
        if ($beforeId) $params['before_id'] = $beforeId;
        return $this->request('GET', '/trackers?' . http_build_query($params));
    }

    // ═══════════════════════════════════════════════════════════════
    // BATCHES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create batch
     */
    public function createBatch(array $shipments): array {
        return $this->request('POST', '/batches', ['batch' => ['shipments' => $shipments]]);
    }

    /**
     * Get batch
     */
    public function getBatch(string $batchId): array {
        return $this->request('GET', "/batches/{$batchId}");
    }

    /**
     * Add shipments to batch
     */
    public function addShipmentsToBatch(string $batchId, array $shipments): array {
        return $this->request('POST', "/batches/{$batchId}/add_shipments", ['shipments' => $shipments]);
    }

    /**
     * Remove shipments from batch
     */
    public function removeShipmentsFromBatch(string $batchId, array $shipments): array {
        return $this->request('POST', "/batches/{$batchId}/remove_shipments", ['shipments' => $shipments]);
    }

    /**
     * Buy batch
     */
    public function buyBatch(string $batchId): array {
        return $this->request('POST', "/batches/{$batchId}/buy");
    }

    /**
     * Generate batch label
     */
    public function generateBatchLabel(string $batchId, string $format = 'PDF'): array {
        return $this->request('POST', "/batches/{$batchId}/label", ['file_format' => $format]);
    }

    /**
     * Generate batch scan form
     */
    public function generateBatchScanForm(string $batchId): array {
        return $this->request('POST', "/batches/{$batchId}/scan_form");
    }

    // ═══════════════════════════════════════════════════════════════
    // SCAN FORMS (SCAN Barcodes)
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create scan form
     */
    public function createScanForm(array $shipmentIds): array {
        $shipments = array_map(fn($id) => ['id' => $id], $shipmentIds);
        return $this->request('POST', '/scan_forms', ['scan_form' => ['shipments' => $shipments]]);
    }

    /**
     * Get scan form
     */
    public function getScanForm(string $scanFormId): array {
        return $this->request('GET', "/scan_forms/{$scanFormId}");
    }

    /**
     * Get scan forms list
     */
    public function getScanForms(int $pageSize = 20): array {
        return $this->request('GET', "/scan_forms?page_size={$pageSize}");
    }

    // ═══════════════════════════════════════════════════════════════
    // INSURANCE
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create insurance
     */
    public function createInsurance(array $insuranceData): array {
        return $this->request('POST', '/insurances', ['insurance' => $insuranceData]);
    }

    /**
     * Get insurance
     */
    public function getInsurance(string $insuranceId): array {
        return $this->request('GET', "/insurances/{$insuranceId}");
    }

    /**
     * Get insurances list
     */
    public function getInsurances(int $pageSize = 20): array {
        return $this->request('GET', "/insurances?page_size={$pageSize}");
    }

    // ═══════════════════════════════════════════════════════════════
    // CARRIER ACCOUNTS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create carrier account
     */
    public function createCarrierAccount(array $carrierData): array {
        return $this->request('POST', '/carrier_accounts', ['carrier_account' => $carrierData]);
    }

    /**
     * Get carrier accounts
     */
    public function getCarrierAccounts(): array {
        return $this->request('GET', '/carrier_accounts');
    }

    /**
     * Update carrier account
     */
    public function updateCarrierAccount(string $carrierId, array $updates): array {
        return $this->request('PUT', "/carrier_accounts/{$carrierId}", ['carrier_account' => $updates]);
    }

    /**
     * Delete carrier account
     */
    public function deleteCarrierAccount(string $carrierId): array {
        return $this->request('DELETE', "/carrier_accounts/{$carrierId}");
    }

    // ═══════════════════════════════════════════════════════════════
    // CUSTOMS INFO
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create customs info
     */
    public function createCustomsInfo(array $customsData): array {
        return $this->request('POST', '/customs_infos', ['customs_info' => $customsData]);
    }

    /**
     * Create customs item
     */
    public function createCustomsItem(array $itemData): array {
        return $this->request('POST', '/customs_items', ['customs_item' => $itemData]);
    }

    // ═══════════════════════════════════════════════════════════════
    // WEBHOOKS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create webhook
     */
    public function createWebhook(string $url): array {
        return $this->request('POST', '/webhooks', ['webhook' => ['url' => $url]]);
    }

    /**
     * Get webhooks
     */
    public function getWebhooks(): array {
        return $this->request('GET', '/webhooks');
    }

    /**
     * Delete webhook
     */
    public function deleteWebhook(string $webhookId): array {
        return $this->request('DELETE', "/webhooks/{$webhookId}");
    }

    /**
     * Update webhook
     */
    public function updateWebhook(string $webhookId, string $url): array {
        return $this->request('PUT', "/webhooks/{$webhookId}", ['webhook' => ['url' => $url]]);
    }

    // ═══════════════════════════════════════════════════════════════
    // PICKUP
    // ═══════════════════════════════════════════════════════════════

    /**
     * Create pickup
     */
    public function createPickup(array $pickupData): array {
        return $this->request('POST', '/pickups', ['pickup' => $pickupData]);
    }

    /**
     * Buy pickup
     */
    public function buyPickup(string $pickupId, string $carrier, string $service): array {
        return $this->request('POST', "/pickups/{$pickupId}/buy", [
            'carrier' => $carrier,
            'service' => $service
        ]);
    }

    /**
     * Cancel pickup
     */
    public function cancelPickup(string $pickupId): array {
        return $this->request('POST', "/pickups/{$pickupId}/cancel");
    }

    // ═══════════════════════════════════════════════════════════════
    // HELPER METHODS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Build address structure
     */
    public static function buildAddress(
        string $name,
        string $street1,
        string $city,
        string $state,
        string $zip,
        string $country,
        ?string $street2 = null,
        ?string $company = null,
        ?string $phone = null,
        ?string $email = null
    ): array {
        return array_filter([
            'name' => $name,
            'street1' => $street1,
            'street2' => $street2,
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'country' => $country,
            'company' => $company,
            'phone' => $phone,
            'email' => $email
        ]);
    }

    /**
     * Build parcel structure
     */
    public static function buildParcel(
        float $length,
        float $width,
        float $height,
        float $weight,
        ?string $predefinedPackage = null
    ): array {
        if ($predefinedPackage) {
            return [
                'predefined_package' => $predefinedPackage,
                'weight' => $weight
            ];
        }

        return [
            'length' => $length,
            'width' => $width,
            'height' => $height,
            'weight' => $weight
        ];
    }

    /**
     * Supported carriers
     */
    public static function getSupportedCarriers(): array {
        return [
            'USPS' => 'United States Postal Service',
            'UPS' => 'United Parcel Service',
            'FedEx' => 'FedEx',
            'DHL' => 'DHL Express',
            'DHLExpress' => 'DHL Express',
            'DHLGlobalMail' => 'DHL Global Mail',
            'CanadaPost' => 'Canada Post',
            'RoyalMail' => 'Royal Mail (UK)',
            'AustraliaPost' => 'Australia Post',
            'LaserShip' => 'LaserShip',
            'OnTrac' => 'OnTrac',
            'Purolator' => 'Purolator',
            'Spee-Dee' => 'Spee-Dee Delivery',
            'APC' => 'APC Postal Logistics',
            'Asendia' => 'Asendia USA',
            'AxlehireV3' => 'Axlehire',
            'Better Trucks' => 'Better Trucks',
            'CloudSort' => 'CloudSort',
            'ColumbusLastMile' => 'Columbus Last Mile',
            'Courier Express' => 'Courier Express',
            'CouriersPleaseAustralia' => 'Couriers Please (AU)',
            'DaiPost' => 'Dai Post',
            'DeliverIt' => 'Deliver-It',
            'DPD' => 'DPD',
            'DPDUK' => 'DPD UK',
            'Estafeta' => 'Estafeta',
            'Fastway' => 'Fastway',
            'FirstMile' => 'First Mile',
            'Globegistics' => 'Globegistics',
            'GSO' => 'Golden State Overnight',
            'Hermes' => 'Hermes',
            'InterlinkExpress' => 'DPD Interlink Express',
            'JitsuExpress' => 'Jitsu Express',
            'LSO' => 'Lone Star Overnight',
            'Newgistics' => 'Newgistics',
            'Norco' => 'Norco',
            'Optima' => 'Optima',
            'Osm' => 'OSM Worldwide',
            'Parcelforce' => 'Parcelforce',
            'PassportGlobal' => 'Passport',
            'PostNL' => 'PostNL',
            'RRDonnelley' => 'RR Donnelley',
            'Seko' => 'SEKO Logistics',
            'SpeeDeeDelivery' => 'Spee-Dee Delivery',
            'StarTrack' => 'Star Track',
            'TForce' => 'TForce',
            'Toll' => 'Toll',
            'UDS' => 'UDS',
            'Veho' => 'Veho',
            'Yanwen' => 'Yanwen'
        ];
    }

    /**
     * Predefined package types
     */
    public static function getPredefinedPackages(): array {
        return [
            // USPS
            'Card' => 'USPS Card',
            'Letter' => 'USPS Letter',
            'Flat' => 'USPS Flat',
            'FlatRateEnvelope' => 'USPS Flat Rate Envelope',
            'FlatRateLegalEnvelope' => 'USPS Flat Rate Legal Envelope',
            'FlatRatePaddedEnvelope' => 'USPS Flat Rate Padded Envelope',
            'SmallFlatRateBox' => 'USPS Small Flat Rate Box',
            'MediumFlatRateBox' => 'USPS Medium Flat Rate Box',
            'LargeFlatRateBox' => 'USPS Large Flat Rate Box',
            'LargeFlatRateBoxAPOFPO' => 'USPS Large Flat Rate Box APO/FPO',
            'RegionalRateBoxA' => 'USPS Regional Rate Box A',
            'RegionalRateBoxB' => 'USPS Regional Rate Box B',
            'Parcel' => 'USPS Parcel',
            // UPS
            'UPSLetter' => 'UPS Letter',
            'UPSExpressBox' => 'UPS Express Box',
            'UPS25kgBox' => 'UPS 25kg Box',
            'UPS10kgBox' => 'UPS 10kg Box',
            'Tube' => 'UPS Tube',
            'Pak' => 'UPS Pak',
            // FedEx
            'FedExEnvelope' => 'FedEx Envelope',
            'FedExBox' => 'FedEx Box',
            'FedExPak' => 'FedEx Pak',
            'FedExTube' => 'FedEx Tube',
            'FedEx10kgBox' => 'FedEx 10kg Box',
            'FedEx25kgBox' => 'FedEx 25kg Box',
            'FedExSmallBox' => 'FedEx Small Box',
            'FedExMediumBox' => 'FedEx Medium Box',
            'FedExLargeBox' => 'FedEx Large Box',
            'FedExExtraLargeBox' => 'FedEx Extra Large Box'
        ];
    }
}
