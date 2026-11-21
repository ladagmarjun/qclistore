<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Shrinks uploaded storefront images before they're saved: scales them down to a
 * sensible width for where they're shown and re-encodes them as WebP.
 *
 * Uses PHP's GD extension. If GD can't handle a file (no WebP support on the
 * server, a huge image, an animated GIF), the original is stored unchanged, so
 * an upload never fails because of optimisation.
 */
class ImageOptimizer
{
    /** Widest an image needs to be in each folder, in pixels. */
    public const MAX_WIDTHS = [
        'banners' => 1920,
        'products' => 1200,
        'categories' => 800,
    ];

    private const WEBP_QUALITY = 82;

    /** Skip images above this many pixels: decoding them could exceed PHP's memory limit. */
    private const MAX_PIXELS = 30_000_000;

    /**
     * Store the upload on the public disk under $folder and return its path there.
     */
    public function store(UploadedFile $file, string $folder): string
    {
        try {
            $webp = $this->toWebp($file, self::MAX_WIDTHS[$folder] ?? 1200);
        } catch (Throwable $e) {
            Log::warning('Image optimisation failed; storing the original.', ['error' => $e->getMessage()]);
            $webp = null;
        }

        if ($webp === null) {
            $path = $file->store($folder, 'public');
        } else {
            $path = $folder.'/'.Str::random(40).'.webp';
            $path = Storage::disk('public')->put($path, $webp) ? $path : false;
        }

        // A full disk or wrong permissions on storage/: fail loudly rather than return a broken link.
        if ($path === false) {
            throw new RuntimeException("Couldn't save the uploaded image to the public disk.");
        }

        return $path;
    }

    /**
     * The image as WebP bytes, or null when it should be stored as uploaded.
     */
    private function toWebp(UploadedFile $file, int $maxWidth): ?string
    {
        if (! function_exists('imagewebp') || ! function_exists('imagecreatefromstring')) {
            return null;
        }

        $info = @getimagesize($file->getRealPath());
        if ($info === false) {
            return null;
        }

        [$width, $height] = $info;
        $mime = $info['mime'];

        // GD would keep only the first frame of an animation.
        if ($mime === 'image/gif' || $width * $height > self::MAX_PIXELS) {
            return null;
        }

        $contents = (string) file_get_contents($file->getRealPath());
        $image = @imagecreatefromstring($contents);
        if (! $image instanceof GdImage) {
            return null;
        }

        $image = $this->applyExifOrientation($image, $file, $mime);
        $image = $this->scaleDown($image, $maxWidth);

        // GD can't encode palette images as WebP.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        ob_start();
        $encoded = imagewebp($image, null, self::WEBP_QUALITY);
        $webp = (string) ob_get_clean();
        imagedestroy($image);

        if (! $encoded || $webp === '') {
            return null;
        }

        // Already small and not resized: keep whichever file is lighter.
        $resized = $width > $maxWidth;
        if (! $resized && strlen($webp) >= strlen($contents)) {
            return null;
        }

        return $webp;
    }

    private function scaleDown(GdImage $image, int $maxWidth): GdImage
    {
        if (imagesx($image) <= $maxWidth) {
            return $image;
        }

        $scaled = imagescale($image, $maxWidth, -1, IMG_BICUBIC);
        if (! $scaled instanceof GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $scaled;
    }

    /**
     * Phone photos are often stored sideways with an EXIF flag saying how to turn
     * them. GD ignores that flag, so rotate the pixels before the flag is dropped.
     */
    private function applyExifOrientation(GdImage $image, UploadedFile $file, string $mime): GdImage
    {
        if ($mime !== 'image/jpeg' || ! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);
        if (! $rotated instanceof GdImage) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
