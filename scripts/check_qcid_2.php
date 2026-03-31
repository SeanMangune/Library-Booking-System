<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$output = "=== Users with MAN ===\n";
$users = App\Models\User::where('name', 'like', '%MAN%')->get();
foreach ($users as $u) {
    $output .= "User ID: {$u->id} | Email: {$u->email} | Name: {$u->name} | Provider: {$u->provider}\n";

    $regs = \App\Models\QcIdRegistration::where('user_id', $u->id)->get();
    foreach ($regs as $r) {
        $output .= "  -> QC ID Registration: ID {$r->id} | QCID# {$r->qcid_number} | Email {$r->email}\n";
    }
}

file_put_contents(__DIR__ . '/check_qcid_specific.txt', $output);
echo "Written to check_qcid_specific.txt";
