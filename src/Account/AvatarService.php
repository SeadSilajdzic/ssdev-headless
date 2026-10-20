<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Auth\User;
use Illuminate\Http\UploadedFile;
use RuntimeException;

final class AvatarService
{
    public function __construct(
        private Factory $storage,
        private Repository $config,
    ) {}

    public function replace(User $user, UploadedFile $file): void
    {
        $previous = $user->getAttribute('avatar_path');

        // store() names the file after a random hash and the extension guessed from its content.
        $path = $file->store($this->setting('directory'), $this->setting('disk'));

        if ($path === false) {
            throw new RuntimeException('The avatar could not be stored.');
        }

        $user->forceFill(['avatar_path' => $path])->save();

        if (is_string($previous)) {
            $this->disk()->delete($previous);
        }
    }

    public function remove(User $user): void
    {
        $previous = $user->getAttribute('avatar_path');

        $user->forceFill(['avatar_path' => null])->save();

        if (is_string($previous)) {
            $this->disk()->delete($previous);
        }
    }

    public function url(User $user): ?string
    {
        return is_string($user->getAttribute('avatar_path')) ? $this->disk()->url($user->getAttribute('avatar_path')) : null;
    }

    /**
     * @return array{accept: list<string>, maxKilobytes: int}
     */
    public function constraints(): array
    {
        return [
            'accept' => array_map(fn (string $extension) => '.'.$extension, $this->mimes()),
            'maxKilobytes' => (int) $this->config->get('headless.avatars.max_kilobytes'),
        ];
    }

    /**
     * @return list<string>
     */
    public function mimes(): array
    {
        return array_values($this->config->get('headless.avatars.mimes', []));
    }

    private function disk(): Filesystem
    {
        return $this->storage->disk($this->setting('disk'));
    }

    private function setting(string $key): string
    {
        return (string) $this->config->get("headless.avatars.{$key}");
    }
}
