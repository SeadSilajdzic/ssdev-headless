<?php

use Ssdev\Headless\Installation\Steps\PrepareUserModel;
use Ssdev\Headless\Installation\StepStatus;

const LARAVEL_12_USER = <<<'PHP'
<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
PHP;

function expectValidPhp(string $code): void
{
    expect(fn () => token_get_all($code, TOKEN_PARSE))->not->toThrow(ParseError::class);
}

it('adds HasRoles and MustVerifyEmail to the Laravel 13 user model', function () {
    $files = fixtureProject();

    $result = app(PrepareUserModel::class)->handle(installContext($files));
    $code = $files->get('app/Models/User.php');

    expect($result->status)->toBe(StepStatus::Changed)
        ->and($code)->toContain("\nuse Spatie\\Permission\\Traits\\HasRoles;\n")
        ->toContain('    use HasApiTokens, HasFactory, HasRoles, Notifiable;')
        ->toContain("\nuse Illuminate\\Contracts\\Auth\\MustVerifyEmail;\n")
        ->toContain("class User extends Authenticatable implements MustVerifyEmail\n")
        ->toContain("#[Fillable(['name', 'email', 'password'])]")
        ->not->toContain('// use Illuminate\Contracts\Auth\MustVerifyEmail;');
    expectValidPhp($code);
});

it('handles the Laravel 12 user model', function () {
    $files = fixtureProject();
    $files->put('app/Models/User.php', LARAVEL_12_USER);

    expect(app(PrepareUserModel::class)->handle(installContext($files))->status)->toBe(StepStatus::Changed);

    $code = $files->get('app/Models/User.php');
    expect($code)->toContain('    use HasApiTokens, HasFactory, HasRoles, Notifiable;')
        ->toContain('class User extends Authenticatable implements MustVerifyEmail')
        ->toContain("    protected \$fillable = [\n        'name',");
    expectValidPhp($code);
});

it('appends MustVerifyEmail to an existing implements list', function () {
    $files = fixtureProject();
    $files->put('app/Models/User.php', str_replace('class User extends Authenticatable', "use App\\Contracts\\HasAvatar;\n\nclass User extends Authenticatable implements HasAvatar", LARAVEL_12_USER));

    app(PrepareUserModel::class)->handle(installContext($files));

    expect($files->get('app/Models/User.php'))->toContain('class User extends Authenticatable implements HasAvatar, MustVerifyEmail');
    expectValidPhp($files->get('app/Models/User.php'));
});

it('leaves out MustVerifyEmail when email verification is disabled', function () {
    config(['headless.email_verification' => false]);
    $files = fixtureProject();

    app(PrepareUserModel::class)->handle(installContext($files));
    $code = $files->get('app/Models/User.php');

    expect($code)->toContain('use HasApiTokens, HasFactory, HasRoles, Notifiable;')
        ->not->toContain('implements MustVerifyEmail')
        ->toContain('// use Illuminate\Contracts\Auth\MustVerifyEmail;');
});

it('is idempotent', function () {
    $files = fixtureProject();
    app(PrepareUserModel::class)->handle(installContext($files));
    $code = $files->get('app/Models/User.php');

    expect(app(PrepareUserModel::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped)
        ->and($files->get('app/Models/User.php'))->toBe($code);
});

it('asks for manual changes when the user model is unrecognisable', function () {
    $files = fixtureProject();
    $files->put('app/Models/User.php', "<?php\n\nclass Member {}\n");

    $result = app(PrepareUserModel::class)->handle(installContext($files));

    expect($result->status)->toBe(StepStatus::Manual)
        ->and($result->instructions)->toContain('HasRoles')
        ->and($files->get('app/Models/User.php'))->toBe("<?php\n\nclass Member {}\n");
});

it('asks for manual changes when the user model is missing', function () {
    $files = fixtureProject();
    $files->delete('app/Models/User.php');

    expect(app(PrepareUserModel::class)->handle(installContext($files))->status)->toBe(StepStatus::Manual);
});

it('adds HasApiTokens to the user model', function () {
    $files = fixtureProject();

    app(PrepareUserModel::class)->handle(installContext($files));
    $code = $files->get('app/Models/User.php');

    expect($code)->toContain("\nuse Laravel\Sanctum\HasApiTokens;\n")
        ->toContain('    use HasApiTokens, HasFactory, HasRoles, Notifiable;');
    expectValidPhp($code);
    expect(app(PrepareUserModel::class)->handle(installContext($files))->status)->toBe(StepStatus::Skipped);
});
