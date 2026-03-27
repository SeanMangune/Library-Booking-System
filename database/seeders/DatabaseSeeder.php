<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $supportsUsername = Schema::hasColumn('users', 'username');

        $accounts = [
            [
                'name' => 'Test User 1',
                'username' => 'testuser1',
                'email' => 'test@example.com',
                'password' => 'password',
                'role' => User::ROLE_USER,
            ],
            [
                'name' => 'Test User 2',
                'username' => 'testuser2',
                'email' => 'test2@example.com',
                'password' => 'password',
                'role' => User::ROLE_USER,
            ],
            [
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@smartspace.local',
                'password' => 'Admin123!',
                'role' => User::ROLE_ADMIN,
            ],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'email' => 'superadmin@smartspace.local',
                'password' => 'SuperAdmin123!',
                'role' => User::ROLE_ADMIN,
            ],
        ];

        foreach ($accounts as $account) {
            $attributes = [
                'name' => $account['name'],
                'password' => Hash::make($account['password']),
                'role' => $account['role'],
                'email_verified_at' => now(),
            ];

            if ($supportsUsername) {
                $attributes['username'] = $account['username'];
            }

            User::updateOrCreate(
                ['email' => $account['email']],
                $attributes,
            );
        }

        $this->call([
            BookingSeeder::class,
        ]);
    }
}
