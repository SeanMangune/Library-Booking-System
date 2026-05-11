<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Send "booking completed" notifications for bookings that have just ended.
| Runs every minute so notifications arrive within ~1 min of end_time + grace.
| Requires a system cron entry: * * * * * php /path/artisan schedule:run
|
*/
Schedule::command('bookings:send-end-notifications')->everyMinute();

/*
|--------------------------------------------------------------------------
| Philippine Holiday Sync
|--------------------------------------------------------------------------
|
| Fetches Philippine public holidays from the Nager.Date API.
| - Syncs the current year on Jan 2 (in case Jan 1 API data isn't ready yet).
| - Syncs the upcoming year on Nov 1 so holidays appear before year-end.
|
*/
Schedule::command('holidays:sync')->yearlyOn(1, 2, '03:00')->withoutOverlapping();
Schedule::command('holidays:sync ' . (now()->year + 1))->monthlyOn(1, '03:15')
    ->when(fn () => now()->month === 11)
    ->withoutOverlapping();

