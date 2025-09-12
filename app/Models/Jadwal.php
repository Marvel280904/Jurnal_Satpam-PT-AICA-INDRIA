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
        'shift_id',
        'user_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function satpam()
    {
        return $this->belongsTo(Satpam::class, 'user_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function createdBySatpam()
    {
        return $this->belongsTo(Satpam::class, 'created_by');
    }

    public function updatedBySatpam()
    {
        return $this->belongsTo(Satpam::class, 'updated_by');
    }
}
