<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::orderBy('id', 'desc')->take(10)->get(['id', 'email', 'username', 'name']);
foreach($users as $user) {
    echo "ID: " . $user->id . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Username: " . $user->username . "\n";
    echo "Name: " . $user->name . "\n";
    echo "--------------------------\n";
}
