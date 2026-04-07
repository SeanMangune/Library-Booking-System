<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $booleanTrue = DB::connection()->getDriverName() === 'pgsql' ? 'true' : true;

        DB::table('rooms')->where('capacity', '<', 5)->update(['capacity' => 5]);
        DB::table('rooms')->where('capacity', '>', 10)->update(['capacity' => 10]);

        $now = now();
        $defaults = [
            [
                'name' => 'Collaborative Room A',
                'slug' => 'collaborative-room-a',
            ],
            [
                'name' => 'Collaborative Room B',
                'slug' => 'collaborative-room-b',
            ],
            [
                'name' => 'Collaborative Room C',
                'slug' => 'collaborative-room-c',
            ],
            [
                'name' => 'Collaborative Room D',
                'slug' => 'collaborative-room-d',
            ],
            [
                'name' => 'Collaborative Room E',
                'slug' => 'collaborative-room-e',
            ],
        ];

        foreach ($defaults as $room) {
            $existing = DB::table('rooms')
                ->where('slug', $room['slug'])
                ->orWhereRaw('LOWER(name) = ?', [strtolower($room['name'])])
                ->orderBy('id')
                ->first();

            $payload = [
                'name' => $room['name'],
                'slug' => $room['slug'],
                'capacity' => 10,
                'description' => 'Collaborative workspace for group study and projects',
                'location' => '2F Library',
                'status' => 'operational',
                'requires_approval' => $booleanTrue,
                'status_start_at' => null,
                'status_end_at' => null,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('rooms')->where('id', $existing->id)->update($payload);
            } else {
                DB::table('rooms')->insert(array_merge($payload, ['created_at' => $now]));
            }
        }
    }

    public function down(): void
    {
        // Keep seeded room defaults and capacity normalization.
    }
};
