<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    public const STATUS_LUNAS = 'lunas';
    public const STATUS_GAGAL = 'gagal';

    protected $fillable = [
        'booking_id',
        'amount',
        'method',
        'paid_at',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
