<?php

namespace Ssdev\Headless\Features\Roles\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ssdev\Headless\Features\Roles\Criteria\RoleCriteria;
use Ssdev\Headless\Features\Roles\Criteria\SearchRolesByName;
use Ssdev\Headless\Features\Roles\Criteria\SortRoles;
use Ssdev\Headless\Features\Roles\RoleRepository;

final class ListRolesRequest extends FormRequest
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
            'sort' => ['nullable', Rule::in(SortRoles::COLUMNS)],
            'direction' => ['nullable', Rule::in(SortRoles::DIRECTIONS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.RoleRepository::MAX_PER_PAGE],
        ];
    }

    /**
     * @return list<RoleCriteria>
     */
    public function criteria(): array
    {
        $criteria = [new SortRoles($this->validated('sort'), $this->validated('direction'))];
        $search = $this->validated('search');

        if (is_string($search) && $search !== '') {
            $criteria[] = new SearchRolesByName($search);
        }

        return $criteria;
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? RoleRepository::DEFAULT_PER_PAGE);
    }

    /**
     * @return array{search: ?string, sort: ?string, direction: ?string}
     */
    public function filters(): array
    {
        return [
            'search' => $this->validated('search'),
            'sort' => $this->validated('sort'),
            'direction' => $this->validated('direction'),
        ];
    }
}
