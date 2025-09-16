<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Resources\TableResource;
use App\Models\Table;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TableController extends Controller
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

        $tablesQuery = Table::query()
            ->withTrashed();

        $tablesQuery->when(!empty($request->search), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('created_at', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('title', 'like', '%' . $request->search . '%')
                    ->orWhere('hashtag', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('link', 'like', '%' . $request->search . '%');
            });
        });

        $tablesQuery->when($request->status, function ($query, $status) {
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
            $tablesQuery->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
        }

        if ($request->filled('to_date')) {
            $tablesQuery->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
        }

        $tablesQuery->orderBy($orderByColumn, $orderByDirection);

        $tables = $tablesQuery->get();

        $statusCounts = DB::table('tables')
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

        $tables = $tablesQuery->get()->map(function ($table) {
            $url = env('PAPASANS_URL') . "/landing-page/{$table->id}/table";

            $qr = QrCode::format('png')->size(100)->generate((string) $url);

            $table->qr_code = 'data:image/png;base64,' . base64_encode($qr);
            $table->qr_url = $url;

            return $table;
        });

        $responseTotals = [
            'all' => (int) $statusCounts->total,
            'active' => (int) $statusCounts->active,
            'in_active' => (int) $statusCounts->in_active,
            'qr_code' => $tables
        ];

        return response()->json([
            'data' => $tables,
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
        DB::beginTransaction();
        try {
            $table = Table::create();

            DB::commit();

            return response()->json([
                'message' => 'Table created successfully.',
                'data' => $table
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong',
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
        $tables = table::query()
            ->find($id);

        $data = [
            'data' => $tables,
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
        DB::beginTransaction();
        try {
            $table = Table::findOrFail($id);

            DB::commit();
            return response()->json([
                'message' => 'Table updated successfully.',
                'data' => $table
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        $table = Table::withTrashed()->find($id);

        if (!$table) {
            return response()->json(['message' => 'Table tidak ditemukan'], 404);
        }

        $table->delete();

        return response()->json([
            'message' => 'Table berhasil dihapus.',
            'data' => new TableResource($table),
        ]);
    }

    public function active(Request $request, $id)
    {
        $table = Table::withTrashed()->find($id);

        if (!$table) {
            return response()->json(['message' => 'Table tidak ditemukan'], 404);
        }

        $table->restore();

        return response()->json([
            'message' => 'Table berhasil diaktifkan.',
            'data' => new TableResource($table),
        ]);
    }

    public function downloadQr($id)
    {
        try {
            // Ambil data table dengan semua relasinya
            $table = Table::query()
                ->withTrashed()
                ->find($id);

            if (!$table) {
                return response()->json(['error' => 'Table tidak ditemukan'], 404);
            }

            // Generate QR Code URL atau data
            $qrData = env('PAPASANS_URL') . "/landing-page/{$table->id}/table";

            // Load logo dari storage dan konversi ke base64
            $logoPath = public_path('images/logo/papasans.png');
            $base64Logo = null;

            if (file_exists($logoPath)) {
                $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                $data = file_get_contents($logoPath);
                $base64Logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }

            // Generate QR Code sebagai base64 dengan Simple QR Code
            $qrCodePng = QrCode::format('png')
                ->size(400)
                ->margin(2)
                ->errorCorrection('M')
                ->generate($qrData);

            $qrCode = 'data:image/png;base64,' . base64_encode($qrCodePng);

            // Siapkan data untuk view
            $data = [
                'table' => $table,
                'qr_code' => $qrCode,
                'qr_data' => $qrData,
                'title' => 'QR Code - Table ' . $table->table_number,
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'logo' => $base64Logo,
            ];

            // Generate PDF
            $pdf = Pdf::loadView('download.qr', $data)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                    'dpi' => 150,
                    'defaultFont' => 'Arial',
                    'margin-top' => 10,
                    'margin-right' => 10,
                    'margin-bottom' => 10,
                    'margin-left' => 10,
                ]);

            // Nama file download
            $filename = 'qr_table_' . $table->table_number . '_' . date('YmdHis') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error downloading QR: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal mengunduh QR code: ' . $e->getMessage()
            ], 500);
        }
    }
}
