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
