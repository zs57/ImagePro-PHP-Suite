# ImagePro v1.0.0 (Global Release)

[![CI](https://github.com/zs57/ImagePro-PHP-Suite/actions/workflows/ci.yml/badge.svg)](https://github.com/zs57/ImagePro-PHP-Suite/actions)
[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](CHANGELOG.md)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

**ImagePro v1.0.0** is a professional, production-ready image processing library for PHP 8.1+. It provides seamless abstraction between **GD** and **Imagick**, delivering enterprise-grade resiliency and standardized output.

---

## 🏛️ Professional Standards
- **PSR-4 Compliant**: `ImagePro\` namespace architecture.
- **PHP 8.1+ Optimized**: Native Enums and `GdImage` object support.
- **Driver Parity**: Identical results on both GD and Imagick engines.
- **Hardened Security**: Deep MIME binary inspection.

## 🛠️ Installation
```bash
composer require zs57/imagepro
```
*Or manual inclusion:* `require_once 'vendor/autoload.php';`

## 🚀 Usage
```php
use ImagePro\ImagePro;

ImagePro::open('source.jpg')
    ->autoOrient()
    ->stripMetadata()
    ->resize(800)
    ->save('output.webp', quality: 80);
```

## 📄 License
MIT License. Part of the **zs57 Open Source Initiative**.
