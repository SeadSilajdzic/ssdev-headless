<?php

namespace Ssdev\Headless\Http\Requests\Settings;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ssdev\Headless\Account\UserRepository;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(UserRepository $users): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'bail', 'required', 'string', 'email', 'max:255',
                // Through the model, so its scopes apply: with tenancy an email only needs to be free within the tenant.
                function (string $attribute, mixed $value, Closure $fail) use ($users): void {
                    if ($users->emailTaken((string) $value, $this->user()?->getAuthIdentifier())) {
                        $fail(__('validation.unique', ['attribute' => $attribute]));
                    }
                },
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
