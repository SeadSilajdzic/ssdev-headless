<?php

namespace Ssdev\Headless\Features\Roles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique((string) config('permission.table_names.roles'), 'name')
                    ->where('guard_name', app(RoleGuard::class)->name())
                    ->ignore($this->route('role')),
            ],
            'permissions' => ['present', 'array'],
            'permissions.*' => ['string', 'distinct'],
        ];
    }

    public function toData(): RoleData
    {
        return new RoleData($this->validated('name'), array_values($this->validated('permissions')));
    }
}
