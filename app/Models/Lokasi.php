<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    //protected $primaryKey = 'lokasi_id';

    protected $fillable = ['nama_lokasi', 'alamat_lokasi', 'foto', 'is_active'];

    public function jurnalSatpams()
    {
        return $this->hasMany(JurnalSatpam::class, 'lokasi_id');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'lokasi_id'); 
    }
}

