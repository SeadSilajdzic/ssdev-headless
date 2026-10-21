<?php

namespace Ssdev\Headless\Account;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\User;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;
use LogicException;
use Ssdev\Headless\Permissions\PermissionRegistry;

final class PersonalAccessTokenService
{
    public function __construct(
        private PermissionRegistry $permissions,
        private Repository $config,
    ) {}

    /**
     * @param  list<string>  $abilities
     */
    public function create(User $user, string $name, array $abilities): NewAccessToken
    {
        if (! method_exists($user, 'createToken')) {
            throw new LogicException('Add the Laravel\Sanctum\HasApiTokens trait to your User model.');
        }

        return $user->createToken($name, $abilities, $this->expiresAt());
    }

    /**
     * @return Collection<int, PersonalAccessToken>
     */
    public function forUser(User $user): Collection
    {
        return PersonalAccessToken::query()
            ->whereMorphedTo('tokenable', $user)
            ->latest('id')
            ->get();
    }

    /**
     * Only the owner's tokens can be revoked; any other id reports nothing found.
     */
    public function revoke(User $user, int|string $tokenId): bool
    {
        return PersonalAccessToken::query()
            ->whereMorphedTo('tokenable', $user)
            ->whereKey($tokenId)
            ->delete() > 0;
    }

    /**
     * A token can only be given permissions its owner has, as seen by the current request.
     *
     * @return list<string>
     */
    public function grantableAbilities(User $user): array
    {
        return array_values(array_filter($this->permissions->names(), fn (string $permission) => $user->can($permission)));
    }

    /**
     * @return list<array{group: string, label: string, abilities: list<array{name: string}>}>
     */
    public function grantableGroups(User $user): array
    {
        $grantable = $this->grantableAbilities($user);
        $groups = [];

        foreach ($this->permissions->grouped() as $group => $permissions) {
            $abilities = array_values(array_filter(
                array_map(fn ($permission) => (string) $permission->value, $permissions),
                fn (string $name) => in_array($name, $grantable, true),
            ));

            if ($abilities !== []) {
                $groups[] = ['group' => $group, 'label' => __($group), 'abilities' => array_map(fn (string $name) => ['name' => $name], $abilities)];
            }
        }

        return $groups;
    }

    private function expiresAt(): ?CarbonImmutable
    {
        $minutes = $this->config->get('headless.api.token_expiration');

        return $minutes === null ? null : CarbonImmutable::now()->addMinutes((int) $minutes);
    }
}
