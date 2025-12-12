<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Rooms\RoomDashboardController;
use App\Http\Controllers\Rooms\RoomController;
use App\Http\Controllers\Rooms\BookingController;
use App\Http\Controllers\Rooms\CalendarController;

Route::redirect('/', '/rooms');

// Dashboard
Route::get('/rooms', [RoomDashboardController::class, 'index'])->name('dashboard');

// Room Management
Route::prefix('rooms')->group(function () {
    Route::get('/manage', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('/manage', [RoomController::class, 'store'])->name('rooms.store');
    Route::get('/manage/{room}', [RoomController::class, 'show'])->name('rooms.show');
    Route::put('/manage/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('/manage/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
    
    // Reservations
    Route::get('/room-reservations', [BookingController::class, 'index'])->name('reservations.index');
    Route::post('/room-reservations', [BookingController::class, 'store'])->name('reservations.store');
    Route::get('/room-reservations/{booking}', [BookingController::class, 'show'])->name('reservations.show');
    Route::put('/room-reservations/{booking}', [BookingController::class, 'update'])->name('reservations.update');
    Route::delete('/room-reservations/{booking}', [BookingController::class, 'destroy'])->name('reservations.destroy');
    Route::post('/room-reservations/{booking}/cancel', [BookingController::class, 'cancel'])->name('reservations.cancel');
    
    // Approvals
    Route::get('/approvals', [BookingController::class, 'approvals'])->name('approvals.index');
    Route::post('/approvals/{booking}/approve', [BookingController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{booking}/reject', [BookingController::class, 'reject'])->name('approvals.reject');
    
    // Calendar
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/events', [CalendarController::class, 'events'])->name('calendar.events');
    Route::get('/calendar/day', [CalendarController::class, 'dayEvents'])->name('calendar.day');
    Route::get('/calendar/month', [CalendarController::class, 'monthData'])->name('calendar.month');
});

// API endpoints for dashboard
Route::get('/api/users/search', function(\Illuminate\Http\Request $request) {
    return \App\Models\User::where('name', 'like', '%' . $request->q . '%')
        ->orWhere('email', 'like', '%' . $request->q . '%')
        ->limit(10)
        ->get(['id', 'name', 'email']);
})->name('users.search');

// Legacy routes for backward compatibility
Route::get('/dashboard', [RoomDashboardController::class, 'index']);
Route::post('/bookings/{booking}/approve', [BookingController::class, 'approve'])->name('bookings.approve');
Route::post('/bookings/{booking}/reject', [BookingController::class, 'reject'])->name('bookings.reject');
Route::get('/calendar-data', [CalendarController::class, 'monthData'])->name('calendar.data');

