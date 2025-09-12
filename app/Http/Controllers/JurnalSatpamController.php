<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\JurnalSatpam;
use App\Models\Upload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class JurnalSatpamController extends Controller
{
    public function create()
    {
        $user = Auth::user();
    
        // Siapkan data untuk view di awal agar tidak berulang
        $viewData = [
            'lokasis' => Lokasi::where('is_active', 1)->get(),
            'shifts' => Shift::where('is_active', 1)->get(),
        ];

        // ===================================================================
        // Pengecekan hanya berlaku untuk role 'Satpam'
        // Kepala Satpam atau role lain bisa langsung lolos.
        // ===================================================================
        if ($user->role !== 'Satpam') {
            return view('admin.fitur-1', $viewData);
        }
        
        // ===================================================================
        // PENGECEKAN #1: Satpam yang login harus punya jadwal hari ini
        // ===================================================================
        $now = now();
        $today = now()->today();
        $todaysJadwal = \App\Models\Jadwal::where('user_id', $user->id)
                                          ->whereDate('tanggal', $today)
                                          ->first();

        if (!$todaysJadwal) {
            session()->flash('flash_notification', [
                'type' => 'warning',
                'message' => 'Anda tidak memiliki jadwal kerja hari ini!'
            ]);
            return view('admin.fitur-1', $viewData);
        }

        // ===================================================================
        // PENGECEKAN #2: Jurnal shift sebelumnya harus sudah terisi
        // (Berdasarkan jurnal terakhir di database)
        // ===================================================================

        $currentUserLokasiId = $todaysJadwal->lokasi_id;
        $currentUserLokasi = Lokasi::find($currentUserLokasiId);

        $latestJurnalForLocation = JurnalSatpam::where('lokasi_id', $currentUserLokasiId)
                                            ->orderBy('tanggal', 'desc')
                                            ->latest()
                                            ->first();

        if ($latestJurnalForLocation) {
            $activeShifts = Shift::where('is_active', 1)->orderBy('mulai_shift')->get();

            if ($activeShifts->count() > 1) {
                $latestShiftId = $latestJurnalForLocation->shift_id;
                $latestShiftIndex = $activeShifts->search(fn($shift) => $shift->id == $latestShiftId);
                
                if ($latestShiftIndex !== false) {
                    $nextShiftIndex = ($latestShiftIndex + 1) % $activeShifts->count();
                    $nextShift = $activeShifts[$nextShiftIndex];
                    
                    $nextDate = \Carbon\Carbon::parse($latestJurnalForLocation->tanggal);
                    if ($nextShiftIndex == 0) {
                        $nextDate->addDay();
                    }

                    $nextJurnalExists = JurnalSatpam::where('shift_id', $nextShift->id)
                                                    ->where('lokasi_id', $currentUserLokasiId)
                                                    ->whereDate('tanggal', $nextDate)->exists();
                    
                    if (!$nextJurnalExists) {
                        // 1. Cari tahu siapa penanggung jawab shift yang terlewat.
                        $jadwalMissedShift = \App\Models\Jadwal::whereDate('tanggal', $nextDate)
                                                        ->where('shift_id', $nextShift->id)
                                                        ->where('lokasi_id', $currentUserLokasiId)
                                                        ->first();
                        $responsibleUserId = $jadwalMissedShift->user_id ?? null;

                        // 2. ATURAN PENGECUALIAN:
                        // Jika user yang login adalah penanggung jawab, JANGAN BLOKIR DIA.
                        // Tanda '!' di awal berarti "jika TIDAK".
                        if (!$responsibleUserId || $responsibleUserId != $user->id) {                            
                            // 3. Untuk semua user LAIN, jalankan pengecekan dampak.
                            $currentUserSchedule = \App\Models\Jadwal::where('user_id', $user->id)
                                ->where('lokasi_id', $currentUserLokasiId)
                                ->where('tanggal', '>=', now()->toDateString())
                                ->join('shifts', 'jadwals.shift_id', '=', 'shifts.id')
                                ->orderBy('tanggal', 'asc')
                                ->orderBy('shifts.mulai_shift', 'asc')
                                ->select('jadwals.*')
                                ->first();

                            $isUserAffected = false;
                            if ($currentUserSchedule) {
                                $userScheduleDate = \Carbon\Carbon::parse($currentUserSchedule->tanggal);
                                
                                // Jika jadwal user jatuh pada hari SETELAH shift yang terlewat
                                if ($userScheduleDate->gt($nextDate)) {
                                    $isUserAffected = true;
                                } 
                                // Jika di hari yang SAMA, cek urutan shiftnya
                                elseif ($userScheduleDate->isSameDay($nextDate)) {
                                    $missedShiftIndexInSequence = $activeShifts->search(fn($s) => $s->id == $nextShift->id);
                                    $userShiftIndexInSequence = $activeShifts->search(fn($s) => $s->id == $currentUserSchedule->shift_id);

                                    if ($userShiftIndexInSequence > $missedShiftIndexInSequence) {
                                        $isUserAffected = true;
                                    }
                                }
                            }
                            
                            // 4. Jika user lain tersebut terdampak, baru BLOKIR.
                            if ($isUserAffected) {
                                $formattedDate = $nextDate->format('d F Y');
                                $message = "Jurnal {$currentUserLokasi->nama_lokasi} - {$nextShift->nama_shift} ({$formattedDate}) belum disubmit.";
                                // dd($responsibleUserId);
                                session()->flash('flash_notification', [
                                    'type' => 'warning',
                                    'message' => $message
                                ]);
                                return view('admin.fitur-1', $viewData);
                            }
                        }
                    }
                }
            }
        }

        // Jika semua pengecekan untuk Satpam lolos, tampilkan halaman form
        return view('admin.fitur-1', $viewData);
    }

    public function store(Request $request)
    {
        // Validasi dasar
        $request->validate([
            'lokasi_id' => 'required|exists:lokasis,id',
            'shift_id' => 'required|exists:shifts,id',
            'tanggal' => 'required|date',
            'cuaca' => 'required|string',
            'info_tambahan' => 'required|string',
        ]);

        // Daftar item isian yang harus dicek jika "yes"
        $items = [
            'kejadian_temuan', 'lembur', 'proyek_vendor', 'paket_dokumen',
            'tamu_belum_keluar', 'karyawan_dinas_keluar', 'barang_keluar',
            'kendaraan_dinas_keluar', 'lampu_mati'
        ];

        foreach ($items as $item) {
            $isChecked = $request->input("is_$item");
            if ($isChecked === '1' && !$request->filled($item)) {
                return back()->withInput()->withErrors([
                    $item => 'Keterangan wajib diisi jika memilih Yes.'
                ]);
            }
        }

        // Simpan data jurnal ke database
        $status = Auth::user()->role === 'Kepala Satpam' ? 'Approve' : 'Waiting';
        $jurnal = JurnalSatpam::create([
            'user_id' => Auth::id(),
            'lokasi_id' => $request->lokasi_id,
            'shift_id' => $request->shift_id,
            'tanggal' => $request->tanggal,
            'cuaca' => $request->cuaca,
            'info_tambahan' => $request->info_tambahan,
            'is_kejadian_temuan' => $request->is_kejadian_temuan,
            'kejadian_temuan' => $request->kejadian_temuan,
            'is_lembur' => $request->is_lembur,
            'lembur' => $request->lembur,
            'is_proyek_vendor' => $request->is_proyek_vendor,
            'proyek_vendor' => $request->proyek_vendor,
            'is_paket_dokumen' => $request->is_paket_dokumen,
            'paket_dokumen' => $request->paket_dokumen,
            'is_tamu_belum_keluar' => $request->is_tamu_belum_keluar,
            'tamu_belum_keluar' => $request->tamu_belum_keluar,
            'is_karyawan_dinas_keluar' => $request->is_karyawan_dinas_keluar,
            'karyawan_dinas_keluar' => $request->karyawan_dinas_keluar,
            'is_barang_keluar' => $request->is_barang_keluar,
            'barang_keluar' => $request->barang_keluar,
            'is_kendaraan_dinas_keluar' => $request->is_kendaraan_dinas_keluar,
            'kendaraan_dinas_keluar' => $request->kendaraan_dinas_keluar,
            'is_lampu_mati' => $request->is_lampu_mati,
            'lampu_mati' => $request->lampu_mati,
            'status' => $status,
        ]);

        // Simpan file upload ke PUBLIC, bukan storage
        if ($request->hasFile('uploads')) {
            $dest = public_path('jurnal_uploads');
            if (!File::exists($dest)) {
                File::makeDirectory($dest, 0775, true);
            }

            foreach ($request->file('uploads') as $file) {
                if ($file && $file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $filenameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();
                    
                    $safeFilename = Str::slug($filenameWithoutExt);
                    // Gunakan time() untuk memastikan keunikan
                    $finalName = time() . '-' . $safeFilename . '.' . $extension;

                    $file->move($dest, $finalName);

                    Upload::create([
                        'jurnal_id' => $jurnal->id,
                        'file_path' => 'jurnal_uploads/' . $finalName,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        }
        return response()->json(['success' => true]);
    }

    public function getByLocation($id)
    {
        $shifts = \App\Models\Shift::where('lokasi_id', $id)->get();
        return response()->json($shifts);
    }

    public function edit($id)
    {
        $jurnal = JurnalSatpam::with(['lokasi', 'shift', 'uploads'])->findOrFail($id);
        $lokasis = Lokasi::where('is_active', 1)->get();
        $shifts = Shift::where('is_active', 1)->get();

        return view('admin.fitur-1-edit', compact('jurnal', 'lokasis', 'shifts'));
    }

    public function destroy($id)
    {
        $jurnal = JurnalSatpam::findOrFail($id);
        
        // Hapus semua file terkait
        foreach ($jurnal->uploads as $upload) {
            File::delete(public_path($upload->file_path));
            $upload->delete();
        }

        $jurnal->delete();

        return redirect()->back()->with('success', 'Jurnal berhasil dihapus.');
    }

    public function update(Request $request, $id)
    {
        $jurnal = JurnalSatpam::findOrFail($id);

        $request->validate([
            'lokasi_id' => 'required|exists:lokasis,id',
            'shift_id' => 'required|exists:shifts,id',
            'tanggal' => 'required|date',
            'cuaca' => 'required|string',
            'info_tambahan' => 'required|string',
        ]);

        $items = [
            'kejadian_temuan', 'lembur', 'proyek_vendor', 'paket_dokumen',
            'tamu_belum_keluar', 'karyawan_dinas_keluar', 'barang_keluar',
            'kendaraan_dinas_keluar', 'lampu_mati'
        ];

        foreach ($items as $item) {
            if ($request->input("is_$item") === '1' && !$request->filled($item)) {
                return back()->withInput()->withErrors([
                    $item => 'Keterangan wajib diisi jika memilih Yes.'
                ]);
            }
        }

        $newStatus = Auth::user()->role === 'Kepala Satpam' ? 'approve' : 'waiting';

        // --- CEK PERUBAHAN DATA ---
        $hasChanges = false;
        $updateData = [
            'lokasi_id' => $request->lokasi_id,
            'shift_id' => $request->shift_id,
            'tanggal' => $request->tanggal,
            'cuaca' => $request->cuaca,
            'info_tambahan' => $request->info_tambahan,
            'is_kejadian_temuan' => $request->is_kejadian_temuan,
            'kejadian_temuan' => $request->kejadian_temuan,
            'is_lembur' => $request->is_lembur,
            'lembur' => $request->lembur,
            'is_proyek_vendor' => $request->is_proyek_vendor,
            'proyek_vendor' => $request->proyek_vendor,
            'is_paket_dokumen' => $request->is_paket_dokumen,
            'paket_dokumen' => $request->paket_dokumen,
            'is_tamu_belum_keluar' => $request->is_tamu_belum_keluar,
            'tamu_belum_keluar' => $request->tamu_belum_keluar,
            'is_karyawan_dinas_keluar' => $request->is_karyawan_dinas_keluar,
            'karyawan_dinas_keluar' => $request->karyawan_dinas_keluar,
            'is_barang_keluar' => $request->is_barang_keluar,
            'barang_keluar' => $request->barang_keluar,
            'is_kendaraan_dinas_keluar' => $request->is_kendaraan_dinas_keluar,
            'kendaraan_dinas_keluar' => $request->kendaraan_dinas_keluar,
            'is_lampu_mati' => $request->is_lampu_mati,
            'lampu_mati' => $request->lampu_mati,
            'status' => $newStatus,
        ];

        foreach ($updateData as $field => $value) {
            if ($jurnal->$field != $value) {
                $hasChanges = true;
                break;
            }
        }

        // cek file dihapus
        if ($request->filled('delete_existing')) {
            $hasChanges = true;
        }

        // cek file baru diupload
        if ($request->hasFile('uploads')) {
            $hasChanges = true;
        }

        // --- JIKA TIDAK ADA PERUBAHAN ---
        if (!$hasChanges) {
            session()->flash('success', 'Tidak ada perubahan.');
            return response()->json([
                'success' => true,
                'redirect_url' => route('log.history')
            ]);
        }

        // --- UPDATE JURNAL ---
        $updateData['updated_by'] = Auth::id();
        $jurnal->update($updateData);

        // --- HAPUS FILE LAMA ---
        if ($request->filled('delete_existing')) {
            foreach ((array)$request->delete_existing as $fileId) {
                if ($upload = Upload::find($fileId)) {
                    File::delete(public_path($upload->file_path));
                    $upload->update(['updated_by' => Auth::id()]);
                    $upload->delete();
                }
            }
        }

        // --- UPLOAD FILE BARU ---
        if ($request->hasFile('uploads')) {
            $dest = public_path('jurnal_uploads');
            if (!File::exists($dest)) {
                File::makeDirectory($dest, 0775, true);
            }

            foreach ($request->file('uploads') as $file) {
                if ($file && $file->isValid()) {
                    $originalName = $file->getClientOriginalName();
                    $filenameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);
                    $extension = $file->getClientOriginalExtension();

                    $safeFilename = Str::slug($filenameWithoutExt);
                    $finalName = time() . '-' . $safeFilename . '.' . $extension;

                    $file->move($dest, $finalName);

                    Upload::create([
                        'jurnal_id' => $jurnal->id,
                        'file_path' => 'jurnal_uploads/' . $finalName,
                        'updated_by' => Auth::id(),
                    ]);
                }
            }
        }

        session()->flash('success', 'Jurnal berhasil diperbarui.');

        return response()->json([
            'success' => true,
            'redirect_url' => route('log.history')
        ]);
    }

}
