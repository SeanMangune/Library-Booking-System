<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Rooms\RoomDashboardController;
use App\Http\Controllers\Rooms\RoomController;
use App\Http\Controllers\Rooms\BookingController;
use App\Http\Controllers\Rooms\CalendarController;
use App\Http\Controllers\BookingVerificationController;
use App\Http\Controllers\Bookings\BookingAccessController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    Route::get('/auth/google/redirect', [AuthController::class, 'googleRedirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [AuthController::class, 'googleCallback'])->name('google.callback');

    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Dashboard (user + admin)
Route::middleware('auth')->group(function () {
    Route::get('/rooms', [RoomDashboardController::class, 'index'])->name('dashboard');

    // Profile (user + admin)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Calendar (user + admin)
    Route::prefix('rooms')->group(function () {
        Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::get('/calendar/day', [CalendarController::class, 'dayEvents'])->name('calendar.day');
        Route::get('/calendar/month', [CalendarController::class, 'monthData'])->name('calendar.month');

        // Booking creation (used by dashboard + calendar modals)
        Route::post('/room-reservations', [BookingController::class, 'store'])->name('reservations.store');
    });

    Route::get('/calendar-data', [CalendarController::class, 'monthData'])->name('calendar.data');

    // QR image endpoint (used by frontend)
    Route::get('/bookings/qr/{token}', [BookingController::class, 'qrImage'])->name('bookings.qr');
});

// Admin-only access (keeps the existing system behavior)
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Settings
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');

    // Room Management
    Route::prefix('rooms')->group(function () {
        Route::get('/manage', [RoomController::class, 'index'])->name('rooms.index');
        Route::post('/manage', [RoomController::class, 'store'])->name('rooms.store');
        Route::get('/manage/{room}', [RoomController::class, 'show'])->name('rooms.show');
        Route::put('/manage/{room}', [RoomController::class, 'update'])->name('rooms.update');
        Route::delete('/manage/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

        // Reservations
        Route::get('/room-reservations', [BookingController::class, 'index'])->name('reservations.index');
        Route::get('/room-reservations/{booking}', [BookingController::class, 'show'])->name('reservations.show');
        Route::put('/room-reservations/{booking}', [BookingController::class, 'update'])->name('reservations.update');
        Route::delete('/room-reservations/{booking}', [BookingController::class, 'destroy'])->name('reservations.destroy');
        Route::post('/room-reservations/{booking}/cancel', [BookingController::class, 'cancel'])->name('reservations.cancel');

        // Approvals
        Route::get('/approvals', [BookingController::class, 'approvals'])->name('approvals.index');
        Route::post('/approvals/{booking}/approve', [BookingController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{booking}/reject', [BookingController::class, 'reject'])->name('approvals.reject');
    });

    // API endpoints for dashboard
    Route::get('/api/users/search', function(\Illuminate\Http\Request $request) {
        return \App\Models\User::where('name', 'like', '%' . $request->q . '%')
            ->orWhere('email', 'like', '%' . $request->q . '%')
            ->limit(10)
            ->get(['id', 'name', 'email']);
    })->name('users.search');

    // Legacy routes for backward compatibility
    Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
    Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
});

Route::get('/dashboard', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

// Public verification page for scanned QR tokens
Route::get('/verify', [BookingController::class, 'verify'])->name('bookings.verify');


