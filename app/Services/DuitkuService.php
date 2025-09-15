<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DuitkuService
{
    private $merchantCode;
    private $apiKey;
    private $baseUrl;

    public function __construct()
    {
        $this->merchantCode = env('DUITKU_MERCHANT_CODE');
        $this->apiKey = env('DUITKU_API_KEY');
        $this->baseUrl = env('DUITKU_BASE_URL', 'https://sandbox.duitku.com');
    }

    /**
     * Get available payment methods from DUITKU
     */
    public function getPaymentMethods($amount = 10000)
    {
        $datetime = date('Y-m-d H:i:s');

        // Signature format: merchantCode + amount + datetime + apiKey
        $signatureString = $this->merchantCode . $amount . $datetime . $this->apiKey;
        $signature = hash('sha256', $signatureString);

        $requestData = [
            'merchantcode' => $this->merchantCode,
            'amount' => $amount,
            'datetime' => $datetime,
            'signature' => $signature
        ];

        Log::info('DUITKU Payment Methods Request:', $requestData);

        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/webapi/api/merchant/paymentmethod/getpaymentmethod', $requestData);

            Log::info('DUITKU Payment Methods Response:', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['responseCode'] == '00') {
                    return [
                        'success' => true,
                        'data' => $data['paymentFee']
                    ];
                }

                return [
                    'success' => false,
                    'message' => $data['responseMessage'] ?? 'Failed to get payment methods'
                ];
            }

            return [
                'success' => false,
                'message' => 'HTTP Error: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('DUITKU Payment Methods Error:', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create payment transaction
     */
    public function createTransaction($data)
    {
        $datetime = date('Y-m-d H:i:s');

        // Signature format for transaction: merchantCode + merchantOrderId + amount + apiKey
        $signatureString = $this->merchantCode . $data['order_id'] . $data['amount'] . $this->apiKey;
        $signature = md5($signatureString);

        $params = [
            'merchantCode' => $this->merchantCode,
            'paymentAmount' => $data['amount'],
            'paymentMethod' => $data['payment_method'],
            'merchantOrderId' => $data['order_id'],
            'productDetails' => $data['product_details'],
            'customerVaName' => $data['customer_name'],
            'email' => $data['email'],
            'phoneNumber' => $data['phone'],
            'callbackUrl' => $data['callback_url'] ?? url('/api/duitku/callback'),
            'returnUrl' => $data['return_url'] ?? url('/payment/success'),
            'signature' => $signature,
            'expiryPeriod' => $data['expiry_period'] ?? 1440 // 24 hours default
        ];

                // Tambahkan itemDetails jika ada
        if (isset($data['item_details']) && is_array($data['item_details'])) {
            $params['itemDetails'] = $data['item_details'];
        }

        Log::info('DUITKU Create Transaction Request:', $params);

        try {
            $response = Http::timeout(30)->post($this->baseUrl . '/webapi/api/merchant/v2/inquiry', $params);

            Log::info('DUITKU Create Transaction Response:', [
                'status' => $response->status(),
                'body' => $response->json()
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['statusCode'] == '00') {
                    return [
                        'success' => true,
                        'data' => $data
                    ];
                }

                return [
                    'success' => false,
                    'message' => $data['statusMessage'] ?? 'Transaction creation failed'
                ];
            }

            return [
                'success' => false,
                'message' => 'HTTP Error: ' . $response->status()
            ];
        } catch (\Exception $e) {
            Log::error('DUITKU Create Transaction Error:', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify callback signature
     */
    public function verifyCallback($request)
    {
        $merchantCode = $request->merchantCode ?? '';
        $amount = $request->amount ?? '';
        $merchantOrderId = $request->merchantOrderId ?? '';
        $productDetail = $request->productDetail ?? '';
        $additionalParam = $request->additionalParam ?? '';
        $paymentCode = $request->paymentCode ?? '';
        $resultCode = $request->resultCode ?? '';
        $merchantUserId = $request->merchantUserId ?? '';
        $reference = $request->reference ?? '';
        $publisherOrderId = $request->publisherOrderId ?? '';
        $spUserHash = $request->spUserHash ?? '';
        $settlementDate = $request->settlementDate ?? '';
        $issuerReffNum = $request->issuerReffNum ?? '';

        $receivedSignature = $request->signature ?? '';

        // Callback signature format
        $signatureString = $merchantCode . $amount . $merchantOrderId . $productDetail .
            $additionalParam . $paymentCode . $resultCode . $merchantUserId .
            $reference . $publisherOrderId . $spUserHash . $settlementDate .
            $issuerReffNum . $this->apiKey;

        $calculatedSignature = hash('sha256', $signatureString);

        Log::info('DUITKU Callback Verification:', [
            'received_signature' => $receivedSignature,
            'calculated_signature' => $calculatedSignature,
            'signature_string' => $signatureString,
            'is_valid' => $receivedSignature === $calculatedSignature
        ]);

        return [
            'is_valid' => $receivedSignature === $calculatedSignature,
            'result_code' => $resultCode,
            'merchant_order_id' => $merchantOrderId,
            'reference' => $reference,
            'amount' => $amount
        ];
    }

    /**
     * Transform payment methods for frontend
     */
    public function transformPaymentMethods($paymentMethods)
    {
        $transformedMethods = [];

        foreach ($paymentMethods as $method) {
            $category = $this->categorizePaymentMethod($method['paymentMethod']);

            if (!isset($transformedMethods[$category['type']])) {
                $transformedMethods[$category['type']] = [
                    'id' => $category['type'],
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'options' => []
                ];
            }

            $transformedMethods[$category['type']]['options'][] = [
                'title' => $method['paymentName'],
                'value' => $method['paymentMethod'],
                'fee' => $method['totalFee']
            ];
        }

        return array_values($transformedMethods);
    }

    /**
     * Categorize payment methods by type
     */
    private function categorizePaymentMethod($paymentMethod)
    {
        $categories = [
            // Virtual Account
            'BC' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'BR' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'I1' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'M2' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'BT' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'A1' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'B1' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'VA' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'AG' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'NC' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],
            'BV' => ['type' => 'va', 'name' => 'Virtual Account', 'icon' => 'mdi-bank'],

            // E-Wallet
            'OV' => ['type' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'mdi-wallet'],
            'OL' => ['type' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'mdi-wallet'],
            'DA' => ['type' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'mdi-wallet'],
            'SA' => ['type' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'mdi-wallet'],
            'SL' => ['type' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'mdi-wallet'],
            'LA' => ['type' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'mdi-wallet'],
            'JP' => ['type' => 'ewallet', 'name' => 'E-Wallet', 'icon' => 'mdi-wallet'],

            // QRIS
            'SP' => ['type' => 'qris', 'name' => 'QRIS', 'icon' => 'mdi-qrcode'],
            'LQ' => ['type' => 'qris', 'name' => 'QRIS', 'icon' => 'mdi-qrcode'],
            'NQ' => ['type' => 'qris', 'name' => 'QRIS', 'icon' => 'mdi-qrcode'],
            'GQ' => ['type' => 'qris', 'name' => 'QRIS', 'icon' => 'mdi-qrcode'],

            // Credit Card
            'VC' => ['type' => 'card', 'name' => 'Kartu Kredit', 'icon' => 'mdi-credit-card'],

            // Retail
            'IR' => ['type' => 'retail', 'name' => 'Retail', 'icon' => 'mdi-store'],
            'FT' => ['type' => 'retail', 'name' => 'Retail', 'icon' => 'mdi-store'],

            // Paylater
            'DN' => ['type' => 'paylater', 'name' => 'PayLater', 'icon' => 'mdi-credit-card-clock']
        ];

        return $categories[$paymentMethod] ?? ['type' => 'other', 'name' => 'Lainnya', 'icon' => 'mdi-cash'];
    }
}
