<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'member_id',
        'number',
        'idempotency_key',
        'total_amount',
        'tax_amount',
        'shipping_fee',
        'status',
        'payment_method',
    ];

    protected $casts = [
        'member_id' => 'integer',
        'total_amount' => 'integer',
        'tax_amount' => 'integer',
        'shipping_fee' => 'integer',
        'status' => 'integer',
        'payment_method' => 'integer',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function orderDetails(): HasMany
    {
        return $this->hasMany(OrderDetail::class);
    }
}
