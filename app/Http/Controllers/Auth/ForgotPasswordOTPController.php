<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Mail\OtpVerificationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ForgotPasswordOTPController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendOtpCode(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ], [
            'email.exists' => 'This email does not exist in our system.',
        ]);

        $user = User::where('email', $request->email)->first();

        $otp = sprintf('%06d', random_int(100000, 999999));

        $user->forceFill([
            'otp_code' => Hash::make($otp),
            'otp_expires_at' => now()->addMinutes(15),
        ])->save();

        Mail::to($user->email)->queue(new OtpVerificationMail($user, $otp));

        return redirect()->route('password.verify.form', ['email' => $user->email])
            ->with('status', 'A 6-digit verification code has been sent to your email.');
    }

    public function showVerifyForm(Request $request)
    {
        if (!$request->has('email')) {
            return redirect()->route('password.request');
        }
        return view('auth.verify-otp', ['email' => $request->email]);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
            'otp' => ['required', 'string', 'size:6'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:16',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*\d).{8,16}$/',
            ],
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user->otp_code || !$user->otp_expires_at) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP code. Please request a new one.']);
        }

        if (now()->isAfter($user->otp_expires_at)) {
            return back()->withErrors(['otp' => 'This verification code has expired. Please request a new one.']);
        }

        if (!Hash::check($request->otp, $user->otp_code)) {
            return back()->withErrors(['otp' => 'The verification code provided is incorrect.']);
        }

        // OTP is valid. Change password and clear OTP
        $user->forceFill([
            'password' => Hash::make($request->password),
            'otp_code' => null,
            'otp_expires_at' => null,
        ])->save();

        return redirect()->route('login')->with('status', 'Your password has been successfully reset. You can now log in.');
    }
}
