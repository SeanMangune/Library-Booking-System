@extends('layouts.guest')

@section('title', 'Sign Up - SmartSpace')

@section('content')
<div x-data="signupLoginApp(false)" class="min-h-screen px-4 py-10">
    <div class="mx-auto mb-8 flex max-w-5xl justify-center">
        <div class="login-brand-wrap">
            <img src="{{ asset('images/smartspace-logo.png') }}" alt="SmartSpace" class="login-brand-logo h-32 w-auto max-w-none sm:h-40" onerror="this.onerror=null;this.src='{{ asset('images/smartspace-logo.svg') }}';">
        </div>
    </div>

    <div class="mx-auto w-full max-w-6xl overflow-hidden rounded-3xl border border-indigo-100 bg-slate-50 shadow-[0_30px_100px_-30px_rgba(30,41,59,0.75)]">
        <div class="signup-hero border-b border-indigo-200/20 bg-gradient-to-br from-indigo-950 via-indigo-900 to-slate-900 px-6 py-6 sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <p class="inline-flex rounded-full border border-indigo-300/20 bg-indigo-400/15 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-indigo-100">User Verification Portal</p>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white">Create your SmartSpace account</h1>
                    <p class="mt-2 max-w-2xl text-sm text-indigo-100">Upload your Quezon City Citizen ID, review captured details, and finish signup in one guided flow.</p>
                    <p class="mt-4 text-sm text-indigo-200">
                        Already registered?
                        <a href="{{ route('login') }}" class="font-semibold text-white underline decoration-indigo-300 underline-offset-2 hover:text-indigo-100">Back to login</a>
                    </p>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 lg:w-[430px]">
                    <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Current status</p>
                        <p class="mt-1 text-lg font-bold text-white" x-text="scan.isVerified ? 'Ready to submit' : (scan.idAssessment === 'Fake QC ID' ? 'Fake QC ID' : (scan.idAssessment === 'INVALID' ? 'Invalid ID' : 'Not submitted'))"></p>
                    </div>
                    <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Detected ID</p>
                        <p class="mt-1 text-lg font-bold text-white" x-text="scan.idAssessment || (signup.ocr_text ? 'Scanning...' : 'Not verified')"></p>
                    </div>
                    <div class="rounded-2xl border border-indigo-300/20 bg-indigo-500/10 px-3 py-3 backdrop-blur-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-indigo-100">Confidence</p>
                        <p class="mt-1 text-lg font-bold text-white" x-text="scan.confidenceLabel || '—'"></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="signup-scroll-area max-h-none p-5 sm:p-6">
            @if ($errors->any() && !$errors->has('login'))
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                    <h2 class="text-sm font-bold text-red-800">Please fix the following:</h2>
                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('auth.partials.qc-signup-form', ['signupStandalone' => true])
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
<script type="text/javascript">
window.signupOldInput = {
    name: @json(old('name', '')),
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
@endpush
