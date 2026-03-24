<?php

namespace App\Http\Controllers;

use App\Models\QcIdRegistration;
use App\Models\User;
use App\Notifications\NewQcIdSubmissionForStaffNotification;
use App\Notifications\QcIdSubmissionPendingNotification;
use App\Services\QcIdOcrVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
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
        $isStaff = $user->isStaff();

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:255'],
            'email' => [$isStaff ? 'nullable' : 'required', 'email', 'max:255'],
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
            $message = ($verification['id_assessment'] ?? 'INVALID') === 'Fake QC ID'
                ? 'Fake QC ID detected. Please upload a genuine Quezon City Citizen ID.'
                : (! empty($verification['rejected_id_type'])
                    ? "This appears to be a {$verification['rejected_id_type']}. Only a valid Quezon City Citizen ID is accepted."
                    : 'Only a valid Quezon City Citizen ID is accepted. Please upload a clearer QC ID image.');

            return back()
                ->withInput()
                ->withErrors([
                    'qcid_image' => $message,
                ]);
        }

        $path = $request->file('qcid_image')->store('qcid-registrations/' . $user->id, 'public');

        $verifiedQcidNumber = $this->normalizeQcidNumber($verification['id_number'] ?? null);
        $verifiedAddress = $this->normalizeAddress($verification['address'] ?? null);
        $verifiedDob = $this->normalizeDateForDatabase($verification['date_of_birth'] ?? null);
        $verifiedIssued = $this->normalizeDateForDatabase($verification['date_issued'] ?? null);
        $verifiedValidUntil = $this->normalizeDateForDatabase($verification['valid_until'] ?? null);

        $registration = QcIdRegistration::query()->firstOrNew([
            'user_id' => $user->id,
        ]);

        if ($registration->exists && $registration->qcid_image_path && Storage::disk('public')->exists($registration->qcid_image_path)) {
            Storage::disk('public')->delete($registration->qcid_image_path);
        }

        $registration->fill([
            'full_name' => $validated['full_name'],
            'email' => $isStaff ? '' : ($validated['email'] ?? ''),
            'contact_number' => $validated['contact_number'] ?? null,
            'qcid_number' => ($validated['qcid_number'] ?? '') !== ''
                ? $this->normalizeQcidNumber($validated['qcid_number'])
                : $verifiedQcidNumber,
            'sex' => $validated['sex'] ?: ($verification['sex'] ?? null),
            'civil_status' => $validated['civil_status'] ?: ($verification['civil_status'] ?? null),
            'date_of_birth' => ($validated['date_of_birth'] ?? '') !== ''
                ? $this->normalizeDateForDatabase($validated['date_of_birth'])
                : $verifiedDob,
            'date_issued' => ($validated['date_issued'] ?? '') !== ''
                ? $this->normalizeDateForDatabase($validated['date_issued'])
                : $verifiedIssued,
            'valid_until' => ($validated['valid_until'] ?? '') !== ''
                ? $this->normalizeDateForDatabase($validated['valid_until'])
                : $verifiedValidUntil,
            'address' => ($validated['address'] ?? '') !== ''
                ? $this->normalizeAddress($validated['address'])
                : $verifiedAddress,
            'ocr_text' => $validated['ocr_text'],
            'verification_status' => 'pending',
            'verification_notes' => null,
            'qcid_image_path' => $path,
            'verified_data' => $verification,
            'submitted_at' => now(),
            'reviewed_at' => null,
        ]);
        $registration->save();

        $user->notify(new QcIdSubmissionPendingNotification($registration));

        $staffRecipients = User::query()
            ->whereIn('role', [User::ROLE_ADMIN, User::ROLE_LIBRARIAN])
            ->where('id', '!=', $user->id)
            ->get();

        if ($staffRecipients->isNotEmpty()) {
            Notification::send($staffRecipients, new NewQcIdSubmissionForStaffNotification($registration, $user));
        }

        return redirect()
            ->route('qcid.registration.show')
            ->with('status', 'QC ID registration submitted successfully. Your details are now pending verification.');
    }

    private function normalizeDateForDatabase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace('.', '/', $value);

        foreach (['Y-m-d', 'Y/m/d', 'm/d/Y', 'd/m/Y', 'Ymd'] as $format) {
            try {
                $parsed = Carbon::createFromFormat($format, $value);
                if ($parsed !== false) {
                    return $parsed->format('Y-m-d');
                }
            } catch (\Throwable) {
                // Try next known format.
            }
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeQcidNumber(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $value) ?? '';
        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 13) {
            $digits = '0' . $digits;
        }

        if (strlen($digits) !== 14) {
            return null;
        }

        return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 8);
    }

    private function normalizeAddress(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = mb_strtoupper(trim($value), 'UTF-8');
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\b(?:DATE\s*ISSUED|VALID\s*UNTIL|DATE\s*(?:OF)?\s*BIRTH|CIVIL\s*STATUS|CARD\s*HOLDER|SIGNATURE|REPUBLIC\s+OF\s+THE\s+PHILIPPINES|Q\s*CITIZEN\s*CARD)\b/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        if (preg_match('/([A-Z0-9,\.\-\s]{8,}?QUEZON\s+CITY)/', $value, $m)) {
            $value = trim($m[1]);
        }

        return $value !== '' ? $value : null;
    }
}
