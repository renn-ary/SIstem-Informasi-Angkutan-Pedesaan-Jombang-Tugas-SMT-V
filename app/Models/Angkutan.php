<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Angkutan extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'kode',
        'jenis',
        'status_operasional',
        'operator_id',
        'vehicle_number',
        'license_plate',
        'capacity',
    ];

    protected $casts = [
        'capacity' => 'integer',
    ];

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function rutes()
    {
        return $this->hasMany(Rute::class);
    }

    public function jadwals()
    {
        return $this->hasMany(Jadwal::class);
    }

    public function tarifs()
    {
        return $this->hasMany(Tarif::class);
    }

    public function verifikasis()
    {
        return $this->hasMany(Verifikasi::class);
    }
}
