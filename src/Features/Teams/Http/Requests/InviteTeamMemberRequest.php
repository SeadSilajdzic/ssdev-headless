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
    public function rules(FeatureRegistry $features): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            // Without the roles feature there are no team roles, so a role in the request is left out.
            'role' => $features->isEnabled(Feature::Roles->value) ? ['nullable', 'integer'] : ['exclude'],
        ];
    }

    public function email(): string
    {
        return (string) $this->validated('email');
    }

    public function roleId(): ?int
    {
        $role = $this->validated('role');

        return $role === null ? null : (int) $role;
    }
}
