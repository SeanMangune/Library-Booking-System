<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncPhilippineHolidays extends Command
{
    protected $signature = 'holidays:sync {year?}';

    protected $description = 'Fetch Philippine public holidays from Nager.Date API and sync to calendar_events';

    public function handle(): int
    {
        $year = (int) ($this->argument('year') ?: now()->year);
        $this->info("Syncing Philippine holidays for {$year}…");

        try {
            $response = Http::timeout(15)
                ->get("https://date.nager.at/api/v3/PublicHolidays/{$year}/PH");

            if (! $response->successful()) {
                $this->error('API returned HTTP ' . $response->status());
                Log::warning('Philippine holiday sync failed', ['status' => $response->status()]);

                return self::FAILURE;
            }

            $holidays = $response->json();

            if (! is_array($holidays)) {
                $this->error('Unexpected API response format.');

                return self::FAILURE;
            }

            $created = 0;
            $skipped = 0;

            foreach ($holidays as $holiday) {
                $date = $holiday['date'] ?? null;
                $name = $holiday['localName'] ?? $holiday['name'] ?? null;

                if (! $date || ! $name) {
                    $skipped++;
                    continue;
                }

                $sourceId = 'nager-ph-' . $date;

                // Skip if already exists for this date/source
                $exists = DB::table('calendar_events')
                    ->where('source', 'api')
                    ->where('source_id', $sourceId)
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                $now = Carbon::now();

                DB::table('calendar_events')->insert([
                    'title' => $name,
                    'description' => ($holiday['name'] !== $name ? $holiday['name'] : null),
                    'type' => 'holiday',
                    'date' => Carbon::parse($date)->toDateString(),
                    'is_all_day' => DB::raw('true'),
                    'start_time' => null,
                    'end_time' => null,
                    'color' => '#3B82F6',
                    'source' => 'api',
                    'source_id' => $sourceId,
                    'is_recurring' => DB::raw('false'),
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $created++;
            }

            $this->info("Done: {$created} holidays created, {$skipped} skipped (already exist).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Holiday sync failed: ' . $e->getMessage());
            Log::error('Philippine holiday sync error', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }
    }
}

