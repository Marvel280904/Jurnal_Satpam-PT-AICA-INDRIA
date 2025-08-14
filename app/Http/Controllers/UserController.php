<?php

namespace App\Http\Controllers;

use App\Models\Satpam;
use App\Models\RecentActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // Tampilkan halaman utama user & role management
    public function index()
    {
        $users = Satpam::where('role', '!=', 'Admin')->get();
        return view('admin.fitur-2', compact('users'));
    }

    // Store user baru
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:satpams',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
        ]);

        Satpam::create([
            'nama' => $validated['nama'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Menambah User: ' . $validated['nama'],
            'severity' => 'info'
        ]);

        return redirect()->route('user.index');
    }

    // update/edit user
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:satpams,username,' . $id,
            'password' => 'nullable|string|min:6', // ✔️ buat nullable
            'role' => 'required|string',
        ]);

        $user = Satpam::findOrFail($id);

        // Update field dasar
        $user->nama = $validated['nama'];
        $user->username = $validated['username'];
        $user->role = $validated['role'];

        // ✔️ Hanya update password jika diisi
        if (!empty($validated['password'])) {
            $user->password = \Hash::make($validated['password']);
        }

        $user->save();

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Edit Data User: ' . $validated['nama'],
            'severity' => 'info'
        ]);

        return redirect()->route('user.index');
    }


    // Hapus user
    public function destroy($id)
    {
        $user = Satpam::findOrFail($id);
        $userName = $user->nama;
        $user->delete();

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Menghapus User: ' . $userName,
            'severity' => 'warning'
        ]);

        return redirect()->back()->with('success', 'User berhasil dihapus.');
    }
}
