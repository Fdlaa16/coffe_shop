<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestDuitku extends Command
{
    protected $signature = 'duitku:test';
    protected $description = 'Test DUITKU API connection';

    public function handle()
    {
        $merchantCode = env('DUITKU_MERCHANT_CODE');
        $apiKey = env('DUITKU_API_KEY');
        $baseUrl = env('DUITKU_BASE_URL', 'https://sandbox.duitku.com');

        $this->info('Testing DUITKU Configuration...');
        $this->line('Merchant Code: ' . ($merchantCode ?: 'NOT SET'));
        $this->line('API Key: ' . ($apiKey ? substr($apiKey, 0, 10) . '...' : 'NOT SET'));
        $this->line('Base URL: ' . $baseUrl);

        if (!$merchantCode || !$apiKey) {
            $this->error('DUITKU credentials are not configured!');
            return 1;
        }

        $datetime = date('Y-m-d H:i:s');
        $signature = hash('sha256', $merchantCode . $apiKey . $datetime);

        $this->line('Datetime: ' . $datetime);
        $this->line('Signature: ' . $signature);

        $requestData = [
            'merchantcode' => $merchantCode,
            'amount' => 10000,
            'datetime' => $datetime,
            'signature' => $signature
        ];

        // Test berbagai format datetime
        $datetimeFormats = [
            'Y-m-d H:i:s',      // 2025-09-13 16:34:37
            'Y-m-d H:i:s O',    // 2025-09-13 16:34:37 +0700
            'd-m-Y H:i:s',      // 13-09-2025 16:34:37
        ];

        foreach ($datetimeFormats as $index => $format) {
            $this->info("Testing datetime format #{$index}: {$format}");

            $datetime = date($format);
            $amount = 10000;
            $signatureString = $merchantCode . $amount . $datetime . $apiKey;
            $signature = hash('sha256', $signatureString);

            $this->line('Datetime: ' . $datetime);
            $this->line('Signature String: ' . $signatureString);
            $this->line('Signature: ' . $signature);

            $requestData = [
                'merchantcode' => $merchantCode,
                'amount' => $amount,
                'datetime' => $datetime,
                'signature' => $signature
            ];

            try {
                $response = Http::timeout(30)->post($baseUrl . '/webapi/api/merchant/paymentmethod/getpaymentmethod', $requestData);

                $this->line('HTTP Status: ' . $response->status());

                if ($response->successful()) {
                    $data = $response->json();
                    $this->line('Response Code: ' . ($data['responseCode'] ?? 'N/A'));

                    if ($data['responseCode'] == '00') {
                        $this->info('SUCCESS with format: ' . $format);
                        $this->info('Available payment methods:');
                        foreach ($data['paymentFee'] as $method) {
                            $this->line('- ' . $method['paymentName'] . ' (' . $method['paymentMethod'] . ')');
                        }
                        return 0; // Exit on success
                    } else {
                        $this->error('API Error: ' . ($data['responseMessage'] ?? 'Unknown error'));
                    }
                } else {
                    $this->error('HTTP Error: ' . $response->status());
                    $this->line('Response: ' . $response->body());
                }
            } catch (\Exception $e) {
                $this->error('Exception: ' . $e->getMessage());
            }

            $this->line('---');
        }

        return 0;
    }
}
