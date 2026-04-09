<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$userModel = 'App\\Models\\User';
$count = $userModel::count();
echo "--- user count ---\n";
echo $count . "\n";
$user = $userModel::first();
if (!$user) {
    echo "No users found.\n";
    exit(0);
}
echo "user_id: " . $user->id . "\n";
echo "unread: " . $user->unreadNotifications()->count() . "\n";
echo "total: " . $user->notifications()->count() . "\n";
foreach ($user->notifications()->take(10)->get() as $n) {
    echo $n->id . ' => ' . ($n->read_at ? 'read' : 'unread') . ' | ' . json_encode($n->data) . "\n";
}
