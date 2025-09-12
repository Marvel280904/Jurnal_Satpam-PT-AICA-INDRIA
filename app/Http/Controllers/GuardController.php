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

        $jadwals = $query->get();

        return view('admin.fitur-3', compact('jadwals', 'lokasis', 'satpams', 'shifts'));
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

    // ===== modal helpers =====

    public function checkJadwal(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
        ]);

        $exists = Jadwal::whereBetween('tanggal', [$request->start_date, $request->end_date])->exists();
        return response()->json(['exists' => $exists]);
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

        $jadwalBatch = [];
        $now = now();
        $loggedInUserId = auth()->id();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $tanggal = $date->format('Y-m-d');

            foreach ($assignments as $lokasiId => $shifts) {
                foreach ($shifts as $shiftId => $userIds) {
                    foreach ($userIds as $userId) {
                        $jadwalBatch[] = [
                            'tanggal'   => $tanggal,
                            'lokasi_id' => $lokasiId !== "null" ? $lokasiId : null,
                            'shift_id'=> $shiftId  !== "null" ? $shiftId  : null,
                            'user_id'   => $userId,
                            'status'    => 'On Duty',
                            'created_at'=> $now,
                            'updated_at'=> $now,
                            'created_by' => $loggedInUserId,
                            'updated_by' => null
                        ];
                    }
                }
            }
        }

        if (empty($jadwalBatch)) {
            return response()->json(['success' => false, 'message' => 'Tidak ada data untuk disimpan.'], 422);
        }

        Jadwal::insert($jadwalBatch);
        session()->flash('success', 'Jadwal berhasil disimpan!');
        return response()->json(['success' => true]);
    }
}
