<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JurnalSatpam extends Model
{
    protected $fillable = [
        'tanggal', 'lokasi_id', 'shift_id', 'user_id', 'next_shift_user_id',
        'laporan_kegiatan', 'is_kejadian_temuan', 'kejadian_temuan',
        'is_lembur', 'lembur', 'is_proyek_vendor', 'proyek_vendor',
        'is_barang_keluar', 'barang_keluar', 'is_kendaraan_dinas_keluar', 
        'kendaraan_dinas_keluar', 'info_tambahan', 'approval_status', 'status', 'updated_by'
    ];

    public function satpam()
    {
        return $this->belongsTo(Satpam::class, 'user_id');
    }

    public function nextShiftUser()
    {
        return $this->belongsTo(Satpam::class, 'next_shift_user_id');
    }

    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'lokasi_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }

    public function uploads()
    {
        return $this->hasMany(Upload::class, 'jurnal_id');
    }

    public function anggotaShift()
    {
        return $this->hasManyThrough(
            Satpam::class,
            LogLocShift::class,
            'lokasi_id', // Foreign key on log_loc_shifts
            'id', // Foreign key on satpams
            'lokasi_id', // Local key on jurnal_satpams
            'user_id' // Local key on log_loc_shifts
        )->whereColumn('log_loc_shifts.shift_id', 'jurnal_satpams.shift_id')
        ->whereDate('log_loc_shifts.tanggal', '=', DB::raw('jurnal_satpams.tanggal'));
    }

    public function updatedBySatpam()
    {
        return $this->belongsTo(Satpam::class, 'updated_by');
    }

}

