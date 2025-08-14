<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jadwal extends Model
{
    use HasFactory;

    protected $table = 'jadwals';

    protected $fillable = [
        'tanggal',
        'lokasi_id',
        'shift_nama',
        'user_id',
        'status',
    ];

    public function satpam()
    {
        return $this->belongsTo(Satpam::class, 'user_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }
}
