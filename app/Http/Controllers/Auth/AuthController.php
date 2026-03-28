<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        $identifier = trim((string) $validated['login']);
        $password = (string) $validated['password'];
        $hasUsernameColumn = Schema::hasColumn('users', 'username');

        $candidate = User::query()
            ->where(function ($query) use ($identifier, $hasUsernameColumn) {
                $query->where('email', $identifier);

                if ($hasUsernameColumn) {
                    $query->orWhere('username', $identifier);
                }
            })
            ->first();

        if (! $candidate || ! Hash::check($password, $candidate->password)) {
            return back()
                ->withErrors(['login' => 'Invalid username/email or password.'])
                ->onlyInput('login');
        }

        Auth::login($candidate, $remember);

        $request->session()->regenerate();

        return $this->redirectFor($candidate);
    }

    public function adminLogin(Request $request)
    {
        $validated = $request->validate([
            'admin_username' => ['required', 'string'],
            'admin_password' => ['required'],
        ]);

        $remember = $request->boolean('remember');

        $username = trim((string) $validated['admin_username']);
        $password = (string) $validated['admin_password'];

        if ($username !== 'admin' || $password !== 'admin123') {
            return back()
                ->withErrors(['admin_username' => 'Invalid admin credentials.'])
                ->onlyInput('admin_username');
        }

        $adminUser = User::query()->where('email', 'admin@local.test')->first();
        if (! $adminUser) {
            $adminUser = User::create([
                'name' => 'Admin',
                'email' => 'admin@local.test',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]);
        } else {
            if ($adminUser->role !== 'admin') {
                $adminUser->forceFill(['role' => 'admin'])->save();
            }
        }

        Auth::login($adminUser, $remember);
        $request->session()->regenerate();

        return $this->redirectFor($adminUser);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z\s,.\-]+$/'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:15', 'max:50', 'confirmed'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'qcid_number' => ['required', 'string', 'max:50'],
            'user_type' => ['nullable', 'string'],
            'employee_category' => ['nullable', 'string'],
            'course' => ['nullable', 'string'],
            'sex' => ['nullable', 'string'],
            'civil_status' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'date_issued' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:100'],
            'ocr_text' => ['required', 'string'],
        ]);

        $ocrText = $validated['ocr_text'];
        $ocrVerifier = app(\App\Services\QcIdOcrVerifier::class);
        if ($ocrVerifier->detectFakeId($ocrText)) {
            return back()->withErrors(['ocr_text' => 'The provided QC ID is invalid or a sample ID. Registration denied.'])->withInput();
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
        ]);

        // Create the initial verified registration record
        $user->qcidRegistration()->create([
            'full_name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['phone_number'],
            'qcid_number' => $validated['qcid_number'],
            'sex' => $validated['sex'],
            'civil_status' => $validated['civil_status'],
            'date_of_birth' => $validated['date_of_birth'],
            'date_issued' => $validated['date_issued'],
            'valid_until' => $validated['valid_until'],
            'address' => $validated['address'],
            'ocr_text' => $validated['ocr_text'],
            'verification_status' => 'verified', // Auto-verified since it came through the portal
            'submitted_at' => now(),
            'reviewed_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectFor($user);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectFor(?User $user)
    {
        // Intentionally ignore "intended" URLs so users can only land on allowed screens.
        return redirect()->route('dashboard');
    }
}