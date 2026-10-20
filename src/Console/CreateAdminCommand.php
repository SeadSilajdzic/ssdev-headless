<?php

namespace Ssdev\Headless\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Ssdev\Headless\Account\AdminAccountCreator;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

final class CreateAdminCommand extends Command
{
    protected $signature = 'headless:create-admin
        {--name= : Name of the admin}
        {--email= : Email of the admin}';

    protected $description = 'Create an admin account with every registered permission';

    public function handle(AdminAccountCreator $creator, ValidatorFactory $validator): int
    {
        // The password is only read from a hidden prompt, never from an option that would stay in the shell history.
        if (! $this->input->isInteractive()) {
            $this->components->error('Run this command interactively, the password is read from a prompt.');

            return self::FAILURE;
        }

        $account = [
            'name' => trim((string) ($this->option('name') ?: text('Name', required: true))),
            'email' => mb_strtolower(trim((string) ($this->option('email') ?: text('Email', required: true)))),
        ];

        if (! $this->passes($validator, $account, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique((string) config('auth.providers.users.model'), 'email')],
        ])) {
            return self::FAILURE;
        }

        $secret = [
            'password' => password('Password', required: true),
            'password_confirmation' => password('Confirm password', required: true),
        ];

        if (! $this->passes($validator, $secret, ['password' => ['required', 'string', Password::defaults(), 'confirmed']])) {
            return self::FAILURE;
        }

        $creator->create($account['name'], $account['email'], $secret['password']);

        $this->components->info("Created the admin account [{$account['email']}].");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $rules
     */
    private function passes(ValidatorFactory $validator, array $data, array $rules): bool
    {
        $validation = $validator->make($data, $rules);

        foreach ($validation->errors()->all() as $message) {
            $this->components->error($message);
        }

        return ! $validation->fails();
    }
}
