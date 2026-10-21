<?php

namespace Ssdev\Headless\Features\TwoFactor\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * API clients have no password confirmation page, so changing two-factor settings asks for the password itself.
 */
final class TwoFactorPasswordRequest extends FormRequest
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
            'password' => ['required', 'string', 'current_password'],
        ];
    }
}
