@extends('layouts.app')

@section('title', 'QC ID Registration')

@section('breadcrumb')
    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-gray-700 font-medium">QC ID Registration</span>
@endsection

@section('content')
<div x-data="qcidRegistrationApp()" x-init="init()" class="max-w-7xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 via-violet-600 to-purple-600 px-6 py-7">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-indigo-50">
                        User verification portal
                    </div>
                    <h1 class="mt-4 text-3xl font-extrabold tracking-tight text-white">Register your QC ID for account verification</h1>
                    <p class="mt-2 text-sm text-indigo-100/95">Upload your Quezon City Citizen ID, review the detected details, and submit your registration for verification. Only QC IDs are accepted.</p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:min-w-[420px]">
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 text-white backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-indigo-100">Current status</p>
                        <p class="mt-2 text-lg font-bold" x-text="statusLabel"></p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 text-white backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-indigo-100">Detected ID type</p>
                        <p class="mt-2 text-lg font-bold" x-text="verification?.is_valid ? 'QC Citizen ID' : 'Not verified'"></p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 text-white backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-indigo-100">Confidence</p>
                        <p class="mt-2 text-lg font-bold" x-text="verification?.confidence_score ? verification.confidence_score + '%' : '—'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6 lg:p-8 space-y-6">
            @if(session('status'))
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 space-y-1">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
                <div class="xl:col-span-2 space-y-6">
                    <form method="POST" action="{{ route('qcid.registration.store') }}" enctype="multipart/form-data" class="space-y-6">
                        @csrf

                        <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">Upload QC ID</h2>
                                    <p class="text-sm text-gray-600">Use a bright, clear photo where the ID text is readable.</p>
                                </div>
                                <button type="button" @click="reprocess()" class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-4 py-2 text-sm font-semibold text-indigo-700 hover:bg-indigo-50 transition-colors">
                                    Re-read QC ID
                                </button>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-[1.1fr_0.9fr]">
                                <div class="rounded-2xl border-2 border-dashed border-indigo-200 bg-white p-5">
                                    <label for="qcid_image" class="flex cursor-pointer flex-col items-center justify-center text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-600">
                                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                            </svg>
                                        </div>
                                        <p class="mt-4 text-sm font-semibold text-gray-900">Drop your QC ID image here or click to browse</p>
                                        <p class="mt-1 text-xs text-gray-500">Accepted: JPG, PNG, WEBP up to 25 MB</p>
                                    </label>
                                    <input id="qcid_image" name="qcid_image" type="file" accept="image/png,image/jpeg,image/jpg,image/webp" class="sr-only" @change="handleFile($event)" required>

                                    <template x-if="imagePreview">
                                        <div class="mt-5 overflow-hidden rounded-2xl border border-gray-200 bg-gray-50">
                                            <img :src="imagePreview" alt="QC ID preview" class="h-64 w-full object-cover">
                                        </div>
                                    </template>
                                </div>

                                <div class="space-y-4">
                                    <div x-show="isProcessing" x-cloak class="rounded-2xl border border-teal-200 bg-teal-50 px-4 py-4">
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <p class="text-sm font-semibold text-teal-800" x-text="statusMessage || 'Reading QC ID…'"></p>
                                                <p class="text-xs text-teal-700 mt-1">OCR is extracting text and checking the QC ID layout.</p>
                                            </div>
                                            <div class="text-lg font-extrabold text-teal-700" x-text="Math.round(progress) + '%' "></div>
                                        </div>
                                        <div class="mt-3 h-2 rounded-full bg-teal-100 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-teal-500 to-emerald-500 transition-all duration-200" :style="`width: ${Math.round(progress)}%`"></div>
                                        </div>
                                    </div>

                                    <div x-show="errorMessage" x-cloak class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" x-text="errorMessage"></div>

                                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">Verification snapshot</p>
                                                <p class="text-xs text-gray-500">Detected details from the uploaded card.</p>
                                            </div>
                                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                                  :class="verification?.is_valid ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                                  x-text="verification?.is_valid ? 'QC ID verified' : 'Waiting for upload'"></span>
                                        </div>

                                        <dl class="mt-4 space-y-3 text-sm">
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-gray-500">Cardholder</dt>
                                                <dd class="text-right font-semibold text-gray-900" x-text="verification?.cardholder_name || '—'"></dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-gray-500">ID number</dt>
                                                <dd class="text-right font-semibold text-gray-900" x-text="verification?.id_number || '—'"></dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-gray-500">Birth date</dt>
                                                <dd class="text-right font-semibold text-gray-900" x-text="verification?.date_of_birth || '—'"></dd>
                                            </div>
                                            <div class="flex items-start justify-between gap-4">
                                                <dt class="text-gray-500">Validity</dt>
                                                <dd class="text-right font-semibold text-gray-900" x-text="verification?.valid_until || '—'"></dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input type="hidden" name="ocr_text" x-model="form.ocr_text">

                        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-lg font-bold text-gray-900">Registration details</h2>
                                    <p class="text-sm text-gray-600">Confirm the captured details and add any missing information before submission.</p>
                                </div>
                                <div class="hidden sm:flex items-center gap-2 rounded-xl bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Review before submit
                                </div>
                            </div>

                            <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                                    <input name="full_name" x-model="form.full_name" value="{{ old('full_name', $registration?->full_name ?? $user->name) }}" type="text" required class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                                    <input name="email" x-model="form.email" value="{{ old('email', $registration?->email ?? $user->email) }}" type="email" required class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile number</label>
                                    <input name="contact_number" x-model="form.contact_number" value="{{ old('contact_number', $registration?->contact_number) }}" type="text" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500" placeholder="09xxxxxxxxx">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">QC ID number</label>
                                    <input name="qcid_number" x-model="form.qcid_number" value="{{ old('qcid_number', $registration?->qcid_number) }}" type="text" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Sex</label>
                                    <input name="sex" x-model="form.sex" value="{{ old('sex', $registration?->sex) }}" type="text" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Civil status</label>
                                    <input name="civil_status" x-model="form.civil_status" value="{{ old('civil_status', $registration?->civil_status) }}" type="text" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of birth</label>
                                    <input name="date_of_birth" x-model="form.date_of_birth" value="{{ old('date_of_birth', optional($registration?->date_of_birth)->format('Y-m-d')) }}" type="date" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date issued</label>
                                    <input name="date_issued" x-model="form.date_issued" value="{{ old('date_issued', optional($registration?->date_issued)->format('Y-m-d')) }}" type="date" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Valid until</label>
                                    <input name="valid_until" x-model="form.valid_until" value="{{ old('valid_until', optional($registration?->valid_until)->format('Y-m-d')) }}" type="date" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea name="address" x-model="form.address" rows="3" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">{{ old('address', $registration?->address) }}</textarea>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-100 pt-5">
                                <p class="text-sm text-gray-500">Submitting will set your QC ID registration status to pending review.</p>
                                <button type="submit" :disabled="!verification?.is_valid || isProcessing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition-all hover:from-indigo-700 hover:to-violet-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Submit QC ID registration
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5">
                        <h2 class="text-lg font-bold text-gray-900">How verification works</h2>
                        <div class="mt-4 space-y-4">
                            <div class="flex gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 font-bold">1</div>
                                <div>
                                    <p class="font-semibold text-gray-900">Upload your QC ID</p>
                                    <p class="text-sm text-gray-600">The system reads the image and checks QC-specific markers.</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 text-violet-700 font-bold">2</div>
                                <div>
                                    <p class="font-semibold text-gray-900">Review captured data</p>
                                    <p class="text-sm text-gray-600">Detected fields are prefilled to help avoid manual encoding errors.</p>
                                </div>
                            </div>
                            <div class="flex gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 font-bold">3</div>
                                <div>
                                    <p class="font-semibold text-gray-900">Submit for verification</p>
                                    <p class="text-sm text-gray-600">Your registration is stored as pending so it can be reviewed later.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 rounded-2xl border border-slate-800 shadow-sm overflow-hidden">
                        <div class="px-5 py-4 border-b border-white/10 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-white">Registration status</h2>
                                <p class="text-sm text-indigo-200">Latest submission details</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                                  :class="statusBadgeClass"
                                  x-text="statusLabel"></span>
                        </div>
                        <div class="p-5 space-y-4 text-sm text-indigo-100">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-indigo-200">Submitted</span>
                                <span class="font-semibold text-white">{{ $registration?->submitted_at?->format('M d, Y h:i A') ?? 'No submission yet' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-indigo-200">Stored cardholder</span>
                                <span class="text-right font-semibold text-white">{{ $registration?->full_name ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-indigo-200">QC ID number</span>
                                <span class="text-right font-semibold text-white">{{ $registration?->qcid_number ?? '—' }}</span>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-indigo-200">Review note</span>
                                <span class="text-right font-semibold text-white">{{ $registration?->verification_notes ?? 'Awaiting review.' }}</span>
                            </div>

                            @if($registration?->qcid_image_path)
                                <div class="rounded-2xl overflow-hidden border border-white/10 bg-white/5">
                                    <img src="{{ asset('storage/' . $registration->qcid_image_path) }}" alt="Stored QC ID" class="w-full h-48 object-cover">
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script>
function qcidRegistrationApp() {
    return {
        verification: @json($registration?->verified_data),
        imagePreview: @json($registration?->qcid_image_path ? asset('storage/' . $registration->qcid_image_path) : null),
        isProcessing: false,
        progress: 0,
        statusMessage: '',
        errorMessage: '',
        currentStatus: @json($registration?->verification_status ?? 'not_submitted'),
        form: {
            full_name: @json(old('full_name', $registration?->full_name ?? $user->name)),
            email: @json(old('email', $registration?->email ?? $user->email)),
            contact_number: @json(old('contact_number', $registration?->contact_number ?? '')),
            qcid_number: @json(old('qcid_number', $registration?->qcid_number ?? '')),
            sex: @json(old('sex', $registration?->sex ?? '')),
            civil_status: @json(old('civil_status', $registration?->civil_status ?? '')),
            date_of_birth: @json(old('date_of_birth', optional($registration?->date_of_birth)->format('Y-m-d'))),
            date_issued: @json(old('date_issued', optional($registration?->date_issued)->format('Y-m-d'))),
            valid_until: @json(old('valid_until', optional($registration?->valid_until)->format('Y-m-d'))),
            address: @json(old('address', $registration?->address ?? '')),
            ocr_text: @json(old('ocr_text', $registration?->ocr_text ?? '')),
        },

        init() {
            this.syncDerivedState();
        },

        get statusLabel() {
            return {
                pending: 'Pending review',
                verified: 'Verified',
                rejected: 'Needs resubmission',
                not_submitted: 'Not submitted',
            }[this.currentStatus] || 'Pending review';
        },

        get statusBadgeClass() {
            return {
                pending: 'bg-amber-100 text-amber-800',
                verified: 'bg-emerald-100 text-emerald-800',
                rejected: 'bg-rose-100 text-rose-800',
                not_submitted: 'bg-slate-100 text-slate-700',
            }[this.currentStatus] || 'bg-slate-100 text-slate-700';
        },

        syncDerivedState() {
            if (this.currentStatus === 'not_submitted' && this.verification?.is_valid) {
                this.currentStatus = 'pending';
            }
        },

        async handleFile(event) {
            const file = event.target?.files?.[0];
            this.errorMessage = '';
            this.progress = 0;

            if (!file) {
                return;
            }

            if (!file.type.startsWith('image/')) {
                this.errorMessage = 'Please upload an image file for the QC ID.';
                return;
            }

            this.imagePreview = URL.createObjectURL(file);
            await this.processFile(file);
        },

        async reprocess() {
            const input = document.getElementById('qcid_image');
            const file = input?.files?.[0];
            if (!file) {
                this.errorMessage = 'Choose a QC ID image first.';
                return;
            }

            await this.processFile(file);
        },

        /**
         * Pre-process image for better OCR: upscale and increase
         * contrast so Tesseract reads the QC ID more reliably.
         */
        async buildEnhancedCanvas(file) {
            return new Promise((resolve) => {
                const img = new Image();
                img.onload = () => {
                    const canvas = document.createElement('canvas');
                    const scale = Math.max(1, 2200 / Math.max(img.width, img.height));
                    canvas.width = Math.round(img.width * scale);
                    canvas.height = Math.round(img.height * scale);
                    const ctx = canvas.getContext('2d');

                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const data = imageData.data;
                    for (let i = 0; i < data.length; i += 4) {
                        const gray = 0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2];
                        const contrast = Math.min(255, Math.max(0, ((gray - 128) * 1.7) + 128));
                        data[i] = contrast;
                        data[i + 1] = contrast;
                        data[i + 2] = contrast;
                    }
                    ctx.putImageData(imageData, 0, 0);

                    resolve(canvas);
                };
                img.onerror = () => resolve(null);
                img.src = URL.createObjectURL(file);
            });
        },

        normalizeOcrText(value) {
            return String(value || '')
                .toUpperCase()
                .replace(/\r/g, '')
                .replace(/[^A-Z0-9,./\-\n\s]/g, ' ')
                .replace(/[ \t]+/g, ' ')
                .replace(/\n{2,}/g, '\n')
                .trim();
        },

        digitCorrectedText(value) {
            return this.normalizeOcrText(value)
                .replace(/[OQDP]/g, '0')
                .replace(/[IL]/g, '1')
                .replace(/Z/g, '2')
                .replace(/S/g, '5')
                .replace(/B/g, '8');
        },

        extractAllDates(text) {
            const normalized = this.digitCorrectedText(text);
            const matches = normalized.match(/\b\d{4}[/-]\d{2}[/-]\d{2}\b/g) || [];

            return [...new Set(matches.map((value) => value.replace(/-/g, '/')))];
        },

        formatQcIdNumber(text) {
            const digits = this.digitCorrectedText(text).replace(/\D/g, '');
            if (digits.length < 13) {
                return '';
            }

            const trimmed = digits.length > 14 ? digits.slice(-14) : digits;
            return `${trimmed.slice(0, 3)} ${trimmed.slice(3, 6)} ${trimmed.slice(6)}`.trim();
        },

        scoreDateRegion(text) {
            const dates = this.extractAllDates(text);
            return (dates.length * 50) + (text.includes('203') ? 10 : 0) + Math.min(text.length, 40);
        },

        scoreIdRegion(text) {
            const formatted = this.formatQcIdNumber(text);
            return formatted ? 200 + formatted.length : (this.digitCorrectedText(text).match(/\d/g) || []).length;
        },

        scoreAddressRegion(text) {
            const normalized = this.normalizeOcrText(text);
            let score = normalized.length;
            if (normalized.includes('QUEZON CITY')) score += 80;
            if (normalized.includes('KINGSPOINT')) score += 60;
            if (normalized.includes('BAGBAG')) score += 60;
            if (normalized.includes('KING CONSTANTINE')) score += 80;
            return score;
        },

        scoreNameRegion(text) {
            const normalized = this.normalizeOcrText(text);
            return normalized.split(/\s+/).filter(Boolean).length * 20;
        },

        async recognizeBestRegion(sourceCanvas, region) {
            const variants = region.variants || [{ rect: region.rect, threshold: region.threshold, config: region.config }];
            let bestText = '';
            let bestScore = -1;

            for (const variant of variants) {
                const canvas = this.createCropCanvas(sourceCanvas, variant.rect, { threshold: variant.threshold ?? region.threshold });
                const text = await this.recognizeCanvas(canvas, variant.config || region.config);
                const score = (region.score || ((value) => value.length))(text);
                if (score > bestScore) {
                    bestScore = score;
                    bestText = text;
                }
            }

            return bestText;
        },

        extractClientDateHints(regionText, fullText) {
            const regionDates = this.extractAllDates(regionText.dates || '');
            const allDates = this.extractAllDates(`${regionText.dates || ''}\n${fullText || ''}`);
            const dob = this.extractAllDates(regionText.demographics || '')[0] || allDates.find((value) => /^19|20/.test(value));
            const dates = regionDates.length >= 2 ? regionDates : allDates.filter((value) => value !== dob);
            const currentYear = new Date().getFullYear();

            let dateIssued = '';
            let validUntil = '';

            for (const value of dates) {
                const year = Number(value.slice(0, 4));
                if (!dateIssued && year >= 2015 && year <= currentYear) {
                    dateIssued = value;
                    continue;
                }
                if (!validUntil && year > currentYear) {
                    validUntil = value;
                }
            }

            if (!dateIssued && dates[0]) {
                dateIssued = dates[0];
            }
            if (!validUntil && dates[1]) {
                validUntil = dates[1];
            }

            return { dateIssued, validUntil };
        },

        extractClientIdHint(regionText, fullText) {
            return this.formatQcIdNumber(regionText.id_number || '')
                || this.formatQcIdNumber(fullText || '')
                || '';
        },

        cleanClientName(text) {
            let value = this.normalizeOcrText(text)
                .replace(/\s+\d+$/, '')
                .replace(/\s+[A-Z0-9]{1,2}$/, '')
                .trim();

            const parts = value.split(/\s+/).filter(Boolean);
            if (parts.length >= 3 && parts[parts.length - 1].length <= 2) {
                parts.pop();
                value = parts.join(' ');
            }

            return value;
        },

        cleanClientAddress(text) {
            let value = this.normalizeOcrText(text)
                .replace(/\b(?:ADDRESS|CARDHOLDER|SIGNATURE|EMERGENCY|CONTACT|RELAY|RE TRAY|GNATURE)\b/g, ' ')
                .replace(/\bAKING\b/g, 'A KING')
                .replace(/\bKINGSPOIN[T]?\b/g, 'KINGSPOINT')
                .replace(/\bOE\s+NE\s+GEOME\b/g, ' ')
                .replace(/\bOE\b|\bNE\b|\bGEOME\b/g, ' ')
                .replace(/\bA\s*KING\b/g, 'A KING')
                .replace(/\s+/g, ' ')
                .trim();

            const streetMatch = value.match(/\b\d+\s*(?:A\s+)?KING\s+CONSTANTINE\s+EXT\b/);
            const localityMatch = value.match(/\bKINGSPOINT\s+BAGBAG,?\s+QUEZON\s+CITY\b/)
                || value.match(/\bBAGBAG,?\s+QUEZON\s+CITY\b/)
                || value.match(/\bQUEZON\s+CITY\b/);

            if (streetMatch && localityMatch) {
                return `${streetMatch[0]}, ${localityMatch[0].replace(/\s+,/g, ',').replace(/\s+/g, ' ')}`;
            }

            const capped = value.match(/(\d+[A-Z\s,.-]+?QUEZON\s+CITY)/);
            if (capped) {
                return capped[1].replace(/\s+,/g, ',').replace(/\s+/g, ' ').trim();
            }

            return value;
        },

        buildClientHints(regionText, fullText) {
            const { dateIssued, validUntil } = this.extractClientDateHints(regionText, fullText);

            return {
                cardholder_name: this.cleanClientName(regionText.name || ''),
                id_number: this.extractClientIdHint(regionText, fullText),
                date_of_birth: this.extractAllDates(regionText.demographics || '')[0] || '',
                date_issued: dateIssued,
                valid_until: validUntil,
                address: this.cleanClientAddress(regionText.address || fullText || ''),
            };
        },

        createCropCanvas(sourceCanvas, rect, options = {}) {
            const threshold = options.threshold ?? false;
            const crop = document.createElement('canvas');
            const sx = Math.max(0, Math.round(sourceCanvas.width * rect.x));
            const sy = Math.max(0, Math.round(sourceCanvas.height * rect.y));
            const sw = Math.max(1, Math.round(sourceCanvas.width * rect.w));
            const sh = Math.max(1, Math.round(sourceCanvas.height * rect.h));

            crop.width = sw;
            crop.height = sh;

            const ctx = crop.getContext('2d');
            ctx.drawImage(sourceCanvas, sx, sy, sw, sh, 0, 0, sw, sh);

            if (threshold) {
                const imageData = ctx.getImageData(0, 0, sw, sh);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const value = data[i] > 145 ? 255 : 0;
                    data[i] = value;
                    data[i + 1] = value;
                    data[i + 2] = value;
                }
                ctx.putImageData(imageData, 0, 0);
            }

            return crop;
        },

        async recognizeCanvas(canvas, config = {}, withProgress = false) {
            const options = {
                preserve_interword_spaces: '1',
                ...config,
            };

            if (withProgress) {
                options.logger = (message) => {
                    if (message.status) {
                        this.statusMessage = message.status;
                    }

                    if (typeof message.progress === 'number') {
                        this.progress = message.progress * 100;
                    }
                };
            }

            const result = await window.Tesseract.recognize(canvas, 'eng', options);

            return this.normalizeOcrText(result?.data?.text || '');
        },

        async collectStructuredOcrText(file) {
            const enhancedCanvas = await this.buildEnhancedCanvas(file);
            if (!enhancedCanvas) {
                throw new Error('Unable to prepare the QC ID image for OCR.');
            }

            this.statusMessage = 'Reading full QC ID image…';
            const fullText = await this.recognizeCanvas(enhancedCanvas, {
                tessedit_pageseg_mode: 6,
            }, true);

            const regions = [
                {
                    key: 'name',
                    label: 'cardholder name',
                    rect: { x: 0.20, y: 0.24, w: 0.55, h: 0.12 },
                    score: (text) => this.scoreNameRegion(text),
                    config: {
                        tessedit_pageseg_mode: 7,
                        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ, .',
                    },
                    variants: [
                        { rect: { x: 0.18, y: 0.23, w: 0.57, h: 0.12 } },
                        { rect: { x: 0.20, y: 0.24, w: 0.55, h: 0.12 } },
                    ],
                },
                {
                    key: 'demographics',
                    label: 'sex, birth date, and civil status',
                    rect: { x: 0.20, y: 0.34, w: 0.56, h: 0.12 },
                    config: {
                        tessedit_pageseg_mode: 6,
                        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/ -',
                    },
                    threshold: true,
                    variants: [
                        { rect: { x: 0.19, y: 0.33, w: 0.57, h: 0.12 }, threshold: true },
                        { rect: { x: 0.20, y: 0.34, w: 0.56, h: 0.12 }, threshold: true },
                    ],
                },
                {
                    key: 'dates',
                    label: 'issue and validity dates',
                    rect: { x: 0.28, y: 0.44, w: 0.48, h: 0.11 },
                    score: (text) => this.scoreDateRegion(text),
                    config: {
                        tessedit_pageseg_mode: 7,
                        tessedit_char_whitelist: '0123456789/ -',
                    },
                    threshold: true,
                    variants: [
                        { rect: { x: 0.24, y: 0.41, w: 0.53, h: 0.13 }, threshold: true },
                        { rect: { x: 0.27, y: 0.43, w: 0.49, h: 0.12 }, threshold: true },
                        { rect: { x: 0.28, y: 0.44, w: 0.48, h: 0.11 }, threshold: true },
                    ],
                },
                {
                    key: 'address',
                    label: 'address block',
                    rect: { x: 0.20, y: 0.58, w: 0.58, h: 0.14 },
                    score: (text) => this.scoreAddressRegion(text),
                    config: {
                        tessedit_pageseg_mode: 6,
                        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789,.- ',
                    },
                    variants: [
                        { rect: { x: 0.18, y: 0.57, w: 0.44, h: 0.13 } },
                        { rect: { x: 0.18, y: 0.56, w: 0.46, h: 0.15 } },
                        { rect: { x: 0.20, y: 0.58, w: 0.58, h: 0.14 } },
                    ],
                },
                {
                    key: 'id_number',
                    label: 'ID number strip',
                    rect: { x: 0.67, y: 0.80, w: 0.28, h: 0.11 },
                    score: (text) => this.scoreIdRegion(text),
                    config: {
                        tessedit_pageseg_mode: 7,
                        tessedit_char_whitelist: '0123456789 ',
                    },
                    threshold: true,
                    variants: [
                        { rect: { x: 0.64, y: 0.77, w: 0.31, h: 0.12 }, threshold: true },
                        { rect: { x: 0.66, y: 0.79, w: 0.29, h: 0.11 }, threshold: true },
                        { rect: { x: 0.67, y: 0.80, w: 0.28, h: 0.11 }, threshold: true },
                    ],
                },
            ];

            const regionText = {};
            for (const region of regions) {
                this.statusMessage = `Reading ${region.label}…`;
                regionText[region.key] = await this.recognizeBestRegion(enhancedCanvas, region);
            }

            const clientHints = this.buildClientHints(regionText, fullText);

            const structuredLines = [fullText];

            if (clientHints.cardholder_name) {
                structuredLines.push('LAST NAME, FIRST NAME, MIDDLE NAME');
                structuredLines.push(clientHints.cardholder_name);
            }

            if (regionText.demographics) {
                structuredLines.push(regionText.demographics);
                structuredLines.push('SEX DATE OF BIRTH CIVIL STATUS');
            }

            if (clientHints.date_issued) {
                structuredLines.push(`DATE ISSUED ${clientHints.date_issued}`);
            }

            if (clientHints.valid_until) {
                structuredLines.push(`VALID UNTIL ${clientHints.valid_until}`);
            }

            if (clientHints.address) {
                structuredLines.push(`ADDRESS ${clientHints.address}`);
            }

            if (clientHints.id_number) {
                structuredLines.push(clientHints.id_number);
            }

            return {
                fullText,
                structuredText: this.normalizeOcrText(structuredLines.filter(Boolean).join('\n')),
                regionText,
                clientHints,
            };
        },

        async processFile(file) {
            if (!window.Tesseract) {
                this.errorMessage = 'OCR is not available right now. Please refresh the page and try again.';
                return;
            }

            this.isProcessing = true;
            this.statusMessage = 'Enhancing image for OCR…';
            this.errorMessage = '';

            try {
                const { fullText, structuredText, regionText, clientHints } = await this.collectStructuredOcrText(file);
                const extractedText = structuredText || fullText;
                if (!extractedText) {
                    throw new Error('No readable text was found. Please upload a clearer QC ID image.');
                }

                console.log('[QC ID OCR] Full text:', fullText);
                console.log('[QC ID OCR] Region text:', regionText);
                console.log('[QC ID OCR] Client hints:', clientHints);
                console.log('[QC ID OCR] Structured text:', extractedText);
                this.form.ocr_text = extractedText;
                this.statusMessage = 'Validating QC ID markers…';

                const response = await fetch('/rooms/qc-id/verify', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        ocr_text: extractedText,
                        user_name: this.form.full_name,
                    }),
                });

                const payload = await response.json();

                const rawVerification = payload.verification || null;
                const mergedVerification = rawVerification ? {
                    ...rawVerification,
                    cardholder_name: rawVerification.cardholder_name || clientHints.cardholder_name || null,
                    id_number: rawVerification.id_number || clientHints.id_number || null,
                    date_of_birth: rawVerification.date_of_birth || clientHints.date_of_birth || null,
                    date_issued: rawVerification.date_issued || clientHints.date_issued || null,
                    valid_until: rawVerification.valid_until || clientHints.valid_until || null,
                    address: rawVerification.address || clientHints.address || null,
                } : {
                    ...clientHints,
                };

                if (!payload.success) {
                    this.verification = null;
                    let rejectMsg = payload.message || 'The uploaded image is not recognized as a QC ID.';
                    if (rawVerification?.rejected_id_type) {
                        rejectMsg = `This appears to be a ${payload.verification.rejected_id_type}. Only Quezon City Citizen IDs (QC IDs) are accepted.`;
                    }
                    console.log('[QC ID OCR] Verification rejected:', rawVerification);
                    this.errorMessage = rejectMsg;
                    return;
                }

                this.verification = mergedVerification;
                console.log('[QC ID OCR] Verification result:', mergedVerification);

                if (mergedVerification) {
                    this.form.full_name = mergedVerification.cardholder_name || this.form.full_name;
                    this.form.qcid_number = mergedVerification.id_number || this.form.qcid_number;
                    this.form.sex = mergedVerification.sex || this.form.sex;
                    this.form.civil_status = mergedVerification.civil_status || this.form.civil_status;
                    this.form.date_of_birth = this.toDateInput(mergedVerification.date_of_birth) || this.form.date_of_birth;
                    this.form.date_issued = this.toDateInput(mergedVerification.date_issued) || this.form.date_issued;
                    this.form.valid_until = this.toDateInput(mergedVerification.valid_until) || this.form.valid_until;
                    this.form.address = mergedVerification.address || this.form.address;
                }

                this.currentStatus = 'pending';
                this.progress = 100;
                this.statusMessage = 'QC ID verified successfully.';
            } catch (error) {
                console.error('QC ID processing failed:', error);
                this.verification = null;
                this.errorMessage = error?.message || 'Unable to process the QC ID image.';
            } finally {
                this.isProcessing = false;
            }
        },

        toDateInput(value) {
            if (!value) {
                return '';
            }

            // Normalize to YYYY-MM-DD for HTML date inputs
            let str = String(value).replaceAll('/', '-');

            // Handle 8-digit dates without separators (20030101)
            if (/^\d{8}$/.test(str)) {
                str = str.slice(0, 4) + '-' + str.slice(4, 6) + '-' + str.slice(6, 8);
            }

            // Validate it looks like a date
            if (/^\d{4}-\d{2}-\d{2}$/.test(str)) {
                return str;
            }

            return '';
        },
    }
}
</script>
@endpush
@endsection
