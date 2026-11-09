<?php

use Illuminate\Filesystem\Filesystem;
use Ssdev\Headless\Installation\ProjectFiles;
use Ssdev\Headless\Installation\Steps\LinkPublicStorage;
use Ssdev\Headless\Installation\StepStatus;

it('links public/storage so uploaded avatars can be served', function () {
    $files = fixtureProject();

    $result = app(LinkPublicStorage::class)->handle(installContext($files));
    $files->put('storage/app/public/avatars/photo.txt', 'photo');

    expect($result->status)->toBe(StepStatus::Changed)
        ->and($files->get('public/storage/avatars/photo.txt'))->toBe('photo');
});

it('leaves an existing public/storage alone', function () {
    $files = fixtureProject();
    $files->put('public/storage/.gitignore', '*');

    expect(app(LinkPublicStorage::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped);
});

it('skips the link when avatars are stored on a disk that is not local', function () {
    config(['headless.avatars.disk' => 's3', 'filesystems.disks.s3.driver' => 's3']);
    $files = fixtureProject();

    expect(app(LinkPublicStorage::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped)
        ->and($files->exists('public/storage'))->toBeFalse();
});

it('asks for storage:link when the link could not be created', function () {
    $filesystem = Mockery::mock(Filesystem::class)->makePartial();
    $filesystem->shouldReceive('link');
    $files = new ProjectFiles(fixtureProject()->path(''), $filesystem);

    $result = app(LinkPublicStorage::class)->handle(installContext($files));

    expect($result->status)->toBe(StepStatus::Manual)
        ->and($result->instructions)->toBe('Run: php artisan storage:link');
});
