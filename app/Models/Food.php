<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Food extends Model
{
    use HasFactory, Softdeletes;

    protected $table = 'foods';
    protected $fillable = [
        'code',
        'name',
        'qty',
        'price',
    ];

    public static $code_prefix = "FOD";

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

    public function order()
    {
        return $this->belongsTo(Order::class, 'food_id', 'id');
    }

    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function food_photo()
    {
        return $this->morphMany(File::class, 'fileable')->where('type', 'food_photo')->latest()->one();
    }
}
