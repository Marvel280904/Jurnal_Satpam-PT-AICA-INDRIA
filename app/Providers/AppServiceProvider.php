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
            if (Auth::check() && Auth::user()->role === 'Kepala Satpam') {

                $today       = Carbon::today();
                $tomorrow    = $today->copy()->addDay();
                $now         = Carbon::now();

                $reminders = $reminders ?? [];

                // 1) Journal Approval (waiting only)
                $waitingCount = JurnalSatpam::whereDate('tanggal', '<=', $today)
                                ->where('status', 'waiting')->count();
                if ($waitingCount > 0) {
                    $reminders[] = [
                        'key'   => 'approval',
                        'icon'  => 'bi-journal-check',
                        'title' => 'Jurnal Approval',
                        'desc'  => 'Jurnal Status - Waiting',
                        'url'   => route('log.history'),
                    ];
                }

                // 2) Guard schedule reminders
                $hasToday    = Jadwal::whereDate('tanggal', $today)->exists();
                $hasTomorrow = Jadwal::whereDate('tanggal', $tomorrow)->exists();

                if (!$hasToday) {
                    $reminders[] = [
                        'key'=>'jadwal-today', 'icon'=>'bi-calendar-plus',
                        'title'=>'Guard Scheduling', 
                        'desc'=>'Tambah jadwal hari ini !',
                        'url'=>route('guard.data')
                    ];
                } elseif (!$hasTomorrow) {
                    $reminders[] = [
                        'key'=>'jadwal-tomorrow', 'icon'=>'bi-plus-square',
                        'title'=>'Guard Scheduling', 
                        'desc'=>'Tambah jadwal untuk besok !',
                        'url'=>route('guard.data')
                    ];
                }

                // 3) Journal Late reminders
                $lokasiNameById = Lokasi::pluck('nama_lokasi', 'id'); // [1=>'Kraton', 2=>'Rembang', ...]
                $shiftMap = Shift::get()->mapWithKeys(function ($s) {
                    return [ $s->lokasi_id.'|'.$s->nama_shift => $s->id ];
                });

                /** 
                 * Ambil semua kombinasi unik (tanggal, lokasi_id, shift_nama) yang dijadwalkan
                 * (boleh dari tanggal paling awal sampai hari ini).
                 */
                $expected = Jadwal::select('tanggal', 'lokasi_id', 'shift_nama')
                    ->whereNotNull('lokasi_id')
                    ->whereNotNull('shift_nama')
                    ->whereDate('tanggal', '<=', Carbon::today()->toDateString())
                    ->get()
                    ->unique(fn ($r) => $r->tanggal.'|'.$r->lokasi_id.'|'.$r->shift_nama)
                    ->values();

                /**
                 * Set kombinasi yang SUDAH ada di jurnal: key = "tanggal|lokasi_id|shift_id"
                 */
                $existing = JurnalSatpam::select('tanggal', 'lokasi_id', 'shift_id')
                    ->get()
                    ->map(fn ($r) => $r->tanggal.'|'.$r->lokasi_id.'|'.$r->shift_id)
                    ->flip(); // jadikan set

                foreach ($expected as $row) {
                    $tanggal  = Carbon::parse($row->tanggal)->toDateString();  // pastikan format 'Y-m-d'
                    $lokasiId = $row->lokasi_id;
                    $shiftNm  = $row->shift_nama;

                    // cari shift_id yang sesuai lokasi + nama shift
                    $shiftId = $shiftMap[$lokasiId.'|'.$shiftNm] ?? null;
                    if (!$shiftId) {
                        // jika mapping tidak ketemu, lewati (hindari false-positive reminder)
                        continue;
                    }

                    $key = $tanggal.'|'.$lokasiId.'|'.$shiftId;

                    // jika kombinasi ini BELUM ada di jurnal, baru tampilkan reminder
                    if (!$existing->has($key)) {
                        $lokasiNama = $lokasiNameById[$lokasiId] ?? '-';
                        $descDate   = Carbon::parse($tanggal)->translatedFormat('d F Y'); // 15 Agustus 2025

                        $reminders[] = [
                            'key'   => 'late-'.$tanggal.'-'.$lokasiId.'-'.$shiftId,
                            'icon'  => 'bi-journal-plus',
                            'title' => 'Journal Entry',
                            'desc'  => "Jurnal untuk Tanggal {$descDate} di {$lokasiNama} • {$shiftNm} belum disubmit",
                            'url'   => route('jurnal.submission'),
                        ];
                    }
                }

                $reminderCount = count($reminders);

                $view->with([
                    'reminders'     => $reminders,
                    'reminderCount' => $reminderCount
                ]);
            }  // ====================== SATPAM REMINDERS ======================
            elseif (Auth::check() && Auth::user()->role === 'Satpam') {
                $user    = Auth::user();
                $today   = Carbon::today();
                $now     = Carbon::now();
                $uid       = $user->id;
                $reminders = [];
                $lookbackDays = 30;

                $jadwalsToCheck = Jadwal::where('user_id', $uid)
                    ->whereDate('tanggal', '<=', $today) // semua tanggal <= hari ini
                    ->orderByDesc('tanggal')
                    ->get();

                foreach ($jadwalsToCheck as $jdw) {
                    // Konversi shift_nama di jadwals -> shift_id di jurnal
                    $shiftId = Shift::where('nama_shift', $jdw->shift_nama)->value('id');

                    // Sudah ada jurnal untuk kombinasi (user, tanggal, lokasi, shift)?
                    $hasJournal = JurnalSatpam::where('user_id', $uid)
                        ->whereDate('tanggal', $jdw->tanggal)
                        ->where('lokasi_id', $jdw->lokasi_id)
                        ->where('shift_id', $shiftId)
                        ->exists();

                    if ($hasJournal) {
                        continue; // sudah diisi, tidak perlu reminder
                    }

                    // Info tampilan
                    $lokasiNama = Lokasi::where('id', $jdw->lokasi_id)->value('nama_lokasi') ?? '-';
                    $isToday    = Carbon::parse($jdw->tanggal)->isSameDay($today);

                    // Pesan berbeda untuk "hari ini" vs tanggal lampau
                    $desc = $isToday
                        ? "Jangan lupa mengisi jurnal hari ini untuk {$lokasiNama} - {$jdw->shift_nama}!"
                        : "Jangan lupa mengisi jurnal untuk tanggal " .
                        Carbon::parse($jdw->tanggal)->format('d F Y') .
                        " di {$lokasiNama} - {$jdw->shift_nama}!";

                    // Key unik per (tanggal, lokasi, shift) agar tidak dobel
                    $reminders[] = [
                        'key'   => 'journal-entry-' . $jdw->tanggal . '-' . $jdw->lokasi_id . '-' . ($shiftId ?? '0'),
                        'icon'  => 'bi-journal-plus',
                        'title' => 'Journal Entry',
                        'desc'  => $desc,
                        'url'   => route('jurnal.submission'), // arahkan ke form pengisian
                    ];
                }

                // 2) JOURNAL APPROVAL (REJECT) — tampilkan SEMUA jurnal user yang statusnya reject
                $rejects = JurnalSatpam::where('user_id', $uid)
                    ->where('status', 'reject')
                    ->orderByDesc('tanggal')
                    ->get();

                foreach ($rejects as $rej) {
                    $dateStr = Carbon::parse($rej->tanggal)->format('d F Y');
                    $reminders[] = [
                        'key'   => 'journal-reject-'.$rej->id,
                        'icon'  => 'bi-journal-x',
                        'title' => 'Journal Approval',
                        'desc'  => "Jurnal untuk tanggal {$dateStr} telah di ditolak",
                        'url'   => route('log.history'), // bisa diarahkan ke riwayat untuk melihat detail
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
