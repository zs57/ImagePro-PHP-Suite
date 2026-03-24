<?php
/**
 * Benchmark: GD vs Imagick (Manual Loading)
 */
require_once __DIR__ . '/src/ImagePro.php';
require_once __DIR__ . '/src/Enums/ImageFilter.php';
require_once __DIR__ . '/src/Enums/WatermarkPosition.php';
require_once __DIR__ . '/src/Exceptions/ImageProException.php';

use ImagePro\ImagePro;

$source = 'sample_nature_source.png';
if (!file_exists($source)) die("Source not found\n");

function benchmark($driver) {
    global $source;
    $start = microtime(true);
    $memStart = memory_get_usage();
    
    // Performance test: 3 operations
    for ($i = 0; $i < 3; $i++) {
        $img = ImagePro::open($source);
        
        // Manual driver injection for test
        $reflector = new ReflectionClass($img);
        $prop = $reflector->getProperty('driver');
        $prop->setAccessible(true);
        $prop->setValue($img, $driver);
        
        $img->resize(800)
            ->save("bench_{$driver}_{$i}.webp", quality: 80);
    }
    
    return [
        'time' => microtime(true) - $start,
        'memory' => (memory_get_usage() - $memStart) / 1024 / 1024
    ];
}

echo "Running benchmarks (3 operations)...\n";
$gd = benchmark('gd');
$imagick = benchmark('imagick');

$results = "
## Performance Benchmark (v2.1.0)
| Engine  | Execution Time (3 ops) | Avg Memory Delta |
| ------- | ----------------------| ---------------- |
| GD      | " . number_format($gd['time'], 3) . "s | " . number_format($gd['memory'], 2) . "MB |
| Imagick | " . number_format($imagick['time'], 3) . "s | " . number_format($imagick['memory'], 2) . "MB |
";

file_put_contents('docs/benchmarks.md', $results);
echo "Done. Results saved to docs/benchmarks.md\n";
