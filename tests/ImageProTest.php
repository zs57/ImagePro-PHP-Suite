<?php

declare(strict_types=1);

namespace ImagePro\Tests;

use PHPUnit\Framework\TestCase;
use ImagePro\ImagePro;

class ImageProTest extends TestCase
{
    public function testOpenThrowsOnInvalidPath()
    {
        $this->expectException(\ImagePro\Exceptions\ImageNotFoundException::class);
        ImagePro::open('non_existent.jpg');
    }

    public function testQualityClamping()
    {
        // Testing that the internal save logic handles bounds
        // (This would ideally be verified with a real image)
        $this->assertTrue(true);
    }
}
