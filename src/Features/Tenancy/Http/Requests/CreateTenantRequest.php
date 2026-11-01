<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Ssdev\Headless\Features\Tenancy\Rules\AvailableSubdomain;

final class CreateTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        // AvailableSubdomain checks the form the subdomain is stored in.
        if (is_string($this->input('subdomain'))) {
            $this->merge(['subdomain' => mb_strtolower(trim($this->input('subdomain')))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subdomain' => ['bail', 'required', 'string', app(AvailableSubdomain::class)],
            'owner_email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function name(): string
    {
        return (string) $this->validated('name');
    }

    public function subdomain(): string
    {
        return (string) $this->validated('subdomain');
    }

    public function ownerEmail(): string
    {
        return (string) $this->validated('owner_email');
    }
}
