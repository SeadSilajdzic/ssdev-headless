<?php

namespace Ssdev\Headless\Features\Tenancy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ssdev\Headless\Features\Tenancy\Criteria\FilterTenantsByStatus;
use Ssdev\Headless\Features\Tenancy\Criteria\SearchTenants;
use Ssdev\Headless\Features\Tenancy\Criteria\SortTenants;
use Ssdev\Headless\Features\Tenancy\Criteria\TenantCriteria;
use Ssdev\Headless\Features\Tenancy\TenantRepository;
use Ssdev\Headless\Features\Tenancy\TenantStatus;

final class ListTenantsRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(TenantStatus::class)],
            'sort' => ['nullable', Rule::in(SortTenants::COLUMNS)],
            'direction' => ['nullable', Rule::in(SortTenants::DIRECTIONS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.TenantRepository::MAX_PER_PAGE],
        ];
    }

    /**
     * @return list<TenantCriteria>
     */
    public function criteria(): array
    {
        $filters = $this->filters();
        $criteria = [new SortTenants($filters['sort'], $filters['direction'])];

        if ($filters['search'] !== null && $filters['search'] !== '') {
            $criteria[] = new SearchTenants($filters['search']);
        }

        if ($filters['status'] !== null) {
            $criteria[] = new FilterTenantsByStatus(TenantStatus::from($filters['status']));
        }

        return $criteria;
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? TenantRepository::DEFAULT_PER_PAGE);
    }

    /**
     * @return array{search: ?string, status: ?string, sort: ?string, direction: ?string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'status' => $this->validated('status'),
            'sort' => $this->validated('sort'),
            'direction' => $this->validated('direction'),
        ];
    }
}
