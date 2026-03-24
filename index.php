<?php
require_once 'ImagePro.php';

$lang = $_GET['lang'] ?? 'ar';
$dir = ($lang == 'ar') ? 'rtl' : 'ltr';

$trans = [
    'ar' => [
        'title' => 'ImagePro | الجيل القادم لضغط الصور',
        'subtitle' => 'الأداة الاحترافية لضغط ومعالجة الصور بأعلى معايير الأمان والسرعة.',
        'drop' => 'اسحب الصورة هنا أو اضغط للاختيار',
        'limit' => 'يدعم JPG, PNG, WEBP (بحد أقصى 5 ميجابايت)',
        'orig' => 'الصورة الأصلية',
        'proc' => 'النسخة المحسنة',
        'size' => 'الحجم',
        'new_size' => 'الحجم الجديد',
        'saving' => 'نسبة التوفير',
        'speed' => 'سرعة المعالجة',
        'download' => 'تحميل الصورة المحسنة ✨',
        'docs' => 'كتاب التعليمات (Documentation)'
    ],
    'en' => [
        'title' => 'ImagePro | Next-Gen Image Optimization',
        'subtitle' => 'Professional-grade image processing with speed, security, and elegance.',
        'drop' => 'Drop image here or click to select',
        'limit' => 'Supports JPG, PNG, WEBP (Max 5MB)',
        'orig' => 'Original Image',
        'proc' => 'Optimized Version',
        'size' => 'Size',
        'new_size' => 'New Size',
        'saving' => 'Saving Ratio',
        'speed' => 'Processing Speed',
        'download' => 'Download Optimized ✨',
        'docs' => 'Developer Manual (Docs)'
    ]
];

$t = $trans[$lang];

$uploadDir = 'uploads/';
$processDir = 'processed/';
$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $file = $_FILES['image'];
    if($file['error'] == 0) {
        $img = new ImagePro($file['tmp_name']);
        $uniqueName = uniqid('img_') . '.webp';
        $webpPath = $processDir . $uniqueName;

        $startTime = microtime(true);
        $img->autoOptimize()
            ->filter(ImageFilter::GREYSCALE)
            ->watermark("ImagePro 3.0", WatermarkPosition::CENTER)
            ->convertToWebP($webpPath, 75);
        $endTime = microtime(true);

        $results = [
            'original' => ['url' => $file['tmp_name'], 'size' => round($file['size'] / 1024, 2) . ' KB'],
            'processed' => [
                'url' => $webpPath,
                'size' => round(filesize($webpPath) / 1024, 2) . ' KB',
                'saving' => round((1 - (filesize($webpPath) / $file['size'])) * 100, 1) . '%',
                'time' => round(($endTime - $startTime) * 1000, 2) . 'ms'
            ]
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&family=IBM+Plex+Sans+Arabic:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="lang-switcher">
            <a href="?lang=ar" class="lang-btn <?php echo ($lang == 'ar' ? 'active' : ''); ?>">عربي</a>
            <a href="?lang=en" class="lang-btn <?php echo ($lang == 'en' ? 'active' : ''); ?>">English</a>
        </div>

        <header>
            <h1>ImagePro <span style="font-size: 1rem; vertical-align: middle; color: #60a5fa; border: 1px solid #60a5fa; padding: 2px 8px; border-radius: 4px;">v2.10</span></h1>
            <p style="color: var(--text-dim); margin-top: 0.5rem;"><?php echo $t['subtitle']; ?></p>
            <a href="docs.php?lang=<?php echo $lang; ?>" style="color: var(--accent); font-size: 0.9rem; text-decoration: none; margin-top: 1rem; display: inline-block;"><?php echo $t['docs']; ?> →</a>
        </header>

        <section class="upload-card">
            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="file-input-wrapper" onclick="document.getElementById('imgFile').click()">
                    <div style="font-size: 3rem; margin-bottom: 1rem;">🖼️</div>
                    <div style="font-weight: 700; font-size: 1.2rem;"><?php echo $t['drop']; ?></div>
                    <div style="color: var(--text-dim); font-size: 0.9rem; margin-top: 0.5rem;"><?php echo $t['limit']; ?></div>
                    <input type="file" name="image" id="imgFile" style="display: none;" onchange="document.getElementById('uploadForm').submit()">
                </div>
            </form>
        </section>

        <?php if ($results): ?>
            <div class="results-grid">
                <div class="image-panel">
                    <h3><?php echo $t['orig']; ?></h3>
                    <img src="data:image/jpeg;base64,<?php echo base64_encode(file_get_contents($results['original']['url'])); ?>" alt="Original">
                    <div class="stats-bar">
                        <div class="stat-box"><div class="stat-label"><?php echo $t['size']; ?></div><div class="stat-value"><?php echo $results['original']['size']; ?></div></div>
                    </div>
                </div>

                <div class="image-panel" style="border-color: var(--accent);">
                    <h3><?php echo $t['proc']; ?></h3>
                    <img src="<?php echo $results['processed']['url']; ?>" alt="Processed">
                    <div class="stats-bar">
                        <div class="stat-box"><div class="stat-label"><?php echo $t['new_size']; ?></div><div class="stat-value"><?php echo $results['processed']['size']; ?></div></div>
                        <div class="stat-box"><div class="stat-label"><?php echo $t['saving']; ?></div><div class="stat-value" style="color: #22c55e;"><?php echo $results['processed']['saving']; ?></div></div>
                        <div class="stat-box" style="background: rgba(59, 130, 246, 0.05);"><div class="stat-label"><?php echo $t['speed']; ?></div><div class="stat-value"><?php echo $results['processed']['time']; ?></div></div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="<?php echo $results['processed']['url']; ?>" download class="btn-optimize" style="text-decoration: none;"><?php echo $t['download']; ?></a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
