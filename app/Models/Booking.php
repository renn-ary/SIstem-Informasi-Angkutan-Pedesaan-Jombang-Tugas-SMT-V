<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_MENUNGGU_PEMBAYARAN = 'menunggu_pembayaran';
    public const STATUS_DIKONFIRMASI = 'dikonfirmasi';
    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const PEMBAYARAN_MENUNGGU = 'menunggu_pembayaran';
    public const PEMBAYARAN_LUNAS = 'lunas';
    public const PEMBAYARAN_GAGAL = 'gagal';

    protected $fillable = [
        'booking_code',
        'user_id',
        'schedule_id',
        'passenger_count',
        'total_fare',
        'booking_status',
        'payment_status',
        'booked_at',
    ];

    protected $casts = [
        'passenger_count' => 'integer',
        'total_fare' => 'decimal:2',
        'booked_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Jadwal::class, 'schedule_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
