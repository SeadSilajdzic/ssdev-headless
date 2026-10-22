<?php

namespace Ssdev\Headless\Features\Roles;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LogicException;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Ssdev\Headless\Permissions\GlobalAssignments;

/**
 * auth:sanctum makes "sanctum" the default guard of an API request, but roles belong to the guard of the user model.
 */
final class RoleGuard
{
    public function __construct(
        private PermissionRegistrar $registrar,
        private Repository $config,
        private GlobalAssignments $assignments,
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

    /**
     * The role's members outside any team; with teams on, team members get their roles inside the team.
     *
     * @return BelongsToMany<Model, Role>
     */
    public function members(Role $role): BelongsToMany
    {
        return $this->assignments->onRelation($role->users());
    }
}
