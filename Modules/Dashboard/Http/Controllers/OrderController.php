<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Resources\OrderResource;
use App\Mail\FinishedOrderMail;
use App\Mail\ProcesOrderMail;
use App\Mail\RejectOrderMail;
use App\Models\Order;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        //
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
                'orderItems'
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
        //
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

    public function process(Request $request, $id)
    {
        $order = Order::with([
            'customer.user',
            'invoice'
        ])->withTrashed()->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        $order->status = 'process';
        $order->save();

        try {
            Mail::to($order->customer->user->email)->send(new ProcesOrderMail($order, $order->customer, $order->customer->user));
        } catch (\Exception $mailException) {
            // Log error email tapi tetap lanjutkan proses
            \Log::error('Failed to send in progress email: ' . $mailException->getMessage());
        }

        return response()->json([
            'message' => 'Order sedang diproses.',
            'data' => new OrderResource($order),
        ]);
    }

    public function finished(Request $request, $id)
    {
        $order = Order::with([
            'customer.user',
            'invoice'
        ])->withTrashed()->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        $order->status = 'finished';
        $order->save();

        try {
            Mail::to($order->customer->user->email)->send(new FinishedOrderMail($order, $order->customer, $order->customer->user));
        } catch (\Exception $mailException) {
            // Log error email tapi tetap lanjutkan proses
            \Log::error('Failed to send finished order email: ' . $mailException->getMessage());
        }

        return response()->json([
            'message' => 'Order Selesai Diproses.',
            'data' => new OrderResource($order),
        ]);
    }

    public function reject(Request $request, $id)
    {
        $order = Order::with('customer.user')->withTrashed()->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order tidak ditemukan'], 404);
        }

        $order->status = 'reject';
        $order->save();

        try {
            Mail::to($order->customer->user->email)->send(new RejectOrderMail($order, $order->customer, $order->customer->user));
        } catch (\Exception $mailException) {
            // Log error email tapi tetap lanjutkan proses
            \Log::error('Failed to send reject order email: ' . $mailException->getMessage());
        }

        return response()->json([
            'message' => 'Proses Order Ditolak.',
            'data' => new OrderResource($order),
        ]);
    }

    public function export(Request $request)
    {
        $orderByColumn = 'created_at';
        $orderByDirection = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $ordersQuery = Order::query()
            ->with(['customer.user', 'table'])
            ->withTrashed();

        // 🔎 Filter search
        $ordersQuery->when(!empty($request->search), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('created_at', 'like', '%' . $request->search . '%')
                    ->orWhere('code', 'like', '%' . $request->search . '%')
                    ->orWhereHas(
                        'customer',
                        fn($qq) =>
                        $qq->where('name', 'like', '%' . $request->search . '%')
                    );
            });
        });

        // 🔎 Filter status
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

        // 🔎 Filter tanggal
        if ($request->filled('from_date')) {
            $ordersQuery->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $ordersQuery->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
        }

        $ordersQuery->orderBy($orderByColumn, $orderByDirection);

        // 🔎 Buat folder kalau belum ada
        $exportDir = 'OrdersExport';
        if (!is_dir(Storage::disk('public')->path($exportDir))) {
            mkdir(Storage::disk('public')->path($exportDir), 0775, true);
            chmod(Storage::disk('public')->path($exportDir), 0775);
        }

        // 🔎 Setup Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'A1' => 'Kode Order',
            'B1' => 'Tanggal Order',
            'C1' => 'Customer',
            'D1' => 'Meja',
            'E1' => 'Subtotal',
            'F1' => 'Pajak',
            'G1' => 'Total',
            'H1' => 'Status',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        $columnWidths = [
            'A' => 20,
            'B' => 15,
            'C' => 30,
            'D' => 15,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 15,
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // 🔎 Isi data
        $row = 2;
        $ordersQuery->chunk(1000, function ($orders) use ($sheet, &$row) {
            foreach ($orders as $order) {
                $sheet->setCellValue("A{$row}", $order->code);
                $sheet->setCellValue("B{$row}", $order->order_date ?? $order->created_at->format('Y-m-d'));
                $sheet->setCellValue("C{$row}", $order->customer->name ?? '-');
                $sheet->setCellValue("D{$row}", $order->table->id ?? '-');
                $sheet->setCellValue("E{$row}", $order->subtotal);
                $sheet->setCellValue("F{$row}", $order->tax);
                $sheet->setCellValue("G{$row}", $order->total_net);
                $sheet->setCellValue("H{$row}", $order->deleted_at ? 'Non Aktif' : 'Aktif');
                $row++;
            }
        });

        // 🔎 Simpan file
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "Orders_Export_{$timestamp}.xlsx";
        $filePath = Storage::disk('public')->path($exportDir . '/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $fileHeaders = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return response()->download($filePath, $fileName, $fileHeaders)->deleteFileAfterSend();
    }
}
