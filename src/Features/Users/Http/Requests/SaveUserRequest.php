<?php

namespace Ssdev\Headless\Features\Users\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Ssdev\Headless\Account\UserRepository;

/**
 * Invites a new user, or changes one when the route names a user.
 */
final class SaveUserRequest extends FormRequest
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
            'email' => ['bail', 'required', 'string', 'email', 'max:255', function (string $attribute, mixed $value, Closure $fail): void {
                $user = $this->route('user');

                if (app(UserRepository::class)->emailTaken($this->email(), is_string($user) ? $user : null)) {
                    $fail(__('validation.unique', ['attribute' => $attribute]));
                }
            }],
        ];
    }

    public function name(): string
    {
        return (string) $this->validated('name');
    }

    public function email(): string
    {
        return mb_strtolower(trim((string) $this->input('email')));
    }
}
