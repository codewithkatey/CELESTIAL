<?php

namespace App\Services;

use Illuminate\Support\Str;

class JsonStorage
{
    private string $dataPath;

    private string $seedPath;

    private bool $usesEphemeralStorage;

    public function __construct()
    {
        $this->seedPath = $this->resolveSeedPath();
        $this->dataPath = $this->resolveDataPath();
        $this->usesEphemeralStorage = $this->dataPath !== $this->seedPath;
        $this->ensureDataDirectory();
        $this->seedDefaults();
    }

    private function resolveSeedPath(): string
    {
        $rootData = base_path('data');

        if (is_dir($rootData) && file_exists($rootData.DIRECTORY_SEPARATOR.'products.json')) {
            return $rootData;
        }

        return storage_path('app/data');
    }

    private function resolveDataPath(): string
    {
        if ($path = env('JSON_DATA_PATH')) {
            return rtrim($path, DIRECTORY_SEPARATOR);
        }

        if (env('VERCEL')) {
            return '/tmp/celestial-data';
        }

        return $this->seedPath;
    }

    private function ensureDataDirectory(): void
    {
        if (! is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0755, true);
        }
    }

    private function seedDefaults(): void
    {
        foreach (['products.json', 'categories.json', 'users.json'] as $file) {
            $this->syncFromSeed($file);
        }
    }

    private function syncFromSeed(string $file): void
    {
        $seedFile = $this->seedPath.DIRECTORY_SEPARATOR.$file;
        $targetFile = $this->path($file);

        if (! file_exists($seedFile)) {
            if (! file_exists($targetFile)) {
                $this->write($file, []);
            }

            return;
        }

        if (! file_exists($targetFile) || $this->isEmptyDataFile($targetFile)) {
            copy($seedFile, $targetFile);
        }
    }

    private function isEmptyDataFile(string $path): bool
    {
        if (! file_exists($path)) {
            return true;
        }

        $contents = file_get_contents($path);
        $data = json_decode($contents, true);

        if (! is_array($data)) {
            return true;
        }

        return count($data) === 0;
    }

    private function readFile(string $path): array
    {
        if (! file_exists($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        $data = json_decode($contents, true);

        return is_array($data) ? $data : [];
    }

    public function read(string $file): array
    {
        $data = $this->readFile($this->path($file));

        if (empty($data)) {
            $seedFile = $this->seedPath.DIRECTORY_SEPARATOR.$file;

            if (file_exists($seedFile)) {
                $data = $this->readFile($seedFile);

                if (! empty($data) && $this->usesEphemeralStorage) {
                    $this->write($file, $data);
                }
            }
        }

        return $data;
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
