<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rooms')
            ->where('capacity', '<', 10)
            ->where(function ($query) {
                $query->where('name', 'like', '%Collaborative%')
                    ->orWhere('name', 'like', '%Collab%')
                    ->orWhere('slug', 'like', '%collab%');
            })
            ->update(['capacity' => 10]);
    }

    public function down(): void
    {
        // This data correction is intentionally not reversed.
    }
};