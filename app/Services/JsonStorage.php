<?php

namespace App\Services;

use Illuminate\Support\Str;

class JsonStorage
{
    private string $dataPath;

    public function __construct()
    {
        $this->dataPath = $this->resolveDataPath();
        $this->ensureDataDirectory();
        $this->seedDefaults();
    }

    private function resolveDataPath(): string
    {
        if ($path = env('JSON_DATA_PATH')) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        if (env('VERCEL')) {
            return '/tmp/celestial-data';
        }

        return storage_path('app/data');
    }

    private function ensureDataDirectory(): void
    {
        if (! is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
    }

    private function seedDefaults(): void
    {
        $seedPath = storage_path('app/data');

        foreach (['products.json', 'categories.json', 'users.json'] as $file) {
            $target = $this->dataPath.DIRECTORY_SEPARATOR.$file;

            if (! file_exists($target) && file_exists($seedPath.DIRECTORY_SEPARATOR.$file)) {
                copy($seedPath.DIRECTORY_SEPARATOR.$file, $target);
            }

            if (! file_exists($target)) {
                $default = $file === 'categories.json' ? [] : [];
                $this->write($file, $default);
            }
        }
    }

    public function read(string $file): array
    {
        $path = $this->path($file);

        if (! file_exists($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        $data = json_decode($contents, true);

        return is_array($data) ? $data : [];
    }

    public function write(string $file, array $data): bool
    {
        $path = $this->path($file);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return file_put_contents($path, $json) !== false;
    }

    public function path(string $file): string
    {
        return $this->dataPath.DIRECTORY_SEPARATOR.$file;
    }

    public function generateId(string $prefix = ''): string
    {
        return $prefix.Str::uuid()->toString();
    }

    public function now(): string
    {
        return now()->toIso8601String();
    }
}
