<?php

use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Process;
use Ssdev\Headless\Installation\Steps\InstallApiContracts;
use Ssdev\Headless\Installation\StepStatus;

it('installs the contract hooks and generates the snapshots', function () {
    Process::fake();
    $files = fixtureProject();

    $result = app(InstallApiContracts::class)->handle(installContext($files));

    expect($result->status)->toBe(StepStatus::Changed)
        ->and($files->get('tests/Feature/HeadlessApiContractTest.php'))->toContain('InteractsWithApiContract');
    Process::assertRan(fn (PendingProcess $process) => str_contains((string) $process->command, 'artisan api:contract:install'));
    Process::assertRan(fn (PendingProcess $process) => str_contains((string) $process->command, 'artisan test tests/Feature/HeadlessApiContractTest.php')
        && ($process->environment['API_CONTRACT_UPDATE'] ?? null) === '1');
});

it('leaves an installed contract test and its snapshots alone', function () {
    Process::fake();
    $files = fixtureProject();
    $files->put('tests/Feature/HeadlessApiContractTest.php', '<?php // customised');

    expect(app(InstallApiContracts::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped)
        ->and($files->get('tests/Feature/HeadlessApiContractTest.php'))->toBe('<?php // customised');
    Process::assertNothingRan();
});

it('asks for manual steps when generating the snapshots fails', function () {
    Process::fake([
        '*api:contract:install*' => Process::result(),
        '*artisan test*' => Process::result(exitCode: 1),
    ]);
    $files = fixtureProject();

    $result = app(InstallApiContracts::class)->handle(installContext($files));

    expect($result->status)->toBe(StepStatus::Manual)
        ->and($result->instructions)->toContain('API_CONTRACT_UPDATE=1');
});
