<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\JurnalSatpam;
use App\Models\Upload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JurnalSatpamController extends Controller
{
    public function create()
    {
        $lokasis = Lokasi::where('is_active', 1)->get();
        $shifts = Shift::all();
        return view('admin.fitur-1', compact('lokasis', 'shifts'));
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
            'tamu_belum_keluar', 'karyawan_dinas_luar', 'barang_inventaris_keluar',
            'kendaraan_dinas_luar', 'lampu_mati'
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
            'is_karyawan_dinas_luar' => $request->is_karyawan_dinas_luar,
            'karyawan_dinas_luar' => $request->karyawan_dinas_luar,
            'is_barang_inventaris_keluar' => $request->is_barang_inventaris_keluar,
            'barang_inventaris_keluar' => $request->barang_inventaris_keluar,
            'is_kendaraan_dinas_luar' => $request->is_kendaraan_dinas_luar,
            'kendaraan_dinas_luar' => $request->kendaraan_dinas_luar,
            'is_lampu_mati' => $request->is_lampu_mati,
            'lampu_mati' => $request->lampu_mati,
            'status' => $status,
        ]);

        // Simpan file upload (jika ada)
        if ($request->hasFile('uploads')) {
            foreach ($request->file('uploads') as $file) {
                $originalName = $file->getClientOriginalName(); // nama asli
                $path = $file->storeAs('jurnal_uploads', $originalName, 'public');

                Upload::create([
                    'jurnal_id' => $jurnal->id,
                    'file_path' => $path, // tetap simpan path-nya
                ]);
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
        $shifts = Shift::where('lokasi_id', $jurnal->lokasi_id)->get();

        return view('admin.fitur-1-edit', compact('jurnal', 'lokasis', 'shifts'));
    }

    public function destroy($id)
    {
        $jurnal = JurnalSatpam::findOrFail($id);
        
        // Hapus semua file terkait
        foreach ($jurnal->uploads as $upload) {
            \Storage::disk('public')->delete($upload->file_path);
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

        // Validasi isian khusus jika 'Yes'
        $items = [
            'kejadian_temuan', 'lembur', 'proyek_vendor', 'paket_dokumen',
            'tamu_belum_keluar', 'karyawan_dinas_luar', 'barang_inventaris_keluar',
            'kendaraan_dinas_luar', 'lampu_mati'
        ];

        foreach ($items as $item) {
            if ($request->input("is_$item") === '1' && !$request->filled($item)) {
                return back()->withInput()->withErrors([
                    $item => 'Keterangan wajib diisi jika memilih Yes.'
                ]);
            }
        }

        // Update jurnal
        $jurnal->update([
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
            'is_karyawan_dinas_luar' => $request->is_karyawan_dinas_luar,
            'karyawan_dinas_luar' => $request->karyawan_dinas_luar,
            'is_barang_inventaris_keluar' => $request->is_barang_inventaris_keluar,
            'barang_inventaris_keluar' => $request->barang_inventaris_keluar,
            'is_kendaraan_dinas_luar' => $request->is_kendaraan_dinas_luar,
            'kendaraan_dinas_luar' => $request->kendaraan_dinas_luar,
            'is_lampu_mati' => $request->is_lampu_mati,
            'lampu_mati' => $request->lampu_mati,
            'status' => "waiting",
        ]);

        // Hapus file lama jika ditandai
        if ($request->has('delete_existing')) {
            foreach ($request->delete_existing as $fileId) {
                $upload = Upload::find($fileId);
                if ($upload) {
                    Storage::disk('public')->delete($upload->file_path);
                    $upload->delete();
                }
            }
        }

        // Upload file baru jika ada
        if ($request->hasFile('uploads')) {
            foreach ($request->file('uploads') as $file) {
                $originalName = $file->getClientOriginalName();
                $path = $file->storeAs('jurnal_uploads', $originalName, 'public');

                Upload::create([
                    'jurnal_id' => $jurnal->id,
                    'file_path' => $path,
                ]);
            }
        }

        return redirect()->route('log.history')->with('success', 'Jurnal berhasil diperbarui');
    }

}
