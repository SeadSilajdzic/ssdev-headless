<?php

use Illuminate\Support\Facades\DB;
use Ssdev\Headless\Account\UserRepository;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantContext;
use Workbench\App\Models\Note;

function noteIn(?Tenant $tenant, string $title): Note
{
    $create = fn (): Note => Note::query()->create(['title' => $title]);

    return $tenant === null ? $create() : $tenant->execute($create);
}

it('reads only the current tenant\'s rows', function () {
    $acme = makeTenant('acme');
    noteIn($acme, 'Acme plan');
    noteIn(makeTenant('globex'), 'Globex plan');
    noteIn(null, 'Central plan');

    expect($acme->execute(fn () => Note::query()->pluck('title')->all()))->toBe(['Acme plan']);
});

it('reads only central rows on the central level', function () {
    noteIn(makeTenant('acme'), 'Acme plan');
    noteIn(null, 'Central plan');

    expect(Note::query()->pluck('title')->all())->toBe(['Central plan']);
});

it('fills the tenant from the context and ignores a given tenant_id', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');

    $note = $acme->execute(fn () => Note::query()->forceCreate(['title' => 'Plan', TenantContext::COLUMN => $globex->id]));

    expect($note->getAttribute(TenantContext::COLUMN))->toBe($acme->id);
});

it('refuses moving a row to another tenant', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $note = noteIn($acme, 'Plan');

    expect(fn () => $acme->execute(fn () => $note->forceFill([TenantContext::COLUMN => $globex->id])->save()))->toThrow(LogicException::class);
});

it('limits mass updates and deletes to the current tenant', function () {
    $acme = makeTenant('acme');
    noteIn($acme, 'Acme plan');
    noteIn(makeTenant('globex'), 'Globex plan');

    $acme->execute(function (): void {
        Note::query()->update(['title' => 'Changed']);
        Note::query()->delete();
    });

    expect(DB::table('notes')->pluck('title')->all())->toBe(['Globex plan']);
});

it('finds the account of the current tenant by email', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');
    $ada = tenantUser($acme, ['email' => 'ada@example.com']);
    $otherAda = tenantUser($globex, ['email' => 'ada@example.com']);
    $find = fn () => app(UserRepository::class)->findByEmail('ada@example.com')?->getKey();

    expect($acme->execute($find))->toBe($ada->getKey())
        ->and($globex->execute($find))->toBe($otherAda->getKey())
        ->and($find())->toBeNull();
});
