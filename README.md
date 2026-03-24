# ImagePro v6.00 (Professional Library)

**ImagePro v6.00** is a certified, production-ready image processing library for PHP 8.1+. It provides seamless abstraction between **GD** and **Imagick**, ensuring that your application remains functional across all server environments.

---

## 🏛️ Professional Standards
- **PHP 8.1+ Compatibility**: Fully verified `GdImage` object management.
- **Multi-Driver Parity**: Identical results whether using GD or Imagick.
- **Configurable Resources**: Fine-grained control over memory limits and quality via `withMemoryLimit()`.
- **Advanced Processing**: Full implementation of `resize()`, `filter()`, and `stripMetadata()`.

## 📂 Project Structure
- `src/ImagePro.php` - The core multi-driver engine.
- `src/docs.html` - Standalone Developer Manual.
- `LICENSE` - MIT License.

## 🛠️ Quick Start
```php
require_once 'src/ImagePro.php';
use ImagePro\Enterprise\ImagePro;

ImagePro::open('source.jpg')
    ->withMemoryLimit('256M')
    ->resize(1200)
    ->save('optimized.webp', quality: 85);
```

This project is part of the **zs57 Open Source Initiative**.

## 📄 License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---
<p align="center">
  <img src="https://img.shields.io/badge/Maintained%20by-zs57-blue?style=for-the-badge" alt="Maintained by zs57">
</p>
