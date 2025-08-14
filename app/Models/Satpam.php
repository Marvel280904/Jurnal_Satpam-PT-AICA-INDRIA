<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Satpam extends Authenticatable
{
    protected $table = 'satpams';

    protected $fillable = [
        'username', 'password', 'foto', 'nama', 'role', 'lokasi_id', 'shift', 'status',
    ];

    protected $hidden = ['password'];

    public function jurnalSatpams()
    {
        return $this->hasMany(JurnalSatpam::class, 'user_id');
    }
    
    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function jadwal()
    {
        return $this->hasOne(Jadwal::class, 'user_id');
    }
}
