<?php

namespace Ssdev\Headless\Navigation;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;

final class NavigationRegistry
{
    /** @var list<NavigationItem> */
    private array $items = [];

    public function add(NavigationItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return list<array{group: string, label: string, items: list<array{key: string, label: string, url: string, icon: string, active: bool}>}>
     */
    public function forUser(?Authenticatable $user, ?string $currentRoute): array
    {
        if ($user === null) {
            return [];
        }

        $sections = [];

        foreach (NavigationGroup::cases() as $group) {
            $items = $this->visibleItems($user, $group);

            if ($items === []) {
                continue;
            }

            $sections[] = [
                'group' => $group->value,
                'label' => __("headless::navigation.groups.{$group->value}"),
                'items' => array_map(fn (NavigationItem $item) => [
                    'key' => $item->key,
                    'label' => __($item->label),
                    'url' => route($item->route),
                    'icon' => $item->icon,
                    'active' => $this->isActive($item, $currentRoute),
                ], $items),
            ];
        }

        return $sections;
    }

    /**
     * @return list<NavigationItem>
     */
    private function visibleItems(Authenticatable $user, NavigationGroup $group): array
    {
        $items = array_values(array_filter(
            $this->items,
            fn (NavigationItem $item) => $item->group === $group && $this->canSee($user, $item),
        ));

        // usort is stable since PHP 8.0, so registration order breaks ties.
        usort($items, fn (NavigationItem $a, NavigationItem $b) => $a->order <=> $b->order);

        return $items;
    }

    private function canSee(Authenticatable $user, NavigationItem $item): bool
    {
        if ($item->permission === null) {
            return true;
        }

        return $user instanceof Authorizable && $user->can($item->permission);
    }

    private function isActive(NavigationItem $item, ?string $currentRoute): bool
    {
        if ($currentRoute === null) {
            return false;
        }

        return $currentRoute === $item->route || str_starts_with($currentRoute, $item->route.'.');
    }
}
