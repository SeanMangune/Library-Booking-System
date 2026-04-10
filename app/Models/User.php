<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const CLASSIFICATION_STUDENT = 'student';

    public const CLASSIFICATION_FACULTY = 'faculty';

    public const CLASSIFICATION_ADMIN = 'admin';

    public const ROLE_USER = 'user';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_LIBRARIAN = 'librarian';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'classification',
        'provider',
        'provider_id',
        'settings',
        'otp_code',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
            'classification' => 'string',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin() && strcasecmp((string) ($this->username ?? ''), 'superadmin') === 0;
    }

    public function isLibrarian(): bool
    {
        return $this->role === self::ROLE_LIBRARIAN;
    }

    public function isStaff(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_LIBRARIAN], true);
    }

    public function roleLabel(): string
    {
        if ($this->isSuperAdmin()) {
            return 'Super Admin';
        }

        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_LIBRARIAN => 'Librarian',
            default => 'User',
        };
    }

    public function classification(): string
    {
        if ($this->role === self::ROLE_ADMIN) {
            return self::CLASSIFICATION_ADMIN;
        }

        if ($this->role === self::ROLE_LIBRARIAN) {
            return self::CLASSIFICATION_FACULTY;
        }

        $classification = strtolower(trim((string) ($this->classification ?? '')));

        if (in_array($classification, [self::CLASSIFICATION_STUDENT, self::CLASSIFICATION_FACULTY, self::CLASSIFICATION_ADMIN], true)) {
            return $classification;
        }

        return match ($this->role) {
            self::ROLE_ADMIN => self::CLASSIFICATION_ADMIN,
            self::ROLE_LIBRARIAN => self::CLASSIFICATION_FACULTY,
            default => self::CLASSIFICATION_STUDENT,
        };
    }

    public function classificationLabel(): string
    {
        return match ($this->classification()) {
            self::CLASSIFICATION_ADMIN => 'Admin',
            self::CLASSIFICATION_FACULTY => 'Faculty',
            default => 'Student',
        };
    }

    public function qcidRegistration(): HasOne
    {
        return $this->hasOne(QcIdRegistration::class);
    }
}
