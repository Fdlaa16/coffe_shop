<?php

namespace Modules\Order\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('order::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('order::create');
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
        return view('order::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('order::edit');
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

    public function downloadReceipt($id)
    {
        try {
            $invoice = Invoice::query()
                ->with([
                    'invoiceItems' => function ($query) {
                        $query->orderBy('created_at', 'asc');
                    },
                    'customer',
                    'order',
                    'table'
                ])
                ->find($id);

            if (!$invoice) {
                return response()->json(['error' => 'Invoice tidak ditemukan'], 404);
            }

            $logoPath = public_path('images/logo/papasans.png');
            $base64Logo = null;

            if (file_exists($logoPath)) {
                $type = pathinfo($logoPath, PATHINFO_EXTENSION);
                $data = file_get_contents($logoPath);
                $base64Logo = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }

            $data = [
                'invoice' => $invoice,
                'title' => 'Receipt - ' . $invoice->invoice_number,
                'generated_at' => now()->format('d/m/Y H:i:s'),
                'logo' => $base64Logo,
            ];

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

            $filename = 'receipt_' . $invoice->invoice_number . '_' . date('YmdHis') . '.pdf';

            return $pdf->download($filename);
        } catch (\Exception $e) {
            \Log::error('Error downloading receipt: ' . $e->getMessage());
            return response()->json([
                'error' => 'Gagal mengunduh receipt: ' . $e->getMessage()
            ], 500);
        }
    }
}
