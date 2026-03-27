<?php

use App\Models\QcIdRegistration;
use App\Models\User;

// List all users with verified QC ID registration but missing image path
echo "Users with verified QC ID but missing image:\n";
$registrations = QcIdRegistration::where('verification_status', 'verified')
    ->where(function($q) {
        $q->whereNull('qcid_image_path')->orWhere('qcid_image_path', '');
    })
    ->with('user')
    ->get();

foreach ($registrations as $reg) {
    $user = $reg->user;
    echo "User ID: {$reg->user_id}, Name: " . ($user ? $user->name : 'N/A') . ", Email: " . ($user ? $user->email : 'N/A') . "\n";
}

echo "\nTotal: " . $registrations->count() . "\n";
