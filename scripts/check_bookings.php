<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$bookingModel = 'App\\Models\\Booking';
echo "--- valid bookings ---\n";
$bookings = $bookingModel::where('qr_validity', 'valid')->get();
echo 'count=' . $bookings->count() . "\n";
foreach ($bookings as $b) {
    echo 'id=' . $b->id . ' user=' . $b->user_id . ' room=' . $b->room_id . ' date=' . $b->date . ' start=' . $b->start_time . ' end=' . $b->end_time . ' status=' . $b->status . ' qr=' . $b->qr_validity . "\n";
}
