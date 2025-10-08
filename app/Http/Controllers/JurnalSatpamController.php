<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\Satpam;
use App\Models\JurnalSatpam;
use App\Models\Upload;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class JurnalSatpamController extends Controller
{
    // Method private untuk melakukan pengecekan dan mengembalikan data yang dibutuhkan
    private function checking($user)
    {
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

        $UserLoginNow = $user->id;
        $count = $totalLokasiCount - $allLatestJurnal->count();

        $getUserNameById = function ($id) {
            return Satpam::where('id', $id)->value('nama') ?? 'User tidak dikenal';
        };

        return compact(
            'allLokasi',
            'totalLokasiCount',
            'latestJurnalsPerLokasi',
            'latestShiftJurnal',
            'latestShiftId',
            'allLatestJurnal',
            'UserLoginNow',
            'count',
            'getUserNameById'
        );
    }

    public function create()
    {
        $user = Auth::user();

        $viewData = [
            'lokasis' => Lokasi::where('is_active', 1)->get(),
            'shifts'  => Shift::where('is_active', 1)->get(),
            'satpams' => Satpam::where('role', 'Satpam')->get(),
        ]; 

        if ($user->role !== 'Satpam') {
            return view('kepala_satpam.journal-sub', $viewData);
        }

        $data = $this->checking($user);

        // Jika tidak ada jurnal sama sekali
        if ($data['latestJurnalsPerLokasi']->isEmpty()) {
            return view('kepala_satpam.journal-sub', $viewData);
        }

        // Jika semua lokasi sudah disubmit jurnal (count = 0)
        if ($data['count'] == 0) {
            $isUserNextShift = $data['allLatestJurnal']->contains(function ($jurnal) use ($data) {
                return $jurnal->next_shift_user_id == $data['UserLoginNow'];
            });

            if ($isUserNextShift) {
                // ambil semua approve status dari latest jurnal
                $latestJournalStatus  = $data['allLatestJurnal']->pluck('status')->unique()->toArray();
                
                // Cek apakah SEMUA approval status = 1 (semua approved) dari latest jurnalnya
                $AllJournalStatusWaiting  = empty($latestJournalStatus) || 
                    collect($latestJournalStatus)->every(fn($status) => $status != 'pending');
                if ($AllJournalStatusWaiting) {
                    // Semua sudah approved
                    return view('kepala_satpam.journal-sub', $viewData);
                } else {
                    // Ada yang belum approved, tampilkan warning
                    session()->flash('flash_notification', [
                        'type' => 'warning',
                        'message' => 'Approve dahulu jurnal shift sebelumnya!'
                    ]);
                    return view('kepala_satpam.journal-sub', $viewData);
                }
            } else {
                $nextShiftUserIds = $data['allLatestJurnal']->pluck('next_shift_user_id')->filter()->unique()->toArray();
                $nextShiftUserNames = Satpam::whereIn('id', $nextShiftUserIds)->pluck('nama')->implode(', ');

                $message = !empty($nextShiftUserNames)
                    ? "{$nextShiftUserNames} belum mengisi jurnal!"
                    : "Jurnal shift terakhir belum menunjuk penanggung jawab berikutnya.";

                session()->flash('flash_notification', [
                    'type' => 'warning',
                    'message' => $message
                ]);
                return view('kepala_satpam.journal-sub', $viewData);
            }
        }
        // Masih ada lokasi yang jurnalnya belum disubmit (count > 0)
        else {
            $isUserResponsible = $data['allLatestJurnal']->contains(function ($jurnal) use ($data) {
                return $jurnal->user_id == $data['UserLoginNow'];
            });

            if ($isUserResponsible) {
                return view('kepala_satpam.journal-sub', $viewData);
            } else {
                $responsibleUserIds = $data['allLatestJurnal']->pluck('user_id')->unique()->toArray();
                $responsibleUserNames = Satpam::whereIn('id', $responsibleUserIds)->pluck('nama')->implode(', ');

                $message = !empty($responsibleUserNames)
                    ? "{$responsibleUserNames} belum mengisi jurnal!"
                    : "Penanggung jawab jurnal belum mengisi jurnal.";

                session()->flash('flash_notification', [
                    'type' => 'warning',
                    'message' => $message
                ]);
                return view('kepala_satpam.journal-sub', $viewData);
            }
        }
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Validasi dasar
        $request->validate([
            'lokasi_id' => 'required|exists:lokasis,id',
            'shift_id' => 'required|exists:shifts,id',
            'next_shift_user_id' => 'nullable|exists:satpams,id',
            'tanggal' => 'required|date',
            'laporan_kegiatan' => 'required|string',
        ]);

        // Cek jurnal double
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

        
        // Cek kesesuaian Jurnal yg disubmit
        $data = $this->checking($user);
        
        //dd($data['count'], $data['latestShiftId'], $data['latestShiftJurnal']->next_shift_user_id);
        if ($data['latestJurnalsPerLokasi']->isNotEmpty()) {
            // Jika jurnal shift sudah lengkap
            if ($data['count'] == 0) {
                $activeShifts = Shift::where('is_active', 1)->orderBy('mulai_shift')->get();
                $latestShiftIndex = $activeShifts->search(fn($shift) => $shift->id == $data['latestShiftJurnal']->shift_id);

                if ($latestShiftIndex === false) {
                    // fallback, misal lanjutkan saja
                }

                $nextShiftIndex = ($latestShiftIndex + 1) % $activeShifts->count();
                $nextShift = $activeShifts[$nextShiftIndex];
                $nextDate = \Carbon\Carbon::parse($data['latestShiftJurnal']->tanggal);
                if ($nextShiftIndex == 0) {
                    $nextDate->addDay();
                }

                $expectedDate = $nextDate->format('Y-m-d');
                $dateMismatch = $request->tanggal != $expectedDate;
                
                if ($request->shift_id != $nextShift->id || $dateMismatch) {
                    //dd($request->tanggal, $dateMismatch);
                    $formattedExpectedDate = $nextDate->format('m-d-Y');
                    $errorMessage = 'Isi jurnal untuk ' . $nextShift->nama_shift . ' pada tanggal ' . $formattedExpectedDate . '!';
                    return response()->json([
                        'success' => false,
                        'message' => $errorMessage
                    ], 422);
                }
            }
            // Jika jurnal shift belum lengkap
            else {
                $responsibleUserIds = $data['allLatestJurnal']->pluck('user_id')->unique()->toArray();
                $nextShiftUserIds = $data['allLatestJurnal']->pluck('next_shift_user_id')->filter()->unique()->toArray();
                $latestJournalDate = $data['allLatestJurnal']->pluck('tanggal')->unique()->toArray();

                if ($user->role == 'Kepala Satpam' || in_array($user->id, $responsibleUserIds)) {
                    // Cek shift_id (perbandingan scalar vs scalar)
                    $shiftMismatch = $request->shift_id != $data['latestShiftId'];
                    
                    // Cek next_shift_user_id (scalar vs array: gunakan in_array)
                    $nextShiftMismatch = !in_array($request->next_shift_user_id, $nextShiftUserIds);

                    // Cek tanggal (scalar vs array: gunakan in_array)
                    $dateMismatch = empty($latestJournalDate) ? false : !in_array($request->tanggal, $latestJournalDate);
                    
                    // Jika salah satu mismatch (OR), return error
                    if ($shiftMismatch || $nextShiftMismatch || $dateMismatch) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Isi jurnal dengan Shift, next Shift, dan Tanggal yang sesuai!'
                        ], 422);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Anda tidak bertanggung jawab untuk mengisi jurnal tersebut'
                    ], 403);
                }
            }
        }

        // Daftar item isian yang harus dicek radio button nya
        $itemsYesNo = ['kejadian_temuan', 'lembur', 'proyek_vendor'];
        $itemsMasukKeluar = ['barang_keluar'];

        
        // Validasi untuk grup "Yes/No"
        foreach ($itemsYesNo as $item) {
            if ($request->input("is_$item") === '1' && !$request->filled($item)) {
                return back()->withInput()->withErrors([
                    $item => 'Keterangan wajib diisi jika memilih Yes.'
                ]);
            }
        }

        // Validasi untuk grup "Masuk/Keluar"
        foreach ($itemsMasukKeluar as $item) {
            // $request->has() akan bernilai true jika 'Masuk' ATAU 'Keluar' dipilih
            if ($request->has("is_$item") && !$request->filled($item)) {
                return back()->withInput()->withErrors([
                    $item => 'Keterangan wajib diisi jika Masuk / Keluar.'
                ]);
            }
        }

        // Simpan data jurnal ke database
        $status = Auth::user()->role === 'Kepala Satpam' ? 'approve' : 'pending';
        $jurnal = JurnalSatpam::create([
            'user_id' => Auth::id(),
            'lokasi_id' => $request->lokasi_id,
            'shift_id' => $request->shift_id,
            'next_shift_user_id' => $request->next_shift_user_id,
            'tanggal' => $request->tanggal,
            'laporan_kegiatan' => $request->laporan_kegiatan,
            'is_kejadian_temuan' => $request->is_kejadian_temuan,
            'kejadian_temuan' => $request->kejadian_temuan,
            'is_lembur' => $request->is_lembur,
            'lembur' => $request->lembur,
            'is_proyek_vendor' => $request->is_proyek_vendor,
            'proyek_vendor' => $request->proyek_vendor,
            'is_barang_keluar' => $request->is_barang_keluar,
            'barang_keluar' => $request->barang_keluar,
            'info_tambahan' => $request->info_tambahan,
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
        $satpams = Satpam::where('role', 'Satpam')->get();

        return view('kepala_satpam.journal-edit', compact('jurnal', 'lokasis', 'shifts', 'satpams'));
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
            'next_shift_user_id' => 'nullable|exists:satpams,id',
            'tanggal' => 'required|date',
            'laporan_kegiatan' => 'required|string',
        ]);

        // Daftar item isian yang harus dicek radio button nya
        $itemsYesNo = ['kejadian_temuan', 'lembur', 'proyek_vendor'];
        $itemsMasukKeluar = ['barang_keluar'];

        // Validasi untuk grup "Yes/No"
        foreach ($itemsYesNo as $item) {
            if ($request->input("is_$item") === '1' && !$request->filled($item)) {
                return back()->withInput()->withErrors([
                    $item => 'Keterangan wajib diisi jika memilih Yes.'
                ]);
            }
        }

        // Validasi untuk grup "Masuk/Keluar"
        foreach ($itemsMasukKeluar as $item) {
            // $request->has() akan bernilai true jika 'Masuk' ATAU 'Keluar' dipilih
            if ($request->has("is_$item") && !$request->filled($item)) {
                return back()->withInput()->withErrors([
                    $item => 'Keterangan wajib diisi jika Masuk / Keluar.'
                ]);
            }
        }

        // --- CEK PERUBAHAN DATA ---
        $hasChanges = false;
        $updateData = [
            'lokasi_id' => $request->lokasi_id, 
            'shift_id' => $request->shift_id,  
            'next_shift_user_id' => $request->next_shift_user_id, 
            'tanggal' => $request->tanggal,
            'laporan_kegiatan' => $request->laporan_kegiatan,
            'info_tambahan' => $request->info_tambahan,
            'is_kejadian_temuan' => $request->is_kejadian_temuan,
            'kejadian_temuan' => $request->kejadian_temuan,
            'is_lembur' => $request->is_lembur,
            'lembur' => $request->lembur,
            'is_proyek_vendor' => $request->is_proyek_vendor,
            'proyek_vendor' => $request->proyek_vendor,
            'is_barang_keluar' => $request->is_barang_keluar,
            'barang_keluar' => $request->barang_keluar,
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

    public function updateApproval(Request $request, $id)
    {
        $jurnal = JurnalSatpam::findOrFail($id);
        
        // Validasi: Hanya next_shift_user_id yang boleh approve
        if (Auth::id() != $jurnal->next_shift_user_id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak berhak approve jurnal ini.'], 403);
        }
        
        // Update status jadi Waiting
        $jurnal->update(['status' => 'waiting']);
        
        session()->flash('success', 'Jurnal berhasil di-approve');
    
        return response()->json([
            'success' => true,
            'redirect_url' => route('log.history')
        ]);
    }
}
