<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Workbench\App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(fn () => Storage::fake('public'));

function uploadAvatar(User $user, UploadedFile $file): TestResponse
{
    return actingAs($user)->post(route('headless.avatar.store'), ['avatar' => $file]);
}

it('stores an uploaded avatar under a generated name', function () {
    $user = User::factory()->create();

    uploadAvatar($user, UploadedFile::fake()->image('my-face.png', 200, 200))->assertSessionHasNoErrors()->assertRedirect();

    $path = $user->fresh()->avatar_path;
    expect($path)->toMatch('#^avatars/[A-Za-z0-9]{40}\.png$#');
    Storage::disk('public')->assertExists($path);
});

it('shares the avatar url with the frontend', function () {
    $user = User::factory()->create();
    uploadAvatar($user, UploadedFile::fake()->image('me.png', 100, 100));

    actingAs($user->fresh())->get(route('headless.profile.edit'))->assertInertia(fn (AssertableInertia $page) => $page
        ->where('auth.user.avatar_url', Storage::disk('public')->url($user->fresh()->avatar_path)));
});

it('replaces the previous avatar file', function () {
    $user = User::factory()->create();
    uploadAvatar($user, UploadedFile::fake()->image('first.png', 100, 100));
    $first = $user->fresh()->avatar_path;

    uploadAvatar($user->fresh(), UploadedFile::fake()->image('second.png', 100, 100));

    Storage::disk('public')->assertMissing($first);
    Storage::disk('public')->assertExists($user->fresh()->avatar_path);
});

it('removes the avatar', function () {
    $user = User::factory()->create();
    uploadAvatar($user, UploadedFile::fake()->image('me.png', 100, 100));
    $path = $user->fresh()->avatar_path;

    actingAs($user->fresh())->delete(route('headless.avatar.destroy'))->assertRedirect();

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('rejects svg files', function () {
    $user = User::factory()->create();
    $svg = UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

    uploadAvatar($user, $svg)->assertSessionHasErrors('avatar');

    expect($user->fresh()->avatar_path)->toBeNull()->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('rejects files that are not real images', function () {
    $user = User::factory()->create();

    uploadAvatar($user, UploadedFile::fake()->createWithContent('fake.png', 'not an image'))->assertSessionHasErrors('avatar');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('rejects avatars over the size limit', function () {
    config(['headless.avatars.max_kilobytes' => 100]);
    $user = User::factory()->create();

    uploadAvatar($user, UploadedFile::fake()->image('big.png', 100, 100)->size(200))->assertSessionHasErrors('avatar');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('rejects avatars with excessive dimensions', function () {
    config(['headless.avatars.max_dimension' => 300]);
    $user = User::factory()->create();

    uploadAvatar($user, UploadedFile::fake()->image('wide.png', 400, 400))->assertSessionHasErrors('avatar');

    expect(Storage::disk('public')->allFiles())->toBe([]);
});
