<?php
require __DIR__ . '/vendor/autoload.php';

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\SvgWriter;

try {
    $result = (new Builder())->build(
        writer: new SvgWriter(),
        data: 'test-qr-svg-v6',
        size: 300,
        margin: 10
    );
        
    echo "SUCCESS: " . strlen($result->getString()) . " bytes of SVG generated.\n";
    echo "Content starts with: " . substr($result->getString(), 0, 50) . "\n";
} catch (\Throwable $e) {
    echo "FAILURE: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
