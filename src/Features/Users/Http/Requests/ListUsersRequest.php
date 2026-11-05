<?php

namespace Ssdev\Headless\Features\Users\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Ssdev\Headless\Features\Feature;
use Ssdev\Headless\Features\FeatureRegistry;
use Ssdev\Headless\Features\Roles\RolePermission;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByRegistrationDate;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByRole;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByStatus;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByVerifiedEmail;
use Ssdev\Headless\Features\Users\Criteria\SearchUsers;
use Ssdev\Headless\Features\Users\Criteria\SortUsers;
use Ssdev\Headless\Features\Users\Criteria\UserCriteria;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Features\Users\UserStatus;

final class ListUsersRequest extends FormRequest
{
    private const DATE_FORMAT = 'Y-m-d';

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
            'status' => ['nullable', Rule::enum(UserStatus::class)],
            'verified' => ['nullable', 'boolean'],
            'role' => ['nullable', 'integer'],
            'registered_from' => ['nullable', 'date_format:'.self::DATE_FORMAT],
            'registered_to' => ['nullable', 'date_format:'.self::DATE_FORMAT, 'after_or_equal:registered_from'],
            'sort' => ['nullable', Rule::in(SortUsers::COLUMNS)],
            'direction' => ['nullable', Rule::in(SortUsers::DIRECTIONS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:'.ManagedUserRepository::MAX_PER_PAGE],
        ];
    }

    /**
     * @return list<UserCriteria>
     */
    public function criteria(): array
    {
        $filters = $this->filters();
        $criteria = [new SortUsers($filters['sort'], $filters['direction'])];

        if ($filters['search'] !== null && $filters['search'] !== '') {
            $criteria[] = new SearchUsers($filters['search']);
        }

        if ($filters['status'] !== null) {
            $criteria[] = new FilterUsersByStatus(UserStatus::from($filters['status']));
        }

        if ($filters['verified'] !== null) {
            $criteria[] = new FilterUsersByVerifiedEmail($filters['verified']);
        }

        // Ignored without the roles feature, and without roles.view, as the result would tell who holds the role.
        if ($filters['role'] !== null && app(FeatureRegistry::class)->isEnabled(Feature::Roles->value) && $this->user()->can(RolePermission::View->value)) {
            $criteria[] = new FilterUsersByRole($filters['role']);
        }

        if ($filters['registered_from'] !== null || $filters['registered_to'] !== null) {
            $criteria[] = new FilterUsersByRegistrationDate($this->parseDate($filters['registered_from']), $this->parseDate($filters['registered_to']));
        }

        return $criteria;
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? ManagedUserRepository::DEFAULT_PER_PAGE);
    }

    /**
     * @return array{search: ?string, status: ?string, verified: ?bool, role: ?int, registered_from: ?string, registered_to: ?string, sort: ?string, direction: ?string}
     */
    public function filters(): array
    {
        $verified = $this->validated('verified');
        $role = $this->validated('role');

        return [
            'search' => $this->validated('search'),
            'status' => $this->validated('status'),
            'verified' => $verified === null ? null : filter_var($verified, FILTER_VALIDATE_BOOLEAN),
            'role' => $role === null ? null : (int) $role,
            'registered_from' => $this->validated('registered_from'),
            'registered_to' => $this->validated('registered_to'),
            'sort' => $this->validated('sort'),
            'direction' => $this->validated('direction'),
        ];
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        return $value === null ? null : (CarbonImmutable::createFromFormat(self::DATE_FORMAT, $value) ?: null);
    }
}
