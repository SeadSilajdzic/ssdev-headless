<?php

namespace Ssdev\Headless\Installation;

use Illuminate\Filesystem\Filesystem;

final class ProjectFiles
{
    public function __construct(
        private string $root,
        private Filesystem $filesystem,
    ) {}

    public function path(string $relative): string
    {
        return rtrim($this->root, '/\\').'/'.ltrim($relative, '/\\');
    }

    public function exists(string $relative): bool
    {
        return $this->filesystem->exists($this->path($relative));
    }

    public function get(string $relative): string
    {
        return $this->filesystem->get($this->path($relative));
    }

    public function put(string $relative, string $contents): void
    {
        $this->filesystem->ensureDirectoryExists(dirname($this->path($relative)));
        $this->filesystem->put($this->path($relative), $contents);
    }

    public function delete(string $relative): void
    {
        $this->filesystem->delete($this->path($relative));
    }

    /**
     * @return list<string> paths relative to the project root
     */
    public function glob(string $pattern): array
    {
        $prefix = strlen($this->path(''));

        return array_values(array_map(
            fn (string $path) => str_replace('\\', '/', substr($path, $prefix)),
            $this->filesystem->glob($this->path($pattern)),
        ));
    }

    public function contains(string $relative, string $needle): bool
    {
        return $this->exists($relative) && str_contains($this->get($relative), $needle);
    }

    /**
     * @return array<string, mixed>
     */
    public function getJson(string $relative): array
    {
        return json_decode($this->get($relative), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function putJson(string $relative, array $data): void
    {
        $this->put($relative, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    }

    public function insertAfterPattern(string $relative, string $pattern, string $insertion): bool
    {
        $contents = $this->get($relative);

        if (preg_match($pattern, $contents, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return false;
        }

        $offset = $match[0][1] + strlen($match[0][0]);
        $this->put($relative, substr($contents, 0, $offset).$insertion.substr($contents, $offset));

        return true;
    }
}
