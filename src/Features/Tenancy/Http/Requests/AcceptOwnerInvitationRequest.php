<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class AcceptOwnerInvitationRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', Password::defaults(), 'confirmed'],
        ];
    }

    public function token(): string
    {
        return (string) $this->validated('token');
    }

    public function email(): string
    {
        return (string) $this->validated('email');
    }

    public function name(): string
    {
        return trim((string) $this->validated('name'));
    }

    public function password(): string
    {
        return (string) $this->validated('password');
    }
}
