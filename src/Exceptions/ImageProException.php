<?php

declare(strict_types=1);

namespace ImagePro\Exceptions;

class ImageProException extends \RuntimeException {}
class ImageNotFoundException extends ImageProException {}
class ImageProcessingException extends ImageProException {}
class HardenedSecurityException extends ImageProException {}
class UnsupportedFormatException extends ImageProException {}
