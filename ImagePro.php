<?php

declare(strict_types=1);

class ImageProException extends \RuntimeException {}
class ImageNotFoundException extends ImageProException {}
class ImageProcessingException extends ImageProException {}
class UnsupportedFormatException extends ImageProException {}

enum ImageFilter: string {
    case GREYSCALE = 'grey';
    case SEPIA = 'sepia';
    case BLUR = 'blur';
    case BRIGHTNESS = 'bright';
}

enum WatermarkPosition {
    case TOP_LEFT; case TOP_RIGHT; case BOTTOM_LEFT; case BOTTOM_RIGHT; case CENTER;
}

class ImagePro
{
    private $image; // Dynamic driver object (GdImage or Imagick)
    private array $info;
    private readonly string $sourcePath;
    private readonly int $type;
    private bool $isProgressive = true;
    private string $driver = 'gd';

    public static function open(string $path): self
    {
        return new self($path);
    }

    private function __construct(string $sourcePath)
    {
        if (!file_exists($sourcePath) || !is_readable($sourcePath)) {
            throw new ImageNotFoundException("Source image not found or not readable: {$sourcePath}");
        }

        if (function_exists('gd_info')) {
            $this->driver = 'gd';
        } elseif (class_exists('Imagick')) {
            $this->driver = 'imagick';
        } else {
            throw new ImageProcessingException("No supported image processing library (GD or Imagick) found on this server.");
        }

        $this->sourcePath = $sourcePath;
        $this->load();
    }

    public function __destruct()
    {
        if ($this->driver === 'gd' && isset($this->image)) {
            @imagedestroy($this->image);
        } elseif ($this->driver === 'imagick' && isset($this->image)) {
            $this->image->clear();
            $this->image->destroy();
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

        if ($this->driver === 'gd') {
            $this->loadGd();
        } else {
            $this->loadImagick();
        }
    }

    private function loadGd(): void
    {
        $this->image = match ($this->type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($this->sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($this->sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($this->sourcePath),
            default => throw new UnsupportedFormatException("Format not supported by GD."),
        };
        if (!$this->image) throw new ImageProcessingException("GD failed to load image.");
        if ($this->type === IMAGETYPE_JPEG) $this->fixOrientation();
    }

    private function loadImagick(): void
    {
        $this->image = new \Imagick($this->sourcePath);
    }

    private function fixOrientation(): void
    {
        if (!function_exists('exif_read_data')) return;
        $exif = @exif_read_data($this->sourcePath);
        if (empty($exif['Orientation'])) return;
        $angle = match ($exif['Orientation']) { 3 => 180, 6 => -90, 8 => 90, default => 0 };
        if ($angle !== 0 && $this->driver === 'gd') {
            $rotated = imagerotate($this->image, $angle, 0);
            if ($rotated) { imagedestroy($this->image); $this->image = $rotated; }
        }
    }

    public function autoOptimize(int $maxWidth = 1920): self
    {
        if ($this->getWidth() > $maxWidth) $this->resize($maxWidth);
        return $this;
    }

    public function getWidth(): int { return ($this->driver === 'gd') ? imagesx($this->image) : $this->image->getImageWidth(); }
    public function getHeight(): int { return ($this->driver === 'gd') ? imagesy($this->image) : $this->image->getImageHeight(); }

    public function resize(int $width, ?int $height = null): self
    {
        $origW = $this->getWidth(); $origH = $this->getHeight();
        if (!$height) $height = (int)($origH * ($width / $origW));
        
        if ($this->driver === 'gd') {
            $new = imagecreatetruecolor($width, $height);
            imagecopyresampled($new, $this->image, 0, 0, 0, 0, $width, $height, $origW, $origH);
            imagedestroy($this->image); $this->image = $new;
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
                ImageFilter::SEPIA => $this->applySepiaGd(),
                ImageFilter::BLUR => imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR),
                ImageFilter::BRIGHTNESS => imagefilter($this->image, IMG_FILTER_BRIGHTNESS, 20),
            };
        }
        return $this;
    }

    private function applySepiaGd(): void
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 90, 60, 40);
    }

    public function watermark(string $text, WatermarkPosition $pos = WatermarkPosition::BOTTOM_RIGHT): self
    {
        if ($this->driver === 'gd') {
            $white = imagecolorallocate($this->image, 255, 255, 255);
            imagestring($this->image, 5, 10, 10, $text, $white);
        }
        return $this;
    }

    public function convertToWebP(string $dest, int $quality = 80): bool
    {
        if ($this->driver === 'gd') {
            return imagewebp($this->image, $dest, $quality);
        } else {
            $this->image->setImageFormat('webp');
            $this->image->setCompressionQuality($quality);
            return $this->image->writeImage($dest);
        }
    }
}
