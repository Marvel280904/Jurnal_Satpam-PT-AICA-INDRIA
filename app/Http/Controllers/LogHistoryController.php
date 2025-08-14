<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\View;
use Illuminate\Http\Request;
use App\Models\JurnalSatpam;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\User;
use App\Models\Jadwal;
use Carbon\Carbon;

class LogHistoryController extends Controller
{
    public function index(Request $request)
    {
        $lokasis = Lokasi::all();
        $shifts  = Shift::all();

        $query = JurnalSatpam::with(['lokasi','shift','satpam','uploads']);

        // Lokasi (id)
        if ($request->filled('lokasi_id')) {
            $query->where('lokasi_id', $request->lokasi_id);
        }

        // Shift (bisa terima "Pagi/Siang/Malam" atau "Shift Pagi/Shift Siang/Shift Malam")
        if ($request->filled('shift')) {
            $map = ['Pagi'=>'Shift Pagi','Siang'=>'Shift Siang','Malam'=>'Shift Malam'];
            $shiftNama = $map[$request->shift] ?? $request->shift; // normalisasi
            if ($shiftId = Shift::where('nama_shift', $shiftNama)->value('id')) {
                $query->where('shift_id', $shiftId);
            }
        }

        // Tanggal persis
        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        // Search by nama satpam / lokasi
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->whereHas('satpam', fn($w) => $w->where('nama','like',"%{$s}%"))
                ->orWhereHas('lokasi', fn($w) => $w->where('nama_lokasi','like',"%{$s}%"));
            });
        }

        $jurnals = $query->orderByDesc('id')->get(); // Collection -> forelse @empty bisa

        return view('admin.fitur-2', compact('jurnals','lokasis','shifts'));
    }

    public function updateStatus(Request $request, $id)
    {
        // Only Kepala Satpam
        if (auth()->user()->role !== 'Kepala Satpam') {
            abort(403, 'Unauthorized');
        }

        $data = $request->validate([
            'status' => 'required|in:approve,reject',
        ]);

        $jurnal = JurnalSatpam::findOrFail($id);
        $jurnal->status = $data['status'];
        $jurnal->save();

        return response()->json([
            'success' => true,
            'status'  => $jurnal->status,
        ]);
    }

    public function downloadPDF($id)
    {
        $jurnal = JurnalSatpam::with(['lokasi', 'shift', 'satpam'])->findOrFail($id);

        // Normalize date and shift name to match jadwals table
        $tanggal   = Carbon::parse($jurnal->tanggal)->toDateString();
        $lokasiId  = $jurnal->lokasi_id;
        // In your app, jadwals.shift_nama stores strings like: "Shift Pagi", "Shift Siang", "Shift Malam"
        $shiftNama = optional($jurnal->shift)->nama_shift; // e.g. "Shift Pagi"

        // Ambil semua satpam yang terjadwal pada tanggal + lokasi + shift yang sama
        $anggotaShift = Jadwal::with('satpam')
            ->whereDate('tanggal', $tanggal)
            ->when($lokasiId, fn($q) => $q->where('lokasi_id', $lokasiId))
            ->when($shiftNama, fn($q) => $q->where('shift_nama', $shiftNama))
            ->get()
            ->pluck('satpam.nama') // assumes Jadwal->satpam relation returns User with 'nama'
            ->filter()             // buang null jika ada
            ->unique()             // jaga-jaga duplikat
            ->values();

        // Nama file
        $formattedDate = Carbon::parse($jurnal->tanggal)->format('d-m-Y');
        $filename = 'journal-' . $formattedDate . '.pdf';

        // Render PDF
        $pdf = Pdf::loadView('pdf.jurnal-pdf', [
            'jurnal'        => $jurnal,
            'anggotaShift'  => $anggotaShift,
        ]);

        return $pdf->download($filename);
    }
}
