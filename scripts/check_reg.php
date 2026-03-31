<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$reg = \App\Models\QcIdRegistration::where('user_id', 16)->first();
if ($reg) {
    echo "ID: " . $reg->id . "\n";
    echo "Name: " . $reg->full_name . "\n";
    echo "QC ID#: " . $reg->qcid_number . "\n";
    echo "OCR TEXT:\n" . $reg->ocr_text . "\n";
} else {
    echo "No registration found for user 16\n";
}
