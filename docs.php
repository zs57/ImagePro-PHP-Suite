<?php
$lang = $_GET['lang'] ?? 'ar';
$dir = ($lang == 'ar') ? 'rtl' : 'ltr';

$docs = [
    'ar' => [
        'title' => 'تعليمات المطورين | ImagePro',
        'back' => 'العودة للوحة التحكم',
        'intro' => 'مرحباً بك في دليل استخدام أداة ImagePro. هذه الأداة مصممة لتكون بسيطة ولكنها قوية جداً.',
        'install' => 'كيفية التثبيت',
        'methods' => 'الوظائف البرمجية',
        'example' => 'مثال كامل'
    ],
    'en' => [
        'title' => 'Developer Manual | ImagePro',
        'back' => 'Return to Dashboard',
        'intro' => 'Welcome to the ImagePro guide. This tool is designed to be simple yet incredibly powerful.',
        'install' => 'Installation',
        'methods' => 'Class Methods',
        'example' => 'Full Example'
    ]
];
$d = $docs[$lang];
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>" dir="<?php echo $dir; ?>">
<head>
    <meta charset="UTF-8">
    <title><?php echo $d['title']; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container" style="max-width: 1200px;">
        <header style="text-align: right; border-bottom: 1px solid var(--border); padding-bottom: 1rem;">
            <a href="index.php?lang=<?php echo $lang; ?>" style="color: var(--text-dim); text-decoration: none;">← <?php echo $d['back']; ?></a>
            <h1 style="margin-top: 1rem; font-size: 2rem;"><?php echo $d['title']; ?></h1>
        </header>

        <div class="docs-container">
            <aside class="docs-sidebar">
                <a href="#intro" class="docs-nav-link active"><?php echo ($lang=='ar'?'المقدمة':'Introduction'); ?></a>
                <a href="#install" class="docs-nav-link"><?php echo $d['install']; ?></a>
                <a href="#methods" class="docs-nav-link"><?php echo $d['methods']; ?></a>
                <a href="#example" class="docs-nav-link"><?php echo $d['example']; ?></a>
            </aside>

            <main class="docs-content">
                <section id="intro">
                    <h2><?php echo ($lang=='ar'?'المرحلة الأولى: البداية':'Phase 1: Getting Started'); ?></h2>
                    <p><?php echo $d['intro']; ?></p>
                </section>

                <section id="install">
                    <h2><?php echo $d['install']; ?></h2>
                    <p><?php echo ($lang=='ar'?'كل ما تحتاجه هو استدعاء الملف في مشروعك:':'Simply include the file in your project:'); ?></p>
                    <div class="code-block">
                        <span class="code-keyword">require_once</span> <span class="code-string">'ImagePro.php'</span>;
                    </div>
                </section>

                <section id="methods">
                    <h2><?php echo $d['methods']; ?></h2>
                    <ul>
                        <li><strong>resize(int $w, int $h)</strong>: <?php echo ($lang=='ar'?'تغيير حجم الصورة مع الحفاظ على الأبعاد.':'Resize image while maintaining aspect ratio.'); ?></li>
                        <li><strong>watermark(string $text, WatermarkPosition $pos)</strong>: <?php echo ($lang=='ar'?'إضافة علامة مائية نصية احترافية باستخدام Enums.':'Add a professional text watermark using Enums.'); ?></li>
                        <li><strong>filter(ImageFilter $type)</strong>: <?php echo ($lang=='ar'?'تطبيق فلاتر النوع آمن (Type-Safe Filters).':'Apply type-safe filters.'); ?></li>
                    </ul>
                </section>

                <section id="example">
                    <h2><?php echo $d['example']; ?></h2>
                    <div class="code-block">
                        <span class="code-func">ImagePro::open</span>(<span class="code-string">'photo.jpg'</span>)<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;-><span class="code-func">autoOptimize</span>()<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;-><span class="code-func">filter</span>(ImageFilter::<span class="code-func">SEPIA</span>)<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;-><span class="code-func">watermark</span>(<span class="code-string">'PRO'</span>, WatermarkPosition::<span class="code-func">CENTER</span>)<br>
                        &nbsp;&nbsp;&nbsp;&nbsp;-><span class="code-func">convertToWebP</span>(<span class="code-string">'output.webp'</span>);
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
