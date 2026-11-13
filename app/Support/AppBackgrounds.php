<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Reads the app-background image library from public/app-background.
 * Layout: public/app-background/<Category>/<image>. Drop files in, they appear
 * in Settings — no code or database change needed.
 */
class AppBackgrounds
{
    public const DIR = 'app-background';

    private const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'avif'];

    /** @return array<string, list<array{path: string, name: string, url: string}>> category => images (empty categories included) */
    public static function all(): array
    {
        $root = public_path(self::DIR);
        if (! File::isDirectory($root)) {
            return [];
        }

        $out = [];
        foreach (File::directories($root) as $dir) {
            $category = basename($dir);
            $images = [];
            foreach (File::files($dir) as $file) {
                if (! in_array(strtolower($file->getExtension()), self::EXTENSIONS, true)) {
                    continue;
                }
                $path = $category . '/' . $file->getFilename();
                $images[] = [
                    'path' => $path,
                    'name' => pathinfo($file->getFilename(), PATHINFO_FILENAME),
                    'url'  => self::url($path),
                ];
            }
            usort($images, fn ($a, $b) => strnatcasecmp($a['name'], $b['name']));
            $out[$category] = $images;
        }
        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }

    /**
     * True only for "<Category>/<file>" that really exists on disk with an allowed
     * extension. Blocks path traversal — the value is user input.
     */
    public static function isValid(?string $path): bool
    {
        if ($path === null || $path === '' || str_contains($path, '..') || str_contains($path, '\\')) {
            return false;
        }
        $parts = explode('/', $path);
        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '' || $parts[0][0] === '.' || $parts[1][0] === '.') {
            return false;
        }
        if (! in_array(strtolower(pathinfo($parts[1], PATHINFO_EXTENSION)), self::EXTENSIONS, true)) {
            return false;
        }

        return is_file(public_path(self::DIR . '/' . $path));
    }

    /** Public URL for a stored path (each segment URL-encoded — folders have spaces). */
    public static function url(string $path): string
    {
        return asset(self::DIR . '/' . implode('/', array_map('rawurlencode', explode('/', $path))));
    }

    /** URL for the user's chosen background, or null if none / the file was removed. */
    public static function urlFor(?string $path): ?string
    {
        return self::isValid($path) ? self::url($path) : null;
    }
}
