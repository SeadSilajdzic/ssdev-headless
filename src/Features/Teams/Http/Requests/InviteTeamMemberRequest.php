<?php

namespace Ssdev\Headless\Features\Teams\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;

final class InviteTeamMemberRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255'],
            'role' => ['nullable', 'integer'],
        ];
    }

    public function email(): string
    {
        return (string) $this->validated('email');
    }

    // Without the roles feature there are no team roles, so a role in the request is ignored.
    public function roleId(): ?int
    {
        $role = $this->validated('role');

        return $role === null || ! app(FeatureRegistry::class)->isEnabled(Feature::Roles->value) ? null : (int) $role;
    }
}
