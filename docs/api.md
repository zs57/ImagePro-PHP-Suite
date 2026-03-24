# API Reference | ImagePro v2.0.0

## Static Factories
### `ImagePro::open(string $path)`
Opens an image from a local file path.

### `ImagePro::fromUpload(array $file)`
Directly accepts an entry from `$_FILES['input_name']`.

---

## Core Methods
### `autoOrient()`
Automatically corrects JPEG rotation based on EXIF data.

### `resize(int $width, ?int $height = null)`
Resizes the image. If height is null, maintains aspect ratio.

### `compressToSize(int $targetBytes, string $format = 'webp')`
Iteratively reduces quality until the file size is under the target.

### `output(string $format = 'webp', int $quality = 82)`
Sends appropriate headers and echoes the binary stream.
