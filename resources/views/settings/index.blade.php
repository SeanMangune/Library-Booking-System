@extends('layouts.app')

@section('title', 'Settings')

@section('breadcrumb')
    <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
    </svg>
    <span class="text-gray-700 font-medium">Settings</span>
@endsection

@section('content')
<div class="max-w-6xl mx-auto space-y-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-6">
            <h1 class="text-white text-xl font-extrabold tracking-tight">Settings</h1>
            <p class="text-indigo-100 text-sm">Customize your experience and manage account security.</p>
        </div>

        <div class="p-6 space-y-6">
            @if(session('status'))
                <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Preferences -->
                <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5">
                    <h2 class="text-base font-bold text-gray-900">Preferences</h2>
                    <p class="text-sm text-gray-600 mt-1">These settings are saved to your account.</p>

                    <form method="POST" action="{{ route('settings.preferences.update') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default calendar view</label>
                            <select name="default_calendar_view" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                @php $defaultView = old('default_calendar_view', $settings['default_calendar_view'] ?? 'month'); @endphp
                                <option value="month" @selected($defaultView === 'month')>Month</option>
                                <option value="week" @selected($defaultView === 'week')>Week</option>
                                <option value="day" @selected($defaultView === 'day')>Day</option>
                            </select>
                            @error('default_calendar_view')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Time format</label>
                            @php $timeFormat = old('time_format', (string)($settings['time_format'] ?? '12')); @endphp
                            <div class="grid grid-cols-2 gap-3">
                                <label class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                                    <input type="radio" name="time_format" value="12" class="text-indigo-600 focus:ring-indigo-500" @checked($timeFormat === '12')>
                                    <span class="text-sm text-gray-700">12-hour (AM/PM)</span>
                                </label>
                                <label class="flex items-center gap-2 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                                    <input type="radio" name="time_format" value="24" class="text-indigo-600 focus:ring-indigo-500" @checked($timeFormat === '24')>
                                    <span class="text-sm text-gray-700">24-hour</span>
                                </label>
                            </div>
                            @error('time_format')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="space-y-3">
                            @php $compact = (bool) old('compact_mode', $settings['compact_mode'] ?? false); @endphp
                            @php $pendingNotif = (bool) old('pending_approval_notifications', $settings['pending_approval_notifications'] ?? true); @endphp

                            <label class="flex items-start gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="compact_mode" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($compact)>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Compact mode</p>
                                    <p class="text-xs text-gray-600">Tighter spacing in tables and lists.</p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 bg-white border border-gray-200 rounded-xl px-4 py-3 cursor-pointer">
                                <input type="checkbox" name="pending_approval_notifications" value="1" class="mt-1 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked($pendingNotif)>
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">Pending approval notifications</p>
                                    <p class="text-xs text-gray-600">Show pending approval indicators in the header.</p>
                                </div>
                            </label>
                        </div>

                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-700 hover:to-indigo-800 text-white font-semibold shadow-lg shadow-indigo-600/20 transition-colors">
                            Save preferences
                        </button>
                    </form>
                </div>

                <!-- Security -->
                <div class="bg-white rounded-2xl border border-gray-200 p-5 shadow-sm">
                    <h2 class="text-base font-bold text-gray-900">Security</h2>
                    <p class="text-sm text-gray-600 mt-1">Change your password to keep your account safe.</p>

                    <form method="POST" action="{{ route('settings.password.update') }}" class="mt-5 space-y-4">
                        @csrf
                        @method('PUT')

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Current password</label>
                            <input name="current_password" type="password" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                            @error('current_password')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">New password</label>
                            <input name="password" type="password" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                            @error('password')
                                <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm new password</label>
                            <input name="password_confirmation" type="password" required
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" />
                        </div>

                        <button type="submit"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-slate-900 to-indigo-950 hover:from-slate-900 hover:to-slate-950 text-white font-semibold shadow-lg shadow-slate-900/10 transition-colors">
                            Update password
                        </button>
                    </form>

                    <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <p class="text-xs text-gray-600">
                            Tip: Use a long passphrase and avoid reusing passwords.
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-sm">
                <a href="{{ route('profile.edit') }}" class="text-indigo-600 hover:text-indigo-700 font-semibold">← Back to Profile</a>
            </div>
        </div>
    </div>
</div>
@endsection
