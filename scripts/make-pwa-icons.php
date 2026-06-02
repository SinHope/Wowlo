<?php

/**
 * One-off: generate PWA app icons from the Wowlo logo.
 * Produces a 192, 512 (transparent) and a 512 maskable (padded on brand cream).
 * Run: php scripts/make-pwa-icons.php
 */

$src = __DIR__ . '/../public/images/logo/wowlo_logo.png';
$outDir = __DIR__ . '/../public/images/pwa';

if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

[$w, $h] = getimagesize($src);
echo "Source: {$w}x{$h}\n";

$logo = imagecreatefrompng($src);

/**
 * Resize the logo to fit within a square of $size, centered, on a given
 * background. $scale shrinks the logo within the square (for maskable padding).
 * $bg = null → transparent.
 */
function render($logo, int $srcW, int $srcH, int $size, float $scale, ?array $bg, string $out): void
{
    $canvas = imagecreatetruecolor($size, $size);
    imagealphablending($canvas, false);
    imagesavealpha($canvas, true);

    if ($bg === null) {
        $fill = imagecolorallocatealpha($canvas, 0, 0, 0, 127); // transparent
    } else {
        $fill = imagecolorallocate($canvas, $bg[0], $bg[1], $bg[2]);
    }
    imagefilledrectangle($canvas, 0, 0, $size, $size, $fill);
    imagealphablending($canvas, true);

    // Fit logo into (size * scale) box, preserve aspect ratio.
    $box = (int) round($size * $scale);
    $ratio = min($box / $srcW, $box / $srcH);
    $dstW = (int) round($srcW * $ratio);
    $dstH = (int) round($srcH * $ratio);
    $dstX = (int) round(($size - $dstW) / 2);
    $dstY = (int) round(($size - $dstH) / 2);

    imagecopyresampled($canvas, $logo, $dstX, $dstY, 0, 0, $dstW, $dstH, $srcW, $srcH);
    imagepng($canvas, $out);
    imagedestroy($canvas);
    echo "Wrote {$out}\n";
}

// Transparent icons (standard).
render($logo, $w, $h, 192, 0.90, null, "{$outDir}/icon-192.png");
render($logo, $w, $h, 512, 0.90, null, "{$outDir}/icon-512.png");

// Maskable: extra padding (safe zone) on brand cream so Android crop doesn't clip it.
render($logo, $w, $h, 512, 0.62, [255, 251, 245], "{$outDir}/icon-512-maskable.png");

imagedestroy($logo);
echo "Done.\n";
