<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = ['jurnal_id', 'file_path'];

    public function jurnal()
    {
        return $this->belongsTo(JurnalSatpam::class, 'jurnal_id');
    }
}
