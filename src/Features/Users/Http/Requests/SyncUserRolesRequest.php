<?php

namespace Ssdev\Headless\Features\Users\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SyncUserRolesRequest extends FormRequest
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
            'roles' => ['present', 'array'],
            'roles.*' => ['integer', 'distinct'],
            'known_roles' => ['sometimes', 'array'],
            'known_roles.*' => ['integer'],
        ];
    }

    /**
     * @return list<int>
     */
    public function roleIds(): array
    {
        return array_values(array_map('intval', $this->validated('roles')));
    }

    /**
     * @return list<int>|null
     */
    public function knownRoleIds(): ?array
    {
        $known = $this->validated('known_roles');

        return $known === null ? null : array_values(array_map('intval', $known));
    }
}
