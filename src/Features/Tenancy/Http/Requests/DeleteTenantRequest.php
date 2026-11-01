<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Ssdev\Headless\Features\Tenancy\TenantRepository;

/**
 * The subdomain typed again, so a slip never deletes a tenant.
 */
final class DeleteTenantRequest extends FormRequest
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
            'subdomain' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                $tenant = app(TenantRepository::class)->findOrFail((string) $this->route('tenant'));

                if (mb_strtolower(trim((string) $value)) !== $tenant->subdomain) {
                    $fail(__('headless::tenancy.errors.delete_confirmation'));
                }
            }],
        ];
    }
}
