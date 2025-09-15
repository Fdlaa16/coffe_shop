<?php

namespace Modules\Order\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Resources\OrderResource;
use App\Mail\OrderMail;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Table;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('dashboard::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $postData = $request->all();

            $rules = [
                'name' => 'required|string',
                'phone' => 'required|string',
                'email' => 'required|email',
                'cartItems' => 'required|array|min:1',
                'cartItems.*.menu_id' => 'required',
                'cartItems.*.menu_name' => 'required|string',
                'cartItems.*.qty' => 'required|numeric|min:1',
                'cartItems.*.price' => 'required|numeric|min:0',
                'cartItems.*.subtotal' => 'required|numeric|min:0',
                'cartItems.*.size' => 'nullable|string',
                'cartItems.*.sugar_level' => 'nullable|string',
                'cartItems.*.notes' => 'nullable|string',
                'cartItems.*.category' => 'required|string',
                'option' => 'nullable|string',
                'total_items' => 'required|numeric|min:1',
                'subtotal' => 'required|numeric|min:0',
                'tax' => 'required|numeric|min:0',
                'total_payment' => 'required|numeric|min:0',
                'table_id' => 'nullable|exists:tables,id'
            ];

            $messages = [
                'name.required' => 'Nama harus diisi',
                'phone.required' => 'Nomor telepon harus diisi',
                'email.required' => 'Email harus diisi',
                'email.email' => 'Format email tidak valid',
                'cartItems.required' => 'Harus ada menu yang dipilih',
                'cartItems.array' => 'Format cart items tidak valid',
                'cartItems.min' => 'Minimal harus ada 1 item',
                'cartItems.*.menu_id.required' => 'ID menu harus diisi',
                'cartItems.*.qty.required' => 'Jumlah menu harus diisi',
                'cartItems.*.qty.numeric' => 'Jumlah menu harus angka',
                'cartItems.*.qty.min' => 'Jumlah menu minimal 1',
                'cartItems.*.price.required' => 'Harga harus diisi',
                'cartItems.*.price.numeric' => 'Harga harus angka',
                'cartItems.*.price.min' => 'Harga minimal 0',
                'cartItems.*.subtotal.required' => 'Subtotal harus diisi',
                'cartItems.*.subtotal.numeric' => 'Subtotal harus angka',
                'cartItems.*.category.required' => 'Kategori menu harus diisi',
                'total_items.required' => 'Total items harus diisi',
                'subtotal.required' => 'Subtotal harus diisi',
                'tax.required' => 'Pajak harus diisi',
                'total_payment.required' => 'Total pembayaran harus diisi',
                'table_id.exists' => 'Meja tidak ditemukan'
            ];

            $validator = Validator::make($postData, $rules, $messages);

            if ($validator->fails()) {
                return response()->json(array('errors' => $validator->messages()->toArray()), 422);
            } else {

                $customer = Customer::updateOrCreate(
                    ['phone' => $request->phone],
                    ['name' => $request->name, 'phone' => $request->phone]
                );

                $user = User::updateOrCreate(
                    ['email' => $request->email],
                    [
                        'name'              => $request->name,
                        'email'             => $request->email,
                        'password'          => Hash::make('default123'),
                        'email_verified_at' => now()
                    ]
                );

                $customer->update(['user_id' => $user->id]);

                $order = Order::create([
                    'customer_id'   => $customer->id,
                    'table_id'      => $request->table_id,
                    'order_date'    => now(),
                    'subtotal'      => $request->subtotal,
                    'tax'           => $request->tax,
                    'total_net'     => $request->total_payment,
                    'status'        => 'pending',
                ]);

                foreach ($request->cartItems as $item) {
                    $menu = Menu::find($item['menu_id']);
                    if (!$menu) {
                        throw new \Exception("Menu dengan ID {$item['menu_id']} tidak ditemukan");
                    }

                    OrderItem::create([
                        'order_id'    => $order->id,
                        'menu_id'     => $item['menu_id'],
                        'menu_name'   => $item['menu_name'],
                        'qty'         => (int) $item['qty'],
                        'unit_price'  => (float) $item['price'],
                        'total_price' => (float) $item['subtotal'],
                        'size'        => $item['size'] ?? 'Regular',
                        'sugar_level' => $item['sugar_level'] ?? 'Normal',
                        'notes'       => $item['notes'] ?? null,
                        'category'    => $item['category'],
                    ]);
                }

                $invoice = Invoice::create([
                    'invoice_number' => Invoice::generateInvoiceNumber(),
                    'order_id'       => $order->id,
                    'customer_id'    => $customer->id,
                    'invoice_date'   => now(),
                    'type'           => $request->order_type,
                    'subtotal'       => $request->subtotal,
                    'tax'            => $request->tax,
                    'total_net'      => $request->total_payment,
                    'status'         => 'paid',
                ]);

                foreach ($request->cartItems as $item) {
                    InvoiceItem::create([
                        'invoice_id'  => $invoice->id,
                        'menu_id'     => $item['menu_id'],
                        'menu_name'   => $item['menu_name'],
                        'qty'         => (int) $item['qty'],
                        'unit_price'  => (float) $item['price'],
                        'total_price' => (float) $item['subtotal'],
                        'size'        => $item['size'] ?? 'Regular',
                        'sugar_level' => $item['sugar_level'] ?? 'Normal',
                        'notes'       => $item['notes'] ?? null,
                        'category'    => $item['category'],
                        'status'      => 'paid'
                    ]);
                }

                // 8. Send email (try-catch agar tidak ganggu transaksi)
                try {
                    Mail::to($user->email)->send(new OrderMail($order, $customer, $user));
                } catch (\Exception $mailException) {
                    \Log::error('Failed to send order email: ' . $mailException->getMessage());
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Order berhasil dibuat',
                    'data' => [
                        'order' => $order->load(['customer', 'orderItems', 'table']),
                        'invoice' => $invoice,
                        'customer' => $customer
                    ]
                ], 201);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Order creation failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat membuat order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('dashboard::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
