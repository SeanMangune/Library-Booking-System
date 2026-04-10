<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$bookingModel = 'App\\Models\\Booking';
$bookings = $bookingModel::all();
echo "--- all bookings ---\n";
foreach ($bookings as $b) {
    echo 'id=' . $b->id . ' user=' . $b->user_id . ' room=' . $b->room_id . ' status=' . $b->status . ' date=' . $b->date . ' start=' . $b->start_time . ' end=' . $b->end_time . ' qr=' . $b->qr_validity . ' created=' . $b->created_at . "\n";
}
