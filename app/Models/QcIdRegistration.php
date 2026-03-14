<?php

namespace App\Models;

use App\Notifications\QcIdRegistrationReviewedNotification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QcIdRegistration extends Model
{
    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'contact_number',
        'qcid_number',
        'sex',
        'civil_status',
        'date_of_birth',
        'date_issued',
        'valid_until',
        'address',
        'ocr_text',
        'verification_status',
        'verification_notes',
        'qcid_image_path',
        'verified_data',
        'submitted_at',
        'reviewed_at',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'date_issued' => 'date',
        'valid_until' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'verified_data' => 'array',
    ];

    protected static function booted(): void
    {
        static::updated(function (QcIdRegistration $registration): void {
            if (! $registration->wasChanged('verification_status')) {
                return;
            }

            $status = (string) $registration->verification_status;
            if (! in_array($status, ['verified', 'rejected'], true)) {
                return;
            }

            $registration->loadMissing('user');
            if ($registration->user) {
                $registration->user->notify(new QcIdRegistrationReviewedNotification($registration));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
