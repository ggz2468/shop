<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductViewCount extends Model
{
    use HasFactory;

    const CREATED_AT = 'recorded_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'product_id',
        'view_counts',
        'recorded_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'view_counts' => 'integer',
        'recorded_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
