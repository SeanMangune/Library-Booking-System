<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rooms = [
            ['name' => 'Collaborative Room A', 'slug' => 'collaborative-room-a', 'capacity' => 10, 'location' => '2F Library', 'status' => 'operational', 'requires_approval' => false],
            ['name' => 'Collaborative Room B', 'slug' => 'collaborative-room-b', 'capacity' => 10, 'location' => '2F Library', 'status' => 'operational', 'requires_approval' => false],
            ['name' => 'Collaborative Room C', 'slug' => 'collaborative-room-c', 'capacity' => 10, 'location' => '2F Library', 'status' => 'operational', 'requires_approval' => false],
            ['name' => 'Collaborative Room D', 'slug' => 'collaborative-room-d', 'capacity' => 10, 'location' => '2F Library', 'status' => 'operational', 'requires_approval' => false],
            ['name' => 'Collaborative Room E', 'slug' => 'collaborative-room-e', 'capacity' => 10, 'location' => '2F Library', 'status' => 'operational', 'requires_approval' => false],
        ];

        foreach ($rooms as $room) {
            DB::table('rooms')->updateOrInsert(
                ['slug' => $room['slug']],
                array_merge($room, [
                    'description' => 'Collaborative room for group study and meetings.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
