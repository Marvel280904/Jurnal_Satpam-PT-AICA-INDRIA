<?php
namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\Shift;
use Illuminate\Http\Request;
use App\Models\RecentActivity;
use Illuminate\Support\Facades\Auth;


class LocationShiftController extends Controller
{
    public function index()
    {
        $locations = Lokasi::with('shifts')->get();
        return view('admin.fitur-1', compact('locations'));
    }

    public function storeLocation(Request $request)
    {
        $validated = $request->validate([
            'nama_lokasi'    => 'required|string|max:100',
            'alamat_lokasi'  => 'required|string',
            'foto'           => 'nullable|image|mimes:jpg,jpeg,png|max:2048'
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('locations', 'public');
        }

        Lokasi::create($validated);

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Menambahkan lokasi: ' . $validated['nama_lokasi'],
            'severity' => 'info'
        ]);

        return redirect()->back()->with('success', 'Location added successfully');
    }

    public function storeShift(Request $request)
    {
        $validated = $request->validate([
            'lokasi_id'     => 'required|exists:lokasis,id',
            'nama_shift'    => 'required|string|max:50',
            'mulai_shift'   => 'required',
            'selesai_shift' => 'required',
        ]);

        Shift::create($validated);

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Menambahkan shift: ' . $validated['nama_shift'],
            'severity' => 'info'
        ]);
        
        return redirect()->back()->with('success', 'Shift added successfully');
    }

    public function updateShift(Request $request, $id)
    {
        $request->validate([
            'nama_shift' => 'required|string|max:50',
            'mulai_shift' => 'required',
            'selesai_shift' => 'required',
        ]);

        $shift = Shift::findOrFail($id);
        $shift->update([
            'nama_shift' => $request->nama_shift,
            'mulai_shift' => $request->mulai_shift,
            'selesai_shift' => $request->selesai_shift,
        ]);

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Mengedit shift: ' . $request->nama_shift,
            'severity' => 'info'
        ]);

        return redirect()->back()->with('success', 'Shift updated successfully');
    }


    public function toggleStatus($id)
    {
        $lokasi = Lokasi::findOrFail($id);
        $lokasi->is_active = !$lokasi->is_active;
        $lokasi->save();

        // Tambah catatan recent activity
        $statusText = $lokasi->is_active ? 'Activate' : 'Inactivate';
        RecentActivity::create([
            'user_id' => Auth::id(),
            'description' => $statusText . ' lokasi: ' . $lokasi->nama_lokasi,
            'severity' => $lokasi->is_active ? 'info' : 'warning'
        ]);

        return redirect()->back()->with('success', 'Status lokasi berhasil diperbarui.');
    }
}
