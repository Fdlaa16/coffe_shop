<?php

namespace App\Http\Controllers;

use App\Services\DuitkuService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private $duitkuService;

    public function __construct(DuitkuService $duitkuService)
    {
        $this->duitkuService = $duitkuService;
    }

    /**
     * Get available payment methods from DUITKU
     */
    public function getPaymentMethods(Request $request)
    {
        $amount = $request->get('amount', 10000); // Default minimal amount

        $response = $this->duitkuService->getPaymentMethods($amount);

        if ($response['success']) {
            // Transform data untuk Vue component
            $transformedMethods = $this->duitkuService->transformPaymentMethods($response['data']);

            return response()->json([
                'success' => true,
                'data' => $transformedMethods
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Failed to get payment methods'
        ]);
    }

    /**
     * Create payment transaction
     */
    public function createPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1000', // Minimal Rp 1.000
            'payment_method' => 'required|string',
            'customer_name' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:15',
            'product_details' => 'required|string|max:255'
        ]);

        $orderId = 'ORDER-' . time() . '-' . Str::random(6);

        $transactionData = [
            'order_id' => $orderId,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'customer_name' => $request->customer_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'product_details' => $request->product_details,
            'callback_url' => url('/api/duitku/callback'),
            'return_url' => url('/payment/success'),
            'expiry_period' => 1440 // 24 hours
        ];

        $response = $this->duitkuService->createTransaction($transactionData);

        if ($response['success']) {
            $data = $response['data'];

            // Optional: Save transaction to database
            $this->saveTransactionLog($orderId, $request->all(), $data);

            return response()->json([
                'success' => true,
                'data' => [
                    'payment_url' => $data['paymentUrl'],
                    'reference' => $data['reference'],
                    'va_number' => $data['vaNumber'] ?? null,
                    'qr_string' => $data['qrString'] ?? null,
                    'amount' => $data['amount'],
                    'fee' => $data['fee'] ?? 0,
                    'order_id' => $orderId
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? 'Payment creation failed'
        ]);
    }

    /**
     * Handle DUITKU callback
     */
    public function callback(Request $request)
    {
        Log::info('DUITKU Callback Received:', $request->all());

        $verification = $this->duitkuService->verifyCallback($request);

        if ($verification['is_valid']) {
            if ($verification['result_code'] == '00') {
                // Payment success
                Log::info('Payment Success:', [
                    'order_id' => $verification['merchant_order_id'],
                    'reference' => $verification['reference'],
                    'amount' => $verification['amount']
                ]);

                // Update transaction status in database
                $this->updateTransactionStatus($verification['merchant_order_id'], 'paid', $verification['reference']);

                return response('OK');
            } else {
                // Payment failed
                Log::warning('Payment Failed:', [
                    'order_id' => $verification['merchant_order_id'],
                    'result_code' => $verification['result_code']
                ]);

                $this->updateTransactionStatus($verification['merchant_order_id'], 'failed');
                return response('FAILED');
            }
        } else {
            // Invalid signature
            Log::error('Invalid Callback Signature:', $request->all());
            return response('Invalid signature', 400);
        }
    }

    /**
     * Payment success page
     */
    public function paymentSuccess(Request $request)
    {
        $reference = $request->get('reference');
        $merchantOrderId = $request->get('merchantOrderId');

        return view('payment.success', [
            'reference' => $reference,
            'order_id' => $merchantOrderId
        ]);
    }

    /**
     * Check payment status
     */
    public function checkPaymentStatus($orderId)
    {
        // Optional: Implement payment status check
        // This would query your database for transaction status

        return response()->json([
            'success' => true,
            'data' => [
                'order_id' => $orderId,
                'status' => 'pending' // or 'paid', 'failed', 'expired'
            ]
        ]);
    }

    /**
     * Save transaction log to database (optional)
     */
    private function saveTransactionLog($orderId, $requestData, $duitkuResponse)
    {
        // Implement database save logic here
        // Example:
        /*
        DB::table('payment_transactions')->insert([
            'order_id' => $orderId,
            'reference' => $duitkuResponse['reference'],
            'payment_method' => $requestData['payment_method'],
            'amount' => $requestData['amount'],
            'customer_name' => $requestData['customer_name'],
            'email' => $requestData['email'],
            'phone' => $requestData['phone'],
            'product_details' => $requestData['product_details'],
            'status' => 'pending',
            'duitku_response' => json_encode($duitkuResponse),
            'created_at' => now(),
            'updated_at' => now()
        ]);
        */
    }

    /**
     * Update transaction status (optional)
     */
    private function updateTransactionStatus($orderId, $status, $reference = null)
    {
        // Implement database update logic here
        // Example:
        /*
        $updateData = [
            'status' => $status,
            'updated_at' => now()
        ];
        
        if ($reference) {
            $updateData['reference'] = $reference;
            if ($status === 'paid') {
                $updateData['paid_at'] = now();
            }
        }
        
        DB::table('payment_transactions')
            ->where('order_id', $orderId)
            ->update($updateData);
        */
    }
}
