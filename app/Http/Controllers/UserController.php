<?php

namespace App\Http\Controllers;

use App\Models\Satpam;
use App\Models\RecentActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // Tampilkan halaman utama user & role management
    public function index()
    {
        $users = Satpam::whereIn('role', ['Admin', 'Kepala Satpam', 'Satpam'])
            ->orderBy('role')
            ->orderBy('nama')
            ->get();

        return view('Admin.user-role', compact('users'));
    }

    // Store user baru
    public function store(Request $request)
    {
        $rules = [
            'nama'     => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:satpams,username',
            'password' => 'required|string|min:6',
            'role'     => 'required|string',
        ];

        $messages = [
            'username.unique'   => 'Username sudah ada',
            'nama.required'     => 'Nama wajib diisi',
            'username.required' => 'Username wajib diisi',
            'password.required' => 'Password wajib diisi',
            'password.min'      => 'Password minimal 6 karakter',
            'role.required'     => 'Role wajib dipilih',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        Satpam::create([
            'nama'     => $validated['nama'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
        ]);

        RecentActivity::create([
            'user_id'     => auth()->id(),
            'description' => 'Menambah User: ' . $validated['nama'],
            'severity'    => 'info'
        ]);

        session()->flash('success', 'User berhasil ditambahkan.');
        session()->flash('flash_type', 'success');

        return response()->json([
            'success'      => true,
            'redirect_url' => route('user.index') 
        ]);
    }

     // Edit/Update user
    public function update(Request $request, $id)
    {
        $user = Satpam::findOrFail($id);

        $rules = [
            'nama'     => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('satpams', 'username')->ignore($user->id),
            ],
            'password' => 'nullable|string|min:6',
            'role'     => 'required|string',
        ];

        $messages = [
            'username.unique'   => 'Username sudah ada',
            'nama.required'     => 'Nama wajib diisi',
            'username.required' => 'Username wajib diisi',
            'password.min'      => 'Password minimal 6 karakter',
            'role.required'     => 'Role wajib dipilih',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        $user->nama     = $validated['nama'];
        $user->username = $validated['username'];
        $user->role     = $validated['role'];

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        RecentActivity::create([
            'user_id'     => auth()->id(),
            'description' => 'Edit Data User: ' . $validated['nama'],
            'severity'    => 'info'
        ]);

        session()->flash('success', 'User berhasil diupdate.');
        session()->flash('flash_type', 'success');
        
        return response()->json([
            'success'      => true,
            'redirect_url' => route('user.index')
        ]);
    }

    // Hapus user
    public function destroy($id)
    {
        $user = Satpam::findOrFail($id);

        // ❗ Blokir penghapusan super user
        if (strtolower($user->username) === 'admin' || $user->nama === 'Administrator') {
            return redirect()->back()
                ->with('flash_type', 'error')
                ->with('success', 'User Administrator tidak boleh dihapus.');
        }

        $userName = $user->nama;
        $user->delete();

        RecentActivity::create([
            'user_id' => auth()->id(),
            'description' => 'Menghapus User: ' . $userName,
            'severity' => 'warning'
        ]);

        return redirect()->back()
            ->with('flash_type', 'success')
            ->with('success', 'User berhasil dihapus.');
    }
}
