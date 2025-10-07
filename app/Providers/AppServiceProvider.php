<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\JurnalSatpam;
use App\Models\Satpam;
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
                $todayCarbon = Carbon::today();
                $today = $todayCarbon->toDateString();
                $tomorrow = $todayCarbon->copy()->addDay()->toDateString();

                $reminders = [];
                $lokasiNameById = Lokasi::pluck('nama_lokasi', 'id');
                $shiftNameById = Shift::pluck('nama_shift', 'id');

                // 1) REMINDER: PERSETUJUAN JURNAL (STATUS WAITING)
                $waitingCount = JurnalSatpam::where('status', 'waiting')
                    ->count();
                if ($waitingCount > 0) {
                    $reminders[] = [
                        'key' => 'approval',
                        'icon' => 'bi-journal-check',
                        'title' => 'Jurnal Approval',
                        'desc' => $waitingCount . ' Jurnal menunggu persetujuan',
                        'url' => route('log.history'),
                    ];
                }

                // 2) REMINDER: SEMUA JURNAL YANG HARUS DISUBMIT (tanpa filter user)
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

                if ($allLatestJurnal->isNotEmpty()) {
                    $count = $totalLokasiCount - $allLatestJurnal->count();

                    $pendingJournals = collect();
                    $activeShifts = Shift::where('is_active', 1)->orderBy('mulai_shift')->get();
                    $latestShiftIndex = $activeShifts->search(fn($shift) => $shift->id == $latestShiftId);

                    if ($latestShiftIndex !== false) {
                        $nextShiftIndex = ($latestShiftIndex + 1) % $activeShifts->count();
                        $nextShift = $activeShifts[$nextShiftIndex];
                        $nextDate = \Carbon\Carbon::parse($latestShiftJurnal->tanggal);
                        if ($nextShiftIndex == 0) {
                            $nextDate->addDay();
                        }
                        $expectedNextDate = $nextDate->format('Y-m-d');
                    } else {
                        $nextShift = $activeShifts->first();
                        $expectedNextDate = $today;
                    }

                    $responsibleUserIds = $allLatestJurnal->pluck('user_id')->unique()->toArray();
                    $nextShiftUserIds = $allLatestJurnal->pluck('next_shift_user_id')->filter()->unique()->toArray();

                    $allUserIds = array_unique(array_merge($responsibleUserIds, $nextShiftUserIds));
                    $userDetails = Satpam::whereIn('id', $allUserIds)->pluck('nama', 'id')->toArray();

                    $defaultResponsibleId = !empty($responsibleUserIds) ? $responsibleUserIds[0] : null;
                    $defaultResponsibleName = $defaultResponsibleId ? ($userDetails[$defaultResponsibleId] ?? 'User  tidak dikenal') : '-';

                    $defaultNextId = !empty($nextShiftUserIds) ? $nextShiftUserIds[0] : null;
                    $defaultNextName = $defaultNextId ? ($userDetails[$defaultNextId] ?? 'User  tidak dikenal') : '-';

                    if ($count == 0) {
                        foreach ($allLokasi as $lokasi) {
                            $pendingJournals->push((object)[
                                'lokasi_id' => $lokasi->id,
                                'lokasi' => $lokasi->nama_lokasi,
                                'shift' => $nextShift->nama_shift,
                                'user_id' => $defaultNextId,
                                'user' => $defaultNextName,
                                'status' => 'Belum mengisi',
                            ]);
                        }
                    } else {
                        $submittedLokasiIds = $allLatestJurnal->pluck('lokasi_id')->unique();
                        $pendingCurrentLokasi = $allLokasi->filter(fn($lokasi) => !$submittedLokasiIds->contains($lokasi->id));

                        $currentShift = Shift::find($latestShiftId);
                        foreach ($pendingCurrentLokasi as $lokasi) {
                            $pendingJournals->push((object)[
                                'lokasi_id' => $lokasi->id,
                                'lokasi' => $lokasi->nama_lokasi,
                                'shift' => $currentShift ? $currentShift->nama_shift : '-',
                                'user_id' => $defaultResponsibleId,
                                'user' => $defaultResponsibleName,
                                'status' => 'Belum dikumpulkan',
                            ]);
                        }
                        foreach ($allLokasi as $lokasi) {
                            $pendingJournals->push((object)[
                                'lokasi_id' => $lokasi->id,
                                'lokasi' => $lokasi->nama_lokasi,
                                'shift' => $nextShift->nama_shift,
                                'user_id' => $defaultNextId,
                                'user' => $defaultNextName,
                                'status' => 'Belum dikumpulkan',
                            ]);
                        }
                    }

                    // Buat reminder dari pendingJournals (batasi 5)
                    foreach ($pendingJournals->take(5) as $entry) {
                        $reminders[] = [
                            'key' => 'pending-' . $entry->lokasi_id . '-' . $entry->shift,
                            'icon' => 'bi-journal-plus',
                            'title' => 'Journal Submission',
                            'desc' => "Jurnal untuk {$entry->lokasi} - {$entry->shift} - {$entry->user} belum disubmit.",
                            'url' => route('jurnal.submission'),
                        ];
                    }
                }

                $view->with([
                    'reminders' => $reminders,
                    'reminderCount' => count($reminders),
                ]);

            // =========================================================================
            // PERAN: SATPAM
            // =========================================================================
            } elseif (Auth::check() && Auth::user()->role === 'Satpam') {
                $user = Auth::user();
                $todayCarbon = Carbon::today();
                $today = $todayCarbon->toDateString();
                $reminders = [];

                $lokasiNameById = Lokasi::pluck('nama_lokasi', 'id');
                $shiftNameById = Shift::pluck('nama_shift', 'id');

                // 1) REMINDER: JURNAL DITOLAK
                $rejects = JurnalSatpam::where('user_id', $user->id)
                    ->where('status', 'reject')
                    ->orderByDesc('tanggal')
                    ->get();

                foreach ($rejects as $rej) {
                    $dateStr = Carbon::parse($rej->tanggal)->format('d F Y');
                    $reminders[] = [
                        'key' => 'reject-' . $rej->id,
                        'icon' => 'bi-journal-x',
                        'title' => 'Journal Rejected',
                        'desc' => "Jurnal untuk tanggal {$dateStr} perlu direvisi.",
                        'url' => route('log.history'),
                    ];
                }

                // 2) REMINDER: JURNAL YANG HARUS DI-APPROVE OLEH NEXT SHIFT
                $allLokasi = Lokasi::where('is_active', 1)->get();
                $latestJurnalsPerLokasi = collect();
                foreach ($allLokasi as $lokasi) {
                    $latestJurnal = JurnalSatpam::with(['lokasi', 'shift'])
                        ->where('lokasi_id', $lokasi->id)
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

                foreach ($allLatestJurnal as $jurnal) {
                    if ($jurnal->status == 'pending' && $jurnal->next_shift_user_id == $user->id) {
                        $dateStr = Carbon::parse($jurnal->tanggal)->format('d F Y');
                        $lokasiNama = $jurnal->lokasi->nama_lokasi ?? $lokasiNameById[$jurnal->lokasi_id] ?? '-';
                        $shiftNama = $jurnal->shift->nama_shift ?? $shiftNameById[$jurnal->shift_id] ?? '-';

                        $reminders[] = [
                            'key' => 'approve-' . $jurnal->id,
                            'icon' => 'bi-journal-check',
                            'title' => 'Journal Approval',
                            'desc' => "Jurnal untuk {$dateStr} - {$lokasiNama} - {$shiftNama} perlu di Approve.",
                            'url' => route('log.history'),
                        ];
                    }
                }

                // 4) REMINDER: JURNAL YANG HARUS DISUBMIT (filter user bertanggung jawab atau next shift)
                $totalLokasiCount = $allLokasi->count(); 
                $count = $totalLokasiCount - $allLatestJurnal->count();
                $pendingJournals = collect(); // Collection untuk reminder

                if ($latestJurnalsPerLokasi->isNotEmpty()) { // Reuse dari bagian sebelumnya
                    // Hitung next shift (adaptasi dari DashboardController)
                    $activeShifts = Shift::where('is_active', 1)->orderBy('mulai_shift')->get(); // Asumsi field 'mulai_shift' ada; ganti orderBy('id') jika tidak
                    $latestShiftIndex = $activeShifts->search(fn($shift) => $shift->id == $latestShiftId);

                    if ($latestShiftIndex !== false) {
                        $nextShiftIndex = ($latestShiftIndex + 1) % $activeShifts->count();
                        $nextShift = $activeShifts[$nextShiftIndex];
                        $nextDate = \Carbon\Carbon::parse($latestShiftJurnal->tanggal);
                        if ($nextShiftIndex == 0) { // Wrap around ke shift pertama
                            $nextDate->addDay();
                        }
                        $expectedNextDate = $nextDate->format('Y-m-d');
                    } else {
                        // Fallback jika tidak ada current shift
                        $nextShift = $activeShifts->first();
                        $expectedNextDate = $today;
                    }

                    // Hitung responsible dan next users (adaptasi dari DashboardController)
                    $responsibleUserIds = $allLatestJurnal->pluck('user_id')->unique()->toArray();
                    $nextShiftUserIds = $allLatestJurnal->pluck('next_shift_user_id')->filter()->unique()->toArray();

                    // Ambil nama user (cache untuk efisiensi)
                    $allUserIds = array_unique(array_merge($responsibleUserIds, $nextShiftUserIds));
                    $userDetails = Satpam::whereIn('id', $allUserIds)->pluck('nama', 'id')->toArray();

                    // Default responsible untuk pending current (first dari array, asumsi user sama untuk shift di lokasi pending)
                    $defaultResponsibleId = !empty($responsibleUserIds) ? $responsibleUserIds[0] : null;
                    $defaultResponsibleName = $defaultResponsibleId ? ($userDetails[$defaultResponsibleId] ?? 'User  tidak dikenal') : '-';

                    // Default next untuk pending next
                    $defaultNextId = !empty($nextShiftUserIds) ? $nextShiftUserIds[0] : null;
                    $defaultNextName = $defaultNextId ? ($userDetails[$defaultNextId] ?? 'User  tidak dikenal') : '-';

                    // Logika pending berdasarkan count (adaptasi dari DashboardController)
                    if ($count == 0) {
                        // Hanya pending next shift untuk semua lokasi
                        foreach ($allLokasi as $lokasi) {
                            $entry = (object) [
                                'lokasi_id' => $lokasi->id,
                                'lokasi' => $lokasi->nama_lokasi,
                                'shift' => $nextShift->nama_shift,
                                'user_id' => $defaultNextId,
                                'user' => $defaultNextName,
                                'status' => 'Belum mengisi'
                            ];
                            $pendingJournals->push($entry);
                        }
                    } else {
                        // Pending current shift (hanya lokasi belum submit) + pending next shift (semua lokasi)
                        // Pending current
                        $submittedLokasiIds = $allLatestJurnal->pluck('lokasi_id')->unique();
                        $pendingCurrentLokasi = $allLokasi->filter(function ($lokasi) use ($submittedLokasiIds) {
                            return !$submittedLokasiIds->contains($lokasi->id);
                        });

                        $currentShift = Shift::find($latestShiftId);
                        foreach ($pendingCurrentLokasi as $lokasi) {
                            $entry = (object) [
                                'lokasi_id' => $lokasi->id,
                                'lokasi' => $lokasi->nama_lokasi,
                                'shift' => $currentShift ? $currentShift->nama_shift : '-',
                                'user_id' => $defaultResponsibleId,
                                'user' => $defaultResponsibleName,
                                'status' => 'Belum dikumpulkan'
                            ];
                            $pendingJournals->push($entry);
                        }

                        // Pending next (sama seperti di atas)
                        foreach ($allLokasi as $lokasi) {
                            $entry = (object) [
                                'lokasi_id' => $lokasi->id,
                                'lokasi' => $lokasi->nama_lokasi,
                                'shift' => $nextShift->nama_shift,
                                'user_id' => $defaultNextId,
                                'user' => $defaultNextName,
                                'status' => 'Belum dikumpulkan'
                            ];
                            $pendingJournals->push($entry);
                        }
                    }

                    // Filter untuk role Satpam: Hanya tampilkan pending milik user login (poin 4 dari permintaan)
                    $isResponsible = in_array($user->id, $responsibleUserIds);
                    $isNextShift = in_array($user->id, $nextShiftUserIds);

                    if ($count == 0) {
                        // Hanya tampilkan pending next jika user adalah next shift
                        if ($isNextShift) {
                            $pendingJournals = $pendingJournals->filter(fn($entry) => $entry->user_id == $user->id);
                        } else {
                            $pendingJournals = collect(); // Kosong jika bukan next
                        }
                    } else {
                        // Hanya tampilkan pending current jika user responsible, atau next jika user next shift
                        if ($isResponsible) {
                            // Filter hanya current (bukan next shift)
                            $nextShiftName = $nextShift->nama_shift ?? '';
                            $pendingJournals = $pendingJournals->filter(function ($entry) use ($user, $nextShiftName) {
                                return $entry->user_id == $user->id && strpos($entry->shift, $nextShiftName) === false;
                            });
                        } elseif ($isNextShift) {
                            // Filter hanya jurnal next shift untuk user login
                            $nextShiftName = $nextShift->nama_shift ?? '';
                            $pendingJournals = $pendingJournals->filter(function ($entry) use ($user, $nextShiftName) {
                                return $entry->user_id == $user->id && strpos($entry->shift, $nextShiftName) !== false;
                            });
                        } else {
                            $pendingJournals = collect(); // Kosong jika bukan responsible atau next shift
                        }
                    }
                } // Jika kosong latestJurnalsPerLokasi, pendingJournals tetap kosong

                // Buat reminder dari $pendingJournals (batasi 5, mirip seperti di Kepala)
                $pendingReminderCount = 0;
                foreach ($pendingJournals->take(5) as $entry) {
                    $reminders[] = [
                        'key' => 'pending-' . $entry->lokasi_id . '-' . $entry->shift,
                        'icon' => 'bi-journal-plus',
                        'title' => 'Journal Submission',
                        'desc' => "Jurnal untuk {$entry->lokasi} - {$entry->shift} belum disubmit.", // Tanpa user, karena sudah milik user login
                        'url' => route('jurnal.submission'),
                    ];
                    $pendingReminderCount++;
                }

                // Jika ada lebih dari 5, tambahkan reminder "more"
                if ($pendingReminderCount > 0 && $pendingJournals->count() > 5) {
                    $reminders[] = [
                        'key' => 'pending-more',
                        'icon' => 'bi-journal-plus',
                        'title' => 'More Submissions',
                        'desc' => ($pendingJournals->count() - 5) . ' jurnal lainnya menunggu submit.',
                        'url' => route('jurnal.submission'),
                    ];
                }

                $view->with([
                    'reminders' => $reminders,
                    'reminderCount' => count($reminders),
                ]);
            }
        });
    }
}
