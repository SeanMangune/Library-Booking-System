<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$start = microtime(true);
try {
    \Illuminate\Support\Facades\Mail::raw('Admin approval test', function($msg) {
        $msg->to('seanm@hotmail.com')->subject('Testing Verified Domain API');
    });
    echo "SUCCESS: Mail sent in " . (microtime(true) - $start) . " seconds\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
