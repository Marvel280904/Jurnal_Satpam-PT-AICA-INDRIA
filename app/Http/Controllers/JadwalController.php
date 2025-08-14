<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lokasi;
use App\Models\Satpam;
use App\Models\Jadwal;
use Carbon\Carbon;
use DB;

class JadwalController extends Controller
{
    public function create()
    {
        $lokasis = Lokasi::all();
        $satpams = Satpam::where('role','Satpam')->get();
        return view('jadwal.create', compact('lokasis','satpams'));
    }

    public function checkDate(Request $request)
    {
        $exists = Jadwal::whereBetween('tanggal', [$request->start_date, $request->end_date])->exists();
        return response()->json(['exists' => $exists]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'assign' => 'required|json'
        ]);

        $assignments = json_decode($request->assign, true);
        if (empty($assignments)) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Data kosong.'], 422)
                : redirect()->back()->with('error', 'Tidak ada satpam yang ditugaskan.');
        }

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);

        $jadwalBatch = [];
        $now = Carbon::now();

        for ($date = $start; $date->lte($end); $date->addDay()) {
            $tanggal = $date->format('Y-m-d');
            foreach ($assignments as $lokasiId => $shifts) {
                foreach ($shifts as $shiftNama => $userIds) {
                    foreach ($userIds as $userId) {
                        $jadwalBatch[] = [
                            'tanggal' => $tanggal,
                            'lokasi_id' => $lokasiId !== "null" ? $lokasiId : null,
                            'shift_nama' => $shiftNama !== "null" ? $shiftNama : null,
                            'user_id' => $userId,
                            'status' => 'Off Duty',
                            'created_at' => $now,
                            'updated_at' => $now
                        ];
                    }
                }
            }
        }

        if (empty($jadwalBatch)) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Data jadwal kosong.'], 422)
                : redirect()->back()->with('error', 'Tidak ada data jadwal yang dimasukkan.');
        }

        Jadwal::insert($jadwalBatch);

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        } else {
            return redirect()->back()->with('success', 'Jadwal berhasil disimpan');
        }
    }

}