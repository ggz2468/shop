<?php

namespace App\Models;

use App\Enums\Member\Gender;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use SoftDeletes;
    use Notifiable;

    protected $fillable = [
        'national_id_number',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = [
        'national_id_number',
        'email',
        'email_verified_at',
        'phone',
        'phone_verified_at',
        'password',
        'deleted_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'password' => 'hashed',
        'birth_date' => 'date',
        'gender' => Gender::class,
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }

    public function routeNotificationForVonage(object $notification): ?string
    {
        return $this->phone;
    }
}
