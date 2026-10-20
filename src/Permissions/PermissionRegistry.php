<?php

namespace Ssdev\Headless\Permissions;

use InvalidArgumentException;

final class PermissionRegistry
{
    /** @var array<class-string<HeadlessPermission>, true> */
    private array $enums = [];

    /**
     * @param  iterable<string>  $enums
     */
    public function __construct(iterable $enums)
    {
        foreach ($enums as $enum) {
            $this->register($enum);
        }
    }

    public function register(string $enum): void
    {
        if (! is_subclass_of($enum, HeadlessPermission::class)) {
            throw new InvalidArgumentException("[{$enum}] must be an enum implementing ".HeadlessPermission::class.'.');
        }

        $this->enums[$enum] = true;
    }

    /**
     * @return list<HeadlessPermission>
     */
    public function all(): array
    {
        $permissions = [];

        foreach (array_keys($this->enums) as $enum) {
            array_push($permissions, ...$enum::cases());
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_map(fn (HeadlessPermission $permission) => (string) $permission->value, $this->all());
    }

    /**
     * @return array<string, list<HeadlessPermission>>
     */
    public function grouped(): array
    {
        $groups = [];

        foreach ($this->all() as $permission) {
            $groups[$permission->group()][] = $permission;
        }

        return $groups;
    }
}
