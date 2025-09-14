<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerController extends Controller
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

        $customersQuery = Customer::query()
            ->with([
                'user'
            ])
            ->withTrashed();

        $customersQuery->when(!empty($request->search), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('created_at', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('title', 'like', '%' . $request->search . '%')
                    ->orWhere('hashtag', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('link', 'like', '%' . $request->search . '%');
            });
        });

        $customersQuery->when($request->status, function ($query, $status) {
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
            $customersQuery->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
        }

        if ($request->filled('to_date')) {
            $customersQuery->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
        }

        $customersQuery->orderBy($orderByColumn, $orderByDirection);

        $customers = $customersQuery->get();

        $statusCounts = DB::table('customers')
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
            'data' => $customers,
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
            $postData = $request->all();
            $rules = [
                'name'      => 'required',
                'email'     => 'required|email|unique:users,email',
                'phone'     => 'required',
            ];

            $messages = [
                'name.required'      => 'Nama harus diisi',
                'email.required'     => 'Email harus diisi',
                'email.email'        => 'Format email tidak valid',
                'email.unique'       => 'Email sudah digunakan',
                'phone.required'     => 'Nomor Telepon harus diisi',
            ];

            $validator = Validator::make($postData, $rules, $messages);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()->toArray()], 422);
            } else {
                $user = User::create(['email' => $request->email]);

                $customer = Customer::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'phone' => $request->phone,
                ]);

                DB::commit();
                return response()->json([
                    'message' => 'Customer created successfully.',
                    'data' => $customer
                ], 201);
            }
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
        $customers = customer::query()
            ->with('user')
            ->find($id);

        $data = [
            'data' => $customers,
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
            $customer = customer::findOrFail($id);
            $postData = $request->all();

            $rules = [
                'name'      => 'required',
                'email'     => 'required|email|unique:users,email,' . $customer->user_id,
                'phone'     => 'required',
            ];

            $messages = [
                'name.required'      => 'Nama harus diisi',
                'email.required'     => 'Email harus diisi',
                'email.email'        => 'Format email tidak valid',
                'email.unique'       => 'Email sudah digunakan',
                'phone.required'     => 'Nomor Telepon harus diisi',
            ];

            $validator = Validator::make($postData, $rules, $messages);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()->toArray()], 422);
            }

            $customer->update([
                'name' => $request->name,
                'phone' => $request->phone,
            ]);

            $customer->user->update([
                'email' => $request->email
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Customer updated successfully.',
                'data' => $customer
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
        $customer = Customer::withTrashed()->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        $customer->delete();

        return response()->json([
            'message' => 'Customer berhasil dihapus.',
            'data' => new CustomerResource($customer),
        ]);
    }

    public function active(Request $request, $id)
    {
        $customer = Customer::withTrashed()->find($id);

        if (!$customer) {
            return response()->json(['message' => 'Customer tidak ditemukan'], 404);
        }

        $customer->restore();

        return response()->json([
            'message' => 'Customer berhasil diaktifkan.',
            'data' => new CustomerResource($customer),
        ]);
    }

    public function export(Request $request)
    {
        $orderByColumn = 'created_at';
        $orderByDirection = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $customersQuery = Customer::query()
            ->with(['user'])
            ->withTrashed();

        // 🔎 Filter search
        $customersQuery->when(!empty($request->search), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('created_at', 'like', '%' . $request->search . '%')
                    ->orWhere('code', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('phone', 'like', '%' . $request->search . '%')
                    ->orWhereHas(
                        'user',
                        fn($qq) =>
                        $qq->where('email', 'like', '%' . $request->search . '%')
                    );
            });
        });

        // 🔎 Filter status
        $customersQuery->when($request->status, function ($query, $status) {
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
            $customersQuery->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
        }
        if ($request->filled('to_date')) {
            $customersQuery->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
        }

        $customersQuery->orderBy($orderByColumn, $orderByDirection);

        // 🔎 Buat folder kalau belum ada
        $exportDir = 'CustomersExport';
        if (!is_dir(Storage::disk('public')->path($exportDir))) {
            mkdir(Storage::disk('public')->path($exportDir), 0775, true);
            chmod(Storage::disk('public')->path($exportDir), 0775);
        }

        // 🔎 Setup Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'A1' => 'Kode Customer',
            'B1' => 'Nama',
            'C1' => 'Email',
            'D1' => 'No. HP',
            'E1' => 'Tanggal Daftar',
            'F1' => 'Status',
        ];

        foreach ($headers as $cell => $header) {
            $sheet->setCellValue($cell, $header);
        }

        $columnWidths = [
            'A' => 20,
            'B' => 30,
            'C' => 30,
            'D' => 20,
            'E' => 20,
            'F' => 15,
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        // 🔎 Isi data
        $row = 2;
        $customersQuery->chunk(1000, function ($customers) use ($sheet, &$row) {
            foreach ($customers as $customer) {
                $sheet->setCellValue("A{$row}", $customer->code);
                $sheet->setCellValue("B{$row}", $customer->name);
                $sheet->setCellValue("C{$row}", $customer->user->email ?? '-');
                $sheet->setCellValue("D{$row}", $customer->phone);
                $sheet->setCellValue("E{$row}", $customer->created_at->format('Y-m-d'));
                $sheet->setCellValue("F{$row}", $customer->deleted_at ? 'Non Aktif' : 'Aktif');
                $row++;
            }
        });

        // 🔎 Simpan file
        $timestamp = now()->format('Y-m-d_H-i-s');
        $fileName = "Customers_Export_{$timestamp}.xlsx";
        $filePath = Storage::disk('public')->path($exportDir . '/' . $fileName);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        $fileHeaders = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        return response()->download($filePath, $fileName, $fileHeaders)->deleteFileAfterSend();
    }
}
