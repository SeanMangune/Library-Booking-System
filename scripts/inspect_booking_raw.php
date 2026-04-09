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
echo "start_time raw: "; var_export($b->getRawOriginal('start_time')); echo "\n";
echo "end_time raw: "; var_export($b->getRawOriginal('end_time')); echo "\n";
echo "qr_validity raw: "; var_export($b->getRawOriginal('qr_validity')); echo "\n";
