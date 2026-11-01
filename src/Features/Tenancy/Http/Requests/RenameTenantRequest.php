<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Only the name changes; the subdomain stays as it was created.
 */
final class RenameTenantRequest extends FormRequest
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
        ];
    }

    public function name(): string
    {
        return (string) $this->validated('name');
    }
}
