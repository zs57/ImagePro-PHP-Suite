<?php
/**
 * Example: Image Conversion CLI Utility
 */
require_once __DIR__ . '/../vendor/autoload.php';
use ImagePro\ImagePro;

if ($argc < 3) {
    die("Usage: php convert.php <source> <destination>\n");
}

try {
    ImagePro::open($argv[1])
        ->autoOrient()
        ->save($argv[2]);
    echo "Success: {$argv[2]} created.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
