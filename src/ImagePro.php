<?php

declare(strict_types=1);

namespace ImagePro;

use ImagePro\Enums\ImageFilter;
use ImagePro\Enums\WatermarkPosition;
use ImagePro\Exceptions\ImageProException;
use ImagePro\Exceptions\ImageNotFoundException;
use ImagePro\Exceptions\ImageProcessingException;
use ImagePro\Exceptions\HardenedSecurityException;
use ImagePro\Exceptions\UnsupportedFormatException;

/**
 * ImagePro v1.0.0 - Global Release (zs57)
 */
class ImagePro
{
    private $image; 
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
            throw new ImageNotFoundException("Source image not found: $path");
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

    public function withMemoryLimit(string $limit = '512M'): self
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
            throw new HardenedSecurityException("Invalid image source.");
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
            throw new ImageProException("No compatible drivers found.");
        }
    }

    private function load(): void
    {
        if ($this->driver === 'gd') {
            $this->image = match ($this->mime) {
                'image/jpeg' => @imagecreatefromjpeg($this->source),
                'image/png'  => @imagecreatefrompng($this->source),
                'image/webp' => @imagecreatefromwebp($this->source),
                default      => throw new UnsupportedFormatException("Unsupported: $this->mime"),
            };

            if (!$this->image instanceof \GdImage) {
                throw new ImageProcessingException("Failed to decode image.");
            }

            if (in_array($this->mime, ['image/png', 'image/webp'], true)) {
                imagealphablending($this->image, true);
                imagesavealpha($this->image, true);
            }
        } else {
            try {
                $this->image = new \Imagick($this->source);
            } catch (\Exception $e) {
                throw new ImageProcessingException($e->getMessage());
            }
        }
    }

    public function resize(int $width, ?int $height = null, string $mode = 'stretch'): self
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

    public function autoOrient(): self
    {
        if ($this->driver === 'imagick') {
            $this->image->autoOrient();
        } elseif ($this->driver === 'gd' && $this->mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($this->source);
            $ort = $exif['Orientation'] ?? 1;
            switch($ort) {
                case 3: $this->image = imagerotate($this->image, 180, 0); break;
                case 6: $this->image = imagerotate($this->image, -90, 0); break;
                case 8: $this->image = imagerotate($this->image, 90, 0); break;
            }
        }
        return $this;
    }

    public function stripMetadata(): self
    {
        if ($this->driver === 'imagick') {
            $this->image->stripImage();
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

    public function save(string $dest, ?string $format = null, int $quality = 82): bool
    {
        $quality = max(0, min(100, $quality));
        $dir = dirname($dest);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $format = ($format) ? strtolower($format) : pathinfo($dest, PATHINFO_EXTENSION);

        if ($this->driver === 'gd') {
            return match ($format) {
                'jpg', 'jpeg' => imagejpeg($this->image, $dest, $quality),
                'png'  => imagepng($this->image, $dest, (int) round((100 - $quality) / 100 * 9)),
                'webp' => imagewebp($this->image, $dest, $quality),
                default => imagejpeg($this->image, $dest, $quality),
            };
        } else {
            $this->image->setImageFormat($format);
            $this->image->setCompressionQuality($quality);
            return $this->image->writeImage($dest);
        }
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
