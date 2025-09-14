<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InvoiceController extends Controller
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

        $invoicesQuery = Invoice::query()
            ->with([
                'invoiceItems'
            ])
            ->withTrashed();

        $invoicesQuery->when(!empty($request->search), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('created_at', 'like', '%' . $request->search . '%')
                    ->orWhere('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhere('title', 'like', '%' . $request->search . '%')
                    ->orWhere('hashtag', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('link', 'like', '%' . $request->search . '%');
            });
        });

        $invoicesQuery->when($request->status, function ($query, $status) {
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
            $invoicesQuery->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
        }

        if ($request->filled('to_date')) {
            $invoicesQuery->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
        }

        $invoicesQuery->orderBy($orderByColumn, $orderByDirection);

        $invoices = $invoicesQuery->get();

        $statusCounts = DB::table('invoices')
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
            'data' => $invoices,
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
        $invoice = Invoice::query()
            ->with([
                'customer.user',
                'invoiceItems',
                'order.table' // Add table relationship if needed
            ])
            ->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        // Transform data to match frontend structure
        $data = [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'invoice_date' => $invoice->invoice_date,
            'expired_date' => $invoice->expired_date,
            'order_id' => $invoice->order_id,
            'customer_id' => $invoice->customer_id,
            'subtotal' => $invoice->subtotal,
            'tax' => $invoice->tax,
            'total_net' => $invoice->total_net,
            'status' => $invoice->status,
            'type' => $invoice->type,

            // Customer information
            'customer' => $invoice->customer ? [
                'id' => $invoice->customer->id,
                'name' => $invoice->customer->name,
                'phone' => $invoice->customer->phone,
                'code' => $invoice->customer->code,
                'user' => $invoice->customer->user ? [
                    'id' => $invoice->customer->user->id,
                    'email' => $invoice->customer->user->email,
                    'name' => $invoice->customer->user->name,
                ] : null
            ] : null,

            // Invoice items with proper structure
            'invoice_items' => $invoice->invoiceItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'invoice_id' => $item->invoice_id,
                    'code' => $item->code,
                    'menu_name' => $item->menu_name, // Match template expectation
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'total_price' => $item->total_price, // Match template expectation
                    'total' => $item->total_price, // For interface compatibility
                    'size' => $item->size,
                    'sugar_level' => $item->sugar_level,
                    'notes' => $item->notes,
                    'category' => $item->category,
                    'status' => $item->status,
                    'type' => 'product' // Default type for interface
                ];
            })->toArray(),

            // Additional fields that template expects
            'order_date' => $invoice->invoice_date, // Use invoice_date as order_date
            'payment_method' => $invoice->payment_method ?? null,
            'notes' => $invoice->notes ?? null,

            // Table information if available through order
            'table' => $invoice->order && $invoice->order->table ? [
                'code' => $invoice->order->table->code ?? 'N/A'
            ] : null
        ];

        return response()->json([
            'data' => $data
        ]);
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
        $invoice = Invoice::withTrashed()->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        $invoice->delete();

        return response()->json([
            'message' => 'invoice berhasil dihapus.',
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function active(Request $request, $id)
    {
        $invoice = Invoice::withTrashed()->find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice tidak ditemukan'], 404);
        }

        $invoice->restore();

        return response()->json([
            'message' => 'Invoice berhasil diaktifkan.',
            'data' => new InvoiceResource($invoice),
        ]);
    }

    public function downloadReceipt($id)
    {
        try {
            // Ambil data invoice dengan semua relasinya
            $invoice = Invoice::query()
                ->with([
                    'invoiceItems' => function ($query) {
                        $query->orderBy('created_at', 'asc');
                    },
                    'customer',
                    'order',
                    'table' // uncomment jika ada relasi table
                ])
                ->find($id);

            if (!$invoice) {
                return response()->json(['error' => 'Invoice tidak ditemukan'], 404);
            }

            // Load logo dari storage dan konversi ke base64
            $logoPath = public_path('images/logo/papasans.png');
            $base64Logo = null;

            if (file_exists($logoPath)) {
                $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                $data = file_get_contents($logoPath);
                $base64Logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }

            // Siapkan data untuk view
            $data = [
                'invoice' => $invoice,
                'title' => 'Receipt - ' . $invoice->invoice_number,
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'logo' => $base64Logo,
            ];

            // Generate PDF
            $pdf = Pdf::loadView('download.invoice_receipt', $data)
                ->setPaper([0, 0, 226.77, 566.93], 'portrait')
                ->setOptions([
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                    'dpi' => 203,
                    'defaultFont' => 'Arial',
                    'margin-top' => 0,
                    'margin-bottom' => 0,
                    'margin-left' => 0,
                    'margin-right' => 0,
                ]);

            // Nama file download
            $filename = 'receipt_' . $invoice->invoice_number . '_' . date('YmdHis') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error downloading receipt: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal mengunduh receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $orderByColumn = 'created_at';
        $orderByDirection = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $invoicesQuery = Invoice::query()
            ->with(['invoiceItems', 'customer'])
            ->withTrashed();

        // 🔎 Filter search
        $invoicesQuery->when(!empty($request->search), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('created_at', 'like', '%' . $request->search . '%')
                    ->orWhere('invoice_number', 'like', '%' . $request->search . '%')
                    ->orWhere('title', 'like', '%' . $request->search . '%')
                    ->orWhere('hashtag', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('link', 'like', '%' . $request->search . '%');
            });
        });

        // 🔎 Filter status
        $invoicesQuery->when($request->status, function ($query, $status) {
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
            $invoicesQuery->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $invoicesQuery->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
        }

        $invoicesQuery->orderBy($orderByColumn, $orderByDirection);

        // 🔎 Buat folder kalau belum ada
        $exportDir = 'InvoicesExport';
        if (!is_dir(Storage::disk('public')->path($exportDir))) {
            mkdir(Storage::disk('public')->path($exportDir), 0775, true);
            chmod(Storage::disk('public')->path($exportDir), 0775);
        }

        // 🔎 Setup Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'A1' => 'No. Invoice',
            'B1' => 'Tanggal',
            'C1' => 'Customer',
            'D1' => 'Subtotal',
            'E1' => 'Tax',
            'F1' => 'Total',
            'G1' => 'Status',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        $columnWidths = [
            'A' => 20,
            'B' => 15,
            'C' => 30,
            'D' => 15,
            'E' => 10,
            'F' => 15,
            'G' => 15,
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // 🔎 Isi data
        $row = 2;
        $invoicesQuery->chunk(1000, function ($invoices) use ($sheet, &$row) {
            foreach ($invoices as $invoice) {
                $sheet->setCellValue("A{$row}", $invoice->invoice_number);
                $sheet->setCellValue("B{$row}", $invoice->created_at->format('Y-m-d'));
                $sheet->setCellValue("C{$row}", $invoice->customer->name ?? '-');
                $sheet->setCellValue("D{$row}", $invoice->subtotal);
                $sheet->setCellValue("E{$row}", $invoice->tax);
                $sheet->setCellValue("F{$row}", $invoice->total_net);
                $sheet->setCellValue("G{$row}", $invoice->deleted_at ? 'Non Aktif' : 'Aktif');
                $row++;
            }
        });

        // 🔎 Simpan file
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "Invoices_Export_{$timestamp}.xlsx";
        $filePath = Storage::disk('public')->path($exportDir . '/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $fileHeaders = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return response()->download($filePath, $fileName, $fileHeaders)->deleteFileAfterSend();
    }
}
