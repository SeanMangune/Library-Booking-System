<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        // Refresh the token whenever the login screen is opened to reduce stale-form 419 errors.
        $request->session()->regenerateToken();

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

        $candidate = User::query()
            ->where(function ($query) use ($identifier) {
                $query->where('email', $identifier)
                    ->orWhere('username', $identifier);
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
        $request->merge([
            'login' => (string) $request->input('staff_username', $request->input('staff_email', '')),
            'password' => (string) $request->input('staff_password', ''),
        ]);

        return $this->login($request);
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_USER,
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

    public function googleRedirect()
    {
        if (! $this->googleConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google login is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env, then run php artisan config:clear.',
            ]);
        }

        return Socialite::driver('google')->redirect();
    }

    public function googleCallback(Request $request)
    {
        if (! $this->googleConfigured()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google login is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env, then run php artisan config:clear.',
            ]);
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            $email = $googleUser->getEmail();
            $providerId = $googleUser->getId();

            $user = User::query()
                ->where(function ($q) use ($email, $providerId) {
                    $q->where('provider', 'google')->where('provider_id', $providerId);

                    if (is_string($email) && $email !== '') {
                        $q->orWhere('email', $email);
                    }
                })
                ->first();

            if (! $user) {
                $user = User::create([
                    'name' => $googleUser->getName() ?: ($email ?: 'User'),
                    'email' => $email ?: (Str::uuid()->toString() . '@example.invalid'),
                    'username' => null,
                    'password' => Hash::make(Str::random(32)),
                    'role' => User::ROLE_USER,
                    'provider' => 'google',
                    'provider_id' => $providerId,
                ]);
            } else {
                if ($user->isStaff()) {
                    return redirect()->route('login')->withErrors([
                        'email' => 'Google login is only available for student accounts. Staff must use username and password in Staff Login.',
                    ]);
                }

                $user->forceFill([
                    'provider' => 'google',
                    'provider_id' => $providerId,
                ])->save();
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return $this->redirectFor($user);
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Google login failed. Please try again. If this persists, verify GOOGLE_CLIENT_ID/SECRET and that the redirect URI matches /auth/google/callback.',
            ]);
        }
    }

    private function googleConfigured(): bool
    {
        return (string) config('services.google.client_id') !== ''
            && (string) config('services.google.client_secret') !== '';
    }

    private function redirectFor(?User $user)
    {
        // Intentionally ignore "intended" URLs so users can only land on allowed screens.
        return redirect()->route('dashboard');
    }
}
