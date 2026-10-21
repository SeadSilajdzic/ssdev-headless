<?php

namespace Ssdev\Headless\Features\Roles\Http\Requests;

use Closure;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;
use Ssdev\Headless\Account\UserRepository;

final class AddRoleMemberRequest extends FormRequest
{
    private ?User $member = null;

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
            'email' => ['bail', 'required', 'string', 'email', function (string $attribute, mixed $value, Closure $fail): void {
                $this->member = app(UserRepository::class)->findByEmail(mb_strtolower(trim((string) $value)));

                if ($this->member === null) {
                    $fail(__('headless::roles.errors.unknown_user'));
                }
            }],
        ];
    }

    public function member(): User
    {
        return $this->member ?? throw new LogicException('The member is known only after validation.');
    }
}
