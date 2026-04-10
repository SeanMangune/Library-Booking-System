<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$bookingModel = 'App\\Models\\Booking';
$values = $bookingModel::select('qr_validity')->distinct()->get()->pluck('qr_validity')->all();
echo "qr_validity distinct values:\n";
print_r($values);
$counts = $bookingModel::select('qr_validity', \DB::raw('count(*) as cnt'))->groupBy('qr_validity')->get();
echo "counts:\n";
foreach ($counts as $row) {
    echo $row->qr_validity . ' => ' . $row->cnt . "\n";
}
