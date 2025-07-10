<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, Softdeletes;

    protected $fillable = [
        'invoice_number',
        'invoice_date',
        'expired_date',
        'order_id',
        'customer_id',
        'type',
        'total_net',
        'status',
    ];

    public static function getNextInvoiceNumber()
    {
        $currentDate = Carbon::now()->format('ymd');
        $lastInvoiceRecord = Invoice::where('invoice_number', '!=', '')->withTrashed()->latest()->first();
        if ($lastInvoiceRecord) {
            $arrLastInoiceNumber = explode('-', $lastInvoiceRecord->invoice_number);
            $newInvoiceNumber = str_pad(((int)$arrLastInoiceNumber[2] + 1), 4, '0', STR_PAD_LEFT);
            $invoiceNumber = 'INV-' . $currentDate . '-' . ($newInvoiceNumber);
        } else {
            $invoiceNumber = 'INV-' . $currentDate . '-0001';
        }

        return $invoiceNumber;
    }

    public function invoiceItem()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
