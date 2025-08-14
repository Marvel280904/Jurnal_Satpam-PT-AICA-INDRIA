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

        // Ambil filter shift dari query string, default "Shift Pagi"
        $shiftFilter = $request->input('shift', 'Shift Pagi');

        // Query jadwal untuk hari ini
        $query = Jadwal::with(['satpam', 'lokasi'])
            ->whereDate('tanggal', $today);

        // Terapkan filter shift jika ada
        if (!empty($shiftFilter)) {
            $query->where('shift_nama', $shiftFilter);
        }

        $jadwals = $query
            ->orderBy('lokasi_id')
            ->orderBy('shift_nama')
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

        return view('admin.dashboard', compact(
            'jadwals',
            'jurnalToday',
            'jurnalHistory',
            'shiftFilter'
        ));
    }
}