# ImagePro v2.0.0 (Professional Reality)

**ImagePro** is a lightweight PHP image processing library for GD and Imagick. It focuses on technical clarity, driver parity, and practical utility for modern web applications.

---

## 🏛️ Practical Standards
- **Standard Autoloading**: PSR-4 compliant (`ImagePro\`).
- **Driver Parity**: 100% feature match between **GD** and **Imagick**.
- **Practical Features**: `autoOrient()`, `stripMetadata()`, `fromUpload()`, and binary `output()`.
- **Honest Engineering**: Includes unit tests and automated CI.

## 🛠️ Usage
```php
use ImagePro\ImagePro;

// Process an uploaded file directly
ImagePro::fromUpload($_FILES['photo'])
    ->autoOrient()
    ->resize(800)
    ->output('webp'); // Stream directly to browser
```

## 📊 Support Matrix
| Format | Load (GD) | Save (GD) | Load (Imagick) | Save (Imagick) |
| ------ | --------- | --------- | -------------- | -------------- |
| JPEG   | ✅        | ✅        | ✅             | ✅             |
| PNG    | ✅        | ✅        | ✅             | ✅             |
| WebP   | ✅        | ✅        | ✅             | ✅             )

## 📄 Documentation
Detailed guides are available in the [docs/](docs/) directory:
- [Installation](docs/installation.md)
- [API Reference](docs/api.md)
- [Real-world Examples](docs/examples.md)

## ⚖️ License
MIT License. Part of the **zs57 Open Source Initiative**.
