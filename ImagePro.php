<?php

declare(strict_types=1);

/**
 * ImagePro v3.0 - Enterprise Image Optimization Suite
 * Fully refactored for PHP 8.1+ with EXIF handling, strict memory management, and Enums.
 */

// --- Custom Exceptions ---
class ImageProException extends \RuntimeException {}
class ImageNotFoundException extends ImageProException {}
class ImageProcessingException extends ImageProException {}

// --- Enums for Type Safety ---
enum ImageFilter: string {
    case GREYSCALE = 'grey';
    case SEPIA = 'sepia';
    case BLUR = 'blur';
    case BRIGHTNESS = 'bright';
}

enum WatermarkPosition {
    case TOP_LEFT;
    case TOP_RIGHT;
    case BOTTOM_LEFT;
    case BOTTOM_RIGHT;
    case CENTER;
}

class ImagePro
{
    private \GdImage $image;
    private array $info;
    private readonly string $sourcePath;
    private readonly int $type;
    private bool $isProgressive = true;

    /**
     * Static factory for cleaner instantiation
     */
    public static function open(string $path): self
    {
        return new self($path);
    }

    private function __construct(string $sourcePath)
    {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            throw new ImageNotFoundException("Source image not found or not readable: {$sourcePath}");
        }

        $this->sourcePath = $sourcePath;
        $this->load();
    }

    /**
     * Always cleanup memory explicitly when object is destroyed
     */
    public function __destruct()
    {
        if (isset($this->image)) {
            imagedestroy($this->image);
        }
    }

    private function load(): void
    {
        $info = getimagesize($this->sourcePath);
        if ($info === false) {
            throw new ImageProcessingException("Invalid image file or unsupported format.");
        }

        $this->info = $info;
        $this->type = $this->info[2];

        $this->image = match ($this->type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($this->sourcePath),
            IMAGETYPE_PNG => $this->loadPng(),
            IMAGETYPE_WEBP => imagecreatefromwebp($this->sourcePath),
            default => throw new ImageProcessingException("Unsupported image type. Only JPEG, PNG, and WebP are allowed."),
        };

        if (!$this->image) {
            throw new ImageProcessingException("Failed to create image object from file.");
        }

        // Fix rotation for images taken by mobile cameras
        if ($this->type === IMAGETYPE_JPEG) {
            $this->fixOrientation();
        }
    }

    private function loadPng(): \GdImage
    {
        $image = imagecreatefrompng($this->sourcePath);
        if (!$image) {
            throw new ImageProcessingException("Failed to load PNG image.");
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    /**
     * Reads EXIF data and rotates image correctly
     */
    private function fixOrientation(): void
    {
        if (!function_exists('exif_read_data')) {
            return;
        }

        $exif = @exif_read_data($this->sourcePath);
        if (empty($exif['Orientation'])) {
            return;
        }

        $angle = match ($exif['Orientation']) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle !== 0) {
            $rotated = imagerotate($this->image, $angle, 0);
            if ($rotated !== false) {
                imagedestroy($this->image); // Free old memory
                $this->image = $rotated;
            }
        }
    }

    public function resize(?int $width = null, ?int $height = null): self
    {
        if ($width === null && $height === null) {
            throw new \InvalidArgumentException("Width and height cannot both be null.");
        }

        $origW = $this->getWidth();
        $origH = $this->getHeight();

        if ($width === null) {
            $width = (int) round($origW * ($height / $origH));
        } elseif ($height === null) {
            $height = (int) round($origH * ($width / $origW));
        }

        $width = max(1, $width);
        $height = max(1, $height);

        $new = imagecreatetruecolor($width, $height);
        $this->handleAlpha($new);
        imagecopyresampled($new, $this->image, 0, 0, 0, 0, $width, $height, $origW, $origH);

        imagedestroy($this->image); // Explicit memory management
        $this->image = $new;

        return $this;
    }

    /**
     * Professional crop function maintaining center focus
     */
    public function crop(int $width, int $height): self
    {
        $origW = $this->getWidth();
        $origH = $this->getHeight();

        $srcX = (int) max(0, ($origW - $width) / 2);
        $srcY = (int) max(0, ($origH - $height) / 2);

        $new = imagecreatetruecolor($width, $height);
        $this->handleAlpha($new);
        imagecopyresampled($new, $this->image, 0, 0, $srcX, $srcY, $width, $height, $width, $height);

        imagedestroy($this->image); // Explicit memory management
        $this->image = $new;

        return $this;
    }

    /**
     * Smart watermark with Enum positioning
     */
    public function watermark(
        string $text,
        WatermarkPosition $position = WatermarkPosition::BOTTOM_RIGHT,
        int $size = 5,
        int $opacity = 50
    ): self {
        $opacity = max(0, min(100, $opacity));
        $alphaLevel = (int) round((100 - $opacity) * 1.27);
        $color = imagecolorallocatealpha($this->image, 255, 255, 255, $alphaLevel);

        if ($color !== false) {
            $fontSize = max(1, min(5, $size));
            $charWidth = imagefontwidth($fontSize);
            $charHeight = imagefontheight($fontSize);
            $textWidth = strlen($text) * $charWidth;

            // Calculate coordinates based on position Enum
            $padding = 10;
            [$x, $y] = match ($position) {
                WatermarkPosition::TOP_LEFT => [$padding, $padding],
                WatermarkPosition::TOP_RIGHT => [$this->getWidth() - $textWidth - $padding, $padding],
                WatermarkPosition::BOTTOM_LEFT => [$padding, $this->getHeight() - $charHeight - $padding],
                WatermarkPosition::BOTTOM_RIGHT => [$this->getWidth() - $textWidth - $padding, $this->getHeight() - $charHeight - $padding],
                WatermarkPosition::CENTER => [($this->getWidth() - $textWidth) / 2, ($this->getHeight() - $charHeight) / 2],
            };

            imagestring($this->image, $fontSize, (int)$x, (int)$y, $text, $color);
        }

        return $this;
    }

    /**
     * Apply filter using Enum for strict typing
     */
    public function filter(ImageFilter $filter): self
    {
        match ($filter) {
            ImageFilter::GREYSCALE => imagefilter($this->image, IMG_FILTER_GRAYSCALE),
            ImageFilter::SEPIA => $this->applySepia(),
            ImageFilter::BLUR => imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR),
            ImageFilter::BRIGHTNESS => imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 20),
        };

        return $this;
    }

    private function applySepia(): void
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 90, 60, 40);
    }

    public function autoOptimize(int $maxWidth = 1920): self
    {
        if ($this->getWidth() > $maxWidth) {
            $this->resize($maxWidth);
        }
        return $this;
    }

    public function save(string $dest, int $quality = 85, ?int $type = null): bool
    {
        $type ??= $this->type;
        $dir = dirname($dest);

        // Smart directory creation
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0777, true) && !is_dir($dir)) {
                throw new ImageProcessingException("Failed to create destination directory: {$dir}");
            }
        } elseif (!is_writable($dir)) {
            throw new ImageProcessingException("Destination directory is not writable: {$dir}");
        }

        if ($this->isProgressive && $type === IMAGETYPE_JPEG) {
            imageinterlace($this->image, true);
        }

        $quality = max(0, min(100, $quality));

        return match ($type) {
            IMAGETYPE_JPEG => imagejpeg($this->image, $dest, $quality),
            IMAGETYPE_PNG => imagepng($this->image, $dest, (int) round((100 - $quality) / 100 * 9)),
            IMAGETYPE_WEBP => imagewebp($this->image, $dest, $quality),
            default => throw new ImageProcessingException("Cannot save: unsupported image type."),
        };
    }

    public function convertToWebP(string $dest, int $quality = 80): bool
    {
        return $this->save($dest, $quality, IMAGETYPE_WEBP);
    }

    private function handleAlpha(\GdImage $new): void
    {
        if (in_array($this->type, [IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            imagealphablending($new, false);
            imagesavealpha($new, true);
            $transparent = imagecolorallocatealpha($new, 255, 255, 255, 127);

            if ($transparent !== false) {
                imagefilledrectangle($new, 0, 0, imagesx($new), imagesy($new), $transparent);
            }
        }
    }

    public function getWidth(): int
    {
        return imagesx($this->image);
    }

    public function getHeight(): int
    {
        return imagesy($this->image);
    }
}
