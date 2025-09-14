<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'customer_id',
        'invoice_number',
        'invoice_date',
        'expired_date',
        'type',
        'subtotal',
        'tax',
        'total_net',
        'status',
    ];

    // protected static function boot()
    // {
    //     parent::boot();

    //     static::creating(function ($model) {
    //         try {
    //             $model->transaction_id = (string)Str::orderedUuid();
    //         } catch (\Exception $e) {
    //             abort(500, $e->getMessage());
    //         }
    //     });
    // }

    public static function generateInvoiceNumber()
    {
        $currentDate = Carbon::now()->format('ymd');
        $lastInvoiceRecord = Invoice::where('invoice_number', '!=', '')->withTrashed()->latest()->first();
        if ($lastInvoiceRecord) {
            $arrLastInvoiceNumber = explode('-', $lastInvoiceRecord->invoice_number);
            $newInvoiceNumber = str_pad(((int)$arrLastInvoiceNumber[2] + 1), 4, '0', STR_PAD_LEFT);
            $invoiceNumber = 'INV-' . $currentDate . '-' . ($newInvoiceNumber);
        } else {
            $invoiceNumber = 'INV-' . $currentDate . '-0001';
        }
        return $invoiceNumber;
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class, 'invoice_id', 'id');
    }

    public function table()
    {
        return $this->hasOne(Table::class, 'id');
    }
}
