<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecentActivity;
use Illuminate\Support\Facades\Auth;

class RecentActivityController extends Controller
{
    /**
     * Menampilkan semua recent activity (opsional: untuk keperluan admin logs).
     */
    public function index()
    {
        if (auth()->user()->role !== 'Admin') {
                abort(403, 'Unauthorized');
            }

        $activities = RecentActivity::latest()->paginate(10);
        return view('admin.recent_activity.index', compact('activities'));
    }
}