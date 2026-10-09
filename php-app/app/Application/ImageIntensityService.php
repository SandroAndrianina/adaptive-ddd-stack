<?php
namespace App\Application;

class ImageIntensityService
{
    public function meanIntensity(string $filePath): int
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException("Image not found: $filePath");
        }
        $info = @getimagesize($filePath);
        if ($info === false) {
            throw new \RuntimeException("Not a valid image");
        }
        [$w, $h, $type] = $info;
        $img = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($filePath),
            IMAGETYPE_PNG  => @imagecreatefrompng($filePath),
            default        => false,
        };
        if ($img === false) throw new \RuntimeException("GD cannot decode (type=$type)");

        $sum = 0.0; $n = 0;
        for ($y = 0; $y < $h; $y += 4) {
            for ($x = 0; $x < $w; $x += 4) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8)  & 0xFF;
                $b =  $rgb        & 0xFF;
                $sum += 0.299 * $r + 0.587 * $g + 0.114 * $b;
                $n++;
            }
        }
        imagedestroy($img);
        return $n === 0 ? 0 : (int) round(($sum / $n) / 2.55);
    }
}