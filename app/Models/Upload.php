<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = ['jurnal_id', 'file_path', 'created_by', 'updated_by'];

    public function jurnal()
    {
        return $this->belongsTo(JurnalSatpam::class, 'jurnal_id');
    }
}
