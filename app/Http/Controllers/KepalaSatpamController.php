<?php

namespace App\Http\Controllers;

use App\Models\Satpam;
use App\Models\JurnalSatpam;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\Jadwal;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;


class KepalaSatpamController extends Controller
{
    public function index(Request $request)
    {
        // Ambil tanggal hari ini sesuai timezone aplikasi
        $today = now(Config::get('app.timezone', 'UTC'))->toDateString();

        // Perbaikan: Ambil semua shift yang aktif untuk dropdown filter
        $shifts = Shift::where('is_active', 1)->orderBy('id')->get();
        
        // Perbaikan: Ambil shift pertama sebagai default jika tidak ada filter yang dipilih
        $defaultShiftId = $shifts->isNotEmpty() ? $shifts->first()->id : null;
        
        // Perbaikan: Ambil filter dari request berdasarkan 'shift_id', bukan 'shift'
        $shiftFilterId = $request->input('shift_id', $defaultShiftId);

        // Query jadwal untuk hari ini, eager load relasi shift
        $query = Jadwal::with(['satpam', 'lokasi', 'shift']) // Perbaikan: Eager load relasi shift
            ->whereDate('tanggal', $today);

        // Perbaikan: Terapkan filter berdasarkan shift_id
        if (!empty($shiftFilterId)) {
            $query->where('shift_id', $shiftFilterId);
        }

        $jadwals = $query
            ->orderBy('lokasi_id')
            ->orderBy('shift_id') // Perbaikan: Order by shift_id
            ->get();

        // Status Pengisian Jurnal (hari ini)
        $jurnalToday = JurnalSatpam::with(['satpam', 'lokasi', 'shift'])
            ->whereDate('tanggal', $today)
            ->get();

        // History Pengisian Jurnal (5 terakhir)
        $jurnalHistory = JurnalSatpam::with(['satpam', 'lokasi', 'shift'])
            ->latest()
            ->take(5)
            ->get();

        return view('KepalaSatpam.dashboard', compact(
            'jadwals',
            'jurnalToday',
            'jurnalHistory',
            'shifts',          // Perbaikan: Kirim data shifts ke view
            'shiftFilterId'    // Perbaikan: Kirim shift_id yang aktif ke view
        ));
    }
}