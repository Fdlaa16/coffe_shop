<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderItem extends Model
{
    use HasFactory, Softdeletes;

    protected $fillable = [
        'code',
        'order_id',
        'qty',
        'price',
        'status',
    ];

    public static $code_prefix = "ORI";

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            try {
                $model->code = self::getNextCode();
            } catch (\Exception $e) {
                abort(500, $e->getMessage());
            }
        });
    }

    public static function getNextCode()
    {
        $last_number = self::withTrashed()->max('code');
        $next_number = empty($last_number) ? 1 : ((int) explode('-', $last_number)[1] + 1);

        return self::makeCode($next_number);
    }

    public static function makeCode($next_number)
    {
        return (string) self::$code_prefix . '-' . str_pad($next_number, 5, 0, STR_PAD_LEFT);
    }

    public function itemable()
    {
        return $this->morphTo();
    }

    public function food()
    {
        return $this->belongsTo(Food::class, 'itemable_id', 'id')->whereItemableType('itemable_type', Food::class);
    }

    public function drink()
    {
        return $this->belongsTo(Drink::class, 'itemable_id', 'id')->whereItemableType('itemable_type', Drink::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }
}
