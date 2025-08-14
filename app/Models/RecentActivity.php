<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecentActivity extends Model
{
    protected $fillable = [
        'user_id', 'description', 'severity', 'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(Satpam::class, 'user_id');
    }
}
