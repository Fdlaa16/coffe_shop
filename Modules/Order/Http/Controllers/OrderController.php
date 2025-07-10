<?php

namespace Modules\Order\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index(Request $request)
    {
        $orderByColumn = 'created_at';
        $orderByDirection = 'desc';

        if ($request->has('sort')) {
            $orderByDirection = $request->input('sort') === 'asc' ? 'asc' : 'desc';
        }

        $ordersQuery = Order::query()
            ->with([
                'customer.user',
                'table'
            ])
            ->withTrashed();

        $ordersQuery->when(!empty($request->search), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('created_at', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('title', 'like', '%' . $request->search . '%')
                    ->orWhere('hashtag', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('link', 'like', '%' . $request->search . '%');
            });
        });

        $ordersQuery->when($request->status, function ($query, $status) {
            switch ($status) {
                case 'in_active':
                    $query->onlyTrashed();
                    break;
                case 'active':
                    $query->whereNull('deleted_at');
                    break;
                case 'all':
                default:
                    break;
            }
        });

        if ($request->filled('from_date')) {
            $ordersQuery->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
        }

        if ($request->filled('to_date')) {
            $ordersQuery->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
        }

        $ordersQuery->orderBy($orderByColumn, $orderByDirection);

        $orders = $ordersQuery->get();

        $statusCounts = DB::table('orders')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN deleted_at IS NULL THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as in_active
            ')
            ->when($request->filled('from_date'), function ($q) use ($request) {
                $q->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
            })
            ->when($request->filled('to_date'), function ($q) use ($request) {
                $q->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
            })
            ->first();

        $responseTotals = [
            'all' => (int) $statusCounts->total,
            'active' => (int) $statusCounts->active,
            'in_active' => (int) $statusCounts->in_active,
        ];

        return response()->json([
            'data' => $orders,
            'totals' => $responseTotals,
        ]);
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
        // DB::beginTransaction();
        // try {
        //     $postData = $request->all();
        //     $rules = [
        //         'name'      => 'required',
        //         'email'     => 'required|email|unique:users,email',
        //         'phone'     => 'required',
        //     ];

        //     $messages = [
        //         'name.required'      => 'Nama harus diisi',
        //         'email.required'     => 'Email harus diisi',
        //         'email.email'        => 'Format email tidak valid',
        //         'email.unique'       => 'Email sudah digunakan',
        //         'phone.required'     => 'Nomor Telepon harus diisi',
        //     ];

        //     $validator = Validator::make($postData, $rules, $messages);

        //     if ($validator->fails()) {
        //         return response()->json(['errors' => $validator->messages()->toArray()], 422);
        //     } else {
        //         $user = User::create(['email' => $request->email]);

        //         $customer = Customer::create([
        //             'user_id' => $user->id,
        //             'name' => $request->name,
        //             'phone' => $request->phone,
        //         ]);

        //         DB::commit();
        //         return response()->json([
        //             'message' => 'Customer created successfully.',
        //             'data' => $customer
        //         ], 201);
        //     }
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'message' => 'Something went wrong',
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
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
        $orders = Order::query()
            ->with([
                'customer.user',
                'table',
                'orderItem'
            ])
            ->find($id);

        $data = [
            'data' => $orders,
        ];

        return $data;
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        // DB::beginTransaction();
        // try {
        //     $customer = customer::findOrFail($id);
        //     $postData = $request->all();

        //     $rules = [
        //         'name'      => 'required',
        //         'email'     => 'required|email|unique:users,email,' . $customer->user_id,
        //         'phone'     => 'required',
        //     ];

        //     $messages = [
        //         'name.required'      => 'Nama harus diisi',
        //         'email.required'     => 'Email harus diisi',
        //         'email.email'        => 'Format email tidak valid',
        //         'email.unique'       => 'Email sudah digunakan',
        //         'phone.required'     => 'Nomor Telepon harus diisi',
        //     ];

        //     $validator = Validator::make($postData, $rules, $messages);
        //     if ($validator->fails()) {
        //         return response()->json(['errors' => $validator->messages()->toArray()], 422);
        //     }

        //     $customer->update([
        //         'name' => $request->name,
        //         'phone' => $request->phone,
        //     ]);

        //     $customer->user->update([
        //         'email' => $request->email
        //     ]);

        //     DB::commit();
        //     return response()->json([
        //         'message' => 'Customer updated successfully.',
        //         'data' => $customer
        //     ], 200);
        // } catch (\Exception $e) {
        //     DB::rollBack();
        //     return response()->json([
        //         'message' => 'Something went wrong',
        //         'error' => $e->getMessage()
        //     ], 500);
        // }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $order = Order::withTrashed()->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order berhasil dihapus.',
            'data' => new OrderResource($order),
        ]);
    }

    public function active(Request $request, $id)
    {
        $order = Order::withTrashed()->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        $order->restore();

        return response()->json([
            'message' => 'Order berhasil diaktifkan.',
            'data' => new OrderResource($order),
        ]);
    }
}
