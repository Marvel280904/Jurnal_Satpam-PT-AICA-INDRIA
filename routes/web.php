<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Models\RecentActivity;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\LocationShiftController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PreventBackHistory;
use App\Http\Controllers\JurnalSatpamController;
use App\Http\Controllers\LogHistoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;



// route login
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/', fn() => redirect()->route('login')); // redirect root ke login
});

// route setelah login
Route::middleware('auth', 'prevent-back-history')->group(function () {

    // Universal dashboard route, redirect by role
    Route::get('/dashboard', function () {
        $user = Auth::user();

        if ($user->role === 'Admin') {
            return redirect()->route('dashboard.admin');
        } elseif ($user->role === 'Satpam') {
            return redirect()->route('dashboard.satpam');
        } elseif ($user->role === 'Kepala Satpam') {
            return redirect()->route('dashboard.kepala');
        }

        abort(403, 'Role tidak dikenali.');
    })->name('dashboard');

    // route dashboard admin
    Route::get('/dashboard-admin', [AdminDashboardController::class, 'index'])->name('dashboard.admin');

    // route my profile
    Route::get('/my-profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/my-profile/update-password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::post('/my-profile/update-photo', [ProfileController::class, 'updatePhoto'])->name('profile.updatePhoto');

    //route fitur lokasi & shift
    Route::get('/location-shift', [LocationShiftController::class, 'index'])->name('location.shift.index');
    Route::post('/location', [LocationShiftController::class, 'storeLocation'])->name('location.store');
    Route::put('/location/{id}', [LocationShiftController::class, 'updateLocation'])->name('location.update');
    Route::post('/location/{id}/toggle-status', [LocationShiftController::class, 'toggleStatusLoc'])->name('location.toggleStatus');
    Route::post('/shift', [LocationShiftController::class, 'storeShift'])->name('shift.store');
    Route::put('/shift/{id}', [LocationShiftController::class, 'updateShift'])->name('shift.update');
    Route::post('/shift/{id}/toggle-status', [LocationShiftController::class, 'toggleStatusShift'])->name('shift.toggleStatus');


    // route fitur user & role
    Route::get('/user-role', [UserController::class, 'index'])->name('user.index');
    Route::post('/user-role', [UserController::class, 'store'])->name('user.store');
    Route::put('/user-role/{id}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user-role/{id}', [UserController::class, 'destroy'])->name('user.destroy');

    // route fitur system log
    Route::get('/system-logs', [SystemLogController::class, 'index'])->name('system.logs');

    // route dashboard satpam
    Route::get('/dashboard-satpam', [DashboardController::class, 'index'])->name('dashboard.satpam');
    // route dashboard kepala satpam
    Route::get('/dashboard-kepala', [DashboardController::class, 'index'])->name('dashboard.kepala');

    // route journal submission
    Route::get('/journal-submission', [JurnalSatpamController::class, 'create'])->name('jurnal.submission');
    Route::post('/journal-submission', [JurnalSatpamController::class, 'store'])->name('jurnal.store');
    Route::get('/shifts/by-location/{id}', [JurnalSatpamController::class, 'getByLocation']);
    Route::get('/jurnal/edit/{id}', [JurnalSatpamController::class, 'edit'])->name('jurnal.edit');
    Route::delete('/jurnal/delete/{id}', [JurnalSatpamController::class, 'destroy'])->name('jurnal.destroy');
    Route::put('/jurnal/{id}/update', [JurnalSatpamController::class, 'update'])->name('jurnal.update');
    Route::post('/jurnal/{id}/approve', [JurnalSatpamController::class, 'updateApproval'])->name('jurnal.approve');

    // route log history
    Route::get('/log-history', [LogHistoryController::class, 'index'])->name('log.history');
    Route::post('/jurnal/{id}/status', [LogHistoryController::class, 'updateStatus'])->name('jurnal.updateStatus');
    Route::get('/log-history/download/{id}', [LogHistoryController::class, 'downloadPDF'])->name('log-history.download');

    // route guard data
    // Route::get('/guard-data', [GuardController::class, 'index'])->name('guard.data');
    // Route::post('/guard-data/update/{id}', [GuardController::class, 'update'])->name('guard.update');
    // Route::get('/guard-data/jadwal/check', [GuardController::class, 'checkJadwal'])->name('guard.jadwal.check');
    // Route::post('/guard-data/jadwal/store', [GuardController::class, 'storeJadwal'])->name('guard.jadwal.store');

    // Route untuk Beta Mode (hanya admin)
    Route::post('/beta-mode/toggle', [SettingsController::class, 'toggleBetaMode'])->name('beta-mode.toggle');
    Route::get('/beta-mode/status', [SettingsController::class, 'getBetaMode'])->name('beta-mode.status');
});

// route logout
Route::post('/logout', function (Request $request) {
    // Simpan aktivitas logout ke recent_activities
    RecentActivity::create([
        'user_id' => Auth::id(),
        'description' => 'Logout dari sistem',
        'severity' => 'info'
    ]);

    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->name('logout');

Route::get('sso-login', [LoginController::class, 'ssoLogin']);
