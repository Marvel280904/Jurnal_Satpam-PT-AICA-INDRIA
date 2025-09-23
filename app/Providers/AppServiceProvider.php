<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;   // <-- REQUIRED
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\JurnalSatpam;
use App\Models\Jadwal;
use App\Models\Shift;
use App\Models\Lokasi;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view) {
            // =========================================================================
            // PERAN: KEPALA SATPAM
            // =========================================================================
            if (Auth::check() && Auth::user()->role === 'Kepala Satpam') {
                // Perbaikan: Definisikan tanggal sebagai objek Carbon dan string secara terpisah
                $todayCarbon    = Carbon::today();
                $today          = $todayCarbon->toDateString(); // <-- Format 'YYYY-MM-DD' untuk query
                $tomorrow       = $todayCarbon->copy()->addDay()->toDateString(); // <-- Format 'YYYY-MM-DD'

                $reminders      = [];
                $lokasiNameById = Lokasi::pluck('nama_lokasi', 'id');
                $shiftNameById  = Shift::pluck('nama_shift', 'id');

                // 1) REMINDER: PERSETUJUAN JURNAL (STATUS WAITING)
                $waitingCount = JurnalSatpam::where('status', 'waiting')->count();
                if ($waitingCount > 0) {
                    $reminders[] = [
                        'key'   => 'approval', 'icon'  => 'bi-journal-check',
                        'title' => 'Jurnal Approval', 'desc'  => $waitingCount . ' Jurnal menunggu persetujuan',
                        'url'   => route('log.history'),
                    ];
                }

                // 2) REMINDER: JADWAL KOSONG
                // Menggunakan variabel string $today dan $tomorrow untuk konsistensi
                $hasToday    = Jadwal::whereDate('tanggal', $today)->exists();
                $hasTomorrow = Jadwal::whereDate('tanggal', $tomorrow)->exists();
                if (!$hasToday) {
                    $reminders[] = [
                        'key'   => 'jadwal-today', 'icon' => 'bi-calendar-plus',
                        'title' => 'Guard Scheduling', 'desc'  => 'Tambah jadwal untuk hari ini !',
                        'url'   => route('guard.data')
                    ];
                } elseif (!$hasTomorrow) {
                    $reminders[] = [
                        'key'   => 'jadwal-tomorrow', 'icon' => 'bi-plus-square',
                        'title' => 'Guard Scheduling', 'desc'  => 'Tambah jadwal untuk besok !',
                        'url'   => route('guard.data')
                    ];
                }

                // 3) REMINDER: JURNAL TERLAMBAT DIISI
                $expectedJournals = Jadwal::select('tanggal', 'lokasi_id', 'shift_id')
                    ->whereNotNull('lokasi_id')->whereNotNull('shift_id')
                    ->whereDate('tanggal', '<=', $today) // Menggunakan variabel string $today
                    ->distinct()
                    ->orderBy('tanggal', 'desc')
                    ->get();

                foreach ($expectedJournals as $expected) {
                    $tanggalString = Carbon::parse($expected->tanggal)->toDateString();

                    $journalExists = JurnalSatpam::whereDate('tanggal', $tanggalString)
                        ->where('lokasi_id', $expected->lokasi_id)
                        ->where('shift_id', $expected->shift_id)
                        ->exists();

                    if (!$journalExists) {
                        $lokasiNama = $lokasiNameById[$expected->lokasi_id] ?? 'Lokasi tidak dikenal';
                        $shiftNama  = $shiftNameById[$expected->shift_id] ?? 'Shift tidak dikenal';
                        $descDate   = Carbon::parse($expected->tanggal)->translatedFormat('d F Y');

                        $reminders[] = [
                            'key'   => 'late-' . $expected->tanggal . '-' . $expected->lokasi_id . '-' . $expected->shift_id,
                            'icon'  => 'bi-journal-plus',
                            'title' => 'Journal Entry',
                            'desc'  => "Jurnal untuk {$descDate} di {$lokasiNama} • {$shiftNama} belum disubmit",
                            'url'   => route('jurnal.submission'),
                        ];
                    }
                }

                $view->with([
                    'reminders'     => $reminders,
                    'reminderCount' => count($reminders)
                ]);

            // =========================================================================
            // PERAN: SATPAM (Logika serupa diterapkan di sini)
            // =========================================================================
            } elseif (Auth::check() && Auth::user()->role === 'Satpam') {
                $user           = Auth::user();
                // Perbaikan: Definisikan tanggal sebagai objek Carbon dan string secara terpisah
                $todayCarbon    = Carbon::today();
                $today          = $todayCarbon->toDateString(); // <-- Format 'YYYY-MM-DD' untuk query
                $reminders      = [];

                $lokasiNameById = Lokasi::pluck('nama_lokasi', 'id');
                $shiftNameById  = Shift::pluck('nama_shift', 'id');

                // 1) REMINDER: JURNAL YANG HARUS DIISI
                $jadwalsToCheck = Jadwal::where('user_id', $user->id)
                    ->whereNotNull('lokasi_id')->whereNotNull('shift_id')
                    ->whereDate('tanggal', '<=', $today) // Menggunakan variabel string $today
                    ->orderByDesc('tanggal')
                    ->limit(30)
                    ->get();

                foreach ($jadwalsToCheck as $jdw) {
                    $tanggalString = Carbon::parse($jdw->tanggal)->toDateString();

                    $journalExists = JurnalSatpam::whereDate('tanggal', $tanggalString)
                        ->where('lokasi_id', $jdw->lokasi_id)
                        ->where('shift_id', $jdw->shift_id)
                        ->exists();

                    if (!$journalExists) {
                        $lokasiNama = $lokasiNameById[$jdw->lokasi_id] ?? '-';
                        $shiftNama  = $shiftNameById[$jdw->shift_id] ?? '-';
                        
                        // Perbandingan isSameDay tetap menggunakan objek Carbon untuk akurasi
                        $isToday    = Carbon::parse($jdw->tanggal)->isSameDay($todayCarbon);

                        $desc = $isToday
                            ? "Harap isi jurnal hari ini: {$lokasiNama} - {$shiftNama}."
                            : "Jurnal tertinggal: " . Carbon::parse($jdw->tanggal)->format('d M Y') . " di {$lokasiNama} - {$shiftNama}.";

                        $reminders[] = [
                            'key'   => 'entry-' . $jdw->tanggal . '-' . $jdw->lokasi_id . '-' . $jdw->shift_id,
                            'icon'  => 'bi-journal-plus', 'title' => 'Journal Entry',
                            'desc'  => $desc, 'url'   => route('jurnal.submission'),
                        ];
                    }
                }

                // 2) REMINDER: JURNAL DITOLAK
                $rejects = JurnalSatpam::where('user_id', $user->id)
                    ->where('status', 'reject')
                    ->orderByDesc('tanggal')
                    ->get();

                foreach ($rejects as $rej) {
                    $dateStr = Carbon::parse($rej->tanggal)->format('d F Y');
                    $reminders[] = [
                        'key'   => 'reject-'.$rej->id, 'icon'  => 'bi-journal-x',
                        'title' => 'Journal Rejected', 'desc'  => "Jurnal untuk tanggal {$dateStr} perlu direvisi.",
                        'url'   => route('log.history'),
                    ];
                }

                $view->with([
                    'reminders'     => $reminders,
                    'reminderCount' => count($reminders),
                ]);
            }
        });
    }
}
