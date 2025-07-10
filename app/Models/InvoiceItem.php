<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use HasFactory, Softdeletes;

    protected $fillable = [
        'code',
        'invoice_id',
        'qty',
        'total',
        'status',
    ];

    public function itemable()
    {
        return $this->morphTo();
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'itemable_id', 'id')->whereItemableType('itemable_type', OrderItem::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'invoice_id', 'id');
    }
}
