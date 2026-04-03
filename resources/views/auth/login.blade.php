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
                <img src="/images/smartspace-logo.png" alt="SmartSpace" class="h-44 sm:h-48 md:h-56 lg:h-64 w-auto max-w-none logo-premium logo-glow-purple">
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

        <x-modals.auth.signup-error />

        @php
            $signupFields = [
                'name',
                'username',
                'email',
                'phone_number',
                'user_type',
                'employee_category',
                'course',
                'qcid_number',
                'sex',
                'civil_status',
                'date_of_birth',
                'date_issued',
                'valid_until',
                'address',
                'ocr_text',
                'qcid_image',
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
                                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">Forgot password?</a>
                            </div>
                            <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold transition-colors">
                                Login
                            </button>
                        </form>

                        <button type="button" @click="signupOpen = true" class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition-colors shadow-lg shadow-teal-700/20">
                            Sign Up
                        </button>


                        <div id="installPromptContainer" class="hidden">
                            <button id="installBtn" type="button" class="install-app-card group/install w-full relative overflow-hidden rounded-2xl border border-emerald-200/60 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-4 text-left transition-all duration-500 hover:border-emerald-300/80 hover:shadow-[0_0_30px_-5px_rgba(16,185,129,0.35)] active:scale-[0.98]">
                                <!-- Animated gradient glow background -->
                                <div class="absolute inset-0 rounded-2xl opacity-0 group-hover/install:opacity-100 transition-opacity duration-700 bg-[radial-gradient(ellipse_at_center,rgba(16,185,129,0.08)_0%,transparent_70%)]"></div>
                                <!-- Shimmer sweep -->
                                <div class="absolute inset-0 -translate-x-full group-hover/install:translate-x-full transition-transform duration-1000 ease-in-out bg-gradient-to-r from-transparent via-white/40 to-transparent"></div>
                                <div class="relative z-10 flex items-center gap-4">
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 shadow-lg shadow-emerald-500/25 group-hover/install:shadow-emerald-500/40 group-hover/install:scale-110 transition-all duration-500">
                                        <i class="fa-solid fa-download text-white text-lg group-hover/install:animate-bounce"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-bold text-gray-900 group-hover/install:text-emerald-700 transition-colors duration-300">Install SmartSpace</p>
                                        <p class="text-xs text-gray-500 group-hover/install:text-emerald-600/70 transition-colors duration-300 mt-0.5">Add to your device for quick access</p>
                                    </div>
                                    <div class="shrink-0 flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 group-hover/install:bg-emerald-500 group-hover/install:text-white transition-all duration-300">
                                        <i class="fa-solid fa-arrow-right text-xs"></i>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div id="iosInstallHint" class="hidden rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm text-blue-800">
                            Tap Share → Add to Home Screen
                        </div>
                    </div>
                        </div>
                    </div>
                </div>
            </div>

            <x-modals.auth.signup />

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
    username: @json(old('username', '')),
    user_type: @json(old('user_type', '')),
    employee_category: @json(old('employee_category', '')),
    course: @json(old('course', '')),
    qcid_number: @json(old('qcid_number', '')),
    sex: @json(old('sex', '')),
    civil_status: @json(old('civil_status', '')),
    date_of_birth: @json(old('date_of_birth', '')),
    date_issued: @json(old('date_issued', '')),
    valid_until: @json(old('valid_until', '')),
    address: @json(old('address', '')),
    ocr_text: @json(old('ocr_text', '')),
};
window.signupQcidVerifyUrl = @json(route('signup.qcid.verify'));
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
        scan: {
            file: null,
            previewUrl: '',
            isProcessing: false,
            error: '',
            status: '',
            idAssessment: '',
            confidenceLabel: '',
            isVerified: false,
            qrData: '',
            qrIdNumber: '',
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

            // Persistence: Sync to URL on change
            this.$watch('signupOpen', (val) => {
                const url = new URL(window.location.href);
                if (val) {
                    url.searchParams.set('auth', 'signup');
                } else {
                    url.searchParams.set('auth', 'login');
                }
                window.history.replaceState({}, '', url);
            });
        },

        validateAndSubmitSignup(formEl) {
            this.scan.error = '';

            if (this.scan.qrIdNumber) {
                const enteredId = (this.signup.qcid_number || '').replace(/\s+/g, '');
                const qrId = this.scan.qrIdNumber.replace(/\s+/g, '');
                if (enteredId !== qrId) {
                    this.scan.error = 'QC ID number mismatch! Your ID\'s QR code shows ' + this.scan.qrIdNumber + ', but the form has ' + this.signup.qcid_number + '. The QC ID number must match the QR code on your physical ID.';
                    return;
                }
            }

            formEl.submit();
        },

        onSignupQcImageChange(event) {
            const file = event.target?.files?.[0] || null;
            this.scan.file = file;
            this.scan.error = '';
            this.scan.status = '';
            this.scan.idAssessment = '';
            this.scan.confidenceLabel = '';
            this.scan.isVerified = false;
            this.scan.qrData = '';
            this.scan.qrIdNumber = '';

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

            if (!file) {
                this.scan.previewUrl = '';
                return;
            }

            if (!file.type.startsWith('image/')) {
                this.scan.error = 'Please upload an image file for QC ID scanning.';
                this.scan.file = null;
                this.scan.previewUrl = '';
                return;
            }

            if (this.scan.previewUrl) {
                URL.revokeObjectURL(this.scan.previewUrl);
            }
            this.scan.previewUrl = URL.createObjectURL(file);

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

                const img = new Image();
                img.onload = () => {
                    // Create a canvas to get pixel data
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    // Try multiple resolutions for better QR detection
                    const attempts = [
                        { w: img.width, h: img.height },           // Original size
                        { w: Math.min(img.width * 2, 4000), h: Math.min(img.height * 2, 4000) }, // Upscaled
                        { w: Math.round(img.width * 0.5), h: Math.round(img.height * 0.5) },     // Downscaled
                    ];

                    for (const size of attempts) {
                        canvas.width = size.w;
                        canvas.height = size.h;
                        ctx.drawImage(img, 0, 0, size.w, size.h);

                        const imageData = ctx.getImageData(0, 0, size.w, size.h);
                        const qrCode = jsQR(imageData.data, imageData.width, imageData.height, {
                            inversionAttempts: 'attemptBoth',
                        });

                        if (qrCode && qrCode.data) {
                            resolve(qrCode.data);
                            return;
                        }
                    }

                    resolve(null);
                };
                img.onerror = () => resolve(null);

                // Load image from file
                const reader = new FileReader();
                reader.onload = (e) => { img.src = e.target.result; };
                reader.onerror = () => resolve(null);
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
                return `${match[1]} ${match[2]} ${match[3]}`;
            }

            // Pattern: continuous 14-digit number
            match = text.match(/(\d{14})/);
            if (match) {
                const d = match[1];
                return `${d.substring(0,3)} ${d.substring(3,6)} ${d.substring(6,14)}`;
            }

            // Pattern: 13-digit number (missing leading zero)
            match = text.match(/(\d{13})/);
            if (match) {
                const d = '0' + match[1];
                return `${d.substring(0,3)} ${d.substring(3,6)} ${d.substring(6,14)}`;
            }

            // Try to extract from URL (e.g. https://qcid.quezon.gov.ph/verify/00500001257479)
            match = text.match(/\d{10,14}/);
            if (match) {
                const d = match[0].padStart(14, '0').substring(0, 14);
                return `${d.substring(0,3)} ${d.substring(3,6)} ${d.substring(6,14)}`;
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
                .replace(/\b(?:DATE ISSUED|VALID UNTIL|DATE OF BIRTH|DOB|CIVIL STATUS|SEX|SIGNATURE)\b/g, ' ')
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

            const chunkRegex = new RegExp(`([A-Z0-9,\\-.\s]{6,}?(?:${brgyAnchors}|${cityPattern}))`, 'i');
            const qcChunk = address.match(chunkRegex);
            if (qcChunk?.[1]) {
                address = qcChunk[1];
            }

            // Noise-Canceling: Fix common misreads and PREVENT DOUBLING
            // This replaces any garbled QUEZON CITY at the end with a single clean one
            address = address.replace(/\b(?:QUEZON\s*)?(?:QUEZON\s*)?(?:CITY|C\s*ITY|C1TY|1TY|ITY|LITY|CTY)\b$/i, ' QUEZON CITY')
                             .replace(/\b(QUEZON)\s+\1\b/gi, '$1')
                             .replace(/\b(?:K\s*)?INGS?POINT\b/gi, 'KINGSPOINT')
                             .replace(/\b(?:B\s*)?AGBAG\b/gi, 'BAGBAG');

            // Force city suffix if it's missing but look like a QC address
            // Only append if "QUEZON CITY" isn't already there
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
                candidates.push(`${normalized.slice(0, 3)} ${normalized.slice(3, 6)} ${normalized.slice(6, 14)}`);
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

                const base64Image = await this.getBase64(this.scan.file);

                const formData = new FormData();
                formData.append('ocr_text', this.signup.ocr_text || '');
                formData.append('user_name', this.signup.name || '');
                formData.append('qcid_image', this.scan.file);
                formData.append('qr_data', this.scan.qrData);

                const response = await fetch(window.signupQcidVerifyUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: formData,
                });

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
                    this.scan.status = 'QC ID verified and fields auto-filled. Please review before creating account.';

                    if (verification.cardholder_name) {
                        this.signup.name = verification.cardholder_name;
                    }
                    const correctedId = this.chooseBestQcIdCandidate(
                        verification.id_number || '',
                        verification.normalized_text || '',
                        this.signup.ocr_text || '',
                    );
                    let ocrIdNumber = correctedId || verification.id_number || '';

                    // === QR Code Cross-Validation ===
                    // If QR data was decoded, extract the QC ID number from it
                    const qrIdNumber = payload?.qr_id_number || this.extractQcIdFromQr(this.scan.qrData);

                    if (verification.qr_validated) {
                        this.scan.qrIdNumber = qrIdNumber;
                        this.scan.isQrVerified = true;

                        // QR is authoritative — providing high-accuracy status message
                        if (verification.qr_profile_extracted) {
                            this.scan.status = 'QR code verified. All ID details auto-filled with 100% accuracy from QR data.';
                        } else if (ocrIdNumber && ocrIdNumber !== qrIdNumber) {
                            this.scan.status = `QR code verified. QC ID auto-corrected from "${ocrIdNumber}" to "${qrIdNumber}".`;
                        } else {
                            this.scan.status = 'QR code verified successfully.';
                        }
                    } else {
                        this.scan.isQrVerified = false;
                    }

                    // Always prioritize QR results for any field provided
                    if (verification._cardholder_name_source === 'qr') this.signup.name = verification.cardholder_name;
                    else this.signup.name = (verification.cardholder_name || this.signup.name || '').trim();

                    if (verification._id_number_source === 'qr') this.signup.qcid_number = verification.id_number;
                    else this.signup.qcid_number = (qrIdNumber || ocrIdNumber || '').trim();

                    if (verification.address) {
                        // QR-sourced address is already accurate, skip cleanup
                        if (verification._address_source === 'qr') {
                            this.signup.address = verification.address;
                        } else {
                            this.signup.address = this.improveAddress(verification.address);
                        }
                    }

                    if (verification.sex) {
                        // Server returns 'M'/'F', dropdown expects 'MALE'/'FEMALE'
                        const sexVal = verification.sex.toUpperCase();
                        if (sexVal === 'M' || sexVal === 'MALE') this.signup.sex = 'MALE';
                        else if (sexVal === 'F' || sexVal === 'FEMALE') this.signup.sex = 'FEMALE';
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
                    if (verification.address) {
                        this.signup.address = this.improveAddress(verification.address);
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
                        this.signup.address = this.improveAddress(this.signup.address);
                    } else if (verification.normalized_text) {
                        this.signup.address = this.improveAddress(verification.normalized_text);
                    }
                }
            } catch (error) {
                this.scan.error = error?.message || 'Unable to scan the QC ID image right now.';
            } finally {
                this.scan.isProcessing = false;
            }
        },
    };
}
    let deferredInstallPrompt;
    const isMobileBrowser = /android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini/i.test(window.navigator.userAgent);
    const isIos = /iphone|ipad|ipod/i.test(window.navigator.userAgent);
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const iosInstallHint = document.getElementById('iosInstallHint');
    const installBtn = document.getElementById('installBtn');
    const installContainer = document.getElementById('installPromptContainer');
    const installToast = document.getElementById('installToast');

    const showInstallToast = (message) => {
        if (!installToast) {
            return;
        }

        installToast.textContent = message;
        installToast.classList.remove('hidden', 'opacity-0', 'translate-y-3');

        window.setTimeout(() => {
            installToast.classList.add('opacity-0', 'translate-y-3');
            window.setTimeout(() => {
                installToast.classList.add('hidden');
            }, 250);
        }, 2600);
    };

    const markAppInstalled = () => {
        try {
            window.localStorage.setItem('smartspace_pwa_installed', '1');
        } catch (error) {
            console.warn('Unable to persist install state', error);
        }
    };

    const isAppInstalled = () => {
        if (isStandalone) {
            return true;
        }

        try {
            return window.localStorage.getItem('smartspace_pwa_installed') === '1';
        } catch (error) {
            console.warn('Unable to read install state', error);
            return false;
        }
    };

    const updateInstallUiState = () => {
        const installed = isAppInstalled();

        // Show Install App button for everyone who hasn't installed yet
        if (installContainer) {
            installContainer.classList.toggle('hidden', installed);
        }
    };

    updateInstallUiState();

    if (isIos && !isStandalone && iosInstallHint) {
        iosInstallHint.classList.remove('hidden');
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredInstallPrompt = e;
        updateInstallUiState();
    });

    installBtn?.addEventListener('click', async () => {
        // Path 1: Native PWA install prompt is available
        if (deferredInstallPrompt) {
            deferredInstallPrompt.prompt();
            const { outcome } = await deferredInstallPrompt.userChoice;

            if (outcome === 'accepted') {
                markAppInstalled();
                showInstallToast('SmartSpace installed successfully!');
                updateInstallUiState();
            }

            deferredInstallPrompt = null;
            return;
        }

        // Path 2: iOS — show specific instructions
        if (isIos) {
            if (iosInstallHint) {
                iosInstallHint.classList.remove('hidden');
            }
            showInstallToast('Tap the Share button, then "Add to Home Screen".');
            return;
        }

        // Path 3: Desktop/Android — guide to browser install UI
        const isChromium = /chrome|chromium|edg|opr|opera/i.test(navigator.userAgent);

        if (isChromium) {
            showInstallToast('Click the install icon (⊕) in your browser\'s address bar, or use Menu → "Install SmartSpace"');
        } else {
            showInstallToast('Use your browser menu to add SmartSpace to your home screen.');
        }
    });

    window.addEventListener('appinstalled', () => {
        deferredInstallPrompt = null;
        markAppInstalled();
        updateInstallUiState();

        if (iosInstallHint) {
            iosInstallHint.classList.add('hidden');
        }

        showInstallToast('SmartSpace has been installed!');
    });

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
</script>

<div id="installToast" class="hidden fixed left-1/2 bottom-6 z-50 w-[90%] max-w-sm -translate-x-1/2 rounded-xl bg-emerald-600 px-4 py-3 text-center text-sm font-semibold text-white shadow-lg opacity-0 translate-y-3 transition-all duration-200"></div>
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
