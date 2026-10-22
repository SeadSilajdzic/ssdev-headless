<?php

namespace Ssdev\Headless\Features\Tenancy\Rules;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Validation\ValidationRule;
use Ssdev\Headless\Features\Tenancy\Tenant;

/**
 * A DNS label of lowercase letters, digits and hyphens that is neither reserved nor taken; lowercase the input first.
 */
final class AvailableSubdomain implements ValidationRule
{
    private const FORMAT = '/^(?!-)[a-z0-9-]{3,63}(?<!-)$/';

    public function __construct(private Repository $config) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match(self::FORMAT, $value) !== 1) {
            $fail(__('headless::tenancy.errors.subdomain_format'));

            return;
        }

        if (in_array($value, $this->config->get('headless.tenancy.reserved_subdomains', []), true)) {
            $fail(__('headless::tenancy.errors.subdomain_reserved'));

            return;
        }

        if (Tenant::query()->where('subdomain', $value)->exists()) {
            $fail(__('headless::tenancy.errors.subdomain_taken'));
        }
    }
}
