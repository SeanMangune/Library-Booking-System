<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
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
        $settings = is_array($user->settings) ? $user->settings : [];
        $staffUsers = $user->isSuperAdmin()
            ? User::query()
                ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_LIBRARIAN])
                ->orderByRaw("case when role = 'admin' then 0 else 1 end")
                ->orderBy('name')
                ->get()
            : collect();

        return view('settings.index', compact('user', 'settings', 'staffUsers'));
    }

    public function updatePreferences(Request $request)
    {
        $user = $this->actingUser($request);

        $validated = $request->validate([
            'default_calendar_view' => ['required', 'in:month,week,day'],
            'time_format' => ['required', 'in:12,24'],
            'compact_mode' => ['nullable', 'boolean'],
            'pending_approval_notifications' => ['nullable', 'boolean'],
        ]);

        $current = is_array($user->settings) ? $user->settings : [];
        $user->settings = array_merge($current, [
            'default_calendar_view' => $validated['default_calendar_view'],
            'time_format' => $validated['time_format'],
            'compact_mode' => (bool) ($validated['compact_mode'] ?? false),
            'pending_approval_notifications' => (bool) ($validated['pending_approval_notifications'] ?? false),
        ]);
        $user->save();

        return back()->with('status', 'Settings updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = $this->actingUser($request);

        $validated = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'The current password is incorrect.',
            ]);
        }

        $user->password = $validated['password'];
        $user->save();

        return back()->with('status', 'Password updated successfully.');
    }

    public function storeStaff(Request $request)
    {
        $actingUser = $this->actingUser($request);

        abort_unless($actingUser->isSuperAdmin(), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'in:' . User::ROLE_ADMIN . ',' . User::ROLE_LIBRARIAN],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => $validated['password'],
        ]);

        return back()->with('status', ucfirst($validated['role']) . ' account created successfully.');
    }
}