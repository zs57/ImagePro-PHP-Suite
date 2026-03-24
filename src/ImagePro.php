<?php

declare(strict_types=1);

namespace ImagePro\Enterprise;

/**
 * ImagePro v5.00 - Elite Edition (zs57)
 * High-performance, multi-driver, namespaced image optimization suite.
 */

// --- Granular Architecture Exceptions ---
class ImageProException extends \RuntimeException {}
class HardenedSecurityException extends ImageProException {}
class MultiDriverConflictException extends ImageProException {}
class MemoryLimitException extends ImageProException {}

// --- Namespaced Enums ---
enum ImageFilter: string {
    case GREYSCALE = 'grey';
    case SEPIA = 'sepia';
    case BLUR = 'blur';
    case SHARPEN = 'sharpen';
    case CONTRAST = 'contrast';
}

enum WatermarkPosition {
    case TOP_LEFT; case TOP_RIGHT; case BOTTOM_LEFT; case BOTTOM_RIGHT; case CENTER;
}

class ImagePro
{
    private $image;
    private readonly string $source;
    private readonly string $mime;
    private string $driver = 'gd';
    private string $originalMemory;

    /**
     * Elite Factory Pattern
     */
    public static function open(string $path): self
    {
        return new self($path);
    }

    private function __construct(string $path)
    {
        $this->originalMemory = ini_get('memory_limit');
        $this->secureMemoryScaling();

        if (!file_exists($path) || !is_readable($path)) {
            throw new ImageProException("Unreachable source: $path");
        }

        $this->source = $path;
        $this->mime = $this->sniffMime();
        $this->detectDriver();
        $this->load();
    }

    public function __destruct()
    {
        $this->cleanup();
        ini_set('memory_limit', $this->originalMemory);
    }

    /**
     * Deep Mime Sniffing (Security Hardening)
     */
    private function sniffMime(): string
    {
        if (!function_exists('finfo_open')) {
            $info = getimagesize($this->source);
            return $info['mime'] ?? 'application/octet-stream';
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $this->source);
        finfo_close($finfo);

        if (!str_starts_with($mime, 'image/')) {
            throw new HardenedSecurityException("Security Breach: File is not an image ($mime)");
        }
        return $mime;
    }

    private function detectDriver(): void
    {
        if (function_exists('gd_info')) {
            $this->driver = 'gd';
        } elseif (class_exists('\Imagick')) {
            $this->driver = 'imagick';
        } else {
            throw new MultiDriverConflictException("No compatible drivers (GD/Imagick) enabled.");
        }
    }

    private function secureMemoryScaling(): void
    {
        ini_set('memory_limit', '512M'); // Enterprise Buffer
    }

    private function load(): void
    {
        if ($this->driver === 'gd') {
            $this->image = match ($this->mime) {
                'image/jpeg' => imagecreatefromjpeg($this->source),
                'image/png'  => imagecreatefrompng($this->source),
                'image/webp' => imagecreatefromwebp($this->source),
                default      => throw new ImageProException("GD Unsupported Format: $this->mime"),
            };
        } else {
            $this->image = new \Imagick($this->source);
        }
    }

    public function filter(ImageFilter $filter): self
    {
        if ($this->driver === 'gd') {
            match ($filter) {
                ImageFilter::GREYSCALE => imagefilter($this->image, IMG_FILTER_GRAYSCALE),
                ImageFilter::SEPIA     => $this->sepiaGd(),
                ImageFilter::SHARPEN   => imagefilter($this->image, IMG_FILTER_SMOOTH, -5),
                ImageFilter::CONTRAST  => imagefilter($this->image, IMG_FILTER_CONTRAST, -20),
                default => null,
            };
        }
        return $this;
    }

    private function sepiaGd(): void
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 90, 60, 40);
    }

    public function convertToWebP(string $dest, int $quality = 82): bool
    {
        $dir = dirname($dest);
        if (!is_dir($dir)) mkdir($dir, 0777, true);

        if ($this->driver === 'gd') {
            return imagewebp($this->image, $dest, $quality);
        } else {
            $this->image->setImageFormat('webp');
            $this->image->setCompressionQuality($quality);
            return $this->image->writeImage($dest);
        }
    }

    private function cleanup(): void
    {
        if ($this->driver === 'gd' && is_resource($this->image)) {
            imagedestroy($this->image);
        }
    }
}
