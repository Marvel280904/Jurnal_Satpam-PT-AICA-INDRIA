<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\JurnalSatpam;
use App\Models\Upload;
use App\Models\Jadwal;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class JurnalSatpamController extends Controller
{
    public function create()
    {
        $user = Auth::user();

        $viewData = [
            'lokasis' => Lokasi::where('is_active', 1)->get(),
            'shifts' => Shift::where('is_active', 1)->get(),
            'prefilledJadwal' => null
        ];

        if ($user->role !== 'Satpam') {
            return view('KepalaSatpam.journal-sub', $viewData);
        }

        // 1. Ambil semua lokasi aktif
        $allLokasi = Lokasi::where('is_active', 1)->get();

        // 2. Ambil latest jurnal per lokasi
        $allLatestJurnal = [];
        foreach ($allLokasi as $lokasi) {
            $latestJurnal = JurnalSatpam::where('lokasi_id', $lokasi->id)
                ->orderBy('tanggal', 'desc')
                ->orderBy('shift_id', 'desc')
                ->first();

            if ($latestJurnal) {
                $allLatestJurnal[] = $latestJurnal;
            }
        }

        // 3. Kalau belum ada data jurnal, abaikan pengecekan
        if (empty($allLatestJurnal)) {
            return view('Satpam.journal-sub', $viewData);
        }

        // 8. Ambil jadwal user hari ini
        $today = now()->today();
        $todaysJadwal = Jadwal::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->first();

        if (!$todaysJadwal) {
            if ($count == 0) {
                session()->flash('flash_notification', [
                    'type' => 'warning',
                    'message' => 'Anda tidak memiliki jadwal kerja hari ini!'
                ]);
                return view('Satpam.journal-sub', $viewData);
            } else{
                $latestResponsibility = collect($UserResponsibleData)
                    ->sortByDesc('tanggal') // urut menurun berdasarkan tanggal
                    ->first();
                $viewData['prefilledJadwal'] = $latestResponsibility ?: null;
                return view('Satpam.journal-sub', $viewData);
                
            }
        }

        $currentUserLokasiId = $todaysJadwal->lokasi_id;
        $currentUserLokasi = Lokasi::find($currentUserLokasiId);

        $latestLokasiIds = collect($allLatestJurnal)->pluck('lokasi_id')->unique()->toArray();
        // Cek apakah lokasi user ada di latest jurnal
        if (!in_array($currentUserLokasiId, $latestLokasiIds)) {
            // Jika lokasi user tidak ada di jurnal terbaru, langsung return view tanpa lanjut pengecekan
            return view('Satpam.journal-sub', $viewData);
        }


        // 4. Ambil semua shift aktif dan urutkan
        $activeShifts = Shift::where('is_active', 1)->orderBy('mulai_shift')->get();

        // Variabel untuk menyimpan tanggung jawab user
        $count = 0;
        $UserResponsibleData = [];

        // 5. Loop semua latest jurnal per lokasi
        foreach ($allLatestJurnal as $latestJurnal) {
            // Hitung next shift dan tanggalnya
            $latestShiftIndex = $activeShifts->search(fn($shift) => $shift->id == $latestJurnal->shift_id);
            if ($latestShiftIndex === false) {
                continue; // skip jika shift tidak ditemukan
            }

            $nextShiftIndex = ($latestShiftIndex + 1) % $activeShifts->count();
            $nextShift = $activeShifts[$nextShiftIndex];
            $nextDate = \Carbon\Carbon::parse($latestJurnal->tanggal);
            if ($nextShiftIndex == 0) {
                $nextDate->addDay();
            }

            // 6. Ambil semua user yang bertanggung jawab di jadwal berikutnya di lokasi ini
            $allResponsibleUsers = Jadwal::whereDate('tanggal', $latestJurnal->tanggal)
                ->where('lokasi_id', $latestJurnal->lokasi_id)
                ->where('shift_id', $nextShift->id)
                ->get();

            // 7. Cek apakah user login bertanggung jawab di jadwal ini
            foreach ($allResponsibleUsers as $jadwal) {
                if ($jadwal->user_id == $user->id) {
                    $count++;
                    $UserResponsibleData[] = $jadwal; // simpan data terakhir yang ditemukan
                }
            }
        }

        

        
        

        // Cari latest jurnal yang lokasi_id-nya sama dengan lokasi user hari ini
        $latestJurnalForCurrentLokasi = null;
        foreach ($allLatestJurnal as $jurnal) {
            if ($jurnal->lokasi_id == $currentUserLokasiId) {
                $latestJurnalForCurrentLokasi = $jurnal;
                break;
            }
        }
        //dd($count);
        // 9. Logika berdasarkan jumlah tanggung jawab user
        if ($count == 0) {
            // Tidak ada tanggung jawab, munculkan notifikasi berdasarkan latest jurnal lokasi user hari ini
            if ($latestJurnalForCurrentLokasi) {
                // Hitung next shift dan tanggalnya
                $latestShiftIndex = $activeShifts->search(fn($shift) => $shift->id == $latestJurnalForCurrentLokasi->shift_id);
                if ($latestShiftIndex === false) {
                    // fallback jika shift tidak ditemukan
                    $nextShift = $activeShifts->first();
                    $nextDate = \Carbon\Carbon::parse($latestJurnalForCurrentLokasi->tanggal);
                } else {
                    $nextShiftIndex = ($latestShiftIndex + 1) % $activeShifts->count();
                    $nextShift = $activeShifts[$nextShiftIndex];
                    $nextDate = \Carbon\Carbon::parse($latestJurnalForCurrentLokasi->tanggal);
                    if ($nextShiftIndex == 0) {
                        $nextDate->addDay();
                    }
                }

                $formattedDate = $nextDate->format('d F Y');
                $lokasiObj = Lokasi::find($latestJurnalForCurrentLokasi->lokasi_id);
                $lokasiNama = $lokasiObj ? $lokasiObj->nama_lokasi : 'Lokasi';

                $message = "Jurnal {$lokasiNama} - {$nextShift->nama_shift} ({$formattedDate}) belum disubmit.";
                session()->flash('flash_notification', [
                    'type' => 'warning',
                    'message' => $message
                ]);
            } else {
                // Jika tidak ada latest jurnal untuk lokasi user hari ini, fallback pesan umum
                session()->flash('flash_notification', [
                    'type' => 'warning',
                    'message' => 'Jurnal belum disubmit.'
                ]);
            }

            return view('Satpam.journal-sub', $viewData);
        } elseif ($count == 1) {
            // Hanya 1 tanggung jawab, isi prefilledJadwal dengan data tersebut
            $viewData['prefilledJadwal'] = $UserResponsibleData[0];
            return view('Satpam.journal-sub', $viewData);
        } else {
            // Lebih dari 1 tanggung jawab
            // Cari data tanggung jawab user yang lokasi_id-nya sama dengan lokasi hari ini
            $responsibleAtCurrentLokasi = null;

            // Jika $User ResponsibleData adalah koleksi, kita perlu cari yang lokasi_id = lokasi hari ini
            // Jika $User ResponsibleData adalah objek tunggal, cek langsung
            if (is_iterable($UserResponsibleData)) {
                foreach ($UserResponsibleData as $jadwal) {
                    if ($jadwal->lokasi_id == $currentUserLokasiId) {
                        $responsibleAtCurrentLokasi = $jadwal;
                        break;
                    }
                }
            } else {
                if ($UserResponsibleData->lokasi_id == $currentUserLokasiId) {
                    $responsibleAtCurrentLokasi = $UserResponsibleData;
                }
            }

            if ($responsibleAtCurrentLokasi) {
                $viewData['prefilledJadwal'] = $responsibleAtCurrentLokasi;
            } else {
                // Jika tidak ada tanggung jawab di lokasi hari ini, fallback ke $User ResponsibleData
                $viewData['prefilledJadwal'] = $UserResponsibleData;
            }

            return view('Satpam.journal-sub', $viewData);
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Validasi dasar
        $request->validate([
            'lokasi_id' => 'required|exists:lokasis,id',
            'shift_id' => 'required|exists:shifts,id',
            'tanggal' => 'required|date',
            'cuaca' => 'required|string',
            'info_tambahan' => 'required|string',
        ]);

        $isDuplicate = JurnalSatpam::where('tanggal', $request->tanggal)
            ->where('lokasi_id', $request->lokasi_id)
            ->where('shift_id', $request->shift_id)
            ->exists();

        if ($isDuplicate) {
            return response()->json([
                'success' => false,
                'message' => 'Jurnal untuk tanggal, lokasi, dan shift tersebut sudah ada!'
            ], 422);
        }

        if ($user->role === 'Satpam') {
            $isCorrectJournal = Jadwal::where('tanggal', $request->tanggal)
                ->where('lokasi_id', $request->lokasi_id)
                ->where('shift_id', $request->shift_id)
                ->where('user_id', $user->id)
                ->exists();
            if (!$isCorrectJournal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jurnal yang disubmit tidak sesuai jadwal Anda!'
                ], 422);
            }
        }

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

        session()->flash('success', 'Jurnal berhasil disubmit.');

        return response()->json([
            'success' => true,
            'redirect_url' => route('log.history') 
        ]);
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

        return view('journal-edit', compact('jurnal', 'lokasis', 'shifts'));
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
