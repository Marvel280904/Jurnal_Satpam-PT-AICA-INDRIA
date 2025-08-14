<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogLocShift extends Model
{
    use HasFactory;

    protected $table = 'log_loc_shifts';

    protected $fillable = [
        'lokasi_id',
        'shift_id',
        'user_id',
        'created_at',
    ];

    /**
     * Relasi ke model Lokasi
     */
    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    /**
     * Relasi ke model Shift
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Relasi ke model Satpam (user_id)
     */
    public function satpam()
    {
        return $this->belongsTo(Satpam::class, 'user_id');
    }
}
