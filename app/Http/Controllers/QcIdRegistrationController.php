<?php

namespace App\Http\Controllers;

use App\Models\QcIdRegistration;
use App\Models\User;
use App\Services\QcIdOcrVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class QcIdRegistrationController extends Controller
{
    private function actingUser(Request $request): User
    {
        $user = $request->user();
        if ($user instanceof User) {
            return $user;
        }

        return User::query()->orderBy('id')->firstOrFail();
    }

    public function show(Request $request): View
    {
        $user = $this->actingUser($request);
        $registration = QcIdRegistration::query()->where('user_id', $user->id)->first();

        return view('qcid.registration', compact('user', 'registration'));
    }

    public function store(Request $request, QcIdOcrVerifier $verifier): RedirectResponse
    {
        $user = $this->actingUser($request);

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'qcid_number' => ['nullable', 'string', 'max:50'],
            'sex' => ['nullable', 'string', 'max:20'],
            'civil_status' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'date'],
            'date_issued' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:1000'],
            'ocr_text' => ['required', 'string', 'min:20', 'max:12000'],
            'qcid_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:25600'],
        ]);

        $verification = $verifier->verify($validated['ocr_text'], $validated['full_name']);

        if (! $verification['is_valid']) {
            $message = ! empty($verification['rejected_id_type'])
                ? "This appears to be a {$verification['rejected_id_type']}. Only a valid Quezon City Citizen ID is accepted."
                : 'Only a valid Quezon City Citizen ID is accepted. Please upload a clearer QC ID image.';

            return back()
                ->withInput()
                ->withErrors([
                    'qcid_image' => $message,
                ]);
        }

        $path = $request->file('qcid_image')->store('qcid-registrations/' . $user->id, 'public');

        $registration = QcIdRegistration::query()->firstOrNew([
            'user_id' => $user->id,
        ]);

        if ($registration->exists && $registration->qcid_image_path && Storage::disk('public')->exists($registration->qcid_image_path)) {
            Storage::disk('public')->delete($registration->qcid_image_path);
        }

        $registration->fill([
            'full_name' => $validated['full_name'],
            'email' => $validated['email'],
            'contact_number' => $validated['contact_number'] ?? null,
            'qcid_number' => $validated['qcid_number'] ?: ($verification['id_number'] ?? null),
            'sex' => $validated['sex'] ?: ($verification['sex'] ?? null),
            'civil_status' => $validated['civil_status'] ?: ($verification['civil_status'] ?? null),
            'date_of_birth' => $validated['date_of_birth'] ?: ($verification['date_of_birth'] ?? null),
            'date_issued' => $validated['date_issued'] ?: ($verification['date_issued'] ?? null),
            'valid_until' => $validated['valid_until'] ?: ($verification['valid_until'] ?? null),
            'address' => $validated['address'] ?: ($verification['address'] ?? null),
            'ocr_text' => $validated['ocr_text'],
            'verification_status' => 'pending',
            'verification_notes' => null,
            'qcid_image_path' => $path,
            'verified_data' => $verification,
            'submitted_at' => now(),
            'reviewed_at' => null,
        ]);
        $registration->save();

        return redirect()
            ->route('qcid.registration.show')
            ->with('status', 'QC ID registration submitted successfully. Your details are now pending verification.');
    }
}
