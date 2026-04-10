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
echo 'reference: ' . $reference->toDateTimeString() . ' tz=' . $reference->getTimezone()->getName() . PHP_EOL;
echo 'server now: ' . \Carbon\Carbon::now()->toDateTimeString() . ' tz=' . \Carbon\Carbon::now()->getTimezone()->getName() . PHP_EOL;
echo 'booking date raw: ' . $b->getRawOriginal('date') . PHP_EOL;
echo 'booking date cast: ' . $b->date . PHP_EOL;
echo 'start: ' . $b->start_time . PHP_EOL;
echo 'end: ' . $b->end_time . PHP_EOL;
echo 'qr_validity: ' . $b->qr_validity . PHP_EOL;
echo 'determineQrValidity: ' . $b->determineQrValidity($reference) . PHP_EOL;
echo 'determineBookingStatus: ' . $b->determineBookingStatus($reference) . PHP_EOL;
