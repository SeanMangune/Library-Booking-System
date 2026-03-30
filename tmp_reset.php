<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Models\User::where('email', 'like', '%seancalimangune%')->update([
    'password' => \Illuminate\Support\Facades\Hash::make('Password0')
]);
echo "Updated passwords for seancalimangune test users.\n";
