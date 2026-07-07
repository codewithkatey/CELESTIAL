<?php

namespace App\Services;

class ImageLibrary
{
    private string $directory = 'assets/images';

    public function list(): array
    {
        $path = public_path($this->directory);

        if (! is_dir($path)) {
            return [];
        }

        $files = glob($path.'/*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE) ?: [];

        sort($files);

        return array_map(function (string $file) {
            $filename = basename($file);

            return [
                'filename' => $filename,
                'path' => '/'.$this->directory.'/'.$filename,
                'url' => asset($this->directory.'/'.$filename),
            ];
        }, $files);
    }

    public function isValidPath(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        $path = $this->normalizePath($path);
        $allowed = array_column($this->list(), 'path');

        return in_array($path, $allowed, true);
    }

    public function normalizePath(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#^https?://[^/]+#', '', $path) ?? $path;

        if (! str_starts_with($path, '/')) {
            $path = '/'.$path;
        }

        return $path;
    }

    public function resolveUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'data:') || str_starts_with($path, 'http')) {
            return $path;
        }

        $normalized = ltrim($this->normalizePath($path), '/');

        return asset($normalized);
    }

    public function storeUpload($file): string
    {
        $directory = public_path($this->directory);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = uniqid('product_', true).'.'.$extension;

        $file->move($directory, $filename);

        return '/'.$this->directory.'/'.$filename;
    }
}
