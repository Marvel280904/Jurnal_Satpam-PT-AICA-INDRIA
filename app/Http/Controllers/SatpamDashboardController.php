<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Models\JurnalSatpam;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\Satpam;
use App\Models\Jadwal;

class SatpamDashboardController extends Controller
{
    public function index()
    {
        $user  = Auth::user();
        $today = Carbon::today()->toDateString();

        // Cari jadwal user untuk HARI INI
        $jadwal = Jadwal::with('lokasi') // jadwal->lokasi tersedia jika relasi ada di model Jadwal
            ->where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->latest()
            ->first();

        // Siapkan $lokasi dan $shift untuk blade
        $lokasi = $jadwal?->lokasi; // null kalau tidak ada
        // shift_nama di tabel jadwals berisi "Shift Pagi/Shift Siang/Shift Malam"
        $shift  = $jadwal
            ? Shift::where('nama_shift', $jadwal->shift_nama)->first()
            : null;

        // Jurnal terakhir user ini (opsional, tetap seperti punya Anda)
        $latestJournal = JurnalSatpam::where('user_id', $user->id)
            ->latest()
            ->first();

        // History beberapa jurnal terbaru (semua user—sesuai kode Anda)
        $jurnalHistory = JurnalSatpam::with(['satpam', 'lokasi', 'shift'])
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'shift', 'lokasi', 'today', 'latestJournal', 'jurnalHistory'
        ));
    }
}
