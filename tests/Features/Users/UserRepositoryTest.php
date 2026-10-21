<?php

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\Permission\Models\Role;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByRegistrationDate;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByRole;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByStatus;
use Ssdev\Headless\Features\Users\Criteria\FilterUsersByVerifiedEmail;
use Ssdev\Headless\Features\Users\Criteria\SearchUsers;
use Ssdev\Headless\Features\Users\Criteria\SortUsers;
use Ssdev\Headless\Features\Users\Criteria\UserCriteria;
use Ssdev\Headless\Features\Users\ManagedUserRepository;
use Ssdev\Headless\Features\Users\UserStatus;
use Workbench\App\Models\User;

/**
 * @param  list<UserCriteria>  $criteria
 * @return list<string>
 */
function listedUserEmails(array $criteria): array
{
    return app(ManagedUserRepository::class)->paginate($criteria, ManagedUserRepository::MAX_PER_PAGE)->getCollection()->pluck('email')->all();
}

it('searches users by name and email', function () {
    User::factory()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
    User::factory()->create(['name' => 'Grace Hopper', 'email' => 'grace@navy.mil']);

    expect(listedUserEmails([new SearchUsers('lovelace')]))->toBe(['ada@example.com'])
        ->and(listedUserEmails([new SearchUsers('navy')]))->toBe(['grace@navy.mil']);
});

it('treats wildcards in a search as text', function () {
    User::factory()->create(['name' => 'Done 100%', 'email' => 'percent@example.com']);
    User::factory()->create(['name' => 'Done 1000', 'email' => 'digits@example.com']);

    expect(listedUserEmails([new SearchUsers('100%')]))->toBe(['percent@example.com']);
});

it('tells the status of a user', function () {
    expect(UserStatus::of(User::factory()->make()))->toBe(UserStatus::Active)
        ->and(UserStatus::of(User::factory()->make(['invited_at' => now()])))->toBe(UserStatus::Invited)
        ->and(UserStatus::of(User::factory()->make(['invited_at' => now(), 'blocked_at' => now()])))->toBe(UserStatus::Blocked);
});

it('filters by status', function () {
    User::factory()->create(['email' => 'active@example.com']);
    User::factory()->create(['email' => 'invited@example.com', 'invited_at' => now()]);
    User::factory()->create(['email' => 'blocked@example.com', 'blocked_at' => now()]);
    User::factory()->create(['email' => 'blocked-invitee@example.com', 'invited_at' => now(), 'blocked_at' => now()]);

    expect(listedUserEmails([new FilterUsersByStatus(UserStatus::Active)]))->toBe(['active@example.com'])
        ->and(listedUserEmails([new FilterUsersByStatus(UserStatus::Invited)]))->toBe(['invited@example.com'])
        ->and(listedUserEmails([new FilterUsersByStatus(UserStatus::Blocked)]))->toEqualCanonicalizing(['blocked@example.com', 'blocked-invitee@example.com']);
});

it('filters by verified email', function () {
    User::factory()->create(['email' => 'verified@example.com']);
    User::factory()->create(['email' => 'unverified@example.com', 'email_verified_at' => null]);

    expect(listedUserEmails([new FilterUsersByVerifiedEmail(true)]))->toBe(['verified@example.com'])
        ->and(listedUserEmails([new FilterUsersByVerifiedEmail(false)]))->toBe(['unverified@example.com']);
});

it('filters by role', function () {
    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    User::factory()->create(['email' => 'editor@example.com'])->assignRole($role);
    User::factory()->create(['email' => 'reader@example.com']);

    expect(listedUserEmails([new FilterUsersByRole($role->getKey())]))->toBe(['editor@example.com']);
});

it('filters by registration date including both days', function () {
    User::factory()->create(['email' => 'first@example.com', 'created_at' => '2026-01-01 00:00:00']);
    User::factory()->create(['email' => 'second@example.com', 'created_at' => '2026-01-02 23:59:59']);
    User::factory()->create(['email' => 'third@example.com', 'created_at' => '2026-01-03 00:00:00']);

    $between = new FilterUsersByRegistrationDate(CarbonImmutable::parse('2026-01-01'), CarbonImmutable::parse('2026-01-02'));

    expect(listedUserEmails([$between]))->toEqualCanonicalizing(['first@example.com', 'second@example.com'])
        ->and(listedUserEmails([new FilterUsersByRegistrationDate(CarbonImmutable::parse('2026-01-03'), null)]))->toBe(['third@example.com'])
        ->and(listedUserEmails([new FilterUsersByRegistrationDate(null, CarbonImmutable::parse('2026-01-01'))]))->toBe(['first@example.com']);
});

it('sorts newest first by default and by whitelisted columns only', function () {
    User::factory()->create(['name' => 'Bea', 'email' => 'old@example.com', 'created_at' => '2026-01-01 00:00:00']);
    User::factory()->create(['name' => 'Ada', 'email' => 'new@example.com', 'created_at' => '2026-02-01 00:00:00']);

    expect(listedUserEmails([new SortUsers(null, null)]))->toBe(['new@example.com', 'old@example.com'])
        ->and(listedUserEmails([new SortUsers('name', 'desc')]))->toBe(['old@example.com', 'new@example.com'])
        ->and(listedUserEmails([new SortUsers('password', null)]))->toBe(['new@example.com', 'old@example.com']);
});

it('caps the page size', function () {
    expect(app(ManagedUserRepository::class)->paginate([], 500)->perPage())->toBe(ManagedUserRepository::MAX_PER_PAGE);
});

it('leaves deleted users out', function () {
    User::factory()->create(['email' => 'kept@example.com']);
    $deleted = User::factory()->create(['email' => 'deleted@example.com']);
    $deleted->delete();

    expect(listedUserEmails([]))->toBe(['kept@example.com'])
        ->and(fn () => app(ManagedUserRepository::class)->findOrFail($deleted->getKey()))->toThrow(ModelNotFoundException::class);
});
