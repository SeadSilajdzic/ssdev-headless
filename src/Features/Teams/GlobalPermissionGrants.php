<?php

namespace Ssdev\Headless\Features\Teams;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Spatie\Permission\PermissionRegistrar;
use Ssdev\Headless\Permissions\GlobalAssignments;

/**
 * Names of the permissions a user holds outside any team, directly or through a role. Read once per request,
 * so checking every permission for a page costs one query.
 */
final class GlobalPermissionGrants
{
    /** @var array<string, list<string>> */
    private array $granted = [];

    public function __construct(
        private ConnectionInterface $database,
        private Repository $config,
        private PermissionRegistrar $registrar,
    ) {}

    /**
     * @return list<string>
     */
    public function of(Model $user): array
    {
        return $this->granted[$user->getMorphClass().'|'.$user->getKey()] ??= $this->query($user);
    }

    /**
     * @return list<string>
     */
    private function query(Model $user): array
    {
        $tables = $this->config->get('permission.table_names');
        $morphKey = $this->config->get('permission.column_names.model_morph_key');
        $team = $this->config->get('permission.column_names.team_foreign_key');
        $pivotRole = $this->registrar->pivotRole;
        $pivotPermission = $this->registrar->pivotPermission;
        $ownedBy = fn (Builder $query, string $table) => $query
            ->where($table.'.'.$team, TeamContext::GLOBAL)
            ->where($table.'.'.GlobalAssignments::MODEL_TYPE, $user->getMorphClass())
            ->where($table.'.'.$morphKey, $user->getKey());

        return $this->database->table($tables['permissions'])
            ->whereIn('id', fn (Builder $direct) => $ownedBy($direct->select($pivotPermission)->from($tables['model_has_permissions']), $tables['model_has_permissions']))
            ->orWhereIn('id', fn (Builder $viaRoles) => $ownedBy(
                $viaRoles->select($tables['role_has_permissions'].'.'.$pivotPermission)
                    ->from($tables['role_has_permissions'])
                    ->join($tables['model_has_roles'], $tables['model_has_roles'].'.'.$pivotRole, '=', $tables['role_has_permissions'].'.'.$pivotRole),
                $tables['model_has_roles'],
            ))
            ->pluck('name')
            ->map(fn (mixed $name): string => (string) $name)
            ->all();
    }
}
