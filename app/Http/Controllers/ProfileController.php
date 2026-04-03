<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    private function actingUser(Request $request): User
    {
        $user = $request->user();
        if ($user instanceof User) {
            return $user;
        }

        return User::query()->orderBy('id')->firstOrFail();
    }

    public function edit(Request $request)
    {
        $user = $this->actingUser($request);

        $bookingQuery = Booking::query()
            ->where('user_id', $user->id)
            ->orWhere('user_email', $user->email);

        $stats = [
            'rooms' => Room::query()->visible()->count(),
            'bookings_total' => (clone $bookingQuery)->count(),
            'bookings_pending' => (clone $bookingQuery)->where('status', 'pending')->count(),
            'bookings_approved' => (clone $bookingQuery)->where('status', 'approved')->count(),
            'bookings_rejected' => (clone $bookingQuery)->where('status', 'rejected')->count(),
        ];

        return view('profile.edit', compact('user', 'stats'));
    }

    public function update(Request $request)
    {
        $user = $this->actingUser($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->update($validated);

        return back()->with('status', 'Profile updated successfully.');
    }
}