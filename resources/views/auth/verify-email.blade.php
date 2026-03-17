@extends('layouts.guest')

@section('title', 'Verify Email - SmartSpace')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-lg rounded-2xl border border-gray-200 bg-white p-8 shadow-lg">
        <img src="{{ asset('images/smartspace-logo.png') }}" alt="SmartSpace" class="h-28 w-auto mb-4" onerror="this.onerror=null;this.src='{{ asset('images/smartspace-logo.svg') }}';">
        <h1 class="text-2xl font-bold text-gray-900">Verify Your Email Address</h1>
        <p class="mt-3 text-sm text-gray-600">
            Thanks for signing up for SmartSpace. We sent a verification link to your email. Please verify your account before making reservations.
        </p>

        @if (session('status'))
            <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                    Resend Verification Email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="inline-flex items-center justify-center rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    Log Out
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
