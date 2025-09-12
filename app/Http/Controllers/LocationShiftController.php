<?php
namespace App\Http\Controllers;

use App\Models\Lokasi;
use App\Models\Shift;
use Illuminate\Http\Request;
use App\Models\RecentActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;


class LocationShiftController extends Controller
{
    public function index()
    {
        $locations = Lokasi::all(); 
        $shifts = Shift::all();     

        return view('admin.fitur-1', compact('locations', 'shifts'));
    }

    public function storeLocation(Request $request)
    {
        $rules = [
            'nama_lokasi'   => 'required|string|max:100|unique:lokasis,nama_lokasi',
            'alamat_lokasi' => 'required|string',
            'foto'          => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ];

        $messages = [
            'nama_lokasi.unique' => 'Lokasi sudah ada',
            'nama_lokasi.required' => 'Nama lokasi wajib diisi',
            'alamat_lokasi.required' => 'Alamat lokasi wajib diisi',
            'foto.required' => 'Foto wajib diupload',
            'foto.image' => 'File harus berupa gambar',
            'foto.mimes' => 'Format foto harus jpg/jpeg/png',
            'foto.max' => 'Ukuran foto maksimal 2MB',
            'foto.uploaded' => 'Ukuran file terlalu besar atau gagal diupload',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        if ($request->hasFile('foto')) {
            $originalName = $request->file('foto')->getClientOriginalName();
            $path = $request->file('foto')->storeAs('locations', $originalName, 'public');
            $validated['foto'] = $path;
        }

        Lokasi::create($validated);

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Menambahkan lokasi: ' . $validated['nama_lokasi'],
            'severity' => 'info'
        ]);

        session()->flash('success', 'Berhasil menambahkan lokasi.');
        return response()->json(['success' => true, 'redirect_url' => route('location.shift.index')]);
    }

    public function updateLocation(Request $request, $id)
    {
        // --- ambil model dulu (penting!) ---
        $location = Lokasi::findOrFail($id);

        $rules = [
            'nama_lokasi'   => ['required','string','max:100', Rule::unique('lokasis','nama_lokasi')->ignore($location->id)],
            'alamat_lokasi' => 'required|string',
            'foto'          => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        $messages = [
            'nama_lokasi.unique' => 'Lokasi sudah ada',
            'nama_lokasi.required' => 'Nama lokasi wajib diisi',
            'alamat_lokasi.required' => 'Alamat lokasi wajib diisi',
            'foto.image' => 'File harus berupa gambar',
            'foto.mimes' => 'Format foto harus jpg/jpeg/png',
            'foto.max' => 'Ukuran foto maksimal 2MB',
            'foto.uploaded' => 'Ukuran file terlalu besar / gagal diupload',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        if ($request->hasFile('foto')) {
            $originalName = $request->file('foto')->getClientOriginalName();
            $validated['foto'] = $request->file('foto')->storeAs('locations', $originalName, 'public');
        }

        $location->update($validated);

        RecentActivity::create([
            'user_id'     => auth()->id(),
            'description' => 'Mengedit lokasi: ' . $request->nama_lokasi,
            'severity'    => 'info',
        ]);

        session()->flash('success', 'Berhasil mengupdate lokasi.');
        return response()->json(['success' => true, 'redirect_url' => route('location.shift.index')]);
    }

    public function storeShift(Request $request)
    {
        $rules = [
            'nama_shift'    => 'required|string|max:50|unique:shifts,nama_shift',
            'mulai_shift'   => 'required',
            'selesai_shift' => 'required|after:mulai_shift',
        ];

        $messages = [
            'nama_shift.unique' => 'Shift sudah ada',
            'nama_shift.required' => 'Nama shift wajib diisi',
            'mulai_shift.required' => 'Jam mulai wajib diisi',
            'selesai_shift.required' => 'Jam selesai wajib diisi',
            'selesai_shift.after' => 'Jam selesai harus setelah jam mulai',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $exactTimeExists = Shift::where('mulai_shift', $validated['mulai_shift'])
                                ->where('selesai_shift', $validated['selesai_shift'])
                                ->exists();

        if ($exactTimeExists) {
            return response()->json(['errors' => ['mulai_shift' => ['Sudah terdapat shift dengan jam tersebut!']]], 422);
        }

        Shift::create($validated);

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Menambahkan shift: ' . $validated['nama_shift'],
            'severity' => 'info'
        ]);

        session()->flash('success', 'Berhasil menambahkan shift.');
        return response()->json(['success' => true, 'redirect_url' => route('location.shift.index')]);
    }

    public function updateShift(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $rules = [
            'nama_shift' => ['required','string','max:50', Rule::unique('shifts','nama_shift')->ignore($shift->id)],
            'mulai_shift'   => 'required',
            'selesai_shift' => 'required|after:mulai_shift',
        ];

        $messages = [
            'nama_shift.unique' => 'Shift sudah ada',
            'nama_shift.required' => 'Nama shift wajib diisi',
            'mulai_shift.required' => 'Jam mulai wajib diisi',
            'selesai_shift.required' => 'Jam selesai wajib diisi',
            'selesai_shift.after' => 'Jam selesai harus setelah jam mulai',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $exactTimeExists = Shift::where('id', '!=', $id)
                                ->where('mulai_shift', $validated['mulai_shift'])
                                ->where('selesai_shift', $validated['selesai_shift'])
                                ->exists();

        if ($exactTimeExists) {
            return response()->json(['errors' => ['mulai_shift' => ['Sudah terdapat shift dengan jam tersebut!']]], 422);
        }

        $shift->update($validated);

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Mengedit shift: ' . $request->nama_shift,
            'severity' => 'info'
        ]);

        session()->flash('success', 'Berhasil mengupdate shift.');
        return response()->json(['success' => true, 'redirect_url' => route('location.shift.index')]);
    }

    public function toggleStatusLoc($id)
    {
        $lokasi = Lokasi::findOrFail($id);
        $lokasi->is_active = !$lokasi->is_active;
        $lokasi->save();

        $statusText = $lokasi->is_active ? 'meng-activate' : 'meng-inactivate';

        RecentActivity::create([
            'user_id' => Auth::id(),
            'description' => ucfirst($statusText).' lokasi: '.$lokasi->nama_lokasi,
            'severity' => $lokasi->is_active ? 'info' : 'warning'
        ]);

        return back()
            ->with('flash_type', $lokasi->is_active ? 'success' : 'warning')
            ->with('success', 'Berhasil '.$statusText.' lokasi.');
    }

    public function toggleStatusShift($id)
    {
        $shift  = Shift::findOrFail($id);

        $shift->is_active = !$shift->is_active;
        $shift->save();

        $statusText = $shift->is_active ? 'meng-activate' : 'meng-inactivate';

        RecentActivity::create([
            'user_id' => Auth::id(),
            'description' => ucfirst($statusText).' shift: '.$shift->nama_shift,
            'severity' => $shift->is_active ? 'info' : 'warning'
        ]);

        return back()
            ->with('flash_type', $shift->is_active ? 'success' : 'warning')
            ->with('success', 'Berhasil '.$statusText.' shift.');
    }

}
