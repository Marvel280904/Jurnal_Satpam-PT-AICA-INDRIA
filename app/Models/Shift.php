<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    //protected $primaryKey = 'shift_id';

    protected $fillable = ['lokasi_id', 'nama_shift', 'mulai_shift', 'selesai_shift', 'is_active'];

    public function jurnalSatpams()
    {
        return $this->hasMany(JurnalSatpam::class, 'shift_id');
    }

    public function location()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

}

