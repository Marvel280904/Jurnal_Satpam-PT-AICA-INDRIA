<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Satpam;
use App\Models\Shift;
use App\Models\Lokasi;
use App\Models\Jadwal;
use Carbon\Carbon;

class GuardController extends Controller
{
    public function index(Request $request)
    {
        $lokasis = Lokasi::where('is_active', 1)->get();
        $shifts = Shift::where('is_active', 1)->get();
        $satpams = Satpam::where('role','Satpam')->get(); // needed by the modal list

        $query = Jadwal::with(['satpam', 'lokasi', 'shift', 'createdBySatpam', 'updatedBySatpam']);

        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
        }

        if ($request->filled('shift_id')) {
            $query->where('shift_id', $request->shift_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('satpam', function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%");
            });
        }

        // Filter tanggal, default hari ini
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        } else {
            $query->whereDate('tanggal', now()->toDateString());
        }

        $jadwals = $query->orderByDesc('status')->get();

        return view('KepalaSatpam.guard-data', compact('jadwals', 'lokasis', 'satpams', 'shifts'));
    }

    public function storeJadwal(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'assign'     => 'required|json',
        ]);

        $assignments = json_decode($request->assign, true);
        if (empty($assignments)) {
            return response()->json(['success' => false, 'message' => 'Data penugasan kosong.'], 422);
        }

        $start = Carbon::parse($request->start_date);
        $end   = Carbon::parse($request->end_date);

        // 1. Kumpulkan semua ID satpam dari modal yang disubmit
        $allUserIdsInModal = [];
        foreach ($assignments as $lokasiData) {
            foreach ($lokasiData as $userIds) {
                $allUserIdsInModal = array_merge($allUserIdsInModal, $userIds);
            }
        }
        $allUserIdsInModal = array_unique($allUserIdsInModal);

        if (empty($allUserIdsInModal)) {
             return response()->json(['success' => false, 'message' => 'Tidak ada satpam yang dipilih.'], 422);
        }
        
        // 2. Ambil semua jadwal yang SUDAH ADA untuk satpam-satpam tersebut pada rentang tanggal yang dipilih.
        // Ini adalah kunci dari logika baru.
        $existingJadwals = Jadwal::whereBetween('tanggal', [$start, $end])
                                 ->whereIn('user_id', $allUserIdsInModal)
                                 ->select('tanggal', 'user_id')
                                 ->get()
                                 ->groupBy('tanggal') // Kelompokkan berdasarkan tanggal
                                 ->map(function ($items) {
                                     // Jadikan daftar user_id untuk setiap tanggal
                                     return $items->pluck('user_id')->all();
                                 })
                                 ->all();

        $jadwalBatch = [];
        $now = now();
        $loggedInUserId = auth()->id();

        // 3. Looping untuk setiap hari dari start_date hingga end_date
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $tanggal = $date->format('Y-m-d');
            
            // Ambil daftar user yang sudah dijadwalkan pada tanggal ini. Jika tidak ada, array akan kosong.
            $alreadyScheduledUserIds = $existingJadwals[$tanggal] ?? [];

            // --- Proses Satpam yang Ditugaskan (On Duty) ---
            foreach ($assignments as $lokasiId => $shifts) {
                foreach ($shifts as $shiftId => $userIds) {
                    // Abaikan satpam yang tidak ditugaskan (unassigned) di loop ini
                    if ($lokasiId === "null" || $shiftId === "null") continue;

                    foreach ($userIds as $userId) {
                        // INILAH PENGECEKANNYA:
                        // Jika user_id BELUM ADA di dalam daftar yang sudah terjadwal untuk hari ini,
                        // maka tambahkan ke batch.
                        if (!in_array($userId, $alreadyScheduledUserIds)) {
                            $jadwalBatch[] = [
                                'tanggal'    => $tanggal,
                                'lokasi_id'  => $lokasiId,
                                'shift_id'   => $shiftId,
                                'user_id'    => $userId,
                                'status'     => 'On Duty',
                                'created_at' => $now,
                                'updated_at' => $now,
                                'created_by' => $loggedInUserId,
                                'updated_by' => null
                            ];
                        }
                    }
                }
            }
            
            // --- Proses Satpam yang Tidak Ditugaskan (Off Duty) ---
            // Ambil dari list 'unassigned'
            $unassignedUsers = $assignments['null']['null'] ?? [];
            foreach ($unassignedUsers as $userId) {
                // Lakukan pengecekan yang sama
                if (!in_array($userId, $alreadyScheduledUserIds)) {
                     $jadwalBatch[] = [
                        'tanggal'    => $tanggal,
                        'lokasi_id'  => null,
                        'shift_id'   => null,
                        'user_id'    => $userId,
                        'status'     => 'Off Duty',
                        'created_at' => $now,
                        'updated_at' => $now,
                        'created_by' => $loggedInUserId,
                        'updated_by' => null
                    ];
                }
            }
        }

        // 4. Jika setelah semua pengecekan tidak ada jadwal baru untuk ditambahkan, beri pesan.
        if (empty($jadwalBatch)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada jadwal baru untuk ditambahkan. Semua satpam pada tanggal tersebut sudah memiliki jadwal.'], 422);
        }

        Jadwal::insert($jadwalBatch);
        session()->flash('success', 'Jadwal baru berhasil disimpan!');
        return response()->json(['success' => true]);
    }

    public function update(Request $request, $id)
    {
        $jadwal = Jadwal::findOrFail($id);
        $loggedInUserId = auth()->id();

        $jadwal->update([
            'lokasi_id'  => $request->lokasi_id ?: null,
            'shift_id' => $request->shift_id ?: null,
            'status'     => $request->status ?: 'Off Duty',
            'updated_by' => $loggedInUserId,
        ]);

        $updatedByName = $jadwal->fresh()->updatedBySatpam->nama ?? '-';

        return response()->json([
            'success'         => true,
            'updated_by_name' => $updatedByName // Kirim nama kembali ke frontend
        ]);
    }

    
}
