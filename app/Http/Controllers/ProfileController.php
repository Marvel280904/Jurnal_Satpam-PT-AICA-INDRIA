<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();
        return view('profile.my-profile', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'nullable|string|min:6'
        ]);

        $user = Auth::user();

        if ($request->password) {
            $user = Auth::user();
            $user->password = Hash::make($request->password);
            $user->save();
        }

        return redirect()->route('profile.show')->with('success', 'Password updated.');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        $user = Auth::user();
        $fileName = time() . '_' . $request->foto->getClientOriginalName();
        $path = $request->foto->storeAs('profile_pictures', $fileName, 'public');

        $user->foto = $path;
        $user->save();

        return redirect()->route('profile.show')->with('success', 'Profile photo updated.');
    }
}
