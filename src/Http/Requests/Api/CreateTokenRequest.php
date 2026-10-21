<?php

namespace Ssdev\Headless\Http\Requests\Api;

use Ssdev\Headless\Http\Requests\Settings\CreateApiTokenRequest;

/**
 * The settings page confirms the password before it opens; an API client sends it with the request.
 */
class CreateTokenRequest extends CreateApiTokenRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
