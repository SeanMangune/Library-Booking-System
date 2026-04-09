<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$userModel = 'App\\Models\\User';
$user = $userModel::find(6);
if (!$user) {
    echo "User not found\n";
    exit(0);
}
$service = app('App\\Services\\BookingTimeAlertService');
$service->syncForUser($user, false);
echo "done\n";
$notifications = $user->notifications()->orderBy('created_at','desc')->take(5)->get();
foreach ($notifications as $n) {
    echo $n->id . ' => ' . ($n->read_at ? 'read' : 'unread') . ' | ' . json_encode($n->data) . "\n";
}
