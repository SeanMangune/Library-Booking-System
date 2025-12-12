<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Rooms\RoomDashboardController;

Route::get('/', [RoomDashboardController::class, 'index']);