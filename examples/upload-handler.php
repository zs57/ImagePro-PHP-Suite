<?php
/**
 * Example: Handling an AJAX Upload with ImagePro
 */
require_once __DIR__ . '/../vendor/autoload.php';
use ImagePro\ImagePro;

try {
    ImagePro::fromUpload($_FILES['image'])
        ->autoOrient()
        ->resize(1200)
        ->stripMetadata()
        ->save('uploads/' . uniqid() . '.webp');
    
    echo json_encode(['status' => 'success']);
} catch (\Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
