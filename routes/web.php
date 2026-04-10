<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\QcIdRegistrationController;
use App\Http\Controllers\QcIdVerificationController;
use App\Http\Controllers\Rooms\RoomDashboardController;
use App\Http\Controllers\Rooms\RoomController;
use App\Http\Controllers\Rooms\BookingController;
use App\Http\Controllers\Rooms\CalendarController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/download-shortcut', function () {
    $filePath = public_path('SmartSpace.exe');
    if (!file_exists($filePath)) {
        abort(404);
    }
    return response()->download($filePath, 'SmartSpace.exe');
})->name('download.shortcut');

Route::get('/pwa', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : view('pwa.landing');
})->name('pwa.landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1') // 5 attempts per minute
        ->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
    Route::post('/register/send-otp', [AuthController::class, 'sendRegistrationOtp'])->name('register.send-otp');
    Route::post('/register/verify-otp', [AuthController::class, 'verifyRegistrationOtp'])->name('register.verify-otp');
    Route::post('/signup/qc-id/verify', QcIdVerificationController::class)->name('signup.qcid.verify');

    // Password Reset via OTP
    Route::get('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordOTPController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [\App\Http\Controllers\Auth\ForgotPasswordOTPController::class, 'sendOtpCode'])->name('password.email');
    Route::get('/verify-otp', [\App\Http\Controllers\Auth\ForgotPasswordOTPController::class, 'showVerifyForm'])->name('password.verify.form');
    Route::post('/verify-otp', [\App\Http\Controllers\Auth\ForgotPasswordOTPController::class, 'verifyOtp'])->name('password.verify.post');


    Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class])
    ->name('logout');

// Dashboard (user + admin)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [RoomDashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/statuses', [RoomDashboardController::class, 'statuses'])->name('rooms.statuses');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

    Route::get('/qcid-registration', [QcIdRegistrationController::class, 'show'])->name('qcid.registration.show');
    Route::post('/qcid-registration', [QcIdRegistrationController::class, 'store'])->name('qcid.registration.store');

    // Profile (user + admin)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Calendar (user + admin)
    Route::prefix('calendar-per-room')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('calendar.index');
        Route::get('/events', [CalendarController::class, 'events'])->name('calendar.events');
        Route::get('/day', [CalendarController::class, 'dayEvents'])->name('calendar.day');
        Route::get('/month', [CalendarController::class, 'monthData'])->name('calendar.month');
        Route::get('/availability', [BookingController::class, 'availability'])->name('calendar.availability');
        Route::get('/users/search', [BookingController::class, 'searchUsers'])->name('rooms.users.search');
        Route::post('/qc-id/verify', QcIdVerificationController::class)->name('qcid.verify');
    });

    // Reservations
    Route::get('/my-reservations', function (Request $request) {
        if ($request->user()?->isAdmin() || $request->user()?->isSuperAdmin()) {
            return redirect()->route('reservations.admin', $request->query());
        }

        return app(BookingController::class)->index($request);
    })->name('reservations.user');

    Route::get('/reservations', function (Request $request) {
        $targetRoute = ($request->user()?->isAdmin() || $request->user()?->isSuperAdmin())
            ? 'reservations.admin'
            : 'reservations.user';

        return redirect()->route($targetRoute, $request->query());
    })->name('reservations.index');

    Route::post('/reservations/{booking}/cancel', [BookingController::class, 'cancel'])->name('reservations.cancel');

    // Booking creation (used by dashboard + calendar modals)
    Route::post('/reservations', [BookingController::class, 'store'])->name('reservations.store');

    Route::get('/calendar-data', [CalendarController::class, 'monthData'])->name('calendar.data');

});

// Public QR image endpoint used in approval emails and booking modals
Route::get('/bookings/qr/{token}', [BookingController::class, 'qrImage'])->name('bookings.qr');

// Admin-only access (keeps the existing system behavior)
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Settings
    Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');

    Route::post('/settings/staff', [SettingsController::class, 'storeStaff'])->name('settings.staff.store');
    Route::put('/settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');

    // Reports
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

    // Room Management
    Route::get('/manage-rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('/manage-rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/manage-rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
    Route::get('/manage-rooms/{room}/affected-bookings', [RoomController::class, 'affectedBookingsPreview'])->name('rooms.affected-bookings');
    Route::put('/manage-rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/manage-rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');

    // Reservations
    Route::get('/all-reservations', [BookingController::class, 'index'])->name('reservations.admin');
    Route::get('/all-reservations/{booking}', [BookingController::class, 'show'])->name('reservations.show');
    Route::put('/all-reservations/{booking}', [BookingController::class, 'update'])->name('reservations.update');
    Route::delete('/all-reservations/{booking}', [BookingController::class, 'destroy'])->name('reservations.destroy');

    // Approvals
    Route::get('/approvals', [BookingController::class, 'approvals'])->name('approvals.index');
    Route::post('/approvals/{booking}/approve', [BookingController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{booking}/reject', [BookingController::class, 'reject'])->name('approvals.reject');

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

Route::middleware('auth')->get('/rooms', function () {
    return redirect()->route('dashboard');
});

Route::middleware('auth')->get('/rooms/calendar', function (Request $request) {
    return redirect()->route('calendar.index', $request->query());
});

Route::middleware('auth')->get('/rooms/room-reservations', function (Request $request) {
    return redirect()->route('reservations.index', $request->query());
});

Route::middleware('auth')->get('/rooms/manage', function (Request $request) {
    return redirect()->route('rooms.index', $request->query());
});

Route::middleware('auth')->get('/rooms/approvals', function (Request $request) {
    return redirect()->route('approvals.index', $request->query());
});

// Public verification page for scanned QR tokens
Route::get('/verify', [BookingController::class, 'verify'])->name('bookings.verify');


