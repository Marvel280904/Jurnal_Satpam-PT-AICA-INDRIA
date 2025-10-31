<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Models\RecentActivity;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    // API untuk toggle beta mode (hanya bisa diakses oleh Admin)
    public function toggleBetaMode(Request $request)
    {
        // Hanya admin yang boleh mengubah beta mode
        if (Auth::user()->role !== 'Admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Admin yang dapat mengubah Beta Mode'
            ], 403);
        }

        $currentMode = Setting::getValue('beta_mode', '0');
        $newMode = $currentMode == '1' ? '0' : '1';
        
        Setting::setValue('beta_mode', $newMode);
        RecentActivity::create([
            'user_id' => Auth::id(),
            'description' => 'Beta Mode: ' . ($newMode == '1' ? 'Diaktifkan' : 'Dinonaktifkan'),
            'severity' => 'info'
        ]);

        $message = 'Beta Mode ' . ($newMode == '1' ? 'diaktifkan' : 'dinonaktifkan');
        
        return response()->json([
            'success' => true,
            'beta_mode' => $newMode,
            'message' => $message
        ]);
    }

    // API untuk mendapatkan status beta mode
    public function getBetaMode()
    {
        $betaMode = Setting::getValue('beta_mode', '0');
        
        return response()->json([
            'beta_mode' => $betaMode
        ]);
    }
}
