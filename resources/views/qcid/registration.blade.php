@extends('layouts.app')

@section('title', 'QC ID Registration')

@section('breadcrumb')
    <i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
    <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700">Dashboard</a>
    <i class="w-4 h-4 text-gray-400 fa-icon fa-solid fa-chevron-right text-base leading-none"></i>
    <span class="text-gray-700 font-medium">QC ID Registration</span>
@endsection

@section('content')
@php
    $isStaffUser = $user->isStaff();
    $initialRegistrationEmail = $isStaffUser
        ? ''
        : old('email', $registration?->email ?? $user->email);
@endphp
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
                        <p class="mt-2 text-lg font-bold"
                           x-text="verification?.is_valid
                               ? 'QC Citizen ID'
                               : (verification?.rejected_id_type
                                   ? verification.rejected_id_type
                                   : (verification === null ? 'Not verified' : 'INVALID'))">
                        </p>
                    </div>
                    <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-4 text-white backdrop-blur-sm">
                        <p class="text-xs uppercase tracking-wide text-indigo-100">Confidence</p>
                        <p class="mt-2 text-lg font-bold" x-text="overallConfidenceLabel"></p>
                        <p class="mt-1 text-[11px] text-indigo-100/90" x-text="overallConfidenceHint"></p>
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
                                            <i class="h-8 w-8 fa-icon fa-solid fa-cloud-arrow-up text-3xl leading-none"></i>
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
                                    <!-- Green alert for fake/invalid ID removed: only red alert will show for both cases -->

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
                                    <i class="w-4 h-4 fa-icon fa-solid fa-circle-check text-base leading-none"></i>
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
                                    <input name="email" x-model="form.email" value="{{ $initialRegistrationEmail }}" type="email" :required="!isStaffUser" :placeholder="isStaffUser ? 'For student accounts only' : 'name@example.com'" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile number</label>
                                    <input name="contact_number" x-model="form.contact_number" value="{{ old('contact_number', $registration?->contact_number) }}" type="text" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500" placeholder="09xxxxxxxxx">
                                </div>
                                <div>
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <label class="block text-sm font-medium text-gray-700">QC ID number</label>
                                        <button type="button"
                                                @click="openConfidenceModal('qcid_number')"
                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                                :class="confidenceBadgeClass('qcid_number')"
                                                x-text="confidenceLabel('qcid_number')"></button>
                                    </div>
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
                                    <div class="mb-1 flex items-center justify-between gap-2">
                                        <label class="block text-sm font-medium text-gray-700">Address</label>
                                        <button type="button"
                                                @click="openConfidenceModal('address')"
                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold"
                                                :class="confidenceBadgeClass('address')"
                                                x-text="confidenceLabel('address')"></button>
                                    </div>
                                    <textarea name="address" x-model="form.address" rows="3" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">{{ old('address', $registration?->address) }}</textarea>
                                </div>
                            </div>

                            <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between border-t border-gray-100 pt-5">
                                <p class="text-sm text-gray-500">Submitting will set your QC ID registration status to pending review.</p>
                                <button type="submit" :disabled="!verification?.is_valid || isProcessing" class="inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-indigo-600/25 transition-all hover:from-indigo-700 hover:to-violet-700 disabled:cursor-not-allowed disabled:opacity-60">
                                    <i class="w-4 h-4 fa-icon fa-solid fa-check text-base leading-none"></i>
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

    <div x-show="showConfidenceModal"
         x-cloak
         class="modal p-4"
         :class="{ 'modal-open': showConfidenceModal }"
         @keydown.escape.window="closeConfidenceModal()">
        <div class="modal-box w-11/12 max-w-md p-0 bg-transparent border-0 shadow-none overflow-visible" @click.stop>
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl border border-slate-200 max-h-[88vh] overflow-y-auto">
                <div class="relative overflow-hidden rounded-xl border border-indigo-100 bg-gradient-to-br from-indigo-50 via-white to-fuchsia-50 p-4">
                <div class="pointer-events-none absolute -right-8 -top-8 h-24 w-24 rounded-full bg-indigo-200/50 blur-2xl"></div>
                <div class="pointer-events-none absolute -left-10 -bottom-10 h-24 w-24 rounded-full bg-fuchsia-200/40 blur-2xl"></div>
                <div class="relative flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-indigo-600">Extraction confidence</p>
                    <h3 class="mt-1 text-lg font-bold text-slate-900" x-text="confidenceFieldTitle()"></h3>
                </div>
                <button type="button" @click="closeConfidenceModal()" class="rounded-lg border border-slate-200 bg-white/80 px-2 py-1 text-slate-600 hover:bg-white">×</button>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Current status</p>
                <p class="mt-1 text-sm font-semibold" :class="confidenceTextClass(confidenceField)" x-text="confidenceLabel(confidenceField)"></p>
                <p class="mt-3 text-sm text-slate-700" x-text="confidenceReason(confidenceField)"></p>
            </div>

            <p class="mt-4 text-sm text-slate-600" x-show="confidenceNeedsManualEntry(confidenceField)">
                This field was not auto-filled because the text is unreadable or inconsistent across OCR passes. Please enter it manually and double-check against the physical ID.
            </p>

            <div class="mt-5 flex justify-end">
                <button type="button" @click="closeConfidenceModal()" class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">Got it</button>
            </div>
        </div>
        </div>
        <button type="button" class="modal-backdrop fixed inset-0 bg-black/40" @click="closeConfidenceModal()">close</button>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script type="application/json" id="qcid-vars">
{!! json_encode([
    'verification' => $registration?->verified_data,
    'imagePreview' => $registration?->qcid_image_path ? asset('storage/' . $registration->qcid_image_path) : null,
    'currentStatus' => $registration?->verification_status ?? 'not_submitted',
    'isStaffUser' => $isStaffUser,
    'form_full_name' => old('full_name', $registration?->full_name ?? $user->name),
    'form_email' => $initialRegistrationEmail,
    'form_contact_number' => old('contact_number', $registration?->contact_number ?? ''),
    'form_qcid_number' => old('qcid_number', $registration?->qcid_number ?? ''),
    'form_sex' => old('sex', $registration?->sex ?? ''),
    'form_civil_status' => old('civil_status', $registration?->civil_status ?? ''),
    'form_date_of_birth' => old('date_of_birth', optional($registration?->date_of_birth)->format('Y-m-d')),
    'form_date_issued' => old('date_issued', optional($registration?->date_issued)->format('Y-m-d')),
    'form_valid_until' => old('valid_until', optional($registration?->valid_until)->format('Y-m-d')),
    'form_address' => old('address', $registration?->address ?? ''),
    'form_ocr_text' => old('ocr_text', $registration?->ocr_text ?? ''),
]) !!}
</script>
<script>
const qcidVars = JSON.parse(document.getElementById('qcid-vars').textContent);
function qcidRegistrationApp() {
    // ...existing code...
    return {
        verification: verification,
        imagePreview: imagePreview,
        isProcessing: false,
        progress: 0,
        statusMessage: '',
        errorMessage: '',
        currentStatus: currentStatus,
        isStaffUser: isStaffUser,
        showConfidenceModal: false,
        hasShownAutoConfidenceModal: false,
        confidenceField: 'qcid_number',
        fieldConfidence: {
            qcid_number: {
                level: 'unknown',
                score: 0,
                reason: 'Upload and re-read the QC ID to evaluate extraction confidence.',
            },
            address: {
                level: 'unknown',
                score: 0,
                reason: 'Upload and re-read the QC ID to evaluate extraction confidence.',
            },
            date_issued: {
                level: 'unknown',
                score: 0,
                reason: 'Upload and re-read the QC ID to evaluate extraction confidence.',
            },
        },
        form: {
            full_name: form_full_name,
            email: form_email,
            contact_number: form_contact_number,
            qcid_number: form_qcid_number,
            sex: form_sex,
            civil_status: form_civil_status,
            date_of_birth: form_date_of_birth,
            date_issued: form_date_issued,
            valid_until: form_valid_until,
            address: form_address,
            ocr_text: form_ocr_text,
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

        get overallConfidenceScore() {
            const base = Number(this.verification?.confidence_score || 0);
            let penalty = 0;

            const levels = [
                this.fieldConfidence.qcid_number?.level,
                this.fieldConfidence.address?.level,
                this.fieldConfidence.date_issued?.level,
            ];

            for (const level of levels) {
                if (level === 'low') penalty += 30;
                else if (level === 'medium') penalty += 12;
            }

            if (!this.form.qcid_number) penalty += 25;
            if (!this.form.address) penalty += 20;
            if (!this.form.date_issued) penalty += 20;

            return Math.max(0, Math.min(100, base - penalty));
        },

        get overallConfidenceLabel() {
            if (!this.verification?.is_valid) {
                return '—';
            }

            return `${this.overallConfidenceScore}%`;
        },

        get overallConfidenceHint() {
            if (!this.verification?.is_valid) {
                return 'Awaiting verified OCR result';
            }

            if (this.overallConfidenceScore >= 85) return 'Strong extraction quality';
            if (this.overallConfidenceScore >= 60) return 'Review extracted fields';
            return 'Manual review required';
        },

        syncDerivedState() {
            if (this.currentStatus === 'not_submitted' && this.verification?.is_valid) {
                this.currentStatus = 'pending';
            }
        },

        openConfidenceModal(field) {
            this.confidenceField = field;
            this.showConfidenceModal = true;
        },

        closeConfidenceModal() {
            this.showConfidenceModal = false;
        },

        maybeOpenAutoConfidenceModal() {
            if (this.hasShownAutoConfidenceModal) {
                return;
            }

            const priorityFields = ['qcid_number', 'address', 'date_issued'];
            const firstLow = priorityFields.find((field) => this.fieldConfidence?.[field]?.level === 'low');
            if (!firstLow) {
                return;
            }

            this.hasShownAutoConfidenceModal = true;
            this.confidenceField = firstLow;
            this.showConfidenceModal = true;
        },

        confidenceFieldTitle() {
            if (this.confidenceField === 'address') {
                return 'Address extraction';
            }

            if (this.confidenceField === 'date_issued') {
                return 'Date issued extraction';
            }

            return 'QC ID number extraction';
        },

        confidenceLabel(field) {
            const level = this.fieldConfidence?.[field]?.level || 'unknown';
            return {
                high: 'High confidence',
                medium: 'Needs review',
                low: 'Manual entry required',
                unknown: 'Not evaluated',
            }[level] || 'Not evaluated';
        },

        confidenceBadgeClass(field) {
            const level = this.fieldConfidence?.[field]?.level || 'unknown';
            return {
                high: 'bg-emerald-100 text-emerald-700',
                medium: 'bg-amber-100 text-amber-700',
                low: 'bg-rose-100 text-rose-700',
                unknown: 'bg-slate-100 text-slate-600',
            }[level] || 'bg-slate-100 text-slate-600';
        },

        confidenceTextClass(field) {
            const level = this.fieldConfidence?.[field]?.level || 'unknown';
            return {
                high: 'text-emerald-700',
                medium: 'text-amber-700',
                low: 'text-rose-700',
                unknown: 'text-slate-700',
            }[level] || 'text-slate-700';
        },

        confidenceReason(field) {
            return this.fieldConfidence?.[field]?.reason || 'No extraction details available yet.';
        },

        confidenceNeedsManualEntry(field) {
            const level = this.fieldConfidence?.[field]?.level || 'unknown';
            return level === 'low';
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
                    const scale = Math.max(1, 3000 / Math.max(img.width, img.height));
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
            const matches = [
                ...(normalized.match(/\b\d{4}[/-]\d{2}[/-]\d{2}\b/g) || []),
                ...(normalized.match(/\b\d{4}[/-]\d{1}[/-]\d{1,2}\b/g) || []),
                ...(normalized.match(/\b\d{2}[/-]\d{2}[/-]\d{4}\b/g) || []),
                ...(normalized.match(/\b\d{1,2}[/-]\d{1,2}[/-]\d{4}\b/g) || []),
            ];

            const normalizedDates = [];
            for (const value of matches) {
                const parsed = this.parseDateCandidate(value);
                if (parsed && !normalizedDates.includes(parsed)) {
                    normalizedDates.push(parsed);
                }
            }

            return normalizedDates;
        },

        parseDateCandidate(value) {
            const raw = String(value || '').replace(/-/g, '/').replace(/\s+/g, '').trim();

            if (/^\d{8}$/.test(raw)) {
                const year = Number(raw.slice(0, 4));
                const month = Number(raw.slice(4, 6));
                const day = Number(raw.slice(6, 8));
                if (year >= 1900 && year <= 2099 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                    return `${String(year).padStart(4, '0')}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                }
            }

            let match = raw.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
            if (match) {
                const year = Number(match[1]);
                const month = Number(match[2]);
                const day = Number(match[3]);
                if (year >= 1900 && year <= 2099 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                    return `${String(year).padStart(4, '0')}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                }
            }

            match = raw.match(/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/);
            if (match) {
                const year = Number(match[1]);
                const month = Number(match[2]);
                const day = Number(match[3]);
                if (year >= 1900 && year <= 2099 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                    return `${String(year).padStart(4, '0')}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                }
            }

            // OCR case: YYYY/MMDD (missing separator between month/day)
            match = raw.match(/^(\d{4})\/(\d{4})$/);
            if (match) {
                const year = Number(match[1]);
                const month = Number(match[2].slice(0, 2));
                const day = Number(match[2].slice(2, 4));
                if (year >= 1900 && year <= 2099 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                    return `${String(year).padStart(4, '0')}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                }
            }

            // OCR case: YYYYMM/DD (missing separator between year/month)
            match = raw.match(/^(\d{6})\/(\d{2})$/);
            if (match) {
                const year = Number(match[1].slice(0, 4));
                const month = Number(match[1].slice(4, 6));
                const day = Number(match[2]);
                if (year >= 1900 && year <= 2099 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                    return `${String(year).padStart(4, '0')}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                }
            }

            match = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
            if (match) {
                const a = Number(match[1]);
                const b = Number(match[2]);
                const year = Number(match[3]);
                if (year < 1900 || year > 2099) {
                    return '';
                }

                const month = a > 12 ? b : a;
                const day = a > 12 ? a : b;
                if (month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                    return `${String(year).padStart(4, '0')}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                }
            }

            match = raw.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
            if (match) {
                const a = Number(match[1]);
                const b = Number(match[2]);
                const year = Number(match[3]);
                if (year < 1900 || year > 2099) {
                    return '';
                }

                const month = a > 12 ? b : a;
                const day = a > 12 ? a : b;
                if (month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                    return `${String(year).padStart(4, '0')}/${String(month).padStart(2, '0')}/${String(day).padStart(2, '0')}`;
                }
            }

            // OCR-garbled compact dates: YYYY/MDD or YYYY/MMD.
            if (/^\d{7}$/.test(raw)) {
                const year = Number(raw.slice(0, 4));
                if (year >= 1900 && year <= 2099) {
                    const monthA = Number(raw.slice(4, 5));
                    const dayA = Number(raw.slice(5, 7));
                    if (monthA >= 1 && monthA <= 9 && dayA >= 1 && dayA <= 31) {
                        return `${String(year).padStart(4, '0')}/${String(monthA).padStart(2, '0')}/${String(dayA).padStart(2, '0')}`;
                    }

                    const monthB = Number(raw.slice(4, 6));
                    const dayB = Number(raw.slice(6, 7));
                    if (monthB >= 1 && monthB <= 12 && dayB >= 1 && dayB <= 9) {
                        return `${String(year).padStart(4, '0')}/${String(monthB).padStart(2, '0')}/${String(dayB).padStart(2, '0')}`;
                    }
                }
            }

            return '';
        },

        extractStrictQcId(text) {
            const normalized = this.digitCorrectedText(text);

            const grouped = normalized.match(/\b(\d{3})\s*(\d{3})\s*(\d{6,8})\b/);
            if (grouped) {
                const digits = this.normalizeIdDigits(`${grouped[1]}${grouped[2]}${grouped[3]}`);
                return this.formatIdFromDigits(digits);
            }

            const candidates = normalized.match(/\b\d{12,14}\b/g) || [];
            if (candidates.length === 0) {
                return '';
            }

            const chosen = candidates[candidates.length - 1];
            const normalizedDigits = this.normalizeIdDigits(chosen);
            return this.formatIdFromDigits(normalizedDigits);
        },

        normalizeIdDigits(value) {
            const digits = this.digitCorrectedText(value).replace(/\D/g, '');
            if (digits.length === 12) {
                return `00${digits}`;
            }
            if (digits.length === 13) {
                return `0${digits}`;
            }
            if (digits.length === 14) {
                return digits;
            }

            return '';
        },

        extractStrictQcIdCandidates(text) {
            const normalized = this.digitCorrectedText(text);
            const candidates = [];

            const groupedMatches = normalized.match(/\b\d{3}\s*\d{3}\s*\d{6,8}\b/g) || [];
            for (const match of groupedMatches) {
                const digits = this.normalizeIdDigits(match);
                if (digits) {
                    candidates.push(digits);
                }
            }

            const compactMatches = normalized.match(/\b\d{12,14}\b/g) || [];
            for (const match of compactMatches) {
                const digits = this.normalizeIdDigits(match);
                if (digits) {
                    candidates.push(digits);
                }
            }

            // Additional fallback: detect grouped ID with noisy separators and 7-8 digit tail.
            const groupedLoose = normalized.match(/\b\d{3}[\s.\-]*\d{3}[\s.\-]*\d{7,8}\b/g) || [];
            for (const match of groupedLoose) {
                const digitsOnly = match.replace(/\D/g, '');
                const normalizedDigits = this.normalizeIdDigits(digitsOnly);
                if (normalizedDigits) {
                    candidates.push(normalizedDigits);
                }
            }

            const groupedVeryLoose = normalized.match(/\b\d{3}\D{0,4}\d{3}\D{0,4}\d{6,8}\b/g) || [];
            for (const match of groupedVeryLoose) {
                const digitsOnly = match.replace(/\D/g, '');
                const normalizedDigits = this.normalizeIdDigits(digitsOnly);
                if (normalizedDigits) {
                    candidates.push(normalizedDigits);
                }
            }

            if (candidates.length === 0) {
                return [];
            }

            return candidates.map((digits) => `${digits.slice(0, 3)} ${digits.slice(3, 6)} ${digits.slice(6, 14)}`);
        },

        digitsOnly(value) {
            return String(value || '').replace(/\D/g, '');
        },

        formatIdFromDigits(digits) {
            const pure = this.digitsOnly(digits);
            if (pure.length !== 14) {
                return '';
            }

            return `${pure.slice(0, 3)} ${pure.slice(3, 6)} ${pure.slice(6, 14)}`;
        },

        canonicalId(value) {
            if (!value) {
                return '';
            }

            const digits = this.normalizeIdDigits(value);
            return this.formatIdFromDigits(digits);
        },

        evaluateIdExtraction(rawId, hintId, fullText, idRegionText = '') {
            const regionCandidates = this.extractStrictQcIdCandidates(idRegionText || '');
            const bottomStrip = this.canonicalId(this.extractBottomStripIdCandidateFromText(fullText || ''));
            const looseTail = this.canonicalId(this.extractLooseIdFromTail(`${idRegionText || ''}\n${fullText || ''}`));
            const rawCandidate = this.canonicalId(rawId || '');
            const hintCandidate = this.canonicalId(hintId || '');

            const weightedCandidates = [
                ...regionCandidates,
                ...regionCandidates,
                bottomStrip,
                bottomStrip,
                looseTail,
                rawCandidate,
                hintCandidate,
            ].filter(Boolean);

            if (weightedCandidates.length === 0) {
                return {
                    value: null,
                    level: 'low',
                    score: 0,
                    reason: 'No readable QC ID number candidate was found in the ID strip.',
                };
            }

            const counts = new Map();
            for (const candidate of weightedCandidates) {
                counts.set(candidate, (counts.get(candidate) || 0) + 1);
            }

            const ranked = [...counts.entries()].sort((a, b) => b[1] - a[1]);
            const [bestId, bestScore] = ranked[0] || ['', 0];

            if ((bestScore || 0) < 2) {
                return {
                    value: null,
                    level: 'low',
                    score: bestScore || 0,
                    reason: 'QC ID number candidates were inconsistent, so auto-fill was skipped to avoid wrong data.',
                };
            }

            return {
                value: bestId || null,
                level: (bestScore || 0) >= 3 ? 'high' : 'medium',
                score: bestScore || 0,
                reason: (bestScore || 0) >= 3
                    ? 'QC ID number matched across multiple OCR passes.'
                    : 'QC ID number matched with limited agreement. Please review before submitting.',
            };
        },

        buildConsensusIdFromCandidates(candidates) {
            const digitCandidates = (candidates || [])
                .map((value) => this.normalizeIdDigits(value))
                .filter((value) => value.length === 14);

            if (digitCandidates.length === 0) {
                return '';
            }

            if (digitCandidates.length === 1) {
                return this.formatIdFromDigits(digitCandidates[0]);
            }

            const positions = Array.from({ length: 14 }, () => ({}));
            for (const candidate of digitCandidates) {
                for (let i = 0; i < 14; i += 1) {
                    const digit = candidate[i];
                    positions[i][digit] = (positions[i][digit] || 0) + 1;
                }
            }

            const consensus = positions
                .map((bucket) => Object.entries(bucket).sort((a, b) => b[1] - a[1])[0]?.[0] || '0')
                .join('');

            return this.formatIdFromDigits(consensus);
        },

        resolveAmbiguousIdCandidates(candidates) {
            const normalized = (candidates || [])
                .map((value) => this.normalizeIdDigits(value))
                .filter((value) => value.length === 14);

            if (normalized.length < 2) {
                return this.formatIdFromDigits(normalized[0] || '');
            }

            for (let i = 0; i < normalized.length; i += 1) {
                for (let j = i + 1; j < normalized.length; j += 1) {
                    const a = normalized[i];
                    const b = normalized[j];
                    let diffCount = 0;
                    let diffIndex = -1;

                    for (let k = 0; k < 14; k += 1) {
                        if (a[k] !== b[k]) {
                            diffCount += 1;
                            diffIndex = k;
                        }
                    }

                    if (diffCount === 1 && diffIndex === 6) {
                        if (a[6] === '0' && /[3689]/.test(b[6])) {
                            return this.formatIdFromDigits(a);
                        }
                        if (b[6] === '0' && /[3689]/.test(a[6])) {
                            return this.formatIdFromDigits(b);
                        }
                    }
                }
            }

            return '';
        },

        pickBestIdCandidate(candidates) {
            if (!candidates || candidates.length === 0) {
                return '';
            }

            const counts = new Map();
            for (const candidate of candidates) {
                counts.set(candidate, (counts.get(candidate) || 0) + 1);
            }

            return [...counts.entries()]
                .sort((a, b) => b[1] - a[1] || b[0].length - a[0].length)[0][0] || '';
        },

        extractBottomStripIdCandidateFromText(text) {
            const normalized = this.digitCorrectedText(text);
            const lines = normalized.split('\n').map((line) => line.trim()).filter(Boolean);
            const tail = lines.slice(-10).join('\n');
            const tailCandidates = this.extractStrictQcIdCandidates(tail);

            if (tailCandidates.length > 0) {
                return tailCandidates[tailCandidates.length - 1];
            }

            return '';
        },

        extractAnyIdFallback(text) {
            const normalized = this.digitCorrectedText(text);
            const matches = normalized.match(/\d{3}\D{0,5}\d{3}\D{0,5}\d{6,8}|\d{12,14}/g) || [];
            for (const value of matches.reverse()) {
                const digits = this.normalizeIdDigits(value);
                const formatted = this.formatIdFromDigits(digits);
                if (formatted) {
                    return formatted;
                }
            }

            return '';
        },

        formatQcIdNumber(text) {
            return this.extractStrictQcId(text);
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
                const canvas = this.createCropCanvas(sourceCanvas, variant.rect, {
                    threshold: variant.threshold ?? region.threshold,
                    scale: variant.scale ?? region.scale ?? 1,
                    sharpen: variant.sharpen ?? region.sharpen ?? false,
                });
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
            const dob = this.extractAllDates(regionText.demographics || '')[0] || allDates[0] || '';
            const dates = regionDates.length >= 2 ? regionDates : allDates.filter((value) => value !== dob);
            const currentYear = new Date().getFullYear();

            const issuedRegionDates = this.extractAllDates(regionText.date_issued || '');
            const validityRegionDates = this.extractAllDates(regionText.valid_until || '');

            const issuedByRegion = issuedRegionDates.find((value) => {
                const year = Number(value.slice(0, 4));
                return year >= 2015 && year <= currentYear;
            }) || '';

            const validByRegion = validityRegionDates.find((value) => Number(value.slice(0, 4)) > currentYear) || '';

            if (issuedByRegion || validByRegion) {
                return {
                    dateIssued: issuedByRegion,
                    validUntil: validByRegion,
                };
            }

            const pairFromLabelContext = this.extractIssueValidityByLabelContext(`${regionText.dates || ''}\n${fullText || ''}`);
            if (pairFromLabelContext.dateIssued || pairFromLabelContext.validUntil) {
                return pairFromLabelContext;
            }

            const nearestIssued = this.extractNearestDateToLabel(fullText || '', 'DATE\\s*ISSUED');
            const nearestValid = this.extractNearestDateToLabel(fullText || '', 'VALID\\s*UNTIL');
            if (nearestIssued || nearestValid) {
                return {
                    dateIssued: nearestIssued,
                    validUntil: nearestValid,
                };
            }

            let dateIssued = '';
            let validUntil = '';

            for (const value of dates) {
                const parsed = this.parseDateCandidate(value);
                if (!parsed) {
                    continue;
                }

                const year = Number(parsed.slice(0, 4));
                if (!dateIssued && year >= 2015 && year <= currentYear) {
                    dateIssued = parsed;
                    continue;
                }
                if (!validUntil && year > currentYear) {
                    validUntil = parsed;
                }
            }

            if (!dateIssued && dates[0]) {
                dateIssued = this.parseDateCandidate(dates[0]) || '';
            }
            if (!validUntil && dates[1]) {
                validUntil = this.parseDateCandidate(dates[1]) || '';
            }

            // If issue date is still missing, recover the latest non-future date
            // that is not DOB and not equal to validUntil.
            if (!dateIssued) {
                const issuedCandidates = allDates.filter((value) => {
                    if (!value || value === dob || value === validUntil) {
                        return false;
                    }
                    const year = Number(value.slice(0, 4));
                    return year >= 2010 && year <= currentYear;
                });

                if (issuedCandidates.length > 0) {
                    dateIssued = issuedCandidates.sort((a, b) => Number(b.slice(0, 4)) - Number(a.slice(0, 4)))[0];
                }
            }

            return { dateIssued, validUntil };
        },

        findBestIssueDateCandidate(dates, validUntil, dateOfBirth) {
            const currentYear = new Date().getFullYear();
            const valid = this.parseDateCandidate(validUntil || '');
            const dob = this.parseDateCandidate(dateOfBirth || '');

            const candidates = (dates || [])
                .map((value) => this.parseDateCandidate(value))
                .filter(Boolean)
                .filter((value) => value !== valid && value !== dob)
                .filter((value) => {
                    const year = Number(value.slice(0, 4));
                    return year >= 2010 && year <= currentYear;
                })
                .sort((a, b) => Number(b.slice(0, 4)) - Number(a.slice(0, 4)));

            return candidates[0] || '';
        },

        extractDateIssuedFromRegions(regionText, validUntil, dateOfBirth, fullText) {
            const regionCandidates = [
                ...this.extractAllDates(regionText?.date_issued || ''),
                ...this.extractAllDates(regionText?.dates || ''),
                ...this.extractAllDates(regionText?.demographics || ''),
            ];

            const fromRegions = this.findBestIssueDateCandidate(regionCandidates, validUntil, dateOfBirth);
            if (fromRegions) {
                return fromRegions;
            }

            const fromFull = this.findBestIssueDateCandidate(this.extractAllDates(fullText || ''), validUntil, dateOfBirth);
            return fromFull || '';
        },

        evaluateDateIssuedExtraction(dateIssued, validUntil, dateOfBirth) {
            const issued = this.parseDateCandidate(dateIssued || '');
            const valid = this.parseDateCandidate(validUntil || '');
            const dob = this.parseDateCandidate(dateOfBirth || '');
            const currentYear = new Date().getFullYear();

            if (!issued) {
                return {
                    level: 'low',
                    score: 0,
                    reason: 'Date issued could not be read clearly from the uploaded ID image.',
                };
            }

            const issuedYear = Number(issued.slice(0, 4));
            if (issuedYear < 2010 || issuedYear > currentYear) {
                return {
                    level: 'low',
                    score: 0,
                    reason: 'Detected date issued is outside expected range, so manual verification is needed.',
                };
            }

            if ((valid && issued >= valid) || (dob && issued === dob)) {
                return {
                    level: 'medium',
                    score: 1,
                    reason: 'Detected date issued may conflict with other dates. Please review before submitting.',
                };
            }

            return {
                level: 'high',
                score: 2,
                reason: 'Date issued was extracted with a valid and consistent date pattern.',
            };
        },

        extractIssueValidityByLabelContext(text) {
            const normalized = this.digitCorrectedText(text);
            const lines = normalized.split('\n').map((line) => line.trim()).filter(Boolean);
            let dateIssued = '';
            let validUntil = '';
            const currentYear = new Date().getFullYear();

            for (let i = 0; i < lines.length; i += 1) {
                if (!/DATE\s*ISSUED|VALID\s*UNTIL/.test(lines[i])) {
                    continue;
                }

                const scope = [lines[i - 1] || '', lines[i], lines[i + 1] || ''].join(' ');
                const extracted = this.extractAllDates(scope);

                for (const date of extracted) {
                    const year = Number(date.slice(0, 4));
                    if (!dateIssued && year >= 2015 && year <= currentYear) {
                        dateIssued = date;
                        continue;
                    }
                    if (!validUntil && year > currentYear) {
                        validUntil = date;
                    }
                }
            }

            return { dateIssued, validUntil };
        },

        extractNearestDateToLabel(text, labelPattern) {
            const normalized = this.digitCorrectedText(text);
            const labelRegex = new RegExp(labelPattern, 'i');
            const labelMatch = normalized.match(labelRegex);
            if (!labelMatch || typeof labelMatch.index !== 'number') {
                return '';
            }

            const labelOffset = labelMatch.index;
            const dateRegex = /\b\d{4}[/-]\d{2}[/-]\d{2}\b|\b\d{2}[/-]\d{2}[/-]\d{4}\b|\b\d{8}\b/g;
            const candidates = [];
            let match = dateRegex.exec(normalized);

            while (match) {
                const parsed = this.parseDateCandidate(match[0]);
                if (parsed) {
                    candidates.push({
                        value: parsed,
                        distance: Math.abs((match.index || 0) - labelOffset),
                    });
                }

                match = dateRegex.exec(normalized);
            }

            if (candidates.length === 0) {
                return '';
            }

            candidates.sort((a, b) => a.distance - b.distance);
            return candidates[0].value || '';
        },

        extractClientIdHint(regionText, fullText) {
            const candidates = [
                ...this.extractStrictQcIdCandidates(regionText.id_number || ''),
                ...this.extractStrictQcIdCandidates(fullText || ''),
                ...this.extractStrictQcIdCandidates((regionText.id_number || '') + '\n' + (fullText || '')),
            ];

            const ambiguityResolved = this.resolveAmbiguousIdCandidates(candidates);
            if (ambiguityResolved) {
                return ambiguityResolved;
            }

            return this.buildConsensusIdFromCandidates(candidates) || this.pickBestIdCandidate(candidates);
        },

        chooseBetterDateIssued(rawIssued, hintIssued, validUntil, dateOfBirth, fullText, regionText = {}) {
            const currentYear = new Date().getFullYear();
            const raw = this.parseDateCandidate(rawIssued || '');
            const hint = this.parseDateCandidate(hintIssued || '');
            const valid = this.parseDateCandidate(validUntil || '');
            const dob = this.parseDateCandidate(dateOfBirth || '');
            const allDates = this.extractAllDates(fullText || '');
            const nearestIssued = this.parseDateCandidate(this.extractNearestDateToLabel(fullText || '', 'DATE\\s*ISSUED') || '');
            const regionIssued = this.extractDateIssuedFromRegions(regionText, valid, dob, fullText || '');

            const isGoodIssued = (value) => {
                if (!value) return false;
                const year = Number(value.slice(0, 4));
                if (year < 2015 || year > currentYear) return false;
                if (dob && value === dob) return false;
                if (valid && value >= valid) return false;
                return true;
            };

            if (isGoodIssued(raw)) return raw;
            if (isGoodIssued(hint)) return hint;
            if (isGoodIssued(regionIssued)) return regionIssued;
            if (isGoodIssued(nearestIssued)) return nearestIssued;

            const candidates = allDates.filter((value) => isGoodIssued(value));
            if (candidates.length > 0) {
                return candidates
                    .sort((a, b) => Number(b.slice(0, 4)) - Number(a.slice(0, 4)))[0];
            }

            // Never allow issued date to equal the validity date.
            if (valid && raw === valid) {
                return hint && hint !== valid ? hint : '';
            }

            return raw || hint || regionIssued || nearestIssued || '';
        },

        chooseBetterId(rawId, hintId, fullText, idRegionText = '') {
            return this.evaluateIdExtraction(rawId, hintId, fullText, idRegionText).value || '';
        },

        evaluateAddressExtraction(rawAddress, hintAddress) {
            const clean = (value) => this.cleanClientAddress(value || '');
            const raw = clean(rawAddress);
            const hint = clean(hintAddress);

            const rawReliable = this.isReliableAddress(raw);
            const hintReliable = this.isReliableAddress(hint);

            if (!rawReliable && !hintReliable) {
                return {
                    value: null,
                    level: 'low',
                    score: 0,
                    reason: 'Address text was unreadable or incomplete, so auto-fill was skipped.',
                };
            }

            if (rawReliable && !hintReliable) {
                return {
                    value: raw,
                    level: 'medium',
                    score: 2,
                    reason: 'Address was extracted from one reliable OCR source. Please review before submitting.',
                };
            }

            if (hintReliable && !rawReliable) {
                return {
                    value: hint,
                    level: 'medium',
                    score: 2,
                    reason: 'Address was extracted from one reliable OCR source. Please review before submitting.',
                };
            }

            if (raw === hint) {
                return {
                    value: raw,
                    level: 'high',
                    score: 3,
                    reason: 'Address matched across multiple OCR sources.',
                };
            }

            const hasCity = (value) => /QUEZON\s+CITY/.test(value || '');
            const hasStreetCue = (value) => /\b(LOT|BLK|ST|STREET|AVE|ROAD|SUBD|BARANGAY|BRGY|PHASE|VILLAGE)\b/.test(value || '');

            const rawScore = (hasCity(raw) ? 3 : 0) + (hasStreetCue(raw) ? 2 : 0) + Math.min(raw.length, 120) / 120;
            const hintScore = (hasCity(hint) ? 3 : 0) + (hasStreetCue(hint) ? 2 : 0) + Math.min(hint.length, 120) / 120;

            return {
                value: hintScore >= rawScore ? hint : raw,
                level: 'medium',
                score: 2,
                reason: 'Address candidates were close but not identical. Please review before submitting.',
            };
        },

        chooseBetterAddress(rawAddress, hintAddress) {
            return this.evaluateAddressExtraction(rawAddress, hintAddress).value;
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
                .replace(/\b(?:ADDRESS|CARDHOLDER|SIGNATURE|EMERGENCY|CONTACT|RELAY|GNATURE|REPUBLIC OF THE PHILIPPINES|Q CITIZENCARD)\b/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();

            // Remove tiny OCR garbage prefixes before the actual address.
            value = value.replace(/^(?:[A-Z]{1,2}\s+){1,3}(?=(?:BLK|LOT|\d))/, '');

            // Keep the strongest chunk ending in QUEZON CITY when available.
            const qcChunk = value.match(/([A-Z0-9\s,.'\-]{10,}?QUEZON\s+CITY)/);
            if (qcChunk) {
                value = qcChunk[1];
            }

            value = value
                .split(/\s+/)
                .filter((token) => {
                    if (/^[A-Z]{11,}$/.test(token) && !/[AEIOU]/.test(token)) {
                        return false;
                    }

                    return true;
                })
                .join(' ');

            return value
                .replace(/\s+,/g, ',')
                .replace(/,{2,}/g, ',')
                .replace(/\s{2,}/g, ' ')
                .trim();
        },

        isReliableAddress(value) {
            const text = this.cleanClientAddress(value || '');
            if (!text) {
                return false;
            }

            if (!/QUEZON\s+CITY/.test(text)) {
                return false;
            }

            if (!/\b(LOT|BLK|ST|STREET|AVE|AVENUE|ROAD|RD|SUBD|BARANGAY|BRGY|PHASE|VILLAGE)\b/.test(text)) {
                return false;
            }

            const tokens = text.split(/\s+/).filter(Boolean);
            const shortTokens = tokens.filter((token) => token.length <= 2 && !/^\d+$/.test(token)).length;
            if (shortTokens >= 4) {
                return false;
            }

            return true;
        },

        buildClientHints(regionText, fullText) {
            const { dateIssued, validUntil } = this.extractClientDateHints(regionText, fullText);
            const anchoredAddress = this.extractAddressByCityAnchor(regionText.address || '')
                || this.extractAddressByCityAnchor(fullText || '');

            return {
                cardholder_name: this.cleanClientName(regionText.name || ''),
                id_number: this.extractClientIdHint(regionText, fullText),
                date_of_birth: this.extractAllDates(regionText.demographics || '')[0] || '',
                date_issued: dateIssued,
                valid_until: validUntil,
                address: this.cleanClientAddress(anchoredAddress || regionText.address || fullText || ''),
            };
        },

        createCropCanvas(sourceCanvas, rect, options = {}) {
            const threshold = options.threshold ?? false;
            const scale = Math.max(1, Number(options.scale || 1));
            const crop = document.createElement('canvas');
            const sx = Math.max(0, Math.round(sourceCanvas.width * rect.x));
            const sy = Math.max(0, Math.round(sourceCanvas.height * rect.y));
            const sw = Math.max(1, Math.round(sourceCanvas.width * rect.w));
            const sh = Math.max(1, Math.round(sourceCanvas.height * rect.h));

            crop.width = Math.max(1, Math.round(sw * scale));
            crop.height = Math.max(1, Math.round(sh * scale));

            const ctx = crop.getContext('2d');
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';
            ctx.drawImage(sourceCanvas, sx, sy, sw, sh, 0, 0, crop.width, crop.height);

            if (options.sharpen) {
                const imageData = ctx.getImageData(0, 0, crop.width, crop.height);
                const data = imageData.data;
                for (let i = 0; i < data.length; i += 4) {
                    const boosted = Math.min(255, Math.max(0, ((data[i] - 128) * 1.35) + 128));
                    data[i] = boosted;
                    data[i + 1] = boosted;
                    data[i + 2] = boosted;
                }
                ctx.putImageData(imageData, 0, 0);
            }

            if (threshold) {
                const imageData = ctx.getImageData(0, 0, crop.width, crop.height);
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

        extractLooseIdFromTail(text) {
            const normalized = this.digitCorrectedText(text);
            const lines = normalized.split('\n').map((line) => line.trim()).filter(Boolean);
            const tailLines = lines.slice(-12);

            for (const line of tailLines.reverse()) {
                const digits = line.replace(/\D/g, '');
                if (digits.length >= 12 && digits.length <= 16) {
                    const normalizedDigits = this.normalizeIdDigits(digits.slice(-14));
                    const formatted = this.formatIdFromDigits(normalizedDigits);
                    if (formatted) {
                        return formatted;
                    }
                }
            }

            return '';
        },

        extractAddressByCityAnchor(text) {
            const normalized = this.normalizeOcrText(text);
            const lines = normalized.split('\n').map((line) => line.trim()).filter(Boolean);
            if (!lines.length) {
                return '';
            }

            const cityIndex = lines.findIndex((line) => /QUEZON\s+CITY/.test(line));
            if (cityIndex < 0) {
                return '';
            }

            const start = Math.max(0, cityIndex - 2);
            const selected = lines.slice(start, cityIndex + 1)
                .filter((line) => !/REPUBLIC OF THE PHILIPPINES|Q CITIZENCARD|DATE ISSUED|VALID UNTIL|SEX|CIVIL STATUS|CARDHOLDER|SIGNATURE|LAST NAME|FIRST NAME|MIDDLE NAME/.test(line))
                .filter((line) => /QUEZON\s+CITY|\d|LOT|BLK|ST|STREET|AVE|ROAD|SUBD|BARANGAY|BRGY|PHASE|VILLAGE/.test(line));

            return selected.join(', ');
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

            // Sparse-text pass catches tiny date/value text blocks that line OCR can miss.
            const sparseText = await this.recognizeCanvas(enhancedCanvas, {
                tessedit_pageseg_mode: 11,
            });
            const combinedFullText = this.normalizeOcrText([fullText, sparseText].filter(Boolean).join('\n'));

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
                    key: 'date_issued',
                    label: 'date issued value',
                    rect: { x: 0.30, y: 0.42, w: 0.18, h: 0.08 },
                    score: (text) => this.scoreDateRegion(text),
                    config: {
                        tessedit_pageseg_mode: 7,
                        tessedit_char_whitelist: '0123456789/ -',
                    },
                    threshold: true,
                    variants: [
                        {
                            rect: { x: 0.20, y: 0.36, w: 0.34, h: 0.14 },
                            threshold: true,
                            config: {
                                tessedit_pageseg_mode: 6,
                                tessedit_char_whitelist: '0123456789/ -',
                            },
                        },
                        {
                            rect: { x: 0.22, y: 0.38, w: 0.30, h: 0.12 },
                            threshold: true,
                            config: {
                                tessedit_pageseg_mode: 6,
                                tessedit_char_whitelist: '0123456789/ -',
                            },
                        },
                        {
                            rect: { x: 0.24, y: 0.39, w: 0.28, h: 0.11 },
                            threshold: true,
                            config: {
                                tessedit_pageseg_mode: 6,
                                tessedit_char_whitelist: '0123456789/ -',
                            },
                        },
                        { rect: { x: 0.28, y: 0.40, w: 0.20, h: 0.09 }, threshold: true },
                        { rect: { x: 0.30, y: 0.42, w: 0.18, h: 0.08 }, threshold: true },
                        { rect: { x: 0.32, y: 0.43, w: 0.17, h: 0.08 }, threshold: true },
                    ],
                },
                {
                    key: 'valid_until',
                    label: 'valid until value',
                    rect: { x: 0.50, y: 0.42, w: 0.20, h: 0.08 },
                    score: (text) => this.scoreDateRegion(text),
                    config: {
                        tessedit_pageseg_mode: 7,
                        tessedit_char_whitelist: '0123456789/ -',
                    },
                    threshold: true,
                    variants: [
                        { rect: { x: 0.48, y: 0.40, w: 0.22, h: 0.09 }, threshold: true },
                        { rect: { x: 0.50, y: 0.42, w: 0.20, h: 0.08 }, threshold: true },
                        { rect: { x: 0.52, y: 0.43, w: 0.18, h: 0.08 }, threshold: true },
                    ],
                },
                {
                    key: 'address',
                    label: 'address block',
                    rect: { x: 0.20, y: 0.58, w: 0.58, h: 0.14 },
                    scale: 2.6,
                    sharpen: true,
                    score: (text) => this.scoreAddressRegion(text),
                    config: {
                        tessedit_pageseg_mode: 6,
                        tessedit_char_whitelist: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789,.- ',
                    },
                    variants: [
                        { rect: { x: 0.16, y: 0.54, w: 0.48, h: 0.18 }, scale: 2.8, sharpen: true },
                        { rect: { x: 0.18, y: 0.57, w: 0.44, h: 0.13 }, scale: 3, sharpen: true },
                        { rect: { x: 0.18, y: 0.56, w: 0.46, h: 0.15 }, scale: 2.8, sharpen: true },
                        { rect: { x: 0.20, y: 0.58, w: 0.58, h: 0.14 }, scale: 2.4, sharpen: true },
                    ],
                },
                {
                    key: 'id_number',
                    label: 'ID number strip',
                    rect: { x: 0.67, y: 0.80, w: 0.28, h: 0.11 },
                    scale: 3.6,
                    sharpen: true,
                    score: (text) => this.scoreIdRegion(text),
                    config: {
                        tessedit_pageseg_mode: 7,
                        tessedit_char_whitelist: '0123456789 ',
                    },
                    threshold: true,
                    variants: [
                        {
                            rect: { x: 0.56, y: 0.72, w: 0.40, h: 0.20 },
                            threshold: true,
                            scale: 4,
                            sharpen: true,
                            config: {
                                tessedit_pageseg_mode: 6,
                                tessedit_char_whitelist: '0123456789 ',
                            },
                        },
                        {
                            rect: { x: 0.58, y: 0.74, w: 0.38, h: 0.18 },
                            threshold: true,
                            scale: 3.8,
                            sharpen: true,
                            config: {
                                tessedit_pageseg_mode: 6,
                                tessedit_char_whitelist: '0123456789 ',
                            },
                        },
                        {
                            rect: { x: 0.60, y: 0.76, w: 0.35, h: 0.15 },
                            threshold: true,
                            scale: 3.6,
                            sharpen: true,
                            config: {
                                tessedit_pageseg_mode: 6,
                                tessedit_char_whitelist: '0123456789 ',
                            },
                        },
                        { rect: { x: 0.64, y: 0.77, w: 0.31, h: 0.12 }, threshold: true, scale: 3.4, sharpen: true },
                        { rect: { x: 0.66, y: 0.79, w: 0.29, h: 0.11 }, threshold: true, scale: 3.2, sharpen: true },
                        { rect: { x: 0.67, y: 0.80, w: 0.28, h: 0.11 }, threshold: true, scale: 3.2, sharpen: true },
                    ],
                },
            ];

            const regionText = {};
            for (const region of regions) {
                this.statusMessage = `Reading ${region.label}…`;
                regionText[region.key] = await this.recognizeBestRegion(enhancedCanvas, region);
            }

            const clientHints = this.buildClientHints(regionText, combinedFullText);

            const structuredLines = [combinedFullText];

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

            if (regionText.date_issued) {
                structuredLines.push(`DATE ISSUED ${regionText.date_issued}`);
            }

            if (clientHints.valid_until) {
                structuredLines.push(`VALID UNTIL ${clientHints.valid_until}`);
            }

            if (regionText.valid_until) {
                structuredLines.push(`VALID UNTIL ${regionText.valid_until}`);
            }

            if (clientHints.address) {
                structuredLines.push(`ADDRESS ${clientHints.address}`);
            }

            if (clientHints.id_number) {
                structuredLines.push(clientHints.id_number);
            }

            return {
                fullText: combinedFullText,
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
            this.hasShownAutoConfidenceModal = false;
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
                const idEvaluation = this.evaluateIdExtraction(
                    rawVerification?.id_number,
                    clientHints.id_number,
                    extractedText,
                    regionText.id_number || '',
                );
                const addressEvaluation = this.evaluateAddressExtraction(
                    rawVerification?.address,
                    clientHints.address,
                );

                const resolvedDateIssued = this.chooseBetterDateIssued(
                    rawVerification?.date_issued,
                    clientHints.date_issued,
                    rawVerification?.valid_until || clientHints.valid_until,
                    rawVerification?.date_of_birth || clientHints.date_of_birth,
                    extractedText,
                    regionText,
                ) || this.extractDateIssuedFromRegions(
                    regionText,
                    rawVerification?.valid_until || clientHints.valid_until,
                    rawVerification?.date_of_birth || clientHints.date_of_birth,
                    extractedText,
                ) || null;

                const dateIssuedEvaluation = this.evaluateDateIssuedExtraction(
                    resolvedDateIssued,
                    rawVerification?.valid_until || clientHints.valid_until,
                    rawVerification?.date_of_birth || clientHints.date_of_birth,
                );

                this.fieldConfidence.qcid_number = {
                    level: idEvaluation.level,
                    score: idEvaluation.score,
                    reason: idEvaluation.reason,
                };
                this.fieldConfidence.address = {
                    level: addressEvaluation.level,
                    score: addressEvaluation.score,
                    reason: addressEvaluation.reason,
                };
                this.fieldConfidence.date_issued = {
                    level: dateIssuedEvaluation.level,
                    score: dateIssuedEvaluation.score,
                    reason: dateIssuedEvaluation.reason,
                };

                const mergedVerification = rawVerification ? {
                    ...rawVerification,
                    cardholder_name: rawVerification.cardholder_name || clientHints.cardholder_name || null,
                    id_number: idEvaluation.value,
                    date_of_birth: rawVerification.date_of_birth || clientHints.date_of_birth || null,
                    date_issued: resolvedDateIssued,
                    valid_until: rawVerification.valid_until || clientHints.valid_until || null,
                    address: addressEvaluation.value,
                } : {
                    ...clientHints,
                    id_number: idEvaluation.value,
                    address: addressEvaluation.value,
                    date_issued: resolvedDateIssued,
                };

                if (!payload.success) {
                    this.verification = null;
                    // Clear all form fields to prevent showing previous user data
                    this.form.full_name = '';
                    this.form.qcid_number = '';
                    this.form.sex = '';
                    this.form.civil_status = '';
                    this.form.date_of_birth = '';
                    this.form.date_issued = '';
                    this.form.valid_until = '';
                    this.form.address = '';
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
                    this.form.qcid_number = mergedVerification.id_number || '';
                    this.form.sex = mergedVerification.sex || this.form.sex;
                    this.form.civil_status = mergedVerification.civil_status || this.form.civil_status;
                    this.form.date_of_birth = this.toDateInput(mergedVerification.date_of_birth) || this.form.date_of_birth;
                    this.form.date_issued = this.toDateInput(mergedVerification.date_issued) || this.form.date_issued;
                    this.form.valid_until = this.toDateInput(mergedVerification.valid_until) || this.form.valid_until;
                    this.form.address = mergedVerification.address || '';
                }

                this.currentStatus = 'pending';
                this.progress = 100;
                this.statusMessage = 'QC ID verified successfully.';
                this.maybeOpenAutoConfidenceModal();
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

            if (/^\d{2}-\d{2}-\d{4}$/.test(str)) {
                const [a, b, c] = str.split('-').map((part) => Number(part));
                const month = a > 12 ? b : a;
                const day = a > 12 ? a : b;
                if (c >= 1900 && c <= 2099 && month >= 1 && month <= 12 && day >= 1 && day <= 31) {
                    str = `${String(c).padStart(4, '0')}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                }
            }

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
