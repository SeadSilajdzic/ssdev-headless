<?php

use Illuminate\Support\Facades\Artisan;
use Ssdev\Headless\Features\Tenancy\Tenant;
use Ssdev\Headless\Features\Tenancy\TenantContext;
use Workbench\App\Jobs\RecordTenant;

beforeEach(function () {
    config(['queue.default' => 'database']);
    RecordTenant::$seen = [];
});

function workOneJob(): void
{
    // A worker starts on the central level, whatever the previous job's tenant was.
    Tenant::forgetCurrent();
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);
}

it('runs each job in the tenant it was dispatched from', function () {
    $acme = makeTenant('acme');
    $globex = makeTenant('globex');

    // A block closure: an arrow function would hand the pending dispatch back, and it would only be sent after the tenant was left.
    $acme->execute(function (): void {
        RecordTenant::dispatch();
    });
    $globex->execute(function (): void {
        RecordTenant::dispatch();
    });
    workOneJob();
    workOneJob();

    expect(RecordTenant::$seen)->toBe([
        [$acme->id, 'http://acme.localhost/login'],
        [$globex->id, 'http://globex.localhost/login'],
    ]);
});

it('sends a job dispatched from an arrow function in its tenant', function () {
    $acme = makeTenant('acme');

    $acme->execute(fn () => RecordTenant::dispatch());
    workOneJob();

    expect(RecordTenant::$seen)->toBe([[$acme->id, 'http://acme.localhost/login']]);
});

it('runs a job dispatched on the central level centrally', function () {
    makeTenant('acme');

    RecordTenant::dispatch();
    workOneJob();

    expect(RecordTenant::$seen)->toBe([[TenantContext::CENTRAL, 'http://localhost/login']]);
});
