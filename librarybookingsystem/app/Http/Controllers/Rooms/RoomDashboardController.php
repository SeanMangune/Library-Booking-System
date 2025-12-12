<?php

namespace App\Http\Controllers\Rooms;
use App\Models\Rooms;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RoomDashboardController extends Controller
{
    public function index()
    {
        $rooms = Rooms::all();
        return view('rooms.roomdashboard', compact('rooms'));
    }
}
