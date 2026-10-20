<?php

namespace Ssdev\Headless\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique((string) config('auth.providers.users.model'), 'email')->ignore($this->user()?->getAuthIdentifier()),
            ],
            // Changing the email is a sensitive action, so it needs the password in the same request.
            'current_password' => [Rule::requiredIf(fn () => $this->changesEmail()), 'nullable', 'string', 'current_password'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => mb_strtolower(trim($email))]);
        }
    }

    private function changesEmail(): bool
    {
        return $this->input('email') !== $this->user()?->getAttribute('email');
    }
}
