<?php

namespace Ssdev\Headless\Features\Roles\Http\Requests;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Ssdev\Headless\Features\Roles\RoleData;
use Ssdev\Headless\Features\Roles\RoleGuard;

/**
 * Which permissions the actor may give is checked by RoleService, which also guards the API.
 */
final class SaveRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail): void {
                // Through the role model, so its scopes apply: with tenancy a name is taken only within the tenant.
                $taken = app(RoleGuard::class)->roles()
                    ->where('name', $value)
                    ->when($this->route('role') !== null, fn (Builder $roles) => $roles->whereKeyNot($this->route('role')))
                    ->exists();

                if ($taken) {
                    $fail(__('validation.unique', ['attribute' => $attribute]));
                }
            }],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct'],
        ];
    }

    public function toData(): RoleData
    {
        return new RoleData($this->validated('name'), array_values($this->validated('permissions')));
    }
}
