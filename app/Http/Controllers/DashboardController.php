<?php

namespace App\Http\Controllers;

use App\Models\Satpam;
use App\Models\JurnalSatpam;
use App\Models\Lokasi;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;


class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $today = now(Config::get('app.timezone', 'UTC'))->toDateString();

        // Ambil semua lokasi yang aktif
        $lokasis = Lokasi::where('is_active', 1)->orderBy('id')->get();

        // Ambil lokasi pertama sebagai default
        $defaultLokasiId = $lokasis->isNotEmpty() ? $lokasis->first()->id : null;

        // Ambil filter dari request berdasarkan 'lokasi_id'
        $lokasiFilterId = $request->input('lokasi_id', $defaultLokasiId);

        // History Pengisian Jurnal (5 terakhir) - tetap seperti asli
        $jurnalHistory = JurnalSatpam::with(['satpam', 'lokasi', 'shift'])
            ->latest()
            ->take(5)
            ->get();

        // Logika checking untuk latest jurnal overall (tidak terikat hari ini)
        $allLokasi = Lokasi::where('is_active', 1)->get();
        $totalLokasiCount = $allLokasi->count();

        $latestJurnalsPerLokasi = collect();
        foreach ($allLokasi as $lokasi) {
            $latestJurnal = JurnalSatpam::where('lokasi_id', $lokasi->id)
                ->orderBy('created_at', 'desc')
                ->first();
            if ($latestJurnal) {
                $latestJurnalsPerLokasi->push($latestJurnal);
            }
        }

        $latestShiftJurnal = $latestJurnalsPerLokasi->sortByDesc('created_at')->first();
        $latestShiftId = $latestShiftJurnal ? $latestShiftJurnal->shift_id : null;

        $allLatestJurnal = $latestJurnalsPerLokasi->filter(function ($jurnal) use ($latestShiftId) {
            return $jurnal->shift_id == $latestShiftId;
        })->values();

        $count = $totalLokasiCount - $allLatestJurnal->count();

        // Wrap dalam $data untuk kemudahan (mirip controller lain)
        $data = compact(
            'allLokasi',
            'totalLokasiCount',
            'latestJurnalsPerLokasi',
            'latestShiftJurnal',
            'latestShiftId',
            'allLatestJurnal',
            'count'
        );

        // Latest Jurnal (satu per lokasi, paling terbaru berdasarkan lokasi)
        if ($user->role === 'Kepala Satpam') {
            // Untuk Kepala: Semua latest per lokasi, diurutkan terbaru
            $latestJurnals = $latestJurnalsPerLokasi->sortByDesc('created_at');
        } else {
            // Untuk Satpam: Hanya latest per lokasi yang diisi oleh user login
            $latestJurnals = collect(); // Buat koleksi kosong untuk menampung hasilnya.
    
            // Loop untuk setiap lokasi yang aktif
            foreach ($lokasis as $lokasi) {
                // Cari satu jurnal paling baru di lokasi ini DAN yang dibuat oleh user yang sedang login
                $latestJurnalForUserInLocation = JurnalSatpam::where('lokasi_id', $lokasi->id)
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                // Jika ditemukan, tambahkan ke koleksi
                if ($latestJurnalForUserInLocation) {
                    $latestJurnals->push($latestJurnalForUserInLocation);
                }
            }
            // Urutkan hasilnya berdasarkan waktu pembuatan terbaru
            $latestJurnals = $latestJurnals->sortByDesc('created_at');
        }

        // Tambahan Adaptasi Poin 2 & Logika Poin 3-4: Hitung next shift dan pending journals (untuk display)
        $pendingJournals = collect(); // Collection untuk view
        $isKepala = $user->role === 'Kepala Satpam';
        $loginUserId = $user->id;

        if ($data['latestJurnalsPerLokasi']->isNotEmpty()) {
            // Hitung next shift (adaptasi dari kode poin 2)
            $activeShifts = Shift::where('is_active', 1)->orderBy('mulai_shift')->get(); // Asumsi field 'mulai_shift' ada; sesuaikan jika beda (misal orderBy('id'))
            $latestShiftIndex = $activeShifts->search(fn($shift) => $shift->id == $data['latestShiftId']);

            if ($latestShiftIndex !== false) {
                $nextShiftIndex = ($latestShiftIndex + 1) % $activeShifts->count();
                $nextShift = $activeShifts[$nextShiftIndex];
                $nextDate = \Carbon\Carbon::parse($data['latestShiftJurnal']->tanggal);
                if ($nextShiftIndex == 0) { // Wrap around ke shift pertama
                    $nextDate->addDay();
                }
                $expectedNextDate = $nextDate->format('Y-m-d');
            } else {
                // Fallback jika tidak ada current shift
                $nextShift = $activeShifts->first();
                $expectedNextDate = $today;
            }

            // Hitung responsible dan next users (adaptasi poin 2)
            $responsibleUserIds = $data['allLatestJurnal']->pluck('user_id')->unique()->toArray();
            $nextShiftUserIds = $data['allLatestJurnal']->pluck('next_shift_user_id')->filter()->unique()->toArray();

            // Ambil nama dan foto user (cache untuk efisiensi)
            $allUserIds = array_unique(array_merge($responsibleUserIds, $nextShiftUserIds));
            $userDetails = Satpam::whereIn('id', $allUserIds)
                ->pluck('nama', 'id')
                ->toArray();
            $userPhotos = Satpam::whereIn('id', $allUserIds)
                ->pluck('foto', 'id')
                ->toArray();

            // Default responsible untuk pending current (first dari array, asumsi user sama untuk shift di lokasi pending)
            $defaultResponsibleId = !empty($responsibleUserIds) ? $responsibleUserIds[0] : null;
            $defaultResponsibleName = $defaultResponsibleId ? ($userDetails[$defaultResponsibleId] ?? 'User  tidak dikenal') : '-';
            $defaultResponsibleFoto = $defaultResponsibleId ? ($userPhotos[$defaultResponsibleId] ?? null) : null;

            // Default next untuk pending next
            $defaultNextId = !empty($nextShiftUserIds) ? $nextShiftUserIds[0] : null;
            $defaultNextName = $defaultNextId ? ($userDetails[$defaultNextId] ?? 'User  tidak dikenal') : '-';
            $defaultNextFoto = $defaultNextId ? ($userPhotos[$defaultNextId] ?? null) : null;

            // Logika pending berdasarkan count (poin 3 & 4)
            if ($data['count'] == 0) {
                // Hanya pending next shift untuk semua lokasi
                foreach ($data['allLokasi'] as $lokasi) {
                    $entry = (object) [
                        'lokasi_id' => $lokasi->id,
                        'lokasi' => $lokasi->nama_lokasi,
                        'shift' => $nextShift->nama_shift,
                        'user_id' => $defaultNextId,
                        'user' => $defaultNextName,
                        'foto' => $defaultNextFoto,
                        'status' => 'Belum mengisi'
                    ];
                    $pendingJournals->push($entry);
                }
            } else {
                // Pending current shift (hanya lokasi belum submit) + pending next shift (semua lokasi)
                // Pending current
                $submittedLokasiIds = $data['allLatestJurnal']->pluck('lokasi_id')->unique();
                $pendingCurrentLokasi = $data['allLokasi']->filter(function ($lokasi) use ($submittedLokasiIds) {
                    return !$submittedLokasiIds->contains($lokasi->id);
                });

                $currentShift = Shift::find($data['latestShiftId']);
                foreach ($pendingCurrentLokasi as $lokasi) {
                    $entry = (object) [
                        'lokasi_id' => $lokasi->id,
                        'lokasi' => $lokasi->nama_lokasi,
                        'shift' => $currentShift ? $currentShift->nama_shift : '-',
                        'user_id' => $defaultResponsibleId,
                        'user' => $defaultResponsibleName,
                        'foto' => $defaultResponsibleFoto,
                        'status' => 'Belum mengisi'
                    ];
                    $pendingJournals->push($entry);
                }

                // Pending next (sama seperti di atas)
                foreach ($data['allLokasi'] as $lokasi) {
                    $entry = (object) [
                        'lokasi_id' => $lokasi->id,
                        'lokasi' => $lokasi->nama_lokasi,
                        'shift' => $nextShift->nama_shift,
                        'user_id' => $defaultNextId,
                        'user' => $defaultNextName,
                        'foto' => $defaultNextFoto,
                        'status' => 'Belum mengisi'
                    ];
                    $pendingJournals->push($entry);
                }
            }

            // Filter berdasarkan lokasi jika dipilih
            if (!empty($lokasiFilterId)) {
                $pendingJournals = $pendingJournals->filter(fn($entry) => $entry->lokasi_id == $lokasiFilterId);
            }

            // Filter untuk role Satpam (poin 4): Hanya tampilkan pending milik user login
            if (!$isKepala) {
                $isResponsible = in_array($loginUserId, $responsibleUserIds);
                $isNextShift = in_array($loginUserId, $nextShiftUserIds);

                if ($data['count'] == 0) {
                    // Hanya tampilkan pending next jika user adalah next shift
                    if ($isNextShift) {
                        $pendingJournals = $pendingJournals->filter(fn($entry) => $entry->user_id == $loginUserId);
                    } else {
                        $pendingJournals = collect(); // Kosong jika bukan next
                    }
                } else {
                    // Hanya tampilkan pending current jika user responsible (abaikan next)
                    if ($isResponsible) {
                        // Filter hanya current (bukan next shift)
                        //dd($isResponsible);
                        $nextShiftName = $nextShift->nama_shift ?? '';
                        $pendingJournals = $pendingJournals->filter(function ($entry) use ($loginUserId, $nextShiftName) {
                            return $entry->user_id == $loginUserId && strpos($entry->shift, $nextShiftName) === false;
                        });
                    }
                    elseif ($isNextShift) {
                        // Filter hanya jurnal yang harus diisi oleh next shift user login
                        $nextShiftName = $nextShift->nama_shift ?? '';
                        $pendingJournals = $pendingJournals->filter(function ($entry) use ($loginUserId, $nextShiftName) {
                            return $entry->user_id == $loginUserId && strpos($entry->shift, $nextShiftName) !== false;
                        });   
                    }else {
                        $pendingJournals = collect(); // Kosong jika bukan responsible
                    }
                }
            }
        } // Jika kosong latestJurnalsPerLokasi, pendingJournals tetap kosong

        // Return view
        return view('kepala_satpam.dashboard', compact(
            'pendingJournals',
            'latestJurnals',
            'jurnalHistory',
            'lokasis',
            'lokasiFilterId',
            'count' // Opsional, untuk debug atau tampilkan di view jika perlu
        ));
    }
}