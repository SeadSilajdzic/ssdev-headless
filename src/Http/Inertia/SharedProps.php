<?php

namespace Ssdev\Headless\Http\Inertia;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Ssdev\Headless\Features\FeatureModule;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Http\Resources\AuthUserResource;
use Ssdev\Headless\Navigation\NavigationRegistry;
use Ssdev\Headless\Permissions\PermissionRegistry;
use Ssdev\Headless\Support\Flash;
use Ssdev\Headless\Translations\HeadlessTranslations;

final class SharedProps
{
    public function __construct(
        private FeatureRegistry $features,
        private NavigationRegistry $navigation,
        private HeadlessTranslations $translations,
        private PermissionRegistry $permissions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'auth' => [
                'user' => fn () => $request->user() ? AuthUserResource::make($request->user())->resolve($request) : null,
                'permissions' => fn () => $this->grantedPermissions($request),
            ],
            'headless' => [
                'appName' => config('app.name'),
                'locale' => app()->getLocale(),
                'features' => fn () => array_map(fn (FeatureModule $module) => $module->key(), $this->features->enabled()),
                'navigation' => fn () => $this->navigation->forUser($request->user(), $request->route()?->getName()),
                'translations' => fn () => $this->translations->forLocale(app()->getLocale()),
                'logoutUrl' => fn () => Route::has('logout') ? route('logout') : null,
                'honeypot' => config('headless.honeypot.field'),
            ],
            'flash' => fn () => [
                'success' => $request->hasSession() ? $request->session()->get(Flash::SUCCESS) : null,
                'error' => $request->hasSession() ? $request->session()->get(Flash::ERROR) : null,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function grantedPermissions(Request $request): array
    {
        $user = $request->user();

        if (! $user instanceof Authorizable) {
            return [];
        }

        return array_values(array_filter($this->permissions->names(), fn (string $permission) => $user->can($permission)));
    }
}
