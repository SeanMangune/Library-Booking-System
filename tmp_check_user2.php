<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'Seancalimangune@gmail.com';
$user = \App\Models\User::where('email', $email)->orWhere('username', $email)->first();
if ($user) {
    echo "FOUND: " . $user->email . " username: " . $user->username . "\n";
} else {
    echo "NOT FOUND\n";
}
