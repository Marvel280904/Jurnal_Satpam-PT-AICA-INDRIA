<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RecentActivity;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = RecentActivity::with('user')
            ->when($request->date, fn($query) =>
                $query->whereDate('created_at', $request->date)
            )
            ->when($request->search, fn($query) =>
                $query->whereHas('user', fn($q) =>
                    $q->where('nama', 'like', '%' . $request->search . '%')
                )->orWhere('description', 'like', '%' . $request->search . '%')
                ->orWhere('severity', 'like', '%' . $request->search . '%')
            )
            ->latest()
            ->get();

        return view('admin.system-log', compact('logs'));
    }
}
