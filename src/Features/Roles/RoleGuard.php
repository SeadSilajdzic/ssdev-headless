<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use LogicException;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * auth:sanctum makes "sanctum" the default guard of an API request, but roles belong to the guard of the user model.
 */
final class RoleGuard
{
    public function __construct(
        private PermissionRegistrar $registrar,
        private Repository $config,
    ) {}

    public function name(): string
    {
        $model = $this->config->get('auth.providers.users.model');

        if (! is_string($model) || $model === '') {
            throw new LogicException('Set auth.providers.users.model, the user model whose guard roles belong to.');
        }

        return Guard::getDefaultName($model);
    }

    /**
     * Built from a role that carries the guard, as Spatie resolves the users relation from it.
     *
     * @return Builder<Role>
     */
    public function roles(): Builder
    {
        /** @var class-string<Role> $roleClass */
        $roleClass = $this->registrar->getRoleClass();

        return (new $roleClass(['guard_name' => $this->name()]))->newQuery()->where('guard_name', $this->name());
    }
}
