<?php

namespace App\Http\Controllers;

use App\Models\Satpam;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\RecentActivity;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $locations = Lokasi::all();
        $recentActivities = RecentActivity::orderBy('created_at', 'desc')->take(5)->get();

        return view('Admin.dashboard', [
            'totalUsers' => Satpam::where('role', '!=', 'Admin')
                                ->whereNotNull('role')
                                ->count(),
            'totalLocations' => Lokasi::count(),
            'totalShifts' => Shift::count(),
            'recentActivities' => $recentActivities,
            'locations' => $locations // ⬅ penting: kirim ke view
        ]);
    }

}

