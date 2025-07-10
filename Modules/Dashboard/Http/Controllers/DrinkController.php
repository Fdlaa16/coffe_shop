<?php

namespace Modules\Dashboard\Http\Controllers;

use App\Helpers\Helper;
use App\Http\Resources\DrinkResource;
use App\Models\Drink;
use App\Models\File;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DrinkController extends Controller
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

        $drinksQuery = Drink::query()
            ->withTrashed();

        $drinksQuery->when(!empty($request->search), function ($q) use ($request) {
            $q->where(function ($q) use ($request) {
                $q->where('created_at', 'like', '%' . $request->search . '%')
                    ->orWhere('name', 'like', '%' . $request->search . '%')
                    ->orWhere('title', 'like', '%' . $request->search . '%')
                    ->orWhere('hashtag', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%')
                    ->orWhere('link', 'like', '%' . $request->search . '%');
            });
        });

        $drinksQuery->when($request->status, function ($query, $status) {
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
            $drinksQuery->where('created_at', '>=', Helper::formatDate($request->from_date, 'Y-m-d') . ' 00:00:00');
        }

        if ($request->filled('to_date')) {
            $drinksQuery->where('created_at', '<=', Helper::formatDate($request->to_date, 'Y-m-d') . ' 23:59:59');
        }

        $drinksQuery->orderBy($orderByColumn, $orderByDirection);

        $drinks = $drinksQuery->get();

        $statusCounts = DB::table('drinks')
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
            'data' => $drinks,
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
                'name' => 'required',
                'qty' => 'required',
                'price' => 'required',
                'drink_photo.*' => 'image|mimes:jpeg,png,jpg,bmp|max:2048',
            ];

            $messages = [
                'name.required' => 'Nama harus diisi',
                'qty.required' => 'Kuantitas harus diisi',
                'price.required' => 'Harga harus diisi',
                'drink_photo.*.image' => 'File harus berupa gambar',
                'drink_photo.*.mimes' => 'Format gambar harus jpeg, png, jpg, atau bmp',
                'drink_photo.*.max' => 'Ukuran gambar maksimal 2MB',
            ];

            $validator = Validator::make($postData, $rules, $messages);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()->toArray()], 422);
            } else {
                $drink = Drink::create([
                    'name' => $request->name,
                    'qty' => $request->qty,
                    'price' => $request->price,
                ]);

                $types = ['drink_photo'];
                $fileObj = new File();

                foreach ($types as $type) {
                    if ($request->hasFile($type)) {
                        $file = $request->file($type);
                        $fileDir = $fileObj->getDirectory($type);
                        $fileName = $fileObj->getFileName($type, $drink->id, $file);

                        $file->storeAs($fileDir, $fileName, 'public');

                        $drink->files()->where('type', $type)->delete();

                        $drink->files()->create([
                            'type' => $type,
                            'name' => $fileName,
                            'original_name' => $file->getClientOriginalName(),
                            'extension' => $file->getClientOriginalExtension(),
                            'path' => "$fileDir$fileName",
                        ]);
                    }
                }

                DB::commit();
                return response()->json([
                    'message' => 'Drink created successfully.',
                    'data' => $drink->load('files') // Load relasi files
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
        $drinks = Drink::query()
            ->with([
                'drink_photo',
            ])
            ->find($id);

        $data = [
            'data' => $drinks,
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
            $drink = Drink::findOrFail($id);
            $postData = $request->all();

            $rules = [
                'name' => 'required',
                'qty' => 'required',
                'price' => 'required',
                'drink_photo.*' => 'image|mimes:jpeg,png,jpg,bmp|max:2048',
            ];

            $messages = [
                'name.required' => 'Nama harus diisi',
                'qty.required' => 'Kuantitas harus diisi',
                'price.required' => 'Harga harus diisi',
                'drink_photo.*.image' => 'File harus berupa gambar',
                'drink_photo.*.mimes' => 'Format gambar harus jpeg, png, jpg, atau bmp',
                'drink_photo.*.max' => 'Ukuran gambar maksimal 2MB',
            ];

            $validator = Validator::make($postData, $rules, $messages);
            if ($validator->fails()) {
                return response()->json(['errors' => $validator->messages()->toArray()], 422);
            }

            $drink->update([
                'name' => $request->name,
                'qty' => $request->qty,
                'price' => $request->price,
            ]);

            $types = ['drink_photo'];
            $fileObj = new File();

            foreach ($types as $type) {
                if ($request->hasFile($type)) {
                    $file = $request->file($type);
                    $fileDir = $fileObj->getDirectory($type);
                    $fileName = $fileObj->getFileName($type, $drink->id, $file);

                    $file->storeAs($fileDir, $fileName, 'public');
                    $drink->files()->where('type', $type)->delete();

                    $drink->files()->create([
                        'type'           => $type,
                        'name'           => $fileName,
                        'original_name'  => $file->getClientOriginalName(),
                        'extension'      => $file->getClientOriginalExtension(),
                        'path'           => $fileDir . $fileName,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Drink updated successfully.',
                'data' => $drink->load('files')
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
        $drink = Drink::withTrashed()->find($id);

        if (!$drink) {
            return response()->json(['message' => 'Drink tidak ditemukan'], 404);
        }

        $drink->delete();

        return response()->json([
            'message' => 'Drink berhasil dihapus.',
            'data' => new DrinkResource($drink),
        ]);
    }

    public function active(Request $request, $id)
    {
        $drink = Drink::withTrashed()->find($id);

        if (!$drink) {
            return response()->json(['message' => 'Drink tidak ditemukan'], 404);
        }

        $drink->restore();

        return response()->json([
            'message' => 'Drink berhasil diaktifkan.',
            'data' => new DrinkResource($drink),
        ]);
    }
}
