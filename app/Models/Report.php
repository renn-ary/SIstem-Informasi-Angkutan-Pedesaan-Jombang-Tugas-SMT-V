<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    public const STATUS_MENUNGGU_TINDAK_LANJUT = 'menunggu_tindak_lanjut';
    public const STATUS_DITINDAKLANJUTI = 'ditindaklanjuti';

    protected $fillable = [
        'user_id',
        'route_id',
        'category',
        'description',
        'photo',
        'latitude',
        'longitude',
        'status',
        'response',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function route()
    {
        return $this->belongsTo(Rute::class, 'route_id');
    }
}
