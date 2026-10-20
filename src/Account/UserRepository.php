<?php

namespace Ssdev\Headless\Account;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User;

final class UserRepository
{
    public function __construct(private Repository $config) {}

    public function find(int|string $key): ?User
    {
        return $this->query()->find($key);
    }

    public function findForUpdate(int|string $key): ?User
    {
        return $this->query()->lockForUpdate()->find($key);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }

    public function emailTaken(string $email, int|string|null $exceptKey = null): bool
    {
        return $this->query()
            ->where('email', $email)
            ->when($exceptKey !== null, fn (Builder $query) => $query->whereKeyNot($exceptKey))
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): User
    {
        $user = $this->newModel();
        $user->forceFill($attributes)->save();

        return $user;
    }

    /**
     * @return Builder<User>
     */
    private function query(): Builder
    {
        return $this->newModel()->newQuery();
    }

    private function newModel(): User
    {
        /** @var class-string<User> $model */
        $model = $this->config->get('auth.providers.users.model');

        return new $model;
    }
}
