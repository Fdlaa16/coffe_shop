<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'invoice_id',
        'menu_id',
        'menu_name',
        'qty',
        'unit_price',
        'total_price',
        'size',
        'sugar_level',
        'notes',
        'category',
        'status',
    ];

    public function itemable()
    {
        return $this->morphTo();
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id', 'id');
    }
}
