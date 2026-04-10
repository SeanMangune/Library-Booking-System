<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$bookingModel = 'App\\Models\\Booking';
$b = $bookingModel::find(1);
if (!$b) {
    echo "Booking not found\n";
    exit(0);
}
$reference = now(config('app.booking_timezone', 'Asia/Manila'));
echo 'reference: ' . $reference->toDateTimeString() . PHP_EOL;
echo 'booking date: ' . $b->date . PHP_EOL;
echo 'start: ' . $b->start_time . PHP_EOL;
echo 'end: ' . $b->end_time . PHP_EOL;
echo 'status: ' . $b->status . PHP_EOL;
echo 'qr_validity stored: ' . $b->qr_validity . PHP_EOL;

$room = $b->room;
echo 'room exists: ' . ($room ? 'yes' : 'no') . PHP_EOL;
if ($room) echo 'room status: ' . $room->effectiveStatus() . PHP_EOL;

$timezone = config('app.booking_timezone', 'Asia/Manila');
$start = \Carbon\Carbon::parse($b->date->format('Y-m-d') . ' ' . $b->start_time, $timezone);
$end = \Carbon\Carbon::parse($b->date->format('Y-m-d') . ' ' . $b->end_time, $timezone);
echo 'start dt: ' . $start->toDateTimeString() . PHP_EOL;
echo 'end dt: ' . $end->toDateTimeString() . PHP_EOL;
echo 'diff seconds end: ' . $reference->diffInSeconds($end, false) . PHP_EOL;

echo 'determineQrValidity ref: ' . $b->determineQrValidity($reference) . PHP_EOL;

foreach ([20,10,0] as $threshold) {
    $secondsLeft = $reference->diffInSeconds($end, false);
    if ($threshold > 0) {
        if ($secondsLeft <= 0) {
            echo "$threshold => too late\n";
            continue;
        }
        $minutesLeft = (int) ceil($secondsLeft / 60);
        echo "$threshold => minutesLeft=$minutesLeft";
        if ($minutesLeft !== $threshold) {
            echo ' (skipped)\n';
            continue;
        }
        if ($b->qr_validity !== 'valid' && $b->determineQrValidity($reference) !== 'valid') {
            echo ' (qr invalid)\n';
            continue;
        }
        echo ' (payload possible)\n';
    } else {
        if ($secondsLeft > 0) {
            echo "$threshold => not yet expired\n";
            continue;
        }
        echo "$threshold => expired payload possible\n";
    }
}
