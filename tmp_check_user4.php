<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = \App\Models\User::find(12);
echo "User 12 password match 'Password0': " . (\Illuminate\Support\Facades\Hash::check('Password0', $u->password) ? 'MATCH' : 'NO MATCH') . "\n";
echo "User 12 username length: " . strlen($u->username) . " | Value: '" . $u->username . "'\n";

$u2 = \App\Models\User::find(13);
echo "User 13 password match 'Password0': " . (\Illuminate\Support\Facades\Hash::check('Password0', $u2->password) ? 'MATCH' : 'NO MATCH') . "\n";
echo "User 13 username length: " . strlen($u2->username) . " | Value: '" . $u2->username . "'\n";

