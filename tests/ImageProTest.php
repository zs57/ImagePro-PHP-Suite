<?php

declare(strict_types=1);

namespace ImagePro\Tests;

use PHPUnit\Framework\TestCase;
use ImagePro\ImagePro;
use ImagePro\Enums\ImageFilter;

class ImageProTest extends TestCase
{
    /**
     * Note: Full tests would require image fixtures.
     * This is a placeholder for basic structural validation.
     */
    public function testOpenThrowsOnInvalidPath()
    {
        $this->expectException(\ImagePro\Exceptions\ImageNotFoundException::class);
        ImagePro::open('non_existent.jpg');
    }
}
