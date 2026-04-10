<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\QcIdRegistration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Mail\RegistrationOtpMail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\InvalidStateException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    private const REGISTRATION_MAX_ATTEMPTS = 4;

    private const REGISTRATION_COOLDOWN_SECONDS = 600;

    private const REGISTRATION_MIN_AGE = 15;

    private ?array $usersTableColumns = null;
    private bool $usersTableColumnsResolved = false;
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

        // --- Account lockout check ---
        $lockoutKey = 'login_lockout:' . $request->ip() . ':' . strtolower($identifier);
        $attemptsKey = 'login_attempts:' . $request->ip() . ':' . strtolower($identifier);
        $lockoutSeconds = 300; // 5 minutes

        // Check if currently locked out
        $lockedUntil = Cache::get($lockoutKey);
        if ($lockedUntil && now()->timestamp < $lockedUntil) {
            $remainingSeconds = (int) ($lockedUntil - now()->timestamp);
            $errorMsg = 'Account locked due to too many failed attempts. Try again in ' . ceil($remainingSeconds / 60) . ' minute(s).';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'locked' => true,
                    'lockout_seconds' => $remainingSeconds,
                    'attempts_remaining' => 0,
                    'show_warning' => true,
                ], 429);
            }

            return back()
                ->withErrors([
                    'login' => $errorMsg,
                    'lockout_seconds' => $remainingSeconds,
                    'locked' => true,
                ])
                ->onlyInput('login');
        }

        $candidate = User::query()
            ->where(function ($query) use ($identifier, $hasUsernameColumn) {
                $query->where('email', $identifier);
                if ($hasUsernameColumn) {
                    $query->orWhere('username', $identifier);
                }
            })
            ->first();

        if (! $candidate || ! Hash::check($password, $candidate->password)) {
            // --- Increment failed attempts ---
            $attempts = (int) Cache::get($attemptsKey, 0) + 1;
            Cache::put($attemptsKey, $attempts, $lockoutSeconds + 60);

            $maxAttempts = 5;
            $warnAtAttempt = 3;
            $attemptsRemaining = max(0, $maxAttempts - $attempts);
            $showWarning = $attempts >= $warnAtAttempt;

            // Lockout after max attempts
            if ($attempts >= $maxAttempts) {
                Cache::put($lockoutKey, now()->timestamp + $lockoutSeconds, $lockoutSeconds);
                Cache::forget($attemptsKey);

                $errorMsg = 'Account locked due to too many failed attempts. Try again in 5 minutes.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $errorMsg,
                        'locked' => true,
                        'lockout_seconds' => $lockoutSeconds,
                        'attempts_remaining' => 0,
                        'show_warning' => true,
                    ], 429);
                }

                return back()
                    ->withErrors([
                        'login' => $errorMsg,
                        'lockout_seconds' => $lockoutSeconds,
                        'locked' => true,
                    ])
                    ->onlyInput('login');
            }

            // Warning after warn threshold
            $errorMsg = 'Invalid username/email or password.';
            if ($showWarning) {
                $errorMsg .= " Warning: {$attemptsRemaining} attempt(s) remaining before account lockout.";
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMsg,
                    'locked' => false,
                    'lockout_seconds' => 0,
                    'attempts_remaining' => $attemptsRemaining,
                    'show_warning' => $showWarning,
                    'total_attempts' => $attempts,
                ], 422);
            }

            return back()
                ->withErrors([
                    'login' => $errorMsg,
                    'attempts_remaining' => $attemptsRemaining,
                    'show_warning' => $showWarning,
                ])
                ->onlyInput('login');
        }

        // --- Successful login: clear attempts ---
        Cache::forget($attemptsKey);
        Cache::forget($lockoutKey);

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


        $username = trim((string) $validated['admin_username']);
        $password = (string) $validated['admin_password'];
        $remember = $request->boolean('remember');

        if ($username !== 'admin' || $password !== 'admin123') {
            return back()
                ->withErrors(['admin_username' => 'Invalid admin credentials.'])
                ->onlyInput('admin_username');
        }

        $adminUser = User::query()->where('email', 'admin@local.test')->first();
        if (! $adminUser) {
            $adminUser = User::create([
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@local.test',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'classification' => User::CLASSIFICATION_ADMIN,
            ]);
        } else {
            if ($adminUser->role !== 'admin') {
                $adminUser->forceFill([
                    'role' => 'admin',
                    'classification' => User::CLASSIFICATION_ADMIN,
                ])->save();
            } elseif ($adminUser->classification() !== User::CLASSIFICATION_ADMIN) {
                $adminUser->forceFill([
                    'classification' => User::CLASSIFICATION_ADMIN,
                ])->save();
            }
        }

        Auth::login($adminUser, $remember);
        $request->session()->regenerate();

        return $this->redirectFor($adminUser);
    }

    public function showRegister()
    {
        return view('auth.login', ['openSignupOnLoad' => true]);
    }

    /**
     * Send a 6-digit OTP to the user's email for registration verification.
     */
    public function sendRegistrationOtp(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if (! $this->looksLikeRealEmailAccount((string) $value)) {
                        $fail('Use a real email.');
                    }
                },
            ],
            'name'  => ['required', 'string', 'max:255'],
        ], [
            'email.unique' => 'This email is already registered. Please log in instead.',
            'email.email' => 'Use a real email.',
        ]);

        $email = $validated['email'];
        $name  = $validated['name'];

        // Rate limit: max 3 OTP sends per email per 15 minutes
        $rateLimitKey = 'reg_otp_rate:' . strtolower($email);
        $attempts = (int) Cache::get($rateLimitKey, 0);
        if ($attempts >= 3) {
            return response()->json([
                'success' => false,
                'message' => 'Too many verification code requests. Please wait a few minutes before trying again.',
            ], 429);
        }

        $otp = sprintf('%06d', random_int(100000, 999999));

        // Store OTP in cache keyed by email, expires in 15 minutes
        Cache::put('reg_otp:' . strtolower($email), Hash::make($otp), now()->addMinutes(15));
        Cache::put($rateLimitKey, $attempts + 1, now()->addMinutes(15));

        try {
            Mail::to($email)->queue(new RegistrationOtpMail($email, $name, $otp));
        } catch (\Throwable $e) {
            Log::warning('Failed to send registration OTP email.', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'A 6-digit verification code has been sent to your email.',
        ]);
    }

    /**
     * Verify the registration OTP and return a one-time token.
     */
    public function verifyRegistrationOtp(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    if (! $this->looksLikeRealEmailAccount((string) $value)) {
                        $fail('Use a real email.');
                    }
                },
            ],
            'otp'   => ['required', 'string', 'size:6'],
        ], [
            'email.email' => 'Use a real email.',
        ]);

        $email = strtolower($validated['email']);
        $cacheKey = 'reg_otp:' . $email;
        $hashedOtp = Cache::get($cacheKey);

        if (! $hashedOtp) {
            return response()->json([
                'success' => false,
                'message' => 'Verification code has expired. Please request a new one.',
            ], 422);
        }

        if (! Hash::check($validated['otp'], $hashedOtp)) {
            return response()->json([
                'success' => false,
                'message' => 'The verification code is incorrect. Please try again.',
            ], 422);
        }

        // OTP verified — generate a one-time token for registration
        $otpToken = Str::random(64);
        Cache::put('reg_otp_verified:' . $email, $otpToken, now()->addMinutes(30));
        Cache::forget($cacheKey);
        Cache::forget('reg_otp_rate:' . $email);

        return response()->json([
            'success'   => true,
            'message'   => 'Email verified successfully!',
            'otp_token' => $otpToken,
        ]);
    }

    public function register(Request $request)
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'username' => substr((string) preg_replace('/[^A-Za-z0-9_]/', '', (string) $request->input('username')), 0, 15),
            'qcid_number' => (string) preg_replace('/\D+/', '', (string) $request->input('qcid_number')),
            'qr_validated_id' => (($qr = (string) preg_replace('/\D+/', '', (string) $request->input('qr_validated_id'))) !== '' ? $qr : null),
            'sex' => strtoupper(trim((string) $request->input('sex'))),
            'campus' => trim((string) $request->input('campus')),
            'address' => $this->sanitizeAddressInput((string) $request->input('address')),
        ]);

        $registrationAttemptBucket = $this->registrationAttemptBucket($request, (string) $request->input('email'));
        $lockedSeconds = $this->registrationLockoutSecondsRemaining($registrationAttemptBucket);
        if ($lockedSeconds > 0) {
            return $this->registrationLockoutResponse($request, $lockedSeconds);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:50', 'regex:/^[\p{L}\s,.\-]+$/u'],
            'username' => ['required', 'string', 'max:15', 'alpha_dash', 'unique:users,username'],
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                function ($attribute, $value, $fail) {
                    if (! $this->looksLikeRealEmailAccount((string) $value)) {
                        $fail('Use a real email.');
                    }
                },
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:16',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*\d).{8,16}$/', // At least 1 capital, 1 number, 8-16 chars
            ],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'qcid_number' => ['required', 'string', 'size:14', 'regex:/^\d{14}$/'],
            'user_type' => ['required', 'string', 'in:student,employee,alumni'],
            'employee_category' => ['nullable', 'string', 'max:50', 'required_if:user_type,employee'],
            'course' => ['nullable', 'string', 'max:100', 'required_if:user_type,student'],
            'campus' => ['nullable', 'string', 'required_if:user_type,student', Rule::in(User::STUDENT_CAMPUSES)],
            'sex' => ['nullable', 'string', 'in:MALE,FEMALE,PREFER_NOT_TO_SAY'],
            'civil_status' => ['nullable', 'string'],
            'date_of_birth' => ['nullable', 'date'],
            'date_issued' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:180'],
            'ocr_text' => ['required', 'string'],
            'qr_validated_id' => ['nullable', 'string', 'size:14', 'regex:/^\d{14}$/'],
            'qcid_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:25600', 'required_without:qcid_temp_upload'],
            'qcid_temp_upload' => ['nullable', 'string', 'max:255'],
            'otp_token' => ['required', 'string'],
        ], [
            'password.regex' => 'Password must be at least 8 characters, contain at least one uppercase letter and one number.',
            'name.regex' => 'Name may contain letters (including ñ), spaces, comma, period, and hyphen only.',
            'username.max' => 'Username must not exceed 15 characters.',
            'email.email' => 'Use a real email.',
            'qcid_number.size' => 'QC ID number must be exactly 14 digits.',
            'qcid_number.regex' => 'QC ID number must contain digits only.',
            'sex.in' => 'Sex must be Male, Female, or Prefer not to say.',
            'campus.required_if' => 'Campus is required for student accounts.',
            'campus.in' => 'Please select a valid campus.',
            'otp_token.required' => 'Email verification is required. Please verify your email address first.',
            'qcid_image.required_without' => 'Please upload and verify your QC ID image before registration.',
        ]);

        if ($validator->fails()) {
            return $this->registrationFailureResponse(
                $request,
                $registrationAttemptBucket,
                $validator->errors()->toArray()
            );
        }

        $validated = $validator->validated();

        if (($validated['user_type'] ?? '') !== 'student') {
            $validated['campus'] = null;
        }

        // Verify OTP token from cache
        $otpTokenKey = 'reg_otp_verified:' . strtolower($validated['email']);
        $cachedToken = Cache::get($otpTokenKey);

        if (! $cachedToken || $cachedToken !== $validated['otp_token']) {
            return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                'email' => 'Email verification has expired or is invalid. Please verify your email again.',
            ]);
        }

        $normalizedTempPath = $this->normalizeTempPath((string) ($validated['qcid_temp_upload'] ?? ''));
        $scanSnapshot = null;
        if ($normalizedTempPath !== '') {
            $scanSnapshot = $this->getVerifiedScanSnapshot($request, $normalizedTempPath);

            if ($scanSnapshot === null) {
                return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                    'qcid_image' => 'Your QC ID verification session has expired. Please re-upload and verify your QC ID.',
                ]);
            }

            // Scanner-captured dates are final and cannot be manually overridden.
            $validated['date_of_birth'] = $scanSnapshot['date_of_birth'] ?? null;
            $validated['date_issued'] = $scanSnapshot['date_issued'] ?? null;
            $validated['valid_until'] = $scanSnapshot['valid_until'] ?? null;

            if (! empty($scanSnapshot['qcid_number'])) {
                $validated['qcid_number'] = (string) $scanSnapshot['qcid_number'];
            }

            if (! empty($scanSnapshot['address'])) {
                $validated['address'] = $this->sanitizeAddressInput((string) $scanSnapshot['address']);
            }
        }

        if ($dateOfBirthError = $this->registrationDateOfBirthError($validated['date_of_birth'] ?? null)) {
            return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                'date_of_birth' => $dateOfBirthError,
            ]);
        }

        $ocrText = $validated['ocr_text'];
        $ocrVerifier = app(\App\Services\QcIdOcrVerifier::class);
        if ($ocrVerifier->detectFakeId($ocrText)) {
            return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                'ocr_text' => 'The provided QC ID is invalid or a sample ID. Registration denied.',
            ]);
        }

        // === QR Cross-Validation Gate (Server-side) ===
        $qrValidatedId = trim((string) ($validated['qr_validated_id'] ?? ''));
        $enteredId = trim((string) ($validated['qcid_number'] ?? ''));

        if ($qrValidatedId !== '') {
            // Normalize both IDs for comparison (digits only)
            $qrClean = preg_replace('/\D+/', '', $qrValidatedId);
            $enteredClean = preg_replace('/\D+/', '', $enteredId);

            if ($qrClean !== $enteredClean) {
                return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                    'qcid_number' => "QC ID number mismatch! Your ID's QR code shows {$qrValidatedId}, but you entered {$enteredId}. The QC ID number must match what's on your physical card."
                ]);
            }
        }

        if ($this->isQcIdAlreadyInUse((string) ($validated['qcid_number'] ?? ''))) {
            return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                'qcid_number' => 'This QC ID is already in use with another account.',
            ]);
        }

        // Resolve QC ID image source: direct upload (desktop flow) or
        // previously verified temporary upload (mobile-safe flow).
        $imagePath = null;
        $copiedFromTemp = false;
        $tempPathForCleanup = null;

        if ($request->hasFile('qcid_image')) {
            $imagePath = $request->file('qcid_image')->store('qcid_images', 'public');
        } else {
            $tempPath = $normalizedTempPath;

            if ($tempPath !== '') {
                if (! str_starts_with($tempPath, 'qcid_scans_temp/')) {
                    return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                        'qcid_image' => 'The verified QC ID image token is invalid. Please re-upload and verify your QC ID.',
                    ]);
                }

                if (! Storage::disk('public')->exists($tempPath)) {
                    return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                        'qcid_image' => 'The verified QC ID image has expired. Please re-upload and verify your QC ID.',
                    ]);
                }

                $extension = pathinfo($tempPath, PATHINFO_EXTENSION) ?: 'jpg';
                $targetPath = 'qcid_images/' . (string) Str::uuid() . '.' . $extension;

                if (! Storage::disk('public')->copy($tempPath, $targetPath)) {
                    return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                        'qcid_image' => 'Unable to finalize your verified QC ID image. Please upload and verify again.',
                    ]);
                }

                $imagePath = $targetPath;
                $copiedFromTemp = true;
                $tempPathForCleanup = $tempPath;
            }
        }

        if ($imagePath === null) {
            return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                'qcid_image' => 'Please upload and verify your QC ID image before registration.',
            ]);
        }

        $classification = $this->classificationFromRegistration($validated);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'] ?? Str::slug($validated['name']) . '_' . Str::random(4),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'user',
                'classification' => $classification,
                'campus' => $validated['campus'] ?? null,
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
                'qcid_image_path' => $imagePath,
                'verification_status' => 'verified', // Auto-verified since it came through the portal
                'submitted_at' => now(),
                'reviewed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            if ($copiedFromTemp && $imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            return $this->registrationFailureResponse($request, $registrationAttemptBucket, [
                'email' => 'Unable to complete registration right now. Please try again.',
            ]);
        }

        if ($tempPathForCleanup !== null) {
            Storage::disk('public')->delete($tempPathForCleanup);
            $this->forgetVerifiedScanSnapshot($request, $tempPathForCleanup);
        }

        // Consume OTP token only after successful account creation.
        Cache::forget($otpTokenKey);
        $this->clearRegistrationAttemptState($registrationAttemptBucket);

        return redirect()->route('login')
            ->with('registration_success', 'Your account has been created successfully! Please log in with your username or email and password.')
            ->with('registered_username', $user->username);
    }

    private function looksLikeRealEmailAccount(string $email): bool
    {
        $email = strtolower(trim($email));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        [$local, $domain] = $parts;

        if ($local === '' || $domain === '') {
            return false;
        }

        if (strlen($local) > 64 || strlen($domain) > 255) {
            return false;
        }

        if (
            str_starts_with($local, '.')
            || str_ends_with($local, '.')
            || str_contains($local, '..')
            || str_starts_with($domain, '-')
            || str_ends_with($domain, '-')
            || str_starts_with($domain, '.')
            || str_ends_with($domain, '.')
            || str_contains($domain, '..')
        ) {
            return false;
        }

        if (preg_match('/^[a-z0-9._%+\-]+$/i', $local) !== 1) {
            return false;
        }

        if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,24}$/i', $domain) !== 1) {
            return false;
        }

        $letterCount = preg_match_all('/[a-z]/i', $local);
        if ($letterCount < 1) {
            return false;
        }

        if (preg_match('/([a-z0-9])\1{5,}/i', $local) === 1) {
            return false;
        }

        return true;
    }

    private function sanitizeAddressInput(string $address): string
    {
        $value = mb_strtoupper(trim($address), 'UTF-8');
        if ($value === '') {
            return '';
        }

        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        $value = preg_replace('/\b(?:Q\s*CITIZEN\s*CARD|CITIZEN\s*CARD|CITIZENCARD|QCITIZENCARD|KASAMA\s*KA\s*SA\s*PAG\s*-?\s*UNLAD|CARDHOLDER|REPUBLIC\s+OF\s+THE\s+PHILIPPINES|BLOOD\s*TYPE|TYPE\s*[ABO][\+\-]?|SINGLE|MARRIED|WIDOWED|DIVORCED|SEPARATED|DATE\s*ISSUED|VALID\s*UNTIL|DATE\s*(?:OF)?\s*BIRTH|CIVIL\s*STATUS|SEX)\b/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        if (preg_match('/^(.*?\bQUEZON\s*CITY\b)/u', $value, $cityMatch) === 1) {
            $value = (string) ($cityMatch[1] ?? $value);
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $value))));
        $locationPattern = '/\b(?:#?\d{1,4}|BLK|BLOCK|LOT|UNIT|BRGY|BARANGAY|SUBD|SUBDIVISION|ST(?:REET)?|ROAD|RD|AVE(?:NUE)?|EXT(?:ENSION)?|PUROK|SITIO|VILLAGE|PHASE|BAESA|BAGBAG|NOVALICHES|KINGSPOINT|FAIRVIEW|COMMONWEALTH|BATASAN|GULOD|TALIPAPA|PAYATAS|CUBAO|HOLY\s*SPIRIT|TANDANG\s*SORA|SAN\s*BARTOLOME|PASONG\s*TAMO|PASONG\s*PUTIK|PROJECT\s*[0-9]+)\b/u';

        $kept = [];
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            $hasLocationMarker = preg_match($locationPattern, $segment) === 1;
            $hasPersonalNoise = preg_match('/\b(?:BLOOD|TYPE|SINGLE|MARRIED|WIDOWED|DIVORCED|SEPARATED|CARDHOLDER|CITIZEN|QCID|NAME|SEX|STATUS)\b/u', $segment) === 1;

            if ($hasPersonalNoise && ! $hasLocationMarker) {
                continue;
            }

            if ($hasLocationMarker || preg_match('/\bQUEZON\s*CITY\b/u', $segment) === 1) {
                $kept[] = trim($segment, ' .');
            }
        }

        if ($kept !== []) {
            $value = implode(', ', $kept);
            if (preg_match('/\bQUEZON\s*CITY\b/u', $value) !== 1) {
                $value = trim($value, ' ,') . ', QUEZON CITY';
            }
        }

        return trim((string) (preg_replace('/\s+/', ' ', $value) ?? $value), ' ,');
    }

    private function normalizeTempPath(string $path): string
    {
        return ltrim(str_replace('\\', '/', trim($path)), '/');
    }

    private function getVerifiedScanSnapshot(Request $request, string $tempPath): ?array
    {
        $normalizedPath = $this->normalizeTempPath($tempPath);
        if ($normalizedPath === '') {
            return null;
        }

        $sessionKey = 'signup_verified_scans';
        $items = (array) $request->session()->get($sessionKey, []);
        $payload = $items[$normalizedPath] ?? null;

        if (! is_array($payload)) {
            return null;
        }

        $storedAt = (int) ($payload['stored_at'] ?? 0);
        if ($storedAt > 0 && (time() - $storedAt) > 7200) {
            unset($items[$normalizedPath]);
            $request->session()->put($sessionKey, $items);

            return null;
        }

        return $payload;
    }

    private function forgetVerifiedScanSnapshot(Request $request, string $tempPath): void
    {
        $normalizedPath = $this->normalizeTempPath($tempPath);
        if ($normalizedPath === '') {
            return;
        }

        $sessionKey = 'signup_verified_scans';
        $items = (array) $request->session()->get($sessionKey, []);

        if (! array_key_exists($normalizedPath, $items)) {
            return;
        }

        unset($items[$normalizedPath]);
        $request->session()->put($sessionKey, $items);
    }

    private function isQcIdAlreadyInUse(string $qcidNumber): bool
    {
        $digits = preg_replace('/\D+/', '', $qcidNumber) ?? '';
        if (strlen($digits) === 13) {
            $digits = '0' . $digits;
        }

        if (strlen($digits) !== 14) {
            return false;
        }

        return QcIdRegistration::query()
            ->whereNotNull('qcid_number')
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(qcid_number, ' ', ''), '-', ''), '.', ''), '/', '') = ?", [$digits])
            ->exists();
    }

    private function registrationAttemptBucket(Request $request, string $email = ''): string
    {
        $normalizedEmail = strtolower(trim($email));
        if ($normalizedEmail === '') {
            $normalizedEmail = strtolower(trim((string) $request->input('email', '')));
        }

        return sha1($request->ip() . '|' . $normalizedEmail);
    }

    private function registrationAttemptsCacheKey(string $bucket): string
    {
        return 'register_attempts:' . $bucket;
    }

    private function registrationLockoutCacheKey(string $bucket): string
    {
        return 'register_lockout:' . $bucket;
    }

    private function registrationLockoutSecondsRemaining(string $bucket): int
    {
        $lockoutKey = $this->registrationLockoutCacheKey($bucket);
        $lockedUntil = (int) Cache::get($lockoutKey, 0);

        if ($lockedUntil <= now()->timestamp) {
            Cache::forget($lockoutKey);

            return 0;
        }

        return $lockedUntil - now()->timestamp;
    }

    private function registrationLockoutResponse(Request $request, int $seconds): RedirectResponse
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return back()->withErrors([
            'email' => 'Too many failed registration attempts. Please wait ' . $minutes . ' minute(s) before trying again.',
        ])->withInput($request->except(['password', 'password_confirmation', 'otp_token']));
    }

    private function registrationFailureResponse(Request $request, string $bucket, array $errors): RedirectResponse
    {
        $normalized = $this->normalizeRegistrationErrors($errors);
        $state = $this->recordRegistrationFailure($bucket);

        if ($state['locked']) {
            $minutes = max(1, (int) ceil(((int) $state['lockout_seconds']) / 60));
            $normalized['email'] = 'Too many failed registration attempts. Please wait ' . $minutes . ' minute(s) before trying again.';
        } elseif (((int) $state['attempts_remaining']) <= 1) {
            $warning = 'Warning: ' . (int) $state['attempts_remaining'] . ' attempt(s) remaining before a temporary cooldown.';
            $normalized['email'] = isset($normalized['email'])
                ? trim($normalized['email'] . ' ' . $warning)
                : $warning;
        }

        return back()->withErrors($normalized)
            ->withInput($request->except(['password', 'password_confirmation', 'otp_token']));
    }

    private function normalizeRegistrationErrors(array $errors): array
    {
        $normalized = [];

        foreach ($errors as $field => $message) {
            if (is_array($message)) {
                $first = $message[0] ?? null;
                if (is_string($first) && trim($first) !== '') {
                    $normalized[(string) $field] = $first;
                }

                continue;
            }

            if (is_string($message) && trim($message) !== '') {
                $normalized[(string) $field] = $message;
            }
        }

        if ($normalized === []) {
            $normalized['email'] = 'Unable to complete registration right now. Please review your details and try again.';
        }

        return $normalized;
    }

    private function recordRegistrationFailure(string $bucket): array
    {
        $attemptsKey = $this->registrationAttemptsCacheKey($bucket);
        $lockoutKey = $this->registrationLockoutCacheKey($bucket);

        $attempts = (int) Cache::get($attemptsKey, 0) + 1;
        Cache::put($attemptsKey, $attempts, now()->addSeconds(self::REGISTRATION_COOLDOWN_SECONDS + 60));

        if ($attempts >= self::REGISTRATION_MAX_ATTEMPTS) {
            Cache::put(
                $lockoutKey,
                now()->timestamp + self::REGISTRATION_COOLDOWN_SECONDS,
                now()->addSeconds(self::REGISTRATION_COOLDOWN_SECONDS)
            );
            Cache::forget($attemptsKey);

            return [
                'locked' => true,
                'lockout_seconds' => self::REGISTRATION_COOLDOWN_SECONDS,
                'attempts_remaining' => 0,
            ];
        }

        return [
            'locked' => false,
            'lockout_seconds' => 0,
            'attempts_remaining' => max(0, self::REGISTRATION_MAX_ATTEMPTS - $attempts),
        ];
    }

    private function clearRegistrationAttemptState(string $bucket): void
    {
        Cache::forget($this->registrationAttemptsCacheKey($bucket));
        Cache::forget($this->registrationLockoutCacheKey($bucket));
    }

    private function registrationDateOfBirthError(mixed $dateOfBirth): ?string
    {
        $dateString = trim((string) ($dateOfBirth ?? ''));
        if ($dateString === '') {
            return 'Date of birth is required and must be extracted from your QC ID scan.';
        }

        try {
            $dob = Carbon::parse($dateString)->startOfDay();
        } catch (\Throwable $e) {
            return 'Date of birth from your QC ID could not be verified. Please scan your QC ID again.';
        }

        $today = Carbon::now()->startOfDay();
        if ($dob->greaterThan($today)) {
            return 'Date of birth cannot be in the future.';
        }

        if ($dob->age < self::REGISTRATION_MIN_AGE) {
            return 'You must be at least ' . self::REGISTRATION_MIN_AGE . ' years old to register.';
        }

        return null;
    }

    private function classificationFromRegistration(array $validated): string
    {
        $userType = strtolower(trim((string) ($validated['user_type'] ?? '')));

        return $userType === 'employee'
            ? User::CLASSIFICATION_FACULTY
            : User::CLASSIFICATION_STUDENT;
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
        if ($error = $this->googleLoginUnavailableError()) {
            return redirect()->route('login')->withErrors([
                'email' => $error,
            ]);
        }

        return $this->googleProvider(request())->redirect();
    }

    public function googleCallback(Request $request)
    {
        if ($error = $this->googleLoginUnavailableError()) {
            return redirect()->route('login')->withErrors([
                'email' => $error,
            ]);
        }

        if ($error = $this->googleCallbackRequestError($request)) {
            return redirect()->route('login')->withErrors([
                'email' => $error,
            ]);
        }

        try {
            $googleUser = $this->googleUserFromCallback($request);

            $email = $googleUser->getEmail();
            $providerId = $googleUser->getId();

            $user = $this->findGoogleUser($email, $providerId);

            if (! $user) {
                $user = User::create($this->googleCreateAttributes($googleUser, $email, $providerId));
            } else {
                $this->syncGoogleIdentity($user, $providerId);
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return $this->redirectFor($user);
        } catch (\Throwable $e) {
            Log::warning('Google login callback failed.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'redirect_uri' => $this->googleRedirectUri($request),
                'request_host' => $request->getSchemeAndHttpHost(),
                'google_error' => $request->input('error'),
                'google_error_description' => $request->input('error_description'),
            ]);

            return redirect()->route('login')->withErrors([
                'email' => $this->googleFailureMessage($e),
            ]);
        }
    }

    private function googleConfigured(): bool
    {
        return (string) config('services.google.client_id') !== ''
            && (string) config('services.google.client_secret') !== '';
    }

    private function googleRedirectUri(Request $request): string
    {
        $configured = trim((string) config('services.google.redirect'));

        if ($configured !== '') {
            return $configured;
        }

        return $request->getSchemeAndHttpHost().route('google.callback', [], false);
    }

    private function googleProvider(Request $request): AbstractProvider
    {
        $config = config('services.google', []);
        $guzzle = $config['guzzle'] ?? [];

        if (! isset($guzzle['verify']) && app()->environment('local')) {
            $guzzle['verify'] = false;
        }

        return Socialite::buildProvider(GoogleProvider::class, [
            'client_id' => (string) ($config['client_id'] ?? ''),
            'client_secret' => (string) ($config['client_secret'] ?? ''),
            'redirect' => $this->googleRedirectUri($request),
            'guzzle' => $guzzle,
        ]);
    }

    private function googleUserFromCallback(Request $request)
    {
        try {
            return $this->googleProvider($request)->user();
        } catch (InvalidStateException $e) {
            if (! app()->environment('local')) {
                throw $e;
            }

            return $this->googleProvider($request)->stateless()->user();
        }
    }

    private function googleCallbackRequestError(Request $request): ?string
    {
        if (! $request->filled('error')) {
            return null;
        }

        $error = (string) $request->input('error');

        if ($error === 'access_denied') {
            return 'Google sign-in was cancelled.';
        }

        if ($error === 'redirect_uri_mismatch') {
            return 'Google redirect URI mismatch. Add this exact URI in Google Console: '.$this->googleRedirectUri($request);
        }

        return 'Google returned an OAuth error: '.$error.'.';
    }

    private function googleFailureMessage(\Throwable $e): string
    {
        if ($e instanceof InvalidStateException) {
            return 'Google login session expired. Retry from the login page and keep the same host (localhost or 127.0.0.1) during the whole flow.';
        }

        $message = strtolower($e->getMessage());

        if (str_contains($message, 'invalid_client')) {
            return 'Google OAuth credentials are invalid. Verify GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in .env.';
        }

        if (str_contains($message, 'redirect_uri_mismatch')) {
            return 'Google redirect URI mismatch. Add this callback URI in Google Console and in GOOGLE_REDIRECT_URI if set.';
        }

        if (str_contains($message, 'unauthorized_client')) {
            return 'Google OAuth client is not authorized for this sign-in request. Check OAuth client type and consent screen settings.';
        }

        if (str_contains($message, 'curl error 60') || str_contains($message, 'ssl certificate problem')) {
            return 'Google OAuth SSL trust failed on this machine (cURL error 60). For local development, SSL verification has been relaxed automatically. Retry Google login now.';
        }

        return 'Google login failed. Please try again. Check storage/logs/laravel.log for the detailed callback error.';
    }

    private function googleLoginUnavailableError(): ?string
    {
        if (! class_exists(Socialite::class)) {
            return 'Google login package is missing. Run composer install (or composer require laravel/socialite), then run php artisan optimize:clear.';
        }

        if (! $this->googleConfigured()) {
            return 'Google login is not configured. Set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET in your .env, then run php artisan config:clear.';
        }

        return null;
    }

    private function findGoogleUser(?string $email, string $providerId): ?User
    {
        if ($this->usersTableHasColumns(['provider', 'provider_id'])) {
            return User::query()
                ->where(function ($q) use ($email, $providerId) {
                    $q->where('provider', 'google')->where('provider_id', $providerId);

                    if (is_string($email) && $email !== '') {
                        $q->orWhere('email', $email);
                    }
                })
                ->first();
        }

        if (is_string($email) && $email !== '') {
            return User::query()->where('email', $email)->first();
        }

        return null;
    }

    private function googleCreateAttributes(\Laravel\Socialite\Contracts\User $googleUser, ?string $email, string $providerId): array
    {
        $attributes = [
            'name' => $googleUser->getName() ?: ($email ?: 'User'),
            'email' => $email ?: (Str::uuid()->toString() . '@example.invalid'),
            'password' => Hash::make(Str::random(32)),
        ];

        if ($this->usersTableHasColumns(['role'])) {
            $attributes['role'] = 'user';
        }

        if ($this->usersTableHasColumns(['provider', 'provider_id'])) {
            $attributes['provider'] = 'google';
            $attributes['provider_id'] = $providerId;
        }

        return $attributes;
    }

    private function syncGoogleIdentity(User $user, string $providerId): void
    {
        if (! $this->usersTableHasColumns(['provider', 'provider_id'])) {
            return;
        }

        $user->forceFill([
            'provider' => 'google',
            'provider_id' => $providerId,
        ])->save();
    }


    private function usersTableHasColumns(array $columns): bool
    {
        $availableColumns = $this->usersTableColumns();

        if ($availableColumns === null) {
            return false;
        }

        foreach ($columns as $column) {
            if (! in_array($column, $availableColumns, true)) {
                return false;
            }
        }

        return true;
    }

    private function usersTableColumns(): ?array
    {
        if ($this->usersTableColumnsResolved) {
            return $this->usersTableColumns;
        }

        $this->usersTableColumnsResolved = true;

        try {
            $this->usersTableColumns = Schema::getColumnListing('users');
        } catch (\Throwable $e) {
            Log::warning('Unable to inspect users table columns for Google login.', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            $this->usersTableColumns = null;
        }

        return $this->usersTableColumns;
    }
    private function redirectFor(?User $user)
    {
        // Intentionally ignore "intended" URLs so users can only land on allowed screens.
        return redirect()->route('dashboard');
    }
}