<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = \App\Models\User::orderBy('id', 'desc')->take(3)->get(['id', 'email', 'username', 'password']);
foreach($users as $user) {
    echo "ID: " . $user->id . "\n";
    echo "Email: " . $user->email . "\n";
    echo "Username: " . $user->username . "\n";
    echo "PasswordHash: " . $user->password . "\n";
    echo "PasswordMatch(Password0): " . (\Illuminate\Support\Facades\Hash::check('Password0', $user->password) ? 'true' : 'false') . "\n";
    echo "PasswordMatch(password0): " . (\Illuminate\Support\Facades\Hash::check('password0', $user->password) ? 'true' : 'false') . "\n";
    echo "--------------------------\n";
}
