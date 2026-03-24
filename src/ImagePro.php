<?php

declare(strict_types=1);

namespace ImagePro\Enterprise;

/**
 * ImagePro v6.00 - Professional Library (zs57)
 * High-performance, multi-driver, namespaced image optimization suite.
 */

// --- Granular Architecture Exceptions ---
class ImageProException extends \RuntimeException {}
class ImageNotFoundException extends ImageProException {}
class ImageProcessingException extends ImageProException {}
class HardenedSecurityException extends ImageProException {}
class UnsupportedFormatException extends ImageProException {}

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
    private $image; // GdImage object or Imagick object
    private array $info;
    private readonly string $source;
    private readonly string $mime;
    private string $driver = 'gd';
    private ?string $temporaryMemoryLimit = null;
    private string $originalMemory;

    public static function open(string $path): self
    {
        return new self($path);
    }

    private function __construct(string $path)
    {
        $this->originalMemory = ini_get('memory_limit');
        
        if (!file_exists($path) || !is_readable($path)) {
            throw new ImageNotFoundException("Source image not found or not readable: $path");
        }

        $this->source = $path;
        $this->mime = $this->sniffMime();
        $this->detectDriver();
        $this->load();
    }

    public function __destruct()
    {
        $this->cleanup();
        if ($this->temporaryMemoryLimit) {
            ini_set('memory_limit', $this->originalMemory);
        }
    }

    public function withMemoryLimit(string $limit): self
    {
        $this->temporaryMemoryLimit = $limit;
        ini_set('memory_limit', $limit);
        return $this;
    }

    private function sniffMime(): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $this->source);
            finfo_close($finfo);
        } else {
            $info = @getimagesize($this->source);
            $mime = $info['mime'] ?? null;
        }

        if (!$mime || !str_starts_with($mime, 'image/')) {
            throw new HardenedSecurityException("File is not a valid image format.");
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
            throw new ImageProException("No compatible drivers (GD or Imagick) found on this server.");
        }
    }

    private function load(): void
    {
        if ($this->driver === 'gd') {
            $this->image = match ($this->mime) {
                'image/jpeg' => @imagecreatefromjpeg($this->source),
                'image/png'  => @imagecreatefrompng($this->source),
                'image/webp' => @imagecreatefromwebp($this->source),
                default      => throw new UnsupportedFormatException("GD Driver does not support: $this->mime"),
            };

            if (!$this->image instanceof \GdImage) {
                throw new ImageProcessingException("Failed to decode image using GD: $this->source");
            }

            // Alpha handling for transparent formats
            if (in_array($this->mime, ['image/png', 'image/webp'], true)) {
                imagealphablending($this->image, true);
                imagesavealpha($this->image, true);
            }
        } else {
            try {
                $this->image = new \Imagick($this->source);
            } catch (\Exception $e) {
                throw new ImageProcessingException("Failed to load image using Imagick: " . $e->getMessage());
            }
        }
    }

    public function resize(int $width, ?int $height = null): self
    {
        $origW = $this->getWidth();
        $origH = $this->getHeight();
        if ($height === null) {
            $height = (int) round($origH * ($width / $origW));
        }

        if ($this->driver === 'gd') {
            $new = imagecreatetruecolor($width, $height);
            if (in_array($this->mime, ['image/png', 'image/webp'], true)) {
                imagealphablending($new, false);
                imagesavealpha($new, true);
            }
            imagecopyresampled($new, $this->image, 0, 0, 0, 0, $width, $height, $origW, $origH);
            imagedestroy($this->image);
            $this->image = $new;
        } else {
            $this->image->resizeImage($width, $height, \Imagick::FILTER_LANCZOS, 1);
        }
        return $this;
    }

    public function filter(ImageFilter $filter): self
    {
        if ($this->driver === 'gd') {
            match ($filter) {
                ImageFilter::GREYSCALE => imagefilter($this->image, IMG_FILTER_GRAYSCALE),
                ImageFilter::SEPIA     => $this->sepiaGd(),
                ImageFilter::BLUR      => imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR),
                ImageFilter::SHARPEN   => imagefilter($this->image, IMG_FILTER_SMOOTH, -5),
                ImageFilter::CONTRAST  => imagefilter($this->image, IMG_FILTER_CONTRAST, -20),
            };
        } else {
            match ($filter) {
                ImageFilter::GREYSCALE => $this->image->modulateImage(100, 0, 100),
                ImageFilter::SEPIA     => $this->image->sepiaToneImage(80),
                ImageFilter::BLUR      => $this->image->blurImage(5, 3),
                ImageFilter::SHARPEN   => $this->image->sharpenImage(0, 1),
                ImageFilter::CONTRAST  => $this->image->contrastImage(true),
            };
        }
        return $this;
    }

    private function sepiaGd(): void
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 90, 60, 40);
    }

    public function stripMetadata(): self
    {
        if ($this->driver === 'imagick') {
            $this->image->stripImage();
        }
        return $this;
    }

    public function save(string $dest, ?int $type = null, int $quality = 82): bool
    {
        $quality = max(0, min(100, $quality));
        $dir = dirname($dest);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new ImageProException("Directory creation failed: $dir");
        }

        if ($this->driver === 'gd') {
            $type ??= match($this->mime) {
                'image/jpeg' => IMAGETYPE_JPEG,
                'image/png'  => IMAGETYPE_PNG,
                'image/webp' => IMAGETYPE_WEBP,
                default      => IMAGETYPE_JPEG
            };
            return match ($type) {
                IMAGETYPE_JPEG => imagejpeg($this->image, $dest, $quality),
                IMAGETYPE_PNG  => imagepng($this->image, $dest, (int) round((100 - $quality) / 100 * 9)),
                IMAGETYPE_WEBP => imagewebp($this->image, $dest, $quality),
                default        => throw new UnsupportedFormatException("Unsupported save type for GD."),
            };
        } else {
            $format = match ($type) {
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG  => 'png',
                IMAGETYPE_WEBP => 'webp',
                default        => 'jpg'
            };
            $this->image->setImageFormat($format);
            $this->image->setCompressionQuality($quality);
            return $this->image->writeImage($dest);
        }
    }

    public function convertToWebP(string $dest, int $quality = 82): bool
    {
        return $this->save($dest, IMAGETYPE_WEBP, $quality);
    }

    public function getWidth(): int 
    { 
        return ($this->driver === 'gd') ? imagesx($this->image) : $this->image->getImageWidth(); 
    }
    
    public function getHeight(): int 
    { 
        return ($this->driver === 'gd') ? imagesy($this->image) : $this->image->getImageHeight(); 
    }

    private function cleanup(): void
    {
        if ($this->driver === 'gd' && $this->image instanceof \GdImage) {
            @imagedestroy($this->image);
        } elseif ($this->driver === 'imagick' && $this->image instanceof \Imagick) {
            $this->image->clear();
            $this->image->destroy();
        }
    }
}
