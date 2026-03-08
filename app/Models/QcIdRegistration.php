<?php

namespace App\Models;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
