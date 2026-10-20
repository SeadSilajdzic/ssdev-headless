<?php

namespace Ssdev\Headless\Account;

use Ssdev\Headless\Http\Requests\Auth\RegisterUserRequest;

final readonly class RegistrationData
{
    public function __construct(
        public string $name,
        public string $email,
        #[\SensitiveParameter]
        public string $password,
    ) {}

    public static function fromRequest(RegisterUserRequest $request): self
    {
        $data = $request->validated();

        return new self($data['name'], $data['email'], $data['password']);
    }
}
