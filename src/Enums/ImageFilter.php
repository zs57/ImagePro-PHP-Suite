<?php

declare(strict_types=1);

namespace ImagePro\Enums;

enum ImageFilter: string {
    case GREYSCALE = 'grey';
    case SEPIA = 'sepia';
    case BLUR = 'blur';
    case SHARPEN = 'sharpen';
    case CONTRAST = 'contrast';
}
