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
        ];
    }

    /**
     * @return list<int>
     */
    public function roleIds(): array
    {
        return array_values(array_map('intval', $this->validated('roles')));
    }
}
