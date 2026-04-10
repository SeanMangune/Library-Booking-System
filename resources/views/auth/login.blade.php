@extends('layouts.guest')

@section('title', 'Login - SmartSpace')

@section('content')
<div class="login-shell min-h-screen flex items-center justify-center px-4 py-10 overflow-hidden relative">
    <div class="pointer-events-none absolute inset-0">
        <div class="login-led-orb login-led-orb-a"></div>
        <div class="login-led-orb login-led-orb-b"></div>
        <div class="login-led-grid"></div>
    </div>

    <div class="w-full max-w-5xl relative z-10">
        <div class="flex items-center justify-center mb-8">
            <div class="premium-logo-container">
                <img src="{{ asset('images/smartspace-logo.png') }}" alt="SmartSpace" class="h-44 sm:h-48 md:h-56 lg:h-64 w-auto max-w-none logo-premium logo-glow-purple">
            </div>
        </div>

        {{-- Registration success message --}}
        @if (session('registration_success'))
            <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 p-4 flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-100 flex items-center justify-center shrink-0">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-emerald-800">Account Created Successfully!</p>
                    <p class="text-xs text-emerald-700 mt-1">{{ session('registration_success') }}</p>
                </div>
            </div>
        @endif

        {{-- Only show login errors for login attempts --}}
        @if ($errors->has('login'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-800">{{ $errors->first('login') }}</p>
            </div>
            @if ($errors->has('locked') && $errors->first('locked'))
                <div x-data="{
                    totalSeconds: {{ (int) $errors->first('lockout_seconds', '0') }},
                    remaining: {{ (int) $errors->first('lockout_seconds', '0') }},
                    get minutes() { return Math.floor(this.remaining / 60) },
                    get seconds() { return this.remaining % 60 },
                    get formatted() { return String(this.minutes).padStart(2, '0') + ':' + String(this.seconds).padStart(2, '0') },
                    get progress() { return this.totalSeconds > 0 ? ((this.totalSeconds - this.remaining) / this.totalSeconds) * 100 : 0 },
                    init() {
                        if (this.remaining > 0) {
                            const timer = setInterval(() => {
                                this.remaining--;
                                if (this.remaining <= 0) {
                                    clearInterval(timer);
                                    window.location.reload();
                                }
                            }, 1000);
                        }
                    }
                }" class="mb-6 rounded-2xl border border-red-200 bg-gradient-to-r from-red-50 to-orange-50 p-5 shadow-sm">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center animate-pulse">
                            <i class="fa-solid fa-lock text-red-600 text-lg"></i>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-red-800">Account Temporarily Locked</p>
                            <p class="text-xs text-red-600">Too many failed login attempts</p>
                        </div>
                    </div>
                    <div class="flex items-center justify-center gap-2 mb-3">
                        <span class="text-3xl font-black tabular-nums text-red-700" x-text="formatted"></span>
                    </div>
                    <div class="h-2 rounded-full bg-red-100 overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-red-500 to-orange-500 transition-all duration-1000" :style="`width: ${progress}%`"></div>
                    </div>
                    <p class="text-xs text-red-600 text-center mt-2">Login will be re-enabled when the timer reaches zero</p>
                </div>
            @elseif ($errors->has('show_warning') && $errors->first('show_warning'))
                <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                    <div>
                        <p class="text-sm font-bold text-amber-800">Warning: Account at risk</p>
                        <p class="text-xs text-amber-700 mt-1">{{ $errors->first('attempts_remaining', '0') }} attempt(s) remaining before your account is temporarily locked.</p>
                    </div>
                </div>
            @endif
        @endif



        @php
            $signupFields = [
                'name',
                'username',
                'email',
                'phone_number',
                'user_type',
                'employee_category',
                'course',
                'campus',
                'qcid_number',
                'sex',
                'civil_status',
                'date_of_birth',
                'date_issued',
                'valid_until',
                'address',
                'ocr_text',
                'qcid_image',
                'qcid_temp_upload',
                'otp_token',
                'password',
                'password_confirmation',
            ];

            $hasSignupOldInput = collect($signupFields)
                ->contains(fn ($field) => filled(old($field)));

            $openSignupOnLoad = $hasSignupOldInput || $errors->hasAny($signupFields);
        @endphp

        <div x-data="signupLoginApp($persist, {{ $openSignupOnLoad ? 'true' : 'false' }})">
            <div class="flex justify-center">
                <!-- User Login -->
                <div class="w-full max-w-xl">
                    <div class="relative group login-card-wrap">
                        <div aria-hidden="true" class="pointer-events-none absolute -inset-x-12 -bottom-14 h-24 bg-gradient-to-r from-cyan-400 via-indigo-500 to-teal-400 blur-3xl opacity-45 transition-opacity duration-300 group-hover:opacity-80"></div>
                        <div class="login-neon-card bg-gradient-to-b from-white/95 to-slate-50/95 backdrop-blur rounded-3xl border border-white/70 shadow-[0_24px_80px_-24px_rgba(15,23,42,0.55)] overflow-hidden transition-all duration-300 group-hover:-translate-y-1 group-hover:shadow-[0_32px_90px_-28px_rgba(30,41,59,0.65)]">
                    <div class="px-6 py-5 border-b border-gray-100 bg-white/60">
                        <h2 class="text-lg font-bold text-gray-900">Login</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <form method="POST" action="{{ route('login.post') }}" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Username or Email</label>
                                <input name="login" type="text" value="{{ old('login', session('registered_username')) }}" required autocomplete="username"
                                       class="mt-1 w-full rounded-xl border border-gray-200 px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700">Password</label>
                                <div class="relative mt-1">
                                    <input name="password" :type="showLoginPassword ? 'text' : 'password'" required autocomplete="current-password"
                                           class="w-full rounded-xl border border-gray-200 px-3 py-2.5 pr-11 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <button type="button"
                                            @click="showLoginPassword = !showLoginPassword"
                                            :aria-label="showLoginPassword ? 'Hide password' : 'Show password'"
                                            class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700">
                                        <i x-show="!showLoginPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye text-lg leading-none"></i>
                                        <i x-show="showLoginPassword" x-cloak class="w-5 h-5 fa-icon fa-regular fa-eye-slash text-lg leading-none"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                    <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Remember me
                                </label>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">Forgot password?</a>
                                @endif
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors">
                                Login
                            </button>
                        </form>

                        <button type="button" @click="signupOpen = true" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition-colors shadow-lg shadow-teal-700/20">
                            Sign Up
                        </button>


                        <div x-data="pwaInstallPrompt()" x-cloak class="mt-4">
                            <button @click="promptInstall" type="button" class="block install-app-card group/install w-full relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-4 text-left transition-all duration-500 hover:border-emerald-300/80 hover:shadow-[0_0_30px_-5px_rgba(16,185,129,0.35)] active:scale-[0.98]">
                                <!-- Animated gradient glow background -->
                                <div class="absolute inset-0 rounded-2xl opacity-0 group-hover/install:opacity-100 transition-opacity duration-700 bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.08)_0%,transparent_70%)]"></div>
                                <!-- Shimmer sweep -->
                                <div class="absolute inset-0 -translate-x-full group-hover/install:translate-x-full transition-transform duration-1000 ease-in-out bg-gradient-to-r from-transparent via-white/40 to-transparent"></div>
                                <div class="relative z-10 flex items-center gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/25 group-hover/install:shadow-emerald-500/40 group-hover/install:scale-110 transition-all duration-500">
                                        <i class="fa-solid fa-mobile-screen text-white text-lg group-hover/install:animate-bounce"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 group-hover/install:text-emerald-700 transition-colors duration-300">Install App</p>
                                        <p class="text-xs text-gray-500 group-hover/install:text-emerald-600/70 transition-colors duration-300 mt-0.5">Quick access for PC & Mobile</p>
                                    </div>
                                    <div class="shrink-0 flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 group-hover/install:bg-emerald-500 group-hover/install:text-white transition-all duration-300">
                                        <i class="fa-solid fa-arrow-down text-xs"></i>
                                    </div>
                                </div>
                            </button>

                            <!-- iOS specific install hint -->
                            <div x-show="showIosHint" x-transition.duration.300ms class="mt-2 text-center text-xs text-gray-500 bg-emerald-50/50 py-2 rounded-xl border border-emerald-100/50">
                                Tap <i class="fa-solid fa-arrow-up-from-bracket mx-1 text-blue-500"></i> Share → <b>Add to Home Screen</b>
                            </div>
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>

            <x-modals.auth.signup />
            <x-modals.auth.signup-otp />
            <x-modals.auth.signup-error />

        </div>
    </div>
</div>

</script>

</script>
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
<script type="text/javascript">
// Blade: Hide signupOldInput and signupQcidVerifyUrl from page output
window.signupOldInput = {
    name: @json(old('name', '')),
    email: @json(old('email', '')),
    username: @json(old('username', '')),
    user_type: @json(old('user_type', '')),
    employee_category: @json(old('employee_category', '')),
    course: @json(old('course', '')),
    campus: @json(old('campus', '')),
    qcid_number: @json(old('qcid_number', '')),
    sex: @json(old('sex', '')),
    civil_status: @json(old('civil_status', '')),
    date_of_birth: @json(old('date_of_birth', '')),
    date_issued: @json(old('date_issued', '')),
    valid_until: @json(old('valid_until', '')),
    address: @json(old('address', '')),
    ocr_text: @json(old('ocr_text', '')),
    qcid_temp_upload: @json(old('qcid_temp_upload', '')),
    otp_token: @json(old('otp_token', '')),
};
window.signupQcidVerifyUrl = @json(route('signup.qcid.verify'));
window.signupSendOtpUrl = @json(route('register.send-otp'));
window.signupVerifyOtpUrl = @json(route('register.verify-otp'));
</script>
<script>
function signupLoginApp($persist, initialSignupOpen) {
    return {
        signupOpen: !!initialSignupOpen || new URLSearchParams(window.location.search).get('auth') === 'signup',
        showLoginPassword: false,
        showSignupPassword: false,
        showSignupConfirmPassword: false,
        signupPassword: '',
        signupConfirmPassword: '',
        signup: { ...window.signupOldInput },
        signupEmailError: '',
        // OTP verification state
        otpModalOpen: false,
        otpCode: '',
        otpEmail: '',
        otpError: '',
        otpStatus: '',
        otpSending: false,
        otpVerifying: false,
        otpResending: false,
        otpResendCooldown: 0,
        otpToken: '',
        _otpFormEl: null,
        scan: {
            file: null,
            previewUrl: '',
            isProcessing: false,
            isCapturing: false,
            error: '',
            status: '',
            idAssessment: '',
            confidenceLabel: '',
            isVerified: false,
            isQrVerified: null,
            qrData: '',
            qrIdNumber: '',
            cameraOpen: false,
            cameraError: '',
            cameraStream: null,
        },

        init() {
            // URL synchronization: ?auth=signup or ?auth=login
            const urlParams = new URLSearchParams(window.location.search);
            const authMode = urlParams.get('auth');
            
            if (authMode === 'signup') {
                this.signupOpen = true;
            } else if (authMode === 'login') {
                this.signupOpen = false;
            }

            // Server-side validation errors take precedence
            if (initialSignupOpen) {
                this.signupOpen = true;
                
                // Merge old inputs dynamically
                for (const key in window.signupOldInput) {
                    if (window.signupOldInput[key]) {
                        this.signup[key] = window.signupOldInput[key];
                    }
                }
            } else if (!this.signup) {
                this.signup = { ...window.signupOldInput };
            }

            this.signup.username = this.sanitizeUsername(this.signup.username || '');
            this.signup.email = String(this.signup.email || '').trim().toLowerCase();
            this.signup.qcid_number = this.normalizeQcIdValue(this.signup.qcid_number || '');
            this.signupEmailError = this.validateSignupEmail(this.signup.email);

            const restoredOtpToken = String(window.signupOldInput?.otp_token || '').trim();
            if (restoredOtpToken) {
                this.otpToken = restoredOtpToken;
                this.otpEmail = this.signup.email || '';
            }

            if (this.signup.qcid_temp_upload && this.signup.ocr_text) {
                this.scan.isVerified = true;
                this.scan.idAssessment = 'Verified';
                this.scan.confidenceLabel = 'Verified';
            }

            // Persistence: Sync to URL on change
            this.$watch('signupOpen', (val) => {
                const url = new URL(window.location.href);
                if (val) {
                    url.searchParams.set('auth', 'signup');
                } else {
                    this.closeSignupCamera();
                    url.searchParams.set('auth', 'login');
                }
                window.history.replaceState({}, '', url);
            });
        },

        sanitizeUsername(value) {
            const cleaned = String(value || '').replace(/[^a-zA-Z0-9_]/g, '');
            return cleaned.substring(0, 15);
        },

        normalizeQcIdValue(value) {
            return String(value || '').replace(/\D/g, '').substring(0, 14);
        },

        isLikelyRealEmailAddress(value) {
            const email = String(value || '').trim().toLowerCase();
            const match = email.match(/^([^@\s]+)@([^@\s]+)$/);
            if (!match) {
                return false;
            }

            const local = String(match[1] || '');
            const domain = String(match[2] || '');

            if (!local || !domain) {
                return false;
            }

            if (local.length > 64 || domain.length > 255) {
                return false;
            }

            if (
                local.startsWith('.')
                || local.endsWith('.')
                || local.includes('..')
                || domain.startsWith('-')
                || domain.endsWith('-')
                || domain.startsWith('.')
                || domain.endsWith('.')
                || domain.includes('..')
            ) {
                return false;
            }

            if (!/^[a-z0-9._%+-]+$/i.test(local)) {
                return false;
            }

            if (!/^[a-z0-9.-]+\.[a-z]{2,24}$/i.test(domain)) {
                return false;
            }

            const letterCount = (local.match(/[a-z]/gi) || []).length;
            if (letterCount < 1) {
                return false;
            }

            if (/([a-z0-9])\1{5,}/i.test(local)) {
                return false;
            }

            return true;
        },

        validateSignupEmail(value) {
            const email = String(value || '').trim().toLowerCase();

            if (!email) {
                return '';
            }

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/i.test(email)) {
                return 'Use a real email.';
            }

            if (!this.isLikelyRealEmailAddress(email)) {
                return 'Use a real email.';
            }

            return '';
        },

        validateSignupDateOfBirth(value) {
            const normalized = this.normalizeDate(value || '');
            if (!normalized) {
                return 'Date of birth must be extracted from your QC ID before registration.';
            }

            const dob = new Date(`${normalized}T00:00:00`);
            if (Number.isNaN(dob.getTime())) {
                return 'Date of birth from your QC ID is invalid. Please rescan your ID.';
            }

            const today = new Date();
            today.setHours(0, 0, 0, 0);
            if (dob > today) {
                return 'Date of birth cannot be in the future.';
            }

            const minAllowedDob = new Date(today.getFullYear() - 15, today.getMonth(), today.getDate());
            if (dob > minAllowedDob) {
                return 'You must be at least 15 years old to register.';
            }

            this.signup.date_of_birth = normalized;
            return '';
        },

        validateAndSubmitSignup(formEl) {
            this.scan.error = '';
            this.signup.username = this.sanitizeUsername(this.signup.username || '');
            this.signup.qcid_number = this.normalizeQcIdValue(this.signup.qcid_number || '');

            if (!this.signup.ocr_text || !this.signup.qcid_temp_upload) {
                this.scan.error = 'Please upload and verify your QC ID first before creating an account.';
                return;
            }

            if ((this.signup.qcid_number || '').length !== 14) {
                this.scan.error = 'QC ID number must be exactly 14 digits.';
                return;
            }

            const dobError = this.validateSignupDateOfBirth(this.signup.date_of_birth);
            if (dobError) {
                this.scan.error = dobError;
                return;
            }

            if ((this.signup.user_type || '') === 'student') {
                if (!String(this.signup.course || '').trim()) {
                    this.scan.error = 'Please select your course or department.';
                    return;
                }

                if (!String(this.signup.campus || '').trim()) {
                    this.scan.error = 'Please select your campus.';
                    return;
                }
            }

            if (this.scan.qrIdNumber) {
                const enteredId = this.normalizeQcIdValue(this.signup.qcid_number || '');
                const qrId = this.normalizeQcIdValue(this.scan.qrIdNumber || '');
                if (enteredId !== qrId) {
                    this.scan.error = 'QC ID number mismatch! Your ID\'s QR code shows ' + this.scan.qrIdNumber + ', but the form has ' + this.signup.qcid_number + '. The QC ID number must match the QR code on your physical ID.';
                    return;
                }
            }

            // If OTP already verified, submit the form
            if (this.otpToken) {
                this.$nextTick(() => this.submitSignupForm(formEl));
                return;
            }

            // Get email from the form
            const emailInput = formEl.querySelector('input[name="email"]');
            const email = emailInput ? String(emailInput.value || '').trim().toLowerCase() : '';
            const name = this.signup.name || '';
            this.signup.email = email;

            if (emailInput) {
                emailInput.value = email;
            }

            this.signupEmailError = this.validateSignupEmail(email);

            if (!email) {
                this.scan.error = 'Use a real email.';
                return;
            }

            if (this.signupEmailError) {
                this.scan.error = this.signupEmailError;
                return;
            }

            if (!name) {
                this.scan.error = 'Please enter your full name.';
                return;
            }

            // Store form reference and send OTP
            this._otpFormEl = formEl;
            this.otpEmail = email;
            this.sendRegOtp(email, name);
        },

        submitSignupForm(formEl) {
            if (!formEl) {
                return;
            }

            if (!this.otpToken) {
                this.scan.error = 'Email verification is required. Please verify your email first.';
                return;
            }

            // Ensure latest reactive values are present in hidden inputs before submit.
            const otpTokenInput = formEl.querySelector('input[name="otp_token"]');
            if (otpTokenInput) {
                otpTokenInput.value = String(this.otpToken || '');
            }

            const tempUploadInput = formEl.querySelector('input[name="qcid_temp_upload"]');
            if (tempUploadInput) {
                tempUploadInput.value = String(this.signup.qcid_temp_upload || '');
            }

            // Prevent Android Chrome "ERR_UPLOAD_FILE_CHANGED" by
            // avoiding a second native file upload when we already have
            // a server-stored verified image token.
            const fileInput = formEl.querySelector('input[name="qcid_image"]');
            if (fileInput) {
                fileInput.disabled = !!this.signup.qcid_temp_upload;
            }

            formEl.submit();
        },

        async sendRegOtp(email, name) {
            this.otpSending = true;
            this.otpError = '';
            this.otpStatus = '';
            this.otpCode = '';

            try {
                const response = await fetch(window.signupSendOtpUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ email, name }),
                });

                const data = await response.json();

                if (data.success) {
                    this.otpModalOpen = true;
                    this.otpStatus = data.message || 'Verification code sent!';
                    this.startResendCooldown();
                } else {
                    // Show error on the signup form if email already exists etc
                    const msg = data.message || 'Failed to send verification code.';
                    if (data.errors?.email) {
                        this.scan.error = data.errors.email[0] || msg;
                    } else {
                        this.scan.error = msg;
                    }
                }
            } catch (error) {
                const errData = error?.response ? await error.response.json().catch(() => null) : null;
                this.scan.error = errData?.message || 'Unable to send verification code. Please try again.';
            } finally {
                this.otpSending = false;
            }
        },

        async verifyRegOtp() {
            if (this.otpCode.length !== 6) return;

            this.otpVerifying = true;
            this.otpError = '';

            try {
                const response = await fetch(window.signupVerifyOtpUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ email: this.otpEmail, otp: this.otpCode }),
                });

                const data = await response.json();

                if (data.success && data.otp_token) {
                    this.otpToken = data.otp_token;
                    this.otpModalOpen = false;

                    // Submit the registration form
                    if (this._otpFormEl) {
                        this.$nextTick(() => this.submitSignupForm(this._otpFormEl));
                    }
                } else {
                    this.otpError = data.message || 'Verification failed. Please try again.';
                }
            } catch (error) {
                this.otpError = 'Unable to verify code. Please try again.';
            } finally {
                this.otpVerifying = false;
            }
        },

        async resendRegOtp() {
            if (this.otpResendCooldown > 0 || this.otpResending) return;

            this.otpResending = true;
            this.otpError = '';
            this.otpStatus = '';

            try {
                const response = await fetch(window.signupSendOtpUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({ email: this.otpEmail, name: this.signup.name || '' }),
                });

                const data = await response.json();

                if (data.success) {
                    this.otpStatus = 'A new verification code has been sent to your email.';
                    this.otpCode = '';
                    this.startResendCooldown();
                } else {
                    this.otpError = data.message || 'Failed to resend code.';
                }
            } catch (error) {
                this.otpError = 'Unable to resend code. Please try again.';
            } finally {
                this.otpResending = false;
            }
        },

        startResendCooldown() {
            this.otpResendCooldown = 60;
            const timer = setInterval(() => {
                this.otpResendCooldown--;
                if (this.otpResendCooldown <= 0) {
                    clearInterval(timer);
                }
            }, 1000);
        },

        resetSignupScanData() {
            this.scan.error = '';
            this.scan.status = '';
            this.scan.idAssessment = '';
            this.scan.confidenceLabel = '';
            this.scan.isVerified = false;
            this.scan.isQrVerified = null;
            this.scan.qrData = '';
            this.scan.qrIdNumber = '';
            this.scan.cameraError = '';

            // Reset registration fields (excluding account info)
            this.signup.name = '';
            this.signup.qcid_number = '';
            this.signup.sex = '';
            this.signup.civil_status = '';
            this.signup.date_of_birth = '';
            this.signup.date_issued = '';
            this.signup.valid_until = '';
            this.signup.address = '';
            this.signup.ocr_text = '';
            this.signup.qcid_temp_upload = '';
        },

        prepareSignupScanFile(file) {
            this.scan.file = file;
            this.resetSignupScanData();

            if (!file) {
                if (this.scan.previewUrl) {
                    URL.revokeObjectURL(this.scan.previewUrl);
                }
                this.scan.previewUrl = '';
                return false;
            }

            if (!String(file.type || '').startsWith('image/')) {
                this.scan.error = 'Please upload an image file for QC ID scanning.';
                this.scan.file = null;
                if (this.scan.previewUrl) {
                    URL.revokeObjectURL(this.scan.previewUrl);
                }
                this.scan.previewUrl = '';
                return false;
            }

            if (file.size > (25 * 1024 * 1024)) {
                this.scan.error = 'Image is too large. Please use an image under 25 MB.';
                this.scan.file = null;
                return false;
            }

            if (this.scan.previewUrl) {
                URL.revokeObjectURL(this.scan.previewUrl);
            }

            this.scan.previewUrl = URL.createObjectURL(file);
            return true;
        },

        stopSignupCameraStream() {
            const stream = this.scan.cameraStream;
            if (stream && typeof stream.getTracks === 'function') {
                stream.getTracks().forEach((track) => track.stop());
            }

            this.scan.cameraStream = null;

            const videoEl = this.$refs?.signupCameraVideo;
            if (videoEl) {
                try {
                    videoEl.pause();
                } catch (e) {
                    // Ignore pause errors from interrupted media state.
                }
                videoEl.srcObject = null;
            }
        },

        closeSignupCamera() {
            this.stopSignupCameraStream();
            this.scan.cameraOpen = false;
            this.scan.isCapturing = false;
            this.scan.cameraError = '';
        },

        async openSignupCamera() {
            if (this.scan.isProcessing || this.scan.isCapturing) {
                return;
            }

            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                this.scan.cameraError = 'Camera capture is not supported on this browser. Please upload an image instead.';
                return;
            }

            this.scan.cameraError = '';
            this.stopSignupCameraStream();
            this.scan.cameraOpen = true;

            try {
                const stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: { ideal: 'environment' },
                        width: { ideal: 1920 },
                        height: { ideal: 1080 },
                    },
                    audio: false,
                });

                this.scan.cameraStream = stream;

                await this.$nextTick();

                const videoEl = this.$refs?.signupCameraVideo;
                if (!videoEl) {
                    throw new Error('Unable to initialize camera preview.');
                }

                videoEl.srcObject = stream;
                await videoEl.play();
            } catch (error) {
                this.stopSignupCameraStream();
                this.scan.cameraOpen = false;
                this.scan.cameraError = 'Unable to access the camera. Please allow camera permission or upload an image file.';
            }
        },

        evaluateCapturedQcIdQuality(canvasEl) {
            const minEdge = Math.min(canvasEl.width, canvasEl.height);
            if (minEdge < 700) {
                return 'Move closer so the QC ID fully fills the guide frame before capturing.';
            }

            const sampleMax = 240;
            const scale = Math.min(1, sampleMax / Math.max(canvasEl.width, canvasEl.height));
            const sampleWidth = Math.max(96, Math.round(canvasEl.width * scale));
            const sampleHeight = Math.max(96, Math.round(canvasEl.height * scale));

            const sampleCanvas = document.createElement('canvas');
            sampleCanvas.width = sampleWidth;
            sampleCanvas.height = sampleHeight;
            const sampleCtx = sampleCanvas.getContext('2d', { willReadFrequently: true });
            if (!sampleCtx) {
                return 'Unable to analyze image quality. Please retake the photo.';
            }

            sampleCtx.drawImage(canvasEl, 0, 0, sampleWidth, sampleHeight);
            const imageData = sampleCtx.getImageData(0, 0, sampleWidth, sampleHeight);
            const pixels = imageData.data;

            const gray = new Float32Array(sampleWidth * sampleHeight);
            let brightnessTotal = 0;

            for (let y = 0; y < sampleHeight; y += 1) {
                for (let x = 0; x < sampleWidth; x += 1) {
                    const offset = ((y * sampleWidth) + x) * 4;
                    const luma = (0.299 * pixels[offset]) + (0.587 * pixels[offset + 1]) + (0.114 * pixels[offset + 2]);
                    gray[(y * sampleWidth) + x] = luma;
                    brightnessTotal += luma;
                }
            }

            const averageBrightness = brightnessTotal / gray.length;
            if (averageBrightness < 45) {
                return 'Image is too dark. Improve lighting before capturing.';
            }

            if (averageBrightness > 235) {
                return 'Image is overexposed. Reduce glare and retake the photo.';
            }

            let edgeSum = 0;
            let edgeSquaredSum = 0;
            let edgeCount = 0;

            for (let y = 1; y < sampleHeight - 1; y += 1) {
                for (let x = 1; x < sampleWidth - 1; x += 1) {
                    const idx = (y * sampleWidth) + x;
                    const gx = gray[idx + 1] - gray[idx - 1];
                    const gy = gray[idx + sampleWidth] - gray[idx - sampleWidth];
                    const magnitude = Math.sqrt((gx * gx) + (gy * gy));

                    edgeSum += magnitude;
                    edgeSquaredSum += magnitude * magnitude;
                    edgeCount += 1;
                }
            }

            if (edgeCount === 0) {
                return 'Image quality could not be measured. Please retake the photo.';
            }

            const edgeMean = edgeSum / edgeCount;
            const edgeVariance = (edgeSquaredSum / edgeCount) - (edgeMean * edgeMean);

            if (edgeVariance < 140) {
                return 'Image looks blurry. Hold your device steady and retake the photo.';
            }

            return '';
        },

        async captureSignupQcIdPhoto() {
            if (this.scan.isCapturing || this.scan.isProcessing) {
                return;
            }

            this.scan.cameraError = '';

            const videoEl = this.$refs?.signupCameraVideo;
            const viewportEl = this.$refs?.signupCameraViewport;
            const frameEl = this.$refs?.signupCameraGuideFrame;

            if (!videoEl || !viewportEl || !frameEl || !videoEl.videoWidth || !videoEl.videoHeight) {
                this.scan.cameraError = 'Camera is not ready yet. Please wait a moment and try again.';
                return;
            }

            this.scan.isCapturing = true;

            try {
                const viewportRect = viewportEl.getBoundingClientRect();
                const frameRect = frameEl.getBoundingClientRect();

                const frameX = Math.max(0, frameRect.left - viewportRect.left);
                const frameY = Math.max(0, frameRect.top - viewportRect.top);
                const frameWidth = Math.min(frameRect.width, viewportRect.width - frameX);
                const frameHeight = Math.min(frameRect.height, viewportRect.height - frameY);

                const videoWidth = videoEl.videoWidth;
                const videoHeight = videoEl.videoHeight;

                const sourceAspect = videoWidth / videoHeight;
                const viewportAspect = viewportRect.width / viewportRect.height;

                let renderedWidth;
                let renderedHeight;
                let offsetX = 0;
                let offsetY = 0;

                if (sourceAspect > viewportAspect) {
                    renderedHeight = viewportRect.height;
                    renderedWidth = renderedHeight * sourceAspect;
                    offsetX = (renderedWidth - viewportRect.width) / 2;
                } else {
                    renderedWidth = viewportRect.width;
                    renderedHeight = renderedWidth / sourceAspect;
                    offsetY = (renderedHeight - viewportRect.height) / 2;
                }

                const scaleX = videoWidth / renderedWidth;
                const scaleY = videoHeight / renderedHeight;

                const sourceX = Math.max(0, (frameX + offsetX) * scaleX);
                const sourceY = Math.max(0, (frameY + offsetY) * scaleY);
                const sourceWidth = Math.max(1, Math.min(videoWidth - sourceX, frameWidth * scaleX));
                const sourceHeight = Math.max(1, Math.min(videoHeight - sourceY, frameHeight * scaleY));

                const captureCanvas = document.createElement('canvas');
                captureCanvas.width = Math.max(900, Math.round(sourceWidth));
                captureCanvas.height = Math.max(560, Math.round(sourceHeight));

                const captureCtx = captureCanvas.getContext('2d', { willReadFrequently: true });
                if (!captureCtx) {
                    throw new Error('Unable to process camera capture.');
                }

                captureCtx.drawImage(
                    videoEl,
                    sourceX,
                    sourceY,
                    sourceWidth,
                    sourceHeight,
                    0,
                    0,
                    captureCanvas.width,
                    captureCanvas.height
                );

                const qualityError = this.evaluateCapturedQcIdQuality(captureCanvas);
                if (qualityError) {
                    this.scan.cameraError = qualityError;
                    return;
                }

                const blob = await new Promise((resolve) => {
                    captureCanvas.toBlob(resolve, 'image/jpeg', 0.94);
                });

                if (!blob) {
                    throw new Error('Unable to capture image from camera. Please try again.');
                }

                const capturedFile = new File([blob], `qcid-capture-${Date.now()}.jpg`, {
                    type: 'image/jpeg',
                });

                const ready = this.prepareSignupScanFile(capturedFile);
                if (!ready) {
                    return;
                }

                this.closeSignupCamera();
                this.scanSignupQcId();
            } catch (error) {
                this.scan.cameraError = error?.message || 'Unable to capture from camera. Please try again.';
            } finally {
                this.scan.isCapturing = false;
            }
        },

        onSignupQcImageChange(event) {
            const file = event.target?.files?.[0] || null;
            this.closeSignupCamera();

            if (!this.prepareSignupScanFile(file)) {
                return;
            }

            // Auto-start scanning immediately after upload selection.
            this.scanSignupQcId();
        },

        normalizeDate(raw) {
            if (!raw) return '';

            // Clean: replace separators with '/' for consistent parsing
            let cleaned = String(raw).trim().replace(/[\-\.]/g, '/');

            // YYYY/MM/DD format (from server)
            let m = cleaned.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);
            if (m) {
                return `${m[1]}-${String(m[2]).padStart(2, '0')}-${String(m[3]).padStart(2, '0')}`;
            }

            // MM/DD/YYYY or DD/MM/YYYY format
            m = cleaned.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
            if (m) {
                const a = parseInt(m[1], 10);
                const b = parseInt(m[2], 10);
                const year = m[3];
                // If first number > 12, it must be day (DD/MM/YYYY)
                const month = a > 12 ? b : a;
                const day = a > 12 ? a : b;
                return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            }

            // YYYYMMDD (8 continuous digits)
            m = cleaned.match(/^(\d{4})(\d{2})(\d{2})$/);
            if (m) {
                return `${m[1]}-${m[2]}-${m[3]}`;
            }

            // YYYY-MM-DD already
            m = cleaned.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (m) {
                return cleaned;
            }

            // Last resort: try string-based parsing without Date object (avoids timezone shift)
            const parts = cleaned.replace(/[^0-9]/g, ' ').replace(/\s+/g, ' ').trim().split(' ');
            if (parts.length === 3) {
                let year, month2, day2;
                if (parts[0].length === 4) { year = parts[0]; month2 = parts[1]; day2 = parts[2]; }
                else if (parts[2].length === 4) {
                    year = parts[2];
                    const aa = parseInt(parts[0], 10);
                    month2 = aa > 12 ? parts[1] : parts[0];
                    day2 = aa > 12 ? parts[0] : parts[1];
                }
                if (year && month2 && day2) {
                    return `${year}-${String(month2).padStart(2, '0')}-${String(day2).padStart(2, '0')}`;
                }
            }

            return '';
        },

        async getBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });
        },

        /**
         * Decode QR code from an uploaded image file using jsQR.
         * Returns the raw QR data string, or null if no QR found.
         */
        async decodeQrFromImage(file) {
            return new Promise((resolve) => {
                if (typeof jsQR === 'undefined') {
                    resolve(null);
                    return;
                }

                let settled = false;
                const resolveOnce = (value) => {
                    if (settled) return;
                    settled = true;
                    clearTimeout(watchdog);
                    resolve(value);
                };

                // Hard guard so scanner never hangs indefinitely.
                const watchdog = setTimeout(() => resolveOnce(null), 9000);

                const img = new Image();
                img.onload = async () => {
                    try {
                        const startedAt = performance.now();
                        const maxDecodeMs = 6500;

                        const shouldStop = () => (performance.now() - startedAt) > maxDecodeMs;

                        const tryDecodeImageData = (imageData) => {
                            const options = [
                                { inversionAttempts: 'attemptBoth' },
                                { inversionAttempts: 'dontInvert' },
                            ];

                            for (const option of options) {
                                const qrCode = jsQR(imageData.data, imageData.width, imageData.height, option);
                                if (qrCode?.data) {
                                    return qrCode.data;
                                }
                            }

                            return null;
                        };

                        const rotateCanvas = (sourceCanvas, degrees) => {
                            const radians = (degrees * Math.PI) / 180;
                            const rotated = document.createElement('canvas');
                            const quarterTurn = Math.abs(degrees) % 180 === 90;
                            rotated.width = quarterTurn ? sourceCanvas.height : sourceCanvas.width;
                            rotated.height = quarterTurn ? sourceCanvas.width : sourceCanvas.height;
                            const rctx = rotated.getContext('2d');
                            if (!rctx) return null;
                            rctx.translate(rotated.width / 2, rotated.height / 2);
                            rctx.rotate(radians);
                            rctx.drawImage(sourceCanvas, -sourceCanvas.width / 2, -sourceCanvas.height / 2);
                            return rotated;
                        };

                        const decodeCanvas = (canvas) => {
                            const ctx = canvas.getContext('2d', { willReadFrequently: true });
                            if (!ctx) return null;
                            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            return tryDecodeImageData(imageData);
                        };

                        const cropAttempts = [
                            { sx: 0, sy: 0, sw: img.width, sh: img.height, maxEdge: 1400 },
                            { sx: img.width * 0.45, sy: 0, sw: img.width * 0.55, sh: img.height, maxEdge: 1600 },
                            { sx: img.width * 0.5, sy: img.height * 0.25, sw: img.width * 0.5, sh: img.height * 0.75, maxEdge: 1700 },
                        ];

                        for (const attempt of cropAttempts) {
                            if (shouldStop()) break;

                            const sourceWidth = Math.max(1, Math.round(attempt.sw));
                            const sourceHeight = Math.max(1, Math.round(attempt.sh));
                            const downscale = Math.min(1, attempt.maxEdge / Math.max(sourceWidth, sourceHeight));
                            const targetWidth = Math.max(220, Math.round(sourceWidth * downscale));
                            const targetHeight = Math.max(220, Math.round(sourceHeight * downscale));

                            const canvas = document.createElement('canvas');
                            canvas.width = targetWidth;
                            canvas.height = targetHeight;

                            const ctx = canvas.getContext('2d', { willReadFrequently: true });
                            if (!ctx) {
                                continue;
                            }

                            ctx.drawImage(
                                img,
                                Math.max(0, Math.round(attempt.sx)),
                                Math.max(0, Math.round(attempt.sy)),
                                sourceWidth,
                                sourceHeight,
                                0,
                                0,
                                targetWidth,
                                targetHeight,
                            );

                            const baseDecoded = decodeCanvas(canvas);
                            if (baseDecoded) {
                                resolveOnce(baseDecoded);
                                return;
                            }

                            const rotated180 = rotateCanvas(canvas, 180);
                            if (rotated180) {
                                const rotatedDecoded = decodeCanvas(rotated180);
                                if (rotatedDecoded) {
                                    resolveOnce(rotatedDecoded);
                                    return;
                                }
                            }

                            // Yield briefly so UI remains responsive on mobile.
                            await new Promise((r) => setTimeout(r, 0));
                        }

                        resolveOnce(null);
                    } catch (_) {
                        resolveOnce(null);
                    }
                };

                img.onerror = () => resolveOnce(null);

                const reader = new FileReader();
                reader.onload = (e) => {
                    const src = e?.target?.result;
                    if (typeof src !== 'string') {
                        resolveOnce(null);
                        return;
                    }
                    img.src = src;
                };
                reader.onerror = () => resolveOnce(null);
                reader.readAsDataURL(file);
            });
        },

        /**
         * Extract a QC ID number from QR code data.
         * QR data may be: plain number, URL, JSON, or key=value pairs.
         */
        extractQcIdFromQr(qrData) {
            if (!qrData) return null;

            const text = String(qrData).trim();

            // Pattern: 14-digit number with optional spaces/dashes (e.g. "005 000 01257479")
            const idPattern = /(\d{3})\s*(\d{3})\s*(\d{8})/;
            let match = text.match(idPattern);
            if (match) {
                return `${match[1]}${match[2]}${match[3]}`;
            }

            // Pattern: continuous 14-digit number
            match = text.match(/(\d{14})/);
            if (match) {
                return match[1];
            }

            // Pattern: 13-digit number (missing leading zero)
            match = text.match(/(\d{13})/);
            if (match) {
                return `0${match[1]}`;
            }

            // Try to extract from URL (e.g. https://qcid.quezon.gov.ph/verify/00500001257479)
            match = text.match(/\d{10,14}/);
            if (match) {
                return match[0].padStart(14, '0').substring(0, 14);
            }

            return null;
        },

        parseDateCandidates(text) {
            const source = String(text || '').toUpperCase();
            const normalized = source
                .replace(/[|]/g, '/')
                .replace(/\s+/g, ' ')
                .trim();

            const results = [];
            const pushDate = (raw) => {
                const parsed = this.normalizeDate(raw);
                if (parsed && !results.includes(parsed)) {
                    results.push(parsed);
                }
            };

            const yyyySlash = normalized.match(/\b\d{4}\/\d{1,2}\/\d{1,2}\b/g) || [];
            yyyySlash.forEach(pushDate);

            const mmddyyyy = normalized.match(/\b\d{1,2}\/\d{1,2}\/\d{4}\b/g) || [];
            mmddyyyy.forEach(pushDate);

            const yyyymmdd = normalized.match(/\b\d{8}\b/g) || [];
            yyyymmdd.forEach((digits) => pushDate(`${digits.slice(0, 4)}/${digits.slice(4, 6)}/${digits.slice(6, 8)}`));

            return results;
        },

        improveAddress(value) {
            let address = String(value || '')
                .toUpperCase()
                .replace(/\s+/g, ' ')
                .trim();

            if (!address) {
                return '';
            }

            address = address
                .replace(/\b(?:DATE ISSUED|VALID UNTIL|DATE OF BIRTH|DOB|CIVIL STATUS|SEX|SIGNATURE|CARDHOLDER|ADDRESS|LAST NAME|FIRST NAME|MIDDLE NAME|REPUBLIC OF THE PHILIPPINES|CITIZEN CARD|CITIZENCARD|QCITIZENCARD|Q CITIZEN CARD|KASAMA KA SA PAG\-UNLAD|BLOOD TYPE|TYPE [ABO][\+\-]?|SINGLE|MARRIED|WIDOWED|DIVORCED|SEPARATED)\b/g, ' ')
                .replace(/\b\d{4}\/\d{1,2}\/\d{1,2}\b/g, ' ')
                .replace(/\b\d{1,2}\/\d{1,2}\/\d{4}\b/g, ' ')
                .replace(/\b(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|SEPT|OCT|NOV|DEC)\b\s+\d{1,2}\b/g, ' ')
                .replace(/\b\d{1,2}\s+(?:JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|SEPT|OCT|NOV|DEC)\b/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            address = address
                .replace(/^\d{1,2}\s+[A-Z]{3,10}\s+(?=\d{1,4}\s+[A-Z])/, '')
                .replace(/^\d{1,2}\s+(?=\d{1,4}\s+[A-Z])/, '')
                .trim();

            const brgyAnchors = 'BAGBAG|NOVALICHES|KINGSPOINT|FAIRVIEW|COMMONWEALTH|BATASAN|GULOD|SAN BARTOLOME|TALIPAPA|PAYATAS|CUBAO|PROJECT [4678]|MATANDANG BALARA|PASONG TAMO|HOLY SPIRIT|TANDANG SORA|BAESA';
            const cityPattern = '(?:QUEZON\\s*(?:CITY|C\\s*ITY|C1TY|1TY|ITY|LITY|CTY))';
            
            const fullRegex = new RegExp(`((?:\\d{1,4}[A-Z\\-]?\\s+[A-Z][A-Z0-9\\s,.\-]{4,})(?:${brgyAnchors}|${cityPattern}))`, 'i');
            const streetAnchor = address.match(fullRegex);
            if (streetAnchor?.[1]) {
                address = streetAnchor[1];
            }

            const chunkRegex = new RegExp(`([A-Z0-9,\\-.\\s]{6,}?(?:${brgyAnchors}|${cityPattern}))`, 'i');
            const qcChunk = address.match(chunkRegex);
            if (qcChunk?.[1]) {
                address = qcChunk[1];
            }

            address = address.replace(/\b(EXT|EXTENSION|ST|STREET|ROAD|RD|AVE|AVENUE|DR|DRIVE)\s+([A-Z]{3,12})\s+(KINGSPOINT|BAGBAG|NOVALICHES|FAIRVIEW|COMMONWEALTH|BATASAN|GULOD|SAN BARTOLOME|TALIPAPA|PAYATAS|CUBAO|PROJECT [4678]|MATANDANG BALARA|PASONG TAMO|PASONG PUTIK|HOLY SPIRIT|TANDANG SORA|BAESA)\b/g, (_, prefix, middle, anchor) => {
                const allowed = ['NORTH', 'SOUTH', 'EAST', 'WEST', 'NEW', 'OLD'];
                return allowed.includes(String(middle || '').toUpperCase())
                    ? `${prefix} ${middle} ${anchor}`
                    : `${prefix} ${anchor}`;
            });

            // Noise-Canceling: Fix common misreads and PREVENT DOUBLING
            // This replaces any garbled QUEZON CITY at the end with a single clean one
            address = address.replace(/\b(?:QUEZON\s*)?(?:QUEZON\s*)?(?:CITY|C\s*ITY|C1TY|1TY|ITY|LITY|CTY)\b$/i, ' QUEZON CITY')
                             .replace(/\b(QUEZON)\s+\1\b/gi, '$1')
                             .replace(/\b(?:K\s*)?INGS?POINT\b/gi, 'KINGSPOINT')
                             .replace(/\b(?:B\s*)?AGBAG\b/gi, 'BAGBAG');

            address = address.replace(/\bQUEZON\s*CITY\b.*$/i, 'QUEZON CITY');

            const cityMatch = address.match(/^(.*?\bQUEZON\s*CITY\b)/i);
            if (cityMatch?.[1]) {
                address = cityMatch[1];
            }

            const segments = address.split(',').map((segment) => segment.trim()).filter(Boolean);
            const locationPattern = /\b(?:#?\d{1,4}|BLK|BLOCK|LOT|UNIT|BRGY|BARANGAY|SUBD|SUBDIVISION|ST(?:REET)?|ROAD|RD|AVE(?:NUE)?|EXT(?:ENSION)?|PUROK|SITIO|VILLAGE|PHASE|BAESA|BAGBAG|NOVALICHES|KINGSPOINT|FAIRVIEW|COMMONWEALTH|BATASAN|GULOD|TALIPAPA|PAYATAS|CUBAO|HOLY SPIRIT|TANDANG SORA|SAN BARTOLOME|PASONG TAMO|PASONG PUTIK|PROJECT [0-9]+)\b/i;
            const noisePattern = /\b(?:BLOOD|TYPE|SINGLE|MARRIED|WIDOWED|DIVORCED|SEPARATED|CARDHOLDER|CITIZEN|QCID|NAME|SEX|STATUS)\b/i;

            const cleanedSegments = segments.filter((segment) => {
                const hasLocationMarker = locationPattern.test(segment) || /QUEZON\s*CITY/i.test(segment);
                if (noisePattern.test(segment) && !hasLocationMarker) {
                    return false;
                }
                return hasLocationMarker;
            });

            if (cleanedSegments.length > 0) {
                address = cleanedSegments.join(', ');
            }

            // Force city suffix if it's missing but look like a QC address.
            if (!address.match(/QUEZON\s*CITY/i) && (address.match(new RegExp(brgyAnchors, 'i')) || address.match(/\d{1,4}\s+[A-Z]/))) {
                address = address.replace(/,\s*$/, '') + ', QUEZON CITY';
            }

            return address.replace(/\s+,/g, ',').replace(/,{2,}/g, ',').replace(/\s{2,}/g, ' ').trim();
        },

        digitsOnly(value) {
            return String(value || '').replace(/\D/g, '');
        },

        normalizeDigitLike(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/[OQDP]/g, '0')
                .replace(/[IL]/g, '1')
                .replace(/Z/g, '2')
                .replace(/S/g, '5')
                .replace(/B/g, '8')
                .replace(/G/g, '6');
        },

        formatQcIdDigits(digits) {
            const only = this.digitsOnly(digits);
            if (only.length === 12) return `00${only}`;
            if (only.length === 13) return `0${only}`;
            if (only.length === 14) return only;
            return '';
        },

        extractQcIdCandidatesFromText(text) {
            const source = this.normalizeDigitLike(text);
            const raw = [
                ...(source.match(/\b\d{3}\s*\d{3}\s*\d{6,8}\b/g) || []),
                ...(source.match(/\b\d{12,14}\b/g) || []),
                ...(source.match(/\b\d{3}\D{0,4}\d{3}\D{0,4}\d{6,8}\b/g) || []),
            ];

            const candidates = [];
            for (const item of raw) {
                const normalized = this.formatQcIdDigits(item);
                if (!normalized) continue;
                candidates.push(normalized);
            }

            return candidates;
        },

        chooseBestQcIdCandidate(initialValue, verificationText, ocrText) {
            const all = [
                ...this.extractQcIdCandidatesFromText(initialValue || ''),
                ...this.extractQcIdCandidatesFromText(verificationText || ''),
                ...this.extractQcIdCandidatesFromText(ocrText || ''),
            ];

            if (all.length === 0) return '';

            const counts = new Map();
            for (const candidate of all) {
                counts.set(candidate, (counts.get(candidate) || 0) + 1);
            }

            const ranked = [...counts.entries()].sort((a, b) => b[1] - a[1]);
            const top = ranked[0]?.[0] || '';
            if (!top) return '';

            const topDigits = this.digitsOnly(top);
            for (const [candidate] of ranked) {
                const digits = this.digitsOnly(candidate);
                if (digits.length !== 14 || topDigits.length !== 14 || digits === topDigits) continue;

                let diffCount = 0;
                let diffIndex = -1;
                for (let i = 0; i < 14; i += 1) {
                    if (digits[i] !== topDigits[i]) {
                        diffCount += 1;
                        diffIndex = i;
                    }
                }

                if (diffCount === 1 && [6, 7].includes(diffIndex)) {
                    const a = topDigits[diffIndex];
                    const b = digits[diffIndex];
                    if ((a === '0' || a === '1') && /[3689]/.test(b)) return top;
                    if ((b === '0' || b === '1') && /[3689]/.test(a)) return candidate;
                }
            }

            return top;
        },

        applyDateFallbacks(verification, ocrText) {
            const currentYear = new Date().getFullYear();
            const candidates = this.parseDateCandidates(`${verification?.normalized_text || ''}\n${ocrText || ''}`);

            const getYear = (value) => Number(String(value || '').slice(0, 4));
            const isBirthYear = (year) => year >= 1940 && year <= 2015;
            const isIssuedYear = (year) => year >= 2015 && year <= currentYear;
            const isValidYear = (year) => year > currentYear;

            const existingDob = this.normalizeDate(verification?.date_of_birth || this.signup.date_of_birth);
            const existingIssued = this.normalizeDate(verification?.date_issued || this.signup.date_issued);
            const existingValid = this.normalizeDate(verification?.valid_until || this.signup.valid_until);

            let dob = existingDob;
            let issued = existingIssued;
            let valid = existingValid;

            for (const date of candidates) {
                const year = getYear(date);
                if (!dob && isBirthYear(year)) {
                    dob = date;
                    continue;
                }
                if (!issued && isIssuedYear(year)) {
                    issued = date;
                    continue;
                }
                if (!valid && isValidYear(year)) {
                    valid = date;
                }
            }

            if (!issued || !valid) {
                const sorted = [...candidates].sort();
                if (!issued) {
                    issued = sorted.find((date) => isIssuedYear(getYear(date))) || issued || '';
                }
                if (!valid) {
                    valid = sorted.find((date) => isValidYear(getYear(date))) || valid || '';
                }
            }

            return { dob, issued, valid };
        },

        async scanSignupQcId() {
            this.scan.error = '';
            this.scan.status = '';
            this.scan.cameraError = '';

            if (this.scan.cameraOpen) {
                this.closeSignupCamera();
            }

            if (!this.scan.file) {
                this.scan.error = 'Upload your QC ID image first.';
                return;
            }

            this.scan.isProcessing = true;
            this.scan.status = 'Scanning QR code & reading QC ID image...';
            this.scan.idAssessment = 'Scanning...';
            this.scan.confidenceLabel = '—';
            this.scan.isVerified = false;
            this.scan.isQrVerified = null;
            this.scan.qrData = '';
            this.scan.qrIdNumber = '';
            this.signup.qcid_temp_upload = '';

            // Reset form fields to ensure a clean capture
            this.signup.ocr_text = '';
            this.signup.name = '';
            this.signup.qcid_number = '';
            this.signup.address = '';

            try {
                // Step 1: Decode QR code from the uploaded image
                const qrResult = await this.decodeQrFromImage(this.scan.file);
                this.scan.qrData = qrResult || '';

                if (qrResult) {
                    this.scan.status = 'QR code found! Verifying with server...';
                } else {
                    this.scan.status = 'No QR code found. Reading text via OCR...';
                }

                const formData = new FormData();
                formData.append('ocr_text', this.signup.ocr_text || '');
                formData.append('user_name', this.signup.name || '');
                formData.append('qcid_image', this.scan.file);
                formData.append('qr_data', this.scan.qrData);

                const controller = new AbortController();
                const verifyTimeoutId = setTimeout(() => controller.abort(), 70000);

                let response;
                try {
                    response = await fetch(window.signupQcidVerifyUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        },
                        signal: controller.signal,
                        body: formData,
                    });
                } finally {
                    clearTimeout(verifyTimeoutId);
                }

                if (!response.ok) {
                    throw new Error('Verification service is temporarily unavailable. Please try again.');
                }

                const contentType = (response.headers.get('content-type') || '').toLowerCase();
                if (!contentType.includes('application/json')) {
                    throw new Error('Verification service returned an unexpected response. Please try again.');
                }

                const payload = await response.json();
                const verification = payload?.verification || {};
                const assessment = verification?.id_assessment || (payload?.success ? 'Verified' : 'INVALID');
                const isVerified = payload?.success && assessment === 'Verified';
                this.scan.idAssessment = assessment;
                this.scan.isVerified = !!isVerified;
                const confidenceScore = Number(verification?.confidence_score || 0);
                this.scan.confidenceLabel = isVerified
                    ? `${Math.max(0, Math.min(100, confidenceScore))}%`
                    : '—';

                if (!isVerified) {
                    this.signup.ocr_text = '';
                    this.signup.qcid_number = '';
                    this.signup.sex = '';
                    this.signup.civil_status = '';
                    this.signup.date_of_birth = '';
                    this.signup.date_issued = '';
                    this.signup.valid_until = '';
                    this.signup.address = '';
                    this.signup.qcid_temp_upload = '';

                    // Use the server's specific message or fake reason
                    const fakeReason = verification?.fake_reason || '';
                    this.scan.error = payload?.message || (
                        this.scan.idAssessment === 'Fake QC ID'
                            ? (fakeReason ? `FAKE ID DETECTED: ${fakeReason}` : 'This ID is FAKE. Please upload a genuine Quezon City Citizen ID.')
                            : 'This ID is INVALID. Only Quezon City Citizen IDs (QC IDs) are accepted.'
                    );
                    this.scan.status = this.scan.idAssessment === 'Fake QC ID' ? 'Fake QC ID detected.' : 'Invalid ID detected.';
                } else {
                    this.signup.ocr_text = payload?.ocr_text || '';
                    this.signup.qcid_temp_upload = payload?.qcid_temp_upload || '';
                    this.scan.status = 'QC ID verified and fields auto-filled. Please review before creating account.';
                    const addressSource = String(verification._address_source || '').toLowerCase();

                    if (verification.cardholder_name) {
                        this.signup.name = verification.cardholder_name;
                    }
                    const correctedId = this.chooseBestQcIdCandidate(
                        verification.id_number || '',
                        verification.normalized_text || '',
                        this.signup.ocr_text || '',
                    );
                    let ocrIdNumber = (verification.id_number || '').trim();
                    if (!ocrIdNumber) {
                        ocrIdNumber = (correctedId || '').trim();
                    }

                    // === QR Code Cross-Validation ===
                    // If QR data was decoded, extract the QC ID number from it
                    const qrIdNumber = payload?.qr_id_number || this.extractQcIdFromQr(this.scan.qrData);

                    if (verification.qr_validated) {
                        this.scan.qrIdNumber = qrIdNumber;
                        this.scan.isQrVerified = true;

                        // QR is authoritative — providing high-accuracy status message
                        if (verification.qr_profile_extracted && verification.qr_address_incomplete && verification.qr_name_missing_enye) {
                            this.scan.status = 'QR code verified. Address detail and Ñ spelling were completed using OCR for higher accuracy.';
                        } else if (verification.qr_profile_extracted && verification.qr_name_missing_enye) {
                            this.scan.status = 'QR code verified. Name accent (Ñ) was recovered from OCR for higher accuracy.';
                        } else if (verification.qr_profile_extracted && !verification.qr_address_incomplete) {
                            this.scan.status = 'QR code verified. All ID details auto-filled with 100% accuracy from QR data.';
                        } else if (verification.qr_profile_extracted && verification.qr_address_incomplete) {
                            this.scan.status = 'QR code verified. Address was completed using OCR for higher detail accuracy.';
                        } else if (ocrIdNumber && ocrIdNumber !== qrIdNumber) {
                            this.scan.status = `QR code verified. QC ID auto-corrected from "${ocrIdNumber}" to "${qrIdNumber}".`;
                        } else {
                            this.scan.status = 'QR code verified successfully.';
                        }
                    } else {
                        this.scan.isQrVerified = false;
                        if (this.scan.qrData) {
                            this.scan.status = 'QR code was detected, but its data could not be decoded. OCR fields were used instead.';
                        }
                    }

                    // Always prioritize QR results for any field provided
                    if (verification._cardholder_name_source === 'qr') this.signup.name = verification.cardholder_name;
                    else this.signup.name = (verification.cardholder_name || this.signup.name || '').trim();

                    if (verification._id_number_source === 'qr') {
                        this.signup.qcid_number = this.normalizeQcIdValue(verification.id_number || '');
                    } else {
                        this.signup.qcid_number = this.normalizeQcIdValue(qrIdNumber || ocrIdNumber || '');
                    }

                    if (verification.address) {
                        // Preserve high-detail addresses from QR or OCR fallback.
                        if (addressSource === 'qr' || addressSource === 'ocr_fallback') {
                            this.signup.address = String(verification.address).trim();
                        } else {
                            this.signup.address = this.improveAddress(verification.address);
                        }
                    }

                    if (verification.sex) {
                        // Server returns 'M'/'F', dropdown expects 'MALE'/'FEMALE'
                        const sexVal = verification.sex.toUpperCase();
                        if (sexVal === 'M' || sexVal === 'MALE') this.signup.sex = 'MALE';
                        else if (sexVal === 'F' || sexVal === 'FEMALE') this.signup.sex = 'FEMALE';
                        else if (['PREFER_NOT_TO_SAY', 'PREFER NOT TO SAY', 'UNKNOWN', 'UNSPECIFIED', 'N/A', 'NA', 'OTHER'].includes(sexVal)) this.signup.sex = 'PREFER_NOT_TO_SAY';
                    }
                    if (verification.civil_status) {
                        this.signup.civil_status = verification.civil_status;
                    }
                    if (verification.date_of_birth) {
                        const normalized = this.normalizeDate(verification.date_of_birth);
                        if (normalized) {
                            this.signup.date_of_birth = normalized;
                        }
                    }
                    if (verification.date_issued) {
                        const normalized = this.normalizeDate(verification.date_issued);
                        if (normalized) {
                            this.signup.date_issued = normalized;
                        }
                    }
                    if (verification.valid_until) {
                        const normalized = this.normalizeDate(verification.valid_until);
                        if (normalized) {
                            this.signup.valid_until = normalized;
                        }
                    }
                    const fallbackDates = this.applyDateFallbacks(verification, this.signup.ocr_text);
                    if (!this.signup.date_of_birth && fallbackDates.dob) {
                        this.signup.date_of_birth = fallbackDates.dob;
                    }
                    if (!this.signup.date_issued && fallbackDates.issued) {
                        this.signup.date_issued = fallbackDates.issued;
                    }
                    if (!this.signup.valid_until && fallbackDates.valid) {
                        this.signup.valid_until = fallbackDates.valid;
                    }

                    if (this.signup.address) {
                        if (addressSource !== 'qr' && addressSource !== 'ocr_fallback') {
                            this.signup.address = this.improveAddress(this.signup.address);
                        }
                    } else if (verification.normalized_text) {
                        this.signup.address = this.improveAddress(verification.normalized_text);
                    }
                }
            } catch (error) {
                if (error?.name === 'AbortError') {
                    this.scan.error = 'Verification timed out. Please retry with a clearer image and stable internet connection.';
                } else {
                    this.scan.error = error?.message || 'Unable to scan the QC ID image right now.';
                }
                this.scan.status = '';
            } finally {
                this.scan.isProcessing = false;
            }
        },
    };
}
    // Register service worker for PWA support
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    }

    document.addEventListener('keydown', function (e) {
        const key = (e.key || '').toLowerCase();
        if ((e.ctrlKey || e.metaKey) && key === 'k') {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('toggle-admin-login'));
        }
    });

    document.addEventListener('alpine:init', () => {
        Alpine.data('pwaInstallPrompt', () => ({
            deferredPrompt: null,
            isIos: false,
            showIosHint: false,
            
            init() {
                // Check if already installed
                if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                    return;
                }

                // Detect iOS for manual install instructions
                const userAgent = window.navigator.userAgent.toLowerCase();
                if (/iphone|ipad|ipod/.test(userAgent)) {
                    this.isIos = true;
                }

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.deferredPrompt = e;
                });
            },
            
            async promptInstall() {
                // If it's already installed, let the user know
                if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
                    alert("App is already installed!");
                    return;
                }

                if (this.deferredPrompt) {
                    this.deferredPrompt.prompt();
                    const { outcome } = await this.deferredPrompt.userChoice;
                    this.deferredPrompt = null;
                } else if (this.isIos) {
                    // Show iOS install hint since Safari doesn't support the prompt API
                    this.showIosHint = true;
                    setTimeout(() => { this.showIosHint = false; }, 8000);
                } else {
                    // PC / Alternative Desktop browser cross-platform fallback: download the shortcut exe
                    window.location.href = "{{ route('download.shortcut') }}";
                }
            }
        }));
    });
</script>
@endpush

@push('styles')
<style>
    .login-shell {
        background:
            radial-gradient(1200px 500px at 18% 10%, rgba(14, 165, 233, 0.14), transparent 60%),
            radial-gradient(900px 420px at 85% 75%, rgba(79, 70, 229, 0.18), transparent 65%),
            linear-gradient(135deg, #eef4ff 0%, #f6f8fc 45%, #ecfbf8 100%);
    }

    .login-led-orb {
        position: absolute;
        border-radius: 999px;
        filter: blur(10px);
        opacity: 0.55;
        animation: pulseGlow 6s ease-in-out infinite;
    }

    .login-led-orb-a {
        width: 320px;
        height: 320px;
        left: -90px;
        top: 45px;
        background: radial-gradient(circle at 35% 35%, rgba(6, 182, 212, 0.82), rgba(59, 130, 246, 0.2) 68%, transparent 72%);
    }

    .login-led-orb-b {
        width: 380px;
        height: 380px;
        right: -120px;
        bottom: -90px;
        animation-delay: 1.25s;
        background: radial-gradient(circle at 40% 40%, rgba(99, 102, 241, 0.8), rgba(45, 212, 191, 0.2) 70%, transparent 74%);
    }

    .login-led-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(99, 102, 241, 0.05) 1px, transparent 1px),
            linear-gradient(90deg, rgba(20, 184, 166, 0.05) 1px, transparent 1px);
        background-size: 28px 28px;
        mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.72) 12%, transparent 80%);
        -webkit-mask-image: radial-gradient(circle at center, rgba(0, 0, 0, 0.72) 12%, transparent 80%);
    }

    .login-card-wrap {
        animation: floatCard 8s ease-in-out infinite, loginCardEntrance 0.6s ease-out both;
    }

    @keyframes loginCardEntrance {
        from {
            opacity: 0;
            transform: translateY(24px) scale(0.97);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    @keyframes loginBrandEntrance {
        from {
            opacity: 0;
            transform: translateY(-16px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .login-brand-wrap {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.25rem 1rem;
        animation: loginBrandEntrance 0.5s ease-out both;
    }

    .login-brand-wrap::before {
        content: '';
        position: absolute;
        inset: -20px -35px;
        border-radius: 999px;
        background: radial-gradient(circle at 30% 40%, rgba(56, 189, 248, 0.35), rgba(79, 70, 229, 0.22) 45%, rgba(20, 184, 166, 0.2) 75%, transparent 100%);
        filter: blur(14px);
        animation: pulseGlow 5.5s ease-in-out infinite;
    }

    .login-brand-logo {
        position: relative;
        filter: drop-shadow(0 10px 18px rgba(76, 82, 235, 0.28));
    }

    .login-neon-card {
        position: relative;
    }

    .login-neon-card::before {
        content: '';
        position: absolute;
        inset: 0;
        border-radius: 1.5rem;
        padding: 1px;
        background: linear-gradient(120deg, rgba(59, 130, 246, 0.35), rgba(45, 212, 191, 0.28), rgba(99, 102, 241, 0.35));
        -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
        -webkit-mask-composite: xor;
        mask-composite: exclude;
        pointer-events: none;
    }

    .signup-scroll-area {
        scrollbar-width: thin;
        scrollbar-color: #99a6ff #eef2ff;
    }

    .signup-scroll-area::-webkit-scrollbar {
        width: 8px;
    }

    .signup-scroll-area::-webkit-scrollbar-track {
        background: #eef2ff;
        border-radius: 999px;
    }

    .signup-scroll-area::-webkit-scrollbar-thumb {
        background: linear-gradient(180deg, #6366f1, #14b8a6);
        border-radius: 999px;
    }

    @keyframes pulseGlow {
        0%, 100% {
            opacity: 0.48;
            transform: scale(1);
        }
        50% {
            opacity: 0.72;
            transform: scale(1.08);
        }
    }

    @keyframes floatCard {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-4px);
        }
    }

    @media (max-width: 768px) {
        .login-led-orb-a {
            width: 230px;
            height: 230px;
            left: -80px;
            top: 80px;
        }

        .login-led-orb-b {
            width: 260px;
            height: 260px;
            right: -90px;
            bottom: -80px;
        }
    }
</style>
@endpush
@endsection
