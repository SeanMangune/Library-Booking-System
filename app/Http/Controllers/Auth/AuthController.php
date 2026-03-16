<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\QcIdRegistration;
use App\Models\User;
use App\Services\QcIdOcrVerifier;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

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

        if (! $candidate->isStaff() && ! $candidate->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

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

    public function register(Request $request, QcIdOcrVerifier $verifier)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['required', 'string', 'max:30'],
            'user_type' => ['required', 'in:student,employee,alumni'],
            'employee_category' => ['nullable', 'required_if:user_type,employee', 'in:professor,academic_staff,administrative_staff,it_personnel,registrar_personnel,guidance_personnel,security_personnel,maintenance_personnel,other'],
            'course' => ['nullable', 'required_if:user_type,student', 'in:BSIT,BSIE,BSENT,BSCS,BSCPE,BSED,BEED,BSOA,BSA,BSBA,OTHER'],
            'qcid_number' => ['required', 'string', 'max:50'],
            'sex' => ['nullable', 'string', 'max:20'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'date_issued' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'ocr_text' => ['required', 'string', 'min:20', 'max:12000'],
            'qcid_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:25600'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $verification = $verifier->verify($validated['ocr_text'], $validated['name']);
        if (! $verification['is_valid']) {
            $message = ! empty($verification['rejected_id_type'])
                ? "This appears to be a {$verification['rejected_id_type']}. Only a valid Quezon City Citizen ID is accepted."
                : 'Only a valid Quezon City Citizen ID is accepted. Please upload a clearer QC ID image.';

            return back()
                ->withInput()
                ->withErrors([
                    'qcid_image' => $message,
                ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'course' => $validated['user_type'] === 'student' ? ($validated['course'] ?? null) : null,
            'employee_category' => $validated['user_type'] === 'employee' ? ($validated['employee_category'] ?? null) : null,
            'password' => Hash::make($validated['password']),
            'role' => User::ROLE_USER,
            'user_type' => $validated['user_type'],
        ]);

        $qcidImagePath = $request->file('qcid_image')->store('qcid-registrations/' . $user->id, 'public');

        $verifiedQcidNumber = $this->normalizeQcidNumber($verification['id_number'] ?? null);
        $verifiedAddress = $this->normalizeAddress($verification['address'] ?? null);
        $verifiedDob = $this->normalizeDateForDatabase($verification['date_of_birth'] ?? null);
        $verifiedIssued = $this->normalizeDateForDatabase($verification['date_issued'] ?? null);
        $verifiedValidUntil = $this->normalizeDateForDatabase($verification['valid_until'] ?? null);

        QcIdRegistration::query()->create([
            'user_id' => $user->id,
            'full_name' => $validated['name'],
            'email' => $validated['email'],
            'contact_number' => $validated['phone_number'],
            'qcid_number' => $this->normalizeQcidNumber($validated['qcid_number']) ?: $verifiedQcidNumber,
            'sex' => $validated['sex'] ?: ($verification['sex'] ?? null),
            'civil_status' => $validated['civil_status'] ?: ($verification['civil_status'] ?? null),
            'date_of_birth' => ($validated['date_of_birth'] ?? '') !== ''
                ? $this->normalizeDateForDatabase($validated['date_of_birth'])
                : $verifiedDob,
            'date_issued' => ($validated['date_issued'] ?? '') !== ''
                ? $this->normalizeDateForDatabase($validated['date_issued'])
                : $verifiedIssued,
            'valid_until' => ($validated['valid_until'] ?? '') !== ''
                ? $this->normalizeDateForDatabase($validated['valid_until'])
                : $verifiedValidUntil,
            'address' => ($validated['address'] ?? '') !== ''
                ? $this->normalizeAddress($validated['address'])
                : $verifiedAddress,
            'ocr_text' => $validated['ocr_text'],
            'verification_status' => 'pending',
            'qcid_image_path' => $qcidImagePath,
            'submitted_at' => now(),
            'verified_data' => $verification,
        ]);

        $user->sendEmailVerificationNotification();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('verification.notice');
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

    private function normalizeDateForDatabase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('.', '/', $value);

        foreach (['Y-m-d', 'Y/m/d', 'm/d/Y', 'd/m/Y', 'Ymd'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Try next known format.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeQcidNumber(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 13) {
            $digits = '0' . $digits;
        }

        if (strlen($digits) !== 14) {
            return null;
        }

        return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 8);
    }

    private function normalizeAddress(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtoupper(trim($value), 'UTF-8');
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return $value !== '' ? $value : null;
    }
}
