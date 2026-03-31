<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$output = "";

$users = App\Models\User::all();
$output .= "=== All Users ===\n";
foreach ($users as $u) {
    $output .= "ID: {$u->id} | Name: {$u->name} | Role: {$u->role}\n";
}

$output .= "\n=== All QC ID Registrations ===\n";
$regs = App\Models\QcIdRegistration::all();
foreach ($regs as $r) {
    $output .= "ID: {$r->id} | User ID: {$r->user_id} | Full Name: {$r->full_name} | QC ID#: {$r->qcid_number} | Status: {$r->verification_status}\n";
}

// Check the currently logged-in user context
$output .= "\n=== User with CALINAWAN ===\n";
$user = App\Models\User::where('name', 'like', '%CALINAWAN%')->first();
if ($user) {
    $output .= "User ID: {$user->id} | Name: {$user->name}\n";
    $reg = App\Models\QcIdRegistration::where('user_id', $user->id)->first();
    if ($reg) {
        $output .= "QC ID Number: {$reg->qcid_number}\n";
        $output .= "Full Name: {$reg->full_name}\n";
        $output .= "Status: {$reg->verification_status}\n";
        $output .= "Verified Data: " . json_encode($reg->verified_data) . "\n";
    } else {
        $output .= "No QC ID registration found\n";
    }
    
    // Check the relationship used in the blade file
    $regViaRelation = $user->qcidRegistration;
    $output .= "\nVia qcidRegistration relation: " . ($regViaRelation ? 'found' : 'null') . "\n";
    if ($regViaRelation) {
        $output .= "Relation QC ID#: {$regViaRelation->qcid_number}\n";
        $output .= "Relation Status: {$regViaRelation->verification_status}\n";
    }
}

file_put_contents(__DIR__ . '/qcid_output.txt', $output);
echo "Output written to scripts/qcid_output.txt\n";
