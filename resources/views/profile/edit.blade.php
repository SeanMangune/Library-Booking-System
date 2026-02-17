@extends('layouts.app')

@section('title', 'My Profile')

@section('breadcrumb')
    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-gray-700 font-medium">My Profile</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-6">
            <div class="flex items-center justify-between gap-6 flex-wrap">
                <div class="flex items-center gap-4">
                    @php
                        $initials = collect(explode(' ', trim($user->name)))->filter()->map(fn($p) => mb_substr($p, 0, 1))->take(2)->implode('');
                    @endphp
                    <div class="w-14 h-14 bg-white/20 rounded-2xl flex items-center justify-center text-white font-extrabold text-lg">
                        {{ $initials ?: 'U' }}
                    </div>
                    <div>
                        <h1 class="text-white text-xl font-extrabold tracking-tight">{{ $user->name }}</h1>
                        <p class="text-indigo-100 text-sm">{{ $user->email }}</p>
                    </div>
                </div>
                <div class="text-indigo-100 text-sm">
                    <p><span class="font-semibold text-white">Member since:</span> {{ $user->created_at?->format('M d, Y') }}</p>
                    <p><span class="font-semibold text-white">Last updated:</span> {{ $user->updated_at?->diffForHumans() }}</p>
                </div>
            </div>
        </div>

        <div class="p-6">
            @if(session('status'))
                <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-gray-50 rounded-2xl border border-gray-200 p-5">
                    <h2 class="text-base font-bold text-gray-900">Account Information</h2>
                    <p class="text-sm text-gray-600 mt-1">Update your display name and email used for this system.</p>

                    <form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full name</label>
                            <input name="name" type="text" value="{{ old('name', $user->name) }}" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
                            @error('name')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email address</label>
                            <input name="email" type="email" value="{{ old('email', $user->email) }}" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white" />
                            @error('email')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold shadow-lg shadow-indigo-600/20 transition-colors">
                            Save changes
                        </button>
                    </form>
                </div>

                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <h2 class="text-base font-bold text-gray-900">Your Activity</h2>
                    <p class="text-sm text-gray-600 mt-1">Quick stats tied to your account.</p>

                    <div class="mt-5 space-y-3">
                        <div class="flex items-center justify-between bg-gray-50 border border-gray-200 rounded-xl px-4 py-3">
                            <p class="text-sm text-gray-600">Total bookings</p>
                            <p class="text-sm font-bold text-gray-900">{{ $stats['bookings_total'] }}</p>
                        </div>
                        <div class="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                            <p class="text-sm text-amber-700">Pending</p>
                            <p class="text-sm font-bold text-amber-900">{{ $stats['bookings_pending'] }}</p>
                        </div>
                        <div class="flex items-center justify-between bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                            <p class="text-sm text-emerald-700">Approved</p>
                            <p class="text-sm font-bold text-emerald-900">{{ $stats['bookings_approved'] }}</p>
                        </div>
                        <div class="flex items-center justify-between bg-rose-50 border border-rose-200 rounded-xl px-4 py-3">
                            <p class="text-sm text-rose-700">Rejected</p>
                            <p class="text-sm font-bold text-rose-900">{{ $stats['bookings_rejected'] }}</p>
                        </div>
                        <div class="flex items-center justify-between bg-indigo-50 border border-indigo-200 rounded-xl px-4 py-3">
                            <p class="text-sm text-indigo-700">Rooms in system</p>
                            <p class="text-sm font-bold text-indigo-900">{{ $stats['rooms'] }}</p>
                        </div>
                    </div>

                    <div class="mt-5 text-sm">
                        @if(auth()->user()?->isAdmin())
                            <a href="{{ route('settings.edit') }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">Go to Settings →</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection